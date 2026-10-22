{{-- Exclusive-offer popup: phone capture verified by WhatsApp OTP, pays out a coupon code. --}}
@php
    $popupEnabled = filter_var($theme->get('offer_popup_enabled', '1'), FILTER_VALIDATE_BOOLEAN);

    // Every visitor sees this, including people who already claimed - they get
    // the "you are already a VIP" note when they enter their number, which is
    // the whole point of that step. Checkout is the one exception, and so is /v:
    // that page is reached by scanning the QR on a product already bought, and the
    // popup lands on top of the product drawer the scan was for.
    $onCheckout = request()->is('checkout*') || request()->is('admin*') || request()->is('v');
@endphp

@if($popupEnabled && !$onCheckout)
@php
    $brandName   = $theme->get('site_name', '') ?: ($theme->get('store_name', '') ?: config('app.name'));
    $storeLogo   = $theme->get('store_logo', '');
    $logoPath    = $storeLogo ? (str_starts_with($storeLogo, 'storage/') || str_starts_with($storeLogo, 'images/') ? $storeLogo : 'storage/' . $storeLogo) : '';

    $accent      = $theme->get('offer_popup_accent', '#4D7C0F');
    $accentLight = $theme->get('offer_popup_accent_light', '#B8DC24');

    $delay       = max(0, (int) $theme->get('offer_popup_delay', 5));
    $title       = $theme->get('offer_popup_title', 'Unlock your exclusive offer');
    $subtitle    = $theme->get('offer_popup_subtitle', 'Drop your WhatsApp number and we will send you a VIP code you can use right away.');
    $buttonText  = $theme->get('offer_popup_button_text', 'Get my code on WhatsApp');
    $consentText = $theme->get('offer_popup_consent_text', '')
        ?: 'I agree to receive offers and updates from ' . $brandName . ' on WhatsApp.';
    $footerText  = $theme->get('offer_popup_footer_text', 'One code per number. You can opt out any time by replying STOP.');
    $prefillPhone = auth()->check() ? (string) (auth()->user()->phone ?? '') : '';
    $prefillName  = auth()->check() ? trim((string) (auth()->user()->first_name ?? '')) : '';
@endphp

<div x-data="offerPopup({ delay: {{ $delay }}, phone: @js($prefillPhone), name: @js($prefillName) })"
     x-show="show"
     x-cloak
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-9999 flex items-center justify-center p-4"
     style="background:rgba(0,0,0,0.55);backdrop-filter:blur(4px);"
     @click.self="dismiss()"
     @keydown.escape.window="dismiss()"
     role="dialog"
     aria-modal="true"
     aria-label="Exclusive offer">

    <div x-transition:enter="transition ease-out duration-300 delay-75"
         x-transition:enter-start="opacity-0 translate-y-4 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="relative w-full max-w-sm bg-white rounded-2xl shadow-2xl overflow-hidden max-h-[92vh] overflow-y-auto">

        {{-- Close --}}
        <button @click="dismiss()" type="button" aria-label="Close"
                class="absolute top-3 right-3 z-10 w-8 h-8 flex items-center justify-center rounded-full bg-white/80 hover:bg-white text-neutral-500 hover:text-neutral-900 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>

        {{-- Brand band --}}
        <div class="px-6 pt-6 pb-5 text-center"
             style="background:linear-gradient(135deg, {{ $accentLight }} 0%, {{ $accent }} 140%);">
            @if($logoPath)
                <img src="{{ asset($logoPath) }}" alt="{{ $brandName }}" class="h-9 w-auto mx-auto mb-3 object-contain">
            @else
                <p class="text-sm font-bold text-neutral-900 mb-3">{{ $brandName }}</p>
            @endif
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-black/85 text-white text-[10px] font-bold uppercase tracking-widest">
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l2.6 5.6 6.1.8-4.5 4.2 1.2 6.1L12 15.8 6.6 18.7l1.2-6.1L3.3 8.4l6.1-.8L12 2z"/></svg>
                VIP Offer
            </span>
        </div>

        <div class="px-6 py-6">

            {{-- Step 1: phone + consent --}}
            <div x-show="step === 'phone'">
                <h2 class="text-xl font-extrabold text-neutral-900 text-center leading-snug">{{ $title }}</h2>
                <p class="text-sm text-neutral-500 text-center mt-2 leading-relaxed">{{ $subtitle }}</p>

                <form @submit.prevent="sendOtp()" class="mt-5 space-y-3">
                    <div class="flex items-stretch rounded-xl border border-neutral-300 focus-within:border-neutral-500 focus-within:ring-2 focus-within:ring-neutral-200 overflow-hidden">
                        <span class="flex items-center px-3 bg-neutral-50 text-sm font-semibold text-neutral-600 border-r border-neutral-200">+91</span>
                        <input type="tel" x-model="phone" x-ref="phoneInput"
                               inputmode="numeric" autocomplete="tel" maxlength="10"
                               @input="phone = phone.replace(/\D/g, '').slice(0, 10); error = ''"
                               placeholder="WhatsApp number"
                               class="flex-1 min-w-0 px-3 py-3 text-sm text-neutral-900 placeholder-neutral-400 focus:outline-none">
                    </div>

                    <label class="flex items-start gap-2.5 text-left cursor-pointer">
                        <input type="checkbox" x-model="consent" @change="error = ''"
                               class="mt-0.5 w-4 h-4 shrink-0 rounded border-neutral-300 focus:ring-2 focus:ring-offset-0"
                               style="accent-color: {{ $accent }};">
                        <span class="text-[12px] leading-relaxed text-neutral-600">{{ $consentText }}</span>
                    </label>

                    <p x-show="error" x-cloak x-text="error" class="text-xs text-red-600 leading-relaxed"></p>

                    <button type="submit" :disabled="busy"
                            class="w-full py-3 rounded-xl font-semibold text-sm text-white transition-opacity hover:opacity-90 disabled:opacity-60 flex items-center justify-center gap-2"
                            style="background-color: {{ $accent }};">
                        <svg x-show="!busy" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.5 14.4c-.3-.2-1.7-.9-2-1-.3-.1-.5-.2-.7.2-.2.3-.7 1-.9 1.2-.2.2-.3.2-.6.1-.3-.2-1.3-.5-2.4-1.5-.9-.8-1.5-1.8-1.6-2.1-.2-.3 0-.5.1-.6l.5-.5c.1-.2.2-.3.3-.5 0-.2 0-.4-.1-.5 0-.2-.7-1.6-.9-2.2-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.5s1.1 2.9 1.2 3.1c.1.2 2.1 3.2 5 4.5.7.3 1.2.5 1.7.6.7.2 1.3.2 1.8.1.6-.1 1.7-.7 1.9-1.4.2-.7.2-1.2.2-1.4-.1-.1-.3-.2-.6-.3M12 2a10 10 0 00-8.6 15L2 22l5.1-1.3A10 10 0 1012 2z"/></svg>
                        <span x-text="busy ? 'Sending...' : @js($buttonText)"></span>
                    </button>
                </form>

                <button @click="dismiss()" type="button" class="w-full mt-3 text-xs font-medium text-neutral-400 hover:text-neutral-600 transition-colors">No thanks, maybe later</button>
                <p class="text-[11px] text-neutral-400 text-center mt-3 leading-relaxed">{{ $footerText }}</p>
            </div>

            {{-- Step 2: OTP --}}
            <div x-show="step === 'otp'" x-cloak>
                <h2 class="text-xl font-extrabold text-neutral-900 text-center leading-snug">Verify your number</h2>
                <p class="text-sm text-neutral-500 text-center mt-2 leading-relaxed">
                    We sent a 6-digit code on WhatsApp to <span class="font-semibold text-neutral-800" x-text="'+91 ' + phone"></span>
                </p>

                <form @submit.prevent="verifyOtp()" class="mt-5 space-y-3">
                    <input type="text" x-model="otp" x-ref="otpInput"
                           inputmode="numeric" autocomplete="one-time-code" maxlength="6"
                           @input="otp = otp.replace(/\D/g, '').slice(0, 6); error = ''; if (otp.length === 6) verifyOtp()"
                           placeholder="------"
                           class="w-full px-4 py-3 text-center text-2xl font-bold tracking-[0.5em] text-neutral-900 placeholder-neutral-300 border border-neutral-300 rounded-xl focus:outline-none focus:border-neutral-500 focus:ring-2 focus:ring-neutral-200">

                    <p x-show="error" x-cloak x-text="error" class="text-xs text-red-600 leading-relaxed"></p>

                    <button type="submit" :disabled="busy || otp.length !== 6"
                            class="w-full py-3 rounded-xl font-semibold text-sm text-white transition-opacity hover:opacity-90 disabled:opacity-60"
                            style="background-color: {{ $accent }};"
                            x-text="busy ? 'Verifying...' : 'Verify &amp; get my code'"></button>
                </form>

                <div class="flex items-center justify-between mt-4 text-xs">
                    <button @click="step = 'phone'; error = ''" type="button" class="font-medium text-neutral-500 hover:text-neutral-800 transition-colors">Change number</button>
                    <button @click="resend()" type="button" :disabled="resendIn > 0 || busy"
                            class="font-medium transition-colors disabled:text-neutral-400"
                            :style="resendIn > 0 ? '' : 'color: {{ $accent }}'"
                            x-text="resendIn > 0 ? `Resend in ${resendIn}s` : 'Resend code'"></button>
                </div>
            </div>

            {{-- Step 3: confirmation. Two flavours - code just sent, or this
                 number claimed the offer once already and is being welcomed back. --}}
            <div x-show="step === 'done'" x-cloak class="text-center">
                <div class="w-14 h-14 mx-auto rounded-full flex items-center justify-center mb-3" style="background-color: {{ $accentLight }};">
                    <svg x-show="!alreadyRegistered" class="w-7 h-7 text-neutral-900" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    <svg x-show="alreadyRegistered" x-cloak class="w-7 h-7 text-neutral-900" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l2.6 5.6 6.1.8-4.5 4.2 1.2 6.1L12 15.8 6.6 18.7l1.2-6.1L3.3 8.4l6.1-.8L12 2z"/></svg>
                </div>

                <h2 class="text-xl font-extrabold text-neutral-900 leading-snug" x-text="heading"></h2>
                <p class="text-sm text-neutral-500 mt-2 leading-relaxed" x-text="message"></p>

                {{-- The code itself is deliberately not rendered here: it is sent
                     only over WhatsApp, so the number has to be real to get it. --}}
                <div class="mt-5 rounded-xl border-2 border-dashed p-4 flex items-start gap-3 text-left"
                     style="border-color: {{ $accent }}; background-color: {{ $accentLight }}1a;">
                    <svg class="w-6 h-6 shrink-0 mt-0.5" fill="#25D366" viewBox="0 0 24 24"><path d="M17.5 14.4c-.3-.2-1.7-.9-2-1-.3-.1-.5-.2-.7.2-.2.3-.7 1-.9 1.2-.2.2-.3.2-.6.1-.3-.2-1.3-.5-2.4-1.5-.9-.8-1.5-1.8-1.6-2.1-.2-.3 0-.5.1-.6l.5-.5c.1-.2.2-.3.3-.5 0-.2 0-.4-.1-.5 0-.2-.7-1.6-.9-2.2-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.5s1.1 2.9 1.2 3.1c.1.2 2.1 3.2 5 4.5.7.3 1.2.5 1.7.6.7.2 1.3.2 1.8.1.6-.1 1.7-.7 1.9-1.4.2-.7.2-1.2.2-1.4-.1-.1-.3-.2-.6-.3M12 2a10 10 0 00-8.6 15L2 22l5.1-1.3A10 10 0 1012 2z"/></svg>
                    <div>
                        <p class="text-sm font-bold text-neutral-900" x-text="alreadyRegistered ? 'Already in your WhatsApp' : 'Sent on WhatsApp'"></p>
                        <p class="text-xs text-neutral-600 mt-0.5 leading-relaxed">
                            Your coupon code is in your chat on <span class="font-semibold" x-text="'+91 ' + phone"></span>. Apply it in the coupon box at checkout to claim <span x-text="offerText"></span>.
                        </p>
                    </div>
                </div>

                <a :href="shopUrl"
                   class="block w-full mt-4 py-3 rounded-xl font-semibold text-sm text-white text-center transition-opacity hover:opacity-90"
                   style="background-color: {{ $accent }};">Start shopping</a>

                <p x-show="!delivered && !alreadyRegistered" x-cloak class="text-[11px] text-neutral-400 mt-3 leading-relaxed">
                    Not there yet? It can take a minute to arrive.
                </p>
                <p x-show="alreadyRegistered" x-cloak class="text-[11px] text-neutral-400 mt-3 leading-relaxed">
                    Cannot find it? Message us on WhatsApp and we will resend it.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
function offerPopup(config) {
    return {
        show: false,
        step: 'phone',
        phone: config.phone || '',
        name: config.name || '',
        consent: false,
        otp: '',
        offerText: '',
        shopUrl: '{{ url('/products') }}',
        heading: '',
        message: '',
        error: '',
        busy: false,
        delivered: true,
        alreadyRegistered: false,
        resendIn: 0,
        resendTimer: null,

        init() {
            // Shown to everyone - a number that already claimed is told so at the
            // number step. Only limit is once per browsing session, so it does not
            // reopen on every page the shopper clicks through to.
            if (sessionStorage.getItem('offer_popup_seen')) return;

            setTimeout(() => this.tryShow(0), (config.delay ?? 5) * 1000);
        },

        tryShow(attempt) {
            // The Shiprocket checkout opens as a full-screen overlay; dropping an
            // offer on top of someone mid-payment is the one place this must not
            // appear. Wait it out rather than giving up on the lead.
            const checkoutOpen = document.body.classList.contains('sr-checkout-open')
                || document.getElementById('fastrr-main-container');

            if (checkoutOpen) {
                if (attempt < 12) setTimeout(() => this.tryShow(attempt + 1), 5000);
                return;
            }

            this.show = true;
            sessionStorage.setItem('offer_popup_seen', '1');

            // Desktop only: focusing on a phone opens the keyboard over the modal.
            if (window.innerWidth >= 1024) {
                this.$nextTick(() => this.$refs.phoneInput?.focus());
            }
        },

        dismiss() {
            this.show = false;
            if (this.resendTimer) clearInterval(this.resendTimer);
        },

        async post(url, body) {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                credentials: 'same-origin',
                body: JSON.stringify(body),
            });

            let data = {};
            try { data = await res.json(); } catch (e) {}

            // Laravel validation errors come back under `errors`, not `message`.
            if (!res.ok && data.errors) {
                data.message = Object.values(data.errors).flat()[0] || data.message;
            }

            return { ok: res.ok, data };
        },

        async sendOtp() {
            if (this.busy) return;

            if (!/^[6-9]\d{9}$/.test(this.phone)) {
                this.error = 'Please enter a valid 10-digit mobile number.';
                return;
            }
            if (!this.consent) {
                this.error = 'Please tick the box to receive offers on WhatsApp.';
                return;
            }

            this.busy = true;
            this.error = '';

            try {
                const { ok, data } = await this.post('{{ route('offer-popup.send-otp') }}', {
                    phone: this.phone,
                    name: this.name,
                    consent: true,
                    page_url: window.location.href,
                });

                if (!ok) {
                    this.error = data.message || 'Could not send the code. Please try again.';
                    return;
                }

                // This number claimed the offer before: no OTP was sent, and it
                // goes straight to a welcome-back note rather than a dead end.
                if (data.status === 'already_registered') {
                    this.heading = data.heading || 'You are already a VIP!';
                    this.message = data.message || '';
                    this.shopUrl = data.shop_url || this.shopUrl;
                    this.alreadyRegistered = true;
                    this.delivered = true;
                    this.step = 'done';
                    if (this.resendTimer) clearInterval(this.resendTimer);
                    return;
                }

                this.step = 'otp';
                this.otp = '';
                this.startResendTimer();
                this.$nextTick(() => this.$refs.otpInput?.focus());
            } catch (e) {
                this.error = 'Network error. Please try again.';
            } finally {
                this.busy = false;
            }
        },

        async verifyOtp() {
            if (this.busy || this.otp.length !== 6) return;

            this.busy = true;
            this.error = '';

            try {
                const { ok, data } = await this.post('{{ route('offer-popup.verify-otp') }}', {
                    phone: this.phone,
                    otp: this.otp,
                    name: this.name,
                });

                if (!ok) {
                    this.error = data.message || 'That code did not work. Please try again.';
                    this.otp = '';
                    return;
                }

                this.offerText = data.offer_text || 'your exclusive discount';
                this.shopUrl = data.shop_url || this.shopUrl;
                this.heading = data.heading || 'You are on the VIP list!';
                this.message = data.message || '';
                this.delivered = data.delivered !== false;
                this.step = 'done';

                if (this.resendTimer) clearInterval(this.resendTimer);
            } catch (e) {
                this.error = 'Network error. Please try again.';
            } finally {
                this.busy = false;
            }
        },

        async resend() {
            if (this.resendIn > 0) return;
            this.otp = '';
            await this.sendOtp();
        },

        startResendTimer() {
            this.resendIn = 30;
            if (this.resendTimer) clearInterval(this.resendTimer);
            this.resendTimer = setInterval(() => {
                this.resendIn--;
                if (this.resendIn <= 0) clearInterval(this.resendTimer);
            }, 1000);
        },

    };
}
</script>
@endif
