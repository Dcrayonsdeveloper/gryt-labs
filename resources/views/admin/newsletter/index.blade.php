<x-layouts.admin>
    <x-slot name="title">Newsletter subscribers</x-slot>

    {{-- Header --}}
    <div class="flex items-center justify-between mb-5">
        <h1 class="text-xl font-semibold text-gray-900">Newsletter subscribers</h1>
        <a href="{{ route('admin.newsletter.export') }}{{ request()->hasAny(['status']) ? '?' . request()->getQueryString() : '' }}"
           class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            Export
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
             allIds: {{ $subscribers->pluck('id') }},
             toggleAll(checked) { this.selected = checked ? [...this.allIds] : []; },
             isAllSelected() { return this.allIds.length > 0 && this.selected.length === this.allIds.length; }
         }">

        {{-- Bulk Actions Bar --}}
        <div x-show="selected.length > 0" x-cloak
             class="px-4 py-3 bg-gray-50 flex items-center justify-between gap-3" style="border-bottom:1px solid #e1e1e1">
            <span class="text-sm font-medium text-gray-700">
                <span x-text="selected.length"></span> selected
            </span>
            <form method="POST" action="{{ route('admin.newsletter.bulk-action') }}"
                  @submit.prevent="$el.querySelector('input[name=ids]').value = JSON.stringify(selected); $el.submit()">
                @csrf
                <input type="hidden" name="ids" value="">
                <div class="flex gap-2">
                    <button type="submit" name="action" value="activate"
                            class="text-sm text-gray-700 hover:text-gray-900 font-medium">Activate</button>
                    <button type="submit" name="action" value="deactivate"
                            class="text-sm text-gray-700 hover:text-gray-900 font-medium">Deactivate</button>
                    <button type="submit" name="action" value="delete"
                            onclick="return confirm('Delete selected subscribers?')"
                            class="text-sm text-red-600 hover:text-red-800 font-medium">Delete</button>
                </div>
            </form>
        </div>

        {{-- Tabs --}}
        <div class="flex items-center gap-0 px-4" style="border-bottom:1px solid #e1e1e1">
            @php
                $tabs = [
                    'all' => 'All',
                    'active' => 'Subscribed',
                    'inactive' => 'Unsubscribed',
                ];
            @endphp
            @foreach($tabs as $key => $label)
                <a href="{{ route('admin.newsletter.index', array_merge(request()->except('status', 'page'), $key === 'all' ? [] : ['status' => $key])) }}"
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
            <form action="{{ route('admin.newsletter.index') }}" method="GET" class="flex items-center gap-2">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                <div class="relative flex-1 max-w-sm">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Search subscribers"
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
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Subscribed date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($subscribers as $subscriber)
                        <tr class="hover:bg-gray-50 cursor-pointer group" style="border-bottom:1px solid #e1e1e1">
                            <td class="pl-4 pr-0 py-3 w-10">
                                <input type="checkbox" class="rounded border-gray-300 text-gray-900 focus:ring-gray-500"
                                       value="{{ $subscriber->id }}"
                                       :checked="selected.includes({{ $subscriber->id }})"
                                       @change="selected.includes({{ $subscriber->id }}) ? selected.splice(selected.indexOf({{ $subscriber->id }}), 1) : selected.push({{ $subscriber->id }})">
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-medium text-gray-900">{{ $subscriber->email }}</span>
                            </td>
                            <td class="px-4 py-3">
                                @if($subscriber->is_active)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Subscribed</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Unsubscribed</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ ($subscriber->subscribed_at ?? $subscriber->created_at)->format('M d, Y') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-12 text-center">
                                <p class="text-gray-500 text-sm">
                                    @if(request()->hasAny(['search', 'status']))
                                        No subscribers match your filters
                                    @else
                                        No subscribers yet
                                    @endif
                                </p>
                                @if(request()->hasAny(['search', 'status']))
                                    <a href="{{ route('admin.newsletter.index') }}"
                                       class="inline-block mt-2 text-sm text-blue-600 hover:text-blue-800 font-medium">Clear filters</a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($subscribers->hasPages())
            <div class="px-4 py-3 flex items-center justify-between" style="border-top:1px solid #e1e1e1">
                <div class="text-sm text-gray-600">
                    Showing {{ $subscribers->firstItem() }}-{{ $subscribers->lastItem() }} of {{ $subscribers->total() }}
                </div>
                <div class="flex items-center gap-1">
                    @if($subscribers->onFirstPage())
                        <span class="px-3 py-1.5 text-sm text-gray-400 border border-gray-200 rounded-lg cursor-not-allowed">&lsaquo;</span>
                    @else
                        <a href="{{ $subscribers->previousPageUrl() }}" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">&lsaquo;</a>
                    @endif
                    @if($subscribers->hasMorePages())
                        <a href="{{ $subscribers->nextPageUrl() }}" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">&rsaquo;</a>
                    @else
                        <span class="px-3 py-1.5 text-sm text-gray-400 border border-gray-200 rounded-lg cursor-not-allowed">&rsaquo;</span>
                    @endif
                </div>
            </div>
        @endif
    </div>
</x-layouts.admin>
