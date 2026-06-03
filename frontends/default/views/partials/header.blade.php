@php $announcement = $theme->get('announcement_text', ''); @endphp

<header id="main-header" x-data="{ visible: true, lastScroll: 0 }"
       x-on:scroll.window="
           let y = window.scrollY;
           if (y < 60) { visible = true }
           else if (y < lastScroll) { visible = true }
           else if (y > lastScroll + 5) { visible = false }
           lastScroll = y;
       "
       class="bg-white border-b border-neutral-100 fixed left-0 right-0 z-40"
       :style="'top:0; transition: transform 0.3s ease; transform: translateY(' + (visible ? '0' : '-100%') + ')'">
    <!-- Top Bar: Announcement + Contact/About links -->
    @if($announcement)
    <div class="text-neutral-900 py-1.5 text-[11px] sm:text-xs font-medium tracking-wide" style="background-color:#B8DC24;">
        <div class="container mx-auto px-4 flex items-center justify-between">
            <div class="hidden sm:flex items-center gap-3">
                <span>{{ $announcement }}</span>
            </div>
            <span class="sm:hidden flex-1 text-center">{{ $announcement }}</span>
            <div class="hidden sm:flex items-center gap-3">
                @php
                    $headerPhone = $theme->get('contact_phone', '');
                    $headerEmail = $theme->get('contact_email', '');
                @endphp
                @if($headerPhone)
                <a href="tel:{{ $headerPhone }}" class="inline-flex items-center gap-1 text-neutral-800 hover:text-black transition">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    {{ $headerPhone }}
                </a>
                @endif
                @if($headerEmail)
                <a href="mailto:{{ $headerEmail }}" class="inline-flex items-center gap-1 text-neutral-800 hover:text-black transition">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    {{ $headerEmail }}
                </a>
                @endif
                <span class="text-neutral-900/30">|</span>
                <a href="{{ route('about') }}" class="text-neutral-800 hover:text-black transition">About Us</a>
                <a href="{{ route('contact') }}" class="text-neutral-800 hover:text-black transition">Contact</a>
            </div>
        </div>
    </div>
    @endif
    <div class="container mx-auto px-4">
        <div class="flex items-center justify-between h-14 lg:h-16">

            @php
                $headerNavItems = $theme->navigation('header');
                $hasCustomNav = $headerNavItems->isNotEmpty();
            @endphp

            <!-- Left: Hamburger + Logo (custom nav) OR Hamburger + Default Nav -->
            <div class="flex items-center gap-3 lg:gap-0 flex-1">
                <button @click="$dispatch('toggle-mobile-nav')" class="lg:hidden p-1.5 -ml-1.5 text-neutral-700 hover:text-primary-600" aria-label="Open menu">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>

                @if($hasCustomNav)
                    {{-- Logo on left for tenants with custom nav --}}
                    <a href="{{ url('/') }}" class="flex items-center shrink-0">
                        <img src="{{ asset($theme->get('store_logo', 'images/logo.png')) }}" alt="{{ $theme->get('store_name', config('app.name')) }}" style="height: {{ $theme->get('logo_height', '48') }}px; width: auto; max-width: 180px;">
                    </a>
                @else
                    <nav class="hidden lg:flex items-center gap-1">
                        <a href="{{ route('new-arrivals') }}" class="px-3 py-2 text-[13px] text-[#222] hover:text-primary-600 font-medium transition-colors tracking-wide uppercase">New Arrival</a>
                        <a href="{{ route('categories.index') }}" class="px-3 py-2 text-[13px] text-[#222] hover:text-primary-600 font-medium transition-colors tracking-wide uppercase">Collections</a>
                        <a href="{{ route('bestsellers') }}" class="px-3 py-2 text-[13px] text-[#222] hover:text-primary-600 font-medium transition-colors tracking-wide uppercase">Bestsellers</a>
                        <a href="{{ route('offers') }}" class="px-3 py-2 text-[13px] text-[#4D7C0F] hover:text-[#3F6212] font-semibold transition-colors tracking-wide uppercase">Sale</a>
                    </nav>
                @endif
            </div>

            <!-- Center: Nav menus (custom nav) OR Logo (default) -->
            @if($hasCustomNav)
                <nav class="hidden lg:flex items-center gap-1 shrink-0">
                    @foreach($headerNavItems as $navItem)
                        @if($navItem->children->isNotEmpty())
                            <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                                <button @click="open = !open" class="px-3 py-2 text-[13px] text-[#222] hover:text-primary-600 font-medium transition-colors tracking-wide uppercase inline-flex items-center gap-1">
                                    {{ $navItem->label }}
                                    <svg class="w-3 h-3 opacity-60 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                                     class="absolute left-0 top-full pt-1 z-50">
                                    <div class="bg-white border border-neutral-200 rounded-lg shadow-lg py-1 min-w-[200px]">
                                        @foreach($navItem->children as $child)
                                            <a href="{{ $child->url }}" class="block px-4 py-2.5 text-sm text-neutral-700 hover:bg-primary-50 hover:text-primary-700 transition-colors">{{ $child->label }}</a>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @else
                            <a href="{{ $navItem->url }}" class="px-3 py-2 text-[13px] text-[#222] hover:text-primary-600 font-medium transition-colors tracking-wide uppercase">{{ $navItem->label }}</a>
                        @endif
                    @endforeach
                </nav>
            @else
                <a href="{{ url('/') }}" class="flex items-center shrink-0 mx-4">
                    <img src="{{ asset($theme->get('store_logo', 'images/logo.png')) }}" alt="{{ $theme->get('store_name', config('app.name')) }}" style="height: {{ $theme->get('logo_height', '48') }}px; width: auto; max-width: 180px;">
                </a>
            @endif

            <!-- Right: Nav links + Icons -->
            <div class="flex items-center gap-1 lg:gap-0 flex-1 justify-end">

                <!-- Desktop Navigation (Right side) -->
                <nav class="hidden lg:flex items-center gap-1 mr-2">
                    @if(config('app.wholesale_enabled'))
                        <a href="{{ route('wholesale') }}" class="px-3 py-2 text-[13px] text-[#222] hover:text-primary-600 font-medium transition-colors tracking-wide uppercase">Wholesale</a>
                    @endif
                </nav>

                <!-- Mobile search icon (shown below sm, links to search page) -->
                <a href="{{ route('search') }}" class="sm:hidden p-2 text-neutral-600 hover:text-primary-600 transition-colors" aria-label="Search">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </a>

                <!-- Inline Search Bar with Typewriter + Mic (hidden on mobile) -->
                <div class="relative hidden sm:block flex-1 max-w-xs lg:max-w-sm mx-1 lg:mx-3"
                     style="z-index: 60;"
                     x-data="searchBar()"
                     @click.outside="showResults = false">
                    <form action="{{ route('search') }}" method="GET" class="relative flex items-center">
                        <!-- Search icon -->
                        <svg class="absolute left-2.5 w-4 h-4 text-neutral-600 pointer-events-none" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>

                        <!-- Input with typewriter placeholder -->
                        <input type="text"
                               name="q"
                               x-ref="searchInput"
                               x-model="query"
                               @input.debounce.300ms="fetchSuggestions()"
                               @focus="showResults = true; stopTypewriter()"
                               @blur="if(!query) startTypewriter()"
                               @keydown.escape="showResults = false; $refs.searchInput.blur()"
                               :placeholder="currentPlaceholder"
                               class="w-full pl-8 pr-16 py-2 text-base bg-neutral-50 border border-neutral-200 rounded-full placeholder-neutral-400 focus:bg-white focus:border-primary-600 transition-all"
                               style="font-size:16px;"
                               autocomplete="off">

                        <!-- Mic button (only shown when browser supports Speech Recognition) -->
                        <button x-show="recognition" x-cloak
                                type="button"
                                @click.prevent="toggleMic()"
                                class="absolute right-8 p-1 transition-colors z-10"
                                :class="listening ? 'text-red-500 animate-pulse' : 'text-neutral-600 hover:text-primary-600'"
                                :title="listening ? 'Stop listening' : 'Voice search'"
                                aria-label="Voice search">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3z"/>
                                <path d="M17 11c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/>
                            </svg>
                        </button>

                        <!-- Submit button -->
                        <button type="submit" class="absolute right-2 p-1 text-neutral-600 hover:text-primary-600 transition-colors" aria-label="Search">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                            </svg>
                        </button>
                    </form>

                    <!-- Search results dropdown -->
                    <div x-show="showResults && (results.length > 0 || (query.length >= 2 && !loading))" x-cloak
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         class="absolute left-0 right-0 top-full mt-2 bg-white border border-neutral-200 rounded-lg shadow-2xl overflow-hidden"
                         style="z-index: 9999;">
                        <div x-show="results.length > 0" class="max-h-60 overflow-y-auto">
                            <ul class="py-1">
                                <template x-for="(result, idx) in results" :key="result.type + '-' + result.id + '-' + idx">
                                    <li>
                                        <a :href="result.url" class="flex items-center gap-2.5 px-3 py-2 hover:bg-neutral-50 transition-colors">
                                            <img :src="result.image || '/images/placeholder.png'" :alt="result.name" class="w-8 h-8 object-cover rounded bg-neutral-100">
                                            <div class="min-w-0 flex-1">
                                                <div class="text-sm text-neutral-900 truncate" x-text="result.name"></div>
                                                <div class="text-xs text-neutral-500 truncate" x-text="result.category || result.type"></div>
                                            </div>
                                        </a>
                                    </li>
                                </template>
                            </ul>
                        </div>
                        <div x-show="query.length >= 2 && results.length === 0 && !loading" class="px-4 py-4 text-center">
                            <p class="text-sm text-neutral-600">No results found</p>
                        </div>
                    </div>
                </div>

                <!-- Wishlist -->
                <a href="{{ route('wishlist') }}" class="relative p-2 text-neutral-600 hover:text-primary-600 transition-colors hidden sm:flex" aria-label="Wishlist">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                    <span x-cloak
                          x-show="$store.wishlist.count > 0"
                          x-text="$store.wishlist.count"
                          class="absolute -top-0.5 -right-0.5 w-4 h-4 bg-accent-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center">
                    </span>
                </a>

                <!-- User account - desktop -->
                <div class="relative hidden lg:block" x-data="dropdown()">
                    <button @click="toggle()" class="p-2 text-neutral-600 hover:text-primary-600 transition-colors" aria-label="Account">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </button>

                    <div x-cloak x-show="open" x-transition @click.outside="close()" class="absolute right-0 mt-1 w-48 bg-white border border-neutral-200 rounded-lg shadow-dropdown z-50 overflow-hidden">
                        @guest
                            <a href="{{ route('login') }}" class="block px-4 py-2 text-sm text-neutral-700 hover:text-primary-600">Login</a>
                            <a href="{{ route('register') }}" class="block px-4 py-2 text-sm text-neutral-700 hover:text-primary-600">Register</a>
                        @else
                            <div class="px-4 py-2 border-b border-neutral-100">
                                <div class="text-sm font-medium text-neutral-900">{{ auth()->user()->full_name }}</div>
                                <div class="text-xs text-neutral-600">{{ auth()->user()->email }}</div>
                            </div>
                            <a href="{{ route('account.dashboard') }}" class="block px-4 py-2 text-sm text-neutral-700 hover:text-primary-600">Dashboard</a>
                            <a href="{{ route('account.orders.index') }}" class="block px-4 py-2 text-sm text-neutral-700 hover:text-primary-600">My Orders</a>
                            <a href="{{ route('account.profile') }}" class="block px-4 py-2 text-sm text-neutral-700 hover:text-primary-600">Profile Settings</a>
                            @if(auth()->user()->deliveryPartner)
                                <a href="{{ route('delivery.login') }}" class="block px-4 py-2 text-sm text-primary-600 hover:text-primary-700 font-medium">Delivery Panel</a>
                            @else
                                <a href="{{ route('account.become-delivery-partner') }}" class="block px-4 py-2 text-sm text-primary-600 hover:text-primary-700 font-medium">Become a Delivery Partner</a>
                            @endif
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2 text-sm text-neutral-700 hover:text-primary-600">Logout</button>
                            </form>
                        @endguest
                    </div>
                </div>

                <!-- Cart -->
                <a href="{{ route('cart.index') }}" class="relative p-2 text-neutral-700 hover:text-primary-600 transition-colors" aria-label="Cart">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                    <span x-cloak
                          x-show="$store.cart.itemCount > 0"
                          x-text="$store.cart.itemCount"
                          class="absolute -top-0.5 -right-0.5 w-4 h-4 bg-accent-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center">
                    </span>
                </a>
            </div>
        </div>
    </div>
    {{-- Marquee ticker — only for tenants with marquee_text set --}}
    @php
        $marqueeText = tenant()?->id === 'ayurvexa' ? $theme->get('marquee_text', '') : '';
        $marqueeItems = $marqueeText ? array_filter(array_map('trim', explode('|', $marqueeText))) : [];
    @endphp
    @if(count($marqueeItems))
    <div class="bg-neutral-900 text-white overflow-hidden" style="height:36px;">
        <div class="marquee-track flex items-center whitespace-nowrap" style="height:36px;">
            @for($i = 0; $i < 3; $i++)
            <div class="marquee-content flex items-center shrink-0">
                @foreach($marqueeItems as $item)
                <span class="inline-flex items-center gap-2.5 px-8 sm:px-12 text-[11px] sm:text-xs font-semibold uppercase tracking-[0.15em]">
                    <svg class="w-3.5 h-3.5 text-white/50 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                    {{ $item }}
                </span>
                @endforeach
            </div>
            @endfor
        </div>
        <style>
            .marquee-track { animation: marquee-scroll 25s linear infinite; }
            .marquee-track:hover { animation-play-state: paused; }
            @keyframes marquee-scroll { 0% { transform: translateX(0); } 100% { transform: translateX(calc(-100% / 3)); } }
        </style>
    </div>
    @endif
</header>
<!-- Spacer for fixed header + announcement bar -->
<div id="header-spacer"
     class="{{ $announcement ? 'h-[86px] lg:h-[94px]' : 'h-14 lg:h-16' }}"
     aria-hidden="true"></div>
<script>
    (function () {
        function syncSpacer() {
            var hdr = document.getElementById('main-header');
            var spc = document.getElementById('header-spacer');
            if (hdr && spc) spc.style.height = hdr.offsetHeight + 'px';
        }
        syncSpacer();
        window.addEventListener('resize', syncSpacer);
    })();
</script>
