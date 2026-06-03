<x-layouts.admin>
    <x-slot name="title">Seller Report</x-slot>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900">Seller Report</h1>
            <p class="text-neutral-600">Marketplace seller performance overview</p>
        </div>
        <form action="{{ route('admin.reports.sellers') }}" method="GET">
            <select name="period" onchange="this.form.submit()"
                    class="px-3 py-2 border border-neutral-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                <option value="7" @selected($period == 7)>Last 7 days</option>
                <option value="30" @selected($period == 30)>Last 30 days</option>
                <option value="90" @selected($period == 90)>Last 90 days</option>
                <option value="365" @selected($period == 365)>Last year</option>
            </select>
        </form>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="card p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-neutral-600">Total Sellers</p>
                    <p class="text-2xl font-bold text-neutral-900">{{ number_format($stats['total_sellers']) }}</p>
                </div>
                <div class="w-12 h-12 bg-primary-100 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="card p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-neutral-600">Active Sellers</p>
                    <p class="text-2xl font-bold text-success-600">{{ number_format($stats['active_sellers']) }}</p>
                </div>
                <div class="w-12 h-12 bg-success-100 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="card p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-neutral-600">Pending Approval</p>
                    <p class="text-2xl font-bold text-warning-600">{{ number_format($stats['pending_sellers']) }}</p>
                </div>
                <div class="w-12 h-12 bg-warning-100 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-warning-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="card p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-neutral-600">New This Period</p>
                    <p class="text-2xl font-bold text-neutral-900">{{ number_format($stats['new_sellers']) }}</p>
                </div>
                <div class="w-12 h-12 bg-neutral-100 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Sellers -->
    <div class="card mb-6">
        <div class="p-4 border-b border-neutral-200">
            <h2 class="font-semibold text-neutral-900">Top Performing Sellers</h2>
        </div>
        <div class="divide-y divide-neutral-200">
            @forelse($topSellers as $index => $seller)
                <div class="p-4 flex items-center gap-4">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full
                        {{ $index < 3 ? 'bg-warning-100 text-warning-600' : 'bg-neutral-100 text-neutral-600' }}
                        text-sm font-medium">
                        {{ $index + 1 }}
                    </span>
                    <div class="w-12 h-12 bg-neutral-200 rounded-full flex items-center justify-center flex-shrink-0">
                        <span class="text-lg font-medium text-neutral-600">
                            {{ strtoupper(substr($seller->store_name, 0, 1)) }}
                        </span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-neutral-900">{{ $seller->store_name }}</p>
                        <p class="text-sm text-neutral-600">{{ $seller->products_count }} products</p>
                    </div>
                    <div class="text-right">
                        <p class="font-semibold text-success-600">@price($seller->total_sales ?? 0)</p>
                        <p class="text-xs text-neutral-600">Total Sales</p>
                    </div>
                    <a href="{{ route('admin.sellers.show', $seller) }}" class="btn btn-secondary text-sm">View</a>
                </div>
            @empty
                <div class="p-8 text-center text-neutral-600">No seller data available</div>
            @endforelse
        </div>
    </div>

    <!-- All Active Sellers -->
    <div class="card overflow-hidden">
        <div class="p-4 border-b border-neutral-200 flex items-center justify-between">
            <h2 class="font-semibold text-neutral-900">Active Sellers</h2>
            <a href="{{ route('admin.sellers.index') }}" class="text-sm text-primary-600 hover:text-primary-700">View All Sellers</a>
        </div>
        @if($sellers->total() > 0)
            <div class="px-5 py-3 border-b border-neutral-200">
                {{ $sellers->links('vendor.pagination.info-bar') }}
            </div>
        @endif
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-neutral-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Seller</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Store</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Products</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Commission</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-neutral-600 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200">
                    @forelse($sellers as $seller)
                        <tr class="hover:bg-neutral-50">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-neutral-200 rounded-full flex items-center justify-center">
                                        <span class="text-sm font-medium text-neutral-600">
                                            {{ strtoupper(substr($seller->user->name ?? 'S', 0, 1)) }}
                                        </span>
                                    </div>
                                    <div>
                                        <p class="font-medium text-neutral-900">{{ $seller->user->name ?? 'N/A' }}</p>
                                        <p class="text-sm text-neutral-600">{{ $seller->user->email ?? 'N/A' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-neutral-700">{{ $seller->store_name }}</td>
                            <td class="px-4 py-3 text-neutral-600">{{ $seller->products_count }}</td>
                            <td class="px-4 py-3 text-neutral-600">{{ $seller->commission_rate ?? 15 }}%</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex px-2 py-1 text-xs font-medium bg-success-100 text-success-700 rounded-full">
                                    Active
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.sellers.show', $seller) }}" class="text-primary-600 hover:text-primary-700">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-neutral-600">No active sellers</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($sellers->hasPages())
            <div class="px-4 py-3 border-t border-neutral-200">
                {{ $sellers->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
