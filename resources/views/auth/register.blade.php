<x-layouts.guest>
    <x-slot name="title">Create Account - {{ config('app.name') }}</x-slot>

    <h1 class="text-2xl font-bold text-neutral-900 text-center mb-2">Create your account</h1>
    <p class="text-neutral-600 text-center mb-8">Join thousands of happy shoppers</p>

    <!-- Social Login -->
    @if(config('services.google.client_id') || config('services.facebook.client_id'))
    <div class="flex gap-2 mb-4">
        @if(config('services.google.client_id'))
        <a href="{{ route('social.redirect', 'google') }}" class="flex-1 flex items-center justify-center gap-2 py-2 border border-neutral-300 rounded hover:bg-neutral-50 transition-colors text-[13px]">
            <svg class="w-4 h-4" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
            Sign up with Google
        </a>
        @endif
        @if(config('services.facebook.client_id'))
        <a href="{{ route('social.redirect', 'facebook') }}" class="flex-1 flex items-center justify-center gap-2 py-2 border border-neutral-300 rounded hover:bg-neutral-50 transition-colors text-[13px]">
            <svg class="w-4 h-4" fill="#1877F2" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
            Facebook
        </a>
        @endif
    </div>
    <div class="relative mb-4">
        <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-neutral-300"></div></div>
        <div class="relative flex justify-center text-[12px]"><span class="bg-white px-3 text-neutral-500">Or register with email</span></div>
    </div>
    @endif

    <form method="POST" action="{{ route('register') }}" class="space-y-6">
        @csrf
        <input type="hidden" name="_register" value="1">

        <!-- Full Name -->
        <div>
            <label for="full_name" class="block text-sm font-medium text-neutral-700 mb-1">Full Name</label>
            <input type="text" name="full_name" id="full_name" value="{{ old('full_name') }}" required autofocus
                   class="form-input w-full @error('full_name') border-error-300 @enderror"
                   placeholder="John Doe">
            @error('full_name')
                <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Email -->
        <div>
            <label for="email" class="block text-sm font-medium text-neutral-700 mb-1">Email address</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}" required
                   class="form-input w-full @error('email') border-error-300 @enderror"
                   placeholder="you@example.com">
            @error('email')
                <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Phone (Optional) -->
        <div>
            <label for="phone" class="block text-sm font-medium text-neutral-700 mb-1">
                Phone number <span class="text-neutral-600">(optional)</span>
            </label>
            <input type="tel" name="phone" id="phone" value="{{ old('phone') }}"
                   class="form-input w-full @error('phone') border-error-300 @enderror"
                   placeholder="+91 98765 43210">
            @error('phone')
                <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="block text-sm font-medium text-neutral-700 mb-1">Password</label>
            <input type="password" name="password" id="password" required
                   class="form-input w-full @error('password') border-error-300 @enderror"
                   placeholder="Create a strong password">
            @error('password')
                <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
            @enderror
            <p class="mt-1 text-xs text-neutral-600">Must be at least 8 characters</p>
        </div>

        <!-- Confirm Password -->
        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-neutral-700 mb-1">Confirm password</label>
            <input type="password" name="password_confirmation" id="password_confirmation" required
                   class="form-input w-full"
                   placeholder="Confirm your password">
        </div>

        <!-- Terms -->
        <div class="flex items-start">
            <input type="checkbox" name="terms" id="terms" required
                   class="w-4 h-4 mt-0.5 rounded border-neutral-300 text-primary-600 focus:ring-primary-500">
            <label for="terms" class="ml-2 text-sm text-neutral-600">
                I agree to the
                <a href="{{ route('terms') }}" class="text-primary-600 hover:text-primary-700">Terms of Service</a>
                and
                <a href="{{ route('privacy') }}" class="text-primary-600 hover:text-primary-700">Privacy Policy</a>
            </label>
        </div>

        <!-- Submit -->
        <button type="submit" class="btn-primary w-full">
            Create account
        </button>
    </form>

    <!-- Login Link -->
    <p class="mt-8 text-center text-sm text-neutral-600">
        Already have an account?
        <a href="{{ route('login') }}" class="font-medium text-primary-600 hover:text-primary-700">
            Sign in
        </a>
    </p>
</x-layouts.guest>
