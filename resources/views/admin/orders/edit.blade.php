<x-layouts.admin>
    <x-slot name="title">Edit Order {{ $order->order_number }}</x-slot>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2 text-sm text-neutral-600 mb-1">
                    <a href="{{ route('admin.orders.index') }}" class="hover:text-primary-600 transition-colors">Orders</a>
                    <svg class="w-3.5 h-3.5 text-neutral-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                    <a href="{{ route('admin.orders.show', $order) }}" class="hover:text-primary-600 transition-colors">{{ $order->order_number }}</a>
                    <svg class="w-3.5 h-3.5 text-neutral-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                    <span class="text-neutral-900">Edit</span>
                </div>
                <h1 class="text-2xl font-bold text-neutral-900">Edit Order {{ $order->order_number }}</h1>
            </div>
            <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Order
            </a>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Order Items -->
            <div class="card overflow-hidden">
                <div class="px-5 py-4 border-b border-neutral-200 flex items-center justify-between">
                    <h2 class="font-semibold text-neutral-900">Order Items</h2>
                    <span class="text-sm text-neutral-500">{{ $order->items->count() }} item(s)</span>
                </div>
                <div class="divide-y divide-neutral-100">
                    @foreach($order->items as $item)
                        <div class="px-5 py-4 flex items-center gap-4">
                            <div class="w-14 h-14 rounded-lg bg-neutral-50 ring-1 ring-neutral-200 overflow-hidden shrink-0">
                                @if($item->product && $item->product->primary_image_url)
                                    <img src="{{ $item->product->primary_image_url }}" alt="{{ $item->product_name }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center">
                                        <svg class="w-5 h-5 text-neutral-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="font-medium text-neutral-900 text-sm">{{ $item->product_name }}</h3>
                                @if($item->variant_name)
                                    <p class="text-xs text-neutral-500 mt-0.5">{{ $item->variant_name }}</p>
                                @endif
                                <p class="text-xs text-neutral-500 font-mono">SKU: {{ $item->sku }}</p>
                                <p class="text-sm text-neutral-700 mt-1">{{ $order->currency }} {{ number_format($item->price, 2) }} each</p>
                            </div>
                            <div class="flex items-center gap-3 shrink-0">
                                <!-- Quantity Update -->
                                <form action="{{ route('admin.orders.update-item', [$order, $item]) }}" method="POST" class="flex items-center gap-2">
                                    @csrf
                                    @method('PUT')
                                    <label class="sr-only" for="qty-{{ $item->id }}">Qty</label>
                                    <input type="number" name="quantity" id="qty-{{ $item->id }}" value="{{ $item->quantity }}" min="1" max="100"
                                           class="form-input w-20 text-center text-sm py-1.5"
                                           onchange="this.form.submit()">
                                </form>
                                <p class="text-sm font-bold text-neutral-900 w-24 text-right">{{ $order->currency }} {{ number_format($item->total, 2) }}</p>
                                <!-- Remove Item -->
                                @if($order->items->count() > 1)
                                    <form action="{{ route('admin.orders.remove-item', [$order, $item]) }}" method="POST"
                                          onsubmit="return confirm('Remove {{ addslashes($item->product_name) }} from this order?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 text-neutral-400 hover:text-error-500 transition-colors rounded-md hover:bg-error-50" title="Remove item">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Order Totals -->
                <div class="px-5 py-4 bg-neutral-50/80 border-t border-neutral-200 space-y-2">
                    <div class="flex justify-between text-sm text-neutral-600">
                        <span>Subtotal</span>
                        <span>{{ $order->currency }} {{ number_format($order->subtotal, 2) }}</span>
                    </div>
                    @if($order->discount > 0)
                        <div class="flex justify-between text-sm text-success-600">
                            <span>Discount</span>
                            <span>-{{ $order->currency }} {{ number_format($order->discount, 2) }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between text-sm text-neutral-600">
                        <span>Shipping</span>
                        <span>{{ $order->currency }} {{ number_format($order->shipping_cost, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-sm text-neutral-600">
                        <span>Tax</span>
                        <span>{{ $order->currency }} {{ number_format($order->tax, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-base font-bold text-neutral-900 pt-2 border-t border-neutral-200">
                        <span>Total</span>
                        <span>{{ $order->currency }} {{ number_format($order->total, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Add Product -->
            <div class="card overflow-hidden" x-data="orderProductSearch()">
                <div class="px-5 py-4 border-b border-neutral-200">
                    <h2 class="font-semibold text-neutral-900">Add Product</h2>
                </div>
                <div class="p-5">
                    <div class="relative">
                        <label class="form-label">Search Products</label>
                        <input type="text" x-model="query" @input="search()" @click.away="showDropdown = false"
                               placeholder="Search by product name..." class="form-input w-full" autocomplete="off">

                        <!-- Search Results Dropdown -->
                        <div x-show="showDropdown" x-transition x-cloak
                             class="absolute z-20 w-full mt-1 bg-white rounded-lg shadow-lg border border-neutral-200 max-h-64 overflow-y-auto">
                            <template x-if="loading">
                                <div class="px-4 py-3 text-sm text-neutral-500 text-center">Searching...</div>
                            </template>
                            <template x-if="!loading && results.length === 0 && query.length >= 2">
                                <div class="px-4 py-3 text-sm text-neutral-500 text-center">No products found</div>
                            </template>
                            <template x-for="product in results" :key="product.id">
                                <button type="button" @click="addProduct(product)"
                                        class="w-full px-4 py-3 flex items-center gap-3 hover:bg-neutral-50 transition-colors text-left border-b border-neutral-100 last:border-0">
                                    <img :src="product.image" class="w-10 h-10 rounded object-cover bg-neutral-100 shrink-0" alt="">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-neutral-900 truncate" x-text="product.name"></p>
                                        <p class="text-xs text-neutral-500">
                                            SKU: <span x-text="product.sku || 'N/A'"></span>
                                            &middot; Stock: <span x-text="product.stock_quantity"></span>
                                        </p>
                                    </div>
                                    <span class="text-sm font-semibold text-neutral-900 shrink-0" x-text="'{{ $order->currency }} ' + product.price.toFixed(2)"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Hidden form for adding -->
                    <form x-ref="addForm" action="{{ route('admin.orders.add-item', $order) }}" method="POST" class="hidden">
                        @csrf
                        <input type="hidden" name="product_id" x-ref="productId">
                        <input type="hidden" name="quantity" value="1">
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Shipping Address -->
            <div class="card overflow-hidden">
                <div class="px-5 py-4 border-b border-neutral-200">
                    <h2 class="font-semibold text-neutral-900">Shipping Address</h2>
                </div>
                <div class="p-5">
                    @php $shipping = $order->shipping_address_snapshot ?? []; @endphp
                    <form action="{{ route('admin.orders.update-order', $order) }}" method="POST" class="space-y-4">
                        @csrf
                        @method('PUT')

                        <div>
                            <label class="form-label" for="shipping_name">Full Name</label>
                            <input type="text" name="shipping_name" id="shipping_name"
                                   value="{{ old('shipping_name', $shipping['name'] ?? ($shipping['first_name'] ?? '') . ' ' . ($shipping['last_name'] ?? '')) }}"
                                   class="form-input w-full" required>
                            @error('shipping_name') <p class="text-xs text-error-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="form-label" for="shipping_phone">Phone</label>
                            <input type="text" name="shipping_phone" id="shipping_phone"
                                   value="{{ old('shipping_phone', $shipping['phone'] ?? '') }}"
                                   class="form-input w-full" required>
                            @error('shipping_phone') <p class="text-xs text-error-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="form-label" for="shipping_address">Address</label>
                            <textarea name="shipping_address" id="shipping_address" rows="2"
                                      class="form-textarea w-full" required>{{ old('shipping_address', $shipping['address'] ?? $shipping['address_line_1'] ?? '') }}</textarea>
                            @error('shipping_address') <p class="text-xs text-error-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="form-label" for="shipping_city">City</label>
                                <input type="text" name="shipping_city" id="shipping_city"
                                       value="{{ old('shipping_city', $shipping['city'] ?? '') }}"
                                       class="form-input w-full" required>
                                @error('shipping_city') <p class="text-xs text-error-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="form-label" for="shipping_state">State</label>
                                <input type="text" name="shipping_state" id="shipping_state"
                                       value="{{ old('shipping_state', $shipping['state'] ?? '') }}"
                                       class="form-input w-full" required>
                                @error('shipping_state') <p class="text-xs text-error-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="form-label" for="shipping_postal_code">Postal Code</label>
                            <input type="text" name="shipping_postal_code" id="shipping_postal_code"
                                   value="{{ old('shipping_postal_code', $shipping['postal_code'] ?? $shipping['zip'] ?? '') }}"
                                   class="form-input w-full" required>
                            @error('shipping_postal_code') <p class="text-xs text-error-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="form-label" for="admin_notes">Admin Notes</label>
                            <textarea name="admin_notes" id="admin_notes" rows="3"
                                      class="form-textarea w-full" placeholder="Internal notes...">{{ old('admin_notes', $order->admin_notes) }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-primary w-full">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Save Changes
                        </button>
                    </form>
                </div>
            </div>

            <!-- Order Info -->
            <div class="card overflow-hidden">
                <div class="px-5 py-4 border-b border-neutral-200">
                    <h2 class="font-semibold text-neutral-900">Order Info</h2>
                </div>
                <div class="p-5 space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-neutral-600">Order Number</span>
                        <span class="font-mono font-medium">{{ $order->order_number }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-neutral-600">Status</span>
                        @php
                            $statusClass = match($order->status) {
                                'confirmed' => 'badge-warning',
                                'processing', 'packed' => 'badge-info',
                                default => 'badge-neutral',
                            };
                        @endphp
                        <span class="badge {{ $statusClass }}">{{ ucfirst($order->status) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-neutral-600">Customer</span>
                        <span class="font-medium">{{ $order->user?->full_name ?? $order->guest_name ?? 'Guest' }}</span>
                    </div>
                    @if($order->guest_email || $order->user?->email)
                    <div class="flex justify-between">
                        <span class="text-neutral-600">Email</span>
                        <span>{{ $order->user?->email ?? $order->guest_email }}</span>
                    </div>
                    @endif
                    @if($order->guest_phone || $order->user?->phone)
                    <div class="flex justify-between">
                        <span class="text-neutral-600">Phone</span>
                        <span>{{ $order->user?->phone ?? $order->guest_phone }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between">
                        <span class="text-neutral-600">Date</span>
                        <span>{{ $order->created_at->format('M d, Y') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function orderProductSearch() {
            return {
                query: '',
                results: [],
                loading: false,
                showDropdown: false,
                debounce: null,

                search() {
                    clearTimeout(this.debounce);
                    this.debounce = setTimeout(async () => {
                        if (this.query.length < 2) {
                            this.results = [];
                            this.showDropdown = false;
                            return;
                        }
                        this.loading = true;
                        this.showDropdown = true;
                        try {
                            const res = await fetch('{{ route("admin.search.products") }}?q=' + encodeURIComponent(this.query));
                            this.results = await res.json();
                        } catch (e) {
                            this.results = [];
                        }
                        this.loading = false;
                    }, 300);
                },

                addProduct(product) {
                    this.$refs.productId.value = product.id;
                    this.$refs.addForm.submit();
                }
            };
        }
    </script>
    @endpush
</x-layouts.admin>
