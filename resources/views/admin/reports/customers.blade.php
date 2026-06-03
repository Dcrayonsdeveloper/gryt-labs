<x-layouts.admin>
    <x-slot name="title">Customer Report</x-slot>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900">Customer Report</h1>
            <p class="text-neutral-600">Customer acquisition and behavior insights</p>
        </div>
        <div class="flex items-center gap-3">
            <form action="{{ route('admin.reports.customers') }}" method="GET" class="flex items-center gap-2">
                <select name="period" onchange="this.form.submit()"
                        class="px-3 py-2 border border-neutral-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    <option value="7" @selected($period == 7)>Last 7 days</option>
                    <option value="30" @selected($period == 30)>Last 30 days</option>
                    <option value="90" @selected($period == 90)>Last 90 days</option>
                    <option value="365" @selected($period == 365)>Last year</option>
                </select>
            </form>
            <a href="{{ route('admin.reports.export', ['type' => 'customers', 'period' => $period]) }}" class="btn btn-secondary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Export CSV
            </a>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="card p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-neutral-600">Total Customers</p>
                    <p class="text-2xl font-bold text-neutral-900">{{ number_format($stats['total_customers']) }}</p>
                </div>
                <div class="w-12 h-12 bg-primary-100 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="card p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-neutral-600">New Customers</p>
                    <p class="text-2xl font-bold text-success-600">{{ number_format($stats['new_customers']) }}</p>
                </div>
                <div class="w-12 h-12 bg-success-100 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="card p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-neutral-600">Returning</p>
                    <p class="text-2xl font-bold text-warning-600">{{ number_format($stats['returning_customers']) }}</p>
                </div>
                <div class="w-12 h-12 bg-warning-100 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-warning-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="card p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-neutral-600">Avg. Lifetime Value</p>
                    <p class="text-2xl font-bold text-neutral-900">@price($stats['average_lifetime_value'])</p>
                </div>
                <div class="w-12 h-12 bg-neutral-100 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Customer Growth -->
        <div class="lg:col-span-2 card p-6">
            <h2 class="font-semibold text-neutral-900 mb-6">New Customer Growth</h2>
            <div class="space-y-3">
                @foreach($growth->take(14) as $data)
                    @php
                        $maxGrowth = $growth->max('count') ?: 1;
                        $percentage = ($data->count / $maxGrowth) * 100;
                    @endphp
                    <div class="flex items-center gap-4">
                        <div class="w-20 text-sm text-neutral-600">{{ \Carbon\Carbon::parse($data->date)->format('M d') }}</div>
                        <div class="flex-1 bg-neutral-100 rounded-full h-4 overflow-hidden">
                            <div class="bg-success-500 h-full rounded-full" style="width: {{ $percentage }}%"></div>
                        </div>
                        <div class="w-12 text-right text-sm font-medium">{{ $data->count }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Customer Segments -->
        <div class="card p-6">
            <h2 class="font-semibold text-neutral-900 mb-4">Customer Segments</h2>
            @php
                $totalCustomers = $stats['new_customers'] + $stats['returning_customers'];
                $newPercentage = $totalCustomers > 0 ? ($stats['new_customers'] / $totalCustomers) * 100 : 0;
                $returningPercentage = $totalCustomers > 0 ? ($stats['returning_customers'] / $totalCustomers) * 100 : 0;
            @endphp
            <div class="space-y-4">
                <div>
                    <div class="flex items-center justify-between text-sm mb-1">
                        <span class="text-neutral-700">New Customers</span>
                        <span class="font-medium">{{ number_format($newPercentage, 1) }}%</span>
                    </div>
                    <div class="bg-neutral-100 rounded-full h-3 overflow-hidden">
                        <div class="bg-success-500 h-full rounded-full" style="width: {{ $newPercentage }}%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex items-center justify-between text-sm mb-1">
                        <span class="text-neutral-700">Returning Customers</span>
                        <span class="font-medium">{{ number_format($returningPercentage, 1) }}%</span>
                    </div>
                    <div class="bg-neutral-100 rounded-full h-3 overflow-hidden">
                        <div class="bg-primary-500 h-full rounded-full" style="width: {{ $returningPercentage }}%"></div>
                    </div>
                </div>
            </div>

            <div class="mt-6 pt-4 border-t border-neutral-200">
                <p class="text-sm text-neutral-600 mb-2">Customer Retention</p>
                <p class="text-3xl font-bold text-primary-600">
                    {{ $stats['total_customers'] > 0 ? number_format(($stats['returning_customers'] / $stats['total_customers']) * 100, 1) : 0 }}%
                </p>
            </div>
        </div>
    </div>

    <!-- Top Customers -->
    <div class="card overflow-hidden">
        <div class="p-4 border-b border-neutral-200">
            <h2 class="font-semibold text-neutral-900">Top Customers</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-neutral-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Rank</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Customer</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Email</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-neutral-600 uppercase">Orders</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-neutral-600 uppercase">Total Spent</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200">
                    @forelse($topCustomers as $index => $customer)
                        <tr class="hover:bg-neutral-50">
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full
                                    {{ $index < 3 ? 'bg-warning-100 text-warning-600' : 'bg-neutral-100 text-neutral-600' }}
                                    text-xs font-medium">
                                    {{ $index + 1 }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-neutral-200 rounded-full flex items-center justify-center">
                                        <span class="text-sm font-medium text-neutral-600">
                                            {{ strtoupper(substr($customer->name ?? 'C', 0, 1)) }}
                                        </span>
                                    </div>
                                    <span class="font-medium text-neutral-900">{{ $customer->name }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-neutral-600">{{ $customer->email }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($customer->order_count ?? 0) }}</td>
                            <td class="px-4 py-3 text-right font-medium text-success-600">@price($customer->period_spent ?? $customer->total_spent ?? 0)</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-neutral-600">No customer data</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.admin>
