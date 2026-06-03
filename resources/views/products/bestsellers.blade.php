<x-layouts.app>
    <x-slot name="title">Bestsellers - {{ config('app.name') }}</x-slot>

    @push('meta')
        <meta name="description" content="Shop our bestselling products at {{ config('app.name') }}. Top-rated items loved by customers.">
        <link rel="canonical" href="{{ url('/products/bestsellers') }}">
        <meta property="og:title" content="Bestsellers - {{ config('app.name') }}">
        <meta property="og:description" content="Shop our bestselling products at {{ config('app.name') }}. Top-rated items loved by customers.">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url('/products/bestsellers') }}">
        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="Bestsellers - {{ config('app.name') }}">
    @endpush

    <div class="bg-neutral-50 border-b border-neutral-100">
        <div class="container mx-auto px-4 py-3">
            <x-breadcrumb :items="[['label' => 'Bestsellers', 'url' => null]]" />
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-neutral-900">Bestsellers</h1>
            <p class="text-neutral-600">{{ $products->total() }} products</p>
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
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                <h3 class="text-lg font-medium text-neutral-900 mb-2">No bestsellers yet</h3>
                <p class="text-neutral-600 mb-4">Check back soon for popular products.</p>
                <a href="{{ route('products.index') }}" class="btn-primary">Browse All Products</a>
            </div>
        @endif
    </div>
</x-layouts.app>
