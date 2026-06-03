<x-layouts.admin>
    <x-slot name="title">Add Attribute</x-slot>

    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm text-neutral-600 mb-1">
            <a href="{{ route('admin.attributes.index') }}" class="hover:text-primary-600">Attributes</a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-neutral-900">Add Attribute</span>
        </div>
        <h1 class="text-2xl font-bold text-neutral-900">Add Attribute</h1>
    </div>

    <form action="{{ route('admin.attributes.store') }}" method="POST">
        @csrf

        <div class="card max-w-2xl">
            <div class="p-4 border-b border-neutral-200">
                <h2 class="font-semibold text-neutral-900">Attribute Details</h2>
            </div>
            <div class="p-4 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-neutral-700 mb-1">Name <span class="text-danger-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="form-input w-full" placeholder="e.g. Size, Color, Material">
                    @error('name')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-neutral-700 mb-1">Type <span class="text-danger-500">*</span></label>
                    <select name="type" class="form-select w-full" required>
                        <option value="select" @selected(old('type') === 'select')>Select (Dropdown)</option>
                        <option value="color" @selected(old('type') === 'color')>Color (Swatch)</option>
                        <option value="text" @selected(old('type') === 'text')>Text (Free Input)</option>
                    </select>
                    @error('type')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center gap-6">
                    <div class="flex items-center gap-2">
                        <input type="hidden" name="is_filterable" value="0">
                        <input type="checkbox" name="is_filterable" value="1" id="is_filterable"
                               class="rounded border-neutral-300 text-primary-600 focus:ring-primary-500"
                               @checked(old('is_filterable'))>
                        <label for="is_filterable" class="text-sm font-medium text-neutral-700">Filterable</label>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="hidden" name="is_visible" value="0">
                        <input type="checkbox" name="is_visible" value="1" id="is_visible"
                               class="rounded border-neutral-300 text-primary-600 focus:ring-primary-500"
                               @checked(old('is_visible', true))>
                        <label for="is_visible" class="text-sm font-medium text-neutral-700">Visible on product page</label>
                    </div>
                </div>
            </div>
            <div class="p-4 border-t border-neutral-200 flex items-center justify-end gap-3">
                <a href="{{ route('admin.attributes.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Create Attribute</button>
            </div>
        </div>
    </form>
</x-layouts.admin>
