@php
    $__reelsHeading = $theme->get('reels_section_heading', '');
    $__reelsSubheading = $theme->get('reels_section_subheading', '');
@endphp
{{-- Shoppable Instagram Reels Carousel — Lazy loaded via AJAX --}}
<section class="ig-shop-section py-10 lg:py-14" style="background: linear-gradient(180deg, #fff 0%, #f8f6f3 100%);"
         x-data="igReels()" x-init="loadReels()">

    {{-- Header --}}
    <div class="container mx-auto px-4">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-linear-to-br from-[#833ab4] via-[#fd1d1d] to-[#fcb045] flex items-center justify-center">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="white"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/></svg>
                </div>
                <div>
                    <h2 class="section-title" style="margin:0;font-size:22px;">
                        {{ $__reelsHeading ?: 'Follow Us on Instagram' }}
                    </h2>
                    <p class="mt-0.5">
                        @if($__reelsSubheading)
                            <span class="text-xs text-[#3a3a3a]">{{ $__reelsSubheading }}</span>
                        @else
                            <img src="{{ asset($theme->get('store_logo', 'images/logo.png')) }}" alt="{{ config('app.name') }}" style="height:24px;width:auto;">
                        @endif
                    </p>
                </div>
            </div>
            <a x-show="igHandle" :href="'https://instagram.com/' + igHandle.replace('@', '')" target="_blank" rel="noopener" class="view-all-link" style="display:inline-flex;align-items:center;gap:6px;">
                View All
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
    </div>

    {{-- Loading skeleton --}}
    <div x-show="loading" class="ig-reels-carousel">
        <div class="ig-reels-track">
            <template x-for="i in 6" :key="i">
                <div class="ig-reels-card">
                    <div class="ig-reels-frame animate-pulse" style="background:#e5e5e5;"></div>
                </div>
            </template>
        </div>
    </div>

    {{-- Reels Carousel --}}
    <div x-show="!loading && reels.length > 0" x-cloak class="ig-reels-carousel">
        <div class="ig-reels-track" x-ref="track">
            <template x-for="(reel, idx) in reels" :key="reel.shortcode || idx">
                <div class="ig-reels-card">
                    <div class="ig-reels-frame">
                        <a :href="reel.permalink" target="_blank" rel="noopener" class="ig-reels-link">
                            {{-- Video autoplay for own reels with media_url --}}
                            <template x-if="reel.media_url">
                                <video :src="reel.media_url"
                                       autoplay muted loop playsinline webkit-playsinline
                                       preload="metadata"
                                       :poster="reel.thumbnail_url || ''"
                                       class="ig-reels-video"
                                       x-intersect:enter="$el.play().catch(function(){})"
                                       x-intersect:leave="$el.pause()">
                                </video>
                            </template>
                            {{-- Thumbnail for collab reels (no media_url) --}}
                            <template x-if="!reel.media_url && reel.thumbnail_url">
                                <img :src="reel.thumbnail_url"
                                     :alt="reel.caption ? reel.caption.substring(0, 60) : 'Instagram Reel'"
                                     loading="lazy"
                                     class="ig-reels-img">
                            </template>
                            {{-- Fallback gradient --}}
                            <template x-if="!reel.media_url && !reel.thumbnail_url">
                                <div class="ig-reels-placeholder">
                                    <svg width="36" height="36" viewBox="0 0 24 24" fill="rgba(255,255,255,0.8)"><path d="M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913a6.6 6.6 0 001.384 2.126A6.6 6.6 0 004.14 23.37c.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558a6.6 6.6 0 002.126-1.384 6.6 6.6 0 001.384-2.126c.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913a6.6 6.6 0 00-1.384-2.126A6.6 6.6 0 0019.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm7.846-10.405a1.44 1.44 0 11-2.88 0 1.44 1.44 0 012.88 0z"/></svg>
                                </div>
                            </template>
                            {{-- Play overlay (hover only, for all reels) --}}
                            <div class="ig-reels-play-overlay">
                                <div class="ig-reels-play-btn">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="white"><path d="M8 5v14l11-7z"/></svg>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </template>
        </div>

        {{-- Scroll arrows removed --}}
    </div>

    {{-- Instagram follow strip --}}
    <div x-show="igHandle" x-cloak class="text-center mt-6">
        <a :href="'https://www.instagram.com/' + igHandle.replace('@', '') + '?igshid=follow'" target="_blank" rel="noopener"
           class="inline-flex items-center gap-2 px-5 py-2 rounded-full text-sm font-semibold text-white transition-all hover:scale-105"
           style="background: linear-gradient(135deg, #833ab4, #fd1d1d, #fcb045);">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="white"><path d="M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913a6.6 6.6 0 001.384 2.126A6.6 6.6 0 004.14 23.37c.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558a6.6 6.6 0 002.126-1.384 6.6 6.6 0 001.384-2.126c.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913a6.6 6.6 0 00-1.384-2.126A6.6 6.6 0 0019.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm7.846-10.405a1.44 1.44 0 11-2.88 0 1.44 1.44 0 012.88 0z"/></svg>
            Follow <span x-text="igHandle"></span>
        </a>
    </div>
</section>

<script>
document.addEventListener('alpine:init', function() {
    Alpine.data('igReels', function() {
        return {
            reels: [],
            igHandle: '',
            reelsUrl: '',
            loading: true,
            loadReels: function() {
                var self = this;
                fetch('/api/reels')
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        var allReels = data.reels || [];
                        allReels = allReels.filter(function(r) { return !!r.media_url || !!r.thumbnail_url; });
                        // Shuffle (Fisher-Yates) so every visit shows a fresh
                        // random mix instead of the same fixed reels each time.
                        for (var i = allReels.length - 1; i > 0; i--) {
                            var j = Math.floor(Math.random() * (i + 1));
                            var tmp = allReels[i]; allReels[i] = allReels[j]; allReels[j] = tmp;
                        }
                        self.reels = allReels.slice(0, 12);
                        self.igHandle = data.handle || '';
                        self.reelsUrl = data.reels_url || '/reels';
                        self.loading = false;
                    })
                    .catch(function() { self.loading = false; });
            }
        };
    });
});
</script>

<style>
    .ig-reels-carousel { position: relative; }
    .ig-reels-track {
        display: flex; gap: 14px; overflow-x: auto; scroll-snap-type: x mandatory;
        scrollbar-width: none; -ms-overflow-style: none;
        padding: 4px 16px 8px; margin: 0; scroll-padding: 0;
        justify-content: center;
    }
    .ig-reels-track::-webkit-scrollbar { display: none; }
    .ig-reels-card { flex: 0 0 200px; scroll-snap-align: start; }

    .ig-reels-frame {
        position: relative; border-radius: 14px; overflow: hidden;
        aspect-ratio: 9/16; background: #f0f0f0;
    }

    .ig-reels-link {
        display: block; width: 100%; height: 100%; text-decoration: none;
        position: relative;
    }
    .ig-reels-img, .ig-reels-video {
        width: 100%; height: 100%; object-fit: cover; display: block;
        transition: transform 0.3s; pointer-events: none;
    }
    .ig-reels-link:hover .ig-reels-img,
    .ig-reels-link:hover .ig-reels-video { transform: scale(1.05); }

    .ig-reels-play-overlay {
        position: absolute; inset: 0;
        display: flex; align-items: center; justify-content: center;
        background: rgba(0,0,0,0.08); opacity: 0; transition: opacity 0.3s;
    }
    .ig-reels-link:hover .ig-reels-play-overlay { opacity: 1; }
    .ig-reels-play-btn {
        width: 48px; height: 48px; border-radius: 50%;
        background: rgba(0,0,0,0.45); backdrop-filter: blur(4px);
        display: flex; align-items: center; justify-content: center;
    }

    .ig-reels-placeholder {
        width: 100%; height: 100%;
        background: linear-gradient(135deg, #833ab4, #fd1d1d, #fcb045);
        display: flex; align-items: center; justify-content: center;
    }

    .ig-reels-badge {
        position: absolute; top: 10px; right: 10px; z-index: 2;
        display: flex; align-items: center; gap: 4px;
        background: rgba(0,0,0,0.5); backdrop-filter: blur(4px);
        padding: 4px 8px; border-radius: 6px;
        font-size: 10px; font-weight: 600; color: #fff;
    }

    .ig-reels-arrow {
        position: absolute; top: 40%; transform: translateY(-50%);
        width: 40px; height: 40px; border-radius: 50%;
        background: #fff; border: 1px solid #e5e5e5;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; z-index: 3; transition: all 0.2s; color: #333;
    }
    .ig-reels-arrow:hover { background: var(--color-primary-600); color: #fff; border-color: var(--color-primary-600); }
    .ig-reels-arrow--left { left: 8px; }
    .ig-reels-arrow--right { right: 8px; }

    @media (max-width: 767px) {
        .ig-reels-card { flex: 0 0 160px; }
        .ig-reels-arrow { display: none; }
        .ig-reels-track { gap: 10px; padding-left: 12px; padding-right: 12px; }
    }
    @media (max-width: 480px) {
        .ig-reels-card { flex: 0 0 140px; }
        .ig-reels-track { gap: 8px; padding-left: 10px; padding-right: 10px; }
    }
</style>
