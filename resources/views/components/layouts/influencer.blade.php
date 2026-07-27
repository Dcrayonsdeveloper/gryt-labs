@php
    $me    = auth('influencer')->user();
    $store = \App\Models\Setting::get('store_name') ?: config('app.name', 'Dashboard');
    $logo  = \App\Models\Setting::get('store_logo');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard' }} — {{ $store }} Influencers</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="font-sans antialiased bg-neutral-50 min-h-screen">

    <header class="bg-white border-b border-neutral-200 sticky top-0 z-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">

            {{-- Brand --}}
            <a href="{{ route('influencer.dashboard') }}" class="flex items-center gap-2.5 min-w-0">
                @if($logo)
                    <img src="{{ asset($logo) }}" alt="{{ $store }}" class="h-8 w-auto shrink-0" style="max-height:32px">
                @else
                    <span class="text-lg font-extrabold tracking-tight text-neutral-900 truncate">{{ $store }}</span>
                @endif
                <span class="hidden sm:inline-flex items-center px-2 py-0.5 rounded-full bg-neutral-100 text-neutral-500 text-xs font-medium uppercase tracking-wide shrink-0">Influencer</span>
            </a>

            {{-- User --}}
            <div class="flex items-center gap-3 sm:gap-4">
                @if($me?->coupon_code)
                    <span class="hidden md:inline-flex items-center gap-1.5 text-xs">
                        <span class="text-neutral-400">Coupon</span>
                        <span class="font-mono font-semibold text-primary-600 bg-primary-50 px-2 py-0.5 rounded ring-1 ring-primary-100">{{ $me->coupon_code }}</span>
                    </span>
                @endif

                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-neutral-900 text-white text-xs font-semibold flex items-center justify-center shrink-0">
                        {{ strtoupper(mb_substr($me?->full_name ?: 'I', 0, 1)) }}
                    </div>
                    <div class="hidden sm:block leading-tight">
                        <p class="text-sm font-semibold text-neutral-900">{{ $me?->full_name }}</p>
                        <p class="text-xs text-neutral-400">{{ '@' . ltrim($me?->username ?? '', '@') }}</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('influencer.logout') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm text-neutral-600 hover:text-error-600 hover:bg-error-50 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        <span class="hidden sm:inline">Logout</span>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 py-6">
        {{ $slot }}
    </main>

    @stack('scripts')
</body>
</html>
