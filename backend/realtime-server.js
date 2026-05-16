const http = require("http");
const https = require("https");
const crypto = require("crypto");

const PORT = Number(process.env.NOTE_NOTY_REALTIME_PORT || 8011);
const API_BASE = (process.env.NOTE_NOTY_API_BASE || "http://127.0.0.1:8000/api").replace(/\/$/, "");
const SECRET = process.env.NOTE_NOTY_REALTIME_SECRET || "notenoty-local-realtime-secret";
const clients = new Map();

function jsonResponse(response, status, payload) {
  const body = JSON.stringify(payload);
  response.writeHead(status, {
    "Content-Type": "application/json",
    "Access-Control-Allow-Origin": "*",
    "Access-Control-Allow-Methods": "GET, POST, OPTIONS",
    "Access-Control-Allow-Headers": "Content-Type, X-NoteNoty-Realtime-Secret"
  });
  response.end(body);
}

function readJson(request) {
  return new Promise((resolve, reject) => {
    let body = "";
    request.on("data", chunk => {
      body += chunk;
      if (body.length > 1024 * 1024) {
        request.destroy();
        reject(new Error("Payload too large."));
      }
    });
    request.on("end", () => {
      try {
        resolve(body ? JSON.parse(body) : {});
      } catch (error) {
        reject(error);
      }
    });
    request.on("error", reject);
  });
}

function postJson(url, headers, payload) {
  return new Promise((resolve, reject) => {
    const parsed = new URL(url);
    const transport = parsed.protocol === "https:" ? https : http;
    const body = JSON.stringify(payload || {});

    const request = transport.request({
      method: "POST",
      hostname: parsed.hostname,
      port: parsed.port || (parsed.protocol === "https:" ? 443 : 80),
      path: `${parsed.pathname}${parsed.search}`,
      headers: {
        "Accept": "application/json",
        "Content-Type": "application/json",
        "Content-Length": Buffer.byteLength(body),
        ...headers
      },
      timeout: 3500
    }, response => {
      let responseBody = "";
      response.on("data", chunk => responseBody += chunk);
      response.on("end", () => {
        try {
          const data = responseBody ? JSON.parse(responseBody) : {};
          if (response.statusCode >= 400 || data.success === false) {
            reject(new Error(data.message || `Laravel API returned ${response.statusCode}.`));
            return;
          }
          resolve(data);
        } catch (error) {
          reject(error);
        }
      });
    });

    request.on("timeout", () => request.destroy(new Error("Laravel API timeout.")));
    request.on("error", reject);
    request.write(body);
    request.end();
  });
}

async function authorizeNotes(token, noteIds) {
  if (!token) {
    return { userId: null, noteIds: [] };
  }

  const uniqueIds = [...new Set((noteIds || []).map(id => String(id)).filter(Boolean))];
  const data = await postJson(`${API_BASE}/realtime/authorize`, {
    Authorization: `Bearer ${token}`
  }, {
    note_ids: uniqueIds
  });

  return {
    userId: data.user_id || null,
    noteIds: (data.note_ids || []).map(id => String(id))
  };
}

function makeFrame(message) {
  const payload = Buffer.from(message);
  const length = payload.length;

  if (length < 126) {
    return Buffer.concat([Buffer.from([0x81, length]), payload]);
  }

  if (length < 65536) {
    const header = Buffer.alloc(4);
    header[0] = 0x81;
    header[1] = 126;
    header.writeUInt16BE(length, 2);
    return Buffer.concat([header, payload]);
  }

  const header = Buffer.alloc(10);
  header[0] = 0x81;
  header[1] = 127;
  header.writeBigUInt64BE(BigInt(length), 2);
  return Buffer.concat([header, payload]);
}

function sendJson(socket, payload) {
  if (!socket.writable) return;
  socket.write(makeFrame(JSON.stringify(payload)));
}

function closeSocket(socket) {
  try {
    socket.end(Buffer.from([0x88, 0x00]));
  } catch (error) {
    socket.destroy();
  }
}

function parseFrames(client, chunk) {
  client.buffer = Buffer.concat([client.buffer, chunk]);

  while (client.buffer.length >= 2) {
    const first = client.buffer[0];
    const second = client.buffer[1];
    const opcode = first & 0x0f;
    const masked = Boolean(second & 0x80);
    let length = second & 0x7f;
    let offset = 2;

    if (length === 126) {
      if (client.buffer.length < offset + 2) return;
      length = client.buffer.readUInt16BE(offset);
      offset += 2;
    } else if (length === 127) {
      if (client.buffer.length < offset + 8) return;
      length = Number(client.buffer.readBigUInt64BE(offset));
      offset += 8;
    }

    const maskOffset = masked ? 4 : 0;
    if (client.buffer.length < offset + maskOffset + length) return;

    let payload = client.buffer.subarray(offset + maskOffset, offset + maskOffset + length);
    if (masked) {
      const mask = client.buffer.subarray(offset, offset + 4);
      payload = Buffer.from(payload.map((byte, index) => byte ^ mask[index % 4]));
    }

    client.buffer = client.buffer.subarray(offset + maskOffset + length);

    if (opcode === 0x8) {
      closeSocket(client.socket);
      return;
    }

    if (opcode === 0x9) {
      sendJson(client.socket, { type: "pong", at: Date.now() });
      continue;
    }

    if (opcode !== 0x1) continue;

    try {
      handleClientMessage(client, JSON.parse(payload.toString("utf8")));
    } catch (error) {
      sendJson(client.socket, { type: "error", message: "Invalid realtime message." });
    }
  }
}

async function handleClientMessage(client, message) {
  if (message.type === "ping") {
    sendJson(client.socket, { type: "pong", at: Date.now() });
    return;
  }

  if (message.type !== "subscribe") {
    return;
  }

  try {
    const authorized = await authorizeNotes(client.token, message.noteIds || []);
    client.userId = authorized.userId;
    client.subscriptions = new Set(authorized.noteIds);
    sendJson(client.socket, {
      type: "subscribed",
      noteIds: [...client.subscriptions],
      at: Date.now()
    });
  } catch (error) {
    client.subscriptions = new Set();
    sendJson(client.socket, {
      type: "error",
      message: "Realtime authorization failed. Please log in again."
    });
  }
}

function broadcastToSubscribers(payload) {
  const noteId = String(payload.noteId || "");
  const userIds = new Set((payload.userIds || []).map(id => String(id)));
  if (!noteId) return 0;

  let sent = 0;
  for (const client of clients.values()) {
    if (!client.subscriptions.has(noteId) && !userIds.has(String(client.userId))) continue;
    sendJson(client.socket, {
      type: "note-event",
      ...payload,
      at: Date.now()
    });
    sent += 1;
  }
  return sent;
}

const server = http.createServer(async (request, response) => {
  if (request.method === "OPTIONS") {
    jsonResponse(response, 204, {});
    return;
  }

  if (request.method === "GET" && request.url === "/health") {
    jsonResponse(response, 200, {
      success: true,
      app: "NoteNoty realtime",
      clients: clients.size
    });
    return;
  }

  if (request.method === "POST" && request.url === "/broadcast") {
    if (request.headers["x-notenoty-realtime-secret"] !== SECRET) {
      jsonResponse(response, 403, { success: false, message: "Invalid realtime secret." });
      return;
    }

    try {
      const payload = await readJson(request);
      const sent = broadcastToSubscribers(payload);
      jsonResponse(response, 200, { success: true, sent });
    } catch (error) {
      jsonResponse(response, 422, { success: false, message: error.message });
    }
    return;
  }

  jsonResponse(response, 404, { success: false, message: "Not found." });
});

server.on("upgrade", (request, socket) => {
  const key = request.headers["sec-websocket-key"];
  if (!key) {
    socket.destroy();
    return;
  }

  const url = new URL(request.url, `http://${request.headers.host}`);
  const clientId = url.searchParams.get("clientId") || crypto.randomUUID();
  const token = url.searchParams.get("token") || "";
  const accept = crypto
    .createHash("sha1")
    .update(`${key}258EAFA5-E914-47DA-95CA-C5AB0DC85B11`)
    .digest("base64");

  socket.write([
    "HTTP/1.1 101 Switching Protocols",
    "Upgrade: websocket",
    "Connection: Upgrade",
    `Sec-WebSocket-Accept: ${accept}`,
    "",
    ""
  ].join("\r\n"));

  const client = {
    id: clientId,
    token,
    socket,
    buffer: Buffer.alloc(0),
    subscriptions: new Set(),
    userId: null
  };

  clients.set(clientId, client);
  sendJson(socket, { type: "hello", clientId, at: Date.now() });

  socket.on("data", chunk => parseFrames(client, chunk));
  socket.on("close", () => clients.delete(clientId));
  socket.on("error", () => clients.delete(clientId));
});

server.listen(PORT, () => {
  console.log(`NoteNoty realtime server listening on ws://127.0.0.1:${PORT}`);
  console.log(`Authorizing subscriptions through ${API_BASE}/realtime/authorize`);
});
