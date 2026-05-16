const CACHE_NAME = "notenoty-frontend-v1";
const APP_SHELL = [
  "./pagehome.html",
  "./dashboard.html",
  "./manifest.webmanifest",
  "./assets/css/pagehome_ui.css",
  "./assets/css/dashboard.css",
  "./assets/css/logo.png",
  "./assets/js/runtime_config.js",
  "./assets/js/api_client.js",
  "./assets/js/pagehome_data.js",
  "./assets/js/pagehome_render.js",
  "./assets/js/pagehome_actions.js",
  "./assets/js/realtime_client.js",
  "./assets/js/dashboard.js",
  "./assets/js/pwa.js",
  "./login_reggister_forgotpass/login.html",
  "./login_reggister_forgotpass/register.html",
  "./login_reggister_forgotpass/forgot_password_1.html",
  "./login_reggister_forgotpass/auth_flow.js",
  "./login_reggister_forgotpass/text.css",
  "./login_reggister_forgotpass/register_UI.css",
  "./login_reggister_forgotpass/forgot_pw_1_UI.css"
];

self.addEventListener("install", event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(APP_SHELL))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener("activate", event => {
  event.waitUntil(
    caches.keys()
      .then(keys => Promise.all(keys.filter(key => key !== CACHE_NAME).map(key => caches.delete(key))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener("fetch", event => {
  const request = event.request;
  if (request.method !== "GET") return;

  const url = new URL(request.url);
  if (url.pathname.includes("/api/")) return;

  event.respondWith(
    caches.match(request).then(cached => {
      const network = fetch(request)
        .then(response => {
          if (response && response.ok && url.origin === self.location.origin) {
            const clone = response.clone();
            caches.open(CACHE_NAME).then(cache => cache.put(request, clone));
          }
          return response;
        })
        .catch(() => cached || caches.match("./pagehome.html"));

      return cached || network;
    })
  );
});
