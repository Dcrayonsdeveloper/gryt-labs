<x-layouts.admin>
    <x-slot name="title">{{ $customer->full_name }} - Orders</x-slot>

    <div class="flex items-center gap-2 text-sm text-neutral-600 mb-6">
        <a href="{{ route('admin.customers.index') }}" class="hover:text-primary-600">Customers</a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
        <a href="{{ route('admin.customers.show', $customer) }}" class="hover:text-primary-600">{{ $customer->full_name }}</a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
        <span class="text-neutral-900">Orders</span>
    </div>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900">Orders by {{ $customer->full_name }}</h1>
            <p class="text-neutral-600">{{ $orders->total() }} orders total</p>
        </div>
        <a href="{{ route('admin.customers.show', $customer) }}" class="btn btn-secondary">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to Customer
        </a>
    </div>

    <!-- Orders Table -->
    <div class="card overflow-hidden">
        @if($orders->total() > 0)
            <div class="px-5 py-3 border-b border-neutral-200">
                {{ $orders->links('vendor.pagination.info-bar') }}
            </div>
        @endif
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-neutral-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Order</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Items</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Payment</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Paid / Due</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-neutral-600 uppercase">Total</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-neutral-600 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200">
                    @forelse($orders as $order)
                        @php
                            $oPaidOnline = (float) ($order->paid_amount ?? 0);
                            $oCodBalance = max(0, (float) $order->total - $oPaidOnline);
                            $oIsPartial  = $oCodBalance > 0 && $oPaidOnline > 0;
                        @endphp
                        <tr class="hover:bg-neutral-50">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.orders.show', $order) }}" class="font-medium text-primary-600 hover:text-primary-700">
                                    {{ $order->order_number }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-sm text-neutral-600">
                                {{ $order->created_at->format('M d, Y') }}
                                <span class="block text-xs">{{ $order->created_at->format('H:i') }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-neutral-600">{{ $order->items->count() }} items</td>
                            <td class="px-4 py-3">
                                <span class="badge {{ $order->status === 'completed' ? 'badge-success' : ($order->status === 'pending' ? 'badge-warning' : ($order->status === 'cancelled' ? 'badge-error' : 'badge-info')) }}">
                                    {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="badge {{ $order->payment_status === 'paid' ? 'badge-success' : 'badge-warning' }}">
                                    {{ ucfirst($order->payment_status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-neutral-600">
                                @if($oIsPartial)
                                    <span class="text-green-600 font-medium">@price($oPaidOnline)</span>
                                    <span class="block text-xs text-amber-600">@price($oCodBalance) COD due</span>
                                @elseif($oPaidOnline > 0)
                                    <span class="text-green-600 font-medium">@price($oPaidOnline)</span>
                                @else
                                    <span class="text-amber-600">@price($order->total) COD</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm font-medium text-right">@price($order->total)</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.orders.show', $order) }}" class="btn-icon" title="View Order">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center">
                                <svg class="w-12 h-12 mx-auto text-neutral-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                </svg>
                                <h3 class="text-lg font-medium text-neutral-900 mb-2">No orders yet</h3>
                                <p class="text-neutral-600">This customer hasn't placed any orders.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($orders->hasPages())
            <div class="px-4 py-3 border-t border-neutral-200">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
