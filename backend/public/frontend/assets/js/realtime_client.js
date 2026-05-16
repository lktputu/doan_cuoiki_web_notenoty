(() => {
  const Api = window.NoteNotyApi;
  const Actions = window.NoteWiseActions;
  const WS_BASE_KEY = "notenoty_realtime_ws_base";
  const DEFAULT_WS_BASES = [
    "ws://127.0.0.1:8011",
    "ws://localhost:8011"
  ];

  if (!Api || !Actions) return;

  let socket = null;
  let reconnectTimer = null;
  let subscribeTimer = null;
  let reconnectAttempt = 0;
  let lastSubscriptionKey = "";

  function getConfiguredBases() {
    const configuredBase = window.NoteNotyConfig?.realtimeWsBase;
    const stored = localStorage.getItem(WS_BASE_KEY);
    const bases = [];

    if (configuredBase && configuredBase !== "auto") {
      bases.push(configuredBase);
    }

    if (stored) {
      bases.push(stored);
    }

    if (window.location.protocol.startsWith("http")) {
      const scheme = window.location.protocol === "https:" ? "wss" : "ws";
      bases.push(`${scheme}://${window.location.hostname}:8011`);
    }

    bases.push(...DEFAULT_WS_BASES);
    return [...new Set(bases.map(base => base.replace(/\/$/, "")))];
  }

  function currentBase() {
    return getConfiguredBases()[0];
  }

  function makeUrl(base) {
    const url = new URL(base);
    url.searchParams.set("token", Api.getSession()?.token || "");
    url.searchParams.set("clientId", Api.getClientId());
    return url.toString();
  }

  function send(payload) {
    if (!socket || socket.readyState !== WebSocket.OPEN) return false;
    socket.send(JSON.stringify(payload));
    return true;
  }

  function scheduleSubscribe(delay = 120) {
    clearTimeout(subscribeTimer);
    subscribeTimer = window.setTimeout(() => {
      const noteIds = Actions.getRealtimeNoteIds();
      const key = noteIds.map(String).sort().join(",");
      if (key === lastSubscriptionKey) return;
      if (send({ type: "subscribe", noteIds })) {
        lastSubscriptionKey = key;
      }
    }, delay);
  }

  function scheduleReconnect() {
    clearTimeout(reconnectTimer);
    const delay = Math.min(8000, 700 * Math.max(1, reconnectAttempt));
    reconnectTimer = window.setTimeout(connect, delay);
  }

  function handleMessage(event) {
    let message;
    try {
      message = JSON.parse(event.data);
    } catch (error) {
      return;
    }

    if (message.type === "hello") {
      scheduleSubscribe(0);
      return;
    }

    if (message.type === "note-event") {
      Actions.handleRealtimeNoteEvent(message);
    }
  }

  function connectWithBase(base, fallbackBases) {
    try {
      socket = new WebSocket(makeUrl(base));
    } catch (error) {
      const next = fallbackBases.shift();
      if (next) connectWithBase(next, fallbackBases);
      return;
    }

    socket.addEventListener("open", () => {
      reconnectAttempt = 0;
      localStorage.setItem(WS_BASE_KEY, base);
      scheduleSubscribe(0);
    });

    socket.addEventListener("message", handleMessage);

    socket.addEventListener("close", () => {
      socket = null;
      lastSubscriptionKey = "";
      reconnectAttempt += 1;
      scheduleReconnect();
    });

    socket.addEventListener("error", () => {
      const next = fallbackBases.shift();
      if (socket?.readyState === WebSocket.CONNECTING && next) {
        socket.close();
        connectWithBase(next, fallbackBases);
      }
    });
  }

  function connect() {
    if (!Api.getSession()?.token) return;
    if (socket && [WebSocket.CONNECTING, WebSocket.OPEN].includes(socket.readyState)) return;

    const bases = getConfiguredBases();
    const first = bases.shift() || currentBase();
    connectWithBase(first, bases);
  }

  window.NoteWiseRealtime = {
    connect,
    refreshSubscriptions: () => scheduleSubscribe(0)
  };

  window.addEventListener("notenoty:state-changed", () => scheduleSubscribe(250));
  window.addEventListener("focus", () => {
    connect();
    scheduleSubscribe(0);
  });

  connect();
})();
