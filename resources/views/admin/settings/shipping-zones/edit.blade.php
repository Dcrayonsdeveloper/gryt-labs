<x-layouts.admin>
    <x-slot name="title">Edit Shipping Zone</x-slot>

    <div class="mb-6">
        <a href="{{ route('admin.settings.shipping-zones.index') }}" class="text-sm text-neutral-600 hover:text-primary-600">&larr; Back to Shipping Zones</a>
        <h1 class="text-2xl font-bold text-neutral-900 mt-1">Edit: {{ $shippingZone->name }}</h1>
    </div>

    <div class="card p-6 max-w-2xl">
        <form action="{{ route('admin.settings.shipping-zones.update', $shippingZone) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="space-y-4">
                <div>
                    <label class="form-label form-label-required">Zone Name</label>
                    <input type="text" name="name" value="{{ old('name', $shippingZone->name) }}" class="form-input" required>
                    @error('name') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" value="1" class="form-checkbox" {{ old('is_active', $shippingZone->is_active) ? 'checked' : '' }}>
                        <span class="text-sm text-neutral-700">Active</span>
                    </label>
                </div>
                <button type="submit" class="btn btn-primary">Update Shipping Zone</button>
            </div>
        </form>
    </div>
</x-layouts.admin>
