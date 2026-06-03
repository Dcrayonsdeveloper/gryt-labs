<x-layouts.admin>
    <x-slot name="title">Edit Social Post</x-slot>

    <div class="flex items-center justify-between mb-5">
        <div>
            <a href="{{ route('admin.social-calendar.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Back to Calendar</a>
            <h1 class="text-xl font-semibold text-gray-900 mt-1">Edit: {{ $post->title }}</h1>
        </div>
        <span class="inline-flex items-center px-3 py-1 text-sm font-medium rounded-full
            {{ match($post->status) {
                'published' => 'bg-green-100 text-green-700',
                'scheduled' => 'bg-blue-100 text-blue-700',
                'draft' => 'bg-gray-100 text-gray-600',
                'failed','partially_failed' => 'bg-red-100 text-red-700',
                default => 'bg-yellow-100 text-yellow-700',
            } }}">
            {{ ucfirst($post->status) }}
        </span>
    </div>

    @if(session('success'))
        <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg">{{ session('error') }}</div>
    @endif

    {{-- Platform Results (if published/failed) --}}
    @if($post->platform_results)
    <div class="mb-6 bg-white rounded-xl shadow-sm border border-gray-200 p-5">
        <h3 class="font-medium text-gray-900 mb-3">Publishing Results</h3>
        <div class="space-y-2">
            @foreach($post->platform_results as $platform => $result)
            <div class="flex items-center justify-between text-sm">
                <span class="font-medium">{{ \App\Models\SocialMediaPost::PLATFORMS[$platform] ?? $platform }}</span>
                <span class="inline-flex items-center gap-1 {{ ($result['status'] ?? '') === 'success' ? 'text-green-600' : 'text-red-600' }}">
                    @if(($result['status'] ?? '') === 'success')
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
                        Published (ID: {{ $result['id'] ?? '' }})
                    @else
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/></svg>
                        {{ $result['message'] ?? 'Failed' }}
                    @endif
                </span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    @if(in_array($post->status, ['published', 'publishing']))
        <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 text-sm rounded-lg px-4 py-3 mb-6">
            This post has been published and cannot be edited.
        </div>
    @else
    <form action="{{ route('admin.social-calendar.update', $post) }}" method="POST" x-data="{ mediaType: '{{ $post->media_type }}' }">
        @csrf @method('PUT')
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Title <span class="text-red-500">*</span></label>
                        <input type="text" name="title" value="{{ old('title', $post->title) }}" required
                               class="w-full rounded-lg border-gray-300 text-sm focus:ring-gray-900 focus:border-gray-900">
                    </div>

                    <div x-data="{ count: {{ strlen($post->caption) }} }">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Caption <span class="text-red-500">*</span></label>
                        <textarea name="caption" rows="8" required maxlength="2200"
                                  x-on:input="count = $el.value.length"
                                  class="w-full rounded-lg border-gray-300 text-sm focus:ring-gray-900 focus:border-gray-900">{{ old('caption', $post->caption) }}</textarea>
                        <div class="text-xs text-gray-400 text-right mt-1" x-text="count + '/2200'"></div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Hashtags</label>
                        <input type="text" name="hashtags" value="{{ old('hashtags', implode(' ', $post->hashtags ?? [])) }}"
                               class="w-full rounded-lg border-gray-300 text-sm focus:ring-gray-900 focus:border-gray-900">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Media Type</label>
                        <select name="media_type" x-model="mediaType"
                                class="w-full rounded-lg border-gray-300 text-sm focus:ring-gray-900 focus:border-gray-900">
                            <option value="image">Single Image</option>
                            <option value="carousel">Carousel</option>
                            <option value="video">Video / Reel</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Media URLs</label>
                        <textarea name="media_urls" rows="4"
                                  class="w-full rounded-lg border-gray-300 text-sm font-mono focus:ring-gray-900 focus:border-gray-900">{{ old('media_urls', implode("\n", $post->media_urls ?? [])) }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Product Link</label>
                        <input type="url" name="link" value="{{ old('link', $post->link) }}"
                               class="w-full rounded-lg border-gray-300 text-sm focus:ring-gray-900 focus:border-gray-900">
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                    <h3 class="font-medium text-gray-900 mb-3">Platforms</h3>
                    <div class="space-y-2">
                        <p class="text-xs text-gray-500 font-medium uppercase">Instagram</p>
                        @foreach(['ig_post' => 'Post', 'ig_reel' => 'Reel', 'ig_story' => 'Story'] as $key => $label)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="platforms[]" value="{{ $key }}"
                                   class="rounded border-gray-300 text-pink-600"
                                   {{ in_array($key, old('platforms', $post->platforms ?? [])) ? 'checked' : '' }}>
                            {{ $label }}
                        </label>
                        @endforeach
                        <p class="text-xs text-gray-500 font-medium uppercase mt-3">Facebook</p>
                        @foreach(['fb_post' => 'Post', 'fb_reel' => 'Reel', 'fb_story' => 'Story'] as $key => $label)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="platforms[]" value="{{ $key }}"
                                   class="rounded border-gray-300 text-blue-600"
                                   {{ in_array($key, old('platforms', $post->platforms ?? [])) ? 'checked' : '' }}>
                            {{ $label }}
                        </label>
                        @endforeach
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                    <h3 class="font-medium text-gray-900 mb-3">Link to Product</h3>
                    <select name="product_id" class="w-full rounded-lg border-gray-300 text-sm">
                        <option value="">None</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" {{ old('product_id', $post->product_id) == $product->id ? 'selected' : '' }}>
                                {{ $product->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                    <h3 class="font-medium text-gray-900 mb-3">Schedule</h3>
                    <input type="datetime-local" name="scheduled_at"
                           value="{{ old('scheduled_at', $post->scheduled_at?->format('Y-m-d\TH:i')) }}"
                           class="w-full rounded-lg border-gray-300 text-sm">
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 space-y-2">
                    <button type="submit" name="action" value="publish_now"
                            class="w-full px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700">
                        Publish Now
                    </button>
                    <button type="submit" name="action" value="schedule"
                            class="w-full px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                        Schedule Post
                    </button>
                    <button type="submit" name="action" value="draft"
                            class="w-full px-4 py-2 bg-gray-200 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-300">
                        Save as Draft
                    </button>
                </div>
            </div>
        </div>
    </form>
    @endif
</x-layouts.admin>
