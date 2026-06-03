<x-layouts.admin>
    <x-slot name="title">Orders</x-slot>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold text-neutral-900">Orders</h1>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.orders.index', array_merge(request()->only(['search', 'status', 'payment_status', 'date_from', 'date_to']), ['export' => 'csv'])) }}" class="btn btn-secondary text-sm">Export CSV</a>
                @if($stats['shiprocket_missing'] ?? 0)
                <button type="button"
                        x-data="{ loading: false }"
                        x-on:click="
                            loading = true;
                            fetch('{{ route('admin.orders.sync-shiprocket-customers') }}', {
                                method: 'POST',
                                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                            })
                            .then(r => r.json())
                            .then(d => { alert(d.message); if (d.synced > 0) location.reload(); })
                            .catch(() => alert('Sync failed — check logs.'))
                            .finally(() => loading = false);
                        "
                        :disabled="loading"
                        class="btn btn-secondary text-sm inline-flex items-center gap-1.5"
                        title="Pull customer details for {{ $stats['shiprocket_missing'] }} order(s) showing as Guest">
                    <span x-show="!loading">Backfill {{ $stats['shiprocket_missing'] }} Shiprocket order(s)</span>
                    <span x-show="loading" x-cloak>Syncing…</span>
                </button>
                @endif
                @if($stats['address_missing'] ?? 0)
                <button type="button"
                        x-data="{ loading: false }"
                        x-on:click="
                            loading = true;
                            fetch('{{ route('admin.orders.sync-shiprocket-addresses') }}', {
                                method: 'POST',
                                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                            })
                            .then(r => r.json())
                            .then(d => { alert(d.message); if (d.synced > 0) location.reload(); })
                            .catch(() => alert('Address sync failed — check logs.'))
                            .finally(() => loading = false);
                        "
                        :disabled="loading"
                        class="btn btn-secondary text-sm inline-flex items-center gap-1.5"
                        title="Pull missing addresses for {{ $stats['address_missing'] }} order(s) from Shiprocket">
                    <span x-show="!loading">Sync {{ $stats['address_missing'] }} missing address(es)</span>
                    <span x-show="loading" x-cloak>Syncing addresses…</span>
                </button>
                @endif
            </div>
        </div>
    </x-slot>

    {{-- Stats Bar --}}
    <x-slot name="statsBar">
        @include('admin.partials.stats-bar', ['stats' => [
            ['label' => 'Orders', 'value' => number_format($stats['total'] ?? 0), 'sparkline' => '2,15 10,12 18,8 26,14 34,6 42,11 50,4 58,9', 'color' => '#5c6ac4'],
            ['label' => 'Items ordered', 'value' => number_format(($stats['total'] ?? 0) * 2), 'sparkline' => '2,14 10,10 18,12 26,6 34,9 42,4 50,8 58,3', 'color' => '#47c1bf'],
            ['label' => 'Returns', 'value' => '₹' . number_format($stats['cancelled'] ?? 0), 'sparkline' => '2,10 10,10 18,10 26,10 34,10 42,10 50,10 58,10', 'color' => '#9c6ade'],
            ['label' => 'Orders fulfilled', 'value' => number_format($stats['completed'] ?? 0), 'sparkline' => '2,16 10,14 18,12 26,10 34,8 42,6 50,4 58,2', 'color' => '#5c6ac4'],
        ]])
    </x-slot>

    {{-- Tab Filters (Shopify style) --}}
    @php
        $currentStatus = request('status', '');
        $currentPayment = request('payment_status', '');
        $currentTab = request('tab', '');
        $attentionActive = $currentTab === 'needs_attention';
        $attentionCount = (int) ($stats['needs_attention'] ?? 0);
        $tabs = [
            '' => 'All',
            'confirmed' => 'Unfulfilled',
            'processing' => 'Processing',
            'shipped' => 'Shipped',
            'delivered' => 'Completed',
            'cancelled' => 'Cancelled',
        ];
    @endphp

    <div class="card overflow-hidden">
        {{-- Tabs --}}
        <div class="flex items-center gap-0 px-4 pt-3 border-b border-gray-200">
            {{-- Needs attention (triage bucket: payment failed + COD/pending unconfirmed + returns awaiting refund) --}}
            <a href="{{ route('admin.orders.index', array_merge(request()->except('status', 'tab', 'page'), ['tab' => 'needs_attention'])) }}"
               class="px-3 pb-3 text-sm font-medium transition-colors relative inline-flex items-center gap-1.5 {{ $attentionActive ? 'text-neutral-900' : 'text-neutral-500 hover:text-neutral-700' }}">
                Needs attention
                @if($attentionCount > 0)
                    <span class="inline-flex items-center text-[11px] font-semibold px-1.5 py-0.5 rounded-full bg-red-100 text-red-800">{{ $attentionCount }}</span>
                @endif
                @if($attentionActive)
                    <span class="absolute bottom-0 left-0 right-0 h-0.5 rounded-full bg-neutral-900"></span>
                @endif
            </a>
            @foreach($tabs as $value => $label)
                @php $isActive = !$attentionActive && $currentStatus === $value; @endphp
                <a href="{{ route('admin.orders.index', array_merge(request()->except('status', 'tab', 'page'), $value ? ['status' => $value] : [])) }}"
                   class="px-3 pb-3 text-sm font-medium transition-colors relative {{ $isActive ? 'text-neutral-900' : 'text-neutral-500 hover:text-neutral-700' }}">
                    {{ $label }}
                    @if($isActive)
                        <span class="absolute bottom-0 left-0 right-0 h-0.5 rounded-full bg-neutral-900"></span>
                    @endif
                </a>
            @endforeach
        </div>

        {{-- Search + Filter Row --}}
        <div class="px-4 py-3 border-b border-gray-200">
            <form action="{{ route('admin.orders.index') }}" method="GET" class="flex items-center gap-2" style="flex-wrap:nowrap">
                @if($attentionActive)
                    <input type="hidden" name="tab" value="needs_attention">
                @elseif($currentStatus)
                    <input type="hidden" name="status" value="{{ $currentStatus }}">
                @endif
                <div class="relative" style="flex:1;min-width:140px;max-width:280px">
                    <svg class="w-4 h-4 text-neutral-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Filter orders" class="form-input w-full pl-9 text-sm h-9">
                </div>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-input text-sm h-9" style="width:140px" title="From date">
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-input text-sm h-9" style="width:140px" title="To date">
                <select name="payment_status" class="form-input text-sm h-9" style="width:140px"
                        x-on:change="$el.form.submit()">
                    <option value="">Payment status</option>
                    <option value="pending" {{ $currentPayment === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="paid" {{ $currentPayment === 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="failed" {{ $currentPayment === 'failed' ? 'selected' : '' }}>Failed</option>
                    <option value="refunded" {{ $currentPayment === 'refunded' ? 'selected' : '' }}>Refunded</option>
                </select>
                <button type="submit" class="btn btn-primary text-sm h-9 px-4 shrink-0">Filter</button>
                @if(request()->hasAny(['search', 'payment_status', 'date_from', 'date_to']))
                    <a href="{{ route('admin.orders.index', $attentionActive ? ['tab' => 'needs_attention'] : ($currentStatus ? ['status' => $currentStatus] : [])) }}" class="text-xs text-neutral-500 hover:text-neutral-700 shrink-0">Clear</a>
                @endif
            </form>
        </div>

        {{-- Orders Table --}}
        <div class="overflow-x-auto" x-data="{
            selected: [],
            allIds: [{{ $orders->pluck('id')->join(',') }}],
            get allChecked() { return this.selected.length === this.allIds.length && this.allIds.length > 0; },
            toggleAll() { this.selected = this.allChecked ? [] : [...this.allIds]; },
            toggle(id) { const i = this.selected.indexOf(id); i === -1 ? this.selected.push(id) : this.selected.splice(i, 1); }
        }">
            {{-- Bulk Actions Bar --}}
            <div x-show="selected.length > 0" x-cloak
                 class="flex flex-wrap items-center justify-between gap-3 px-3 py-2 bg-gray-100 border-b border-gray-200">
                <span class="text-[13px] font-medium text-gray-800">
                    <span x-text="selected.length"></span> selected
                </span>
                <form method="POST" action="{{ route('admin.orders.bulk-action') }}"
                      @submit.prevent="
                          const action = $refs.bulkAction.value;
                          if (!action) { alert('Please select an action'); return; }
                          const labels = { processing: 'mark as processing', shipped: 'mark as shipped', delivered: 'mark as delivered', cancelled: 'cancel' };
                          if (!confirm('Are you sure you want to ' + labels[action] + ' ' + selected.length + ' order(s)?')) return;
                          $refs.bulkIds.value = JSON.stringify(selected);
                          $el.submit();
                      ">
                    @csrf
                    <input type="hidden" name="ids" x-ref="bulkIds" value="">
                    <div class="flex items-center gap-2">
                        <select name="action" x-ref="bulkAction"
                                class="px-2 py-1 text-xs border border-gray-300 rounded-md outline-none bg-white focus:border-blue-600 focus:ring-1 focus:ring-blue-600">
                            <option value="">Bulk action</option>
                            <option value="processing">Mark as Processing</option>
                            <option value="shipped">Mark as Shipped</option>
                            <option value="delivered">Mark as Delivered</option>
                            <option value="cancelled">Cancel Selected</option>
                        </select>
                        <button type="submit"
                                class="px-2.5 py-1 text-xs font-medium bg-gray-900 hover:bg-gray-700 text-white border-0 rounded-md cursor-pointer">
                            Apply
                        </button>
                    </div>
                </form>
            </div>

            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 w-8">
                            <input type="checkbox" class="form-checkbox rounded" x-on:click="toggleAll()" :checked="allChecked">
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500">Order</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500">Customer</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-neutral-500">Total</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-neutral-500">Advance Paid</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500">Payment status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500">Fulfillment status</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-neutral-500">Items</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr class="hover:bg-neutral-50 cursor-pointer border-b border-gray-100"
                            x-on:click="if(!$event.target.closest('input[type=checkbox]')) window.location.href='{{ route('admin.orders.show', $order) }}'">
                            <td class="px-4 py-3" x-on:click.stop>
                                <input type="checkbox" class="form-checkbox rounded" value="{{ $order->id }}"
                                       x-on:click="toggle({{ $order->id }})" :checked="selected.includes({{ $order->id }})">
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-sm font-medium text-blue-700">{{ $order->order_number }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-sm text-neutral-600">{{ $order->created_at->isToday() ? 'Today at ' . $order->created_at->format('g:i a') : ($order->created_at->isYesterday() ? 'Yesterday at ' . $order->created_at->format('g:i a') : $order->created_at->format('M d, Y')) }}</span>
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $custKey    = $order->user_id ? "u_{$order->user_id}" : ($order->guest_email ? "e_{$order->guest_email}" : null);
                                    $orderCount = $custKey ? ($repeatCustomers[$custKey] ?? 1) : 1;
                                    $searchTerm = $order->user?->email ?? $order->guest_email ?? null;
                                @endphp
                                <div class="flex items-center gap-1.5">
                                    <div class="min-w-0">
                                        @if(!$order->user && empty($order->guest_name) && empty($order->guest_phone) && empty($order->guest_email) && $order->shiprocket_order_id)
                                            <span class="inline-flex items-center gap-1 rounded bg-orange-50 px-1.5 py-0.5 text-xs font-medium text-orange-700" title="Customer details pending sync from Shiprocket">
                                                <svg class="h-3 w-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/><path fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"/></svg>
                                                Pending Shiprocket sync
                                            </span>
                                            <span class="block text-xs text-neutral-500 mt-0.5" title="Shiprocket checkout reference">SR #{{ $order->shiprocket_order_id }}</span>
                                        @else
                                            <span class="text-sm text-neutral-900">{{ $order->guest_name ?? $order->user?->full_name ?? 'Guest' }}</span>
                                            @if(!$order->user && ($order->guest_email || $order->guest_phone))
                                                <span class="block text-xs text-neutral-500">{{ $order->guest_email ?? $order->guest_phone }}</span>
                                            @endif
                                        @endif
                                    </div>
                                    @if($orderCount > 1 && $searchTerm)
                                        <a href="{{ route('admin.orders.index', array_merge(request()->only(['status', 'tab']), ['search' => $searchTerm])) }}"
                                           x-on:click.stop
                                           title="{{ $orderCount }} orders from this customer — click to view all"
                                           class="shrink-0 inline-flex items-center gap-0.5 text-[11px] font-semibold px-1.5 py-0.5 rounded-full bg-violet-100 text-violet-800 hover:bg-violet-200 whitespace-nowrap">
                                            <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                            {{ $orderCount }}
                                        </a>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <span class="text-sm text-neutral-900">@price($order->total)</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                @php
                                    $codAdvance = $order->metadata['cod_advance']
                                        ?? $order->metadata['cod_advance_paid']
                                        ?? (($order->paid_amount > 0 && $order->paid_amount < $order->total) ? $order->paid_amount : 0);
                                @endphp
                                @if($codAdvance > 0)
                                    <span class="text-sm text-green-700 font-medium">@price($codAdvance)</span>
                                @else
                                    <span class="text-sm text-neutral-300">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $payTextColor = match($order->payment_status) {
                                        'paid' => 'text-green-700',
                                        'pending' => 'text-amber-700',
                                        'failed' => 'text-red-700',
                                        'refunded' => 'text-gray-600',
                                        default => 'text-gray-600',
                                    };
                                @endphp
                                <span class="text-xs font-medium {{ $payTextColor }}">{{ ucfirst($order->payment_status) }}</span>
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $fulfill = match($order->status) {
                                        'delivered', 'completed' => ['cls' => 'text-green-700', 'label' => 'Fulfilled'],
                                        'shipped', 'out_for_delivery' => ['cls' => 'text-blue-700', 'label' => 'In transit'],
                                        'cancelled', 'returned' => ['cls' => 'text-red-700', 'label' => 'Cancelled'],
                                        default => ['cls' => 'text-amber-700', 'label' => 'Unfulfilled'],
                                    };
                                @endphp
                                <span class="text-xs font-medium {{ $fulfill['cls'] }}">{{ $fulfill['label'] }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="text-sm text-neutral-600">{{ $order->items->count() }} {{ Str::plural('item', $order->items->count()) }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-16 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="w-12 h-12 rounded-full flex items-center justify-center mb-3 bg-gray-100">
                                        <svg class="w-6 h-6 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z"/></svg>
                                    </div>
                                    <p class="text-sm font-medium text-neutral-900 mb-1">No orders found</p>
                                    <p class="text-sm text-neutral-500">
                                        @if(request()->hasAny(['search', 'status', 'payment_status']))
                                            Try changing the filters or search term.
                                        @else
                                            Orders will appear here when customers place them.
                                        @endif
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($orders->hasPages())
            <div class="px-4 py-3 flex items-center justify-between text-sm border-t border-gray-200">
                <span class="text-neutral-500">{{ $orders->firstItem() }}-{{ $orders->lastItem() }} of {{ $orders->total() }}</span>
                <div class="flex items-center gap-1">
                    @if($orders->onFirstPage())
                        <span class="px-2 py-1 text-neutral-300">&laquo;</span>
                    @else
                        <a href="{{ $orders->previousPageUrl() }}" class="px-2 py-1 text-neutral-600 hover:text-neutral-900">&laquo;</a>
                    @endif
                    @if($orders->hasMorePages())
                        <a href="{{ $orders->nextPageUrl() }}" class="px-2 py-1 text-neutral-600 hover:text-neutral-900">&raquo;</a>
                    @else
                        <span class="px-2 py-1 text-neutral-300">&raquo;</span>
                    @endif
                </div>
            </div>
        @endif
    </div>
</x-layouts.admin>
