<x-layouts.app>
    <x-slot name="title">Blog - {{ config('app.name') }}</x-slot>

    @push('meta')
        <meta name="description" content="Read the latest articles, tips, and guides about trending products and smart shopping at {{ config('app.name') }} blog.">
        <link rel="canonical" href="{{ url('/blog') }}">
        <meta property="og:title" content="Blog - {{ config('app.name') }}">
        <meta property="og:description" content="Read the latest articles, tips, and guides about trending products and smart shopping at {{ config('app.name') }}.">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url('/blog') }}">
        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="Blog - {{ config('app.name') }}">
        <meta name="twitter:description" content="Latest articles about trending products and smart shopping at {{ config('app.name') }}.">
    @endpush

    <div class="container mx-auto px-4 py-8 sm:py-12">

        {{-- Header --}}
        <div class="text-center mb-8">
            <div class="w-12 h-12 mx-auto rounded-full bg-primary-50 flex items-center justify-center mb-3">
                <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                </svg>
            </div>
            <h1 class="text-xl sm:text-2xl font-bold text-neutral-900">Our Blog</h1>
            <p class="text-[13px] text-neutral-600 mt-2 max-w-md mx-auto">Shopping tips, product guides, and the latest trends — straight from our experts.</p>
        </div>

        {{-- Search & Filters --}}
        <form method="GET">
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3 mb-6">
                <div class="relative w-full sm:w-72">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Search posts..."
                           class="w-full pl-9 pr-4 py-2 border border-neutral-200 rounded-full text-[13px] focus:outline-none focus:border-primary-300 focus:ring-2 focus:ring-primary-100 bg-white">
                </div>
                @if(request('search'))
                    <button type="submit" class="px-3 py-1.5 bg-primary-600 text-white text-[13px] font-medium rounded-full hover:bg-primary-700 transition-colors">
                        Search
                    </button>
                @endif
            </div>

            @if($tags->count())
                <div class="flex gap-2 mb-8 overflow-x-auto pb-2 scrollbar-thin">
                    <a href="{{ route('blog') }}"
                       class="px-4 py-1.5 rounded-full text-[13px] font-medium transition-colors whitespace-nowrap shrink-0 {{ !request('tag') ? 'bg-primary-600 text-white shadow-sm' : 'bg-white border border-neutral-200 text-neutral-600 hover:border-primary-300 hover:text-primary-600' }}">
                        All
                    </a>
                    @foreach($tags as $tag)
                        <a href="{{ route('blog', ['tag' => $tag]) }}"
                           class="px-4 py-1.5 rounded-full text-[13px] font-medium transition-colors whitespace-nowrap shrink-0 {{ request('tag') === $tag ? 'bg-primary-600 text-white shadow-sm' : 'bg-white border border-neutral-200 text-neutral-600 hover:border-primary-300 hover:text-primary-600' }}">
                            {{ $tag }}
                        </a>
                    @endforeach
                </div>
            @endif
        </form>

        @if($posts->count())
            {{-- Posts Grid --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 mb-8">
                @foreach($posts as $post)
                    <article class="bg-white rounded-xl border border-neutral-100 overflow-hidden hover:shadow-md transition-all duration-200 group flex flex-col">
                        {{-- Image --}}
                        <a href="{{ route('blog.show', $post->slug) }}" class="block h-100 overflow-hidden shrink-0">
                            @if($post->featured_image)
                                <img src="{{ asset('storage/' . $post->featured_image) }}" alt="{{ $post->title }}"
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                            @else
                                @php
                                    $gradients = [
                                        'from-primary-600 to-primary-900',
                                        'from-[#1a3a5c] to-[#0d1f33]',
                                        'from-[#4a2c5e] to-[#2a1835]',
                                        'from-[#2d4a3e] to-[#1a2d25]',
                                        'from-[#5c3a1a] to-[#33200d]',
                                    ];
                                    $gradient = $gradients[crc32($post->title) % count($gradients)];
                                @endphp
                                <div class="w-full h-full bg-linear-to-br {{ $gradient }} flex items-center justify-center p-5 group-hover:scale-105 transition-transform duration-300">
                                    <div class="text-center">
                                        <div class="w-8 h-8 mx-auto mb-2 rounded-full bg-white/10 flex items-center justify-center">
                                            <svg class="w-4 h-4 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
                                        </div>
                                        <p class="text-white font-bold text-sm leading-snug line-clamp-3 px-2">{{ $post->title }}</p>
                                        @if($post->category)
                                        <p class="text-white/50 text-[10px] font-medium uppercase tracking-wider mt-1.5">{{ $post->category }}</p>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </a>

                        {{-- Content --}}
                        <div class="p-4 flex flex-col flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                @if($post->category)
                                    <span class="text-[11px] font-bold text-primary-600 uppercase tracking-wide">{{ $post->category }}</span>
                                    <span class="text-neutral-300">·</span>
                                @endif
                                <span class="text-[11px] text-neutral-600">{{ $post->reading_time }} min read</span>
                            </div>

                            <h2 class="font-bold text-neutral-900 mb-2 line-clamp-2 group-hover:text-primary-700 transition-colors text-[15px] leading-snug">
                                <a href="{{ route('blog.show', $post->slug) }}">{{ $post->title }}</a>
                            </h2>

                            <p class="text-[13px] text-neutral-600 line-clamp-2 mb-3 leading-relaxed flex-1">{{ $post->excerpt ?? '' }}</p>

                            <div class="flex items-center justify-between pt-3 border-t border-neutral-100">
                                <span class="text-[12px] text-neutral-600">
                                    {{ $post->published_at ? $post->published_at->format('M d, Y') : '' }}
                                </span>
                                <a href="{{ route('blog.show', $post->slug) }}"
                                   class="text-[13px] font-semibold text-primary-600 hover:text-primary-700 inline-flex items-center gap-1">
                                    Read more
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            {{-- Pagination --}}
            @if($posts->hasPages())
                <div class="flex justify-center">
                    {{ $posts->links() }}
                </div>
            @endif

        @else
            {{-- Empty state --}}
            <div class="text-center py-16">
                <div class="w-14 h-14 bg-neutral-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-7 h-7 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                    </svg>
                </div>
                <h3 class="text-[15px] font-semibold text-neutral-700 mb-1">No posts found</h3>
                <p class="text-[13px] text-neutral-600">
                    @if(request('search') || request('category'))
                        No posts match your search. <a href="{{ route('blog') }}" class="text-primary-600 hover:text-primary-700 font-medium">Clear filters</a>
                    @else
                        Check back soon for our latest articles.
                    @endif
                </p>
            </div>
        @endif

    </div>
</x-layouts.app>
