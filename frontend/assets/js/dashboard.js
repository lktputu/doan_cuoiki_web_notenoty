(() => {
  const { STORAGE_KEYS, DEFAULT_NOTES, DEFAULT_PREFS, DEFAULT_USER, ROUTES } = window.NoteWiseData;
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

  function saveUser(user) {
    localStorage.setItem(STORAGE_KEYS.user, JSON.stringify(user));
  }

  function saveBootstrap(data) {
    if (data.user) localStorage.setItem(STORAGE_KEYS.user, JSON.stringify(data.user));
    if (data.notes) localStorage.setItem(STORAGE_KEYS.notes, JSON.stringify(data.notes));
    if (data.receivedNotes) localStorage.setItem(STORAGE_KEYS.receivedNotes, JSON.stringify(data.receivedNotes));
    if (data.preferences) localStorage.setItem(STORAGE_KEYS.prefs, JSON.stringify(data.preferences));
    if (data.labels) localStorage.setItem(STORAGE_KEYS.labels, JSON.stringify(data.labels));
  }

  function requireAuthSession() {
    const session = Api.getSession();
    if (!session?.token) {
      window.location.href = ROUTES.login;
      return false;
    }
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

  function setAvatar(targetId, user) {
    const target = document.getElementById(targetId);
    if (!target) return;

    if (user.avatar) {
      target.innerHTML = `<img src="${user.avatar}" alt="Avatar ${user.name}">`;
    } else {
      target.textContent = user.initials || "NN";
    }
  }

  if (!requireAuthSession()) return;

  let notes = loadStored(STORAGE_KEYS.notes, DEFAULT_NOTES);
  let receivedNotes = loadStored(STORAGE_KEYS.receivedNotes, []);
  let prefs = { ...DEFAULT_PREFS, ...loadStored(STORAGE_KEYS.prefs, DEFAULT_PREFS) };
  let user = normalizeUser(loadStored(STORAGE_KEYS.user, DEFAULT_USER));
  let pendingAvatar = user.avatar;
  let removeAvatarRequested = false;

  function renderDashboard() {
    user.initials = deriveInitials(user.name || "NoteNoty User");
    setAvatar("dashboardAvatar", user);
    setAvatar("profileAvatarPreview", { ...user, avatar: pendingAvatar });
    document.getElementById("dashboardName").textContent = user.name;
    document.getElementById("dashboardEmail").textContent = user.email;
    document.getElementById("dashboardJoinedDate").textContent = user.joinedDate || "--/--/----";
    document.getElementById("dashboardVerifyBadge").style.display = user.emailVerified ? "inline-flex" : "none";
    document.getElementById("openPasswordModalBtn").disabled = user.emailVerified === false;
    document.getElementById("openPasswordModalBtn").title = user.emailVerified === false
      ? "Hãy kích hoạt tài khoản tại email đã đăng kí trước khi đổi mật khẩu"
      : "";
    document.getElementById("totalNotesCount").textContent = notes.length;
    document.getElementById("sharedNotesCount").textContent = notes.filter(note => note.shared).length + receivedNotes.length;

    document.getElementById("statsGrid").innerHTML = [
      { label: "Đã ghim", value: notes.filter(note => note.pinned).length },
      { label: "Có mật khẩu", value: notes.filter(note => note.locked).length },
      { label: "Chia sẻ được sửa", value: notes.filter(note => note.shares.some(share => share.perm === "editable")).length },
      { label: "Nhãn đang dùng", value: new Set(notes.flatMap(note => note.labels)).size }
    ].map(stat => `
      <article class="stat-card">
        <div class="stat-label">${stat.label}</div>
        <div class="stat-value">${stat.value}</div>
      </article>
    `).join("");

    document.getElementById("prefsList").innerHTML = [
      { label: "Chế độ giao diện", value: prefs.darkMode ? "Dark mode" : "Light mode" },
      { label: "Cỡ chữ ghi chú", value: `${prefs.noteFontSize}px` },
      { label: "Kiểu hiển thị", value: prefs.view === "grid" ? "Dạng lưới" : "Dạng danh sách" },
      { label: "Tự động lưu", value: prefs.autoSaveEnabled ? "Bật" : "Tắt" }
    ].map(item => `
      <div class="pref-item">
        <div class="pref-label">${item.label}</div>
        <div class="pref-value">${item.value}</div>
      </div>
    `).join("");

    const sharedNotes = notes
      .filter(note => note.shared)
      .sort((a, b) => b.updatedAt - a.updatedAt)
      .slice(0, 5);

    const received = receivedNotes
      .sort((a, b) => b.updatedAt - a.updatedAt)
      .slice(0, 5);

    document.getElementById("sharedNoteList").innerHTML = [...sharedNotes, ...received].length
      ? [
          ...sharedNotes.map(note => `
            <div class="shared-note-item">
              <div class="shared-note-title">${note.title}</div>
              <div class="shared-note-meta">
                ${note.shares.length} người nhận - ${note.shares.some(share => share.perm === "editable") ? "Có người được chỉnh sửa" : "Chỉ chia sẻ xem"}
              </div>
            </div>
          `),
          ...received.map(note => `
            <div class="shared-note-item">
              <div class="shared-note-title">${note.title}</div>
              <div class="shared-note-meta">
                Nhận từ ${note.ownerName || note.ownerEmail || "người chia sẻ"} - ${note.canEdit ? "Editable" : "Read-only"}
              </div>
            </div>
          `)
        ].join("")
      : `<div class="pref-item"><div class="pref-value">Hiện chưa có ghi chú chia sẻ.</div></div>`;
  }

  function openOverlay(id) {
    document.getElementById(id)?.classList.add("open");
  }

  function closeOverlay(id) {
    document.getElementById(id)?.classList.remove("open");
  }

  function populateProfileForm() {
    document.getElementById("profileNameInput").value = user.name;
    document.getElementById("profileAvatarInput").value = "";
    pendingAvatar = user.avatar;
    removeAvatarRequested = false;
    setAvatar("profileAvatarPreview", { ...user, avatar: pendingAvatar });
    document.getElementById("removeAvatarBtn").classList.toggle("hidden", !pendingAvatar);
  }

  async function savePassword() {
    const currentPassword = document.getElementById("currentUserPassword").value;
    const button = document.getElementById("saveUserPasswordBtn");

    if (!currentPassword) {
      showToast("Vui lòng nhập mật khẩu hiện tại.", "error");
      return;
    }

    try {
      button.disabled = true;
      button.textContent = "Đang gửi...";
      const data = await Api.changePassword({
        current_password: currentPassword
      });
      closeOverlay("changePasswordOverlay");
      showToast(data.message || "Email xác nhận đổi mật khẩu đã được gửi.");
    } catch (error) {
      showToast(error.message, "error");
    } finally {
      button.disabled = false;
      button.textContent = "Gửi liên kết xác nhận";
    }
  }

  async function saveProfile() {
    const nextName = document.getElementById("profileNameInput").value.trim();
    const button = document.getElementById("saveProfileBtn");

    if (!nextName) {
      showToast("Tên hiển thị không được để trống.", "error");
      return;
    }

    try {
      button.disabled = true;
      button.textContent = "Đang lưu...";
      const data = await Api.updateProfile({
        name: nextName,
        role: user.role,
        avatar: pendingAvatar && pendingAvatar.startsWith("data:image/") ? pendingAvatar : "",
        remove_avatar: removeAvatarRequested
      });
      user = normalizeUser(data.user);
      pendingAvatar = user.avatar;
      saveUser(user);
      renderDashboard();
      closeOverlay("editProfileOverlay");
      showToast("Đã cập nhật hồ sơ.");
    } catch (error) {
      showToast(error.message, "error");
    } finally {
      button.disabled = false;
      button.textContent = "Lưu thay đổi";
    }
  }

  async function handleAvatarChange(event) {
    const file = event.target.files?.[0];
    if (!file) return;

    if (!file.type.startsWith("image/")) {
      showToast("Chỉ được chọn file ảnh.", "error");
      event.target.value = "";
      return;
    }

    if (file.size > 6 * 1024 * 1024) {
      showToast("Ảnh đại diện cần nhỏ hơn 6MB.", "error");
      event.target.value = "";
      return;
    }

    try {
      showToast("Đang xử lý ảnh đại diện...");
      pendingAvatar = await window.NoteNotyImageTools.imageToDataUrl(file, {
        maxWidth: 640,
        maxHeight: 640,
        quality: 0.84
      });
      removeAvatarRequested = false;
      setAvatar("profileAvatarPreview", { ...user, avatar: pendingAvatar });
      document.getElementById("removeAvatarBtn").classList.remove("hidden");
    } catch (error) {
      showToast(error.message || "Không thể xử lý ảnh đại diện.", "error");
      event.target.value = "";
    }
  }

  function removeAvatar() {
    pendingAvatar = "";
    removeAvatarRequested = true;
    document.getElementById("profileAvatarInput").value = "";
    setAvatar("profileAvatarPreview", { ...user, avatar: "" });
    document.getElementById("removeAvatarBtn").classList.add("hidden");
    showToast("Đã chọn xóa ảnh đại diện, bấm Lưu thay đổi để cập nhật.");
  }

  async function syncFromBackend() {
    try {
      const data = await Api.bootstrap();
      user = normalizeUser(data.user || user);
      notes = data.notes || [];
      receivedNotes = data.receivedNotes || [];
      prefs = { ...DEFAULT_PREFS, ...(data.preferences || {}) };
      saveBootstrap(data);
      renderDashboard();
    } catch (error) {
      showToast("Đang hiển thị dữ liệu cache offline.", "error");
    }
  }

  function bindEvents() {
    document.getElementById("openPasswordModalBtn").addEventListener("click", () => {
      if (user.emailVerified === false) {
        showToast("Hãy kích hoạt tài khoản tại email đã đăng kí trước khi đổi mật khẩu.", "error");
        return;
      }
      document.getElementById("currentUserPassword").value = "";
      openOverlay("changePasswordOverlay");
    });

    document.getElementById("openProfileModalBtn").addEventListener("click", () => {
      populateProfileForm();
      openOverlay("editProfileOverlay");
    });

    document.getElementById("saveUserPasswordBtn").addEventListener("click", savePassword);
    document.getElementById("saveProfileBtn").addEventListener("click", saveProfile);
    document.getElementById("profileAvatarInput").addEventListener("change", handleAvatarChange);
    document.getElementById("removeAvatarBtn").addEventListener("click", removeAvatar);

    document.querySelectorAll("[data-close-overlay]").forEach(button => {
      button.addEventListener("click", () => closeOverlay(button.dataset.closeOverlay));
    });

    document.querySelectorAll(".overlay").forEach(overlay => {
      overlay.addEventListener("click", event => {
        if (event.target === overlay) closeOverlay(overlay.id);
      });
    });
  }

  renderDashboard();
  bindEvents();
  syncFromBackend();
})();
