<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Influencer Login — {{ \App\Models\Setting::get('store_name', config('app.name')) }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-neutral-50 min-h-screen flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-sm">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-neutral-900">{{ \App\Models\Setting::get('store_name', config('app.name')) }}</h1>
            <p class="text-sm text-neutral-500 mt-1">Influencer Dashboard</p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm ring-1 ring-neutral-200 p-6">
            <h2 class="text-lg font-semibold text-neutral-900 mb-1">Sign in</h2>
            <p class="text-sm text-neutral-500 mb-5">Track your coupon performance</p>

            @if ($errors->any())
                <div class="mb-4 rounded-lg bg-error-50 text-error-700 text-sm px-3 py-2">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('influencer.login') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="username" class="block text-sm font-medium text-neutral-700 mb-1">Username</label>
                    <input type="text" name="username" id="username" value="{{ old('username') }}" required autofocus
                           autocomplete="username" class="form-input w-full">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-neutral-700 mb-1">Password</label>
                    <input type="password" name="password" id="password" required
                           autocomplete="current-password" class="form-input w-full">
                </div>

                <label class="flex items-center">
                    <input type="checkbox" name="remember"
                           class="rounded border-neutral-300 text-primary-600 focus:ring-primary-500">
                    <span class="ml-2 text-sm text-neutral-600">Remember me</span>
                </label>

                <button type="submit" class="btn btn-primary w-full">Sign in</button>
            </form>
        </div>

        <p class="text-center text-xs text-neutral-400 mt-6">Powered by {{ config('app.name') }}</p>
    </div>
</body>
</html>
