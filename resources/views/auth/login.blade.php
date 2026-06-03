@php
    $faviconRaw = \App\Models\Setting::get('store_favicon', '') ?: \App\Models\Setting::get('store_logo', '');
    $faviconUrl = $faviconRaw ? '/' . ltrim($faviconRaw, '/') : '';
    $primaryColor = \App\Models\Setting::get('primary_color', '') ?: '#334155';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>Login - {{ config('app.name') }}</title>
    @if($faviconUrl)
    <link rel="icon" href="{{ $faviconUrl }}" type="image/png">
    <link rel="shortcut icon" href="{{ $faviconUrl }}">
    @endif

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-white min-h-screen" x-data>

    @include('partials.header')

    <div class="max-w-[350px] mx-auto px-4 pt-6 pb-8"
         x-data="{
            mode: '{{ $errors->has('full_name') || $errors->has('phone') || $errors->has('terms') || old('_register') || request()->get('mode') === 'register' ? 'register' : 'login' }}'
         }">

        <!-- ============================
             LOGIN FORM
             ============================ -->
        <div x-show="mode === 'login'" x-cloak:remove>
            <div class="border border-neutral-300 rounded-lg p-5 mb-4" x-data="unifiedLogin()">
                <h1 class="text-[22px] font-normal text-[#111] mb-4">Sign in</h1>

                @if(session('success'))
                    <div class="mb-3 p-3 bg-emerald-50 border border-emerald-200 rounded text-emerald-700 text-sm">{{ session('success') }}</div>
                @endif

                {{-- Step 1: Enter email or phone --}}
                <div x-show="step === 'identifier'" class="space-y-3">
                    <div>
                        <label for="login-identifier" class="block text-[13px] font-bold text-[#111] mb-1">Email or Phone Number</label>
                        <input id="login-identifier" type="text" x-model="identifier" @keyup.enter="continueLogin()" autofocus
                               :aria-invalid="error ? 'true' : 'false'" aria-describedby="login-identifier-error"
                               class="w-full px-3 py-[7px] border border-neutral-400 rounded text-sm text-[#111] focus:outline-none focus:ring-1 focus:ring-neutral-400 focus:border-neutral-400"
                               placeholder="Email or mobile number">
                    </div>
                    <button @click="continueLogin()" type="button"
                            class="w-full py-2 px-4 bg-[var(--color-primary-600)] border border-[var(--color-primary-700)] rounded text-[13px] font-medium text-white hover:opacity-90 cursor-pointer transition-opacity">
                        Continue
                    </button>
                    <p id="login-identifier-error" x-show="error" x-text="error" role="alert" class="text-xs text-red-700" x-cloak></p>
                </div>

                {{-- Step 2a: Password login (email detected) --}}
                <div x-show="step === 'password'" x-cloak class="space-y-3">
                    <div class="flex items-center justify-between">
                        <p class="text-[13px] text-[#111]"><span x-text="identifier"></span></p>
                        <button @click="step='identifier';error=''" type="button" class="text-[12px] text-[var(--color-primary-600)] hover:underline">Change</button>
                    </div>

                    <form method="POST" action="{{ route('login') }}" class="space-y-3">
                        @csrf
                        <input type="hidden" name="email" :value="identifier">
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-[13px] font-bold text-[#111]">Password</label>
                                <a href="{{ route('password.request') }}" class="text-[12px] text-[var(--color-primary-600)] hover:underline">Forgot?</a>
                            </div>
                            <input id="login-password" type="password" name="password" required autocomplete="current-password"
                                   :aria-invalid="error ? 'true' : 'false'" aria-describedby="login-password-error"
                                   class="w-full px-3 py-[7px] border border-neutral-400 rounded text-sm text-[#111] focus:outline-none focus:ring-1 focus:ring-neutral-400 focus:border-neutral-400">
                        </div>
                        <p id="login-password-error" x-show="error" x-text="error" role="alert" class="text-xs text-red-700 mb-2" x-cloak></p>
                        <button type="submit" class="w-full py-2 px-4 bg-[var(--color-primary-600)] border border-[var(--color-primary-700)] rounded text-[13px] font-medium text-white hover:opacity-90 transition-opacity">
                            Sign in
                        </button>
                    </form>

                    <div class="text-center">
                        <button @click="sendOtpForIdentifier()" type="button" class="text-[12px] text-[var(--color-primary-600)] hover:underline">
                            Sign in with OTP instead
                        </button>
                    </div>
                </div>

                {{-- Step 2b: OTP sent (phone detected or user chose OTP) --}}
                <div x-show="step === 'otp_sent'" x-cloak class="space-y-3">
                    <div class="flex items-center justify-between">
                        <p class="text-[13px] text-[#111]">OTP sent to <strong x-text="identifier"></strong></p>
                        <button @click="step='identifier';error=''" type="button" class="text-[12px] text-[var(--color-primary-600)] hover:underline">Change</button>
                    </div>

                    <div>
                        <label for="login-otp" class="block text-[13px] font-bold text-[#111] mb-1">Enter OTP</label>
                        <input id="login-otp" type="text" x-model="otp" maxlength="6" inputmode="numeric" @keyup.enter="verifyOtp()"
                               :aria-invalid="error ? 'true' : 'false'" aria-describedby="login-otp-error login-otp-hint"
                               class="w-full px-3 py-[7px] border border-neutral-400 rounded text-sm text-center tracking-[6px] font-bold focus:outline-none focus:ring-1 focus:ring-neutral-400 focus:border-neutral-400"
                               placeholder="- - - - - -">
                    </div>
                    <button @click="verifyOtp()" :disabled="verifying" type="button"
                            class="w-full py-2 px-4 bg-[var(--color-primary-600)] border border-[var(--color-primary-700)] rounded text-[13px] font-medium text-white hover:opacity-90 disabled:opacity-50 transition-opacity">
                        <span x-show="!verifying">Verify & Sign in</span>
                        <span x-show="verifying">Verifying...</span>
                    </button>
                    <p id="login-otp-error" x-show="error" x-text="error" role="alert" class="text-xs text-red-700" x-cloak></p>
                    <p id="login-otp-hint" class="text-xs text-neutral-700">OTP sent via WhatsApp + Email. Valid 10 min.</p>
                </div>

                {{-- Step: Sending OTP --}}
                <div x-show="step === 'sending_otp'" x-cloak class="py-4 text-center">
                    <p class="text-[13px] text-neutral-600">Sending OTP to <strong x-text="identifier"></strong>...</p>
                </div>

                <div class="flex items-center mt-3">
                    <input type="checkbox" id="remember" class="w-[13px] h-[13px] border-neutral-400 rounded text-primary-600">
                    <label for="remember" class="ml-2 text-[13px] text-[#111]">Keep me signed in.</label>
                </div>
            </div>

            <!-- Social Login -->
            <div class="relative my-4">
                <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-neutral-300"></div></div>
                <div class="relative flex justify-center text-[12px]"><span class="bg-white px-3 text-neutral-500">Or continue with</span></div>
            </div>
            <div class="flex gap-2">
                @if(config('services.google.client_id'))
                <a href="{{ route('social.redirect', 'google') }}" class="flex-1 flex items-center justify-center gap-2 py-2 border border-neutral-300 rounded hover:bg-neutral-50 transition-colors text-[13px]">
                    <svg class="w-4 h-4" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
                    Google
                </a>
                @endif
                <a href="{{ route('social.redirect', 'facebook') }}" class="flex-1 flex items-center justify-center gap-2 py-2 border border-neutral-300 rounded hover:bg-neutral-50 transition-colors text-[13px]">
                    <svg class="w-4 h-4" fill="#1877F2" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    Facebook
                </a>
            </div>

            <!-- Divider -->
            <div class="relative my-4">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-neutral-300"></div>
                </div>
                <div class="relative flex justify-center text-[12px]">
                    <span class="bg-white px-3 text-neutral-500">New to {{ config('app.name') }}?</span>
                </div>
            </div>

            <!-- Create account button -->
            <button @click="mode = 'register'"
                    class="w-full py-2 px-4 bg-white border border-[var(--color-primary-600)] text-[var(--color-primary-600)] rounded text-[13px] font-medium hover:bg-[var(--color-primary-600)] hover:text-white transition-colors cursor-pointer">
                Create your {{ config('app.name') }} account
            </button>
        </div>

        <!-- ============================
             REGISTER FORM
             ============================ -->
        <div x-show="mode === 'register'" x-cloak>
            <div class="border border-neutral-300 rounded-lg p-5 mb-4">
                <h1 class="text-[22px] font-normal text-[#111] mb-4">Create account</h1>

                <form method="POST" action="{{ route('register') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="_register" value="1">

                    <!-- Full Name -->
                    <div>
                        <label for="full_name" class="block text-[13px] font-bold text-[#111] mb-1">Your name</label>
                        <input type="text" name="full_name" id="full_name" value="{{ old('full_name') }}" required autocomplete="name"
                               class="w-full px-3 py-[7px] border border-neutral-400 rounded text-sm text-[#111] focus:outline-none focus:ring-1 focus:ring-neutral-400 focus:border-neutral-400 @error('full_name') border-red-500 @enderror"
                               placeholder="First and last name">
                        @error('full_name')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Phone -->
                    <div>
                        <label for="phone" class="block text-[13px] font-bold text-[#111] mb-1">Mobile number <span class="font-normal text-neutral-500">(optional)</span></label>
                        <input type="tel" name="phone" id="phone" value="{{ old('phone') }}" autocomplete="tel"
                               class="w-full px-3 py-[7px] border border-neutral-400 rounded text-sm text-[#111] focus:outline-none focus:ring-1 focus:ring-neutral-400 focus:border-neutral-400 @error('phone') border-red-500 @enderror"
                               placeholder="Mobile number">
                        @error('phone')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="reg_email" class="block text-[13px] font-bold text-[#111] mb-1">Email</label>
                        <input type="email" name="email" id="reg_email" value="{{ old('_register') ? old('email') : '' }}" required autocomplete="email"
                               class="w-full px-3 py-[7px] border border-neutral-400 rounded text-sm text-[#111] focus:outline-none focus:ring-1 focus:ring-neutral-400 focus:border-neutral-400 @if(old('_register')) @error('email') border-red-500 @enderror @endif">
                        @if(old('_register'))
                            @error('email')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="reg_password" class="block text-[13px] font-bold text-[#111] mb-1">Password</label>
                        <input type="password" name="password" id="reg_password" required autocomplete="new-password"
                               class="w-full px-3 py-[7px] border border-neutral-400 rounded text-sm text-[#111] focus:outline-none focus:ring-1 focus:ring-neutral-400 focus:border-neutral-400 @if(old('_register')) @error('password') border-red-500 @enderror @endif"
                               placeholder="At least 8 characters">
                        <p class="mt-1 text-[12px] text-neutral-600"><span class="font-medium">i</span> Passwords must be at least 8 characters.</p>
                        @if(old('_register'))
                            @error('password')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label for="password_confirmation" class="block text-[13px] font-bold text-[#111] mb-1">Re-enter password</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" required autocomplete="new-password"
                               class="w-full px-3 py-[7px] border border-neutral-400 rounded text-sm text-[#111] focus:outline-none focus:ring-1 focus:ring-neutral-400 focus:border-neutral-400">
                    </div>

                    <!-- Submit -->
                    <button type="submit"
                            class="w-full py-2 px-4 bg-[var(--color-primary-600)] border border-[var(--color-primary-700)] rounded text-[13px] font-medium text-white hover:opacity-90 transition-opacity cursor-pointer">
                        Create your {{ config('app.name') }} account
                    </button>
                </form>

                <p class="mt-4 text-[12px] text-[#111] leading-relaxed">
                    By creating an account, you agree to {{ config('app.name') }}'s
                    <a href="{{ route('terms') }}" class="text-[var(--color-primary-600)] hover:underline">Conditions of Use</a>
                    and
                    <a href="{{ route('privacy') }}" class="text-[var(--color-primary-600)] hover:underline">Privacy Notice</a>.
                </p>
            </div>

            <!-- Divider -->
            <div class="relative my-4">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-neutral-300"></div>
                </div>
                <div class="relative flex justify-center text-[12px]">
                    <span class="bg-white px-3 text-neutral-500">Already have an account?</span>
                </div>
            </div>

            <!-- Sign in button -->
            <button @click="mode = 'login'"
                    class="w-full py-2 px-4 bg-white border border-[var(--color-primary-600)] text-[var(--color-primary-600)] rounded text-[13px] font-medium hover:bg-[var(--color-primary-600)] hover:text-white transition-colors cursor-pointer">
                Sign in to your account
            </button>
        </div>
    </div>

    <!-- Footer -->
    <div class="border-t border-neutral-200 mt-4">
        <div class="max-w-[500px] mx-auto px-4 py-6 text-center">
        </div>
    </div>

    @include('partials.footer')

<script>
function unifiedLogin() {
    return {
        identifier: '{{ old("email", "") }}',
        otp: '',
        step: '{{ $errors->has("email") && old("email") ? "password" : "identifier" }}',
        sending: false, verifying: false,
        error: '{{ $errors->first("email") }}',

        continueLogin() {
            if (!this.identifier.trim()) { this.error = 'Enter email or phone number'; return; }
            this.error = '';
            const isPhone = /^\+?\d[\d\s-]{8,}$/.test(this.identifier.replace(/\s/g, ''));
            if (isPhone) {
                this.sendOtpForIdentifier();
            } else {
                this.step = 'password';
            }
        },

        async sendOtpForIdentifier() {
            this.step = 'sending_otp'; this.error = '';
            try {
                const r = await fetch('{{ route("otp.send-login") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                    body: JSON.stringify({ identifier: this.identifier })
                });
                if (r.status === 429) { this.error = 'Too many attempts. Please wait a few minutes.'; this.step = 'identifier'; return; }
                const d = await r.json();
                if (d.success) { this.step = 'otp_sent'; }
                else { this.error = d.message || 'Failed to send OTP'; this.step = 'identifier'; }
            } catch(e) { this.error = 'Something went wrong. Please try again.'; this.step = 'identifier'; }
        },

        async verifyOtp() {
            if (!this.otp || this.otp.length !== 6) { this.error = 'Enter 6-digit OTP'; return; }
            this.verifying = true; this.error = '';
            try {
                const r = await fetch('{{ route("otp.verify-login") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                    body: JSON.stringify({ identifier: this.identifier, otp: this.otp })
                });
                if (r.status === 429) { this.error = 'Too many attempts. Please wait a few minutes.'; this.verifying = false; return; }
                const d = await r.json();
                if (d.success) { window.location.href = d.redirect; }
                else { this.error = d.message || 'Invalid OTP'; }
            } catch(e) { this.error = 'Something went wrong. Please try again.'; }
            this.verifying = false;
        }
    };
}
function otpReset() {
    return {
        identifier: '', otp: '', password: '', passwordConfirm: '',
        step: 1, sending: false, verifying: false, resetting: false, error: '', success: '',
        async sendOtp() {
            if (!this.identifier) return;
            this.sending = true; this.error = '';
            try {
                const r = await fetch('{{ route("otp.send-reset") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify({ identifier: this.identifier })
                });
                const d = await r.json();
                if (d.success) { this.step = 2; }
                else { this.error = d.message; }
            } catch(e) { this.error = 'Network error'; }
            this.sending = false;
        },
        async verifyOtp() {
            if (!this.otp || this.otp.length !== 6) { this.error = 'Enter 6-digit OTP'; return; }
            this.verifying = true; this.error = '';
            try {
                const r = await fetch('{{ route("otp.verify-reset") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify({ identifier: this.identifier, otp: this.otp })
                });
                const d = await r.json();
                if (d.success) { this.step = 3; }
                else { this.error = d.message; }
            } catch(e) { this.error = 'Network error'; }
            this.verifying = false;
        },
        async resetPassword() {
            if (this.password.length < 8) { this.error = 'Password must be at least 8 characters'; return; }
            if (this.password !== this.passwordConfirm) { this.error = 'Passwords do not match'; return; }
            this.resetting = true; this.error = '';
            try {
                const r = await fetch('{{ route("otp.reset-password") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                    body: JSON.stringify({ password: this.password, password_confirmation: this.passwordConfirm })
                });
                const d = await r.json();
                if (d.success) { window.location.href = d.redirect; }
                else { this.error = d.message; }
            } catch(e) { this.error = 'Network error'; }
            this.resetting = false;
        }
    };
}
</script>
</body>
</html>
