<x-layouts.admin>
    <x-slot name="title">Gift Cards</x-slot>

    {{-- Header --}}
    <div class="flex items-center justify-between mb-5">
        <h1 class="text-xl font-semibold text-gray-900">Gift Cards</h1>
        <a href="{{ route('admin.gift-cards.create') }}"
           class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
            Create gift card
        </a>
    </div>

    @if(session('success'))
        <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wider">Total</p>
            <p class="text-2xl font-semibold text-gray-900 mt-1">{{ $stats['total'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wider">Active</p>
            <p class="text-2xl font-semibold text-green-600 mt-1">{{ $stats['active'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wider">Active Value</p>
            <p class="text-2xl font-semibold text-gray-900 mt-1">{{ number_format($stats['total_value'], 0) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wider">Depleted</p>
            <p class="text-2xl font-semibold text-gray-400 mt-1">{{ $stats['depleted'] }}</p>
        </div>
    </div>

    {{-- Main Card --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">

        {{-- Tabs --}}
        <div class="flex items-center gap-0 px-4" style="border-bottom:1px solid #e1e1e1">
            @php
                $tabs = [
                    '' => 'All',
                    'active' => 'Active',
                    'depleted' => 'Depleted',
                    'disabled' => 'Disabled',
                ];
            @endphp
            @foreach($tabs as $key => $label)
                <a href="{{ route('admin.gift-cards.index', array_merge(request()->except('status', 'page'), $key ? ['status' => $key] : [])) }}"
                   class="relative px-4 py-3 text-sm font-medium transition-colors
                          {{ request('status', '') === $key ? 'text-gray-900' : 'text-gray-500 hover:text-gray-700' }}">
                    {{ $label }}
                    @if(request('status', '') === $key)
                        <span class="absolute bottom-0 left-4 right-4 h-0.5 bg-gray-900 rounded-full"></span>
                    @endif
                </a>
            @endforeach
        </div>

        {{-- Search --}}
        <div class="px-4 py-3" style="border-bottom:1px solid #e1e1e1">
            <form action="{{ route('admin.gift-cards.index') }}" method="GET" class="flex items-center gap-2">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                <div class="relative flex-1 max-w-sm">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Search by code, recipient..."
                           class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                </div>
            </form>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider" style="border-bottom:1px solid #e1e1e1">
                        <th class="px-4 py-3">Code</th>
                        <th class="px-4 py-3">Recipient</th>
                        <th class="px-4 py-3">Balance</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Expires</th>
                        <th class="px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($giftCards as $card)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.gift-cards.show', $card) }}" class="font-mono text-sm font-medium text-gray-900 hover:text-blue-600">
                                    {{ $card->code }}
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-gray-900">{{ $card->recipient_name }}</div>
                                <div class="text-xs text-gray-500">{{ $card->recipient_email }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-medium text-gray-900">{{ number_format($card->current_balance, 2) }}</span>
                                <span class="text-xs text-gray-400">/ {{ number_format($card->initial_balance, 2) }}</span>
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $statusColors = [
                                        'active' => 'bg-green-100 text-green-700',
                                        'depleted' => 'bg-gray-100 text-gray-600',
                                        'disabled' => 'bg-red-100 text-red-700',
                                    ];
                                @endphp
                                <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full {{ $statusColors[$card->status] ?? 'bg-gray-100 text-gray-600' }}">
                                    {{ ucfirst($card->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-500">
                                {{ $card->expires_at ? $card->expires_at->format('M d, Y') : 'Never' }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.gift-cards.show', $card) }}" class="text-gray-400 hover:text-gray-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>
                                    <form action="{{ route('admin.gift-cards.destroy', $card) }}" method="POST"
                                          onsubmit="return confirm('Delete this gift card?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-gray-400 hover:text-red-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">No gift cards found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($giftCards->hasPages())
            <div class="px-4 py-3" style="border-top:1px solid #e1e1e1">
                {{ $giftCards->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
