<x-layouts.admin>
    <x-slot name="title">Shipping Settings</x-slot>

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-neutral-900">Settings</h1>
        <p class="text-neutral-600">Manage your store configuration</p>
    </div>

    <!-- Settings Navigation -->
    @include('admin.settings.partials.nav', ['active' => 'shipping'])

    <form action="{{ route('admin.settings.shipping.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Shipping Methods -->
            <div class="space-y-6">
                <!-- Free Shipping -->
                <div class="card" x-data="{ enabled: {{ ($settings['free_shipping_enabled'] ?? false) ? 'true' : 'false' }} }">
                    <div class="px-5 py-3.5 border-b border-neutral-200 flex items-center justify-between">
                        <div>
                            <h2 class="text-sm font-semibold text-neutral-900">Free Shipping</h2>
                            <p class="text-xs text-neutral-600">Offer free shipping above a threshold</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="free_shipping_enabled" value="1" x-model="enabled" class="sr-only peer">
                            <div class="w-11 h-6 bg-neutral-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-100 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                        </label>
                    </div>
                    <div class="p-5" x-show="enabled" x-collapse>
                        <label class="form-label">Minimum Order Amount</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-neutral-600 text-sm">&#8377;</span>
                            <input type="number" name="free_shipping_threshold" value="{{ old('free_shipping_threshold', $settings['free_shipping_threshold'] ?? '') }}" step="0.01" min="0" class="form-input pl-7">
                        </div>
                    </div>
                </div>

                <!-- Flat Rate -->
                <div class="card" x-data="{ enabled: {{ ($settings['flat_rate_enabled'] ?? true) ? 'true' : 'false' }} }">
                    <div class="px-5 py-3.5 border-b border-neutral-200 flex items-center justify-between">
                        <div>
                            <h2 class="text-sm font-semibold text-neutral-900">Flat Rate Shipping</h2>
                            <p class="text-xs text-neutral-600">Charge a fixed shipping fee</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="flat_rate_enabled" value="1" x-model="enabled" class="sr-only peer">
                            <div class="w-11 h-6 bg-neutral-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-100 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                        </label>
                    </div>
                    <div class="p-5" x-show="enabled" x-collapse>
                        <label class="form-label">Shipping Fee</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-neutral-600 text-sm">&#8377;</span>
                            <input type="number" name="flat_rate_amount" value="{{ old('flat_rate_amount', $settings['flat_rate_amount'] ?? '5.99') }}" step="0.01" min="0" class="form-input pl-7">
                        </div>
                    </div>
                </div>

                <!-- Local Pickup -->
                <div class="card" x-data="{ enabled: {{ ($settings['local_pickup_enabled'] ?? false) ? 'true' : 'false' }} }">
                    <div class="px-5 py-3.5 border-b border-neutral-200 flex items-center justify-between">
                        <div>
                            <h2 class="text-sm font-semibold text-neutral-900">Local Pickup</h2>
                            <p class="text-xs text-neutral-600">Allow in-store pickup</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="local_pickup_enabled" value="1" x-model="enabled" class="sr-only peer">
                            <div class="w-11 h-6 bg-neutral-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-100 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                        </label>
                    </div>
                    <div class="p-5" x-show="enabled" x-collapse>
                        <label class="form-label">Pickup Address</label>
                        <textarea name="local_pickup_address" rows="3" class="form-textarea" placeholder="Enter your store address...">{{ old('local_pickup_address', $settings['local_pickup_address'] ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Shipping Origin -->
            <div class="card h-fit">
                <div class="px-5 py-3.5 border-b border-neutral-200">
                    <h2 class="text-sm font-semibold text-neutral-900">Shipping Origin</h2>
                    <p class="text-xs text-neutral-600">Where packages are shipped from</p>
                </div>
                <div class="p-5 space-y-4">
                    <div>
                        <label class="form-label form-label-required">Country</label>
                        <select name="shipping_origin_country" required class="form-select">
                            <option value="US" @selected(($settings['shipping_origin_country'] ?? 'US') === 'US')>United States</option>
                            <option value="CA" @selected(($settings['shipping_origin_country'] ?? '') === 'CA')>Canada</option>
                            <option value="GB" @selected(($settings['shipping_origin_country'] ?? '') === 'GB')>United Kingdom</option>
                            <option value="AU" @selected(($settings['shipping_origin_country'] ?? '') === 'AU')>Australia</option>
                            <option value="DE" @selected(($settings['shipping_origin_country'] ?? '') === 'DE')>Germany</option>
                            <option value="FR" @selected(($settings['shipping_origin_country'] ?? '') === 'FR')>France</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">State/Province</label>
                        <input type="text" name="shipping_origin_state" value="{{ old('shipping_origin_state', $settings['shipping_origin_state'] ?? '') }}" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">ZIP/Postal Code</label>
                        <input type="text" name="shipping_origin_zip" value="{{ old('shipping_origin_zip', $settings['shipping_origin_zip'] ?? '') }}" class="form-input">
                    </div>
                </div>
            </div>
        </div>

        <!-- Default Package Dimensions -->
        <div class="card mt-6">
            <div class="px-5 py-3.5 border-b border-neutral-200">
                <h2 class="text-sm font-semibold text-neutral-900">Default Package Dimensions</h2>
                <p class="text-xs text-neutral-600">Fallback values when products don't have their own dimensions set</p>
            </div>
            <div class="p-5">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <label class="form-label">Length (cm)</label>
                        <input type="number" name="default_package_length" value="{{ old('default_package_length', $settings['default_package_length'] ?? '20') }}" min="0" step="1" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Breadth (cm)</label>
                        <input type="number" name="default_package_breadth" value="{{ old('default_package_breadth', $settings['default_package_breadth'] ?? '15') }}" min="0" step="1" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Height (cm)</label>
                        <input type="number" name="default_package_height" value="{{ old('default_package_height', $settings['default_package_height'] ?? '10') }}" min="0" step="1" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Weight (kg)</label>
                        <input type="number" name="default_package_weight" value="{{ old('default_package_weight', $settings['default_package_weight'] ?? '0.5') }}" min="0" step="0.1" class="form-input">
                    </div>
                </div>
            </div>
        </div>

        <!-- Default Shipping Provider -->
        <div class="card mt-6">
            <div class="px-5 py-3.5 border-b border-neutral-200">
                <h2 class="text-sm font-semibold text-neutral-900">Default Shipping Provider</h2>
                <p class="text-xs text-neutral-600">Choose which carrier to use for booking shipments by default</p>
            </div>
            <div class="p-5">
                <select name="shipping_default_provider" class="form-select">
                    <option value="">Auto-detect (first configured)</option>
                    <option value="delhivery" @selected(($settings['shipping_default_provider'] ?? '') === 'delhivery')>Delhivery</option>
                    <option value="bluedart" @selected(($settings['shipping_default_provider'] ?? '') === 'bluedart')>BlueDart</option>
                </select>
            </div>
        </div>

        <!-- Delhivery -->
        <div class="card mt-6" x-data="{ enabled: {{ ($settings['delhivery_enabled'] ?? '1') !== '0' ? 'true' : 'false' }} }">
            <div class="px-5 py-3.5 border-b border-neutral-200 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-neutral-900">Delhivery</h2>
                    <p class="text-xs text-neutral-600">Ship orders via Delhivery API</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="hidden" name="delhivery_enabled" value="0">
                    <input type="checkbox" name="delhivery_enabled" value="1" x-model="enabled" class="sr-only peer">
                    <div class="w-11 h-6 bg-neutral-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-100 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                </label>
            </div>
            <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-4" x-show="enabled" x-collapse>
                <div>
                    <label class="form-label">API Token</label>
                    <input type="password" name="delhivery_api_token" value="" class="form-input" placeholder="{{ !empty($settings['delhivery_api_token'] ?? '') ? '•••••••• (leave blank to keep current)' : 'Enter Delhivery API token' }}" autocomplete="new-password">
                </div>
                <div>
                    <label class="form-label">Origin Pincode</label>
                    <input type="text" name="delhivery_origin_pin" value="{{ old('delhivery_origin_pin', $settings['delhivery_origin_pin'] ?? '') }}" class="form-input" placeholder="110001">
                </div>
                <div>
                    <label class="form-label">Pickup Location Name</label>
                    <input type="text" name="delhivery_pickup_location" value="{{ old('delhivery_pickup_location', $settings['delhivery_pickup_location'] ?? '') }}" class="form-input" placeholder="Main Warehouse">
                </div>
                <div>
                    <label class="form-label">Return Phone</label>
                    <input type="text" name="delhivery_return_phone" value="{{ old('delhivery_return_phone', $settings['delhivery_return_phone'] ?? '') }}" class="form-input" placeholder="9876543210">
                </div>
                <div class="md:col-span-2">
                    <label class="form-label">Return Address</label>
                    <textarea name="delhivery_return_address" rows="2" class="form-textarea" placeholder="Warehouse address for returns...">{{ old('delhivery_return_address', $settings['delhivery_return_address'] ?? '') }}</textarea>
                </div>
            </div>
        </div>

        <!-- BlueDart -->
        <div class="card mt-6" x-data="{ enabled: {{ ($settings['bluedart_enabled'] ?? '') === '1' ? 'true' : 'false' }} }">
            <div class="px-5 py-3.5 border-b border-neutral-200 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-neutral-900">BlueDart</h2>
                    <p class="text-xs text-neutral-600">Ship orders via BlueDart API</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="hidden" name="bluedart_enabled" value="0">
                    <input type="checkbox" name="bluedart_enabled" value="1" x-model="enabled" class="sr-only peer">
                    <div class="w-11 h-6 bg-neutral-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-100 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                </label>
            </div>
            <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-4" x-show="enabled" x-collapse>
                <div>
                    <label class="form-label">API Token</label>
                    <input type="password" name="bluedart_api_token" value="" class="form-input" placeholder="{{ !empty($settings['bluedart_api_token'] ?? '') ? '•••••••• (leave blank to keep current)' : 'Enter BlueDart API token' }}" autocomplete="new-password">
                </div>
                <div>
                    <label class="form-label">Login ID</label>
                    <input type="text" name="bluedart_login_id" value="{{ old('bluedart_login_id', $settings['bluedart_login_id'] ?? '') }}" class="form-input" placeholder="e.g. BOM12345">
                </div>
                <div>
                    <label class="form-label">Licence Key</label>
                    <input type="password" name="bluedart_licence_key" value="" class="form-input" placeholder="{{ !empty($settings['bluedart_licence_key'] ?? '') ? '•••••••• (leave blank to keep current)' : 'Enter licence key' }}" autocomplete="new-password">
                </div>
                <div>
                    <label class="form-label">Customer Code</label>
                    <input type="text" name="bluedart_customer_code" value="{{ old('bluedart_customer_code', $settings['bluedart_customer_code'] ?? '') }}" class="form-input" placeholder="e.g. 12345">
                </div>
                <div>
                    <label class="form-label">Origin Area Code</label>
                    <input type="text" name="bluedart_origin_area" value="{{ old('bluedart_origin_area', $settings['bluedart_origin_area'] ?? '') }}" class="form-input" placeholder="e.g. BOM">
                </div>
                <div>
                    <label class="form-label">Origin Pincode</label>
                    <input type="text" name="bluedart_origin_pin" value="{{ old('bluedart_origin_pin', $settings['bluedart_origin_pin'] ?? '') }}" class="form-input" placeholder="400001">
                </div>
                <div>
                    <label class="form-label">Return Phone</label>
                    <input type="text" name="bluedart_return_phone" value="{{ old('bluedart_return_phone', $settings['bluedart_return_phone'] ?? '') }}" class="form-input" placeholder="9876543210">
                </div>
                <div>
                    <label class="form-label">Mode</label>
                    <select name="bluedart_mode" class="form-select">
                        <option value="sandbox" @selected(($settings['bluedart_mode'] ?? 'sandbox') === 'sandbox')>Sandbox (testing)</option>
                        <option value="live" @selected(($settings['bluedart_mode'] ?? '') === 'live')>Live (production)</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Product Type</label>
                    <select name="bluedart_product_type" class="form-select">
                        <option value="D" @selected(($settings['bluedart_product_type'] ?? 'D') === 'D')>D (Domestic Surface)</option>
                        <option value="A" @selected(($settings['bluedart_product_type'] ?? '') === 'A')>A (Air)</option>
                        <option value="E" @selected(($settings['bluedart_product_type'] ?? '') === 'E')>E (Express)</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Sub Product Type</label>
                    <select name="bluedart_sub_product_type" class="form-select">
                        <option value="P" @selected(($settings['bluedart_sub_product_type'] ?? 'P') === 'P')>P (Prepaid)</option>
                        <option value="C" @selected(($settings['bluedart_sub_product_type'] ?? '') === 'C')>C (COD / Cash on Delivery)</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="form-label">Return Address</label>
                    <textarea name="bluedart_return_address" rows="2" class="form-textarea" placeholder="Warehouse address for returns...">{{ old('bluedart_return_address', $settings['bluedart_return_address'] ?? '') }}</textarea>
                </div>
            </div>
        </div>

        <!-- Shiprocket Checkout (Buy Now) -->
        <div class="card mt-6" x-data="{ enabled: {{ ($settings['shiprocket_checkout_enabled'] ?? false) ? 'true' : 'false' }} }">
            <div class="px-5 py-3.5 border-b border-neutral-200 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-neutral-900">Shiprocket Checkout</h2>
                    <p class="text-xs text-neutral-600">Enable Buy Now button with Shiprocket's hosted checkout (Fastrr)</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="hidden" name="shiprocket_checkout_enabled" value="0">
                    <input type="checkbox" name="shiprocket_checkout_enabled" value="1" x-model="enabled" class="sr-only peer">
                    <div class="w-11 h-6 bg-neutral-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-100 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                </label>
            </div>
            <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-4" x-show="enabled" x-collapse>
                <div>
                    <label class="form-label form-label-required">Checkout API Key</label>
                    <input type="text" name="shiprocket_checkout_api_key" value="{{ old('shiprocket_checkout_api_key', $settings['shiprocket_checkout_api_key'] ?? '') }}" class="form-input" placeholder="tP71JCr...">
                    <p class="text-xs text-neutral-500 mt-1">From Shiprocket Checkout dashboard &rarr; Settings &rarr; API Keys</p>
                </div>
                <div>
                    <label class="form-label form-label-required">Checkout API Secret</label>
                    <input type="password" name="shiprocket_checkout_api_secret" value="" class="form-input" placeholder="{{ !empty($settings['shiprocket_checkout_api_secret'] ?? '') ? '•••••••• (leave blank to keep current)' : 'Enter secret key' }}" autocomplete="new-password">
                    <p class="text-xs text-neutral-500 mt-1">HMAC secret for signing checkout requests</p>
                </div>
                <div>
                    <label class="form-label">Webhook Header Key</label>
                    <input type="text" name="shiprocket_checkout_webhook_header_key" value="{{ old('shiprocket_checkout_webhook_header_key', $settings['shiprocket_checkout_webhook_header_key'] ?? '') }}" class="form-input" placeholder="ayurvexa">
                    <p class="text-xs text-neutral-500 mt-1">Custom header name configured in Shiprocket webhook settings</p>
                </div>
                <div>
                    <label class="form-label">Webhook Header Value</label>
                    <input type="text" name="shiprocket_checkout_webhook_header_value" value="{{ old('shiprocket_checkout_webhook_header_value', $settings['shiprocket_checkout_webhook_header_value'] ?? '') }}" class="form-input" placeholder="Hakunamatata@12">
                    <p class="text-xs text-neutral-500 mt-1">Secret value for the webhook header</p>
                </div>
            </div>
        </div>

        <!-- Shiprocket Shipping Integration -->
        <div class="card mt-6" x-data="{ enabled: {{ ($settings['shiprocket_enabled'] ?? false) ? 'true' : 'false' }} }">
            <div class="px-5 py-3.5 border-b border-neutral-200 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-neutral-900">Shiprocket Shipping</h2>
                    <p class="text-xs text-neutral-600">Auto-push orders to Shiprocket for fulfillment, track shipments</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="hidden" name="shiprocket_enabled" value="0">
                    <input type="checkbox" name="shiprocket_enabled" value="1" x-model="enabled" class="sr-only peer">
                    <div class="w-11 h-6 bg-neutral-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-100 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                </label>
            </div>
            <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-4" x-show="enabled" x-collapse>
                <div>
                    <label class="form-label form-label-required">Shiprocket Email</label>
                    <input type="email" name="shiprocket_email" value="{{ old('shiprocket_email', $settings['shiprocket_email'] ?? '') }}" class="form-input" placeholder="api-user@example.com">
                </div>
                <div>
                    <label class="form-label">Password</label>
                    <input type="password" name="shiprocket_password" value="" class="form-input" placeholder="{{ !empty($settings['shiprocket_password'] ?? '') ? '•••••••• (leave blank to keep current)' : 'Enter password' }}" autocomplete="new-password">
                </div>
                <div>
                    <label class="form-label form-label-required">Pickup Location Nickname</label>
                    <input type="text" name="shiprocket_pickup_location" value="{{ old('shiprocket_pickup_location', $settings['shiprocket_pickup_location'] ?? 'Primary') }}" class="form-input" placeholder="Primary">
                    <p class="text-xs text-neutral-500 mt-1">Must match the nickname configured in your Shiprocket dashboard</p>
                </div>
                <div>
                    <label class="form-label form-label-required">Pickup Pincode</label>
                    <input type="text" name="shiprocket_pickup_pincode" value="{{ old('shiprocket_pickup_pincode', $settings['shiprocket_pickup_pincode'] ?? '') }}" class="form-input" placeholder="110001" maxlength="10">
                    <p class="text-xs text-neutral-500 mt-1">Used for serviceability checks from your warehouse</p>
                </div>
            </div>
        </div>

        <div class="mt-6 flex justify-end">
            <button type="submit" class="btn btn-primary"
                    x-data="{ loading: false }"
                    @click="loading = true; setTimeout(() => loading = false, 5000)"
                    :disabled="loading">
                <span x-show="!loading">Save Settings</span>
                <span x-show="loading" x-cloak class="flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    Saving...
                </span>
            </button>
        </div>
    </form>
</x-layouts.admin>
