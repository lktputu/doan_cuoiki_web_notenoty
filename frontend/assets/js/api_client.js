(() => {
  const API_BASE_KEY = "notenoty_api_base";
  const SESSION_KEY = "notenoty_session_v1";
  const CLIENT_ID_KEY = "notenoty_client_id_v1";
  const DEFAULT_API_BASE = "http://127.0.0.1:8000/api";
  const FALLBACK_API_BASES = [
    "http://127.0.0.1:8000/api",
    "http://127.0.0.1:8010/api",
    "http://localhost:8000/api",
    "http://localhost:8010/api"
  ];

  function getApiBase() {
    return (localStorage.getItem(API_BASE_KEY) || DEFAULT_API_BASE).replace(/\/$/, "");
  }

  function getApiBases() {
    const bases = [getApiBase()];

    if (window.location.protocol.startsWith("http")) {
      bases.push(`${window.location.origin}/api`);
    }

    bases.push(...FALLBACK_API_BASES);
    return [...new Set(bases.map(base => base.replace(/\/$/, "")))];
  }

  function getSession() {
    try {
      return JSON.parse(localStorage.getItem(SESSION_KEY) || "null");
    } catch (error) {
      return null;
    }
  }

  function setSession(session) {
    localStorage.setItem(SESSION_KEY, JSON.stringify(session));
  }

  function clearSession() {
    localStorage.removeItem(SESSION_KEY);
  }

  function getClientId() {
    let clientId = sessionStorage.getItem(CLIENT_ID_KEY);
    if (!clientId) {
      clientId = `client-${Date.now()}-${Math.random().toString(36).slice(2, 10)}`;
      sessionStorage.setItem(CLIENT_ID_KEY, clientId);
    }
    return clientId;
  }

  function authHeaders() {
    const session = getSession();
    return session?.token
      ? {
          Authorization: `Bearer ${session.token}`,
          "X-NoteNoty-Client-Id": getClientId()
        }
      : {};
  }

  async function fetchWithTimeout(url, options, timeoutMs = 3500) {
    const controller = new AbortController();
    const timeout = window.setTimeout(() => controller.abort(), timeoutMs);

    try {
      return await fetch(url, {
        ...options,
        signal: controller.signal
      });
    } finally {
      window.clearTimeout(timeout);
    }
  }

  async function request(path, options = {}) {
    const { timeoutMs = 3500, headers: optionHeaders = {}, ...fetchOptions } = options;
    const headers = {
      Accept: "application/json",
      ...(fetchOptions.body instanceof FormData ? {} : { "Content-Type": "application/json" }),
      ...authHeaders(),
      ...optionHeaders
    };

    let lastError = null;

    for (const base of getApiBases()) {
      try {
        const response = await fetchWithTimeout(`${base}${path}`, {
          ...fetchOptions,
          headers
        }, timeoutMs);

        const text = await response.text();
        let data = {};
        try {
          data = text ? JSON.parse(text) : {};
        } catch (parseError) {
          data = {
            success: false,
            message: response.status === 413
              ? "Ảnh tải lên quá lớn. Vui lòng chọn ảnh nhỏ hơn hoặc giảm số lượng ảnh."
              : "Máy chủ đang gặp lỗi xử lý. Vui lòng thử lại sau ít phút."
          };
        }

        if (!response.ok || data.success === false) {
          const error = new Error(data.message || "Không thể kết nối máy chủ.");
          error.status = response.status;
          error.payload = data;
          throw error;
        }

        localStorage.setItem(API_BASE_KEY, base);
        return data;
      } catch (error) {
        if (error.name === "AbortError") {
          error = new Error("Kết nối server quá lâu. NoteNoty sẽ giữ dữ liệu offline và thử đồng bộ lại sau.");
        } else if (!error.status) {
          error = new Error("Không kết nối được server. NoteNoty sẽ giữ dữ liệu offline và thử đồng bộ lại sau.");
        }
        lastError = error;
        if (error.status && error.status !== 404) break;
      }
    }

    throw lastError || new Error("Không thể kết nối máy chủ.");
  }

  function imageToDataUrl(file, { maxWidth = 1280, maxHeight = 1280, quality = 0.82, mimeType = "image/jpeg" } = {}) {
    return new Promise((resolve, reject) => {
      if (!file || !file.type?.startsWith("image/")) {
        reject(new Error("Vui lòng chọn đúng định dạng ảnh."));
        return;
      }

      const reader = new FileReader();
      reader.onerror = () => reject(new Error("Không thể đọc ảnh đã chọn."));
      reader.onload = () => {
        const image = new Image();
        image.onerror = () => reject(new Error("Không thể xử lý ảnh đã chọn."));
        image.onload = () => {
          const scale = Math.min(1, maxWidth / image.width, maxHeight / image.height);
          const width = Math.max(1, Math.round(image.width * scale));
          const height = Math.max(1, Math.round(image.height * scale));
          const canvas = document.createElement("canvas");
          canvas.width = width;
          canvas.height = height;

          const context = canvas.getContext("2d");
          context.fillStyle = "#ffffff";
          context.fillRect(0, 0, width, height);
          context.drawImage(image, 0, 0, width, height);
          resolve(canvas.toDataURL(mimeType, quality));
        };
        image.src = reader.result;
      };
      reader.readAsDataURL(file);
    });
  }

  window.NoteNotyApi = {
    getApiBase,
    getSession,
    setSession,
    clearSession,
    getClientId,
    request,
    register: payload => request("/register", { method: "POST", body: JSON.stringify(payload), timeoutMs: 20000 }),
    login: payload => request("/login", { method: "POST", body: JSON.stringify(payload) }),
    logout: () => request("/logout", { method: "POST" }),
    forgotPassword: payload => request("/forgot-password", { method: "POST", body: JSON.stringify(payload), timeoutMs: 20000 }),
    resetPassword: payload => request("/reset-password", { method: "POST", body: JSON.stringify(payload) }),
    bootstrap: () => request("/bootstrap"),
    updateProfile: payload => request("/profile", { method: "PUT", body: JSON.stringify(payload), timeoutMs: 12000 }),
    changePassword: payload => request("/change-password", { method: "POST", body: JSON.stringify(payload), timeoutMs: 20000 }),
    completePasswordChange: payload => request("/change-password/complete", { method: "POST", body: JSON.stringify(payload) }),
    updatePreferences: preferences => request("/preferences", { method: "PUT", body: JSON.stringify({ preferences }) }),
    getNote: id => request(`/notes/${id}`),
    createNote: payload => request("/notes", { method: "POST", body: JSON.stringify(payload), timeoutMs: payload?.images?.length ? 15000 : 3500 }),
    updateNote: (id, payload) => request(`/notes/${id}`, { method: "PUT", body: JSON.stringify(payload), timeoutMs: payload?.images?.length ? 15000 : 3500 }),
    deleteNote: id => request(`/notes/${id}`, { method: "DELETE" }),
    togglePin: id => request(`/notes/${id}/pin`, { method: "POST" }),
    unlockNote: (id, password) => request(`/notes/${id}/unlock`, { method: "POST", body: JSON.stringify({ password }) }),
    setNotePassword: (id, payload) => request(`/notes/${id}/password`, { method: "POST", body: JSON.stringify(payload), timeoutMs: 2000 }),
    disableNotePassword: (id, currentPassword) => request(`/notes/${id}/password`, { method: "DELETE", body: JSON.stringify({ current_password: currentPassword }), timeoutMs: 2000 }),
    createLabel: name => request("/labels", { method: "POST", body: JSON.stringify({ name }), timeoutMs: 2000 }),
    updateLabel: (id, name) => request(`/labels/${id}`, { method: "PUT", body: JSON.stringify({ name }), timeoutMs: 2000 }),
    deleteLabel: id => request(`/labels/${id}`, { method: "DELETE", timeoutMs: 2000 }),
    shareNote: (id, payload) => request(`/notes/${id}/shares`, { method: "POST", body: JSON.stringify(payload) }),
    updateShare: (noteId, shareId, permission) => request(`/notes/${noteId}/shares/${shareId}`, { method: "PUT", body: JSON.stringify({ permission }) }),
    revokeShare: (noteId, shareId) => request(`/notes/${noteId}/shares/${shareId}`, { method: "DELETE" })
  };

  window.NoteNotyImageTools = {
    imageToDataUrl
  };
})();
