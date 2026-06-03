<x-layouts.admin>
    <x-slot name="title">Edit Customer</x-slot>

    <div class="flex items-center gap-2 text-sm text-neutral-600 mb-6">
        <a href="{{ route('admin.customers.index') }}" class="hover:text-primary-600">Customers</a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
        <a href="{{ route('admin.customers.show', $customer) }}" class="hover:text-primary-600">{{ $customer->full_name }}</a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
        <span class="text-neutral-900">Edit</span>
    </div>

    <div class="max-w-2xl">
        <h1 class="text-2xl font-bold text-neutral-900 mb-6">Edit Customer</h1>

        <form action="{{ route('admin.customers.update', $customer) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="card p-6 space-y-6">
                <h2 class="font-semibold text-neutral-900">Customer Information</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="first_name" class="block text-sm font-medium text-neutral-700 mb-1">First Name *</label>
                        <input type="text" name="first_name" id="first_name" value="{{ old('first_name', $customer->first_name) }}" required
                               class="form-input w-full @error('first_name') border-error-300 @enderror">
                        @error('first_name')
                            <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="last_name" class="block text-sm font-medium text-neutral-700 mb-1">Last Name *</label>
                        <input type="text" name="last_name" id="last_name" value="{{ old('last_name', $customer->last_name) }}" required
                               class="form-input w-full @error('last_name') border-error-300 @enderror">
                        @error('last_name')
                            <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label for="email" class="block text-sm font-medium text-neutral-700 mb-1">Email *</label>
                        <input type="email" name="email" id="email" value="{{ old('email', $customer->email) }}" required
                               class="form-input w-full @error('email') border-error-300 @enderror">
                        @error('email')
                            <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label for="phone" class="block text-sm font-medium text-neutral-700 mb-1">Phone</label>
                        <input type="tel" name="phone" id="phone" value="{{ old('phone', $customer->phone) }}"
                               class="form-input w-full @error('phone') border-error-300 @enderror">
                        @error('phone')
                            <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="card p-6 space-y-6">
                <h2 class="font-semibold text-neutral-900">Account Status</h2>

                <label class="flex items-center">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $customer->is_active) ? 'checked' : '' }}
                           class="rounded border-neutral-300 text-primary-600 focus:ring-primary-500">
                    <span class="ml-2 text-sm text-neutral-700">Account is active</span>
                </label>
                <p class="text-sm text-neutral-600">Inactive accounts cannot login or place orders.</p>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="btn btn-primary">Update Customer</button>
                <a href="{{ route('admin.customers.show', $customer) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</x-layouts.admin>
