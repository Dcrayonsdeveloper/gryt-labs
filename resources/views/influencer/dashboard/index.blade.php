@php
    $ranges = ['today' => 'Today', 'yesterday' => 'Yesterday', '7days' => 'Last 7 Days', '30days' => 'Last 30 Days', 'this_month' => 'This Month'];
    $c = $a['cards'];
@endphp

<x-layouts.influencer title="Dashboard">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-xl font-bold text-neutral-900">Hi, {{ $influencer->full_name }}</h1>
            <p class="text-sm text-neutral-500">Your coupon performance</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-xs text-neutral-500">Coupon</span>
            <span class="inline-flex items-center px-3 py-1.5 rounded-lg bg-primary-50 text-primary-700 font-mono font-semibold text-sm ring-1 ring-primary-200">
                {{ $influencer->coupon_code }}
            </span>
        </div>
    </div>

    {{-- Filters + export --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5 no-print">
        <div class="flex flex-wrap items-center gap-1.5">
            @foreach ($ranges as $key => $label)
                <a href="{{ route('influencer.dashboard', ['range' => $key]) }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ $range === $key ? 'bg-neutral-900 text-white' : 'bg-white text-neutral-600 ring-1 ring-neutral-200 hover:bg-neutral-50' }}">
                    {{ $label }}
                </a>
            @endforeach

            <form method="GET" action="{{ route('influencer.dashboard') }}" class="flex items-center gap-1.5 ml-1">
                <input type="hidden" name="range" value="custom">
                <input type="date" name="from" value="{{ $from }}" class="form-input text-xs py-1.5">
                <span class="text-neutral-400 text-xs">to</span>
                <input type="date" name="to" value="{{ $to }}" class="form-input text-xs py-1.5">
                <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-medium bg-white text-neutral-700 ring-1 ring-neutral-200 hover:bg-neutral-50">Apply</button>
            </form>
        </div>

        <div class="flex items-center gap-1.5">
            <a href="{{ route('influencer.export', request()->query()) }}"
               class="px-3 py-1.5 rounded-lg text-xs font-medium bg-white text-neutral-700 ring-1 ring-neutral-200 hover:bg-neutral-50">Export CSV</a>
            <button type="button" onclick="window.print()"
                    class="px-3 py-1.5 rounded-lg text-xs font-medium bg-white text-neutral-700 ring-1 ring-neutral-200 hover:bg-neutral-50">Print</button>
        </div>
    </div>

    {{-- Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-6">
        @foreach ([
            ['Total Orders', number_format($c['orders']), 'text-blue-600'],
            ['Total Sales', '₹' . number_format($c['sales'], 2), 'text-green-600'],
            ['Total Discount', '₹' . number_format($c['discount'], 2), 'text-amber-600'],
            ['Total Customers', number_format($c['customers']), 'text-violet-600'],
            ['Avg Order Value', '₹' . number_format($c['aov'], 2), 'text-neutral-700'],
        ] as [$label, $value, $color])
            <div class="bg-white rounded-xl ring-1 ring-neutral-200 p-4">
                <p class="text-xs text-neutral-500 mb-1">{{ $label }}</p>
                <p class="text-lg font-bold {{ $color }}">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    {{-- Charts --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4 no-print">
        <div class="bg-white rounded-xl ring-1 ring-neutral-200 p-4">
            <h3 class="text-sm font-semibold text-neutral-900 mb-3">Daily Orders</h3>
            <div style="height:240px;position:relative;"><canvas id="dailyOrdersChart"></canvas></div>
        </div>
        <div class="bg-white rounded-xl ring-1 ring-neutral-200 p-4">
            <h3 class="text-sm font-semibold text-neutral-900 mb-3">Daily Sales</h3>
            <div style="height:240px;position:relative;"><canvas id="dailySalesChart"></canvas></div>
        </div>
    </div>
    <div class="bg-white rounded-xl ring-1 ring-neutral-200 p-4 mb-6 no-print">
        <h3 class="text-sm font-semibold text-neutral-900 mb-3">Monthly Sales</h3>
        <div style="height:240px;position:relative;"><canvas id="monthlySalesChart"></canvas></div>
    </div>

    {{-- Sales table --}}
    <div class="bg-white rounded-xl ring-1 ring-neutral-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-neutral-200">
            <h3 class="text-sm font-semibold text-neutral-900">Orders using your coupon</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-neutral-50 text-left text-xs text-neutral-500">
                        <th class="px-4 py-2.5 font-medium">Order ID</th>
                        <th class="px-4 py-2.5 font-medium">Customer</th>
                        <th class="px-4 py-2.5 font-medium">Phone</th>
                        <th class="px-4 py-2.5 font-medium">Date</th>
                        <th class="px-4 py-2.5 font-medium text-right">Amount</th>
                        <th class="px-4 py-2.5 font-medium text-right">Discount</th>
                        <th class="px-4 py-2.5 font-medium text-right">Final</th>
                        <th class="px-4 py-2.5 font-medium">Status</th>
                        <th class="px-4 py-2.5 font-medium">Payment</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @forelse ($orders as $o)
                        <tr class="hover:bg-neutral-50">
                            <td class="px-4 py-2.5 font-medium text-neutral-900 whitespace-nowrap">{{ $o->order_number }}</td>
                            <td class="px-4 py-2.5 text-neutral-700">{{ $o->user?->full_name ?: ($o->guest_name ?: '—') }}</td>
                            <td class="px-4 py-2.5 text-neutral-500 whitespace-nowrap">{{ $o->guest_phone ?: ($o->user?->phone ?: '—') }}</td>
                            <td class="px-4 py-2.5 text-neutral-500 whitespace-nowrap">{{ $o->created_at?->format('d M Y') }}</td>
                            <td class="px-4 py-2.5 text-right text-neutral-700">₹{{ number_format((float) $o->subtotal, 2) }}</td>
                            <td class="px-4 py-2.5 text-right text-amber-600">₹{{ number_format((float) $o->discount, 2) }}</td>
                            <td class="px-4 py-2.5 text-right font-semibold text-neutral-900">₹{{ number_format((float) $o->total, 2) }}</td>
                            <td class="px-4 py-2.5"><span class="text-xs capitalize text-neutral-600">{{ str_replace('_', ' ', $o->status) }}</span></td>
                            <td class="px-4 py-2.5"><span class="text-xs capitalize text-neutral-600">{{ str_replace('_', ' ', $o->payment_status) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-4 py-10 text-center text-neutral-400 text-sm">No orders in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($orders->hasPages())
            <div class="px-4 py-3 border-t border-neutral-200">{{ $orders->withQueryString()->links() }}</div>
        @endif
    </div>

    @push('styles')
        <style>@media print { .no-print { display:none !important; } }</style>
    @endpush

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const font = "'Inter',sans-serif";
                Chart.defaults.font.family = font;
                Chart.defaults.color = '#6b7280';

                new Chart(document.getElementById('dailyOrdersChart'), {
                    type: 'bar',
                    data: { labels: @json($a['daily_labels']), datasets: [{ label: 'Orders', data: @json($a['daily_orders']), backgroundColor: '#3b82f6', borderRadius: 4 }] },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
                });

                new Chart(document.getElementById('dailySalesChart'), {
                    type: 'line',
                    data: { labels: @json($a['daily_labels']), datasets: [{ label: 'Sales', data: @json($a['daily_sales']), borderColor: '#16a34a', backgroundColor: 'rgba(22,163,74,.1)', fill: true, tension: .3 }] },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
                });

                new Chart(document.getElementById('monthlySalesChart'), {
                    type: 'bar',
                    data: { labels: @json($a['monthly_labels']), datasets: [{ label: 'Monthly Sales', data: @json($a['monthly_sales']), backgroundColor: '#8b5cf6', borderRadius: 4 }] },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
                });
            });
        </script>
    @endpush
</x-layouts.influencer>
