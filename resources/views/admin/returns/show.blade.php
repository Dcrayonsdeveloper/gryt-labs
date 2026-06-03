<x-layouts.admin>
    <x-slot name="title">Return {{ $return->return_number }}</x-slot>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2 text-sm text-neutral-600 mb-1">
                    <a href="{{ route('admin.returns.index') }}" class="hover:text-primary-600 transition-colors">Returns</a>
                    <svg class="w-3.5 h-3.5 text-neutral-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                    <span class="text-neutral-900">{{ $return->return_number }}</span>
                </div>
                <h1 class="text-2xl font-bold text-neutral-900">Return {{ $return->return_number }}</h1>
            </div>
            <div class="flex items-center gap-2">
                @php
                    $headerStatusClass = match($return->status) {
                        'requested' => 'badge-warning',
                        'approved', 'pickup_scheduled', 'picked_up' => 'badge-info',
                        'received', 'processed' => 'badge-primary',
                        'rejected' => 'badge-error',
                        'completed' => 'badge-success',
                        default => 'badge-neutral',
                    };
                @endphp
                <span class="badge {{ $headerStatusClass }} text-sm px-3 py-1">
                    {{ $return->status === 'processed' ? 'Refund Processed' : ucfirst(str_replace('_', ' ', $return->status)) }}
                </span>
            </div>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Return Details -->
            <div class="card overflow-hidden">
                <div class="px-5 py-4 border-b border-neutral-200">
                    <h2 class="font-semibold text-neutral-900">Return Details</h2>
                </div>
                <div class="p-5 space-y-3 text-sm">
                    <div class="flex justify-between items-center">
                        <span class="text-neutral-600">Type</span>
                        @php
                            $typeClass = match($return->type ?? 'return') {
                                'return' => 'bg-info-50 text-info-700',
                                'refund' => 'bg-warning-50 text-warning-700',
                                'exchange' => 'bg-purple-50 text-purple-700',
                                default => 'bg-neutral-100 text-neutral-600',
                            };
                        @endphp
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $typeClass }}">
                            {{ ucfirst($return->type ?? 'Return') }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-neutral-600">Reason</span>
                        <span class="font-medium text-neutral-700">{{ $return->reason_category ?? $return->reason ?? '-' }}</span>
                    </div>
                    @if($return->reason && $return->reason_category)
                        <div class="pt-3 border-t border-neutral-100">
                            <span class="text-neutral-600">Details</span>
                            <p class="mt-1.5 text-neutral-700 bg-neutral-50 rounded-lg p-3">{{ $return->reason }}</p>
                        </div>
                    @endif
                    @if($return->description)
                        <div class="pt-3 border-t border-neutral-100">
                            <span class="text-neutral-600">Description</span>
                            <p class="mt-1.5 text-neutral-700 bg-neutral-50 rounded-lg p-3">{{ $return->description }}</p>
                        </div>
                    @endif
                    <div class="flex justify-between items-center">
                        <span class="text-neutral-600">Created</span>
                        <span class="font-medium text-neutral-700">{{ $return->created_at->format('M d, Y h:i A') }}</span>
                    </div>
                    @if($return->approved_at)
                        <div class="flex justify-between items-center">
                            <span class="text-neutral-600">Approved</span>
                            <span class="font-medium text-neutral-700">{{ $return->approved_at->format('M d, Y h:i A') }}</span>
                        </div>
                    @endif
                    @if($return->refund_amount)
                        <div class="flex justify-between items-center">
                            <span class="text-neutral-600">Refund Amount</span>
                            <span class="font-semibold text-success-600">@price($return->refund_amount)</span>
                        </div>
                    @endif
                    @if($return->completed_at)
                        <div class="flex justify-between items-center">
                            <span class="text-neutral-600">Completed At</span>
                            <span class="font-medium text-neutral-700">{{ $return->completed_at->format('M d, Y h:i A') }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Return Items -->
            @if($return->items->count())
                <div class="card overflow-hidden">
                    <div class="px-5 py-4 border-b border-neutral-200">
                        <h2 class="font-semibold text-neutral-900">Return Items</h2>
                    </div>
                    <div class="divide-y divide-neutral-100">
                        @foreach($return->items as $item)
                            <div class="px-5 py-4 flex gap-4">
                                <div class="w-14 h-14 rounded-lg bg-neutral-50 ring-1 ring-neutral-200 overflow-hidden shrink-0">
                                    @if($item->orderItem->product->primary_image_url ?? null)
                                        <img src="{{ $item->orderItem->product->primary_image_url }}" alt="{{ $item->orderItem->product->name }}" class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center">
                                            <svg class="w-5 h-5 text-neutral-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-neutral-900">{{ $item->orderItem->product->name ?? 'Product' }}</p>
                                    <p class="text-sm text-neutral-600 mt-0.5">Qty: {{ $item->quantity ?? 1 }}</p>
                                    @if($item->reason)
                                        <p class="text-xs text-neutral-600 mt-1">{{ $item->reason }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Credit Note (shown after refund processed) -->
            @if($return->creditNote)
                <div class="card overflow-hidden">
                    <div class="px-5 py-4 border-b border-neutral-200 flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-success-50 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                        </div>
                        <h2 class="font-semibold text-neutral-900">Credit Note Issued</h2>
                    </div>
                    <div class="p-5">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <a href="{{ route('admin.credit-notes.show', $return->creditNote) }}" class="font-semibold text-primary-600 hover:text-primary-700 text-lg">
                                    {{ $return->creditNote->credit_note_number }}
                                </a>
                                <p class="text-xs text-neutral-600 mt-0.5">{{ $return->creditNote->created_at->format('M d, Y h:i A') }}</p>
                            </div>
                            @php
                                $cnStatusClass = match($return->creditNote->status) {
                                    'active' => 'badge-success',
                                    'partially_used' => 'badge-info',
                                    'fully_used' => 'badge-neutral',
                                    'expired' => 'badge-error',
                                    default => 'badge-neutral',
                                };
                            @endphp
                            <span class="badge {{ $cnStatusClass }}">{{ ucfirst(str_replace('_', ' ', $return->creditNote->status)) }}</span>
                        </div>
                        <div class="grid grid-cols-3 gap-3 text-center">
                            <div class="bg-neutral-50 rounded-lg p-3">
                                <p class="text-xs text-neutral-600">Amount</p>
                                <p class="font-bold text-neutral-900">@price($return->creditNote->amount)</p>
                            </div>
                            <div class="bg-neutral-50 rounded-lg p-3">
                                <p class="text-xs text-neutral-600">Used</p>
                                <p class="font-bold text-info-600">@price($return->creditNote->used_amount)</p>
                            </div>
                            <div class="bg-neutral-50 rounded-lg p-3">
                                <p class="text-xs text-neutral-600">Remaining</p>
                                <p class="font-bold text-success-600">@price($return->creditNote->remaining_amount)</p>
                            </div>
                        </div>
                        <a href="{{ route('admin.credit-notes.show', $return->creditNote) }}" class="btn btn-secondary w-full mt-4 text-sm">
                            View Credit Note Details
                        </a>
                    </div>
                </div>
            @endif

            <!-- Update Status -->
            @if(!in_array($return->status, ['completed', 'rejected']))
                <div class="card overflow-hidden" x-data="{ status: '{{ $return->status }}' }">
                    <div class="px-5 py-4 border-b border-neutral-200 flex items-center justify-between">
                        <h2 class="font-semibold text-neutral-900">Update Status</h2>
                        <span class="badge {{ $headerStatusClass }}">
                            {{ $return->status === 'processed' ? 'Refund Processed' : ucfirst(str_replace('_', ' ', $return->status)) }}
                        </span>
                    </div>
                    <div class="p-5">
                        <form action="{{ route('admin.returns.status', $return) }}" method="POST" class="space-y-4">
                            @csrf
                            @method('PUT')
                            <div>
                                <label class="form-label">New Status</label>
                                <select name="status" x-model="status" class="form-select w-full">
                                    <option value="requested">Requested</option>
                                    <option value="approved">Approved</option>
                                    <option value="rejected">Rejected</option>
                                    <option value="pickup_scheduled">Pickup Scheduled</option>
                                    <option value="picked_up">Picked Up</option>
                                    <option value="received">Received</option>
                                </select>
                                <p class="text-xs text-neutral-600 mt-1">To complete the return, use the "Process Refund" form below</p>
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

                <!-- Process Refund -->
                @if(!in_array($return->status, ['requested', 'rejected']) && !$return->refund_amount)
                    <div class="card overflow-hidden">
                        <div class="px-5 py-4 border-b border-neutral-200 flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-success-50 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <h2 class="font-semibold text-neutral-900">Process Refund</h2>
                        </div>
                        <div class="p-5">
                            <form action="{{ route('admin.returns.refund', $return) }}" method="POST" class="space-y-4">
                                @csrf
                                <div>
                                    <label class="form-label">Refund Amount ({{ currency_symbol() }})</label>
                                    <input type="number" name="amount" step="0.01" min="0"
                                           value="{{ $return->items->sum(fn($item) => ($item->orderItem->price ?? 0) * ($item->quantity ?? 1)) }}"
                                           class="form-input w-full" required>
                                </div>
                                <div>
                                    <label class="form-label">Refund Method</label>
                                    <select name="refund_method" class="form-select w-full" required>
                                        <option value="wallet">Store Credit (Wallet)</option>
                                        <option value="original">Original Payment Method</option>
                                        <option value="bank">Bank Transfer</option>
                                    </select>
                                    <p class="text-xs text-neutral-600 mt-1">Store credit will be added to customer's account balance</p>
                                </div>
                                <div>
                                    <label class="form-label">Notes (optional)</label>
                                    <textarea name="notes" rows="2" class="form-textarea w-full" placeholder="Add refund notes..."></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary w-full">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Process Refund
                                </button>
                            </form>
                        </div>
                    </div>
                @endif
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Order Info -->
            <div class="card overflow-hidden">
                <div class="px-5 py-4 border-b border-neutral-200">
                    <h2 class="font-semibold text-neutral-900">Order Info</h2>
                </div>
                <div class="p-5 space-y-3 text-sm">
                    <div class="flex justify-between items-center">
                        <span class="text-neutral-600">Order</span>
                        <a href="{{ route('admin.orders.show', $return->order_id) }}" class="font-medium text-primary-600 hover:text-primary-700 transition-colors">
                            {{ $return->order->order_number ?? 'N/A' }}
                        </a>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-neutral-600">Order Total</span>
                        <span class="font-semibold text-neutral-900">@price($return->order->total ?? 0)</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-neutral-600">Order Status</span>
                        @php
                            $orderStatusClass = match($return->order->status ?? '') {
                                'delivered', 'completed' => 'badge-success',
                                'pending', 'confirmed' => 'badge-warning',
                                'processing', 'packed' => 'badge-info',
                                'shipped', 'out_for_delivery' => 'badge-primary',
                                'cancelled', 'returned' => 'badge-error',
                                default => 'badge-neutral',
                            };
                        @endphp
                        <span class="badge {{ $orderStatusClass }}">
                            {{ ucfirst(str_replace('_', ' ', $return->order->status ?? '-')) }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-neutral-600">Order Date</span>
                        <span class="font-medium text-neutral-700">{{ $return->order->created_at?->format('M d, Y') ?? '-' }}</span>
                    </div>
                </div>
            </div>

            <!-- Assign Pickup Partner -->
            @if(in_array($return->status, ['approved', 'pickup_scheduled', 'picked_up']))
                <div class="card overflow-hidden">
                    <div class="px-5 py-4 border-b border-neutral-200 flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-purple-50 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                            </svg>
                        </div>
                        <h2 class="font-semibold text-neutral-900">Pickup Partner</h2>
                    </div>
                    <div class="p-5">
                        @if($return->pickupPartner)
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                                    <span class="font-medium text-purple-600">{{ substr($return->pickupPartner->user->first_name ?? 'P', 0, 1) }}</span>
                                </div>
                                <div>
                                    <p class="font-medium text-neutral-900">{{ $return->pickupPartner->user->full_name }}</p>
                                    <p class="text-xs text-neutral-600">{{ $return->pickupPartner->partner_id }} &middot; {{ $return->pickupPartner->phone }}</p>
                                </div>
                            </div>
                        @endif
                        <form action="{{ route('admin.returns.assign-partner', $return) }}" method="POST" class="space-y-3">
                            @csrf
                            <select name="pickup_partner_id" class="form-select w-full text-sm">
                                <option value="">-- No partner --</option>
                                @foreach($activePartners as $partner)
                                    <option value="{{ $partner->id }}" {{ $return->pickup_partner_id == $partner->id ? 'selected' : '' }}>
                                        {{ $partner->user->full_name }} ({{ $partner->partner_id }})
                                    </option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-primary w-full text-sm">
                                {{ $return->pickup_partner_id ? 'Change Partner' : 'Assign Partner' }}
                            </button>
                        </form>
                    </div>
                </div>
            @elseif($return->pickupPartner)
                <div class="card overflow-hidden">
                    <div class="px-5 py-4 border-b border-neutral-200">
                        <h2 class="font-semibold text-neutral-900">Pickup Partner</h2>
                    </div>
                    <div class="p-5">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                                <span class="font-medium text-purple-600">{{ substr($return->pickupPartner->user->first_name ?? 'P', 0, 1) }}</span>
                            </div>
                            <div>
                                <p class="font-medium text-neutral-900">{{ $return->pickupPartner->user->full_name }}</p>
                                <p class="text-xs text-neutral-600">{{ $return->pickupPartner->partner_id }} &middot; {{ $return->pickupPartner->phone }}</p>
                            </div>
                        </div>
                        @if($return->pickup_scheduled_at)
                            <p class="text-xs text-neutral-600 mt-2">Scheduled: {{ $return->pickup_scheduled_at->format('M d, Y h:i A') }}</p>
                        @endif
                        @if($return->picked_up_at)
                            <p class="text-xs text-neutral-600">Picked up: {{ $return->picked_up_at->format('M d, Y h:i A') }}</p>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Customer Info -->
            <div class="card overflow-hidden">
                <div class="px-5 py-4 border-b border-neutral-200">
                    <h2 class="font-semibold text-neutral-900">Customer</h2>
                </div>
                <div class="p-5">
                    @if($return->order->user ?? null)
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 bg-linear-to-br from-primary-50 to-purple-50 rounded-full flex items-center justify-center ring-1 ring-neutral-200">
                                <span class="text-sm font-bold text-primary-500">{{ strtoupper(substr($return->order->user->first_name ?? 'G', 0, 1)) }}</span>
                            </div>
                            <div>
                                <p class="font-medium text-neutral-900">{{ $return->order->user->full_name }}</p>
                                <p class="text-sm text-neutral-600">{{ $return->order->user->email }}</p>
                            </div>
                        </div>
                        @if($return->order->user->phone ?? null)
                            <div class="flex items-center gap-2 text-sm text-neutral-600 mb-2">
                                <svg class="w-4 h-4 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                                {{ $return->order->user->phone }}
                            </div>
                        @endif
                        <a href="{{ route('admin.customers.show', $return->order->user) }}" class="btn btn-secondary w-full text-center mt-2">
                            View Customer
                        </a>
                    @else
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-neutral-100 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            <span class="text-sm text-neutral-600">Guest checkout</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Photos -->
            @if($return->images && count($return->images))
                <div class="card overflow-hidden">
                    <div class="px-5 py-4 border-b border-neutral-200">
                        <h2 class="font-semibold text-neutral-900">Photos</h2>
                    </div>
                    <div class="p-4 grid grid-cols-2 gap-2">
                        @foreach($return->images as $image)
                            <a href="{{ $image }}" target="_blank" class="block rounded-lg overflow-hidden ring-1 ring-neutral-200 hover:ring-primary-300 transition-all">
                                <img src="{{ $image }}" alt="Return photo" class="w-full h-24 object-cover">
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Refund Notes -->
            @if($return->processedBy)
                <div class="card overflow-hidden">
                    <div class="px-5 py-4 border-b border-neutral-200">
                        <h2 class="font-semibold text-neutral-900">Processed By</h2>
                    </div>
                    <div class="p-5">
                        <p class="text-sm text-neutral-600">{{ $return->processedBy->full_name }}</p>
                        @if($return->completed_at)
                            <p class="text-xs text-neutral-600 mt-1">{{ $return->completed_at->format('M d, Y h:i A') }}</p>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-layouts.admin>
