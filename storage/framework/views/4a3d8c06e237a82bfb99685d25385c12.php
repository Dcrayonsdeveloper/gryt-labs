<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['product', 'showQuickView' => true, 'compact' => false]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['product', 'showQuickView' => true, 'compact' => false]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
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
?>

<?php if($compact): ?>
    
    <div <?php echo e($attributes->merge(['class' => 'group shrink-0 w-full flex flex-col h-full'])); ?>>
        <a href="<?php echo e(route('products.show', $product)); ?>" class="block relative"
           onclick="typeof gtag!=='undefined'&&gtag('event','select_item',{items:[{item_id:<?php echo e(json_encode($product->sku ?? (string)$product->id)); ?>,item_name:<?php echo e(json_encode($product->name)); ?>,price:<?php echo e((float)$product->price); ?>}]})">
            <div class="aspect-square rounded-xl overflow-hidden mb-2 bg-neutral-100">
                <img src="<?php echo e($product->primary_image_url); ?>"
                     alt="<?php echo e($product->name); ?>"
                     width="300" height="300"
                     class="w-full h-full object-contain group-hover:scale-105 transition-transform duration-300"
                     loading="lazy"
                     decoding="async"
                     fetchpriority="low"
                     onerror="this.src='<?php echo e($placeholderImage); ?>'">
            </div>
            <?php if($hasDiscount): ?>
                <span class="absolute top-2 left-2 text-[10px] font-bold px-1.5 py-0.5 rounded-sm" style="background-color:<?php echo e($badgeColor); ?>;color:<?php echo e($badgeTextColor); ?>"><?php echo e(round($discount)); ?>% off</span>
            <?php endif; ?>
        </a>

        <a href="<?php echo e(route('products.show', $product)); ?>" class="block px-0.5"
           onclick="typeof gtag!=='undefined'&&gtag('event','select_item',{items:[{item_id:<?php echo e(json_encode($product->sku ?? (string)$product->id)); ?>,item_name:<?php echo e(json_encode($product->name)); ?>,price:<?php echo e((float)$product->price); ?>}]})">
            <h3 class="text-[13px] text-[#0F1111] line-clamp-2 mb-1 leading-snug hover:text-link-hover transition-colors" style="min-height: 2.5em;">
                <?php echo e($product->name); ?>

            </h3>
        </a>

        
        <div style="margin-top:auto;" class="px-0.5">
            
            <div class="flex items-center gap-1 mb-1">
                <div class="flex items-center">
                    <?php for($i = 1; $i <= 5; $i++): ?>
                        <?php if($i <= floor($rating)): ?>
                            <svg class="w-3.5 h-3.5 <?php echo e($starColor ? '' : 'text-primary-600'); ?>" fill="currentColor" viewBox="0 0 20 20" <?php if($starColor): ?> style="color:<?php echo e($starColor); ?>" <?php endif; ?>><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        <?php elseif($i == ceil($rating) && $rating - floor($rating) >= 0.25): ?>
                            <svg class="w-3.5 h-3.5" viewBox="0 0 20 20">
                                <defs><linearGradient id="half-star-compact-<?php echo e($product->id); ?>"><stop offset="50%" stop-color="<?php echo e($starColor ?: 'var(--color-primary-600)'); ?>"/><stop offset="50%" stop-color="#767676"/></linearGradient></defs>
                                <path fill="url(#half-star-compact-<?php echo e($product->id); ?>)" d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        <?php else: ?>
                            <svg class="w-3.5 h-3.5 text-[#767676]" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
                <span class="text-[11px] text-link">(<?php echo e($reviewCount); ?>)</span>
            </div>

            <div class="flex items-baseline gap-1 flex-wrap mb-1">
                <span class="text-[15px] font-medium text-[#0F1111]"><?php echo format_price($product->price); ?></span>
                <?php if($hasDiscount): ?>
                    <span class="text-[11px] text-[#3a3a3a] line-through"><?php echo format_price($product->mrp); ?></span>
                <?php endif; ?>
            </div>

            <div style="height:16px;" class="mb-1">
                <?php if($hasDiscount): ?>
                    <p class="text-[11px] font-medium" style="color:<?php echo e($badgeColor); ?>">Save <?php echo e(round($discount)); ?>%</p>
                <?php endif; ?>
            </div>

        
        <?php if($showAddToCart): ?>
            <div>
                <?php if (! ($outOfStock)): ?>
                    <button @click="$store.cart.add(<?php echo e($product->id); ?>)"
                            class="w-full py-1.5 text-[11px] font-semibold text-white bg-accent-500 hover:bg-accent-600 rounded-md transition-colors shadow-sm">
                        Add to Cart
                    </button>
                <?php else: ?>
                    <a href="<?php echo e(route('products.show', $product->slug)); ?>"
                       class="block w-full py-1.5 text-xs font-medium text-[#3a3a3a] bg-[#F0F2F2] rounded-full transition-colors text-center">
                        Notify Me
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    
    <div <?php echo e($attributes->merge(['class' => 'group card-product flex flex-col overflow-hidden'])); ?>>
        
        <div class="relative aspect-square overflow-hidden rounded-xl bg-neutral-100">
            <a href="<?php echo e(route('products.show', $product)); ?>"
               onclick="typeof gtag!=='undefined'&&gtag('event','select_item',{items:[{item_id:<?php echo e(json_encode($product->sku ?? (string)$product->id)); ?>,item_name:<?php echo e(json_encode($product->name)); ?>,price:<?php echo e((float)$product->price); ?>}]})">
                <img src="<?php echo e($product->primary_image_url); ?>"
                     alt="<?php echo e($product->name); ?>"
                     width="300" height="300"
                     class="w-full h-full object-contain group-hover:scale-105 transition-transform duration-300"
                     loading="lazy"
                     decoding="async"
                     fetchpriority="low"
                     onerror="this.src='<?php echo e($placeholderImage); ?>'">
            </a>

            
            <?php if($hasDiscount): ?>
                <div class="absolute top-2 left-2">
                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-sm" style="background-color:<?php echo e($badgeColor); ?>;color:<?php echo e($badgeTextColor); ?>"><?php echo e(round($discount)); ?>% off</span>
                </div>
            <?php endif; ?>

            
            <?php if($hasHoverActions): ?>
                <div class="absolute top-2 right-2 flex flex-col gap-1.5 sm:opacity-0 sm:group-hover:opacity-100 focus-within:opacity-100 transition-opacity duration-200">
                    <?php if($showWishlist): ?>
                        <button @click="$store.wishlist.toggle(<?php echo e($product->id); ?>)"
                                class="w-9 h-9 bg-white rounded-full shadow-sm flex items-center justify-center transition-colors"
                                :style="$store.wishlist.has(<?php echo e($product->id); ?>) ? 'color: #ef4444;' : 'color: #3a3a3a;'"
                                aria-label="Toggle wishlist">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                        </button>
                    <?php endif; ?>
                    <?php if($showQuickViewBtn): ?>
                        <button @click="$dispatch('quick-view', { productSlug: '<?php echo e($product->slug); ?>' })"
                                class="w-9 h-9 bg-white rounded-full shadow-sm flex items-center justify-center text-[#3a3a3a] hover:text-[#0F1111] transition-colors"
                                aria-label="Quick view">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            
            <?php if($outOfStock): ?>
                <div class="absolute inset-0 bg-white/70 flex items-center justify-center">
                    <span class="text-xs font-semibold text-[#B12704] bg-white px-3 py-1 rounded-full shadow-sm border border-[#E3E6E6]">Out of Stock</span>
                </div>
            <?php endif; ?>
        </div>

        
        <div class="p-3 flex flex-col flex-1">
            
            <h3 class="text-[13px] text-[#0F1111] mb-1.5 leading-snug min-h-9">
                <a href="<?php echo e(route('products.show', $product)); ?>" class="line-clamp-2 hover:text-link-hover transition-colors"
                   onclick="typeof gtag!=='undefined'&&gtag('event','select_item',{items:[{item_id:<?php echo e(json_encode($product->sku ?? (string)$product->id)); ?>,item_name:<?php echo e(json_encode($product->name)); ?>,price:<?php echo e((float)$product->price); ?>}]})">
                    <?php echo e($product->name); ?>

                </a>
            </h3>

            
            <div class="flex items-center gap-1 mb-1.5">
                <div class="flex items-center">
                    <?php for($i = 1; $i <= 5; $i++): ?>
                        <?php if($i <= floor($rating)): ?>
                            <svg class="w-3.5 h-3.5 <?php echo e($starColor ? '' : 'text-primary-600'); ?>" fill="currentColor" viewBox="0 0 20 20" <?php if($starColor): ?> style="color:<?php echo e($starColor); ?>" <?php endif; ?>>
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        <?php elseif($i == ceil($rating) && $rating - floor($rating) >= 0.25): ?>
                            <svg class="w-3.5 h-3.5" viewBox="0 0 20 20">
                                <defs><linearGradient id="half-star-full-<?php echo e($product->id); ?>"><stop offset="50%" stop-color="<?php echo e($starColor ?: 'var(--color-primary-600)'); ?>"/><stop offset="50%" stop-color="#767676"/></linearGradient></defs>
                                <path fill="url(#half-star-full-<?php echo e($product->id); ?>)" d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        <?php else: ?>
                            <svg class="w-3.5 h-3.5 text-[#767676]" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
                <a href="<?php echo e(route('products.show', $product)); ?>#reviews" class="text-[11px] text-link hover:text-link-hover hover:underline">(<?php echo e($reviewCount); ?>)</a>
            </div>

            
            <div class="mb-1.5">
                <div class="flex items-baseline gap-1.5">
                    <span class="text-[16px] font-medium text-[#0F1111]"><?php echo format_price($product->price); ?></span>
                    <?php if($hasDiscount): ?>
                        <span class="text-[12px] text-[#3a3a3a] line-through"><?php echo format_price($product->mrp); ?></span>
                    <?php endif; ?>
                </div>
                <?php if($hasDiscount): ?>
                    <span class="text-[11px] font-medium" style="color:<?php echo e($badgeColor); ?>">(<?php echo e(round($discount)); ?>% off)</span>
                <?php endif; ?>
            </div>

            
            <?php if($product->price >= $theme->get('free_delivery_threshold', 499)): ?>
                <p class="text-[11px] text-[#0F1111] mb-1.5">
                    <span class="text-[#3a3a3a]">FREE Delivery by</span> <span class="font-medium"><?php echo e(config('app.name')); ?></span>
                </p>
            <?php endif; ?>

            
            <?php if(!$outOfStock && $product->stock_quantity > 0 && $product->stock_quantity <= 10): ?>
                <p class="text-[11px] font-medium mb-1" style="color:<?php echo e($badgeColor); ?>">
                    Only <?php echo e($product->stock_quantity); ?> left in stock - order soon
                </p>
            <?php endif; ?>

            
            <?php if($showAddToCart): ?>
                <div class="mt-auto pt-2">
                    <?php if (! ($outOfStock)): ?>
                        <button @click="$store.cart.add(<?php echo e($product->id); ?>)"
                                class="w-full py-1.5 text-[11px] font-semibold text-white bg-accent-500 hover:bg-accent-600 rounded-md transition-colors shadow-sm">
                            Add to Cart
                        </button>
                    <?php else: ?>
                        <a href="<?php echo e(route('products.show', $product->slug)); ?>"
                           class="block w-full py-1.5 text-xs font-medium text-[#3a3a3a] bg-[#F0F2F2] rounded-full transition-colors text-center">
                            Notify Me
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
<?php /**PATH D:\projects\grytlabs345\resources\views/components/product-card.blade.php ENDPATH**/ ?>