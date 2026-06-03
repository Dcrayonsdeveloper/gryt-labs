<x-layouts.admin>
    <x-slot name="title">Edit Store</x-slot>

    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm text-neutral-600 mb-1">
            <a href="{{ route('admin.stores.index') }}" class="hover:text-primary-600">Stores</a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-neutral-900">Edit: {{ $store->name }}</span>
        </div>
        <h1 class="text-2xl font-bold text-neutral-900">Edit Store: {{ $store->name }}</h1>
    </div>

    <form action="{{ route('admin.stores.update', $store) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="card">
                    <div class="p-4 border-b border-neutral-200">
                        <h2 class="font-semibold text-neutral-900">Store Details</h2>
                    </div>
                    <div class="p-4 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-neutral-700 mb-1">Name <span class="text-danger-500">*</span></label>
                                <input type="text" name="name" value="{{ old('name', $store->name) }}" required
                                       class="form-input w-full">
                                @error('name')
                                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-neutral-700 mb-1">Code <span class="text-danger-500">*</span></label>
                                <input type="text" name="code" value="{{ old('code', $store->code) }}" required
                                       class="form-input w-full">
                                @error('code')
                                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-1">Address</label>
                            <input type="text" name="address" value="{{ old('address', $store->address) }}" class="form-input w-full">
                            @error('address')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="p-4 border-b border-neutral-200">
                        <h2 class="font-semibold text-neutral-900">Contact Information</h2>
                    </div>
                    <div class="p-4 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-neutral-700 mb-1">Phone</label>
                                <input type="text" name="phone" value="{{ old('phone', $store->phone) }}" class="form-input w-full">
                                @error('phone')
                                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-neutral-700 mb-1">Email</label>
                                <input type="email" name="email" value="{{ old('email', $store->email) }}" class="form-input w-full">
                                @error('email')
                                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                @if($store->registers->count())
                    <div class="card">
                        <div class="p-4 border-b border-neutral-200">
                            <h2 class="font-semibold text-neutral-900">POS Registers ({{ $store->registers->count() }})</h2>
                        </div>
                        <div class="divide-y divide-neutral-200">
                            @foreach($store->registers as $register)
                                <div class="px-4 py-3 flex items-center justify-between">
                                    <div>
                                        <p class="font-medium text-neutral-900">{{ $register->name }}</p>
                                        <p class="text-sm text-neutral-600">{{ $register->code ?? '' }}</p>
                                    </div>
                                    <span class="badge {{ $register->is_active ? 'badge-success' : 'badge-warning' }}">
                                        {{ $register->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <div class="space-y-6">
                <div class="card">
                    <div class="p-4 border-b border-neutral-200">
                        <h2 class="font-semibold text-neutral-900">Status</h2>
                    </div>
                    <div class="p-4 space-y-3">
                        <div class="flex items-center gap-2">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" id="is_active"
                                   class="rounded border-neutral-300 text-primary-600 focus:ring-primary-500"
                                   @checked(old('is_active', $store->is_active))>
                            <label for="is_active" class="text-sm font-medium text-neutral-700">Active</label>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="p-4 border-b border-neutral-200">
                        <h2 class="font-semibold text-neutral-900">Info</h2>
                    </div>
                    <div class="p-4 space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-neutral-600">Code</span>
                            <span class="font-medium font-mono">{{ $store->code }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-neutral-600">Registers</span>
                            <span class="font-medium">{{ $store->registers->count() }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-neutral-600">Created</span>
                            <span class="font-medium">{{ $store->created_at->format('M d, Y') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-neutral-600">Updated</span>
                            <span class="font-medium">{{ $store->updated_at->format('M d, Y') }}</span>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="p-4 space-y-3">
                        <button type="submit" class="btn btn-primary w-full">Update Store</button>
                        <a href="{{ route('admin.stores.index') }}" class="btn btn-secondary w-full text-center">Cancel</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-layouts.admin>
