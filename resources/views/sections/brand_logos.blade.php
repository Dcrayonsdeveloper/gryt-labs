@php $settings = $settings ?? []; @endphp
@php
    $heading = $settings['heading'] ?? 'Our Brands';
    $brandIds = $settings['brand_ids'] ?? [];
    $style = $settings['style'] ?? 'scroll';

    $query = \App\Models\Brand::where('is_active', true);
    $brands = count($brandIds)
        ? $query->whereIn('id', $brandIds)->orderByRaw('FIELD(id,' . implode(',', array_map('intval', $brandIds)) . ')')->get()
        : $query->where('is_featured', true)->orderBy('position')->limit(20)->get();
@endphp

@if($brands->count())
<section class="py-8 sm:py-12">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        @if($heading)
        <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mb-6 text-center">{{ $heading }}</h2>
        @endif

        @if($style === 'grid')
        <div class="grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-6 gap-4">
            @foreach($brands as $brand)
            <a href="{{ route('brands.show', $brand->slug) }}"
               class="flex items-center justify-center p-4 bg-white rounded-lg border border-gray-100 hover:shadow-md transition aspect-[3/2]">
                <img src="{{ $brand->logo_url ?? '/images/placeholder.webp' }}" alt="{{ $brand->name }}"
                     class="max-h-12 w-auto object-contain grayscale hover:grayscale-0 transition" loading="lazy">
            </a>
            @endforeach
        </div>
        @else
        <div class="overflow-hidden" x-data="{}" x-init="
            const el = $el.querySelector('.scroll-track');
            if (el) { el.innerHTML += el.innerHTML; }
        ">
            <div class="scroll-track flex items-center gap-10 animate-marquee">
                @foreach($brands as $brand)
                <a href="{{ route('brands.show', $brand->slug) }}" class="shrink-0 flex items-center justify-center px-4">
                    <img src="{{ $brand->logo_url ?? '/images/placeholder.webp' }}" alt="{{ $brand->name }}"
                         class="h-10 sm:h-12 w-auto object-contain grayscale hover:grayscale-0 transition" loading="lazy">
                </a>
                @endforeach
            </div>
        </div>
        <style>
            @keyframes marquee { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }
            .animate-marquee { animation: marquee 30s linear infinite; }
            .animate-marquee:hover { animation-play-state: paused; }
        </style>
        @endif
    </div>
</section>
@endif
