@php
    $ranges = ['today' => 'Today', '7days' => 'Last 7 Days', '30days' => 'Last 30 Days', 'this_month' => 'This Month'];
    $c = $a['cards'];
@endphp

<x-layouts.admin title="Influencer Analytics">

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <a href="{{ route('admin.influencers.index') }}" class="text-sm text-neutral-500 hover:underline">&larr; Influencers</a>
            <h1 class="text-xl font-bold text-neutral-900 mt-1">{{ $influencer->full_name }}</h1>
            <p class="text-sm text-neutral-500">
                Coupon <span class="font-mono text-primary-600">{{ $influencer->coupon_code }}</span>
                · <span class="font-mono">{{ '@' . ltrim($influencer->instagram ?? '', '@') ?: '—' }}</span>
            </p>
        </div>
        <a href="{{ route('admin.influencers.edit', $influencer) }}" class="btn btn-secondary text-sm">Edit</a>
    </div>

    {{-- Filters --}}
    <div class="flex flex-wrap items-center gap-1.5 mb-5">
        @foreach($ranges as $key => $label)
            <a href="{{ route('admin.influencers.analytics', [$influencer, 'range' => $key]) }}"
               class="px-3 py-1.5 rounded-lg text-xs font-medium {{ $range === $key ? 'bg-neutral-900 text-white' : 'bg-white text-neutral-600 ring-1 ring-neutral-200 hover:bg-neutral-50' }}">{{ $label }}</a>
        @endforeach
        <form method="GET" action="{{ route('admin.influencers.analytics', $influencer) }}" class="flex items-center gap-1.5 ml-1">
            <input type="hidden" name="range" value="custom">
            <input type="date" name="from" value="{{ $from }}" class="form-input text-xs py-1.5">
            <span class="text-neutral-400 text-xs">to</span>
            <input type="date" name="to" value="{{ $to }}" class="form-input text-xs py-1.5">
            <button class="px-3 py-1.5 rounded-lg text-xs font-medium bg-white text-neutral-700 ring-1 ring-neutral-200 hover:bg-neutral-50">Apply</button>
        </form>
    </div>

    {{-- Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-6">
        @foreach([
            ['Total Orders', number_format($c['orders'])],
            ['Total Revenue', '₹' . number_format($c['sales'], 2)],
            ['Total Discount', '₹' . number_format($c['discount'], 2)],
            ['Total Customers', number_format($c['customers'])],
            ['Avg Order Value', '₹' . number_format($c['aov'], 2)],
        ] as [$label, $value])
            <div class="card p-4">
                <p class="text-xs text-neutral-500 mb-1">{{ $label }}</p>
                <p class="text-lg font-bold text-neutral-900">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    {{-- Charts --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
        <div class="card p-4"><h3 class="text-sm font-semibold text-neutral-900 mb-3">Daily Sales</h3><div style="height:240px;position:relative;"><canvas id="dSales"></canvas></div></div>
        <div class="card p-4"><h3 class="text-sm font-semibold text-neutral-900 mb-3">Orders per Day</h3><div style="height:240px;position:relative;"><canvas id="dOrders"></canvas></div></div>
        <div class="card p-4"><h3 class="text-sm font-semibold text-neutral-900 mb-3">Monthly Sales</h3><div style="height:240px;position:relative;"><canvas id="mSales"></canvas></div></div>
        <div class="card p-4"><h3 class="text-sm font-semibold text-neutral-900 mb-3">Revenue Growth (cumulative)</h3><div style="height:240px;position:relative;"><canvas id="revGrowth"></canvas></div></div>
    </div>

    {{-- Sales table --}}
    <div class="card overflow-hidden">
        <div class="px-4 py-3 border-b border-neutral-200"><h3 class="text-sm font-semibold text-neutral-900">Orders</h3></div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-neutral-50 text-left text-xs text-neutral-500">
                        <th class="px-4 py-2.5 font-medium">Order ID</th>
                        <th class="px-4 py-2.5 font-medium">Customer</th>
                        <th class="px-4 py-2.5 font-medium">Date</th>
                        <th class="px-4 py-2.5 font-medium">Coupon</th>
                        <th class="px-4 py-2.5 font-medium text-right">Amount</th>
                        <th class="px-4 py-2.5 font-medium text-right">Discount</th>
                        <th class="px-4 py-2.5 font-medium text-right">Final</th>
                        <th class="px-4 py-2.5 font-medium">Status</th>
                        <th class="px-4 py-2.5 font-medium">Payment</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @forelse($orders as $o)
                        <tr class="hover:bg-neutral-50">
                            <td class="px-4 py-2.5 font-medium text-neutral-900 whitespace-nowrap">{{ $o->order_number }}</td>
                            <td class="px-4 py-2.5 text-neutral-700">{{ $o->user?->full_name ?: ($o->guest_name ?: '—') }}</td>
                            <td class="px-4 py-2.5 text-neutral-500 whitespace-nowrap">{{ $o->created_at?->format('d M Y') }}</td>
                            <td class="px-4 py-2.5 font-mono text-xs text-primary-600">{{ $influencer->coupon_code }}</td>
                            <td class="px-4 py-2.5 text-right text-neutral-700">₹{{ number_format((float) $o->subtotal, 2) }}</td>
                            <td class="px-4 py-2.5 text-right text-amber-600">₹{{ number_format((float) $o->discount, 2) }}</td>
                            <td class="px-4 py-2.5 text-right font-semibold text-neutral-900">₹{{ number_format((float) $o->total, 2) }}</td>
                            <td class="px-4 py-2.5 capitalize text-xs text-neutral-600">{{ str_replace('_', ' ', $o->status) }}</td>
                            <td class="px-4 py-2.5 capitalize text-xs text-neutral-600">{{ str_replace('_', ' ', $o->payment_status) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-4 py-10 text-center text-neutral-400">No orders in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($orders->hasPages())<div class="px-4 py-3 border-t border-neutral-200">{{ $orders->withQueryString()->links() }}</div>@endif
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                Chart.defaults.font.family = "'Inter',sans-serif";
                Chart.defaults.color = '#6b7280';
                const dailyLabels = @json($a['daily_labels']);
                const dailySales  = @json($a['daily_sales']);
                const dailyOrders = @json($a['daily_orders']);

                new Chart(dSales, { type: 'line', data: { labels: dailyLabels, datasets: [{ data: dailySales, borderColor: '#16a34a', backgroundColor: 'rgba(22,163,74,.1)', fill: true, tension: .3 }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } } });
                new Chart(dOrders, { type: 'bar', data: { labels: dailyLabels, datasets: [{ data: dailyOrders, backgroundColor: '#3b82f6', borderRadius: 4 }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } } });
                new Chart(mSales, { type: 'bar', data: { labels: @json($a['monthly_labels']), datasets: [{ data: @json($a['monthly_sales']), backgroundColor: '#8b5cf6', borderRadius: 4 }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } } });

                let run = 0; const cumulative = dailySales.map(v => (run += v));
                new Chart(revGrowth, { type: 'line', data: { labels: dailyLabels, datasets: [{ data: cumulative, borderColor: '#0ea5e9', backgroundColor: 'rgba(14,165,233,.1)', fill: true, tension: .3 }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } } });
            });
        </script>
    @endpush
</x-layouts.admin>
