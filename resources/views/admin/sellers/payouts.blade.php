<x-layouts.admin>
    <x-slot name="title">{{ $seller->store_name }} - Payouts</x-slot>

    <div class="flex items-center justify-between mb-6">
        <div>
            <div class="flex items-center gap-2 text-sm text-neutral-600 mb-1">
                <a href="{{ route('admin.sellers.index') }}" class="hover:text-primary-600">Sellers</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <a href="{{ route('admin.sellers.show', $seller) }}" class="hover:text-primary-600">{{ $seller->store_name }}</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <span class="text-neutral-900">Payouts</span>
            </div>
            <h1 class="text-2xl font-bold text-neutral-900">{{ $seller->store_name }}</h1>
            <p class="text-neutral-600">Payouts</p>
        </div>
        <a href="{{ route('admin.sellers.show', $seller) }}" class="btn btn-secondary">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to Seller
        </a>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <div class="card p-4 sm:p-5 flex items-center gap-3 sm:gap-4">
            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-success-50 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 sm:w-6 sm:h-6 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs sm:text-sm text-neutral-600">Available Balance</p>
                <p class="text-xl sm:text-2xl font-bold text-success-600">@price($seller->available_balance ?? 0)</p>
            </div>
        </div>
        <div class="card p-4 sm:p-5 flex items-center gap-3 sm:gap-4">
            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-warning-50 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 sm:w-6 sm:h-6 text-warning-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs sm:text-sm text-neutral-600">Pending</p>
                <p class="text-xl sm:text-2xl font-bold text-warning-600">@price($stats['pending'])</p>
            </div>
        </div>
        <div class="card p-4 sm:p-5 flex items-center gap-3 sm:gap-4">
            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-primary-50 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 sm:w-6 sm:h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </div>
            <div>
                <p class="text-xs sm:text-sm text-neutral-600">Processing</p>
                <p class="text-xl sm:text-2xl font-bold text-primary-600">@price($stats['processing'])</p>
            </div>
        </div>
        <div class="card p-4 sm:p-5 flex items-center gap-3 sm:gap-4">
            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-neutral-100 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 sm:w-6 sm:h-6 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs sm:text-sm text-neutral-600">Completed</p>
                <p class="text-xl sm:text-2xl font-bold text-neutral-900">@price($stats['completed'])</p>
            </div>
        </div>
    </div>

    <div class="card overflow-hidden">
        @if($payouts->total() > 0)
            <div class="px-5 py-3 border-b border-neutral-200">
                {{ $payouts->links('vendor.pagination.info-bar') }}
            </div>
        @endif
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-neutral-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">ID</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Amount</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Method</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Requested</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Completed</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200">
                    @forelse($payouts as $payout)
                        <tr class="hover:bg-neutral-50">
                            <td class="px-4 py-3 text-sm font-mono text-neutral-600">#{{ $payout->id }}</td>
                            <td class="px-4 py-3 font-medium text-neutral-900">@price($payout->amount)</td>
                            <td class="px-4 py-3 text-sm text-neutral-600">{{ ucfirst(str_replace('_', ' ', $payout->payout_method)) }}</td>
                            <td class="px-4 py-3">
                                @switch($payout->status)
                                    @case('pending')
                                        <span class="badge badge-warning">Pending</span>
                                        @break
                                    @case('processing')
                                        <span class="badge badge-info">Processing</span>
                                        @break
                                    @case('completed')
                                        <span class="badge badge-success">Completed</span>
                                        @break
                                    @case('failed')
                                        <span class="badge badge-error">Failed</span>
                                        @break
                                @endswitch
                            </td>
                            <td class="px-4 py-3 text-sm text-neutral-600">{{ $payout->created_at->format('M d, Y') }}</td>
                            <td class="px-4 py-3 text-sm text-neutral-600">{{ $payout->completed_at?->format('M d, Y') ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-neutral-600">
                                No payouts found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($payouts->hasPages())
            <div class="px-4 py-3 border-t border-neutral-200">
                {{ $payouts->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
