<x-layouts.admin>
    <x-slot name="title">Shipping Zones</x-slot>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900">Shipping Zones</h1>
            <p class="text-neutral-600">Manage shipping zones and regions</p>
        </div>
        <a href="{{ route('admin.settings.shipping-zones.create') }}" class="btn btn-primary">Add Shipping Zone</a>
    </div>
    <div class="card overflow-hidden">
        @if($zones->total() > 0)
            <div class="px-5 py-3 border-b border-neutral-200">
                {{ $zones->links('vendor.pagination.info-bar') }}
            </div>
        @endif
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-neutral-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Regions</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-neutral-600 uppercase">Rates</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-neutral-600 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200">
                    @forelse($zones as $zone)
                        <tr class="hover:bg-neutral-50">
                            <td class="px-4 py-3 font-medium text-neutral-900">{{ $zone->name }}</td>
                            <td class="px-4 py-3 text-sm text-neutral-600">
                                @if($zone->regions)
                                    {{ implode(', ', array_slice($zone->regions, 0, 3)) }}{{ count($zone->regions) > 3 ? ' +' . (count($zone->regions) - 3) . ' more' : '' }}
                                @else
                                    <span class="text-neutral-600">All regions</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-right">{{ $zone->rates_count }}</td>
                            <td class="px-4 py-3">
                                <span class="badge {{ $zone->is_active ? 'badge-success' : 'badge-neutral' }}">
                                    {{ $zone->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.settings.shipping-zones.edit', $zone) }}" class="text-primary-600 hover:text-primary-700 text-sm font-medium">Edit</a>
                                    <form action="{{ route('admin.settings.shipping-zones.destroy', $zone) }}" method="POST" onsubmit="return confirm('Delete this zone?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-error-600 hover:text-error-700 text-sm font-medium">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center text-neutral-600">
                                No shipping zones configured.
                                <a href="{{ route('admin.settings.shipping-zones.create') }}" class="text-primary-600 font-medium ml-1">Add one now</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($zones->hasPages())
            <div class="px-4 py-3 border-t border-neutral-200">{{ $zones->links() }}</div>
        @endif
    </div>
</x-layouts.admin>
