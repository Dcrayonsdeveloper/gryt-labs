<x-layouts.app>
    @php
        $storeName = $theme->get('store_name', config('app.name'));
        $offersBanner    = $theme->get('offers_banner_image', '');
        $offersHtml      = $theme->get('offers_html_content', '');
        $offersCollageRaw = $theme->get('offers_collage_json', '');
        $offersCollage   = $offersCollageRaw ? json_decode($offersCollageRaw, true) : null;
        $freeShipThreshold = $theme->get('free_shipping_threshold', 499);
        $returnDays = $theme->get('return_policy_days', 7);
        $refundDays = $theme->get('refund_processing_days', '3-5');
        $reviewCashback = $theme->get('review_cashback_amount', 100);
        $reviewVideoDuration = $theme->get('review_video_duration', '30-60');
        $reviewCashbackHours = $theme->get('review_cashback_hours', 48);
        $codAdvance = $theme->get('cod_advance_amount', 100);
        $whatsappNumber = $theme->get('whatsapp_number', $theme->get('contact_phone', ''));
        $instagramUrl = $theme->get('social_instagram', '#');
        $navratriPct = $theme->get('navratri_discount_pct', 5);
    @endphp

    <x-slot name="title">Offers & Deals - {{ $storeName }}</x-slot>

    @push('meta')
        <meta name="description" content="Exclusive offers and deals at {{ $storeName }}. Special discounts, video review cashback, and more!">
        <link rel="canonical" href="{{ url('/pages/offers') }}">
    @endpush

    <div class="bg-white min-h-screen">

        {{-- Banner: image if set, else gradient --}}
        @if($offersBanner)
        <section class="relative overflow-hidden" style="min-height:220px;background:url('{{ $offersBanner }}') center/cover no-repeat;">
            <div class="absolute inset-0 bg-black/30"></div>
            <div class="relative container mx-auto px-4 py-16 text-center">
                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-white">Offers</h1>
            </div>
        </section>
        @else
        <div class="relative overflow-hidden" style="background: linear-gradient(135deg, var(--color-primary-600) 0%, var(--color-accent-500) 100%);">
            <div class="absolute inset-0 opacity-10">
                <svg width="100%" height="100%" viewBox="0 0 100 100" preserveAspectRatio="none"><circle cx="20" cy="30" r="15" fill="white"/><circle cx="80" cy="70" r="20" fill="white"/><circle cx="50" cy="10" r="10" fill="white"/></svg>
            </div>
            <div class="container mx-auto px-4 py-12 lg:py-16 text-center relative z-10">
                <h1 class="text-3xl lg:text-5xl font-extrabold text-white mb-3">Offers & Deals</h1>
                <p class="text-white/90 text-sm lg:text-base max-w-lg mx-auto">Grab the best deals on {{ $storeName }} products. Limited time offers you don't want to miss!</p>
            </div>
        </div>
        @endif

        {{-- Custom HTML content --}}
        @if($offersHtml)
        <section class="py-12 sm:py-16 bg-white">
            <div class="container mx-auto px-4">
                <div class="max-w-4xl mx-auto">
                    <div class="prose prose-neutral max-w-none text-neutral-800 leading-relaxed
                                [&_p]:mb-4 [&_ul]:my-4 [&_ul]:pl-5 [&_li]:mb-1 [&_li]:list-disc">
                        {!! $offersHtml !!}
                    </div>
                </div>
            </div>
        </section>

        {{-- Product image collage --}}
        @elseif($offersCollage && count($offersCollage))
        <section class="py-10 sm:py-14 bg-white">
            <div class="container mx-auto px-4">
                <div class="max-w-5xl mx-auto">
                    <style>
                        .offers-collage { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
                        @media (min-width: 640px) { .offers-collage { grid-template-columns: repeat(4, 1fr); gap: 24px; } }
                        .offers-collage-item { display: block; overflow: hidden; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,.1); transition: box-shadow .2s; }
                        .offers-collage-item:hover { box-shadow: 0 4px 12px rgba(0,0,0,.15); }
                        .offers-collage-item img { width: 100%; height: 100%; object-fit: cover; display: block; aspect-ratio: 3/4; }
                    </style>
                    <div class="offers-collage">
                        @foreach($offersCollage as $item)
                            @if(!empty($item['url']))
                            <a href="{{ $item['url'] }}" class="offers-collage-item">
                                <img src="{{ $item['image'] }}" alt="{{ $item['alt'] ?? '' }}" loading="lazy">
                            </a>
                            @else
                            <div class="offers-collage-item">
                                <img src="{{ $item['image'] }}" alt="{{ $item['alt'] ?? '' }}" loading="lazy">
                            </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        {{-- Generic offer cards fallback --}}
        @else
        <div class="container mx-auto px-4 py-10 lg:py-14">
            <div class="grid gap-8 lg:gap-10 max-w-4xl mx-auto">

                <!-- OFFER 1: Navratri 5% Extra Off -->
                <div class="rounded-2xl overflow-hidden shadow-lg border border-orange-100">
                    <div class="p-1" style="background: linear-gradient(135deg, var(--color-primary-600), var(--color-accent-500));">
                        <div class="bg-white rounded-xl p-6 lg:p-8">
                            <div class="flex flex-col lg:flex-row lg:items-center gap-6">
                                <div class="flex-shrink-0 w-20 h-20 lg:w-24 lg:h-24 rounded-2xl flex items-center justify-center text-4xl lg:text-5xl bg-primary-50">
                                    🎉
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="px-3 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider text-white bg-primary-600">Live Now</span>
                                        <span class="px-3 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-green-100 text-green-700">Auto Applied</span>
                                    </div>
                                    <h2 class="text-xl lg:text-2xl font-bold text-[#0F1111] mb-2">Navratri Special - Extra {{ $navratriPct }}% Off</h2>
                                    <p class="text-sm text-[#3a3a3a] mb-3">Celebrate Navratri with {{ $storeName }}! Get <strong>flat {{ $navratriPct }}% extra discount</strong> on your entire order, applied automatically at checkout after all other discounts. No coupon needed!</p>
                                    <ul class="text-xs text-[#3a3a3a] space-y-1 mb-4">
                                        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> Applied on top of coupon discounts</li>
                                        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> Valid on all products, all payment methods</li>
                                        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> Limited period offer</li>
                                    </ul>
                                    <a href="{{ route('products.index') }}" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-full text-sm font-bold text-white bg-primary-600 hover:bg-primary-700 transition-all hover:scale-105">
                                        Shop Now
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- OFFER 2: Video Review ₹100 Cashback -->
                <div class="rounded-2xl overflow-hidden shadow-lg border border-blue-100">
                    <div class="p-1" style="background: linear-gradient(135deg, var(--color-primary-600), #2d7a85);">
                        <div class="bg-white rounded-xl p-6 lg:p-8">
                            <div class="flex flex-col lg:flex-row lg:items-center gap-6">
                                <div class="flex-shrink-0 w-20 h-20 lg:w-24 lg:h-24 rounded-2xl flex items-center justify-center text-4xl lg:text-5xl" style="background: linear-gradient(135deg, #E0F2F1, #B2DFDB);">
                                    🎥
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="px-3 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider text-white bg-primary-600">Cashback</span>
                                        <span class="px-3 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-yellow-100 text-yellow-700">₹{{ $reviewCashback }} Reward</span>
                                    </div>
                                    <h2 class="text-xl lg:text-2xl font-bold text-[#0F1111] mb-2">Share a Video, Get ₹{{ $reviewCashback }} Cashback!</h2>
                                    <p class="text-sm text-[#3a3a3a] mb-3">Already have a {{ $storeName }} product? Make a short video review showing how you use it and send it to us. We'll give you <strong>₹{{ $reviewCashback }} cashback</strong> directly to your account!</p>

                                    <div class="bg-[#f8f6f3] rounded-xl p-4 mb-4">
                                        <h3 class="text-sm font-bold text-[#0F1111] mb-3">How it works:</h3>
                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                            <div class="text-center">
                                                <div class="w-10 h-10 rounded-full bg-primary-600 text-white flex items-center justify-center mx-auto mb-2 text-sm font-bold">1</div>
                                                <p class="text-xs text-[#3a3a3a]">Record a <strong>{{ $reviewVideoDuration }} sec video</strong> of your {{ $storeName }} product in use</p>
                                            </div>
                                            <div class="text-center">
                                                <div class="w-10 h-10 rounded-full bg-primary-600 text-white flex items-center justify-center mx-auto mb-2 text-sm font-bold">2</div>
                                                <p class="text-xs text-[#3a3a3a]">Send the video to us on <strong>WhatsApp</strong> or <strong>Instagram DM</strong></p>
                                            </div>
                                            <div class="text-center">
                                                <div class="w-10 h-10 rounded-full bg-primary-600 text-white flex items-center justify-center mx-auto mb-2 text-sm font-bold">3</div>
                                                <p class="text-xs text-[#3a3a3a]">Get <strong>₹{{ $reviewCashback }} cashback</strong> in your bank/UPI within {{ $reviewCashbackHours }} hours</p>
                                            </div>
                                        </div>
                                    </div>

                                    <ul class="text-xs text-[#3a3a3a] space-y-1 mb-4">
                                        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> One cashback per product per customer</li>
                                        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> Video must show the actual {{ $storeName }} product</li>
                                        <li class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> We may feature your video on our Instagram (with credit!)</li>
                                    </ul>

                                    <div class="flex flex-wrap gap-3">
                                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $whatsappNumber) }}?text=Hi%20{{ $storeName }}!%20I%20want%20to%20share%20a%20video%20review%20of%20my%20product%20for%20the%20%E2%82%B9{{ $reviewCashback }}%20cashback%20offer." target="_blank" rel="noopener"
                                           class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-bold text-white bg-[#25D366] hover:bg-[#20BD5A] transition-all hover:scale-105">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                            Send on WhatsApp
                                        </a>
                                        <a href="{{ $instagramUrl }}" target="_blank" rel="noopener"
                                           class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-bold text-white transition-all hover:scale-105" style="background: linear-gradient(135deg, #833ab4, #fd1d1d, #fcb045);">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913a6.6 6.6 0 001.384 2.126A6.6 6.6 0 004.14 23.37c.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558a6.6 6.6 0 002.126-1.384 6.6 6.6 0 001.384-2.126c.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913a6.6 6.6 0 00-1.384-2.126A6.6 6.6 0 0019.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm7.846-10.405a1.44 1.44 0 11-2.88 0 1.44 1.44 0 012.88 0z"/></svg>
                                            DM on Instagram
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- OFFER 3: Free Shipping -->
                <div class="rounded-2xl overflow-hidden shadow-lg border border-green-100">
                    <div class="p-1 bg-linear-to-r from-green-500 to-emerald-500">
                        <div class="bg-white rounded-xl p-6 lg:p-8">
                            <div class="flex flex-col lg:flex-row lg:items-center gap-6">
                                <div class="flex-shrink-0 w-20 h-20 lg:w-24 lg:h-24 rounded-2xl flex items-center justify-center text-4xl lg:text-5xl bg-green-50">
                                    🚚
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="px-3 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider text-white bg-green-600">Always On</span>
                                    </div>
                                    <h2 class="text-xl lg:text-2xl font-bold text-[#0F1111] mb-2">Free Shipping on Orders Above {{ currency_symbol() }}{{ $freeShipThreshold }}</h2>
                                    <p class="text-sm text-[#3a3a3a] mb-3">No hidden shipping charges! All orders above {{ currency_symbol() }}{{ $freeShipThreshold }} ship free. COD available with just {{ currency_symbol() }}{{ $codAdvance }} advance payment.</p>
                                    <a href="{{ route('products.index') }}" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-full text-sm font-bold text-white bg-green-600 hover:bg-green-700 transition-all hover:scale-105">
                                        Start Shopping
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- OFFER 4: 7-Day Easy Returns -->
                <div class="rounded-2xl overflow-hidden shadow-lg border border-purple-100">
                    <div class="p-1 bg-linear-to-r from-purple-500 to-indigo-500">
                        <div class="bg-white rounded-xl p-6 lg:p-8">
                            <div class="flex flex-col lg:flex-row lg:items-center gap-6">
                                <div class="flex-shrink-0 w-20 h-20 lg:w-24 lg:h-24 rounded-2xl flex items-center justify-center text-4xl lg:text-5xl bg-purple-50">
                                    🔄
                                </div>
                                <div class="flex-1">
                                    <h2 class="text-xl lg:text-2xl font-bold text-[#0F1111] mb-2">{{ $returnDays }}-Day Easy Returns</h2>
                                    <p class="text-sm text-[#3a3a3a] mb-3">Not satisfied? Return any product within {{ $returnDays }} days of delivery. No questions asked. We'll process your refund within {{ $refundDays }} business days.</p>
                                    <a href="{{ url('/pages/return-policy') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-purple-600 hover:text-purple-800 transition-colors">
                                        View Return Policy
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        @endif

    </div>
</x-layouts.app>
