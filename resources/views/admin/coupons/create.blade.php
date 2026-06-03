<x-layouts.admin>
    <x-slot name="title">Add Coupon</x-slot>

    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm text-neutral-600 mb-1">
            <a href="{{ route('admin.coupons.index') }}" class="hover:text-primary-600">Coupons</a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-neutral-900">Add Coupon</span>
        </div>
        <h1 class="text-2xl font-bold text-neutral-900">Add Coupon</h1>
    </div>

    <form action="{{ route('admin.coupons.store') }}" method="POST">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                {{-- Coupon Details --}}
                <div class="card">
                    <div class="card-header">
                        <h2 class="font-semibold text-neutral-900">Coupon Details</h2>
                    </div>
                    <div class="p-4 space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="code" class="form-label">Code <span class="text-error-500">*</span></label>
                                <input type="text" name="code" id="code" value="{{ old('code') }}" required
                                       class="form-input w-full font-mono uppercase" placeholder="e.g. SAVE20">
                                @error('code')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="name" class="form-label">Name <span class="text-error-500">*</span></label>
                                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                       class="form-input w-full" placeholder="e.g. Summer Sale 20% Off">
                                @error('name')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label for="description" class="form-label">Description</label>
                            <textarea name="description" id="description" rows="2" class="form-textarea w-full" placeholder="Optional description...">{{ old('description') }}</textarea>
                            @error('description')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div x-data="{ couponType: '{{ old('type', 'percentage') }}' }">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="type" class="form-label">Type <span class="text-error-500">*</span></label>
                                    <select name="type" id="type" class="form-select w-full" required x-model="couponType">
                                        <option value="percentage">Percentage (%)</option>
                                        <option value="fixed">Fixed Amount</option>
                                        <option value="free_shipping">Free Shipping</option>
                                        <option value="buy_x_get_y">Buy X Get Y</option>
                                    </select>
                                    @error('type')
                                        <p class="form-error">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="value" class="form-label">
                                        <span x-show="couponType !== 'buy_x_get_y'">Value</span>
                                        <span x-show="couponType === 'buy_x_get_y'" x-cloak>Discount % on free items</span>
                                        <span class="text-error-500">*</span>
                                    </label>
                                    <input type="number" name="value" id="value" value="{{ old('value') }}" step="0.01" min="0" required
                                           class="form-input w-full"
                                           :placeholder="couponType === 'buy_x_get_y' ? 'e.g. 100 for free' : 'e.g. 20'">
                                    <p x-show="couponType === 'buy_x_get_y'" x-cloak class="text-xs text-neutral-600 mt-1">Enter 100 for completely free, 50 for half price, etc.</p>
                                    @error('value')
                                        <p class="form-error">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            {{-- Buy X Get Y Configuration --}}
                            <div x-show="couponType === 'buy_x_get_y'" x-cloak class="mt-4">
                                <div class="p-4 bg-primary-50 border border-primary-100 rounded-lg space-y-4">
                                    <h3 class="text-sm font-semibold text-primary-800">Buy X Get Y Configuration</h3>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label for="conditions_buy_qty" class="form-label">Buy Quantity <span class="text-error-500">*</span></label>
                                            <input type="number" name="conditions[buy_qty]" id="conditions_buy_qty"
                                                   value="{{ old('conditions.buy_qty') }}" min="1" step="1"
                                                   class="form-input w-full" placeholder="e.g. 2">
                                            <p class="text-xs text-neutral-600 mt-1">Customer must buy this many items</p>
                                            @error('conditions.buy_qty')
                                                <p class="form-error">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="conditions_get_qty" class="form-label">Get Quantity <span class="text-error-500">*</span></label>
                                            <input type="number" name="conditions[get_qty]" id="conditions_get_qty"
                                                   value="{{ old('conditions.get_qty') }}" min="1" step="1"
                                                   class="form-input w-full" placeholder="e.g. 1">
                                            <p class="text-xs text-neutral-600 mt-1">Number of items discounted</p>
                                            @error('conditions.get_qty')
                                                <p class="form-error">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>
                                    <p class="text-xs text-primary-600">
                                        Example: Buy <span x-text="$el.closest('[x-data]').querySelector('#conditions_buy_qty')?.value || '2'"></span>,
                                        Get <span x-text="$el.closest('[x-data]').querySelector('#conditions_get_qty')?.value || '1'"></span>
                                        at <span x-text="($el.closest('[x-data]').querySelector('#value')?.value || '100') + '% off'"></span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Limits --}}
                <div class="card">
                    <div class="card-header">
                        <h2 class="font-semibold text-neutral-900">Limits</h2>
                    </div>
                    <div class="p-4 space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="max_discount" class="form-label">Max Discount ({{ currency_symbol() }})</label>
                                <input type="number" name="max_discount" id="max_discount" value="{{ old('max_discount') }}" step="0.01" min="0"
                                       class="form-input w-full" placeholder="No limit">
                                @error('max_discount')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="min_order_amount" class="form-label">Min Order Amount ({{ currency_symbol() }})</label>
                                <input type="number" name="min_order_amount" id="min_order_amount" value="{{ old('min_order_amount') }}" step="0.01" min="0"
                                       class="form-input w-full" placeholder="No minimum">
                                @error('min_order_amount')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="usage_limit" class="form-label">Total Usage Limit</label>
                                <input type="number" name="usage_limit" id="usage_limit" value="{{ old('usage_limit') }}" min="1"
                                       class="form-input w-full" placeholder="Unlimited">
                                @error('usage_limit')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="usage_per_user" class="form-label">Usage Per User</label>
                                <input type="number" name="usage_per_user" id="usage_per_user" value="{{ old('usage_per_user') }}" min="1"
                                       class="form-input w-full" placeholder="Unlimited">
                                @error('usage_per_user')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Applicable Products --}}
                <div class="card" x-data="productSearch" data-search-url="{{ route('admin.search.products') }}"
                     data-selected="{{ json_encode(old('applicable_products', []), JSON_HEX_APOS | JSON_HEX_QUOT) }}"
                     data-selected-names="{{ json_encode(new stdClass(), JSON_HEX_APOS | JSON_HEX_QUOT) }}">
                    <div class="card-header">
                        <h2 class="font-semibold text-neutral-900">Applicable Products</h2>
                    </div>
                    <div class="p-4 space-y-3">
                        <p class="text-xs text-neutral-600">Leave empty to apply to all products.</p>

                        {{-- Select2-style container --}}
                        <div class="relative" @click.outside="showDropdown = false; focused = false">
                            <div class="flex flex-wrap items-center gap-1.5 min-h-10.5 max-h-32 overflow-y-auto w-full rounded-lg border bg-white px-2.5 py-1.5 cursor-text transition-colors"
                                 :class="focused ? 'border-neutral-400' : 'border-neutral-300'"
                                 @click="$refs.searchInput.focus()">
                                {{-- Selected tags --}}
                                <template x-for="id in selected" :key="id">
                                    <span class="inline-flex items-center gap-1 bg-primary-50 text-primary-700 text-xs font-medium pl-2 pr-1 py-0.5 rounded">
                                        <span x-text="getName(id)" class="max-w-37.5 truncate"></span>
                                        <button type="button" @click.stop="remove(id)" class="text-primary-400 hover:text-primary-700 hover:bg-primary-100 rounded p-0.5 transition-colors">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                        <input type="hidden" name="applicable_products[]" :value="id">
                                    </span>
                                </template>
                                {{-- Inline search input --}}
                                <input type="text" x-ref="searchInput" x-model="search"
                                       @input="onSearch()"
                                       @focus="focused = true; if (results.length > 0) showDropdown = true"
                                       @blur="focused = false"
                                       @keydown.backspace="if (search === '' && selected.length > 0) remove(selected[selected.length - 1])"
                                       placeholder=""
                                       :placeholder="selected.length === 0 ? 'Search and select products...' : ''"
                                       class="flex-1 min-w-30 border-0 bg-transparent p-0 text-sm text-neutral-900 placeholder-neutral-400 focus:ring-0 focus:outline-none"
                                       autocomplete="off">
                                {{-- Loading / chevron indicator --}}
                                <div class="shrink-0 ml-auto pl-1">
                                    <template x-if="loading">
                                        <svg class="w-4 h-4 animate-spin text-neutral-600" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                        </svg>
                                    </template>
                                    <template x-if="!loading">
                                        <svg class="w-4 h-4 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                        </svg>
                                    </template>
                                </div>
                            </div>

                            {{-- Dropdown results --}}
                            <div x-show="showDropdown" x-cloak x-transition.opacity.duration.150ms
                                 class="absolute z-50 mt-1 w-full bg-white border border-neutral-200 rounded-lg shadow-lg max-h-52 overflow-y-auto">
                                <template x-if="!loading && results.length === 0 && search.length >= 2">
                                    <div class="px-3 py-2.5 text-sm text-neutral-600 flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M12 2a10 10 0 100 20 10 10 0 000-20z"/>
                                        </svg>
                                        No products found for "<span x-text="search" class="font-medium text-neutral-600"></span>"
                                    </div>
                                </template>
                                <template x-for="(product, index) in results" :key="product.id">
                                    <button type="button" @click="add(product)"
                                            class="w-full text-left px-3 py-2 text-sm text-neutral-700 hover:bg-primary-50 hover:text-primary-700 transition-colors flex items-center gap-2"
                                            :class="index < results.length - 1 ? 'border-b border-neutral-100' : ''">
                                        <svg class="w-4 h-4 text-neutral-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                        <span x-text="product.name"></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        @error('applicable_products')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Applicable Categories --}}
                <div class="card" x-data="{
                    selected: @json(old('applicable_categories', [])),
                    categories: @json($categories)
                }">
                    <div class="card-header">
                        <h2 class="font-semibold text-neutral-900">Applicable Categories</h2>
                    </div>
                    <div class="p-4 space-y-3">
                        <p class="text-xs text-neutral-600">Leave empty to apply to all categories.</p>

                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 max-h-48 overflow-y-auto">
                            <template x-for="cat in categories" :key="cat.id">
                                <label class="inline-flex items-center gap-2 text-sm cursor-pointer">
                                    <input type="checkbox" name="applicable_categories[]"
                                           :value="cat.id"
                                           :checked="selected.includes(cat.id)"
                                           class="rounded border-neutral-300 text-primary-600 focus:ring-primary-500">
                                    <span x-text="cat.name" class="text-neutral-700 truncate"></span>
                                </label>
                            </template>
                        </div>

                        @error('applicable_categories')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                {{-- Schedule --}}
                <div class="card">
                    <div class="card-header">
                        <h2 class="font-semibold text-neutral-900">Schedule</h2>
                    </div>
                    <div class="p-4 space-y-4">
                        <div>
                            <label for="starts_at" class="form-label">Starts At</label>
                            <input type="datetime-local" name="starts_at" id="starts_at" value="{{ old('starts_at') }}" class="form-input w-full">
                            @error('starts_at')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="expires_at" class="form-label">Expires At</label>
                            <input type="datetime-local" name="expires_at" id="expires_at" value="{{ old('expires_at') }}" class="form-input w-full">
                            @error('expires_at')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Status & Application --}}
                <div class="card">
                    <div class="card-header">
                        <h2 class="font-semibold text-neutral-900">Status & Application</h2>
                    </div>
                    <div class="p-4 space-y-4">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" id="is_active"
                                   class="rounded border-neutral-300 text-primary-600 focus:ring-primary-500"
                                   @checked(old('is_active', true))>
                            <div>
                                <span class="text-sm font-medium text-neutral-700">Active</span>
                                <p class="text-xs text-neutral-600">Coupon can be used by customers</p>
                            </div>
                        </label>

                        <div class="border-t border-neutral-100 pt-4">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="hidden" name="auto_apply" value="0">
                                <input type="checkbox" name="auto_apply" value="1" id="auto_apply"
                                       class="rounded border-neutral-300 text-primary-600 focus:ring-primary-500"
                                       @checked(old('auto_apply', false))>
                                <div>
                                    <span class="text-sm font-medium text-neutral-700">Auto Apply</span>
                                    <p class="text-xs text-neutral-600">Automatically apply when conditions match</p>
                                </div>
                            </label>
                        </div>

                        <div class="border-t border-neutral-100 pt-4">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="hidden" name="is_stackable" value="0">
                                <input type="checkbox" name="is_stackable" value="1" id="is_stackable"
                                       class="rounded border-neutral-300 text-primary-600 focus:ring-primary-500"
                                       @checked(old('is_stackable', false))>
                                <div>
                                    <span class="text-sm font-medium text-neutral-700">Allow Stacking</span>
                                    <p class="text-xs text-neutral-600">Can be combined with other stackable coupons</p>
                                </div>
                            </label>
                        </div>

                        <div class="border-t border-neutral-100 pt-3">
                            <p class="text-xs text-neutral-600">
                                <span class="font-medium text-neutral-600">Manual:</span> Customer enters coupon code at checkout.
                            </p>
                            <p class="text-xs text-neutral-600 mt-1">
                                <span class="font-medium text-neutral-600">Auto:</span> Applied automatically if min order amount, product, and category conditions are met.
                            </p>
                            <p class="text-xs text-neutral-600 mt-1">
                                <span class="font-medium text-neutral-600">Stackable:</span> Multiple stackable coupons can be applied together. Non-stackable coupons replace any existing.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex flex-col gap-3">
                    <button type="submit" class="btn btn-primary w-full justify-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Create Coupon
                    </button>
                    <a href="{{ route('admin.coupons.index') }}" class="btn btn-secondary w-full text-center justify-center">Cancel</a>
                </div>
            </div>
        </div>
    </form>

    @push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('productSearch', function () {
                return {
                    search: '',
                    results: [],
                    loading: false,
                    showDropdown: false,
                    focused: false,
                    selected: [],
                    selectedNames: {},
                    debounceTimer: null,
                    searchUrl: '',
                    init() {
                        this.searchUrl = this.$el.closest('[data-search-url]').dataset.searchUrl;
                        this.selected = JSON.parse(this.$el.closest('[data-selected]').dataset.selected || '[]');
                        this.selectedNames = JSON.parse(this.$el.closest('[data-selected-names]').dataset.selectedNames || '{}');
                    },
                    async fetchProducts() {
                        if (this.search.length < 2) { this.results = []; this.showDropdown = false; return; }
                        this.loading = true;
                        this.showDropdown = true;
                        try {
                            const res = await fetch(this.searchUrl + '?q=' + encodeURIComponent(this.search));
                            const data = await res.json();
                            this.results = data.filter(p => !this.selected.includes(p.id));
                        } catch (e) { this.results = []; }
                        this.loading = false;
                        if (this.results.length === 0 && !this.loading) this.showDropdown = this.search.length >= 2;
                    },
                    onSearch() {
                        clearTimeout(this.debounceTimer);
                        this.debounceTimer = setTimeout(() => this.fetchProducts(), 300);
                    },
                    add(product) {
                        if (!this.selected.includes(product.id)) {
                            this.selected.push(product.id);
                            this.selectedNames[product.id] = product.name;
                        }
                        this.search = '';
                        this.results = [];
                        this.showDropdown = false;
                        this.$refs.searchInput.focus();
                    },
                    remove(id) {
                        this.selected = this.selected.filter(i => i !== id);
                        delete this.selectedNames[id];
                    },
                    getName(id) {
                        return this.selectedNames[id] || 'Product #' + id;
                    }
                };
            });
        });
    </script>
    @endpush
</x-layouts.admin>
