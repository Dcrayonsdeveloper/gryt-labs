<x-layouts.admin>
    <x-slot name="title">Support Tickets</x-slot>

    {{-- Header --}}
    <div class="flex items-center justify-between mb-5">
        <h1 class="text-xl font-semibold text-gray-900">Support Tickets</h1>
    </div>

    {{-- Main card --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">

        {{-- Tabs --}}
        <div class="flex items-center gap-0 px-4" style="border-bottom:1px solid #e1e1e1">
            @php
                $currentStatus = request('status', '');
                $tabs = [
                    '' => 'All',
                    'open' => 'Open',
                    'in_progress' => 'In Progress',
                    'closed' => 'Closed',
                ];
            @endphp
            @foreach($tabs as $value => $label)
                <a href="{{ route('admin.support-tickets.index', array_merge(request()->except('status', 'page'), $value ? ['status' => $value] : [])) }}"
                   class="relative px-4 py-3 text-sm font-medium transition-colors
                          {{ $currentStatus === $value ? 'text-gray-900' : 'text-gray-500 hover:text-gray-700' }}">
                    {{ $label }}
                    @if($value === '' && isset($stats['total']))
                        <span class="ml-1 text-xs text-gray-400">{{ $stats['total'] }}</span>
                    @elseif($value === 'open' && isset($stats['open']) && $stats['open'] > 0)
                        <span class="ml-1 text-xs text-gray-400">{{ $stats['open'] }}</span>
                    @elseif($value === 'in_progress' && isset($stats['answered']) && $stats['answered'] > 0)
                        <span class="ml-1 text-xs text-gray-400">{{ $stats['answered'] }}</span>
                    @elseif($value === 'closed' && isset($stats['closed']) && $stats['closed'] > 0)
                        <span class="ml-1 text-xs text-gray-400">{{ $stats['closed'] }}</span>
                    @endif
                    @if($currentStatus === $value)
                        <span class="absolute bottom-0 left-0 right-0 h-0.5 bg-gray-900 rounded-full"></span>
                    @endif
                </a>
            @endforeach
        </div>

        {{-- Search --}}
        <div class="px-4 py-3" style="border-bottom:1px solid #e1e1e1">
            <form action="{{ route('admin.support-tickets.index') }}" method="GET" class="flex items-center gap-3">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                <div class="relative flex-1 max-w-md">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Search tickets..."
                           class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                </div>
                @if(request('search'))
                    <a href="{{ route('admin.support-tickets.index', request('status') ? ['status' => request('status')] : []) }}"
                       class="text-sm text-gray-500 hover:text-gray-700">Clear</a>
                @endif
            </form>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50" style="border-bottom:1px solid #e1e1e1">
                        <th class="w-10 px-4 py-3 text-left">
                            <input type="checkbox" class="rounded border-gray-300 text-gray-600 focus:ring-gray-500">
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ticket #</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tickets as $ticket)
                        <tr class="hover:bg-gray-50 cursor-pointer group" style="border-bottom:1px solid #e1e1e1"
                            onclick="window.location='{{ route('admin.support-tickets.show', $ticket) }}'">
                            <td class="px-4 py-3" onclick="event.stopPropagation()">
                                <input type="checkbox" class="rounded border-gray-300 text-gray-600 focus:ring-gray-500">
                            </td>
                            <td class="px-4 py-3 text-gray-700 font-medium">
                                #{{ $ticket->id }}
                            </td>
                            <td class="px-4 py-3">
                                <div>
                                    <p class="font-medium text-gray-900">{{ $ticket->user->full_name }}</p>
                                    <p class="text-xs text-gray-500">{{ $ticket->user->email }}</p>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-700">
                                {{ Str::limit($ticket->subject, 40) }}
                            </td>
                            <td class="px-4 py-3">
                                @switch($ticket->priority)
                                    @case('high')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-50 text-red-700">High</span>
                                        @break
                                    @case('normal')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700">Normal</span>
                                        @break
                                    @case('low')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Low</span>
                                        @break
                                @endswitch
                            </td>
                            <td class="px-4 py-3">
                                @switch($ticket->status)
                                    @case('open')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                            Open
                                        </span>
                                        @break
                                    @case('answered')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                            In Progress
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
                                {{ $ticket->created_at->format('M d, Y') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-gray-500">
                                <p class="text-sm">No support tickets found</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($tickets->hasPages())
            <div class="px-4 py-3" style="border-top:1px solid #e1e1e1">
                {{ $tickets->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
