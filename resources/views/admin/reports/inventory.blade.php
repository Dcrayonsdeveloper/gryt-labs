<x-layouts.admin>
    <x-slot name="title">Inventory Report</x-slot>

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <div class="flex items-center gap-2 text-sm text-neutral-600 mb-1">
                <a href="{{ route('admin.reports.analytics') }}" class="hover:text-primary-600">Reports</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <span class="text-neutral-900">Inventory</span>
            </div>
            <h1 class="text-2xl font-bold text-neutral-900">Inventory Report</h1>
            <p class="text-neutral-600">Overview of product stock levels and movements</p>
        </div>
    </div>

    <!-- Stats -->
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
                    <p class="text-2xl font-bold text-neutral-900">{{ number_format($stats['total'] ?? 0) }}</p>
                </div>
            </div>
        </div>
        <div class="card px-5 py-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-success-100 rounded-lg flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-neutral-600">Active</p>
                    <p class="text-2xl font-bold text-success-600">{{ number_format($stats['active'] ?? 0) }}</p>
                </div>
            </div>
        </div>
        <div class="card px-5 py-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-error-100 rounded-lg flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-error-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-neutral-600">Out of Stock</p>
                    <p class="text-2xl font-bold text-error-600">{{ number_format($stats['out_of_stock'] ?? 0) }}</p>
                </div>
            </div>
        </div>
        <div class="card px-5 py-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-warning-100 rounded-lg flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-warning-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-neutral-600">Low Stock</p>
                    <p class="text-2xl font-bold text-warning-600">{{ number_format($stats['low_stock'] ?? 0) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-6" x-data="{ open: {{ request()->hasAny(['search', 'stock_status', 'category']) ? 'true' : 'false' }} }">
        <div class="px-5 py-3 flex items-center justify-between cursor-pointer" @click="open = !open">
            <div class="flex items-center gap-2 text-sm font-medium text-neutral-700">
                <svg class="w-4 h-4 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
                Filters & Search
                @if(request()->hasAny(['search', 'stock_status', 'category']))
                    <span class="badge badge-primary">Active</span>
                @endif
            </div>
            <svg class="w-4 h-4 text-neutral-600 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </div>
        <div x-show="open" x-cloak x-transition class="px-5 pb-4 border-t border-neutral-100">
            <form action="{{ route('admin.reports.inventory') }}" method="GET" class="pt-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="form-label">Stock Status</label>
                        <select name="stock_status" class="form-input w-full">
                            <option value="">All Status</option>
                            <option value="in_stock" {{ request('stock_status') === 'in_stock' ? 'selected' : '' }}>In Stock</option>
                            <option value="low_stock" {{ request('stock_status') === 'low_stock' ? 'selected' : '' }}>Low Stock</option>
                            <option value="out_of_stock" {{ request('stock_status') === 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Category</label>
                        <select name="category" class="form-input w-full">
                            <option value="">All Categories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Search</label>
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="Product name or SKU..."
                               class="form-input w-full">
                    </div>
                    <div>
                        <label class="form-label">&nbsp;</label>
                        <div class="flex gap-2">
                            <button type="submit" class="btn btn-primary shrink-0" title="Apply filters">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </button>
                            @if(request()->hasAny(['search', 'stock_status', 'category']))
                                <a href="{{ route('admin.reports.inventory') }}" class="btn btn-secondary shrink-0" title="Reset filters">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Products Table -->
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
                        <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-600 uppercase tracking-wide">Product Name</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-600 uppercase tracking-wide">SKU</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-600 uppercase tracking-wide">Category</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-neutral-600 uppercase tracking-wide">
                            <a href="{{ route('admin.reports.inventory', array_merge(request()->query(), [
                                'sort' => 'stock_quantity',
                                'direction' => request('sort') === 'stock_quantity' && request('direction') === 'asc' ? 'desc' : 'asc'
                            ])) }}" class="inline-flex items-center gap-1 hover:text-neutral-700">
                                Stock Qty
                                @if(request('sort') === 'stock_quantity')
                                    <svg class="w-3 h-3 {{ request('direction') === 'desc' ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                    </svg>
                                @else
                                    <svg class="w-3 h-3 text-neutral-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                                    </svg>
                                @endif
                            </a>
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-neutral-600 uppercase tracking-wide">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-600 uppercase tracking-wide">Last Movement</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @forelse($products as $product)
                        <tr class="hover:bg-neutral-50/60 transition-colors">
                            <td class="px-4 py-3">
                                <span class="font-medium text-neutral-900 text-sm">{{ $product->name }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-xs font-mono bg-neutral-100 text-neutral-600 px-2 py-0.5 rounded">{{ $product->sku ?? '—' }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-neutral-600">
                                {{ $product->category->name ?? '—' }}
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
                            <td class="px-4 py-3 text-center">
                                @if($product->stock_quantity <= 0)
                                    <span class="badge badge-error">Out of Stock</span>
                                @elseif($product->low_stock_threshold && $product->stock_quantity <= $product->low_stock_threshold)
                                    <span class="badge badge-warning">Low Stock</span>
                                @else
                                    <span class="badge badge-success">In Stock</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-neutral-600">
                                @if($product->last_movement_at)
                                    {{ \Carbon\Carbon::parse($product->last_movement_at)->format('M d, Y') }}
                                @else
                                    <span class="text-neutral-600">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-16 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 rounded-full bg-neutral-100 flex items-center justify-center mb-4">
                                        <svg class="w-8 h-8 text-neutral-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                    </div>
                                    <h3 class="text-lg font-semibold text-neutral-900 mb-1">No products found</h3>
                                    <p class="text-sm text-neutral-600">
                                        @if(request()->hasAny(['search', 'stock_status', 'category']))
                                            Try adjusting your filters to find what you're looking for.
                                        @else
                                            Product inventory data will appear here.
                                        @endif
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($products->hasPages())
            <div class="px-5 py-3 border-t border-neutral-200">
                {{ $products->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
