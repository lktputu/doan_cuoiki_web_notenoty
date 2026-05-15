(() => {
  const STORAGE_KEYS = {
    user: "notenoty_user_v3"
  };

  const ROUTES = {
    login: "login.html",
    home: "../pagehome.html"
  };

  function showMessage(elementId, message, type = "success") {
    const target = document.getElementById(elementId);
    if (!target) return;
    target.textContent = message;
    target.className = `auth-message ${type}`;
  }

  function clearMessage(elementId) {
    const target = document.getElementById(elementId);
    if (!target) return;
    target.textContent = "";
    target.className = "auth-message";
  }

  function isEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  }

  function cacheUser(user) {
    localStorage.setItem(STORAGE_KEYS.user, JSON.stringify(user));
  }

  function absoluteRoute(route) {
    return new URL(route, window.location.href).href;
  }

  function bindRegister() {
    const form = document.getElementById("registerForm");
    if (!form) return;

    form.addEventListener("submit", async event => {
      event.preventDefault();
      clearMessage("registerMessage");
      const submitButton = form.querySelector("button[type='submit']");

      const name = document.getElementById("registerUsername").value.trim();
      const email = document.getElementById("registerEmail").value.trim().toLowerCase();
      const password = document.getElementById("registerPassword").value;
      const passwordConfirmation = document.getElementById("registerConfirmPassword").value;

      if (!name) {
        showMessage("registerMessage", "Vui lòng nhập username.", "error");
        return;
      }

      if (!isEmail(email)) {
        showMessage("registerMessage", "Email không hợp lệ!", "error");
        return;
      }

      if (password.length < 6) {
        showMessage("registerMessage", "Mật khẩu phải có ít nhất 6 kí tự!", "error");
        return;
      }

      if (password !== passwordConfirmation) {
        showMessage("registerMessage", "Mật khẩu xác nhận không khớp.", "error");
        return;
      }

      try {
        submitButton.disabled = true;
        submitButton.textContent = "Đang đăng kí...";
        await window.NoteNotyApi.register({
          name,
          email,
          password,
          password_confirmation: passwordConfirmation
        });
        submitButton.textContent = "Đang đăng kí...";
        const loginData = await window.NoteNotyApi.login({ email, password });
        window.NoteNotyApi.setSession({
          email,
          token: loginData.token,
          loggedInAt: Date.now()
        });
        cacheUser(loginData.user);
        localStorage.setItem("notenoty_last_email", email);
        window.location.href = ROUTES.home;
      } catch (error) {
        showMessage("registerMessage", error.message, "error");
      } finally {
        submitButton.disabled = false;
        submitButton.textContent = "Đăng kí";
      }
    });
  }

  function bindLogin() {
    const form = document.getElementById("loginForm");
    if (!form) return;

    const lastEmail = localStorage.getItem("notenoty_last_email");
    if (lastEmail) {
      document.getElementById("loginEmail").value = lastEmail;
    }

    form.addEventListener("submit", async event => {
      event.preventDefault();
      clearMessage("loginMessage");
      const submitButton = form.querySelector("button[type='submit']");

      const email = document.getElementById("loginEmail").value.trim().toLowerCase();
      const password = document.getElementById("loginPassword").value;

      if (!isEmail(email)) {
        showMessage("loginMessage", "Email không hợp lệ!", "error");
        return;
      }

      if (!password) {
        showMessage("loginMessage", "Vui lòng nhập mật khẩu", "error");
        return;
      }

      try {
        submitButton.disabled = true;
        submitButton.textContent = "Đang đăng nhập...";
        const data = await window.NoteNotyApi.login({ email, password });
        window.NoteNotyApi.setSession({
          email,
          token: data.token,
          loggedInAt: Date.now()
        });
        cacheUser(data.user);
        localStorage.setItem("notenoty_last_email", email);
        window.location.href = ROUTES.home;
      } catch (error) {
        showMessage("loginMessage", error.message, "error");
      } finally {
        submitButton.disabled = false;
        submitButton.textContent = "Đăng nhập";
      }
    });
  }

  function bindForgotPassword() {
    const form = document.getElementById("forgotForm");
    if (!form) return;

    form.addEventListener("submit", async event => {
      event.preventDefault();
      clearMessage("forgotMessage");
      const submitButton = form.querySelector("button[type='submit']");

      const email = document.getElementById("forgotEmail").value.trim().toLowerCase();

      if (!isEmail(email)) {
        showMessage("forgotMessage", "Email không hợp lệ!", "error");
        return;
      }

      try {
        submitButton.disabled = true;
        submitButton.textContent = "Đang gửi email...";
        const data = await window.NoteNotyApi.forgotPassword({
          email,
          login_url: absoluteRoute(ROUTES.login)
        });
        showMessage("forgotMessage", data.message || "Liên kết khôi phục đã được gửi về email.");
      } catch (error) {
        showMessage("forgotMessage", error.message, "error");
      } finally {
        submitButton.disabled = false;
        submitButton.textContent = "Đã gửi liên kết khôi phục";
      }
    });
  }

  function autoRedirectIfLoggedIn() {
    const session = window.NoteNotyApi.getSession();
    const isAuthPage = window.location.pathname.endsWith("/login.html") || window.location.pathname.endsWith("/register.html");
    const force = new URLSearchParams(window.location.search).has("force");

    if (session?.token && isAuthPage && !force) {
      window.location.href = ROUTES.home;
    }
  }

  autoRedirectIfLoggedIn();
  bindRegister();
  bindLogin();
  bindForgotPassword();
})();
