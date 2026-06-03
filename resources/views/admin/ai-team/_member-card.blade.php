@php
    $initial = strtoupper(mb_substr($member->name, 0, 1));
    $tags = is_array($member->expertise_tags ?? null) ? $member->expertise_tags : [];
@endphp
<a href="{{ route('admin.ai-team.show', $member->slug) }}"
   class="block bg-white rounded-xl border border-gray-200 p-4 hover:border-gray-400 hover:shadow-sm transition no-underline text-inherit">
    <div class="flex items-start gap-3">
        <div class="w-10 h-10 rounded-lg bg-gray-900 text-white flex items-center justify-center text-sm font-semibold shrink-0">
            {{ $initial }}
        </div>
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2">
                <h3 class="text-sm font-semibold text-gray-900 truncate">{{ $member->name }}</h3>
                <span class="text-[10px] font-medium text-gray-500 uppercase tracking-wide">{{ $member->slug }}</span>
            </div>
            <p class="text-xs text-gray-600 mt-0.5 truncate">{{ $member->role }}</p>

            <div class="flex items-center gap-2 mt-2 text-[11px] text-gray-500">
                <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-gray-100 text-gray-700">
                    {{ Str::title(str_replace('_', ' ', $member->department)) }}
                </span>
                @if($member->reports_to_slug)
                    <span class="truncate">Reports to <span class="font-medium text-gray-700">{{ $member->reports_to_slug }}</span></span>
                @else
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-amber-100 text-amber-800">Leader</span>
                @endif
            </div>

            @if(count($tags) > 0)
                <div class="flex flex-wrap gap-1 mt-2">
                    @foreach(array_slice($tags, 0, 4) as $tag)
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 text-[10px]">{{ $tag }}</span>
                    @endforeach
                    @if(count($tags) > 4)
                        <span class="text-[10px] text-gray-400">+{{ count($tags) - 4 }} more</span>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-100">
        <span class="text-[11px] text-gray-500">
            {{ $member->knowledge_count ?? 0 }} knowledge {{ Str::plural('entry', $member->knowledge_count ?? 0) }}
        </span>
        <span class="text-[11px] font-medium text-gray-700">View details &rarr;</span>
    </div>
</a>
