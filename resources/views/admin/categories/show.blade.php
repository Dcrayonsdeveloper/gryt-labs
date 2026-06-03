<x-layouts.admin>
    <x-slot name="title">{{ $category->name }}</x-slot>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2 text-sm text-neutral-600 mb-1">
                    <a href="{{ route('admin.categories.index') }}" class="hover:text-primary-600 transition-colors">Categories</a>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    <span class="text-neutral-700">{{ $category->name }}</span>
                </div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold text-neutral-900">{{ $category->name }}</h1>
                    @if($category->is_active)
                        <span class="badge badge-success">Active</span>
                    @else
                        <span class="badge badge-neutral">Inactive</span>
                    @endif
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('category.show', $category) }}" target="_blank" class="btn btn-secondary">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                    View on Site
                </a>
                <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-primary">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit
                </a>
            </div>
        </div>
    </x-slot>

    <!-- Stats row -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <div class="card p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-primary-50 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
            <div>
                <p class="text-xs text-neutral-600">Products</p>
                <p class="text-lg font-bold text-neutral-900">{{ $products->total() }}</p>
            </div>
        </div>
        <div class="card p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-purple-50 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs text-neutral-600">Subcategories</p>
                <p class="text-lg font-bold text-neutral-900">{{ $category->children->count() }}</p>
            </div>
        </div>
        <div class="card p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg {{ $category->is_active ? 'bg-success-50' : 'bg-neutral-100' }} flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 {{ $category->is_active ? 'text-success-600' : 'text-neutral-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs text-neutral-600">Status</p>
                <p class="text-lg font-bold {{ $category->is_active ? 'text-success-600' : 'text-neutral-600' }}">{{ $category->is_active ? 'Active' : 'Inactive' }}</p>
            </div>
        </div>
        <div class="card p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs text-neutral-600">Created</p>
                <p class="text-sm font-bold text-neutral-900">{{ $category->created_at->format('M d, Y') }}</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Products in Category -->
            <div class="card overflow-hidden">
                <div class="px-5 py-4 border-b border-neutral-100 bg-neutral-50/50 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        <h2 class="font-semibold text-neutral-900">Products</h2>
                        <span class="badge badge-primary">{{ $products->total() }}</span>
                    </div>
                    @if($products->total() > 0)
                        <a href="{{ route('admin.products.index', ['category' => $category->id]) }}" class="btn btn-sm btn-secondary">
                            View All
                            <svg class="w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    @endif
                </div>

                @if($products->count() > 0)
                    @if($products->total() > 0)
                        <div class="px-5 py-3 border-b border-neutral-200">
                            {{ $products->links('vendor.pagination.info-bar') }}
                        </div>
                    @endif
                    <div class="divide-y divide-neutral-100">
                        @foreach($products as $product)
                            <div class="px-5 py-4 hover:bg-neutral-50/60 transition-colors">
                                <div class="flex items-center gap-4">
                                    <!-- Product Image -->
                                    <a href="{{ route('admin.products.edit', $product) }}" class="shrink-0">
                                        @if($product->primary_image_url)
                                            <img src="{{ $product->primary_image_url }}" alt="{{ $product->name }}"
                                                 class="w-14 h-14 rounded-xl object-cover ring-1 ring-neutral-200">
                                        @else
                                            <div class="w-14 h-14 rounded-xl bg-neutral-100 ring-1 ring-neutral-200 flex items-center justify-center">
                                                <svg class="w-6 h-6 text-neutral-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                </svg>
                                            </div>
                                        @endif
                                    </a>

                                    <!-- Product Info -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <a href="{{ route('admin.products.edit', $product) }}"
                                                   class="text-sm font-semibold text-neutral-900 hover:text-primary-600 transition-colors truncate block"
                                                   title="{{ $product->name }}">
                                                    {{ $product->name }}
                                                </a>
                                                <div class="flex items-center gap-3 mt-1">
                                                    @if($product->seller)
                                                        <span class="text-xs text-neutral-600 flex items-center gap-1">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                                            {{ $product->seller->store_name }}
                                                        </span>
                                                    @endif
                                                    @if($product->sku ?? false)
                                                        <span class="text-xs text-neutral-600 font-mono">SKU: {{ $product->sku }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="text-right shrink-0">
                                                <p class="text-sm font-bold text-neutral-900 font-mono">@price($product->price)</p>
                                                @if($product->is_active)
                                                    <span class="inline-flex items-center gap-1 text-xs text-success-600">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-success-500"></span>
                                                        Active
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1 text-xs text-neutral-600">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-neutral-300"></span>
                                                        Inactive
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if($products->hasPages())
                        <div class="px-5 py-3 border-t border-neutral-200 bg-neutral-50/30">
                            {{ $products->links() }}
                        </div>
                    @endif
                @else
                    <div class="px-5 py-16 text-center">
                        <div class="flex flex-col items-center">
                            <div class="w-16 h-16 rounded-full bg-neutral-100 flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-neutral-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </div>
                            <h3 class="text-sm font-semibold text-neutral-900 mb-1">No products yet</h3>
                            <p class="text-xs text-neutral-600">Products assigned to this category will appear here.</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Category Details -->
            <div class="card overflow-hidden">
                <div class="px-5 py-4 border-b border-neutral-100 bg-neutral-50/50">
                    <h2 class="font-semibold text-neutral-900 flex items-center gap-2">
                        <svg class="w-4 h-4 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Details
                    </h2>
                </div>
                <div class="p-5">
                    @if($category->image_url)
                        <div class="mb-5 flex justify-center">
                            <div class="w-28 h-28 rounded-xl overflow-hidden ring-1 ring-neutral-200 shadow-sm">
                                <img src="{{ asset('storage/' . $category->image_url) }}" alt="{{ $category->name }}" class="w-full h-full object-cover">
                            </div>
                        </div>
                    @endif
                    <dl class="space-y-3.5 text-sm">
                        <div class="flex items-center justify-between py-1">
                            <dt class="text-neutral-600 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                Slug
                            </dt>
                            <dd class="font-mono text-xs text-neutral-700 bg-neutral-100 px-2 py-0.5 rounded">{{ $category->slug }}</dd>
                        </div>
                        <div class="flex items-center justify-between py-1">
                            <dt class="text-neutral-600 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                                Parent
                            </dt>
                            <dd class="font-medium text-neutral-700">
                                @if($category->parent)
                                    <a href="{{ route('admin.categories.show', $category->parent) }}" class="text-primary-600 hover:text-primary-700 transition-colors">{{ $category->parent->name }}</a>
                                @else
                                    <span class="text-neutral-600">Root</span>
                                @endif
                            </dd>
                        </div>
                        <div class="flex items-center justify-between py-1">
                            <dt class="text-neutral-600 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                                Sort Order
                            </dt>
                            <dd class="font-medium text-neutral-700">{{ $category->sort_order }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card overflow-hidden">
                <div class="px-5 py-4 border-b border-neutral-100 bg-neutral-50/50">
                    <h2 class="font-semibold text-neutral-900 flex items-center gap-2">
                        <svg class="w-4 h-4 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        Quick Actions
                    </h2>
                </div>
                <div class="p-3 space-y-1">
                    <a href="{{ route('admin.categories.edit', $category) }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-neutral-50 transition-colors group">
                        <div class="w-8 h-8 rounded-lg bg-primary-50 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-neutral-900 group-hover:text-primary-600 transition-colors">Edit Category</p>
                            <p class="text-xs text-neutral-600">Modify name, description, SEO</p>
                        </div>
                    </a>
                    <form action="{{ route('admin.categories.toggle-status', $category) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <button type="submit" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-neutral-50 transition-colors group text-left">
                            <div class="w-8 h-8 rounded-lg {{ $category->is_active ? 'bg-warning-50' : 'bg-success-50' }} flex items-center justify-center shrink-0">
                                @if($category->is_active)
                                    <svg class="w-4 h-4 text-warning-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                    </svg>
                                @else
                                    <svg class="w-4 h-4 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                @endif
                            </div>
                            <div>
                                <p class="text-sm font-medium text-neutral-900 group-hover:text-primary-600 transition-colors">
                                    {{ $category->is_active ? 'Deactivate' : 'Activate' }}
                                </p>
                                <p class="text-xs text-neutral-600">
                                    {{ $category->is_active ? 'Hide from storefront' : 'Make visible on storefront' }}
                                </p>
                            </div>
                        </button>
                    </form>
                    <form action="{{ route('admin.categories.destroy', $category) }}" method="POST"
                          onsubmit="return confirm('Delete &quot;{{ $category->name }}&quot;? This cannot be undone.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-error-50 transition-colors group text-left">
                            <div class="w-8 h-8 rounded-lg bg-error-50 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-error-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-neutral-900 group-hover:text-error-600 transition-colors">Delete Category</p>
                                <p class="text-xs text-neutral-600">Permanently remove this category</p>
                            </div>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Subcategories -->
            @if($category->children->count())
                <div class="card overflow-hidden">
                    <div class="px-5 py-4 border-b border-neutral-100 bg-neutral-50/50">
                        <h2 class="font-semibold text-neutral-900 flex items-center gap-2">
                            <svg class="w-4 h-4 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                            </svg>
                            Subcategories
                            <span class="badge badge-neutral">{{ $category->children->count() }}</span>
                        </h2>
                    </div>
                    <div class="divide-y divide-neutral-100">
                        @foreach($category->children as $child)
                            <a href="{{ route('admin.categories.show', $child) }}"
                               class="flex items-center justify-between px-5 py-3 hover:bg-neutral-50 transition-colors group">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-7 h-7 rounded bg-neutral-100 flex items-center justify-center shrink-0">
                                        <svg class="w-3.5 h-3.5 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6z"/>
                                        </svg>
                                    </div>
                                    <span class="text-sm font-medium text-neutral-900 group-hover:text-primary-600 transition-colors">{{ $child->name }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if($child->is_active)
                                        <span class="w-2 h-2 rounded-full bg-success-500"></span>
                                    @else
                                        <span class="w-2 h-2 rounded-full bg-neutral-300"></span>
                                    @endif
                                    <svg class="w-4 h-4 text-neutral-300 group-hover:text-primary-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Description -->
            @if($category->description)
                <div class="card overflow-hidden">
                    <div class="px-5 py-4 border-b border-neutral-100 bg-neutral-50/50">
                        <h2 class="font-semibold text-neutral-900 flex items-center gap-2">
                            <svg class="w-4 h-4 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/>
                            </svg>
                            Description
                        </h2>
                    </div>
                    <div class="p-5">
                        <p class="text-sm text-neutral-600 leading-relaxed">{{ $category->description }}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-layouts.admin>
