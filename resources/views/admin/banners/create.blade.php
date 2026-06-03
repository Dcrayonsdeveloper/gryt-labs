<x-layouts.admin>
    <x-slot name="title">Add Banner</x-slot>

    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm text-neutral-600 mb-1">
            <a href="{{ route('admin.banners.index') }}" class="hover:text-primary-600">Banners</a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-neutral-900">Add Banner</span>
        </div>
        <h1 class="text-2xl font-bold text-neutral-900">Add Banner</h1>
    </div>

    <form action="{{ route('admin.banners.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="card">
                    <div class="p-4 border-b border-neutral-200">
                        <h2 class="font-semibold text-neutral-900">Banner Details</h2>
                    </div>
                    <div class="p-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-1">Name <span class="text-danger-500">*</span></label>
                            <input type="text" name="name" value="{{ old('name') }}" required
                                   class="form-input w-full" placeholder="e.g. Summer Sale Hero Banner">
                            @error('name')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-1">Link URL</label>
                            <input type="url" name="link" value="{{ old('link') }}"
                                   class="form-input w-full" placeholder="https://example.com/page">
                            @error('link')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="p-4 border-b border-neutral-200">
                        <h2 class="font-semibold text-neutral-900">Images</h2>
                    </div>
                    <div class="p-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-1">Banner Image <span class="text-danger-500">*</span></label>
                            <input type="file" name="image" accept="image/*" required class="form-input w-full">
                            <p class="mt-1 text-xs text-neutral-600">Max 5MB. Recommended: 1920x600px</p>
                            @error('image')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-1">Mobile Image</label>
                            <input type="file" name="mobile_image" accept="image/*" class="form-input w-full">
                            <p class="mt-1 text-xs text-neutral-600">Optional. Recommended: 768x400px</p>
                            @error('mobile_image')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="card">
                    <div class="p-4 border-b border-neutral-200">
                        <h2 class="font-semibold text-neutral-900">Placement</h2>
                    </div>
                    <div class="p-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-1">Position <span class="text-danger-500">*</span></label>
                            <select name="position" required class="form-select w-full">
                                <option value="">Select position</option>
                                <option value="hero" @selected(old('position') == 'hero')>Hero</option>
                                <option value="sidebar" @selected(old('position') == 'sidebar')>Sidebar</option>
                                <option value="footer" @selected(old('position') == 'footer')>Footer</option>
                                <option value="category" @selected(old('position') == 'category')>Category</option>
                                <option value="popup" @selected(old('position') == 'popup')>Popup</option>
                            </select>
                            @error('position')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-1">Priority</label>
                            <input type="number" name="priority" value="{{ old('priority', 0) }}" min="0"
                                   class="form-input w-full">
                            <p class="mt-1 text-xs text-neutral-600">Lower number = higher priority</p>
                            @error('priority')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="p-4 border-b border-neutral-200">
                        <h2 class="font-semibold text-neutral-900">Schedule</h2>
                    </div>
                    <div class="p-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-1">Starts At</label>
                            <input type="datetime-local" name="starts_at" value="{{ old('starts_at') }}" class="form-input w-full">
                            @error('starts_at')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-1">Ends At</label>
                            <input type="datetime-local" name="ends_at" value="{{ old('ends_at') }}" class="form-input w-full">
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
                        <button type="submit" class="btn btn-primary w-full">Create Banner</button>
                        <a href="{{ route('admin.banners.index') }}" class="btn btn-secondary w-full text-center">Cancel</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-layouts.admin>
