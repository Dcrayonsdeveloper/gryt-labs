<x-layouts.app>
    <x-slot name="title">{{ $page->meta_title ?? $page->title }}</x-slot>

    @push('meta')
        @if($page->meta_description)
            <meta name="description" content="{{ $page->meta_description }}">
            <meta property="og:description" content="{{ $page->meta_description }}">
            <meta name="twitter:description" content="{{ $page->meta_description }}">
        @endif
        <link rel="canonical" href="{{ url('/page/' . $page->slug) }}">
        <meta property="og:title" content="{{ $page->meta_title ?? $page->title }} - {{ config('app.name') }}">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url('/page/' . $page->slug) }}">
        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="{{ $page->meta_title ?? $page->title }} - {{ config('app.name') }}">
    @endpush

    <div class="container mx-auto px-4 py-12">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-4xl font-bold text-neutral-900 mb-6">{{ $page->title }}</h1>

            <div class="prose prose-lg max-w-none">
                {!! \App\Helpers\HtmlSanitizer::clean($page->content) !!}
            </div>
        </div>
    </div>
</x-layouts.app>
