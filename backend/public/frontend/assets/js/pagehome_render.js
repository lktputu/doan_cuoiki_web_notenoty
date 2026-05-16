(() => {
  const { COLORS } = window.NoteWiseData;

  function escapeHtml(text = "") {
    return String(text ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#39;");
  }

  function jsValue(value) {
    return JSON.stringify(String(value));
  }

  function idsEqual(a, b) {
    return String(a) === String(b) || Number(a) === Number(b);
  }

  function safeArray(value) {
    return Array.isArray(value) ? value : [];
  }

  function dataValue(value) {
    return escapeHtml(String(value ?? ""));
  }

  function highlightText(text = "") {
    const query = window.appState.searchQ.trim();
    const safeText = escapeHtml(text);
    if (!query) return safeText;

    const pattern = query.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
    return safeText.replace(new RegExp(`(${pattern})`, "gi"), "<mark>$1</mark>");
  }

  function formatDate(timestamp) {
    if (!timestamp) return "Vừa xong";

    const diff = Date.now() - timestamp;
    const hour = 60 * 60 * 1000;
    const day = 24 * hour;

    if (diff < hour) {
      const minutes = Math.max(1, Math.round(diff / (60 * 1000)));
      return `${minutes} phút trước`;
    }

    if (diff < day) {
      const hours = Math.round(diff / hour);
      return `${hours} giờ trước`;
    }

    return new Intl.DateTimeFormat("vi-VN", {
      day: "2-digit",
      month: "2-digit",
      year: "numeric",
      hour: "2-digit",
      minute: "2-digit"
    }).format(timestamp);
  }

  function getColorByClass(cls) {
    return COLORS.find(color => color.cls === cls) || COLORS[0];
  }

  function getLabelById(id) {
    return window.appState.labels.find(label => String(label.id) === String(id) || Number(label.id) === Number(id));
  }

  function setAvatar(targetId, user, extraClass = "") {
    const target = document.getElementById(targetId);
    if (!target) return;

    if (user.avatar) {
      target.innerHTML = `<img src="${escapeHtml(user.avatar)}" alt="Avatar ${escapeHtml(user.name)}" class="avatar-image ${extraClass}">`;
    } else {
      target.textContent = user.initials || "NN";
    }
  }

  function renderColorStrip(containerId, selectedClass) {
    const container = document.getElementById(containerId);
    if (!container) return;

    container.innerHTML = COLORS.map(color => `
      <button
        class="color-dot ${selectedClass === color.cls ? "sel" : ""}"
        type="button"
        style="background:${color.cssVar};${color.cls === "nc-wht" ? "border:1px solid var(--border);" : ""}"
        onclick="selectEditorColor('${color.cls}', this)">
      </button>
    `).join("");
  }

  function renderSelectedLabels(targetId, selectedIds) {
    const container = document.getElementById(targetId);
    if (!container) return;

    container.innerHTML = safeArray(selectedIds)
      .map(getLabelById)
      .filter(Boolean)
      .map(label => `
        <span class="tag-pill" style="background:${label.color}18;color:${label.color};border:1px solid ${label.color}44">
          ${escapeHtml(label.name)}
        </span>
      `)
      .join("");
  }

  function renderLabelPicker(targetId, selectedIds) {
    const container = document.getElementById(targetId);
    if (!container) return;

    if (!window.appState.labels.length) {
      container.innerHTML = `<div style="font-size:12px;color:var(--text-3)">Chưa có nhãn nào.</div>`;
      return;
    }

    selectedIds = safeArray(selectedIds);
    container.innerHTML = window.appState.labels.map(label => `
      <button
        class="chip-option ${selectedIds.some(id => idsEqual(id, label.id)) ? "active" : ""}"
        type="button"
        style="background:${label.color}18;color:${label.color}"
        data-label-action="toggle-editor"
        data-label-id="${dataValue(label.id)}">
        ${escapeHtml(label.name)}
      </button>
    `).join("");
  }

  function renderImagePreview(targetId, images, mode) {
    const container = document.getElementById(targetId);
    if (!container) return;

    if (!images.length) {
      container.innerHTML = `<div class="empty-preview-text">Chưa có ảnh nào được thêm.</div>`;
      return;
    }

    container.innerHTML = images.map((src, index) => `
      <div class="thumb">
        <img src="${escapeHtml(src)}" alt="Ảnh ghi chú ${index + 1}" onclick="openZoomImage('${String(src).replace(/'/g, "\\'")}')">
        ${mode === "editor" ? `<button class="thumb-remove" type="button" onclick="removeEditorImage(${index})"><i class="ti ti-x"></i></button>` : ""}
      </div>
    `).join("");
  }

  function renderSidebarLabels() {
    const container = document.getElementById("labelSidebar");
    if (!container) return;

    container.innerHTML = window.appState.labels.map(label => `
      <button class="sb-item ${idsEqual(window.appState.filterLabel, label.id) ? "active" : ""}" type="button" data-label-action="filter" data-label-id="${dataValue(label.id)}">
        <span class="sb-label-dot" style="background:${label.color}"></span>
        ${escapeHtml(label.name)}
        <span class="sb-count">${window.appState.notes.filter(note => safeArray(note.labels).some(id => idsEqual(id, label.id))).length}</span>
      </button>
    `).join("");
  }

  function renderLabelManager() {
    const container = document.getElementById("labelMgrList");
    if (!container) return;

    container.innerHTML = window.appState.labels.map(label => `
      <div class="label-manage-row">
        <span class="label-dot-m" style="background:${label.color}"></span>
        <input class="label-row-input" id="labelInput-${label.id}" value="${escapeHtml(label.name)}">
        <div class="label-row-actions">
          <button class="secondary-btn" type="button" data-label-action="save" data-label-id="${dataValue(label.id)}"><i class="ti ti-device-floppy"></i></button>
          <button class="secondary-btn" type="button" data-label-action="delete" data-label-id="${dataValue(label.id)}"><i class="ti ti-trash"></i></button>
        </div>
      </div>
    `).join("");
  }

  function renderSharedList() {
    const container = document.getElementById("sharedList");
    if (!container) return;

    const note = window.appState.notes.find(item => idsEqual(item.id, window.appState.shareId));
    const shares = safeArray(note?.shares);
    if (!note || !shares.length) {
      container.innerHTML = `<div style="font-size:13px;color:var(--text-3)">Chưa chia sẻ với ai.</div>`;
      return;
    }

    container.innerHTML = shares.map(share => `
      <div class="shared-row">
        <div class="shared-avatar">${escapeHtml(share.email?.[0]?.toUpperCase() || "U")}</div>
        <div class="shared-meta">
          <div class="shared-email">${escapeHtml(share.email)}</div>
          <div class="shared-sub">Chia sẻ lúc ${formatDate(share.sharedAt)}</div>
        </div>
        <div class="shared-controls">
          <select class="shared-select" onchange="updateSharePermission(${share.id}, this.value)">
            <option value="readonly" ${share.perm === "readonly" ? "selected" : ""}>Chỉ xem</option>
            <option value="editable" ${share.perm === "editable" ? "selected" : ""}>Chỉnh sửa</option>
          </select>
          <button class="revoke-btn" type="button" onclick="revokeShare(${share.id})">
            <i class="ti ti-x"></i>
          </button>
        </div>
      </div>
    `).join("");
  }

  function getVisibleNotes() {
    const { notes, receivedNotes, filter, filterLabel, searchQ, prefs } = window.appState;
    let visible = filter === "shared"
      ? [...notes.filter(note => note.shared), ...(receivedNotes || [])]
      : [...notes];

    if (filterLabel) visible = visible.filter(note => safeArray(note.labels).some(id => idsEqual(id, filterLabel)));
    if (filter === "pinned") visible = visible.filter(note => note.pinned);
    if (filter === "locked") visible = visible.filter(note => note.locked);

    if (searchQ) {
      const lowered = searchQ.toLowerCase();
      visible = visible.filter(note =>
        String(note.title || "").toLowerCase().includes(lowered) ||
        String(note.content || "").toLowerCase().includes(lowered)
      );
    }

    visible.sort((a, b) => {
      if (a.pinned && b.pinned) {
        return (b.pinnedAt || 0) - (a.pinnedAt || 0);
      }

      if (prefs.sort === "alpha") return String(a.title || "").localeCompare(String(b.title || ""), "vi");
      const key = prefs.sort === "created" ? "createdAt" : "updatedAt";
      return (b[key] || 0) - (a[key] || 0);
    });

    return visible;
  }

  function ownerActions(note, compact = false) {
    if (note.received) return "";
    const noteId = dataValue(note.id);

    if (compact) {
      return `
        <button class="note-action-btn lock ${note.locked ? "active" : ""}" type="button" data-note-action="password" data-note-id="${noteId}">
          <i class="ti ${note.locked ? "ti-lock-filled" : "ti-lock"}"></i>
        </button>
        <button class="note-action-btn pin ${note.pinned ? "active" : ""}" type="button" data-note-action="pin" data-note-id="${noteId}">
          <i class="ti ti-pin"></i>
        </button>
        <button class="note-action-btn" type="button" data-note-action="share" data-note-id="${noteId}">
          <i class="ti ti-share"></i>
        </button>
        <button class="note-action-btn" type="button" data-note-action="delete" data-note-id="${noteId}">
          <i class="ti ti-trash"></i>
        </button>
      `;
    }

    return `
      <button class="note-action-btn pin ${note.pinned ? "active" : ""}" type="button" data-note-action="pin" data-note-id="${noteId}">
        <i class="ti ti-pin"></i>
      </button>
      <button class="note-action-btn" type="button" data-note-action="share" data-note-id="${noteId}">
        <i class="ti ti-share"></i>
      </button>
      <button class="note-action-btn" type="button" data-note-action="delete" data-note-id="${noteId}">
        <i class="ti ti-trash"></i>
      </button>
    `;
  }

  function renderStatusIcons(note) {
    return [
      note.locked ? `<span class="si si-lock" title="Có mật khẩu"><i class="ti ti-lock"></i></span>` : "",
      (note.shared || note.received) ? `<span class="si si-share" title="Đã chia sẻ"><i class="ti ti-share"></i></span>` : "",
      note.pinned ? `<span class="si si-pin" title="Đã ghim"><i class="ti ti-pin-filled"></i></span>` : ""
    ].join("");
  }

  function renderCard(note) {
    const noteId = dataValue(note.id);
    const labels = safeArray(note.labels);
    const images = safeArray(note.images);
    const labelsHtml = labels
      .map(getLabelById)
      .filter(Boolean)
      .map(label => `
        <span class="note-lbl" style="background:${label.color}22;color:${label.color};border:1px solid ${label.color}44">
          ${escapeHtml(label.name)}
        </span>
      `)
      .join("");

    return `
      <article class="note-card ${note.color}" data-note-action="open" data-note-id="${noteId}">
        ${note.pinned ? `<div class="pin-ribbon"><i class="ti ti-pin-filled"></i></div>` : ""}
        ${note.images.length ? `<div class="note-img"><img src="${escapeHtml(note.images[0])}" alt="Ảnh ghi chú"></div>` : ""}
        <div class="note-head">
          <div class="note-title">${highlightText(note.title || "Không có tiêu đề")}</div>
        </div>
        ${labelsHtml ? `<div class="note-labels">${labelsHtml}</div>` : ""}
        ${note.received ? `<div class="note-labels"><span class="note-lbl">Từ ${escapeHtml(note.ownerName || note.ownerEmail || "người chia sẻ")} - ${note.canEdit ? "Editable" : "Read-only"}</span></div>` : ""}
        <div class="note-body ${note.locked ? "locked" : ""}">
          ${note.locked ? "••••••••••••" : highlightText(note.content || "Chưa có nội dung")}
        </div>
        <div class="note-foot">
          <div class="note-foot-left">
            ${note.received ? "" : `<button class="note-action-btn lock ${note.locked ? "active" : ""}" type="button" data-note-action="password" data-note-id="${noteId}">
              <i class="ti ${note.locked ? "ti-lock-filled" : "ti-lock"}"></i>
            </button>`}
            <span class="note-date">${formatDate(note.updatedAt)}</span>
          </div>
          <div class="status-icons">${renderStatusIcons(note)}</div>
          <div class="note-actions">${ownerActions(note)}</div>
        </div>
      </article>
    `;
  }

  function renderRow(note) {
    const noteId = dataValue(note.id);
    return `
      <article class="note-row" data-note-action="open" data-note-id="${noteId}">
        <div class="row-color-bar" style="background:${getColorByClass(note.color).hex}"></div>
        <div class="row-main">
          <div class="row-title">${highlightText(note.title || "Không có tiêu đề")}</div>
          <div class="row-preview">${note.locked ? "•••••••••••••••" : escapeHtml(note.content || "Chưa có nội dung")}</div>
        </div>
        <div class="row-right">
          <div class="status-icons">${renderStatusIcons(note)}</div>
          <span class="note-date">${formatDate(note.updatedAt)}</span>
          ${ownerActions(note, true)}
        </div>
      </article>
    `;
  }

  function renderNotes() {
    const visibleNotes = getVisibleNotes();
    const pinned = visibleNotes.filter(note => note.pinned);
    const regular = visibleNotes.filter(note => !note.pinned);
    const wrapClass = window.appState.prefs.view === "grid" ? "notes-grid" : "notes-list";
    const renderer = window.appState.prefs.view === "grid" ? renderCard : renderRow;

    const pinnedSection = document.getElementById("pinnedSection");
    const pinnedContainer = document.getElementById("pinnedContainer");
    const notesContainer = document.getElementById("notesContainer");

    pinnedSection.style.display = pinned.length && window.appState.filter !== "pinned" ? "" : "none";
    pinnedContainer.className = wrapClass;
    notesContainer.className = wrapClass;
    pinnedContainer.innerHTML = pinned.map(renderer).join("");
    notesContainer.innerHTML = regular.length
      ? regular.map(renderer).join("")
      : `<div class="empty"><i class="ti ti-mood-empty"></i><p>Không tìm thấy ghi chú nào phù hợp.</p></div>`;

    document.getElementById("gridBtn").classList.toggle("active", window.appState.prefs.view === "grid");
    document.getElementById("listBtn").classList.toggle("active", window.appState.prefs.view === "list");
  }

  function renderPageTitle() {
    const titleMap = {
      all: "Tất cả <span>ghi chú</span>",
      shared: "Ghi chú <span>được chia sẻ</span>",
      pinned: "Ghi chú <span>đã ghim</span>",
      locked: "Ghi chú <span>có mật khẩu</span>"
    };

    const target = document.getElementById("pageTitle");
    if (window.appState.filterLabel) {
      const label = getLabelById(window.appState.filterLabel);
      target.innerHTML = `Nhãn: <span>${escapeHtml(label?.name || "")}</span>`;
      return;
    }

    target.innerHTML = titleMap[window.appState.filter] || titleMap.all;
  }

  function renderSidebarFilterState() {
    document.querySelectorAll(".sb-item[data-filter]").forEach(button => {
      button.classList.toggle("active", !window.appState.filterLabel && button.dataset.filter === window.appState.filter);
    });
  }

  function updateCounts() {
    const receivedCount = (window.appState.receivedNotes || []).length;
    document.getElementById("allCount").textContent = window.appState.notes.length;
    document.getElementById("sharedCount").textContent = window.appState.notes.filter(note => note.shared).length + receivedCount;
    document.getElementById("pinnedCount").textContent = window.appState.notes.filter(note => note.pinned).length;
    document.getElementById("lockedCount").textContent = window.appState.notes.filter(note => note.locked).length;
  }

  function applyPreferences() {
    const { prefs, user } = window.appState;

    document.body.classList.toggle("dark", !!prefs.darkMode);
    document.documentElement.style.setProperty("--note-font-size", `${prefs.noteFontSize}px`);
    document.documentElement.style.setProperty("--bg-page", prefs.pageBackground);
    document.getElementById("fontLbl").textContent = `${prefs.noteFontSize}px`;
    document.getElementById("sortSelect").value = prefs.sort;
    document.getElementById("darkToggle").classList.toggle("on", !!prefs.darkMode);
    document.getElementById("autosaveToggle").classList.toggle("on", !!prefs.autoSaveEnabled);
    document.getElementById("deleteConfirmToggle").classList.toggle("on", !!prefs.confirmDelete);

    document.querySelectorAll(".bg-chip").forEach(button => {
      button.classList.toggle("sel", button.dataset.bg === prefs.pageBackground);
    });

    setAvatar("userAvatar", user);
    setAvatar("userAvatarLarge", user);
    document.getElementById("userNameNav").textContent = user.name;
    document.getElementById("userDropdownName").textContent = user.name;
    document.getElementById("userDropdownEmail").textContent = user.email;
    document.getElementById("activationBanner")?.classList.toggle("hidden", user.emailVerified !== false);
  }

  function renderEditor() {
    const editor = window.appState.editor;
    const readonly = editor.mode === "edit" && editor.canEdit === false;
    document.getElementById("noteEditorKicker").textContent = readonly ? "Xem ghi chú" : (editor.mode === "edit" ? "Chỉnh sửa ghi chú" : "Ghi chú");
    document.getElementById("noteEditorHeading").textContent = readonly ? "Xem ghi chú được chia sẻ" : (editor.mode === "edit" ? "Chỉnh sửa ghi chú" : "Ghi chú");
    document.getElementById("saveNoteBtn").textContent = editor.mode === "edit" ? "Lưu thay đổi" : "Lưu";
    document.getElementById("saveNoteBtn").disabled = readonly;
    document.getElementById("noteTitleInput").disabled = readonly;
    document.getElementById("noteContentInput").disabled = readonly;
    document.getElementById("editorAddImageBtn").disabled = readonly;
    document.getElementById("noteLabelBtn").disabled = readonly;

    renderColorStrip("noteColorStrip", editor.color);
    renderLabelPicker("noteLabelPicker", editor.labels);
    renderSelectedLabels("noteSelectedLabels", editor.labels);
    renderImagePreview("editorImagePreview", editor.images, readonly ? "viewer" : "editor");
  }

  function renderAll() {
    applyPreferences();
    renderPageTitle();
    renderSidebarFilterState();
    renderSidebarLabels();
    renderNotes();
    renderLabelManager();
    updateCounts();
    renderEditor();
  }

  window.NoteWiseRender = {
    applyPreferences,
    formatDate,
    getColorByClass,
    getLabelById,
    renderAll,
    renderEditor,
    renderImagePreview,
    renderLabelManager,
    renderLabelPicker,
    renderNotes,
    renderPageTitle,
    renderSelectedLabels,
    renderSharedList,
    renderSidebarLabels,
    renderSidebarFilterState,
    setAvatar,
    updateCounts
  };
})();
