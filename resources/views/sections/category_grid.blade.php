@php $settings = $settings ?? []; @endphp
@php
    $heading = $settings['heading'] ?? 'Shop by Category';
    $columns = $settings['columns'] ?? 4;
    $style = $settings['style'] ?? 'card';
    $categoryIds = $settings['category_ids'] ?? [];

    $query = \App\Models\Category::where('is_active', true);
    $categories = count($categoryIds)
        ? $query->whereIn('id', $categoryIds)->orderByRaw('FIELD(id,' . implode(',', array_map('intval', $categoryIds)) . ')')->get()
        : $query->where('is_featured', true)->orderBy('position')->limit(12)->get();

    $gridCols = match((int) $columns) {
        2 => 'grid-cols-2',
        3 => 'grid-cols-2 sm:grid-cols-3',
        6 => 'grid-cols-3 sm:grid-cols-6',
        default => 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-4',
    };
@endphp

@if($categories->count())
<section class="py-8 sm:py-12">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mb-6">{{ $heading }}</h2>
        <div class="grid {{ $gridCols }} gap-4">
            @foreach($categories as $category)
            <a href="{{ route('categories.show', $category->slug) }}" class="group block">
                @if($style === 'circle')
                <div class="flex flex-col items-center text-center">
                    <div class="w-20 h-20 sm:w-28 sm:h-28 rounded-full overflow-hidden bg-gray-100 ring-2 ring-transparent group-hover:ring-primary-500 transition mb-2">
                        <img src="{{ $category->image_url ?? '/images/placeholder.webp' }}" alt="{{ $category->name }}" class="w-full h-full object-cover" loading="lazy">
                    </div>
                    <span class="text-sm font-medium text-gray-800 group-hover:text-primary-600 transition">{{ $category->name }}</span>
                </div>
                @elseif($style === 'overlay')
                <div class="relative aspect-[4/3] rounded-lg overflow-hidden">
                    <img src="{{ $category->image_url ?? '/images/placeholder.webp' }}" alt="{{ $category->name }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300" loading="lazy">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent flex items-end p-4">
                        <span class="text-white font-semibold text-sm sm:text-base">{{ $category->name }}</span>
                    </div>
                </div>
                @else
                <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden group-hover:shadow-md transition">
                    <div class="aspect-square bg-gray-50">
                        <img src="{{ $category->image_url ?? '/images/placeholder.webp' }}" alt="{{ $category->name }}" class="w-full h-full object-cover" loading="lazy">
                    </div>
                    <div class="p-3 text-center">
                        <span class="text-sm font-medium text-gray-800 group-hover:text-primary-600 transition">{{ $category->name }}</span>
                    </div>
                </div>
                @endif
            </a>
            @endforeach
        </div>
    </div>
</section>
@endif
