<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>Reset Password - {{ config('app.name') }}</title>
    @php $faviconRaw = \App\Models\Setting::get('store_favicon', '') ?: \App\Models\Setting::get('store_logo', ''); @endphp
    @if($faviconRaw)
    <link rel="icon" href="/{{ ltrim($faviconRaw, '/') }}" type="image/png">
    <link rel="shortcut icon" href="/{{ ltrim($faviconRaw, '/') }}">
    @endif

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800|poppins:300,400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .form-enter { animation: formIn 0.45s cubic-bezier(0.4, 0, 0.2, 1) forwards; }
        @keyframes formIn {
            from { opacity: 0; transform: translateY(12px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @media (max-width: 1023px) {
            body { background: linear-gradient(180deg, #ffffff 0%, #fdf5ff 50%, #fae6ff 100%); }
        }
    </style>
</head>
<body class="font-sans antialiased bg-white" x-data>
    @include('partials.header')

    <div class="min-h-[60vh] flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md mx-auto form-enter">

                <!-- Header -->
                <div class="mb-5">
                    <div class="w-12 h-12 bg-primary-600/10 rounded-xl flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/>
                        </svg>
                    </div>
                    <h1 class="text-2xl font-bold text-neutral-900 mb-1">Forgot your password?</h1>
                    <p class="text-neutral-600 text-sm">No worries, we'll send you reset instructions.</p>
                </div>

                @if (session('status'))
                    <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl p-4 mb-5">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-sm">{{ session('status') }}</p>
                        </div>
                    </div>
                @endif

                <div x-data="otpReset()">
                    {{-- Step 1: Enter phone/email --}}
                    <div x-show="step === 1" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-1">Phone Number or Email</label>
                            <input type="text" x-model="identifier" required autofocus
                                   class="w-full px-4 py-3 bg-neutral-50 border border-neutral-200 rounded-xl text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-1 focus:ring-neutral-300 focus:border-neutral-300 transition-all"
                                   placeholder="Phone number or email">
                        </div>
                        <p x-show="error" x-text="error" class="text-sm text-red-600" x-cloak></p>
                        <button @click="sendOtp()" :disabled="sending" type="button"
                                class="w-full py-2 px-4 bg-gradient-to-r from-accent-500 to-accent-600 hover:from-accent-600 hover:to-accent-600 text-white font-semibold rounded-xl transition-all disabled:opacity-50">
                            <span x-show="!sending">Send OTP</span>
                            <span x-show="sending">Sending...</span>
                        </button>
                        <p class="text-xs text-neutral-500 text-center">OTP will be sent via WhatsApp + Email</p>
                    </div>

                    {{-- Step 2: Enter OTP --}}
                    <div x-show="step === 2" x-cloak class="space-y-4">
                        <p class="text-sm text-neutral-600">OTP sent to <strong x-text="identifier"></strong></p>
                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-1">Enter OTP</label>
                            <input type="text" x-model="otp" maxlength="6"
                                   class="w-full px-4 py-3 bg-neutral-50 border border-neutral-200 rounded-xl text-neutral-900 text-center text-xl tracking-[8px] font-bold focus:outline-none focus:ring-1 focus:ring-neutral-300 focus:border-neutral-300"
                                   placeholder="------">
                        </div>
                        <p x-show="error" x-text="error" class="text-sm text-red-600" x-cloak></p>
                        <button @click="verifyOtp()" :disabled="verifying" type="button"
                                class="w-full py-2 px-4 bg-gradient-to-r from-accent-500 to-accent-600 text-white font-semibold rounded-xl disabled:opacity-50">
                            <span x-show="!verifying">Verify OTP</span>
                            <span x-show="verifying">Verifying...</span>
                        </button>
                        <button @click="step=1;otp='';error=''" type="button" class="w-full text-sm text-primary-600 hover:underline">Change number/email</button>
                    </div>

                    {{-- Step 3: Set new password --}}
                    <div x-show="step === 3" x-cloak class="space-y-4">
                        <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl p-3 text-sm">OTP verified! Set your new password.</div>
                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-1">New Password</label>
                            <input type="password" x-model="password" minlength="8"
                                   class="w-full px-4 py-3 bg-neutral-50 border border-neutral-200 rounded-xl focus:outline-none focus:ring-1 focus:ring-neutral-300 focus:border-neutral-300"
                                   placeholder="Min 8 characters">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-1">Confirm Password</label>
                            <input type="password" x-model="passwordConfirm"
                                   class="w-full px-4 py-3 bg-neutral-50 border border-neutral-200 rounded-xl focus:outline-none focus:ring-1 focus:ring-neutral-300 focus:border-neutral-300"
                                   placeholder="Confirm password">
                        </div>
                        <p x-show="error" x-text="error" class="text-sm text-red-600" x-cloak></p>
                        <button @click="resetPassword()" :disabled="resetting" type="button"
                                class="w-full py-2 px-4 bg-gradient-to-r from-accent-500 to-accent-600 text-white font-semibold rounded-xl disabled:opacity-50">
                            <span x-show="!resetting">Reset Password</span>
                            <span x-show="resetting">Resetting...</span>
                        </button>
                    </div>
                </div>

                <!-- Back to Login -->
                <p class="mt-5 text-center text-sm text-neutral-600">
                    Remember your password?
                    <a href="{{ route('login') }}" class="font-semibold text-primary-600 hover:text-primary-700 transition-colors">Sign in</a>
                </p>

            </div>
        </div>

    @include('partials.footer')

<script>
function otpReset() {
    return {
        identifier: '', otp: '', password: '', passwordConfirm: '',
        step: 1, sending: false, verifying: false, resetting: false, error: '',
        async sendOtp() {
            if (!this.identifier) return;
            this.sending = true; this.error = '';
            try {
                const r = await fetch('{{ route("otp.send-reset") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                    body: JSON.stringify({ identifier: this.identifier })
                });
                if (r.status === 429) { this.error = 'Too many attempts. Please wait a few minutes.'; this.sending = false; return; }
                const d = await r.json();
                if (d.success) { this.step = 2; }
                else { this.error = d.message; }
            } catch(e) { this.error = 'Something went wrong. Please try again.'; }
            this.sending = false;
        },
        async verifyOtp() {
            if (!this.otp || this.otp.length !== 6) { this.error = 'Enter 6-digit OTP'; return; }
            this.verifying = true; this.error = '';
            try {
                const r = await fetch('{{ route("otp.verify-reset") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                    body: JSON.stringify({ identifier: this.identifier, otp: this.otp })
                });
                if (r.status === 429) { this.error = 'Too many attempts. Please wait a few minutes.'; this.verifying = false; return; }
                const d = await r.json();
                if (d.success) { this.step = 3; }
                else { this.error = d.message; }
            } catch(e) { this.error = 'Something went wrong. Please try again.'; }
            this.verifying = false;
        },
        async resetPassword() {
            if (this.password.length < 8) { this.error = 'Min 8 characters'; return; }
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
