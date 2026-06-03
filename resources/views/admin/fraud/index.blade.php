<x-layouts.admin>
    <x-slot name="title">Fraud Review</x-slot>

    {{-- Header --}}
    <div class="flex items-center justify-between mb-5">
        <h1 class="text-xl font-semibold text-gray-900">Fraud Review</h1>
    </div>

    {{-- Main card --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">

        {{-- Tabs --}}
        <div class="flex items-center gap-0 px-4" style="border-bottom:1px solid #e1e1e1">
            @php
                $currentReviewed = request('reviewed', '');
                $tabs = [
                    '' => 'All',
                    'no' => 'Pending',
                    'yes' => 'Reviewed',
                ];
            @endphp
            @foreach($tabs as $value => $label)
                <a href="{{ route('admin.fraud.index', array_merge(request()->except('reviewed', 'page'), $value ? ['reviewed' => $value] : [])) }}"
                   class="relative px-4 py-3 text-sm font-medium transition-colors
                          {{ $currentReviewed === $value ? 'text-gray-900' : 'text-gray-500 hover:text-gray-700' }}">
                    {{ $label }}
                    @if($value === '' && isset($stats['total']))
                        <span class="ml-1 text-xs text-gray-400">{{ $stats['total'] ?? 0 }}</span>
                    @elseif($value === 'no' && isset($stats['unreviewed']) && ($stats['unreviewed'] ?? 0) > 0)
                        <span class="ml-1 text-xs text-gray-400">{{ $stats['unreviewed'] ?? 0 }}</span>
                    @endif
                    @if($currentReviewed === $value)
                        <span class="absolute bottom-0 left-0 right-0 h-0.5 bg-gray-900 rounded-full"></span>
                    @endif
                </a>
            @endforeach
        </div>

        {{-- Search --}}
        <div class="px-4 py-3" style="border-bottom:1px solid #e1e1e1">
            <form action="{{ route('admin.fraud.index') }}" method="GET" class="flex items-center gap-3">
                @if(request('reviewed'))
                    <input type="hidden" name="reviewed" value="{{ request('reviewed') }}">
                @endif
                <div class="relative flex-1 max-w-md">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Search by order or customer..."
                           class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                </div>
                @if(request('search'))
                    <a href="{{ route('admin.fraud.index', request('reviewed') ? ['reviewed' => request('reviewed')] : []) }}"
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
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Risk Level</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($fraudLogs as $log)
                        <tr class="hover:bg-gray-50 cursor-pointer group" style="border-bottom:1px solid #e1e1e1"
                            onclick="window.location='{{ route('admin.fraud.show', $log) }}'">
                            <td class="px-4 py-3" onclick="event.stopPropagation()">
                                <input type="checkbox" class="rounded border-gray-300 text-gray-600 focus:ring-gray-500">
                            </td>
                            <td class="px-4 py-3">
                                @if($log->order)
                                    <span class="font-medium text-gray-900">{{ $log->order->order_number }}</span>
                                @else
                                    <span class="text-gray-400">N/A</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($log->user)
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $log->user->full_name ?? $log->user->first_name . ' ' . $log->user->last_name }}</p>
                                        <p class="text-xs text-gray-500">{{ $log->user->email }}</p>
                                    </div>
                                @else
                                    <span class="text-gray-400">Guest</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $score = $log->risk_score ?? 0;
                                @endphp
                                @if($score >= 75)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-50 text-red-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                        High ({{ $score }})
                                    </span>
                                @elseif($score >= 50)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        Medium ({{ $score }})
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-50 text-green-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                        Low ({{ $score }})
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-700">
                                {{ ucfirst(str_replace('_', ' ', $log->type ?? '-')) }}
                            </td>
                            <td class="px-4 py-3">
                                @if($log->reviewed_at)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-50 text-green-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                        Reviewed
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        Pending
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-500 text-xs">
                                {{ $log->created_at->format('M d, Y') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-gray-500">
                                <p class="text-sm">No fraud logs found</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($fraudLogs->hasPages())
            <div class="px-4 py-3" style="border-top:1px solid #e1e1e1">
                {{ $fraudLogs->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
