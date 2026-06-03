<x-layouts.admin>
    <x-slot name="title">AI Team</x-slot>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 leading-tight">AI Team</h1>
                <p class="text-xs text-gray-500 mt-0.5">Dedicated specialists assigned to this account. Read-only view.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.ai-team.knowledge') }}"
                   class="inline-flex items-center bg-white hover:bg-gray-50 border border-gray-500 text-gray-800 text-[13px] font-medium px-3 py-1.5 rounded-lg no-underline">
                    Knowledge Library
                </a>
            </div>
        </div>
    </x-slot>

    <x-slot name="statsBar">
        @include('admin.partials.stats-bar', ['stats' => [
            ['label' => 'Active members', 'value' => number_format($stats['members']), 'sparkline' => '2,12 10,10 18,8 26,10 34,6 42,8 50,5 58,3', 'color' => '#5c6ac4'],
            ['label' => 'Knowledge entries', 'value' => number_format($stats['knowledge']), 'sparkline' => '2,14 10,12 18,10 26,8 34,10 42,6 50,8 58,4', 'color' => '#50b83c'],
            ['label' => 'Daily briefs', 'value' => number_format($stats['briefs']), 'sparkline' => '2,10 10,10 18,10 26,10 34,10 42,10 50,10 58,10', 'color' => '#47c1bf'],
            ['label' => 'Conversations logged', 'value' => number_format($stats['conversations']), 'sparkline' => '2,16 10,14 18,12 26,10 34,8 42,6 50,4 58,2', 'color' => '#de3618'],
        ]])
    </x-slot>

    @php
        // Render known departments in curated order, then any unexpected ones afterwards.
        $orderedKeys = array_keys($departmentOrder);
        $otherKeys = $membersByDepartment->keys()->diff($orderedKeys)->values();
    @endphp

    @foreach($orderedKeys as $deptKey)
        @if($membersByDepartment->has($deptKey))
            @php $deptMembers = $membersByDepartment[$deptKey]; @endphp
            <div class="mb-6">
                <div class="flex items-baseline justify-between mb-2">
                    <h2 class="text-sm font-semibold text-gray-800 uppercase tracking-wide">
                        {{ $departmentOrder[$deptKey] }}
                    </h2>
                    <span class="text-xs text-gray-500">{{ $deptMembers->count() }} {{ Str::plural('member', $deptMembers->count()) }}</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach($deptMembers as $member)
                        @include('admin.ai-team._member-card', ['member' => $member])
                    @endforeach
                </div>
            </div>
        @endif
    @endforeach

    @foreach($otherKeys as $deptKey)
        @php $deptMembers = $membersByDepartment[$deptKey]; @endphp
        <div class="mb-6">
            <div class="flex items-baseline justify-between mb-2">
                <h2 class="text-sm font-semibold text-gray-800 uppercase tracking-wide">
                    {{ Str::title(str_replace('_', ' ', $deptKey)) }}
                </h2>
                <span class="text-xs text-gray-500">{{ $deptMembers->count() }} {{ Str::plural('member', $deptMembers->count()) }}</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach($deptMembers as $member)
                    @include('admin.ai-team._member-card', ['member' => $member])
                @endforeach
            </div>
        </div>
    @endforeach
</x-layouts.admin>
