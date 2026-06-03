<x-layouts.app>
    @php
        $storeName = $theme->get('store_name', config('app.name'));
        $legalName = $theme->get('legal_name', $storeName);
        $tagline = $theme->get('site_tagline', 'Beauty & Wellness, Naturally');
        $address = $theme->get('company_address', '');
        $gstin = $theme->get('company_gstin', '');
        $phone = $theme->get('store_phone', $theme->get('contact_phone', ''));
        $email = $theme->get('store_email', $theme->get('contact_email', ''));
        $aboutHtml = $theme->get('about_html_content', '');
        $aboutText = $theme->get('about_story', '');
        $aboutMission = $theme->get('about_mission', '');
        $aboutProducts = $theme->get('about_products', '');
        $ctaHeading = $theme->get('about_cta_heading', "Start Your Natural Beauty Journey");
        $ctaText = $theme->get('about_cta_text', "Explore our curated collection of natural skincare and haircare products.");
        $heroImage = $theme->get('about_hero_image', '');
        $instagram = $theme->get('social_instagram', '');
        $youtube = $theme->get('social_youtube', '');
    @endphp

    <x-slot name="title">About Us - {{ $storeName }}</x-slot>

    @push('meta')
        <meta name="description" content="Learn about {{ $storeName }} - {{ $tagline }}. Natural skincare and haircare products made in India.">
        <link rel="canonical" href="{{ url('/pages/about-us') }}">
        <meta property="og:title" content="About Us - {{ $storeName }}">
        <meta property="og:description" content="Learn about {{ $storeName }} - {{ $tagline }}">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url('/pages/about-us') }}">
        @if($heroImage)
        <meta property="og:image" content="{{ $heroImage }}">
        @endif

        @php
            $aboutSchema = [
                '@context' => 'https://schema.org',
                '@type' => 'Organization',
                'name' => $storeName,
                'legalName' => $legalName,
                'url' => url('/'),
                'logo' => asset($theme->get('store_logo', 'images/logo.png')),
                'description' => $tagline,
                'email' => $email,
                'telephone' => $phone,
                'address' => [
                    '@type' => 'PostalAddress',
                    'streetAddress' => $address,
                    'addressCountry' => 'IN',
                ],
                'sameAs' => array_filter([
                    $instagram,
                    $theme->get('social_facebook'),
                    $youtube,
                    $theme->get('social_pinterest'),
                ]),
            ];
        @endphp
        <script type="application/ld+json">{!! json_encode($aboutSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    @endpush

    @push('styles')
    <style>
        .about-content h2 { margin-top: 0; margin-bottom: 1rem; }
        .about-content h3 { margin-top: 0; margin-bottom: 0.5rem; }
        .about-content p { margin-bottom: 1.25rem; line-height: 1.85; color: #525252; }
        .about-content p:last-child { margin-bottom: 0; }
        .about-content ul { list-style: none; padding-left: 0; margin-bottom: 1rem; }
        .about-content ul li { position: relative; padding-left: 1.5rem; margin-bottom: 0.5rem; }
        .about-content ul li::before { content: ''; position: absolute; left: 0; top: 0.55em; width: 8px; height: 8px; border-radius: 50%; background: var(--color-primary-400, #ffa8d2); }
        .about-content img { border-radius: 1rem; }
    </style>
    @endpush

    <!-- HERO -->
    <section class="bg-white">
        <div class="container mx-auto px-4 py-16 sm:py-20 lg:py-24">
            <div class="max-w-2xl mx-auto text-center">
                <span class="inline-flex items-center gap-1.5 text-[11px] font-bold text-primary-500 uppercase tracking-[0.2em] mb-4">
                    <span class="w-5 h-px bg-primary-400"></span>
                    About {{ $storeName }}
                    <span class="w-5 h-px bg-primary-400"></span>
                </span>
                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-neutral-900 leading-tight mb-5">
                    {{ $tagline }}
                </h1>
                <p class="text-[15px] sm:text-base text-neutral-600 leading-relaxed max-w-xl mx-auto mb-8">
                    {{ Str::limit($aboutText, 250) }}
                </p>
                <div class="flex flex-wrap items-center justify-center gap-3">
                    <a href="{{ route('products.index') }}" class="inline-flex items-center gap-2 px-6 py-2.5 bg-primary-500 hover:bg-primary-600 text-white text-sm font-semibold rounded-full transition-colors">
                        Explore Products
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                    <a href="{{ route('contact') }}" class="inline-flex items-center gap-2 px-6 py-2.5 border border-neutral-300 hover:border-primary-400 text-neutral-800 text-sm font-semibold rounded-full transition-colors">
                        Contact Us
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- TRUST STRIP -->
    <section class="bg-white border-b border-neutral-100">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-2 md:grid-cols-4 divide-x divide-neutral-100">
                <div class="flex items-center justify-center gap-2.5 py-5">
                    <div class="w-9 h-9 rounded-full bg-primary-50 flex items-center justify-center shrink-0">
                        <svg class="w-4.5 h-4.5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-neutral-900">100% Natural</p>
                        <p class="text-[10px] text-neutral-500">Clean ingredients</p>
                    </div>
                </div>
                <div class="flex items-center justify-center gap-2.5 py-5">
                    <div class="w-9 h-9 rounded-full bg-primary-50 flex items-center justify-center shrink-0">
                        <svg class="w-4.5 h-4.5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/></svg>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-neutral-900">Cruelty Free</p>
                        <p class="text-[10px] text-neutral-500">Never tested on animals</p>
                    </div>
                </div>
                <div class="flex items-center justify-center gap-2.5 py-5">
                    <div class="w-9 h-9 rounded-full bg-primary-50 flex items-center justify-center shrink-0">
                        <svg class="w-4.5 h-4.5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.9 17.9 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/></svg>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-neutral-900">Free Shipping</p>
                        <p class="text-[10px] text-neutral-500">Above {{ currency_symbol() }}{{ $theme->get('free_shipping_threshold', 499) }}</p>
                    </div>
                </div>
                <div class="flex items-center justify-center gap-2.5 py-5">
                    <div class="w-9 h-9 rounded-full bg-primary-50 flex items-center justify-center shrink-0">
                        <svg class="w-4.5 h-4.5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-neutral-900">Made in India</p>
                        <p class="text-[10px] text-neutral-500">For Indian skin</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ABOUT CONTENT (rich HTML from settings) -->
    @if($aboutHtml)
    <section class="py-14 sm:py-18 lg:py-20 bg-white">
        <div class="container mx-auto px-4">
            <div class="max-w-6xl mx-auto">
                <div class="about-content text-[15px] text-neutral-700">
                    {!! $aboutHtml !!}
                </div>
            </div>
        </div>
    </section>
    @elseif($aboutText)
    <section class="py-14 sm:py-18 lg:py-20 bg-white">
        <div class="container mx-auto px-4">
            <div class="max-w-3xl mx-auto">
                <div class="text-center mb-10">
                    <h2 class="text-2xl sm:text-3xl font-bold text-neutral-900">The {{ $storeName }} Story</h2>
                </div>
                <p class="text-[15px] text-neutral-700 leading-relaxed">{{ $aboutText }}</p>
            </div>
        </div>
    </section>
    @endif

    <!-- VALUES GRID (only show when no custom about_html_content, to avoid duplicate sections) -->
    @if(!$aboutHtml)
    <section class="py-14 sm:py-18 bg-neutral-50">
        <div class="container mx-auto px-4">
            <div class="max-w-5xl mx-auto">
                <div class="text-center mb-10">
                    <span class="inline-flex items-center gap-1.5 text-[11px] font-bold text-primary-500 uppercase tracking-[0.2em] mb-3">
                        <span class="w-5 h-px bg-primary-400"></span>
                        Our Values
                        <span class="w-5 h-px bg-primary-400"></span>
                    </span>
                    <h2 class="text-2xl sm:text-3xl font-bold text-neutral-900">What We Stand For</h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    {{-- Mission --}}
                    @if($aboutMission)
                    <div class="bg-white rounded-2xl p-6 border border-neutral-100 hover:border-primary-200 hover:shadow-sm transition-all">
                        <div class="w-10 h-10 bg-primary-100 rounded-xl flex items-center justify-center mb-4">
                            <svg class="w-5 h-5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </div>
                        <h3 class="text-sm font-bold text-neutral-900 mb-2">Our Mission</h3>
                        <p class="text-[13px] text-neutral-600 leading-relaxed">{{ $aboutMission }}</p>
                    </div>
                    @endif

                    {{-- Promise --}}
                    @if($aboutProducts)
                    <div class="bg-white rounded-2xl p-6 border border-neutral-100 hover:border-primary-200 hover:shadow-sm transition-all">
                        <div class="w-10 h-10 bg-primary-100 rounded-xl flex items-center justify-center mb-4">
                            <svg class="w-5 h-5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 11.25v8.25a1.5 1.5 0 01-1.5 1.5H5.25a1.5 1.5 0 01-1.5-1.5v-8.25M12 4.875A2.625 2.625 0 109.375 7.5H12m0-2.625V7.5m0-2.625A2.625 2.625 0 1114.625 7.5H12m0 0V21m-8.625-9.75h18c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125h-18c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
                        </div>
                        <h3 class="text-sm font-bold text-neutral-900 mb-2">Our Promise</h3>
                        <p class="text-[13px] text-neutral-600 leading-relaxed">{{ $aboutProducts }}</p>
                    </div>
                    @endif

                    {{-- Quality --}}
                    <div class="bg-white rounded-2xl p-6 border border-neutral-100 hover:border-primary-200 hover:shadow-sm transition-all">
                        <div class="w-10 h-10 bg-primary-100 rounded-xl flex items-center justify-center mb-4">
                            <svg class="w-5 h-5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/></svg>
                        </div>
                        <h3 class="text-sm font-bold text-neutral-900 mb-2">Quality First</h3>
                        <p class="text-[13px] text-neutral-600 leading-relaxed">Every product is made without harmful toxins, harsh chemicals, or overhyped claims. Just functional ingredients, designed to do what they say.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @endif

    <!-- PRODUCTS SHOWCASE -->
    @if($products->count())
    <section class="py-14 sm:py-18 bg-white">
        <div class="container mx-auto px-4">
            <div class="max-w-6xl mx-auto">
                <div class="text-center mb-10">
                    <span class="inline-flex items-center gap-1.5 text-[11px] font-bold text-primary-500 uppercase tracking-[0.2em] mb-3">
                        <span class="w-5 h-px bg-primary-400"></span>
                        Our Collection
                        <span class="w-5 h-px bg-primary-400"></span>
                    </span>
                    <h2 class="text-2xl sm:text-3xl font-bold text-neutral-900 mb-2">Crafted for Your Wellness</h2>
                    <p class="text-sm text-neutral-600 max-w-lg mx-auto">Natural skincare and haircare products formulated for Indian skin and weather.</p>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 lg:gap-5">
                    @foreach($products as $product)
                        <x-product-card :product="$product" />
                    @endforeach
                </div>

                <div class="text-center mt-8">
                    <a href="{{ route('products.index') }}" class="inline-flex items-center gap-2 px-6 py-2.5 bg-primary-500 hover:bg-primary-600 text-white text-sm font-semibold rounded-full transition-colors">
                        View All Products
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </div>
        </div>
    </section>
    @endif

    <!-- COMPANY INFO -->
    @if($legalName || $gstin || $address)
    <section class="py-12 bg-neutral-50 border-t border-neutral-100">
        <div class="container mx-auto px-4">
            <div class="max-w-3xl mx-auto">
                <div class="bg-white border border-neutral-200 rounded-2xl p-6 sm:p-7">
                    <h3 class="text-xs font-bold text-neutral-500 uppercase tracking-wider mb-4">Company Information</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                        @if($legalName)
                        <div>
                            <p class="text-[11px] font-semibold text-neutral-500 uppercase mb-0.5">Legal Name</p>
                            <p class="text-neutral-900 text-[13px]">{{ $legalName }}</p>
                        </div>
                        @endif
                        @if($email)
                        <div>
                            <p class="text-[11px] font-semibold text-neutral-500 uppercase mb-0.5">Email</p>
                            <p class="text-neutral-900 text-[13px]">{{ $email }}</p>
                        </div>
                        @endif
                        @if($phone)
                        <div>
                            <p class="text-[11px] font-semibold text-neutral-500 uppercase mb-0.5">Phone</p>
                            <p class="text-neutral-900 text-[13px]">{{ $phone }}</p>
                        </div>
                        @endif
                        @if($gstin)
                        <div>
                            <p class="text-[11px] font-semibold text-neutral-500 uppercase mb-0.5">GSTIN</p>
                            <p class="text-neutral-900 text-[13px] font-mono">{{ $gstin }}</p>
                        </div>
                        @endif
                        @if($address)
                        <div class="sm:col-span-2">
                            <p class="text-[11px] font-semibold text-neutral-500 uppercase mb-0.5">Registered Address</p>
                            <p class="text-neutral-900 text-[13px]">{{ $address }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
    @endif

    <!-- CTA -->
    <section class="py-14 sm:py-18 bg-white">
        <div class="container mx-auto px-4">
            <div class="max-w-3xl mx-auto">
                <div class="rounded-2xl border border-neutral-200 bg-white p-10 sm:p-12 text-center">
                    <h2 class="text-2xl sm:text-3xl font-bold text-neutral-900 mb-3">{{ $ctaHeading }}</h2>
                    <p class="text-sm text-neutral-600 mb-7 max-w-md mx-auto">{{ $ctaText }}</p>
                        <div class="flex flex-wrap items-center justify-center gap-3">
                            <a href="{{ route('products.index') }}" class="inline-flex items-center gap-2 px-6 py-2.5 bg-primary-500 hover:bg-primary-600 text-white text-sm font-bold rounded-full transition-colors">
                                Shop Now
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </a>
                            @if($instagram)
                            <a href="{{ $instagram }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-semibold text-neutral-800 border border-neutral-300 rounded-full hover:border-primary-400 transition-colors">
                                Follow Us
                            </a>
                            @endif
                        </div>
                </div>
            </div>
        </div>
    </section>

</x-layouts.app>
