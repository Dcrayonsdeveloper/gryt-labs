<x-layouts.admin>
    <x-slot name="title">Add Location</x-slot>

    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm text-neutral-600 mb-1">
            <a href="{{ route('admin.inventory.index') }}" class="hover:text-primary-600">Inventory</a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <a href="{{ route('admin.inventory.locations.index') }}" class="hover:text-primary-600">Locations</a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-neutral-900">Add</span>
        </div>
        <h1 class="text-2xl font-bold text-neutral-900">Add Location</h1>
    </div>

    <form action="{{ route('admin.inventory.locations.store') }}" method="POST">
        @csrf

        <div class="card max-w-2xl">
            <div class="p-4 border-b border-neutral-200">
                <h2 class="font-semibold text-neutral-900">Location Details</h2>
            </div>
            <div class="p-4 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-neutral-700 mb-1">Name <span class="text-danger-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="form-input w-full" placeholder="e.g. Main Warehouse">
                    @error('name')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-neutral-700 mb-1">Code <span class="text-danger-500">*</span></label>
                    <input type="text" name="code" value="{{ old('code') }}" required
                           class="form-input w-full" placeholder="e.g. WH-001">
                    @error('code')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-neutral-700 mb-1">Address</label>
                    <textarea name="address" rows="2" class="form-textarea w-full" placeholder="Full address...">{{ old('address') }}</textarea>
                    @error('address')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center gap-2">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" id="is_active"
                           class="rounded border-neutral-300 text-primary-600 focus:ring-primary-500"
                           @checked(old('is_active', true))>
                    <label for="is_active" class="text-sm font-medium text-neutral-700">Active</label>
                </div>
            </div>
            <div class="p-4 border-t border-neutral-200 flex items-center justify-end gap-3">
                <a href="{{ route('admin.inventory.locations.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Create Location</button>
            </div>
        </div>
    </form>
</x-layouts.admin>
