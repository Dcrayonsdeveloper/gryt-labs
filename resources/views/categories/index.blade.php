<x-layouts.app>
    <x-slot name="title">All Collections - {{ config('app.name') }}</x-slot>

    <!-- Breadcrumb -->
    <div class="bg-white border-b border-neutral-100">
        <div class="container mx-auto px-4 py-2.5">
            <x-breadcrumb :items="[['label' => 'Collections', 'url' => null]]" />
        </div>
    </div>

    <!-- Header -->
    <div class="relative overflow-hidden" style="background: linear-gradient(135deg, var(--color-primary-600) 0%, var(--brand-800) 100%); height: 180px;">
        <div class="absolute inset-0 w-full h-full" style="background: linear-gradient(135deg, var(--color-primary-600) 0%, var(--color-primary-700) 50%, var(--brand-900) 100%); opacity: 0.6;"></div>
        <div class="absolute inset-0 bg-gradient-to-r from-black/50 via-black/20 to-transparent"></div>
        <div class="relative container mx-auto px-4 h-full flex flex-col justify-center">
            <h1 class="text-2xl md:text-3xl font-bold text-white mb-1">Shop by Collection</h1>
            <p class="text-sm" style="color: rgba(255,255,255,0.85);">Browse our wide range of products & accessories</p>
            <p class="text-xs mt-2" style="color: rgba(255,255,255,0.7);">{{ $categories->count() }} collections</p>
        </div>
    </div>

    <div class="container mx-auto px-4 py-6 md:py-8">
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-5">
            @foreach($categories as $category)
                @php
                    // Fallback image: category image > first product image from children > first direct product image
                    $catImage = null;
                    if ($category->image_url) {
                        $catImage = asset('storage/' . $category->image_url);
                    } else {
                        // Try first product from this category
                        $firstProduct = $category->products->first();
                        if ($firstProduct) {
                            $catImage = $firstProduct->primary_image_url;
                        } else {
                            // Try first product from child categories
                            foreach ($category->children as $child) {
                                $childProduct = $child->products()->where('is_active', true)->with('primaryImage')->first();
                                if ($childProduct) {
                                    $catImage = $childProduct->primary_image_url;
                                    break;
                                }
                            }
                        }
                    }
                @endphp
                <a href="{{ route('categories.show', $category->slug) }}" class="group rounded-xl overflow-hidden hover:shadow-md transition-all duration-200">
                    <div class="aspect-[4/3] bg-white overflow-hidden relative rounded-xl">
                        @if($catImage)
                            <img src="{{ $catImage }}"
                                 alt="{{ $category->name }}"
                                 class="w-full h-full object-contain p-2 group-hover:scale-105 transition-transform duration-300"
                                 loading="lazy"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="w-full h-full items-center justify-center bg-gradient-to-br from-primary-600/10 to-primary-600/20 absolute inset-0" style="display: none;">
                                <svg class="w-12 h-12 text-primary-600/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                            </div>
                        @else
                            <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-primary-600/10 to-primary-600/20">
                                <svg class="w-12 h-12 text-primary-600/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                            </div>
                        @endif
                        <div class="absolute bottom-2 right-2">
                            <span class="px-2 py-0.5 bg-black/50 backdrop-blur-sm text-white text-[10px] font-medium rounded-full">{{ $category->total_products_count }} products</span>
                        </div>
                    </div>
                    <div class="p-3">
                        <h3 class="font-semibold text-sm text-neutral-900 group-hover:text-primary-600 transition-colors">
                            {{ $category->name }}
                        </h3>
                        @if($category->children->count())
                            <div class="mt-2 flex flex-wrap gap-1">
                                @foreach($category->children->take(3) as $child)
                                    <span class="text-[11px] text-neutral-600 bg-neutral-50 border border-neutral-100 rounded-full px-2 py-0.5">{{ $child->name }}</span>
                                @endforeach
                                @if($category->children->count() > 3)
                                    <span class="text-[11px] text-primary-600 bg-primary-600/5 border border-primary-600/15 rounded-full px-2 py-0.5">+{{ $category->children->count() - 3 }} more</span>
                                @endif
                            </div>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</x-layouts.app>
