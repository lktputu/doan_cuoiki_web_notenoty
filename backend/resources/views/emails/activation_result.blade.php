<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} - NoteNoty</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: radial-gradient(circle at top, #fff 0, #f4efff 42%, #eee8ff 100%);
            font-family: Arial, "Helvetica Neue", sans-serif;
            color: #2f2847;
        }
        .card {
            width: min(92vw, 520px);
            padding: 38px 34px;
            border-radius: 24px;
            background: rgba(255,255,255,.94);
            border: 1px solid #d8cef9;
            border-top: 6px solid #7d72cc;
            text-align: center;
            box-shadow: 0 22px 56px rgba(99,75,180,.13);
        }
        .brand {
            display: inline-flex;
            padding: 12px 24px;
            margin-bottom: 26px;
            border-radius: 16px;
            background: #7d72cc;
            color: #fff;
            font-size: 20px;
            font-weight: 900;
        }
        .icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 18px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: {{ $success ? '#eee8ff' : '#fff0f0' }};
            color: {{ $success ? '#7d72cc' : '#de5a57' }};
            font-size: 30px;
        }
        h1 { margin: 0 0 12px; font-size: 26px; }
        p { margin: 0; color: #6b6384; line-height: 1.8; }
    </style>
</head>
<body>
    <main class="card">
        <div class="brand">NoteNoty</div>
        <div class="icon">{!! $success ? '&#10003;' : '!' !!}</div>
        <h1>{{ $title }}</h1>
        <p>{{ $message }}</p>
    </main>
</body>
</html>
