<x-layouts.admin>
    <x-slot name="title">Create Social Post</x-slot>

    <div class="flex items-center justify-between mb-5">
        <div>
            <a href="{{ route('admin.social-calendar.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Back to Calendar</a>
            <h1 class="text-xl font-semibold text-gray-900 mt-1">Create Social Post</h1>
        </div>
    </div>

    <form action="{{ route('admin.social-calendar.store') }}" method="POST" x-data="{ mediaType: 'image' }">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Left: Content --}}
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Title <span class="text-red-500">*</span></label>
                        <input type="text" name="title" value="{{ old('title') }}" required
                               class="w-full rounded-lg border-gray-300 text-sm focus:ring-gray-900 focus:border-gray-900"
                               placeholder="Internal title (not shown publicly)">
                        @error('title') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div x-data="{ count: 0 }">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Caption <span class="text-red-500">*</span></label>
                        <textarea name="caption" rows="8" required maxlength="2200"
                                  x-on:input="count = $el.value.length"
                                  class="w-full rounded-lg border-gray-300 text-sm focus:ring-gray-900 focus:border-gray-900"
                                  placeholder="Write your post caption here...">{{ old('caption') }}</textarea>
                        <div class="flex justify-between text-xs text-gray-400 mt-1">
                            <span>Tip: Keep under 2200 chars for Instagram</span>
                            <span x-text="count + '/2200'"></span>
                        </div>
                        @error('caption') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Hashtags</label>
                        <input type="text" name="hashtags" value="{{ old('hashtags') }}"
                               class="w-full rounded-lg border-gray-300 text-sm focus:ring-gray-900 focus:border-gray-900"
                               placeholder="#ShopNow #NewArrival (space or comma separated)">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Media Type <span class="text-red-500">*</span></label>
                        <select name="media_type" x-model="mediaType"
                                class="w-full rounded-lg border-gray-300 text-sm focus:ring-gray-900 focus:border-gray-900">
                            <option value="image">Single Image</option>
                            <option value="carousel">Carousel (Multiple Images)</option>
                            <option value="video">Video / Reel</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Media URLs</label>
                        <textarea name="media_urls" rows="4"
                                  class="w-full rounded-lg border-gray-300 text-sm font-mono focus:ring-gray-900 focus:border-gray-900"
                                  placeholder="One URL per line (must be publicly accessible)&#10;https://{{ request()->getHost() }}/images/social/...&#10;https://{{ request()->getHost() }}/videos/...">{{ old('media_urls') }}</textarea>
                        <p class="text-xs text-gray-400 mt-1">Public URLs that Meta API can access. Upload to server first.</p>
                        @error('media_urls') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Product Link</label>
                        <input type="url" name="link" value="{{ old('link') }}"
                               class="w-full rounded-lg border-gray-300 text-sm focus:ring-gray-900 focus:border-gray-900"
                               placeholder="https://{{ request()->getHost() }}/product/...">
                    </div>
                </div>
            </div>

            {{-- Right: Settings --}}
            <div class="space-y-6">
                {{-- Platforms --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                    <h3 class="font-medium text-gray-900 mb-3">Platforms <span class="text-red-500">*</span></h3>
                    <div class="space-y-2">
                        <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Instagram</p>
                        @foreach(['ig_post' => 'Post', 'ig_reel' => 'Reel', 'ig_story' => 'Story'] as $key => $label)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="platforms[]" value="{{ $key }}"
                                   class="rounded border-gray-300 text-pink-600 focus:ring-pink-500"
                                   {{ in_array($key, old('platforms', [])) ? 'checked' : '' }}>
                            {{ $label }}
                        </label>
                        @endforeach

                        <p class="text-xs text-gray-500 font-medium uppercase tracking-wide mt-3">Facebook</p>
                        @foreach(['fb_post' => 'Post', 'fb_reel' => 'Reel', 'fb_story' => 'Story'] as $key => $label)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="platforms[]" value="{{ $key }}"
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                   {{ in_array($key, old('platforms', [])) ? 'checked' : '' }}>
                            {{ $label }}
                        </label>
                        @endforeach
                    </div>
                    @error('platforms') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Product --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                    <h3 class="font-medium text-gray-900 mb-3">Link to Product</h3>
                    <select name="product_id" class="w-full rounded-lg border-gray-300 text-sm focus:ring-gray-900 focus:border-gray-900">
                        <option value="">None</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                                {{ $product->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Schedule --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                    <h3 class="font-medium text-gray-900 mb-3">Schedule</h3>
                    <input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at') }}"
                           class="w-full rounded-lg border-gray-300 text-sm focus:ring-gray-900 focus:border-gray-900">
                    <p class="text-xs text-gray-400 mt-1">Leave empty for drafts. Required for scheduling.</p>

                    <div class="mt-3 text-xs text-gray-500">
                        <p class="font-medium mb-1">Best posting times (IST):</p>
                        <ul class="space-y-0.5">
                            <li>IG Reels: 9-11 AM, 7-9 PM</li>
                            <li>IG Posts: 11 AM-1 PM, 7-9 PM</li>
                            <li>FB Posts: 1-3 PM, 6-8 PM</li>
                            <li>Stories: 8-10 AM, 8-10 PM</li>
                        </ul>
                    </div>
                </div>

                {{-- Actions --}}
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
</x-layouts.admin>
