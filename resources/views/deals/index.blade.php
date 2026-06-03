<x-layouts.app>
    <x-slot name="title">Deals - {{ config('app.name') }}</x-slot>

    @push('meta')
        <meta name="description" content="Browse the latest deals and discounts at {{ config('app.name') }}. Save big on top products.">
        <link rel="canonical" href="{{ url('/deals') }}">
        <meta property="og:title" content="Deals & Discounts - {{ config('app.name') }}">
        <meta property="og:description" content="Browse the latest deals and discounts at {{ config('app.name') }}. Save big on top products.">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url('/deals') }}">
        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="Deals & Discounts - {{ config('app.name') }}">
    @endpush

    <div class="bg-neutral-50 border-b border-neutral-100">
        <div class="container mx-auto px-4 py-3">
            <x-breadcrumb :items="[['label' => 'Deals', 'url' => null]]" />
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-neutral-900">Deals & Discounts</h1>
            <p class="text-neutral-600">{{ $products->total() }} products on sale</p>
        </div>

        @if($products->count())
            <div x-data="{
                page: {{ $products->currentPage() }},
                loading: false,
                hasMore: {{ $products->hasMorePages() ? 'true' : 'false' }},
                loadMore() {
                    if (this.loading || !this.hasMore) return;
                    this.loading = true;
                    this.page++;
                    const url = new URL(window.location.href);
                    url.searchParams.set('page', this.page);
                    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(r => r.json())
                        .then(data => {
                            this.$refs.grid.insertAdjacentHTML('beforeend', data.html);
                            this.hasMore = data.hasMore;
                            this.loading = false;
                        })
                        .catch(() => { this.loading = false; });
                }
            }" x-init="new IntersectionObserver((e) => { if (e[0].isIntersecting) loadMore(); }, { rootMargin: '200px' }).observe($refs.sentinel)">
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 md:gap-6" x-ref="grid">
                    @foreach($products as $product)
                        <x-product-card :product="$product" />
                    @endforeach
                </div>
                <div x-ref="sentinel" class="h-4"></div>
                <div x-show="loading" x-cloak class="flex justify-center py-8">
                    <svg class="animate-spin h-6 w-6 text-primary-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                </div>
            </div>
        @else
            <div class="text-center py-16">
                <svg class="w-16 h-16 mx-auto text-neutral-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/>
                </svg>
                <h3 class="text-lg font-medium text-neutral-900 mb-2">No deals available</h3>
                <p class="text-neutral-600 mb-4">Check back soon for new deals and discounts.</p>
                <a href="{{ route('products.index') }}" class="btn-primary">Browse Products</a>
            </div>
        @endif
    </div>
</x-layouts.app>
