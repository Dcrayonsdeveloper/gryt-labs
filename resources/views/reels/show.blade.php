<x-layouts.app>
    <x-slot name="title">{{ Str::limit($reel['caption'], 60) ?: 'Watch Reel' }} - {{ config('app.name') }}</x-slot>

    @push('meta')
        <meta name="description" content="{{ Str::limit($reel['caption'], 160) }}">
        <meta property="og:title" content="{{ Str::limit($reel['caption'], 60) ?: 'Watch Reel' }} - {{ config('app.name') }}">
        <meta property="og:description" content="{{ Str::limit($reel['caption'], 160) }}">
        <meta property="og:type" content="video.other">
        <meta property="og:url" content="{{ route('reels.show', $reel['shortcode']) }}">
        @if($reel['thumbnail_url'])
            <meta property="og:image" content="{{ $reel['thumbnail_url'] }}">
        @endif
        @if($reel['media_url'])
            <meta property="og:video" content="{{ $reel['media_url'] }}">
            <meta property="og:video:type" content="video/mp4">
        @endif
        <meta name="twitter:card" content="player">
        <meta name="twitter:title" content="{{ Str::limit($reel['caption'], 60) }}">
        <meta name="twitter:description" content="{{ Str::limit($reel['caption'], 160) }}">
        @if($reel['thumbnail_url'])
            <meta name="twitter:image" content="{{ $reel['thumbnail_url'] }}">
        @endif
        <link rel="canonical" href="{{ route('reels.show', $reel['shortcode']) }}">

        <?php
            $isVideo = in_array($reel['media_type'] ?? 'VIDEO', ['VIDEO', 'REELS']);
            $reelCaption = $reel['caption'] ?: config('app.name') . ' - Product Video';
            $reelDesc = $reel['caption'] ?: 'Watch this product video from ' . config('app.name') . '. Shop the latest trending products.';
            $reelThumb = $reel['thumbnail_url'] ?: asset('images/placeholder-banner.png');
            $reelDate = !empty($reel['timestamp']) ? \Carbon\Carbon::parse($reel['timestamp'])->toIso8601String() : now()->toIso8601String();

            if ($isVideo) {
                $reelShowSchema = [
                    '@context' => 'https://schema.org',
                    '@type' => 'VideoObject',
                    'name' => Str::limit($reelCaption, 100),
                    'description' => Str::limit($reelDesc, 300),
                    'thumbnailUrl' => $reelThumb,
                    'uploadDate' => $reelDate,
                    'url' => route('reels.show', $reel['shortcode']),
                    'embedUrl' => route('reels.show', $reel['shortcode']),
                    'publisher' => [
                        '@type' => 'Organization',
                        'name' => config('app.name'),
                        'logo' => [
                            '@type' => 'ImageObject',
                            'url' => asset(\App\Models\Setting::get('store_logo', 'images/logo.png')),
                        ],
                    ],
                    'interactionStatistic' => [
                        '@type' => 'InteractionCounter',
                        'interactionType' => ['@type' => 'WatchAction'],
                        'userInteractionCount' => ($reel['like_count'] ?? 0) + ($reel['comments_count'] ?? 0),
                    ],
                ];
                // Only add contentUrl if actual video URL exists (not permalink)
                if (!empty($reel['media_url'])) {
                    $reelShowSchema['contentUrl'] = $reel['media_url'];
                }
            } else {
                $reelShowSchema = [
                    '@context' => 'https://schema.org',
                    '@type' => 'ImageObject',
                    'name' => Str::limit($reelCaption, 100),
                    'description' => Str::limit($reelDesc, 300),
                    'thumbnailUrl' => $reelThumb,
                    'uploadDate' => $reelDate,
                    'url' => route('reels.show', $reel['shortcode']),
                    'publisher' => [
                        '@type' => 'Organization',
                        'name' => config('app.name'),
                        'logo' => [
                            '@type' => 'ImageObject',
                            'url' => asset(\App\Models\Setting::get('store_logo', 'images/logo.png')),
                        ],
                    ],
                ];
            }
        ?>
        <script type="application/ld+json">{!! json_encode($reelShowSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    @endpush

    <div class="bg-[#f8f6f3] min-h-screen">
        {{-- Breadcrumb --}}
        <div class="container mx-auto px-4 py-4">
            <nav class="text-sm text-[#3a3a3a]">
                <a href="{{ route('home') }}" class="hover:text-primary-600">Home</a>
                <span class="mx-1">/</span>
                <a href="{{ route('reels.index') }}" class="hover:text-primary-600">Instagram</a>
                <span class="mx-1">/</span>
                <span class="text-[#0F1111]">{{ Str::limit($reel['caption'], 30) ?: $reel['shortcode'] }}</span>
            </nav>
        </div>

        {{-- Main Content --}}
        <div class="container mx-auto px-4 pb-12">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                {{-- Instagram Embed (watch time goes to Instagram) --}}
                <div class="lg:col-span-5">
                    <div class="sticky top-24">
                        <div class="ig-embed-wrapper rounded-2xl overflow-hidden bg-white shadow-sm">
                            <blockquote class="instagram-media"
                                        data-instgrm-captioned
                                        data-instgrm-permalink="{{ $reel['permalink'] }}"
                                        data-instgrm-version="14"
                                        style="background:#FFF; border:0; border-radius:16px; margin:0; max-width:100%; min-width:280px; padding:0; width:100%;">
                            </blockquote>
                        </div>

                        {{-- Share buttons --}}
                        <div class="flex items-center justify-between mt-4">
                            <a href="{{ $reel['permalink'] }}" target="_blank" rel="noopener"
                               class="inline-flex items-center gap-2 text-sm text-[#3a3a3a] hover:text-primary-600 transition-colors">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913-.306-.789-.718-1.459-1.384-2.126C21.319 1.347 20.651.935 19.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 2.16c3.203 0 3.585.016 4.85.071 1.17.055 1.805.249 2.227.415.562.217.96.477 1.382.896.419.42.679.819.896 1.381.164.422.36 1.057.413 2.227.057 1.266.07 1.646.07 4.85s-.015 3.585-.074 4.85c-.061 1.17-.256 1.805-.421 2.227-.224.562-.479.96-.899 1.382-.419.419-.824.679-1.38.896-.42.164-1.065.36-2.235.413-1.274.057-1.649.07-4.859.07-3.211 0-3.586-.015-4.859-.074-1.171-.061-1.816-.256-2.236-.421-.569-.224-.96-.479-1.379-.899-.421-.419-.69-.824-.9-1.38-.165-.42-.359-1.065-.42-2.235-.045-1.26-.061-1.649-.061-4.844 0-3.196.016-3.586.061-4.861.061-1.17.255-1.814.42-2.234.21-.57.479-.96.9-1.381.419-.419.81-.689 1.379-.898.42-.166 1.051-.361 2.221-.421 1.275-.045 1.65-.06 4.859-.06l.045.03zm0 3.678c-3.405 0-6.162 2.76-6.162 6.162 0 3.405 2.76 6.162 6.162 6.162 3.405 0 6.162-2.76 6.162-6.162 0-3.405-2.76-6.162-6.162-6.162zM12 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm7.846-10.405c0 .795-.646 1.44-1.44 1.44-.795 0-1.44-.646-1.44-1.44 0-.794.646-1.439 1.44-1.439.793-.001 1.44.645 1.44 1.439z"/></svg>
                                View on Instagram
                            </a>
                            <button onclick="navigator.share ? navigator.share({title: '{{ e(Str::limit($reel['caption'], 50)) }}', url: window.location.href}) : navigator.clipboard.writeText(window.location.href).then(() => alert('Link copied!'))"
                                    class="inline-flex items-center gap-2 text-sm text-[#3a3a3a] hover:text-primary-600 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                                Share
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Info & More Posts --}}
                <div class="lg:col-span-7">
                    <div class="bg-white rounded-2xl p-6 lg:p-8 shadow-sm">
                        <p class="text-xs text-[#3a3a3a] mb-3">{{ \Carbon\Carbon::parse($reel['timestamp'])->diffForHumans() }}</p>

                        @if($reel['caption'])
                            <p class="text-[#0F1111] text-sm leading-relaxed mb-6 whitespace-pre-line">{{ $reel['caption'] }}</p>
                        @endif

                        {{-- Shop Now CTA --}}
                        <div class="border-t border-[#E3E6E6] pt-6 mt-6">
                            <h3 class="text-lg font-semibold text-[#0F1111] mb-3">Shop Our Products</h3>
                            <p class="text-sm text-[#3a3a3a] mb-4">Love what you see? Browse our collection.</p>
                            <a href="{{ route('products.index') }}"
                               class="inline-flex items-center gap-2 px-6 py-2.5 bg-primary-600 text-white rounded-full font-semibold text-sm hover:bg-primary-700 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                Shop Now
                            </a>
                        </div>
                    </div>

                    {{-- More Posts --}}
                    @if(count($moreReels))
                        <div class="mt-8">
                            <h3 class="text-lg font-semibold text-[#0F1111] mb-4">More from Instagram</h3>
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                                @foreach($moreReels as $more)
                                    @php $moreIsVideo = in_array($more['media_type'] ?? 'VIDEO', ['VIDEO', 'REELS']); @endphp
                                    <a href="{{ route('reels.show', $more['shortcode']) }}" class="group block">
                                        <div class="relative rounded-xl overflow-hidden bg-[#f0f0f0]" style="aspect-ratio: {{ $moreIsVideo ? '9/16' : '1/1' }};">
                                            @if($more['thumbnail_url'])
                                                <img src="{{ $more['thumbnail_url'] }}" alt="{{ Str::limit($more['caption'], 40) }}" loading="lazy"
                                                     class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">
                                            @endif
                                            @if($moreIsVideo)
                                            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors flex items-center justify-center">
                                                <div class="w-10 h-10 bg-black/50 backdrop-blur rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                                    <svg class="w-5 h-5 text-white ml-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                                </div>
                                            </div>
                                            @endif
                                        </div>
                                        <p class="mt-2 text-xs text-[#3a3a3a] line-clamp-2">{{ Str::limit($more['caption'], 50) }}</p>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Instagram embed script --}}
    @push('scripts')
    <script async src="//www.instagram.com/embed.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (window.instgrm) window.instgrm.Embeds.process();
        });
    </script>
    @endpush

    @push('styles')
    <style>
        .ig-embed-wrapper iframe { border-radius: 16px !important; max-width: 100% !important; }
        .ig-embed-wrapper .instagram-media { max-width: 100% !important; min-width: 0 !important; }
        @media (max-width: 767px) {
            .ig-embed-wrapper { margin: 0 -0.5rem; }
        }
    </style>
    @endpush
</x-layouts.app>
