<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>Two-Factor Verification - {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: #f1f1f1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .logo {
            margin-bottom: 24px;
            text-align: center;
        }
        .logo img {
            height: 36px;
        }
        .card {
            background: #ffffff;
            border: 1px solid #e1e1e1;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            padding: 32px;
            width: 100%;
            max-width: 400px;
        }
        .card-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: #eef2ff;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
        }
        .card h1 {
            font-size: 20px;
            font-weight: 600;
            color: #1a1a1a;
            text-align: center;
            margin-bottom: 6px;
        }
        .card p.subtitle {
            font-size: 13px;
            color: #616161;
            text-align: center;
            margin-bottom: 24px;
            line-height: 1.5;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 6px;
        }
        .code-input {
            width: 100%;
            padding: 12px 16px;
            font-size: 24px;
            font-weight: 600;
            letter-spacing: 8px;
            text-align: center;
            border: 1px solid #c9c9c9;
            border-radius: 8px;
            outline: none;
            font-family: 'Inter', system-ui, sans-serif;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .code-input:hover {
            border-color: #999;
        }
        .code-input:focus {
            border-color: #005bd3;
            box-shadow: 0 0 0 2px rgba(0, 91, 211, 0.15);
        }
        .recovery-input {
            width: 100%;
            padding: 10px 14px;
            font-size: 15px;
            font-weight: 500;
            font-family: monospace;
            text-align: center;
            letter-spacing: 1px;
            border: 1px solid #c9c9c9;
            border-radius: 8px;
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .recovery-input:hover {
            border-color: #999;
        }
        .recovery-input:focus {
            border-color: #005bd3;
            box-shadow: 0 0 0 2px rgba(0, 91, 211, 0.15);
        }
        .btn-verify {
            width: 100%;
            padding: 12px;
            background: #1a1a1a;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
            transition: background 0.15s;
        }
        .btn-verify:hover {
            background: #333333;
        }
        .btn-verify:active {
            background: #000000;
        }
        .error-alert {
            background: #fde8e8;
            border: 1px solid #fca5a5;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 13px;
            color: #c53030;
            margin-bottom: 16px;
            text-align: center;
        }
        .toggle-link {
            display: block;
            text-align: center;
            margin-top: 16px;
            font-size: 13px;
            color: #005bd3;
            text-decoration: none;
            cursor: pointer;
        }
        .toggle-link:hover {
            text-decoration: underline;
            color: #004299;
        }
        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 20px 0;
            font-size: 12px;
            color: #8c9196;
        }
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e1e1e1;
        }
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        .back-link a {
            font-size: 13px;
            color: #616161;
            text-decoration: none;
        }
        .back-link a:hover {
            color: #1a1a1a;
            text-decoration: underline;
        }
        .footer {
            margin-top: 24px;
            font-size: 11px;
            color: #8c9196;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="logo">
        <a href="{{ url('/') }}">
            <img src="{{ asset(\App\Models\Setting::get('store_logo', 'images/logo.png')) }}" alt="{{ config('app.name') }}">
        </a>
    </div>

    <div class="card">
        <div class="card-icon">
            <svg width="24" height="24" fill="none" stroke="#005bd3" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
        </div>

        @if(session('error'))
            <div class="error-alert">{{ session('error') }}</div>
        @endif

        @if(empty($showRecovery))
            {{-- TOTP Code Entry --}}
            <h1>Two-factor verification</h1>
            <p class="subtitle">
                Enter the 6-digit code from your authenticator app to continue.
            </p>

            <form method="POST" action="{{ route('admin.2fa.verify.code') }}">
                @csrf
                <div class="form-group">
                    <label for="code">Authentication code</label>
                    <input
                        type="text"
                        name="code"
                        id="code"
                        class="code-input"
                        placeholder="000000"
                        maxlength="6"
                        pattern="[0-9]{6}"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        autofocus
                        required
                    >
                    @error('code')
                        <p style="font-size:12px;color:#c53030;margin-top:6px;text-align:center">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="btn-verify">Verify</button>
            </form>

            <a href="{{ route('admin.2fa.verify', ['recovery' => 1]) }}" class="toggle-link">
                Use a recovery code instead
            </a>
        @else
            {{-- Recovery Code Entry --}}
            <h1>Recovery code</h1>
            <p class="subtitle">
                Enter one of your recovery codes to access your account. Each code can only be used once.
            </p>

            <form method="POST" action="{{ route('admin.2fa.recovery') }}">
                @csrf
                <div class="form-group">
                    <label for="recovery_code">Recovery code</label>
                    <input
                        type="text"
                        name="recovery_code"
                        id="recovery_code"
                        class="recovery-input"
                        placeholder="xxxxx-xxxxx"
                        autocomplete="off"
                        autofocus
                        required
                    >
                    @error('recovery_code')
                        <p style="font-size:12px;color:#c53030;margin-top:6px;text-align:center">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="btn-verify">Verify recovery code</button>
            </form>

            <a href="{{ route('admin.2fa.verify') }}" class="toggle-link">
                Use authenticator code instead
            </a>
        @endif
    </div>

    <div class="back-link">
        <form method="POST" action="{{ route('admin.logout') }}" style="display:inline">
            @csrf
            <button type="submit" style="background:none;border:none;font-size:13px;color:#616161;cursor:pointer;font-family:'Inter',sans-serif;text-decoration:none">
                Sign out and use a different account
            </button>
        </form>
    </div>

    <div class="footer">
        &copy; {{ date('Y') }} {{ config('app.name') }}
    </div>

    <script>
        // Auto-submit when 6 digits are entered
        const codeInput = document.getElementById('code');
        if (codeInput) {
            codeInput.addEventListener('input', function() {
                // Only allow digits
                this.value = this.value.replace(/[^0-9]/g, '');
                if (this.value.length === 6) {
                    this.closest('form').submit();
                }
            });
        }
    </script>
</body>
</html>
