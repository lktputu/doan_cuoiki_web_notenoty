(() => {
  window.NoteWiseData = {
    STORAGE_KEYS: {
      notes: "notenoty_notes_v3",
      receivedNotes: "notenoty_received_notes_v1",
      labels: "notenoty_labels_v3",
      prefs: "notenoty_prefs_v3",
      user: "notenoty_user_v3",
      session: "notenoty_session_v1",
      offlineQueue: "notenoty_offline_queue_v1",
      offlineIdMap: "notenoty_offline_id_map_v1"
    },
    ROUTES: {
      home: "pagehome.html",
      dashboard: "dashboard.html",
      login: "login_reggister_forgotpass/login.html"
    },
    COLORS: [
      { cls: "nc-lav", hex: "#7d72cc", cssVar: "var(--c-lav)" },
      { cls: "nc-mint", hex: "#61c8a8", cssVar: "var(--c-mint)" },
      { cls: "nc-pch", hex: "#f5c0a4", cssVar: "var(--c-pch)" },
      { cls: "nc-but", hex: "#f4df8c", cssVar: "var(--c-but)" },
      { cls: "nc-blsh", hex: "#f7bfd4", cssVar: "var(--c-blsh)" },
      { cls: "nc-sky", hex: "#bfdff8", cssVar: "var(--c-sky)" },
      { cls: "nc-wht", hex: "#f7f7f7", cssVar: "var(--bg-card)" }
    ],
    LABEL_PALETTE: [
      "#7d72cc",
      "#61c8a8",
      "#f29e67",
      "#d7b43a",
      "#d76e97",
      "#67a6db",
      "#ed6b6b",
      "#6a7c8f"
    ],
    DEFAULT_LABELS: [],
    DEFAULT_NOTES: [],
    DEFAULT_PREFS: {
      darkMode: false,
      noteFontSize: 14,
      pageBackground: "#f7f3ff",
      view: "grid",
      sort: "modified",
      autoSaveEnabled: true,
      confirmDelete: true
    },
    DEFAULT_USER: {
      name: "NoteNoty User",
      initials: "NN",
      email: "",
      role: "Người dùng NoteNoty",
      avatar: "",
      joinedDate: "",
      emailVerified: false
    }
  };

  window.appState = {
    notes: [],
    receivedNotes: [],
    labels: [],
    prefs: {},
    user: {},
    filter: "all",
    filterLabel: null,
    searchQ: "",
    deleteId: null,
    shareId: null,
    passwordTargetId: null,
    selectedPerm: "readonly",
    saveTimer: null,
    searchTimer: null,
    unlockAction: null,
    passwordMode: "set",
    offline: false,
    editor: {
      mode: "create",
      noteId: null,
      color: "nc-lav",
      labels: [],
      images: []
    }
  };
})();
