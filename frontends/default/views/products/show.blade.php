<x-layouts.app>
    <x-slot name="title">{{ $product->seo_data['meta_title'] ?? $product->name }} - {{ $theme->get('store_name', config('app.name')) }}</x-slot>

    @push('meta')
        <meta name="description" content="{{ $product->seo_data['meta_description'] ?? Str::limit(strip_tags($product->short_description ?? $product->description), 155) }}">
        <link rel="canonical" href="{{ route('products.show', $product->slug) }}">
        <meta property="og:title" content="{{ $product->seo_data['meta_title'] ?? $product->name }} | {{ $theme->get('store_name', config('app.name')) }}">
        <meta property="og:description" content="{{ $product->seo_data['meta_description'] ?? Str::limit(strip_tags($product->short_description ?? $product->description), 155) }}">
        <meta property="og:image" content="{{ url($product->primary_image_url) }}">
        <meta property="og:type" content="product">
        <meta property="og:url" content="{{ route('products.show', $product->slug) }}">
        <meta property="product:price:amount" content="{{ $product->price }}">
        <meta property="product:price:currency" content="INR">
        <meta property="product:availability" content="{{ $product->isInStock() ? 'in stock' : 'out of stock' }}">
        <meta property="product:condition" content="new">
        <meta property="product:retailer_item_id" content="{{ $product->id }}">
        @if($product->brand)
        <meta property="product:brand" content="{{ $product->brand->name }}">
        @endif
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $product->seo_data['meta_title'] ?? $product->name }} | {{ $theme->get('store_name', config('app.name')) }}">
        <meta name="twitter:description" content="{{ $product->seo_data['meta_description'] ?? Str::limit(strip_tags($product->short_description ?? $product->description), 155) }}">
        <meta name="twitter:image" content="{{ url($product->primary_image_url) }}">

        {{-- JSON-LD Structured Data --}}
        <x-product-schema :productSchema="$productSchema ?? null" :faqSchema="$faqSchema ?? null" :breadcrumbSchema="$breadcrumbSchema ?? null" />
    @endpush

    <!-- Breadcrumb -->
    <div class="bg-white border-b border-[#E3E6E6]">
        <div class="container mx-auto px-4 py-2">
            <x-breadcrumb :items="$breadcrumbs" />
        </div>
    </div>

    @php $certImg = $pdpData['certImage'] ?? ''; @endphp
    <div class="container mx-auto px-4 py-4 lg:py-6">
        <!-- Product Main Section -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 lg:gap-5" id="pdp-main-grid">
            <!-- Image Gallery — Col 1 -->
            <div x-data="{
                    images: @js($product->images->pluck('full_url')->values()),
                    imageAlts: @js($product->images->map(fn($img, $i) => $img->alt_text ?? $product->name . ' - Image ' . ($i + 1))->values()),
                    videoUrl: '{{ $product->video_url ?? '' }}',
                    showingVideo: false,
                    activeIndex: 0,
                    touchStartX: 0,
                    touchEndX: 0,
                    showZoom: false,
                    get activeImage() { return this.images[this.activeIndex] || '{{ $product->primary_image_url }}'; },
                    select(index) {
                        this.showingVideo = false;
                        if (index !== this.activeIndex) {
                            this.activeIndex = index;
                        }
                    },
                    showVideo() { this.showingVideo = true; },
                    next() { this.showingVideo = false; this.activeIndex = (this.activeIndex + 1) % this.images.length; },
                    prev() { this.showingVideo = false; this.activeIndex = (this.activeIndex - 1 + this.images.length) % this.images.length; },
                    handleSwipe() {
                        const diff = this.touchStartX - this.touchEndX;
                        if (Math.abs(diff) > 50) {
                            diff > 0 ? this.next() : this.prev();
                        }
                    }
                 }"
                 class="w-full lg:col-span-5" id="pdp-image-col">

                <div class="space-y-3" id="pdp-image-inner">
                <div class="flex gap-3">
                    <!-- Thumbnails (Desktop vertical with scroll) -->
                    @if($product->images->count() > 1)
                        <div class="hidden lg:block w-16 shrink-0 relative" x-data="{ thumbEl: null }" x-init="thumbEl = $refs.thumbStrip">
                            {{-- Up arrow --}}
                            <button @click="thumbEl.scrollBy({ top: -144, behavior: 'smooth' })"
                                    class="absolute -top-1 left-0 right-0 z-10 flex justify-center py-0.5 bg-linear-to-b from-white via-white/90 to-transparent hover:text-link-hover text-neutral-500 transition-colors"
                                    aria-label="Scroll up">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                            </button>
                            <div x-ref="thumbStrip" class="flex flex-col gap-2 max-h-120 overflow-y-auto scrollbar-hide py-5">
                                @foreach($product->images as $index => $image)
                                    <button @click="select({{ $index }}); $el.scrollIntoView({ behavior: 'smooth', block: 'nearest' })"
                                            class="w-16 h-16 rounded border-2 overflow-hidden shrink-0 transition-all duration-200 cursor-pointer"
                                            :class="activeIndex === {{ $index }} && !showingVideo
                                                ? 'border-link-hover shadow-sm'
                                                : 'border-[#E3E6E6] hover:border-link-hover'">
                                        <img src="{{ $image->full_url }}" alt="{{ $image->alt_text ?? $product->name . ' - Image ' . ($loop->index + 1) }}" class="w-full h-full object-contain" width="64" height="64" loading="lazy" decoding="async">
                                    </button>
                                @endforeach
                                @if($product->video_url)
                                    <button @click="showVideo(); $el.scrollIntoView({ behavior: 'smooth', block: 'nearest' })"
                                            class="w-16 h-16 rounded border-2 overflow-hidden shrink-0 transition-all duration-200 cursor-pointer relative"
                                            :class="showingVideo ? 'border-link-hover shadow-sm' : 'border-[#E3E6E6] hover:border-link-hover'">
                                        <div class="w-full h-full bg-neutral-900 flex items-center justify-center">
                                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                        </div>
                                    </button>
                                @endif
                            </div>
                            {{-- Down arrow --}}
                            <button @click="thumbEl.scrollBy({ top: 144, behavior: 'smooth' })"
                                    class="absolute -bottom-1 left-0 right-0 z-10 flex justify-center py-0.5 bg-linear-to-t from-white via-white/90 to-transparent hover:text-link-hover text-neutral-500 transition-colors"
                                    aria-label="Scroll down">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                        </div>
                    @endif

                    <!-- Main Image / Video -->
                    <div class="relative bg-white rounded-lg overflow-hidden group flex-1 min-w-0"
                         @touchstart="touchStartX = $event.changedTouches[0].screenX"
                         @touchend="touchEndX = $event.changedTouches[0].screenX; handleSwipe()">
                        {{-- Video Player --}}
                        @if($product->video_url)
                        <div x-show="showingVideo" x-cloak class="aspect-[9/16] max-h-130 mx-auto bg-black flex items-center justify-center">
                            <video x-show="showingVideo"
                                   class="w-full h-full object-contain"
                                   controls playsinline
                                   :src="showingVideo ? '{{ str_starts_with($product->video_url, 'http') ? $product->video_url : asset($product->video_url) }}' : ''">
                            </video>
                        </div>
                        @endif

                        <div x-show="!showingVideo && images.length > 0"
                             class="relative overflow-hidden cursor-zoom-in aspect-square lg:max-h-130"
                             x-data="{ zooming: false, zoomX: 50, zoomY: 50 }"
                             @mouseenter="zooming = true"
                             @mouseleave="zooming = false"
                             @mousemove="let r = $el.getBoundingClientRect(); zoomX = ((($event.clientX - r.left) / r.width) * 100); zoomY = ((($event.clientY - r.top) / r.height) * 100)"
                             @click="showZoom = true">
                            <img :src="activeImage"
                                 :alt="imageAlts[activeIndex] || '{{ addslashes($product->name) }}'"
                                 class="w-full h-full object-contain transition-transform duration-200"
                                 width="600" height="600"
                                 fetchpriority="high"
                                 :style="zooming ? 'transform: scale(2); transform-origin: ' + zoomX + '% ' + zoomY + '%' : ''">
                        </div>

                        <div x-show="!showingVideo && images.length === 0" class="flex items-center justify-center py-20">
                            <svg class="w-20 h-20 text-neutral-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>

                        <!-- Image counter -->
                        <template x-if="images.length > 1">
                            <div class="absolute bottom-3 left-3 bg-white/90 text-[#0F1111] text-xs font-medium px-2.5 py-1 rounded shadow-sm border border-[#E3E6E6]" x-text="(activeIndex + 1) + ' / ' + images.length"></div>
                        </template>

                        <!-- Nav Arrows -->
                        <template x-if="images.length > 1">
                            <div>
                                <button @click="prev()" aria-label="Previous image"
                                        class="absolute left-2 top-1/2 -translate-y-1/2 w-9 h-9 bg-white hover:bg-[#F7FAFA] rounded-full flex items-center justify-center shadow  opacity-0 group-hover:opacity-100 transition-opacity">
                                    <svg class="w-4 h-4 text-[#0F1111]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                </button>
                                <button @click="next()" aria-label="Next image"
                                        class="absolute right-2 top-1/2 -translate-y-1/2 w-9 h-9 bg-white hover:bg-[#F7FAFA] rounded-full flex items-center justify-center shadow  opacity-0 group-hover:opacity-100 transition-opacity">
                                    <svg class="w-4 h-4 text-[#0F1111]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Mobile Thumbnails -->
                @if($product->images->count() > 1 || $product->video_url)
                    <div class="flex lg:hidden gap-2 overflow-x-auto pb-1 scrollbar-thin">
                        @foreach($product->images as $index => $image)
                            <button @click="select({{ $index }})"
                                    class="w-14 h-14 rounded border-2 overflow-hidden shrink-0"
                                    :class="activeIndex === {{ $index }} && !showingVideo ? 'border-link-hover' : 'border-[#E3E6E6]'">
                                <img src="{{ $image->full_url }}" alt="{{ $image->alt_text ?? $product->name . ' - Image ' . ($loop->index + 1) }}" class="w-full h-full object-contain" width="56" height="56" loading="lazy" decoding="async">
                            </button>
                        @endforeach
                        @if($product->video_url)
                            <button @click="showVideo()"
                                    class="w-14 h-14 rounded border-2 overflow-hidden shrink-0 relative"
                                    :class="showingVideo ? 'border-link-hover' : 'border-[#E3E6E6]'">
                                <div class="w-full h-full bg-neutral-900 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                </div>
                            </button>
                        @endif
                    </div>
                @endif
                </div>{{-- close sticky wrapper --}}

                <!-- Lightbox Modal -->
                <template x-teleport="body">
                    <div x-show="showZoom" x-cloak
                         @keydown.escape.window="showZoom = false"
                         @keydown.left.window="if(showZoom) prev()"
                         @keydown.right.window="if(showZoom) next()"
                         @click.self="showZoom = false"
                         class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/90"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         @touchstart="if($event.touches.length === 1) touchStartX = $event.changedTouches[0].screenX"
                         @touchend="if($event.changedTouches.length === 1) { touchEndX = $event.changedTouches[0].screenX; handleSwipe() }">
                        <button @click="showZoom = false" class="absolute top-4 right-4 w-10 h-10 bg-white/20 hover:bg-white/30 rounded-full flex items-center justify-center text-white transition-colors z-10" aria-label="Close zoom">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                        <template x-if="images.length > 1">
                            <div>
                                <button @click.stop="prev()" class="absolute left-3 top-1/2 -translate-y-1/2 w-10 h-10 bg-white/10 hover:bg-white/20 rounded-full flex items-center justify-center text-white" aria-label="Previous"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></button>
                                <button @click.stop="next()" class="absolute right-3 top-1/2 -translate-y-1/2 w-10 h-10 bg-white/10 hover:bg-white/20 rounded-full flex items-center justify-center text-white" aria-label="Next"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></button>
                            </div>
                        </template>
                        {{-- Scrollable container for native pinch-to-zoom on mobile --}}
                        <div class="overflow-auto overscroll-contain w-full h-full flex items-center justify-center"
                             style="touch-action: pan-x pan-y pinch-zoom;"
                             @click.self="showZoom = false">
                            <img :src="images[activeIndex] || '{{ $product->primary_image_url }}'"
                                 :alt="imageAlts[activeIndex] || '{{ addslashes($product->name) }}'"
                                 class="max-w-[90vw] max-h-[85vh] object-contain select-none md:pointer-events-none"
                                 style="touch-action: pinch-zoom;"
                                 @click.stop>
                        </div>
                        <template x-if="images.length > 1">
                            <div class="absolute bottom-4 left-1/2 -translate-x-1/2 text-white/70 text-sm" x-text="(activeIndex + 1) + ' / ' + images.length"></div>
                        </template>
                        {{-- Pinch-to-zoom hint on mobile --}}
                        <div class="absolute bottom-10 left-1/2 -translate-x-1/2 text-white/50 text-xs md:hidden pointer-events-none">
                            Pinch to zoom
                        </div>
                    </div>
                </template>
            </div>

            <!-- Product Info + Buy Box — Col 2 (Right, split into 2 sub-columns on xl) -->
            <div id="pdp-info-col" class="mt-4 lg:mt-0 lg:col-span-7">
            <div class="flex flex-col xl:flex-row xl:gap-5">
            <div class="flex-1 min-w-0 space-y-4">
                <!-- Brand -->
                @if($product->brand)
                    <a href="{{ route('brands.show', $product->brand) }}" class="text-sm text-link hover:text-link-hover hover:underline">
                        Visit the {{ $product->brand->name }} Store
                    </a>
                @endif

                <!-- Title + Certifications -->
                <div class="flex items-start justify-between gap-4">
                    <h1 class="text-lg lg:text-xl font-normal text-[#0F1111] leading-snug flex-1">{{ $product->name }}</h1>
                    @if($certImg)
                    <img src="{{ asset($certImg) }}" alt="Certifications" class="h-10 lg:h-12 object-contain shrink-0" loading="lazy">
                    @endif
                </div>

                <!-- Rating Row -->
                <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                    <span class="text-sm text-link">{{ number_format($product->rating, 1) }}</span>
                    <a href="#customer-reviews" class="inline-flex items-center gap-0.5 group">
                        @for($i = 1; $i <= 5; $i++)
                            <svg class="w-4 h-4 {{ $i <= round($product->rating) ? 'text-primary-600' : 'text-[#767676]' }}" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        @endfor
                    </a>
                    <a href="#customer-reviews" class="text-sm text-link hover:text-link-hover hover:underline">{{ number_format($product->review_count) }} ratings</a>
                </div>

                <hr class="border-[#E3E6E6]">

                {{-- Text slider strip (Rasayanam-style) --}}
                @php $__sliderItems = $pdpData['sliderItems'] ?? []; @endphp
                @if(count($__sliderItems))
                {{-- Desktop: continuous horizontal scroll --}}
                <div class="hidden sm:block overflow-hidden rounded-md border" style="background:linear-gradient(#FAF8E5,#F3E9C0);border-color:rgba(0,0,0,0.05);">
                    <div class="pdp-slider-track flex whitespace-nowrap" style="padding:5px 0;">
                        @for($i = 0; $i < 2; $i++)
                        <div class="pdp-slider-content flex shrink-0 items-center">
                            @foreach($__sliderItems as $item)
                            <span class="inline-flex items-center gap-1.5 px-7 text-[13px] font-medium uppercase tracking-[0.5px]" style="color:#3d2314;">
                                <svg class="w-3.5 h-3.5 shrink-0" style="color:#38271f;" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                {{ $item }}
                            </span>
                            @endforeach
                        </div>
                        @endfor
                    </div>
                </div>
                {{-- Mobile: vertical slide, one at a time --}}
                <div class="sm:hidden overflow-hidden rounded-md border" style="background:linear-gradient(#FAF8E5,#F3E9C0);border-color:rgba(0,0,0,0.05);height:28px;">
                    <div class="pdp-slider-mobile flex flex-col" style="height:28px;">
                        @foreach($__sliderItems as $item)
                        <div class="flex items-center justify-center shrink-0" style="height:28px;">
                            <span class="text-[11px] font-medium uppercase tracking-[0.5px]" style="color:#3d2314;">{{ $item }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                <style>
                    .pdp-slider-mobile { animation: pdp-vslide {{ count($__sliderItems) * 4 }}s ease-in-out infinite; }
                    @keyframes pdp-vslide {
                        @php $n = count($__sliderItems); $step = 100 / $n; @endphp
                        @for($i = 0; $i < $n; $i++)
                        {{ round($i * $step) }}%, {{ round($i * $step + $step * 0.75) }}% { transform: translateY(-{{ $i * 28 }}px); }
                        @endfor
                        100% { transform: translateY(0); }
                    }
                </style>
                @endif

                <!-- Price Block + Wishlist/Share -->
                @php $shareUrl = route('products.show', $product); $shareText = $product->name . ' - ' . $shareUrl; @endphp
                <div class="flex items-start justify-between gap-3 sm:gap-4">
                    <div class="space-y-1 flex-1 min-w-0">
                        @php $__dealBadge = $pdpData['dealBadge'] ?? ''; @endphp
                        @php $__badgeColor = $theme->get('badge_color', '') ?: '#CC0C39'; @endphp
                        @if($product->mrp > $product->price && $__dealBadge)
                            <div class="inline-flex items-center gap-1.5 text-white text-xs font-bold px-2.5 py-1 rounded-sm" style="background-color:{{ $__badgeColor }}">
                                {{ $__dealBadge }}
                            </div>
                        @endif
                        <div class="flex items-baseline gap-1.5 sm:gap-2 flex-wrap">
                            @if($product->mrp > $product->price)
                                <span class="text-lg sm:text-xl font-normal" style="color:{{ $__badgeColor }}">-{{ round($product->discount_percentage) }}%</span>
                            @endif
                            <span class="text-2xl sm:text-[28px] font-medium text-[#0F1111]">@price($product->price)</span>
                        </div>
                        @if($product->mrp > $product->price)
                            <div class="text-sm text-[#3a3a3a]">
                                M.R.P.: <span class="line-through">@price($product->mrp)</span>
                            </div>
                        @endif
                        @if($pdpData['taxText'] ?? '')
                            <p class="text-xs text-[#3a3a3a]">{{ $pdpData['taxText'] }}</p>
                        @endif
                        @if($pdpData['loyaltyEnabled'] ?? false)
                            @php $__loyaltyPoints = $pdpData['loyaltyPoints'] ?? 0; @endphp
                            @if($__loyaltyPoints > 0)
                                <p class="text-xs text-[#067D62] flex items-center gap-1 mt-0.5">
                                    <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                    Earn {{ $__loyaltyPoints }} points with this purchase
                                </p>
                            @endif
                        @endif
                    </div>
                    <div class="flex flex-col items-end gap-2 shrink-0">
                        <button @click="$store.wishlist.toggle({{ $product->id }})"
                                class="flex items-center gap-1.5 px-2 sm:px-3 py-1.5 text-sm font-medium rounded-full border transition-colors"
                                :class="$store.wishlist.has({{ $product->id }}) ? 'text-red-500 border-red-200 bg-red-50' : 'text-[#0F1111] border-[#D5D9D9] hover:bg-[#F7FAFA]'"
                                aria-label="Toggle wishlist">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                            <span class="hidden sm:inline" x-text="$store.wishlist.has({{ $product->id }}) ? 'Wishlisted' : 'Wishlist'"></span>
                        </button>
                        <div x-data="{ copied: false }" class="flex items-center gap-2.5">
                            <button @click="if(navigator.share) { navigator.share({title: '{{ addslashes($product->name) }}', url: window.location.href}).catch(()=>{}) } else { navigator.clipboard.writeText(window.location.href); copied = true; setTimeout(() => copied = false, 2000) }"
                                    class="flex items-center gap-1 text-[#3a3a3a] hover:text-[#0F1111]">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                                <span class="text-xs" x-text="copied ? 'Copied!' : 'Share'"></span>
                            </button>
                            <a href="https://wa.me/?text={{ urlencode($shareText) }}" target="_blank" rel="noopener" class="text-[#3a3a3a] hover:text-[#25D366]" aria-label="Share on WhatsApp">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            </a>
                            <button @click="navigator.clipboard.writeText(window.location.href); copied = true; setTimeout(() => copied = false, 2000)" class="text-[#3a3a3a] hover:text-[#0F1111]" aria-label="Copy link">
                                <svg x-show="!copied" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                                <svg x-show="copied" x-cloak class="w-4 h-4 text-[#007600]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Stock Urgency -->
                @php $__lowStockThreshold = (int) ($pdpData['lowStockThreshold'] ?? 5); @endphp
                @if($product->stock_quantity > 0 && $product->stock_quantity <= $__lowStockThreshold)
                <div class="flex items-center gap-1.5 my-2 px-2.5 py-1.5 bg-[#FFF3E0] rounded">
                    <svg class="w-4 h-4 text-[#E65100] shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    <span class="text-sm font-semibold text-red-600">Only {{ $product->stock_quantity }} left in stock!</span>
                </div>
                @elseif($product->stock_quantity > $__lowStockThreshold)
                <p class="text-xs text-[#067D62] font-semibold my-1.5">&#10003; In Stock</p>
                @endif


                {{-- Social Proof --}}
                @if($product->social_proof_text)
                <div class="flex flex-wrap gap-2 my-1.5">
                    <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-primary-600 bg-primary-50 px-2 py-0.5 rounded">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                        {{ $product->social_proof_text }}
                    </span>
                </div>
                @endif

                {{-- Clinical stats carousel --}}
                @php $__stats = $pdpData['statsCarousel'] ?? []; @endphp
                @if(!empty($__stats))
                <div class="space-y-2" x-data="{
                        slide: 0,
                        stats: @js($__stats),
                        init() { this.timer = setInterval(() => { this.slide = (this.slide + 1) % this.stats.length; }, 3500); },
                        destroy() { clearInterval(this.timer); }
                     }">
                    <div class="relative overflow-hidden rounded-lg bg-[#e8f5e9] shadow-sm border border-[#c8e6c9]">
                        <div class="relative h-[72px] sm:h-[88px]">
                            <template x-for="(s, i) in stats" :key="i">
                                <div class="absolute inset-0 flex items-center justify-center px-4 sm:px-5 transition-opacity duration-700 ease-in-out"
                                     :class="slide === i ? 'opacity-100' : 'opacity-0'">
                                    <div class="flex items-baseline gap-2 sm:gap-3 text-[#165a1e]">
                                        <span class="text-3xl sm:text-5xl font-extrabold leading-none tracking-tight" x-text="s.value"></span>
                                        <span class="text-sm sm:text-lg font-semibold leading-none -ml-1 sm:-ml-2" x-text="s.unit"></span>
                                        <span class="text-xs sm:text-base font-medium ml-1 sm:ml-2 leading-tight text-[#333]" x-text="s.label"></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <div class="absolute bottom-2 left-1/2 -translate-x-1/2 flex items-center gap-1.5">
                            <template x-for="(s, i) in stats" :key="'dot-'+i">
                                <button type="button" @click="slide = i"
                                        class="h-1.5 rounded-full transition-all"
                                        :class="slide === i ? 'w-5 bg-[#165a1e]' : 'w-1.5 bg-[#777]'"
                                        aria-label="Show stat"></button>
                            </template>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Available Coupons -->
                @if(($pdpData['showCoupons'] ?? true) && isset($availableCoupons) && $availableCoupons->count())
                <div class="space-y-2.5">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-[#CC0C39]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 5a3 3 0 015-2.236A3 3 0 0114.83 6H16a2 2 0 110 4h-5V9a1 1 0 10-2 0v1H4a2 2 0 110-4h1.17C5.06 5.687 5 5.35 5 5zm4 1V5a1 1 0 10-1 1h1zm2 0a1 1 0 10-1-1v1h1z" clip-rule="evenodd"/><path d="M9 11H3v5a2 2 0 002 2h4v-7zM11 18h4a2 2 0 002-2v-5h-6v7z"/></svg>
                        <span class="text-sm font-bold text-[#0F1111]">Available Offers</span>
                    </div>
                    {{-- Horizontal scroll carousel for offers --}}
                    <div class="flex gap-3 overflow-x-auto pb-1 scrollbar-thin snap-x snap-mandatory">
                        @foreach($availableCoupons as $coupon)
                        <div class="flex items-start gap-3 border border-dashed border-accent-500/60 bg-[#FFFBF2] rounded-lg p-3 cursor-pointer hover:border-accent-500 hover:bg-[#FFF3E0] transition-colors relative shrink-0 snap-start w-[260px] sm:w-[280px]" onclick="navigator.clipboard.writeText('{{ $coupon->code }}'); let t=this.querySelector('.copy-msg'); t.style.display='block'; setTimeout(()=>t.style.display='none',1500);">
                            <div class="shrink-0 bg-[#FFF3E0] border border-dashed border-accent-500 rounded-md px-2.5 py-1.5 text-center min-w-[75px] relative">
                                <span class="text-xs font-bold text-link-hover block">{{ $coupon->code }}</span>
                                <span class="copy-msg" style="display:none; position:absolute; top:-24px; left:50%; transform:translateX(-50%); background:#0F1111; color:#fff; font-size:10px; padding:2px 8px; border-radius:4px; white-space:nowrap;">Copied!</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-[13px] font-bold text-[#0F1111]">
                                    @if($coupon->type === 'percentage')
                                        {{ (int) $coupon->value }}% Off
                                        @if($coupon->max_discount)
                                            (upto ₹{{ number_format($coupon->max_discount) }})
                                        @endif
                                    @else
                                        Flat ₹{{ number_format($coupon->value) }} Off
                                    @endif
                                </p>
                                <p class="text-xs text-[#3a3a3a] leading-snug">
                                    {{ $coupon->description }}
                                    @if($coupon->min_order_amount)
                                        · Min. order ₹{{ number_format($coupon->min_order_amount) }}
                                    @endif
                                </p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Product Benefits grid (per-product first, then tenant fallback) --}}
                @php
                    $benefits = $product->attributes['benefits'] ?? null;
                    if (empty($benefits)) {
                        $benefits = $pdpData['benefits'] ?? [];
                    }
                @endphp
                @if(!empty($benefits))
                <div class="rounded-lg overflow-hidden border border-neutral-200 mb-3">
                    <div class="grid grid-cols-2 divide-x divide-y divide-neutral-100">
                        @foreach($benefits as $benefit)
                        <div class="flex items-start gap-2.5 p-3">
                            <div class="w-9 h-9 rounded-full bg-neutral-100 flex items-center justify-center shrink-0 mt-0.5">
                                <svg class="w-4.5 h-4.5 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    @switch($benefit['icon'] ?? 'check')
                                        @case('molecule')
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                                            @break
                                        @case('herb')
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                                            @break
                                        @case('shield')
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                            @break
                                        @case('energy')
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                            @break
                                        @case('noside')
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            @break
                                        @case('recovery')
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                            @break
                                        @default
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"/>
                                    @endswitch
                                </svg>
                            </div>
                            <span class="text-sm font-medium text-neutral-700 leading-snug">{{ $benefit['text'] }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- About this item (only when benefits grid not shown) -->
                @if($product->short_description && empty($benefits))
                    <div>
                        <h3 class="text-sm font-bold text-[#0F1111] mb-2">About this item</h3>
                        <ul class="space-y-1.5 text-sm text-[#333] list-disc pl-4">
                            @foreach(preg_split('/[\.\n]+/', html_entity_decode(strip_tags($product->short_description)), -1, PREG_SPLIT_NO_EMPTY) as $point)
                                @if(strlen(trim($point)) > 3)
                                    <li class="leading-relaxed">{{ trim($point) }}</li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Attributes shown in Product Details table below description --}}

            </div>{{-- close product details sub-col --}}

                <!-- Buy Box — Amazon-style right sidebar -->
                <div class="xl:w-60 xl:shrink-0 mt-4 xl:mt-0">
                <div x-data="quantitySelector()" class="border border-[#D5D9D9] rounded-lg bg-white xl:sticky xl:top-20">

                    <div class="p-4 space-y-3">
                        @php
                            $hasBundle = $product->hasPackOffer();
                            $bundleTiers = $hasBundle ? collect($product->packTiers(4))->map(function ($t) {
                                $t['badge'] = [2 => 'BEST VALUE'][$t['qty']] ?? null;
                                return $t;
                            })->values()->all() : [];
                        @endphp

                        @if($hasBundle)
                        {{-- Bundle offer: Amazon-style pack selector that drives `quantity` --}}
                        <div x-data="{ tiers: {{ \Illuminate\Support\Js::from($bundleTiers) }}, cur() { return this.tiers.find(t => t.qty == quantity) || null; } }" x-init="quantity = 1" class="space-y-2">
                            <div class="flex items-baseline gap-2">
                                <span class="text-2xl font-bold text-[#0F1111]" x-text="'₹' + (cur()?.total ?? ({{ (int) $product->price }} * quantity)).toLocaleString('en-IN')"></span>
                                <template x-if="cur() && cur().mrp > cur().total">
                                    <span class="text-sm text-neutral-400 line-through" x-text="'₹' + cur().mrp.toLocaleString('en-IN')"></span>
                                </template>
                                <template x-if="cur() && cur().savingsPct > 0">
                                    <span class="text-xs font-bold text-[#B12704]" x-text="'-' + cur().savingsPct + '%'"></span>
                                </template>
                            </div>
                            <p class="text-[13px] font-bold text-[#0F1111]">Choose your pack &amp; save more</p>
                            <div class="flex gap-2 overflow-x-auto pb-1 -mx-0.5 px-0.5">
                                <template x-for="t in tiers" :key="t.qty">
                                    <button type="button" @click="quantity = t.qty"
                                            class="relative shrink-0 w-[100px] text-left rounded-lg border-2 p-2 pt-3 transition-colors"
                                            :class="quantity == t.qty ? 'border-[#E77600] bg-[#FFF7ED]' : 'border-[#D5D9D9] hover:border-neutral-400'">
                                        <template x-if="t.badge">
                                            <span class="absolute -top-2 left-1/2 -translate-x-1/2 text-[8px] font-bold text-white bg-[#067D62] px-1.5 py-0.5 rounded whitespace-nowrap" x-text="t.badge"></span>
                                        </template>
                                        <img src="{{ $product->primary_image_url }}" alt="" class="w-full h-11 object-contain mb-1">
                                        <div class="text-[11px] font-semibold text-[#565959]" x-text="t.qty === 1 ? 'Buy 1' : 'Buy ' + t.qty"></div>
                                        <div class="text-[13px] font-bold text-[#0F1111]" x-text="'₹' + t.total.toLocaleString('en-IN')"></div>
                                        <div class="text-[10px] text-neutral-400 line-through leading-tight" x-show="t.mrp > t.total" x-text="'₹' + t.mrp.toLocaleString('en-IN')"></div>
                                    </button>
                                </template>
                            </div>
                        </div>
                        @else
                        {{-- Price --}}
                        <div class="text-2xl font-bold text-[#0F1111]">@price($product->price)</div>
                        @endif


                        {{-- Stock --}}
                        @if($product->stock_quantity > 0)
                            <div class="text-sm font-medium text-[#007600]">In stock</div>
                        @else
                            <div class="text-sm font-medium text-[#B12704]">Currently unavailable</div>
                        @endif

                        {{-- Qty + Buttons --}}
                        @if($product->stock_quantity > 0)
                        @unless($hasBundle)
                        <div class="flex items-center gap-2">
                            <label class="text-sm text-[#0F1111]">Qty:</label>
                            <select x-model="quantity" class="border border-[#D5D9D9] rounded-lg bg-[#F0F2F2] text-sm py-1.5 px-3 shadow-sm">
                                @for($i = 1; $i <= min($product->stock_quantity, 10); $i++)
                                    <option value="{{ $i }}">{{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                        @endunless

                        <div class="flex flex-col gap-2" data-sticky-trigger>
                            <button x-data="{ adding: false, added: false }"
                                    @click="if(adding) return; adding = true; const _qty = quantity; await $store.cart.add({{ $product->id }}, _qty); adding = false; added = true; setTimeout(() => added = false, 2000)"
                                    :disabled="adding"
                                    class="w-full flex items-center justify-center gap-2 bg-black hover:bg-neutral-800 text-white font-semibold py-2.5 rounded-full text-sm transition-colors disabled:opacity-70">
                                <span x-text="adding ? 'Adding...' : (added ? '✓ Added!' : 'Add to Cart')"></span>
                            </button>
                            <button x-data="{ buying: false }"
                                    @click="if (typeof window.__srBuyNow === 'function') { __srBuyNow($event, $el, {{ $product->id }}, quantity); return; } if(buying) return; buying = true; const _qty = quantity; axios.post('/cart/add', { product_id: {{ $product->id }}, quantity: _qty }).then(() => { window.location.href = '{{ route('checkout.index') }}'; }).catch(e => { $store.toast.error(e.response?.data?.error || 'Failed'); buying = false; })"
                                    :disabled="buying"
                                    class="w-full flex items-center justify-center gap-2 bg-[#FFD814] hover:bg-[#F7CA00] text-[#0F1111] font-semibold py-2.5 rounded-full text-sm transition-colors disabled:opacity-70">
                                <span x-text="buying ? 'Please wait...' : 'Buy Now'"></span>
                            </button>
                        </div>
                        @else
                            <div x-data="{ email: '{{ auth()->user()?->email ?? '' }}', submitting: false, submitted: false, error: '', async submit() { if (!this.email) { this.error = 'Enter email'; return; } this.submitting = true; this.error = ''; try { const r = await axios.post('{{ route('product.notify-stock', $product) }}', { email: this.email }); this.submitted = true; $store.toast.success(r.data.message || 'We\'ll notify you!'); } catch (e) { this.error = e.response?.data?.message || 'Failed'; } finally { this.submitting = false; } } }" class="space-y-2">
                                <template x-if="!submitted"><div class="flex gap-2"><input type="email" x-model="email" placeholder="Email" @keydown.enter.prevent="submit()" class="flex-1 border border-[#D5D9D9] rounded-lg text-sm py-2 px-3" /><button @click="submit()" :disabled="submitting" class="shrink-0 bg-accent-500 text-white font-semibold py-2 px-4 rounded-lg text-sm"><span x-text="submitting ? '...' : 'Notify Me'"></span></button></div></template>
                                <template x-if="submitted"><p class="text-sm text-[#007600] font-medium">We'll email you when back in stock.</p></template>
                                <p x-show="error" x-text="error" class="text-xs text-red-600"></p>
                            </div>
                        @endif

                        {{-- Secure transaction --}}
                        <div class="flex items-center gap-1.5 text-xs text-[#067D62]">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            <span class="font-medium">Secure transaction</span>
                        </div>

                        {{-- Seller info (Amazon 2-column style) --}}
                        <div class="text-xs text-[#565959] space-y-1">
                            <div class="flex justify-between"><span>Ships from</span><span class="text-link">{{ $theme->get('store_name', config('app.name')) }}</span></div>
                            <div class="flex justify-between"><span>Sold by</span><span class="text-link">{{ $theme->get('store_name', config('app.name')) }}</span></div>
                        </div>
                    </div>

                    {{-- Trust badges --}}
                    <div class="grid grid-cols-2 gap-x-3 gap-y-1 px-4 py-3 border-t border-[#E3E6E6]">
                        <div class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg><span class="text-[11px] text-[#0F1111]">Free Delivery</span></div>
                        <div class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg><span class="text-[11px] text-[#0F1111]">Pay on Delivery</span></div>
                        <div class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg><span class="text-[11px] text-[#0F1111]">7 Days Return</span></div>
                        <div class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg><span class="text-[11px] text-[#0F1111]">Secure Payment</span></div>
                    </div>

                    {{-- Wishlist + Share --}}
                    <div class="px-4 py-3 border-t border-[#E3E6E6] space-y-2">
                        <button @click="$store.wishlist.toggle({{ $product->id }})"
                                class="w-full flex items-center justify-center gap-2 py-2 border border-[#D5D9D9] rounded-full text-sm text-[#0F1111] hover:bg-[#F7FAFA] transition-colors">
                            <svg class="w-4 h-4" :class="$store.wishlist.has({{ $product->id }}) ? 'text-red-500 fill-red-500' : 'text-neutral-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                            <span x-text="$store.wishlist.has({{ $product->id }}) ? 'Remove from Wishlist' : 'Add to Wish List'">Add to Wish List</span>
                        </button>
                    </div>
                </div>
                </div>{{-- close buy box sidebar --}}
            </div>{{-- close flex row --}}
            </div>{{-- close info+buy col --}}
        </div>{{-- close grid layout --}}

        <!-- Product Description / Details / Information — Tabbed -->
        @php
            $hasDescription = !empty($product->description);
            $hasAttributes = is_array($product->attributes) && count($product->attributes) > 0;
            $hasInfo = $product->sku || $product->weight || $product->dimensions || $product->brand || (is_array($product->specifications) && count($product->specifications));
            $aplusPath = 'images/aplus/' . $product->slug;
            $hasAplus = file_exists(public_path($aplusPath . '/hero.jpg'));
            $defaultTab = $hasDescription ? 'description' : ($hasAttributes ? 'details' : 'information');
        @endphp

        @if($hasDescription || $hasAttributes || $hasInfo)
        <section class="mt-8">

            {{-- Product Description --}}
            @if($hasDescription)
            <div class="border-t border-[#E3E6E6] pt-6 mb-8">
                <h2 class="text-lg font-bold text-[#0F1111] mb-4">Product Description</h2>
                <div class="pdp-desc">
                    {!! \App\Helpers\HtmlSanitizer::clean(html_entity_decode($product->description)) !!}
                </div>

                {{-- A+ Content (inside description tab) --}}
                @if($hasAplus)
                <div class="mt-8">
                    {{-- A+ Hero Banner --}}
                <div class="rounded-xl overflow-hidden mb-6">
                    <img src="{{ asset($aplusPath . '/hero.jpg') }}" alt="{{ $product->name }} - Premium Quality" class="w-full h-auto" loading="lazy">
                </div>

                {{-- A+ Features Grid --}}
                @if(file_exists(public_path($aplusPath . '/features.jpg')))
                <div class="rounded-xl overflow-hidden mb-6">
                    <img src="{{ asset($aplusPath . '/features.jpg') }}" alt="{{ $product->name }} - Key Features" class="w-full h-auto" loading="lazy">
                </div>
                @endif

                {{-- A+ Content: use product description rich text for highlights --}}

                {{-- A+ Brand Story --}}
                @if(file_exists(public_path($aplusPath . '/brand-story.jpg')))
                <div class="rounded-xl overflow-hidden mb-6">
                    <img src="{{ asset($aplusPath . '/brand-story.jpg') }}" alt="{{ $product->name }} - Brand Story" class="w-full h-auto" loading="lazy">
                </div>
                @endif

                {{-- A+ Lifestyle --}}
                @if(file_exists(public_path($aplusPath . '/lifestyle.jpg')))
                <div class="rounded-xl overflow-hidden mb-6">
                    <img src="{{ asset($aplusPath . '/lifestyle.jpg') }}" alt="{{ $product->name }} - Perfect for Office, Yoga & Gym" class="w-full h-auto" loading="lazy">
                </div>
                @endif

                </div>
                @endif
            </div>
            @endif

            {{-- Product Details (attributes) --}}
            @if($hasAttributes)
            <div class="border-t border-[#E3E6E6] pt-6 mb-8">
                <h2 class="text-lg font-bold text-[#0F1111] mb-4">Product Details</h2>
                <div class="border border-neutral-200 rounded-lg overflow-hidden">
                    <table class="w-full text-sm">
                        @foreach($product->attributes as $attrKey => $attrValue)
                        @if(!empty($attrValue) && is_string($attrValue))
                        <tr class="{{ $loop->even ? 'bg-neutral-50' : 'bg-white' }}">
                            <td class="px-4 py-2.5 font-medium text-neutral-600 w-2/5 border-b border-neutral-100">{{ $attrKey }}</td>
                            <td class="px-4 py-2.5 text-[#0F1111] border-b border-neutral-100">{{ $attrValue }}</td>
                        </tr>
                        @endif
                        @endforeach
                    </table>
                </div>
            </div>
            @endif

            {{-- Product Information (specs) --}}
            @if($hasInfo)
            <div class="border-t border-[#E3E6E6] pt-6 mb-8">
                <h2 class="text-lg font-bold text-[#0F1111] mb-4">Product Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-0">
                    @if($product->sku)
                        <div class="flex border-b border-[#E3E6E6] py-2.5">
                            <span class="w-1/3 text-sm text-[#3a3a3a] font-medium">SKU</span>
                            <span class="w-2/3 text-sm text-[#0F1111]">{{ $product->sku }}</span>
                        </div>
                    @endif
                    @if($product->brand)
                        <div class="flex border-b border-[#E3E6E6] py-2.5">
                            <span class="w-1/3 text-sm text-[#3a3a3a] font-medium">Brand</span>
                            <span class="w-2/3 text-sm text-[#0F1111]">{{ $product->brand->name }}</span>
                        </div>
                    @endif
                    @if($product->weight)
                        <div class="flex border-b border-[#E3E6E6] py-2.5">
                            <span class="w-1/3 text-sm text-[#3a3a3a] font-medium">Weight</span>
                            <span class="w-2/3 text-sm text-[#0F1111]">{{ $product->weight }} kg</span>
                        </div>
                    @endif
                    @if($product->dimensions)
                        <div class="flex border-b border-[#E3E6E6] py-2.5">
                            <span class="w-1/3 text-sm text-[#3a3a3a] font-medium">Dimensions</span>
                            <span class="w-2/3 text-sm text-[#0F1111]">{{ $product->dimensions }}</span>
                        </div>
                    @endif
                    @if($product->category)
                        <div class="flex border-b border-[#E3E6E6] py-2.5">
                            <span class="w-1/3 text-sm text-[#3a3a3a] font-medium">Category</span>
                            <span class="w-2/3 text-sm text-[#0F1111]">{{ $product->category->name }}</span>
                        </div>
                    @endif
                    @if(is_array($product->specifications) && count($product->specifications))
                        @foreach($product->specifications as $specName => $specValue)
                            <div class="flex border-b border-[#E3E6E6] py-2.5">
                                <span class="w-1/3 text-sm text-[#3a3a3a] font-medium">{{ $specName }}</span>
                                <span class="w-2/3 text-sm text-[#0F1111]">{{ $specValue }}</span>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
            @endif

            {{-- FAQ section --}}
            <div class="border-t border-[#E3E6E6] pt-6 mb-8">
                <x-faq-section :product="$product" class="!py-0 !bg-transparent" />
            </div>
        </section>
        @endif

        <!-- Frequently Bought Together -->
        @if(isset($frequentlyBought) && $frequentlyBought->count())
            <section class="mt-8 border-t border-[#E3E6E6] pt-6" x-data="frequentlyBought()">
                <h2 class="text-lg font-bold text-[#0F1111] mb-4">Frequently Bought Together</h2>
                <div class="flex flex-col lg:flex-row gap-6">
                    {{-- Products with checkboxes --}}
                    <div class="flex items-center gap-2 overflow-x-auto pb-2 sm:pb-0 sm:flex-wrap scrollbar-thin">
                        {{-- Current product (always selected) --}}
                        <div class="flex flex-col items-center w-[110px] sm:w-[130px] shrink-0">
                            <div class="relative">
                                <img src="{{ $product->primary_image_url }}" alt="{{ $product->name }}" class="w-25 h-25 object-contain rounded border border-[#E3E6E6] bg-white p-1">
                                <span class="absolute -top-1 -left-1 w-5 h-5 bg-primary-600 rounded flex items-center justify-center">
                                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                </span>
                            </div>
                            <p class="text-[10px] text-[#0F1111] text-center mt-1.5 line-clamp-2 leading-tight">{{ Str::limit($product->name, 40) }}</p>
                            <p class="text-xs font-bold text-[#0F1111] mt-0.5">@price($product->price)</p>
                        </div>

                        @foreach($frequentlyBought as $idx => $fbProduct)
                            <span class="text-xl text-[#3a3a3a] font-light mx-1">+</span>
                            <div class="flex flex-col items-center w-[110px] sm:w-[130px] shrink-0">
                                <label class="relative cursor-pointer">
                                    <input type="checkbox" class="sr-only peer" checked
                                           x-model="selected" value="{{ $fbProduct->id }}"
                                           @change="recalculate()">
                                    <img src="{{ $fbProduct->primary_image_url }}" alt="{{ $fbProduct->name }}"
                                         class="w-25 h-25 object-contain rounded border-2 bg-white p-1 transition-all peer-checked:border-primary-600 border-[#E3E6E6] opacity-60 peer-checked:opacity-100">
                                    <span class="absolute -top-1 -left-1 w-5 h-5 rounded flex items-center justify-center transition-colors peer-checked:bg-primary-600 bg-[#D5D9D9]">
                                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    </span>
                                </label>
                                <a href="{{ route('products.show', $fbProduct) }}" class="text-[10px] text-link hover:text-link-hover text-center mt-1.5 line-clamp-2 leading-tight hover:underline">{{ Str::limit($fbProduct->name, 40) }}</a>
                                <p class="text-xs font-bold text-[#0F1111] mt-0.5">@price($fbProduct->price)</p>
                            </div>
                        @endforeach
                    </div>

                    {{-- Total + Add All button --}}
                    <div class="flex flex-col justify-center items-start lg:items-center gap-3 lg:min-w-50 lg:border-l lg:border-[#E3E6E6] lg:pl-6">
                        <div>
                            <p class="text-sm text-[#3a3a3a]">Total price for selected items:</p>
                            <p class="text-xl font-bold text-[#0F1111]" x-text="'₹' + totalPrice.toFixed(2)"></p>
                            @php
                                $fbtTotalMrp = $product->mrp + $frequentlyBought->sum('mrp');
                                $fbtTotalPrice = $product->price + $frequentlyBought->sum('price');
                            @endphp
                            <template x-if="totalSaving > 0">
                                <p class="text-xs text-green-700 font-semibold" x-text="'You save ₹' + totalSaving.toFixed(2)"></p>
                            </template>
                        </div>
                        <button @click="addAllToCart()" :disabled="adding"
                                class="bg-black hover:bg-neutral-800 text-white font-medium py-2.5 px-6 rounded-full text-sm transition-colors shadow-sm disabled:opacity-60">
                            <span x-text="adding ? 'Adding...' : 'Add all to Cart'"></span>
                        </button>
                    </div>
                </div>
            </section>

            <script>
                function frequentlyBought() {
                    const products = @json($frequentlyBought->map(fn($p) => ['id' => $p->id, 'price' => (float)$p->price, 'mrp' => (float)$p->mrp]));
                    const mainPrice = {{ (float) $product->price }};
                    const mainMrp = {{ (float) $product->mrp }};
                    return {
                        selected: products.map(p => String(p.id)),
                        adding: false,
                        get totalPrice() {
                            let total = mainPrice;
                            for (const p of products) {
                                if (this.selected.includes(String(p.id))) total += p.price;
                            }
                            return total;
                        },
                        get totalSaving() {
                            let mrpTotal = mainMrp;
                            let priceTotal = mainPrice;
                            for (const p of products) {
                                if (this.selected.includes(String(p.id))) {
                                    mrpTotal += p.mrp;
                                    priceTotal += p.price;
                                }
                            }
                            return mrpTotal - priceTotal;
                        },
                        recalculate() { /* reactivity handled by Alpine getters */ },
                        async addAllToCart() {
                            this.adding = true;
                            try {
                                await Alpine.store('cart').add({{ $product->id }});
                                for (const id of this.selected) {
                                    await Alpine.store('cart').add(parseInt(id));
                                }
                            } catch (e) { console.error(e); }
                            this.adding = false;
                        }
                    };
                }
            </script>
        @endif

        <!-- Compare with similar items -->
        @if(isset($compareProducts) && $compareProducts->count() && ($pdpData['showCompare'] ?? true))
            <section class="mt-8 border-t border-[#E3E6E6] pt-6">
                <h2 class="text-lg font-bold text-[#0F1111] mb-4">Compare with similar items</h2>
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse min-w-120">
                        <thead>
                            <tr>
                                <td class="p-3 w-32"></td>
                                <td class="p-3 text-center border-l border-[#E3E6E6]">
                                    <a href="{{ route('products.show', $product) }}" class="block">
                                        <img src="{{ $product->primary_image_url }}" alt="{{ $product->name }}" class="w-24 h-24 object-contain mx-auto mb-2">
                                        <p class="text-xs text-link line-clamp-3 hover:text-link-hover">{{ Str::limit($product->name, 80) }}</p>
                                    </a>
                                </td>
                                @foreach($compareProducts as $cp)
                                    <td class="p-3 text-center border-l border-[#E3E6E6]">
                                        <a href="{{ route('products.show', $cp) }}" class="block">
                                            <img src="{{ $cp->primary_image_url }}" alt="{{ $cp->name }}" class="w-24 h-24 object-contain mx-auto mb-2">
                                            <p class="text-xs text-link line-clamp-3 hover:text-link-hover">{{ Str::limit($cp->name, 80) }}</p>
                                        </a>
                                    </td>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-t border-[#E3E6E6]">
                                <td class="p-3 text-sm font-medium text-[#0F1111]">Customer Rating</td>
                                <td class="p-3 text-center border-l border-[#E3E6E6]">
                                    <div class="flex items-center justify-center gap-1">
                                        <span class="text-sm">{{ number_format($product->rating, 1) }}</span>
                                        <svg class="w-4 h-4 text-primary-600" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                    </div>
                                    <p class="text-xs text-link">{{ $product->review_count }}</p>
                                </td>
                                @foreach($compareProducts as $cp)
                                    <td class="p-3 text-center border-l border-[#E3E6E6]">
                                        <div class="flex items-center justify-center gap-1">
                                            <span class="text-sm">{{ number_format($cp->rating ?? 0, 1) }}</span>
                                            <svg class="w-4 h-4 text-primary-600" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                        </div>
                                        <p class="text-xs text-link">{{ $cp->review_count ?? 0 }}</p>
                                    </td>
                                @endforeach
                            </tr>
                            <tr class="border-t border-[#E3E6E6]">
                                <td class="p-3 text-sm font-medium text-[#0F1111]">Price</td>
                                <td class="p-3 text-center border-l border-[#E3E6E6] text-sm font-medium text-[#0F1111]">@price($product->price)</td>
                                @foreach($compareProducts as $cp)
                                    <td class="p-3 text-center border-l border-[#E3E6E6] text-sm font-medium text-[#0F1111]">@price($cp->price)</td>
                                @endforeach
                            </tr>
                            <tr class="border-t border-[#E3E6E6]">
                                <td class="p-3 text-sm font-medium text-[#0F1111]">Brand</td>
                                <td class="p-3 text-center border-l border-[#E3E6E6] text-sm text-[#0F1111]">{{ $product->brand?->name ?? '-' }}</td>
                                @foreach($compareProducts as $cp)
                                    <td class="p-3 text-center border-l border-[#E3E6E6] text-sm text-[#0F1111]">{{ $cp->brand?->name ?? '-' }}</td>
                                @endforeach
                            </tr>
                            <tr class="border-t border-[#E3E6E6]">
                                <td class="p-3 text-sm font-medium text-[#0F1111]">Availability</td>
                                <td class="p-3 text-center border-l border-[#E3E6E6] text-sm {{ $product->stock_quantity > 0 ? 'text-[#007600]' : 'text-[#B12704]' }}">{{ $product->stock_quantity > 0 ? 'In Stock' : 'Out of Stock' }}</td>
                                @foreach($compareProducts as $cp)
                                    <td class="p-3 text-center border-l border-[#E3E6E6] text-sm {{ $cp->stock_quantity > 0 ? 'text-[#007600]' : 'text-[#B12704]' }}">{{ $cp->stock_quantity > 0 ? 'In Stock' : 'Out of Stock' }}</td>
                                @endforeach
                            </tr>
                            <tr class="border-t border-[#E3E6E6] bg-[#F7F8FA]">
                                <td class="p-3"></td>
                                <td class="p-3 text-center border-l border-[#E3E6E6] text-sm font-medium text-[#0F1111] italic">This product</td>
                                @foreach($compareProducts as $cp)
                                    <td class="p-3 text-center border-l border-[#E3E6E6]">
                                        <a href="{{ route('products.show', $cp) }}" class="text-sm text-link hover:text-link-hover hover:underline">View details →</a>
                                    </td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        <!-- Customer Testimonial Videos (product-specific, then global fallback) -->
        @php
            $pdpTestimonialVideos = $product->testimonial_videos ?? [];
            if (empty($pdpTestimonialVideos)) {
                $globalVids = $theme->get('testimonial_videos', '');
                $pdpTestimonialVideos = $globalVids ? json_decode($globalVids, true) : [];
            }
        @endphp
        @if(count($pdpTestimonialVideos))
            <section class="mt-10 border-t border-[#E3E6E6] pt-8">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-lg sm:text-xl font-bold text-[#0F1111]">Customer Testimonials</h2>
                    <span class="text-xs text-neutral-500">Real results from real customers</span>
                </div>
                <div class="flex gap-4 overflow-x-auto pb-2" style="-ms-overflow-style:none;scrollbar-width:none;">
                    @foreach($pdpTestimonialVideos as $vid)
                        <div class="relative rounded-xl overflow-hidden bg-black shadow-sm ring-1 ring-neutral-200 shrink-0" style="width:200px;aspect-ratio:9/16;">
                            <video
                                src="{{ Str::startsWith($vid, 'http') ? $vid : asset('images/' . $vid) }}"
                                class="w-full h-full object-cover"
                                controls
                                playsinline
                                preload="metadata"
                                muted
                            ></video>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        <!-- Customer Reviews Section -->
        <section class="mt-8 border-t border-[#E3E6E6] pt-6" id="customer-reviews">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                <!-- Left: Rating Summary -->
                <div class="lg:col-span-4">
                    <h2 class="text-lg font-bold text-[#0F1111] mb-3">Customer Reviews</h2>
                    <div class="flex items-center gap-2 mb-2">
                        <div class="flex items-center">
                            @for($i = 1; $i <= 5; $i++)
                                <svg class="w-5 h-5 {{ $i <= round($product->rating) ? 'text-primary-600' : 'text-[#767676]' }}" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            @endfor
                        </div>
                        <span class="text-sm text-[#0F1111]">{{ number_format($product->rating, 1) }} out of 5</span>
                    </div>
                    <p class="text-sm text-[#3a3a3a] mb-4">{{ number_format($product->review_count) }} global ratings</p>

                    <!-- Rating Bars -->
                    @php $totalReviews = max($product->review_count, 1); @endphp
                    <div class="space-y-1.5">
                        @for($star = 5; $star >= 1; $star--)
                            @php $pct = $totalReviews > 0 ? round(($ratingDistribution[$star] / $totalReviews) * 100) : 0; @endphp
                            <div class="flex items-center gap-2">
                                <a href="#" class="text-sm text-link hover:underline whitespace-nowrap w-14">{{ $star }} star</a>
                                <div class="flex-1 h-5 bg-[#F0F2F2] rounded-sm overflow-hidden">
                                    <div class="h-full bg-[#FFA41C] rounded-sm" style="width: {{ $pct }}%"></div>
                                </div>
                                <span class="text-sm text-link w-10 text-right">{{ $pct }}%</span>
                            </div>
                        @endfor
                    </div>

                    <hr class="border-[#E3E6E6] my-4">

                    <!-- Write a Review CTA -->
                    <h3 class="text-base font-bold text-[#0F1111] mb-1">Review this product</h3>
                    <p class="text-sm text-[#3a3a3a] mb-3">Share your thoughts with other customers</p>
                    @auth
                        <a href="{{ route('account.reviews.create', $product) }}"
                           class="block w-full text-center py-1.5 text-sm font-medium text-[#0F1111] bg-white rounded-full hover:bg-[#F7FAFA] shadow-sm transition-colors">
                            Write a customer review
                        </a>
                    @else
                        <div x-data="{ showForm: false, submitted: false, submitting: false, errorMsg: '', rating: 0, hover: 0 }">
                            <template x-if="submitted">
                                <div class="mt-4 p-4 bg-green-50 border border-green-200 rounded-lg text-center">
                                    <svg class="w-10 h-10 text-green-500 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <p class="text-sm font-semibold text-green-800">Thank you for submitting your review!</p>
                                    <p class="text-xs text-green-600 mt-1">Your review will appear after moderation.</p>
                                </div>
                            </template>

                            <template x-if="!submitted">
                                <div>
                                    <button @click="showForm = !showForm"
                                            class="w-full text-center py-1.5 text-sm font-medium text-[#0F1111] bg-white rounded-full hover:bg-[#F7FAFA] shadow-sm transition-colors">
                                        Write a customer review
                                    </button>

                                    <form x-show="showForm" x-cloak @submit.prevent="
                                        if (!rating) { errorMsg = 'Please select a rating'; return; }
                                        errorMsg = '';
                                        submitting = true;
                                        const fd = new FormData($el);
                                        fd.set('rating', rating);
                                        fd.delete('_token');
                                        fetch($el.action, {
                                            method: 'POST',
                                            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                                            credentials: 'same-origin',
                                            body: fd
                                        })
                                        .then(r => r.json().then(d => ({ok: r.ok, data: d})))
                                        .then(({ok, data}) => {
                                            submitting = false;
                                            if (ok || data.success) { submitted = true; }
                                            else { errorMsg = data.message || Object.values(data.errors || {}).flat()[0] || 'Something went wrong'; }
                                        })
                                        .catch(() => { submitting = false; errorMsg = 'Something went wrong. Please try again.'; })
                                    " action="{{ route('product.guest-review', $product) }}" class="mt-4 space-y-3 bg-[#F7F8FA] rounded-lg p-4 border border-[#E3E6E6]">
                                        @csrf
                                        <input type="text" name="honeypot" class="hidden" value="" tabindex="-1" autocomplete="off">

                                        <div>
                                            <label class="block text-sm font-medium text-[#0F1111] mb-1">Your Name</label>
                                            <input type="text" name="guest_name" required class="w-full rounded-lg px-3 py-2 text-sm focus:ring-primary-600 focus:border-link" placeholder="e.g. Priya S.">
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-[#0F1111] mb-1">Rating</label>
                                            <div class="flex gap-0.5">
                                                @for($i = 1; $i <= 5; $i++)
                                                    <button type="button" @click="rating = {{ $i }}" @mouseenter="hover = {{ $i }}" @mouseleave="hover = 0">
                                                        <svg class="w-7 h-7 cursor-pointer transition-colors" :class="(hover || rating) >= {{ $i }} ? 'text-primary-600' : 'text-[#767676]'" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                        </svg>
                                                    </button>
                                                @endfor
                                                <input type="hidden" name="rating" :value="rating">
                                            </div>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-[#0F1111] mb-1">Title <span class="text-[#3a3a3a]">(optional)</span></label>
                                            <input type="text" name="title" class="w-full rounded-lg px-3 py-2 text-sm focus:ring-primary-600 focus:border-link" placeholder="Sum up your experience">
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-[#0F1111] mb-1">Your Review</label>
                                            <textarea name="content" required rows="4" class="w-full rounded-lg px-3 py-2 text-sm focus:ring-primary-600 focus:border-link" placeholder="What did you like or dislike?"></textarea>
                                        </div>

                                        <p x-show="errorMsg" x-text="errorMsg" class="text-sm text-red-600" x-cloak></p>

                                        <button type="submit" :disabled="submitting" class="bg-accent-500 hover:bg-accent-600 text-white font-medium py-2 px-6 rounded-full text-sm shadow-sm transition-colors disabled:opacity-50">
                                            <span x-show="!submitting">Submit Review</span>
                                            <span x-show="submitting" x-cloak>Submitting...</span>
                                        </button>
                                    </form>
                                </div>
                            </template>
                        </div>
                    @endauth
                </div>

                <!-- Right: Reviews List -->
                <div class="lg:col-span-8">
                    <h3 class="text-base font-bold text-[#0F1111] mb-4">Top Reviews</h3>

                    @if($displayReviews->count())
                        <div x-data="{ showAll: false }">
                        <div class="space-y-6">
                            @foreach($displayReviews as $review)
                                <div class="border-b border-[#E3E6E6] pb-5" @if($loop->index >= 10) x-show="showAll" x-cloak @endif>
                                    <!-- Reviewer -->
                                    <div class="flex items-center gap-2 mb-1.5">
                                        <div class="w-8 h-8 bg-[#F0F2F2] rounded-full flex items-center justify-center">
                                            <span class="text-xs font-medium text-[#3a3a3a]">{{ $review->reviewer_initial }}</span>
                                        </div>
                                        <span class="text-sm text-[#0F1111]">{{ $review->reviewer_name }}</span>
                                    </div>

                                    <!-- Stars + Title -->
                                    <div class="flex items-center gap-2 mb-1">
                                        <div class="flex">
                                            @for($i = 1; $i <= 5; $i++)
                                                <svg class="w-4 h-4 {{ $i <= $review->rating ? 'text-primary-600' : 'text-[#767676]' }}" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                </svg>
                                            @endfor
                                        </div>
                                        @if($review->title)
                                            <span class="text-sm font-bold text-[#0F1111]">{{ $review->title }}</span>
                                        @endif
                                    </div>

                                    <!-- Date + Verified -->
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="text-xs text-[#3a3a3a]">Reviewed on {{ $review->created_at->format('j F Y') }}</span>
                                        @if($review->is_verified_purchase)
                                            <span class="text-xs font-bold text-[#C45500]">Verified Purchase</span>
                                        @else
                                            <span class="text-xs text-[#3a3a3a]">Unverified</span>
                                        @endif
                                    </div>

                                    <!-- Content -->
                                    <p class="text-sm text-[#0F1111] leading-relaxed">{{ $review->content }}</p>

                                    <!-- Pros/Cons -->
                                    @if($review->pros && count($review->pros))
                                        <div class="mt-2 flex flex-wrap gap-1.5">
                                            @foreach($review->pros as $pro)
                                                <span class="inline-flex items-center gap-1 text-xs bg-[#F0FFF4] text-[#007600] px-2 py-0.5 rounded-full border border-[#C6F6D5]">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                    {{ $pro }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                    @if($review->cons && count($review->cons))
                                        <div class="mt-1 flex flex-wrap gap-1.5">
                                            @foreach($review->cons as $con)
                                                <span class="inline-flex items-center gap-1 text-xs bg-[#FFF5F5] text-[#B12704] px-2 py-0.5 rounded-full border border-[#FED7D7]">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    {{ $con }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif

                                    <!-- Helpful -->
                                    <div class="mt-3 flex items-center gap-3">
                                        <span class="text-xs text-[#3a3a3a]">{{ $review->helpful_count ?? 0 }} {{ ($review->helpful_count ?? 0) == 1 ? 'person' : 'people' }} found this helpful</span>
                                    </div>

                                    @if($review->admin_reply)
                                        <div class="mt-3 ml-4 bg-[#F7F8FA] border-l-3 border-primary-600 rounded-r-lg p-3">
                                            <p class="text-xs font-bold text-primary-700 mb-1">Store Response</p>
                                            <p class="text-sm text-[#0F1111] leading-relaxed">{{ $review->admin_reply }}</p>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        @if($displayReviews->count() > 10)
                            <button type="button" @click="showAll = !showAll"
                                    class="mt-5 w-full sm:w-auto inline-flex items-center justify-center gap-1.5 text-sm font-semibold text-[#0F1111] border border-[#D5D9D9] rounded-lg px-5 py-2.5 hover:bg-[#F7F8FA] transition-colors">
                                <span x-show="!showAll">Show all {{ number_format($displayReviews->count()) }} reviews</span>
                                <span x-show="showAll" x-cloak>Show fewer reviews</span>
                                <svg class="w-4 h-4 transition-transform" :class="showAll ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                        @endif
                        </div>
                    @else
                        <div class="text-center py-8 bg-[#F7F8FA] rounded-lg border border-[#E3E6E6]">
                            <p class="text-sm text-[#3a3a3a] mb-2">No reviews yet.</p>
                            <p class="text-sm text-[#0F1111]">Be the first to review this product!</p>
                        </div>
                    @endif
                </div>
            </div>
        </section>

        <!-- Recently Viewed -->
        <x-recently-viewed :limit="8" />

        <!-- Related Products -->
        @if($relatedProducts->count())
            <section class="mt-8 border-t border-[#E3E6E6] pt-6">
                <h2 class="text-lg font-bold text-[#0F1111] mb-4">Products related to this item</h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3 lg:gap-4">
                    @foreach($relatedProducts as $relatedProduct)
                        <x-product-card :product="$relatedProduct" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>

    {{-- Sticky Mobile Add-to-Cart Bar --}}
    @if($product->stock_quantity > 0)
        <script>
        (function() {
            // Create sticky bar and append directly to document.body to avoid transform/overflow issues
            var isMobile = window.innerWidth < 1024;
            if (!isMobile) return;

            var maxQty = {{ min($product->stock_quantity, 10) }};
            var usePack = false;

            // Get current quantity from the main page selector
            function getQty() {
                var sel = document.querySelector('select[x-model="quantity"]');
                return sel ? parseInt(sel.value) || 1 : 1;
            }
            function setQty(val) {
                var sel = document.querySelector('select[x-model="quantity"]');
                if (sel) { sel.value = val; sel.dispatchEvent(new Event('input', {bubbles:true})); }
                updateQtyDisplay();
            }
            function updateQtyDisplay() {
                var el = document.getElementById('sticky-qty-val');
                if (el) el.textContent = getQty();
            }

            var bar = document.createElement('div');
            bar.id = 'mobile-sticky-bar';
            bar.style.cssText = 'position:fixed;left:0;right:0;bottom:0;z-index:45;background:#fff;border-top:1px solid #D5D9D9;box-shadow:0 -2px 10px rgba(0,0,0,0.12);display:none;padding-bottom:env(safe-area-inset-bottom,0);';
            bar.innerHTML = '<div style="display:flex;align-items:center;gap:6px;padding:10px 12px">'
                + '<div style="min-width:0">'
                + '<span style="font-size:14px;font-weight:700;color:#0F1111">@price($product->price)</span>'
                @if($product->mrp > $product->price)
                + '<span style="font-size:10px;color:#3a3a3a;text-decoration:line-through;margin-left:4px">@price($product->mrp)</span>'
                @endif
                + '</div>'
                + (usePack ? '' : '<div style="display:flex;align-items:center;gap:0;border:1px solid #D5D9D9;border-radius:6px;overflow:hidden">'
                + '<button id="sticky-qty-dec" style="width:28px;height:28px;background:#F0F2F2;border:none;font-size:14px;font-weight:700;cursor:pointer;color:#0F1111">−</button>'
                + '<span id="sticky-qty-val" style="width:24px;text-align:center;font-size:13px;font-weight:600;color:#0F1111">1</span>'
                + '<button id="sticky-qty-inc" style="width:28px;height:28px;background:#F0F2F2;border:none;font-size:14px;font-weight:700;cursor:pointer;color:#0F1111">+</button>'
                + '</div>')
                + '<button id="sticky-add-btn" style="flex-shrink:0;background:#000;color:#fff;font-weight:600;padding:10px 14px;border-radius:999px;font-size:12px;border:none;cursor:pointer">Add to Cart</button>'
                + '<button id="sticky-buy-btn" style="flex-shrink:0;background:#FFD814;color:#0F1111;font-weight:600;padding:10px 14px;border-radius:999px;font-size:12px;border:none;cursor:pointer">Buy Now</button>'
                + '</div>';
            document.body.appendChild(bar);

            // Wire up quantity +/- buttons
            if (!usePack) {
                document.getElementById('sticky-qty-dec').addEventListener('click', function() {
                    var q = getQty(); if (q > 1) setQty(q - 1);
                });
                document.getElementById('sticky-qty-inc').addEventListener('click', function() {
                    var q = getQty(); if (q < maxQty) setQty(q + 1);
                });
                // Sync display when main selector changes
                var mainSel = document.querySelector('select[x-model="quantity"]');
                if (mainSel) mainSel.addEventListener('change', updateQtyDisplay);
            }

            // Wire up Add to Cart
            document.getElementById('sticky-add-btn').addEventListener('click', function() {
                var qty = getQty();
                if (window.Alpine && Alpine.store('cart')) {
                    Alpine.store('cart').add({{ $product->id }}, qty);
                } else {
                    axios.post('/cart/add', {product_id: {{ $product->id }}, quantity: qty}).then(function(){ location.reload(); });
                }
            });

            // Wire up Buy Now: open Shiprocket Checkout if available, else add to cart + checkout
            document.getElementById('sticky-buy-btn').addEventListener('click', function(evt) {
                var qty = getQty();
                if (typeof window.__srBuyNow === 'function') {
                    window.__srBuyNow(evt, this, {{ $product->id }}, qty);
                    return;
                }
                axios.post('/cart/add', {product_id: {{ $product->id }}, quantity: qty}).then(function(){
                    window.location.href = '{{ route("checkout.index") }}';
                });
            });

            window.addEventListener('scroll', function() {
                bar.style.display = window.scrollY > 300 ? 'block' : 'none';
                if (window.scrollY > 300) updateQtyDisplay();
            });
        })();
        </script>
    @endif

    {{-- GA4 view_item + FB ViewContent tracking --}}
    @php $hasFbPixelPDP = $theme->get('facebook_pixel_id', ''); @endphp
    @if(config('services.ga4.measurement_id') || $hasFbPixelPDP)
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @if(config('services.ga4.measurement_id'))
            gtag('event', 'view_item', {
                currency: 'INR',
                value: {{ (float) $product->price }},
                items: [{
                    item_id: '{{ $product->sku ?? $product->id }}',
                    item_name: @json($product->name),
                    item_category: @json($product->category?->name ?? ''),
                    item_brand: @json($product->brand?->name ?? ''),
                    price: {{ (float) $product->price }},
                    quantity: 1
                }]
            });
            @endif

            @if($hasFbPixelPDP)
            fbq('track', 'ViewContent', {
                content_name: @json($product->name),
                content_category: @json($product->category?->name ?? ''),
                content_ids: ['{{ $product->id }}'],
                content_type: 'product',
                value: {{ (float) $product->price }},
                currency: 'INR'
            }@if(!empty($fbEventId)), {eventID: '{{ $fbEventId }}'}@endif);
            @endif
        });
    </script>
    @endif

    <x-trust-badges />

</x-layouts.app>
