<x-layouts.admin>
    <x-slot name="title">{{ $member->name }} &mdash; AI Team</x-slot>

    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <a href="{{ route('admin.ai-team.index') }}"
                   class="inline-flex p-1.5 text-gray-600 hover:bg-gray-100 rounded-md"
                   title="Back to AI Team">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                </a>
                <div class="min-w-0">
                    <h1 class="text-xl font-semibold text-gray-900 leading-tight truncate">{{ $member->name }}</h1>
                    <p class="text-xs text-gray-500 truncate">{{ $member->role }}</p>
                </div>
            </div>
            <a href="{{ route('admin.ai-team.knowledge', ['member_id' => $member->id]) }}"
               class="inline-flex items-center bg-white hover:bg-gray-50 border border-gray-500 text-gray-800 text-[13px] font-medium px-3 py-1.5 rounded-lg no-underline shrink-0">
                Search this member's knowledge
            </a>
        </div>
    </x-slot>

    @php
        $initial = strtoupper(mb_substr($member->name, 0, 1));
        $tags = is_array($member->expertise_tags ?? null) ? $member->expertise_tags : [];
        $connected = is_array($member->connected_to ?? null) ? $member->connected_to : [];
        $kpis = is_array($member->kpi_definitions ?? null) ? $member->kpi_definitions : [];
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Left column: member profile -->
        <div class="lg:col-span-1 space-y-4">
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <div class="flex items-start gap-3">
                    <div class="w-14 h-14 rounded-lg bg-gray-900 text-white flex items-center justify-center text-xl font-semibold shrink-0">
                        {{ $initial }}
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-base font-semibold text-gray-900 truncate">{{ $member->name }}</h2>
                        <p class="text-xs text-gray-500">{{ $member->slug }}</p>
                        <div class="flex flex-wrap items-center gap-1.5 mt-2">
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-gray-100 text-gray-700 text-[11px]">
                                {{ Str::title(str_replace('_', ' ', $member->department)) }}
                            </span>
                            @if($member->is_active)
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-green-100 text-green-800 text-[11px]">Active</span>
                            @else
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-gray-100 text-gray-500 text-[11px]">Inactive</span>
                            @endif
                        </div>
                    </div>
                </div>

                <dl class="mt-4 grid grid-cols-2 gap-3 text-[12px]">
                    <div>
                        <dt class="text-gray-500">Conversations</dt>
                        <dd class="font-semibold text-gray-900">{{ number_format($member->total_conversations) }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Decisions made</dt>
                        <dd class="font-semibold text-gray-900">{{ number_format($member->total_decisions_made) }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Performance</dt>
                        <dd class="font-semibold text-gray-900">{{ number_format($member->performance_score, 1) }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Acceptance</dt>
                        <dd class="font-semibold text-gray-900">{{ $member->getAcceptanceRate() }}%</dd>
                    </div>
                </dl>
            </div>

            <!-- Reporting line -->
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <h3 class="text-xs font-semibold text-gray-800 uppercase tracking-wide mb-2">Reporting</h3>
                @if($reportsTo)
                    <p class="text-[12px] text-gray-600">Reports to
                        <a href="{{ route('admin.ai-team.show', $reportsTo->slug) }}" class="text-blue-700 hover:underline font-medium">{{ $reportsTo->name }}</a>
                        <span class="text-gray-500">&middot; {{ $reportsTo->role }}</span>
                    </p>
                @else
                    <p class="text-[12px] text-gray-500">No reporting line (top of account).</p>
                @endif

                @if($directReports->count() > 0)
                    <div class="mt-3">
                        <p class="text-[11px] text-gray-500 uppercase tracking-wide mb-1">Direct reports ({{ $directReports->count() }})</p>
                        <div class="flex flex-wrap gap-1">
                            @foreach($directReports as $dr)
                                <a href="{{ route('admin.ai-team.show', $dr->slug) }}"
                                   class="inline-flex items-center px-1.5 py-0.5 rounded bg-gray-100 hover:bg-gray-200 text-gray-700 text-[11px] no-underline">
                                    {{ $dr->name }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <!-- Expertise tags -->
            @if(count($tags) > 0)
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <h3 class="text-xs font-semibold text-gray-800 uppercase tracking-wide mb-2">Expertise</h3>
                    <div class="flex flex-wrap gap-1">
                        @foreach($tags as $tag)
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 text-[11px]">{{ $tag }}</span>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Connected members -->
            @if(count($connected) > 0)
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <h3 class="text-xs font-semibold text-gray-800 uppercase tracking-wide mb-2">Works closely with</h3>
                    <div class="flex flex-wrap gap-1">
                        @foreach($connected as $slug)
                            <a href="{{ route('admin.ai-team.show', $slug) }}"
                               class="inline-flex items-center px-1.5 py-0.5 rounded bg-gray-100 hover:bg-gray-200 text-gray-700 text-[11px] no-underline">
                                {{ $slug }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- KPIs -->
            @if(count($kpis) > 0)
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <h3 class="text-xs font-semibold text-gray-800 uppercase tracking-wide mb-2">KPIs</h3>
                    <ul class="space-y-1.5 text-[12px]">
                        @foreach($kpis as $kpi)
                            <li class="flex items-center justify-between gap-2">
                                <span class="text-gray-600 truncate">{{ $kpi['metric'] ?? '—' }}</span>
                                <span class="font-medium text-gray-900 shrink-0">
                                    {{ $kpi['target'] ?? '' }}<span class="text-gray-500 text-[11px] ml-0.5">{{ $kpi['unit'] ?? '' }}</span>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <!-- Right column: prompt + brief + knowledge -->
        <div class="lg:col-span-2 space-y-4">
            <!-- System prompt preview -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden" x-data="{ expanded: false }">
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-900">System Prompt</h3>
                    <button type="button"
                            @click="expanded = !expanded"
                            class="text-xs text-blue-700 hover:underline cursor-pointer">
                        <span x-show="!expanded">Expand</span>
                        <span x-show="expanded" x-cloak>Collapse</span>
                    </button>
                </div>
                <div class="p-4">
                    <pre class="text-[12px] text-gray-700 whitespace-pre-wrap font-mono leading-relaxed"
                         x-bind:class="expanded ? '' : 'line-clamp-8'">{{ $member->system_prompt }}</pre>
                </div>
            </div>

            <!-- Today's brief -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-900">Today's Daily Brief</h3>
                    <span class="text-xs text-gray-500">{{ now()->toDateString() }}</span>
                </div>
                <div class="p-4">
                    @if($todayBrief)
                        <pre class="text-[12px] text-gray-700 whitespace-pre-wrap leading-relaxed">{{ $todayBrief->brief_content }}</pre>
                    @else
                        <p class="text-[12px] text-gray-500">No brief generated for today yet. Briefs are produced by the scheduled job.</p>
                    @endif
                </div>
            </div>

            <!-- Knowledge entries -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-900">Knowledge ({{ $knowledge->count() }})</h3>
                    <a href="{{ route('admin.ai-team.knowledge', ['member_id' => $member->id]) }}"
                       class="text-xs text-blue-700 hover:underline">Open in library &rarr;</a>
                </div>

                @if($knowledge->count() === 0)
                    <div class="p-6 text-center">
                        <p class="text-sm text-gray-600 mb-1">No knowledge entries yet.</p>
                        <p class="text-xs text-gray-500">Run <code class="bg-gray-100 px-1 py-0.5 rounded text-[11px]">php artisan db:seed --class=JikraTeamKnowledgeSeeder</code> to seed starter entries.</p>
                    </div>
                @else
                    <ul class="divide-y divide-gray-100">
                        @foreach($knowledge as $k)
                            @php
                                $priorityColor = match($k->priority) {
                                    'critical' => 'bg-red-100 text-red-800',
                                    'high' => 'bg-orange-100 text-orange-800',
                                    'medium' => 'bg-blue-100 text-blue-800',
                                    'low' => 'bg-gray-100 text-gray-600',
                                    default => 'bg-gray-100 text-gray-600',
                                };
                            @endphp
                            <li class="px-4 py-3 hover:bg-gray-50">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <h4 class="text-sm font-medium text-gray-900">{{ $k->title }}</h4>
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded {{ $priorityColor }} text-[10px] uppercase tracking-wide">{{ $k->priority }}</span>
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-gray-100 text-gray-700 text-[10px]">{{ $k->category }}</span>
                                        </div>
                                        <p class="text-[12px] text-gray-600 mt-1 line-clamp-2">{{ Str::limit(strip_tags($k->content), 220) }}</p>
                                        <div class="flex items-center gap-3 mt-1.5 text-[11px] text-gray-500">
                                            <span>{{ optional($k->knowledge_date)->format('M d, Y') }}</span>
                                            @if($k->source)
                                                <span class="truncate">Source: {{ $k->source }}</span>
                                            @endif
                                            @if($k->source_url)
                                                <a href="{{ $k->source_url }}" target="_blank" rel="noopener" class="text-blue-700 hover:underline">Link</a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</x-layouts.admin>
