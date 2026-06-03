<x-layouts.admin>
    <x-slot name="title">Credit Notes</x-slot>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold text-neutral-900">Credit Notes</h1>
        </div>
    </x-slot>

    @php
        $currentStatus = request('status', '');
        $tabs = [
            '' => 'All',
            'active' => 'Active',
            'partially_used' => 'Partial',
            'fully_used' => 'Used',
            'expired' => 'Expired',
        ];
    @endphp

    <div class="card overflow-hidden">
        {{-- Tabs --}}
        <div class="flex items-center gap-0 px-4 pt-3" style="border-bottom:1px solid #e1e1e1">
            @foreach($tabs as $value => $label)
                <a href="{{ route('admin.credit-notes.index', $value ? ['status' => $value] : []) }}"
                   class="px-3 pb-3 text-sm font-medium transition-colors relative {{ $currentStatus === $value ? 'text-neutral-900' : 'text-neutral-500 hover:text-neutral-700' }}">
                    {{ $label }}
                    @if($currentStatus === $value)
                        <span class="absolute bottom-0 left-0 right-0 h-0.5 rounded-full" style="background:#1a1a1a"></span>
                    @endif
                </a>
            @endforeach
        </div>

        {{-- Search --}}
        <div class="px-4 py-3" style="border-bottom:1px solid #e1e1e1">
            <form action="{{ route('admin.credit-notes.index') }}" method="GET" class="flex items-center gap-2">
                @if($currentStatus)
                    <input type="hidden" name="status" value="{{ $currentStatus }}">
                @endif
                <div class="relative flex-1 max-w-sm">
                    <svg class="w-4 h-4 text-neutral-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Filter credit notes" class="form-input w-full pl-9 text-sm" style="height:36px">
                </div>
                @if(request('search'))
                    <a href="{{ route('admin.credit-notes.index', $currentStatus ? ['status' => $currentStatus] : []) }}" class="text-xs text-neutral-500 hover:text-neutral-700">Clear</a>
                @endif
            </form>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr style="border-bottom:1px solid #e1e1e1">
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 w-8"><input type="checkbox" class="form-checkbox rounded"></th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500">Credit Note</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500">Customer</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500">Order</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-neutral-500">Amount</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-neutral-500">Remaining</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500">Expires</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($creditNotes as $note)
                        <tr class="hover:bg-neutral-50 cursor-pointer" onclick="window.location='{{ route('admin.credit-notes.show', $note) }}'" style="border-bottom:1px solid #f0f0f0">
                            <td class="px-4 py-3" onclick="event.stopPropagation()"><input type="checkbox" class="form-checkbox rounded" value="{{ $note->id }}"></td>
                            <td class="px-4 py-3"><span class="text-sm font-medium" style="color:#005bd3">{{ $note->credit_note_number }}</span></td>
                            <td class="px-4 py-3"><span class="text-sm text-neutral-600">{{ $note->created_at->format('M d, Y') }}</span></td>
                            <td class="px-4 py-3"><span class="text-sm text-neutral-900">{{ $note->user->full_name ?? 'N/A' }}</span></td>
                            <td class="px-4 py-3">
                                @if($note->order)
                                    <span class="text-sm" style="color:#005bd3">{{ $note->order->order_number }}</span>
                                @else
                                    <span class="text-sm text-neutral-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right"><span class="text-sm text-neutral-900">@price($note->amount)</span></td>
                            <td class="px-4 py-3 text-right"><span class="text-sm font-medium" style="color:{{ $note->remaining_amount > 0 ? '#1a7431' : '#6c757d' }}">@price($note->remaining_amount)</span></td>
                            <td class="px-4 py-3">
                                @php
                                    $sc = match($note->status) {
                                        'active' => ['bg'=>'#e3f1df','text'=>'#1a7431','dot'=>'#22c55e'],
                                        'partially_used' => ['bg'=>'#dbeafe','text'=>'#1e40af','dot'=>'#3b82f6'],
                                        'fully_used' => ['bg'=>'#e9ecef','text'=>'#495057','dot'=>'#6c757d'],
                                        'expired' => ['bg'=>'#fde8e8','text'=>'#c53030','dot'=>'#e53e3e'],
                                        default => ['bg'=>'#e9ecef','text'=>'#495057','dot'=>'#6c757d'],
                                    };
                                @endphp
                                <span class="inline-flex items-center gap-1.5 text-xs font-medium px-2 py-0.5 rounded" style="background:{{ $sc['bg'] }};color:{{ $sc['text'] }}">
                                    <span class="w-1.5 h-1.5 rounded-full" style="background:{{ $sc['dot'] }}"></span>
                                    {{ ucfirst(str_replace('_', ' ', $note->status)) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-sm {{ $note->expires_at?->isPast() ? 'text-red-500' : 'text-neutral-600' }}">
                                    {{ $note->expires_at ? $note->expires_at->format('M d, Y') : 'Never' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-16 text-center">
                                <p class="text-sm font-medium text-neutral-900 mb-1">No credit notes found</p>
                                <p class="text-sm text-neutral-500">Credit notes will appear here when refunds are processed.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($creditNotes->hasPages())
            <div class="px-4 py-3 flex items-center justify-between text-sm" style="border-top:1px solid #e1e1e1">
                <span class="text-neutral-500">{{ $creditNotes->firstItem() }}-{{ $creditNotes->lastItem() }} of {{ $creditNotes->total() }}</span>
                <div class="flex items-center gap-1">
                    @if(!$creditNotes->onFirstPage())
                        <a href="{{ $creditNotes->previousPageUrl() }}" class="px-2 py-1 text-neutral-600 hover:text-neutral-900">&laquo;</a>
                    @endif
                    @if($creditNotes->hasMorePages())
                        <a href="{{ $creditNotes->nextPageUrl() }}" class="px-2 py-1 text-neutral-600 hover:text-neutral-900">&raquo;</a>
                    @endif
                </div>
            </div>
        @endif
    </div>
</x-layouts.admin>
