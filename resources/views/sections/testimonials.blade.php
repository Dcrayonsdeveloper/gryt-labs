@php $settings = $settings ?? []; @endphp
@php
    $heading = $settings['heading'] ?? 'What Our Customers Say';
    $source = $settings['source'] ?? 'auto';
    $count = min($settings['count'] ?? 6, 12);

    if ($source === 'manual' && !empty($settings['review_ids'])) {
        $reviews = \App\Models\Review::whereIn('id', $settings['review_ids'])
            ->where('is_approved', true)->where('created_at', '<=', now())
            ->with('product', 'user')->get();
    } else {
        $reviews = \App\Models\Review::where('is_approved', true)
            ->where('created_at', '<=', now())
            ->where('rating', '>=', 4)
            ->with('product', 'user')
            ->latest()->limit($count)->get();
    }
@endphp

@if($reviews->count())
<section class="py-8 sm:py-12 bg-gray-50">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mb-6 text-center">{{ $heading }}</h2>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($reviews as $review)
            <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
                <div class="flex items-center gap-1 mb-3">
                    @for($i = 1; $i <= 5; $i++)
                    <svg class="w-4 h-4 {{ $i <= $review->rating ? 'text-amber-400' : 'text-gray-200' }}" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    @endfor
                </div>
                @if($review->title)
                <p class="font-semibold text-gray-900 text-sm mb-1">{{ $review->title }}</p>
                @endif
                <p class="text-gray-600 text-sm leading-relaxed mb-4 line-clamp-3">{{ $review->content }}</p>
                <div class="flex items-center justify-between border-t border-gray-50 pt-3">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-primary-100 text-primary-700 flex items-center justify-center text-xs font-bold">
                            {{ $review->reviewer_initial }}
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-800">{{ $review->reviewer_name }}</p>
                            @if($review->is_verified_purchase)
                            <p class="text-xs text-green-600">Verified Purchase</p>
                            @endif
                        </div>
                    </div>
                    @if($review->product)
                    <a href="{{ route('products.show', $review->product->slug) }}" class="text-xs text-primary-600 hover:underline truncate max-w-[120px]">
                        {{ $review->product->name }}
                    </a>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif
