<x-layouts.admin>
    <x-slot name="title">Low Stock</x-slot>

    <div class="flex items-center justify-between mb-6">
        <div>
            <div class="flex items-center gap-2 text-sm text-neutral-600 mb-1">
                <a href="{{ route('admin.inventory.index') }}" class="hover:text-primary-600">Inventory</a>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <span class="text-neutral-900">Low Stock</span>
            </div>
            <h1 class="text-2xl font-bold text-neutral-900">Low Stock Products</h1>
            <p class="text-neutral-600">Products below their low stock threshold</p>
        </div>
        <a href="{{ route('admin.inventory.index') }}" class="btn btn-secondary">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to Inventory
        </a>
    </div>

    @if($products->total() > 0)
        <div class="card px-5 py-4 mb-6 border-l-4 border-warning-400 bg-warning-50">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-warning-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <p class="text-sm font-medium text-warning-800">
                    <span class="font-bold">{{ $products->total() }}</span> {{ Str::plural('product', $products->total()) }} running low on stock. Consider restocking soon.
                </p>
            </div>
        </div>
    @endif

    <div class="card overflow-hidden">
        @if($products->total() > 0)
            <div class="px-5 py-3 border-b border-neutral-200">
                <p class="text-sm text-neutral-600">
                    Showing <span class="font-medium text-neutral-900">{{ $products->firstItem() }}</span>–<span class="font-medium text-neutral-900">{{ $products->lastItem() }}</span> of <span class="font-medium text-neutral-900">{{ $products->total() }}</span> products
                </p>
            </div>
        @endif
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-neutral-50 border-b border-neutral-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-600 uppercase tracking-wide">Product</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-600 uppercase tracking-wide">SKU</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-neutral-600 uppercase tracking-wide">Stock</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-neutral-600 uppercase tracking-wide">Threshold</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-600 uppercase tracking-wide">Level</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-neutral-600 uppercase tracking-wide">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @forelse($products as $product)
                        @php
                            $pct = $product->low_stock_threshold > 0
                                ? min(100, round(($product->stock_quantity / $product->low_stock_threshold) * 100))
                                : 0;
                        @endphp
                        <tr class="hover:bg-neutral-50 transition-colors">
                            <td class="px-4 py-3">
                                <span class="text-sm font-medium text-neutral-900">{{ $product->name }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-xs font-mono bg-neutral-100 text-neutral-600 px-2 py-0.5 rounded">{{ $product->sku ?? '—' }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="text-sm font-bold text-warning-600">{{ $product->stock_quantity }}</span>
                            </td>
                            <td class="px-4 py-3 text-center text-sm text-neutral-600">{{ $product->low_stock_threshold ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2 min-w-24">
                                    <div class="flex-1 h-1.5 bg-neutral-200 rounded-full overflow-hidden">
                                        <div class="h-full bg-warning-500 rounded-full" style="width: {{ $pct }}%"></div>
                                    </div>
                                    <span class="text-xs text-neutral-600 shrink-0">{{ $pct }}%</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button onclick="openStockModal({{ $product->id }}, '{{ addslashes($product->name) }}', {{ $product->stock_quantity }})"
                                        class="btn btn-secondary btn-xs gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                    </svg>
                                    Restock
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-16 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <div class="w-12 h-12 bg-success-100 rounded-full flex items-center justify-center">
                                        <svg class="w-6 h-6 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <p class="text-sm font-medium text-neutral-900">All stocked up!</p>
                                    <p class="text-xs text-neutral-600">No products are below their low stock threshold.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($products->hasPages())
            <div class="px-4 py-3 border-t border-neutral-200">
                {{ $products->links() }}
            </div>
        @endif
    </div>

    <!-- Stock Modal (same as index) -->
    <div x-data="{ open: false, productId: null, productName: '', currentStock: 0 }"
         x-on:open-stock-modal.window="open = true; productId = $event.detail.id; productName = $event.detail.name; currentStock = $event.detail.stock"
         x-show="open" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="fixed inset-0 bg-black/50" x-on:click="open = false"></div>
        <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md mx-4" x-transition>
            <div class="px-6 py-4 border-b border-neutral-200 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-neutral-900">Restock Product</h3>
                    <p class="text-xs text-neutral-600 mt-0.5" x-text="productName"></p>
                </div>
                <button type="button" x-on:click="open = false" class="btn-icon">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="px-6 py-3 bg-neutral-50 border-b border-neutral-100">
                <div class="flex items-center justify-between">
                    <span class="text-xs text-neutral-600">Current Stock</span>
                    <span class="text-sm font-bold text-warning-600" x-text="currentStock"></span>
                </div>
            </div>
            <form method="POST" x-bind:action="'/admin/inventory/' + productId + '/stock'">
                @csrf
                @method('PUT')
                <div class="p-6 space-y-4">
                    <div>
                        <label class="form-label form-label-required">Adjustment Type</label>
                        <select name="type" class="form-select" required>
                            <option value="add">Add Stock</option>
                            <option value="set">Set Stock To</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label form-label-required">Quantity</label>
                        <input type="number" name="quantity" min="1" required class="form-input" placeholder="0">
                    </div>
                    <div>
                        <label class="form-label">Reason <span class="text-neutral-600 font-normal">(optional)</span></label>
                        <input type="text" name="reason" class="form-input" placeholder="e.g. Restock, Purchase order">
                    </div>
                </div>
                <div class="px-6 py-4 bg-neutral-50 border-t border-neutral-200 flex items-center justify-end gap-3 rounded-b-xl">
                    <button type="button" x-on:click="open = false" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary">Restock</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openStockModal(id, name, stock) {
            window.dispatchEvent(new CustomEvent('open-stock-modal', { detail: { id, name, stock } }));
        }
    </script>
</x-layouts.admin>
