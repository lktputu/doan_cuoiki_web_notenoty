<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Tạo mật khẩu mới' }} - NoteNoty</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: radial-gradient(circle at top, #ffffff 0, #f5f0ff 42%, #ece6ff 100%);
            font-family: Arial, "Helvetica Neue", sans-serif;
            color: #2f2847;
        }
        .card {
            width: min(92vw, 430px);
            padding: 40px;
            border-radius: 22px;
            background: rgba(255, 255, 255, 0.88);
            border: 1px solid rgba(125, 114, 204, 0.24);
            border-top: 5px solid #7d72cc;
            box-shadow: 0 24px 70px rgba(73, 54, 151, 0.16);
            backdrop-filter: blur(14px);
        }
        .brand {
            width: fit-content;
            margin: 0 auto 24px;
            padding: 11px 24px;
            border-radius: 16px;
            background: #7d72cc;
            color: #fff;
            font-weight: 900;
            letter-spacing: 0;
        }
        h1 {
            margin: 0 0 8px;
            text-align: center;
            font-size: 26px;
        }
        p {
            margin: 0 0 24px;
            text-align: center;
            color: #6b6384;
            line-height: 1.6;
        }
        .box {
            margin: 18px 0;
        }
        label {
            display: block;
            margin: 0 0 8px;
            color: #5e567b;
            font-size: 13px;
            font-weight: 800;
        }
        input {
            width: 100%;
            height: 48px;
            padding: 0 16px;
            border-radius: 999px;
            border: 1.5px solid #d8cef9;
            outline: none;
            color: #2f2847;
            font-size: 15px;
            background: #fff;
        }
        input:focus {
            border-color: #7d72cc;
            box-shadow: 0 0 0 4px rgba(125, 114, 204, 0.12);
        }
        button {
            width: 100%;
            height: 46px;
            margin-top: 10px;
            border: 0;
            border-radius: 999px;
            background: #7d72cc;
            color: #fff;
            font-size: 15px;
            font-weight: 900;
            cursor: pointer;
        }
        button:disabled {
            opacity: .68;
            cursor: wait;
        }
        .message {
            min-height: 22px;
            margin-top: 16px;
            text-align: center;
            font-size: 14px;
            line-height: 1.6;
            color: #665c92;
        }
        .message.error { color: #b74444; }
        .message.success { color: #5f54b8; }
    </style>
</head>
<body>
    <main class="card">
        <div class="brand">NoteNoty</div>
        <h1>{{ $title ?? 'Tạo mật khẩu mới' }}</h1>
        <p>{{ $subtitle ?? 'Nhập mật khẩu mới cho tài khoản NoteNoty của bạn.' }}</p>

        <form id="resetForm">
            <div class="box">
                <label for="password">Mật khẩu mới</label>
                <input id="password" type="password" minlength="6" autocomplete="new-password" required>
            </div>

            <div class="box">
                <label for="password_confirmation">Xác nhận mật khẩu</label>
                <input id="password_confirmation" type="password" minlength="6" autocomplete="new-password" required>
            </div>

            <button type="submit" id="submitBtn">{{ $buttonText ?? 'Cập nhật mật khẩu' }}</button>
        </form>

        <div id="message" class="message"></div>
    </main>

    <script>
        const apiBase = @json($apiBase);
        const token = @json($token);
        const email = @json($email ?? '');
        const mode = @json($mode ?? 'reset');
        const successMessage = @json($successMessage ?? 'Mật khẩu đã được cập nhật.');
        const loginUrl = @json($loginUrl ?? url('/login'));
        const form = document.getElementById('resetForm');
        const message = document.getElementById('message');
        const submitBtn = document.getElementById('submitBtn');

        function setMessage(text, type = '') {
            message.textContent = text;
            message.className = `message ${type}`.trim();
        }

        form.addEventListener('submit', async event => {
            event.preventDefault();
            setMessage('');

            const password = document.getElementById('password').value;
            const confirmation = document.getElementById('password_confirmation').value;

            if (password.length < 6) {
                setMessage('Mật khẩu cần có ít nhất 6 ký tự.', 'error');
                return;
            }

            if (password !== confirmation) {
                setMessage('Xác nhận mật khẩu chưa khớp.', 'error');
                return;
            }

            const endpoint = mode === 'change' ? '/change-password/complete' : '/reset-password';
            const payload = mode === 'change'
                ? { token, password, password_confirmation: confirmation }
                : { email, token, password, password_confirmation: confirmation };

            try {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Đang cập nhật...';

                const response = await fetch(`${apiBase}${endpoint}`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();
                if (!response.ok || data.success === false) {
                    throw new Error(data.message || 'Không thể cập nhật mật khẩu.');
                }

                setMessage(data.message || successMessage, 'success');
                form.reset();
                window.localStorage?.removeItem('notenoty_session_v1');
                window.setTimeout(() => {
                    window.location.href = loginUrl;
                }, 1300);
            } catch (error) {
                setMessage(error.message, 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = @json($buttonText ?? 'Cập nhật mật khẩu');
            }
        });
    </script>
</body>
</html>
