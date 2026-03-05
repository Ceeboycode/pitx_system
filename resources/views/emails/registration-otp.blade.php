<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Email Verification</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif; color: #18181b; }
        .wrap { max-width: 540px; margin: 48px auto; padding: 0 16px; }
        .card { background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,.08), 0 4px 16px rgba(0,0,0,.04); }
        .header { background: #18181b; padding: 32px 40px; }
        .header-inner { display: flex; align-items: center; gap: 12px; }
        .logo-dot { width: 8px; height: 8px; border-radius: 50%; background: #a1a1aa; }
        .header h1 { color: #fafafa; font-size: 16px; font-weight: 600; letter-spacing: -.2px; }
        .header p { color: #a1a1aa; font-size: 13px; margin-top: 4px; }
        .body { padding: 40px; }
        .greeting { font-size: 15px; color: #3f3f46; margin-bottom: 8px; }
        .purpose { font-size: 14px; color: #71717a; margin-bottom: 32px; line-height: 1.5; }
        .otp-wrapper { background: #f9f9f9; border: 1px solid #e4e4e7; border-radius: 10px; padding: 28px 20px; text-align: center; margin-bottom: 28px; }
        .otp-label { font-size: 11px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: #71717a; margin-bottom: 12px; }
        .otp-code { font-size: 44px; font-weight: 700; letter-spacing: 14px; color: #09090b; font-variant-numeric: tabular-nums; line-height: 1; padding-left: 14px; /* optical center of letter-spacing */ }
        .otp-expiry { margin-top: 14px; font-size: 13px; color: #71717a; }
        .otp-expiry strong { color: #3f3f46; }
        .divider { border: none; border-top: 1px solid #f4f4f5; margin: 8px 0 24px; }
        .note { font-size: 13px; color: #a1a1aa; line-height: 1.6; }
        .footer { background: #fafafa; border-top: 1px solid #f4f4f5; padding: 20px 40px; text-align: center; }
        .footer p { font-size: 12px; color: #a1a1aa; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">

        <!-- Header -->
        <div class="header">
            <div class="header-inner">
                <div class="logo-dot"></div>
                <div>
                    <h1>{{ config('app.name') }}</h1>
                    <p>
                        @if ($purpose === 'account')
                            Account Email Verification
                        @else
                            Company Email Verification
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <!-- Body -->
        <div class="body">
            <p class="greeting">Hi {{ $recipientName }},</p>

            @if ($purpose === 'account')
                <p class="purpose">
                    Use the code below to verify your <strong>personal account email</strong> and continue your company registration.
                </p>
            @else
                <p class="purpose">
                    Use the code below to verify your <strong>company email address</strong> and continue your registration.
                </p>
            @endif

            <div class="otp-wrapper">
                <div class="otp-label">Your verification code</div>
                <div class="otp-code">{{ $otp }}</div>
                <div class="otp-expiry">Expires in <strong>10 minutes</strong></div>
            </div>

            <hr class="divider" />

            <p class="note">
                If you did not request this code, you can safely ignore this email.
                Never share this code with anyone — our team will never ask for it.
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>

    </div>
</div>
</body>
</html>
