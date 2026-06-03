<x-layouts.admin>
    <x-slot name="title">Add Value - {{ $attribute->name }}</x-slot>

    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm text-neutral-600 mb-1">
            <a href="{{ route('admin.attributes.index') }}" class="hover:text-primary-600">Attributes</a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <a href="{{ route('admin.attributes.edit', $attribute) }}" class="hover:text-primary-600">{{ $attribute->name }}</a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-neutral-900">Add Value</span>
        </div>
        <h1 class="text-2xl font-bold text-neutral-900">Add Value to "{{ $attribute->name }}"</h1>
    </div>

    <form action="{{ route('admin.attributes.values.store', $attribute) }}" method="POST">
        @csrf

        <div class="card max-w-lg">
            <div class="p-4 border-b border-neutral-200">
                <h2 class="font-semibold text-neutral-900">Value Details</h2>
            </div>
            <div class="p-4 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-neutral-700 mb-1">Value <span class="text-danger-500">*</span></label>
                    <input type="text" name="value" value="{{ old('value') }}" required
                           class="form-input w-full" placeholder="e.g. Red, XL, Cotton">
                    @error('value')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                @if($attribute->type === 'color')
                    <div>
                        <label class="block text-sm font-medium text-neutral-700 mb-1">Color Code</label>
                        <div class="flex items-center gap-2">
                            <input type="color" name="color_code" value="{{ old('color_code', '#000000') }}"
                                   class="w-10 h-10 rounded border border-neutral-300 cursor-pointer">
                            <input type="text" value="{{ old('color_code', '#000000') }}" readonly
                                   class="form-input flex-1 bg-neutral-50" id="color_text">
                        </div>
                        @error('color_code')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                <div>
                    <label class="block text-sm font-medium text-neutral-700 mb-1">Position</label>
                    <input type="number" name="position" value="{{ old('position', 0) }}" min="0"
                           class="form-input w-full">
                    @error('position')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <div class="p-4 border-t border-neutral-200 flex items-center justify-end gap-3">
                <a href="{{ route('admin.attributes.edit', $attribute) }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Add Value</button>
            </div>
        </div>
    </form>
</x-layouts.admin>
