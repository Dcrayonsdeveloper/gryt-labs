@props(['product', 'showQuickView' => true, 'compact' => false])

@php
    $discount = $product->discount_percentage ?? 0;
    $hasDiscount = $product->price < $product->mrp;
    $rating = $product->rating ?? 0;
    $reviewCount = $product->review_count ?? 0;
    $outOfStock = !$product->isInStock();

    // Read hover action settings (cached for 1hr by Setting::get)
    $showWishlist = $theme->get('product_card_wishlist', true);
    $showAddToCart = $theme->get('product_card_add_to_cart', true);
    $showQuickViewBtn = $showQuickView && $theme->get('product_card_quick_view', true);
    $hasHoverActions = $showWishlist || $showQuickViewBtn;

    // Badge colours (tenant-customisable)
    $badgeColor = $theme->get('badge_color', '') ?: '#CC0C39';
    $badgeTextColor = $theme->get('badge_text_color', '') ?: '#ffffff';

    // Star colour (tenant-customisable, falls back to CSS primary-600)
    $starColor = $theme->get('star_color', '');

    // Placeholder image
    $placeholderImage = asset('images/placeholder-product.svg');

    // Per-product bundle offer → open the pack-picker popup instead of adding a single unit.
    $hasPack = $product->hasPackOffer();
    $packBadges = $hasPack ? (array) ($product->pack_config['badges'] ?? []) : [];
    $cartClick = $hasPack
        ? "\$dispatch('open-pack-picker', " . \Illuminate\Support\Js::from([
              'productId' => $product->id,
              'name'      => $product->name,
              'image'     => $product->primary_image_url ?: $placeholderImage,
              'heading'   => $product->pack_config['heading'] ?? 'Choose your pack & save',
              'tiers'     => collect($product->packTiers(4))->map(fn ($t) => $t + [
                  'badge' => $packBadges[$t['qty']] ?? $packBadges[(string) $t['qty']] ?? null,
              ])->values()->all(),
          ]) . ")"
        : "\$store.cart.add({$product->id})";
@endphp

@if($compact)
    {{-- Compact card for horizontal scrollable rows --}}
    <div {{ $attributes->merge(['class' => 'group shrink-0 w-full flex flex-col h-full']) }}>
        <a href="{{ route('products.show', $product) }}" class="block relative"
           onclick="typeof gtag!=='undefined'&&gtag('event','select_item',{items:[{item_id:{{ json_encode($product->sku ?? (string)$product->id) }},item_name:{{ json_encode($product->name) }},price:{{ (float)$product->price }}}]})">
            <div class="aspect-square rounded-xl overflow-hidden mb-2 bg-neutral-100">
                <img src="{{ $product->primary_image_url }}"
                     alt="{{ $product->name }}"
                     width="300" height="300"
                     class="w-full h-full object-contain group-hover:scale-105 transition-transform duration-300"
                     loading="lazy"
                     decoding="async"
                     fetchpriority="low"
                     onerror="this.src='{{ $placeholderImage }}'">
            </div>
            @if($hasDiscount)
                <span class="absolute top-2 left-2 text-[10px] font-bold px-1.5 py-0.5 rounded-sm" style="background-color:{{ $badgeColor }};color:{{ $badgeTextColor }}">{{ round($discount) }}% off</span>
            @endif
        </a>

        <a href="{{ route('products.show', $product) }}" class="block px-0.5"
           onclick="typeof gtag!=='undefined'&&gtag('event','select_item',{items:[{item_id:{{ json_encode($product->sku ?? (string)$product->id) }},item_name:{{ json_encode($product->name) }},price:{{ (float)$product->price }}}]})">
            <h3 class="text-[13px] text-[#0F1111] line-clamp-2 mb-1 leading-snug hover:text-link-hover transition-colors" style="min-height: 2.5em;">
                {{ $product->name }}
            </h3>
        </a>

        {{-- Bottom section pushed down --}}
        <div style="margin-top:auto;" class="px-0.5">
            {{-- Star Rating --}}
            <div class="flex items-center gap-1 mb-1">
                <div class="flex items-center">
                    @for($i = 1; $i <= 5; $i++)
                        @if($i <= floor($rating))
                            <svg class="w-3.5 h-3.5 {{ $starColor ? '' : 'text-primary-600' }}" fill="currentColor" viewBox="0 0 20 20" @if($starColor) style="color:{{ $starColor }}" @endif><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        @elseif($i == ceil($rating) && $rating - floor($rating) >= 0.25)
                            <svg class="w-3.5 h-3.5" viewBox="0 0 20 20">
                                <defs><linearGradient id="half-star-compact-{{ $product->id }}"><stop offset="50%" stop-color="{{ $starColor ?: 'var(--color-primary-600)' }}"/><stop offset="50%" stop-color="#767676"/></linearGradient></defs>
                                <path fill="url(#half-star-compact-{{ $product->id }})" d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        @else
                            <svg class="w-3.5 h-3.5 text-[#767676]" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        @endif
                    @endfor
                </div>
                <span class="text-[11px] text-link">({{ $reviewCount }})</span>
            </div>

            <div class="flex items-baseline gap-1 flex-wrap mb-1">
                <span class="text-[15px] font-medium text-[#0F1111]">@price($product->price)</span>
                @if($hasDiscount)
                    <span class="text-[11px] text-[#3a3a3a] line-through">@price($product->mrp)</span>
                @endif
            </div>

            <div style="height:16px;" class="mb-1">
                @if($hasDiscount)
                    <p class="text-[11px] font-medium" style="color:{{ $badgeColor }}">Save {{ round($discount) }}%</p>
                @endif
            </div>

        {{-- Add to Cart --}}
        @if($showAddToCart)
            <div>
                @unless($outOfStock)
                    <button @click="{!! $cartClick !!}"
                            class="w-full py-1.5 text-[11px] font-semibold text-white bg-black hover:bg-neutral-800 rounded-md transition-colors shadow-sm">
                        Add to Cart
                    </button>
                @else
                    <a href="{{ route('products.show', $product->slug) }}"
                       class="block w-full py-1.5 text-xs font-medium text-[#3a3a3a] bg-[#F0F2F2] rounded-full transition-colors text-center">
                        Notify Me
                    </a>
                @endunless
            </div>
        @endif
        </div>{{-- end mt-auto bottom section --}}
    </div>
@else
    {{-- Full product card --}}
    <div {{ $attributes->merge(['class' => 'group card-product flex flex-col overflow-hidden']) }}>
        {{-- Image Section --}}
        <div class="relative aspect-square overflow-hidden rounded-xl bg-neutral-100">
            <a href="{{ route('products.show', $product) }}"
               onclick="typeof gtag!=='undefined'&&gtag('event','select_item',{items:[{item_id:{{ json_encode($product->sku ?? (string)$product->id) }},item_name:{{ json_encode($product->name) }},price:{{ (float)$product->price }}}]})">
                <img src="{{ $product->primary_image_url }}"
                     alt="{{ $product->name }}"
                     width="300" height="300"
                     class="w-full h-full object-contain group-hover:scale-105 transition-transform duration-300"
                     loading="lazy"
                     decoding="async"
                     fetchpriority="low"
                     onerror="this.src='{{ $placeholderImage }}'">
            </a>

            {{-- Top-left badges --}}
            @if($hasDiscount)
                <div class="absolute top-2 left-2">
                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-sm" style="background-color:{{ $badgeColor }};color:{{ $badgeTextColor }}">{{ round($discount) }}% off</span>
                </div>
            @endif

            {{-- Top-right hover actions (Wishlist + Quick View) --}}
            @if($hasHoverActions)
                <div class="absolute top-2 right-2 flex flex-col gap-1.5 sm:opacity-0 sm:group-hover:opacity-100 focus-within:opacity-100 transition-opacity duration-200">
                    @if($showWishlist)
                        <button @click="$store.wishlist.toggle({{ $product->id }})"
                                class="w-9 h-9 bg-white rounded-full shadow-sm flex items-center justify-center transition-colors"
                                :style="$store.wishlist.has({{ $product->id }}) ? 'color: #ef4444;' : 'color: #3a3a3a;'"
                                aria-label="Toggle wishlist">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                        </button>
                    @endif
                    @if($showQuickViewBtn)
                        <button @click="$dispatch('quick-view', { productSlug: '{{ $product->slug }}' })"
                                class="w-9 h-9 bg-white rounded-full shadow-sm flex items-center justify-center text-[#3a3a3a] hover:text-[#0F1111] transition-colors"
                                aria-label="Quick view">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                    @endif
                </div>
            @endif

            {{-- Out of stock overlay --}}
            @if($outOfStock)
                <div class="absolute inset-0 bg-white/70 flex items-center justify-center">
                    <span class="text-xs font-semibold text-[#B12704] bg-white px-3 py-1 rounded-full shadow-sm border border-[#E3E6E6]">Out of Stock</span>
                </div>
            @endif
        </div>

        {{-- Content Section --}}
        <div class="p-3 flex flex-col flex-1">
            {{-- Product Name --}}
            <h3 class="text-[13px] text-[#0F1111] mb-1.5 leading-snug min-h-9">
                <a href="{{ route('products.show', $product) }}" class="line-clamp-2 hover:text-link-hover transition-colors"
                   onclick="typeof gtag!=='undefined'&&gtag('event','select_item',{items:[{item_id:{{ json_encode($product->sku ?? (string)$product->id) }},item_name:{{ json_encode($product->name) }},price:{{ (float)$product->price }}}]})">
                    {{ $product->name }}
                </a>
            </h3>

            {{-- Star Rating Row --}}
            <div class="flex items-center gap-1 mb-1.5">
                <div class="flex items-center">
                    @for($i = 1; $i <= 5; $i++)
                        @if($i <= floor($rating))
                            <svg class="w-3.5 h-3.5 {{ $starColor ? '' : 'text-primary-600' }}" fill="currentColor" viewBox="0 0 20 20" @if($starColor) style="color:{{ $starColor }}" @endif>
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        @elseif($i == ceil($rating) && $rating - floor($rating) >= 0.25)
                            <svg class="w-3.5 h-3.5" viewBox="0 0 20 20">
                                <defs><linearGradient id="half-star-full-{{ $product->id }}"><stop offset="50%" stop-color="{{ $starColor ?: 'var(--color-primary-600)' }}"/><stop offset="50%" stop-color="#767676"/></linearGradient></defs>
                                <path fill="url(#half-star-full-{{ $product->id }})" d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        @else
                            <svg class="w-3.5 h-3.5 text-[#767676]" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        @endif
                    @endfor
                </div>
                <a href="{{ route('products.show', $product) }}#reviews" class="text-[11px] text-link hover:text-link-hover hover:underline">({{ $reviewCount }})</a>
            </div>

            {{-- Price Row --}}
            <div class="mb-1.5">
                <div class="flex items-baseline gap-1.5">
                    <span class="text-[16px] font-medium text-[#0F1111]">@price($product->price)</span>
                    @if($hasDiscount)
                        <span class="text-[12px] text-[#3a3a3a] line-through">@price($product->mrp)</span>
                    @endif
                </div>
                @if($hasDiscount)
                    <span class="text-[11px] font-medium" style="color:{{ $badgeColor }}">({{ round($discount) }}% off)</span>
                @endif
            </div>

            {{-- Free Delivery badge --}}
            @if($product->price >= $theme->get('free_delivery_threshold', 499))
                <p class="text-[11px] text-[#0F1111] mb-1.5">
                    <span class="text-[#3a3a3a]">FREE Delivery by</span> <span class="font-medium">{{ config('app.name') }}</span>
                </p>
            @endif

            {{-- Scarcity badge --}}
            @if(!$outOfStock && $product->stock_quantity > 0 && $product->stock_quantity <= 10)
                <p class="text-[11px] font-medium mb-1" style="color:{{ $badgeColor }}">
                    Only {{ $product->stock_quantity }} left in stock - order soon
                </p>
            @endif

            {{-- Add to Cart / Notify --}}
            @if($showAddToCart)
                <div class="mt-auto pt-2">
                    @unless($outOfStock)
                        <button @click="{!! $cartClick !!}"
                                class="w-full py-1.5 text-[11px] font-semibold text-white bg-black hover:bg-neutral-800 rounded-md transition-colors shadow-sm">
                            Add to Cart
                        </button>
                    @else
                        <a href="{{ route('products.show', $product->slug) }}"
                           class="block w-full py-1.5 text-xs font-medium text-[#3a3a3a] bg-[#F0F2F2] rounded-full transition-colors text-center">
                            Notify Me
                        </a>
                    @endunless
                </div>
            @endif
        </div>
    </div>
@endif
