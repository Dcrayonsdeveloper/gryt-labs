<x-layouts.admin>
    <x-slot name="title">Edit Review #{{ $review->id }}</x-slot>

    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm text-neutral-600 mb-1">
            <a href="{{ route('admin.reviews.index') }}" class="hover:text-primary-600">Reviews</a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="{{ route('admin.reviews.show', $review) }}" class="hover:text-primary-600">Review #{{ $review->id }}</a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-neutral-900">Edit</span>
        </div>
        <h1 class="text-2xl font-bold text-neutral-900">Edit Review</h1>
    </div>

    <form action="{{ route('admin.reviews.update', $review) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="card">
                    <div class="p-4 border-b border-neutral-200">
                        <h2 class="font-semibold text-neutral-900">Review Content</h2>
                    </div>
                    <div class="p-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-1">Rating *</label>
                            <div class="flex items-center gap-1" x-data="{ rating: {{ old('rating', $review->rating) }} }">
                                @for($i = 1; $i <= 5; $i++)
                                    <button type="button" @click="rating = {{ $i }}"
                                        :class="rating >= {{ $i }} ? 'text-yellow-400' : 'text-neutral-200'"
                                        class="w-8 h-8 focus:outline-none">
                                        <svg fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    </button>
                                @endfor
                                <input type="hidden" name="rating" x-bind:value="rating">
                                <span class="ml-2 text-sm text-neutral-600" x-text="rating + '/5'"></span>
                            </div>
                            @error('rating') <p class="text-sm text-danger-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-1">Reviewer Name</label>
                            <input type="text" name="reviewer_name" value="{{ old('reviewer_name', $review->reviewer_name) }}" class="form-input w-full" placeholder="Customer name">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-1">Title</label>
                            <input type="text" name="title" value="{{ old('title', $review->title) }}" class="form-input w-full" placeholder="Review title">
                            @error('title') <p class="text-sm text-danger-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-1">Content</label>
                            <textarea name="content" rows="5" class="form-input w-full" placeholder="Review content">{{ old('content', $review->content) }}</textarea>
                            @error('content') <p class="text-sm text-danger-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="p-4 border-b border-neutral-200">
                        <h2 class="font-semibold text-neutral-900">Product</h2>
                    </div>
                    <div class="p-4">
                        @if($review->product)
                            <p class="font-medium text-neutral-900">{{ $review->product->name }}</p>
                            <p class="text-sm text-neutral-600">{{ $review->product->sku ?? 'N/A' }}</p>
                        @else
                            <p class="text-neutral-600">Product has been deleted</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="card">
                    <div class="p-4 border-b border-neutral-200">
                        <h2 class="font-semibold text-neutral-900">Status & Flags</h2>
                    </div>
                    <div class="p-4 space-y-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_approved" value="1" {{ old('is_approved', $review->is_approved) ? 'checked' : '' }} class="rounded border-neutral-300 text-primary-600 focus:ring-primary-500">
                            <span class="text-sm font-medium text-neutral-700">Approved</span>
                        </label>

                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $review->is_featured) ? 'checked' : '' }} class="rounded border-neutral-300 text-primary-600 focus:ring-primary-500">
                            <span class="text-sm font-medium text-neutral-700">Featured</span>
                        </label>

                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_verified_purchase" value="1" {{ old('is_verified_purchase', $review->is_verified_purchase) ? 'checked' : '' }} class="rounded border-neutral-300 text-primary-600 focus:ring-primary-500">
                            <span class="text-sm font-medium text-neutral-700">Verified Purchase</span>
                        </label>
                    </div>
                </div>

                <div class="card">
                    <div class="p-4 border-b border-neutral-200">
                        <h2 class="font-semibold text-neutral-900">Info</h2>
                    </div>
                    <div class="p-4 space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-neutral-600">Customer</span>
                            <span class="font-medium">{{ $review->user ? $review->user->first_name . ' ' . $review->user->last_name : 'Guest' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-neutral-600">Submitted</span>
                            <span class="font-medium">{{ $review->created_at->format('M d, Y H:i') }}</span>
                        </div>
                    </div>
                </div>

                <div class="space-y-3">
                    <button type="submit" class="btn btn-primary w-full text-center">Save Changes</button>
                    <a href="{{ route('admin.reviews.show', $review) }}" class="btn btn-secondary w-full text-center">Cancel</a>
                </div>
            </div>
        </div>
    </form>
</x-layouts.admin>
