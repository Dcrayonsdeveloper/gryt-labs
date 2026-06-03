<x-layouts.admin>
    <x-slot name="title">Stores</x-slot>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900">Stores</h1>
            <p class="text-neutral-600">Manage physical store locations</p>
        </div>
        <a href="{{ route('admin.stores.create') }}" class="btn btn-primary">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Add Store
        </a>
    </div>
    <div class="card overflow-hidden">
        @if($stores->total() > 0)
            <div class="px-5 py-3 border-b border-neutral-200">
                {{ $stores->links('vendor.pagination.info-bar') }}
            </div>
        @endif
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-neutral-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Store</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Code</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Contact</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-neutral-600 uppercase">Registers</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-neutral-600 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200">
                    @forelse($stores as $store)
                        <tr class="hover:bg-neutral-50">
                            <td class="px-4 py-3">
                                <p class="font-medium text-neutral-900">{{ $store->name }}</p>
                                @if($store->address)
                                    <p class="text-sm text-neutral-600 truncate max-w-[200px]" title="{{ $store->address }}">{{ $store->address }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-mono text-sm text-neutral-600">{{ $store->code }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-neutral-600">
                                @if($store->phone)
                                    <p>{{ $store->phone }}</p>
                                @endif
                                @if($store->email)
                                    <p class="text-neutral-600">{{ $store->email }}</p>
                                @endif
                                @if(!$store->phone && !$store->email)
                                    <span class="text-neutral-600">--</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-sm font-medium">
                                {{ $store->registers_count }}
                            </td>
                            <td class="px-4 py-3">
                                @if($store->is_active)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-warning">Inactive</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.stores.edit', $store) }}" class="text-primary-600 hover:text-primary-700 text-sm font-medium">Edit</a>
                                    <form action="{{ route('admin.stores.destroy', $store) }}" method="POST" onsubmit="return confirm('Delete this store?')">
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
                                No stores found.
                                <a href="{{ route('admin.stores.create') }}" class="text-primary-600 hover:text-primary-700 font-medium ml-1">Create one now</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($stores->hasPages())
            <div class="px-4 py-3 border-t border-neutral-200">
                {{ $stores->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
