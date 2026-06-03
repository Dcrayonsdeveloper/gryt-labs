<x-layouts.admin>
    <x-slot name="title">Add Role</x-slot>

    <div class="mb-6">
        <a href="{{ route('admin.settings.roles.index') }}" class="text-sm text-neutral-600 hover:text-primary-600">&larr; Back to Roles</a>
        <h1 class="text-2xl font-bold text-neutral-900 mt-1">Add Role</h1>
    </div>

    <div class="card p-6 max-w-2xl">
        <form action="{{ route('admin.settings.roles.store') }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="form-label form-label-required">Role Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" class="form-input" placeholder="e.g. editor, moderator" required>
                    @error('name') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                @if($permissions->count())
                    <div>
                        <label class="form-label">Permissions</label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 mt-2">
                            @foreach($permissions as $permission)
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" class="form-checkbox"
                                        {{ in_array($permission->id, old('permissions', [])) ? 'checked' : '' }}>
                                    <span class="text-sm text-neutral-700">{{ $permission->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif
                <button type="submit" class="btn btn-primary">Create Role</button>
            </div>
        </form>
    </div>
</x-layouts.admin>
