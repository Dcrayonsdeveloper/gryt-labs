<x-layouts.admin>
    <x-slot name="title">Add Brand</x-slot>

    <x-slot name="header">
        <div>
            <div class="flex items-center gap-2 text-sm text-neutral-600 mb-1">
                <a href="{{ route('admin.brands.index') }}" class="hover:text-primary-600 transition-colors">Brands</a>
                <svg class="w-3.5 h-3.5 text-neutral-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <span class="text-neutral-900">Add Brand</span>
            </div>
            <h1 class="text-2xl font-bold text-neutral-900">Add Brand</h1>
        </div>
    </x-slot>

    <form action="{{ route('admin.brands.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="card">
                    <div class="px-5 py-4 border-b border-neutral-200">
                        <h2 class="font-semibold text-neutral-900">Brand Details</h2>
                    </div>
                    <div class="p-5 space-y-5">
                        <div>
                            <label class="form-label">Name <span class="text-danger-500">*</span></label>
                            <input type="text" name="name" value="{{ old('name') }}" required
                                   class="form-input w-full" placeholder="e.g. L'Oreal">
                            @error('name')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="form-label">Description</label>
                            <textarea name="description" rows="3" class="form-textarea w-full" placeholder="Brief description of the brand...">{{ old('description') }}</textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="form-label">Logo</label>
                            <div class="mt-1 flex items-center gap-5">
                                <div class="w-16 h-16 rounded-xl bg-neutral-100 flex items-center justify-center ring-1 ring-neutral-200 shrink-0" id="logo-preview-container">
                                    <svg class="w-6 h-6 text-neutral-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <input type="file" name="logo" accept="image/*"
                                           class="block w-full text-sm text-neutral-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                                    <p class="mt-1.5 text-xs text-neutral-600">PNG, JPG or SVG. Max 2MB.</p>
                                </div>
                            </div>
                            @error('logo')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="px-5 py-4 border-b border-neutral-200">
                        <h2 class="font-semibold text-neutral-900">SEO</h2>
                    </div>
                    <div class="p-5 space-y-5">
                        <div>
                            <label class="form-label">Meta Title</label>
                            <input type="text" name="meta_title" value="{{ old('meta_title') }}"
                                   class="form-input w-full" placeholder="SEO title (defaults to brand name)">
                        </div>
                        <div>
                            <label class="form-label">Meta Description</label>
                            <textarea name="meta_description" rows="2" class="form-textarea w-full"
                                      placeholder="SEO description">{{ old('meta_description') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="card">
                    <div class="px-5 py-4 border-b border-neutral-200">
                        <h2 class="font-semibold text-neutral-900">Status</h2>
                    </div>
                    <div class="p-5 space-y-4">
                        <label for="is_active" class="flex items-center gap-3 cursor-pointer">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" id="is_active"
                                   class="rounded border-neutral-300 text-primary-600 focus:ring-primary-500"
                                   @checked(old('is_active', true))>
                            <div>
                                <span class="text-sm font-medium text-neutral-700">Active</span>
                                <p class="text-xs text-neutral-600">Brand is visible on the storefront</p>
                            </div>
                        </label>
                        <label for="is_featured" class="flex items-center gap-3 cursor-pointer">
                            <input type="hidden" name="is_featured" value="0">
                            <input type="checkbox" name="is_featured" value="1" id="is_featured"
                                   class="rounded border-neutral-300 text-primary-600 focus:ring-primary-500"
                                   @checked(old('is_featured', false))>
                            <div>
                                <span class="text-sm font-medium text-neutral-700">Featured</span>
                                <p class="text-xs text-neutral-600">Show in About Us brand carousel</p>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="card">
                    <div class="px-5 py-4 border-b border-neutral-200">
                        <h2 class="font-semibold text-neutral-900">Actions</h2>
                    </div>
                    <div class="p-5 space-y-3">
                        <button type="submit" class="btn btn-primary w-full">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Create Brand
                        </button>
                        <a href="{{ route('admin.brands.index') }}" class="btn btn-secondary w-full text-center">Cancel</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-layouts.admin>
