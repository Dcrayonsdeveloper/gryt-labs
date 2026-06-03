<x-layouts.admin>
    <x-slot name="title">Add Shipping Zone</x-slot>

    <div class="mb-6">
        <a href="{{ route('admin.settings.shipping-zones.index') }}" class="text-sm text-neutral-600 hover:text-primary-600">&larr; Back to Shipping Zones</a>
        <h1 class="text-2xl font-bold text-neutral-900 mt-1">Add Shipping Zone</h1>
    </div>

    <div class="card p-6 max-w-2xl">
        <form action="{{ route('admin.settings.shipping-zones.store') }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="form-label form-label-required">Zone Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" class="form-input" placeholder="e.g. Domestic, International" required>
                    @error('name') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" value="1" class="form-checkbox" {{ old('is_active', true) ? 'checked' : '' }}>
                        <span class="text-sm text-neutral-700">Active</span>
                    </label>
                </div>
                <button type="submit" class="btn btn-primary">Create Shipping Zone</button>
            </div>
        </form>
    </div>
</x-layouts.admin>
