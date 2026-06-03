@php $settings = $settings ?? []; @endphp
@php
    $heading = $settings['heading'] ?? 'Products';
    $source = $settings['source'] ?? 'featured';
    $count = min($settings['count'] ?? 12, 24);

    $query = \App\Models\Product::active();
    $products = match($source) {
        'bestsellers' => $query->orderByDesc('sales_count')->limit($count)->get(),
        'new'         => $query->latest()->limit($count)->get(),
        default       => $query->featured()->limit($count)->get(),
    };
@endphp

@if($products->count())
<section class="py-8 sm:py-12">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mb-6">{{ $heading }}</h2>
        <div class="flex gap-4 overflow-x-auto pb-4 snap-x snap-mandatory scrollbar-hide -mx-4 px-4">
            @foreach($products as $product)
            <div class="snap-start shrink-0 w-44 sm:w-56 lg:w-64">
                <x-product-card :product="$product" :compact="true" />
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif
