<x-layouts.admin>
    <x-slot name="title">Payment Settings</x-slot>

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-neutral-900">Settings</h1>
        <p class="text-neutral-600">Manage your store configuration</p>
    </div>

    @include('admin.settings.partials.nav', ['active' => 'payment'])

    @if(session('success'))
        <div class="mb-6 flex items-center gap-3 px-4 py-3 bg-success-50 border border-success-200 rounded-xl text-sm text-success-700">
            <svg class="w-5 h-5 text-success-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('admin.settings.payment.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="space-y-4">

            {{-- Razorpay (handles Cards + UPI + Net Banking + Wallets) --}}
            <div class="card" x-data="{ enabled: {{ ($settings['razorpay_enabled'] ?? '0') === '1' ? 'true' : 'false' }} }">
                <div class="px-5 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0" style="background:#072654;">
                            <span class="text-white font-bold text-sm tracking-tight">R₹</span>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-neutral-900 flex items-center gap-2">
                                Razorpay Payment Gateway
                                @if(!empty($settings['razorpay_key_id']))
                                    <span class="text-[10px] font-medium text-success-600 bg-success-50 px-1.5 py-0.5 rounded">Connected</span>
                                @endif
                            </h3>
                            <p class="text-xs text-neutral-600">Cards, UPI (Google Pay, PhonePe), Net Banking, Wallets</p>
                        </div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="razorpay_enabled" value="1" x-model="enabled" class="sr-only peer">
                        <div class="w-11 h-6 bg-neutral-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-100 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                    </label>
                </div>
                <div class="px-5 pb-5 space-y-4 border-t border-neutral-100" x-show="enabled" x-collapse>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4">
                        <div>
                            <label class="form-label">Key ID *</label>
                            <input type="text" name="razorpay_key_id" value="{{ old('razorpay_key_id', $settings['razorpay_key_id'] ?? '') }}" placeholder="rzp_live_..." class="form-input">
                        </div>
                        <div>
                            <label class="form-label">Key Secret *</label>
                            <input type="password" name="razorpay_key_secret" value="" placeholder="{{ !empty($settings['razorpay_key_secret']) ? '••••••••••••' : 'Enter key secret' }}" class="form-input">
                            @if(!empty($settings['razorpay_key_secret']))
                                <p class="text-xs text-neutral-500 mt-1">Secret is saved. Leave blank to keep current.</p>
                            @endif
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Webhook Secret</label>
                            <input type="password" name="razorpay_webhook_secret" value="" placeholder="{{ !empty($settings['razorpay_webhook_secret']) ? '••••••••••••' : 'Enter webhook secret' }}" class="form-input">
                            @if(!empty($settings['razorpay_webhook_secret']))
                                <p class="text-xs text-neutral-500 mt-1">Saved. Leave blank to keep current.</p>
                            @endif
                        </div>
                        <div>
                            <label class="form-label">Mode</label>
                            <select name="razorpay_mode" class="form-select">
                                <option value="test" @selected(($settings['razorpay_mode'] ?? 'test') === 'test')>Test / Sandbox</option>
                                <option value="live" @selected(($settings['razorpay_mode'] ?? '') === 'live')>Live / Production</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Payment Config ID</label>
                            <input type="text" name="razorpay_config_id" value="{{ $settings['razorpay_config_id'] ?? '' }}" placeholder="config_XXXXXXXXXXXX" class="form-input">
                            <p class="text-xs text-neutral-500 mt-1">From Razorpay Dashboard > Payment Configuration. Controls which payment methods show.</p>
                        </div>
                    </div>

                    {{-- Payment methods info --}}
                    <div class="bg-neutral-50 rounded-lg p-3">
                        <p class="text-xs font-semibold text-neutral-700 mb-2">Payment methods included:</p>
                        <div class="flex flex-wrap gap-2">
                            <span class="inline-flex items-center gap-1 text-[10px] font-medium bg-white border border-neutral-200 rounded-full px-2 py-1">
                                <svg class="w-3 h-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                Cards (Visa, MC, RuPay)
                            </span>
                            <span class="inline-flex items-center gap-1 text-[10px] font-medium bg-white border border-neutral-200 rounded-full px-2 py-1">
                                <svg class="w-3 h-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                UPI (GPay, PhonePe, Paytm)
                            </span>
                            <span class="inline-flex items-center gap-1 text-[10px] font-medium bg-white border border-neutral-200 rounded-full px-2 py-1">
                                <svg class="w-3 h-3 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                Net Banking
                            </span>
                            <span class="inline-flex items-center gap-1 text-[10px] font-medium bg-white border border-neutral-200 rounded-full px-2 py-1">
                                <svg class="w-3 h-3 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                Wallets
                            </span>
                        </div>
                        <p class="text-[10px] text-neutral-500 mt-2">Enable/disable specific methods in your <a href="https://dashboard.razorpay.com/app/payment-methods" target="_blank" class="text-link hover:underline">Razorpay Dashboard → Payment Methods</a></p>
                    </div>

                    <p class="text-xs text-neutral-500">Get API keys from <a href="https://dashboard.razorpay.com/app/keys" target="_blank" class="text-link hover:underline">Razorpay Dashboard → Settings → API Keys</a></p>
                </div>
            </div>

            {{-- Cashfree --}}
            <div class="card" x-data="{ enabled: {{ ($settings['cashfree_enabled'] ?? '0') === '1' ? 'true' : 'false' }} }">
                <div class="px-5 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0" style="background:#6933FF;">
                            <span class="text-white font-bold text-sm tracking-tight">CF</span>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-neutral-900 flex items-center gap-2">
                                Cashfree Payment Gateway
                                @if(!empty($settings['cashfree_app_id']))
                                    <span class="text-[10px] font-medium text-success-600 bg-success-50 px-1.5 py-0.5 rounded">Connected</span>
                                @endif
                            </h3>
                            <p class="text-xs text-neutral-600">Cards, UPI, Net Banking, Wallets via Cashfree</p>
                        </div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="cashfree_enabled" value="1" x-model="enabled" class="sr-only peer">
                        <div class="w-11 h-6 bg-neutral-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-100 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                    </label>
                </div>
                <div class="px-5 pb-5 space-y-4 border-t border-neutral-100" x-show="enabled" x-collapse>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4">
                        <div>
                            <label class="form-label">App ID *</label>
                            <input type="text" name="cashfree_app_id" value="{{ old('cashfree_app_id', $settings['cashfree_app_id'] ?? '') }}" placeholder="App ID from Cashfree dashboard" class="form-input">
                        </div>
                        <div>
                            <label class="form-label">Secret Key *</label>
                            <input type="password" name="cashfree_secret_key" value="" placeholder="{{ !empty($settings['cashfree_secret_key']) ? '••••••••••••' : 'Enter secret key' }}" class="form-input">
                            @if(!empty($settings['cashfree_secret_key']))
                                <p class="text-xs text-neutral-500 mt-1">Secret is saved. Leave blank to keep current.</p>
                            @endif
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Webhook Secret</label>
                            <input type="password" name="cashfree_webhook_secret" value="" placeholder="{{ !empty($settings['cashfree_webhook_secret']) ? '••••••••••••' : 'Enter webhook secret' }}" class="form-input">
                            @if(!empty($settings['cashfree_webhook_secret']))
                                <p class="text-xs text-neutral-500 mt-1">Saved. Leave blank to keep current.</p>
                            @endif
                        </div>
                        <div>
                            <label class="form-label">Mode</label>
                            <select name="cashfree_mode" class="form-select">
                                <option value="sandbox" @selected(($settings['cashfree_mode'] ?? 'sandbox') === 'sandbox')>Sandbox / Test</option>
                                <option value="production" @selected(($settings['cashfree_mode'] ?? '') === 'production')>Production / Live</option>
                            </select>
                        </div>
                    </div>
                    <p class="text-xs text-neutral-500">Get API keys from <a href="https://merchant.cashfree.com/merchants/pg/developers/api-keys" target="_blank" class="text-link hover:underline">Cashfree Dashboard → Developers → API Keys</a>. Add webhook URL <code class="text-[10px] bg-neutral-100 px-1 py-0.5 rounded">{{ url('/checkout/cashfree/webhook') }}</code> in Cashfree dashboard.</p>
                </div>
            </div>

            {{-- COD --}}
            <div class="card" x-data="{ enabled: {{ ($settings['cod_enabled'] ?? '0') === '1' ? 'true' : 'false' }} }">
                <div class="px-5 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-success-50 rounded-lg flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-neutral-900">Cash on Delivery (COD)</p>
                            <p class="text-xs text-neutral-600">Customer pays when order is delivered</p>
                        </div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="cod_enabled" value="1" x-model="enabled" class="sr-only peer">
                        <div class="w-11 h-6 bg-neutral-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-100 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                    </label>
                </div>
                <div class="px-5 pb-4 border-t border-neutral-100 space-y-4" x-show="enabled" x-collapse>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4">
                        <div>
                            <label class="form-label">COD Advance Amount (&#8377;)</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-neutral-600 text-sm">&#8377;</span>
                                <input type="number" name="cod_advance_amount" value="{{ old('cod_advance_amount', $settings['cod_advance_amount'] ?? '100') }}" min="0" step="1" class="form-input pl-7">
                            </div>
                            <p class="text-xs text-neutral-500 mt-1">Minimum advance payment required for COD orders. Set 0 to disable.</p>
                        </div>
                        <div>
                            <label class="form-label">Minimum Order for COD (&#8377;)</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-neutral-600 text-sm">&#8377;</span>
                                <input type="number" name="cod_minimum_amount" value="{{ old('cod_minimum_amount', $settings['cod_minimum_amount'] ?? '199') }}" min="0" step="1" class="form-input pl-7">
                            </div>
                            <p class="text-xs text-neutral-500 mt-1">COD not available for orders below this amount.</p>
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Instructions for Customer <span class="text-neutral-500 font-normal">(optional)</span></label>
                        <textarea name="cod_instructions" rows="2" class="form-textarea" placeholder="e.g. Please keep exact change ready at delivery.">{{ old('cod_instructions', $settings['cod_instructions'] ?? '') }}</textarea>
                    </div>
                </div>
            </div>

        </div>

        <div class="mt-6 flex justify-end">
            <button type="submit" class="btn btn-primary"
                    x-data="{ loading: false }"
                    @click="loading = true; setTimeout(() => loading = false, 5000)"
                    :disabled="loading">
                <span x-show="!loading">Save Payment Settings</span>
                <span x-show="loading" x-cloak class="flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    Saving...
                </span>
            </button>
        </div>
    </form>
</x-layouts.admin>
