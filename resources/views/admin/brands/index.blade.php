<x-layouts.admin>
    <x-slot name="title">Brands</x-slot>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-neutral-900">Brands</h1>
                <p class="text-sm text-neutral-600 mt-1">Manage product brands</p>
            </div>
            <a href="{{ route('admin.brands.create') }}" class="btn btn-primary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Brand
            </a>
        </div>
    </x-slot>

    <!-- Stats -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <div class="card p-4 sm:p-5 flex items-center gap-3 sm:gap-4">
            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-primary-50 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 sm:w-6 sm:h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </div>
            <div>
                <p class="text-xs sm:text-sm text-neutral-600">Total</p>
                <p class="text-xl sm:text-2xl font-bold text-neutral-900">{{ $brands->total() }}</p>
            </div>
        </div>
        <div class="card p-4 sm:p-5 flex items-center gap-3 sm:gap-4">
            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-success-50 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 sm:w-6 sm:h-6 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs sm:text-sm text-neutral-600">Active</p>
                <p class="text-xl sm:text-2xl font-bold text-success-600">{{ $brands->where('is_active', true)->count() }}</p>
            </div>
        </div>
        <div class="card p-4 sm:p-5 flex items-center gap-3 sm:gap-4">
            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-warning-50 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 sm:w-6 sm:h-6 text-warning-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs sm:text-sm text-neutral-600">Featured</p>
                <p class="text-xl sm:text-2xl font-bold text-warning-600">{{ $brands->where('is_featured', true)->count() }}</p>
            </div>
        </div>
        <div class="card p-4 sm:p-5 flex items-center gap-3 sm:gap-4">
            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-info-50 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 sm:w-6 sm:h-6 text-info-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs sm:text-sm text-neutral-600">With Logo</p>
                <p class="text-xl sm:text-2xl font-bold text-info-600">{{ $brands->whereNotNull('logo_url')->count() }}</p>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-6" x-data="{ open: {{ request()->hasAny(['search', 'status', 'featured']) ? 'true' : 'false' }} }">
        <div class="px-5 py-3 flex items-center justify-between cursor-pointer" @click="open = !open">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
                <span class="text-sm font-medium text-neutral-700">Filters & Search</span>
                @if(request()->hasAny(['search', 'status', 'featured']))
                    <span class="badge badge-primary">Active</span>
                @endif
            </div>
            <svg class="w-5 h-5 text-neutral-600 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </div>
        <div x-show="open" x-collapse class="px-5 pb-4 border-t border-neutral-200">
            <form action="{{ route('admin.brands.index') }}" method="GET" class="pt-4">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="form-label">Search</label>
                        <input type="text" name="search" value="{{ request('search') }}"
                               class="form-input w-full" placeholder="Search by name...">
                    </div>
                    <div>
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select w-full">
                            <option value="">All Statuses</option>
                            <option value="active" @selected(request('status') === 'active')>Active</option>
                            <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Featured</label>
                        <select name="featured" class="form-select w-full">
                            <option value="">All</option>
                            <option value="1" @selected(request('featured') === '1')>Featured</option>
                            <option value="0" @selected(request('featured') === '0')>Not Featured</option>
                        </select>
                    </div>
                </div>
                <div class="flex items-center gap-2 mt-3">
                    <button type="submit" class="btn btn-primary btn-sm">Apply Filters</button>
                    @if(request()->hasAny(['search', 'status', 'featured']))
                        <a href="{{ route('admin.brands.index') }}" class="btn btn-secondary btn-sm">Clear</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Brands Table -->
    <div class="card overflow-hidden">
        @if($brands->total() > 0)
            <div class="px-5 py-3 border-b border-neutral-200">
                {{ $brands->links('vendor.pagination.info-bar') }}
            </div>
        @endif
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-neutral-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-medium text-neutral-600 uppercase tracking-wider">Brand</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-neutral-600 uppercase tracking-wider">Slug</th>
                        <th class="px-5 py-3 text-center text-xs font-medium text-neutral-600 uppercase tracking-wider">Products</th>
                        <th class="px-5 py-3 text-center text-xs font-medium text-neutral-600 uppercase tracking-wider">Status</th>
                        <th class="px-5 py-3 text-center text-xs font-medium text-neutral-600 uppercase tracking-wider">Featured</th>
                        <th class="px-5 py-3 text-right text-xs font-medium text-neutral-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @forelse($brands as $brand)
                        <tr class="hover:bg-neutral-50/60 transition-colors">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    @if($brand->logo_url)
                                        <img src="{{ Storage::url($brand->logo_url) }}" alt="{{ $brand->name }}" class="w-10 h-10 rounded-lg object-contain bg-neutral-50 ring-1 ring-neutral-200 p-1">
                                    @else
                                        <div class="w-10 h-10 bg-linear-to-br from-primary-50 to-primary-100 rounded-lg flex items-center justify-center ring-1 ring-neutral-200">
                                            <span class="text-sm font-bold text-primary-400">{{ strtoupper(substr($brand->name, 0, 2)) }}</span>
                                        </div>
                                    @endif
                                    <div>
                                        <p class="font-medium text-neutral-900">{{ $brand->name }}</p>
                                        @if($brand->description)
                                            <p class="text-xs text-neutral-600 truncate max-w-50" title="{{ $brand->description }}">{{ $brand->description }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="text-sm text-neutral-600 font-mono">{{ $brand->slug }}</span>
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <span class="inline-flex items-center justify-center min-w-7 px-2 py-0.5 rounded-full text-xs font-medium {{ $brand->products_count > 0 ? 'bg-primary-50 text-primary-700' : 'bg-neutral-100 text-neutral-600' }}">
                                    {{ $brand->products_count }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                @if($brand->is_active)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-neutral">Inactive</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                @if($brand->is_featured)
                                    <span class="badge badge-info">Featured</span>
                                @else
                                    <span class="text-xs text-neutral-300">&mdash;</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('admin.brands.edit', $brand) }}" class="btn-icon" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    <form action="{{ route('admin.brands.destroy', $brand) }}" method="POST"
                                          onsubmit="return confirm('Delete &quot;{{ $brand->name }}&quot;? This cannot be undone.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-icon text-error-400 hover:text-error-600 hover:bg-error-50" title="Delete">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-16 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 rounded-full bg-neutral-100 flex items-center justify-center mb-4">
                                        <svg class="w-8 h-8 text-neutral-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                        </svg>
                                    </div>
                                    <h3 class="text-lg font-semibold text-neutral-900 mb-1">No brands found</h3>
                                    <p class="text-sm text-neutral-600 mb-5">Get started by creating your first brand.</p>
                                    <a href="{{ route('admin.brands.create') }}" class="btn btn-primary">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                        </svg>
                                        Add Brand
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($brands->hasPages())
            <div class="px-5 py-3 border-t border-neutral-200">
                {{ $brands->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
