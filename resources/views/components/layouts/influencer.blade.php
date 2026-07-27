<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard' }} — {{ \App\Models\Setting::get('store_name', config('app.name')) }} Influencers</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="font-sans antialiased bg-neutral-50 min-h-screen">
    @php $me = auth('influencer')->user(); @endphp

    <header class="bg-white border-b border-neutral-200 sticky top-0 z-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 h-14 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="font-bold text-neutral-900">{{ \App\Models\Setting::get('store_name', config('app.name')) }}</span>
                <span class="text-xs text-neutral-400 hidden sm:inline">Influencer Dashboard</span>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-sm text-neutral-600 hidden sm:inline">{{ $me?->full_name }}</span>
                <form method="POST" action="{{ route('influencer.logout') }}">
                    @csrf
                    <button type="submit" class="text-sm text-neutral-500 hover:text-error-600 font-medium">Logout</button>
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
