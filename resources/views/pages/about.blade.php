<x-layouts.app>
    @php
        $storeName = $theme->get('store_name', config('app.name'));
        $legalName = $theme->get('legal_name', $storeName);
        $tagline = $theme->get('site_tagline', 'Built Through Purpose. Driven By Grit.');
        $address = $theme->get('company_address', '');
        $gstin = $theme->get('company_gstin', '');
        $phone = $theme->get('store_phone', $theme->get('contact_phone', ''));
        $email = $theme->get('store_email', $theme->get('contact_email', ''));
        $aboutHtml = $theme->get('about_html_content', '');
        $aboutText = $theme->get('about_story', '');
        $aboutMission = $theme->get('about_mission', '');
        $aboutProducts = $theme->get('about_products', '');
        $ctaHeading = $theme->get('about_cta_heading', "Start Building Strength For Life");
        $ctaText = $theme->get('about_cta_text', "Explore our range of clean, science-backed supplements made for long-term wellness.");
        $storeLogo = asset($theme->get('store_logo', 'images/logo.png'));
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
        :root {
            --gryt-lime: #B8DC24;
            --gryt-lime-dark: #a3c61c;
            --gryt-lime-tint: #f4fadf;
            --gryt-ink: #1c1c1c;
        }

        /* Brand buttons */
        .gryt-btn-lime { background: var(--gryt-lime); color: var(--gryt-ink); }
        .gryt-btn-lime:hover { background: var(--gryt-lime-dark); }
        .gryt-btn-ghost { border: 1px solid rgba(255,255,255,.25); color: #fff; }
        .gryt-btn-ghost:hover { border-color: var(--gryt-lime); color: var(--gryt-lime); }
        .gryt-btn-dark { background: var(--gryt-ink); color: #fff; }
        .gryt-btn-dark:hover { background: #000; }
        .gryt-btn-outline-dark { border: 1px solid #d4d4d4; color: var(--gryt-ink); }
        .gryt-btn-outline-dark:hover { border-color: var(--gryt-lime); color: #4d6d00; }

        /* Hero */
        .gryt-hero { background: var(--gryt-ink); position: relative; overflow: hidden; }
        .gryt-hero::before {
            content: ''; position: absolute; top: -20%; right: -10%;
            width: 480px; height: 480px; border-radius: 9999px;
            background: radial-gradient(circle, rgba(184,220,36,.22) 0%, rgba(184,220,36,0) 70%);
            pointer-events: none;
        }
        .gryt-hero::after {
            content: ''; position: absolute; bottom: -30%; left: -8%;
            width: 360px; height: 360px; border-radius: 9999px;
            background: radial-gradient(circle, rgba(184,220,36,.12) 0%, rgba(184,220,36,0) 70%);
            pointer-events: none;
        }
        .gryt-eyebrow { color: var(--gryt-lime); }
        .gryt-eyebrow .bar { background: var(--gryt-lime); }

        /* Rich about content */
        .about-content { color: #3f3f46; }
        .about-content > *:first-child { margin-top: 0; }
        .about-content h2 {
            font-size: 1.5rem; line-height: 1.25; font-weight: 800; color: var(--gryt-ink);
            margin-top: 2.75rem; margin-bottom: 1.1rem; padding-bottom: .65rem; position: relative;
        }
        .about-content h2::after {
            content: ''; position: absolute; left: 0; bottom: 0;
            width: 52px; height: 4px; border-radius: 9999px; background: var(--gryt-lime);
        }
        .about-content h3 { font-size: 1.075rem; font-weight: 700; color: var(--gryt-ink); margin-top: 1.4rem; margin-bottom: .4rem; }
        .about-content p { margin-bottom: 1.1rem; line-height: 1.85; color: #52525b; }
        .about-content p:last-child { margin-bottom: 0; }
        .about-content strong { color: var(--gryt-ink); font-weight: 700; }
        .about-content ul { list-style: none; padding-left: 0; margin: 0 0 1.25rem; }
        .about-content ul li { position: relative; padding-left: 1.85rem; margin-bottom: .65rem; line-height: 1.6; color: #3f3f46; }
        .about-content ul li::before {
            content: ''; position: absolute; left: 0; top: .42em;
            width: 13px; height: 13px; border-radius: 3px; background: var(--gryt-lime); transform: rotate(45deg);
        }
        .about-content img { border-radius: 1rem; }
    </style>
    @endpush

    <!-- HERO -->
    <section class="gryt-hero">
        <div class="container mx-auto px-4 py-16 sm:py-20 lg:py-24 relative z-10">
            <div class="max-w-2xl mx-auto text-center">
                <div class="inline-flex items-center justify-center bg-white rounded-2xl px-5 py-3 mb-7 shadow-lg shadow-black/20">
                    <img src="{{ $storeLogo }}" alt="{{ $storeName }}" class="h-10 sm:h-12 w-auto" style="max-width:200px;">
                </div>
                <div class="gryt-eyebrow inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[0.25em] mb-4">
                    <span class="bar w-5 h-px"></span>
                    About {{ $storeName }}
                    <span class="bar w-5 h-px"></span>
                </div>
                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-white leading-tight mb-5">
                    {{ $tagline }}
                </h1>
                <p class="text-[15px] sm:text-base text-neutral-300 leading-relaxed max-w-xl mx-auto mb-4">
                    {{ Str::limit($aboutText, 250) }}
                </p>
                <p class="text-[13px] sm:text-sm text-neutral-400 mb-8">
                    A brand owned by <span class="font-semibold text-white">Suyash Enterprise</span>
                </p>
                <div class="flex flex-wrap items-center justify-center gap-3">
                    <a href="{{ route('products.index') }}" class="gryt-btn-lime inline-flex items-center gap-2 px-6 py-3 text-sm font-bold rounded-full transition-colors">
                        Explore Products
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                    <a href="{{ route('contact') }}" class="gryt-btn-ghost inline-flex items-center gap-2 px-6 py-3 text-sm font-semibold rounded-full transition-colors">
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
                @php
                    $trustItems = [
                        ['title' => 'GMP Certified', 'sub' => 'Quality manufacturing', 'path' => 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                        ['title' => 'FSSAI Compliant', 'sub' => 'Safe & regulated', 'path' => 'M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z'],
                        ['title' => 'Lab Tested', 'sub' => 'Purity assured', 'path' => 'M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23-.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5'],
                        ['title' => 'Vegetarian Friendly', 'sub' => 'Clean nutrition', 'path' => 'M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z'],
                    ];
                @endphp
                @foreach($trustItems as $item)
                <div class="flex items-center justify-center gap-2.5 py-5">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0" style="background: var(--gryt-lime-tint);">
                        <svg class="w-4.5 h-4.5" style="color:#4d6d00;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $item['path'] }}"/></svg>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-neutral-900">{{ $item['title'] }}</p>
                        <p class="text-[10px] text-neutral-500">{{ $item['sub'] }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- ABOUT CONTENT (rich HTML from settings) -->
    @if($aboutHtml)
    <section class="py-14 sm:py-18 lg:py-20 bg-white">
        <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto">
                <div class="about-content text-[15px]">
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
                    <span class="block w-14 h-1 rounded-full mx-auto mt-4" style="background: var(--gryt-lime);"></span>
                </div>
                <p class="text-[15px] text-neutral-700 leading-relaxed">{{ $aboutText }}</p>
            </div>
        </div>
    </section>
    @endif

    <!-- FOUNDER -->
    @php $founderPhoto = $theme->get('founder_photo_url', ''); @endphp
    <section class="py-14 sm:py-16 bg-neutral-50">
        <div class="container mx-auto px-4">
            <div class="max-w-3xl mx-auto">
                <div class="bg-white rounded-2xl border border-neutral-100 p-6 sm:p-8">
                    <div class="flex flex-col sm:flex-row items-center sm:items-start gap-6">
                        <div class="shrink-0 text-center">
                            @if($founderPhoto)
                                <img src="{{ $founderPhoto }}" alt="Prabhat Ranjan — Founder, {{ $storeName }}"
                                     class="w-28 h-28 sm:w-32 sm:h-32 rounded-2xl object-cover border border-neutral-100 shadow-sm">
                            @else
                                <div class="w-28 h-28 sm:w-32 sm:h-32 rounded-2xl flex items-center justify-center text-3xl font-extrabold"
                                     style="background: var(--gryt-lime-tint); color:#4d6d00;">PR</div>
                            @endif
                            <a href="https://www.linkedin.com/in/prabhnz" target="_blank" rel="noopener"
                               class="mt-3 inline-flex items-center justify-center gap-1.5 px-3.5 py-1.5 text-xs font-semibold rounded-full text-white transition-opacity hover:opacity-90"
                               style="background:#0A66C2;">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
                                LinkedIn
                            </a>
                        </div>
                        <div class="text-center sm:text-left">
                            <span class="gryt-eyebrow inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[0.2em] mb-2">
                                <span class="bar w-5 h-px"></span>
                                Meet the Founder
                            </span>
                            <h2 class="text-xl sm:text-2xl font-extrabold text-neutral-900">Prabhat Ranjan</h2>
                            <p class="text-[13px] font-semibold mt-0.5" style="color:#4d6d00;">Founder, {{ $storeName }}</p>
                            <p class="text-[14px] text-neutral-600 leading-relaxed mt-3">
                                GRYT was born from Prabhat's own journey — years of fitness discipline built while living and
                                working in New Zealand, and a deeper purpose shaped by watching a close family member fight
                                serious health challenges. The name comes from <strong>grit</strong>: the ability to stay
                                resilient, focused and committed no matter how difficult the journey becomes. That belief —
                                that wellness should be built on honesty, transparency and consistency rather than quick
                                fixes — is what every {{ $storeName }} product stands on.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- VALUES GRID (only show when no custom about_html_content, to avoid duplicate sections) -->
    @if(!$aboutHtml)
    <section class="py-14 sm:py-18 bg-neutral-50">
        <div class="container mx-auto px-4">
            <div class="max-w-5xl mx-auto">
                <div class="text-center mb-10">
                    <span class="gryt-eyebrow inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[0.2em] mb-3">
                        <span class="bar w-5 h-px"></span>
                        Our Values
                        <span class="bar w-5 h-px"></span>
                    </span>
                    <h2 class="text-2xl sm:text-3xl font-bold text-neutral-900">What We Stand For</h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    {{-- Mission --}}
                    @if($aboutMission)
                    <div class="bg-white rounded-2xl p-6 border border-neutral-100 hover:shadow-sm transition-all" onmouseover="this.style.borderColor='var(--gryt-lime)'" onmouseout="this.style.borderColor=''">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-4" style="background: var(--gryt-lime-tint);">
                            <svg class="w-5 h-5" style="color:#4d6d00;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </div>
                        <h3 class="text-sm font-bold text-neutral-900 mb-2">Our Mission</h3>
                        <p class="text-[13px] text-neutral-600 leading-relaxed">{{ $aboutMission }}</p>
                    </div>
                    @endif

                    {{-- Promise --}}
                    @if($aboutProducts)
                    <div class="bg-white rounded-2xl p-6 border border-neutral-100 hover:shadow-sm transition-all" onmouseover="this.style.borderColor='var(--gryt-lime)'" onmouseout="this.style.borderColor=''">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-4" style="background: var(--gryt-lime-tint);">
                            <svg class="w-5 h-5" style="color:#4d6d00;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 11.25v8.25a1.5 1.5 0 01-1.5 1.5H5.25a1.5 1.5 0 01-1.5-1.5v-8.25M12 4.875A2.625 2.625 0 109.375 7.5H12m0-2.625V7.5m0-2.625A2.625 2.625 0 1114.625 7.5H12m0 0V21m-8.625-9.75h18c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125h-18c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
                        </div>
                        <h3 class="text-sm font-bold text-neutral-900 mb-2">Our Promise</h3>
                        <p class="text-[13px] text-neutral-600 leading-relaxed">{{ $aboutProducts }}</p>
                    </div>
                    @endif

                    {{-- Quality --}}
                    <div class="bg-white rounded-2xl p-6 border border-neutral-100 hover:shadow-sm transition-all" onmouseover="this.style.borderColor='var(--gryt-lime)'" onmouseout="this.style.borderColor=''">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-4" style="background: var(--gryt-lime-tint);">
                            <svg class="w-5 h-5" style="color:#4d6d00;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/></svg>
                        </div>
                        <h3 class="text-sm font-bold text-neutral-900 mb-2">Quality First</h3>
                        <p class="text-[13px] text-neutral-600 leading-relaxed">Every product is made in GMP-certified, FSSAI-compliant facilities — clean, lab-tested formulations with no overhyped claims. Just functional nutrition that does what it says.</p>
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
                    <span class="gryt-eyebrow inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[0.2em] mb-3">
                        <span class="bar w-5 h-px"></span>
                        Our Collection
                        <span class="bar w-5 h-px"></span>
                    </span>
                    <h2 class="text-2xl sm:text-3xl font-bold text-neutral-900 mb-2">Crafted for Your Wellness</h2>
                    <p class="text-sm text-neutral-600 max-w-lg mx-auto">Clean, science-backed supplements built for strength, recovery, and everyday wellness.</p>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 lg:gap-5">
                    @foreach($products as $product)
                        <x-product-card :product="$product" />
                    @endforeach
                </div>

                <div class="text-center mt-8">
                    <a href="{{ route('products.index') }}" class="gryt-btn-dark inline-flex items-center gap-2 px-6 py-3 text-sm font-bold rounded-full transition-colors">
                        View All Products
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </div>
        </div>
    </section>
    @endif

    <!-- COMPANY INFO -->
    {{-- Always rendered: the parent company and FSSAI licence below are constant, even when
         the optional theme-settings fields (legal name, GSTIN, address) are blank. --}}
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
                        <div>
                            <p class="text-[11px] font-semibold text-neutral-500 uppercase mb-0.5">Parent Company</p>
                            <p class="text-neutral-900 text-[13px]">Suyash Enterprise</p>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-neutral-500 uppercase mb-0.5">FSSAI License</p>
                            <p class="text-neutral-900 text-[13px] font-mono">10425999000390</p>
                            <a href="{{ route('fssai-license') }}" class="text-[12px] text-primary-600 font-semibold hover:underline">View licence details</a>
                        </div>
                    </div>
                    <p class="text-[13px] text-neutral-600 leading-relaxed mt-5 pt-5 border-t border-neutral-100">
                        Suyash Enterprise is the parent company of GRYT Health Labs. Our products are manufactured and sold
                        under FSSAI licence number <span class="font-semibold text-neutral-900">10425999000390</span>, issued under the
                        Food Safety and Standards Act, 2006.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="py-14 sm:py-18 bg-white">
        <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto">
                <div class="gryt-hero rounded-3xl p-10 sm:p-14 text-center">
                    <div class="relative z-10">
                        <span class="block w-12 h-1 rounded-full mx-auto mb-5" style="background: var(--gryt-lime);"></span>
                        <h2 class="text-2xl sm:text-3xl font-extrabold text-white mb-3">{{ $ctaHeading }}</h2>
                        <p class="text-sm text-neutral-300 mb-8 max-w-md mx-auto">{{ $ctaText }}</p>
                        <div class="flex flex-wrap items-center justify-center gap-3">
                            <a href="{{ route('products.index') }}" class="gryt-btn-lime inline-flex items-center gap-2 px-7 py-3 text-sm font-bold rounded-full transition-colors">
                                Shop Now
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </a>
                            @if($instagram)
                            <a href="{{ $instagram }}" target="_blank" rel="noopener" class="gryt-btn-ghost inline-flex items-center gap-2 px-7 py-3 text-sm font-semibold rounded-full transition-colors">
                                Follow Us
                            </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

</x-layouts.app>
