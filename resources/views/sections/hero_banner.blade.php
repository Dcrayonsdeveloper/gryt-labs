@php $settings = $settings ?? []; @endphp
@php
    $slides = $settings['slides'] ?? [];
    $autoplay = $settings['autoplay'] ?? true;
    $interval = $settings['interval'] ?? 5000;
@endphp

@if(count($slides))
<section x-data="{
    current: 0,
    slides: {{ count($slides) }},
    auto: {{ $autoplay ? 'true' : 'false' }},
    init() {
        if (this.auto) {
            setInterval(() => this.next(), {{ $interval }});
        }
    },
    next() { this.current = (this.current + 1) % this.slides },
    prev() { this.current = (this.current - 1 + this.slides) % this.slides }
}" class="relative w-full overflow-hidden">
    <div class="relative">
        @foreach($slides as $i => $slide)
        <div x-show="current === {{ $i }}"
             x-transition:enter="transition ease-out duration-500"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="w-full">
            <div class="relative aspect-[21/9] sm:aspect-[21/9] lg:aspect-[21/8]">
                <picture>
                    @if(!empty($slide['mobile_image']))
                    <source media="(max-width: 639px)" srcset="{{ $slide['mobile_image'] }}">
                    @endif
                    <img src="{{ $slide['image'] ?? '' }}" alt="{{ $slide['heading'] ?? 'Banner' }}"
                         class="w-full h-full object-cover" loading="{{ $i === 0 ? 'eager' : 'lazy' }}">
                </picture>
                @if(!empty($slide['heading']) || !empty($slide['cta_text']))
                <div class="absolute inset-0 bg-black/30 flex items-center">
                    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                        <div class="max-w-xl text-white">
                            @if(!empty($slide['heading']))
                            <h2 class="text-2xl sm:text-4xl lg:text-5xl font-bold mb-2">{{ $slide['heading'] }}</h2>
                            @endif
                            @if(!empty($slide['subheading']))
                            <p class="text-sm sm:text-lg lg:text-xl mb-4 opacity-90">{{ $slide['subheading'] }}</p>
                            @endif
                            @if(!empty($slide['cta_text']) && !empty($slide['cta_link']))
                            <a href="{{ $slide['cta_link'] }}"
                               class="inline-block bg-primary-600 hover:bg-primary-700 text-white font-semibold px-6 py-3 rounded-lg transition">
                                {{ $slide['cta_text'] }}
                            </a>
                            @endif
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    @if(count($slides) > 1)
    <button @click="prev()" class="absolute left-3 top-1/2 -translate-y-1/2 bg-white/80 hover:bg-white rounded-full p-2 shadow transition" aria-label="Previous">
        <svg class="w-5 h-5 text-gray-800" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    </button>
    <button @click="next()" class="absolute right-3 top-1/2 -translate-y-1/2 bg-white/80 hover:bg-white rounded-full p-2 shadow transition" aria-label="Next">
        <svg class="w-5 h-5 text-gray-800" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    </button>
    <div class="absolute bottom-3 left-1/2 -translate-x-1/2 flex gap-2">
        @foreach($slides as $i => $slide)
        <button @click="current = {{ $i }}"
                :class="current === {{ $i }} ? 'bg-primary-600 w-6' : 'bg-white/60 w-2'"
                class="h-2 rounded-full transition-all" aria-label="Slide {{ $i + 1 }}"></button>
        @endforeach
    </div>
    @endif
</section>
@endif
