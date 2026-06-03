<x-layouts.app>
    <x-slot name="title">{{ $post->seo_data['meta_title'] ?? $post->title }}</x-slot>

    @push('meta')
        <meta name="description" content="{{ $post->seo_data['meta_description'] ?? $post->excerpt ?? Str::limit(strip_tags($post->content), 160) }}">
        @if(!empty($post->seo_data['keywords']))
        <meta name="keywords" content="{{ $post->seo_data['keywords'] }}">
        @endif
        <link rel="canonical" href="{{ route('blog.show', $post->slug) }}">
        <meta property="og:title" content="{{ $post->seo_data['meta_title'] ?? $post->title }}">
        <meta property="og:description" content="{{ $post->excerpt ?? Str::limit(strip_tags($post->content), 160) }}">
        <meta property="og:type" content="article">
        <meta property="og:url" content="{{ route('blog.show', $post->slug) }}">
        @if($post->featured_image)
        <meta property="og:image" content="{{ asset('storage/' . $post->featured_image) }}">
        @endif
        <meta property="article:published_time" content="{{ $post->published_at?->toIso8601String() }}">
        <meta property="article:modified_time" content="{{ $post->updated_at->toIso8601String() }}">
        @if($post->category)
        <meta property="article:section" content="{{ $post->category }}">
        @endif
        @if($post->tags)
            @foreach($post->tags as $tag)
        <meta property="article:tag" content="{{ $tag }}">
            @endforeach
        @endif
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $post->seo_data['meta_title'] ?? $post->title }}">
        <meta name="twitter:description" content="{{ $post->excerpt ?? Str::limit(strip_tags($post->content), 160) }}">
        @if($post->featured_image)
        <meta name="twitter:image" content="{{ asset('storage/' . $post->featured_image) }}">
        @endif

        {{-- BlogPosting JSON-LD --}}
        <?php $blogSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $post->title,
            'description' => $post->excerpt ?? Str::limit(strip_tags($post->content), 160),
            'url' => route('blog.show', $post->slug),
            'datePublished' => $post->published_at?->toIso8601String(),
            'dateModified' => $post->updated_at->toIso8601String(),
            'author' => [
                '@type' => 'Organization',
                'name' => config('app.name', 'Store'),
                'url' => url('/'),
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => config('app.name', 'Store'),
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => asset(\App\Models\Setting::get('store_logo', 'images/logo.png')),
                ],
            ],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => route('blog.show', $post->slug),
            ],
            'image' => $post->featured_image ? asset('storage/' . $post->featured_image) : null,
            'articleSection' => $post->category,
            'keywords' => $post->tags ? implode(', ', $post->tags) : null,
            'wordCount' => str_word_count(strip_tags($post->content ?? '')),
        ]; ?>
        <script type="application/ld+json">{!! json_encode($blogSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    @endpush

    {{-- Breadcrumb --}}
    <div class="bg-neutral-50 border-b border-neutral-100">
        <div class="container mx-auto px-4 py-3">
            <x-breadcrumb :items="[
                ['label' => 'Blog', 'url' => route('blog')],
                ['label' => $post->title, 'url' => null],
            ]" />
        </div>
    </div>

    <div class="container mx-auto px-4 py-8 sm:py-10">
        <div class="max-w-6xl mx-auto">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                {{-- ── Main Content (left) ── --}}
                <div class="lg:col-span-2 min-w-0">

                    {{-- Category & Meta --}}
                    <div class="flex items-center gap-2 mb-4 flex-wrap">
                        @if($post->category)
                            <a href="{{ route('blog', ['category' => $post->category]) }}"
                               class="inline-flex px-3 py-1 text-[11px] font-bold text-primary-700 bg-primary-50 rounded-full hover:bg-primary-100 transition-colors uppercase tracking-wide">
                                {{ $post->category }}
                            </a>
                            <span class="text-neutral-300">·</span>
                        @endif
                        <span class="text-[13px] text-neutral-600">{{ $post->reading_time }} min read</span>
                        <span class="text-neutral-300">·</span>
                        <span class="text-[13px] text-neutral-600">{{ $post->published_at?->format('M d, Y') }}</span>
                        <span class="text-neutral-300">·</span>
                        <span class="text-[13px] text-neutral-600">{{ number_format($post->view_count) }} views</span>
                    </div>

                    {{-- Title --}}
                    <h1 class="text-2xl sm:text-3xl font-bold text-neutral-900 leading-snug mb-4">
                        {{ $post->title }}
                    </h1>

                    {{-- Excerpt --}}
                    @if($post->excerpt)
                        <p class="text-[15px] text-neutral-600 mb-6 leading-relaxed border-l-4 border-primary-200 pl-4">{{ $post->excerpt }}</p>
                    @endif

                    {{-- Featured image is only shown on listing page, not on detail page --}}

                    {{-- Content --}}
                    <div class="bg-white border border-neutral-100 rounded-xl p-5 sm:p-8 mb-6">
                        @if($post->content)
                            <div class="prose prose-neutral max-w-none text-neutral-700 leading-relaxed
                                        prose-headings:text-neutral-900 prose-headings:font-bold prose-headings:leading-tight
                                        prose-h2:text-xl prose-h2:mt-8 prose-h2:mb-3 prose-h2:pb-2 prose-h2:border-b prose-h2:border-neutral-100
                                        prose-h3:text-lg prose-h3:mt-6 prose-h3:mb-2
                                        prose-p:text-[15px] prose-p:leading-[1.8] prose-p:mb-4
                                        prose-a:text-primary-600 prose-a:font-medium prose-a:no-underline hover:prose-a:underline
                                        prose-img:rounded-xl prose-img:shadow-sm
                                        prose-ul:my-4 prose-ul:space-y-1 prose-li:text-[15px] prose-li:leading-[1.7]
                                        prose-ol:my-4 prose-ol:space-y-1
                                        prose-blockquote:border-l-4 prose-blockquote:border-primary-300 prose-blockquote:bg-primary-50/50 prose-blockquote:rounded-r-lg prose-blockquote:py-3 prose-blockquote:px-5 prose-blockquote:not-italic prose-blockquote:text-neutral-700
                                        prose-strong:text-neutral-900
                                        prose-code:text-primary-700 prose-code:bg-primary-50 prose-code:px-1.5 prose-code:py-0.5 prose-code:rounded prose-code:text-sm">
                                {!! \App\Helpers\HtmlSanitizer::clean($post->content) !!}
                            </div>
                        @else
                            <p class="text-neutral-600 italic text-[13px]">No content available.</p>
                        @endif
                    </div>

                    {{-- Share --}}
                    <div class="flex items-center gap-3 mb-6">
                        <span class="text-[13px] text-neutral-500 font-medium">Share:</span>
                        <a href="https://wa.me/?text={{ urlencode($post->title . ' ' . route('blog.show', $post->slug)) }}" target="_blank" rel="noopener"
                           class="w-8 h-8 rounded-full bg-[#25D366]/10 text-[#25D366] flex items-center justify-center hover:bg-[#25D366]/20 transition">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.625.846 5.059 2.284 7.034L.789 23.492l4.624-1.474A11.932 11.932 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.75c-2.15 0-4.148-.688-5.78-1.855l-.413-.31-2.741.874.892-2.657-.342-.432A9.706 9.706 0 012.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75z"/></svg>
                        </a>
                        <a href="https://twitter.com/intent/tweet?text={{ urlencode($post->title) }}&url={{ urlencode(route('blog.show', $post->slug)) }}" target="_blank" rel="noopener"
                           class="w-8 h-8 rounded-full bg-neutral-100 text-neutral-600 flex items-center justify-center hover:bg-neutral-200 transition">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                        </a>
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(route('blog.show', $post->slug)) }}" target="_blank" rel="noopener"
                           class="w-8 h-8 rounded-full bg-[#1877F2]/10 text-[#1877F2] flex items-center justify-center hover:bg-[#1877F2]/20 transition">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </a>
                    </div>

                    {{-- Tags --}}
                    @if($post->tags && count($post->tags))
                        <div class="flex flex-wrap gap-2 mb-6">
                            <span class="text-[13px] text-neutral-600 font-medium self-center">Tags:</span>
                            @foreach($post->tags as $tag)
                                <span class="px-3 py-1 bg-neutral-100 text-neutral-600 rounded-full text-[13px]">{{ $tag }}</span>
                            @endforeach
                        </div>
                    @endif

                    {{-- Recommended Products --}}
                    @php
                        $recommendedProducts = \App\Models\Product::where('is_active', true)
                            ->where('stock_quantity', '>', 0)
                            ->inRandomOrder()
                            ->take(4)
                            ->get();
                    @endphp
                    @if($recommendedProducts->count())
                    <div class="bg-white border border-neutral-100 rounded-xl p-5 sm:p-6 mb-6">
                        <h2 class="text-base font-bold text-neutral-900 mb-4">Recommended Products</h2>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            @foreach($recommendedProducts as $rProduct)
                            <a href="{{ route('products.show', $rProduct->slug) }}" class="group">
                                <div class="aspect-square rounded-lg overflow-hidden bg-neutral-50 mb-2">
                                    @if($rProduct->primary_image_url)
                                        <img src="{{ $rProduct->primary_image_url }}" alt="{{ $rProduct->name }}"
                                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center bg-linear-to-br from-neutral-100 to-neutral-50">
                                            <svg class="w-8 h-8 text-neutral-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                        </div>
                                    @endif
                                </div>
                                <p class="text-[12px] font-medium text-neutral-800 line-clamp-2 leading-snug group-hover:text-primary-600 transition-colors">{{ $rProduct->name }}</p>
                                <div class="flex items-center gap-1.5 mt-1">
                                    <span class="text-[13px] font-bold text-neutral-900">@price($rProduct->price)</span>
                                    @if($rProduct->compare_at_price && $rProduct->compare_at_price > $rProduct->price)
                                    <span class="text-[11px] text-neutral-400 line-through">@price($rProduct->compare_at_price)</span>
                                    @endif
                                </div>
                            </a>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- Back link --}}
                    <a href="{{ route('blog') }}" class="inline-flex items-center gap-2 text-[13px] font-medium text-primary-600 hover:text-primary-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Back to Blog
                    </a>
                </div>

                {{-- ── Sidebar (right) ── --}}
                <div class="lg:col-span-1">
                    <div class="lg:sticky lg:top-6 space-y-4">

                        {{-- More Articles --}}
                        <div class="bg-white border border-neutral-100 rounded-xl overflow-hidden">
                            <div class="px-4 py-3 border-b border-neutral-100 flex items-center justify-between">
                                <h2 class="text-[14px] font-bold text-neutral-900">More Articles</h2>
                                <a href="{{ route('blog') }}" class="text-[12px] text-primary-600 hover:text-primary-700 font-medium">View all</a>
                            </div>

                            @if($related->count())
                                <div class="divide-y divide-neutral-100">
                                    @foreach($related as $rPost)
                                        <a href="{{ route('blog.show', $rPost->slug) }}"
                                           class="flex items-start gap-3 p-3 hover:bg-neutral-50 transition-colors group">
                                            {{-- Thumbnail --}}
                                            <div class="w-16 h-12 rounded-lg overflow-hidden bg-neutral-100 shrink-0">
                                                @if($rPost->featured_image)
                                                    <img src="{{ asset('storage/' . $rPost->featured_image) }}" alt="{{ $rPost->title }}"
                                                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200">
                                                @else
                                                    <div class="w-full h-full flex items-center justify-center bg-neutral-50">
                                                        <svg class="w-5 h-5 text-neutral-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                        </svg>
                                                    </div>
                                                @endif
                                            </div>
                                            {{-- Info --}}
                                            <div class="min-w-0 flex-1">
                                                <p class="text-[13px] font-semibold text-neutral-900 line-clamp-2 group-hover:text-primary-600 transition-colors leading-snug">
                                                    {{ $rPost->title }}
                                                </p>
                                                <div class="flex items-center gap-1.5 mt-1">
                                                    @if($rPost->category)
                                                        <span class="text-[11px] text-primary-600 font-medium">{{ $rPost->category }}</span>
                                                        <span class="text-neutral-300">·</span>
                                                    @endif
                                                    <span class="text-[11px] text-neutral-600">{{ $rPost->published_at?->format('M d, Y') }}</span>
                                                </div>
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            @else
                                <div class="p-4 text-center">
                                    <p class="text-[13px] text-neutral-600">No other articles yet.</p>
                                </div>
                            @endif
                        </div>

                        {{-- Browse by Category --}}
                        @php
                            $sidebarCategories = \App\Models\BlogPost::published()
                                ->whereNotNull('category')
                                ->distinct()
                                ->pluck('category');
                        @endphp
                        @if($sidebarCategories->count())
                            <div class="bg-white border border-neutral-100 rounded-xl overflow-hidden">
                                <div class="px-4 py-3 border-b border-neutral-100">
                                    <h2 class="text-[14px] font-bold text-neutral-900">Browse by Topic</h2>
                                </div>
                                <div class="p-3 flex flex-wrap gap-2">
                                    @foreach($sidebarCategories as $cat)
                                        <a href="{{ route('blog', ['category' => $cat]) }}"
                                           class="px-3 py-1.5 rounded-full text-[12px] font-medium bg-neutral-100 text-neutral-600 hover:bg-primary-50 hover:text-primary-700 transition-colors">
                                            {{ $cat }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                    </div>
                </div>

            </div>
        </div>
    </div>
</x-layouts.app>
