<x-layouts.admin>
    <x-slot name="title">Inventory Locations</x-slot>

    <div class="flex items-center justify-between mb-6">
        <div>
            <div class="flex items-center gap-2 text-sm text-neutral-600 mb-1">
                <a href="{{ route('admin.inventory.index') }}" class="hover:text-primary-600">Inventory</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <span class="text-neutral-900">Locations</span>
            </div>
            <h1 class="text-2xl font-bold text-neutral-900">Inventory Locations</h1>
            <p class="text-neutral-600">Manage warehouse and storage locations</p>
        </div>
        <a href="{{ route('admin.inventory.locations.create') }}" class="btn btn-primary">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Add Location
        </a>
    </div>
    <div class="card overflow-hidden">
        @if($locations->total() > 0)
            <div class="px-5 py-3 border-b border-neutral-200">
                {{ $locations->links('vendor.pagination.info-bar') }}
            </div>
        @endif
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-neutral-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Code</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Address</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-neutral-600 uppercase">Items</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-neutral-600 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200">
                    @forelse($locations as $location)
                        <tr class="hover:bg-neutral-50">
                            <td class="px-4 py-3 font-medium text-neutral-900">{{ $location->name }}</td>
                            <td class="px-4 py-3 text-sm text-neutral-600">{{ $location->code }}</td>
                            <td class="px-4 py-3 text-sm text-neutral-600 max-w-[200px] truncate" title="{{ $location->address ?? '' }}">{{ $location->address ?? '-' }}</td>
                            <td class="px-4 py-3 text-right text-sm font-medium">{{ $location->stocks_count }}</td>
                            <td class="px-4 py-3">
                                <span class="badge {{ $location->is_active ? 'badge-success' : 'badge-error' }}">
                                    {{ $location->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.inventory.locations.edit', $location) }}" class="text-primary-600 hover:text-primary-700 text-sm font-medium">Edit</a>
                                    <form action="{{ route('admin.inventory.locations.destroy', $location) }}" method="POST" onsubmit="return confirm('Delete this location?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-danger-600 hover:text-danger-700 text-sm font-medium">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-neutral-600">
                                No locations found.
                                <a href="{{ route('admin.inventory.locations.create') }}" class="text-primary-600 hover:text-primary-700 font-medium ml-1">Add one now</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($locations->hasPages())
            <div class="px-4 py-3 border-t border-neutral-200">
                {{ $locations->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
