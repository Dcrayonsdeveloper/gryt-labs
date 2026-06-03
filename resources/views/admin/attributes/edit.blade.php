<x-layouts.admin>
    <x-slot name="title">Edit Attribute</x-slot>

    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm text-neutral-600 mb-1">
            <a href="{{ route('admin.attributes.index') }}" class="hover:text-primary-600">Attributes</a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-neutral-900">Edit: {{ $attribute->name }}</span>
        </div>
        <h1 class="text-2xl font-bold text-neutral-900">Edit Attribute: {{ $attribute->name }}</h1>
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <!-- Attribute Details -->
            <form action="{{ route('admin.attributes.update', $attribute) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="card">
                    <div class="p-4 border-b border-neutral-200">
                        <h2 class="font-semibold text-neutral-900">Attribute Details</h2>
                    </div>
                    <div class="p-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-1">Name <span class="text-danger-500">*</span></label>
                            <input type="text" name="name" value="{{ old('name', $attribute->name) }}" required
                                   class="form-input w-full">
                            @error('name')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-1">Type <span class="text-danger-500">*</span></label>
                            <select name="type" class="form-select w-full" required>
                                <option value="select" @selected(old('type', $attribute->type) === 'select')>Select (Dropdown)</option>
                                <option value="color" @selected(old('type', $attribute->type) === 'color')>Color (Swatch)</option>
                                <option value="text" @selected(old('type', $attribute->type) === 'text')>Text (Free Input)</option>
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
                                       @checked(old('is_filterable', $attribute->is_filterable))>
                                <label for="is_filterable" class="text-sm font-medium text-neutral-700">Filterable</label>
                            </div>
                            <div class="flex items-center gap-2">
                                <input type="hidden" name="is_visible" value="0">
                                <input type="checkbox" name="is_visible" value="1" id="is_visible"
                                       class="rounded border-neutral-300 text-primary-600 focus:ring-primary-500"
                                       @checked(old('is_visible', $attribute->is_visible))>
                                <label for="is_visible" class="text-sm font-medium text-neutral-700">Visible on product page</label>
                            </div>
                        </div>
                    </div>
                    <div class="p-4 border-t border-neutral-200 flex items-center justify-end gap-3">
                        <a href="{{ route('admin.attributes.index') }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Attribute</button>
                    </div>
                </div>
            </form>

            <!-- Attribute Values -->
            <div class="card">
                <div class="p-4 border-b border-neutral-200 flex items-center justify-between">
                    <h2 class="font-semibold text-neutral-900">Values</h2>
                    <a href="{{ route('admin.attributes.values.create', $attribute) }}" class="btn btn-primary btn-sm">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Add Value
                    </a>
                </div>
                @if($attribute->values->count())
                    <div class="divide-y divide-neutral-200">
                        @foreach($attribute->values as $value)
                            <div class="px-4 py-3 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    @if($attribute->type === 'color' && $value->color_code)
                                        <div class="w-6 h-6 rounded-full border border-neutral-200" style="background-color: {{ $value->color_code }}"></div>
                                    @endif
                                    <span class="font-medium text-neutral-900">{{ $value->value }}</span>
                                    @if($value->color_code)
                                        <span class="text-sm text-neutral-600">{{ $value->color_code }}</span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.values.edit', $value) }}" class="text-primary-600 hover:text-primary-700 text-sm font-medium">Edit</a>
                                    <form action="{{ route('admin.values.destroy', $value) }}" method="POST" onsubmit="return confirm('Delete this value?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-danger-600 hover:text-danger-700 text-sm font-medium">Delete</button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-8 text-center text-neutral-600">
                        No values added yet.
                        <a href="{{ route('admin.attributes.values.create', $attribute) }}" class="text-primary-600 hover:text-primary-700 font-medium ml-1">Add one now</a>
                    </div>
                @endif
            </div>
        </div>

        <!-- Sidebar Info -->
        <div>
            <div class="card">
                <div class="p-4 border-b border-neutral-200">
                    <h2 class="font-semibold text-neutral-900">Info</h2>
                </div>
                <div class="p-4 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-neutral-600">Type</span>
                        <span class="font-medium capitalize">{{ $attribute->type }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-neutral-600">Values</span>
                        <span class="font-medium">{{ $attribute->values->count() }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-neutral-600">Created</span>
                        <span class="font-medium">{{ $attribute->created_at->format('M d, Y') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-neutral-600">Updated</span>
                        <span class="font-medium">{{ $attribute->updated_at->format('M d, Y') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
