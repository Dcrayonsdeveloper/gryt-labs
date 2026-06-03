<x-layouts.admin>
    <x-slot name="title">Online Store</x-slot>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold text-neutral-900">Online Store</h1>
            <a href="{{ url('/') }}" target="_blank" class="btn btn-secondary text-sm">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                View store
            </a>
        </div>
    </x-slot>

    {{-- Quick navigation cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <a href="{{ route('admin.homepage.site-settings') }}" class="bg-white rounded-xl shadow-sm p-5 flex items-center gap-4 hover:shadow-md transition-shadow">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0 bg-gray-100">
                <svg class="w-5 h-5 text-neutral-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <div>
                <p class="text-sm font-semibold text-neutral-900">Site Settings</p>
                <p class="text-xs text-neutral-500">Logo, brand, social</p>
            </div>
        </a>

        <a href="{{ route('admin.homepage.hero-banners') }}" class="bg-white rounded-xl shadow-sm p-5 flex items-center gap-4 hover:shadow-md transition-shadow">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0 bg-gray-100">
                <svg class="w-5 h-5 text-neutral-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg>
            </div>
            <div>
                <p class="text-sm font-semibold text-neutral-900">Hero Banners</p>
                <p class="text-xs text-neutral-500">{{ $banners->count() }} banners</p>
            </div>
        </a>

        <a href="{{ route('admin.homepage.sections') }}" class="bg-white rounded-xl shadow-sm p-5 flex items-center gap-4 hover:shadow-md transition-shadow">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0 bg-gray-100">
                <svg class="w-5 h-5 text-neutral-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/></svg>
            </div>
            <div>
                <p class="text-sm font-semibold text-neutral-900">Sections</p>
                <p class="text-xs text-neutral-500">{{ $sections->where('is_active', true)->count() }} active</p>
            </div>
        </a>

        <a href="{{ route('admin.homepage.testimonials') }}" class="bg-white rounded-xl shadow-sm p-5 flex items-center gap-4 hover:shadow-md transition-shadow">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0 bg-gray-100">
                <svg class="w-5 h-5 text-neutral-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"/></svg>
            </div>
            <div>
                <p class="text-sm font-semibold text-neutral-900">Testimonials</p>
                <p class="text-xs text-neutral-500">{{ $testimonials->count() }} reviews</p>
            </div>
        </a>
    </div>

    {{-- Navigation --}}
    <a href="{{ route('admin.homepage.navigation') }}" class="bg-white rounded-xl shadow-sm p-5 flex items-center gap-4 hover:shadow-md transition-shadow mb-6">
        <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0 bg-gray-100">
            <svg class="w-5 h-5 text-neutral-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
        </div>
        <div class="flex-1">
            <p class="text-sm font-semibold text-neutral-900">Navigation</p>
            <p class="text-xs text-neutral-500">Manage menus and links</p>
        </div>
        <svg class="w-4 h-4 text-neutral-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
    </a>

    {{-- Section Order --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-5 py-4 flex items-center justify-between border-b border-gray-200">
            <h2 class="text-sm font-semibold text-neutral-900">Homepage sections</h2>
            <a href="{{ route('admin.homepage.sections') }}" class="text-sm font-medium text-blue-700">Manage</a>
        </div>

        <div class="divide-y divide-gray-100">
            @foreach($sections->sortBy('position') as $section)
                <div class="flex items-center justify-between px-5 py-3 hover:bg-neutral-50 transition-colors">
                    <div class="flex items-center gap-3">
                        <span class="w-6 h-6 rounded flex items-center justify-center text-xs font-medium text-neutral-500 bg-gray-100">
                            {{ $loop->iteration }}
                        </span>
                        <div>
                            <p class="text-sm font-medium text-neutral-900">{{ $section->title }}</p>
                            <p class="text-xs text-neutral-400">{{ $section->type }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        @if($section->is_active)
                            <span class="inline-flex items-center gap-1 text-xs font-medium text-green-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-700"></span> Active
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 text-xs font-medium text-neutral-400">
                                <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Hidden
                            </span>
                        @endif
                        <a href="{{ route('admin.homepage.sections.edit', $section) }}" class="text-xs font-medium text-blue-700">Edit</a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-layouts.admin>
