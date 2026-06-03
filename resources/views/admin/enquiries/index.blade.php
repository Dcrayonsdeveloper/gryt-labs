<x-layouts.admin>
    <x-slot name="title">Enquiries</x-slot>

    {{-- Header --}}
    <div class="flex items-center justify-between mb-5">
        <h1 class="text-xl font-semibold text-gray-900">Enquiries</h1>
    </div>

    {{-- Main card --}}
    @php $pageIds = $enquiries->pluck('id')->toArray(); @endphp
    <div class="bg-white rounded-xl shadow-sm border border-gray-200"
         x-data="{
             selected: [],
             toggleAll(checked) { this.selected = checked ? {{ json_encode($pageIds) }} : []; },
             toggle(id) { const i = this.selected.indexOf(id); i === -1 ? this.selected.push(id) : this.selected.splice(i, 1); }
         }">

        {{-- Tabs --}}
        <div class="flex items-center gap-0 px-4" style="border-bottom:1px solid #e1e1e1">
            @php
                $currentStatus = request('status', '');
                $tabs = [
                    '' => 'All',
                    'new' => 'New',
                    'read' => 'Read',
                    'replied' => 'Replied',
                ];
            @endphp
            @foreach($tabs as $value => $label)
                <a href="{{ route('admin.enquiries.index', array_merge(request()->except('status', 'page'), $value ? ['status' => $value] : [])) }}"
                   class="relative px-4 py-3 text-sm font-medium transition-colors
                          {{ $currentStatus === $value ? 'text-gray-900' : 'text-gray-500 hover:text-gray-700' }}">
                    {{ $label }}
                    @if($value === '' && isset($stats['total']))
                        <span class="ml-1 text-xs text-gray-400">{{ $stats['total'] }}</span>
                    @elseif($value === 'new' && isset($stats['new']) && $stats['new'] > 0)
                        <span class="ml-1 text-xs text-gray-400">{{ $stats['new'] }}</span>
                    @elseif($value === 'read' && isset($stats['read']) && $stats['read'] > 0)
                        <span class="ml-1 text-xs text-gray-400">{{ $stats['read'] }}</span>
                    @elseif($value === 'replied' && isset($stats['replied']) && $stats['replied'] > 0)
                        <span class="ml-1 text-xs text-gray-400">{{ $stats['replied'] }}</span>
                    @endif
                    @if($currentStatus === $value)
                        <span class="absolute bottom-0 left-0 right-0 h-0.5 bg-gray-900 rounded-full"></span>
                    @endif
                </a>
            @endforeach
        </div>

        {{-- Search --}}
        <div class="px-4 py-3" style="border-bottom:1px solid #e1e1e1">
            <form action="{{ route('admin.enquiries.index') }}" method="GET" class="flex items-center gap-3">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                <div class="relative flex-1 max-w-md">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Search enquiries..."
                           class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                </div>
                @if(request('search'))
                    <a href="{{ route('admin.enquiries.index', request('status') ? ['status' => request('status')] : []) }}"
                       class="text-sm text-gray-500 hover:text-gray-700">Clear</a>
                @endif
            </form>
        </div>

        {{-- Bulk Action Bar --}}
        @include('admin.partials.bulk-action-bar', [
            'route' => route('admin.enquiries.bulk-action'),
            'actions' => ['delete' => 'Delete', 'read' => 'Mark as Read', 'closed' => 'Close'],
        ])

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50" style="border-bottom:1px solid #e1e1e1">
                        <th class="w-10 px-4 py-3 text-left">
                            <input type="checkbox" class="rounded border-gray-300 text-gray-600 focus:ring-gray-500"
                                   @change="toggleAll($el.checked)" :checked="selected.length === {{ count($pageIds) }} && selected.length > 0">
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sender</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($enquiries as $enquiry)
                        <tr class="hover:bg-gray-50 cursor-pointer group" style="border-bottom:1px solid #e1e1e1"
                            onclick="window.location='{{ route('admin.enquiries.show', $enquiry) }}'">
                            <td class="px-4 py-3" onclick="event.stopPropagation()">
                                <input type="checkbox" class="rounded border-gray-300 text-gray-600 focus:ring-gray-500"
                                       :checked="selected.includes({{ $enquiry->id }})" @change="toggle({{ $enquiry->id }})">
                            </td>
                            <td class="px-4 py-3">
                                <div>
                                    <p class="font-medium text-gray-900">{{ $enquiry->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $enquiry->email }}</p>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-700">
                                {{ Str::limit($enquiry->subject, 50) }}
                            </td>
                            <td class="px-4 py-3">
                                @switch($enquiry->status)
                                    @case('new')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                            New
                                        </span>
                                        @break
                                    @case('read')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                            Read
                                        </span>
                                        @break
                                    @case('replied')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-50 text-green-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                            Replied
                                        </span>
                                        @break
                                    @case('closed')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                            Closed
                                        </span>
                                        @break
                                @endswitch
                            </td>
                            <td class="px-4 py-3 text-gray-500 text-xs">
                                {{ $enquiry->created_at->format('M d, Y') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center text-gray-500">
                                <p class="text-sm">No enquiries found</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($enquiries->hasPages())
            <div class="px-4 py-3" style="border-top:1px solid #e1e1e1">
                {{ $enquiries->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
