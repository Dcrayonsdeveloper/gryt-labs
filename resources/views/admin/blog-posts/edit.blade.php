<x-layouts.admin>
    <x-slot name="title">Edit: {{ $blogPost->title }}</x-slot>

    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm text-neutral-600 mb-1">
            <a href="{{ route('admin.blog-posts.index') }}" class="hover:text-primary-600">Blog Posts</a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-neutral-900">Edit</span>
        </div>
        <h1 class="text-2xl font-bold text-neutral-900">Edit Blog Post</h1>
    </div>

    <form action="{{ route('admin.blog-posts.update', $blogPost) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Main Content --}}
            <div class="lg:col-span-2 space-y-6">
                <div class="card">
                    <div class="p-4 border-b border-neutral-200">
                        <h2 class="font-semibold text-neutral-900">Post Content</h2>
                    </div>
                    <div class="p-4 space-y-4">
                        <div>
                            <label class="form-label">Title <span class="text-danger-500">*</span></label>
                            <input type="text" name="title" value="{{ old('title', $blogPost->title) }}" required
                                   class="form-input w-full">
                            @error('title')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="form-label">Slug</label>
                            <div class="flex items-center gap-1 text-xs text-neutral-500 mb-1">
                                <span>/blog/</span><span class="font-medium text-neutral-700">{{ $blogPost->slug }}</span>
                            </div>
                            <input type="text" name="slug" value="{{ old('slug', $blogPost->slug) }}"
                                   class="form-input w-full">
                            @error('slug')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="form-label">Excerpt</label>
                            <textarea name="excerpt" rows="3" class="form-textarea w-full"
                                      placeholder="Short description shown in blog listing...">{{ old('excerpt', $blogPost->excerpt) }}</textarea>
                            @error('excerpt')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="form-label">Content</label>
                            <textarea name="content" id="content">{{ old('content', $blogPost->content) }}</textarea>
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
                            <input type="text" name="seo_data[meta_title]"
                                   value="{{ old('seo_data.meta_title', $blogPost->seo_data['meta_title'] ?? '') }}"
                                   class="form-input w-full">
                        </div>
                        <div>
                            <label class="form-label">Meta Description</label>
                            <textarea name="seo_data[meta_description]" rows="2" class="form-textarea w-full">{{ old('seo_data.meta_description', $blogPost->seo_data['meta_description'] ?? '') }}</textarea>
                        </div>
                        <div>
                            <label class="form-label">Keywords</label>
                            <input type="text" name="seo_data[keywords]" value="{{ old('seo_data.keywords', $blogPost->seo_data['keywords'] ?? '') }}"
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
                                   @checked(old('is_published', $blogPost->is_published))>
                            <label for="is_published" class="text-sm font-medium text-neutral-700">Published</label>
                        </div>
                        @if($blogPost->published_at)
                            <p class="mt-1.5 text-xs text-neutral-600">Published {{ $blogPost->published_at->format('M d, Y') }}</p>
                        @endif
                    </div>
                </div>

                {{-- Featured Image --}}
                <div class="card">
                    <div class="p-4 border-b border-neutral-200">
                        <h2 class="font-semibold text-neutral-900">Featured Image</h2>
                    </div>
                    <div class="p-4">
                        @if($blogPost->featured_image)
                            <div class="mb-3">
                                <img src="{{ asset('storage/' . $blogPost->featured_image) }}" alt="{{ $blogPost->title }}"
                                     class="w-full h-32 object-cover rounded-lg">
                            </div>
                        @endif
                        <input type="file" name="featured_image" accept="image/*"
                               class="block w-full text-sm text-neutral-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                        <p class="mt-1.5 text-xs text-neutral-600">Upload new to replace existing. JPG, PNG, WebP. Max 2MB.</p>
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
                            <input type="text" name="category" value="{{ old('category', $blogPost->category) }}"
                                   class="form-input w-full" placeholder="e.g. Fashion, Parenting Tips">
                        </div>
                        <div>
                            <label class="form-label">Tags</label>
                            <input type="text" name="tags"
                                   value="{{ old('tags', $blogPost->tags ? implode(', ', $blogPost->tags) : '') }}"
                                   class="form-input w-full" placeholder="tag1, tag2, tag3">
                            <p class="mt-1 text-xs text-neutral-600">Comma separated</p>
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="card">
                    <div class="p-4 space-y-3">
                        <button type="submit" class="btn btn-primary w-full">Save Changes</button>
                        @if($blogPost->is_published)
                            <a href="{{ route('blog.show', $blogPost->slug) }}" target="_blank"
                               class="btn btn-secondary w-full text-center">View on Site</a>
                        @endif
                        <a href="{{ route('admin.blog-posts.index') }}" class="btn btn-secondary w-full text-center">Cancel</a>
                    </div>
                </div>
            </div>
        </div>
    </form>

    {{-- Danger Zone (must be outside the update form) --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
        <div class="lg:col-start-3">
            <div class="card border border-danger-200">
                <div class="p-4">
                    <form action="{{ route('admin.blog-posts.destroy', $blogPost) }}" method="POST"
                          onsubmit="return confirm('Permanently delete this post?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn w-full text-danger-600 border border-danger-200 hover:bg-danger-50 text-sm">
                            Delete Post
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

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
