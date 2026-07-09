<x-layouts.app>
    <x-slot name="title">Contact Us - {{ config('app.name') }}</x-slot>

    @push('meta')
        <meta name="description" content="Get in touch with {{ config('app.name') }}. We're here to help with orders, returns, and any questions about your orders.">
        <link rel="canonical" href="{{ url('/pages/contact') }}">
        <meta property="og:title" content="Contact Us - {{ config('app.name') }}">
        <meta property="og:description" content="Get in touch with {{ config('app.name') }}. We're here to help with orders, returns, and any questions about your orders.">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url('/pages/contact') }}">
        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="Contact Us - {{ config('app.name') }}">
        <meta name="twitter:description" content="Get in touch with {{ config('app.name') }}. We're here to help with orders, returns, and any questions.">

        {{-- ContactPage JSON-LD --}}
        @php
            $contactSchema = [
                '@context' => 'https://schema.org',
                '@type' => 'ContactPage',
                'name' => 'Contact Us - ' . $theme->get('store_name', config('app.name')),
                'url' => url('/contact'),
                'mainEntity' => [
                    '@type' => 'Organization',
                    'name' => $theme->get('store_name', config('app.name')),
                    'email' => $theme->get('contact_email', ''),
                    'telephone' => $theme->get('contact_phone', ''),
                    'contactPoint' => [
                        '@type' => 'ContactPoint',
                        'contactType' => 'customer service',
                        'telephone' => $theme->get('contact_phone', ''),
                        'email' => $theme->get('contact_email', ''),
                        'availableLanguage' => json_decode($theme->get('support_languages', '["English", "Hindi"]'), true),
                    ],
                ],
            ];
        @endphp
        <script type="application/ld+json">{!! json_encode($contactSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    @endpush

    <!-- Hero Header -->
    <div class="bg-linear-to-br from-primary-700 via-primary-600 to-primary-700 text-white">
        <div class="container mx-auto px-4 py-12 sm:py-16 text-center">
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-white/10 backdrop-blur rounded-full text-[11px] font-semibold uppercase tracking-wider mb-3">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                We're Here to Help
            </div>
            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold mb-3 tracking-tight">Get in Touch</h1>
            <p class="text-sm sm:text-base text-white/85 max-w-xl mx-auto">Have a question about your order, need product advice, or just want to say hello? We typically reply within a few hours.</p>
        </div>
    </div>

    <div class="container mx-auto px-4 py-10 sm:py-14">
        <div class="max-w-6xl mx-auto">

            <!-- Success Message -->
            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl flex items-start gap-3">
                    <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm text-green-700">{{ session('success') }}</p>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-8">

                <!-- Left Column: Contact Form (50%) -->
                <div>
                    <div class="bg-white border border-neutral-100 rounded-2xl p-6 sm:p-8 h-full shadow-sm">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-10 h-10 bg-primary-600/10 rounded-xl flex items-center justify-center">
                                <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-neutral-900">Send us a message</h2>
                                <p class="text-[11px] text-neutral-500">We'll get back to you within 24 hours</p>
                            </div>
                        </div>

                        <form action="{{ route('contact.send') }}" method="POST" class="flex flex-col h-[calc(100%-2rem)] space-y-4">
                            @csrf
                            {{-- Honeypot: hidden from humans, bots fill it --}}
                            <div style="position:absolute;left:-9999px;opacity:0;height:0;overflow:hidden" aria-hidden="true" tabindex="-1">
                                <input type="text" name="website" value="" autocomplete="off" tabindex="-1">
                            </div>
                            <input type="hidden" name="_ts" value="{{ time() }}">

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="name" class="block text-sm font-medium text-neutral-700 mb-1.5">Your Name <span class="text-red-400">*</span></label>
                                    <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                           class="w-full px-4 py-2.5 bg-neutral-50 border border-neutral-200 rounded-xl text-sm text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-primary-600/20 focus:border-primary-600 transition-all @error('name') border-red-300 bg-red-50 @enderror"
                                           placeholder="John Doe">
                                    @error('name')
                                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="email" class="block text-sm font-medium text-neutral-700 mb-1.5">Email Address <span class="text-red-400">*</span></label>
                                    <input type="email" name="email" id="email" value="{{ old('email') }}" required
                                           class="w-full px-4 py-2.5 bg-neutral-50 border border-neutral-200 rounded-xl text-sm text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-primary-600/20 focus:border-primary-600 transition-all @error('email') border-red-300 bg-red-50 @enderror"
                                           placeholder="you@example.com">
                                    @error('email')
                                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div>
                                <label for="phone" class="block text-sm font-medium text-neutral-700 mb-1.5">Phone Number</label>
                                <input type="tel" name="phone" id="phone" value="{{ old('phone') }}"
                                       class="w-full px-4 py-2.5 bg-neutral-50 border border-neutral-200 rounded-xl text-sm text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-primary-600/20 focus:border-primary-600 transition-all @error('phone') border-red-300 bg-red-50 @enderror"
                                       placeholder="+91 98765 43210">
                                @error('phone')
                                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="subject" class="block text-sm font-medium text-neutral-700 mb-1.5">Subject <span class="text-red-400">*</span></label>
                                <input type="text" name="subject" id="subject" value="{{ old('subject') }}" required
                                       class="w-full px-4 py-2.5 bg-neutral-50 border border-neutral-200 rounded-xl text-sm text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-primary-600/20 focus:border-primary-600 transition-all @error('subject') border-red-300 bg-red-50 @enderror"
                                       placeholder="How can we help?">
                                @error('subject')
                                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="flex-1">
                                <label for="message" class="block text-sm font-medium text-neutral-700 mb-1.5">Message <span class="text-red-400">*</span></label>
                                <textarea name="message" id="message" rows="6" required
                                          class="w-full h-[calc(100%-1.75rem)] min-h-[140px] px-4 py-2.5 bg-neutral-50 border border-neutral-200 rounded-xl text-sm text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-primary-600/20 focus:border-primary-600 transition-all resize-none @error('message') border-red-300 bg-red-50 @enderror"
                                          placeholder="Tell us more about your inquiry...">{{ old('message') }}</textarea>
                                @error('message')
                                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="pt-1">
                                <button type="submit"
                                        class="w-full sm:w-auto px-6 py-2 bg-linear-to-r from-accent-500 via-accent-500 to-accent-600 hover:from-accent-600 hover:via-accent-600 hover:to-accent-600 text-white text-sm font-semibold rounded-xl shadow-lg shadow-accent-500/25 hover:shadow-accent-500/40 transition-all duration-300 transform hover:-translate-y-0.5 active:translate-y-0 focus:outline-none focus:ring-2 focus:ring-primary-600/50 focus:ring-offset-2">
                                    Send Message
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Right Column: Contact Details + Map (50%) -->
                <div class="flex flex-col gap-5">

                    <!-- Contact Details -->
                    <div class="bg-white border border-neutral-100 rounded-2xl p-6 sm:p-7 shadow-sm">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="w-10 h-10 bg-accent-500/10 rounded-xl flex items-center justify-center">
                                <svg class="w-5 h-5 text-accent-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-neutral-900">Reach Us Directly</h2>
                                <p class="text-[11px] text-neutral-500">Multiple ways to connect</p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <!-- Address -->
                            <div class="flex items-start gap-3">
                                <div class="w-9 h-9 bg-primary-600/5 rounded-lg flex items-center justify-center shrink-0 mt-0.5">
                                    <svg class="w-4.5 h-4.5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-neutral-900">Visit Us</p>
                                    <p class="text-[13px] text-neutral-600 leading-relaxed">{!! nl2br(e($theme->get('company_address', ''))) !!}</p>
                                </div>
                            </div>

                            <!-- Phone -->
                            <div class="flex items-start gap-3">
                                <div class="w-9 h-9 bg-primary-600/5 rounded-lg flex items-center justify-center shrink-0 mt-0.5">
                                    <svg class="w-4.5 h-4.5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-neutral-900">{{ __('ui.phone') }}</p>
                                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', (string) ($theme->get('contact_whatsapp', '') ?: $theme->get('contact_phone', ''))) }}" target="_blank" class="text-[13px] text-primary-600 hover:text-primary-700 transition-colors">{{ $theme->get('contact_phone', '') }} (WhatsApp)</a>
                                </div>
                            </div>

                            <!-- Email -->
                            <div class="flex items-start gap-3">
                                <div class="w-9 h-9 bg-primary-600/5 rounded-lg flex items-center justify-center shrink-0 mt-0.5">
                                    <svg class="w-4.5 h-4.5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-neutral-900">{{ __('ui.email') }}</p>
                                    <a href="mailto:{{ $theme->get('contact_email', '') }}" class="text-[13px] text-primary-600 hover:text-primary-700 transition-colors">{{ $theme->get('contact_email', '') }}</a>
                                </div>
                            </div>

                            <!-- Business Hours -->
                            <div class="flex items-start gap-3">
                                <div class="w-9 h-9 bg-primary-600/5 rounded-lg flex items-center justify-center shrink-0 mt-0.5">
                                    <svg class="w-4.5 h-4.5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-neutral-900">Business Hours</p>
                                    <p class="text-[13px] text-neutral-600 leading-relaxed">{{ $theme->get('business_hours', 'Mon-Fri 9AM-6PM, Sat 10AM-4PM') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Google Map (dynamic from Settings) -->
                    @php $mapEmbed = $theme->get('google_maps_embed', ''); @endphp
                    @if($mapEmbed)
                    <div class="bg-white border border-neutral-100 rounded-2xl overflow-hidden flex-1 shadow-sm">
                        <iframe
                            src="{{ $mapEmbed }}"
                            width="100%"
                            height="280"
                            style="border:0;"
                            allowfullscreen=""
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            class="w-full h-full min-h-[280px] block">
                        </iframe>
                    </div>
                    @endif
                </div>

            </div>

            {{-- Quick FAQ teaser --}}
            <div class="mt-10 bg-linear-to-br from-neutral-50 to-white border border-neutral-100 rounded-2xl p-6 sm:p-8 text-center">
                <h3 class="text-lg font-bold text-neutral-900 mb-2">Looking for quick answers?</h3>
                <p class="text-sm text-neutral-600 mb-4 max-w-md mx-auto">Most common questions about orders, shipping, and returns are answered in our FAQ.</p>
                <a href="{{ route('faq') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold rounded-full transition-colors shadow-sm">
                    Browse FAQs
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
        </div>
    </div>
</x-layouts.app>
