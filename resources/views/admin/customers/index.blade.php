<x-layouts.admin>
    <x-slot name="title">Customers</x-slot>

    {{-- Header --}}
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-xl font-semibold text-gray-900">Customers</h1>
    </div>

    {{-- Stats Bar --}}
    @include('admin.partials.stats-bar', ['stats' => [
        ['label' => 'Total customers', 'value' => number_format($customers->total()), 'sparkline' => '2,16 10,14 18,12 26,10 34,8 42,6 50,4 58,2', 'color' => '#5c6ac4'],
        ['label' => 'Returning rate', 'value' => '0%', 'sparkline' => '2,10 10,10 18,10 26,10 34,10 42,10 50,10 58,10', 'color' => '#47c1bf'],
        ['label' => 'Avg order value', 'value' => '₹0', 'sparkline' => '2,10 10,10 18,10 26,10 34,10 42,10 50,10 58,10', 'color' => '#9c6ade'],
    ]])

    {{-- Main card --}}
    <div class="bg-white rounded-xl shadow-sm" x-data="{ checkAll: false }">

        {{-- Tabs --}}
        <div class="flex gap-0 px-4" style="border-bottom:1px solid #e1e1e1">
            <a href="{{ route('admin.customers.index') }}"
               class="relative px-4 py-3 text-sm font-medium text-gray-900">
                All
                <span class="absolute bottom-0 left-0 right-0 h-0.5 bg-gray-900 rounded-t"></span>
            </a>
        </div>

        {{-- Search row --}}
        <div class="flex items-center gap-2 p-4" style="border-bottom:1px solid #e1e1e1">
            <form action="{{ route('admin.customers.index') }}" method="GET" class="flex items-center gap-2 flex-1">
                <div class="relative flex-1 max-w-md">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Search customers"
                           class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                </div>
                <button type="submit" class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                    Search
                </button>
                @if(request('search'))
                    <a href="{{ route('admin.customers.index') }}" class="px-3 py-2 text-sm text-gray-500 hover:text-gray-700">Clear</a>
                @endif
            </form>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50" style="border-bottom:1px solid #e1e1e1">
                        <th class="w-10 px-4 py-3 text-left">
                            <input type="checkbox" class="rounded border-gray-300" x-model="checkAll">
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Orders</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total spent</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $customer)
                        <tr class="hover:bg-gray-50 cursor-pointer" style="border-bottom:1px solid #e1e1e1"
                            onclick="window.location='{{ route('admin.customers.show', $customer) }}'">
                            <td class="px-4 py-3" onclick="event.stopPropagation()">
                                <input type="checkbox" class="rounded border-gray-300" :checked="checkAll">
                            </td>
                            <td class="px-4 py-3">
                                <div>
                                    <div class="flex items-center flex-wrap gap-1.5">
                                        <p class="font-medium text-gray-900">{{ $customer->full_name }}</p>
                                        @if(is_array($customer->admin_tags) && count($customer->admin_tags))
                                            @foreach(array_slice($customer->admin_tags, 0, 2) as $tag)
                                                <span class="inline-flex items-center px-1.5 py-0.5 text-[10px] font-medium bg-primary-50 text-primary-700 rounded-full">{{ $tag }}</span>
                                            @endforeach
                                            @if(count($customer->admin_tags) > 2)
                                                <span class="inline-flex items-center px-1.5 py-0.5 text-[10px] font-medium bg-gray-100 text-gray-600 rounded-full">+{{ count($customer->admin_tags) - 2 }}</span>
                                            @endif
                                        @endif
                                    </div>
                                    <p class="text-xs text-gray-500">{{ $customer->email }}</p>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ $customer->orders_count }}</td>
                            <td class="px-4 py-3 text-gray-700">@price($customer->orders_sum_total ?? 0)</td>
                            <td class="px-4 py-3 text-gray-500">
                                @if($customer->city || $customer->country)
                                    {{ collect([$customer->city, $customer->country])->filter()->implode(', ') }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ $customer->created_at->format('M d, Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center">
                                <div class="text-center py-12">
                                    <svg class="w-12 h-12 mx-auto mb-3 text-neutral-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128H5.228A2 2 0 013 17.208V17.13a4.002 4.002 0 013.21-3.922A3 3 0 019 10.5a3 3 0 013.79 2.708 4.003 4.003 0 013.21 3.922"/></svg>
                                    <h3 class="text-sm font-semibold text-neutral-900 mb-1">No customers yet</h3>
                                    <p class="text-xs text-neutral-500 mb-4">
                                        @if(request('search'))
                                            No customers match your search. Try a different term.
                                        @else
                                            Customers will appear here when they create an account.
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
        @if($customers->hasPages())
            <div class="px-4 py-3 flex items-center justify-between" style="border-top:1px solid #e1e1e1">
                <p class="text-sm text-gray-500">
                    Showing {{ $customers->firstItem() }}–{{ $customers->lastItem() }} of {{ $customers->total() }}
                </p>
                <div class="flex items-center gap-1">
                    @if($customers->onFirstPage())
                        <span class="px-3 py-1.5 text-sm text-gray-300 border border-gray-200 rounded-lg">&lsaquo; Previous</span>
                    @else
                        <a href="{{ $customers->previousPageUrl() }}" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">&lsaquo; Previous</a>
                    @endif
                    @if($customers->hasMorePages())
                        <a href="{{ $customers->nextPageUrl() }}" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">Next &rsaquo;</a>
                    @else
                        <span class="px-3 py-1.5 text-sm text-gray-300 border border-gray-200 rounded-lg">Next &rsaquo;</span>
                    @endif
                </div>
            </div>
        @endif
    </div>
</x-layouts.admin>
