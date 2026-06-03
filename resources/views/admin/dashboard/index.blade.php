<x-layouts.admin>
    <x-slot name="title">Dashboard</x-slot>

    <x-slot name="header">
        <h1 class="text-xl font-semibold text-neutral-900">Home</h1>
    </x-slot>

    {{-- Date Filter Pills --}}
    <div class="flex items-center gap-2 mb-5" x-data>
        <form method="GET" action="{{ route('admin.dashboard') }}" x-ref="filterForm" class="flex items-center gap-2">
            <input type="hidden" name="start_date" x-ref="startDate" value="{{ request('start_date') }}">
            <input type="hidden" name="end_date" x-ref="endDate" value="{{ request('end_date') }}">
            @php
                $isToday = request('start_date') == today()->format('Y-m-d') && request('end_date') == today()->format('Y-m-d');
                $is7d = request('start_date') == now()->subDays(6)->format('Y-m-d') && request('end_date') == today()->format('Y-m-d');
                $is30d = request('start_date') == now()->subDays(29)->format('Y-m-d') && request('end_date') == today()->format('Y-m-d');
                $isMonth = request('start_date') == now()->startOfMonth()->format('Y-m-d') && request('end_date') == today()->format('Y-m-d');
                $isYear = request('start_date') == now()->startOfYear()->format('Y-m-d') && request('end_date') == today()->format('Y-m-d');
                $noFilter = !$hasDateFilter;
                $pillBase = 'px-3 py-1.5 text-sm font-medium rounded-lg transition-colors cursor-pointer border';
                $pillActive = 'bg-gray-900 text-white border-gray-900';
                $pillNormal = 'bg-white text-gray-800 border-gray-300 hover:bg-gray-50';
            @endphp
            <button type="button" @click="$refs.startDate.value='{{ today()->format('Y-m-d') }}';$refs.endDate.value='{{ today()->format('Y-m-d') }}';$refs.filterForm.submit()"
                    class="{{ $pillBase }} {{ $isToday ? $pillActive : $pillNormal }}">Today</button>
            <button type="button" @click="$refs.startDate.value='{{ now()->subDays(6)->format('Y-m-d') }}';$refs.endDate.value='{{ today()->format('Y-m-d') }}';$refs.filterForm.submit()"
                    class="{{ $pillBase }} {{ $is7d ? $pillActive : $pillNormal }}">7 Days</button>
            <button type="button" @click="$refs.startDate.value='{{ now()->subDays(29)->format('Y-m-d') }}';$refs.endDate.value='{{ today()->format('Y-m-d') }}';$refs.filterForm.submit()"
                    class="{{ $pillBase }} {{ $is30d || $noFilter ? $pillActive : $pillNormal }}">30 Days</button>
            <button type="button" @click="$refs.startDate.value='{{ now()->startOfMonth()->format('Y-m-d') }}';$refs.endDate.value='{{ today()->format('Y-m-d') }}';$refs.filterForm.submit()"
                    class="{{ $pillBase }} {{ $isMonth ? $pillActive : $pillNormal }}">This Month</button>
            <button type="button" @click="$refs.startDate.value='{{ now()->startOfYear()->format('Y-m-d') }}';$refs.endDate.value='{{ today()->format('Y-m-d') }}';$refs.filterForm.submit()"
                    class="{{ $pillBase }} {{ $isYear ? $pillActive : $pillNormal }}">This Year</button>
        </form>
    </div>

    {{-- KPI Cards --}}
    @php
        // Build sparkline path strings server-side (cheap, keeps JS minimal).
        $buildSparkPath = function (array $points, int $width = 72, int $height = 20, int $pad = 2) {
            $points = array_values(array_map('floatval', $points));
            $n = count($points);
            if ($n === 0) return '';
            if ($n === 1) {
                $y = $height / 2;
                return 'M' . $pad . ',' . round($y, 2) . ' L' . ($width - $pad) . ',' . round($y, 2);
            }
            $min = min($points);
            $max = max($points);
            $range = ($max - $min) > 0 ? ($max - $min) : 1;
            $innerW = $width - ($pad * 2);
            $innerH = $height - ($pad * 2);
            $stepX = $innerW / ($n - 1);
            $parts = [];
            foreach ($points as $i => $v) {
                $x = round($pad + ($i * $stepX), 2);
                $y = round($pad + $innerH - ((($v - $min) / $range) * $innerH), 2);
                $parts[] = ($i === 0 ? 'M' : 'L') . $x . ',' . $y;
            }
            return implode(' ', $parts);
        };

        $kpis = [
            [
                'label' => 'Conversion Rate',
                'value' => $conversionWindowCurrent . '%',
                'previous' => $conversionWindowPrevious . '%',
                'delta' => $conversionWindowDelta,
                'suffix' => '%',
                'spark' => $sparkConversion,
            ],
            [
                'label' => 'Avg Order Value',
                'value' => '₹' . number_format($aovWindowCurrent),
                'previous' => '₹' . number_format($aovWindowPrevious),
                'delta' => $aovWindowDelta,
                'suffix' => '%',
                'spark' => $sparkAov,
            ],
            [
                'label' => 'Return Rate',
                'value' => $returnRateWindowCurrent . '%',
                'previous' => $returnRateWindowPrevious . '%',
                'delta' => $returnRateWindowDelta,
                'suffix' => 'pp',
                'invert' => true,
                'spark' => $sparkReturnRate,
            ],
            [
                'label' => 'Repeat Customer Rate',
                'value' => $repeatRateWindowCurrent . '%',
                'previous' => $repeatRateWindowPrevious . '%',
                'delta' => $repeatRateWindowDelta,
                'suffix' => 'pp',
                'spark' => $sparkRepeatRate,
            ],
        ];
        $compareDefault = request('compare') === '1' ? 'true' : 'false';
    @endphp
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-5" x-data="{ compare: {{ $compareDefault }} }">
        <div class="col-span-2 lg:col-span-4 flex items-center justify-end gap-2 -mb-2">
            <span class="text-xs font-medium text-gray-600">Compare to previous period</span>
            <button type="button"
                    @click="compare = !compare"
                    :aria-pressed="compare"
                    aria-label="Toggle previous-period comparison"
                    class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors"
                    :class="compare ? 'bg-gray-900' : 'bg-gray-300'">
                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                      :class="compare ? 'translate-x-4' : 'translate-x-0.5'"></span>
            </button>
        </div>
        @foreach($kpis as $kpi)
            @php
                $isPositive = isset($kpi['invert']) ? $kpi['delta'] <= 0 : $kpi['delta'] >= 0;
                $deltaAbs = abs($kpi['delta']);
                $sparkColor = $kpi['delta'] == 0 ? 'text-gray-400' : ($isPositive ? 'text-green-500' : 'text-red-500');
                $sparkPath = $buildSparkPath($kpi['spark']);
            @endphp
            <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-xs text-gray-600">{{ $kpi['label'] }}</p>
                    @if($sparkPath !== '')
                        <svg class="w-[72px] h-5 shrink-0 {{ $sparkColor }}" viewBox="0 0 72 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="{{ $sparkPath }}"/>
                        </svg>
                    @endif
                </div>
                <p class="text-2xl font-semibold mt-1 text-gray-900">{{ $kpi['value'] }}</p>
                <div class="flex items-center gap-1 mt-1">
                    @if($kpi['delta'] != 0)
                        <svg class="w-3.5 h-3.5 {{ $isPositive ? 'text-green-600' : 'text-red-500' }} {{ $kpi['delta'] < 0 ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/>
                        </svg>
                        <span class="text-xs font-medium {{ $isPositive ? 'text-green-600' : 'text-red-500' }}">{{ $deltaAbs }}{{ $kpi['suffix'] }}</span>
                    @endif
                    <span class="text-xs text-gray-400">vs prev period</span>
                </div>
                <p class="text-xs mt-1 {{ $isPositive ? 'text-green-600' : 'text-red-500' }}" x-show="compare" x-cloak>
                    vs {{ $kpi['previous'] }}
                    @if($kpi['delta'] != 0)
                        ({{ $kpi['delta'] > 0 ? '+' : '-' }}{{ $deltaAbs }}{{ $kpi['suffix'] }})
                    @endif
                </p>
            </div>
        @endforeach
    </div>

    {{-- Stats Bar --}}
    <div class="bg-white overflow-hidden mb-5 rounded-xl border border-gray-200 shadow-sm">
        <div class="flex">
            <div class="flex-1 px-5 py-4">
                <p class="text-xs font-medium mb-1 text-gray-500">Orders</p>
                <p class="text-2xl font-semibold text-gray-900">{{ number_format($topOrders) }}</p>
            </div>
            <div class="flex-1 px-5 py-4 border-l border-gray-200">
                <p class="text-xs font-medium mb-1 text-gray-500">Revenue</p>
                <p class="text-2xl font-semibold text-gray-900">@price($topRevenue)</p>
            </div>
            <div class="flex-1 px-5 py-4 border-l border-gray-200">
                <p class="text-xs font-medium mb-1 text-gray-500">Customers</p>
                <p class="text-2xl font-semibold text-gray-900">{{ number_format($totalCustomers) }}</p>
            </div>
            <div class="flex-1 px-5 py-4 border-l border-gray-200">
                <p class="text-xs font-medium mb-1 text-gray-500">Confirmed</p>
                <p class="text-2xl font-semibold text-gray-900">{{ number_format($pendingOrders) }}</p>
            </div>
            <div class="flex-1 px-5 py-4 border-l border-gray-200">
                <p class="text-xs font-medium mb-1 text-gray-500">Returns</p>
                <p class="text-2xl font-semibold text-gray-900">{{ number_format($totalReturns) }}</p>
            </div>
            <div class="flex-1 px-5 py-4 border-l border-gray-200">
                <p class="text-xs font-medium mb-1 text-gray-500">Products</p>
                <p class="text-2xl font-semibold text-gray-900">{{ number_format($totalProducts) }}</p>
            </div>
        </div>
    </div>

    {{-- Charts Row --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-5">
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 shadow-sm">
            <div class="px-5 py-4 flex items-center justify-between border-b border-gray-200">
                <p class="text-sm font-semibold text-gray-900">Revenue Overview</p>
                <p class="text-xs text-gray-500">{{ $hasDateFilter ? $startDate->format('M d') . ' - ' . $endDate->format('M d, Y') : 'Last 7 Days' }}</p>
            </div>
            <div class="p-4"><canvas id="revenueChart" height="240"></canvas></div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
            <div class="px-5 py-4 border-b border-gray-200">
                <p class="text-sm font-semibold text-gray-900">Order Status</p>
            </div>
            <div class="p-4 flex items-center justify-center"><canvas id="orderStatusChart" height="220"></canvas></div>
        </div>
    </div>

    {{-- Monthly Revenue + Performance --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-5">
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 shadow-sm">
            <div class="px-5 py-4 flex items-center justify-between border-b border-gray-200">
                <p class="text-sm font-semibold text-gray-900">Monthly Revenue</p>
            </div>
            <div class="p-4"><canvas id="monthlyRevenueChart" height="200"></canvas></div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
            <div class="px-5 py-4 border-b border-gray-200">
                <p class="text-sm font-semibold text-gray-900">Performance</p>
            </div>
            <div class="p-5 space-y-5">
                @foreach([
                    ['label' => 'Completion Rate', 'value' => $completionRate, 'detail' => number_format($completedOrders).' of '.number_format($totalOrders).' delivered', 'color' => '#10b981'],
                    ['label' => 'Cancellation Rate', 'value' => $cancellationRate, 'detail' => number_format($cancelledOrders).' of '.number_format($totalOrders).' cancelled', 'color' => '#ef4444'],
                    ['label' => 'Active Products', 'value' => $productActiveRate, 'detail' => number_format($activeProducts).' of '.number_format($totalProducts).' active', 'color' => '#6366f1'],
                ] as $metric)
                <div class="flex items-center gap-4">
                    <div class="relative w-14 h-14 shrink-0">
                        <svg class="w-14 h-14 -rotate-90" viewBox="0 0 120 120">
                            <circle cx="60" cy="60" r="52" fill="none" stroke="#e5e7eb" stroke-width="8"/>
                            <circle cx="60" cy="60" r="52" fill="none" stroke="{{ $metric['color'] }}" stroke-width="8" stroke-dasharray="{{ 2*3.14159*52 }}" stroke-dashoffset="{{ 2*3.14159*52*(1-$metric['value']/100) }}" stroke-linecap="round"/>
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center"><span class="text-xs font-bold text-gray-900">{{ $metric['value'] }}%</span></div>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ $metric['label'] }}</p>
                        <p class="text-xs text-gray-500">{{ $metric['detail'] }}</p>
                    </div>
                </div>
                @endforeach
                <div class="pt-4 space-y-2 border-t border-gray-200">
                    <div class="flex justify-between text-sm"><span class="text-gray-500">Total Revenue</span><span class="font-semibold text-gray-900">@price($totalRevenue)</span></div>
                    <div class="flex justify-between text-sm"><span class="text-gray-500">Total Orders</span><span class="font-semibold text-gray-900">{{ number_format($totalOrders) }}</span></div>
                    <div class="flex justify-between text-sm"><span class="text-gray-500">Total Sellers</span><span class="font-semibold text-gray-900">{{ number_format($totalSellers) }}</span></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Orders + Top Products --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <div class="lg:col-span-2 bg-white overflow-hidden rounded-xl border border-gray-200 shadow-sm">
            <div class="px-5 py-4 flex items-center justify-between border-b border-gray-200">
                <p class="text-sm font-semibold text-gray-900">Recent Orders</p>
                <a href="{{ route('admin.orders.index') }}" class="text-xs font-medium text-blue-600 hover:text-blue-700">View all</a>
            </div>
            <table class="w-full">
                <thead><tr class="border-b border-gray-200">
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Order</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Customer</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Status</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Total</th>
                </tr></thead>
                <tbody>
                    @forelse($recentOrders as $order)
                    @php
                        $statusClasses = match($order->status) {
                            'delivered', 'completed' => 'bg-green-100 text-green-800',
                            'cancelled' => 'bg-red-100 text-red-800',
                            default => 'bg-amber-100 text-amber-800',
                        };
                    @endphp
                    <tr class="hover:bg-neutral-50 cursor-pointer border-b border-gray-100" data-order-url="{{ route('admin.orders.show', $order) }}">
                        <td class="px-4 py-3">
                            <span class="text-sm font-medium text-blue-600">{{ $order->order_number }}</span>
                            <p class="text-xs text-gray-500">{{ $order->created_at->diffForHumans() }}</p>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900">{{ $order->user->full_name ?? 'Guest' }}</td>
                        <td class="px-4 py-3">
                            <span class="text-xs font-medium px-2 py-0.5 rounded {{ $statusClasses }}">{{ ucfirst(str_replace('_',' ',$order->status)) }}</span>
                        </td>
                        <td class="px-4 py-3 text-sm text-right font-medium text-gray-900">@price($order->total)</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">No orders yet</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="bg-white overflow-hidden rounded-xl border border-gray-200 shadow-sm">
            <div class="px-5 py-4 border-b border-gray-200">
                <p class="text-sm font-semibold text-gray-900">Top Products</p>
            </div>
            @forelse($topProducts as $i => $product)
            <div class="px-4 py-3 flex items-center gap-3 {{ !$loop->last ? 'border-b border-gray-100' : '' }}">
                <span class="text-xs font-medium w-4 text-center shrink-0 text-gray-400">{{ $i+1 }}</span>
                <div class="w-8 h-8 rounded overflow-hidden shrink-0 bg-gray-100 border border-gray-200">
                    @if($product->primary_image_url)
                        <img src="{{ $product->primary_image_url }}" class="w-full h-full object-cover">
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium truncate text-gray-900" title="{{ $product->name }}">{{ $product->name }}</p>
                    <p class="text-xs text-gray-500">{{ $product->total_sold ?? 0 }} sold</p>
                </div>
                <span class="text-sm font-medium text-gray-900">@price($product->price)</span>
            </div>
            @empty
            <div class="p-4 text-center text-sm text-gray-500">No products yet</div>
            @endforelse
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const fontFamily = "'Inter', sans-serif";

            // Delegate clicks on order rows (replaces inline onclick)
            document.querySelectorAll('tr[data-order-url]').forEach(function(row) {
                row.addEventListener('click', function() {
                    window.location = row.getAttribute('data-order-url');
                });
            });

            new Chart(document.getElementById('revenueChart'), {
                type: 'line',
                data: {
                    labels: @json($chartLabels),
                    datasets: [{
                        label: 'Revenue', data: @json($chartRevenue),
                        borderColor: '#5c6ac4', backgroundColor: 'rgba(92,106,196,.06)',
                        borderWidth: 2, fill: true, tension: 0.4,
                        pointBackgroundColor: '#5c6ac4', pointBorderColor: '#fff', pointBorderWidth: 2, pointRadius: 3, pointHoverRadius: 5
                    }, {
                        label: 'Orders', data: @json($chartOrders),
                        borderColor: '#47c1bf', backgroundColor: 'rgba(71,193,191,.04)',
                        borderWidth: 2, fill: true, tension: 0.4,
                        pointBackgroundColor: '#47c1bf', pointBorderColor: '#fff', pointBorderWidth: 2, pointRadius: 2, pointHoverRadius: 4,
                        yAxisID: 'y1'
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { position: 'top', align: 'end', labels: { usePointStyle: true, pointStyle: 'circle', padding: 16, font: { size: 11, family: fontFamily } } } },
                    scales: {
                        x: { grid: { display: false }, ticks: { font: { size: 11, family: fontFamily }, color: '#999' } },
                        y: { position: 'left', grid: { color: '#f5f5f5' }, ticks: { font: { size: 11, family: fontFamily }, color: '#999', callback: v => '₹'+v.toLocaleString() } },
                        y1: { position: 'right', grid: { drawOnChartArea: false }, ticks: { font: { size: 11, family: fontFamily }, color: '#999', stepSize: 1 } }
                    }
                }
            });

            const statusData = @json($orderStatusCounts);
            new Chart(document.getElementById('orderStatusChart'), {
                type: 'doughnut',
                data: {
                    labels: Object.keys(statusData).map(s => s.charAt(0).toUpperCase()+s.slice(1).replace('_',' ')),
                    datasets: [{ data: Object.values(statusData), backgroundColor: Object.keys(statusData).map(s => ({pending:'#f59e0b',confirmed:'#3b82f6',processing:'#8b5cf6',packed:'#6366f1',shipped:'#06b6d4',out_for_delivery:'#14b8a6',delivered:'#10b981',completed:'#059669',cancelled:'#ef4444',returned:'#f97316'})[s]||'#bbb'), borderWidth: 2, borderColor: '#fff', hoverOffset: 4 }]
                },
                options: { responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, pointStyle: 'circle', padding: 10, font: { size: 11, family: fontFamily } } } } }
            });

            new Chart(document.getElementById('monthlyRevenueChart'), {
                type: 'bar',
                data: {
                    labels: @json($monthLabels),
                    datasets: [{ label: 'Revenue', data: @json($monthData), backgroundColor: 'rgba(92,106,196,.15)', hoverBackgroundColor: 'rgba(92,106,196,.3)', borderColor: '#5c6ac4', borderWidth: 1, borderRadius: 6, borderSkipped: false }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false }, ticks: { font: { size: 11, family: fontFamily }, color: '#999' } }, y: { grid: { color: '#f5f5f5' }, ticks: { font: { size: 11, family: fontFamily }, color: '#999', callback: v => '₹'+v.toLocaleString() } } } }
            });
        });
    </script>
    @endpush
</x-layouts.admin>
