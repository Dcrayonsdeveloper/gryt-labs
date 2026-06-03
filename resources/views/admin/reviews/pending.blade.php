<x-layouts.admin>
    <x-slot name="title">Pending Reviews</x-slot>

    <div class="flex items-center justify-between mb-6">
        <div>
            <div class="flex items-center gap-2 text-sm text-neutral-600 mb-1">
                <a href="{{ route('admin.reviews.index') }}" class="hover:text-primary-600">Reviews</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <span class="text-neutral-900">Pending</span>
            </div>
            <h1 class="text-2xl font-bold text-neutral-900">Pending Reviews</h1>
            <p class="text-neutral-600">Reviews awaiting moderation</p>
        </div>
        <a href="{{ route('admin.reviews.index') }}" class="btn btn-secondary">All Reviews</a>
    </div>
    @if($reviews->total() > 0)
        <div class="card mb-4">
            <div class="px-5 py-3 border-b border-neutral-200">
                {{ $reviews->links('vendor.pagination.info-bar') }}
            </div>
        </div>
    @endif

    <div class="space-y-4">
        @forelse($reviews as $review)
            <div class="card">
                <div class="p-4">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="flex items-center gap-0.5">
                                    @for($i = 1; $i <= 5; $i++)
                                        <svg class="w-4 h-4 {{ $i <= $review->rating ? 'text-yellow-400' : 'text-neutral-200' }}" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    @endfor
                                </div>
                                @if($review->is_verified_purchase)
                                    <span class="badge badge-success">Verified Purchase</span>
                                @endif
                            </div>
                            @if($review->title)
                                <h3 class="font-semibold text-neutral-900 mb-1">{{ $review->title }}</h3>
                            @endif
                            @if($review->content)
                                <p class="text-sm text-neutral-600 mb-2">{{ $review->content }}</p>
                            @endif
                            <div class="flex items-center gap-4 text-xs text-neutral-600">
                                <span>Product: <span class="font-medium text-neutral-700">{{ $review->product->name ?? 'Deleted' }}</span></span>
                                <span>By: <span class="font-medium text-neutral-700">{{ $review->user ? $review->user->first_name . ' ' . $review->user->last_name : 'Guest' }}</span></span>
                                <span>{{ $review->created_at->format('M d, Y H:i') }}</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 ml-4">
                            <form action="{{ route('admin.reviews.approve', $review) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-primary text-sm px-3 py-1.5">Approve</button>
                            </form>
                            <form action="{{ route('admin.reviews.reject', $review) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-secondary text-sm px-3 py-1.5 text-danger-600 hover:text-danger-700">Reject</button>
                            </form>
                            <form action="{{ route('admin.reviews.destroy', $review) }}" method="POST" onsubmit="return confirm('Delete this review?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-danger-600 hover:text-danger-700 text-sm font-medium px-2">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="card">
                <div class="p-12 text-center text-neutral-600">
                    No pending reviews. All caught up!
                </div>
            </div>
        @endforelse
    </div>

    @if($reviews->hasPages())
        <div class="mt-4">
            {{ $reviews->links() }}
        </div>
    @endif
</x-layouts.admin>
