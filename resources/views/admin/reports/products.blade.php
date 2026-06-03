<x-layouts.admin>
    <x-slot name="title">Product Report</x-slot>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900">Product Report</h1>
            <p class="text-sm text-neutral-600">Product performance and inventory insights</p>
        </div>
        <div class="flex items-center gap-3">
            <form action="{{ route('admin.reports.products') }}" method="GET" class="flex items-center gap-2">
                <select name="period" onchange="this.form.submit()"
                        class="form-input text-sm py-1.5">
                    <option value="7" @selected($period == 7)>Last 7 days</option>
                    <option value="30" @selected($period == 30)>Last 30 days</option>
                    <option value="90" @selected($period == 90)>Last 90 days</option>
                    <option value="365" @selected($period == 365)>Last year</option>
                </select>
            </form>
            <a href="{{ route('admin.reports.export', ['type' => 'products', 'period' => $period]) }}" class="btn btn-secondary text-sm h-9">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Export CSV
            </a>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="card px-5 py-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-xs text-neutral-600">Total Products</p>
                    <p class="text-xl font-bold text-neutral-900">{{ number_format($stats['total_products']) }}</p>
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
                <div class="min-w-0">
                    <p class="text-xs text-neutral-600">Active Products</p>
                    <p class="text-xl font-bold text-success-600">{{ number_format($stats['active_products']) }}</p>
                </div>
            </div>
        </div>
        <div class="card px-5 py-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-warning-100 rounded-lg flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-warning-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-xs text-neutral-600">Low Stock</p>
                    <p class="text-xl font-bold text-warning-600">{{ number_format($stats['low_stock']) }}</p>
                </div>
            </div>
        </div>
        <div class="card px-5 py-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-error-100 rounded-lg flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-error-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-xs text-neutral-600">Out of Stock</p>
                    <p class="text-xl font-bold text-error-600">{{ number_format($stats['out_of_stock']) }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Category Breakdown -->
        <div class="card">
            <div class="px-5 py-3.5 border-b border-neutral-200">
                <h2 class="font-semibold text-neutral-900 text-sm">Products by Category</h2>
            </div>
            <div class="p-4">
                @if($categoryBreakdown->count() > 0)
                    <canvas id="categoryChart" height="280"></canvas>
                @else
                    <div class="flex items-center justify-center py-12 text-neutral-600 text-sm">
                        No category data available
                    </div>
                @endif
            </div>
        </div>

        <!-- Inventory Alerts -->
        <div class="lg:col-span-2 card">
            <div class="px-5 py-3.5 border-b border-neutral-200 flex items-center justify-between">
                <h2 class="font-semibold text-neutral-900 text-sm">Inventory Alerts</h2>
                <a href="{{ route('admin.inventory.low-stock') }}" class="text-xs text-primary-600 hover:text-primary-700 font-medium">View All</a>
            </div>
            <div class="p-4">
                @if($stats['low_stock'] > 0 || $stats['out_of_stock'] > 0)
                    <div class="space-y-3">
                        @if($stats['out_of_stock'] > 0)
                            <div class="flex items-center gap-3 p-3 bg-error-50 rounded-lg">
                                <div class="w-10 h-10 bg-error-100 rounded-full flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 text-error-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-medium text-error-800 text-sm">{{ $stats['out_of_stock'] }} products out of stock</p>
                                    <p class="text-xs text-error-600">These products cannot be purchased</p>
                                </div>
                            </div>
                        @endif
                        @if($stats['low_stock'] > 0)
                            <div class="flex items-center gap-3 p-3 bg-warning-50 rounded-lg">
                                <div class="w-10 h-10 bg-warning-100 rounded-full flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 text-warning-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-medium text-warning-800 text-sm">{{ $stats['low_stock'] }} products low on stock</p>
                                    <p class="text-xs text-warning-600">Stock level at 10 units or below</p>
                                </div>
                            </div>
                        @endif

                        <!-- Stock overview bar -->
                        <div class="pt-3 border-t border-neutral-100">
                            @php
                                $inStock = $stats['total_products'] - $stats['out_of_stock'] - $stats['low_stock'];
                                $inStockPct = $stats['total_products'] > 0 ? ($inStock / $stats['total_products']) * 100 : 0;
                                $lowPct = $stats['total_products'] > 0 ? ($stats['low_stock'] / $stats['total_products']) * 100 : 0;
                                $outPct = $stats['total_products'] > 0 ? ($stats['out_of_stock'] / $stats['total_products']) * 100 : 0;
                            @endphp
                            <p class="text-xs text-neutral-600 mb-2">Inventory Distribution</p>
                            <div class="flex h-3 rounded-full overflow-hidden">
                                <div class="bg-success-500" style="width: {{ $inStockPct }}%"></div>
                                <div class="bg-warning-400" style="width: {{ $lowPct }}%"></div>
                                <div class="bg-error-400" style="width: {{ $outPct }}%"></div>
                            </div>
                            <div class="flex items-center gap-4 mt-2">
                                <div class="flex items-center gap-1.5">
                                    <div class="w-2.5 h-2.5 bg-success-500 rounded-full"></div>
                                    <span class="text-xs text-neutral-600">In Stock ({{ $inStock }})</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <div class="w-2.5 h-2.5 bg-warning-400 rounded-full"></div>
                                    <span class="text-xs text-neutral-600">Low ({{ $stats['low_stock'] }})</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <div class="w-2.5 h-2.5 bg-error-400 rounded-full"></div>
                                    <span class="text-xs text-neutral-600">Out ({{ $stats['out_of_stock'] }})</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="text-center py-8">
                        <div class="w-12 h-12 bg-success-100 rounded-full flex items-center justify-center mx-auto mb-3">
                            <svg class="w-6 h-6 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <p class="text-sm text-neutral-600">All products are well stocked!</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Product Performance Table -->
    <div class="card">
        <div class="px-5 py-3.5 border-b border-neutral-200">
            <h2 class="font-semibold text-neutral-900 text-sm">Product Performance</h2>
        </div>
        @if($products->total() > 0)
            <div class="px-5 py-3 border-b border-neutral-200">
                {{ $products->links('vendor.pagination.info-bar') }}
            </div>
        @endif
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-neutral-50">
                    <tr>
                        <th class="px-4 py-2.5 text-left text-xs font-medium text-neutral-600 uppercase">Product</th>
                        <th class="px-4 py-2.5 text-left text-xs font-medium text-neutral-600 uppercase">SKU</th>
                        <th class="px-4 py-2.5 text-left text-xs font-medium text-neutral-600 uppercase">Stock</th>
                        <th class="px-4 py-2.5 text-left text-xs font-medium text-neutral-600 uppercase">Price</th>
                        <th class="px-4 py-2.5 text-right text-xs font-medium text-neutral-600 uppercase">Units Sold</th>
                        <th class="px-4 py-2.5 text-right text-xs font-medium text-neutral-600 uppercase">Revenue</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @forelse($products as $product)
                        <tr class="hover:bg-neutral-50/50">
                            <td class="px-4 py-2.5">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-neutral-100 rounded-lg shrink-0 overflow-hidden">
                                        @if($product->primary_image_url)
                                            <img src="{{ $product->primary_image_url }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center">
                                                <svg class="w-4 h-4 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                </svg>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-medium text-neutral-900 text-sm truncate" title="{{ $product->name }}">{{ Str::limit($product->name, 30) }}</p>
                                        <p class="text-xs text-neutral-600">{{ $product->category->name ?? 'Uncategorized' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-2.5 text-sm text-neutral-600">{{ $product->sku ?? '-' }}</td>
                            <td class="px-4 py-2.5">
                                @if($product->stock_quantity <= 0)
                                    <span class="inline-flex px-2 py-0.5 text-xs font-medium bg-error-100 text-error-700 rounded-full">Out of stock</span>
                                @elseif($product->stock_quantity <= 10)
                                    <span class="inline-flex px-2 py-0.5 text-xs font-medium bg-warning-100 text-warning-700 rounded-full">{{ $product->stock_quantity }} left</span>
                                @else
                                    <span class="text-sm text-neutral-600">{{ $product->stock_quantity }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-2.5 text-sm text-neutral-600">@price($product->price)</td>
                            <td class="px-4 py-2.5 text-right text-sm font-semibold">{{ number_format($product->sold ?? 0) }}</td>
                            <td class="px-4 py-2.5 text-right text-sm font-semibold text-success-600">@price($product->revenue ?? 0)</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-neutral-600 text-sm">No products found</td>
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

    @if($categoryBreakdown->count() > 0)
        @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const fontFamily = "'Poppins', 'Inter', sans-serif";
                const categoryColors = ['#9c00ad', '#d946ef', '#f472b6', '#fb923c', '#facc15', '#4ade80', '#22d3ee', '#818cf8', '#a78bfa', '#94a3b8'];

                new Chart(document.getElementById('categoryChart'), {
                    type: 'bar',
                    data: {
                        labels: @json($categoryBreakdown->pluck('name')),
                        datasets: [{
                            data: @json($categoryBreakdown->pluck('count')),
                            backgroundColor: categoryColors.slice(0, {{ $categoryBreakdown->count() }}),
                            borderRadius: 6,
                            borderSkipped: false
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            x: {
                                grid: { color: '#f5f5f5' },
                                ticks: { font: { size: 11, family: fontFamily }, color: '#9e9e9e', stepSize: 1 }
                            },
                            y: {
                                grid: { display: false },
                                ticks: { font: { size: 11, family: fontFamily }, color: '#525252' }
                            }
                        }
                    }
                });
            });
        </script>
        @endpush
    @endif
</x-layouts.admin>
