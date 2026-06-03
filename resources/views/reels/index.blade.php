<x-layouts.app>
    <x-slot name="title">Reels & Videos - {{ config('app.name') }}</x-slot>

    @push('meta')
        <meta name="description" content="Watch the latest reels and videos from {{ config('app.name') }}. See our products in action!">
        <meta property="og:title" content="Reels & Videos - {{ config('app.name') }}">
        <meta property="og:description" content="Watch the latest reels and videos from {{ config('app.name') }}.">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ route('reels.index') }}">
        <link rel="canonical" href="{{ route('reels.index') }}">

        {{-- JSON-LD ItemList for video gallery --}}
        <?php
            $fallbackThumb = asset('images/placeholder-banner.png');
            $reelsSchema = [
                '@context' => 'https://schema.org',
                '@type' => 'CollectionPage',
                'name' => 'Reels & Videos - ' . config('app.name'),
                'description' => 'Watch the latest product videos and reels from ' . config('app.name') . '. Shop trending products now.',
                'url' => route('reels.index'),
                'mainEntity' => [
                    '@type' => 'ItemList',
                    'numberOfItems' => count($reels),
                    'itemListElement' => collect($reels)->map(function ($r, $i) use ($fallbackThumb) {
                        $isVideo = in_array($r['media_type'] ?? 'VIDEO', ['VIDEO', 'REELS']);
                        $caption = $r['caption'] ?: config('app.name') . ' - Product Video';
                        $thumb = $r['thumbnail_url'] ?: $fallbackThumb;
                        $date = !empty($r['timestamp']) ? \Carbon\Carbon::parse($r['timestamp'])->toIso8601String() : now()->toIso8601String();

                        $item = [
                            '@type' => $isVideo ? 'VideoObject' : 'ImageObject',
                            'name' => Str::limit($caption, 100),
                            'description' => Str::limit($r['caption'] ?: 'Watch this product video from ' . config('app.name'), 300),
                            'thumbnailUrl' => $thumb,
                            'uploadDate' => $date,
                            'url' => route('reels.show', $r['shortcode']),
                        ];

                        // Only add contentUrl for videos with actual media URL
                        if ($isVideo && !empty($r['media_url'])) {
                            $item['contentUrl'] = $r['media_url'];
                        }

                        return [
                            '@type' => 'ListItem',
                            'position' => $i + 1,
                            'item' => $item,
                        ];
                    })->toArray(),
                ],
            ];
        ?>
        <script type="application/ld+json">{!! json_encode($reelsSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    @endpush

    <div class="bg-[#f8f6f3] min-h-screen">
        {{-- Header --}}
        <div class="bg-primary-600 py-8 lg:py-12">
            <div class="container mx-auto px-4 text-center">
                <h1 class="text-2xl lg:text-3xl font-bold text-white mb-2">Instagram Feed</h1>
                <p class="text-white/70 text-sm max-w-md mx-auto">Watch our latest videos and explore our products</p>
            </div>
        </div>

        {{-- Reels Grid --}}
        <div class="container mx-auto px-4 py-8 lg:py-12">
            @if(count($reels))
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 lg:gap-6">
                    @foreach($reels as $reel)
                        @php $isVideo = in_array($reel['media_type'] ?? 'VIDEO', ['VIDEO', 'REELS']); @endphp
                        <a href="{{ route('reels.show', $reel['shortcode']) }}" class="group block">
                            <div class="relative rounded-2xl overflow-hidden bg-[#f0f0f0] shadow-sm group-hover:shadow-md transition-shadow" style="aspect-ratio: {{ $isVideo ? '9/16' : '1/1' }};">
                                @if($reel['thumbnail_url'])
                                    <img src="{{ $reel['thumbnail_url'] }}" alt="{{ Str::limit($reel['caption'], 60) }}" loading="lazy"
                                         class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">
                                @else
                                    <div class="w-full h-full bg-gradient-to-br from-primary-600 to-primary-700"></div>
                                @endif

                                {{-- Play overlay (only for videos) --}}
                                @if($isVideo)
                                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors flex items-center justify-center">
                                    <div class="w-14 h-14 bg-black/50 backdrop-blur rounded-full flex items-center justify-center opacity-70 group-hover:opacity-100 group-hover:scale-110 transition-all">
                                        <svg class="w-7 h-7 text-white ml-1" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                    </div>
                                </div>
                                @endif

                                {{-- Badge --}}
                                <div class="absolute top-3 right-3 flex items-center gap-1 bg-black/50 backdrop-blur px-2 py-1 rounded-md">
                                    @if($isVideo)
                                        <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/></svg>
                                        <span class="text-[10px] font-semibold text-white">Reel</span>
                                    @else
                                        <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>
                                        <span class="text-[10px] font-semibold text-white">Post</span>
                                    @endif
                                </div>

                                {{-- Hover badge --}}
                                <div class="absolute bottom-3 left-3 right-3">
                                    <div class="bg-white/95 backdrop-blur rounded-lg px-3 py-2 flex items-center justify-between opacity-0 group-hover:opacity-100 transition-opacity translate-y-2 group-hover:translate-y-0">
                                        <span class="text-xs font-semibold text-primary-600">{{ $isVideo ? 'Watch & Shop' : 'View & Shop' }}</span>
                                        <svg class="w-4 h-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </div>
                                </div>
                            </div>
                            @if($reel['caption'])
                                <p class="mt-2 text-xs text-[#3a3a3a] line-clamp-2">{{ Str::limit($reel['caption'], 60) }}</p>
                            @endif
                        </a>
                    @endforeach
                </div>
            @else
                <div class="text-center py-16">
                    <p class="text-[#3a3a3a]">No reels available at the moment. Check back soon!</p>
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>
