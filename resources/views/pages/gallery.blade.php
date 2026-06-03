<x-layouts.app>
    @php
        $storeName = $theme->get('store_name', config('app.name'));
        $storeLogo = asset($theme->get('store_logo', 'images/logo.png'));
    @endphp

    <x-slot name="title">Gallery - {{ $storeName }}</x-slot>

    @push('meta')
        <meta name="description" content="Watch {{ $storeName }}'s latest videos.">
        <link rel="canonical" href="{{ url('/gallery') }}">
        <meta property="og:title" content="Gallery - {{ $storeName }}">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url('/gallery') }}">
    @endpush

    <div class="bg-white min-h-screen">
        {{-- Header --}}
        <div class="py-10 text-center border-b border-gray-100">
            <h1 class="text-3xl md:text-4xl font-bold text-gray-900 tracking-tight">Gallery</h1>
            @if($videos->count())
                <p class="mt-2 text-gray-400 text-sm">{{ $videos->count() }} videos</p>
            @endif
        </div>

        <div class="container mx-auto px-4 py-10">
            @if($videos->count())
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($videos as $video)
                        <a href="{{ $video['url'] }}" target="_blank" rel="noopener"
                           class="block bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-shadow border border-gray-100">

                            {{-- Channel header --}}
                            <div class="flex items-center gap-2.5 px-3 pt-3 pb-2">
                                <img src="{{ $storeLogo }}" alt="{{ $storeName }}"
                                     class="w-9 h-9 rounded-full object-cover border border-gray-100 bg-gray-50">
                                <div class="min-w-0">
                                    <p class="text-[13px] font-semibold text-gray-900 truncate">{{ $storeName }}</p>
                                    <p class="text-[11px] text-gray-400 truncate">{{ $storeName }}</p>
                                </div>
                            </div>

                            {{-- Thumbnail --}}
                            <div class="relative bg-gray-100" style="aspect-ratio:16/9;">
                                <img src="{{ $video['thumb'] }}"
                                     alt="{{ $video['title'] }}"
                                     loading="lazy"
                                     class="w-full h-full object-cover">

                                {{-- Red YouTube play button --}}
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <div class="w-14 h-14 bg-red-600 rounded-full flex items-center justify-center shadow-lg">
                                        <svg class="w-7 h-7 text-white ml-1" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M8 5v14l11-7z"/>
                                        </svg>
                                    </div>
                                </div>

                                {{-- Link icon --}}
                                <div class="absolute bottom-2 left-2">
                                    <div class="w-7 h-7 bg-black/50 backdrop-blur rounded-full flex items-center justify-center">
                                        <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            {{-- Title --}}
                            @if($video['title'])
                                <div class="px-3 py-2.5">
                                    <p class="text-sm font-medium text-gray-900 line-clamp-2">{{ $video['title'] }}</p>
                                </div>
                            @endif
                        </a>
                    @endforeach
                </div>
            @else
                <div class="text-center py-20">
                    <p class="text-gray-400">No videos available yet.</p>
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>
