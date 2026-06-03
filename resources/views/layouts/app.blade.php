<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'Laravel') }}</title>

    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="{{ \App\Models\Setting::get('primary_color', '') ?: '#334155' }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{{ \App\Models\Setting::get('store_name', config('app.name')) }}">
    <meta name="application-name" content="{{ \App\Models\Setting::get('store_name', config('app.name')) }}">
    <meta name="msapplication-TileColor" content="{{ \App\Models\Setting::get('primary_color', '') ?: '#334155' }}">
    <meta name="format-detection" content="telephone=no">
    <link rel="manifest" href="/manifest.json">
    @php $favicon = \App\Models\Setting::get('store_favicon', '') ?: \App\Models\Setting::get('store_logo', ''); @endphp
    @if($favicon)
    <link rel="icon" type="image/png" href="/{{ ltrim($favicon, '/') }}">
    <link rel="shortcut icon" href="/{{ ltrim($favicon, '/') }}">
    @endif
    <link rel="apple-touch-icon" sizes="180x180" href="/images/icons/apple-touch-icon.png">

    <!-- SEO Meta Tags -->
    @stack('meta')

    <!-- Default OG fallbacks -->
    @unless(View::hasSection('meta'))
        @php $fallbackDesc = $theme->get('meta_description', '') ?: ($theme->get('site_tagline', '') ?: config('app.name')); @endphp
        <meta name="description" content="{{ $fallbackDesc }}">
        <meta property="og:site_name" content="{{ config('app.name') }}">
        <meta property="og:title" content="@yield('title', config('app.name'))">
        <meta property="og:description" content="{{ $fallbackDesc }}">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:image" content="{{ asset($theme->get('store_logo', 'images/og-default.png')) }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="@yield('title', config('app.name'))">
        <meta name="twitter:description" content="{{ $fallbackDesc }}">
        <meta name="twitter:image" content="{{ asset($theme->get('store_logo', 'images/og-default.png')) }}">
    @endunless

    {{-- Canonical is set by each page via @push('meta'). No layout fallback to avoid duplicates. --}}
    <link rel="alternate" hreflang="en-in" href="{{ url()->current() }}">
    <link rel="alternate" hreflang="x-default" href="{{ url()->current() }}">

    <!-- Performance: DNS prefetch + preconnect -->
    <link rel="dns-prefetch" href="https://fonts.bunny.net">
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>

    <!-- Fonts (display=swap for fast first paint) -->
    <link href="https://fonts.bunny.net/css?family=poppins:300,400,500,600,700|inter:400,500,600&display=swap" rel="stylesheet" />

    <!-- Critical CSS first to prevent flash -->
    @vite(['resources/css/critical.css'])

    <!-- Main Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>
<body class="font-sans antialiased bg-white text-[#222222]" style="font-family: 'Poppins', sans-serif;" x-data>
    <!-- Toast Notifications -->
    <div class="fixed top-4 right-4 z-50 flex flex-col gap-2 max-w-[calc(100vw-2rem)]">
        <template x-for="toast in $store.toast.items" :key="toast.id">
            <div x-show="true"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-x-8"
                 x-transition:enter-end="opacity-100 translate-x-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-x-0"
                 x-transition:leave-end="opacity-0 translate-x-8"
                 class="card px-4 py-3 flex items-center gap-3 shadow-lg border"
                 :class="{
                     'bg-success-50 border-success-200 text-success-800': toast.type === 'success',
                     'bg-error-50 border-error-200 text-error-800': toast.type === 'error',
                     'bg-warning-50 border-warning-200 text-warning-800': toast.type === 'warning',
                     'bg-info-50 border-info-200 text-info-800': toast.type === 'info'
                 }">
                <span x-text="toast.message" class="text-sm"></span>
                <button @click="$store.toast.remove(toast.id)" class="text-current opacity-60 hover:opacity-100 shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </template>
    </div>

    <!-- Skip to main content -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-primary-500 text-white px-4 py-2 rounded-md z-50">
        Skip to main content
    </a>

    <!-- Header -->
    @include('partials.header')

    <!-- Mobile Navigation -->
    @include('partials.mobile-nav')

    <!-- Main Content -->
    <main id="main-content" class="min-h-screen pb-0">
        {{ $slot }}
    </main>

    <!-- Footer -->
    @include('partials.footer')

    <!-- Mobile Bottom Navigation -->
    @include('partials.mobile-bottom-nav')

    <!-- PWA Install Prompt (Mobile) -->
    <div x-data="pwaInstall()" x-show="showPrompt" x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-full"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-full"
         class="fixed bottom-20 lg:bottom-4 left-4 right-4 z-50 bg-white rounded-2xl shadow-2xl border border-neutral-100 p-4 mx-auto max-w-sm">
        <div class="flex items-start gap-3">
            <div class="w-11 h-11 rounded-xl bg-primary-600 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-neutral-900">Add {{ \App\Models\Setting::get('store_name', config('app.name')) }} to Home Screen</p>
                <p class="text-xs text-neutral-500 mt-0.5">Fast access, works offline</p>
            </div>
            <button @click="dismiss()" class="shrink-0 p-1 text-neutral-400 hover:text-neutral-600" aria-label="Dismiss">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="flex gap-2 mt-3">
            <button @click="dismiss()" class="flex-1 py-2 text-xs font-medium text-neutral-600 bg-neutral-100 rounded-xl hover:bg-neutral-200 transition-colors">Later</button>
            <button @click="install()" class="flex-1 py-2 text-xs font-medium text-white bg-primary-600 rounded-xl hover:bg-primary-700 transition-colors">Install App</button>
        </div>
    </div>

    @stack('scripts')

    <!-- Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').catch(() => {});
            });
        }

        // PWA Install Prompt
        function pwaInstall() {
            return {
                showPrompt: false,
                deferredPrompt: null,
                init() {
                    // Don't show if already installed or dismissed recently
                    if (window.matchMedia('(display-mode: standalone)').matches) return;
                    if (sessionStorage.getItem('pwa_dismissed')) return;

                    window.addEventListener('beforeinstallprompt', (e) => {
                        e.preventDefault();
                        this.deferredPrompt = e;
                        // Show after 30s of browsing
                        setTimeout(() => { this.showPrompt = true; }, 30000);
                    });
                },
                async install() {
                    if (!this.deferredPrompt) return;
                    this.deferredPrompt.prompt();
                    const { outcome } = await this.deferredPrompt.userChoice;
                    this.deferredPrompt = null;
                    this.showPrompt = false;
                    if (outcome === 'accepted') {
                        sessionStorage.setItem('pwa_dismissed', '1');
                    }
                },
                dismiss() {
                    this.showPrompt = false;
                    sessionStorage.setItem('pwa_dismissed', '1');
                }
            };
        }
    </script>
</body>
</html>
