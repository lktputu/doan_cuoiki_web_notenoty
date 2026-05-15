<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
</head>
<body style="margin:0;padding:0;background:#f5efff;font-family:Arial,'Helvetica Neue',sans-serif;color:#2f2847;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f5efff;padding:42px 16px;">
        <tr>
            <td align="center">
                <div style="display:inline-block;padding:14px 28px;border-radius:16px;background:#7d72cc;color:#fff;font-size:22px;font-weight:900;letter-spacing:.5px;">
                    {{ $brand }}
                </div>

                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:540px;margin:28px auto 0;background:#fff;border:1px solid #d8cef9;border-top:6px solid #8b7cf0;border-radius:20px;box-shadow:0 22px 56px rgba(99,75,180,.12);overflow:hidden;">
                    <tr>
                        <td align="center" style="padding:38px 36px 32px;">
                            <div style="width:68px;height:68px;border-radius:50%;background:#eee8ff;border:1px solid #cfc3ff;line-height:68px;text-align:center;font-size:30px;margin:0 auto 22px;color:#7d72cc;">
                                &#9993;
                            </div>

                            <h1 style="margin:0 0 16px;font-size:26px;line-height:1.25;color:#2b2542;font-weight:900;">
                                {{ $title }}
                            </h1>

                            <p style="margin:0 0 8px;font-size:15px;line-height:1.7;color:#6b6384;font-weight:700;">
                                {{ $hello }}
                            </p>

                            <p style="margin:0 auto 26px;max-width:430px;font-size:15px;line-height:1.8;color:#6b6384;">
                                {{ $body }}
                            </p>

                            <a href="{{ $actionUrl }}" style="display:inline-block;padding:16px 34px;border-radius:14px;background:#7d72cc;color:#fff;text-decoration:none;font-size:15px;font-weight:900;box-shadow:0 14px 26px rgba(125,114,204,.28);">
                                {{ $buttonText }}
                            </a>

                            <div style="height:1px;background:#eee9ff;margin:32px 0 22px;"></div>

                            <p style="margin:0 0 10px;font-size:12px;line-height:1.6;color:#8b84a3;">
                                Nếu nút không hoạt động, hãy sao chép và mở liên kết này:
                            </p>

                            <p style="margin:0;padding:10px 12px;border-radius:10px;background:#f8f5ff;border:1px solid #e3dafd;color:#6a5fc2;font-size:12px;line-height:1.6;word-break:break-all;">
                                {{ $actionUrl }}
                            </p>

                            <div style="margin-top:26px;padding:16px 18px;border-radius:14px;background:rgba(125,114,204,.08);border:1px solid rgba(125,114,204,.22);color:#675f84;font-size:13px;line-height:1.7;">
                                {{ $note }}
                            </div>
                        </td>
                    </tr>
                </table>

                <p style="margin:26px 0 0;color:#8f86aa;font-size:13px;line-height:1.7;">
                    Email này được gửi bởi <strong style="color:#7d72cc;">NoteNoty</strong> - trợ lý ghi chú của bạn.
                </p>
                <p style="margin:8px 0 0;color:#b8b0ca;font-size:12px;">
                    © 2026 NoteNoty. All rights reserved.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
