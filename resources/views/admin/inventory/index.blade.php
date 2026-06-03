<x-layouts.admin>
    <x-slot name="title">Inventory</x-slot>

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900">Inventory</h1>
            <p class="text-neutral-600">Manage product stock levels</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.inventory.movements') }}" class="btn btn-secondary btn-sm">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Movements
            </a>
            <a href="{{ route('admin.inventory.locations.index') }}" class="btn btn-secondary btn-sm">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Locations
            </a>
            <button onclick="openStockModal(null, '', 0)" class="btn btn-primary btn-sm">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Adjust Stock
            </button>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="card px-5 py-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-neutral-600">Total Products</p>
                    <p class="text-2xl font-bold text-neutral-900">{{ $stats['total'] }}</p>
                </div>
            </div>
        </div>
        <a href="{{ route('admin.inventory.index', ['status' => 'in_stock']) }}" class="card px-5 py-4 hover:border-success-300 transition-colors">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-success-100 rounded-lg flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-neutral-600">In Stock</p>
                    <p class="text-2xl font-bold text-success-600">{{ $stats['in_stock'] }}</p>
                </div>
            </div>
        </a>
        <a href="{{ route('admin.inventory.low-stock') }}" class="card px-5 py-4 hover:border-warning-300 transition-colors">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-warning-100 rounded-lg flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-warning-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-neutral-600">Low Stock</p>
                    <p class="text-2xl font-bold text-warning-600">{{ $stats['low_stock'] }}</p>
                </div>
            </div>
        </a>
        <a href="{{ route('admin.inventory.out-of-stock') }}" class="card px-5 py-4 hover:border-error-300 transition-colors">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-error-100 rounded-lg flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-error-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-neutral-600">Out of Stock</p>
                    <p class="text-2xl font-bold text-error-600">{{ $stats['out_of_stock'] }}</p>
                </div>
            </div>
        </a>
    </div>

    <!-- Filters -->
    <div class="card mb-6" x-data="{ open: {{ request()->hasAny(['search', 'status']) ? 'true' : 'false' }} }">
        <div class="px-5 py-3 flex items-center justify-between cursor-pointer" @click="open = !open">
            <div class="flex items-center gap-2 text-sm font-medium text-neutral-700">
                <svg class="w-4 h-4 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
                Filters & Search
                @if(request()->hasAny(['search', 'status']))
                    <span class="badge badge-primary">Active</span>
                @endif
            </div>
            <svg class="w-4 h-4 text-neutral-600 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </div>
        <div x-show="open" x-cloak x-transition class="px-5 pb-4 border-t border-neutral-100">
            <form action="{{ route('admin.inventory.index') }}" method="GET" class="pt-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="lg:col-span-2">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Product name or SKU..." class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="in_stock" {{ request('status') === 'in_stock' ? 'selected' : '' }}>In Stock</option>
                            <option value="low_stock" {{ request('status') === 'low_stock' ? 'selected' : '' }}>Low Stock</option>
                            <option value="out_of_stock" {{ request('status') === 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Per Page</label>
                        <select name="per_page" class="form-select" onchange="this.form.submit()">
                            @foreach([10, 25, 50, 100] as $n)
                                <option value="{{ $n }}" {{ request('per_page', 10) == $n ? 'selected' : '' }}>{{ $n }} per page</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="flex items-center gap-2 mt-3">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        Apply
                    </button>
                    @if(request()->hasAny(['search', 'status']))
                        <a href="{{ route('admin.inventory.index') }}" class="btn btn-secondary btn-sm">Clear</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Table -->
    <div class="card overflow-hidden">
        @if($products->total() > 0)
            <div class="px-5 py-3 border-b border-neutral-200 flex items-center justify-between">
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
                        <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-600 uppercase tracking-wide">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-neutral-600 uppercase tracking-wide">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @forelse($products as $product)
                        <tr class="hover:bg-neutral-50 transition-colors">
                            <td class="px-4 py-3">
                                <span class="font-medium text-neutral-900 text-sm">{{ $product->name }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-xs font-mono bg-neutral-100 text-neutral-600 px-2 py-0.5 rounded">{{ $product->sku ?? '—' }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @php
                                    $qty = $product->stock_quantity;
                                    $threshold = $product->low_stock_threshold;
                                    $isOut = $qty <= 0;
                                    $isLow = !$isOut && $threshold && $qty <= $threshold;
                                    $qtyColor = $isOut ? 'text-error-600' : ($isLow ? 'text-warning-600' : 'text-success-700');
                                @endphp
                                <span class="text-sm font-bold {{ $qtyColor }}">{{ $qty }}</span>
                            </td>
                            <td class="px-4 py-3 text-center text-sm text-neutral-600">
                                {{ $product->low_stock_threshold ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                @if($product->stock_quantity <= 0)
                                    <span class="badge badge-error">Out of Stock</span>
                                @elseif($product->low_stock_threshold && $product->stock_quantity <= $product->low_stock_threshold)
                                    <span class="badge badge-warning">Low Stock</span>
                                @else
                                    <span class="badge badge-success">In Stock</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button onclick="openStockModal({{ $product->id }}, '{{ addslashes($product->name) }}', {{ $product->stock_quantity }})"
                                            class="btn btn-secondary btn-xs gap-1.5">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                        Adjust
                                    </button>
                                    <form action="{{ route('admin.products.destroy', $product) }}" method="POST"
                                          onsubmit="return confirm('Delete {{ addslashes($product->name) }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-secondary btn-xs gap-1.5 text-red-600 hover:bg-red-50 hover:border-red-200">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-16 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <div class="w-12 h-12 bg-neutral-100 rounded-full flex items-center justify-center">
                                        <svg class="w-6 h-6 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                    </div>
                                    <p class="text-sm font-medium text-neutral-900">No products found</p>
                                    @if(request()->hasAny(['search', 'status']))
                                        <a href="{{ route('admin.inventory.index') }}" class="text-sm text-primary-600 hover:text-primary-700">Clear filters</a>
                                    @endif
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

    <!-- Update Stock Modal -->
    <div x-data="{ open: false, productId: null, productName: '', currentStock: 0, isNew: false }"
         x-on:open-stock-modal.window="open = true; productId = $event.detail.id; productName = $event.detail.name; currentStock = $event.detail.stock; isNew = !$event.detail.id"
         x-show="open" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="fixed inset-0 bg-black/50" x-on:click="open = false"></div>
        <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md mx-4" x-transition>
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-neutral-200 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-neutral-900" x-text="isNew ? 'Adjust Stock' : 'Adjust Stock'"></h3>
                    <p class="text-xs text-neutral-600 mt-0.5" x-show="!isNew" x-text="productName"></p>
                </div>
                <button type="button" x-on:click="open = false" class="btn-icon">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Current Stock Info -->
            <div class="px-6 py-3 bg-neutral-50 border-b border-neutral-100" x-show="!isNew">
                <div class="flex items-center justify-between">
                    <span class="text-xs text-neutral-600">Current Stock</span>
                    <span class="text-sm font-bold text-neutral-900" x-text="currentStock"></span>
                </div>
            </div>

            <form method="POST" x-bind:action="'/admin/inventory/' + productId + '/stock'">
                @csrf
                @method('PUT')
                <div class="p-6 space-y-4">
                    <div x-show="isNew">
                        <label class="form-label form-label-required">Product</label>
                        <select class="form-select" x-on:change="productId = $event.target.value; let opt = $event.target.selectedOptions[0]; currentStock = opt.dataset.stock || 0" x-bind:required="isNew">
                            <option value="">Select a product...</option>
                            @foreach(\App\Models\Product::select('id', 'name', 'stock_quantity')->orderBy('name')->get() as $p)
                                <option value="{{ $p->id }}" data-stock="{{ $p->stock_quantity }}">{{ $p->name }} (Stock: {{ $p->stock_quantity }})</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-neutral-600 mt-1" x-show="productId && isNew">Current stock: <span class="font-semibold text-neutral-900" x-text="currentStock"></span></p>
                    </div>
                    <div>
                        <label class="form-label form-label-required">Adjustment Type</label>
                        <select name="type" class="form-select" required>
                            <option value="add">Add Stock</option>
                            <option value="remove">Remove Stock</option>
                            <option value="set">Set Stock To</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label form-label-required">Quantity</label>
                        <input type="number" name="quantity" min="0" required class="form-input" placeholder="0">
                    </div>
                    <div>
                        <label class="form-label">Reason <span class="text-neutral-600 font-normal">(optional)</span></label>
                        <input type="text" name="reason" class="form-input" placeholder="e.g. Restock, Damaged, Correction">
                    </div>
                </div>
                <div class="px-6 py-4 bg-neutral-50 border-t border-neutral-200 flex items-center justify-end gap-3 rounded-b-xl">
                    <button type="button" x-on:click="open = false" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Adjustment</button>
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
