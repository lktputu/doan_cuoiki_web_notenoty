# NoteNoty

## Local run

1. Start MySQL/XAMPP and make sure `backend/.env` points to database `note_app_db`.
2. In `backend`, run Laravel API:

```bash
cd backend
php artisan migrate
php artisan config:clear
php artisan serve --host=127.0.0.1 --port=8000
```

If port `8000` is busy, port `8010` is also supported by the frontend fallback.

3. In another terminal, start the realtime WebSocket relay:

```bash
cd backend
npm run realtime
```

By default it listens on `ws://127.0.0.1:8011` and receives Laravel broadcasts through `http://127.0.0.1:8011/broadcast`.

4. Open the frontend page:

```text
frontend/login_reggister_forgotpass/login.html
```

The frontend calls Laravel API routes under `/api/...` and caches the latest data in `localStorage` for offline viewing.

For local testing with PWA/service worker, open the `frontend` folder through a local HTTP server such as VS Code Live Server. Service workers do not run from `file://`.

## Deployment checklist

Before pushing NoteNoty to the internet:

1. Copy `backend/.env.production.example` to the production environment variables and set real values:
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `APP_URL=https://your-backend-domain.com`
   - `NOTE_NOTY_HOME_URL=https://your-frontend-domain.com/pagehome.html`
   - `NOTE_NOTY_LOGIN_URL=https://your-frontend-domain.com/login_reggister_forgotpass/login.html`
   - `NOTE_NOTY_REALTIME_HTTP_URL=https://your-realtime-domain.com`
   - `NOTE_NOTY_REALTIME_SECRET` to a long private random string
2. Do not commit `backend/.env`. If a Gmail app password was ever committed or shared, rotate it in Google Account settings.
3. Edit `frontend/assets/js/runtime_config.js` for the deployed frontend:

```js
window.NoteNotyConfig = {
  apiBase: "https://your-backend-domain.com/api",
  realtimeWsBase: "wss://your-realtime-domain.com",
  enablePwa: true
};
```

4. Deploy the Laravel backend using PHP 8.2 or newer. The included `backend/Dockerfile` now uses `php:8.2-apache`.
5. Deploy the realtime relay as a separate Node process:

```bash
cd backend
npm run realtime
```

Set the same `NOTE_NOTY_REALTIME_SECRET` for Laravel and the realtime process.
6. After deploy, run:

```bash
php artisan migrate --force
php artisan config:clear
php artisan route:clear
```

7. Test with two different accounts in two browsers:
   - Owner shares a note as editable.
   - Receiver edits title/content/color/images.
   - Owner sees realtime update without reload.
   - Owner changes receiver to read-only and receiver can no longer edit.

## Email features

The app now sends email for:

- Account activation after registration.
- Forgot password reset link.
- Confirming account password changes from dashboard.

For Gmail SMTP, use an app password in `backend/.env`:

```text
MAIL_DRIVER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_gmail_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your_email@gmail.com
MAIL_FROM_NAME="NoteNoty"
NOTE_NOTY_AUTO_ACTIVATE=false
```

Unactivated users can still log in and use basic notes, but sharing and account password changes are blocked until they click the activation email.

## Important paths

- Auth UI: `frontend/login_reggister_forgotpass`
- Main notes UI: `frontend/pagehome.html`
- Dashboard UI: `frontend/dashboard.html`
- Frontend API client: `frontend/assets/js/api_client.js`
- Laravel API controller: `backend/app/Http/Controllers/ApiController.php`
- API routes: `backend/routes/api.php`
