# NoteNoty

## Local run

1. Start MySQL/XAMPP and make sure `backend/.env` points to database `note_app_db`.
2. In `backend`, run:

```bash
php artisan migrate
php artisan config:clear
php artisan serve --host=127.0.0.1 --port=8000
```

If port `8000` is busy, port `8010` is also supported by the frontend fallback.

3. Open the frontend page:

```text
frontend/login_reggister_forgotpass/login.html
```

The frontend calls Laravel API routes under `/api/...` and caches the latest data in `localStorage` for offline viewing.

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
