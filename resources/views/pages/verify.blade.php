<x-layouts.app>
    @php
        // store_name is blank on prod, and a default only applies when the key is missing,
        // not when it is set to ''. Same fallback chain the offer popup uses.
        $storeName = $theme->get('site_name', '') ?: ($theme->get('store_name', '') ?: config('app.name'));
        $whatsappNumber = $theme->get('whatsapp_number', $theme->get('contact_phone', ''));
        $placeholder = asset('images/placeholder-product.svg');

        // Everything the drawer needs, so picking a product is instant (no extra request).
        $items = $products->map(fn ($p) => [
            'id'       => $p->id,
            'name'     => $p->name,
            'sku'      => $p->sku,
            'image'    => $p->primary_image_url ?: $placeholder,
            'price'    => (float) $p->price,
            'mrp'      => (float) $p->mrp,
            'desc'     => strip_tags($p->short_description ?? ''),
            'category' => $p->category->name ?? '',
            'brand'    => $p->brand->name ?? '',
            'url'      => url('/products/' . $p->slug),
        ])->values();
    @endphp

    <x-slot name="title">Verify your product - {{ $storeName }}</x-slot>

    @push('meta')
        <meta name="description" content="Scanned the QR on your {{ $storeName }} pack? Pick your product to see its details and how to use it.">
        <meta name="robots" content="noindex, follow">
    @endpush

    @push('styles')
    <style>
        .gryt-confetti {
            position: absolute; top: -12px; width: 8px; height: 14px; border-radius: 2px;
            opacity: 0; animation: gryt-fall 2.6s linear infinite;
        }
        @keyframes gryt-fall {
            0%   { transform: translateY(-12px) rotate(0deg);   opacity: 0; }
            12%  { opacity: 1; }
            100% { transform: translateY(420px) rotate(540deg); opacity: 0; }
        }
        .gryt-pop { animation: gryt-pop-in .45s cubic-bezier(.34,1.56,.64,1) both; }
        @keyframes gryt-pop-in {
            0%   { transform: scale(0);   opacity: 0; }
            100% { transform: scale(1);   opacity: 1; }
        }
        @media (prefers-reduced-motion: reduce) {
            .gryt-confetti, .gryt-pop { animation: none; }
            .gryt-confetti { display: none; }
        }
    </style>
    @endpush

    <div class="bg-neutral-50 min-h-screen">

        {{-- Hero — first thing seen after a scan, so keep it short and reassuring.
             Want stronger wording? Change the <h1> text below. --}}
        <section class="relative overflow-hidden" style="background: linear-gradient(135deg, var(--color-primary-600) 0%, var(--color-primary-700) 100%);">
            <div class="container mx-auto px-4 py-10 sm:py-14 text-center relative z-10">
                <div class="inline-flex items-center justify-center w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-white/15 backdrop-blur mb-4">
                    <svg class="w-9 h-9 sm:w-11 sm:h-11 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    </svg>
                </div>
                <h1 class="text-2xl sm:text-4xl font-extrabold text-white mb-2">Thanks for buying {{ $storeName }}</h1>
                <p class="text-white/85 text-sm sm:text-base max-w-md mx-auto">
                    You scanned the code on your pack. Pick the product you bought to see its details and how to use it.
                </p>
            </div>
            <div class="absolute inset-0 opacity-10 pointer-events-none">
                <svg width="100%" height="100%" viewBox="0 0 100 100" preserveAspectRatio="none"><circle cx="15" cy="25" r="18" fill="white"/><circle cx="85" cy="75" r="22" fill="white"/></svg>
            </div>
        </section>

        <div class="container mx-auto px-4 py-8 sm:py-10"
             x-data="{
                items: @js($items),
                q: '',
                selected: null,
                welcome: true,
                get filtered() {
                    const q = this.q.trim().toLowerCase();
                    if (!q) return this.items;
                    return this.items.filter(i => i.name.toLowerCase().includes(q)
                        || (i.category || '').toLowerCase().includes(q)
                        || (i.sku || '').toLowerCase().includes(q));
                },
                money(n) { return '₹' + Number(n || 0).toLocaleString('en-IN'); },
                open(item) { this.selected = item; document.body.style.overflow = 'hidden'; },
                close() { this.selected = null; document.body.style.overflow = ''; },
                startPicking() { this.welcome = false; document.body.style.overflow = ''; },
             }"
             x-init="document.body.style.overflow = 'hidden'">
            <div class="max-w-5xl mx-auto">

                <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-5">
                    <div>
                        <h2 class="text-lg sm:text-xl font-bold text-neutral-900">Which product did you buy?</h2>
                        <p class="text-sm text-neutral-500 mt-0.5">Tap it to see the details.</p>
                    </div>
                    @if($items->count() > 8)
                        <div class="relative sm:w-64">
                            <input type="search" x-model="q" placeholder="Search products..."
                                   class="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-neutral-300 focus:border-link focus:ring-1 focus:ring-link outline-none">
                            <svg class="w-4 h-4 text-neutral-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M19 11a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"/>
                            </svg>
                        </div>
                    @endif
                </div>

                @if($items->isEmpty())
                    <div class="bg-white rounded-xl border border-neutral-200 p-8 text-center">
                        <p class="text-neutral-600 text-sm">Our catalogue is being updated. Please check back shortly.</p>
                        <a href="{{ url('/products') }}" class="inline-block mt-4 px-5 py-2.5 rounded-lg bg-primary-600 text-white text-sm font-semibold hover:bg-primary-700">Browse all products</a>
                    </div>
                @else
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">
                        <template x-for="item in filtered" :key="item.id">
                            <button type="button" @click="open(item)"
                                    class="group bg-white rounded-xl border border-neutral-200 p-3 text-left hover:border-primary-600 hover:shadow-md transition-all focus:outline-none focus:ring-2 focus:ring-primary-600">
                                <div class="aspect-square rounded-lg bg-neutral-50 overflow-hidden mb-2.5">
                                    <img :src="item.image" :alt="item.name" loading="lazy"
                                         class="w-full h-full object-contain group-hover:scale-105 transition-transform duration-200">
                                </div>
                                <p class="text-xs sm:text-sm font-semibold text-neutral-900 leading-snug line-clamp-2" x-text="item.name"></p>
                                <p x-show="item.category" class="text-[11px] text-neutral-500 mt-1" x-text="item.category"></p>
                            </button>
                        </template>
                    </div>

                    <p x-show="filtered.length === 0" x-cloak class="text-center text-sm text-neutral-500 py-10">
                        No product matches that search.
                    </p>
                @endif

                {{-- Reassurance strip — what a customer can actually check themselves --}}
                <div class="mt-10 grid sm:grid-cols-3 gap-3">
                    <div class="bg-white rounded-xl border border-neutral-200 p-4">
                        <p class="text-sm font-semibold text-neutral-900">Buy from official stores</p>
                        <p class="text-xs text-neutral-500 mt-1">Order from {{ $storeName }} or our authorised sellers only.</p>
                    </div>
                    <div class="bg-white rounded-xl border border-neutral-200 p-4">
                        <p class="text-sm font-semibold text-neutral-900">Check the seal</p>
                        <p class="text-xs text-neutral-500 mt-1">The pack should be sealed, with batch and expiry printed clearly.</p>
                    </div>
                    <div class="bg-white rounded-xl border border-neutral-200 p-4">
                        <p class="text-sm font-semibold text-neutral-900">Something looks off?</p>
                        @if($whatsappNumber)
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $whatsappNumber) }}?text={{ urlencode('Hi, I scanned the QR on my pack and want to check my product.') }}"
                               target="_blank" rel="noopener"
                               class="text-xs text-link hover:text-link-hover font-semibold mt-1 inline-block">Message us on WhatsApp &rarr;</a>
                        @else
                            <a href="{{ url('/pages/contact') }}" class="text-xs text-link hover:text-link-hover font-semibold mt-1 inline-block">Contact us &rarr;</a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Congrats popup — the first beat after a scan. Teleported for the same
                 `contain: layout style` reason as the drawer below. --}}
            <template x-teleport="body">
                <div x-show="welcome" x-cloak
                     class="fixed inset-0 z-[110] flex items-center justify-center p-4"
                     style="display:none;background:rgba(0,0,0,0.6);backdrop-filter:blur(4px);"
                     x-transition.opacity.duration.300ms
                     role="dialog" aria-modal="true" aria-labelledby="gryt-congrats-title">

                    <div class="relative bg-white w-full max-w-sm rounded-2xl shadow-2xl overflow-hidden text-center"
                         x-show="welcome"
                         x-transition:enter="transition ease-out duration-400"
                         x-transition:enter-start="opacity-0 scale-90"
                         x-transition:enter-end="opacity-100 scale-100">

                        {{-- Confetti, CSS only — no library, no layout shift --}}
                        <div class="absolute inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
                            @foreach([['8%','0s','#4D7C0F'],['20%','.35s','#B8DC24'],['33%','.1s','#F8931D'],['46%','.5s','#4D7C0F'],['59%','.2s','#B8DC24'],['72%','.45s','#F8931D'],['85%','.05s','#4D7C0F'],['94%','.3s','#B8DC24']] as [$left, $delay, $color])
                                <span class="gryt-confetti" style="left:{{ $left }};animation-delay:{{ $delay }};background:{{ $color }}"></span>
                            @endforeach
                        </div>

                        <div class="relative px-6 pt-8 pb-6">
                            <div class="gryt-pop mx-auto w-20 h-20 rounded-full flex items-center justify-center mb-5"
                                 style="background:rgba(6,125,98,0.1)">
                                <svg class="w-11 h-11" style="color:#067D62" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                                </svg>
                            </div>

                            <h2 id="gryt-congrats-title" class="text-2xl font-extrabold text-neutral-900 mb-2">Congratulations!</h2>
                            <p class="text-sm text-neutral-600 leading-relaxed mb-1">
                                Thanks for choosing <span class="font-semibold text-neutral-900">{{ $storeName }}</span>.
                            </p>
                            <p class="text-sm text-neutral-500 leading-relaxed">
                                You have got the real thing, straight from us. Tell us which one you bought and we will show you how to get the most out of it.
                            </p>

                            <button type="button" @click="startPicking()"
                                    class="mt-6 w-full px-4 py-3 rounded-lg bg-primary-600 text-white text-sm font-bold hover:bg-primary-700 transition-colors">
                                Show me my product
                            </button>
                        </div>
                    </div>
                </div>
            </template>

            {{-- Detail drawer — bottom sheet on mobile, centred card on desktop.
                 Teleported to <body> because app.css applies `contain: layout style` to
                 main/section/article, which makes <main> the containing block for fixed
                 children — without this the panel lands below the fold instead of over it. --}}
            <template x-teleport="body">
                <div x-show="selected" x-cloak class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center" style="display:none">
                    <div class="absolute inset-0 bg-black/50" @click="close()" x-show="selected" x-transition.opacity></div>

                    <div class="relative bg-white w-full sm:max-w-md rounded-t-2xl sm:rounded-2xl shadow-xl max-h-[85vh] overflow-y-auto"
                         @click.stop
                         @keydown.escape.window="close()"
                         x-show="selected"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="translate-y-full sm:translate-y-4 opacity-0"
                         x-transition:enter-end="translate-y-0 opacity-100">

                        <template x-if="selected">
                            <div>
                                <div class="flex items-start justify-between p-4 pb-0">
                                    <div class="inline-flex items-center gap-1.5 text-[11px] font-bold text-[#067D62] bg-[#067D62]/10 px-2 py-1 rounded">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-8 8a1 1 0 0 1-1.4 0l-4-4a1 1 0 1 1 1.4-1.4L8 12.6l7.3-7.3a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>
                                        <span>Official {{ $storeName }} product</span>
                                    </div>
                                    <button type="button" @click="close()" aria-label="Close"
                                            class="text-neutral-400 hover:text-neutral-700 text-2xl leading-none px-1 -mt-1">&times;</button>
                                </div>

                                <div class="p-4 pt-3">
                                    <div class="aspect-square w-48 mx-auto rounded-xl bg-neutral-50 overflow-hidden mb-4">
                                        <img :src="selected.image" :alt="selected.name" class="w-full h-full object-contain">
                                    </div>

                                    <h3 class="text-base font-bold text-neutral-900 leading-snug" x-text="selected.name"></h3>
                                    <p x-show="selected.brand || selected.category" class="text-xs text-neutral-500 mt-1"
                                       x-text="[selected.brand, selected.category].filter(Boolean).join(' / ')"></p>

                                    <p x-show="selected.desc" class="text-sm text-neutral-600 mt-3 leading-relaxed" x-text="selected.desc"></p>

                                    <dl class="mt-4 border-t border-neutral-100 pt-3 space-y-2 text-sm">
                                        {{-- Product code is hidden until the catalogue SKUs are cleaned up: several are
                                             placeholders (AGSDKJHA-ASDAS-SADS) or belong to a different product, and a
                                             wrong code on this page undoes the trust it exists to build. Re-enable once
                                             every SKU matches its product.
                                        <div x-show="selected.sku" class="flex justify-between gap-4">
                                            <dt class="text-neutral-500">Product code</dt>
                                            <dd class="font-medium text-neutral-900 text-right" x-text="selected.sku"></dd>
                                        </div>
                                        --}}
                                        <div class="flex justify-between gap-4">
                                            <dt class="text-neutral-500">Price</dt>
                                            <dd class="font-medium text-neutral-900 text-right">
                                                <span x-text="money(selected.price)"></span>
                                                <span x-show="selected.mrp > selected.price"
                                                      class="text-neutral-400 line-through text-xs ml-1" x-text="money(selected.mrp)"></span>
                                            </dd>
                                        </div>
                                    </dl>

                                    <div class="mt-5 space-y-2">
                                        <a :href="selected.url"
                                           class="block w-full text-center px-4 py-3 rounded-lg bg-primary-600 text-white text-sm font-bold hover:bg-primary-700">
                                            View full details &amp; how to use
                                        </a>
                                        <a :href="selected.url"
                                           class="block w-full text-center px-4 py-2.5 rounded-lg border border-neutral-300 text-neutral-800 text-sm font-semibold hover:bg-neutral-50">
                                            Buy again
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>

</x-layouts.app>
