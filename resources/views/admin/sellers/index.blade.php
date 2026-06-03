<x-layouts.admin>
    <x-slot name="title">Sellers</x-slot>

    {{-- Header --}}
    <div class="flex items-center justify-between mb-5">
        <h1 class="text-xl font-semibold text-gray-900">Sellers</h1>
        <a href="{{ route('admin.sellers.pending') }}" class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
            Pending approvals
            @if($stats['pending'] > 0)
                <span class="ml-2 px-1.5 py-0.5 text-xs bg-white text-gray-900 rounded-full">{{ $stats['pending'] }}</span>
            @endif
        </a>
    </div>

    {{-- Main card --}}
    <div class="bg-white rounded-xl shadow-sm" x-data="{ tab: '{{ request('status', 'all') }}', checkAll: false }">

        {{-- Tabs --}}
        <div class="flex gap-0 px-4" style="border-bottom:1px solid #e1e1e1">
            @php
                $tabs = [
                    'all' => 'All',
                    'approved' => 'Active',
                    'pending' => 'Pending',
                    'suspended' => 'Suspended',
                ];
            @endphp
            @foreach($tabs as $key => $label)
                <a href="{{ route('admin.sellers.index', array_merge(request()->except('status', 'page'), $key !== 'all' ? ['status' => $key] : [])) }}"
                   class="relative px-4 py-3 text-sm font-medium transition-colors
                          {{ request('status', 'all') === $key ? 'text-gray-900' : 'text-gray-500 hover:text-gray-700' }}">
                    {{ $label }}
                    @if(request('status', 'all') === $key)
                        <span class="absolute bottom-0 left-0 right-0 h-0.5 bg-gray-900 rounded-t"></span>
                    @endif
                </a>
            @endforeach
        </div>

        {{-- Search & filter row --}}
        <div class="flex items-center gap-2 p-4" style="border-bottom:1px solid #e1e1e1">
            <form action="{{ route('admin.sellers.index') }}" method="GET" class="flex items-center gap-2 flex-1">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                <div class="relative flex-1 max-w-md">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Search sellers"
                           class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                </div>
                <button type="submit" class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                    Search
                </button>
                @if(request('search'))
                    <a href="{{ route('admin.sellers.index', request()->only('status')) }}" class="px-3 py-2 text-sm text-gray-500 hover:text-gray-700">Clear</a>
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
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Seller</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Store</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Products</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Commission</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sellers as $seller)
                        <tr class="hover:bg-gray-50 cursor-pointer" style="border-bottom:1px solid #e1e1e1"
                            onclick="window.location='{{ route('admin.sellers.show', $seller) }}'">
                            <td class="px-4 py-3" onclick="event.stopPropagation()">
                                <input type="checkbox" class="rounded border-gray-300" :checked="checkAll">
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center shrink-0">
                                        <span class="text-xs font-medium text-gray-600">{{ strtoupper(substr($seller->user->name ?? 'S', 0, 1)) }}</span>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $seller->user->name ?? 'N/A' }}</p>
                                        <p class="text-xs text-gray-500">{{ $seller->user->email ?? 'N/A' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-gray-900">{{ $seller->store_name }}</p>
                                @if($seller->business_name)
                                    <p class="text-xs text-gray-500">{{ $seller->business_name }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ $seller->products_count ?? $seller->products->count() }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $seller->commission_rate ?? 15 }}%</td>
                            <td class="px-4 py-3">
                                @switch($seller->status)
                                    @case('approved')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 text-xs font-medium rounded-full bg-green-50 text-green-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-green-600"></span>
                                            Active
                                        </span>
                                        @break
                                    @case('pending')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 text-xs font-medium rounded-full bg-yellow-50 text-yellow-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-yellow-500"></span>
                                            Pending
                                        </span>
                                        @break
                                    @case('suspended')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 text-xs font-medium rounded-full bg-red-50 text-red-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-red-600"></span>
                                            Suspended
                                        </span>
                                        @break
                                    @case('rejected')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-600">
                                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                            Rejected
                                        </span>
                                        @break
                                @endswitch
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ $seller->created_at->format('M d, Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-gray-500">No sellers found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($sellers->hasPages())
            <div class="px-4 py-3 flex items-center justify-between" style="border-top:1px solid #e1e1e1">
                <p class="text-sm text-gray-500">
                    Showing {{ $sellers->firstItem() }}–{{ $sellers->lastItem() }} of {{ $sellers->total() }}
                </p>
                <div class="flex items-center gap-1">
                    @if($sellers->onFirstPage())
                        <span class="px-3 py-1.5 text-sm text-gray-300 border border-gray-200 rounded-lg">&lsaquo; Previous</span>
                    @else
                        <a href="{{ $sellers->previousPageUrl() }}" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">&lsaquo; Previous</a>
                    @endif
                    @if($sellers->hasMorePages())
                        <a href="{{ $sellers->nextPageUrl() }}" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">Next &rsaquo;</a>
                    @else
                        <span class="px-3 py-1.5 text-sm text-gray-300 border border-gray-200 rounded-lg">Next &rsaquo;</span>
                    @endif
                </div>
            </div>
        @endif
    </div>
</x-layouts.admin>
