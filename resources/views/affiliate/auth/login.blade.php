<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Affiliate Sign In - {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-white">
    @include('partials.header')

    <!-- Login Form -->
    <div class="min-h-[70vh] flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-sm">
            <h1 class="text-2xl font-bold text-neutral-900 mb-1">Sign in</h1>
            <p class="text-sm text-neutral-500 mb-6">Access your affiliate dashboard</p>

            <form method="POST" action="{{ route('affiliate.login') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="email" class="block text-sm font-medium text-neutral-700 mb-1">Email</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                           class="form-input w-full @error('email') border-error-300 @enderror">
                    @error('email')
                        <p class="mt-1 text-xs text-error-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-neutral-700 mb-1">Password</label>
                    <input type="password" name="password" id="password" required
                           class="form-input w-full @error('password') border-error-300 @enderror">
                    @error('password')
                        <p class="mt-1 text-xs text-error-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center">
                        <input type="checkbox" name="remember" class="rounded border-neutral-300 text-primary-600 focus:ring-primary-500">
                        <span class="ml-2 text-sm text-neutral-600">Remember me</span>
                    </label>
                </div>

                <button type="submit" class="w-full py-2.5 px-4 bg-[#FFD814] hover:bg-[#F7CA00] text-neutral-900 font-medium text-sm rounded-lg border border-[#FCD200] transition-colors">
                    Sign in
                </button>
            </form>

            <div class="mt-6 pt-6 border-t border-neutral-200">
                <p class="text-center text-sm text-neutral-600">
                    New to {{ \App\Models\Setting::get('store_name', config('app.name')) }} Affiliates?
                    <a href="{{ route('affiliate.register') }}" class="font-medium text-primary-600 hover:underline">Create an account</a>
                </p>
            </div>
        </div>
    </div>

    @include('partials.footer')
</body>
</html>
