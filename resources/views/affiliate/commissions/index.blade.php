<x-layouts.affiliate :title="$title">
    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="card p-6">
            <p class="text-sm text-neutral-600">Total Earned</p>
            <p class="text-xl font-bold text-neutral-900 mt-1">@price($stats['total'])</p>
        </div>
        <div class="card p-6">
            <p class="text-sm text-neutral-600">Pending</p>
            <p class="text-xl font-bold text-warning-600 mt-1">@price($stats['pending'])</p>
        </div>
        <div class="card p-6">
            <p class="text-sm text-neutral-600">Approved</p>
            <p class="text-xl font-bold text-success-600 mt-1">@price($stats['approved'])</p>
        </div>
        <div class="card p-6">
            <p class="text-sm text-neutral-600">Paid Out</p>
            <p class="text-xl font-bold text-primary-600 mt-1">@price($stats['paid'])</p>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="card">
        <div class="border-b border-neutral-200 px-6">
            <nav class="flex gap-6 -mb-px">
                @php
                    $tabs = [
                        '' => 'All',
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'paid' => 'Paid',
                        'cancelled' => 'Cancelled',
                    ];
                @endphp
                @foreach($tabs as $key => $label)
                    <a href="{{ route('affiliate.commissions.index', $key ? ['status' => $key] : []) }}"
                       class="py-4 text-sm font-medium border-b-2 {{ ($currentStatus ?? '') === $key ? 'border-primary-600 text-primary-600' : 'border-transparent text-neutral-600 hover:text-neutral-900' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </nav>
        </div>

        <!-- Commissions Table -->
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-neutral-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Order</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-neutral-500 uppercase">Order Total</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-neutral-500 uppercase">Rate</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-neutral-500 uppercase">Commission</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-neutral-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @forelse($commissions as $commission)
                        <tr class="hover:bg-neutral-50">
                            <td class="px-6 py-4 text-sm font-medium text-neutral-900">{{ $commission->order?->order_number ?? '-' }}</td>
                            <td class="px-6 py-4 text-sm text-neutral-600">{{ $commission->created_at->format('d M Y, h:i A') }}</td>
                            <td class="px-6 py-4 text-sm text-neutral-900 text-right">@price($commission->order_total)</td>
                            <td class="px-6 py-4 text-sm text-neutral-600 text-right">{{ $commission->commission_rate }}%</td>
                            <td class="px-6 py-4 text-sm font-medium text-success-600 text-right">+@price($commission->commission_amount)</td>
                            <td class="px-6 py-4 text-center">
                                @php
                                    $statusColors = [
                                        'pending' => 'bg-warning-100 text-warning-700',
                                        'approved' => 'bg-success-100 text-success-700',
                                        'paid' => 'bg-primary-100 text-primary-700',
                                        'cancelled' => 'bg-error-100 text-error-700',
                                    ];
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$commission->status] ?? 'bg-neutral-100 text-neutral-700' }}">
                                    {{ ucfirst($commission->status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <svg class="w-12 h-12 text-neutral-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                                <p class="text-sm text-neutral-500">No commissions found.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($commissions->hasPages())
            <div class="px-6 py-4 border-t border-neutral-200">
                {{ $commissions->withQueryString()->links() }}
            </div>
        @endif
    </div>
</x-layouts.affiliate>
