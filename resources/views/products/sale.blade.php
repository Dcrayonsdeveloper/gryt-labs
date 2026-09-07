<x-layouts.app>
    <x-slot name="title">Sale - {{ config('app.name') }}</x-slot>

    @push('meta')
        <meta name="description" content="Shop the sale at {{ config('app.name') }} — limited-time prices on selected products.">
        <link rel="canonical" href="{{ url('/sale') }}">
        <meta property="og:title" content="Sale - {{ config('app.name') }}">
        <meta property="og:description" content="Limited-time prices on selected products at {{ config('app.name') }}.">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url('/sale') }}">
        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="Sale - {{ config('app.name') }}">
    @endpush

    <div class="bg-neutral-50 border-b border-neutral-100">
        <div class="container mx-auto px-4 py-3">
            <x-breadcrumb :items="[['label' => 'Sale', 'url' => null]]" />
        </div>
    </div>

    {{-- Hero --}}
    <div class="text-white" style="background:linear-gradient(135deg, var(--color-primary-600), var(--color-accent-500));">
        <div class="container mx-auto px-4 py-8 lg:py-10 text-center">
            <span class="inline-block px-3 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-white/20 mb-2">Limited time</span>
            <h1 class="text-2xl lg:text-3xl font-extrabold">Sale</h1>
            <p class="text-white/90 text-sm mt-1">Handpicked products at special prices</p>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        @if($products->count())
            <p class="text-sm text-neutral-600 mb-5">{{ $products->total() }} {{ Str::plural('product', $products->total()) }} on sale</p>

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
                <div class="text-5xl mb-3">🏷️</div>
                <h3 class="text-lg font-medium text-neutral-900 mb-2">No products on sale right now</h3>
                <p class="text-neutral-600 mb-4">Check back soon — new offers are added regularly.</p>
                <a href="{{ route('products.index') }}" class="btn-primary">Browse All Products</a>
            </div>
        @endif
    </div>
</x-layouts.app>
