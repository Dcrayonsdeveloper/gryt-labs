<x-layouts.affiliate :title="$title">
    <!-- Header with Redeem Button -->
    <div class="flex items-center justify-between mb-8">
        <div>
            <h2 class="text-xl font-bold text-neutral-900">Redemptions</h2>
            <p class="text-sm text-neutral-600 mt-1">Track your payout requests</p>
        </div>
        @if($affiliate->available_balance >= config('affiliate.minimum_redemption', 500))
            <a href="{{ route('affiliate.redemptions.create') }}" class="btn-primary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Redeem Now
            </a>
        @endif
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="card p-6">
            <p class="text-sm text-neutral-600">Available Balance</p>
            <p class="text-xl font-bold text-success-600 mt-1">@price($stats['available'])</p>
        </div>
        <div class="card p-6">
            <p class="text-sm text-neutral-600">Pending Payouts</p>
            <p class="text-xl font-bold text-warning-600 mt-1">@price($stats['pending'])</p>
        </div>
        <div class="card p-6">
            <p class="text-sm text-neutral-600">Total Paid Out</p>
            <p class="text-xl font-bold text-neutral-900 mt-1">@price($stats['completed'])</p>
        </div>
    </div>

    <!-- Redemptions Table -->
    <div class="card">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-neutral-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Requested</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Method</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-neutral-500 uppercase">Amount</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-neutral-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Completed</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @forelse($redemptions as $redemption)
                        <tr class="hover:bg-neutral-50">
                            <td class="px-6 py-4 text-sm font-medium text-neutral-900">#{{ $redemption->id }}</td>
                            <td class="px-6 py-4 text-sm text-neutral-600">{{ $redemption->created_at->format('d M Y, h:i A') }}</td>
                            <td class="px-6 py-4 text-sm text-neutral-900">
                                {{ $redemption->payout_method === 'bank_transfer' ? 'Bank Transfer' : 'UPI' }}
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-neutral-900 text-right">@price($redemption->amount)</td>
                            <td class="px-6 py-4 text-center">
                                @php
                                    $statusColors = [
                                        'pending' => 'bg-warning-100 text-warning-700',
                                        'processing' => 'bg-info-100 text-info-700',
                                        'completed' => 'bg-success-100 text-success-700',
                                        'failed' => 'bg-error-100 text-error-700',
                                    ];
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$redemption->status] ?? 'bg-neutral-100 text-neutral-700' }}">
                                    {{ ucfirst($redemption->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-neutral-600">
                                {{ $redemption->completed_at?->format('d M Y') ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <svg class="w-12 h-12 text-neutral-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                </svg>
                                <p class="text-sm text-neutral-500">No redemptions yet.</p>
                                @if($affiliate->available_balance >= config('affiliate.minimum_redemption', 500))
                                    <a href="{{ route('affiliate.redemptions.create') }}" class="mt-2 inline-block text-sm text-primary-600 hover:text-primary-700">
                                        Request your first payout &rarr;
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($redemptions->hasPages())
            <div class="px-6 py-4 border-t border-neutral-200">
                {{ $redemptions->links() }}
            </div>
        @endif
    </div>
</x-layouts.affiliate>
