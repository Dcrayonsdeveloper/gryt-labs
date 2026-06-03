<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="theme-color" content="#333333">
    <title>Offline - {{ \App\Models\Setting::get('store_name', config('app.name')) }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
            background: #f8f6f3; color: #222; padding: 24px;
        }
        .offline-card {
            text-align: center; max-width: 380px; width: 100%;
            background: #fff; border-radius: 16px; padding: 40px 24px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.06);
        }
        .offline-icon {
            width: 72px; height: 72px; margin: 0 auto 20px;
            background: rgba(32,82,88,0.08); border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
        }
        .offline-icon svg { width: 36px; height: 36px; color: var(--color-primary-600); }
        h1 { font-size: 22px; font-weight: 600; color: var(--color-primary-600); margin-bottom: 8px; }
        p { font-size: 14px; color: #666; line-height: 1.6; margin-bottom: 24px; }
        .retry-btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 12px 28px; background: var(--color-primary-600); color: #fff;
            border: none; border-radius: 30px; font-size: 14px; font-weight: 600;
            cursor: pointer; transition: background 0.2s;
        }
        .retry-btn:hover { background: var(--color-primary-700); }
        .retry-btn:active { transform: scale(0.97); }
    </style>
</head>
<body>
    <div class="offline-card">
        <div class="offline-icon">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M18.364 5.636a9 9 0 010 12.728M5.636 18.364a9 9 0 010-12.728M8.464 15.536a5 5 0 010-7.072M15.536 8.464a5 5 0 010 7.072M12 12h.01"/>
            </svg>
        </div>
        <h1>You're Offline</h1>
        <p>It looks like you've lost your internet connection. Check your connection and try again.</p>
        <button class="retry-btn" onclick="window.location.reload()">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Try Again
        </button>
    </div>
</body>
</html>
