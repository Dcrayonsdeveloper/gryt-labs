<x-layouts.admin :title="$title">
    <x-slot:breadcrumbs>
        @php $breadcrumbs = [['label' => 'Affiliates', 'url' => route('admin.affiliates.index')], ['label' => $affiliate->user->full_name]]; @endphp
    </x-slot:breadcrumbs>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Affiliate Details -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Profile Card -->
            <div class="card p-6">
                <div class="flex items-start justify-between mb-6">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 bg-primary-100 rounded-full flex items-center justify-center">
                            <span class="text-xl font-bold text-primary-600">{{ substr($affiliate->user->first_name, 0, 1) }}</span>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-neutral-900">{{ $affiliate->user->full_name }}</h2>
                            <p class="text-sm text-neutral-600">{{ $affiliate->user->email }}</p>
                            <p class="text-sm text-neutral-600">{{ $affiliate->user->phone ?? 'No phone' }}</p>
                        </div>
                    </div>
                    @php
                        $statusColors = [
                            'pending' => 'bg-warning-100 text-warning-700',
                            'approved' => 'bg-success-100 text-success-700',
                            'suspended' => 'bg-error-100 text-error-700',
                            'rejected' => 'bg-neutral-100 text-neutral-700',
                        ];
                    @endphp
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $statusColors[$affiliate->status] }}">
                        {{ ucfirst($affiliate->status) }}
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-neutral-500">Referral Code:</span>
                        <code class="ml-2 bg-neutral-100 px-2 py-0.5 rounded font-mono">{{ $affiliate->referral_code }}</code>
                    </div>
                    <div>
                        <span class="text-neutral-500">Platform:</span>
                        <span class="ml-2 font-medium">{{ ucfirst($affiliate->channel ?? '-') }}</span>
                    </div>
                    <div>
                        <span class="text-neutral-500">Commission Rate:</span>
                        <span class="ml-2 font-medium">{{ $affiliate->commission_rate }}%</span>
                    </div>
                    <div>
                        <span class="text-neutral-500">Joined:</span>
                        <span class="ml-2 font-medium">{{ $affiliate->created_at->format('d M Y') }}</span>
                    </div>
                    @if($affiliate->channel_url)
                    <div class="col-span-2">
                        <span class="text-neutral-500">Channel URL:</span>
                        <a href="{{ $affiliate->channel_url }}" target="_blank" class="ml-2 text-primary-600 hover:text-primary-700">
                            {{ $affiliate->channel_url }}
                        </a>
                    </div>
                    @endif
                    @if($affiliate->bio)
                    <div class="col-span-2">
                        <span class="text-neutral-500">Bio:</span>
                        <p class="mt-1 text-neutral-700">{{ $affiliate->bio }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Actions -->
            <div class="card p-6">
                <h3 class="font-semibold text-neutral-900 mb-4">Actions</h3>
                <div class="flex flex-wrap gap-3">
                    @if($affiliate->status === 'pending')
                        <form method="POST" action="{{ route('admin.affiliates.approve', $affiliate) }}">
                            @csrf
                            <button type="submit" class="btn-primary">Approve</button>
                        </form>
                        <form method="POST" action="{{ route('admin.affiliates.reject', $affiliate) }}" class="flex gap-2">
                            @csrf
                            <input type="text" name="reason" placeholder="Rejection reason (optional)" class="form-input text-sm">
                            <button type="submit" class="px-4 py-2 bg-error-600 text-white rounded-lg hover:bg-error-700 text-sm font-medium">Reject</button>
                        </form>
                    @endif
                    @if($affiliate->status === 'approved')
                        <form method="POST" action="{{ route('admin.affiliates.suspend', $affiliate) }}" class="flex gap-2">
                            @csrf
                            <input type="text" name="reason" placeholder="Suspension reason (optional)" class="form-input text-sm">
                            <button type="submit" class="px-4 py-2 bg-warning-600 text-white rounded-lg hover:bg-warning-700 text-sm font-medium">Suspend</button>
                        </form>
                    @endif
                    @if($affiliate->status === 'suspended' || $affiliate->status === 'rejected')
                        <form method="POST" action="{{ route('admin.affiliates.approve', $affiliate) }}">
                            @csrf
                            <button type="submit" class="btn-primary">Re-approve</button>
                        </form>
                    @endif
                </div>
                @if($affiliate->rejection_reason)
                    <p class="mt-3 text-sm text-error-600">Rejection reason: {{ $affiliate->rejection_reason }}</p>
                @endif
                @if($affiliate->suspension_reason)
                    <p class="mt-3 text-sm text-warning-600">Suspension reason: {{ $affiliate->suspension_reason }}</p>
                @endif
            </div>

            <!-- Recent Commissions -->
            <div class="card">
                <div class="px-6 py-4 border-b border-neutral-200">
                    <h3 class="font-semibold text-neutral-900">Recent Commissions</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-neutral-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Order</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Date</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-neutral-500 uppercase">Order Total</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-neutral-500 uppercase">Commission</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-neutral-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100">
                            @forelse($affiliate->commissions as $commission)
                                <tr>
                                    <td class="px-6 py-3 text-sm">{{ $commission->order?->order_number ?? '-' }}</td>
                                    <td class="px-6 py-3 text-sm text-neutral-600">{{ $commission->created_at->format('d M Y') }}</td>
                                    <td class="px-6 py-3 text-sm text-right">@price($commission->order_total)</td>
                                    <td class="px-6 py-3 text-sm font-medium text-success-600 text-right">+@price($commission->commission_amount)</td>
                                    <td class="px-6 py-3 text-center">
                                        @php
                                            $cColors = ['pending' => 'bg-warning-100 text-warning-700', 'approved' => 'bg-success-100 text-success-700', 'paid' => 'bg-primary-100 text-primary-700', 'cancelled' => 'bg-error-100 text-error-700'];
                                        @endphp
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $cColors[$commission->status] ?? '' }}">{{ ucfirst($commission->status) }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-6 py-8 text-center text-sm text-neutral-500">No commissions yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Sidebar Stats -->
        <div class="space-y-6">
            <div class="card p-6">
                <h3 class="font-semibold text-neutral-900 mb-4">Financial Summary</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-sm text-neutral-600">Total Earned</span>
                        <span class="font-semibold">@price($affiliate->total_earned)</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-neutral-600">Available</span>
                        <span class="font-semibold text-success-600">@price($affiliate->available_balance)</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-neutral-600">Pending</span>
                        <span class="font-semibold text-warning-600">@price($affiliate->pending_balance)</span>
                    </div>
                    <div class="border-t border-neutral-200 pt-3 mt-3">
                        <div class="flex justify-between">
                            <span class="text-sm text-neutral-600">Pending Commission</span>
                            <span class="font-semibold">@price($commissionStats['pending'])</span>
                        </div>
                        <div class="flex justify-between mt-2">
                            <span class="text-sm text-neutral-600">Approved Commission</span>
                            <span class="font-semibold">@price($commissionStats['approved'])</span>
                        </div>
                        <div class="flex justify-between mt-2">
                            <span class="text-sm text-neutral-600">Paid Commission</span>
                            <span class="font-semibold">@price($commissionStats['paid'])</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card p-6">
                <h3 class="font-semibold text-neutral-900 mb-4">Performance</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-sm text-neutral-600">Total Orders</span>
                        <span class="font-semibold">{{ $affiliate->total_orders }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-neutral-600">Total Clicks</span>
                        <span class="font-semibold">{{ $affiliate->total_clicks }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-neutral-600">Conversion Rate</span>
                        <span class="font-semibold">{{ $affiliate->total_clicks > 0 ? round(($affiliate->total_orders / $affiliate->total_clicks) * 100, 2) : 0 }}%</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-neutral-600">Commission Rate</span>
                        <span class="font-semibold">{{ $affiliate->commission_rate }}%</span>
                    </div>
                </div>
            </div>

            @if($affiliate->payout_method)
            <div class="card p-6">
                <h3 class="font-semibold text-neutral-900 mb-4">Payout Details</h3>
                <div class="space-y-2 text-sm">
                    <div><span class="text-neutral-500">Method:</span> <span class="font-medium">{{ $affiliate->payout_method === 'bank_transfer' ? 'Bank Transfer' : 'UPI' }}</span></div>
                    @if($affiliate->payout_method === 'upi' && $affiliate->upi_id)
                        <div><span class="text-neutral-500">UPI ID:</span> <span class="font-medium">{{ $affiliate->upi_id }}</span></div>
                    @endif
                    @if($affiliate->payout_method === 'bank_transfer' && $affiliate->bank_details)
                        <div><span class="text-neutral-500">Bank:</span> <span class="font-medium">{{ $affiliate->bank_details['bank_name'] ?? '-' }}</span></div>
                        <div><span class="text-neutral-500">Account:</span> <span class="font-medium">{{ $affiliate->bank_details['account_number'] ?? '-' }}</span></div>
                        <div><span class="text-neutral-500">IFSC:</span> <span class="font-medium">{{ $affiliate->bank_details['ifsc_code'] ?? '-' }}</span></div>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</x-layouts.admin>
