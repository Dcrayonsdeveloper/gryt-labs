<x-layouts.admin :title="$title">
    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="card p-6">
            <p class="text-sm text-neutral-600">Pending Payouts</p>
            <p class="text-2xl font-bold text-warning-600 mt-1">@price($stats['pending'])</p>
        </div>
        <div class="card p-6">
            <p class="text-sm text-neutral-600">Processing</p>
            <p class="text-2xl font-bold text-info-600 mt-1">@price($stats['processing'])</p>
        </div>
        <div class="card p-6">
            <p class="text-sm text-neutral-600">Total Completed</p>
            <p class="text-2xl font-bold text-success-600 mt-1">@price($stats['completed'])</p>
        </div>
    </div>

    <!-- Filter Tabs + Table -->
    <div class="card">
        <div class="border-b border-neutral-200 px-6">
            <nav class="flex gap-6 -mb-px">
                @php $tabs = ['' => 'All', 'pending' => 'Pending', 'processing' => 'Processing', 'completed' => 'Completed', 'failed' => 'Failed']; @endphp
                @foreach($tabs as $key => $label)
                    <a href="{{ route('admin.affiliates.redemptions', $key ? ['status' => $key] : []) }}"
                       class="py-4 text-sm font-medium border-b-2 {{ ($currentStatus ?? '') === $key ? 'border-primary-600 text-primary-600' : 'border-transparent text-neutral-600 hover:text-neutral-900' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </nav>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-neutral-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Affiliate</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Method</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-neutral-500 uppercase">Amount</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-neutral-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-neutral-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @forelse($redemptions as $redemption)
                        <tr class="hover:bg-neutral-50">
                            <td class="px-6 py-4 text-sm font-medium">#{{ $redemption->id }}</td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-neutral-900">{{ $redemption->affiliate->user->full_name }}</div>
                                <div class="text-xs text-neutral-500">{{ $redemption->affiliate->referral_code }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-neutral-600">{{ $redemption->created_at->format('d M Y') }}</td>
                            <td class="px-6 py-4 text-sm">{{ $redemption->payout_method === 'bank_transfer' ? 'Bank' : 'UPI' }}</td>
                            <td class="px-6 py-4 text-sm font-medium text-right">@price($redemption->amount)</td>
                            <td class="px-6 py-4 text-center">
                                @php
                                    $sColors = ['pending' => 'bg-warning-100 text-warning-700', 'processing' => 'bg-info-100 text-info-700', 'completed' => 'bg-success-100 text-success-700', 'failed' => 'bg-error-100 text-error-700'];
                                @endphp
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $sColors[$redemption->status] ?? '' }}">
                                    {{ ucfirst($redemption->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    @if($redemption->status === 'pending')
                                        <form method="POST" action="{{ route('admin.affiliates.redemptions.process', $redemption) }}">
                                            @csrf
                                            <button type="submit" class="text-xs bg-info-600 text-white px-3 py-1 rounded hover:bg-info-700">Process</button>
                                        </form>
                                    @endif
                                    @if($redemption->status === 'processing')
                                        <form method="POST" action="{{ route('admin.affiliates.redemptions.complete', $redemption) }}" class="flex gap-1">
                                            @csrf
                                            <input type="text" name="transaction_id" placeholder="Txn ID" class="form-input text-xs w-24 py-1">
                                            <button type="submit" class="text-xs bg-success-600 text-white px-3 py-1 rounded hover:bg-success-700">Complete</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.affiliates.redemptions.fail', $redemption) }}" x-data
                                              @submit.prevent="if(confirm('This will refund the amount back. Continue?')) $el.submit()">
                                            @csrf
                                            <input type="hidden" name="reason" value="Payment failed">
                                            <button type="submit" class="text-xs bg-error-600 text-white px-3 py-1 rounded hover:bg-error-700">Fail</button>
                                        </form>
                                    @endif
                                    @if($redemption->status === 'completed')
                                        <span class="text-xs text-neutral-500">{{ $redemption->transaction_id ?? '-' }}</span>
                                    @endif
                                    @if($redemption->status === 'failed')
                                        <span class="text-xs text-error-600">{{ $redemption->failure_reason }}</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-sm text-neutral-500">No redemptions found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($redemptions->hasPages())
            <div class="px-6 py-4 border-t border-neutral-200">
                {{ $redemptions->withQueryString()->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
