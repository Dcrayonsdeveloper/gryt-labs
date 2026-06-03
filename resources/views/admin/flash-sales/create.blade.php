<x-layouts.admin>
    <x-slot name="title">Add Flash Sale</x-slot>

    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm text-neutral-600 mb-1">
            <a href="{{ route('admin.flash-sales.index') }}" class="hover:text-primary-600">Flash Sales</a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-neutral-900">Add Flash Sale</span>
        </div>
        <h1 class="text-2xl font-bold text-neutral-900">Add Flash Sale</h1>
    </div>

    <form action="{{ route('admin.flash-sales.store') }}" method="POST">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
                <div class="card">
                    <div class="p-4 border-b border-neutral-200">
                        <h2 class="font-semibold text-neutral-900">Flash Sale Details</h2>
                    </div>
                    <div class="p-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-1">Name <span class="text-danger-500">*</span></label>
                            <input type="text" name="name" value="{{ old('name') }}" required
                                   class="form-input w-full" placeholder="e.g. Weekend Mega Sale">
                            @error('name')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-1">Description</label>
                            <textarea name="description" rows="3" class="form-textarea w-full" placeholder="Optional description...">{{ old('description') }}</textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="card">
                    <div class="p-4 border-b border-neutral-200">
                        <h2 class="font-semibold text-neutral-900">Schedule</h2>
                    </div>
                    <div class="p-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-1">Starts At <span class="text-danger-500">*</span></label>
                            <input type="datetime-local" name="starts_at" value="{{ old('starts_at') }}" required class="form-input w-full">
                            @error('starts_at')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-1">Ends At <span class="text-danger-500">*</span></label>
                            <input type="datetime-local" name="ends_at" value="{{ old('ends_at') }}" required class="form-input w-full">
                            @error('ends_at')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="p-4 border-b border-neutral-200">
                        <h2 class="font-semibold text-neutral-900">Status</h2>
                    </div>
                    <div class="p-4">
                        <div class="flex items-center gap-2">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" id="is_active"
                                   class="rounded border-neutral-300 text-primary-600 focus:ring-primary-500"
                                   @checked(old('is_active', true))>
                            <label for="is_active" class="text-sm font-medium text-neutral-700">Active</label>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="p-4 space-y-3">
                        <button type="submit" class="btn btn-primary w-full">Create Flash Sale</button>
                        <a href="{{ route('admin.flash-sales.index') }}" class="btn btn-secondary w-full text-center">Cancel</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-layouts.admin>
