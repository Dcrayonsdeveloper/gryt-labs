<x-layouts.admin>
    <x-slot name="title">Attributes</x-slot>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-neutral-900">Attributes</h1>
                <p class="text-sm text-neutral-600 mt-1">Manage product attributes and specifications</p>
            </div>
            <a href="{{ route('admin.attributes.create') }}" class="btn btn-primary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Add Attribute
            </a>
        </div>
    </x-slot>

    <!-- Stats -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
        <div class="card p-4 sm:p-5 flex items-center gap-3 sm:gap-4">
            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-primary-50 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 sm:w-6 sm:h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs sm:text-sm text-neutral-600">Total</p>
                <p class="text-xl sm:text-2xl font-bold text-neutral-900">{{ $stats['total'] }}</p>
            </div>
        </div>
        <div class="card p-4 sm:p-5 flex items-center gap-3 sm:gap-4">
            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-info-50 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 sm:w-6 sm:h-6 text-info-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"/>
                </svg>
            </div>
            <div>
                <p class="text-xs sm:text-sm text-neutral-600">Select</p>
                <p class="text-xl sm:text-2xl font-bold text-info-600">{{ $stats['select'] }}</p>
            </div>
        </div>
        <div class="card p-4 sm:p-5 flex items-center gap-3 sm:gap-4">
            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-warning-50 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 sm:w-6 sm:h-6 text-warning-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                </svg>
            </div>
            <div>
                <p class="text-xs sm:text-sm text-neutral-600">Color</p>
                <p class="text-xl sm:text-2xl font-bold text-warning-600">{{ $stats['color'] }}</p>
            </div>
        </div>
        <div class="card p-4 sm:p-5 flex items-center gap-3 sm:gap-4">
            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-success-50 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 sm:w-6 sm:h-6 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/>
                </svg>
            </div>
            <div>
                <p class="text-xs sm:text-sm text-neutral-600">Text</p>
                <p class="text-xl sm:text-2xl font-bold text-success-600">{{ $stats['text'] }}</p>
            </div>
        </div>
        <div class="card p-4 sm:p-5 flex items-center gap-3 sm:gap-4">
            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-purple-50 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 sm:w-6 sm:h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs sm:text-sm text-neutral-600">Filterable</p>
                <p class="text-xl sm:text-2xl font-bold text-purple-600">{{ $stats['filterable'] }}</p>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-6" x-data="{ open: {{ request()->hasAny(['search', 'type', 'filterable']) ? 'true' : 'false' }} }">
        <button @click="open = !open" class="w-full px-5 py-3 flex items-center justify-between hover:bg-neutral-50 transition-colors">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
                <span class="text-sm font-medium text-neutral-700">Filters</span>
                @if(request()->hasAny(['search', 'type', 'filterable']))
                    <span class="badge badge-primary text-xs">Active</span>
                @endif
            </div>
            <svg class="w-5 h-5 text-neutral-600 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
        <div x-show="open" x-collapse>
            <form action="{{ route('admin.attributes.index') }}" method="GET" class="px-5 pb-4 pt-2">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-neutral-700 mb-1">Search</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search attributes or values..." class="form-input w-full text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-neutral-700 mb-1">Type</label>
                        <select name="type" class="form-select w-full text-sm">
                            <option value="">All Types</option>
                            <option value="select" @selected(request('type') === 'select')>Select (Dropdown)</option>
                            <option value="color" @selected(request('type') === 'color')>Color (Swatch)</option>
                            <option value="text" @selected(request('type') === 'text')>Text (Free Input)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-neutral-700 mb-1">Filterable</label>
                        <select name="filterable" class="form-select w-full text-sm">
                            <option value="">All</option>
                            <option value="yes" @selected(request('filterable') === 'yes')>Yes</option>
                            <option value="no" @selected(request('filterable') === 'no')>No</option>
                        </select>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-3 mt-4">
                    <a href="{{ route('admin.attributes.index') }}" class="btn btn-secondary text-sm">Clear</a>
                    <button type="submit" class="btn btn-primary text-sm">Apply Filters</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Pagination Info -->
    @if($attributes->total() > 0)
        <div class="card mb-5">
            <div class="px-5 py-3">
                {{ $attributes->links('vendor.pagination.info-bar') }}
            </div>
        </div>
    @endif

    <!-- Attributes Grid -->
    @if($attributes->count())
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            @foreach($attributes as $attribute)
                <div class="card overflow-hidden hover:shadow-md transition-shadow">
                    <!-- Card Header -->
                    <div class="px-5 py-4 border-b border-neutral-100 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0
                                @if($attribute->type === 'select') bg-info-50
                                @elseif($attribute->type === 'color') bg-warning-50
                                @else bg-success-50
                                @endif">
                                @if($attribute->type === 'select')
                                    <svg class="w-5 h-5 text-info-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"/>
                                    </svg>
                                @elseif($attribute->type === 'color')
                                    <svg class="w-5 h-5 text-warning-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                                    </svg>
                                @else
                                    <svg class="w-5 h-5 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/>
                                    </svg>
                                @endif
                            </div>
                            <div>
                                <h3 class="font-semibold text-neutral-900">{{ $attribute->name }}</h3>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span class="badge text-xs
                                        @if($attribute->type === 'select') badge-info
                                        @elseif($attribute->type === 'color') badge-warning
                                        @else badge-success
                                        @endif">{{ ucfirst($attribute->type) }}</span>
                                    <span class="text-xs text-neutral-600">{{ $attribute->values_count }} {{ Str::plural('value', $attribute->values_count) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-1">
                            <a href="{{ route('admin.attributes.edit', $attribute) }}" class="btn-icon" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </a>
                            <form action="{{ route('admin.attributes.destroy', $attribute) }}" method="POST" onsubmit="return confirm('Delete this attribute and all its values?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-icon text-danger-600 hover:text-danger-700 hover:bg-danger-50" title="Delete">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Values Preview -->
                    <div class="px-5 py-4">
                        @if($attribute->values->count())
                            @if($attribute->type === 'color')
                                <div class="flex flex-wrap gap-2">
                                    @foreach($attribute->values as $value)
                                        <div class="flex items-center gap-1.5 px-2 py-1 rounded-md bg-neutral-50 border border-neutral-100">
                                            <div class="w-4 h-4 rounded-full border border-neutral-200 shrink-0" style="background-color: {{ $value->color_code ?? '#ccc' }}"></div>
                                            <span class="text-xs text-neutral-600">{{ $value->value }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach($attribute->values as $value)
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md bg-neutral-50 border border-neutral-100 text-xs font-medium text-neutral-600">{{ $value->value }}</span>
                                    @endforeach
                                </div>
                            @endif
                        @else
                            <div class="text-center py-4">
                                <p class="text-sm text-neutral-600 italic">No values added yet</p>
                                <a href="{{ route('admin.attributes.values.create', $attribute) }}" class="text-primary-600 hover:text-primary-700 text-xs font-medium mt-1 inline-block">Add values</a>
                            </div>
                        @endif
                    </div>

                    <!-- Card Footer -->
                    <div class="px-5 py-3 bg-neutral-50/50 border-t border-neutral-100 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-1.5">
                                @if($attribute->is_filterable)
                                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-success-50">
                                        <svg class="w-3 h-3 text-success-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    </span>
                                @else
                                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-neutral-200">
                                        <svg class="w-3 h-3 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </span>
                                @endif
                                <span class="text-xs text-neutral-600">Filterable</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                @if($attribute->is_visible)
                                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-success-50">
                                        <svg class="w-3 h-3 text-success-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    </span>
                                @else
                                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-neutral-200">
                                        <svg class="w-3 h-3 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </span>
                                @endif
                                <span class="text-xs text-neutral-600">Visible</span>
                            </div>
                        </div>
                        <a href="{{ route('admin.attributes.edit', $attribute) }}" class="text-xs font-medium text-primary-600 hover:text-primary-700">
                            Manage Values
                            <svg class="w-3 h-3 inline ml-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="card">
            <div class="px-5 py-16 text-center">
                <div class="flex flex-col items-center">
                    <div class="w-16 h-16 rounded-full bg-neutral-100 flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                    </div>
                    <p class="text-neutral-600 mb-1">No attributes found</p>
                    <a href="{{ route('admin.attributes.create') }}" class="text-primary-600 hover:text-primary-700 font-medium text-sm">Add your first attribute</a>
                </div>
            </div>
        </div>
    @endif

    <!-- Pagination -->
    @if($attributes->hasPages())
        <div class="mt-5">
            {{ $attributes->links() }}
        </div>
    @endif
</x-layouts.admin>
