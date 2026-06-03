<x-layouts.admin>
    <x-slot name="title">Themes</x-slot>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-lg font-bold text-neutral-800">Themes</h1>
            <a href="{{ route('admin.theme.index') }}" class="px-3 py-1.5 text-xs font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors">Customize Active Theme</a>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($themes as $slug => $theme)
        <div class="bg-white rounded-xl border border-neutral-200 shadow-sm overflow-hidden {{ $slug === $activeTheme ? 'ring-2 ring-primary-500' : '' }}">
            {{-- Screenshot --}}
            <div class="aspect-[16/10] bg-neutral-100 relative overflow-hidden">
                @php
                    $screenshot = app(\App\Services\ThemeManager::class)->getScreenshot($slug);
                @endphp
                @if($screenshot)
                    <img src="{{ $screenshot }}" alt="{{ $theme['name'] ?? ucfirst($slug) }}" class="w-full h-full object-cover">
                @else
                    <div class="w-full h-full flex items-center justify-center text-neutral-300">
                        <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg>
                    </div>
                @endif

                @if($slug === $activeTheme)
                <div class="absolute top-2 right-2 px-2 py-1 text-[10px] font-bold text-white bg-primary-600 rounded-full uppercase tracking-wider">Active</div>
                @endif
            </div>

            {{-- Info --}}
            <div class="p-4">
                <h3 class="font-semibold text-neutral-900">{{ $theme['name'] ?? ucfirst($slug) }}</h3>
                <p class="text-xs text-neutral-500 mt-0.5">{{ $theme['description'] ?? '' }}</p>
                <div class="flex items-center justify-between mt-3">
                    <span class="text-[10px] text-neutral-400">v{{ $theme['version'] ?? '1.0.0' }} by {{ $theme['author'] ?? \App\Models\Setting::get('store_name', config('app.name')) }}</span>
                    @if($slug === $activeTheme)
                        <a href="{{ route('admin.theme.index') }}" class="px-3 py-1.5 text-xs font-medium text-primary-700 bg-primary-600/10 rounded-lg hover:bg-primary-600/20 transition-colors">Customize</a>
                    @else
                        <form method="POST" action="{{ route('admin.themes.activate') }}">
                            @csrf
                            <input type="hidden" name="theme" value="{{ $slug }}">
                            <button type="submit" onclick="return confirm('Switch to {{ $theme['name'] ?? ucfirst($slug) }} theme?')" class="px-3 py-1.5 text-xs font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors">Activate</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-full text-center py-12">
            <p class="text-neutral-500">No themes available yet. The default theme is active.</p>
            <a href="{{ route('admin.theme.index') }}" class="inline-block mt-3 px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700">Customize Current Theme</a>
        </div>
        @endforelse
    </div>

</x-layouts.admin>
