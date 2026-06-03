<x-layouts.admin :title="'Two-Factor Authentication'">
    <x-slot:header>
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-neutral-900">Two-Factor Authentication</h1>
                <p class="text-sm mt-0.5 text-neutral-500">
                    Add an extra layer of security to your admin account
                </p>
            </div>
            <a href="{{ route('admin.profile') }}" class="admin-btn admin-btn-outline inline-flex items-center gap-1.5 no-underline">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back to Profile
            </a>
        </div>
    </x-slot:header>

    <div class="max-w-[640px]">

        {{-- Just enabled: show recovery codes --}}
        @if(!empty($justEnabled) && !empty($recoveryCodes))
            <div class="admin-card p-6 mb-5">
                <div class="flex items-center gap-2.5 mb-4">
                    <div class="w-10 h-10 rounded-full bg-[#e3f1df] flex items-center justify-center">
                        <svg width="20" height="20" fill="none" stroke="#1a7431" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-base font-semibold text-neutral-900">Two-factor authentication enabled</h2>
                        <p class="text-sm text-neutral-500">Your account is now more secure</p>
                    </div>
                </div>

                <div class="bg-[#fff8e1] border border-[#ffe082] rounded-lg p-3.5 mb-4">
                    <p class="text-sm font-semibold text-[#856404] mb-1">Save your recovery codes</p>
                    <p class="text-xs text-[#856404]">
                        Store these codes in a safe place. Each code can only be used once. If you lose access to your authenticator app, you can use these codes to sign in.
                    </p>
                </div>

                <div class="font-mono text-sm bg-neutral-100 border border-neutral-200 rounded-lg p-4 leading-loose mb-4">
                    @foreach($recoveryCodes as $code)
                        <div>{{ $code }}</div>
                    @endforeach
                </div>

                <div class="flex gap-2">
                    <button @click="copyRecoveryCodes()" class="admin-btn admin-btn-primary inline-flex items-center gap-1.5">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                        Copy codes
                    </button>
                    <a href="{{ route('admin.profile') }}" class="admin-btn admin-btn-outline no-underline">Done</a>
                </div>
            </div>

            <script>
                function copyRecoveryCodes() {
                    const codes = @json(implode("\n", $recoveryCodes));
                    navigator.clipboard.writeText(codes).then(function() {
                        if (typeof toastr !== 'undefined') {
                            toastr.success('Recovery codes copied to clipboard');
                        } else {
                            alert('Recovery codes copied to clipboard');
                        }
                    });
                }
            </script>
        @endif

        {{-- Setup form: show QR code --}}
        @if(!empty($qrCodeUrl) && !empty($secret))
            <div class="admin-card p-6 mb-5">
                <h2 class="text-base font-semibold text-neutral-900 mb-1">Set up authenticator app</h2>
                <p class="text-sm text-neutral-500 mb-5">
                    Scan the QR code below with your authenticator app (Google Authenticator, Authy, 1Password, etc.), then enter the 6-digit code to verify.
                </p>

                {{-- Step 1: QR Code --}}
                <div class="mb-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-neutral-500 mb-2.5">Step 1: Scan QR code</p>
                    <div class="bg-white border border-neutral-200 rounded-lg p-5 inline-block">
                        <img src="{{ $qrCodeUrl }}" alt="2FA QR Code" width="200" height="200" class="block">
                    </div>
                </div>

                {{-- Manual entry --}}
                <div class="mb-5">
                    <p class="text-xs text-neutral-500 mb-1.5">
                        Can't scan the QR code? Enter this key manually:
                    </p>
                    <div class="flex items-center gap-2">
                        <code class="text-sm font-semibold bg-neutral-100 border border-neutral-200 rounded-md px-3 py-2 tracking-widest select-all break-all">{{ $secret }}</code>
                        <button type="button" @click="navigator.clipboard.writeText('{{ $secret }}').then(()=>{if(typeof toastr!=='undefined')toastr.success('Secret copied');})" class="admin-btn admin-btn-outline text-xs whitespace-nowrap">
                            Copy
                        </button>
                    </div>
                </div>

                {{-- Step 2: Verify --}}
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-neutral-500 mb-2.5">Step 2: Enter verification code</p>

                    @if(session('error'))
                        <div class="text-sm bg-red-100 border border-red-300 rounded-md px-3.5 py-2.5 text-red-700 mb-3">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.2fa.enable') }}">
                        @csrf
                        <div class="flex gap-2 items-start">
                            <div>
                                <input
                                    type="text"
                                    name="code"
                                    class="admin-input text-lg font-semibold w-40 tracking-[6px] text-center"
                                    placeholder="000000"
                                    maxlength="6"
                                    pattern="[0-9]{6}"
                                    inputmode="numeric"
                                    autocomplete="one-time-code"
                                    autofocus
                                    required
                                >
                                @error('code')
                                    <p class="text-xs text-red-700 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <button type="submit" class="admin-btn admin-btn-primary px-5 py-2.5">
                                Verify &amp; Enable
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        {{-- Disable 2FA (shown when already enabled and not in justEnabled flow) --}}
        @if(!empty($isEnabled) && empty($justEnabled) && empty($qrCodeUrl))
            <div class="admin-card p-6">
                <div class="flex items-center gap-2.5 mb-4">
                    <div class="w-10 h-10 rounded-full bg-[#e3f1df] flex items-center justify-center">
                        <svg width="20" height="20" fill="none" stroke="#1a7431" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-base font-semibold text-neutral-900">Two-factor authentication is enabled</h2>
                        <p class="text-sm text-neutral-500">Your account is protected with TOTP-based 2FA</p>
                    </div>
                </div>

                <div class="border-t border-neutral-200 pt-4 mt-4">
                    <p class="text-sm text-neutral-500 mb-3">
                        To disable two-factor authentication, enter your password below.
                    </p>

                    @if(session('error'))
                        <div class="text-sm bg-red-100 border border-red-300 rounded-md px-3.5 py-2.5 text-red-700 mb-3">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.2fa.disable') }}" @submit="if (!confirm('Are you sure you want to disable two-factor authentication?')) { $event.preventDefault(); }">
                        @csrf
                        <div class="flex gap-2 items-start">
                            <input
                                type="password"
                                name="password"
                                class="admin-input max-w-[260px]"
                                placeholder="Enter your password"
                                required
                            >
                            <button type="submit" class="admin-btn bg-red-700 text-white px-4 py-2.5">
                                Disable 2FA
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

    </div>
</x-layouts.admin>
