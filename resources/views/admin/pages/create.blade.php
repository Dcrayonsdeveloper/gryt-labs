<x-layouts.admin>
    <x-slot name="title">Add Page</x-slot>

    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm text-neutral-600 mb-1">
            <a href="{{ route('admin.pages.index') }}" class="hover:text-primary-600">Pages</a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-neutral-900">Add Page</span>
        </div>
        <h1 class="text-2xl font-bold text-neutral-900">Add Page</h1>
    </div>

    <form action="{{ route('admin.pages.store') }}" method="POST">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="card">
                    <div class="p-4 border-b border-neutral-200">
                        <h2 class="font-semibold text-neutral-900">Page Details</h2>
                    </div>
                    <div class="p-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-1">Title <span class="text-danger-500">*</span></label>
                            <input type="text" name="title" value="{{ old('title') }}" required
                                   class="form-input w-full" placeholder="e.g. About Us">
                            @error('title')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-1">Slug</label>
                            <input type="text" name="slug" value="{{ old('slug') }}"
                                   class="form-input w-full" placeholder="auto-generated-from-title">
                            <p class="mt-1 text-xs text-neutral-600">Leave empty to auto-generate from title</p>
                            @error('slug')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-1">Content</label>
                            <textarea name="content" id="page-content" rows="12" class="form-textarea w-full" placeholder="Page content...">{{ old('content') }}</textarea>
                            @error('content')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="p-4 border-b border-neutral-200">
                        <h2 class="font-semibold text-neutral-900">SEO</h2>
                    </div>
                    <div class="p-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-1">Meta Title</label>
                            <input type="text" name="seo_data[meta_title]" value="{{ old('seo_data.meta_title') }}"
                                   class="form-input w-full" placeholder="SEO title">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-1">Meta Description</label>
                            <textarea name="seo_data[meta_description]" rows="2" class="form-textarea w-full"
                                      placeholder="SEO description">{{ old('seo_data.meta_description') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="card">
                    <div class="p-4 border-b border-neutral-200">
                        <h2 class="font-semibold text-neutral-900">Status</h2>
                    </div>
                    <div class="p-4">
                        <div class="flex items-center gap-2">
                            <input type="hidden" name="is_published" value="0">
                            <input type="checkbox" name="is_published" value="1" id="is_published"
                                   class="rounded border-neutral-300 text-primary-600 focus:ring-primary-500"
                                   @checked(old('is_published'))>
                            <label for="is_published" class="text-sm font-medium text-neutral-700">Published</label>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="p-4 space-y-3">
                        <button type="submit" class="btn btn-primary w-full">Create Page</button>
                        <a href="{{ route('admin.pages.index') }}" class="btn btn-secondary w-full text-center">Cancel</a>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <x-slot name="styles">
        @vite('resources/js/admin-editor.js')
        <style>
            .ql-editor { min-height: 300px; font-size: 14px; line-height: 1.7; }
            .ql-toolbar.ql-snow { border-color: #d4d4d4; border-radius: 8px 8px 0 0; background: #fafafa; }
            .ql-container.ql-snow { border-color: #d4d4d4; border-radius: 0 0 8px 8px; }
            .ql-editor:focus { outline: none; }
            .ql-editor img { max-width: 100%; height: auto; border-radius: 8px; margin: 12px 0; }
        </style>
    </x-slot>

    @push('scripts')
    <script>
    (function tryInit() {
        if (typeof window.initQuillEditor === 'function') {
            window.initQuillEditor('#page-content', {
                uploadUrl: '{{ route("admin.editor.upload-image") }}',
                placeholder: 'Write page content...'
            });
        } else {
            setTimeout(tryInit, 50);
        }
    })();
    </script>
    @endpush
</x-layouts.admin>
