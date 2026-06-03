<x-layouts.admin>
    <x-slot name="title">Tax Rates</x-slot>

    <div class="flex items-center justify-between mb-6">
        <div>
            <div class="flex items-center gap-2 text-sm text-neutral-600 mb-1">
                <a href="{{ route('admin.settings.tax') }}" class="hover:text-primary-600">Tax Settings</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <span class="text-neutral-900">Tax Rates</span>
            </div>
            <h1 class="text-2xl font-bold text-neutral-900">Tax Rates</h1>
            <p class="text-neutral-600">Configure tax rates by region</p>
        </div>
        <a href="{{ route('admin.settings.tax-rates.create') }}" class="btn btn-primary">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Add Tax Rate
        </a>
    </div>
    <div class="card overflow-hidden">
        @if($taxRates->total() > 0)
            <div class="px-5 py-3 border-b border-neutral-200">
                {{ $taxRates->links('vendor.pagination.info-bar') }}
            </div>
        @endif
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-neutral-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">State</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-neutral-600 uppercase">CGST %</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-neutral-600 uppercase">SGST %</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-neutral-600 uppercase">IGST %</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-neutral-600 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200">
                    @forelse($taxRates as $taxRate)
                        <tr class="hover:bg-neutral-50">
                            <td class="px-4 py-3 font-medium text-neutral-900">{{ $taxRate->name }}</td>
                            <td class="px-4 py-3 text-sm text-neutral-600">{{ $taxRate->state ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-right">{{ number_format($taxRate->cgst_rate, 2) }}%</td>
                            <td class="px-4 py-3 text-sm text-right">{{ number_format($taxRate->sgst_rate, 2) }}%</td>
                            <td class="px-4 py-3 text-sm text-right">{{ number_format($taxRate->igst_rate, 2) }}%</td>
                            <td class="px-4 py-3">
                                <span class="badge {{ $taxRate->is_active ? 'badge-success' : 'badge-error' }}">
                                    {{ $taxRate->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.settings.tax-rates.edit', $taxRate) }}" class="text-primary-600 hover:text-primary-700 text-sm font-medium">
                                        Edit
                                    </a>
                                    <form action="{{ route('admin.settings.tax-rates.destroy', $taxRate) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this tax rate?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-danger-600 hover:text-danger-700 text-sm font-medium">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-neutral-600">
                                No tax rates configured yet.
                                <a href="{{ route('admin.settings.tax-rates.create') }}" class="text-primary-600 hover:text-primary-700 font-medium ml-1">Add one now</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($taxRates->hasPages())
            <div class="px-4 py-3 border-t border-neutral-200">
                {{ $taxRates->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
