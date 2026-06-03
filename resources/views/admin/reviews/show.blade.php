<x-layouts.admin>
    <x-slot name="title">Review Details</x-slot>

    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm text-neutral-600 mb-1">
            <a href="{{ route('admin.reviews.index') }}" class="hover:text-primary-600">Reviews</a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-neutral-900">Review #{{ $review->id }}</span>
        </div>
        <h1 class="text-2xl font-bold text-neutral-900">Review Details</h1>
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="card">
                <div class="p-4 border-b border-neutral-200">
                    <h2 class="font-semibold text-neutral-900">Review Content</h2>
                </div>
                <div class="p-4 space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-0.5">
                            @for($i = 1; $i <= 5; $i++)
                                <svg class="w-5 h-5 {{ $i <= $review->rating ? 'text-yellow-400' : 'text-neutral-200' }}" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            @endfor
                        </div>
                        <span class="text-lg font-semibold text-neutral-900">{{ $review->rating }}/5</span>
                    </div>

                    @if($review->title)
                        <div>
                            <label class="block text-sm font-medium text-neutral-600 mb-1">Title</label>
                            <p class="text-neutral-900 font-medium">{{ $review->title }}</p>
                        </div>
                    @endif

                    @if($review->content)
                        <div>
                            <label class="block text-sm font-medium text-neutral-600 mb-1">Content</label>
                            <p class="text-neutral-700">{{ $review->content }}</p>
                        </div>
                    @endif

                    @if($review->pros && count($review->pros))
                        <div>
                            <label class="block text-sm font-medium text-neutral-600 mb-1">Pros</label>
                            <ul class="list-disc list-inside text-sm text-neutral-700 space-y-1">
                                @foreach($review->pros as $pro)
                                    <li>{{ $pro }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if($review->cons && count($review->cons))
                        <div>
                            <label class="block text-sm font-medium text-neutral-600 mb-1">Cons</label>
                            <ul class="list-disc list-inside text-sm text-neutral-700 space-y-1">
                                @foreach($review->cons as $con)
                                    <li>{{ $con }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="p-4 border-b border-neutral-200">
                    <h2 class="font-semibold text-neutral-900">Store Response</h2>
                </div>
                <div class="p-4">
                    @if($review->admin_reply)
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                            <p class="text-sm text-neutral-700">{{ $review->admin_reply }}</p>
                            <p class="text-xs text-neutral-500 mt-2">Replied {{ $review->admin_replied_at?->format('M d, Y H:i') }}</p>
                        </div>
                    @endif

                    <form action="{{ route('admin.reviews.reply', $review) }}" method="POST">
                        @csrf
                        <textarea name="admin_reply" rows="3" class="w-full rounded-lg border-neutral-300 text-sm focus:ring-primary-500 focus:border-primary-500" placeholder="Write a reply to this review...">{{ old('admin_reply', $review->admin_reply) }}</textarea>
                        @error('admin_reply')
                            <p class="text-sm text-danger-600 mt-1">{{ $message }}</p>
                        @enderror
                        <button type="submit" class="btn btn-primary text-sm mt-2">{{ $review->admin_reply ? 'Update Reply' : 'Reply' }}</button>
                    </form>
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
                    <h2 class="font-semibold text-neutral-900">Status</h2>
                </div>
                <div class="p-4 space-y-3">
                    <div>
                        @if($review->status === 'approved')
                            <span class="badge badge-success">Approved</span>
                        @elseif($review->status === 'rejected')
                            <span class="badge badge-error">Rejected</span>
                        @elseif($review->status === 'flagged')
                            <span class="badge badge-warning">Flagged</span>
                        @else
                            <span class="badge badge-info">Pending</span>
                        @endif
                    </div>

                    <div class="flex gap-2 pt-2 border-t border-neutral-100">
                        @if($review->status !== 'approved')
                            <form action="{{ route('admin.reviews.approve', $review) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-primary text-sm px-3 py-1.5">Approve</button>
                            </form>
                        @endif
                        @if($review->status !== 'rejected')
                            <form action="{{ route('admin.reviews.reject', $review) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-secondary text-sm px-3 py-1.5">Reject</button>
                            </form>
                        @endif
                    </div>
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
                        <span class="text-neutral-600">Verified Purchase</span>
                        <span class="font-medium">{{ $review->is_verified_purchase ? 'Yes' : 'No' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-neutral-600">Featured</span>
                        <span class="font-medium">{{ $review->is_featured ? 'Yes' : 'No' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-neutral-600">Helpful</span>
                        <span class="font-medium">{{ $review->helpful_count }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-neutral-600">Unhelpful</span>
                        <span class="font-medium">{{ $review->unhelpful_count }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-neutral-600">Submitted</span>
                        <span class="font-medium">{{ $review->created_at->format('M d, Y H:i') }}</span>
                    </div>
                    @if($review->moderated_at)
                        <div class="flex justify-between">
                            <span class="text-neutral-600">Moderated</span>
                            <span class="font-medium">{{ $review->moderated_at->format('M d, Y H:i') }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="p-4 space-y-3">
                    <a href="{{ route('admin.reviews.edit', $review) }}" class="btn btn-primary w-full text-center">Edit Review</a>
                    <a href="{{ route('admin.reviews.index') }}" class="btn btn-secondary w-full text-center">Back to Reviews</a>
                    <form action="{{ route('admin.reviews.destroy', $review) }}" method="POST" onsubmit="return confirm('Delete this review?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full text-center text-sm font-medium text-danger-600 hover:text-danger-700 py-2">Delete Review</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
