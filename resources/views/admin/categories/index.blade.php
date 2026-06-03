<x-layouts.admin>
    <x-slot name="title">Collections</x-slot>

    {{-- Header: "Collections" title + "Add collection" button --}}
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-xl font-semibold text-gray-900 leading-7">Collections</h1>
        <a href="{{ route('admin.categories.create') }}"
           class="inline-flex items-center gap-1 bg-gray-900 hover:bg-gray-700 text-white text-[13px] font-medium px-3 py-1.5 rounded-lg no-underline">
            Add collection
        </a>
    </div>

    {{-- Main card --}}
    <div class="bg-white rounded-xl shadow-sm ring-1 ring-black/5">

        {{-- Tab row + toolbar --}}
        <div class="flex items-center justify-between px-3 border-b border-gray-200">
            <div class="flex items-center gap-0">
                {{-- "All" tab --}}
                <button class="text-[13px] font-medium text-gray-900 px-3 py-2.5 border-b-2 border-gray-900 bg-transparent border-t-0 border-l-0 border-r-0 cursor-pointer">
                    All
                </button>
                {{-- "+" tab button --}}
                <button class="text-[13px] text-gray-600 px-2 py-2.5 bg-transparent border-0 cursor-pointer flex items-center" title="Create view">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="none">
                        <path d="M10 5v10M5 10h10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>

            {{-- Right toolbar: search, filter, sort --}}
            <div class="flex items-center gap-1 py-1.5">
                {{-- Search --}}
                <button class="p-1.5 bg-transparent hover:bg-gray-100 border border-gray-300 rounded-lg cursor-pointer flex items-center justify-center text-gray-600" title="Search">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M8.5 3a5.5 5.5 0 014.383 8.823l3.896 3.9a.75.75 0 01-1.06 1.06l-3.9-3.896A5.5 5.5 0 118.5 3zm0 1.5a4 4 0 100 8 4 4 0 000-8z"/>
                    </svg>
                </button>
                {{-- Filter --}}
                <button class="p-1.5 bg-transparent hover:bg-gray-100 border border-gray-300 rounded-lg cursor-pointer flex items-center justify-center text-gray-600" title="Filter">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M2 5.5A.5.5 0 012.5 5h15a.5.5 0 010 1h-15a.5.5 0 01-.5-.5zm3 5a.5.5 0 01.5-.5h9a.5.5 0 010 1h-9a.5.5 0 01-.5-.5zm3 5a.5.5 0 01.5-.5h3a.5.5 0 010 1h-3a.5.5 0 01-.5-.5z"/>
                    </svg>
                </button>
                {{-- Sort --}}
                <button class="p-1.5 bg-transparent hover:bg-gray-100 border border-gray-300 rounded-lg cursor-pointer flex items-center justify-center text-gray-600" title="Sort">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M6 4.5a.75.75 0 01.75.75v8.69l1.72-1.72a.75.75 0 011.06 1.06l-3 3a.75.75 0 01-1.06 0l-3-3a.75.75 0 111.06-1.06l1.72 1.72V5.25A.75.75 0 016 4.5zm8 11a.75.75 0 01-.75-.75V6.06l-1.72 1.72a.75.75 0 01-1.06-1.06l3-3a.75.75 0 011.06 0l3 3a.75.75 0 01-1.06 1.06L14.75 6.06v8.69a.75.75 0 01-.75.75z"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Table --}}
        @if($categories->total() > 0)
            <table class="w-full border-collapse">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="w-7 py-2.5 pl-3 text-left">
                            <input type="checkbox" class="w-4 h-4 accent-gray-900 cursor-pointer rounded" />
                        </th>
                        <th class="px-3 py-2.5 text-left text-xs font-medium text-gray-600">Title</th>
                        <th class="px-3 py-2.5 text-left text-xs font-medium text-gray-600">Products</th>
                        <th class="px-3 py-2.5 text-left text-xs font-medium text-gray-600">Product conditions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($categories as $category)
                        <tr class="border-b border-gray-200 cursor-pointer hover:bg-gray-50"
                            x-on:click="if(!$event.target.closest('input[type=checkbox]')) window.location.href='{{ route('admin.categories.edit', $category) }}'">
                            <td class="w-7 py-2.5 pl-3" x-on:click.stop>
                                <input type="checkbox" value="{{ $category->id }}" class="w-4 h-4 accent-gray-900 cursor-pointer rounded" />
                            </td>
                            <td class="px-3 py-2.5">
                                <div class="flex items-center gap-3">
                                    @if($category->image_url)
                                        <img src="{{ asset('storage/' . $category->image_url) }}" alt="{{ $category->name }}"
                                             class="w-9 h-9 rounded-lg object-cover border border-gray-200 flex-shrink-0" />
                                    @else
                                        <div class="w-9 h-9 rounded-lg bg-gray-100 border border-gray-200 flex items-center justify-center flex-shrink-0">
                                            <svg width="18" height="18" viewBox="0 0 20 20" fill="#8a8a8a">
                                                <path d="M3.5 3A1.5 1.5 0 002 4.5v11A1.5 1.5 0 003.5 17h13a1.5 1.5 0 001.5-1.5v-11A1.5 1.5 0 0016.5 3h-13zm4.25 4a1.25 1.25 0 11-2.5 0 1.25 1.25 0 012.5 0zm-1.06 2.81L5 12.44V15.5h10v-2.44l-2.31-2.31a.75.75 0 00-1.06 0L9.31 13 7.75 11.44a1 1 0 00-1.06-.63z"/>
                                            </svg>
                                        </div>
                                    @endif
                                    <div>
                                        <span class="text-[13px] font-medium text-gray-900">{{ $category->name }}</span>
                                        @if($category->children && $category->children->count() > 0)
                                            <p class="text-xs text-gray-600 mt-px mb-0">{{ $category->children->count() }} subcollections</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-2.5 text-[13px] text-gray-900">
                                {{ $category->total_products_count ?? $category->products_count }}
                            </td>
                            <td class="px-3 py-2.5 text-[13px] text-gray-900">
                                @if($category->parent)
                                    {{ $category->parent->name }}
                                @elseif($category->is_active)
                                    <span class="text-gray-600">Manual</span>
                                @else
                                    <span class="text-gray-600">Draft</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Pagination --}}
            @if($categories->hasPages())
                <div class="flex items-center justify-center py-3 px-4 border-t border-gray-200">
                    <div class="flex items-center gap-2">
                        {{-- Previous --}}
                        @if($categories->onFirstPage())
                            <span class="px-2 py-1 border border-gray-200 rounded-lg cursor-not-allowed opacity-40 flex items-center">
                                <svg width="16" height="16" viewBox="0 0 20 20" fill="none">
                                    <path d="M12 5l-5 5 5 5" stroke="#616161" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                        @else
                            <a href="{{ $categories->previousPageUrl() }}"
                               class="px-2 py-1 border border-gray-300 rounded-lg flex items-center no-underline bg-white hover:bg-gray-50">
                                <svg width="16" height="16" viewBox="0 0 20 20" fill="none">
                                    <path d="M12 5l-5 5 5 5" stroke="#616161" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </a>
                        @endif

                        <span class="text-[13px] text-gray-900">{{ $categories->currentPage() }} of {{ $categories->lastPage() }}</span>

                        {{-- Next --}}
                        @if($categories->hasMorePages())
                            <a href="{{ $categories->nextPageUrl() }}"
                               class="px-2 py-1 border border-gray-300 rounded-lg flex items-center no-underline bg-white hover:bg-gray-50">
                                <svg width="16" height="16" viewBox="0 0 20 20" fill="none">
                                    <path d="M8 5l5 5-5 5" stroke="#616161" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </a>
                        @else
                            <span class="px-2 py-1 border border-gray-200 rounded-lg cursor-not-allowed opacity-40 flex items-center">
                                <svg width="16" height="16" viewBox="0 0 20 20" fill="none">
                                    <path d="M8 5l5 5-5 5" stroke="#616161" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                        @endif
                    </div>
                </div>
            @endif

        @else
            {{-- Empty state --}}
            <div class="flex flex-col items-center justify-center py-16 px-5 text-center">
                <div class="w-12 h-12 rounded-xl bg-gray-100 flex items-center justify-center mb-4">
                    <svg width="24" height="24" viewBox="0 0 20 20" fill="#8a8a8a">
                        <path d="M3.5 3A1.5 1.5 0 002 4.5v11A1.5 1.5 0 003.5 17h13a1.5 1.5 0 001.5-1.5v-11A1.5 1.5 0 0016.5 3h-13z"/>
                    </svg>
                </div>
                <p class="text-sm font-medium text-gray-900 mb-1">No collections found</p>
                <p class="text-[13px] text-gray-600 mb-4">Create your first collection to organize your products.</p>
                <a href="{{ route('admin.categories.create') }}"
                   class="inline-flex items-center bg-gray-900 hover:bg-gray-700 text-white text-[13px] font-medium px-3 py-1.5 rounded-lg no-underline">
                    Add collection
                </a>
            </div>
        @endif
    </div>
</x-layouts.admin>
