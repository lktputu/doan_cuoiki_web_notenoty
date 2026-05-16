window.NoteNotyConfig = {
  // Local default: keep empty so NoteNoty tries the current origin first,
  // then falls back to http://127.0.0.1:8000/api and http://127.0.0.1:8010/api.
  // Deployment example: apiBase: "https://your-backend-domain.com/api"
  apiBase: "",

  // Local default: keep empty so NoteNoty tries ws(s)://current-host:8011,
  // then falls back to ws://127.0.0.1:8011.
  // Deployment example: realtimeWsBase: "wss://your-realtime-domain.com"
  realtimeWsBase: "",

  enablePwa: true
};
