<x-layouts.admin>
    <x-slot name="title">Pages</x-slot>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold text-gray-900 leading-tight">Pages</h1>
            <a href="{{ route('admin.pages.create') }}"
               class="inline-flex items-center bg-gray-900 hover:bg-gray-700 border border-gray-900 text-white text-[13px] font-medium px-3 py-1.5 rounded-lg no-underline">
                Add page
            </a>
        </div>
    </x-slot>

    @php
        $currentStatus = request('status', '');
        $pageIds = $pages->pluck('id')->toArray();
    @endphp

    <div class="bg-white rounded-xl shadow-sm overflow-hidden"
         x-data="{
             selected: [],
             toggleAll(checked) {
                 this.selected = checked ? {{ json_encode($pageIds) }} : [];
             },
             toggle(id) {
                 const idx = this.selected.indexOf(id);
                 idx === -1 ? this.selected.push(id) : this.selected.splice(idx, 1);
             },
             get allChecked() {
                 return this.selected.length === {{ count($pageIds) }} && {{ count($pageIds) }} > 0;
             }
         }">

        {{-- Tab row --}}
        <div class="flex items-center justify-between border-b border-gray-200 px-3">
            <div class="flex items-center gap-0">
                @php
                    $tabs = [
                        '' => 'All',
                        'published' => 'Published',
                        'draft' => 'Draft',
                    ];
                @endphp
                @foreach($tabs as $statusKey => $label)
                    <a href="{{ route('admin.pages.index', array_merge(request()->except('status', 'page'), $statusKey ? ['status' => $statusKey] : [])) }}"
                       class="relative px-3 py-2.5 text-[13px] font-medium no-underline {{ $currentStatus === $statusKey ? 'text-gray-900' : 'text-gray-600 hover:text-gray-900' }}">
                        {{ $label }}
                        @if($currentStatus === $statusKey)
                            <span class="absolute -bottom-px left-3 right-3 h-0.5 bg-gray-900 rounded-sm"></span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Search row --}}
        <div class="flex items-center gap-2 p-3 border-b border-gray-200">
            <form action="{{ route('admin.pages.index') }}" method="GET" class="flex items-center gap-2 flex-1">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                <div class="flex-1 relative">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="#8a8a8a" class="absolute left-2.5 top-1/2 -translate-y-1/2"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/></svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Filter pages"
                           class="w-full py-1.5 pl-8 pr-2.5 text-[13px] border border-gray-300 rounded-lg outline-none text-gray-900 bg-white focus:border-blue-600 focus:ring-1 focus:ring-blue-600">
                </div>
                @if(request()->hasAny(['search']))
                    <a href="{{ route('admin.pages.index', request('status') ? ['status' => request('status')] : []) }}"
                       class="px-3 py-1.5 text-[13px] text-gray-800 no-underline whitespace-nowrap">
                        Clear filters
                    </a>
                @endif
            </form>
        </div>

        {{-- Bulk actions --}}
        <div x-show="selected.length > 0" x-cloak
             class="flex items-center gap-3 px-3 py-2 bg-gray-100 border-b border-gray-200">
            <span class="text-[13px] font-medium text-gray-800">
                <span x-text="selected.length"></span> selected
            </span>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="w-9 px-2 py-2 pl-3 text-center">
                            <input type="checkbox"
                                   class="w-4 h-4 cursor-pointer accent-gray-900"
                                   @change="toggleAll($event.target.checked)"
                                   :checked="allChecked">
                        </th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">Title</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">Slug</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">Status</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">Updated</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pages as $page)
                        <tr class="border-b border-gray-100 cursor-pointer hover:bg-gray-50"
                            x-on:click="if(!$event.target.closest('input[type=checkbox]')) window.location.href='{{ route('admin.pages.edit', $page) }}'">
                            <td class="w-9 px-2 py-2 pl-3 text-center" x-on:click.stop>
                                <input type="checkbox"
                                       class="w-4 h-4 cursor-pointer accent-gray-900"
                                       :checked="selected.includes({{ $page->id }})"
                                       @change="toggle({{ $page->id }})">
                            </td>
                            <td class="px-3 py-2">
                                <span class="text-[13px] font-medium text-gray-900">{{ $page->title }}</span>
                            </td>
                            <td class="px-3 py-2">
                                <span class="text-xs text-neutral-500 font-mono">/{{ $page->slug }}</span>
                            </td>
                            <td class="px-3 py-2">
                                @if($page->is_published)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-800 inline-block"></span>
                                        Published
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-gray-200 text-gray-600">
                                        <span class="w-1.5 h-1.5 rounded-full bg-gray-500 inline-block"></span>
                                        Draft
                                    </span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-[13px] text-gray-800">
                                {{ $page->updated_at ? $page->updated_at->format('M d, Y') : '--' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#c9c9c9" stroke-width="1.5" class="mb-3"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    <p class="text-sm font-medium text-gray-900 mb-1">No pages found</p>
                                    <p class="text-[13px] text-gray-600 mb-4">
                                        @if(request()->hasAny(['search', 'status']))
                                            Try changing the filters or search term.
                                        @else
                                            Create pages for your store like About Us or Contact.
                                        @endif
                                    </p>
                                    @if(!request()->hasAny(['search', 'status']))
                                        <a href="{{ route('admin.pages.create') }}"
                                           class="inline-flex items-center bg-gray-900 hover:bg-gray-700 text-white text-[13px] font-medium px-3.5 py-1.5 rounded-lg no-underline">
                                            Add page
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($pages->total() > 0)
            <div class="flex items-center justify-center gap-4 p-3 border-t border-gray-200">
                <div class="flex items-center gap-2">
                    @if($pages->onFirstPage())
                        <span class="p-1.5 text-gray-300 cursor-not-allowed">
                            <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        </span>
                    @else
                        <a href="{{ $pages->previousPageUrl() }}" class="inline-flex p-1.5 text-gray-600 hover:bg-gray-100 rounded-md no-underline">
                            <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        </a>
                    @endif

                    <span class="text-[13px] text-gray-800">
                        {{ $pages->firstItem() }}-{{ $pages->lastItem() }} of {{ $pages->total() }}
                    </span>

                    @if($pages->hasMorePages())
                        <a href="{{ $pages->nextPageUrl() }}" class="inline-flex p-1.5 text-gray-600 hover:bg-gray-100 rounded-md no-underline">
                            <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                        </a>
                    @else
                        <span class="p-1.5 text-gray-300 cursor-not-allowed">
                            <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                        </span>
                    @endif
                </div>
            </div>
        @endif
    </div>
</x-layouts.admin>
