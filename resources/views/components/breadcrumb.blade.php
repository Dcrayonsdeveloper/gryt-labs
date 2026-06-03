@props(['items' => []])

@php
    $breadcrumbBg = isset($theme) ? $theme->get('breadcrumb_bg_image') : '';
    $hideBreadcrumb = isset($theme) && $theme->get('hide_breadcrumb');
@endphp

@if($hideBreadcrumb)
{{-- Visual breadcrumb hidden via setting; JSON-LD still renders below --}}
@elseif($breadcrumbBg)
<div class="relative w-screen left-1/2 -ml-[50vw] bg-cover bg-center" style="background-image: url('{{ asset($breadcrumbBg) }}');">
    <div class="absolute inset-0 bg-black/40"></div>
    <div class="relative container mx-auto px-4 py-5">
        <nav class="text-[13px]" aria-label="Breadcrumb">
            <ol class="flex items-center flex-wrap gap-1.5">
                <li>
                    <a href="{{ url('/') }}" class="inline-flex items-center gap-1 text-white/80 hover:text-white transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        <span>Home</span>
                    </a>
                </li>
                @foreach($items as $index => $item)
                    <li class="flex items-center gap-1.5">
                        <svg class="w-3 h-3 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                        </svg>
                        @if($loop->last)
                            <span class="text-white font-medium">{{ $item['label'] }}</span>
                        @else
                            <a href="{{ $item['url'] }}" class="text-white/80 hover:text-white transition-colors">{{ $item['label'] }}</a>
                        @endif
                    </li>
                @endforeach
            </ol>
        </nav>
    </div>
</div>
@else
<nav {{ $attributes->merge(['class' => 'text-[13px]']) }} aria-label="Breadcrumb">
    <ol class="flex items-center flex-wrap gap-1.5">
        <li>
            <a href="{{ url('/') }}" class="inline-flex items-center gap-1 text-neutral-600 hover:text-primary-600 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span>Home</span>
            </a>
        </li>

        @foreach($items as $index => $item)
            <li class="flex items-center gap-1.5">
                <svg class="w-3 h-3 text-neutral-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                </svg>
                @if($loop->last)
                    <span class="text-neutral-800 font-medium">{{ $item['label'] }}</span>
                @else
                    <a href="{{ $item['url'] }}" class="text-neutral-600 hover:text-primary-600 transition-colors">{{ $item['label'] }}</a>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
@endif

{{-- BreadcrumbList JSON-LD --}}
<?php
$breadcrumbSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
    ],
];
foreach ($items as $i => $item) {
    $entry = ['@type' => 'ListItem', 'position' => $i + 2, 'name' => $item['label']];
    if (!empty($item['url'])) {
        $entry['item'] = $item['url'];
    }
    $breadcrumbSchema['itemListElement'][] = $entry;
}
?>
<script type="application/ld+json">{!! json_encode($breadcrumbSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
