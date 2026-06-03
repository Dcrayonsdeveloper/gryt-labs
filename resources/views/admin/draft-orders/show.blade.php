<x-layouts.admin>
    <x-slot name="title">Draft Order #D{{ $draftOrder->id }}</x-slot>

    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm text-neutral-600 mb-1">
            <a href="{{ route('admin.draft-orders.index') }}" class="hover:text-primary-600">Draft Orders</a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-neutral-900">#D{{ $draftOrder->id }}</span>
        </div>
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-neutral-900">Draft Order #D{{ $draftOrder->id }}</h1>
                @php
                    $statusColors = [
                        'draft' => 'bg-gray-100 text-gray-700',
                        'sent' => 'bg-blue-100 text-blue-700',
                        'completed' => 'bg-green-100 text-green-700',
                        'cancelled' => 'bg-red-100 text-red-700',
                    ];
                @endphp
                <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-medium {{ $statusColors[$draftOrder->status] ?? 'bg-gray-100 text-gray-700' }}">
                    {{ ucfirst($draftOrder->status) }}
                </span>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Line Items --}}
            <div class="card">
                <div class="card-header">
                    <h2 class="font-semibold text-neutral-900">Items</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-neutral-500 uppercase" style="border-bottom:1px solid #e5e5e5">
                                <th class="px-4 py-2.5 font-medium">Product</th>
                                <th class="px-4 py-2.5 font-medium text-center">Qty</th>
                                <th class="px-4 py-2.5 font-medium text-right">Price</th>
                                <th class="px-4 py-2.5 font-medium text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100">
                            @foreach($draftOrder->items ?? [] as $item)
                                @php
                                    $product = $products[$item['product_id']] ?? null;
                                @endphp
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            @if($product && $product->primary_image_url)
                                                <img src="{{ $product->primary_image_url }}" alt="" class="w-10 h-10 rounded-lg object-cover border border-neutral-200">
                                            @else
                                                <div class="w-10 h-10 rounded-lg bg-neutral-100 flex items-center justify-center">
                                                    <svg class="w-5 h-5 text-neutral-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                                </div>
                                            @endif
                                            <div>
                                                <p class="font-medium text-neutral-900">{{ $item['product_name'] ?? 'Unknown Product' }}</p>
                                                @if(!empty($item['sku']))
                                                    <p class="text-xs text-neutral-500">SKU: {{ $item['sku'] }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center">{{ $item['quantity'] }}</td>
                                    <td class="px-4 py-3 text-right">@price($item['price'])</td>
                                    <td class="px-4 py-3 text-right font-medium">@price($item['price'] * $item['quantity'])</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Totals --}}
                <div class="px-4 py-4 space-y-2" style="border-top:1px solid #e5e5e5">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-neutral-600">Subtotal</span>
                        <span class="font-medium">@price($draftOrder->subtotal)</span>
                    </div>
                    @if($draftOrder->discount > 0)
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-neutral-600">Discount</span>
                            <span class="text-green-600 font-medium">-@price($draftOrder->discount)</span>
                        </div>
                    @endif
                    @if($draftOrder->shipping_cost > 0)
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-neutral-600">Shipping</span>
                            <span class="font-medium">@price($draftOrder->shipping_cost)</span>
                        </div>
                    @endif
                    @if($draftOrder->tax > 0)
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-neutral-600">Tax</span>
                            <span class="font-medium">@price($draftOrder->tax)</span>
                        </div>
                    @endif
                    <div class="flex items-center justify-between text-base font-semibold border-t border-neutral-200 pt-2">
                        <span>Total</span>
                        <span>@price($draftOrder->total)</span>
                    </div>
                </div>
            </div>

            {{-- Notes --}}
            @if($draftOrder->notes)
                <div class="card">
                    <div class="card-header">
                        <h2 class="font-semibold text-neutral-900">Notes</h2>
                    </div>
                    <div class="p-4">
                        <p class="text-sm text-neutral-700 whitespace-pre-wrap">{{ $draftOrder->notes }}</p>
                    </div>
                </div>
            @endif

            {{-- Completed Order Link --}}
            @if($draftOrder->isCompleted() && $draftOrder->order)
                <div class="card">
                    <div class="card-header">
                        <h2 class="font-semibold text-neutral-900">Converted Order</h2>
                    </div>
                    <div class="p-4">
                        <a href="{{ route('admin.orders.show', $draftOrder->order_id) }}" class="text-primary-600 hover:text-primary-700 font-medium text-sm">
                            View Order #{{ $draftOrder->order->order_number }}
                        </a>
                    </div>
                </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Customer --}}
            <div class="card">
                <div class="card-header">
                    <h2 class="font-semibold text-neutral-900">Customer</h2>
                </div>
                <div class="p-4 space-y-2 text-sm">
                    @if($draftOrder->customer_name)
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            <span class="text-neutral-900">{{ $draftOrder->customer_name }}</span>
                        </div>
                    @endif
                    @if($draftOrder->customer_email)
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            <span class="text-neutral-700">{{ $draftOrder->customer_email }}</span>
                        </div>
                    @endif
                    @if($draftOrder->customer_phone)
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            <span class="text-neutral-700">{{ $draftOrder->customer_phone }}</span>
                        </div>
                    @endif
                    @if($draftOrder->customer_id)
                        <div class="mt-2 pt-2 border-t border-neutral-100">
                            <a href="{{ route('admin.customers.show', $draftOrder->customer_id) }}" class="text-xs text-primary-600 hover:text-primary-700">View customer profile</a>
                        </div>
                    @endif
                    @if(!$draftOrder->customer_name && !$draftOrder->customer_email && !$draftOrder->customer_phone)
                        <p class="text-neutral-400">No customer information</p>
                    @endif
                </div>
            </div>

            {{-- Info --}}
            <div class="card">
                <div class="card-header">
                    <h2 class="font-semibold text-neutral-900">Details</h2>
                </div>
                <div class="p-4 space-y-2.5 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-neutral-600">Created by</span>
                        <span class="font-medium text-neutral-800">{{ $draftOrder->admin?->name ?? 'Admin' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-neutral-600">Created</span>
                        <span class="text-neutral-700">{{ $draftOrder->created_at->format('M d, Y H:i') }}</span>
                    </div>
                    @if($draftOrder->sent_at)
                        <div class="flex items-center justify-between">
                            <span class="text-neutral-600">Sent</span>
                            <span class="text-neutral-700">{{ $draftOrder->sent_at->format('M d, Y H:i') }}</span>
                        </div>
                    @endif
                    @if($draftOrder->completed_at)
                        <div class="flex items-center justify-between">
                            <span class="text-neutral-600">Completed</span>
                            <span class="text-neutral-700">{{ $draftOrder->completed_at->format('M d, Y H:i') }}</span>
                        </div>
                    @endif
                    @if($draftOrder->payment_link)
                        <div class="border-t border-neutral-100 pt-2">
                            <p class="text-xs text-neutral-500 mb-1">Payment Link</p>
                            <input type="text" value="{{ $draftOrder->payment_link }}" class="form-input w-full text-xs" readonly onclick="this.select()">
                        </div>
                    @endif
                </div>
            </div>

            {{-- Actions --}}
            @if(!$draftOrder->isCompleted() && !$draftOrder->isCancelled())
                <div class="flex flex-col gap-3">
                    <form action="{{ route('admin.draft-orders.complete', $draftOrder) }}" method="POST"
                          onsubmit="return confirm('Convert this draft into a real order? This will create an order and decrement stock.')">
                        @csrf
                        <button type="submit" class="btn btn-primary w-full justify-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Complete Order
                        </button>
                    </form>

                    @if(!$draftOrder->isSent())
                        <form action="{{ route('admin.draft-orders.send', $draftOrder) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-secondary w-full justify-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                Send Invoice
                            </button>
                        </form>
                    @endif

                    <form action="{{ route('admin.draft-orders.destroy', $draftOrder) }}" method="POST"
                          onsubmit="return confirm('Delete this draft order? This cannot be undone.')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger w-full justify-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Delete Draft
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</x-layouts.admin>
