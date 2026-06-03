<x-layouts.admin>
    <x-slot name="title">Inventory Movements</x-slot>

    <div class="flex items-center justify-between mb-6">
        <div>
            <div class="flex items-center gap-2 text-sm text-neutral-600 mb-1">
                <a href="{{ route('admin.inventory.index') }}" class="hover:text-primary-600">Inventory</a>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <span class="text-neutral-900">Movements</span>
            </div>
            <h1 class="text-2xl font-bold text-neutral-900">Stock Movements</h1>
            <p class="text-neutral-600">Full history of stock changes</p>
        </div>
        <a href="{{ route('admin.inventory.index') }}" class="btn btn-secondary">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to Inventory
        </a>
    </div>

    <div class="card overflow-hidden">
        @if($movements->total() > 0)
            <div class="px-5 py-3 border-b border-neutral-200 flex items-center justify-between">
                <p class="text-sm text-neutral-600">
                    Showing <span class="font-medium text-neutral-900">{{ $movements->firstItem() }}</span>–<span class="font-medium text-neutral-900">{{ $movements->lastItem() }}</span> of <span class="font-medium text-neutral-900">{{ $movements->total() }}</span> records
                </p>
            </div>
        @endif
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-neutral-50 border-b border-neutral-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-600 uppercase tracking-wide">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-600 uppercase tracking-wide">Product</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-600 uppercase tracking-wide">Type</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-neutral-600 uppercase tracking-wide">Qty</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-neutral-600 uppercase tracking-wide">Before → After</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-600 uppercase tracking-wide">Reason</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-600 uppercase tracking-wide">By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @forelse($movements as $movement)
                        <tr class="hover:bg-neutral-50 transition-colors">
                            <td class="px-4 py-3">
                                <p class="text-sm text-neutral-900">{{ $movement->created_at->format('M d, Y') }}</p>
                                <p class="text-xs text-neutral-600">{{ $movement->created_at->format('H:i') }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-sm font-medium text-neutral-900">{{ $movement->product->name ?? 'N/A' }}</p>
                                @if($movement->product?->sku)
                                    <span class="text-xs font-mono bg-neutral-100 text-neutral-600 px-1.5 py-0.5 rounded">{{ $movement->product->sku }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $typeBadges = [
                                        'in'         => 'badge-success',
                                        'out'        => 'badge-error',
                                        'adjustment' => 'badge-info',
                                    ];
                                    $typeLabels = ['in' => 'Stock In', 'out' => 'Stock Out', 'adjustment' => 'Adjustment'];
                                @endphp
                                <span class="badge {{ $typeBadges[$movement->type] ?? 'badge-info' }}">
                                    {{ $typeLabels[$movement->type] ?? ucfirst($movement->type) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @php
                                    $qtyColor = $movement->type === 'in' ? 'text-success-600' : ($movement->type === 'out' ? 'text-error-600' : 'text-info-600');
                                    $qtySign = $movement->type === 'in' ? '+' : ($movement->type === 'out' ? '-' : '±');
                                @endphp
                                <span class="text-sm font-bold {{ $qtyColor }}">{{ $qtySign }}{{ $movement->quantity }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="text-sm text-neutral-600">{{ $movement->quantity_before }}</span>
                                <span class="text-neutral-600 mx-1">→</span>
                                <span class="text-sm font-semibold text-neutral-900">{{ $movement->quantity_after }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-neutral-600">{{ $movement->reason ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-neutral-600">{{ $movement->createdBy?->full_name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-16 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <div class="w-12 h-12 bg-neutral-100 rounded-full flex items-center justify-center">
                                        <svg class="w-6 h-6 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                        </svg>
                                    </div>
                                    <p class="text-sm font-medium text-neutral-900">No movements recorded yet</p>
                                    <p class="text-xs text-neutral-600">Stock adjustments will appear here</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($movements->hasPages())
            <div class="px-4 py-3 border-t border-neutral-200">
                {{ $movements->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
