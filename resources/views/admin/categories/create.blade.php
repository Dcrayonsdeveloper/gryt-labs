<x-layouts.admin>
    <x-slot name="title">Add Category</x-slot>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2 text-sm text-neutral-600 mb-1">
                    <a href="{{ route('admin.categories.index') }}" class="hover:text-primary-600 transition-colors">Categories</a>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    <span class="text-neutral-700">Add Category</span>
                </div>
                <h1 class="text-2xl font-bold text-neutral-900">Add New Category</h1>
            </div>
            <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back
            </a>
        </div>
    </x-slot>

    <div class="max-w-3xl" x-data="categoryForm()">
        <form action="{{ route('admin.categories.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <!-- Category Information -->
            <div class="card overflow-hidden">
                <div class="px-6 py-4 border-b border-neutral-100 bg-neutral-50/50">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                        </svg>
                        <h2 class="font-semibold text-neutral-900">Category Information</h2>
                    </div>
                </div>
                <div class="p-6 space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label for="name" class="form-label form-label-required">Category Name</label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                   class="form-input w-full @error('name') form-input-error @enderror"
                                   placeholder="e.g. Girls Dresses"
                                   @input="if(!slugManual) slug = toSlug($event.target.value)">
                            @error('name')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="slug" class="form-label">Slug</label>
                            <input type="text" name="slug" id="slug" x-model="slug"
                                   class="form-input w-full @error('slug') form-input-error @enderror"
                                   placeholder="auto-generated-from-name"
                                   @input="slugManual = ($event.target.value.trim() !== '')">
                            <p class="form-help">Leave blank to auto-generate</p>
                            @error('slug')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label for="parent_id" class="form-label">Parent Category</label>
                            <select name="parent_id" id="parent_id"
                                    class="form-input w-full @error('parent_id') form-input-error @enderror">
                                <option value="">None (Root Category)</option>
                                @foreach($parentCategories as $parent)
                                    <option value="{{ $parent->id }}" {{ old('parent_id') == $parent->id ? 'selected' : '' }}>
                                        {{ $parent->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('parent_id')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="position" class="form-label">Sort Order</label>
                            <input type="number" name="position" id="position" value="{{ old('position', 0) }}" min="0"
                                   class="form-input w-full @error('position') form-input-error @enderror">
                            <p class="form-help">Lower numbers appear first</p>
                            @error('position')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Thumbnail -->
                    <div x-data="{ preview: null }">
                        <label class="form-label">Thumbnail Image</label>
                        <div class="flex items-start gap-4">
                            <div class="w-20 h-20 rounded-xl bg-neutral-100 border-2 border-dashed border-neutral-300 flex items-center justify-center overflow-hidden shrink-0">
                                <template x-if="preview">
                                    <img :src="preview" class="w-full h-full object-cover rounded-lg">
                                </template>
                                <template x-if="!preview">
                                    <svg class="w-8 h-8 text-neutral-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </template>
                            </div>
                            <div class="flex-1">
                                <input type="file" name="image" id="image" accept="image/jpeg,image/png,image/webp"
                                       class="form-input w-full @error('image') form-input-error @enderror"
                                       @change="preview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null">
                                <p class="form-help">JPG, PNG or WebP. Max 2MB. Displayed in "Shop by Category" section.</p>
                                @error('image')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="description" class="form-label">Description</label>
                        <textarea name="description" id="description" rows="3"
                                  class="form-input w-full @error('description') form-input-error @enderror"
                                  placeholder="Brief category description...">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="flex items-center gap-2.5 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                                   class="form-checkbox">
                            <span class="text-sm text-neutral-700">Active</span>
                            <span class="text-xs text-neutral-600">Category will be visible on the storefront</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- SEO -->
            <div class="card overflow-hidden">
                <div class="px-6 py-4 border-b border-neutral-100 bg-neutral-50/50">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <h2 class="font-semibold text-neutral-900">SEO</h2>
                    </div>
                </div>
                <div class="p-6 space-y-5">
                    <div>
                        <label for="meta_title" class="form-label">Meta Title</label>
                        <input type="text" name="meta_title" id="meta_title" value="{{ old('meta_title') }}"
                               class="form-input w-full @error('meta_title') form-input-error @enderror"
                               placeholder="Defaults to category name">
                        @error('meta_title')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="meta_description" class="form-label">Meta Description</label>
                        <textarea name="meta_description" id="meta_description" rows="2"
                                  class="form-input w-full @error('meta_description') form-input-error @enderror"
                                  placeholder="SEO description for search engines...">{{ old('meta_description') }}</textarea>
                        @error('meta_description')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Google Shopping -->
            <div class="card overflow-hidden">
                <div class="px-6 py-4 border-b border-neutral-100 bg-neutral-50/50">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                        <h2 class="font-semibold text-neutral-900">Google Shopping</h2>
                    </div>
                </div>
                <div class="p-6">
                    <div>
                        <label for="google_product_category" class="form-label">Google Product Category</label>
                        <input type="text" name="google_product_category" id="google_product_category"
                               value="{{ old('google_product_category') }}"
                               class="form-input w-full"
                               placeholder="e.g. Health & Beauty > Personal Care > Hair Care">
                        <p class="text-xs text-neutral-500 mt-1">Used in Google Merchant feed. Leave empty for auto-detection.</p>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-3">
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Create Category
                </button>
                <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        function categoryForm() {
            return {
                slug: '{{ old("slug", "") }}',
                slugManual: {{ old('slug') ? 'true' : 'false' }},
                toSlug(text) {
                    return text
                        .toLowerCase()
                        .trim()
                        .replace(/[^\w\s-]/g, '')
                        .replace(/[\s_]+/g, '-')
                        .replace(/-+/g, '-')
                        .replace(/^-+|-+$/g, '');
                }
            };
        }
    </script>
    @endpush
</x-layouts.admin>
