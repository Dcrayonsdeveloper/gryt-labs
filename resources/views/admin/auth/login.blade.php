@php
    $store = \App\Models\Setting::get('store_name') ?: config('app.name', 'Admin');
    $logo  = \App\Models\Setting::get('store_logo');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>Admin Login — {{ $store }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="font-sans antialiased bg-neutral-50">
    <div class="min-h-screen grid lg:grid-cols-2">

        {{-- ── Brand panel (desktop) ──────────────────────────────── --}}
        <div class="hidden lg:flex flex-col justify-between p-12 text-white relative overflow-hidden"
             style="background:linear-gradient(135deg,#0b1220 0%,#111827 45%,#1f2937 100%);">
            <div style="position:absolute;top:-90px;right:-70px;width:340px;height:340px;border-radius:9999px;background:radial-gradient(circle,rgba(99,102,241,.35),transparent 70%);"></div>
            <div style="position:absolute;bottom:-120px;left:-70px;width:400px;height:400px;border-radius:9999px;background:radial-gradient(circle,rgba(16,185,129,.22),transparent 70%);"></div>

            <div class="relative z-10">
                <span class="text-xl font-extrabold tracking-tight text-white">{{ $store }}</span>
            </div>

            <div class="relative z-10 max-w-md">
                <p class="text-xs uppercase tracking-widest text-white/40 mb-3">Admin Panel</p>
                <h2 class="text-4xl font-extrabold leading-tight">Your store,<br>one <span class="text-emerald-400">dashboard.</span></h2>
                <p class="mt-5 text-white/70 leading-relaxed">Orders, products, customers, discounts and analytics — manage everything from a single place.</p>
                <ul class="mt-8 space-y-3 text-sm text-white/80">
                    <li class="flex items-center gap-2.5"><span class="inline-flex w-5 h-5 rounded-full bg-emerald-500/20 text-emerald-400 items-center justify-center">✓</span> Orders, fulfilment &amp; shipping</li>
                    <li class="flex items-center gap-2.5"><span class="inline-flex w-5 h-5 rounded-full bg-emerald-500/20 text-emerald-400 items-center justify-center">✓</span> Products, inventory &amp; discounts</li>
                    <li class="flex items-center gap-2.5"><span class="inline-flex w-5 h-5 rounded-full bg-emerald-500/20 text-emerald-400 items-center justify-center">✓</span> Customers, marketing &amp; analytics</li>
                </ul>
            </div>

            <p class="relative z-10 text-xs text-white/40">© {{ date('Y') }} {{ $store }}. All rights reserved.</p>
        </div>

        {{-- ── Form panel ─────────────────────────────────────────── --}}
        <div class="flex items-center justify-center p-6 sm:p-10">
            <div class="w-full max-w-sm">
                <div class="text-center mb-8">
                    @if($logo)
                        <a href="{{ url('/') }}"><img src="{{ asset($logo) }}" alt="{{ $store }}" class="h-10 w-auto mx-auto" style="max-height:44px"></a>
                    @else
                        <span class="text-2xl font-extrabold tracking-tight text-neutral-900">{{ $store }}</span>
                    @endif
                    <p class="text-xs text-neutral-400 mt-1.5 uppercase tracking-widest">Admin Panel</p>
                </div>

                <h1 class="text-2xl font-bold text-neutral-900">Welcome back</h1>
                <p class="text-sm text-neutral-500 mb-6">Sign in to your admin dashboard.</p>

                @if ($errors->any() || session('error'))
                    <div class="mb-4 rounded-lg bg-error-50 text-error-700 text-sm px-3 py-2.5 flex items-start gap-2">
                        <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>{{ session('error') ?: $errors->first() }}</span>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.login') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="email" class="block text-sm font-medium text-neutral-700 mb-1.5">Email</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-neutral-400 pointer-events-none">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </span>
                            <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                                   autocomplete="username" placeholder="you@example.com" class="form-input w-full" style="padding-left:2.5rem">
                        </div>
                    </div>

                    <div x-data="{ show: false }">
                        <label for="password" class="block text-sm font-medium text-neutral-700 mb-1.5">Password</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-neutral-400 pointer-events-none">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            </span>
                            <input :type="show ? 'text' : 'password'" name="password" id="password" required
                                   autocomplete="current-password" placeholder="••••••••" class="form-input w-full" style="padding-left:2.5rem;padding-right:2.5rem">
                            <button type="button" @click="show = !show" tabindex="-1"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-neutral-400 hover:text-neutral-600">
                                <svg x-show="!show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <svg x-show="show" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-1">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" name="remember" class="rounded border-neutral-300 text-primary-600 focus:ring-primary-500">
                            <span class="ml-2 text-sm text-neutral-600">Keep me signed in</span>
                        </label>
                        <a href="{{ url('/') }}" class="text-sm text-neutral-400 hover:text-neutral-600">&larr; Store</a>
                    </div>

                    <button type="submit" class="btn btn-primary w-full py-2.5 justify-center gap-2 group">
                        Sign in
                        <svg class="w-4 h-4 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                    </button>
                </form>

                <p class="text-center text-xs text-neutral-400 mt-8">Authorized staff only.</p>
            </div>
        </div>
    </div>
</body>
</html>
