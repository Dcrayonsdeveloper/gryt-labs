<x-layouts.admin>
    <x-slot name="title">Discounts</x-slot>

    {{-- Header --}}
    <div class="flex items-center justify-between mb-5">
        <h1 class="text-xl font-semibold text-gray-900">Discounts</h1>
        <a href="{{ route('admin.coupons.create') }}"
           class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
            Create discount
        </a>
    </div>

    @if(session('success'))
        <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    {{-- Main Card --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200"
         x-data="{
             tab: '{{ request('status', 'all') }}',
             search: '{{ request('search', '') }}',
             selected: [],
             allIds: {{ $coupons->pluck('id') }},
             toggleAll(checked) { this.selected = checked ? [...this.allIds] : []; },
             isAllSelected() { return this.allIds.length > 0 && this.selected.length === this.allIds.length; }
         }">

        {{-- Tabs --}}
        <div class="flex items-center gap-0 px-4" style="border-bottom:1px solid #e1e1e1">
            @php
                $tabs = [
                    'all' => 'All',
                    'active' => 'Active',
                    'expired' => 'Expired',
                ];
            @endphp
            @foreach($tabs as $key => $label)
                <a href="{{ route('admin.coupons.index', array_merge(request()->except('status', 'page'), $key === 'all' ? [] : ['status' => $key])) }}"
                   class="relative px-4 py-3 text-sm font-medium transition-colors
                          {{ request('status', 'all') === $key ? 'text-gray-900' : 'text-gray-500 hover:text-gray-700' }}">
                    {{ $label }}
                    @if(request('status', 'all') === $key)
                        <span class="absolute bottom-0 left-4 right-4 h-0.5 bg-gray-900 rounded-full"></span>
                    @endif
                </a>
            @endforeach
        </div>

        {{-- Search --}}
        <div class="px-4 py-3" style="border-bottom:1px solid #e1e1e1">
            <form action="{{ route('admin.coupons.index') }}" method="GET" class="flex items-center gap-2">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                <div class="relative flex-1 max-w-sm">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Search discounts"
                           class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                </div>
            </form>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider" style="border-bottom:1px solid #e1e1e1">
                        <th class="pl-4 pr-0 py-3 w-10">
                            <input type="checkbox" class="rounded border-gray-300 text-gray-900 focus:ring-gray-500"
                                   @change="toggleAll($event.target.checked)"
                                   :checked="isAllSelected()">
                        </th>
                        <th class="px-4 py-3">Code</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Value</th>
                        <th class="px-4 py-3">Usage</th>
                        <th class="px-4 py-3">Revenue</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Expires</th>
                        <th class="px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($coupons as $coupon)
                        <tr class="hover:bg-gray-50 cursor-pointer group" style="border-bottom:1px solid #e1e1e1"
                            onclick="if(!event.target.closest('input[type=checkbox]')) window.location='{{ route('admin.coupons.edit', $coupon) }}'">
                            <td class="pl-4 pr-0 py-3 w-10" onclick="event.stopPropagation()">
                                <input type="checkbox" class="rounded border-gray-300 text-gray-900 focus:ring-gray-500"
                                       value="{{ $coupon->id }}"
                                       :checked="selected.includes({{ $coupon->id }})"
                                       @change="selected.includes({{ $coupon->id }}) ? selected.splice(selected.indexOf({{ $coupon->id }}), 1) : selected.push({{ $coupon->id }})">
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-medium text-gray-900">{{ $coupon->code }}</span>
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ ucfirst(str_replace('_', ' ', $coupon->type)) }}
                            </td>
                            <td class="px-4 py-3 text-gray-900 font-medium">
                                @if($coupon->type === 'percentage')
                                    {{ $coupon->value }}%
                                @elseif($coupon->type === 'free_shipping')
                                    Free shipping
                                @else
                                    @price($coupon->value)
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ $couponAnalytics[$coupon->id]['usage_count'] ?? ($coupon->times_used ?? 0) }}{{ $coupon->usage_limit ? ' / ' . $coupon->usage_limit : '' }}
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                @if(isset($couponAnalytics[$coupon->id]['total_revenue']))
                                    @price($couponAnalytics[$coupon->id]['total_revenue'])
                                @else
                                    --
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($coupon->isValid())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>
                                @elseif($coupon->expires_at?->isPast())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Expired</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Inactive</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                @if($coupon->expires_at)
                                    {{ $coupon->expires_at->format('M d, Y') }}
                                @else
                                    --
                                @endif
                            </td>
                            <td class="px-4 py-3" onclick="event.stopPropagation()">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.coupons.edit', $coupon) }}" class="text-xs text-blue-600 hover:text-blue-800 font-medium">Edit</a>
                                    <form action="{{ route('admin.coupons.destroy', $coupon) }}" method="POST" onsubmit="return confirm('Delete coupon {{ $coupon->code }}? This cannot be undone.')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-xs text-red-500 hover:text-red-700 font-medium">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-12 text-center">
                                <div class="text-center py-12">
                                    <svg class="w-12 h-12 mx-auto mb-3 text-neutral-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 6h.008v.008H6V6z"/></svg>
                                    <h3 class="text-sm font-semibold text-neutral-900 mb-1">No discounts yet</h3>
                                    <p class="text-xs text-neutral-500 mb-4">
                                        @if(request('search'))
                                            No discounts match your search. Try a different term.
                                        @else
                                            Get started by creating your first discount code.
                                        @endif
                                    </p>
                                    <a href="{{ route('admin.coupons.create') }}"
                                       class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
                                        Create discount
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($coupons->hasPages())
            <div class="px-4 py-3 flex items-center justify-between" style="border-top:1px solid #e1e1e1">
                <div class="text-sm text-gray-600">
                    Showing {{ $coupons->firstItem() }}-{{ $coupons->lastItem() }} of {{ $coupons->total() }}
                </div>
                <div class="flex items-center gap-1">
                    @if($coupons->onFirstPage())
                        <span class="px-3 py-1.5 text-sm text-gray-400 border border-gray-200 rounded-lg cursor-not-allowed">&lsaquo;</span>
                    @else
                        <a href="{{ $coupons->previousPageUrl() }}" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">&lsaquo;</a>
                    @endif
                    @if($coupons->hasMorePages())
                        <a href="{{ $coupons->nextPageUrl() }}" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">&rsaquo;</a>
                    @else
                        <span class="px-3 py-1.5 text-sm text-gray-400 border border-gray-200 rounded-lg cursor-not-allowed">&rsaquo;</span>
                    @endif
                </div>
            </div>
        @endif
    </div>

    {{-- ── Shiprocket Checkout coupons (read-only) ─────────────────────────── --}}
    @if(!empty($shiprocketCoupons))
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 mt-6">
            <div class="px-4 py-4 flex items-start justify-between gap-4" style="border-bottom:1px solid #e1e1e1">
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-sm font-semibold text-gray-900">Shiprocket Checkout coupons</h2>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-indigo-50 text-indigo-700">Read-only</span>
                    </div>
                    <p class="text-xs text-gray-500 mt-1 max-w-2xl">
                        Discount codes seen on live Shiprocket orders. These are managed in your
                        <a href="https://checkout-dashboard.shiprocket.in/settings/discount" target="_blank" rel="noopener" class="text-indigo-600 hover:underline">Shiprocket dashboard</a>,
                        not here — Shiprocket has no API to list them, so this is built from the codes customers actually used.
                    </p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider" style="border-bottom:1px solid #e1e1e1">
                            <th class="px-4 py-3 whitespace-nowrap">Code</th>
                            <th class="px-4 py-3 whitespace-nowrap">Orders</th>
                            <th class="px-4 py-3 whitespace-nowrap">Sales</th>
                            <th class="px-4 py-3 whitespace-nowrap">Discount given</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($shiprocketCoupons as $sc)
                            <tr style="border-bottom:1px solid #e1e1e1">
                                <td class="px-4 py-3">
                                    <span class="font-medium text-gray-900">{{ $sc['code'] }}</span>
                                    @if($sc['managed'])
                                        <span class="inline-flex items-center px-2 py-0.5 ml-2 rounded-full text-[11px] font-medium bg-green-50 text-green-700 whitespace-nowrap">Also a store coupon</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ number_format($sc['orders']) }}</td>
                                <td class="px-4 py-3 text-gray-900 font-medium">@price($sc['sales'])</td>
                                <td class="px-4 py-3 text-gray-600">@price($sc['discount'])</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-4 py-2.5 text-[11px] text-gray-400" style="border-top:1px solid #e1e1e1">
                Auto-updates as new orders come in · codes never used in Shiprocket won't appear here.
            </div>
        </div>
    @endif
</x-layouts.admin>
