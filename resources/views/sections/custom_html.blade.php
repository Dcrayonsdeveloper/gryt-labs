@php $settings = $settings ?? []; @endphp
@php
    $html = $settings['html'] ?? '';
    $allowed = '<div><span><p><a><img><h1><h2><h3><h4><h5><h6><ul><ol><li><br><hr><strong><em><b><i><u><table><thead><tbody><tr><th><td><blockquote><figure><figcaption><picture><source><video><section><article><header><footer><nav><main><small><sub><sup><del><ins><mark><details><summary>';
    $sanitized = \App\Helpers\HtmlSanitizer::clean($html);
@endphp

@if($sanitized)
<section class="py-8 sm:py-12">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="prose prose-gray max-w-none">
            {!! $sanitized !!}
        </div>
    </div>
</section>
@endif
