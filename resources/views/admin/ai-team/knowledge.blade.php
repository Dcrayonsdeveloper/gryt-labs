<x-layouts.admin>
    <x-slot name="title">AI Team &mdash; Knowledge Library</x-slot>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.ai-team.index') }}"
                   class="inline-flex p-1.5 text-gray-600 hover:bg-gray-100 rounded-md"
                   title="Back to AI Team">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                </a>
                <div>
                    <h1 class="text-xl font-semibold text-gray-900 leading-tight">Knowledge Library</h1>
                    <p class="text-xs text-gray-500 mt-0.5">Searchable research, intel, and context across all team members.</p>
                </div>
            </div>
            <span class="text-xs text-gray-500">{{ number_format($entries->total()) }} {{ Str::plural('entry', $entries->total()) }}</span>
        </div>
    </x-slot>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <form method="GET" action="{{ route('admin.ai-team.knowledge') }}" class="p-4 border-b border-gray-100">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-2">
                <div class="lg:col-span-2">
                    <label class="block text-[11px] font-medium text-gray-600 mb-1">Search</label>
                    <input type="text" name="search" value="{{ $search }}"
                           placeholder="Title or content..."
                           class="w-full text-[13px] border border-gray-300 rounded-lg px-2.5 py-1.5 focus:border-gray-800 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-gray-600 mb-1">Member</label>
                    <select name="member_id" class="w-full text-[13px] border border-gray-300 rounded-lg px-2.5 py-1.5 focus:border-gray-800 focus:outline-none">
                        <option value="">All members</option>
                        @foreach($members as $m)
                            <option value="{{ $m->id }}" @selected((string) $memberId === (string) $m->id)>{{ $m->name }} ({{ $m->slug }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-gray-600 mb-1">Category</label>
                    <select name="category" class="w-full text-[13px] border border-gray-300 rounded-lg px-2.5 py-1.5 focus:border-gray-800 focus:outline-none">
                        <option value="">All categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}" @selected($category === $cat)>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-gray-600 mb-1">Priority</label>
                    <select name="priority" class="w-full text-[13px] border border-gray-300 rounded-lg px-2.5 py-1.5 focus:border-gray-800 focus:outline-none">
                        <option value="">All priorities</option>
                        @foreach($priorities as $p)
                            <option value="{{ $p }}" @selected($priority === $p)>{{ Str::title($p) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="flex items-center gap-2 mt-3">
                <button type="submit"
                        class="inline-flex items-center bg-gray-900 hover:bg-gray-700 border border-gray-900 text-white text-[13px] font-medium px-3 py-1.5 rounded-lg cursor-pointer">
                    Apply filters
                </button>
                @if($search || $memberId || $category || $priority)
                    <a href="{{ route('admin.ai-team.knowledge') }}"
                       class="inline-flex items-center bg-white hover:bg-gray-50 border border-gray-500 text-gray-800 text-[13px] font-medium px-3 py-1.5 rounded-lg no-underline">
                        Clear
                    </a>
                @endif
            </div>
        </form>

        @if($entries->count() === 0)
            <div class="p-10 text-center">
                <p class="text-sm text-gray-600">No knowledge entries match these filters.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full border-collapse min-w-[900px]">
                    <thead class="bg-gray-50">
                        <tr class="border-b border-gray-200">
                            <th class="px-3 py-2.5 text-left text-xs font-medium text-gray-600 whitespace-nowrap min-w-[320px]">Title</th>
                            <th class="px-3 py-2.5 text-left text-xs font-medium text-gray-600 whitespace-nowrap">Member</th>
                            <th class="px-3 py-2.5 text-left text-xs font-medium text-gray-600 whitespace-nowrap">Category</th>
                            <th class="px-3 py-2.5 text-left text-xs font-medium text-gray-600 whitespace-nowrap">Priority</th>
                            <th class="px-3 py-2.5 text-left text-xs font-medium text-gray-600 whitespace-nowrap">Date</th>
                            <th class="px-3 py-2.5 text-right text-xs font-medium text-gray-600 whitespace-nowrap">&nbsp;</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($entries as $entry)
                            @php
                                $priorityColor = match($entry->priority) {
                                    'critical' => 'bg-red-100 text-red-800',
                                    'high' => 'bg-orange-100 text-orange-800',
                                    'medium' => 'bg-blue-100 text-blue-800',
                                    'low' => 'bg-gray-100 text-gray-600',
                                    default => 'bg-gray-100 text-gray-600',
                                };
                            @endphp
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="px-3 py-2.5">
                                    <div class="text-[13px] font-medium text-gray-900">{{ $entry->title }}</div>
                                    <div class="text-[11px] text-gray-500 line-clamp-1 mt-0.5">{{ Str::limit(strip_tags($entry->content), 140) }}</div>
                                </td>
                                <td class="px-3 py-2.5 text-[12px]">
                                    @if($entry->member)
                                        <a href="{{ route('admin.ai-team.show', $entry->member->slug) }}"
                                           class="inline-flex items-center px-1.5 py-0.5 rounded bg-gray-100 hover:bg-gray-200 text-gray-700 text-[11px] no-underline">
                                            {{ $entry->member->name }}
                                        </a>
                                    @else
                                        <span class="text-gray-400 text-[11px]">Shared</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2.5">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-gray-100 text-gray-700 text-[11px]">{{ $entry->category }}</span>
                                </td>
                                <td class="px-3 py-2.5">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded {{ $priorityColor }} text-[10px] uppercase tracking-wide">{{ $entry->priority }}</span>
                                </td>
                                <td class="px-3 py-2.5 text-[12px] text-gray-600 whitespace-nowrap">
                                    {{ optional($entry->knowledge_date)->format('M d, Y') }}
                                </td>
                                <td class="px-3 py-2.5 text-right">
                                    @if($entry->source_url)
                                        <a href="{{ $entry->source_url }}" target="_blank" rel="noopener"
                                           class="text-[12px] text-blue-700 hover:underline">Source</a>
                                    @elseif($entry->member)
                                        <a href="{{ route('admin.ai-team.show', $entry->member->slug) }}"
                                           class="text-[12px] text-blue-700 hover:underline">View</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-4 py-3 border-t border-gray-100">
                {{ $entries->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
