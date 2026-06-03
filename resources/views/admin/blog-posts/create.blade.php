<x-layouts.admin>
    <x-slot name="title">New Blog Post</x-slot>

    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm text-neutral-600 mb-1">
            <a href="{{ route('admin.blog-posts.index') }}" class="hover:text-primary-600">Blog Posts</a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-neutral-900">New Post</span>
        </div>
        <h1 class="text-2xl font-bold text-neutral-900">New Blog Post</h1>
    </div>

    <form action="{{ route('admin.blog-posts.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Main Content --}}
            <div class="lg:col-span-2 space-y-6">
                <div class="card">
                    <div class="p-4 border-b border-neutral-200">
                        <h2 class="font-semibold text-neutral-900">Post Content</h2>
                    </div>
                    <div class="p-4 space-y-4">
                        <div x-data="{ title: '{{ old('title') }}', slugManual: false, slug: '{{ old('slug') }}' }">
                            <div class="mb-4">
                                <label class="form-label">Title <span class="text-danger-500">*</span></label>
                                <input type="text" name="title" x-model="title" required
                                       @input="if(!slugManual) slug = title.toLowerCase().replace(/[^a-z0-9\s-]/g,'').replace(/[\s]+/g,'-').replace(/-+/g,'-').replace(/^-|-$/g,'')"
                                       class="form-input w-full" placeholder="Post title">
                                @error('title')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="form-label">Slug</label>
                                <input type="text" name="slug" x-model="slug" @input="slugManual = slug.length > 0"
                                       class="form-input w-full" placeholder="auto-generated-from-title">
                                <p class="mt-1 text-xs text-neutral-600">Auto-generated from title. Edit to customize.</p>
                                @error('slug')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div>
                            <label class="form-label">Excerpt</label>
                            <textarea name="excerpt" rows="3" class="form-textarea w-full"
                                      placeholder="Short description shown in blog listing...">{{ old('excerpt') }}</textarea>
                            <p class="mt-1 text-xs text-neutral-600">Max 500 characters</p>
                            @error('excerpt')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="form-label">Content</label>
                            <textarea name="content" id="content">{{ old('content') }}</textarea>
                            @error('content')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                {{-- SEO --}}
                <div class="card">
                    <div class="p-4 border-b border-neutral-200">
                        <h2 class="font-semibold text-neutral-900">SEO</h2>
                    </div>
                    <div class="p-4 space-y-4">
                        <div>
                            <label class="form-label">Meta Title</label>
                            <input type="text" name="seo_data[meta_title]" value="{{ old('seo_data.meta_title') }}"
                                   class="form-input w-full" placeholder="SEO title (defaults to post title)">
                        </div>
                        <div>
                            <label class="form-label">Meta Description</label>
                            <textarea name="seo_data[meta_description]" rows="2" class="form-textarea w-full"
                                      placeholder="SEO meta description">{{ old('seo_data.meta_description') }}</textarea>
                        </div>
                        <div>
                            <label class="form-label">Keywords</label>
                            <input type="text" name="seo_data[keywords]" value="{{ old('seo_data.keywords') }}"
                                   class="form-input w-full" placeholder="keyword1, keyword2, keyword3">
                            <p class="mt-1 text-xs text-neutral-600">Comma separated SEO keywords</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                {{-- Status --}}
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
                            <label for="is_published" class="text-sm font-medium text-neutral-700">Publish immediately</label>
                        </div>
                        <p class="mt-1.5 text-xs text-neutral-600">Unchecked = saved as draft</p>
                    </div>
                </div>

                {{-- Featured Image --}}
                <div class="card">
                    <div class="p-4 border-b border-neutral-200">
                        <h2 class="font-semibold text-neutral-900">Featured Image</h2>
                    </div>
                    <div class="p-4">
                        <input type="file" name="featured_image" accept="image/*"
                               class="block w-full text-sm text-neutral-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                        <p class="mt-1.5 text-xs text-neutral-600">JPG, PNG, WebP. Max 2MB.</p>
                        @error('featured_image')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                {{-- Category & Tags --}}
                <div class="card">
                    <div class="p-4 border-b border-neutral-200">
                        <h2 class="font-semibold text-neutral-900">Classification</h2>
                    </div>
                    <div class="p-4 space-y-4">
                        <div>
                            <label class="form-label">Category</label>
                            <input type="text" name="category" value="{{ old('category') }}"
                                   class="form-input w-full" placeholder="e.g. Fashion, Parenting Tips">
                        </div>
                        <div>
                            <label class="form-label">Tags</label>
                            <input type="text" name="tags" value="{{ old('tags') }}"
                                   class="form-input w-full" placeholder="tag1, tag2, tag3">
                            <p class="mt-1 text-xs text-neutral-600">Comma separated</p>
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="card">
                    <div class="p-4 space-y-3">
                        <button type="submit" class="btn btn-primary w-full">Create Post</button>
                        <a href="{{ route('admin.blog-posts.index') }}" class="btn btn-secondary w-full text-center">Cancel</a>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <x-slot name="styles">
        @vite('resources/js/admin-editor.js')
        <style>
            .ql-editor { min-height: 400px; font-size: 14px; line-height: 1.7; }
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
            window.initQuillEditor('#content', {
                uploadUrl: '{{ route("admin.editor.upload-image") }}',
                placeholder: 'Write blog post content...'
            });
        } else {
            setTimeout(tryInit, 50);
        }
    })();
    </script>
    @endpush
</x-layouts.admin>
