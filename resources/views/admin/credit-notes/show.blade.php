<x-layouts.admin>
    <x-slot name="title">Credit Note {{ $creditNote->credit_note_number }}</x-slot>

    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm text-neutral-600 mb-2">
            <a href="{{ route('admin.credit-notes.index') }}" class="hover:text-primary-600">Credit Notes</a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-neutral-900">{{ $creditNote->credit_note_number }}</span>
        </div>
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-neutral-900">{{ $creditNote->credit_note_number }}</h1>
                <p class="text-sm text-neutral-600 mt-1">Created {{ $creditNote->created_at->format('F d, Y h:i A') }}</p>
            </div>
            @php
                $statusClass = match($creditNote->status) {
                    'active' => 'badge-success',
                    'partially_used' => 'badge-info',
                    'fully_used' => 'badge-neutral',
                    'expired' => 'badge-error',
                    default => 'badge-neutral',
                };
            @endphp
            <span class="badge text-base px-4 py-2 {{ $statusClass }}">
                {{ ucfirst(str_replace('_', ' ', $creditNote->status)) }}
            </span>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Amount Details -->
            <div class="card">
                <div class="p-5 border-b border-neutral-200">
                    <h2 class="font-semibold text-neutral-900">Credit Details</h2>
                </div>
                <div class="p-5">
                    <div class="grid grid-cols-3 gap-6">
                        <div class="text-center p-4 bg-neutral-50 rounded-xl">
                            <p class="text-sm text-neutral-600 mb-1">Total Amount</p>
                            <p class="text-2xl font-bold text-neutral-900">@price($creditNote->amount)</p>
                        </div>
                        <div class="text-center p-4 bg-info-50 rounded-xl">
                            <p class="text-sm text-info-600 mb-1">Used</p>
                            <p class="text-2xl font-bold text-info-700">@price($creditNote->used_amount)</p>
                        </div>
                        <div class="text-center p-4 bg-success-50 rounded-xl">
                            <p class="text-sm text-success-600 mb-1">Remaining</p>
                            <p class="text-2xl font-bold text-success-700">@price($creditNote->remaining_amount)</p>
                        </div>
                    </div>

                    @if($creditNote->amount > 0)
                        <div class="mt-4">
                            <div class="flex justify-between text-xs text-neutral-600 mb-1">
                                <span>Usage</span>
                                <span>{{ number_format(($creditNote->used_amount / $creditNote->amount) * 100, 0) }}%</span>
                            </div>
                            <div class="w-full bg-neutral-200 rounded-full h-2.5">
                                <div class="bg-primary-500 h-2.5 rounded-full" style="width: {{ ($creditNote->used_amount / $creditNote->amount) * 100 }}%"></div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Return Items -->
            @if($creditNote->return && $creditNote->return->items->count())
                <div class="card">
                    <div class="p-5 border-b border-neutral-200">
                        <h2 class="font-semibold text-neutral-900">Returned Items</h2>
                    </div>
                    <div class="divide-y divide-neutral-100">
                        @foreach($creditNote->return->items as $item)
                            <div class="p-4 flex gap-4">
                                <img src="{{ $item->orderItem->product->primary_image_url ?? '' }}" alt="{{ $item->orderItem->product_name ?? 'Product' }}"
                                     class="w-14 h-14 rounded-lg object-cover bg-neutral-100">
                                <div class="flex-1">
                                    <h3 class="font-medium text-neutral-900">{{ $item->orderItem->product_name ?? 'Product' }}</h3>
                                    <p class="text-sm text-neutral-600">Qty: {{ $item->quantity }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-medium">@price($item->orderItem->price ?? 0)</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Usage History -->
            <div class="card">
                <div class="p-5 border-b border-neutral-200">
                    <h2 class="font-semibold text-neutral-900">Usage History</h2>
                </div>
                @if($creditNote->usages && $creditNote->usages->count())
                    <div class="divide-y divide-neutral-100">
                        @foreach($creditNote->usages as $usage)
                            <div class="px-5 py-3.5 flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-neutral-900">
                                        Used on Order
                                        @if($usage->order)
                                            <a href="{{ route('admin.orders.show', $usage->order) }}" class="text-primary-600 hover:text-primary-700">
                                                #{{ $usage->order->order_number }}
                                            </a>
                                        @endif
                                    </p>
                                    <p class="text-xs text-neutral-600">{{ $usage->created_at->format('M d, Y h:i A') }}</p>
                                </div>
                                <span class="font-semibold text-error-600">-@price($usage->amount)</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-8 text-center">
                        <p class="text-sm text-neutral-600">No usage yet. This credit hasn't been redeemed.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Customer Info -->
            <div class="card p-5">
                <h2 class="font-semibold text-neutral-900 mb-4">Customer</h2>
                @if($creditNote->user)
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center">
                            <span class="font-medium text-primary-600">{{ substr($creditNote->user->first_name, 0, 1) }}</span>
                        </div>
                        <div>
                            <p class="font-medium text-neutral-900">{{ $creditNote->user->full_name }}</p>
                            <p class="text-sm text-neutral-600">{{ $creditNote->user->email }}</p>
                        </div>
                    </div>
                    <a href="{{ route('admin.customers.show', $creditNote->user) }}" class="btn btn-secondary w-full mt-4 text-sm">View Customer</a>
                @else
                    <p class="text-neutral-600">Customer not found</p>
                @endif
            </div>

            <!-- Linked Order -->
            @if($creditNote->order)
                <div class="card p-5">
                    <h2 class="font-semibold text-neutral-900 mb-4">Source Order</h2>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-neutral-600">Order</dt>
                            <dd>
                                <a href="{{ route('admin.orders.show', $creditNote->order) }}" class="font-medium text-primary-600 hover:text-primary-700">
                                    {{ $creditNote->order->order_number }}
                                </a>
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-neutral-600">Order Total</dt>
                            <dd class="font-medium">@price($creditNote->order->total)</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-neutral-600">Order Status</dt>
                            <dd>
                                <span class="badge {{ $creditNote->order->status === 'completed' ? 'badge-success' : ($creditNote->order->status === 'cancelled' ? 'badge-error' : 'badge-info') }}">
                                    {{ ucfirst(str_replace('_', ' ', $creditNote->order->status)) }}
                                </span>
                            </dd>
                        </div>
                    </dl>
                </div>
            @endif

            <!-- Linked Return -->
            @if($creditNote->return)
                <div class="card p-5">
                    <h2 class="font-semibold text-neutral-900 mb-4">Source Return</h2>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-neutral-600">Return</dt>
                            <dd>
                                <a href="{{ route('admin.returns.show', $creditNote->return) }}" class="font-medium text-primary-600 hover:text-primary-700">
                                    {{ $creditNote->return->return_number }}
                                </a>
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-neutral-600">Status</dt>
                            <dd>
                                <span class="badge badge-success">{{ ucfirst(str_replace('_', ' ', $creditNote->return->status)) }}</span>
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-neutral-600">Reason</dt>
                            <dd class="text-right">{{ $creditNote->return->reason ?? '-' }}</dd>
                        </div>
                    </dl>
                </div>
            @endif

            <!-- Credit Note Info -->
            <div class="card p-5">
                <h2 class="font-semibold text-neutral-900 mb-4">Details</h2>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-neutral-600">Created</dt>
                        <dd class="font-medium">{{ $creditNote->created_at->format('M d, Y') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-neutral-600">Expires</dt>
                        <dd class="font-medium {{ $creditNote->expires_at?->isPast() ? 'text-error-500' : '' }}">
                            {{ $creditNote->expires_at?->format('M d, Y') ?? 'Never' }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-neutral-600">Status</dt>
                        <dd>
                            <span class="badge {{ $statusClass }}">{{ ucfirst(str_replace('_', ' ', $creditNote->status)) }}</span>
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</x-layouts.admin>
