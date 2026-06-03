<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">

    @php
        $storeName = $theme->get('store_name', config('app.name', 'Store'));
        $storeLogo = $theme->get('store_logo', 'images/logo.png');
    @endphp

    <title>{{ $title ?? 'Checkout' }} - {{ $storeName }}</title>

    {{-- Colors baked into frontend CSS @theme --}}

    <meta name="theme-color" content="{{ $theme->get('primary_color') ?: '#334155' }}">
    @php $favicon = \App\Models\Setting::get('store_favicon', '') ?: \App\Models\Setting::get('store_logo', ''); @endphp
    @if($favicon)
    <link rel="icon" type="image/png" href="/{{ ltrim($favicon, '/') }}">
    @endif

    <link rel="dns-prefetch" href="https://fonts.bunny.net">
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    @php $__fe = config('app.frontend', 'default'); @endphp
    @vite(["frontends/{$__fe}/css/app.css", "frontends/{$__fe}/js/app.js"])

    @stack('meta')
    {{ $styles ?? '' }}

    <style>
        body { font-family: 'Inter', system-ui, -apple-system, sans-serif; }

        /* Shopify-style input focus: 2px brand-colored outline */
        .checkout-input {
            width: 100%;
            font-size: 14px;
            padding: 10px 12px;
            border: 1px solid #d9d9d9;
            border-radius: 8px;
            background: #fff;
            color: #1a1a1a;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .checkout-input::placeholder { color: #595959; }
        .checkout-input:hover { border-color: #595959; }
        .checkout-input:focus {
            outline: none;
            border-color: var(--color-primary-600);
            box-shadow: none;
        }
        input:focus, select:focus, textarea:focus {
            outline: none !important;
            border: 1px solid var(--color-primary-600) !important;
            box-shadow: none !important;
        }

        /* Trust indicators */
        .checkout-trust-bar {
            background: #f6f6f6;
            border-top: 1px solid #e5e5e5;
            padding: 8px 0;
            font-size: 11px;
            color: #737373;
            text-align: center;
        }
    </style>

    {{-- Checkout Security Headers (Shopify-level) --}}
    @php
        // These headers make checkout as trusted as Shopify's
        header('X-Frame-Options: DENY'); // Prevent iframe embedding (clickjacking)
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    @endphp
</head>
<body class="antialiased bg-white min-h-screen flex flex-col">

    {{-- Shopify-style Trust Bar (top) --}}
    <div class="checkout-trust-bar">
        <div class="max-w-[1080px] mx-auto px-4 flex items-center justify-center gap-4">
            <span class="flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                256-bit SSL Encrypted
            </span>
            <span>|</span>
            <span class="flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                PCI Compliant
            </span>
            <span>|</span>
            <span class="flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Easy Returns
            </span>
        </div>
    </div>

    {{-- Checkout Header: Logo + Breadcrumb + Lock icon (Shopify-style) --}}
    <header class="border-b border-neutral-200 bg-white">
        <div class="max-w-[1080px] mx-auto px-4 sm:px-6">
            <div class="flex items-center justify-between h-14 sm:h-16">
                {{-- Logo --}}
                <a href="{{ url('/') }}" class="shrink-0">
                    <img src="{{ asset($storeLogo) }}" alt="{{ $storeName }}" class="h-7 sm:h-8 w-auto object-contain">
                </a>

                {{-- Breadcrumb Steps (desktop) --}}
                <nav class="hidden sm:flex items-center gap-2 text-xs text-neutral-500" aria-label="Checkout steps">
                    <a href="{{ route('cart.index') }}" class="text-link hover:text-link-hover font-medium">Cart</a>
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    {{ $breadcrumb ?? '' }}
                </nav>

                {{-- Security badge --}}
                <div class="flex items-center gap-1.5 text-neutral-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    <span class="text-[11px] font-medium hidden sm:inline">Secure checkout</span>
                </div>
            </div>
        </div>
    </header>

    {{-- Main Content — Shopify split: left white, right grey --}}
    <main class="flex-1 relative overflow-hidden">
        <div class="hidden lg:block absolute top-0 right-0 bottom-0 w-[45%] bg-[#fafafa] border-l border-neutral-200"></div>
        <div class="relative">
            {{ $slot }}
        </div>
    </main>

    {{-- Minimal Footer --}}
    <footer class="border-t border-neutral-200 bg-white">
        <div class="max-w-[1080px] mx-auto px-4 sm:px-6 py-4">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-2 text-[11px] text-neutral-500">
                <div class="flex items-center gap-4">
                    <a href="{{ route('terms') }}" class="hover:text-neutral-600">Terms of service</a>
                    <a href="{{ route('privacy') }}" class="hover:text-neutral-600">Privacy policy</a>
                    <a href="{{ route('shipping') }}" class="hover:text-neutral-600">Shipping policy</a>
                </div>
                <div class="flex items-center gap-1.5">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    <span>All transactions are secure and encrypted</span>
                </div>
            </div>
        </div>
    </footer>

    {{ $scripts ?? '' }}
    @stack('scripts')
</body>
</html>
