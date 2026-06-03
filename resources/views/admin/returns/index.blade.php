<x-layouts.admin>
    <x-slot name="title">Returns</x-slot>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold text-neutral-900">Returns</h1>
        </div>
    </x-slot>

    @php
        $currentStatus = request('status', '');
        $tabs = [
            '' => 'All',
            'requested' => 'Requested',
            'approved' => 'Approved',
            'received' => 'Received',
            'completed' => 'Completed',
            'rejected' => 'Rejected',
        ];
    @endphp

    <div class="card overflow-hidden">
        {{-- Tabs --}}
        <div class="flex items-center gap-0 px-4 pt-3 border-b border-gray-200">
            @foreach($tabs as $value => $label)
                <a href="{{ route('admin.returns.index', $value ? ['status' => $value] : []) }}"
                   class="px-3 pb-3 text-sm font-medium transition-colors relative {{ $currentStatus === $value ? 'text-neutral-900' : 'text-neutral-500 hover:text-neutral-700' }}">
                    {{ $label }}
                    @if($currentStatus === $value)
                        <span class="absolute bottom-0 left-0 right-0 h-0.5 rounded-full bg-neutral-900"></span>
                    @endif
                </a>
            @endforeach
        </div>

        {{-- Search --}}
        <div class="px-4 py-3 border-b border-gray-200">
            <form action="{{ route('admin.returns.index') }}" method="GET" class="flex items-center gap-2">
                @if($currentStatus)
                    <input type="hidden" name="status" value="{{ $currentStatus }}">
                @endif
                <div class="relative flex-1 max-w-sm">
                    <svg class="w-4 h-4 text-neutral-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Filter returns" class="form-input w-full pl-9 text-sm h-9">
                </div>
                @if(request('search'))
                    <a href="{{ route('admin.returns.index', $currentStatus ? ['status' => $currentStatus] : []) }}" class="text-xs text-neutral-500 hover:text-neutral-700">Clear</a>
                @endif
            </form>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 w-8"><input type="checkbox" class="form-checkbox rounded"></th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500">Return</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500">Customer</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500">Order</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-neutral-500">Refund</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($returns as $return)
                        <tr class="hover:bg-neutral-50 cursor-pointer border-b border-gray-100"
                            x-on:click="if(!$event.target.closest('input[type=checkbox]')) window.location.href='{{ route('admin.returns.show', $return) }}'">
                            <td class="px-4 py-3" x-on:click.stop><input type="checkbox" class="form-checkbox rounded" value="{{ $return->id }}"></td>
                            <td class="px-4 py-3"><span class="text-sm font-medium text-blue-700">{{ $return->return_number }}</span></td>
                            <td class="px-4 py-3"><span class="text-sm text-neutral-600">{{ $return->created_at->format('M d, Y') }}</span></td>
                            <td class="px-4 py-3"><span class="text-sm text-neutral-900">{{ $return->order->user->full_name ?? 'N/A' }}</span></td>
                            <td class="px-4 py-3"><span class="text-sm text-blue-700">{{ $return->order->order_number ?? '-' }}</span></td>
                            <td class="px-4 py-3">
                                <span class="text-xs font-medium px-2 py-0.5 rounded bg-gray-200 text-gray-700">{{ ucfirst($return->type ?? 'Return') }}</span>
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $statusCls = match($return->status) {
                                        'requested' => 'bg-amber-100 text-amber-800',
                                        'approved', 'pickup_scheduled', 'picked_up' => 'bg-blue-100 text-blue-800',
                                        'received', 'processed' => 'bg-indigo-100 text-indigo-800',
                                        'completed' => 'bg-green-100 text-green-800',
                                        'rejected' => 'bg-red-100 text-red-800',
                                        default => 'bg-gray-200 text-gray-700',
                                    };
                                    $dotCls = match($return->status) {
                                        'requested' => 'bg-amber-500',
                                        'approved', 'pickup_scheduled', 'picked_up' => 'bg-blue-500',
                                        'received', 'processed' => 'bg-indigo-500',
                                        'completed' => 'bg-green-500',
                                        'rejected' => 'bg-red-500',
                                        default => 'bg-gray-500',
                                    };
                                @endphp
                                <span class="inline-flex items-center gap-1.5 text-xs font-medium px-2 py-0.5 rounded {{ $statusCls }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $dotCls }}"></span>
                                    {{ ucfirst(str_replace('_', ' ', $return->status)) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <span class="text-sm text-neutral-900">{{ $return->refund_amount ? '₹' . number_format($return->refund_amount, 2) : '-' }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-16 text-center">
                                <p class="text-sm font-medium text-neutral-900 mb-1">No returns found</p>
                                <p class="text-sm text-neutral-500">Return requests will appear here.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($returns->hasPages())
            <div class="px-4 py-3 flex items-center justify-between text-sm border-t border-gray-200">
                <span class="text-neutral-500">{{ $returns->firstItem() }}-{{ $returns->lastItem() }} of {{ $returns->total() }}</span>
                <div class="flex items-center gap-1">
                    @if(!$returns->onFirstPage())
                        <a href="{{ $returns->previousPageUrl() }}" class="px-2 py-1 text-neutral-600 hover:text-neutral-900">&laquo;</a>
                    @endif
                    @if($returns->hasMorePages())
                        <a href="{{ $returns->nextPageUrl() }}" class="px-2 py-1 text-neutral-600 hover:text-neutral-900">&raquo;</a>
                    @endif
                </div>
            </div>
        @endif
    </div>
</x-layouts.admin>
