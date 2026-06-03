<x-layouts.app>
    <x-slot name="title">Checkout - {{ config('app.name') }}</x-slot>

    @push('meta')
        <meta name="description" content="Secure checkout at {{ config('app.name') }}. Fast payment with Razorpay or Partial Pay.">
        <meta name="robots" content="noindex, nofollow">
        <meta property="og:title" content="Checkout - {{ config('app.name') }}">
        <meta property="og:description" content="Complete your order securely at {{ config('app.name') }}.">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ route('checkout.index') }}">
        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="Checkout - {{ config('app.name') }}">

        <?php
        $checkoutSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => 'Checkout',
            'description' => 'Secure checkout at ' . config('app.name'),
            'url' => route('checkout.index'),
            'breadcrumb' => [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'Cart', 'item' => route('cart.index')],
                    ['@type' => 'ListItem', 'position' => 3, 'name' => 'Checkout'],
                ],
            ],
            'potentialAction' => [
                '@type' => 'OrderAction',
                'target' => route('checkout.process'),
            ],
        ];
        ?>
        <script type="application/ld+json">{!! json_encode($checkoutSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
        {{-- Phone input styled inline - India only --}}
        <style>
            @media (min-width: 1024px) {
                #checkout-grid { display: flex !important; flex-direction: row !important; align-items: flex-start !important; gap: 16px !important; }
                #checkout-left { flex: 1 !important; min-width: 0 !important; }
                #checkout-right { width: 340px !important; flex-shrink: 0 !important; position: sticky !important; top: 16px !important; }
            }
            .iti { width: 100%; }
            .iti__tel-input { width: 100% !important; }
        </style>
    @endpush

    {{-- Facebook Pixel: InitiateCheckout --}}
    @if(!empty($fbEventId) && $theme->get('facebook_pixel_id', ''))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof fbq !== 'undefined') {
                fbq('track', 'InitiateCheckout', {
                    content_ids: {!! json_encode($cart->items->pluck('product_id')->map('strval')->values()->toArray()) !!},
                    content_type: 'product',
                    value: {{ (float) ($cart->subtotal - $cart->discount) }},
                    currency: 'INR',
                    num_items: {{ $cart->items->sum('quantity') }}
                }, {eventID: '{{ $fbEventId }}'});
            }
        });
    </script>
    @endif

    {{-- Shopify-like: all config passed from controller, JS reads from window.__checkout --}}
    <script>window.__checkout = {!! json_encode($jsConfig, JSON_UNESCAPED_SLASHES) !!};</script>

    <div class="bg-[#F7F8FA] min-h-screen">
        <div class="container mx-auto px-3 py-3">
            <x-breadcrumb :items="[['label' => 'Cart', 'url' => route('cart.index')], ['label' => 'Checkout', 'url' => null]]" />
        </div>

        <div class="container mx-auto px-3 pb-8">
            {{-- Header with back + user info + logout --}}
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-3">
                    <a href="{{ route('cart.index') }}" class="text-xs text-link hover:text-link-hover font-medium flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        Cart
                    </a>
                    <h1 class="text-base font-bold text-[#0F1111]">Checkout</h1>
                </div>
                @auth
                <div class="flex items-center gap-2">
                    <span class="text-xs text-[#3a3a3a]">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-xs text-[#CC0C39] hover:underline font-medium">Logout</button>
                    </form>
                </div>
                @endauth
            </div>

            {{-- Express Checkout Banner (for returning users with saved preferences) --}}
            @if(!empty($oneClickReady) && $defaultAddress)
            <div class="bg-linear-to-r from-primary-600 to-primary-700 rounded-lg p-3 mb-3 text-white" x-data="{ expressLoading: false }">
                <div class="flex items-center justify-between">
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-bold">Express Checkout</p>
                        <p class="text-[10px] opacity-80 mt-0.5">Ship to {{ $defaultAddress->name }} — {{ $defaultAddress->city }}, {{ $defaultAddress->postal_code }}</p>
                    </div>
                    <form action="{{ route('checkout.process') }}" method="POST" @submit="expressLoading = true">
                        @csrf
                        <input type="hidden" name="shipping_address_id" value="{{ $defaultAddress->id }}">
                        <input type="hidden" name="same_billing_address" value="1">
                        <input type="hidden" name="payment_method" value="{{ $checkoutPreference->default_payment_method ?? 'cod' }}">
                        <input type="hidden" name="express_checkout" value="1">
                        <button type="submit" :disabled="expressLoading"
                                class="px-4 py-2 bg-primary-700 hover:bg-primary-800 text-white text-xs font-bold rounded-lg transition-colors whitespace-nowrap disabled:opacity-50">
                            <span x-show="!expressLoading">Place Order</span>
                            <span x-show="expressLoading">Processing...</span>
                        </button>
                    </form>
                </div>
            </div>
            @endif

            {{-- Payment availability computed in controller --}}

            <form action="{{ route('checkout.process') }}" method="POST"
                  x-data="checkoutForm('{{ $firstMethod }}')"
                  @submit.prevent="handleSubmit($event)"
                  @cart-totals-updated.window="handleCartUpdate($event)">
                @csrf

                <div id="checkout-grid" class="space-y-4">
                    {{-- ═══ LEFT COLUMN ═══ --}}
                    <div id="checkout-left" class="space-y-3">

                        {{-- ── Section 1: Contact + Shipping (merged for guests, just shipping for auth) ── --}}
                        <div class="bg-white rounded border border-[#E3E6E6]">
                            <div class="flex items-center gap-2 px-3 py-2.5 border-b border-[#E3E6E6] bg-[#F7F8FA]">
                                <div class="w-5 h-5 rounded-full bg-primary-600 text-white text-[10px] font-bold flex items-center justify-center">1</div>
                                <h2 class="text-xs font-bold text-[#0F1111] uppercase tracking-wide">
                                    @if($isGuest) Contact & Shipping @else Shipping Address @endif
                                </h2>
                            </div>

                            <div class="p-3">
                                @if($isGuest)
                                {{-- Guest: contact + address in one compact block --}}
                                <p class="text-[11px] text-[#3a3a3a] mb-2">Have an account? <a href="{{ route('login') }}" class="text-link hover:text-link-hover font-medium">Log in</a> for faster checkout.</p>

                                <div class="space-y-2 mb-2">
                                    <div x-data="{
                                        cc: '+91',
                                        codes: [
                                            {c:'+91',f:'🇮🇳',p:'98765 43210',mx:10},
                                            {c:'+1',f:'🇺🇸',p:'(555) 123-4567',mx:10},
                                            {c:'+44',f:'🇬🇧',p:'7911 123456',mx:11},
                                            {c:'+971',f:'🇦🇪',p:'50 123 4567',mx:9},
                                            {c:'+65',f:'🇸🇬',p:'9123 4567',mx:8},
                                            {c:'+61',f:'🇦🇺',p:'412 345 678',mx:9},
                                        ],
                                        get sel(){ return this.codes.find(x=>x.c===this.cc)||this.codes[0]; },
                                        detect(v){ if(v.startsWith('+')){let m=this.codes.find(x=>v.startsWith(x.c));if(m){this.cc=m.c;this.$refs.ph.value=v.substring(m.c.length).replace(/\D/g,'');}} }
                                    }">
                                        <label class="block text-xs font-semibold text-[#3a3a3a] mb-0.5">Phone *</label>
                                        <div class="flex">
                                            <select x-model="cc" class="text-sm border border-r-0 border-[#E3E6E6] pl-2.5 pr-1 py-2.5 bg-[#F7F8FA] text-[#3a3a3a]" style="min-width:75px;border-radius:8px 0 0 8px;">
                                                <template x-for="c in codes" :key="c.c"><option :value="c.c" x-text="c.f+' '+c.c"></option></template>
                                            </select>
                                            <input type="tel" name="guest_phone" id="guest_phone" x-ref="ph" value="{{ old('guest_phone') }}" required autocomplete="tel" autofocus
                                                   :maxlength="sel.mx" inputmode="numeric"
                                                   class="w-full text-sm border border-[#E3E6E6] px-3 py-2.5" style="border-radius:0 8px 8px 0;" :placeholder="sel.p"
                                                   @input="detect($el.value); captureAbandoned(false)" @blur="captureAbandoned(true)">
                                        </div>
                                        @error('guest_phone') <p class="text-[10px] text-[#CC0C39] mt-0.5">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-xs font-semibold text-[#3a3a3a] mb-0.5">Name *</label>
                                            <input type="text" name="guest_name" value="{{ old('guest_name') }}" required autocomplete="name"
                                                   class="w-full text-sm border border-[#E3E6E6] rounded-lg px-3 py-2.5 focus:border-link focus:outline-none focus:ring-1 focus:ring-primary-600" placeholder="Full name"
                                                   @input="captureAbandoned(false)" @blur="captureAbandoned(true)">
                                            @error('guest_name') <p class="text-[10px] text-[#CC0C39] mt-0.5">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold text-[#3a3a3a] mb-0.5">Email <span class="text-[#CC0C39]">*</span></label>
                                            <input type="email" name="guest_email" value="{{ old('guest_email') }}" autocomplete="email" required
                                                   class="w-full text-sm border border-[#E3E6E6] rounded-lg px-3 py-2.5 focus:border-link focus:outline-none focus:ring-1 focus:ring-primary-600" placeholder="email@example.com"
                                                   @input="captureAbandoned(false)" @blur="captureAbandoned(true)">
                                            @error('guest_email') <p class="text-[10px] text-[#CC0C39] mt-0.5">{{ $message }}</p> @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="border-t border-dashed border-[#E3E6E6] my-2"></div>

                                {{-- Shipping fields with PIN autocomplete --}}
                                <div class="space-y-2" x-data="pinLookup()">
                                    {{-- Use Current Location (GPS) --}}
                                    <button type="button" @click="useGps()" :disabled="gpsLoading"
                                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2 text-xs font-semibold text-primary-700 bg-primary-50 hover:bg-primary-100 border border-primary-200 rounded transition-colors disabled:opacity-60">
                                        <svg x-show="!gpsLoading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        <svg x-show="gpsLoading" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"></circle><path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" class="opacity-75"></path></svg>
                                        <span x-text="gpsLoading ? 'Detecting your location…' : 'Use My Current Location'"></span>
                                    </button>
                                    <p x-show="gpsError" x-text="gpsError" class="text-[10px] text-[#CC0C39]" x-cloak></p>

                                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                        <div>
                                            <label class="block text-xs font-semibold text-[#3a3a3a] mb-0.5">PIN Code *</label>
                                            <input type="text" name="shipping_postal_code" x-model="pin" @input="fetchPinData()" value="{{ old('shipping_postal_code') }}" required maxlength="6" autocomplete="postal-code"
                                                   class="w-full text-sm border border-[#E3E6E6] rounded-lg px-3 py-2.5 focus:border-link focus:outline-none focus:ring-1 focus:ring-primary-600" placeholder="400001">
                                            <p x-show="pinError" x-text="pinError" class="text-[10px] text-[#CC0C39] mt-0.5" x-cloak></p>
                                            <p x-show="pinServiceable === true" class="text-[10px] text-[#067D62] mt-0.5 flex items-center gap-0.5" x-cloak>
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                Delivery available
                                            </p>
                                            @error('shipping_postal_code') <p class="text-[10px] text-[#CC0C39] mt-0.5">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold text-[#3a3a3a] mb-0.5">City *</label>
                                            <input type="text" name="shipping_city" x-model="city" value="{{ old('shipping_city') }}" required autocomplete="address-level2"
                                                   class="w-full text-sm border border-[#E3E6E6] rounded-lg px-3 py-2.5 focus:border-link focus:outline-none focus:ring-1 focus:ring-primary-600" placeholder="City">
                                            @error('shipping_city') <p class="text-[10px] text-[#CC0C39] mt-0.5">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold text-[#3a3a3a] mb-0.5">State *</label>
                                            <input type="text" name="shipping_state" x-model="state" value="{{ old('shipping_state') }}" required autocomplete="address-level1"
                                                   class="w-full text-sm border border-[#E3E6E6] rounded-lg px-3 py-2.5 focus:border-link focus:outline-none focus:ring-1 focus:ring-primary-600" placeholder="State">
                                            @error('shipping_state') <p class="text-[10px] text-[#CC0C39] mt-0.5">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold text-[#3a3a3a] mb-0.5">Recipient Name *</label>
                                            <input type="text" name="shipping_name" value="{{ old('shipping_name') }}" required autocomplete="shipping name"
                                                   class="w-full text-sm border border-[#E3E6E6] rounded-lg px-3 py-2.5 focus:border-link focus:outline-none focus:ring-1 focus:ring-primary-600" placeholder="Recipient name">
                                            @error('shipping_name') <p class="text-[10px] text-[#CC0C39] mt-0.5">{{ $message }}</p> @enderror
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-xs font-semibold text-[#3a3a3a] mb-0.5">Address Line 2</label>
                                            <input type="text" name="shipping_address_line_2" value="{{ old('shipping_address_line_2') }}" autocomplete="address-line2"
                                                   class="w-full text-sm border border-[#E3E6E6] rounded-lg px-3 py-2.5 focus:border-link focus:outline-none focus:ring-1 focus:ring-primary-600" placeholder="Area, Landmark (optional)">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-[#3a3a3a] mb-0.5">Address *</label>
                                        <input type="text" name="shipping_address_line_1" x-model="address" value="{{ old('shipping_address_line_1') }}" required autocomplete="address-line1"
                                               class="w-full text-sm border border-[#E3E6E6] rounded-lg px-3 py-2.5 focus:border-link focus:outline-none focus:ring-1 focus:ring-primary-600" placeholder="House no., Building, Street">
                                        @error('shipping_address_line_1') <p class="text-[10px] text-[#CC0C39] mt-0.5">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                                <input type="hidden" name="same_billing_address" value="1">
                                @else
                                {{-- Authenticated: saved addresses compact --}}
                                @if($addresses->count())
                                    <div class="space-y-2">
                                        @foreach($addresses as $address)
                                            <label class="flex items-start gap-2.5 p-2.5 border rounded cursor-pointer transition-colors
                                                {{ $address->id === $defaultAddress?->id ? 'border-primary-600 bg-primary-600/5' : 'border-[#E3E6E6] hover:border-link' }}">
                                                <input type="radio" name="shipping_address_id" value="{{ $address->id }}"
                                                       {{ $address->id === $defaultAddress?->id ? 'checked' : '' }}
                                                       class="mt-0.5 text-primary-600 focus:ring-primary-600">
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center gap-1.5">
                                                        <span class="text-xs font-semibold text-[#0F1111]">{{ $address->name }}</span>
                                                        @if($address->is_default)
                                                            <span class="text-[9px] font-medium text-primary-600 bg-primary-600/10 px-1 py-px rounded">Default</span>
                                                        @endif
                                                    </div>
                                                    <p class="text-[11px] text-[#3a3a3a] leading-relaxed">
                                                        {{ $address->address_line_1 }}{{ $address->address_line_2 ? ', ' . $address->address_line_2 : '' }},
                                                        {{ $address->city }}, {{ $address->state }} {{ $address->postal_code }}
                                                        &middot; {{ $address->phone }}
                                                    </p>
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>

                                    <button type="button" @click="showAddressForm = !showAddressForm" class="inline-flex items-center gap-1 mt-2 text-[11px] font-medium text-link hover:text-link-hover">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        <span x-text="showAddressForm ? 'Cancel' : 'Add New Address'"></span>
                                    </button>
                                @else
                                    <div class="text-center py-4" x-show="!showAddressForm">
                                        <p class="text-xs text-[#3a3a3a] mb-2">No saved addresses found.</p>
                                        <button type="button" @click="showAddressForm = true" class="inline-flex items-center gap-1 text-xs font-semibold text-white bg-primary-600 hover:bg-primary-600/90 px-3 py-1.5 rounded transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                            Add Address
                                        </button>
                                    </div>
                                @endif

                                {{-- Inline Add Address Form with PIN autocomplete --}}
                                <div x-show="showAddressForm" x-collapse x-cloak class="mt-2 p-3 bg-[#F7F8FA] rounded border border-[#E3E6E6] space-y-2" x-data="pinLookup()">
                                    <div class="flex items-center justify-between gap-2">
                                        <h3 class="text-xs font-bold text-[#0F1111]">New Address</h3>
                                        <button type="button" @click="useGps()" :disabled="gpsLoading"
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-[11px] font-semibold text-primary-700 bg-primary-50 hover:bg-primary-100 border border-primary-200 rounded transition-colors disabled:opacity-60">
                                            <svg x-show="!gpsLoading" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                            <svg x-show="gpsLoading" x-cloak class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"></circle><path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" class="opacity-75"></path></svg>
                                            <span x-text="gpsLoading ? 'Detecting…' : 'Use Location'"></span>
                                        </button>
                                    </div>
                                    <p x-show="gpsError" x-text="gpsError" class="text-[10px] text-[#CC0C39]" x-cloak></p>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-xs font-semibold text-[#3a3a3a] mb-0.5">Full Name *</label>
                                            <input type="text" id="new_addr_name" value="{{ auth()->user()?->full_name ?? '' }}" class="w-full text-xs border border-[#E3E6E6] rounded px-2.5 py-2 focus:border-link focus:outline-none focus:ring-1 focus:ring-primary-600" placeholder="Full name">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold text-[#3a3a3a] mb-0.5">Phone *</label>
                                            <input type="tel" id="new_addr_phone" value="{{ auth()->user()?->phone ?? '' }}" class="w-full text-xs border border-[#E3E6E6] rounded px-2.5 py-2 focus:border-link focus:outline-none focus:ring-1 focus:ring-primary-600" placeholder="+91 98765 43210">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-[#3a3a3a] mb-0.5">Address *</label>
                                        <input type="text" id="new_addr_line1" class="w-full text-xs border border-[#E3E6E6] rounded px-2.5 py-2 focus:border-link focus:outline-none focus:ring-1 focus:ring-primary-600" placeholder="House no., Building, Street">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-[#3a3a3a] mb-0.5">Area / Landmark</label>
                                        <input type="text" id="new_addr_line2" class="w-full text-xs border border-[#E3E6E6] rounded px-2.5 py-2 focus:border-link focus:outline-none focus:ring-1 focus:ring-primary-600" placeholder="Optional">
                                    </div>
                                    <div class="grid grid-cols-3 gap-2">
                                        <div>
                                            <label class="block text-xs font-semibold text-[#3a3a3a] mb-0.5">PIN Code *</label>
                                            <input type="text" id="new_addr_pincode" x-model="pin" @input="fetchPinData()" maxlength="6"
                                                   class="w-full text-xs border border-[#E3E6E6] rounded px-2.5 py-2 focus:border-link focus:outline-none focus:ring-1 focus:ring-primary-600" placeholder="400001">
                                            <p x-show="pinError" x-text="pinError" class="text-[10px] text-[#CC0C39] mt-0.5" x-cloak></p>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold text-[#3a3a3a] mb-0.5">City *</label>
                                            <input type="text" id="new_addr_city" x-model="city"
                                                   class="w-full text-xs border border-[#E3E6E6] rounded px-2.5 py-2 focus:border-link focus:outline-none focus:ring-1 focus:ring-primary-600" placeholder="City">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold text-[#3a3a3a] mb-0.5">State *</label>
                                            <input type="text" id="new_addr_state" x-model="state"
                                                   class="w-full text-xs border border-[#E3E6E6] rounded px-2.5 py-2 focus:border-link focus:outline-none focus:ring-1 focus:ring-primary-600" placeholder="State">
                                        </div>
                                    </div>
                                    <div id="new_addr_error" class="hidden text-[10px] text-[#CC0C39]"></div>
                                    <div class="flex gap-2 pt-1">
                                        <button type="button" :disabled="savingAddress"
                                                @click="
                                                    let name = document.getElementById('new_addr_name').value.trim();
                                                    let phone = document.getElementById('new_addr_phone').value.trim();
                                                    let line1 = document.getElementById('new_addr_line1').value.trim();
                                                    let line2 = document.getElementById('new_addr_line2').value.trim();
                                                    let pincode = pin;
                                                    let errEl = document.getElementById('new_addr_error');
                                                    if (!name || !phone || !line1 || !city || !state || !pincode) {
                                                        errEl.textContent = 'Please fill all required fields.';
                                                        errEl.classList.remove('hidden');
                                                        return;
                                                    }
                                                    errEl.classList.add('hidden');
                                                    savingAddress = true;
                                                    fetch('{{ route('account.addresses.store') }}', {
                                                        method: 'POST',
                                                        headers: { 'Content-Type': 'application/json', 'X-XSRF-TOKEN': getCsrfToken(), 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || getCsrfToken(), 'Accept': 'application/json' },
                                                        body: JSON.stringify({ name, phone, address_line1: line1, address_line2: line2, city, state, postal_code: pincode, country: 'IN' })
                                                    }).then(r => r.json().then(d => ({ok: r.ok, data: d}))).then(({ok, data}) => {
                                                        savingAddress = false;
                                                        if (ok) { location.reload(); }
                                                        else {
                                                            let msg = data.message || Object.values(data.errors || {}).flat().join(', ') || 'Failed to save address';
                                                            errEl.textContent = msg;
                                                            errEl.classList.remove('hidden');
                                                        }
                                                    }).catch(() => { savingAddress = false; errEl.textContent = 'Something went wrong.'; errEl.classList.remove('hidden'); });
                                                "
                                                class="px-3 py-1.5 text-xs font-semibold text-white bg-primary-600 hover:bg-primary-600/90 rounded transition-colors disabled:opacity-50">
                                            <span x-show="!savingAddress">Save</span>
                                            <span x-show="savingAddress" class="inline-flex items-center gap-1">
                                                <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                                Saving...
                                            </span>
                                        </button>
                                        <button type="button" @click="showAddressForm = false" class="px-3 py-1.5 text-xs font-medium text-[#3a3a3a] border border-[#E3E6E6] rounded hover:bg-[#F7F8FA] transition-colors">Cancel</button>
                                    </div>
                                </div>

                                @error('shipping_address_id')
                                    <p class="mt-1 text-[10px] text-[#CC0C39]">{{ $message }}</p>
                                @enderror
                                @endif
                            </div>
                        </div>

                        {{-- ── Section 2: Billing (auth only, collapsed by default) ── --}}
                        @if(!$isGuest)
                        <div class="bg-white rounded border border-[#E3E6E6]">
                            <div class="px-3 py-2.5">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="same_billing_address" value="1" x-model="sameBilling"
                                           class="rounded border-[#E3E6E6] text-primary-600 focus:ring-primary-600 w-3.5 h-3.5">
                                    <span class="text-xs text-[#0F1111] font-medium">Billing same as shipping</span>
                                </label>

                                <div x-show="!sameBilling" x-collapse class="mt-2 pt-2 border-t border-[#E3E6E6]">
                                    @if($addresses->count())
                                        <div class="space-y-1.5">
                                            @foreach($addresses as $address)
                                                <label class="flex items-start gap-2 p-2 border border-[#E3E6E6] rounded cursor-pointer hover:border-link transition-colors">
                                                    <input type="radio" name="billing_address_id" value="{{ $address->id }}"
                                                           class="mt-0.5 text-primary-600 focus:ring-primary-600">
                                                    <div class="flex-1 min-w-0">
                                                        <span class="text-[11px] font-semibold text-[#0F1111]">{{ $address->name }}</span>
                                                        <p class="text-[10px] text-[#3a3a3a]">{{ $address->address_line_1 }}, {{ $address->city }}, {{ $address->state }}</p>
                                                    </div>
                                                </label>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif

                        {{-- ── Section 3: Payment Method (Razorpay + COD only) ── --}}
                        <div class="bg-white rounded border border-[#E3E6E6]">
                            <div class="flex items-center gap-2 px-3 py-2.5 border-b border-[#E3E6E6] bg-[#F7F8FA]">
                                <div class="w-5 h-5 rounded-full bg-primary-600 text-white text-[10px] font-bold flex items-center justify-center">{{ $isGuest ? '2' : '2' }}</div>
                                <h2 class="text-xs font-bold text-[#0F1111] uppercase tracking-wide">Payment</h2>
                            </div>
                            <div class="p-3 space-y-2">
                                {{-- Razorpay --}}
                                @if($razorpayAvailable)
                                <div @click="paymentMethod = 'razorpay'; $dispatch('payment-changed', {method: 'razorpay'})"
                                     :class="paymentMethod === 'razorpay' ? 'border-primary-600 bg-primary-600/5 ring-1 ring-primary-600/20' : 'border-[#E3E6E6] hover:border-link'"
                                     class="border rounded cursor-pointer transition-all">
                                    <div class="flex items-center gap-2.5 p-2.5">
                                        <input type="radio" name="payment_method" value="razorpay" x-model="paymentMethod"
                                               class="text-primary-600 focus:ring-primary-600">
                                        <div class="flex items-center gap-2 flex-1">
                                            {{-- Razorpay Logo --}}
                                            <img src="{{ asset('images/razorpay.png') }}" alt="Razorpay" class="h-5 w-auto shrink-0">
                                            <div>
                                                <p class="text-[10px] text-[#3a3a3a]">Cards, UPI, Net Banking & more</p>
                                                @php $prepaidDiscount = (float) $prepaidDiscountPct; @endphp
                                                @if($prepaidDiscount > 0)
                                                <p class="text-[10px] font-semibold text-green-600">Extra {{ intval($prepaidDiscount) }}% off on prepaid!</p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                {{-- Partial Pay (₹100 advance + rest COD) - only for orders >= ₹199 --}}
                                @if($codAvailable)
                                @php
                                    $codAdvanceAmt = (int) $codAdvanceAmt;
                                    $codMinAmt = (int) $codMinAmt;
                                @endphp
                                <div x-show="liveTotal >= {{ $codMinAmt }}" @click="paymentMethod = 'cod'; $dispatch('payment-changed', {method: 'cod'})"
                                     :class="paymentMethod === 'cod' ? 'border-primary-600 bg-primary-600/5 ring-1 ring-primary-600/20' : 'border-[#E3E6E6] hover:border-link'"
                                     class="border rounded cursor-pointer transition-all">
                                    <div class="flex items-center gap-2.5 p-2.5">
                                        <input type="radio" name="payment_method" value="cod" x-model="paymentMethod"
                                               class="text-primary-600 focus:ring-primary-600">
                                        <div class="flex items-center gap-2 flex-1">
                                            <div class="w-7 h-7 rounded flex items-center justify-center shrink-0"
                                                 :class="paymentMethod === 'cod' ? 'bg-primary-700/15 text-primary-700' : 'bg-[#F7F8FA] text-[#3a3a3a]'">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <span class="text-xs font-medium text-[#0F1111]">Partial Pay</span>
                                                @if($razorpayAvailable)
                                                <p class="text-[10px] text-[#3a3a3a]">Pay ₹{{ $codAdvanceAmt }} now, rest on delivery</p>
                                                @else
                                                <p class="text-[10px] text-[#3a3a3a]">Pay full amount on delivery</p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div x-show="paymentMethod === 'cod'" x-collapse>
                                        <div class="px-2.5 pb-2.5 pt-0">
                                            <div class="flex items-center gap-1.5 p-2 bg-primary-600/5 border border-primary-600/15 rounded text-[10px] text-[#0F1111]">
                                                <svg class="w-3.5 h-3.5 text-primary-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                                @if($razorpayAvailable)
                                                <span>Pay <strong class="text-primary-600">₹<span x-text="Math.min({{ $codAdvanceAmt }}, liveTotal).toFixed(0)"></span></strong> advance to confirm. <strong>₹<span x-text="Math.max(0, liveTotal - {{ $codAdvanceAmt }}).toFixed(0)"></span></strong> on delivery.</span>
                                                @else
                                                <span>Cash on Delivery — pay the full amount when your order arrives.</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                @error('payment_method')
                                    <p class="text-[10px] text-[#CC0C39]">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Navratri Offer Banner --}}
                        @if(isset($navratriActive) && $navratriActive)
                        <div class="rounded p-2.5 flex items-center gap-2" style="background: linear-gradient(135deg, #FFF3E0, #FFE0B2); border: 1px solid #FFB74D;">
                            <span class="text-lg">🎉</span>
                            <div>
                                <p class="text-[11px] font-bold" style="color: #E65100;">Navratri Special: Extra 5% Off Applied Automatically!</p>
                            </div>
                        </div>
                        @endif

                        {{-- Order Notes (compact) --}}
                        <div class="bg-white rounded border border-[#E3E6E6]">
                            <div class="p-3">
                                <label class="block text-[10px] font-semibold text-[#3a3a3a] mb-1">Order Notes (optional)</label>
                                <textarea name="notes" rows="2" class="w-full text-xs border border-[#E3E6E6] rounded px-2.5 py-2 focus:border-link focus:outline-none focus:ring-1 focus:ring-primary-600 resize-none"
                                          placeholder="Special delivery instructions...">{{ old('notes') }}</textarea>
                            </div>
                        </div>
                    </div>

                    {{-- ═══ RIGHT COLUMN - Order Summary ═══ --}}
                    <div id="checkout-right">
                        {{-- freeShipThreshold passed from controller --}}

                        <div class="bg-white rounded border border-[#E3E6E6] lg:sticky lg:top-20"
                             x-data="orderSummary()"
                             @payment-changed.window="isPrepaid = $event.detail.method === 'razorpay'">

                            {{-- Free Shipping Nudge (dynamic) --}}
                            <div x-cloak x-show="subtotal < freeShipThreshold" style="background:#FFF3E0;border:1px solid #FFB74D;border-radius:6px 6px 0 0;padding:10px 14px;">
                                <p style="font-size:12px;color:#E65100;font-weight:600;margin:0 0 6px;">Add ₹<span x-text="Math.max(0, freeShipThreshold - subtotal).toFixed(0)"></span> more for FREE shipping!</p>
                                <div style="background:#FFE0B2;border-radius:4px;height:6px;overflow:hidden;">
                                    <div style="background:var(--color-primary-700);height:100%;border-radius:4px;transition:width 0.3s" :style="'width:' + Math.min(100, (subtotal / freeShipThreshold) * 100) + '%'"></div>
                                </div>
                            </div>
                            <div x-cloak x-show="subtotal >= freeShipThreshold" style="background:#E8F5E9;border-radius:6px 6px 0 0;padding:8px 14px;">
                                <p style="font-size:12px;color:#2E7D32;font-weight:600;margin:0;">&#10003; You qualify for FREE shipping!</p>
                            </div>

                            {{-- Coupon Input / Applied Badge --}}
                            <div class="p-3 border-b border-[#E3E6E6]">
                                {{-- Input mode (no coupon applied) --}}
                                <div x-show="!appliedCode">
                                    <div class="flex gap-1.5">
                                        <input type="text" x-model="couponInput" placeholder="Discount code"
                                               class="flex-1 text-xs border border-[#E3E6E6] rounded px-2.5 py-2 placeholder-[#595959]"
                                               @keydown.enter.prevent="applyCouponCode(couponInput)">
                                        <button type="button" @click="applyCouponCode(couponInput)" :disabled="applying || !couponInput"
                                                class="px-3 py-2 text-[10px] font-semibold text-link border border-link rounded hover:bg-link hover:text-white transition-colors disabled:opacity-40">
                                            <span x-show="!applying">Apply</span>
                                            <span x-show="applying">...</span>
                                        </button>
                                    </div>
                                    <p x-show="couponError" x-text="couponError" class="text-[10px] text-[#CC0C39] mt-1" x-cloak></p>
                                </div>
                                {{-- Applied mode (coupon active — show code + remove + change) --}}
                                <div x-show="appliedCode" x-cloak>
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-3.5 h-3.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                            <span class="text-[10px] font-bold text-green-700 bg-green-100 px-1.5 py-0.5 rounded font-mono" x-text="appliedCode"></span>
                                            <span class="text-[10px] text-green-600" x-text="couponLabel"></span>
                                        </div>
                                        <button type="button" @click="removeCoupon()" class="text-[10px] font-medium text-[#CC0C39] hover:underline">Remove</button>
                                    </div>
                                    {{-- Change coupon --}}
                                    <div class="mt-2 flex gap-1.5">
                                        <input type="text" x-model="couponInput" placeholder="Change code"
                                               class="flex-1 text-xs border border-[#E3E6E6] rounded px-2.5 py-1.5 placeholder-[#595959]"
                                               @keydown.enter.prevent="applyCouponCode(couponInput)">
                                        <button type="button" @click="applyCouponCode(couponInput)" :disabled="applying || !couponInput"
                                                class="px-2.5 py-1.5 text-[10px] font-medium text-[#3a3a3a] border border-[#E3E6E6] rounded hover:bg-[#F7F8FA] transition-colors disabled:opacity-40">
                                            Change
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {{-- Coupons Carousel (horizontal scroll) --}}
                            @if($availableCoupons->count())
                            <div class="p-3 border-b border-[#E3E6E6]">
                                <div class="flex items-center gap-1.5 mb-2">
                                    <svg class="w-3.5 h-3.5 text-accent-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                    <span class="text-[10px] font-bold text-[#0F1111] uppercase tracking-wider">Coupons</span>
                                </div>
                                <div class="flex gap-2 overflow-x-auto pb-1 -mx-1 px-1 scrollbar-thin">
                                    @foreach($availableCoupons as $coupon)
                                        <div class="flex-shrink-0 w-36 sm:w-44 border border-dashed rounded p-2"
                                             x-show="subtotal >= {{ (float) ($coupon->min_order_amount ?? 0) }} || appliedCode === '{{ $coupon->code }}'"
                                             :class="appliedCode === '{{ $coupon->code }}' ? 'border-primary-600 bg-primary-600/5' : 'border-[#E3E6E6]'">
                                            <div class="flex items-center justify-between mb-1">
                                                <span class="text-[10px] font-bold text-primary-600 bg-primary-600/10 px-1.5 py-0.5 rounded font-mono">{{ $coupon->code }}</span>
                                                <span x-show="appliedCode === '{{ $coupon->code }}'" class="text-[9px] font-medium text-green-700 bg-green-50 px-1 py-px rounded">Applied</span>
                                            </div>
                                            <p class="text-[10px] text-[#3a3a3a] leading-snug mb-1.5 line-clamp-2">{{ $coupon->name }}</p>
                                            <button type="button" x-show="appliedCode !== '{{ $coupon->code }}'" :disabled="applying"
                                                    @click="applyCoupon('{{ $coupon->code }}')"
                                                    class="w-full text-[10px] font-semibold text-link hover:text-white border border-link hover:bg-link rounded py-1 transition-colors">
                                                <span x-show="!applying">Apply</span>
                                                <span x-show="applying">Applying...</span>
                                            </button>
                                            <button type="button" x-show="appliedCode === '{{ $coupon->code }}'"
                                                    @click="removeCoupon()"
                                                    class="w-full text-[10px] font-medium text-[#CC0C39] border border-[#CC0C39]/30 rounded py-1 hover:bg-[#CC0C39]/5 transition-colors inline-flex items-center justify-center gap-1">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                Remove
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif

                            {{-- Applied Coupon Badge --}}
                            <template x-if="appliedCode">
                                <div class="px-3 py-2 border-b border-[#E3E6E6] bg-green-50/50">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-[10px] font-bold text-green-700 bg-green-100 px-1.5 py-0.5 rounded font-mono" x-text="appliedCode"></span>
                                            <span class="text-[10px] text-green-600 font-medium" x-text="couponLabel"></span>
                                        </div>
                                        <span class="text-xs font-bold text-green-700" x-text="'-₹' + discount.toFixed(0)"></span>
                                    </div>
                                </div>
                            </template>

                            {{-- Order Items --}}
                            <div class="p-3 border-b border-[#E3E6E6]">
                                <h3 class="text-[10px] font-bold text-[#3a3a3a] uppercase tracking-wider mb-2">
                                    <span x-text="items.reduce((s,i) => s + i.qty, 0) + ' ' + (items.reduce((s,i) => s + i.qty, 0) === 1 ? 'Item' : 'Items')"></span>
                                </h3>
                                <div class="space-y-2 max-h-40 overflow-y-auto">
                                    <template x-for="(item, idx) in items" :key="item.id">
                                        <div class="flex gap-2">
                                            <img :src="item.img" :alt="item.name"
                                                 class="w-10 h-10 rounded border border-[#E3E6E6] bg-white object-contain shrink-0">
                                            <div class="flex-1 min-w-0">
                                                <p class="text-[11px] font-medium text-[#0F1111] line-clamp-1" x-text="item.name"></p>
                                                <div class="flex items-center justify-between">
                                                    <span class="text-[10px] text-[#3a3a3a] flex items-center gap-1">
                                                        <button type="button" class="w-5 h-5 rounded bg-gray-100 hover:bg-gray-200 text-xs flex items-center justify-center border"
                                                                @click="updateQty(item, item.qty - 1)" :disabled="item.qty <= 1">-</button>
                                                        <span class="font-semibold text-[#0F1111] px-1" x-text="item.qty"></span>
                                                        <button type="button" class="w-5 h-5 rounded bg-gray-100 hover:bg-gray-200 text-xs flex items-center justify-center border"
                                                                @click="updateQty(item, item.qty + 1)">+</button>
                                                        <button type="button" class="ml-1.5 text-[#CC0C39] hover:text-[#a00a2e] transition-colors"
                                                                @click="removeItem(item, idx)" title="Remove item">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                        </button>
                                                    </span>
                                                    <span class="text-[11px] font-semibold text-[#0F1111]" x-text="'₹' + (item.price * item.qty).toFixed(0)"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- Price Details --}}
                            <div class="p-3">
                                <div class="space-y-1.5">
                                    <div class="flex items-center justify-between text-[11px]">
                                        <span class="text-[#3a3a3a]">Subtotal</span>
                                        <span class="text-[#0F1111] font-medium" x-text="'₹' + subtotal.toFixed(2)"></span>
                                    </div>

                                    <div class="flex items-center justify-between text-[11px]" x-show="discount > 0" x-cloak>
                                        <span class="text-[#3a3a3a]">Coupon Discount</span>
                                        <span class="text-green-600 font-medium" x-text="'-₹' + discount.toFixed(0)"></span>
                                    </div>

                                    @if(isset($navratriActive) && $navratriActive)
                                        @php $navratriSaving = round(($cart->subtotal - $cart->discount) * 0.05, 2); @endphp
                                        <div class="flex items-center justify-between text-[11px]">
                                            <span class="text-[#CC0C39] font-semibold">Navratri 5% Extra Off</span>
                                            <span class="text-[#CC0C39] font-semibold">-@price($navratriSaving)</span>
                                        </div>
                                    @endif

                                    {{-- Prepaid discount (dynamic — shows only when Razorpay selected) --}}
                                    @if((float) $prepaidDiscountPct > 0)
                                    <div x-show="isPrepaid && prepaidSaving > 0" x-cloak class="flex items-center justify-between text-[11px]">
                                        <span class="text-green-700 font-semibold">Prepaid {{ intval($prepaidDiscountPct) }}% Off</span>
                                        <span class="text-green-700 font-semibold" x-text="'-₹' + prepaidSaving"></span>
                                    </div>
                                    @endif

                                    <div class="flex items-center justify-between text-[11px]">
                                        <span class="text-[#3a3a3a]">Shipping</span>
                                        <span x-cloak x-show="shipFee > 0" class="text-[#0F1111] font-medium" x-text="'₹' + shipFee"></span>
                                        <span x-cloak x-show="shipFee === 0" class="text-green-600 font-semibold">FREE</span>
                                    </div>
                                    <p x-cloak x-show="shipFee > 0" class="text-[9px] text-[#3a3a3a]">Free shipping on orders above ₹{{ $freeShipThreshold }}</p>

                                    {{-- Loyalty Points Redemption --}}
                                    @if(!empty($loyaltyPoints) && $loyaltyPoints > 0)
                                        <div class="flex items-center justify-between text-[11px] pt-1 border-t border-dashed border-[#E3E6E6]"
                                             x-data="{ usePoints: false, pointsToUse: {{ min($loyaltyPoints, (int) ceil(($cart->subtotal - $cart->discount) / 0.25)) }} }">
                                            <div class="flex items-center gap-1.5">
                                                <input type="checkbox" name="use_loyalty_points" value="1" x-model="usePoints"
                                                       class="w-3 h-3 rounded border-gray-300 text-amber-600 focus:ring-amber-500">
                                                <span class="text-amber-700 font-medium">Use {{ number_format($loyaltyPoints) }} points</span>
                                                <span class="text-[9px] text-[#3a3a3a]">(worth @price($loyaltyValue))</span>
                                            </div>
                                            <template x-if="usePoints">
                                                <span class="text-amber-600 font-semibold">-@price($loyaltyValue)</span>
                                            </template>
                                            <input type="hidden" name="loyalty_points_used" :value="usePoints ? {{ $loyaltyPoints }} : 0">
                                        </div>
                                    @endif

                                    @php $taxCalc = $taxCalculation; @endphp
                                    @if($cart->tax > 0 && $taxCalc === 'exclusive')
                                        <div class="flex items-center justify-between text-[11px]">
                                            <span class="text-[#3a3a3a]">GST</span>
                                            <span class="text-[#0F1111] font-medium">@price($cart->tax)</span>
                                        </div>
                                    @endif
                                </div>

                                <div class="border-t border-dashed border-[#E3E6E6] my-2"></div>

                                {{-- shipFee, displayTotal, codMinOrder pre-computed in controller --}}
                                @php $showCod = $displayTotal >= $codMinOrder; @endphp

                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-bold text-[#0F1111]">Total</span>
                                    <span class="text-sm font-bold text-[#CC0C39]" x-text="'₹' + displayTotal.toFixed(0)"></span>
                                </div>
                                <p class="text-[9px] text-[#3a3a3a] text-center mt-0.5">Inclusive of all taxes</p>

                                <p x-show="discount > 0" x-cloak class="text-[10px] font-medium text-green-700 text-center mt-1.5 bg-green-50 rounded py-1"
                                   x-text="'You save ₹' + discount.toFixed(0) + ' on this order'"></p>

                                @if(isset($navratriActive) && $navratriActive)
                                    <div class="mt-2 p-2 rounded-lg text-center bg-primary-600">
                                        <p class="text-[10px] font-bold text-white">Navratri Special - Extra {{ (int) 5 }}% Off Applied!</p>
                                    </div>
                                @endif
                            </div>

                            {{-- Place Order Button --}}
                            <div class="p-3 pt-0">
                                <button type="submit" :disabled="processing"
                                        class="block w-full py-2.5 bg-primary-600 hover:bg-primary-700 active:bg-primary-800 text-white text-xs font-bold text-center rounded transition-colors shadow-sm disabled:opacity-50 disabled:cursor-not-allowed uppercase tracking-wide">
                                    <span x-show="!processing">
                                        <template x-if="liveTotal <= 0">
                                            <span>Place Free Order</span>
                                        </template>
                                        <template x-if="liveTotal > 0 && paymentMethod === 'razorpay'">
                                            <span>Pay Now &middot; ₹<span x-text="displayTotal.toFixed(0)"></span></span>
                                        </template>
                                        <template x-if="liveTotal > 0 && paymentMethod === 'cod'">
                                            <span>Pay ₹{{ (int) $codAdvanceAmt }} & Place Order</span>
                                        </template>
                                    </span>
                                    <span x-show="processing" x-cloak class="flex items-center justify-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                        Processing...
                                    </span>
                                </button>
                                <p x-show="error" x-text="error" class="text-[10px] text-[#CC0C39] text-center mt-1.5" x-cloak></p>
                                @if(!$isGuest && $addresses->isEmpty())
                                    <p class="text-[10px] text-[#CC0C39] text-center mt-1.5">Please add an address to place your order.</p>
                                @endif
                            </div>

                            {{-- Trust Badges --}}
                            <div class="px-3 pb-3">
                                <div class="flex items-center justify-center gap-3 pt-2 border-t border-[#E3E6E6]">
                                    <div class="flex items-center gap-1 text-[#3a3a3a]">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                        <span class="text-[9px] font-medium">Secure</span>
                                    </div>
                                    <div class="flex items-center gap-1 text-[#3a3a3a]">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                        <span class="text-[9px] font-medium">Genuine</span>
                                    </div>
                                    <div class="flex items-center gap-1 text-[#3a3a3a]">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                        <span class="text-[9px] font-medium">Easy Returns</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Terms --}}
                            <div class="px-3 pb-2.5">
                                <p class="text-[9px] text-[#3a3a3a] text-center leading-relaxed">
                                    By placing your order, you agree to our
                                    <a href="{{ route('terms') }}" class="text-link hover:text-link-hover">Terms</a> &
                                    <a href="{{ route('privacy') }}" class="text-link hover:text-link-hover">Privacy Policy</a>.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            {{-- Upsell / Cross-sell Section --}}
            @if(isset($upsellProducts) && $upsellProducts->count())
            <div class="mt-4" x-data="upsellSection()">
                <div class="bg-white rounded border border-[#E3E6E6] p-3">
                    <div class="flex items-center gap-2 mb-3">
                        <svg class="w-4 h-4 text-accent-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        <h3 class="text-xs font-bold text-[#0F1111] uppercase tracking-wide">You Might Also Like</h3>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        @foreach($upsellProducts as $upsell)
                            <x-product-card :product="$upsell" :compact="true" />
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

    <x-slot name="scripts">
        @if($razorpayAvailable || $codAvailable)
        <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
        @endif
        {{-- Fresh CSRF token helper — reads from cookie instead of stale rendered value --}}
        <script>
            function getCsrfToken() {
                // Try cookie first, then meta tag fallback
                let match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
                if (match) return decodeURIComponent(match[1]);
                let meta = document.querySelector('meta[name="csrf-token"]');
                return meta ? meta.content : '{{ csrf_token() }}';
            }
        </script>
        {{-- Phone: simple +91 prefix for India-only store --}}
        <script>
            // PIN code autocomplete using India Post API + Delhivery serviceability
            function pinLookup() {
                return {
                    pin: '',
                    city: '',
                    state: '',
                    address: '',
                    pinError: '',
                    pinServiceable: null,
                    pinTimeout: null,
                    gpsLoading: false,
                    gpsError: '',

                    useGps() {
                        this.gpsError = '';
                        if (!('geolocation' in navigator)) {
                            this.gpsError = 'Your browser does not support location access.';
                            return;
                        }
                        this.gpsLoading = true;
                        navigator.geolocation.getCurrentPosition(
                            (pos) => {
                                const { latitude, longitude } = pos.coords;
                                fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${latitude}&lon=${longitude}&addressdetails=1&zoom=18`, {
                                    headers: { 'Accept': 'application/json' }
                                })
                                .then(r => r.json())
                                .then(data => {
                                    this.gpsLoading = false;
                                    if (!data || !data.address) {
                                        this.gpsError = 'Could not detect your address. Please enter manually.';
                                        return;
                                    }
                                    const a = data.address;
                                    // PIN
                                    if (a.postcode) {
                                        this.pin = a.postcode.replace(/\s/g, '').slice(0, 6);
                                    }
                                    // City — try multiple OSM keys
                                    this.city = a.city || a.town || a.village || a.suburb || a.county || '';
                                    // State
                                    this.state = a.state || '';
                                    // Address line: build from house_number, road, neighbourhood, suburb
                                    const parts = [a.house_number, a.road, a.neighbourhood, a.suburb].filter(Boolean);
                                    this.address = parts.join(', ') || data.display_name?.split(',').slice(0, 2).join(',') || '';
                                    // Sync to plain inputs (auth-checkout new-address form uses IDs, not x-model)
                                    const line1El = document.getElementById('new_addr_line1');
                                    if (line1El && !line1El.value) line1El.value = this.address;
                                    // Trigger PIN serviceability check
                                    if (this.pin.length === 6) this.fetchPinData();
                                })
                                .catch(() => {
                                    this.gpsLoading = false;
                                    this.gpsError = 'Could not detect your address. Please enter manually.';
                                });
                            },
                            (err) => {
                                this.gpsLoading = false;
                                if (err.code === 1) this.gpsError = 'Location permission denied. Please allow access in your browser.';
                                else if (err.code === 2) this.gpsError = 'Location unavailable. Please try again.';
                                else if (err.code === 3) this.gpsError = 'Location request timed out. Please try again.';
                                else this.gpsError = 'Could not get your location.';
                            },
                            { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
                        );
                    },

                    fetchPinData() {
                        this.pinError = '';
                        this.pinServiceable = null;
                        clearTimeout(this.pinTimeout);
                        if (this.pin.length !== 6) return;

                        this.pinTimeout = setTimeout(() => {
                            // India Post API for city/state autofill
                            fetch('https://api.postalpincode.in/pincode/' + this.pin)
                                .then(r => r.json())
                                .then(data => {
                                    if (data[0] && data[0].Status === 'Success' && data[0].PostOffice && data[0].PostOffice.length) {
                                        const po = data[0].PostOffice[0];
                                        this.city = po.District || po.Division || '';
                                        this.state = po.State || '';
                                    } else {
                                        this.pinError = 'Invalid PIN code';
                                    }
                                })
                                .catch(() => {});

                            // Delhivery serviceability check
                            fetch('/api/check-pincode/' + this.pin)
                                .then(r => r.json())
                                .then(data => {
                                    this.pinServiceable = data.serviceable === true;
                                    if (!data.serviceable) {
                                        this.pinError = 'Delivery not available to this pincode';
                                    }
                                })
                                .catch(() => {});
                        }, 300);
                    }
                };
            }

            // Capture guest contact info for abandoned checkout recovery
            // Fires on both @input (debounced 2s) and @blur (immediate) for early capture
            let abandonedCaptureTimeout = null;
            let lastCapturedData = '';
            function captureAbandoned(immediate) {
                clearTimeout(abandonedCaptureTimeout);
                const delay = immediate ? 0 : 2000;
                abandonedCaptureTimeout = setTimeout(() => {
                    const phone = (document.querySelector('[name="guest_phone"]')?.value || '').replace(/\D/g, '');
                    const email = document.querySelector('[name="guest_email"]')?.value || '';
                    const name = document.querySelector('[name="guest_name"]')?.value || '';

                    // Need at least a phone (10+ digits) or email to capture
                    if (phone.length < 10 && !email.includes('@')) return;

                    // Don't re-send identical data
                    const dataKey = phone + '|' + email + '|' + name;
                    if (dataKey === lastCapturedData) return;
                    lastCapturedData = dataKey;

                    fetch('{{ route("checkout.abandoned.capture") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-XSRF-TOKEN': getCsrfToken(),
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || getCsrfToken(),
                        },
                        body: JSON.stringify({ phone, email, name }),
                    }).catch(() => {});
                }, delay);
            }

            function upsellSection() {
                return {
                    adding: null,
                    addToCart(productId, el) {
                        this.adding = productId;
                        fetch('{{ route("cart.add") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': getCsrfToken(),
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({ product_id: productId, quantity: 1 }),
                        })
                        .then(r => r.json())
                        .then(data => {
                            this.adding = null;
                            if (data.success) {
                                // Refresh the page to update order summary with new cart state
                                window.location.reload();
                            } else {
                                alert(data.error || 'Could not add to cart');
                            }
                        })
                        .catch(() => {
                            this.adding = null;
                            alert('Something went wrong. Please try again.');
                        });
                    }
                };
            }

            function checkoutForm(firstMethod) {
                return {
                    sameBilling: true,
                    paymentMethod: firstMethod,
                    showAddressForm: false,
                    savingAddress: false,
                    processing: false,
                    error: '',
                    liveTotal: {{ (float) ($displayTotal ?? $cart->total) }},
                    liveSubtotal: {{ (float) $cart->subtotal }},

                    handleCartUpdate(e) {
                        this.liveTotal = e.detail.total || this.liveTotal;
                        this.liveSubtotal = e.detail.subtotal || this.liveSubtotal;
                        // If total drops below 199, switch off COD
                        if (this.liveTotal < {{ $codMinAmt ?? 199 }} && this.paymentMethod === 'cod') {
                            this.paymentMethod = 'razorpay';
                        }
                    },

                    async handleSubmit(e) {
                        this.error = '';

                        // Refresh CSRF token before submission to prevent 419 mismatch
                        try {
                            const res = await fetch('/csrf-token', { credentials: 'same-origin' });
                            const data = await res.json();
                            if (data.token) {
                                document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', data.token);
                                const tokenInput = e.target.querySelector('input[name="_token"]');
                                if (tokenInput) tokenInput.value = data.token;
                            }
                        } catch (err) {}

                        // Free order (100% discount): bypass payment gateway entirely.
                        // The server-side process() handler detects ₹0 totals and skips payment.
                        if (this.liveTotal <= 0) {
                            this.processing = true;
                            // Force payment_method to 'free' so the controller routes correctly
                            const hidden = document.createElement('input');
                            hidden.type = 'hidden';
                            hidden.name = 'payment_method';
                            hidden.value = 'free';
                            // Remove any existing payment_method radios from this submission
                            e.target.querySelectorAll('input[name="payment_method"]').forEach(el => el.disabled = true);
                            e.target.appendChild(hidden);
                            e.target.submit();
                            return;
                        }

                        @if(!$razorpayAvailable)
                            // No Razorpay — submit COD directly via form POST
                            this.processing = true;
                            e.target.submit();
                            return;
                        @endif
                        // Razorpay available: both methods go through Razorpay
                        // COD = partial pay (₹100 advance via Razorpay, rest on delivery)
                        // Razorpay = full payment
                        this.initiateRazorpay(e.target);
                    },

                    async initiateRazorpay(form) {
                        this.processing = true;
                        const formData = new FormData(form);
                        const data = Object.fromEntries(formData.entries());

                        try {
                            const csrfToken = getCsrfToken();
                            const response = await fetch('{{ route("checkout.razorpay.create") }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-XSRF-TOKEN': csrfToken,
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || csrfToken,
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify(data),
                            });

                            const result = await response.json();

                            if (!response.ok) {
                                this.error = result.error || result.message || 'Something went wrong. Please try again.';
                                this.processing = false;
                                return;
                            }

                            // Free order: server says no payment needed → submit form to process()
                            if (result.free_order) {
                                const hidden = document.createElement('input');
                                hidden.type = 'hidden';
                                hidden.name = 'payment_method';
                                hidden.value = 'free';
                                form.querySelectorAll('input[name="payment_method"]').forEach(el => el.disabled = true);
                                form.appendChild(hidden);
                                form.submit();
                                return;
                            }

                            this.openRazorpayCheckout(result);
                        } catch (err) {
                            this.error = 'Network error. Please check your connection and try again.';
                            this.processing = false;
                        }
                    },

                    openRazorpayCheckout(orderData) {
                        const self = this;

                        const options = {
                            key: orderData.key,
                            amount: orderData.amount,
                            currency: orderData.currency,
                            name: orderData.name,
                            description: orderData.description,
                            order_id: orderData.order_id,
                            image: '{{ $jsConfig['theme']['storeLogo'] ?? asset('images/logo.png') }}',
                            prefill: orderData.prefill,
                            theme: {
                                color: '{{ $jsConfig['theme']['primaryColor'] ?? '#334155' }}',
                                backdrop_color: 'rgba(0, 0, 0, 0.5)',
                            },
                            modal: {
                                ondismiss: function() {
                                    self.processing = false;
                                    self.error = 'Payment was cancelled. You can try again.';
                                },
                                confirm_close: true,
                                escape: false,
                            },
                            handler: function(response) {
                                self.verifyPayment(response);
                            },
                        };

                        const rzp = new Razorpay(options);
                        rzp.on('payment.failed', function(response) {
                            self.processing = false;
                            self.error = response.error.description || 'Payment failed. Please try again.';
                        });
                        rzp.open();
                    },

                    async verifyPayment(paymentResponse) {
                        try {
                            const csrfTkn = getCsrfToken();
                            const response = await fetch('{{ route("checkout.razorpay.verify") }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-XSRF-TOKEN': csrfTkn,
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || csrfTkn,
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify({
                                    razorpay_order_id: paymentResponse.razorpay_order_id,
                                    razorpay_payment_id: paymentResponse.razorpay_payment_id,
                                    razorpay_signature: paymentResponse.razorpay_signature,
                                }),
                            });

                            const result = await response.json();

                            if (result.success && result.redirect) {
                                window.location.href = result.redirect;
                            } else {
                                this.error = result.error || 'Payment verification failed. Please contact support.';
                                this.processing = false;
                            }
                        } catch (err) {
                            this.error = 'Verification failed. If amount was deducted, it will be refunded. Please contact support.';
                            this.processing = false;
                        }
                    },
                };
            }
        </script>

        {{-- GA4 begin_checkout --}}
        @if(config('services.ga4.measurement_id'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                @php
                    $ga4CheckoutItems = $cart->items->map(function ($item) {
                        return [
                            'item_id' => $item->product->sku ?? (string) $item->product_id,
                            'item_name' => $item->product->name,
                            'price' => (float) $item->price,
                            'quantity' => $item->quantity,
                        ];
                    });
                @endphp
                var checkoutItems = {!! json_encode($ga4CheckoutItems, JSON_UNESCAPED_UNICODE) !!};
                gtag('event', 'begin_checkout', {
                    currency: 'INR',
                    value: {{ (float) $cart->total }},
                    items: checkoutItems
                });
            });
        </script>
        @endif

        <script>
            function orderSummary() {
                return {
                    applying: false,
                    couponInput: '',
                    couponError: '',
                    appliedCode: '{{ $cart->coupon->code ?? '' }}',
                    couponLabel: '{{ $cart->coupon ? ($cart->coupon->type === "percentage" ? intval($cart->coupon->value) . "% off" : ($cart->coupon->type === "fixed" ? "₹" . intval($cart->coupon->value) . " off" : "Applied")) : "" }}',
                    discount: {{ (float) $cart->discount }},
                    subtotal: {{ (float) $cart->subtotal }},
                    cartTotal: {{ (float) $cart->total }},
                    shipFee: {{ $shipFee ?? 0 }},
                    items: {!! json_encode($cart->items->map(fn($item) => [
                        'id' => $item->id,
                        'name' => $item->product->name,
                        'img' => $item->product->primary_image_url,
                        'price' => (float) $item->price,
                        'qty' => $item->quantity,
                    ])->values()) !!},
                    get csrfToken() { return getCsrfToken(); },
                    prepaidPct: {{ (float) $prepaidDiscountPct }},
                    isPrepaid: true,
                    get prepaidSaving() {
                        return (this.prepaidPct > 0 && this.isPrepaid) ? Math.round((this.subtotal - this.discount) * this.prepaidPct / 100) : 0;
                    },
                    get displayTotal() {
                        return Math.max(1, this.cartTotal + this.shipFee - this.prepaidSaving);
                    },
                    _headers() {
                        let token = this.csrfToken;
                        return { 'Content-Type': 'application/json', 'X-XSRF-TOKEN': token, 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || token, 'Accept': 'application/json' };
                    },
                    freeShipThreshold: {{ (float) ($freeShipThreshold ?? 499) }},
                    flatShippingRate: {{ (float) ($flatShipRate ?? 50) }},
                    _updateFromResponse(d) {
                        this.subtotal = d.cart_subtotal ?? this.subtotal;
                        this.discount = d.cart_discount ?? this.discount;
                        this.cartTotal = d.cart_total ?? (this.subtotal - this.discount);
                        // Recalculate shipping — free if above threshold OR coupon covers full subtotal
                        let afterDiscount = this.subtotal - this.discount;
                        this.shipFee = (afterDiscount <= 0 || afterDiscount >= this.freeShipThreshold) ? 0 : this.flatShippingRate;
                        if (d.coupon_removed) {
                            this.appliedCode = '';
                            this.couponLabel = '';
                            this.discount = 0;
                        }
                        if (d.coupon) {
                            this.appliedCode = d.coupon.code;
                            if (d.coupon.type === 'percentage') this.couponLabel = Math.round(d.coupon.value) + '% off';
                            else if (d.coupon.type === 'fixed') this.couponLabel = '₹' + Math.round(d.coupon.value) + ' off';
                            else this.couponLabel = 'Applied';
                        }
                        // Notify checkoutForm of total change
                        this.$dispatch('cart-totals-updated', {
                            subtotal: this.subtotal,
                            discount: this.discount,
                            total: this.displayTotal,
                            cartTotal: this.cartTotal,
                            shipFee: this.shipFee,
                            itemCount: this.items.reduce((s, i) => s + i.qty, 0)
                        });
                    },
                    updateQty(item, newQty) {
                        if (newQty < 1) return;
                        let oldQty = item.qty;
                        item.qty = newQty;
                        fetch('/cart/' + item.id, {
                            method: 'PUT',
                            headers: this._headers(),
                            body: JSON.stringify({ quantity: newQty })
                        }).then(r => {
                            if (r.ok) return r.json();
                            return r.json().catch(() => ({ error: 'Something went wrong' })).then(d => { throw new Error(d.error || 'Failed to update'); });
                        }).then(d => {
                            this._updateFromResponse(d);
                        }).catch(e => {
                            item.qty = oldQty;
                            this.couponError = e.message;
                            setTimeout(() => this.couponError = '', 4000);
                        });
                    },
                    removeItem(item, idx) {
                        if (!confirm('Remove ' + item.name + '?')) return;
                        let removed = this.items.splice(idx, 1)[0];
                        fetch('/cart/' + item.id, {
                            method: 'DELETE',
                            headers: this._headers()
                        }).then(r => {
                            if (r.ok) return r.json();
                            return r.json().catch(() => ({ error: 'Failed to remove' })).then(d => { throw new Error(d.error); });
                        }).then(d => {
                            this._updateFromResponse(d);
                            if (this.items.length === 0) window.location.href = '/cart';
                        }).catch(e => {
                            this.items.splice(idx, 0, removed);
                            this.couponError = e.message;
                            setTimeout(() => this.couponError = '', 4000);
                        });
                    },
                    applyCouponCode(code) {
                        if (!code || !code.trim()) return;
                        this.couponError = '';
                        this.applying = true;
                        fetch('{{ route("cart.apply-coupon") }}', {
                            method: 'POST',
                            headers: this._headers(),
                            body: JSON.stringify({ code: code.trim() })
                        }).then(r => {
                            if (r.ok) return r.json();
                            return r.json().then(d => { throw new Error(d.error || 'Invalid coupon code'); });
                        }).then(d => {
                            this._updateFromResponse(d);
                            this.applying = false;
                            this.couponInput = '';
                            this.couponError = '';
                        }).catch(e => {
                            this.couponError = e.message;
                            this.applying = false;
                        });
                    },
                    applyCoupon(code) {
                        this.applyCouponCode(code);
                    },
                    removeCoupon() {
                        fetch('{{ route("cart.remove-coupon") }}', {
                            method: 'DELETE',
                            headers: this._headers()
                        }).then(r => r.json()).then(d => {
                            this.appliedCode = '';
                            this.couponLabel = '';
                            this.discount = 0;
                            this._updateFromResponse(d);
                        });
                    }
                };
            }
        </script>
    </x-slot>
</x-layouts.app>
