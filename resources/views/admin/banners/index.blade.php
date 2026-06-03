<x-layouts.admin>
    <x-slot name="title">Banners</x-slot>

    {{-- Header --}}
    <div class="flex items-center justify-between mb-5">
        <h1 class="text-xl font-semibold text-gray-900">Banners</h1>
        <a href="{{ route('admin.banners.create') }}"
           class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
            Add banner
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
             allIds: {{ $banners->pluck('id') }},
             toggleAll(checked) { this.selected = checked ? [...this.allIds] : []; },
             isAllSelected() { return this.allIds.length > 0 && this.selected.length === this.allIds.length; }
         }">

        {{-- Tabs --}}
        <div class="flex items-center gap-0 px-4" style="border-bottom:1px solid #e1e1e1">
            @php
                $tabs = [
                    'all' => 'All',
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                ];
            @endphp
            @foreach($tabs as $key => $label)
                <a href="{{ route('admin.banners.index', array_merge(request()->except('status', 'page'), $key === 'all' ? [] : ['status' => $key])) }}"
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
            <form action="{{ route('admin.banners.index') }}" method="GET" class="flex items-center gap-2">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                <div class="relative flex-1 max-w-sm">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Search banners"
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
                        <th class="px-4 py-3">Banner</th>
                        <th class="px-4 py-3">Position</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Order</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($banners as $banner)
                        <tr class="hover:bg-gray-50 cursor-pointer group" style="border-bottom:1px solid #e1e1e1"
                            onclick="if(!event.target.closest('input[type=checkbox]') && !event.target.closest('a') && !event.target.closest('button') && !event.target.closest('form')) window.location='{{ route('admin.banners.edit', $banner) }}'">
                            <td class="pl-4 pr-0 py-3 w-10" onclick="event.stopPropagation()">
                                <input type="checkbox" class="rounded border-gray-300 text-gray-900 focus:ring-gray-500"
                                       value="{{ $banner->id }}"
                                       :checked="selected.includes({{ $banner->id }})"
                                       @change="selected.includes({{ $banner->id }}) ? selected.splice(selected.indexOf({{ $banner->id }}), 1) : selected.push({{ $banner->id }})">
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    @if($banner->image_url)
                                        <img src="{{ asset('storage/' . $banner->image_url) }}" alt="{{ $banner->name }}"
                                             class="w-12 h-12 object-cover rounded-lg border border-gray-200 shrink-0">
                                    @else
                                        <div class="w-12 h-12 bg-gray-100 rounded-lg border border-gray-200 flex items-center justify-center shrink-0">
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                    @endif
                                    <span class="font-medium text-gray-900">{{ $banner->name }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ ucfirst(str_replace('_', ' ', $banner->position)) }}
                            </td>
                            <td class="px-4 py-3">
                                @if($banner->isActive())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Inactive</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ $banner->priority }}
                            </td>
                            <td class="px-4 py-3 text-right" onclick="event.stopPropagation()">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.banners.edit', $banner) }}"
                                       class="text-sm text-blue-600 hover:text-blue-800 font-medium">Edit</a>
                                    <form action="{{ route('admin.banners.destroy', $banner) }}" method="POST"
                                          onsubmit="return confirm('Delete this banner?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm text-red-600 hover:text-red-800 font-medium">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center">
                                <p class="text-gray-500 text-sm">No banners found</p>
                                <a href="{{ route('admin.banners.create') }}"
                                   class="inline-flex items-center mt-3 px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
                                    Add banner
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($banners->hasPages())
            <div class="px-4 py-3 flex items-center justify-between" style="border-top:1px solid #e1e1e1">
                <div class="text-sm text-gray-600">
                    Showing {{ $banners->firstItem() }}-{{ $banners->lastItem() }} of {{ $banners->total() }}
                </div>
                <div class="flex items-center gap-1">
                    @if($banners->onFirstPage())
                        <span class="px-3 py-1.5 text-sm text-gray-400 border border-gray-200 rounded-lg cursor-not-allowed">&lsaquo;</span>
                    @else
                        <a href="{{ $banners->previousPageUrl() }}" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">&lsaquo;</a>
                    @endif
                    @if($banners->hasMorePages())
                        <a href="{{ $banners->nextPageUrl() }}" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">&rsaquo;</a>
                    @else
                        <span class="px-3 py-1.5 text-sm text-gray-400 border border-gray-200 rounded-lg cursor-not-allowed">&rsaquo;</span>
                    @endif
                </div>
            </div>
        @endif
    </div>
</x-layouts.admin>
