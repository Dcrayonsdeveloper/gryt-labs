<x-layouts.admin>
    <x-slot name="title">Features Settings</x-slot>

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-neutral-900">Settings</h1>
        <p class="text-neutral-600">Manage your store configuration</p>
    </div>

    <!-- Settings Navigation -->
    @include('admin.settings.partials.nav', ['active' => 'product-card'])

    <form action="{{ route('admin.settings.product-card.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="max-w-2xl">
            <div class="card">
                <div class="px-5 py-3.5 border-b border-neutral-200">
                    <h2 class="text-sm font-semibold text-neutral-900">Product Card Hover Actions</h2>
                    <p class="text-xs text-neutral-600 mt-0.5">Control which actions appear when customers hover over product cards on the storefront.</p>
                </div>
                <div class="p-5 space-y-4">
                    <!-- Quick View -->
                    <label class="flex items-center justify-between p-4 rounded-lg border border-neutral-200 cursor-pointer hover:bg-neutral-50 transition-colors" x-data="{ enabled: {{ $settings['product_card_quick_view'] ? 'true' : 'false' }} }">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-info-100 rounded-lg flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-info-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-neutral-900">Quick View</p>
                                <p class="text-xs text-neutral-600">Show an eye icon to preview product details in a popup without leaving the page.</p>
                            </div>
                        </div>
                        <div class="relative shrink-0 ml-4">
                            <input type="hidden" name="product_card_quick_view" value="0">
                            <input type="checkbox" name="product_card_quick_view" value="1" x-model="enabled" class="sr-only peer">
                            <div @click="enabled = !enabled"
                                 class="w-11 h-6 bg-neutral-300 rounded-full transition-colors cursor-pointer"
                                 :class="enabled ? 'bg-primary-600!' : 'bg-neutral-300'">
                                <div class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform"
                                     :class="enabled ? 'translate-x-5' : 'translate-x-0'"></div>
                            </div>
                        </div>
                    </label>

                    <!-- Add to Cart -->
                    <label class="flex items-center justify-between p-4 rounded-lg border border-neutral-200 cursor-pointer hover:bg-neutral-50 transition-colors" x-data="{ enabled: {{ $settings['product_card_add_to_cart'] ? 'true' : 'false' }} }">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-success-100 rounded-lg flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-neutral-900">Add to Cart</p>
                                <p class="text-xs text-neutral-600">Show the Add to Bag button on hover for quick cart additions.</p>
                            </div>
                        </div>
                        <div class="relative shrink-0 ml-4">
                            <input type="hidden" name="product_card_add_to_cart" value="0">
                            <input type="checkbox" name="product_card_add_to_cart" value="1" x-model="enabled" class="sr-only peer">
                            <div @click="enabled = !enabled"
                                 class="w-11 h-6 bg-neutral-300 rounded-full transition-colors cursor-pointer"
                                 :class="enabled ? 'bg-primary-600!' : 'bg-neutral-300'">
                                <div class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform"
                                     :class="enabled ? 'translate-x-5' : 'translate-x-0'"></div>
                            </div>
                        </div>
                    </label>

                    <!-- Wishlist -->
                    <label class="flex items-center justify-between p-4 rounded-lg border border-neutral-200 cursor-pointer hover:bg-neutral-50 transition-colors" x-data="{ enabled: {{ $settings['product_card_wishlist'] ? 'true' : 'false' }} }">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-error-100 rounded-lg flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-error-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-neutral-900">Wishlist</p>
                                <p class="text-xs text-neutral-600">Show a heart icon on hover so customers can save products to their wishlist.</p>
                            </div>
                        </div>
                        <div class="relative shrink-0 ml-4">
                            <input type="hidden" name="product_card_wishlist" value="0">
                            <input type="checkbox" name="product_card_wishlist" value="1" x-model="enabled" class="sr-only peer">
                            <div @click="enabled = !enabled"
                                 class="w-11 h-6 bg-neutral-300 rounded-full transition-colors cursor-pointer"
                                 :class="enabled ? 'bg-primary-600!' : 'bg-neutral-300'">
                                <div class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform"
                                     :class="enabled ? 'translate-x-5' : 'translate-x-0'"></div>
                            </div>
                        </div>
                    </label>
                </div>

                <div class="px-5 py-3 bg-neutral-50 border-t border-neutral-200 flex justify-end rounded-b-lg">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </div>

            <!-- Product Page Sections -->
            <div class="card mt-6">
                <div class="px-5 py-3.5 border-b border-neutral-200">
                    <h2 class="text-sm font-semibold text-neutral-900">Product Page Sections</h2>
                    <p class="text-xs text-neutral-600 mt-0.5">Show or hide sections on the product detail page.</p>
                </div>
                <div class="p-5 space-y-4">
                    <!-- Available Coupons -->
                    <label class="flex items-center justify-between p-4 rounded-lg border border-neutral-200 cursor-pointer hover:bg-neutral-50 transition-colors" x-data="{ enabled: {{ ($settings['show_product_coupons'] ?? '1') === '1' ? 'true' : 'false' }} }">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-warning-100 rounded-lg flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-warning-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-neutral-900">Available Coupons</p>
                                <p class="text-xs text-neutral-600">Show available coupon offers on product pages.</p>
                            </div>
                        </div>
                        <div class="relative shrink-0 ml-4">
                            <input type="hidden" name="show_product_coupons" value="0">
                            <input type="checkbox" name="show_product_coupons" value="1" x-model="enabled" class="sr-only peer">
                            <div @click="enabled = !enabled"
                                 class="w-11 h-6 bg-neutral-300 rounded-full transition-colors cursor-pointer"
                                 :class="enabled ? 'bg-primary-600!' : 'bg-neutral-300'">
                                <div class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform"
                                     :class="enabled ? 'translate-x-5' : 'translate-x-0'"></div>
                            </div>
                        </div>
                    </label>

                    <!-- Compare Products -->
                    <label class="flex items-center justify-between p-4 rounded-lg border border-neutral-200 cursor-pointer hover:bg-neutral-50 transition-colors" x-data="{ enabled: {{ ($settings['show_product_compare'] ?? '1') === '1' ? 'true' : 'false' }} }">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-info-100 rounded-lg flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-info-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-neutral-900">Compare Products</p>
                                <p class="text-xs text-neutral-600">Show the "Compare with similar items" table on product pages.</p>
                            </div>
                        </div>
                        <div class="relative shrink-0 ml-4">
                            <input type="hidden" name="show_product_compare" value="0">
                            <input type="checkbox" name="show_product_compare" value="1" x-model="enabled" class="sr-only peer">
                            <div @click="enabled = !enabled"
                                 class="w-11 h-6 bg-neutral-300 rounded-full transition-colors cursor-pointer"
                                 :class="enabled ? 'bg-primary-600!' : 'bg-neutral-300'">
                                <div class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform"
                                     :class="enabled ? 'translate-x-5' : 'translate-x-0'"></div>
                            </div>
                        </div>
                    </label>
                </div>

                <div class="px-5 py-3 bg-neutral-50 border-t border-neutral-200 flex justify-end rounded-b-lg">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </div>

            <!-- Product Page Content -->
            <div class="card mt-6">
                <div class="px-5 py-3.5 border-b border-neutral-200">
                    <h2 class="text-sm font-semibold text-neutral-900">Product Page Content</h2>
                    <p class="text-xs text-neutral-600 mt-0.5">Configure text and badges shown on product pages. Leave blank to hide a section.</p>
                </div>
                <div class="p-5 space-y-5">
                    {{-- Text Settings --}}
                    <div>
                        <label for="pdp_social_proof_text" class="form-label">Social Proof Text</label>
                        <input type="text" name="pdp_social_proof_text" id="pdp_social_proof_text" value="{{ $settings['pdp_social_proof_text'] ?? '' }}" class="form-input w-full" placeholder="e.g. bought this month">
                        <p class="text-xs text-neutral-500 mt-1">Shown as "{count}+ {text}". Leave empty to hide.</p>
                    </div>
                    <div>
                        <label for="pdp_deal_badge_text" class="form-label">Deal Badge Text</label>
                        <input type="text" name="pdp_deal_badge_text" id="pdp_deal_badge_text" value="{{ $settings['pdp_deal_badge_text'] ?? '' }}" class="form-input w-full" placeholder="e.g. Limited Time Deal">
                        <p class="text-xs text-neutral-500 mt-1">Shown when product has a discount.</p>
                    </div>
                    <div>
                        <label for="pdp_tax_text" class="form-label">Tax / Price Note</label>
                        <input type="text" name="pdp_tax_text" id="pdp_tax_text" value="{{ $settings['pdp_tax_text'] ?? '' }}" class="form-input w-full" placeholder="e.g. Inclusive of all taxes">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="pdp_delivery_days" class="form-label">Estimated Delivery Days</label>
                            <input type="number" name="pdp_delivery_days" id="pdp_delivery_days" value="{{ $settings['pdp_delivery_days'] ?? '' }}" class="form-input w-full" placeholder="e.g. 3" min="0">
                        </div>
                        <div>
                            <label for="pdp_fastest_delivery_text" class="form-label">Fastest Delivery Text</label>
                            <input type="text" name="pdp_fastest_delivery_text" id="pdp_fastest_delivery_text" value="{{ $settings['pdp_fastest_delivery_text'] ?? '' }}" class="form-input w-full" placeholder="e.g. Tomorrow">
                        </div>
                    </div>
                    <div>
                        <label for="pdp_payment_methods" class="form-label">Payment Methods</label>
                        <input type="text" name="pdp_payment_methods" id="pdp_payment_methods" value="{{ $settings['pdp_payment_methods'] ?? '' }}" class="form-input w-full" placeholder="e.g. VISA, MC, UPI, RuPay, Net Banking, COD">
                        <p class="text-xs text-neutral-500 mt-1">Comma-separated list shown under "Secure transaction".</p>
                    </div>

                    {{-- Trust Badges — visual builder --}}
                    <div class="border-t border-neutral-100 pt-5" x-data="{
                        badges: @js(json_decode($settings['pdp_trust_badges'] ?? '[]', true) ?: []),
                        add() { if (this.badges.length < 4) this.badges.push({ title: '', subtitle: '' }); },
                        remove(i) { this.badges.splice(i, 1); }
                    }">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <p class="form-label mb-0">Trust Badges</p>
                                <p class="text-xs text-neutral-500">Shown at the bottom of the buy box. Max 4.</p>
                            </div>
                            <button type="button" @click="add()" x-show="badges.length < 4"
                                    class="text-xs font-medium text-primary-600 hover:text-primary-800 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Add Badge
                            </button>
                        </div>
                        <input type="hidden" name="pdp_trust_badges" :value="JSON.stringify(badges)">
                        <div class="space-y-2">
                            <template x-for="(badge, index) in badges" :key="index">
                                <div class="p-3 rounded-lg border border-neutral-200 bg-neutral-50">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-xs font-semibold text-neutral-500">Badge <span x-text="index + 1"></span></span>
                                        <button type="button" @click="remove(index)" class="w-6 h-6 flex items-center justify-center text-red-400 hover:text-red-600 hover:bg-red-50 rounded transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <input type="text" x-model="badge.title" placeholder="Title (e.g. Free Delivery)" class="form-input text-sm">
                                        <input type="text" x-model="badge.subtitle" placeholder="Subtitle (e.g. Above ₹499)" class="form-input text-sm">
                                    </div>
                                </div>
                            </template>
                            <p x-show="badges.length === 0" class="text-xs text-neutral-400 italic py-2">No trust badges. Click "Add Badge" to create one.</p>
                        </div>
                    </div>

                    {{-- Stats Carousel — visual builder --}}
                    <div class="border-t border-neutral-100 pt-5" x-data="{
                        stats: @js(json_decode($settings['product_stats_carousel'] ?? '[]', true) ?: []),
                        add() { this.stats.push({ value: '', unit: '', label: '' }); },
                        remove(i) { this.stats.splice(i, 1); }
                    }">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <p class="form-label mb-0">Stats Carousel</p>
                                <p class="text-xs text-neutral-500">Rotating stats shown on the product page (e.g. clinical results).</p>
                            </div>
                            <button type="button" @click="add()"
                                    class="text-xs font-medium text-primary-600 hover:text-primary-800 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Add Stat
                            </button>
                        </div>
                        <input type="hidden" name="product_stats_carousel" :value="JSON.stringify(stats)">
                        <div class="space-y-2">
                            <template x-for="(stat, index) in stats" :key="index">
                                <div class="p-3 rounded-lg border border-neutral-200 bg-neutral-50">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-xs font-semibold text-neutral-500">Stat <span x-text="index + 1"></span></span>
                                        <button type="button" @click="remove(index)" class="w-6 h-6 flex items-center justify-center text-red-400 hover:text-red-600 hover:bg-red-50 rounded transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                    <div class="grid grid-cols-4 gap-2">
                                        <input type="text" x-model="stat.value" placeholder="Value" class="form-input text-sm text-center font-bold">
                                        <input type="text" x-model="stat.unit" placeholder="Unit" class="form-input text-sm text-center">
                                        <input type="text" x-model="stat.label" placeholder="Label (e.g. Reduction in Stress)" class="form-input text-sm col-span-2">
                                    </div>
                                </div>
                            </template>
                            <p x-show="stats.length === 0" class="text-xs text-neutral-400 italic py-2">No stats configured. Click "Add Stat" to create one.</p>
                        </div>
                        <div x-show="stats.length > 0" class="mt-3 p-3 rounded-lg bg-green-50 border border-green-200">
                            <p class="text-xs font-medium text-green-800 mb-1">Preview:</p>
                            <div class="flex flex-wrap items-baseline gap-x-4 gap-y-1 text-green-900">
                                <template x-for="(stat, i) in stats" :key="'preview-'+i">
                                    <span class="text-sm">
                                        <strong x-text="stat.value"></strong><span x-text="stat.unit"></span>
                                        <span class="text-green-700 text-xs" x-text="stat.label"></span>
                                    </span>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-5 py-3 bg-neutral-50 border-t border-neutral-200 flex justify-end rounded-b-lg">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </div>

            <!-- Instagram / Reels Section -->
            <div class="card mt-6">
                <div class="px-5 py-3.5 border-b border-neutral-200">
                    <h2 class="text-sm font-semibold text-neutral-900">Instagram Reels Section</h2>
                    <p class="text-xs text-neutral-600 mt-0.5">Heading and subheading for the Instagram reels section on the homepage.</p>
                </div>
                <div class="p-5 space-y-4">
                    <div>
                        <label for="reels_section_heading" class="form-label">Heading</label>
                        <input type="text" name="reels_section_heading" id="reels_section_heading" value="{{ $settings['reels_section_heading'] ?? '' }}" class="form-input w-full" placeholder="e.g. Follow Us on Instagram">
                    </div>
                    <div>
                        <label for="reels_section_subheading" class="form-label">Subheading</label>
                        <input type="text" name="reels_section_subheading" id="reels_section_subheading" value="{{ $settings['reels_section_subheading'] ?? '' }}" class="form-input w-full" placeholder="e.g. @yourhandle — Watch, explore, and shop">
                    </div>
                </div>
                <div class="px-5 py-3 bg-neutral-50 border-t border-neutral-200 flex justify-end rounded-b-lg">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </div>

            <!-- Product Benefits Grid -->
            <div class="card mt-6">
                <div class="px-5 py-3.5 border-b border-neutral-200">
                    <h2 class="text-sm font-semibold text-neutral-900">Product Benefits Grid</h2>
                    <p class="text-xs text-neutral-600 mt-0.5">Default benefits shown on all product pages. Individual products can override this via their own attributes.</p>
                </div>
                <div class="p-5" x-data="{
                    benefits: @js(json_decode($settings['product_benefits'] ?? '[]', true) ?: []),
                    icons: [
                        { value: 'check', label: 'Checkmark' },
                        { value: 'shield', label: 'Shield' },
                        { value: 'energy', label: 'Energy' },
                        { value: 'herb', label: 'Herb/Star' },
                        { value: 'molecule', label: 'Molecule' },
                        { value: 'noside', label: 'No Side Effects' },
                        { value: 'recovery', label: 'Recovery' }
                    ],
                    add() { this.benefits.push({ text: '', icon: 'check' }); },
                    remove(i) { this.benefits.splice(i, 1); }
                }">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-xs text-neutral-500">Max 6 benefits. Shown in a 2-column grid on the product page.</p>
                        <button type="button" @click="add()" x-show="benefits.length < 6"
                                class="text-xs font-medium text-primary-600 hover:text-primary-800 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Add Benefit
                        </button>
                    </div>
                    <input type="hidden" name="product_benefits" :value="JSON.stringify(benefits)">
                    <div class="space-y-2">
                        <template x-for="(benefit, index) in benefits" :key="index">
                            <div class="p-3 rounded-lg border border-neutral-200 bg-neutral-50">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-semibold text-neutral-500">Benefit <span x-text="index + 1"></span></span>
                                    <button type="button" @click="remove(index)" class="w-6 h-6 flex items-center justify-center text-red-400 hover:text-red-600 hover:bg-red-50 rounded transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                                <div class="space-y-2">
                                    <input type="text" x-model="benefit.text" placeholder="Benefit text (e.g. 100% Safe & Effective)" class="form-input text-sm w-full">
                                    <select x-model="benefit.icon" class="form-input text-sm w-full">
                                        <template x-for="ic in icons" :key="ic.value">
                                            <option :value="ic.value" x-text="'Icon: ' + ic.label" :selected="benefit.icon === ic.value"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>
                        </template>
                        <p x-show="benefits.length === 0" class="text-xs text-neutral-400 italic py-2">No benefits configured. Click "Add Benefit" to create one.</p>
                    </div>
                </div>

                <div class="px-5 py-3 bg-neutral-50 border-t border-neutral-200 flex justify-end rounded-b-lg">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </div>

            <!-- Choose Your Pack -->
            <div class="card mt-6">
                <div class="px-5 py-3.5 border-b border-neutral-200">
                    <h2 class="text-sm font-semibold text-neutral-900">Choose Your Pack</h2>
                    <p class="text-xs text-neutral-600 mt-0.5">Bundle pricing tiers shown on product pages. Enable and configure pack options below.</p>
                </div>
                <div class="p-5 space-y-5">
                    {{-- Enable toggle --}}
                    <label class="flex items-center justify-between p-4 rounded-lg border border-neutral-200 cursor-pointer hover:bg-neutral-50 transition-colors" x-data="{ enabled: {{ ($settings['product_packs_enabled'] ?? '0') === '1' ? 'true' : 'false' }} }">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8 4-8-4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-neutral-900">Enable Pack Pricing</p>
                                <p class="text-xs text-neutral-600">Show "Choose Your Pack" section with quantity tiers and discounts.</p>
                            </div>
                        </div>
                        <div class="relative shrink-0 ml-4">
                            <input type="hidden" name="product_packs_enabled" value="0">
                            <input type="checkbox" name="product_packs_enabled" value="1" x-model="enabled" class="sr-only peer">
                            <div @click="enabled = !enabled"
                                 class="w-11 h-6 bg-neutral-300 rounded-full transition-colors cursor-pointer"
                                 :class="enabled ? 'bg-primary-600!' : 'bg-neutral-300'">
                                <div class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform"
                                     :class="enabled ? 'translate-x-5' : 'translate-x-0'"></div>
                            </div>
                        </div>
                    </label>

                    {{-- Pack config --}}
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label for="pack_unit_label" class="form-label">Unit Label</label>
                            <input type="text" name="pack_unit_label" id="pack_unit_label" value="{{ $settings['pack_unit_label'] ?? 'Capsules' }}" class="form-input w-full" placeholder="e.g. Capsules, Tablets, ml">
                        </div>
                        <div>
                            <label for="pack_units_per_qty" class="form-label">Units per Pack</label>
                            <input type="number" name="pack_units_per_qty" id="pack_units_per_qty" value="{{ $settings['pack_units_per_qty'] ?? '60' }}" class="form-input w-full" placeholder="e.g. 60" min="1">
                        </div>
                        <div>
                            <label for="pack_months_per_qty" class="form-label">Months per Pack</label>
                            <input type="number" name="pack_months_per_qty" id="pack_months_per_qty" value="{{ $settings['pack_months_per_qty'] ?? '1' }}" class="form-input w-full" placeholder="e.g. 1" min="1">
                        </div>
                    </div>

                    {{-- Pack tiers --}}
                    <div x-data="{
                        tiers: @js(json_decode($settings['pack_tiers'] ?? '[{&quot;qty&quot;:1,&quot;discount&quot;:0,&quot;badge&quot;:null},{&quot;qty&quot;:2,&quot;discount&quot;:5,&quot;badge&quot;:&quot;MOST POPULAR&quot;},{&quot;qty&quot;:3,&quot;discount&quot;:10,&quot;badge&quot;:&quot;BEST VALUE&quot;}]', true) ?: []),
                        add() { this.tiers.push({ qty: this.tiers.length + 1, discount: 0, badge: '' }); },
                        remove(i) { this.tiers.splice(i, 1); }
                    }">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <p class="form-label mb-0">Pack Tiers</p>
                                <p class="text-xs text-neutral-500">Each tier = quantity multiplier. Discount is % off the total.</p>
                            </div>
                            <button type="button" @click="add()"
                                    class="text-xs font-medium text-primary-600 hover:text-primary-800 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Add Tier
                            </button>
                        </div>
                        <input type="hidden" name="pack_tiers" :value="JSON.stringify(tiers)">
                        <div class="space-y-2">
                            <template x-for="(tier, index) in tiers" :key="index">
                                <div class="p-3 rounded-lg border border-neutral-200 bg-neutral-50">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-xs font-semibold text-neutral-500">Tier <span x-text="index + 1"></span></span>
                                        <button type="button" @click="remove(index)" class="w-6 h-6 flex items-center justify-center text-red-400 hover:text-red-600 hover:bg-red-50 rounded transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                    <div class="grid grid-cols-3 gap-2">
                                        <div>
                                            <label class="text-[10px] text-neutral-500 uppercase">Quantity</label>
                                            <input type="number" x-model.number="tier.qty" min="1" class="form-input text-sm w-full text-center font-bold">
                                        </div>
                                        <div>
                                            <label class="text-[10px] text-neutral-500 uppercase">Discount %</label>
                                            <input type="number" x-model.number="tier.discount" min="0" max="99" class="form-input text-sm w-full text-center">
                                        </div>
                                        <div>
                                            <label class="text-[10px] text-neutral-500 uppercase">Badge Text</label>
                                            <input type="text" x-model="tier.badge" placeholder="e.g. BEST VALUE" class="form-input text-sm w-full">
                                        </div>
                                    </div>
                                </div>
                            </template>
                            <p x-show="tiers.length === 0" class="text-xs text-neutral-400 italic py-2">No tiers configured. Click "Add Tier" to create one.</p>
                        </div>
                    </div>
                </div>

                <div class="px-5 py-3 bg-neutral-50 border-t border-neutral-200 flex justify-end rounded-b-lg">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </div>

            <!-- Marketing & Engagement -->
            <div class="card mt-6">
                <div class="px-5 py-3.5 border-b border-neutral-200">
                    <h2 class="text-sm font-semibold text-neutral-900">Marketing & Engagement</h2>
                    <p class="text-xs text-neutral-600 mt-0.5">Configure popups and post-purchase engagement features.</p>
                </div>
                <div class="p-5 space-y-4">
                    <!-- Exit-Intent Popup Discount -->
                    <div class="p-4 rounded-lg border border-neutral-200">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-neutral-900">Exit-Intent Popup Discount %</p>
                                <p class="text-xs text-neutral-600">Set to 0 to show a plain newsletter popup. Set a number (e.g. 10) for a "Get 10% off" popup.</p>
                            </div>
                        </div>
                        <input type="number" name="exit_popup_discount" value="{{ $settings['exit_popup_discount'] ?? '0' }}" min="0" max="100" step="1" class="form-input w-32" placeholder="0">
                    </div>

                    <!-- Exit Popup Text Customization (only when no discount) -->
                    <div class="p-4 rounded-lg border border-neutral-200 space-y-3">
                        <div class="flex items-center gap-3 mb-1">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-neutral-900">Newsletter Popup Text</p>
                                <p class="text-xs text-neutral-600">Customize the popup text shown when discount is 0. Leave blank for defaults.</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="form-label">Title</label>
                                <input type="text" name="exit_popup_title" value="{{ $settings['exit_popup_title'] ?? '' }}" class="form-input w-full" placeholder="Stay in the Loop">
                            </div>
                            <div>
                                <label class="form-label">Button Text</label>
                                <input type="text" name="exit_popup_button_text" value="{{ $settings['exit_popup_button_text'] ?? '' }}" class="form-input w-full" placeholder="Subscribe">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="form-label">Description</label>
                                <textarea name="exit_popup_description" rows="2" class="form-input w-full" placeholder="Sign up for exclusive deals, new arrivals, and updates from {store_name}.">{{ $settings['exit_popup_description'] ?? '' }}</textarea>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="form-label">Footer Text</label>
                                <input type="text" name="exit_popup_footer_text" value="{{ $settings['exit_popup_footer_text'] ?? '' }}" class="form-input w-full" placeholder="No spam, ever. Unsubscribe anytime.">
                            </div>
                            <div>
                                <label class="form-label">Success Title</label>
                                <input type="text" name="exit_popup_success_title" value="{{ $settings['exit_popup_success_title'] ?? '' }}" class="form-input w-full" placeholder="Subscribed!">
                            </div>
                            <div>
                                <label class="form-label">Success Message</label>
                                <input type="text" name="exit_popup_success_text" value="{{ $settings['exit_popup_success_text'] ?? '' }}" class="form-input w-full" placeholder="Thanks for subscribing. Stay tuned for updates!">
                            </div>
                        </div>
                    </div>

                    <!-- Review Coupon -->
                    <label class="flex items-center justify-between p-4 rounded-lg border border-neutral-200 cursor-pointer hover:bg-neutral-50 transition-colors" x-data="{ enabled: {{ ($settings['review_coupon_enabled'] ?? '1') === '1' ? 'true' : 'false' }} }">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-neutral-900">Send Discount Coupon After Review</p>
                                <p class="text-xs text-neutral-600">When enabled, customers receive a coupon code after submitting a product review.</p>
                            </div>
                        </div>
                        <div class="relative shrink-0 ml-4">
                            <input type="hidden" name="review_coupon_enabled" value="0">
                            <input type="checkbox" name="review_coupon_enabled" value="1" x-model="enabled" class="sr-only peer">
                            <div @click="enabled = !enabled"
                                 class="w-11 h-6 bg-neutral-300 rounded-full transition-colors cursor-pointer"
                                 :class="enabled ? 'bg-primary-600!' : 'bg-neutral-300'">
                                <div class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform"
                                     :class="enabled ? 'translate-x-5' : 'translate-x-0'"></div>
                            </div>
                        </div>
                    </label>
                </div>

                <div class="px-5 py-3 bg-neutral-50 border-t border-neutral-200 flex justify-end rounded-b-lg">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </div>

            <!-- Customer Features -->
            <div class="card mt-6">
                <div class="px-5 py-3.5 border-b border-neutral-200">
                    <h2 class="text-sm font-semibold text-neutral-900">Customer Features</h2>
                    <p class="text-xs text-neutral-600 mt-0.5">Enable or disable features available to customers.</p>
                </div>
                <div class="p-5 space-y-4">
                    <!-- Support Tickets -->
                    <label class="flex items-center justify-between p-4 rounded-lg border border-neutral-200 cursor-pointer hover:bg-neutral-50 transition-colors" x-data="{ enabled: {{ $settings['support_tickets_enabled'] ? 'true' : 'false' }} }">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-neutral-900">Support Tickets</p>
                                <p class="text-xs text-neutral-600">Allow customers to raise support tickets from their account dashboard.</p>
                            </div>
                        </div>
                        <div class="relative shrink-0 ml-4">
                            <input type="hidden" name="support_tickets_enabled" value="0">
                            <input type="checkbox" name="support_tickets_enabled" value="1" x-model="enabled" class="sr-only peer">
                            <div @click="enabled = !enabled"
                                 class="w-11 h-6 bg-neutral-300 rounded-full transition-colors cursor-pointer"
                                 :class="enabled ? 'bg-primary-600!' : 'bg-neutral-300'">
                                <div class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform"
                                     :class="enabled ? 'translate-x-5' : 'translate-x-0'"></div>
                            </div>
                        </div>
                    </label>
                </div>

                <div class="px-5 py-3 bg-neutral-50 border-t border-neutral-200 flex justify-end rounded-b-lg">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </div>
        </div>
    </form>
</x-layouts.admin>
