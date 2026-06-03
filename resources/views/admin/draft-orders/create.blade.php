<x-layouts.admin>
    <x-slot name="title">Create Draft Order</x-slot>

    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm text-neutral-600 mb-1">
            <a href="{{ route('admin.draft-orders.index') }}" class="hover:text-primary-600">Draft Orders</a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-neutral-900">Create</span>
        </div>
        <h1 class="text-2xl font-bold text-neutral-900">Create Draft Order</h1>
    </div>

    @if(session('error'))
        <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    <form action="{{ route('admin.draft-orders.store') }}" method="POST"
          x-data="draftOrderForm()"
          @submit.prevent="submitForm($event)">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Content --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- Products --}}
                <div class="card">
                    <div class="card-header">
                        <h2 class="font-semibold text-neutral-900">Products</h2>
                    </div>
                    <div class="p-4 space-y-4">
                        {{-- Product Search --}}
                        <div class="relative" @click.outside="showProductDropdown = false">
                            <div class="relative">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                <input type="text" x-model="productSearch" @input="searchProducts()" @focus="if(productResults.length) showProductDropdown = true"
                                       class="w-full pl-9 pr-4 py-2.5 text-sm border border-neutral-200 rounded-lg focus:ring-1 focus:ring-neutral-300"
                                       placeholder="Search products to add..." autocomplete="off">
                            </div>
                            <div x-show="showProductDropdown" x-cloak x-transition.opacity
                                 class="absolute z-50 mt-1 w-full bg-white border border-neutral-200 rounded-lg shadow-lg max-h-52 overflow-y-auto">
                                <template x-if="productLoading">
                                    <div class="px-3 py-3 text-sm text-neutral-500 text-center">Searching...</div>
                                </template>
                                <template x-if="!productLoading && productResults.length === 0 && productSearch.length >= 2">
                                    <div class="px-3 py-3 text-sm text-neutral-500">No products found</div>
                                </template>
                                <template x-for="product in productResults" :key="product.id">
                                    <button type="button" @click="addProduct(product)"
                                            class="w-full text-left px-3 py-2.5 text-sm text-neutral-700 hover:bg-primary-50 hover:text-primary-700 border-b border-neutral-100 last:border-0 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-neutral-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                        <span x-text="product.name"></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        {{-- Line Items --}}
                        <template x-if="items.length === 0">
                            <div class="text-center py-8 text-neutral-400">
                                <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                <p class="text-sm">Search and add products above</p>
                            </div>
                        </template>

                        <template x-if="items.length > 0">
                            <div class="space-y-3">
                                <template x-for="(item, index) in items" :key="index">
                                    <div class="flex items-center gap-3 p-3 bg-neutral-50 rounded-lg">
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-neutral-900 truncate" :title="item.product_name" x-text="item.product_name"></p>
                                            <input type="hidden" :name="'items['+index+'][product_id]'" :value="item.product_id">
                                            <input type="hidden" :name="'items['+index+'][variant_id]'" :value="item.variant_id || ''">
                                        </div>
                                        <div class="w-20">
                                            <input type="number" :name="'items['+index+'][quantity]'" x-model.number="item.quantity"
                                                   @input="recalculate()" min="1" max="999"
                                                   class="w-full text-sm border border-neutral-200 rounded-lg px-2.5 py-1.5 text-center">
                                        </div>
                                        <div class="w-28">
                                            <input type="number" :name="'items['+index+'][price]'" x-model.number="item.price"
                                                   @input="recalculate()" step="0.01" min="0"
                                                   class="w-full text-sm border border-neutral-200 rounded-lg px-2.5 py-1.5 text-right">
                                        </div>
                                        <div class="w-24 text-right">
                                            <span class="text-sm font-medium text-neutral-900" x-text="formatPrice(item.quantity * item.price)"></span>
                                        </div>
                                        <button type="button" @click="removeItem(index)" class="text-neutral-400 hover:text-red-500 p-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                </template>

                                {{-- Column Headers --}}
                                <div class="flex items-center gap-3 px-3 text-xs text-neutral-500">
                                    <div class="flex-1">Product</div>
                                    <div class="w-20 text-center">Qty</div>
                                    <div class="w-28 text-right">Price</div>
                                    <div class="w-24 text-right">Total</div>
                                    <div class="w-6"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Payment --}}
                <div class="card">
                    <div class="card-header">
                        <h2 class="font-semibold text-neutral-900">Payment</h2>
                    </div>
                    <div class="p-4 space-y-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-neutral-600">Subtotal</span>
                            <span class="font-medium" x-text="formatPrice(subtotal)"></span>
                        </div>
                        <div class="flex items-center gap-3">
                            <label class="text-sm text-neutral-600 shrink-0">Discount</label>
                            <input type="number" name="discount" x-model.number="discount" @input="recalculate()"
                                   step="0.01" min="0" class="form-input w-32 text-sm text-right ml-auto" placeholder="0.00">
                        </div>
                        <div class="flex items-center gap-3">
                            <label class="text-sm text-neutral-600 shrink-0">Shipping</label>
                            <input type="number" name="shipping_cost" x-model.number="shippingCost" @input="recalculate()"
                                   step="0.01" min="0" class="form-input w-32 text-sm text-right ml-auto" placeholder="0.00">
                        </div>
                        <div class="flex items-center gap-3">
                            <label class="text-sm text-neutral-600 shrink-0">Tax</label>
                            <input type="number" name="tax" x-model.number="tax" @input="recalculate()"
                                   step="0.01" min="0" class="form-input w-32 text-sm text-right ml-auto" placeholder="0.00">
                        </div>
                        <div class="flex items-center justify-between text-sm font-semibold border-t border-neutral-200 pt-3">
                            <span>Total</span>
                            <span class="text-lg" x-text="formatPrice(total)"></span>
                        </div>
                    </div>
                </div>

                {{-- Notes --}}
                <div class="card">
                    <div class="card-header">
                        <h2 class="font-semibold text-neutral-900">Notes</h2>
                    </div>
                    <div class="p-4">
                        <textarea name="notes" rows="3" class="form-textarea w-full text-sm" placeholder="Internal notes about this order...">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                {{-- Customer --}}
                <div class="card">
                    <div class="card-header">
                        <h2 class="font-semibold text-neutral-900">Customer</h2>
                    </div>
                    <div class="p-4 space-y-4">
                        {{-- Customer Search --}}
                        <div class="relative" @click.outside="showCustomerDropdown = false">
                            <input type="text" x-model="customerSearch" @input="searchCustomers()" @focus="if(customerResults.length) showCustomerDropdown = true"
                                   class="form-input w-full text-sm" placeholder="Search existing customer..." autocomplete="off">
                            <div x-show="showCustomerDropdown" x-cloak x-transition.opacity
                                 class="absolute z-50 mt-1 w-full bg-white border border-neutral-200 rounded-lg shadow-lg max-h-40 overflow-y-auto">
                                <template x-for="cust in customerResults" :key="cust.id">
                                    <button type="button" @click="selectCustomer(cust)"
                                            class="w-full text-left px-3 py-2 text-sm text-neutral-700 hover:bg-primary-50 border-b border-neutral-100 last:border-0">
                                        <p class="font-medium" x-text="cust.name"></p>
                                        <p class="text-xs text-neutral-500" x-text="cust.email"></p>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <input type="hidden" name="customer_id" x-model="customerId">

                        <template x-if="customerId">
                            <div class="flex items-center justify-between px-3 py-2 bg-primary-50 rounded-lg">
                                <div>
                                    <p class="text-sm font-medium text-primary-800" x-text="customerName"></p>
                                    <p class="text-xs text-primary-600" x-text="customerEmail"></p>
                                </div>
                                <button type="button" @click="clearCustomer()" class="text-xs text-primary-600 hover:text-primary-800">Clear</button>
                            </div>
                        </template>

                        <div class="border-t border-neutral-100 pt-4 space-y-3">
                            <p class="text-xs text-neutral-500 font-medium">Or enter manually:</p>
                            <div>
                                <label class="form-label text-xs">Name</label>
                                <input type="text" name="customer_name" x-model="customerName" class="form-input w-full text-sm" placeholder="Customer name">
                            </div>
                            <div>
                                <label class="form-label text-xs">Email</label>
                                <input type="email" name="customer_email" x-model="customerEmail" class="form-input w-full text-sm" placeholder="customer@example.com">
                            </div>
                            <div>
                                <label class="form-label text-xs">Phone</label>
                                <input type="text" name="customer_phone" x-model="customerPhone" class="form-input w-full text-sm" placeholder="+91...">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex flex-col gap-3">
                    <button type="submit" @click="action = 'save'" class="btn btn-primary w-full justify-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Save as Draft
                    </button>
                    <button type="submit" @click="action = 'send'" class="btn btn-secondary w-full justify-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        Save & Send Invoice
                    </button>
                    <a href="{{ route('admin.draft-orders.index') }}" class="btn btn-secondary w-full text-center justify-center">Cancel</a>
                </div>
            </div>
        </div>

        <input type="hidden" name="action" x-model="action">
    </form>

    @push('scripts')
    <script>
    function draftOrderForm() {
        return {
            items: [],
            discount: 0,
            shippingCost: 0,
            tax: 0,
            subtotal: 0,
            total: 0,
            action: 'save',

            // Product search
            productSearch: '',
            productResults: [],
            productLoading: false,
            showProductDropdown: false,
            productDebounce: null,

            // Customer search
            customerSearch: '',
            customerResults: [],
            showCustomerDropdown: false,
            customerDebounce: null,
            customerId: '',
            customerName: '',
            customerEmail: '',
            customerPhone: '',

            searchProducts() {
                clearTimeout(this.productDebounce);
                this.productDebounce = setTimeout(async () => {
                    if (this.productSearch.length < 2) {
                        this.productResults = [];
                        this.showProductDropdown = false;
                        return;
                    }
                    this.productLoading = true;
                    this.showProductDropdown = true;
                    try {
                        const res = await fetch('{{ route("admin.search.products") }}?q=' + encodeURIComponent(this.productSearch));
                        this.productResults = await res.json();
                    } catch (e) {
                        this.productResults = [];
                    }
                    this.productLoading = false;
                }, 300);
            },

            addProduct(product) {
                // Check if already added
                const existing = this.items.find(i => i.product_id === product.id);
                if (existing) {
                    existing.quantity++;
                    this.recalculate();
                } else {
                    this.items.push({
                        product_id: product.id,
                        variant_id: null,
                        product_name: product.name,
                        quantity: 1,
                        price: product.price || 0,
                    });
                    this.recalculate();
                }
                this.productSearch = '';
                this.productResults = [];
                this.showProductDropdown = false;
            },

            removeItem(index) {
                this.items.splice(index, 1);
                this.recalculate();
            },

            recalculate() {
                this.subtotal = this.items.reduce((sum, item) => sum + (item.quantity * item.price), 0);
                this.total = Math.max(0, this.subtotal - (this.discount || 0) + (this.shippingCost || 0) + (this.tax || 0));
            },

            formatPrice(amount) {
                return new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR', minimumFractionDigits: 0, maximumFractionDigits: 2 }).format(amount || 0);
            },

            searchCustomers() {
                clearTimeout(this.customerDebounce);
                this.customerDebounce = setTimeout(async () => {
                    if (this.customerSearch.length < 2) {
                        this.customerResults = [];
                        this.showCustomerDropdown = false;
                        return;
                    }
                    try {
                        const res = await fetch('{{ route("admin.draft-orders.search-customers") }}?q=' + encodeURIComponent(this.customerSearch));
                        this.customerResults = await res.json();
                        this.showCustomerDropdown = this.customerResults.length > 0;
                    } catch (e) {
                        this.customerResults = [];
                    }
                }, 300);
            },

            selectCustomer(cust) {
                this.customerId = cust.id;
                this.customerName = cust.name;
                this.customerEmail = cust.email || '';
                this.customerPhone = cust.phone || '';
                this.customerSearch = '';
                this.customerResults = [];
                this.showCustomerDropdown = false;
            },

            clearCustomer() {
                this.customerId = '';
                this.customerName = '';
                this.customerEmail = '';
                this.customerPhone = '';
            },

            submitForm(e) {
                if (this.items.length === 0) {
                    alert('Please add at least one product.');
                    return;
                }
                e.target.submit();
            }
        };
    }
    </script>
    @endpush
</x-layouts.admin>
