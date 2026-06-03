<x-layouts.admin>
    <x-slot name="title">Draft Orders</x-slot>

    {{-- Header --}}
    <div class="flex items-center justify-between mb-5">
        <h1 class="text-xl font-semibold text-gray-900">Draft Orders</h1>
        <a href="{{ route('admin.draft-orders.create') }}"
           class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
            Create draft order
        </a>
    </div>

    @if(session('success'))
        <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    @if(session('warning'))
        <div class="mb-4 px-4 py-3 bg-yellow-50 border border-yellow-200 text-yellow-700 text-sm rounded-lg">
            {{ session('warning') }}
        </div>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-5">
        <div class="bg-white rounded-lg border border-gray-200 px-4 py-3">
            <p class="text-xs text-gray-500">Total</p>
            <p class="text-lg font-semibold text-gray-900">{{ $stats['total'] }}</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 px-4 py-3">
            <p class="text-xs text-gray-500">Draft</p>
            <p class="text-lg font-semibold text-gray-900">{{ $stats['draft'] }}</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 px-4 py-3">
            <p class="text-xs text-gray-500">Sent</p>
            <p class="text-lg font-semibold text-gray-900">{{ $stats['sent'] }}</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 px-4 py-3">
            <p class="text-xs text-gray-500">Completed</p>
            <p class="text-lg font-semibold text-gray-900">{{ $stats['completed'] }}</p>
        </div>
    </div>

    {{-- Main Card --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">

        {{-- Tabs --}}
        <div class="flex items-center gap-0 px-4" style="border-bottom:1px solid #e1e1e1">
            @php
                $tabs = [
                    'all' => 'All',
                    'draft' => 'Draft',
                    'sent' => 'Sent',
                    'completed' => 'Completed',
                    'cancelled' => 'Cancelled',
                ];
            @endphp
            @foreach($tabs as $key => $label)
                <a href="{{ route('admin.draft-orders.index', array_merge(request()->except('status', 'page'), $key === 'all' ? [] : ['status' => $key])) }}"
                   class="relative px-4 py-3 text-sm font-medium transition-colors
                          {{ request('status', 'all') === $key ? 'text-gray-900' : 'text-gray-500 hover:text-gray-700' }}">
                    {{ $label }}
                    @if(request('status', 'all') === $key)
                        <span class="absolute bottom-0 left-4 right-4 h-0.5 bg-gray-900 rounded-full"></span>
                    @endif
                </a>
            @endforeach
        </div>

        {{-- Search --}}
        <div class="px-4 py-3" style="border-bottom:1px solid #e1e1e1">
            <form action="{{ route('admin.draft-orders.index') }}" method="GET" class="flex items-center gap-2">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                <div class="relative flex-1 max-w-sm">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}"
                           class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:ring-1 focus:ring-gray-300 focus:border-gray-300"
                           placeholder="Search by customer name, email, phone...">
                </div>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50">
                    Search
                </button>
            </form>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-gray-500 uppercase tracking-wider" style="border-bottom:1px solid #e1e1e1">
                        <th class="px-4 py-3 font-medium">Draft</th>
                        <th class="px-4 py-3 font-medium">Customer</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium text-right">Total</th>
                        <th class="px-4 py-3 font-medium">Created</th>
                        <th class="px-4 py-3 font-medium text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($draftOrders as $draft)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.draft-orders.show', $draft) }}" class="text-primary-600 hover:text-primary-700 font-medium">
                                    #D{{ $draft->id }}
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                <div>
                                    <p class="font-medium text-gray-900">{{ $draft->customer_name ?: 'No customer' }}</p>
                                    @if($draft->customer_email)
                                        <p class="text-xs text-gray-500">{{ $draft->customer_email }}</p>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $statusColors = [
                                        'draft' => 'bg-gray-100 text-gray-700',
                                        'sent' => 'bg-blue-100 text-blue-700',
                                        'completed' => 'bg-green-100 text-green-700',
                                        'cancelled' => 'bg-red-100 text-red-700',
                                    ];
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $statusColors[$draft->status] ?? 'bg-gray-100 text-gray-700' }}">
                                    {{ ucfirst($draft->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right font-medium">@price($draft->total)</td>
                            <td class="px-4 py-3 text-gray-500">{{ $draft->created_at->format('M d, Y') }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.draft-orders.show', $draft) }}" class="text-gray-400 hover:text-gray-600" title="View">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </a>
                                    @if(!$draft->isCompleted() && !$draft->isCancelled())
                                        <form action="{{ route('admin.draft-orders.destroy', $draft) }}" method="POST" onsubmit="return confirm('Delete this draft order?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-gray-400 hover:text-red-500" title="Delete">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-gray-400">
                                <svg class="w-10 h-10 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <p class="text-sm">No draft orders yet.</p>
                                <a href="{{ route('admin.draft-orders.create') }}" class="text-sm text-primary-600 hover:text-primary-700 mt-1 inline-block">Create your first draft order</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($draftOrders->hasPages())
            <div class="px-4 py-3" style="border-top:1px solid #e1e1e1">
                {{ $draftOrders->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
