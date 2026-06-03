<x-layouts.affiliate :title="$title">
    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Available Balance -->
        <div class="card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-neutral-600">Available Balance</p>
                    <p class="text-2xl font-bold text-neutral-900 mt-1">@price($affiliate->available_balance)</p>
                </div>
                <div class="w-12 h-12 bg-success-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            @if($affiliate->available_balance >= config('affiliate.minimum_redemption', 500))
                <a href="{{ route('affiliate.redemptions.create') }}" class="mt-3 inline-flex items-center text-sm text-success-600 hover:text-success-700 font-medium">
                    Redeem Now &rarr;
                </a>
            @endif
        </div>

        <!-- This Month Earnings -->
        <div class="card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-neutral-600">This Month</p>
                    <p class="text-2xl font-bold text-neutral-900 mt-1">@price($thisMonthEarnings)</p>
                </div>
                <div class="w-12 h-12 bg-primary-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Total Clicks -->
        <div class="card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-neutral-600">Total Clicks</p>
                    <p class="text-2xl font-bold text-neutral-900 mt-1">{{ number_format($affiliate->total_clicks) }}</p>
                </div>
                <div class="w-12 h-12 bg-info-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-info-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Conversion Rate -->
        <div class="card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-neutral-600">Conversion Rate</p>
                    <p class="text-2xl font-bold text-neutral-900 mt-1">{{ $conversionRate }}%</p>
                </div>
                <div class="w-12 h-12 bg-warning-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-warning-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Referral Link Card -->
    <div class="card p-6 mb-8" x-data="{ copied: false }">
        <h3 class="font-semibold text-neutral-900 mb-3">Your Referral Link</h3>
        <div class="flex items-center gap-3">
            <input type="text" readonly value="{{ $affiliate->getReferralUrl() }}"
                   class="form-input flex-1 bg-neutral-50 text-sm" id="referral-link">
            <button @click="navigator.clipboard.writeText('{{ $affiliate->getReferralUrl() }}'); copied = true; setTimeout(() => copied = false, 2000)"
                    class="btn-primary whitespace-nowrap">
                <span x-show="!copied">Copy Link</span>
                <span x-show="copied" x-cloak>Copied!</span>
            </button>
        </div>
        <p class="text-xs text-neutral-500 mt-2">
            Share this link in your video descriptions and ads. You earn {{ $affiliate->commission_rate }}% on every sale.
        </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Recent Commissions -->
        <div class="lg:col-span-2">
            <div class="card">
                <div class="px-6 py-4 border-b border-neutral-200 flex justify-between items-center">
                    <h3 class="font-semibold text-neutral-900">Recent Commissions</h3>
                    <a href="{{ route('affiliate.commissions.index') }}" class="text-sm text-primary-600 hover:text-primary-700">View All</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-neutral-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Order</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Date</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-neutral-500 uppercase">Amount</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-neutral-500 uppercase">Commission</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-neutral-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100">
                            @forelse($recentCommissions as $commission)
                                <tr class="hover:bg-neutral-50">
                                    <td class="px-6 py-3 text-sm text-neutral-900">{{ $commission->order?->order_number ?? '-' }}</td>
                                    <td class="px-6 py-3 text-sm text-neutral-600">{{ $commission->created_at->format('d M Y') }}</td>
                                    <td class="px-6 py-3 text-sm text-neutral-900 text-right">@price($commission->order_total)</td>
                                    <td class="px-6 py-3 text-sm font-medium text-success-600 text-right">+@price($commission->commission_amount)</td>
                                    <td class="px-6 py-3 text-center">
                                        @php
                                            $statusColors = [
                                                'pending' => 'bg-warning-100 text-warning-700',
                                                'approved' => 'bg-success-100 text-success-700',
                                                'paid' => 'bg-primary-100 text-primary-700',
                                                'cancelled' => 'bg-error-100 text-error-700',
                                            ];
                                        @endphp
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$commission->status] ?? 'bg-neutral-100 text-neutral-700' }}">
                                            {{ ucfirst($commission->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-sm text-neutral-500">
                                        No commissions yet. Start sharing your referral link!
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Lifetime Stats -->
        <div>
            <div class="card p-6">
                <h3 class="font-semibold text-neutral-900 mb-4">Lifetime Stats</h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-neutral-600">Total Earned</span>
                        <span class="font-semibold text-neutral-900">@price($affiliate->total_earned)</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-neutral-600">Total Orders</span>
                        <span class="font-semibold text-neutral-900">{{ number_format($affiliate->total_orders) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-neutral-600">Total Clicks</span>
                        <span class="font-semibold text-neutral-900">{{ number_format($affiliate->total_clicks) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-neutral-600">Pending Balance</span>
                        <span class="font-semibold text-warning-600">@price($affiliate->pending_balance)</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-neutral-600">Commission Rate</span>
                        <span class="font-semibold text-neutral-900">{{ $affiliate->commission_rate }}%</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-neutral-600">Platform</span>
                        <span class="font-semibold text-neutral-900">{{ ucfirst($affiliate->channel ?? '-') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.affiliate>
