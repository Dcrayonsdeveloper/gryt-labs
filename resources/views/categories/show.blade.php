<x-layouts.app>
    @php
        $storeName = $theme->get('store_name', config('app.name'));
        $seo = $category->seo_data ?? [];
        $seoTitle = $seo['meta_title'] ?? "{$category->name} — {$storeName}";
        $catDesc = $seo['meta_description'] ?? $category->description ?? "Shop {$category->name} at {$storeName}. Browse {$products->total()} products with great prices.";
        $catDesc = preg_replace('/\s+/', ' ', strip_tags($catDesc));
    @endphp
    <x-slot name="title">{{ $seoTitle }}</x-slot>

    @push('meta')
        <meta name="description" content="{{ Str::limit(strip_tags($catDesc), 160) }}">
        @if(!empty($seo['keywords']))
        <meta name="keywords" content="{{ $seo['keywords'] }}">
        @endif
        <link rel="canonical" href="{{ url('/collections/' . $category->slug) }}">
        <meta property="og:title" content="{{ $seoTitle }}">
        <meta property="og:description" content="{{ Str::limit(strip_tags($catDesc), 160) }}">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url('/collections/' . $category->slug) }}">
        @if($category->image_url)
        <meta property="og:image" content="{{ asset('storage/' . $category->image_url) }}">
        @endif
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $seoTitle }}">
        <meta name="twitter:description" content="{{ Str::limit(strip_tags($catDesc), 160) }}">

        @php
            $breadcrumbItems = [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Home',
                    'item' => url('/'),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => 'Categories',
                    'item' => route('categories.index'),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 3,
                    'name' => $category->name,
                    'item' => route('categories.show', $category->slug),
                ],
            ];

            $collectionSchema = [
                '@context' => 'https://schema.org',
                '@graph' => [
                    [
                        '@type' => 'CollectionPage',
                        'name' => $seoTitle,
                        'description' => Str::limit(strip_tags($catDesc), 300),
                        'url' => route('categories.show', $category->slug),
                        'numberOfItems' => $products->total(),
                        'isPartOf' => [
                            '@type' => 'WebSite',
                            'name' => $storeName,
                            'url' => url('/'),
                        ],
                    ],
                    [
                        '@type' => 'BreadcrumbList',
                        'itemListElement' => $breadcrumbItems,
                    ],
                ],
            ];

            if ($category->image_url) {
                $collectionSchema['@graph'][0]['image'] = asset('storage/' . $category->image_url);
            }
        @endphp
        <script type="application/ld+json">{!! json_encode($collectionSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    @endpush

    <!-- Category Header -->
    @php $headerBg = isset($theme) ? $theme->get('breadcrumb_bg_image') : ''; @endphp
    @if($headerBg)
    <div class="relative overflow-hidden w-full" style="background-image: url('{{ asset($headerBg) }}'); background-size: cover; background-position: center; background-repeat: no-repeat; height: 224px;">
        <div class="absolute inset-0" style="background: rgba(0,0,0,0.35);"></div>
        <div class="relative container mx-auto px-4 h-full flex flex-col justify-center">
            <h1 class="text-2xl md:text-3xl font-bold text-white mb-1">{{ $category->name }}</h1>
            @if($category->description)
                <p class="text-sm max-w-lg line-clamp-2 text-white/90">{{ strip_tags(html_entity_decode($category->description)) }}</p>
            @endif
            <p class="text-xs mt-2 text-white/80">{{ $products->total() }} products</p>
        </div>
    </div>
    @else
    <div class="relative overflow-hidden" style="background: linear-gradient(135deg, var(--color-primary-600) 0%, var(--brand-800) 100%); height: 224px;">
        <div class="absolute inset-0 w-full h-full" style="background: linear-gradient(135deg, var(--color-primary-600) 0%, var(--color-primary-700) 50%, var(--brand-900) 100%); opacity: 0.6;"></div>
        <div class="absolute inset-0 bg-linear-to-r from-black/50 via-black/20 to-transparent"></div>
        <div class="relative container mx-auto px-4 h-full flex flex-col justify-center">
            <h1 class="text-2xl md:text-3xl font-bold text-white mb-1">{{ $category->name }}</h1>
            @if($category->description)
                <p class="text-sm max-w-lg line-clamp-2" style="color: rgba(255,255,255,0.9);">{{ strip_tags(html_entity_decode($category->description)) }}</p>
            @endif
            <p class="text-xs mt-2" style="color: rgba(255,255,255,0.8);">{{ $products->total() }} products</p>
        </div>
    </div>
    @endif

    <!-- Subcategories -->
    @if($subcategories->count())
        <div class="bg-white border-b border-neutral-200 sticky top-14 z-20">
            <div class="container mx-auto px-4">
                <div class="flex gap-1 overflow-x-auto py-2.5 scrollbar-hide">
                    <a href="{{ route('category.show', $category) }}"
                       class="shrink-0 px-4 py-1.5 rounded-full text-sm font-medium transition-colors {{ !request('subcategory') ? 'bg-primary-600 text-white shadow-sm' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200' }}">
                        All
                    </a>
                    @foreach($subcategories as $sub)
                        <a href="{{ route('category.show', $sub) }}"
                           class="shrink-0 px-4 py-1.5 rounded-full text-sm font-medium bg-neutral-100 text-neutral-600 hover:bg-neutral-200 transition-colors">
                            {{ $sub->name }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div class="container mx-auto px-4 py-6">
        <!-- Active Filters -->
        @if(request()->hasAny(['subcategory', 'min_price', 'max_price', 'in_stock', 'on_sale']))
            <div class="flex flex-wrap items-center gap-2 mb-5">
                <span class="text-xs font-medium text-neutral-600 uppercase tracking-wide">Active Filters:</span>
                @if(request('subcategory'))
                    @foreach((array) request('subcategory') as $subSlug)
                        @php $subName = $filterSubcategories->firstWhere('slug', $subSlug)?->name ?? $subSlug; @endphp
                        <a href="{{ request()->fullUrlWithoutQuery('subcategory') }}"
                           class="inline-flex items-center gap-1 px-2.5 py-1 bg-primary-600/5 text-primary-700 text-xs font-medium rounded-full border border-primary-600/30 hover:bg-primary-600/10 transition-colors">
                            {{ $subName }}
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </a>
                    @endforeach
                @endif
                @if(request('min_price') || request('max_price'))
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-primary-600/5 text-primary-700 text-xs font-medium rounded-full border border-primary-600/30">
                        @price(request('min_price', 0)) - @price(request('max_price', '...'))
                    </span>
                @endif
                @if(request('in_stock'))
                    <span class="inline-flex items-center px-2.5 py-1 bg-primary-600/5 text-primary-700 text-xs font-medium rounded-full border border-primary-600/30">In Stock</span>
                @endif
                @if(request('on_sale'))
                    <span class="inline-flex items-center px-2.5 py-1 bg-primary-600/5 text-primary-700 text-xs font-medium rounded-full border border-primary-600/30">On Sale</span>
                @endif
                <a href="{{ route('category.show', $category) }}" class="text-xs text-neutral-600 hover:text-primary-600 underline ml-1">Clear all</a>
            </div>
        @endif

        <div class="flex flex-col lg:flex-row gap-6">
            <!-- Filters Sidebar -->
            <aside class="lg:w-60 shrink-0" x-data="{ mobileOpen: false }">
                <!-- Mobile filter toggle -->
                <button @click="mobileOpen = true"
                        class="lg:hidden w-full flex items-center justify-center gap-2 px-3 py-1.5 bg-white border border-neutral-200 rounded-lg text-sm font-medium text-neutral-700 hover:border-neutral-300 transition-colors mb-4">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    Filters
                    @if(request()->hasAny(['brand', 'min_price', 'max_price', 'in_stock', 'on_sale']))
                        <span class="w-5 h-5 bg-accent-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center">
                            {{ count(array_filter([request('brand'), request('min_price'), request('max_price'), request('in_stock'), request('on_sale')])) }}
                        </span>
                    @endif
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
                            @include('categories.partials.filters')
                        </div>
                    </div>
                </div>

                <!-- Desktop filters -->
                <div class="hidden lg:block">
                    @include('categories.partials.filters')
                </div>
            </aside>

            <!-- Products Grid -->
            <div class="flex-1 min-w-0">
                <!-- Sort Bar -->
                <div class="flex items-center justify-between mb-5 pb-4 border-b border-neutral-100">
                    <p class="text-sm text-neutral-600">
                        <span class="font-semibold text-neutral-900">{{ $products->total() }}</span> products found
                    </p>

                    <div class="flex items-center gap-2">
                        <label class="text-xs text-neutral-600 hidden sm:inline">Sort by:</label>
                        <select onchange="window.location.href = '{{ route('category.show', $category) }}?' + new URLSearchParams({...Object.fromEntries(new URLSearchParams(window.location.search)), sort: this.value})"
                                class="text-sm py-1.5 pl-3 pr-8 border border-neutral-200 rounded-lg bg-white text-neutral-700 focus:outline-none focus:border-primary-600 cursor-pointer">
                            <option value="newest" {{ request('sort') === 'newest' ? 'selected' : '' }}>Newest</option>
                            <option value="price_asc" {{ request('sort') === 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
                            <option value="price_desc" {{ request('sort') === 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
                            <option value="rating" {{ request('sort') === 'rating' ? 'selected' : '' }}>Best Rating</option>
                            <option value="bestselling" {{ request('sort') === 'bestselling' ? 'selected' : '' }}>Bestselling</option>
                        </select>
                    </div>
                </div>

                @if($products->count())
                    <div x-data="{
                        page: {{ $products->currentPage() }},
                        loading: false,
                        hasMore: {{ $products->hasMorePages() ? 'true' : 'false' }},
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
                    }" x-init="new IntersectionObserver((e) => { if (e[0].isIntersecting) loadMore(); }, { rootMargin: '200px' }).observe($refs.sentinel)">
                        <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3 md:gap-4" x-ref="grid">
                            @foreach($products as $product)
                                <x-product-card :product="$product" />
                            @endforeach
                        </div>
                        <div x-ref="sentinel" class="h-4"></div>
                        <div x-show="loading" x-cloak class="flex justify-center py-8">
                            <svg class="animate-spin h-6 w-6 text-primary-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        </div>
                    </div>
                @else
                    <div class="text-center py-20">
                        <div class="w-20 h-20 mx-auto mb-4 bg-neutral-100 rounded-full flex items-center justify-center">
                            <svg class="w-10 h-10 text-neutral-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-neutral-900 mb-1">No products found</h3>
                        <p class="text-sm text-neutral-600 mb-5">Try adjusting your filters or browse all products.</p>
                        <a href="{{ route('category.show', $category) }}" class="inline-flex items-center gap-2 px-3 py-1.5 bg-accent-500 hover:bg-accent-600 text-white text-sm font-semibold rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            Clear All Filters
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <x-trust-badges />
    <x-faq-section />

    {{-- GA4 view_item_list --}}
    @if(config('services.ga4.measurement_id') && $products->count())
    @php
        $ga4Items = $products->getCollection()->values()->map(function ($p, $i) use ($category) {
            return [
                'item_id' => $p->sku ?? (string) $p->id,
                'item_name' => $p->name,
                'item_category' => $category->name,
                'item_brand' => $p->brand?->name ?? '',
                'price' => (float) $p->price,
                'index' => $i,
            ];
        });
    @endphp
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            gtag('event', 'view_item_list', {
                item_list_id: 'category_{{ $category->id }}',
                item_list_name: {!! json_encode($category->name, JSON_UNESCAPED_UNICODE) !!},
                items: {!! json_encode($ga4Items, JSON_UNESCAPED_UNICODE) !!}
            });
        });
    </script>
    @endif
</x-layouts.app>
