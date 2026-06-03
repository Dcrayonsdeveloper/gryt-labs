<x-layouts.admin>
    <x-slot name="title">Sales Report</x-slot>

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-neutral-900">Sales Report</h1>
            <p class="text-sm text-neutral-500 mt-0.5">Overview of your sales performance</p>
        </div>
        <a href="{{ route('admin.reports.export', ['type' => 'sales', 'period' => $period]) }}"
           class="inline-flex items-center px-3.5 py-1.5 text-sm font-medium rounded-lg bg-white text-neutral-700 hover:bg-neutral-50 transition"
           style="border:1px solid #e1e1e1">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Export CSV
        </a>
    </div>

    {{-- Date filter row --}}
    <div class="flex items-center gap-2 mb-6">
        @foreach([
            7   => 'Last 7 days',
            30  => 'Last 30 days',
            90  => 'Last 90 days',
            365 => 'Last year',
        ] as $val => $label)
            <a href="{{ route('admin.reports.sales', ['period' => $val]) }}"
               class="px-3.5 py-1.5 text-sm rounded-lg font-medium transition
                   {{ $period == $val
                       ? 'bg-neutral-900 text-white'
                       : 'bg-white text-neutral-600 hover:bg-neutral-100' }}"
               style="border:1px solid {{ $period == $val ? '#171717' : '#e1e1e1' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- Stats bar --}}
    <div class="bg-white rounded-xl mb-6 overflow-hidden" style="border:1px solid #e1e1e1">
        <div class="grid grid-cols-2 lg:grid-cols-4">
            {{-- Total Revenue --}}
            <div class="px-5 py-4 lg:border-r" style="border-color:#e1e1e1">
                <p class="text-xs text-neutral-500 font-medium mb-1">Total Revenue</p>
                <div class="flex items-end justify-between gap-3">
                    <div>
                        <p class="text-2xl font-semibold text-neutral-900 leading-none">@price($stats['total_revenue'])</p>
                        @if($stats['revenue_change'] != 0)
                            <p class="text-xs mt-1 {{ $stats['revenue_change'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $stats['revenue_change'] > 0 ? '+' : '' }}{{ number_format($stats['revenue_change'], 1) }}% vs prev
                            </p>
                        @endif
                    </div>
                    <svg class="w-16 h-8 shrink-0" viewBox="0 0 64 32" fill="none" stroke-width="2">
                        <polyline points="2,28 12,22 22,24 32,14 42,16 52,8 62,12" stroke="#22c55e" fill="none" />
                    </svg>
                </div>
            </div>
            {{-- Total Orders --}}
            <div class="px-5 py-4 border-t lg:border-t-0 lg:border-r" style="border-color:#e1e1e1">
                <p class="text-xs text-neutral-500 font-medium mb-1">Total Orders</p>
                <div class="flex items-end justify-between gap-3">
                    <p class="text-2xl font-semibold text-neutral-900 leading-none">{{ number_format($stats['total_orders']) }}</p>
                    <svg class="w-16 h-8 shrink-0" viewBox="0 0 64 32" fill="none" stroke-width="2">
                        <polyline points="2,26 12,20 22,22 32,12 42,18 52,10 62,14" stroke="#a0a0a0" fill="none" />
                    </svg>
                </div>
            </div>
            {{-- Average Order --}}
            <div class="px-5 py-4 border-t lg:border-t-0 lg:border-r" style="border-color:#e1e1e1">
                <p class="text-xs text-neutral-500 font-medium mb-1">Average Order</p>
                <div class="flex items-end justify-between gap-3">
                    <p class="text-2xl font-semibold text-neutral-900 leading-none">@price($stats['average_order'])</p>
                    <svg class="w-16 h-8 shrink-0" viewBox="0 0 64 32" fill="none" stroke-width="2">
                        <polyline points="2,20 12,22 22,18 32,16 42,20 52,14 62,16" stroke="#a0a0a0" fill="none" />
                    </svg>
                </div>
            </div>
            {{-- Items Sold --}}
            <div class="px-5 py-4 border-t lg:border-t-0" style="border-color:#e1e1e1">
                <p class="text-xs text-neutral-500 font-medium mb-1">Items Sold</p>
                <div class="flex items-end justify-between gap-3">
                    <p class="text-2xl font-semibold text-neutral-900 leading-none">{{ number_format($stats['items_sold']) }}</p>
                    <svg class="w-16 h-8 shrink-0" viewBox="0 0 64 32" fill="none" stroke-width="2">
                        <polyline points="2,24 12,26 22,18 32,20 42,14 52,16 62,10" stroke="#a0a0a0" fill="none" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts row --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        {{-- Sales Chart --}}
        <div class="lg:col-span-2 bg-white rounded-xl" style="border:1px solid #e1e1e1">
            <div class="px-5 py-4 flex items-center justify-between" style="border-bottom:1px solid #e1e1e1">
                <h2 class="text-sm font-semibold text-neutral-900">Daily Sales</h2>
                <span class="text-xs text-neutral-400">Last {{ $period }} days</span>
            </div>
            <div class="p-5">
                @if($salesData->count() > 0)
                    <div style="height:260px; position:relative;">
                        <canvas id="salesChart"></canvas>
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center py-16 text-neutral-400">
                        <svg class="w-12 h-12 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        <p class="text-sm text-neutral-500">No sales data for this period</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Sales by Category --}}
        <div class="bg-white rounded-xl" style="border:1px solid #e1e1e1">
            <div class="px-5 py-4" style="border-bottom:1px solid #e1e1e1">
                <h2 class="text-sm font-semibold text-neutral-900">Sales by Category</h2>
            </div>
            <div class="p-5">
                @if($salesByCategory->count() > 0)
                    <div style="height:260px; position:relative;">
                        <canvas id="categoryChart"></canvas>
                    </div>
                @else
                    <div class="flex items-center justify-center py-12 text-neutral-500 text-sm">
                        No category data available
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Top Products --}}
    <div class="bg-white rounded-xl mb-6" style="border:1px solid #e1e1e1">
        <div class="px-5 py-4" style="border-bottom:1px solid #e1e1e1">
            <h2 class="text-sm font-semibold text-neutral-900">Top Selling Products</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr style="border-bottom:1px solid #e1e1e1">
                        <th class="px-5 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Rank</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Product</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Price</th>
                        <th class="px-5 py-3 text-right text-xs font-medium text-neutral-500 uppercase tracking-wider">Units Sold</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topProducts as $index => $product)
                        <tr class="hover:bg-neutral-50 transition-colors" style="border-bottom:1px solid #f3f3f3">
                            <td class="px-5 py-3">
                                <span class="text-sm font-semibold text-neutral-{{ $index < 3 ? '900' : '500' }}">{{ $index + 1 }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-neutral-100 rounded-lg shrink-0 overflow-hidden" style="border:1px solid #e1e1e1">
                                        @if($product->primary_image_url)
                                            <img src="{{ $product->primary_image_url }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center">
                                                <svg class="w-4 h-4 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                </svg>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-medium text-neutral-900 text-sm truncate" title="{{ $product->name }}">{{ $product->name }}</p>
                                        <p class="text-xs text-neutral-400">{{ $product->sku ?? 'N/A' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-sm text-neutral-600">@price($product->price)</td>
                            <td class="px-5 py-3 text-right">
                                <span class="text-sm font-semibold text-neutral-900">{{ number_format($product->sold ?? 0) }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-10 text-center text-neutral-500 text-sm">No sales data for this period</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($salesData->count() > 0 || $salesByCategory->count() > 0)
        @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const fontFamily = "'Poppins', 'Inter', sans-serif";

                @if($salesData->count() > 0)
                // Sales Chart - Bar + Line combo
                new Chart(document.getElementById('salesChart'), {
                    type: 'bar',
                    data: {
                        labels: @json($salesData->pluck('date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('M d'))),
                        datasets: [{
                            label: 'Revenue',
                            data: @json($salesData->pluck('revenue')->map(fn($r) => round($r, 2))),
                            backgroundColor: 'rgba(156, 0, 173, 0.15)',
                            hoverBackgroundColor: 'rgba(156, 0, 173, 0.3)',
                            borderColor: '#9c00ad',
                            borderWidth: 1,
                            borderRadius: 6,
                            borderSkipped: false,
                            yAxisID: 'y'
                        }, {
                            label: 'Orders',
                            data: @json($salesData->pluck('orders')),
                            type: 'line',
                            borderColor: '#06b6d4',
                            backgroundColor: 'rgba(6, 182, 212, 0.05)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#06b6d4',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 3,
                            pointHoverRadius: 5,
                            yAxisID: 'y1'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: {
                                position: 'top',
                                align: 'end',
                                labels: { usePointStyle: true, pointStyle: 'circle', padding: 16, font: { size: 11, family: fontFamily } }
                            }
                        },
                        scales: {
                            x: { grid: { display: false }, ticks: { font: { size: 10, family: fontFamily }, color: '#9e9e9e', maxRotation: 45 } },
                            y: {
                                position: 'left',
                                grid: { color: '#f5f5f5' },
                                ticks: { font: { size: 11, family: fontFamily }, color: '#9e9e9e', callback: v => '₹' + v.toLocaleString() }
                            },
                            y1: {
                                position: 'right',
                                grid: { drawOnChartArea: false },
                                ticks: { font: { size: 11, family: fontFamily }, color: '#9e9e9e', stepSize: 1 }
                            }
                        }
                    }
                });
                @endif

                @if($salesByCategory->count() > 0)
                // Category Chart - Doughnut
                const categoryColors = ['#9c00ad', '#d946ef', '#f472b6', '#fb923c', '#facc15', '#4ade80', '#22d3ee', '#818cf8', '#a78bfa', '#94a3b8'];
                new Chart(document.getElementById('categoryChart'), {
                    type: 'doughnut',
                    data: {
                        labels: @json($salesByCategory->pluck('name')),
                        datasets: [{
                            data: @json($salesByCategory->pluck('revenue')->map(fn($r) => round($r, 2))),
                            backgroundColor: categoryColors.slice(0, {{ $salesByCategory->count() }}),
                            borderWidth: 2,
                            borderColor: '#fff',
                            hoverOffset: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '65%',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { usePointStyle: true, pointStyle: 'circle', padding: 10, font: { size: 11, family: fontFamily } }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(ctx) {
                                        return ctx.label + ': ₹' + ctx.parsed.toLocaleString();
                                    }
                                }
                            }
                        }
                    }
                });
                @endif
            });
        </script>
        @endpush
    @endif
</x-layouts.admin>
