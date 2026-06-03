@php $settings = $settings ?? []; @endphp
@php
    $heading = $settings['heading'] ?? '';
    $body = $settings['body'] ?? '';
    $image = $settings['image'] ?? '';
    $ctaText = $settings['cta_text'] ?? '';
    $ctaLink = $settings['cta_link'] ?? '';
    $imagePosition = $settings['image_position'] ?? 'right';
@endphp

<section class="py-8 sm:py-14">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-8 lg:gap-12 items-center">
            {{-- Text --}}
            <div class="{{ $imagePosition === 'left' ? 'lg:order-2' : '' }}">
                @if($heading)
                <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-4">{{ $heading }}</h2>
                @endif
                @if($body)
                <div class="prose prose-gray max-w-none text-gray-600 leading-relaxed">
                    {!! nl2br(e($body)) !!}
                </div>
                @endif
                @if($ctaText && $ctaLink)
                <div class="mt-6">
                    <a href="{{ $ctaLink }}"
                       class="inline-block bg-primary-600 hover:bg-primary-700 text-white font-semibold px-6 py-3 rounded-lg transition">
                        {{ $ctaText }}
                    </a>
                </div>
                @endif
            </div>
            {{-- Image --}}
            <div class="{{ $imagePosition === 'left' ? 'lg:order-1' : '' }}">
                @if($image)
                <div class="rounded-xl overflow-hidden shadow-lg">
                    <img src="{{ $image }}" alt="{{ $heading }}" class="w-full h-auto object-cover" loading="lazy">
                </div>
                @endif
            </div>
        </div>
    </div>
</section>
