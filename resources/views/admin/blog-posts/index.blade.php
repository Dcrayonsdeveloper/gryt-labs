<x-layouts.admin>
    <x-slot name="title">Blog posts</x-slot>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold text-gray-900 leading-tight">Blog posts</h1>
            <a href="{{ route('admin.blog-posts.create') }}"
               class="inline-flex items-center bg-gray-900 hover:bg-gray-700 border border-gray-900 text-white text-[13px] font-medium px-3 py-1.5 rounded-lg no-underline">
                Add post
            </a>
        </div>
    </x-slot>

    @php
        $currentStatus = request('status', '');
        $pageIds = $posts->pluck('id')->toArray();
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
                    <a href="{{ route('admin.blog-posts.index', array_merge(request()->except('status', 'page'), $statusKey ? ['status' => $statusKey] : [])) }}"
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
            <form action="{{ route('admin.blog-posts.index') }}" method="GET" class="flex items-center gap-2 flex-1">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                <div class="flex-1 relative">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="#8a8a8a" class="absolute left-2.5 top-1/2 -translate-y-1/2"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/></svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Filter blog posts"
                           class="w-full py-1.5 pl-8 pr-2.5 text-[13px] border border-gray-300 rounded-lg outline-none text-gray-900 bg-white focus:border-blue-600 focus:ring-1 focus:ring-blue-600">
                </div>
                @if(request()->hasAny(['search']))
                    <a href="{{ route('admin.blog-posts.index', request('status') ? ['status' => request('status')] : []) }}"
                       class="py-1.5 px-3 text-[13px] text-gray-800 no-underline whitespace-nowrap">
                        Clear filters
                    </a>
                @endif
            </form>
        </div>

        {{-- Bulk actions --}}
        @include('admin.partials.bulk-action-bar', [
            'route' => route('admin.blog-posts.bulk-action'),
            'actions' => ['publish' => 'Publish', 'unpublish' => 'Unpublish', 'delete' => 'Delete'],
        ])

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
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">Author</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">Status</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($posts as $post)
                        <tr class="border-b border-gray-100 cursor-pointer hover:bg-gray-50"
                            x-on:click="if(!$event.target.closest('input[type=checkbox]')) window.location.href='{{ route('admin.blog-posts.edit', $post) }}'">
                            <td class="w-9 px-2 py-2 pl-3 text-center" x-on:click.stop>
                                <input type="checkbox"
                                       class="w-4 h-4 cursor-pointer accent-gray-900"
                                       :checked="selected.includes({{ $post->id }})"
                                       x-on:change="toggle({{ $post->id }})">
                            </td>
                            <td class="px-3 py-2">
                                <div class="flex items-center gap-3">
                                    @if($post->featured_image)
                                        <img src="{{ asset('storage/' . $post->featured_image) }}" alt="{{ $post->title }}"
                                             class="w-10 h-10 rounded-lg object-cover border border-gray-200 flex-shrink-0">
                                    @else
                                        <div class="w-10 h-10 rounded-lg bg-gray-100 border border-gray-200 flex items-center justify-center flex-shrink-0">
                                            <svg width="18" height="18" viewBox="0 0 20 20" fill="#b5b5b5"><path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/></svg>
                                        </div>
                                    @endif
                                    <span class="block text-[13px] font-medium text-gray-900 whitespace-nowrap overflow-hidden text-ellipsis max-w-[280px]" title="{{ $post->title }}">
                                        {{ $post->title }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-3 py-2 text-[13px] text-gray-800">
                                {{ $post->author->full_name ?? $post->author->name ?? '--' }}
                            </td>
                            <td class="px-3 py-2">
                                @if($post->is_published)
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
                                {{ $post->published_at ? $post->published_at->format('M d, Y') : ($post->created_at ? $post->created_at->format('M d, Y') : '--') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#c9c9c9" stroke-width="1.5" class="mb-3"><path stroke-linecap="round" stroke-linejoin="round" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
                                    <p class="text-sm font-medium text-gray-900 mb-1">No blog posts found</p>
                                    <p class="text-[13px] text-gray-600 mb-4">
                                        @if(request()->hasAny(['search', 'status']))
                                            Try changing the filters or search term.
                                        @else
                                            Write your first blog post to engage customers.
                                        @endif
                                    </p>
                                    @if(!request()->hasAny(['search', 'status']))
                                        <a href="{{ route('admin.blog-posts.create') }}"
                                           class="inline-flex items-center bg-gray-900 hover:bg-gray-700 text-white text-[13px] font-medium px-3.5 py-1.5 rounded-lg no-underline">
                                            Add post
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
        @if($posts->total() > 0)
            <div class="flex items-center justify-center gap-4 p-3 border-t border-gray-200">
                <div class="flex items-center gap-2">
                    @if($posts->onFirstPage())
                        <span class="p-1.5 text-gray-300 cursor-not-allowed">
                            <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        </span>
                    @else
                        <a href="{{ $posts->previousPageUrl() }}" class="inline-flex p-1.5 text-gray-600 hover:bg-gray-100 rounded-md no-underline">
                            <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        </a>
                    @endif

                    <span class="text-[13px] text-gray-800">
                        {{ $posts->firstItem() }}-{{ $posts->lastItem() }} of {{ $posts->total() }}
                    </span>

                    @if($posts->hasMorePages())
                        <a href="{{ $posts->nextPageUrl() }}" class="inline-flex p-1.5 text-gray-600 hover:bg-gray-100 rounded-md no-underline">
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
