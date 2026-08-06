<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Temporary Password</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif; color: #18181b; }
        .wrap { max-width: 540px; margin: 48px auto; padding: 0 16px; }
        .card { background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,.08), 0 4px 16px rgba(0,0,0,.04); }
        .header { background: #18181b; padding: 32px 40px; }
        .header h1 { color: #fafafa; font-size: 16px; font-weight: 600; }
        .header p { color: #a1a1aa; font-size: 13px; margin-top: 4px; }
        .body { padding: 40px; }
        .greeting { font-size: 15px; color: #3f3f46; margin-bottom: 8px; }
        .copy { font-size: 14px; color: #71717a; margin-bottom: 28px; line-height: 1.5; }
        .password-wrapper { background: #f9f9f9; border: 1px solid #e4e4e7; border-radius: 10px; padding: 24px 20px; text-align: center; margin-bottom: 28px; }
        .password-label { font-size: 11px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: #71717a; margin-bottom: 12px; }
        .password-code { font-size: 24px; font-weight: 700; color: #09090b; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', monospace; word-break: break-all; }
        .divider { border: none; border-top: 1px solid #f4f4f5; margin: 8px 0 24px; }
        .note { font-size: 13px; color: #a1a1aa; line-height: 1.6; }
        .footer { background: #fafafa; border-top: 1px solid #f4f4f5; padding: 20px 40px; text-align: center; }
        .footer p { font-size: 12px; color: #a1a1aa; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <div class="header">
            <h1>{{ config('app.name') }}</h1>
            <p>Temporary Password</p>
        </div>

        <div class="body">
            <p class="greeting">Hi {{ $user->name }},</p>
            <p class="copy">
                An administrator reset your account password. Use the temporary password below to sign in.
            </p>

            <div class="password-wrapper">
                <div class="password-label">Temporary password</div>
                <div class="password-code">{{ $temporaryPassword }}</div>
            </div>

            <hr class="divider" />

            <p class="note">
                You will be asked to change this password after signing in. If you did not expect this change,
                contact your administrator immediately.
            </p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</div>
</body>
</html>
