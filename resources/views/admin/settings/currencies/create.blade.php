<x-layouts.admin>
    <x-slot name="title">Add Currency</x-slot>

    <div class="mb-6">
        <a href="{{ route('admin.settings.currencies.index') }}" class="text-sm text-neutral-600 hover:text-primary-600">&larr; Back to Currencies</a>
        <h1 class="text-2xl font-bold text-neutral-900 mt-1">Add Currency</h1>
    </div>

    <div class="card p-6 max-w-2xl">
        <form action="{{ route('admin.settings.currencies.store') }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label form-label-required">Currency Code</label>
                        <input type="text" name="code" value="{{ old('code') }}" class="form-input" placeholder="USD" maxlength="3" required>
                        @error('code') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label form-label-required">Name</label>
                        <input type="text" name="name" value="{{ old('name') }}" class="form-input" placeholder="US Dollar" required>
                        @error('name') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label form-label-required">Symbol</label>
                        <input type="text" name="symbol" value="{{ old('symbol') }}" class="form-input" placeholder="$" required>
                        @error('symbol') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label form-label-required">Exchange Rate</label>
                        <input type="number" name="exchange_rate" value="{{ old('exchange_rate', '1') }}" class="form-input" step="0.000001" min="0" required>
                        @error('exchange_rate') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_default" value="1" class="form-checkbox" {{ old('is_default') ? 'checked' : '' }}>
                        <span class="text-sm text-neutral-700">Default currency</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" value="1" class="form-checkbox" {{ old('is_active', true) ? 'checked' : '' }}>
                        <span class="text-sm text-neutral-700">Active</span>
                    </label>
                </div>
                <button type="submit" class="btn btn-primary">Create Currency</button>
            </div>
        </form>
    </div>
</x-layouts.admin>
