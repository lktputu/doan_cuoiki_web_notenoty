(() => {
  if (window.NoteNotyConfig?.enablePwa === false) return;
  if (!("serviceWorker" in navigator)) return;
  if (!window.location.protocol.startsWith("http")) return;

  const scriptSrc = document.currentScript?.src || new URL("assets/js/pwa.js", window.location.href).href;

  window.addEventListener("load", () => {
    const serviceWorkerUrl = new URL("../../sw.js", scriptSrc);
    const scope = new URL("../../", scriptSrc);

    navigator.serviceWorker
      .register(serviceWorkerUrl, { scope: scope.pathname })
      .catch(() => {
        // PWA is a progressive enhancement; app logic still works without it.
      });
  });
})();
