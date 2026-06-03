@php $settings = $settings ?? []; @endphp
@php
    $heading = $settings['heading'] ?? 'Follow Us on Instagram';
    $count = $settings['count'] ?? 6;
@endphp

<section class="py-8 sm:py-12">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        @if($heading)
        <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mb-6 text-center">{{ $heading }}</h2>
        @endif
        <x-instagram-reels :count="$count" />
    </div>
</section>
