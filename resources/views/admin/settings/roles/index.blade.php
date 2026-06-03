<x-layouts.admin>
    <x-slot name="title">Roles</x-slot>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900">Roles & Permissions</h1>
            <p class="text-neutral-600">Manage user roles</p>
        </div>
        <a href="{{ route('admin.settings.roles.create') }}" class="btn btn-primary">Add Role</a>
    </div>
    <div class="card overflow-hidden">
        @if($roles->total() > 0)
            <div class="px-5 py-3 border-b border-neutral-200">
                {{ $roles->links('vendor.pagination.info-bar') }}
            </div>
        @endif
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-neutral-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Role</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-neutral-600 uppercase">Permissions</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-neutral-600 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200">
                    @forelse($roles as $role)
                        <tr class="hover:bg-neutral-50">
                            <td class="px-4 py-3 font-medium text-neutral-900">{{ $role->name }}</td>
                            <td class="px-4 py-3 text-sm text-right text-neutral-600">{{ $role->permissions_count }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.settings.roles.edit', $role) }}" class="text-primary-600 hover:text-primary-700 text-sm font-medium">Edit</a>
                                    @if($role->name !== 'super-admin')
                                        <form action="{{ route('admin.settings.roles.destroy', $role) }}" method="POST" onsubmit="return confirm('Delete this role?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-error-600 hover:text-error-700 text-sm font-medium">Delete</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-12 text-center text-neutral-600">
                                No roles configured.
                                <a href="{{ route('admin.settings.roles.create') }}" class="text-primary-600 font-medium ml-1">Add one now</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($roles->hasPages())
            <div class="px-4 py-3 border-t border-neutral-200">{{ $roles->links() }}</div>
        @endif
    </div>
</x-layouts.admin>
