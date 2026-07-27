<x-layouts.admin>
    <x-slot name="title">Order {{ $order->order_number }}</x-slot>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2 text-sm text-neutral-600 mb-1">
                    <a href="{{ route('admin.orders.index') }}" class="hover:text-primary-600 transition-colors">Orders</a>
                    <svg class="w-3.5 h-3.5 text-neutral-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                    <span class="text-neutral-900">{{ $order->order_number }}</span>
                </div>
                <h1 class="text-2xl font-bold text-neutral-900">Order {{ $order->order_number }}</h1>
            </div>
            <div class="flex items-center gap-2">
                @if(in_array($order->status, ['confirmed', 'processing', 'packed']))
                    <a href="{{ route('admin.orders.edit', $order) }}" class="btn btn-secondary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edit Order
                    </a>
                @endif
                <a href="{{ route('admin.orders.invoice', $order) }}" class="btn btn-secondary" target="_blank">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Print Invoice
                </a>
                <a href="{{ route('admin.orders.packing-slip', $order) }}" class="btn btn-secondary" target="_blank">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Packing Slip
                </a>
            </div>
        </div>
    </x-slot>

    {{-- Order Tracking Timeline --}}
    @if(!in_array($order->status, ['pending', 'cancelled', 'returned']))
        <div class="card mb-6">
            <div class="px-5 py-4 border-b border-neutral-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <h2 class="font-semibold text-neutral-900">Order Tracking</h2>
                @if($latestShipment && $latestShipment->tracking_number)
                    <div class="flex items-center gap-2 text-sm">
                        <span class="text-neutral-600">Tracking ID:</span>
                        <span class="font-mono font-semibold text-primary-600">{{ $latestShipment->tracking_number }}</span>
                        @if($latestShipment->carrier)
                            <span class="badge badge-info">{{ $latestShipment->carrier }}</span>
                        @endif
                    </div>
                @endif
            </div>
            <div class="p-6">
                <div class="relative">
                    <div class="flex items-start justify-between">
                        @foreach($trackingSteps as $index => $step)
                            <div class="flex-1 {{ $index < count($trackingSteps) - 1 ? 'relative' : '' }}">
                                <div class="flex flex-col items-center">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center z-10 relative transition-all
                                        {{ $step['completed'] ? 'bg-success-500 text-white' : ($step['current'] ? 'bg-primary-500 text-white ring-4 ring-primary-100' : 'bg-neutral-200 text-neutral-600') }}">
                                        @if($step['completed'] && !$step['current'])
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        @elseif($step['icon'] === 'clipboard-check')
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                            </svg>
                                        @elseif($step['icon'] === 'cube')
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                            </svg>
                                        @elseif($step['icon'] === 'truck')
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                                            </svg>
                                        @elseif($step['icon'] === 'map-pin')
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                        @elseif($step['icon'] === 'check-circle')
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        @endif
                                    </div>
                                    <p class="mt-2 text-xs font-semibold text-center {{ $step['completed'] || $step['current'] ? 'text-neutral-900' : 'text-neutral-600' }}">
                                        {{ $step['label'] }}
                                    </p>
                                    @if($step['timestamp'])
                                        <p class="text-xs text-neutral-600 text-center mt-0.5">
                                            {{ $step['timestamp']->format('M d, h:i A') }}
                                        </p>
                                    @endif
                                </div>
                                @if($index < count($trackingSteps) - 1)
                                    <div class="absolute top-5 left-1/2 w-full h-0.5 {{ $trackingSteps[$index + 1]['completed'] || $trackingSteps[$index + 1]['current'] ? 'bg-success-500' : 'bg-neutral-200' }}"></div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Order Items -->
            <div class="bg-white rounded-[8px] p-4" style="border: 2px solid rgb(240, 240, 240);">
                <div class="flex items-center gap-2">
                    <span role="img" aria-label="code-sandbox" class="anticon anticon-code-sandbox text-md text-neutral-800 leading-none">
                        <svg viewBox="64 64 896 896" focusable="false" data-icon="code-sandbox" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M709.6 210l.4-.2h.2L512 96 313.9 209.8h-.2l.7.3L151.5 304v416L512 928l360.5-208V304l-162.9-94zM482.7 843.6L339.6 761V621.4L210 547.8V372.9l272.7 157.3v313.4zM238.2 321.5l134.7-77.8 138.9 79.7 139.1-79.9 135.2 78-273.9 158-274-158zM814 548.3l-128.8 73.1v139.1l-143.9 83V530.4L814 373.1v175.2z"></path></svg>
                    </span>
                    <h3 class="text-lg font-bold m-0 text-neutral-900">Item Details</h3>
                    <span class="text-[12px] text-[#38446D] ml-auto">{{ $order->items->count() }} {{ Str::plural('item', $order->items->count()) }}</span>
                </div>
                <div class="flex flex-col gap-2 mt-4">
                    @foreach($order->items as $item)
                        <div class="flex items-center justify-between px-4 py-2 rounded-lg" style="border: 2px solid rgb(240, 240, 240);">
                            <div class="flex items-center space-x-4 min-w-0">
                                <div class="w-12 h-12 bg-[#F0F0F0] rounded-lg flex items-center justify-center shrink-0 overflow-hidden">
                                    @if($item->product->primary_image_url ?? null)
                                        <img src="{{ $item->product->primary_image_url }}" alt="{{ $item->product_name }}" class="rounded-lg w-full h-full object-contain">
                                    @else
                                        <svg class="w-6 h-6 text-neutral-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    @endif
                                </div>
                                <div class="text-left flex flex-col min-w-0">
                                    <span class="text-sm text-[#0F172A] font-semibold">{{ $item->product_name }}</span>
                                    @if($item->variant_name)
                                        <span class="text-[12px] text-[#38446D]">{{ $item->variant_name }}</span>
                                    @endif
                                    @if($item->sku)
                                        <span class="text-[12px] text-[#38446D]">SKU: {{ $item->sku }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="text-right flex flex-col shrink-0 ml-3">
                                <span class="text-[12px] font-medium text-[#38446D]">Qty: {{ $item->quantity }}</span>
                                <span class="text-sm text-[#0F172A] font-semibold">{{ $order->currency }} {{ number_format($item->total, 2) }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Shipping Address -->
            <div class="card overflow-hidden">
                <div class="px-5 py-4 border-b border-neutral-200">
                    <h2 class="font-semibold text-neutral-900">Shipping Address</h2>
                </div>
                <div class="p-5">
                    @php $shipping = $order->shipping_address_snapshot; @endphp
                    @if($shipping)
                        <div class="flex items-start gap-3">
                            <div class="w-9 h-9 rounded-lg bg-neutral-100 flex items-center justify-center shrink-0 mt-0.5">
                                <svg class="w-4.5 h-4.5 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <div class="text-sm text-neutral-600 space-y-0.5">
                                <p class="font-semibold text-neutral-900">{{ $shipping['name'] ?? ($shipping['first_name'] ?? '') . ' ' . ($shipping['last_name'] ?? '') }}</p>
                                @if(!empty($shipping['phone'])) <p>{{ $shipping['phone'] }}</p> @endif
                                @if(!empty($shipping['address'])) <p>{{ $shipping['address'] }}</p> @endif
                                @if(!empty($shipping['address_line_1'])) <p>{{ $shipping['address_line_1'] }}</p> @endif
                                <p>{{ $shipping['city'] ?? '' }}{{ !empty($shipping['state']) ? ', ' . $shipping['state'] : '' }} {{ $shipping['postal_code'] ?? $shipping['zip'] ?? '' }}</p>
                            </div>
                        </div>
                    @elseif($order->user)
                        <p class="text-sm text-neutral-600">{{ $order->user->full_name }}</p>
                        <p class="text-sm text-neutral-600">{{ $order->user->email }}</p>
                    @elseif($order->guest_name || $order->guest_phone)
                        <div class="text-sm text-neutral-600 space-y-0.5">
                            @if($order->guest_name) <p class="font-semibold text-neutral-900">{{ $order->guest_name }}</p> @endif
                            @if($order->guest_phone) <p>{{ $order->guest_phone }}</p> @endif
                            @if($order->guest_email) <p>{{ $order->guest_email }}</p> @endif
                        </div>
                    @endif
                </div>
            </div>

            <!-- Order Notes -->
            @if($order->notes)
                <div class="card overflow-hidden">
                    <div class="px-5 py-4 border-b border-neutral-200">
                        <h2 class="font-semibold text-neutral-900">Order Notes</h2>
                    </div>
                    <div class="p-5">
                        <p class="text-sm text-neutral-600">{{ $order->notes }}</p>
                    </div>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Update Order Status -->
            @php $transitions = \App\Models\Order::ALLOWED_TRANSITIONS[$order->status] ?? []; @endphp
            <div class="card overflow-hidden" x-data="{ status: '{{ $transitions[0] ?? '' }}' }">
                <div class="px-5 py-4 border-b border-neutral-200 flex items-center justify-between">
                    <h2 class="font-semibold text-neutral-900">Update Status</h2>
                    @php
                        $currentClass = match($order->status) {
                            'delivered', 'completed' => 'badge-success',
                            'confirmed' => 'badge-warning',
                            'processing', 'packed' => 'badge-info',
                            'shipped', 'out_for_delivery' => 'badge-primary',
                            'cancelled', 'returned' => 'badge-error',
                            default => 'badge-neutral',
                        };
                    @endphp
                    <div class="flex items-center gap-2">
                        <span class="badge {{ $currentClass }}">
                            {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                        </span>
                        @php
                            $revertTo = [
                                'processing'       => 'Confirmed',
                                'packed'           => 'Processing',
                                'shipped'          => 'Packed',
                                'out_for_delivery' => 'Shipped',
                                'delivered'        => 'Out for Delivery',
                                'cancelled'        => 'Confirmed',
                                'returned'         => 'Delivered',
                            ][$order->status] ?? null;
                        @endphp
                        @if($revertTo)
                            <form action="{{ route('admin.orders.revert', $order) }}" method="POST"
                                  onsubmit="return confirm('Revert this order one step back to {{ $revertTo }}?')">
                                @csrf
                                <button type="submit" title="Revert to {{ $revertTo }}"
                                        class="p-1 rounded-md text-neutral-400 hover:text-primary-600 hover:bg-neutral-100 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a5 5 0 010 10h-1M3 10l5-5M3 10l5 5"/>
                                    </svg>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
                <div class="p-5">
                    <form action="{{ route('admin.orders.status', $order) }}" method="POST" class="space-y-4">
                        @csrf
                        @method('PUT')
                        <div>
                            <label class="form-label">New Status</label>
                            <select name="status" x-model="status" class="form-select w-full">
                                @php
                                    $labels = [
                                        'confirmed' => 'Confirmed', 'processing' => 'Processing', 'packed' => 'Packed',
                                        'shipped' => 'Shipped', 'out_for_delivery' => 'Out for Delivery',
                                        'delivered' => 'Delivered', 'cancelled' => 'Cancelled', 'returned' => 'Returned',
                                    ];
                                @endphp
                                @forelse($transitions as $status)
                                    <option value="{{ $status }}">{{ $labels[$status] ?? ucfirst($status) }}</option>
                                @empty
                                    <option value="" disabled>No transitions available</option>
                                @endforelse
                            </select>
                        </div>

                        {{-- Hint to use Fulfillment card for shipping --}}
                        <div x-show="status === 'shipped'" x-transition x-cloak>
                            <p class="text-xs text-amber-600 bg-amber-50 rounded-md px-3 py-2">
                                <svg class="w-3.5 h-3.5 inline -mt-0.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Use the <strong>Shipping & Fulfillment</strong> card below to add carrier & tracking details.
                            </p>
                        </div>

                        <div>
                            <label class="form-label">Note (optional)</label>
                            <textarea name="comment" rows="2" class="form-textarea w-full" placeholder="Add a note..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-full">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Update Status
                        </button>
                    </form>
                </div>
            </div>

            <!-- Shipping & Fulfillment -->
            @if(in_array($order->status, ['confirmed', 'processing', 'packed', 'shipped', 'out_for_delivery', 'delivered']))
                @php
                    $hasDelhiveryConfig = app(\App\Services\DelhiveryService::class)->isConfigured();
                    $hasBluedartConfig = app(\App\Services\BlueDartService::class)->isConfigured();
                @endphp
                <div class="card overflow-hidden">
                    <div class="px-5 py-4 border-b border-neutral-200 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25"/></svg>
                            <h2 class="font-semibold text-neutral-900">Shipping & Fulfillment</h2>
                        </div>
                        @if($order->tracking_number || $latestShipment?->tracking_number)
                            <span class="badge badge-success">Fulfilled</span>
                        @elseif(in_array($order->status, ['confirmed', 'processing', 'packed']))
                            <span class="badge badge-warning">Unfulfilled</span>
                        @endif
                    </div>
                    <div class="p-5">
                        {{-- STATE 1: Unfulfilled — show fulfillment form --}}
                        @if(!$order->tracking_number && !$latestShipment?->tracking_number && in_array($order->status, ['confirmed', 'processing', 'packed']))
                            <div x-data="{
                                carrier: '', notifyCustomer: true, showAdd: false, newCarrier: '', adding: false,
                                carriers: {{ \Illuminate\Support\Js::from(\App\Http\Controllers\Admin\OrderController::allCarriers()) }},
                                async addCarrier() {
                                    const name = this.newCarrier.trim();
                                    if (!name) return;
                                    this.adding = true;
                                    try {
                                        const res = await fetch('{{ route('admin.orders.carriers.store') }}', {
                                            method: 'POST',
                                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                                            body: JSON.stringify({ name })
                                        });
                                        const data = await res.json();
                                        if (res.ok && data.carriers) {
                                            this.carriers = data.carriers;
                                            this.carrier = name;
                                            this.newCarrier = '';
                                            this.showAdd = false;
                                        } else {
                                            alert(data.message || 'Could not add carrier.');
                                        }
                                    } catch (e) { alert('Could not add carrier.'); }
                                    finally { this.adding = false; }
                                }
                            }">
                                {{-- Manual Fulfillment Form --}}
                                <form action="{{ route('admin.orders.ship', $order) }}" method="POST" onsubmit="return confirm('Fulfill this order and mark as shipped?')">
                                    @csrf
                                    <div class="space-y-3">
                                        <div>
                                            <label class="form-label flex items-center justify-between">
                                                <span>Shipping Carrier</span>
                                                <button type="button" @click="showAdd = !showAdd" title="Add a new carrier"
                                                        class="text-primary-600 hover:text-primary-700 p-0.5 -my-1 rounded">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                                    </svg>
                                                </button>
                                            </label>
                                            <select name="carrier" x-model="carrier" class="form-select w-full text-sm" required>
                                                <option value="">Select a carrier...</option>
                                                <template x-for="c in carriers" :key="c">
                                                    <option :value="c" x-text="c"></option>
                                                </template>
                                                <option value="other">Other...</option>
                                            </select>

                                            {{-- Add a new carrier (saved for future orders) --}}
                                            <div x-show="showAdd" x-transition x-cloak class="mt-2 flex gap-2">
                                                <input type="text" x-model="newCarrier" placeholder="New carrier name"
                                                       class="form-input w-full text-sm" @keydown.enter.prevent="addCarrier()">
                                                <button type="button" @click="addCarrier()" :disabled="adding || !newCarrier.trim()"
                                                        class="btn btn-secondary text-sm whitespace-nowrap" x-text="adding ? 'Adding…' : 'Add'"></button>
                                            </div>
                                        </div>

                                        {{-- Custom carrier name when "Other" selected --}}
                                        <div x-show="carrier === 'other'" x-transition x-cloak>
                                            <label class="form-label">Carrier Name</label>
                                            <input type="text" name="carrier_custom" class="form-input w-full text-sm" placeholder="Enter carrier name">
                                        </div>

                                        <div>
                                            <label class="form-label">Tracking Number</label>
                                            <input type="text" name="tracking_number" class="form-input w-full text-sm" placeholder="Enter tracking number (optional)">
                                        </div>

                                        <div>
                                            <label class="form-label">Tracking URL</label>
                                            <input type="url" name="tracking_url" class="form-input w-full text-sm" placeholder="Shipping partner tracking link (optional)">
                                        </div>

                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="checkbox" name="notify_customer" value="1" x-model="notifyCustomer" class="form-checkbox rounded text-primary-600">
                                            <span class="text-sm text-neutral-700">Send shipping notification to customer</span>
                                        </label>
                                    </div>

                                    <button type="submit" class="btn btn-primary w-full mt-4">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Fulfill Order
                                    </button>
                                </form>

                                {{-- Auto-book options (only shows carriers that are configured) --}}
                                @if($hasDelhiveryConfig || $hasBluedartConfig)
                                    <div class="relative my-4">
                                        <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-neutral-200"></div></div>
                                        <div class="relative flex justify-center"><span class="bg-white px-3 text-xs text-neutral-500 uppercase">or auto-book</span></div>
                                    </div>
                                    <div class="space-y-2">
                                        @if($hasBluedartConfig)
                                            <form action="{{ route('admin.delivery.book', $order) }}" method="POST" onsubmit="return confirm('Book shipment via BlueDart API?')">
                                                @csrf
                                                <input type="hidden" name="carrier" value="bluedart">
                                                <input type="hidden" name="notify_customer" value="1" :value="typeof notifyCustomer !== 'undefined' ? (notifyCustomer ? '1' : '0') : '1'">
                                                <button type="submit" class="btn btn-outline w-full border-red-300 text-red-700 hover:bg-red-50">
                                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                                    Auto-Book via BlueDart
                                                </button>
                                                <p class="text-[10px] text-neutral-500 mt-1.5 text-center">One-click AWB generation + pickup scheduling</p>
                                            </form>
                                        @endif
                                        @if($hasDelhiveryConfig)
                                            <form action="{{ route('admin.delivery.book', $order) }}" method="POST" onsubmit="return confirm('Book shipment via Delhivery API?')">
                                                @csrf
                                                <input type="hidden" name="carrier" value="delhivery">
                                                <input type="hidden" name="notify_customer" value="1" :value="typeof notifyCustomer !== 'undefined' ? (notifyCustomer ? '1' : '0') : '1'">
                                                <button type="submit" class="btn btn-outline w-full border-blue-300 text-blue-700 hover:bg-blue-50">
                                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                                    Auto-Book via Delhivery
                                                </button>
                                                <p class="text-[10px] text-neutral-500 mt-1.5 text-center">One-click booking with auto-generated tracking</p>
                                            </form>
                                        @endif
                                    </div>
                                @endif
                            </div>

                        {{-- STATE 2: Fulfilled — show shipment details --}}
                        @elseif($order->tracking_number || $latestShipment?->tracking_number)
                            @php
                                $shipCarrier = $order->carrier ?: $latestShipment?->carrier;
                                $shipTracking = $order->tracking_number ?: $latestShipment?->tracking_number;
                                $shipDate = $order->shipped_at ?: $latestShipment?->shipped_at;
                                $isDelhivery = $shipCarrier === 'Delhivery' && $hasDelhiveryConfig;
                                $isBlueDart = $shipCarrier === 'BlueDart';
                                $hasCarrierActions = $isDelhivery || $isBlueDart;
                            @endphp
                            <div class="space-y-3">
                                <div class="flex items-start gap-3">
                                    <div class="w-9 h-9 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                        <svg class="w-4.5 h-4.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-semibold text-neutral-900">Order Fulfilled</p>
                                        @if($shipDate)
                                            <p class="text-xs text-neutral-500">{{ \Carbon\Carbon::parse($shipDate)->format('M d, Y \a\t h:i A') }}</p>
                                        @endif
                                    </div>
                                </div>

                                <div class="bg-neutral-50 rounded-lg p-3 space-y-2">
                                    <div class="flex justify-between items-center">
                                        <span class="text-xs text-neutral-500">Carrier</span>
                                        <span class="text-sm font-medium text-neutral-900">{{ $shipCarrier ?: 'Manual' }}</span>
                                    </div>
                                    @if($shipTracking)
                                        <div class="flex justify-between items-center">
                                            <span class="text-xs text-neutral-500">Tracking #</span>
                                            <div class="flex items-center gap-1.5" x-data="{ copied: false }">
                                                <span class="font-mono text-sm font-medium text-primary-600">{{ $shipTracking }}</span>
                                                <button type="button" @click="navigator.clipboard.writeText('{{ $shipTracking }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                                        class="text-neutral-400 hover:text-neutral-600 transition-colors" title="Copy">
                                                    <svg x-show="!copied" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                                    <svg x-show="copied" x-cloak class="w-3.5 h-3.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                </button>
                                            </div>
                                        </div>
                                    @endif
                                    @if($latestShipment?->status)
                                        <div class="flex justify-between items-center">
                                            <span class="text-xs text-neutral-500">Status</span>
                                            <span class="badge badge-info text-xs">{{ ucfirst(str_replace('_', ' ', $latestShipment->status)) }}</span>
                                        </div>
                                    @endif
                                </div>

                                {{-- Carrier actions (Delhivery / BlueDart) --}}
                                @if($hasCarrierActions)
                                    <div class="flex gap-2 pt-1">
                                        <a href="{{ route('admin.delivery.label', $order) }}" class="btn btn-outline text-xs flex-1">
                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            Label
                                        </a>
                                        <button onclick="fetch('{{ route('admin.delivery.track', $order) }}').then(r=>r.json()).then(d=>alert(d.success ? 'Status: ' + d.status + '\nLocation: ' + d.status_location : d.message))"
                                                class="btn btn-outline text-xs flex-1">
                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                            Track
                                        </button>
                                    </div>
                                    @if(in_array($order->status, ['shipped']))
                                        <form action="{{ route('admin.delivery.cancel', $order) }}" method="POST" onsubmit="return confirm('Cancel this shipment? This cannot be undone.')">
                                            @csrf
                                            <button type="submit" class="text-xs text-red-500 hover:text-red-700 hover:underline w-full text-center mt-1">Cancel Shipment</button>
                                        </form>
                                    @endif
                                @endif

                                {{-- Unfulfill — undo shipment, set back to Packed (any carrier) --}}
                                @if(in_array($order->status, ['shipped', 'out_for_delivery']))
                                    <form action="{{ route('admin.orders.unfulfill', $order) }}" method="POST"
                                          onsubmit="return confirm('Unfulfill this order? This removes the tracking/shipment and sets it back to Packed so you can re-fulfill it.')"
                                          class="pt-1">
                                        @csrf
                                        <button type="submit" class="btn btn-outline w-full border-red-200 text-red-600 hover:bg-red-50 text-sm">
                                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a5 5 0 010 10h-1M3 10l5-5M3 10l5 5"/>
                                            </svg>
                                            Unfulfill Order
                                        </button>
                                    </form>
                                @endif
                            </div>

                        {{-- STATE 3: No action needed (edge case) --}}
                        @else
                            <p class="text-sm text-neutral-500 text-center py-2">No fulfillment actions available.</p>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Shiprocket Integration -->
            @php $shiprocketEnabled = \App\Models\Setting::get('shiprocket_enabled', false); @endphp
            @if($shiprocketEnabled && in_array($order->status, ['confirmed', 'processing', 'packed', 'shipped', 'out_for_delivery', 'delivered']))
                <div class="card overflow-hidden">
                    <div class="px-5 py-4 border-b border-neutral-200 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            <h2 class="font-semibold text-neutral-900">Shiprocket</h2>
                        </div>
                        @if($order->shiprocket_order_id)
                            <span class="badge badge-success">Pushed</span>
                        @else
                            <span class="badge badge-warning">Not Pushed</span>
                        @endif
                    </div>
                    <div class="p-5">
                        @if($order->shiprocket_order_id)
                            <div class="bg-neutral-50 rounded-lg p-3 space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-neutral-500">SR Order ID</span>
                                    <span class="font-mono font-medium text-neutral-900">{{ $order->shiprocket_order_id }}</span>
                                </div>
                                @if($order->shiprocket_shipment_id)
                                    <div class="flex justify-between">
                                        <span class="text-neutral-500">Shipment ID</span>
                                        <span class="font-mono font-medium text-neutral-900">{{ $order->shiprocket_shipment_id }}</span>
                                    </div>
                                @endif
                                @if($order->shiprocket_awb)
                                    <div class="flex justify-between">
                                        <span class="text-neutral-500">AWB</span>
                                        <span class="font-mono font-medium text-primary-600">{{ $order->shiprocket_awb }}</span>
                                    </div>
                                @endif
                                @if($order->shiprocket_courier)
                                    <div class="flex justify-between">
                                        <span class="text-neutral-500">Courier</span>
                                        <span class="font-medium text-neutral-900">{{ $order->shiprocket_courier }}</span>
                                    </div>
                                @endif
                                @if($order->shiprocket_pushed_at)
                                    <div class="flex justify-between">
                                        <span class="text-neutral-500">Pushed</span>
                                        <span class="text-neutral-700">{{ \Carbon\Carbon::parse($order->shiprocket_pushed_at)->format('M d, Y h:i A') }}</span>
                                    </div>
                                @endif
                                @if($order->tracking_url)
                                    <a href="{{ $order->tracking_url }}" target="_blank" rel="noopener" class="block mt-2 text-center text-xs text-primary-600 hover:underline">Open tracking page →</a>
                                @endif
                            </div>
                        @else
                            <p class="text-sm text-neutral-600 mb-3">This order has not been pushed to Shiprocket yet. The auto-push may have failed or the order was placed before Shiprocket was enabled.</p>
                            <form action="{{ route('admin.orders.push-shiprocket', $order) }}" method="POST" onsubmit="return confirm('Push this order to Shiprocket?')">
                                @csrf
                                <button type="submit" class="btn btn-outline w-full border-blue-300 text-blue-700 hover:bg-blue-50">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    Push to Shiprocket
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Assign Delivery Partner -->
            @if(in_array($order->status, ['packed', 'shipped', 'out_for_delivery']))
                <div class="card overflow-hidden">
                    <div class="px-5 py-4 border-b border-neutral-200 flex items-center justify-between">
                        <h2 class="font-semibold text-neutral-900">Delivery Partner</h2>
                        @if($order->deliveryPartner)
                            <span class="badge badge-success">Assigned</span>
                        @endif
                    </div>
                    <div class="p-5">
                        @if($order->deliveryPartner)
                            <div class="flex items-center gap-3 mb-4 p-3 bg-neutral-50 rounded-lg">
                                <div class="w-9 h-9 rounded-full bg-primary-100 flex items-center justify-center">
                                    <span class="text-sm font-bold text-primary-600">{{ strtoupper(substr($order->deliveryPartner->user->first_name, 0, 1) . substr($order->deliveryPartner->user->last_name, 0, 1)) }}</span>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-neutral-900">{{ $order->deliveryPartner->user->full_name }}</p>
                                    <p class="text-xs text-neutral-600">{{ $order->deliveryPartner->partner_id }} &middot; {{ $order->deliveryPartner->phone }}</p>
                                </div>
                            </div>
                        @endif
                        <form action="{{ route('admin.orders.assign-partner', $order) }}" method="POST" class="space-y-3">
                            @csrf
                            <div>
                                <label class="form-label">{{ $order->deliveryPartner ? 'Change Partner' : 'Select Partner' }}</label>
                                <select name="delivery_partner_id" class="form-select w-full">
                                    <option value="">-- None --</option>
                                    @foreach($activePartners as $partner)
                                        <option value="{{ $partner->id }}" @selected($order->delivery_partner_id == $partner->id)>
                                            {{ $partner->user->full_name }} ({{ $partner->partner_id }}) - {{ ucfirst($partner->vehicle_type) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary w-full">
                                {{ $order->delivery_partner_id ? 'Update Partner' : 'Assign Partner' }}
                            </button>
                        </form>
                    </div>
                </div>
            @elseif($order->deliveryPartner)
                <div class="card overflow-hidden">
                    <div class="px-5 py-4 border-b border-neutral-200">
                        <h2 class="font-semibold text-neutral-900">Delivery Partner</h2>
                    </div>
                    <div class="p-5">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-primary-100 flex items-center justify-center">
                                <span class="text-sm font-bold text-primary-600">{{ strtoupper(substr($order->deliveryPartner->user->first_name, 0, 1) . substr($order->deliveryPartner->user->last_name, 0, 1)) }}</span>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-neutral-900">{{ $order->deliveryPartner->user->full_name }}</p>
                                <p class="text-xs text-neutral-600">{{ $order->deliveryPartner->partner_id }} &middot; {{ $order->deliveryPartner->phone }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Customer Info -->
            <div class="card overflow-hidden">
                <div class="px-5 py-4 border-b border-neutral-200">
                    <h2 class="font-semibold text-neutral-900">Customer</h2>
                </div>
                <div class="p-5">
                    @if($order->user)
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 bg-linear-to-br from-primary-50 to-purple-50 rounded-full flex items-center justify-center ring-1 ring-neutral-200">
                                <span class="text-sm font-bold text-primary-500">{{ strtoupper(substr($order->user->first_name, 0, 1)) }}</span>
                            </div>
                            <div>
                                <p class="font-medium text-neutral-900">{{ $order->user->full_name }}</p>
                                <p class="text-sm text-neutral-600">{{ $order->user->email }}</p>
                            </div>
                        </div>
                        <a href="{{ route('admin.customers.show', $order->user) }}" class="btn btn-secondary w-full text-center">
                            View Customer
                        </a>
                    @else
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 bg-neutral-100 rounded-full flex items-center justify-center shrink-0">
                                @if($order->guest_name)
                                    <span class="text-sm font-bold text-neutral-600">{{ strtoupper(substr($order->guest_name, 0, 1)) }}</span>
                                @else
                                    <svg class="w-5 h-5 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                @endif
                            </div>
                            <div>
                                @if($order->guest_name)
                                    <p class="font-medium text-neutral-900">{{ $order->guest_name }}</p>
                                @endif
                                @if($order->guest_email)
                                    <p class="text-sm text-neutral-600">{{ $order->guest_email }}</p>
                                @endif
                                @if($order->guest_phone)
                                    <p class="text-sm text-neutral-600">{{ $order->guest_phone }}</p>
                                @endif
                                <span class="text-xs text-neutral-400 mt-1 inline-block">Guest checkout</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Order Info -->
            <div class="card overflow-hidden">
                <div class="px-5 py-4 border-b border-neutral-200">
                    <h2 class="font-semibold text-neutral-900">Order Info</h2>
                </div>
                <div class="p-5 space-y-3 text-sm">
                    <div class="flex justify-between items-center">
                        <span class="text-neutral-600">Order Date</span>
                        <span class="font-medium text-neutral-700">{{ $order->created_at->format('M d, Y h:i A') }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-neutral-600">Payment Status</span>
                        @php
                            $payClass = match($order->payment_status) {
                                'paid' => 'badge-success',
                                'pending' => 'badge-warning',
                                'failed' => 'badge-error',
                                'refunded' => 'badge-neutral',
                                default => 'badge-neutral',
                            };
                        @endphp
                        <span class="badge {{ $payClass }}">{{ ucfirst($order->payment_status) }}</span>
                    </div>
                    @php
                        $pmMethod = $order->metadata['payment_method'] ?? 'unknown';
                        $pmGateway = $order->metadata['payment_gateway'] ?? null;
                        $pmPaidOnline = (float) ($order->paid_amount ?? 0);
                        $pmCodBalance = max(0, (float) $order->total - $pmPaidOnline);
                        $pmIsPartial = $pmCodBalance > 0 && $pmPaidOnline > 0;
                        $pmHasOnlinePayment = !empty($order->razorpay_payment_id) || !empty($order->metadata['razorpay_payment_id']) || !empty($order->metadata['cashfree_payment_id']);
                        $pmIsShiprocket = $pmGateway === 'shiprocket' || str_starts_with($pmMethod, 'shiprocket');
                        // Detect gateway label: Shiprocket > Cashfree > Razorpay
                        $pmGatewayLabel = $pmIsShiprocket ? 'Shiprocket'
                            : ($pmGateway === 'cashfree' ? 'Cashfree'
                            : ((!empty($order->metadata['cashfree_order_id']) || $pmMethod === 'cashfree') ? 'Cashfree' : 'Razorpay'));
                        // If metadata says COD but gateway paid full amount, it's actually prepaid
                        $pmActualMethod = ($pmMethod === 'cod' && $pmHasOnlinePayment && $pmCodBalance <= 0) ? $pmGatewayLabel : $pmMethod;
                        // COD check covers native cod + Shiprocket COD variants
                        $pmIsCod = in_array($pmActualMethod, ['cod', 'shiprocket_cod', 'shiprocket_cod_partial']);
                    @endphp
                    <div class="flex justify-between items-center">
                        <span class="text-neutral-600">Payment Method</span>
                        @if($pmIsPartial)
                            <span class="badge badge-warning">Partial Pay ({{ $pmGatewayLabel }})</span>
                        @elseif($pmIsShiprocket && $order->payment_status === 'paid' && !$pmIsPartial)
                            <span class="badge badge-success">Online (Shiprocket)</span>
                        @elseif($pmIsCod && $order->payment_status !== 'paid')
                            <span class="badge badge-warning">Pay on Delivery</span>
                        @elseif($pmHasOnlinePayment || in_array($pmMethod, ['razorpay', 'upi', 'cashfree']))
                            <span class="badge badge-success">Online ({{ $pmGatewayLabel }})</span>
                        @elseif($pmIsCod && $order->payment_status === 'paid')
                            <span class="badge badge-success">Paid via {{ $pmGatewayLabel }}</span>
                        @elseif($pmMethod === 'free')
                            <span class="badge badge-success">Free Order</span>
                        @else
                            <span class="badge badge-neutral">{{ ucfirst(str_replace('_', ' ', $pmActualMethod)) }}</span>
                        @endif
                    </div>
                    @if($pmPaidOnline > 0)
                        <div class="flex justify-between items-center">
                            <span class="text-neutral-600">Paid Online</span>
                            <span class="font-semibold text-green-600">{{ format_price($pmPaidOnline) }}</span>
                        </div>
                    @endif
                    @php
                        $txnId = $order->metadata['cashfree_payment_id'] ?? $order->metadata['razorpay_payment_id'] ?? $order->razorpay_payment_id ?? null;
                        $txnOrderId = $order->metadata['cashfree_order_id'] ?? $order->razorpay_order_id ?? null;
                    @endphp
                    @if($txnId)
                        <div class="flex justify-between items-center">
                            <span class="text-neutral-600">Transaction ID</span>
                            <span class="font-mono text-xs text-neutral-700">{{ $txnId }}</span>
                        </div>
                    @endif
                    @if($txnOrderId)
                        <div class="flex justify-between items-center">
                            <span class="text-neutral-600">Gateway Order ID</span>
                            <span class="font-mono text-xs text-neutral-700">{{ Str::limit($txnOrderId, 30) }}</span>
                        </div>
                    @endif
                    @if($pmIsPartial)
                        <div class="flex justify-between items-center">
                            <span class="text-neutral-600">Balance Due on Delivery</span>
                            <span class="font-semibold text-amber-600">{{ format_price($pmCodBalance) }}</span>
                        </div>
                    @elseif($pmIsCod && $order->payment_status !== 'paid' && $pmPaidOnline <= 0)
                        <div class="flex justify-between items-center">
                            <span class="text-neutral-600">Collect on Delivery</span>
                            <span class="font-semibold text-amber-600">{{ format_price($order->total) }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between items-center">
                        <span class="text-neutral-600">Payment Collected</span>
                        @if($order->payment_collected)
                            <span class="badge badge-success">Yes</span>
                        @else
                            <span class="badge badge-warning">No</span>
                        @endif
                    </div>
                    @if($order->payment_collected_at)
                        <div class="flex justify-between items-center">
                            <span class="text-neutral-600">Collected At</span>
                            <span class="font-medium text-neutral-700">{{ $order->payment_collected_at->format('M d, Y h:i A') }}</span>
                        </div>
                    @endif
                    @if($orderReturns->isNotEmpty())
                        <div class="pt-2 mt-2 border-t border-neutral-100">
                            <span class="text-xs font-semibold text-neutral-500 uppercase tracking-wide">Refunds</span>
                        </div>
                        @foreach($orderReturns as $orderReturn)
                            <div class="flex justify-between items-center">
                                <span class="text-neutral-600">{{ $orderReturn->return_number }}</span>
                                <span class="font-semibold text-red-600">-{{ format_price($orderReturn->refund_amount) }}</span>
                            </div>
                            <div class="flex justify-between items-center text-xs text-neutral-500">
                                <span>{{ ucfirst($orderReturn->refund_method ?? 'N/A') }}</span>
                                <span>{{ $orderReturn->completed_at?->format('d M Y') }}</span>
                            </div>
                        @endforeach
                        <div class="flex justify-between items-center pt-1">
                            <span class="text-neutral-600 font-medium">Total Refunded</span>
                            <span class="font-semibold text-red-600">-{{ format_price($orderReturns->sum('refund_amount')) }}</span>
                        </div>
                    @endif
                    @if($latestShipment)
                        <div class="flex justify-between items-center">
                            <span class="text-neutral-600">Carrier</span>
                            <span class="font-medium text-neutral-700">{{ $latestShipment->carrier }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-neutral-600">Tracking #</span>
                            <span class="font-mono font-medium text-primary-600">{{ $latestShipment->tracking_number }}</span>
                        </div>
                    @endif
                    @if($order->shipped_at)
                        <div class="flex justify-between items-center">
                            <span class="text-neutral-600">Shipped</span>
                            <span class="font-medium text-neutral-700">{{ $order->shipped_at->format('M d, Y') }}</span>
                        </div>
                    @endif
                    @if($order->delivered_at)
                        <div class="flex justify-between items-center">
                            <span class="text-neutral-600">Delivered</span>
                            <span class="font-medium text-neutral-700">{{ $order->delivered_at->format('M d, Y') }}</span>
                        </div>
                    @endif
                    @php
                        $capiMeta = $order->metadata ?? [];
                        $fastrHandlesPixel = (bool) \App\Models\Setting::get('fastrr_handles_purchase_pixel', false);
                    @endphp
                    <div class="flex justify-between items-center">
                        <span class="text-neutral-600">Facebook CAPI</span>
                        @if(!empty($capiMeta['capi_sent_at']))
                            <span class="badge badge-success text-xs">Sent</span>
                        @elseif($fastrHandlesPixel)
                            <span class="badge badge-info text-xs">Via Shiprocket</span>
                        @else
                            <span class="badge badge-warning text-xs">Not sent</span>
                        @endif
                    </div>
                    @if(!empty($capiMeta['capi_sent_at']))
                        <div class="flex justify-between items-center">
                            <span class="text-neutral-600">CAPI Source</span>
                            <span class="font-medium text-neutral-700 text-xs">{{ $capiMeta['capi_source'] ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-neutral-600">CAPI Sent At</span>
                            <span class="font-medium text-neutral-700 text-xs">{{ \Carbon\Carbon::parse($capiMeta['capi_sent_at'])->format('M d, Y h:i A') }}</span>
                        </div>
                    @endif
                </div>
                {{-- Expected Delivery Date --}}
                <div class="px-5 py-4 border-t border-neutral-200" x-data="{ editing: false }">
                    <div x-show="!editing" class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-neutral-600">Expected Delivery</p>
                            @if($order->expected_delivery_date)
                                <p class="text-sm font-semibold text-success-700 mt-0.5">
                                    {{ $order->expected_delivery_date->format('D, M d, Y') }}
                                    @if($order->expected_delivery_date->isToday())
                                        <span class="text-xs font-normal text-success-500">(Today)</span>
                                    @elseif($order->expected_delivery_date->isTomorrow())
                                        <span class="text-xs font-normal text-success-500">(Tomorrow)</span>
                                    @endif
                                </p>
                            @else
                                <p class="text-sm text-neutral-600 mt-0.5">Not set</p>
                            @endif
                        </div>
                        @if(!in_array($order->status, ['delivered', 'cancelled', 'returned']))
                            <button @click="editing = true" class="text-xs text-primary-600 hover:text-primary-700 font-medium">
                                {{ $order->expected_delivery_date ? 'Change' : 'Set Date' }}
                            </button>
                        @endif
                    </div>
                    @if(!in_array($order->status, ['delivered', 'cancelled', 'returned']))
                        <form x-show="editing" x-cloak action="{{ route('admin.orders.expected-delivery', $order) }}" method="POST" class="space-y-2 mt-2">
                            @csrf
                            @method('PUT')
                            <label class="form-label">Expected Delivery Date</label>
                            <input type="date" name="expected_delivery_date"
                                   value="{{ $order->expected_delivery_date?->format('Y-m-d') }}"
                                   min="{{ today()->format('Y-m-d') }}"
                                   class="form-input w-full">
                            <div class="flex gap-2 pt-1">
                                <button type="submit" class="btn btn-primary btn-sm flex-1 text-xs">Save</button>
                                <button type="button" @click="editing = false" class="btn btn-secondary btn-sm text-xs">Cancel</button>
                            </div>
                            @if($order->expected_delivery_date)
                                <button type="submit" class="w-full text-xs text-error-500 hover:text-error-600 text-center py-1"
                                        onclick="this.closest('form').querySelector('input[name=expected_delivery_date]').value = ''">
                                    Clear date
                                </button>
                            @endif
                        </form>
                    @endif
                </div>
            </div>

            <!-- Shiprocket Checkout Event Timeline -->
            @if($checkoutEvents->isNotEmpty())
                <div class="card overflow-hidden">
                    <div class="px-5 py-4 border-b border-neutral-200 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            <h2 class="font-semibold text-neutral-900">Checkout Journey</h2>
                        </div>
                        <span class="text-xs text-neutral-500">{{ $checkoutEvents->where('is_duplicate', false)->count() }} events</span>
                    </div>
                    <div class="p-5">
                        <div class="relative">
                            <div class="absolute left-3 top-0 bottom-0 w-px bg-neutral-200"></div>
                            <div class="space-y-4">
                                @foreach($checkoutEvents->where('is_duplicate', false) as $evt)
                                    <div class="flex gap-3 text-sm relative">
                                        @php
                                            $evtDot = match($evt->stage) {
                                                'Payment Complete' => 'bg-green-100 text-green-600',
                                                'ORDER_PLACED'     => 'bg-blue-100 text-blue-600',
                                                'PAYMENT_INITIATED'=> 'bg-yellow-100 text-yellow-700',
                                                'Abandoned Cart'   => 'bg-red-100 text-red-500',
                                                default            => 'bg-neutral-100 text-neutral-500',
                                            };
                                            $riskLevel = $evt->risk_analysis['level'] ?? 'low';
                                        @endphp
                                        <div class="w-6 h-6 rounded-full flex items-center justify-center z-10 shrink-0 {{ $evtDot }}">
                                            <div class="w-2 h-2 rounded-full bg-current"></div>
                                        </div>
                                        <div class="flex-1 pb-1">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <p class="font-medium text-neutral-900">{{ $evt->stageLabel() }}</p>
                                                @if($riskLevel !== 'low')
                                                    <span class="text-xs px-1.5 py-0.5 rounded font-medium {{ $evt->riskBadgeClass() }}">
                                                        {{ ucfirst($riskLevel) }} risk
                                                    </span>
                                                @endif
                                                @if($evt->payment_mode)
                                                    <span class="text-xs px-1.5 py-0.5 rounded bg-neutral-100 text-neutral-600 font-medium">
                                                        {{ $evt->payment_mode }}
                                                        @if($evt->payment_amount) · ₹{{ number_format($evt->payment_amount, 0) }} @endif
                                                    </span>
                                                @endif
                                            </div>
                                            @if($evt->full_name || $evt->phone)
                                                <p class="text-neutral-600 text-xs mt-0.5">
                                                    {{ $evt->full_name }}@if($evt->full_name && $evt->phone) · @endif{{ $evt->phone }}
                                                </p>
                                            @endif
                                            @if($evt->city || $evt->pincode)
                                                <p class="text-neutral-500 text-xs">
                                                    {{ implode(', ', array_filter([$evt->address_line_1, $evt->city, $evt->pincode])) }}
                                                </p>
                                            @endif
                                            @if($evt->net_payable > 0)
                                                <p class="text-xs text-neutral-500">
                                                    Net payable: <span class="font-medium text-neutral-700">₹{{ number_format($evt->net_payable, 2) }}</span>
                                                    @if($evt->total_discount > 0) (₹{{ number_format($evt->total_discount, 2) }} discount) @endif
                                                </p>
                                            @endif
                                            <p class="text-xs text-neutral-400 mt-0.5">{{ $evt->received_at?->format('M d, Y h:i A') }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Status History -->
            @if($order->statusHistory->count())
                <div class="card overflow-hidden">
                    <div class="px-5 py-4 border-b border-neutral-200">
                        <h2 class="font-semibold text-neutral-900">Activity Log</h2>
                    </div>
                    <div class="p-5">
                        <div class="relative">
                            <div class="absolute left-3 top-0 bottom-0 w-px bg-neutral-200"></div>
                            <div class="space-y-4">
                                @foreach($order->statusHistory->sortByDesc('created_at') as $history)
                                    <div class="flex gap-3 text-sm relative">
                                        @php
                                            $dotColor = match(true) {
                                                $history->status === 'delivered' => 'bg-success-100 text-success-600',
                                                in_array($history->status, ['cancelled', 'returned']) => 'bg-error-100 text-error-600',
                                                default => 'bg-primary-100 text-primary-600',
                                            };
                                            $innerDot = match(true) {
                                                $history->status === 'delivered' => 'bg-success-500',
                                                in_array($history->status, ['cancelled', 'returned']) => 'bg-error-500',
                                                default => 'bg-primary-500',
                                            };
                                        @endphp
                                        <div class="w-6 h-6 rounded-full flex items-center justify-center z-10 {{ $dotColor }}">
                                            <div class="w-2 h-2 rounded-full {{ $innerDot }}"></div>
                                        </div>
                                        <div class="flex-1 pb-1">
                                            <p class="font-medium text-neutral-900">{{ ucfirst(str_replace('_', ' ', $history->status)) }}</p>
                                            @if($history->comment)
                                                <p class="text-neutral-600">{{ $history->comment }}</p>
                                            @endif
                                            <p class="text-xs text-neutral-600 mt-0.5">{{ $history->created_at->format('M d, Y \a\t h:i A') }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-layouts.admin>
