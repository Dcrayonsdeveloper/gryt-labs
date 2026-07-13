<x-layouts.app>
    <x-slot name="title">{{ $theme->get('seo_homepage_title', '') ?: $siteSettings['site_name'] . ' - ' . $siteSettings['site_tagline'] }}</x-slot>

    @push('meta')
        @php $metaDesc = $theme->get('meta_description', $siteSettings['site_tagline'] . ' - Shop online at ' . $siteSettings['site_name'] . '.'); @endphp
        <meta name="description" content="{{ $metaDesc }}">
        <link rel="canonical" href="{{ url('/') }}">
        <meta property="og:title" content="{{ $siteSettings['site_name'] }} - {{ $siteSettings['site_tagline'] }}">
        <meta property="og:description" content="{{ $metaDesc }}">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url('/') }}">
        @if($siteSettings['site_logo'] ?? $siteSettings['site_favicon'] ?? null)
        <meta property="og:image" content="{{ asset($siteSettings['site_logo'] ?: $siteSettings['site_favicon']) }}">
        @endif
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $siteSettings['site_name'] }} - {{ $siteSettings['site_tagline'] }}">
        <meta name="twitter:description" content="{{ $metaDesc }}">

        {{-- Organization + WebSite JSON-LD --}}
        <?php
        $companyAddress = $theme->get('company_address', '');
        $companyGstin = $theme->get('company_gstin', '');
        $companyCity = $theme->get('company_city', '');
        $companyState = $theme->get('company_state', '');
        $companyPincode = $theme->get('company_pincode', '');
        $foundingYear = $theme->get('founding_year', '');

        // Build full PostalAddress with available fields
        $postalAddress = ['@type' => 'PostalAddress', 'addressCountry' => 'IN'];
        if ($companyAddress) $postalAddress['streetAddress'] = $companyAddress;
        if ($companyCity) $postalAddress['addressLocality'] = $companyCity;
        if ($companyState) $postalAddress['addressRegion'] = $companyState;
        if ($companyPincode) $postalAddress['postalCode'] = $companyPincode;

        // Build sameAs (all social profiles for entity linking)
        $sameAs = array_values(array_filter([
            $theme->get('social_instagram', ''),
            $theme->get('social_facebook', ''),
            $theme->get('social_twitter', ''),
            $theme->get('social_youtube', ''),
            $theme->get('social_linkedin', ''),
            $theme->get('social_pinterest', ''),
        ]));

        $organization = [
            '@type' => 'Organization',
            '@id' => url('/') . '#organization',
            'name' => $siteSettings['site_name'],
            'url' => url('/'),
            'logo' => ['@type' => 'ImageObject', 'url' => asset($theme->get('store_logo', 'images/logo.png'))],
            'image' => asset($theme->get('store_logo', 'images/logo.png')),
            'description' => $siteSettings['site_tagline'],
            'email' => $theme->get('contact_email', ''),
            'telephone' => $theme->get('contact_phone', ''),
            'legalName' => $theme->get('legal_name', config('app.name')),
            'address' => $postalAddress,
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'contactType' => 'customer service',
                'telephone' => $theme->get('contact_phone', ''),
                'email' => $theme->get('contact_email', ''),
                'url' => route('contact'),
                'availableLanguage' => ['English', 'Hindi'],
                'contactOption' => 'TollFree',
            ],
            'areaServed' => ['@type' => 'Country', 'name' => 'India'],
        ];

        if (!empty($sameAs)) $organization['sameAs'] = $sameAs;
        if ($foundingYear) $organization['foundingDate'] = $foundingYear;
        if ($companyGstin) $organization['taxID'] = $companyGstin;

        $homeSchema = [
            '@context' => 'https://schema.org',
            '@graph' => [
                $organization,
                [
                    '@type' => 'WebSite',
                    '@id' => url('/') . '#website',
                    'name' => $siteSettings['site_name'],
                    'url' => url('/'),
                    'publisher' => ['@id' => url('/') . '#organization'],
                    'inLanguage' => config('app.locale', 'en-IN'),
                    'potentialAction' => [
                        '@type' => 'SearchAction',
                        'target' => ['@type' => 'EntryPoint', 'urlTemplate' => url('/products') . '?search={search_term_string}'],
                        'query-input' => 'required name=search_term_string',
                    ],
                ],
            ],
        ];
        ?>
        <script type="application/ld+json">{!! json_encode($homeSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    @endpush

    <x-slot name="styles">
        @php $__fe = config('app.frontend', 'default'); @endphp
        @vite(["frontends/{$__fe}/css/home.css"])
    </x-slot>

    {{-- Flash Sale Popup --}}
    @if($flashSale)
        <div x-data="flashSalePopup({{ $flashSale->remaining_time }}, '{{ $flashSale->slug }}')"
             x-show="open" x-cloak
             @keydown.escape.window="dismiss()"
             class="fixed inset-0 z-60 flex items-center justify-center p-4">
            <div x-show="open"
                 x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 @click="dismiss()" class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
            <div x-show="open"
                 x-transition:enter="transition ease-out duration-300 delay-100" x-transition:enter-start="opacity-0 scale-90 translate-y-4" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-90"
                 class="relative w-full max-w-md overflow-hidden rounded-2xl shadow-2xl" @click.stop>
                <button @click="dismiss()" class="absolute top-3 right-3 w-8 h-8 flex items-center justify-center text-white/80 hover:text-white rounded-full hover:bg-white/10 transition-colors z-10">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
                <div class="relative bg-linear-to-br from-accent-500 via-accent-600 to-accent-600 px-6 pt-8 pb-6 text-center overflow-hidden">
                    <div class="absolute -top-10 -left-10 w-40 h-40 bg-white/5 rounded-full"></div>
                    <div class="absolute -bottom-8 -right-8 w-32 h-32 bg-white/5 rounded-full"></div>
                    <div class="relative inline-flex items-center justify-center w-14 h-14 bg-white/15 rounded-full mb-4 ring-4 ring-white/10">
                        <svg class="w-7 h-7 text-yellow-200" fill="currentColor" viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <p class="text-white/80 text-xs font-semibold tracking-widest uppercase mb-1">Limited Time Offer</p>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-white leading-tight mb-2">{{ $flashSale->name }}</h2>
                    @if($flashSale->description)
                        <p class="text-white/80 text-sm leading-relaxed max-w-xs mx-auto mb-4">{{ Str::limit($flashSale->description, 100) }}</p>
                    @endif
                    <div class="flex items-center justify-center gap-2 sm:gap-3">
                        <div class="bg-white/15 backdrop-blur-sm rounded-xl px-3 py-2 min-w-15">
                            <span class="block text-2xl font-bold text-white tabular-nums" x-text="hours">00</span>
                            <span class="block text-[10px] text-white/70 uppercase tracking-wide">Hours</span>
                        </div>
                        <span class="text-2xl font-bold text-white/50">:</span>
                        <div class="bg-white/15 backdrop-blur-sm rounded-xl px-3 py-2 min-w-15">
                            <span class="block text-2xl font-bold text-white tabular-nums" x-text="minutes">00</span>
                            <span class="block text-[10px] text-white/70 uppercase tracking-wide">Mins</span>
                        </div>
                        <span class="text-2xl font-bold text-white/50">:</span>
                        <div class="bg-white/15 backdrop-blur-sm rounded-xl px-3 py-2 min-w-15">
                            <span class="block text-2xl font-bold text-white tabular-nums" x-text="seconds">00</span>
                            <span class="block text-[10px] text-white/70 uppercase tracking-wide">Secs</span>
                        </div>
                    </div>
                </div>
                <div class="bg-white px-6 py-5 text-center">
                    <p class="text-xs text-neutral-600 mb-3">
                        <span class="font-semibold text-neutral-700">{{ $flashSale->products_count }} {{ Str::plural('product', $flashSale->products_count) }}</span> on sale
                    </p>
                    <a href="{{ route('products.index') }}?flash_sale={{ $flashSale->slug }}" @click="dismiss()"
                       class="inline-flex items-center justify-center gap-2 w-full py-2 bg-accent-500 hover:bg-accent-600 text-white text-sm font-bold rounded-xl shadow-lg transition-all hover:-translate-y-0.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                        Shop the Sale Now
                    </a>
                    <button @click="dismiss()" class="mt-2 text-xs text-neutral-600 hover:text-neutral-600 transition-colors">No thanks, maybe later</button>
                </div>
            </div>
        </div>
        <script>
            function flashSalePopup(remainingSeconds, saleSlug) {
                return {
                    open: false, remaining: remainingSeconds, timer: null,
                    get hours() { return String(Math.floor(this.remaining / 3600)).padStart(2, '0'); },
                    get minutes() { return String(Math.floor((this.remaining % 3600) / 60)).padStart(2, '0'); },
                    get seconds() { return String(this.remaining % 60).padStart(2, '0'); },
                    init() {
                        const key = 'flash_sale_dismissed_' + saleSlug;
                        if (sessionStorage.getItem(key)) return;
                        setTimeout(() => { this.open = true; document.body.style.overflow = 'hidden'; }, 1500);
                        this.timer = setInterval(() => {
                            if (this.remaining > 0) { this.remaining--; } else { clearInterval(this.timer); this.dismiss(); }
                        }, 1000);
                    },
                    dismiss() {
                        this.open = false; document.body.style.overflow = '';
                        sessionStorage.setItem('flash_sale_dismissed_' + saleSlug, '1');
                        if (this.timer) clearInterval(this.timer);
                    }
                };
            }
        </script>
    @endif

    <!-- ==========================================
         HERO BANNER SLIDER
         ========================================== -->
    {{-- SEO: H1 heading for homepage (skip if categories_grid provides a visible H1) --}}
    @if(!isset($sections['categories_grid']) || !$sections['categories_grid']->is_active)
        <h1 class="sr-only">{{ $theme->get('store_name', config('app.name')) }} — {{ $theme->get('site_tagline', '') }}</h1>
    @endif

    @if($banners->count())
    <section class="hero-banner"
             x-data="{
                current: 0,
                slides: [
                    @foreach($banners as $banner)
                    { img: '{{ asset('storage/' . $banner->image_url) }}', link: '{{ $banner->link ?? route('products.index') }}' },
                    @endforeach
                ],
                timer: null,
                init() { this.startTimer(); },
                startTimer() { this.timer = setInterval(() => this.next(), 5000); },
                next() { this.current = (this.current + 1) % this.slides.length; },
                prev() { this.current = (this.current - 1 + this.slides.length) % this.slides.length; },
                goTo(i) { this.current = i; clearInterval(this.timer); this.startTimer(); }
             }">
        <div class="hero-slides">
            <template x-for="(slide, index) in slides" :key="index">
                <a :href="slide.link"
                   x-show="current === index"
                   x-transition:enter="transition-opacity ease-out duration-500"
                   x-transition:enter-start="opacity-0"
                   x-transition:enter-end="opacity-100"
                   x-transition:leave="transition-opacity ease-in duration-300"
                   x-transition:leave-start="opacity-100"
                   x-transition:leave-end="opacity-0"
                   class="hero-slide block">
                    <img :src="slide.img" :alt="'{{ $siteSettings['site_name'] }}'">
                </a>
            </template>

            <!-- Dots -->
            <div class="hero-dots">
                <template x-for="(slide, index) in slides" :key="'dot-'+index">
                    <button @click="goTo(index)" class="hero-dot" :class="current === index ? 'active' : ''"></button>
                </template>
            </div>
        </div>
        {{-- Waves removed - clean edge --}}
    </section>
    @endif

    <!-- ==========================================
         SHOP BY CATEGORY - SEO Grid (per-tenant via homepage_sections)
         ========================================== -->
    @if(isset($sections['categories_grid']) && $sections['categories_grid']->is_active && isset($categories) && $categories->count())
        @php $catGrid = $sections['categories_grid']; @endphp
        <section class="py-10 lg:py-14 bg-white">
            <div class="container mx-auto px-4">
                <div class="text-center mb-8">
                    <h1 class="text-2xl sm:text-3xl font-bold text-neutral-900 mb-2">{{ $catGrid->title ?? 'Shop by Category' }}</h1>
                    @if($catGrid->subtitle)
                        <p class="text-sm sm:text-base text-neutral-600 max-w-2xl mx-auto">{{ $catGrid->subtitle }}</p>
                    @endif
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4">
                    @foreach($categories as $cat)
                        <a href="{{ route('category.show', $cat) }}" class="group block rounded-xl overflow-hidden border border-neutral-200 hover:border-primary-400 hover:shadow-md transition-all bg-white">
                            <div class="bg-[#f8f6f3] overflow-hidden" style="height:160px;">
                                @php
                                    $catImg = $cat->image_url ? asset('storage/' . $cat->image_url) : ($cat->products->first()?->primary_image_url ?? null);
                                @endphp
                                @if($catImg)
                                    <img src="{{ $catImg }}" alt="{{ $cat->name }}" class="w-full h-full object-contain group-hover:scale-105 transition-transform duration-300" loading="lazy">
                                @else
                                    <div class="w-full h-full flex items-center justify-center">
                                        <svg class="w-12 h-12 text-primary-600/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                                        </svg>
                                    </div>
                                @endif
                            </div>
                            <div class="px-3 py-2.5 text-center">
                                <h3 class="text-sm font-semibold text-neutral-800 group-hover:text-primary-600 transition-colors">{{ $cat->name }}</h3>
                                @if($cat->products_count)
                                    <p class="text-xs text-neutral-500 mt-0.5">{{ $cat->products_count }} Products</p>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <!-- ==========================================
         CATEGORY CAROUSEL
         ========================================== -->
    @if(isset($carouselCategories) && $carouselCategories->count() && $carouselCategories->contains(fn($c) => $c->products_count > 5))
        <section class="py-5 lg:py-6 bg-white">
            <div class="container mx-auto px-4">
                <div class="flex gap-5 overflow-x-auto scrollbar-hide pb-1" style="-ms-overflow-style:none;scrollbar-width:none;">
                    @foreach($carouselCategories as $cat)
                        <a href="{{ route('category.show', $cat) }}"
                           class="flex flex-col items-center gap-2 shrink-0 group"
                           style="min-width: 120px; max-width: 130px;">
                            @php
                                $catImage = null;
                                if ($cat->image_url) {
                                    $catImage = asset('storage/' . $cat->image_url);
                                } elseif ($cat->products->first()?->primary_image_url) {
                                    $catImage = $cat->products->first()->primary_image_url;
                                }
                            @endphp
                            <div class="rounded-full overflow-hidden border-2 border-transparent group-hover:border-primary-600 transition-all bg-[#f8f6f3] flex items-center justify-center shadow-sm" style="width: 96px; height: 96px;">
                                @if($catImage)
                                    <img src="{{ $catImage }}"
                                         alt="{{ $cat->name }}"
                                         class="w-full h-full object-cover"
                                         loading="lazy">
                                @elseif($cat->icon)
                                    <span class="text-2xl">{{ $cat->icon }}</span>
                                @else
                                    <svg class="w-6 h-6 text-primary-600/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                                    </svg>
                                @endif
                            </div>
                            <span class="text-[11px] lg:text-xs font-medium text-[#0F1111] text-center leading-tight line-clamp-2 group-hover:text-primary-600 transition-colors">
                                {{ $cat->name }}
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Category section removed --}}

    <!-- ==========================================
         FEATURED PRODUCTS - Horizontal Slider
         ========================================== -->
    @if($featuredProducts->count() && (!isset($sections['featured']) || $sections['featured']->is_active))
        <section class="py-8 lg:py-12 bg-white">
            <div class="container mx-auto px-4">
                <div class="section-header">
                    <h2 class="section-title">{{ $sections['featured']->title ?? 'New Arrivals' }}</h2>
                    <a href="{{ route('products.index') }}" class="view-all-link">
                        View All
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                @if($featuredProducts->count() <= 6)
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                    @foreach($featuredProducts as $product)
                        <x-product-card :product="$product" :compact="true" />
                    @endforeach
                </div>
                @else
                <div class="relative" x-data="{ el: null }" x-init="el = $refs.featuredSlider">
                    <button @click="el.scrollBy({left: -300, behavior: 'smooth'})" class="absolute left-0 top-1/2 -translate-y-1/2 z-10 w-8 h-8 bg-white/90 rounded-full shadow flex items-center justify-center hover:bg-white" style="left: -4px;" aria-label="Previous products">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <button @click="el.scrollBy({left: 300, behavior: 'smooth'})" class="absolute right-0 top-1/2 -translate-y-1/2 z-10 w-8 h-8 bg-white/90 rounded-full shadow flex items-center justify-center hover:bg-white" style="right: -4px;" aria-label="Next products">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                    <div class="product-slider" x-ref="featuredSlider">
                        @foreach($featuredProducts->take(10) as $product)
                            <div class="slide-item">
                                <x-product-card :product="$product" :compact="true" />
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </section>
    @endif

    <!-- ==========================================
         SHOP OUR REELS - Shoppable Instagram Carousel
         ========================================== -->
    <x-instagram-reels />

    <!-- ==========================================
         COFFEE LOVERS COLLECTION
         ========================================== -->
    @if(isset($coffeeProducts) && $coffeeProducts->count())
        <section class="py-8 lg:py-12 bg-white">
            <div class="container mx-auto px-4">
                <div class="section-header">
                    <h2 class="section-title">Love Over Coffee</h2>
                    <a href="{{ route('products.index') }}?search=coffee" class="view-all-link">
                        View All
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <div class="product-slider">
                    @foreach($coffeeProducts as $product)
                        <div class="slide-item">
                            <x-product-card :product="$product" :compact="true" />
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Categories already rendered after hero slider --}}

    <!-- ==========================================
         VIDEO TESTIMONIALS (per-tenant via homepage_sections)
         ========================================== -->
    @if(isset($sections['video_testimonials']) && $sections['video_testimonials']->is_active)
        @php
            $vtSection = $sections['video_testimonials'];
            $vtVideos = [];
            if (is_array($vtSection->content) && isset($vtSection->content['videos'])) {
                $vtVideos = $vtSection->content['videos'];
            }
            if (empty($vtVideos)) {
                $vtSetting = $theme->get('testimonial_videos', '');
                $vtVideos = $vtSetting ? json_decode($vtSetting, true) : [];
            }
        @endphp
        @if(count($vtVideos))
        <section class="py-10 lg:py-14 bg-white">
            <div class="container mx-auto px-4">
                <div class="text-center mb-6">
                    <h2 class="text-xl sm:text-2xl font-bold text-neutral-900 mb-1">{{ $vtSection->title ?? 'Customer Testimonials' }}</h2>
                    @if($vtSection->subtitle)
                        <p class="text-sm text-neutral-600">{{ $vtSection->subtitle }}</p>
                    @endif
                </div>
                <div class="flex gap-4 justify-center overflow-x-auto pb-2" style="-ms-overflow-style:none;scrollbar-width:none;">
                    @foreach($vtVideos as $vid)
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
            </div>
        </section>
        @endif
    @endif

    <!-- ==========================================
         BESTSELLERS - Horizontal Slider
         ========================================== -->
    @if($bestsellers->count() && (!isset($sections['bestsellers']) || $sections['bestsellers']->is_active))
        <section class="py-8 lg:py-12 bg-white" style="background-color:#F1F7E8">
            <div class="container mx-auto px-4">
                <div class="section-header">
                    <h2 class="section-title">{{ $sections['bestsellers']->title ?? 'Our Products' }}</h2>
                    <a href="{{ route('bestsellers') }}" class="view-all-link">
                        View All
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <div class="relative" x-data="{ el: null }" x-init="el = $refs.bestsellerSlider">
                    <button @click="el.scrollBy({left: -300, behavior: 'smooth'})" class="absolute left-0 top-1/2 -translate-y-1/2 z-10 w-8 h-8 bg-white/90 rounded-full shadow flex items-center justify-center hover:bg-white" style="left: -4px;" aria-label="Previous products">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <button @click="el.scrollBy({left: 300, behavior: 'smooth'})" class="absolute right-0 top-1/2 -translate-y-1/2 z-10 w-8 h-8 bg-white/90 rounded-full shadow flex items-center justify-center hover:bg-white" style="right: -4px;" aria-label="Next products">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                    <div class="product-slider" x-ref="bestsellerSlider">
                        @foreach($bestsellers->take(10) as $product)
                            <div class="slide-item">
                                <x-product-card :product="$product" :compact="true" />
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    <!-- ==========================================
         PRODUCT BANNER 1 (Configurable)
         ========================================== -->
    @if(isset($sections['product_banner_1']) && $sections['product_banner_1']->is_active && $sections['product_banner_1']->image_url)
        <section class="bg-white">
            <a href="{{ $sections['product_banner_1']->button_link ?? route('products.index') }}" class="block">
                <img src="{{ asset('storage/' . $sections['product_banner_1']->image_url) }}" alt="{{ $sections['product_banner_1']->title }}" class="w-full h-auto object-cover" loading="lazy">
            </a>
        </section>
    @endif

    <!-- ==========================================
         WHY CHOOSE US - Feature Grid
         ========================================== -->
    @if(isset($sections['benefits']) && $sections['benefits']->is_active && is_array($sections['benefits']->content))
    @php $benefitsSection = $sections['benefits']; @endphp
    <section class="features-section bg-white">
        <div class="container mx-auto px-4">
            <div class="features-header">
                <h2 class="features-heading" style="font-size: 22px;">{{ $benefitsSection->title }}</h2>
                @if($benefitsSection->button_text)
                    <a href="{{ $benefitsSection->button_link ?? route('products.index') }}" class="view-all-link">
                        {{ $benefitsSection->button_text }}
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                    </a>
                @endif
            </div>
            <div class="features-grid">
                @foreach($benefitsSection->content as $benefit)
                    <div class="feature-card">
                        <div class="feature-icon">
                            @include('partials.benefit-icon', ['icon' => $benefit['icon'] ?? 'default'])
                        </div>
                        <h3>{{ $benefit['title'] }}</h3>
                        <p>{{ $benefit['description'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    <!-- ==========================================
         TODAY'S DEALS
         ========================================== -->
    @if($deals->count() && (!isset($sections['deals']) || $sections['deals']->is_active))
        <section class="deals-section bg-white" style="background-color:#F1F7E8">
            <div class="container mx-auto px-4">
                <div class="section-header">
                    <h2 class="section-title">{{ $sections['deals']->title ?? "Steal Deals" }}</h2>
                    <a href="{{ route('products.index') }}?on_sale=1" class="view-all-link">
                        View All
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <div class="relative" x-data="{ el: null }" x-init="el = $refs.dealsSlider">
                    <button @click="el.scrollBy({left: -300, behavior: 'smooth'})" class="absolute left-0 top-1/2 -translate-y-1/2 z-10 w-8 h-8 bg-white/90 rounded-full shadow flex items-center justify-center hover:bg-white" style="left: -4px;" aria-label="Previous deals">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <button @click="el.scrollBy({left: 300, behavior: 'smooth'})" class="absolute right-0 top-1/2 -translate-y-1/2 z-10 w-8 h-8 bg-white/90 rounded-full shadow flex items-center justify-center hover:bg-white" style="right: -4px;" aria-label="Next deals">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                    <div class="product-slider" x-ref="dealsSlider">
                        @foreach($deals->take(12) as $product)
                            <div class="slide-item">
                                <x-product-card :product="$product" :compact="true" />
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    <!-- ==========================================
         PRODUCT BANNER 2 (Configurable)
         ========================================== -->
    @if(isset($sections['product_banner_2']) && $sections['product_banner_2']->is_active && $sections['product_banner_2']->image_url)
        <section class="bg-white">
            <a href="{{ $sections['product_banner_2']->button_link ?? route('products.index') }}" class="block">
                <img src="{{ asset('storage/' . $sections['product_banner_2']->image_url) }}" alt="{{ $sections['product_banner_2']->title }}" class="w-full h-auto object-cover" loading="lazy">
            </a>
        </section>
    @endif

    <!-- ==========================================
         PROMO BANNER (CTA)
         ========================================== -->
    @if(isset($sections['promo_banner']) && $sections['promo_banner']->is_active)
        @php $promo = $sections['promo_banner']; @endphp
        <div x-data="{ showConsultation: false }" @open-consultation.window="showConsultation = true">
        <section class="relative overflow-hidden" style="background-color: {{ $promo->background_color ?? 'var(--color-primary-600)' }};"
                >
            @if($promo->image_url)
                <img src="{{ asset('storage/' . $promo->image_url) }}" alt="{{ $promo->title }}" class="absolute inset-0 w-full h-full object-cover">
                <div class="absolute inset-0 bg-black/60"></div>
            @endif
            <div class="container mx-auto px-4 relative z-10 py-14 lg:py-20 text-center">
                <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-white mb-3" style="color: {{ $promo->text_color ?? '#ffffff' }};">{{ $promo->title }}</h2>
                @if($promo->subtitle)
                    <p class="text-base sm:text-lg mb-6 max-w-xl mx-auto" style="color: {{ $promo->text_color ?? '#ffffff' }}; opacity: 0.85;">{{ $promo->subtitle }}</p>
                @endif
                @if($promo->button_text)
                    @if($theme->get('consultation_enabled') === '1')
                        <button onclick="var m=document.getElementById('consultModal');document.body.appendChild(m);m.style.display='flex';document.body.style.overflow='hidden';" type="button" class="inline-flex items-center gap-2 px-8 py-3 bg-white text-primary-600 rounded-full font-semibold text-sm hover:bg-neutral-100 transition-colors shadow-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                            {{ $promo->button_text }}
                        </button>
                    @else
                        <a href="{{ $promo->button_link ?? route('products.index') }}" class="inline-flex items-center gap-2 px-8 py-3 bg-white text-primary-600 rounded-full font-semibold text-sm hover:bg-neutral-100 transition-colors shadow-lg">
                            {{ $promo->button_text }}
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    @endif
                @endif
            </div>

        </section>

        </div>
    @endif

    <!-- ==========================================
         HAPPY CUSTOMERS / TESTIMONIALS
         ========================================== -->
    @if($testimonials->count() && (!isset($sections['testimonials']) || $sections['testimonials']->is_active))
        <section class="testimonial-section" style="background-color:#F1F7E8;">
            <div class="container mx-auto px-4">
                <div class="testimonial-layout">
                    {{-- Static Title Card --}}
                    <div class="testimonial-title-card">
                        <h2>{{ $sections['testimonials']->title ?? 'Happy Customers' }}</h2>
                        <p>{{ $sections['testimonials']->subtitle ?? ($testimonials->count() . '+ reviews from happy customers') }}</p>
                    </div>

                    {{-- Scrollable Testimonial Carousel with arrows --}}
                    <div class="testimonial-carousel-wrap" x-data="{ el: null }" x-init="el = $refs.tCarousel">
                        <button @click="el.scrollBy({left: -300, behavior: 'smooth'})" class="absolute left-0 top-1/2 -translate-y-1/2 z-10 w-8 h-8 bg-white/90 rounded-full shadow flex items-center justify-center hover:bg-white" style="left: -4px;">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <button @click="el.scrollBy({left: 300, behavior: 'smooth'})" class="absolute right-0 top-1/2 -translate-y-1/2 z-10 w-8 h-8 bg-white/90 rounded-full shadow flex items-center justify-center hover:bg-white" style="right: -4px;">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                        <div class="testimonial-carousel" x-ref="tCarousel">
                            @foreach($testimonials as $testimonial)
                                <div class="testimonial-card">
                                    <div class="testimonial-stars">★★★★★</div>
                                    <p class="testimonial-text">"{{ Str::limit($testimonial->content, 120) }}"</p>
                                    <div class="testimonial-author">
                                        @if($testimonial->avatar_url)
                                            <img src="{{ asset('storage/' . $testimonial->avatar_url) }}" alt="{{ $testimonial->name }}" class="w-9 h-9 rounded-full object-cover">
                                        @else
                                            <div class="testimonial-avatar">{{ strtoupper(substr($testimonial->name, 0, 1)) }}</div>
                                        @endif
                                        <div>
                                            <div class="testimonial-name">{{ $testimonial->name }}</div>
                                            <div class="testimonial-label">Verified Buyer</div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    <!-- ==========================================
         VIEW ON AMAZON (only if store has Amazon link)
         ========================================== -->
    @php $amazonUrl = $theme->get('amazon_store_url', ''); @endphp
    @if($amazonUrl)
    <section style="background:#232f3e;">
        <a href="{{ $amazonUrl }}" target="_blank" rel="noopener" class="block">
            <div class="container mx-auto px-4 flex flex-col sm:flex-row items-center justify-center gap-3 sm:gap-6 py-5 sm:py-7">
                <img src="{{ asset('images/amazon-logo.jpg') }}" alt="Amazon" class="h-10 sm:h-12 w-auto rounded-lg" loading="lazy">
                <div class="text-white text-center sm:text-left">
                    <p class="text-base sm:text-lg font-bold m-0">Also Available on Amazon</p>
                    <p class="text-[11px] sm:text-xs text-white/60 mt-0.5">Shop {{ $theme->get('store_name', config('app.name')) }} with Prime delivery & easy returns</p>
                </div>
                <span class="bg-[#FF9900] text-[#0F1111] px-5 py-2 sm:px-6 sm:py-2.5 rounded-full text-xs sm:text-sm font-bold whitespace-nowrap">Shop on Amazon</span>
            </div>
        </a>
    </section>
    @endif

    <!-- ==========================================
         NEWSLETTER SIGNUP
         ========================================== -->
    @php
        $nlSection = $sections['newsletter'] ?? null;
        $nlTitle = $nlSection->title ?? $theme->get('newsletter_heading', 'Get 10% Off Your First Order!');
        $nlSubtitle = $nlSection->subtitle ?? $theme->get('newsletter_subtitle', 'Sign up for our newsletter and receive exclusive deals, new arrivals, and shopping tips.');
        $nlBtnText = $nlSection->button_text ?? 'Get 10% Off';
    @endphp
    <section class="newsletter" x-data="{ email: '', loading: false, message: '', success: false }">
        <div class="newsletter-inner">
            <h2>{{ $nlTitle }}</h2>
            <p>{{ $nlSubtitle }}</p>
            <form class="newsletter-form" @submit.prevent="
                if (!email) return;
                loading = true; message = '';
                fetch('/newsletter/subscribe', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: JSON.stringify({ email, source: 'homepage_newsletter', first_buyer_discount: true })
                })
                .then(r => r.json())
                .then(d => { loading = false; success = d.success; message = d.message || 'Check your email for the discount code!'; })
                .catch(() => { loading = false; message = 'Something went wrong. Please try again.'; })
            ">
                <template x-if="!success">
                    <div class="newsletter-fields">
                        <input type="email" x-model="email" class="newsletter-input" placeholder="Enter your email address" required>
                        <button type="submit" class="newsletter-btn" :disabled="loading">
                            <span x-show="!loading">{{ $nlBtnText }}</span>
                            <span x-show="loading" x-cloak>...</span>
                        </button>
                    </div>
                </template>
                <p x-show="message" x-text="message" class="text-sm mt-3" :class="success ? 'text-green-900 font-semibold' : 'text-red-800 font-semibold'" x-cloak></p>
            </form>
            <p class="newsletter-disclaimer">No spam, ever. Unsubscribe anytime.</p>
        </div>
    </section>

    {{-- Dynamic page_sections render on minimal/custom themes only (see themes/minimal/views/home.blade.php) --}}
    {{-- The default homepage uses its own built-in sections above --}}

    <!-- ==========================================
         RECENTLY VIEWED
         ========================================== -->
    <div class="container mx-auto px-4">
        <x-recently-viewed />
    </div>

    <!-- ==========================================
         TRUST BADGES + FAQ
         ========================================== -->
    <x-trust-badges />
    <x-faq-section />

    {{-- Consultation Popup — Pure JS, no Alpine dependency --}}
    @if($theme->get('consultation_enabled') === '1')
    <div id="consultModal" onclick="if(event.target===this){this.style.display='none';document.body.style.overflow='';}" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);backdrop-filter:blur(4px);z-index:999999;align-items:center;justify-content:center;padding:12px;">
        <div style="background:#fff;border-radius:16px;max-width:420px;width:100%;box-shadow:0 25px 60px rgba(0,0,0,0.3);position:relative;">
            <button onclick="document.getElementById('consultModal').style.display='none';document.body.style.overflow='';" style="position:absolute;top:10px;right:10px;z-index:10;width:28px;height:28px;border-radius:50%;background:rgba(255,255,255,0.2);border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:15px;color:#fff;">&times;</button>
            <div style="padding:16px 20px;background:linear-gradient(135deg, var(--color-primary-600), var(--color-primary-700));border-radius:16px 16px 0 0;display:flex;align-items:center;gap:12px;">
                <div style="width:36px;height:36px;background:rgba(255,255,255,0.15);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg width="18" height="18" fill="none" stroke="#fff" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <div>
                    <h3 style="font-size:16px;font-weight:700;color:#fff;margin:0;">Book Free Consultation</h3>
                    <p style="font-size:11px;color:rgba(255,255,255,0.7);margin:0;">Get expert advice from our specialists</p>
                </div>
            </div>
            <div id="consultForm" style="padding:14px 20px 18px;">
                <div style="display:flex;flex-direction:column;gap:10px;">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                        <div>
                            <label style="display:block;font-size:11px;font-weight:600;color:#374151;margin-bottom:3px;">Name</label>
                            <input type="text" id="cName" placeholder="Your name" style="width:100%;padding:8px 10px;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;box-sizing:border-box;background:#f9fafb;">
                        </div>
                        <div>
                            <label style="display:block;font-size:11px;font-weight:600;color:#374151;margin-bottom:3px;">Phone</label>
                            <input type="tel" id="cPhone" placeholder="+91 98765 43210" style="width:100%;padding:8px 10px;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;box-sizing:border-box;background:#f9fafb;">
                        </div>
                    </div>
                    <div>
                        <label style="display:block;font-size:11px;font-weight:600;color:#374151;margin-bottom:3px;">Consultation Type</label>
                        <select id="cType" style="width:100%;padding:8px 10px;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;box-sizing:border-box;background:#f9fafb;color:#374151;">
                            <option value="">Select consultation...</option>
                            <option>Energy & Stamina</option>
                            <option>Liver Health</option>
                            <option>Skin Radiance</option>
                            <option>Nutrition & Immunity</option>
                            <option>Heart Health</option>
                        </select>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                        <div>
                            <label style="display:block;font-size:11px;font-weight:600;color:#374151;margin-bottom:3px;">Preferred Date</label>
                            <input type="date" id="cDate" min="{{ now()->format('Y-m-d') }}" style="width:100%;padding:8px 10px;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;box-sizing:border-box;background:#f9fafb;">
                        </div>
                        <div>
                            <label style="display:block;font-size:11px;font-weight:600;color:#374151;margin-bottom:3px;">Preferred Time</label>
                            <select id="cTime" style="width:100%;padding:8px 10px;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;box-sizing:border-box;background:#f9fafb;color:#374151;">
                                <option value="">Select time...</option>
                                <option value="09-10 AM">09-10 AM</option>
                                <option value="10-11 AM">10-11 AM</option>
                                <option value="11-12 PM">11-12 PM</option>
                                <option value="12-01 PM">12-01 PM</option>
                                <option value="02-03 PM">02-03 PM</option>
                                <option value="03-04 PM">03-04 PM</option>
                                <option value="04-05 PM">04-05 PM</option>
                                <option value="05-06 PM">05-06 PM</option>
                            </select>
                        </div>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                        <div>
                            <label style="display:block;font-size:11px;font-weight:600;color:#374151;margin-bottom:3px;">Email <span style="font-weight:400;color:#9ca3af;">optional</span></label>
                            <input type="email" id="cEmail" placeholder="your@email.com" style="width:100%;padding:8px 10px;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;box-sizing:border-box;background:#f9fafb;">
                        </div>
                        <div>
                            <label style="display:block;font-size:11px;font-weight:600;color:#374151;margin-bottom:3px;">Query <span style="font-weight:400;color:#9ca3af;">optional</span></label>
                            <input type="text" id="cMsg" placeholder="Health concern..." style="width:100%;padding:8px 10px;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;box-sizing:border-box;background:#f9fafb;">
                        </div>
                    </div>
                    <div id="cError" style="font-size:11px;color:#dc2626;display:none;background:#fef2f2;padding:6px 10px;border-radius:6px;"></div>
                    <button onclick="submitConsultation()" id="cBtn" style="width:100%;padding:11px;background:var(--color-primary-600);color:#fff;font-weight:600;border:none;border-radius:10px;font-size:14px;cursor:pointer;margin-top:2px;">Book Free Consultation</button>
                </div>
            </div>
            <div id="consultSuccess" style="display:none;padding:40px 32px;text-align:center;">
                <div style="width:64px;height:64px;margin:0 auto 16px;background:#dcfce7;border-radius:50%;display:flex;align-items:center;justify-content:center;">
                    <svg width="32" height="32" fill="none" stroke="#16a34a" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
                <h4 style="font-size:18px;font-weight:700;color:#1f2937;margin:0 0 4px;">Thank You!</h4>
                <p style="font-size:13px;color:#6b7280;margin:0;">Our specialist will contact you within 24 hours.</p>
            </div>
        </div>
    </div>
    <script>
    function submitConsultation(){
        var t=document.getElementById('cType').value, n=document.getElementById('cName').value, p=document.getElementById('cPhone').value, e=document.getElementById('cEmail').value, m=document.getElementById('cMsg').value, d=document.getElementById('cDate').value, tm=document.getElementById('cTime').value, err=document.getElementById('cError');
        if(!t||!n||!p||!d||!tm){err.textContent='Please fill all required fields';err.style.display='block';return;}
        err.style.display='none';
        document.getElementById('cBtn').textContent='Submitting...';
        var msg='Consultation: '+t+'\nPreferred Date: '+d+'\nPreferred Time: '+tm+(m?'\nQuery: '+m:'');
        fetch('/pages/contact',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'},body:JSON.stringify({name:n,phone:p,email:e||'',subject:'Consultation: '+t,message:msg})})
        .then(function(){document.getElementById('consultForm').style.display='none';document.getElementById('consultSuccess').style.display='block';setTimeout(function(){document.getElementById('consultModal').style.display='none';document.body.style.overflow='';document.getElementById('consultForm').style.display='block';document.getElementById('consultSuccess').style.display='none';document.getElementById('cBtn').textContent='Book Free Consultation';document.getElementById('cType').value='';document.getElementById('cName').value='';document.getElementById('cPhone').value='';document.getElementById('cEmail').value='';document.getElementById('cMsg').value='';document.getElementById('cDate').value='';document.getElementById('cTime').value='';},3000);})
        .catch(function(){err.textContent='Something went wrong.';err.style.display='block';document.getElementById('cBtn').textContent='Book Free Consultation';});
    }
    </script>
    @endif

</x-layouts.app>
