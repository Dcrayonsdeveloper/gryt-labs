<?php if (isset($component)) { $__componentOriginal5863877a5171c196453bfa0bd807e410 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5863877a5171c196453bfa0bd807e410 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layouts.app','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layouts.app'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
    <?php $storeName = $theme->get('store_name', config('app.name')); ?>
     <?php $__env->slot('title', null, []); ?> <?php echo e(request('category') ? ($categories->firstWhere('slug', request('category'))?->name ?? 'Products') : 'All Products'); ?> — <?php echo e($storeName); ?> <?php $__env->endSlot(); ?>

    <?php $__env->startPush('meta'); ?>
        <?php
            $metaCat = request('category') ? ($categories->firstWhere('slug', request('category'))?->name ?? null) : null;
            $metaBrand = request('brand') ? ($brands->firstWhere('slug', request('brand'))?->name ?? null) : null;
            $metaDesc = $metaCat
                ? "Shop {$metaCat} at {$storeName}. Browse {$products->total()} products with great prices and free shipping."
                : ($metaBrand
                    ? "Shop {$metaBrand} products at {$storeName}. Discover {$products->total()} products with great deals."
                    : "Shop our collection at {$storeName}. Browse {$products->total()} products with great deals.");
        ?>
        <meta name="description" content="<?php echo e($metaDesc); ?>">
        <link rel="canonical" href="<?php echo e(url('/products')); ?>">
        <meta property="og:title" content="<?php echo e($metaCat ?? ($metaBrand ?? 'All Products')); ?> — <?php echo e($storeName); ?>">
        <meta property="og:description" content="<?php echo e($metaDesc); ?>">
        <meta property="og:type" content="website">
        <meta property="og:url" content="<?php echo e(url()->current()); ?>">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="<?php echo e($metaCat ?? ($metaBrand ?? 'All Products')); ?> — <?php echo e($storeName); ?>">
        <meta name="twitter:description" content="<?php echo e($metaDesc); ?>">
        <?php if(request()->anyFilled(['category', 'brand', 'min_price', 'max_price', 'rating', 'in_stock', 'on_sale', 'sort'])): ?>
        <meta name="robots" content="noindex, follow">
        <?php endif; ?>
    <?php $__env->stopPush(); ?>

    <?php $__env->startPush('styles'); ?>
    <style>
        /* Custom page scrollbar for products */
        html { scrollbar-width: thin; scrollbar-color: var(--color-primary-600) #F7F8FA; }
        html::-webkit-scrollbar { width: 8px; }
        html::-webkit-scrollbar-track { background: #F7F8FA; }
        html::-webkit-scrollbar-thumb { background: #b0c4c7; border-radius: 4px; border: 2px solid #F7F8FA; }
        html::-webkit-scrollbar-thumb:hover { background: var(--color-primary-600); }
    </style>
    <?php $__env->stopPush(); ?>

    <!-- Breadcrumb -->
    <div class="bg-white border-b border-neutral-100">
        <div class="container mx-auto px-4 py-2.5">
            <?php if (isset($component)) { $__componentOriginale19f62b34dfe0bfdf95075badcb45bc2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale19f62b34dfe0bfdf95075badcb45bc2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.breadcrumb','data' => ['items' => [['label' => 'Products', 'url' => null]]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('breadcrumb'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['items' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([['label' => 'Products', 'url' => null]])]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale19f62b34dfe0bfdf95075badcb45bc2)): ?>
<?php $attributes = $__attributesOriginale19f62b34dfe0bfdf95075badcb45bc2; ?>
<?php unset($__attributesOriginale19f62b34dfe0bfdf95075badcb45bc2); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale19f62b34dfe0bfdf95075badcb45bc2)): ?>
<?php $component = $__componentOriginale19f62b34dfe0bfdf95075badcb45bc2; ?>
<?php unset($__componentOriginale19f62b34dfe0bfdf95075badcb45bc2); ?>
<?php endif; ?>
        </div>
    </div>

    <!-- Header -->
    <div class="bg-primary-600">
        <div class="container mx-auto px-4 py-6 md:py-8">
            <h1 class="text-2xl md:text-3xl font-bold text-white mb-1">All Products</h1>
            <p class="text-white/90 text-sm">Browse our wide range of products & accessories</p>
            <p class="text-white/70 text-xs mt-2"><?php echo e($products->total()); ?> products</p>
        </div>
    </div>

    <div class="container mx-auto px-4 py-6">
        <!-- Active Filters -->
        <?php if(request()->hasAny(['category', 'brand', 'min_price', 'max_price', 'rating', 'in_stock', 'on_sale'])): ?>
            <div class="flex flex-wrap items-center gap-2 mb-5">
                <span class="text-xs font-medium text-neutral-600 uppercase tracking-wide">Active Filters:</span>
                <?php if(request('category')): ?>
                    <?php $catName = $categories->firstWhere('slug', request('category'))?->name ?? request('category'); ?>
                    <a href="<?php echo e(request()->fullUrlWithoutQuery('category')); ?>"
                       class="inline-flex items-center gap-1 px-2.5 py-1 bg-primary-600/5 text-primary-700 text-xs font-medium rounded-full border border-primary-600/30 hover:bg-primary-600/10 transition-colors">
                        <?php echo e($catName); ?>

                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </a>
                <?php endif; ?>
                <?php if(request('brand')): ?>
                    <?php $allBrands = (array) request('brand'); ?>
                    <?php $__currentLoopData = $allBrands; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $brandSlug): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $brandName = $brands->firstWhere('slug', $brandSlug)?->name ?? $brandSlug;
                            $remaining = array_values(array_diff($allBrands, [$brandSlug]));
                            $newQuery = array_merge(request()->except('brand', 'page'), count($remaining) > 0 ? ['brand' => $remaining] : []);
                            $removeUrl = url()->current() . (count($newQuery) ? '?' . http_build_query($newQuery) : '');
                        ?>
                        <a href="<?php echo e($removeUrl); ?>"
                           class="inline-flex items-center gap-1 px-2.5 py-1 bg-primary-600/5 text-primary-700 text-xs font-medium rounded-full border border-primary-600/30 hover:bg-primary-600/10 transition-colors">
                            <?php echo e($brandName); ?>

                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php endif; ?>
                <?php if(request('min_price') || request('max_price')): ?>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-primary-600/5 text-primary-700 text-xs font-medium rounded-full border border-primary-600/30">
                        <?php echo format_price(request('min_price', 0)); ?> - <?php echo format_price(request('max_price', '...')); ?>
                    </span>
                <?php endif; ?>
                <?php if(request('rating')): ?>
                    <span class="inline-flex items-center px-2.5 py-1 bg-primary-600/5 text-primary-700 text-xs font-medium rounded-full border border-primary-600/30">
                        <?php echo e(request('rating')); ?>+ Stars
                    </span>
                <?php endif; ?>
                <?php if(request('in_stock')): ?>
                    <span class="inline-flex items-center px-2.5 py-1 bg-primary-600/5 text-primary-700 text-xs font-medium rounded-full border border-primary-600/30">In Stock</span>
                <?php endif; ?>
                <?php if(request('on_sale')): ?>
                    <span class="inline-flex items-center px-2.5 py-1 bg-primary-600/5 text-primary-700 text-xs font-medium rounded-full border border-primary-600/30">On Sale</span>
                <?php endif; ?>
                <a href="<?php echo e(route('products.index')); ?>" class="text-xs text-neutral-600 hover:text-primary-600 underline ml-1">Clear all</a>
            </div>
        <?php endif; ?>

        <div class="flex flex-col lg:flex-row gap-6">
            <!-- Filters Sidebar -->
            <aside class="lg:w-60 shrink-0" x-data="{ mobileOpen: false }">
                <!-- Mobile filter toggle -->
                <button @click="mobileOpen = true"
                        class="lg:hidden w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-white border border-neutral-200 rounded-lg text-sm font-semibold text-neutral-700 transition-colors mb-4 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    Filters
                    <?php if(request()->hasAny(['category', 'brand', 'min_price', 'max_price', 'rating', 'in_stock', 'on_sale'])): ?>
                        <span class="w-5 h-5 bg-accent-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center">
                            <?php echo e(count(array_filter([request('category'), request('brand'), request('min_price'), request('max_price'), request('rating'), request('in_stock'), request('on_sale')]))); ?>

                        </span>
                    <?php endif; ?>
                </button>

                <!-- Mobile filter overlay -->
                <div x-show="mobileOpen" x-cloak class="lg:hidden fixed inset-0 z-50">
                    <div x-show="mobileOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                         @click="mobileOpen = false" class="absolute inset-0 bg-black/40"></div>
                    <div x-show="mobileOpen" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
                         x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
                         class="absolute inset-y-0 left-0 w-80 max-w-[85vw] bg-white shadow-xl flex flex-col">
                        <div class="flex items-center justify-between px-4 py-3 border-b border-neutral-100">
                            <h3 class="font-semibold text-neutral-900">Filters</h3>
                            <button @click="mobileOpen = false" class="p-1 text-neutral-600 hover:text-neutral-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <div class="flex-1 overflow-y-auto p-4">
                            <?php echo $__env->make('products.partials.filters', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        </div>
                    </div>
                </div>

                <!-- Desktop filters -->
                <div class="hidden lg:block">
                    <?php echo $__env->make('products.partials.filters', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                </div>
            </aside>

            <!-- Products Grid -->
            <div class="flex-1 min-w-0">
                <!-- Sort Bar -->
                <div class="flex items-center justify-between mb-5 pb-4 border-b border-neutral-100">
                    <p class="text-sm text-neutral-600">
                        <span class="font-semibold text-neutral-900"><?php echo e($products->total()); ?></span> products found
                    </p>

                    <div class="flex items-center gap-2">
                        <label class="text-xs text-neutral-600 hidden sm:inline">Sort by:</label>
                        <select onchange="window.location.href = '<?php echo e(route('products.index')); ?>?' + new URLSearchParams({...Object.fromEntries(new URLSearchParams(window.location.search)), sort: this.value})"
                                class="text-sm py-1.5 pl-3 pr-8 border border-neutral-200 rounded-lg bg-white text-neutral-700 focus:outline-none focus:border-primary-600 cursor-pointer">
                            <option value="newest" <?php echo e(request('sort') === 'newest' ? 'selected' : ''); ?>>Newest</option>
                            <option value="price_asc" <?php echo e(request('sort') === 'price_asc' ? 'selected' : ''); ?>>Price: Low to High</option>
                            <option value="price_desc" <?php echo e(request('sort') === 'price_desc' ? 'selected' : ''); ?>>Price: High to Low</option>
                            <option value="rating" <?php echo e(request('sort') === 'rating' ? 'selected' : ''); ?>>Best Rating</option>
                            <option value="bestselling" <?php echo e(request('sort') === 'bestselling' ? 'selected' : ''); ?>>Bestselling</option>
                        </select>
                    </div>
                </div>

                <?php if($products->count()): ?>
                    <div x-data="{
                        page: <?php echo e($products->currentPage()); ?>,
                        loading: false,
                        hasMore: <?php echo e($products->hasMorePages() ? 'true' : 'false'); ?>,
                        loadMore() {
                            if (this.loading || !this.hasMore) return;
                            this.loading = true;
                            this.page++;
                            const url = new URL(window.location.href);
                            url.searchParams.set('page', this.page);
                            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                                .then(r => r.json())
                                .then(data => {
                                    this.$refs.grid.insertAdjacentHTML('beforeend', data.html);
                                    this.hasMore = data.hasMore;
                                    this.loading = false;
                                })
                                .catch(() => { this.loading = false; });
                        }
                    }" x-init="
                        $nextTick(() => {
                            new IntersectionObserver((entries) => {
                                if (entries[0].isIntersecting) $data.loadMore();
                            }, { rootMargin: '200px' }).observe($refs.sentinel);
                        });
                    ">
                        <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3 md:gap-4" x-ref="grid">
                            <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php if (isset($component)) { $__componentOriginal3fd2897c1d6a149cdb97b41db9ff827a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3fd2897c1d6a149cdb97b41db9ff827a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.product-card','data' => ['product' => $product]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('product-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['product' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($product)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3fd2897c1d6a149cdb97b41db9ff827a)): ?>
<?php $attributes = $__attributesOriginal3fd2897c1d6a149cdb97b41db9ff827a; ?>
<?php unset($__attributesOriginal3fd2897c1d6a149cdb97b41db9ff827a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3fd2897c1d6a149cdb97b41db9ff827a)): ?>
<?php $component = $__componentOriginal3fd2897c1d6a149cdb97b41db9ff827a; ?>
<?php unset($__componentOriginal3fd2897c1d6a149cdb97b41db9ff827a); ?>
<?php endif; ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>

                        <!-- Sentinel for infinite scroll -->
                        <div x-ref="sentinel" class="h-4"></div>

                        <!-- Loading spinner -->
                        <div x-show="loading" x-cloak class="flex justify-center py-8">
                            <svg class="animate-spin h-6 w-6 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-20">
                        <div class="w-20 h-20 mx-auto mb-4 bg-neutral-100 rounded-full flex items-center justify-center">
                            <svg class="w-10 h-10 text-neutral-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-neutral-900 mb-1">No products found</h3>
                        <p class="text-sm text-neutral-600 mb-5">Try adjusting your filters or browse all products.</p>
                        <a href="<?php echo e(route('products.index')); ?>" class="inline-flex items-center gap-2 px-4 py-1.5 bg-accent-500 hover:bg-accent-600 text-white text-sm font-semibold rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            Clear All Filters
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (isset($component)) { $__componentOriginald1860217e247f31dc2f0bf9319aae99a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald1860217e247f31dc2f0bf9319aae99a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.trust-badges','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('trust-badges'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald1860217e247f31dc2f0bf9319aae99a)): ?>
<?php $attributes = $__attributesOriginald1860217e247f31dc2f0bf9319aae99a; ?>
<?php unset($__attributesOriginald1860217e247f31dc2f0bf9319aae99a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald1860217e247f31dc2f0bf9319aae99a)): ?>
<?php $component = $__componentOriginald1860217e247f31dc2f0bf9319aae99a; ?>
<?php unset($__componentOriginald1860217e247f31dc2f0bf9319aae99a); ?>
<?php endif; ?>
    <?php if (isset($component)) { $__componentOriginald509f1dd991e98b5837bfe6e90a061dc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald509f1dd991e98b5837bfe6e90a061dc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.faq-section','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('faq-section'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald509f1dd991e98b5837bfe6e90a061dc)): ?>
<?php $attributes = $__attributesOriginald509f1dd991e98b5837bfe6e90a061dc; ?>
<?php unset($__attributesOriginald509f1dd991e98b5837bfe6e90a061dc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald509f1dd991e98b5837bfe6e90a061dc)): ?>
<?php $component = $__componentOriginald509f1dd991e98b5837bfe6e90a061dc; ?>
<?php unset($__componentOriginald509f1dd991e98b5837bfe6e90a061dc); ?>
<?php endif; ?>

    
    <?php if(config('services.ga4.measurement_id') && $products->count()): ?>
    <?php
        $ga4Items = $products->getCollection()->values()->map(function ($p, $i) {
            return [
                'item_id' => $p->sku ?? (string) $p->id,
                'item_name' => $p->name,
                'item_category' => $p->category?->name ?? '',
                'item_brand' => $p->brand?->name ?? '',
                'price' => (float) $p->price,
                'index' => $i,
            ];
        });
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            gtag('event', 'view_item_list', {
                item_list_id: 'all_products',
                item_list_name: 'All Products',
                items: <?php echo json_encode($ga4Items, JSON_UNESCAPED_UNICODE); ?>

            });
        });
    </script>
    <?php endif; ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5863877a5171c196453bfa0bd807e410)): ?>
<?php $attributes = $__attributesOriginal5863877a5171c196453bfa0bd807e410; ?>
<?php unset($__attributesOriginal5863877a5171c196453bfa0bd807e410); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5863877a5171c196453bfa0bd807e410)): ?>
<?php $component = $__componentOriginal5863877a5171c196453bfa0bd807e410; ?>
<?php unset($__componentOriginal5863877a5171c196453bfa0bd807e410); ?>
<?php endif; ?>
<?php /**PATH D:\projects\grytlabs345\frontends/default/views/products/index.blade.php ENDPATH**/ ?>