<x-layouts.admin :title="$title">
    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="card p-6">
            <p class="text-sm text-neutral-600">Total Affiliates</p>
            <p class="text-2xl font-bold text-neutral-900 mt-1">{{ $stats['total'] }}</p>
        </div>
        <div class="card p-6">
            <p class="text-sm text-neutral-600">Pending Approval</p>
            <p class="text-2xl font-bold text-warning-600 mt-1">{{ $stats['pending'] }}</p>
        </div>
        <div class="card p-6">
            <p class="text-sm text-neutral-600">Approved</p>
            <p class="text-2xl font-bold text-success-600 mt-1">{{ $stats['approved'] }}</p>
        </div>
        <div class="card p-6">
            <p class="text-sm text-neutral-600">Suspended</p>
            <p class="text-2xl font-bold text-error-600 mt-1">{{ $stats['suspended'] }}</p>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="mb-6 flex gap-4">
        <a href="{{ route('admin.affiliates.redemptions') }}" class="text-sm text-primary-600 hover:text-primary-700 font-medium">
            View Pending Redemptions &rarr;
        </a>
    </div>

    <!-- Filter Tabs + Table -->
    <div class="card">
        <div class="border-b border-neutral-200 px-6">
            <nav class="flex gap-6 -mb-px">
                @php
                    $tabs = ['' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'suspended' => 'Suspended', 'rejected' => 'Rejected'];
                @endphp
                @foreach($tabs as $key => $label)
                    <a href="{{ route('admin.affiliates.index', $key ? ['status' => $key] : []) }}"
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Affiliate</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Channel</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Code</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-neutral-500 uppercase">Earned</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-neutral-500 uppercase">Balance</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-neutral-500 uppercase">Orders</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-neutral-500 uppercase">Clicks</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-neutral-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-neutral-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @forelse($affiliates as $affiliate)
                        <tr class="hover:bg-neutral-50">
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-neutral-900">{{ $affiliate->user->full_name }}</div>
                                <div class="text-xs text-neutral-500">{{ $affiliate->user->email }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-neutral-600">{{ ucfirst($affiliate->channel ?? '-') }}</td>
                            <td class="px-6 py-4">
                                <code class="text-xs bg-neutral-100 px-2 py-0.5 rounded">{{ $affiliate->referral_code }}</code>
                            </td>
                            <td class="px-6 py-4 text-sm text-neutral-900 text-right">@price($affiliate->total_earned)</td>
                            <td class="px-6 py-4 text-sm text-neutral-900 text-right">@price($affiliate->available_balance)</td>
                            <td class="px-6 py-4 text-sm text-neutral-600 text-center">{{ $affiliate->total_orders }}</td>
                            <td class="px-6 py-4 text-sm text-neutral-600 text-center">{{ $affiliate->total_clicks }}</td>
                            <td class="px-6 py-4 text-center">
                                @php
                                    $statusColors = [
                                        'pending' => 'bg-warning-100 text-warning-700',
                                        'approved' => 'bg-success-100 text-success-700',
                                        'suspended' => 'bg-error-100 text-error-700',
                                        'rejected' => 'bg-neutral-100 text-neutral-700',
                                    ];
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$affiliate->status] ?? 'bg-neutral-100 text-neutral-700' }}">
                                    {{ ucfirst($affiliate->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <a href="{{ route('admin.affiliates.show', $affiliate) }}" class="text-primary-600 hover:text-primary-700 text-sm font-medium">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center text-sm text-neutral-500">No affiliates found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($affiliates->hasPages())
            <div class="px-6 py-4 border-t border-neutral-200">
                {{ $affiliates->withQueryString()->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
