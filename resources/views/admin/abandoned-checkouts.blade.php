<x-layouts.admin>
<x-slot name="title">Abandoned Checkouts</x-slot>
<div class="p-6 max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Abandoned checkouts</h1>
            <p class="text-sm text-gray-500 mt-0.5">Customers who added items to cart but didn't complete purchase</p>
        </div>
        <a href="{{ route('admin.abandoned-checkouts', array_merge(request()->only(['tab', 'from', 'to']), ['export' => 'csv'])) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Export CSV
        </a>
    </div>

    @if(session('success'))
    <div class="mb-4 p-3 rounded-lg bg-green-50 border border-green-200 text-green-800 text-sm">{{ session('success') }}</div>
    @endif

    {{-- Date filter --}}
    <form method="GET" action="{{ route('admin.abandoned-checkouts') }}" class="mb-4 flex flex-wrap items-end gap-3 bg-white rounded-lg border border-gray-200 p-4">
        @if(request('tab'))
        <input type="hidden" name="tab" value="{{ request('tab') }}">
        @endif
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">From</label>
            <input type="date" name="from" value="{{ $dateFrom ?? '' }}" class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">To</label>
            <input type="date" name="to" value="{{ $dateTo ?? '' }}" class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
        </div>
        <button type="submit" class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-800 transition-colors">Filter</button>
        @if(($dateFrom ?? null) || ($dateTo ?? null))
        <a href="{{ route('admin.abandoned-checkouts', request()->only('tab')) }}" class="px-4 py-2 bg-white border border-gray-300 text-sm font-medium text-gray-700 rounded-md hover:bg-gray-50 transition-colors">Clear</a>
        @endif
    </form>

    {{-- Stats cards --}}
    @php
        $statsQuery = \App\Models\AbandonedCheckout::query();
        if ($dateFrom ?? null) $statsQuery->whereDate('created_at', '>=', $dateFrom);
        if ($dateTo ?? null) $statsQuery->whereDate('created_at', '<=', $dateTo);
        $totalCount = (clone $statsQuery)->count();
        $withContactCount = (clone $statsQuery)->where(function($q) { $q->whereNotNull('email')->orWhereNotNull('phone'); })->where('recovered', false)->count();
        $recoveredCount = (clone $statsQuery)->where('recovered', true)->count();
        $totalValue = (clone $statsQuery)->where('recovered', false)->sum('cart_total');
        $recoveredValue = (clone $statsQuery)->where('recovered', true)->sum('cart_total');
        $tab = request('tab', 'all');
    @endphp
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-2xl font-bold text-gray-900">{{ $totalCount }}</p>
            <p class="text-xs text-gray-500 mt-1">Total abandoned</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-2xl font-bold text-gray-900">₹{{ number_format($totalValue, 0) }}</p>
            <p class="text-xs text-gray-500 mt-1">Lost revenue</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-2xl font-bold text-green-600">{{ $recoveredCount }}</p>
            <p class="text-xs text-gray-500 mt-1">Recovered</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-2xl font-bold text-green-600">₹{{ number_format($recoveredValue, 0) }}</p>
            <p class="text-xs text-gray-500 mt-1">Revenue recovered</p>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="flex items-center gap-6 border-b border-gray-200 mb-0">
        <a href="{{ route('admin.abandoned-checkouts', request()->only(['from', 'to'])) }}"
           class="pb-3 text-sm font-medium border-b-2 {{ $tab === 'all' ? 'border-gray-900 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            All <span class="ml-1 text-xs text-gray-400">{{ $totalCount }}</span>
        </a>
        <a href="{{ route('admin.abandoned-checkouts', array_merge(request()->only(['from', 'to']), ['tab' => 'with-contact'])) }}"
           class="pb-3 text-sm font-medium border-b-2 {{ $tab === 'with-contact' ? 'border-gray-900 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            With contact <span class="ml-1 text-xs text-gray-400">{{ $withContactCount }}</span>
        </a>
        <a href="{{ route('admin.abandoned-checkouts', array_merge(request()->only(['from', 'to']), ['tab' => 'recovered'])) }}"
           class="pb-3 text-sm font-medium border-b-2 {{ $tab === 'recovered' ? 'border-gray-900 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            Recovered <span class="ml-1 text-xs text-gray-400">{{ $recoveredCount }}</span>
        </a>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-b-lg border border-t-0 border-gray-200" x-data="bulkActions()">
        {{-- Bulk actions --}}
        <div x-show="selected.length > 0" x-cloak class="px-4 py-2.5 bg-gray-50 border-b border-gray-200 flex items-center gap-3">
            <span class="text-sm text-gray-700" x-text="selected.length + ' selected'"></span>
            <form method="POST" action="{{ route('admin.abandoned-checkouts.bulk') }}" class="inline">
                @csrf
                <template x-for="id in selected" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
                <button type="submit" name="action" value="delete" onclick="return confirm('Delete selected?')"
                        class="px-3 py-1 text-xs font-medium text-red-600 bg-white border border-gray-300 rounded-md hover:bg-red-50">Delete</button>
                <button type="submit" name="action" value="remind" onclick="return confirm('Send reminders?')"
                        class="px-3 py-1 text-xs font-medium text-blue-600 bg-white border border-gray-300 rounded-md hover:bg-blue-50 ml-1">Send reminder</button>
            </form>
        </div>

        <table class="w-full text-sm">
            <thead class="border-b border-gray-200 bg-gray-50/50">
                <tr>
                    <th class="w-10 px-4 py-3"><input type="checkbox" class="rounded border-gray-300" @change="toggleAll($event)"></th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Checkout</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Products</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            @forelse($abandoned as $item)
            @php
                $items = is_array($item->cart_snapshot) ? $item->cart_snapshot : [];
                $itemCount = $item->items_count ?? count($items);
                $firstItem = $items[0] ?? null;
                $timeSince = $item->created_at->diffForHumans();
            @endphp
            <tbody x-data="reminderRow({
                    id: {{ $item->id }},
                    hasContact: @js((bool) ($item->email || $item->phone)),
                    recovered: @js((bool) $item->recovered),
                    initialNotifiedHuman: @js($item->notified_at?->diffForHumans()),
                    initialNotifiedIso: @js($item->notified_at?->toIso8601String()),
                })" class="border-b border-gray-100 last:border-0">
                <tr class="hover:bg-gray-50/70 transition-colors cursor-pointer" @click="open = !open">
                    <td class="px-4 py-3" @click.stop>
                        <input type="checkbox" class="rounded border-gray-300" value="{{ $item->id }}" x-model="selected">
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-gray-900">#{{ $item->id }}</span>
                            <span class="text-xs text-gray-400">{{ $timeSince }}</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $item->step === 'contact_captured' ? 'Contact entered' : 'Viewed checkout' }}</p>
                    </td>
                    <td class="px-4 py-3">
                        @if($firstItem)
                        <div class="flex items-center gap-2.5">
                            {{-- Product image from first item --}}
                            @php
                                $prodId = $firstItem['product_id'] ?? $firstItem['variant_id'] ?? 0;
                                $prod = $prodId ? \App\Models\Product::withTrashed()->with('primaryImage')->find($prodId) : null;
                                $imgUrl = $prod?->primary_image_url ?? '';
                            @endphp
                            @if($imgUrl)
                            <img src="{{ $imgUrl }}" alt="" class="w-10 h-10 object-cover rounded border border-gray-200 shrink-0">
                            @else
                            <div class="w-10 h-10 bg-gray-100 rounded border border-gray-200 shrink-0 flex items-center justify-center">
                                <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14"/></svg>
                            </div>
                            @endif
                            <div class="min-w-0">
                                <p class="text-sm text-gray-900 truncate max-w-50" title="{{ $firstItem['product_name'] ?? $firstItem['name'] ?? 'Product' }}">{{ $firstItem['product_name'] ?? $firstItem['name'] ?? 'Product' }}</p>
                                @if($itemCount > 1)
                                <p class="text-xs text-gray-400">+ {{ $itemCount - 1 }} more {{ Str::plural('item', $itemCount - 1) }}</p>
                                @endif
                            </div>
                        </div>
                        @else
                        <span class="text-xs text-gray-400">{{ $itemCount }} {{ Str::plural('item', $itemCount) }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if($item->name || $item->email || $item->phone)
                            <p class="text-sm font-medium text-gray-900">{{ $item->name ?: 'Guest' }}</p>
                            @if($item->phone)<p class="text-xs text-gray-500">{{ $item->phone }}</p>@endif
                            @if($item->email)<p class="text-xs text-gray-400">{{ $item->email }}</p>@endif
                        @else
                            <span class="text-xs text-gray-400 italic">Anonymous</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        <span class="text-sm font-semibold text-gray-900">₹{{ number_format($item->cart_total, 0) }}</span>
                    </td>
                    <td class="px-4 py-3">
                        @if($item->recovered && $item->order_id)
                            <a href="{{ route('admin.orders.show', $item->order_id) }}" @click.stop class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700 hover:bg-green-200">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                Recovered
                            </a>
                        @elseif($item->notified_at)
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-amber-50 text-amber-700">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                Reminded
                            </span>
                            <p class="text-[11px] text-gray-400 mt-1" x-show="notifiedHuman" x-cloak>
                                Sent <span x-text="notifiedHuman"></span>
                            </p>
                        @elseif($item->email || $item->phone)
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-50 text-red-600" x-show="!notifiedHuman">Not recovered</span>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-amber-50 text-amber-700" x-show="notifiedHuman" x-cloak>
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                Reminded
                            </span>
                            <p class="text-[11px] text-gray-400 mt-1" x-show="notifiedHuman" x-cloak>
                                Sent <span x-text="notifiedHuman"></span>
                            </p>
                        @else
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-500">No contact</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right" @click.stop>
                        @if(!$item->recovered && ($item->email || $item->phone))
                            <button type="button"
                                    @click="sendReminder()"
                                    :disabled="loading || status === 'success'"
                                    :class="status === 'success' ? 'bg-green-50 text-green-700 border-green-200' : (status === 'error' ? 'bg-red-50 text-red-700 border-red-200' : 'bg-white text-blue-600 border-gray-300 hover:bg-blue-50')"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium border rounded-md transition-colors disabled:opacity-60 disabled:cursor-not-allowed">
                                <template x-if="loading">
                                    <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                </template>
                                <template x-if="!loading && status !== 'success'">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                </template>
                                <template x-if="!loading && status === 'success'">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                </template>
                                <span x-text="buttonLabel"></span>
                            </button>
                        @elseif($item->recovered)
                            <span class="text-xs text-gray-400">—</span>
                        @else
                            <span class="text-xs text-gray-400 italic">No contact</span>
                        @endif
                    </td>
                </tr>
                {{-- Expandable product list --}}
                @if(count($items) > 0)
                <tr x-show="open" x-cloak x-collapse>
                    <td colspan="7" class="bg-gray-50/80 px-6 py-3">
                        <div class="max-w-2xl space-y-2">
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Cart Contents</p>
                            @foreach($items as $cartItem)
                            @php $cProd = \App\Models\Product::withTrashed()->with('primaryImage')->find($cartItem['product_id'] ?? $cartItem['variant_id'] ?? 0); @endphp
                            <div class="flex items-center gap-3 bg-white rounded-lg border border-gray-200 p-2.5">
                                @if($cProd?->primary_image_url)
                                <img src="{{ $cProd->primary_image_url }}" alt="" class="w-12 h-12 object-cover rounded border border-gray-100 shrink-0">
                                @else
                                <div class="w-12 h-12 bg-gray-100 rounded shrink-0"></div>
                                @endif
                                <div class="flex-1 min-w-0">
                                    <a href="{{ $cProd ? route('admin.products.edit', $cProd->slug) : '#' }}" target="_blank" class="text-sm font-medium text-gray-900 hover:text-blue-600 hover:underline truncate block" title="{{ $cartItem['product_name'] ?? $cartItem['name'] ?? 'Product' }}">{{ $cartItem['product_name'] ?? $cartItem['name'] ?? 'Product' }}</a>
                                    <p class="text-xs text-gray-500">Qty: {{ $cartItem['quantity'] ?? 1 }} × ₹{{ number_format($cartItem['price'] ?? 0, 0) }}</p>
                                </div>
                                <div class="text-sm font-semibold text-gray-900 shrink-0">₹{{ number_format(($cartItem['price'] ?? 0) * ($cartItem['quantity'] ?? 1), 0) }}</div>
                            </div>
                            @endforeach
                            <div class="flex justify-between items-center pt-2 border-t border-gray-200 mt-2">
                                <span class="text-xs text-gray-500">{{ count($items) }} {{ Str::plural('item', count($items)) }}</span>
                                <span class="text-sm font-bold text-gray-900">Total: ₹{{ number_format($item->cart_total, 0) }}</span>
                            </div>
                        </div>
                    </td>
                </tr>
                @endif
            </tbody>
            @empty
            <tbody><tr><td colspan="7" class="px-4 py-16 text-center">
                <svg class="w-12 h-12 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
                <p class="text-gray-400 text-sm">No abandoned checkouts</p>
            </td></tr></tbody>
            @endforelse
        </table>
    </div>

    @if($abandoned->hasPages())
    <div class="mt-4">{{ $abandoned->links() }}</div>
    @endif
</div>

@push('scripts')
<script>
function bulkActions() {
    return {
        selected: [],
        toggleAll(e) {
            this.selected = e.target.checked ? @json($abandoned->pluck('id')->map(fn($id) => (string) $id)) : [];
        }
    };
}

function reminderRow(opts) {
    return {
        open: false,
        id: opts.id,
        hasContact: opts.hasContact,
        recovered: opts.recovered,
        notifiedHuman: opts.initialNotifiedHuman || null,
        notifiedIso: opts.initialNotifiedIso || null,
        loading: false,
        status: 'idle', // idle | success | error
        get buttonLabel() {
            if (this.loading) return 'Sending...';
            if (this.status === 'success') return 'Sent';
            if (this.status === 'error') return 'Retry';
            return this.notifiedHuman ? 'Send again' : 'Send';
        },
        async sendReminder() {
            if (this.loading || this.status === 'success') return;
            if (!this.hasContact || this.recovered) return;
            this.loading = true;
            this.status = 'idle';
            try {
                const form = new FormData();
                form.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
                form.append('action', 'remind');
                form.append('checkout_id', this.id);
                const res = await fetch(@json(route('admin.abandoned-checkouts.bulk')), {
                    method: 'POST',
                    body: form,
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const data = await res.json();
                if (!data.ok) throw new Error(data.message || 'Failed');
                this.notifiedHuman = data.notified_at_human || 'just now';
                this.notifiedIso = data.notified_at_iso || null;
                this.status = 'success';
                // Allow re-send after a short cool-down window
                setTimeout(() => { if (this.status === 'success') this.status = 'idle'; }, 4000);
            } catch (e) {
                this.status = 'error';
                setTimeout(() => { if (this.status === 'error') this.status = 'idle'; }, 3000);
            } finally {
                this.loading = false;
            }
        },
    };
}
</script>
@endpush
</x-layouts.admin>
