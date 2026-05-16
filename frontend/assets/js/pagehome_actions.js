(() => {
  const { STORAGE_KEYS, DEFAULT_LABELS, DEFAULT_NOTES, DEFAULT_PREFS, DEFAULT_USER, LABEL_PALETTE, ROUTES } = window.NoteWiseData;
  const Render = window.NoteWiseRender;
  const Api = window.NoteNotyApi;

  function clone(value) {
    return JSON.parse(JSON.stringify(value));
  }

  function loadStored(key, fallback) {
    try {
      const raw = localStorage.getItem(key);
      return raw ? JSON.parse(raw) : clone(fallback);
    } catch (error) {
      return clone(fallback);
    }
  }

  function saveState() {
    localStorage.setItem(STORAGE_KEYS.notes, JSON.stringify(window.appState.notes));
    localStorage.setItem(STORAGE_KEYS.receivedNotes, JSON.stringify(window.appState.receivedNotes || []));
    localStorage.setItem(STORAGE_KEYS.labels, JSON.stringify(window.appState.labels));
    localStorage.setItem(STORAGE_KEYS.prefs, JSON.stringify(window.appState.prefs));
    localStorage.setItem(STORAGE_KEYS.user, JSON.stringify(window.appState.user));
    window.dispatchEvent(new CustomEvent("notenoty:state-changed"));
  }

  function saveLabelsState() {
    localStorage.setItem(STORAGE_KEYS.labels, JSON.stringify(window.appState.labels));
  }

  function sameId(a, b) {
    return String(a) === String(b) || Number(a) === Number(b);
  }

  function isTempId(id) {
    return String(id).startsWith("tmp-");
  }

  function makeTempId(type) {
    return `tmp-${type}-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
  }

  function loadOfflineQueue() {
    return loadStored(STORAGE_KEYS.offlineQueue, []);
  }

  function saveOfflineQueue(queue) {
    localStorage.setItem(STORAGE_KEYS.offlineQueue, JSON.stringify(queue));
    window.appState.pendingSyncCount = queue.length;
  }

  function loadIdMap() {
    return loadStored(STORAGE_KEYS.offlineIdMap, {});
  }

  function saveIdMap(idMap) {
    localStorage.setItem(STORAGE_KEYS.offlineIdMap, JSON.stringify(idMap));
  }

  function resolveId(id) {
    const idMap = loadIdMap();
    return idMap[String(id)] || id;
  }

  function isConnectionError(error) {
    return !error?.status || error?.name === "AbortError";
  }

  function queueToast() {
    const count = loadOfflineQueue().length;
    if (count) showToast(`Đã lưu offline ${count} thao tác. Hệ thống sẽ tự đồng bộ khi có mạng.`, "error");
  }

  function enqueueOperation(operation) {
    const queue = loadOfflineQueue();
    queue.push({
      id: makeTempId("op"),
      createdAt: Date.now(),
      ...operation
    });
    saveOfflineQueue(queue);
    window.appState.offline = true;
    queueToast();
  }

  function rememberMappedId(tempId, realId) {
    if (!isTempId(tempId)) return;
    const idMap = loadIdMap();
    idMap[String(tempId)] = realId;
    saveIdMap(idMap);
    replaceLocalId(tempId, realId);
    remapQueuedIds(tempId, realId);
  }

  function replaceLocalId(tempId, realId) {
    window.appState.notes.forEach(note => {
      if (sameId(note.id, tempId)) note.id = realId;
      note.labels = (note.labels || []).map(id => sameId(id, tempId) ? realId : id);
    });
    window.appState.receivedNotes.forEach(note => {
      note.labels = (note.labels || []).map(id => sameId(id, tempId) ? realId : id);
    });
    window.appState.labels.forEach(label => {
      if (sameId(label.id, tempId)) label.id = realId;
    });
    window.appState.editor.labels = (window.appState.editor.labels || []).map(id => sameId(id, tempId) ? realId : id);
    if (sameId(window.appState.editor.noteId, tempId)) window.appState.editor.noteId = realId;
    if (sameId(window.appState.filterLabel, tempId)) window.appState.filterLabel = realId;
    if (sameId(window.appState.deleteId, tempId)) window.appState.deleteId = realId;
    if (sameId(window.appState.shareId, tempId)) window.appState.shareId = realId;
    if (sameId(window.appState.passwordTargetId, tempId)) window.appState.passwordTargetId = realId;
    saveState();
  }

  function remapQueuedIds(tempId, realId) {
    const remapPayload = payload => {
      if (!payload) return payload;
      const next = { ...payload };
      if (sameId(next.noteId, tempId)) next.noteId = realId;
      if (sameId(next.labelId, tempId)) next.labelId = realId;
      if (sameId(next.localId, tempId)) next.localId = realId;
      if (Array.isArray(next.labels)) {
        next.labels = next.labels.map(id => sameId(id, tempId) ? realId : id);
      }
      return next;
    };

    const queue = loadOfflineQueue().map(item => ({
      ...item,
      payload: remapPayload(item.payload)
    }));
    saveOfflineQueue(queue);
  }

  function mergeQueuedNoteSave(noteId, payload, baseUpdatedAt, clientUpdatedAt) {
    const queue = loadOfflineQueue();
    const createItem = queue.find(item => item.type === "note_create" && sameId(item.payload.localId, noteId));
    if (createItem) {
      createItem.payload = { ...createItem.payload, ...payload, clientUpdatedAt };
      saveOfflineQueue(queue);
      return;
    }

    const previousUpdate = queue.find(item => item.type === "note_update" && sameId(item.payload.noteId, noteId));
    if (previousUpdate) {
      previousUpdate.payload = { noteId, ...payload, baseUpdatedAt: previousUpdate.payload.baseUpdatedAt || baseUpdatedAt, clientUpdatedAt };
      saveOfflineQueue(queue);
      return;
    }

    enqueueOperation({ type: "note_update", payload: { noteId, ...payload, baseUpdatedAt, clientUpdatedAt } });
  }

  function removeQueuedOperationsFor(id) {
    const queue = loadOfflineQueue().filter(item => {
      const payload = item.payload || {};
      return !sameId(payload.noteId, id) && !sameId(payload.localId, id) && !sameId(payload.labelId, id);
    });
    saveOfflineQueue(queue);
  }

  function removeLabelFromQueuedNotes(labelId) {
    const queue = loadOfflineQueue().map(item => {
      if (!["note_create", "note_update"].includes(item.type) || !Array.isArray(item.payload?.labels)) {
        return item;
      }
      return {
        ...item,
        payload: {
          ...item.payload,
          labels: item.payload.labels.filter(id => !sameId(id, labelId))
        }
      };
    });
    saveOfflineQueue(queue);
  }

  function requireAuthSession() {
    const session = Api.getSession();
    if (!session?.token) {
      window.location.href = ROUTES.login;
      return false;
    }
    return true;
  }

  function consumeActivationSession() {
    const params = new URLSearchParams(window.location.search);
    const token = params.get("api_token");

    if (!token) return false;

    const email = params.get("email") || "";
    Api.setSession({
      email,
      token,
      loggedInAt: Date.now()
    });

    if (email) {
      localStorage.setItem("notenoty_last_email", email);
    }

    sessionStorage.setItem("notenoty_activation_success", "1");
    params.delete("activated");
    params.delete("api_token");
    params.delete("email");

    const nextSearch = params.toString();
    const nextUrl = `${window.location.pathname}${nextSearch ? `?${nextSearch}` : ""}${window.location.hash}`;
    window.history.replaceState({}, document.title, nextUrl);
    return true;
  }

  function showToast(message, type = "success") {
    const stack = document.getElementById("toastStack");
    const item = document.createElement("div");
    item.className = `toast ${type}`;
    item.textContent = message;
    stack.appendChild(item);
    window.setTimeout(() => item.remove(), 2600);
  }

  function handleApiError(error, fallback = "Không thể kết nối máy chủ.") {
    if (error?.status === 401) {
      Api.clearSession();
      window.location.href = ROUTES.login;
      return;
    }
    showToast(error?.message || fallback, "error");
  }

  function deriveInitials(name) {
    return name
      .trim()
      .split(/\s+/)
      .slice(0, 2)
      .map(part => part[0]?.toUpperCase() || "")
      .join("") || "NN";
  }

  function normalizeUser(user) {
    return {
      ...DEFAULT_USER,
      ...user,
      initials: user?.initials || deriveInitials(user?.name || "NoteNoty User")
    };
  }

  function normalizeNote(note) {
    return {
      title: "Không có tiêu đề",
      content: "",
      color: "nc-lav",
      labels: [],
      images: [],
      shares: [],
      pinned: false,
      locked: false,
      shared: false,
      canEdit: true,
      received: false,
      ...note,
      labels: Array.isArray(note?.labels) ? note.labels : [],
      images: Array.isArray(note?.images) ? note.images : [],
      shares: Array.isArray(note?.shares) ? note.shares : []
    };
  }

  function normalizeNoteList(notes) {
    return Array.isArray(notes) ? notes.map(normalizeNote) : [];
  }

  function initializeCachedState() {
    window.appState.notes = normalizeNoteList(loadStored(STORAGE_KEYS.notes, DEFAULT_NOTES));
    window.appState.receivedNotes = normalizeNoteList(loadStored(STORAGE_KEYS.receivedNotes, []));
    window.appState.labels = loadStored(STORAGE_KEYS.labels, DEFAULT_LABELS);
    window.appState.prefs = { ...DEFAULT_PREFS, ...loadStored(STORAGE_KEYS.prefs, DEFAULT_PREFS) };
    window.appState.user = normalizeUser(loadStored(STORAGE_KEYS.user, DEFAULT_USER));
    window.appState.pendingSyncCount = loadOfflineQueue().length;
  }

  async function syncOfflineQueue({ silent = false } = {}) {
    let queue = loadOfflineQueue();
    if (!queue.length || window.appState.syncingOffline) return true;

    window.appState.syncingOffline = true;
    if (!silent) showToast("Đang đồng bộ dữ liệu offline...");

    try {
      const serverSnapshot = await Api.bootstrap();
      const serverNotes = [...(serverSnapshot.notes || []), ...(serverSnapshot.receivedNotes || [])];
      const completedIds = new Set();

      for (const item of queue) {
        const payload = item.payload || {};

        if (item.type === "label_create") {
          const data = await Api.createLabel(payload.name);
          rememberMappedId(payload.localId, data.label.id);
          const local = window.appState.labels.find(label => sameId(label.id, data.label.id));
          if (local) Object.assign(local, data.label);
          completedIds.add(item.id);
          continue;
        }

        if (item.type === "label_update") {
          await Api.updateLabel(resolveId(payload.labelId), payload.name);
          completedIds.add(item.id);
          continue;
        }

        if (item.type === "label_delete") {
          await Api.deleteLabel(resolveId(payload.labelId));
          completedIds.add(item.id);
          continue;
        }

        if (item.type === "note_create") {
          const data = await Api.createNote({
            title: payload.title,
            content: payload.content,
            color: payload.color,
            labels: (payload.labels || []).map(resolveId),
            images: payload.images || []
          });
          rememberMappedId(payload.localId, data.note.id);
          replaceNote(data.note);
          completedIds.add(item.id);
          continue;
        }

        if (item.type === "note_update") {
          const noteId = resolveId(payload.noteId);
          const serverNote = serverNotes.find(note => sameId(note.id, noteId));
          if (serverNote && serverNote.updatedAt > (payload.clientUpdatedAt || 0)) {
            replaceNote(serverNote);
            completedIds.add(item.id);
            showToast("Một ghi chú có thay đổi mới hơn trên server, NoteNoty đã giữ bản mới nhất.", "error");
            continue;
          }

          const data = await Api.updateNote(noteId, {
            title: payload.title,
            content: payload.content,
            color: payload.color,
            labels: (payload.labels || []).map(resolveId),
            images: payload.images || []
          });
          replaceNote(data.note);
          completedIds.add(item.id);
          continue;
        }

        if (item.type === "note_delete") {
          await Api.deleteNote(resolveId(payload.noteId));
          completedIds.add(item.id);
          continue;
        }

        if (item.type === "note_pin") {
          const noteId = resolveId(payload.noteId);
          const serverNote = serverNotes.find(note => sameId(note.id, noteId));
          if (!serverNote || Boolean(serverNote.pinned) !== Boolean(payload.pinned)) {
            const data = await Api.togglePin(noteId);
            if (data.note) replaceNote(data.note);
          }
          completedIds.add(item.id);
          continue;
        }

        if (item.type === "prefs_update") {
          await Api.updatePreferences(payload.preferences);
          completedIds.add(item.id);
        }
      }

      queue = loadOfflineQueue().filter(item => !completedIds.has(item.id));
      saveOfflineQueue(queue);
      saveState();
      window.appState.offline = queue.length > 0;

      if (!queue.length && !silent) showToast("Đã đồng bộ dữ liệu offline lên server.");
      return !queue.length;
    } catch (error) {
      window.appState.offline = true;
      if (!silent) showToast("Chưa thể đồng bộ, dữ liệu vẫn được giữ trên trình duyệt.", "error");
      return false;
    } finally {
      window.appState.syncingOffline = false;
    }
  }

  async function syncFromBackend() {
    if (window.appState.syncingBackend) {
      return;
    }

    window.appState.syncingBackend = true;

    try {
      if (loadOfflineQueue().length) {
        const synced = await syncOfflineQueue({ silent: true });
        if (!synced || loadOfflineQueue().length) {
          throw new Error("Offline changes are still pending.");
        }
      }

      const data = await Api.bootstrap();
      window.appState.notes = normalizeNoteList(data.notes || []);
      window.appState.receivedNotes = normalizeNoteList(data.receivedNotes || []);
      window.appState.labels = data.labels || [];
      window.appState.prefs = { ...DEFAULT_PREFS, ...(data.preferences || {}) };
      window.appState.user = normalizeUser(data.user || DEFAULT_USER);
      window.appState.offline = false;
      saveState();
      Render.renderAll();
      if (loadOfflineQueue().length) queueToast();
    } catch (error) {
      window.appState.offline = true;
      Render.renderAll();
      showToast("Đang dùng dữ liệu offline đã lưu trên trình duyệt.", "error");
    } finally {
      window.appState.syncingBackend = false;
    }
  }

  function refreshFromBackendWhenVisible() {
    if (document.visibilityState === "hidden" || !Api.getSession()?.token) {
      return;
    }

    const now = Date.now();
    if (window.appState.lastForegroundSyncAt && now - window.appState.lastForegroundSyncAt < 1500) {
      return;
    }

    window.appState.lastForegroundSyncAt = now;
    syncFromBackend();
  }

  function getNextLabelId() {
    return window.appState.labels.reduce((max, label) => Math.max(max, Number(label.id) || 0), 0) + 1;
  }

  function getNoteById(id) {
    return window.appState.notes.find(note => sameId(note.id, id))
      || window.appState.receivedNotes.find(note => sameId(note.id, id));
  }

  function replaceNote(note) {
    note = normalizeNote(note);
    const ownIndex = window.appState.notes.findIndex(item => sameId(item.id, note.id));
    if (ownIndex >= 0) {
      window.appState.notes.splice(ownIndex, 1, note);
      return;
    }

    const receivedIndex = window.appState.receivedNotes.findIndex(item => sameId(item.id, note.id));
    if (receivedIndex >= 0) {
      window.appState.receivedNotes.splice(receivedIndex, 1, note);
      return;
    }

    if (note.received) {
      window.appState.receivedNotes.unshift(note);
      return;
    }

    window.appState.notes.unshift(note);
  }

  function removeLocalNote(noteId) {
    const beforeOwn = window.appState.notes.length;
    const beforeReceived = window.appState.receivedNotes.length;
    window.appState.notes = window.appState.notes.filter(note => !sameId(note.id, noteId));
    window.appState.receivedNotes = window.appState.receivedNotes.filter(note => !sameId(note.id, noteId));
    return beforeOwn !== window.appState.notes.length || beforeReceived !== window.appState.receivedNotes.length;
  }

  function realtimeSubscriptionNoteIds() {
    return [...window.appState.notes, ...(window.appState.receivedNotes || [])]
      .map(note => note.id)
      .filter(id => id !== null && id !== undefined && !isTempId(id));
  }

  async function handleRealtimeNoteEvent(message) {
    if (!message?.noteId || message.actorClientId === Api.getClientId()) {
      return;
    }

    const noteId = message.noteId;

    if (message.event === "note.deleted") {
      if (removeLocalNote(noteId)) {
        saveState();
        Render.renderAll();
        showToast("Một ghi chú được chia sẻ vừa bị xóa.");
      }
      return;
    }

    try {
      const data = await Api.getNote(noteId);
      if (data.note) {
        replaceNote(data.note);
        saveState();
        Render.renderAll();
        showToast("Ghi chú được chia sẻ vừa cập nhật realtime.");
      }
    } catch (error) {
      if (error?.status === 403 || error?.status === 404) {
        if (removeLocalNote(noteId)) {
          saveState();
          Render.renderAll();
          showToast("Quyền truy cập một ghi chú được chia sẻ vừa thay đổi.", "error");
        }
        return;
      }

      handleApiError(error);
    }
  }

  function applyEditorToLocalNote(payload) {
    const editor = window.appState.editor;
    if (!editor.noteId) return null;

    const note = getNoteById(editor.noteId);
    if (!note || note.received) return null;

    Object.assign(note, {
      title: payload.title,
      content: payload.content,
      color: payload.color,
      labels: [...payload.labels],
      images: [...payload.images],
      updatedAt: Date.now()
    });

    saveState();
    Render.renderAll();
    return note;
  }

  function openOverlay(id) {
    document.getElementById(id)?.classList.add("open");
  }

  function closeOverlay(id) {
    document.getElementById(id)?.classList.remove("open");
  }

  function openDrawer(id) {
    document.getElementById(id)?.classList.add("open");
  }

  function closeDrawer(id) {
    document.getElementById(id)?.classList.remove("open");
  }

  function updateAutosaveStatus(text, waiting = false) {
    const status = document.getElementById("autosaveStatus");
    const dot = document.getElementById("editorAutosaveDot");
    if (!status || !dot) return;

    status.textContent = text;
    dot.classList.toggle("waiting", waiting);
  }

  function closePickers() {
    document.getElementById("noteLabelPicker")?.classList.remove("open");
  }

  function readEditorFields() {
    return {
      title: document.getElementById("noteTitleInput").value.trim(),
      content: document.getElementById("noteContentInput").value.trim()
    };
  }

  function resetEditorState() {
    window.appState.editor = {
      mode: "create",
      noteId: null,
      color: "nc-lav",
      labels: [],
      images: [],
      canEdit: true
    };
    document.getElementById("noteTitleInput").value = "";
    document.getElementById("noteContentInput").value = "";
    updateAutosaveStatus(window.appState.prefs.autoSaveEnabled ? "Chờ nhập nội dung để tự lưu" : "Tự động lưu đang tắt");
    Render.renderEditor();
  }

  function populateEditorFromNote(note) {
    window.appState.editor = {
      mode: "edit",
      noteId: note.id,
      color: note.color,
      labels: [...note.labels],
      images: [...note.images],
      canEdit: note.canEdit !== false
    };
    document.getElementById("noteTitleInput").value = note.title;
    document.getElementById("noteContentInput").value = note.content;
    updateAutosaveStatus(note.canEdit === false ? "Ghi chú chỉ xem" : (window.appState.prefs.autoSaveEnabled ? "Sẵn sàng tự lưu sau 3 giây" : "Tự động lưu đang tắt"));
    Render.renderEditor();
  }

  function openCreateNote() {
    resetEditorState();
    openOverlay("noteEditorOverlay");
  }

  function openEditNote(noteId, skipLockCheck = false) {
    const note = getNoteById(noteId);
    if (!note) return;

    if (note.locked && !skipLockCheck) {
      openUnlockModal(noteId, () => openEditNote(noteId, true));
      return;
    }

    populateEditorFromNote(note);
    openOverlay("noteEditorOverlay");
  }

  function editorPayload() {
    const { title, content } = readEditorFields();
    const editor = window.appState.editor;

    return {
      title: title || "Không có tiêu đề",
      content,
      color: editor.color,
      labels: [...editor.labels],
      images: [...editor.images]
    };
  }

  function makeLocalNote(payload, id = makeTempId("note")) {
    const now = Date.now();
    return {
      id,
      title: payload.title || "Không có tiêu đề",
      content: payload.content || "",
      color: payload.color || "nc-lav",
      labels: [...(payload.labels || [])],
      images: [...(payload.images || [])],
      pinned: false,
      pinnedAt: null,
      locked: false,
      password: "",
      shared: false,
      shares: [],
      createdAt: now,
      updatedAt: now,
      ownerId: window.appState.user?.id,
      ownerName: window.appState.user?.name,
      ownerEmail: window.appState.user?.email,
      canEdit: true,
      received: false,
      offlinePending: true
    };
  }

  async function persistEditorNote(fromAutoSave = false) {
    const payload = editorPayload();
    const editor = window.appState.editor;

    if (!payload.title && !payload.content && !payload.images.length) {
      if (!fromAutoSave) showToast("Nhập ít nhất tiêu đề, nội dung hoặc ảnh trước khi lưu.", "error");
      return null;
    }

    if (editor.canEdit === false) return null;

    if (editor.noteId && isTempId(editor.noteId)) {
      const clientUpdatedAt = Date.now();
      const localNote = applyEditorToLocalNote(payload);
      if (localNote) {
        localNote.offlinePending = true;
        localNote.updatedAt = clientUpdatedAt;
        mergeQueuedNoteSave(editor.noteId, payload, 0, clientUpdatedAt);
        saveState();
        Render.renderAll();
        updateAutosaveStatus("Đã lưu offline, sẽ đồng bộ khi có mạng", false);
        return localNote;
      }
    }

    try {
      if (editor.noteId) {
        applyEditorToLocalNote(payload);
        updateAutosaveStatus("Đang đồng bộ...", true);
      }

      const data = editor.noteId
        ? await Api.updateNote(editor.noteId, payload)
        : await Api.createNote(payload);

      replaceNote(data.note);
      window.appState.editor.noteId = data.note.id;
      window.appState.editor.mode = "edit";
      saveState();
      Render.renderAll();
      return data.note;
    } catch (error) {
      if (isConnectionError(error)) {
        const clientUpdatedAt = Date.now();
        let localNote;

        if (editor.noteId) {
          const baseUpdatedAt = getNoteById(editor.noteId)?.updatedAt || 0;
          localNote = applyEditorToLocalNote(payload);
          if (localNote) {
            localNote.offlinePending = true;
            localNote.updatedAt = clientUpdatedAt;
            mergeQueuedNoteSave(editor.noteId, payload, baseUpdatedAt, clientUpdatedAt);
          }
        } else {
          localNote = makeLocalNote(payload);
          window.appState.notes.unshift(localNote);
          window.appState.editor.noteId = localNote.id;
          window.appState.editor.mode = "edit";
          enqueueOperation({
            type: "note_create",
            payload: {
              localId: localNote.id,
              ...payload,
              clientUpdatedAt
            }
          });
        }

        window.appState.offline = true;
        saveState();
        Render.renderAll();
        updateAutosaveStatus("Đã lưu offline, sẽ đồng bộ khi có mạng", false);
        if (!fromAutoSave) showToast("Đã lưu ghi chú offline. NoteNoty sẽ tự đồng bộ khi có mạng.", "error");
        return localNote;
      }

      if (!fromAutoSave) handleApiError(error);
      updateAutosaveStatus("Chưa lưu được, kiểm tra kết nối", false);
      return null;
    }
  }

  async function saveEditorNote() {
    const saveButton = document.getElementById("saveNoteBtn");
    const originalText = saveButton.textContent;
    saveButton.disabled = true;
    saveButton.textContent = "Đang lưu...";

    const note = await persistEditorNote(false);
    saveButton.disabled = false;
    saveButton.textContent = originalText;
    if (!note) return;

    updateAutosaveStatus("Đã lưu ghi chú");
    showToast(window.appState.editor.mode === "edit" ? "Đã cập nhật ghi chú." : "Đã tạo ghi chú mới.");
    closeOverlay("noteEditorOverlay");
  }

  function scheduleEditorAutoSave() {
    clearTimeout(window.appState.saveTimer);

    if (!window.appState.prefs.autoSaveEnabled || window.appState.editor.canEdit === false) {
      updateAutosaveStatus(window.appState.editor.canEdit === false ? "Ghi chú chỉ xem" : "Tự động lưu đang tắt");
      return;
    }

    const { title, content } = readEditorFields();
    if (!title && !content && !window.appState.editor.images.length) {
      updateAutosaveStatus("Chờ nhập nội dung để tự lưu");
      return;
    }

    updateAutosaveStatus("Sẽ tự lưu sau 3 giây...", true);
    window.appState.saveTimer = window.setTimeout(async () => {
      const note = await persistEditorNote(true);
      if (note) updateAutosaveStatus("Đã tự động lưu");
    }, 3000);
  }

  function selectEditorColor(colorClass, button) {
    if (window.appState.editor.canEdit === false) return;
    window.appState.editor.color = colorClass;
    document.querySelectorAll("#noteColorStrip .color-dot").forEach(item => item.classList.remove("sel"));
    button.classList.add("sel");
    scheduleEditorAutoSave();
  }

  function toggleEditorLabel(labelId) {
    if (window.appState.editor.canEdit === false) return;
    const labels = window.appState.editor.labels;
    window.appState.editor.labels = labels.some(id => sameId(id, labelId))
      ? labels.filter(id => !sameId(id, labelId))
      : [...labels, labelId];

    Render.renderLabelPicker("noteLabelPicker", window.appState.editor.labels);
    Render.renderSelectedLabels("noteSelectedLabels", window.appState.editor.labels);
    scheduleEditorAutoSave();
  }

  function handleEditorLabelPickerClick(event) {
    const trigger = event.target.closest("[data-label-action='toggle-editor']");
    if (!trigger) return;

    event.preventDefault();
    event.stopPropagation();
    toggleEditorLabel(trigger.dataset.labelId);
  }

  async function handleEditorImageSelect(event) {
    if (window.appState.editor.canEdit === false) return;
    const files = Array.from(event.target.files || []);
    if (!files.length) return;

    const validFiles = files.filter(file => file.type.startsWith("image/"));
    if (validFiles.length !== files.length) {
      showToast("Chỉ được đính kèm file ảnh.", "error");
    }

    if (!validFiles.length) {
      event.target.value = "";
      return;
    }

    try {
      showToast("Đang xử lý ảnh...");
      const compressedImages = await Promise.all(validFiles.map(file =>
        window.NoteNotyImageTools.imageToDataUrl(file, {
          maxWidth: 1280,
          maxHeight: 1280,
          quality: 0.8
        })
      ));
      window.appState.editor.images.push(...compressedImages);
      Render.renderImagePreview("editorImagePreview", window.appState.editor.images, "editor");
      scheduleEditorAutoSave();
    } catch (error) {
      showToast(error.message || "Không thể xử lý ảnh đã chọn.", "error");
    } finally {
      event.target.value = "";
    }
  }

  function removeEditorImage(index) {
    if (window.appState.editor.canEdit === false) return;
    window.appState.editor.images.splice(index, 1);
    Render.renderImagePreview("editorImagePreview", window.appState.editor.images, "editor");
    scheduleEditorAutoSave();
  }

  function openZoomImage(src) {
    document.getElementById("zoomImage").src = src;
    openOverlay("imageZoomOverlay");
  }

  function activateFilter(filter) {
    window.appState.filter = filter;
    window.appState.filterLabel = null;
    Render.renderSidebarFilterState();
    Render.renderPageTitle();
    Render.renderNotes();
  }

  function activateLabelFilter(labelId) {
    window.appState.filter = "all";
    window.appState.filterLabel = labelId;
    Render.renderSidebarFilterState();
    Render.renderSidebarLabels();
    Render.renderPageTitle();
    Render.renderNotes();
  }

  function handleSidebarLabelClick(event) {
    const trigger = event.target.closest("[data-label-action='filter']");
    if (!trigger) return;

    event.preventDefault();
    activateLabelFilter(trigger.dataset.labelId);
  }

  async function persistPreferences() {
    saveState();
    try {
      await Api.updatePreferences(window.appState.prefs);
    } catch (error) {
      if (isConnectionError(error)) {
        window.appState.offline = true;
        enqueueOperation({ type: "prefs_update", payload: { preferences: { ...window.appState.prefs } } });
      }
    }
  }

  function setView(view) {
    window.appState.prefs.view = view;
    persistPreferences();
    Render.renderNotes();
  }

  function setSort(sort) {
    window.appState.prefs.sort = sort;
    persistPreferences();
    Render.renderNotes();
  }

  function scheduleSearch(value) {
    clearTimeout(window.appState.searchTimer);
    window.appState.searchTimer = window.setTimeout(() => {
      window.appState.searchQ = value.trim();
      Render.renderNotes();
    }, 300);
  }

  async function deleteNoteDirectly(id) {
    if (isTempId(id)) {
      window.appState.notes = window.appState.notes.filter(note => !sameId(note.id, id));
      removeQueuedOperationsFor(id);
      saveState();
      Render.renderAll();
      showToast("Đã xóa ghi chú offline.");
      return;
    }

    try {
      await Api.deleteNote(id);
      window.appState.notes = window.appState.notes.filter(note => !sameId(note.id, id));
      saveState();
      Render.renderAll();
      showToast("Đã xóa ghi chú.");
    } catch (error) {
      if (isConnectionError(error)) {
        window.appState.notes = window.appState.notes.filter(note => !sameId(note.id, id));
        window.appState.receivedNotes = window.appState.receivedNotes.filter(note => !sameId(note.id, id));
        if (isTempId(id)) {
          removeQueuedOperationsFor(id);
        } else {
          removeQueuedOperationsFor(id);
          enqueueOperation({ type: "note_delete", payload: { noteId: id } });
        }
        saveState();
        Render.renderAll();
        showToast("Đã xóa ghi chú offline. Hệ thống sẽ đồng bộ khi có mạng.", "error");
      } else {
        handleApiError(error);
      }
    }
  }

  function openConfirmDelete(id, event, skipLockCheck = false) {
    event?.stopPropagation();
    const note = getNoteById(id);
    if (!note || note.received) return;

    if (note.locked && !skipLockCheck) {
      openUnlockModal(id, () => openConfirmDelete(id, null, true));
      return;
    }

    window.appState.deleteId = id;
    if (!window.appState.prefs.confirmDelete) {
      deleteNoteDirectly(id);
      return;
    }

    openOverlay("confirmOverlay");
  }

  function confirmDeleteCurrentNote() {
    if (!window.appState.deleteId) return;
    deleteNoteDirectly(window.appState.deleteId);
    window.appState.deleteId = null;
    closeOverlay("confirmOverlay");
  }

  function handleNoteSelection(noteId) {
    openEditNote(noteId);
  }

  function handleRenderedNoteClick(event) {
    const trigger = event.target.closest("[data-note-action]");
    if (!trigger) return;

    const noteId = trigger.dataset.noteId || trigger.closest("[data-note-id]")?.dataset.noteId;
    if (!noteId) return;

    event.preventDefault();
    event.stopPropagation();

    const action = trigger.dataset.noteAction;
    if (action === "open") {
      handleNoteSelection(noteId);
      return;
    }

    if (action === "password") {
      openPasswordManager(noteId, event);
      return;
    }

    if (action === "pin") {
      togglePin(noteId, event);
      return;
    }

    if (action === "share") {
      openShare(noteId, event);
      return;
    }

    if (action === "delete") {
      openConfirmDelete(noteId, event);
    }
  }

  function openShare(noteId, event, skipLockCheck = false) {
    event?.stopPropagation();
    const note = getNoteById(noteId);
    if (!note || note.received) return;

    if (isTempId(note.id)) {
      showToast("Ghi chú offline cần được đồng bộ trước khi chia sẻ.", "error");
      return;
    }

    if (window.appState.user.emailVerified === false) {
      showToast("Hãy kích hoạt tài khoản tại email đã đăng kí trước khi chia sẻ ghi chú.", "error");
      return;
    }

    if (note.locked && !skipLockCheck) {
      openUnlockModal(noteId, () => openShare(noteId, null, true));
      return;
    }

    window.appState.shareId = noteId;
    window.appState.selectedPerm = "readonly";
    document.getElementById("shareEmail").value = "";
    document.querySelectorAll(".pt-btn").forEach(button => {
      button.classList.toggle("active", button.dataset.perm === "readonly");
    });
    Render.renderSharedList();
    openOverlay("shareOverlay");
  }

  function selectPermission(permission) {
    window.appState.selectedPerm = permission;
    document.querySelectorAll(".pt-btn").forEach(button => {
      button.classList.toggle("active", button.dataset.perm === permission);
    });
  }

  async function addShare() {
    const note = getNoteById(window.appState.shareId);
    const email = document.getElementById("shareEmail").value.trim().toLowerCase();
    if (!note) return;

    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      showToast("Email chưa đúng định dạng.", "error");
      return;
    }

    try {
      const data = await Api.shareNote(note.id, { email, permission: window.appState.selectedPerm });
      replaceNote(data.note);
      saveState();
      Render.renderAll();
      Render.renderSharedList();
      document.getElementById("shareEmail").value = "";
      showToast("Đã chia sẻ ghi chú.");
    } catch (error) {
      handleApiError(error);
    }
  }

  async function updateSharePermission(shareId, permission) {
    const note = getNoteById(window.appState.shareId);
    if (!note) return;

    try {
      const data = await Api.updateShare(note.id, shareId, permission);
      replaceNote(data.note);
      saveState();
      Render.renderSharedList();
      Render.renderAll();
      showToast("Đã cập nhật quyền chia sẻ.");
    } catch (error) {
      handleApiError(error);
    }
  }

  async function revokeShare(shareId) {
    const note = getNoteById(window.appState.shareId);
    if (!note) return;

    try {
      const data = await Api.revokeShare(note.id, shareId);
      replaceNote(data.note);
      saveState();
      Render.renderSharedList();
      Render.renderAll();
      showToast("Đã thu hồi quyền truy cập.");
    } catch (error) {
      handleApiError(error);
    }
  }

  function openPasswordManager(noteId, event) {
    event?.stopPropagation();
    const note = getNoteById(noteId);
    if (!note || note.received) return;

    if (isTempId(note.id)) {
      showToast("Ghi chú offline cần được đồng bộ trước khi đặt mật khẩu.", "error");
      return;
    }

    window.appState.passwordTargetId = noteId;
    document.getElementById("currentPasswordInput").value = "";
    document.getElementById("newPasswordInput").value = "";
    document.getElementById("confirmPasswordInput").value = "";

    if (note.locked) {
      window.appState.passwordMode = "manage";
      document.getElementById("passwordModalTitle").textContent = "Quản lý mật khẩu ghi chú";
      document.getElementById("currentPasswordField").classList.remove("hidden");
      document.getElementById("disablePasswordBtn").classList.remove("hidden");
    } else {
      window.appState.passwordMode = "set";
      document.getElementById("passwordModalTitle").textContent = "Thiết lập mật khẩu ghi chú";
      document.getElementById("currentPasswordField").classList.add("hidden");
      document.getElementById("disablePasswordBtn").classList.add("hidden");
    }

    openOverlay("passwordOverlay");
  }

  async function savePassword() {
    const note = getNoteById(window.appState.passwordTargetId);
    if (!note) return;

    const currentPassword = document.getElementById("currentPasswordInput").value;
    const newPassword = document.getElementById("newPasswordInput").value;
    const confirmPassword = document.getElementById("confirmPasswordInput").value;
    const button = document.getElementById("savePasswordBtn");

    if (!newPassword || newPassword.length < 4) {
      showToast("Mật khẩu cần tối thiểu 4 ký tự.", "error");
      return;
    }

    if (newPassword !== confirmPassword) {
      showToast("Xác nhận mật khẩu chưa khớp.", "error");
      return;
    }

    try {
      button.disabled = true;
      button.textContent = "Đang lưu...";
      const data = await Api.setNotePassword(note.id, {
        current_password: currentPassword,
        new_password: newPassword,
        new_password_confirmation: confirmPassword
      });
      replaceNote(data.note);
      saveState();
      Render.renderAll();
      closeOverlay("passwordOverlay");
      showToast(window.appState.passwordMode === "manage" ? "Đã đổi mật khẩu ghi chú." : "Đã khóa ghi chú bằng mật khẩu.");
    } catch (error) {
      if (isConnectionError(error)) {
        showToast("Đặt mật khẩu ghi chú cần kết nối server để bảo mật. Nội dung ghi chú vẫn được giữ offline.", "error");
      } else {
        handleApiError(error);
      }
    } finally {
      button.disabled = false;
      button.textContent = "Lưu";
    }
  }

  async function disablePassword() {
    const note = getNoteById(window.appState.passwordTargetId);
    if (!note) return;

    if (!confirm("Tắt mật khẩu cho ghi chú này?")) return;

    const currentPassword = document.getElementById("currentPasswordInput").value;
    const button = document.getElementById("disablePasswordBtn");

    try {
      button.disabled = true;
      button.textContent = "Đang tắt...";
      const data = await Api.disableNotePassword(note.id, currentPassword);
      replaceNote(data.note);
      saveState();
      Render.renderAll();
      closeOverlay("passwordOverlay");
      showToast("Đã tắt mật khẩu cho ghi chú.");
    } catch (error) {
      if (isConnectionError(error)) {
        showToast("Tắt mật khẩu ghi chú cần kết nối server để bảo mật. Vui lòng thử lại khi online.", "error");
      } else {
        handleApiError(error);
      }
    } finally {
      button.disabled = false;
      button.textContent = "Tắt mật khẩu";
    }
  }

  function openUnlockModal(noteId, action) {
    window.appState.passwordTargetId = noteId;
    window.appState.unlockAction = action;
    document.getElementById("unlockPasswordInput").value = "";
    openOverlay("unlockOverlay");
  }

  async function confirmUnlock() {
    const note = getNoteById(window.appState.passwordTargetId);
    if (!note) return;

    const password = document.getElementById("unlockPasswordInput").value;

    try {
      const data = await Api.unlockNote(note.id, password);
      if (data.note) {
        replaceNote(data.note);
        saveState();
        Render.renderAll();
      }
      closeOverlay("unlockOverlay");
      const action = window.appState.unlockAction;
      window.appState.unlockAction = null;
      showToast("Mở khóa thành công.");
      if (typeof action === "function") action();
    } catch (error) {
      handleApiError(error);
    }
  }

  async function togglePin(noteId, event, skipLockCheck = false) {
    event?.stopPropagation();
    const note = getNoteById(noteId);
    if (!note || note.received) return;

    if (note.locked && !skipLockCheck) {
      openUnlockModal(noteId, () => togglePin(noteId, null, true));
      return;
    }

    if (isTempId(note.id)) {
      note.pinned = !note.pinned;
      note.pinnedAt = note.pinned ? Date.now() : null;
      note.updatedAt = Date.now();
      note.offlinePending = true;
      enqueueOperation({ type: "note_pin", payload: { noteId: note.id, pinned: note.pinned } });
      saveState();
      Render.renderAll();
      showToast(note.pinned ? "Đã ghim offline, sẽ đồng bộ khi có mạng." : "Đã bỏ ghim offline, sẽ đồng bộ khi có mạng.", "error");
      return;
    }

    try {
      const data = await Api.togglePin(note.id);
      replaceNote(data.note);
      saveState();
      Render.renderAll();
      showToast(data.note.pinned ? "Đã ghim ghi chú." : "Đã bỏ ghim ghi chú.");
    } catch (error) {
      if (isConnectionError(error)) {
        note.pinned = !note.pinned;
        note.pinnedAt = note.pinned ? Date.now() : null;
        note.updatedAt = Date.now();
        note.offlinePending = true;
        enqueueOperation({ type: "note_pin", payload: { noteId: note.id, pinned: note.pinned } });
        saveState();
        Render.renderAll();
        showToast(note.pinned ? "Đã ghim offline, sẽ đồng bộ khi có mạng." : "Đã bỏ ghim offline, sẽ đồng bộ khi có mạng.", "error");
      } else {
        handleApiError(error);
      }
    }
  }

  async function addLabel() {
    const input = document.getElementById("newLabelInput");
    const name = input.value.trim();
    if (!name) {
      showToast("Nhập tên nhãn trước khi thêm.", "error");
      return;
    }

    if (window.appState.labels.some(label => label.name.toLowerCase() === name.toLowerCase())) {
      showToast("Nhãn này đã tồn tại.", "error");
      return;
    }

    const button = document.getElementById("addLabelBtn");
    try {
      button.disabled = true;
      button.textContent = "Đang thêm...";
      const data = await Api.createLabel(name);
      window.appState.labels.push(data.label);
      input.value = "";
      saveLabelsState();
      Render.renderAll();
      showToast("Đã thêm nhãn mới.");
    } catch (error) {
      if (isConnectionError(error)) {
        const localId = makeTempId("label");
        window.appState.labels.push({
          id: localId,
          name,
          color: LABEL_PALETTE[window.appState.labels.length % LABEL_PALETTE.length]
        });
        enqueueOperation({ type: "label_create", payload: { localId, name } });
        input.value = "";
        saveLabelsState();
        Render.renderAll();
        showToast("Đã thêm nhãn offline. Hệ thống sẽ đồng bộ khi có mạng.", "error");
      } else {
        handleApiError(error);
      }
    } finally {
      button.disabled = false;
      button.textContent = "Thêm";
    }
  }

  async function saveLabelName(labelId) {
    const input = document.getElementById(`labelInput-${labelId}`);
    const label = window.appState.labels.find(item => sameId(item.id, labelId));
    const nextName = input?.value.trim();
    if (!label || !nextName) {
      showToast("Tên nhãn không được để trống.", "error");
      return;
    }

    if (isTempId(labelId)) {
      label.name = nextName;
      const queue = loadOfflineQueue();
      const createItem = queue.find(item => item.type === "label_create" && sameId(item.payload.localId, labelId));
      if (createItem) {
        createItem.payload.name = nextName;
        saveOfflineQueue(queue);
      }
      saveLabelsState();
      Render.renderAll();
      showToast("Đã cập nhật nhãn offline. Hệ thống sẽ đồng bộ khi có mạng.", "error");
      return;
    }

    try {
      const data = await Api.updateLabel(labelId, nextName);
      Object.assign(label, data.label);
      saveLabelsState();
      Render.renderAll();
      showToast("Đã cập nhật tên nhãn.");
    } catch (error) {
      if (isConnectionError(error)) {
        label.name = nextName;
        const queue = loadOfflineQueue();
        const createItem = queue.find(item => item.type === "label_create" && sameId(item.payload.localId, labelId));
        if (createItem) {
          createItem.payload.name = nextName;
          saveOfflineQueue(queue);
        } else {
          enqueueOperation({ type: "label_update", payload: { labelId, name: nextName } });
        }
        saveLabelsState();
        Render.renderAll();
        showToast("Đã cập nhật nhãn offline. Hệ thống sẽ đồng bộ khi có mạng.", "error");
      } else {
        handleApiError(error);
      }
    }
  }

  async function deleteLabel(labelId) {
    if (isTempId(labelId)) {
      window.appState.labels = window.appState.labels.filter(label => !sameId(label.id, labelId));
      window.appState.notes.forEach(note => {
        note.labels = note.labels.filter(id => !sameId(id, labelId));
      });
      if (sameId(window.appState.filterLabel, labelId)) window.appState.filterLabel = null;
      window.appState.editor.labels = window.appState.editor.labels.filter(id => !sameId(id, labelId));
      removeQueuedOperationsFor(labelId);
      removeLabelFromQueuedNotes(labelId);
      saveState();
      Render.renderAll();
      showToast("Đã xóa nhãn offline.");
      return;
    }

    try {
      await Api.deleteLabel(labelId);
      window.appState.labels = window.appState.labels.filter(label => !sameId(label.id, labelId));
      window.appState.notes.forEach(note => {
        note.labels = note.labels.filter(id => !sameId(id, labelId));
      });

      if (sameId(window.appState.filterLabel, labelId)) window.appState.filterLabel = null;
      window.appState.editor.labels = window.appState.editor.labels.filter(id => !sameId(id, labelId));

      saveState();
      Render.renderAll();
      showToast("Đã xóa nhãn, các ghi chú vẫn được giữ nguyên.");
    } catch (error) {
      if (isConnectionError(error)) {
        window.appState.labels = window.appState.labels.filter(label => !sameId(label.id, labelId));
        window.appState.notes.forEach(note => {
          note.labels = note.labels.filter(id => !sameId(id, labelId));
        });
        if (sameId(window.appState.filterLabel, labelId)) window.appState.filterLabel = null;
        window.appState.editor.labels = window.appState.editor.labels.filter(id => !sameId(id, labelId));

        if (isTempId(labelId)) {
          removeQueuedOperationsFor(labelId);
        } else {
          removeQueuedOperationsFor(labelId);
          enqueueOperation({ type: "label_delete", payload: { labelId } });
        }
        removeLabelFromQueuedNotes(labelId);

        saveState();
        Render.renderAll();
        showToast("Đã xóa nhãn offline. Hệ thống sẽ đồng bộ khi có mạng.", "error");
      } else {
        handleApiError(error);
      }
    }
  }

  function handleLabelManagerClick(event) {
    const trigger = event.target.closest("[data-label-action]");
    if (!trigger) return;

    const labelId = trigger.dataset.labelId;
    if (!labelId) return;

    event.preventDefault();
    event.stopPropagation();

    if (trigger.dataset.labelAction === "save") {
      saveLabelName(labelId);
      return;
    }

    if (trigger.dataset.labelAction === "delete") {
      deleteLabel(labelId);
    }
  }

  function toggleDarkMode() {
    window.appState.prefs.darkMode = !window.appState.prefs.darkMode;
    persistPreferences();
    Render.applyPreferences();
  }

  function changeFontSize(delta) {
    window.appState.prefs.noteFontSize = Math.min(20, Math.max(12, window.appState.prefs.noteFontSize + delta));
    persistPreferences();
    Render.applyPreferences();
  }

  function setBackground(color) {
    window.appState.prefs.pageBackground = color;
    persistPreferences();
    Render.applyPreferences();
  }

  function toggleAutoSave() {
    window.appState.prefs.autoSaveEnabled = !window.appState.prefs.autoSaveEnabled;
    clearTimeout(window.appState.saveTimer);
    persistPreferences();
    Render.applyPreferences();
    updateAutosaveStatus(window.appState.prefs.autoSaveEnabled ? "Tự động lưu đã bật" : "Tự động lưu đang tắt");
  }

  function toggleDeleteConfirm() {
    window.appState.prefs.confirmDelete = !window.appState.prefs.confirmDelete;
    persistPreferences();
    Render.applyPreferences();
    showToast(window.appState.prefs.confirmDelete ? "Đã bật xác nhận trước khi xóa." : "Đã tắt xác nhận trước khi xóa.");
  }

  function toggleUserDropdown() {
    document.getElementById("userDropdown").classList.toggle("open");
    document.getElementById("userMenuBtn").classList.toggle("open");
  }

  function closeUserDropdown() {
    document.getElementById("userDropdown").classList.remove("open");
    document.getElementById("userMenuBtn").classList.remove("open");
  }

  function goToDashboard() {
    window.location.href = ROUTES.dashboard;
  }

  async function logoutUser() {
    try {
      await Api.logout();
    } catch (error) {
      // Local session cleanup still happens if the server is unreachable.
    }
    Api.clearSession();
    window.location.href = ROUTES.login;
  }

  function bindEvents() {
    document.querySelectorAll(".sb-item[data-filter]").forEach(button => {
      button.addEventListener("click", () => activateFilter(button.dataset.filter));
    });

    document.getElementById("labelSidebar").addEventListener("click", handleSidebarLabelClick);
    document.getElementById("searchInput").addEventListener("input", event => scheduleSearch(event.target.value));
    document.getElementById("notesContainer").addEventListener("click", handleRenderedNoteClick);
    document.getElementById("pinnedContainer").addEventListener("click", handleRenderedNoteClick);
    document.getElementById("newNoteBtn").addEventListener("click", openCreateNote);
    document.getElementById("settingsBtn").addEventListener("click", () => openDrawer("settingsDrawer"));
    document.getElementById("noteTitleInput").addEventListener("input", scheduleEditorAutoSave);
    document.getElementById("noteContentInput").addEventListener("input", scheduleEditorAutoSave);
    document.getElementById("saveNoteBtn").addEventListener("click", saveEditorNote);
    document.getElementById("noteLabelBtn").addEventListener("click", event => {
      event.stopPropagation();
      document.getElementById("noteLabelPicker").classList.toggle("open");
    });
    document.getElementById("noteLabelPicker").addEventListener("click", handleEditorLabelPickerClick);
    document.getElementById("editorAddImageBtn").addEventListener("click", () => document.getElementById("editorImageInput").click());
    document.getElementById("editorImageInput").addEventListener("change", handleEditorImageSelect);
    document.getElementById("gridBtn").addEventListener("click", () => setView("grid"));
    document.getElementById("listBtn").addEventListener("click", () => setView("list"));
    document.getElementById("sortSelect").addEventListener("change", event => setSort(event.target.value));
    document.getElementById("confirmDeleteBtn").addEventListener("click", confirmDeleteCurrentNote);
    document.getElementById("addShareBtn").addEventListener("click", addShare);
    document.querySelectorAll(".pt-btn").forEach(button => {
      button.addEventListener("click", () => selectPermission(button.dataset.perm));
    });
    document.getElementById("savePasswordBtn").addEventListener("click", savePassword);
    document.getElementById("disablePasswordBtn").addEventListener("click", disablePassword);
    document.getElementById("confirmUnlockBtn").addEventListener("click", confirmUnlock);
    document.getElementById("openLabelMgrBtn").addEventListener("click", () => openOverlay("labelMgrOverlay"));
    document.getElementById("addLabelBtn").addEventListener("click", addLabel);
    document.getElementById("labelMgrList").addEventListener("click", handleLabelManagerClick);
    document.getElementById("fontMinusBtn").addEventListener("click", () => changeFontSize(-1));
    document.getElementById("fontPlusBtn").addEventListener("click", () => changeFontSize(1));
    document.getElementById("darkToggle").addEventListener("click", toggleDarkMode);
    document.getElementById("autosaveToggle").addEventListener("click", toggleAutoSave);
    document.getElementById("deleteConfirmToggle").addEventListener("click", toggleDeleteConfirm);
    document.querySelectorAll(".bg-chip").forEach(button => {
      button.addEventListener("click", () => setBackground(button.dataset.bg));
    });
    document.getElementById("userMenuBtn").addEventListener("click", event => {
      event.stopPropagation();
      toggleUserDropdown();
    });
    document.getElementById("profileBtn").addEventListener("click", goToDashboard);
    document.getElementById("logoutBtn").addEventListener("click", logoutUser);

    document.querySelectorAll("[data-close-overlay]").forEach(button => {
      button.addEventListener("click", () => closeOverlay(button.dataset.closeOverlay));
    });

    document.querySelectorAll("[data-close-drawer]").forEach(button => {
      button.addEventListener("click", () => closeDrawer(button.dataset.closeDrawer));
    });

    document.querySelectorAll(".overlay").forEach(overlay => {
      overlay.addEventListener("click", event => {
        if (event.target === overlay) overlay.classList.remove("open");
      });
    });

    document.addEventListener("click", event => {
      if (!event.target.closest(".picker-wrap")) closePickers();
      if (!event.target.closest(".user-menu-wrap")) closeUserDropdown();
    });

    document.addEventListener("keydown", event => {
      if (event.key === "Escape") {
        document.querySelectorAll(".overlay.open").forEach(overlay => overlay.classList.remove("open"));
        closeDrawer("settingsDrawer");
        closePickers();
        closeUserDropdown();
      }
    });

    window.addEventListener("online", async () => {
      showToast("Đã có kết nối, đang đồng bộ dữ liệu offline...");
      await syncOfflineQueue();
      await syncFromBackend();
    });

    window.addEventListener("offline", () => {
      window.appState.offline = true;
      showToast("Đang offline. Thao tác ghi chú sẽ được lưu trên trình duyệt.", "error");
    });
  }

  function bindForegroundSyncEvents() {
    window.addEventListener("focus", refreshFromBackendWhenVisible);
    document.addEventListener("visibilitychange", () => {
      if (document.visibilityState === "visible") refreshFromBackendWhenVisible();
    });

    window.addEventListener("storage", event => {
      if (event.key !== STORAGE_KEYS.session) {
        return;
      }

      initializeCachedState();
      Render.renderAll();
      refreshFromBackendWhenVisible();
    });
  }

  function initPage() {
    const activatedFromEmail = consumeActivationSession();
    if (!requireAuthSession()) return;
    initializeCachedState();
    bindEvents();
    bindForegroundSyncEvents();
    Render.renderAll();
    if (sessionStorage.getItem("notenoty_activation_success") === "1") {
      sessionStorage.removeItem("notenoty_activation_success");
      showToast("Tài khoản đã được kích hoạt. Chào mừng em quay lại NoteNoty.");
    }
    updateAutosaveStatus(window.appState.prefs.autoSaveEnabled ? "Chờ nhập nội dung để tự lưu" : "Tự động lưu đang tắt");
    syncFromBackend();
    if (activatedFromEmail) {
      window.setTimeout(refreshFromBackendWhenVisible, 500);
    }
  }

  window.selectEditorColor = selectEditorColor;
  window.toggleEditorLabel = toggleEditorLabel;
  window.removeEditorImage = removeEditorImage;
  window.openZoomImage = openZoomImage;
  window.activateLabelFilter = activateLabelFilter;
  window.handleNoteSelection = handleNoteSelection;
  window.openShare = openShare;
  window.openConfirmDelete = openConfirmDelete;
  window.openPasswordManager = openPasswordManager;
  window.togglePin = togglePin;
  window.revokeShare = revokeShare;
  window.updateSharePermission = updateSharePermission;
  window.saveLabelName = saveLabelName;
  window.deleteLabel = deleteLabel;
  window.NoteWiseActions = {
    getRealtimeNoteIds: realtimeSubscriptionNoteIds,
    handleRealtimeNoteEvent
  };

  initPage();
})();
