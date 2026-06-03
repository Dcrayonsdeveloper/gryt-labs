<x-layouts.admin>
    <x-slot name="title">Homepage Sections</x-slot>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-neutral-900">Homepage Sections</h1>
                <p class="text-sm text-neutral-600 mt-1">Toggle visibility and edit content of homepage sections</p>
            </div>
            <a href="{{ route('admin.homepage.index') }}" class="btn btn-secondary">Back to Homepage</a>
        </div>
    </x-slot>

    <div class="card p-6">
        <div class="space-y-3">
            @foreach($sections as $section)
                <div class="flex items-center justify-between p-4 bg-neutral-50 rounded-lg border border-neutral-200 {{ !$section->is_active ? 'opacity-60' : '' }}">
                    <div class="flex items-center gap-4">
                        <div class="cursor-move text-neutral-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/>
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-0.5">
                                <h3 class="font-semibold text-neutral-900">{{ $section->title }}</h3>
                                <span class="badge badge-neutral text-[10px]">{{ $section->key }}</span>
                            </div>
                            <p class="text-sm text-neutral-600">
                                @switch($section->type)
                                    @case('products')
                                        Product slider section
                                        @break
                                    @case('benefits')
                                        Feature cards ({{ is_array($section->content) ? count($section->content) : 0 }} items)
                                        @break
                                    @case('cta')
                                        Promotional banner
                                        @break
                                    @case('testimonials')
                                        Customer reviews carousel
                                        @break
                                    @case('newsletter')
                                        Email subscription form
                                        @break
                                    @case('categories')
                                        Category collections grid
                                        @break
                                    @case('product_banner')
                                        Full-width product banner image
                                        @break
                                    @default
                                        {{ ucfirst($section->type) }} content
                                @endswitch
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <form action="{{ route('admin.homepage.sections.toggle', $section) }}" method="POST" class="inline">
                            @csrf
                            @method('PUT')
                            <button type="submit" class="btn btn-sm {{ $section->is_active ? 'btn-success' : 'btn-secondary' }}">
                                {{ $section->is_active ? 'Visible' : 'Hidden' }}
                            </button>
                        </form>
                        <a href="{{ route('admin.homepage.sections.edit', $section) }}" class="btn btn-sm btn-primary">Edit</a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-layouts.admin>
