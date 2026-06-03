<x-layouts.admin>
    <x-slot name="title">Products</x-slot>

    {{-- Header: Products title + action buttons --}}
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold text-gray-900 leading-tight">Products</h1>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.products.export', request()->query()) }}"
                   class="inline-flex items-center bg-white hover:bg-gray-50 border border-gray-500 text-gray-800 text-[13px] font-medium px-3 py-1.5 rounded-lg no-underline">
                    Export
                </a>
                <button @click="$dispatch('open-import-modal')"
                        class="inline-flex items-center bg-white hover:bg-gray-50 border border-gray-500 text-gray-800 text-[13px] font-medium px-3 py-1.5 rounded-lg cursor-pointer">
                    Import
                </button>
                <a href="{{ route('admin.products.create') }}"
                   class="inline-flex items-center bg-gray-900 hover:bg-gray-700 border border-gray-900 text-white text-[13px] font-medium px-3 py-1.5 rounded-lg no-underline">
                    Add product
                </a>
            </div>
        </div>
    </x-slot>

    {{-- Stats Bar --}}
    <x-slot name="statsBar">
        @include('admin.partials.stats-bar', ['stats' => [
            ['label' => 'Total products', 'value' => number_format($stats['total'] ?? 0), 'sparkline' => '2,12 10,10 18,8 26,10 34,6 42,8 50,5 58,3', 'color' => '#5c6ac4'],
            ['label' => 'Active', 'value' => number_format($stats['active'] ?? 0), 'sparkline' => '2,14 10,12 18,10 26,8 34,10 42,6 50,8 58,4', 'color' => '#50b83c'],
            ['label' => 'Out of stock', 'value' => number_format($stats['out_of_stock'] ?? 0), 'sparkline' => '2,10 10,10 18,10 26,10 34,10 42,10 50,10 58,10', 'color' => '#de3618'],
            ['label' => 'Inventory value', 'value' => '₹' . number_format(($stats['total'] ?? 0) * 500), 'sparkline' => '2,16 10,14 18,12 26,10 34,8 42,6 50,4 58,2', 'color' => '#47c1bf'],
        ]])
    </x-slot>

    @php
        $currentStatus = request('status', '');
        $pageIds = $products->pluck('id')->toArray();
    @endphp

    {{-- Single card containing tabs + filters + table + pagination --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden"
         x-data="{
             selected: [],
             toggleAll(checked) {
                 this.selected = checked ? {{ json_encode($pageIds) }} : [];
             },
             toggle(id) {
                 const idx = this.selected.indexOf(id);
                 idx === -1 ? this.selected.push(id) : this.selected.splice(idx, 1);
             },
             get allChecked() {
                 return this.selected.length === {{ count($pageIds) }} && {{ count($pageIds) }} > 0;
             }
         }">

        {{-- Tab row --}}
        <div class="flex items-center justify-between border-b border-gray-200 px-3">
            <div class="flex items-center gap-0">
                @php
                    $tabs = [
                        '' => ['label' => 'All', 'count' => $stats['total']],
                        'active' => ['label' => 'Active', 'count' => $stats['active']],
                        'inactive' => ['label' => 'Draft', 'count' => $stats['inactive']],
                        'archived' => ['label' => 'Archived', 'count' => null],
                    ];
                @endphp
                @foreach($tabs as $statusKey => $tab)
                    <a href="{{ route('admin.products.index', array_merge(request()->except('status', 'page'), $statusKey ? ['status' => $statusKey] : [])) }}"
                       class="relative px-3 py-2.5 text-[13px] font-medium no-underline {{ $currentStatus === $statusKey ? 'text-gray-900' : 'text-gray-600 hover:text-gray-900' }}">
                        {{ $tab['label'] }}
                        @if($currentStatus === $statusKey)
                            <span class="absolute -bottom-px left-3 right-3 h-0.5 bg-gray-900 rounded-sm"></span>
                        @endif
                    </a>
                @endforeach
                {{-- Plus tab --}}
                <button class="px-2 py-2.5 text-gray-600 bg-transparent border-0 cursor-pointer text-base leading-none" title="Create view">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><path d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"/></svg>
                </button>
            </div>
            {{-- Right icons: search, filter, sort --}}
            <div class="flex items-center gap-1">
                {{-- Search icon --}}
                <button class="p-1.5 text-gray-600 bg-transparent hover:bg-gray-100 border-0 cursor-pointer rounded-md" title="Search">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/></svg>
                </button>
                {{-- Filter icon --}}
                <button class="p-1.5 text-gray-600 bg-transparent hover:bg-gray-100 border-0 cursor-pointer rounded-md" title="Filter">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd"/></svg>
                </button>
                {{-- Sort icon --}}
                <button class="p-1.5 text-gray-600 bg-transparent hover:bg-gray-100 border-0 cursor-pointer rounded-md" title="Sort">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><path d="M3 3a1 1 0 000 2h11a1 1 0 100-2H3zM3 7a1 1 0 000 2h7a1 1 0 100-2H3zM3 11a1 1 0 100 2h4a1 1 0 100-2H3zM15 8a1 1 0 10-2 0v5.586l-1.293-1.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L15 13.586V8z"/></svg>
                </button>
            </div>
        </div>

        {{-- Search / filter row --}}
        <div class="flex items-center gap-2 p-3 border-b border-gray-200">
            <form action="{{ route('admin.products.index') }}" method="GET" class="flex items-center gap-2 flex-1">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                <div class="flex-1 relative">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="#8a8a8a" class="absolute left-2.5 top-1/2 -translate-y-1/2"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/></svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Filter products"
                           class="w-full py-1.5 pl-8 pr-2.5 text-[13px] border border-gray-300 rounded-lg outline-none text-gray-900 bg-white focus:border-blue-600 focus:ring-1 focus:ring-blue-600">
                </div>
                <select name="category"
                        x-on:change="$el.form.submit()"
                        class="py-1.5 pr-7 pl-2.5 text-[13px] border border-gray-300 rounded-lg outline-none text-gray-900 bg-white cursor-pointer focus:border-blue-600 focus:ring-1 focus:ring-blue-600">
                    <option value="">All categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
                <select name="seller"
                        x-on:change="$el.form.submit()"
                        class="py-1.5 pr-7 pl-2.5 text-[13px] border border-gray-300 rounded-lg outline-none text-gray-900 bg-white cursor-pointer focus:border-blue-600 focus:ring-1 focus:ring-blue-600">
                    <option value="">All sellers</option>
                    @foreach($sellers as $seller)
                        <option value="{{ $seller->id }}" {{ request('seller') == $seller->id ? 'selected' : '' }}>
                            {{ $seller->store_name }}
                        </option>
                    @endforeach
                </select>
                @if(request()->hasAny(['search', 'category', 'seller', 'stock']))
                    <a href="{{ route('admin.products.index', request('status') ? ['status' => request('status')] : []) }}"
                       class="px-3 py-1.5 text-[13px] text-gray-800 no-underline whitespace-nowrap">
                        Clear filters
                    </a>
                @endif
            </form>
        </div>

        {{-- Bulk Actions Bar --}}
        <div x-show="selected.length > 0" x-cloak
             class="flex flex-wrap items-center justify-between gap-3 px-3 py-2 bg-gray-100 border-b border-gray-200">
            <span class="text-[13px] font-medium text-gray-800">
                <span x-text="selected.length"></span> selected
            </span>
            <form method="POST" action="{{ route('admin.products.bulk-action') }}"
                  x-ref="bulkForm"
                  @submit.prevent="
                      const action = $refs.bulkAction.value;
                      if (!action) { alert('Please select an action'); return; }
                      const labels = { activate: 'activate', deactivate: 'deactivate', approve: 'approve', delete: 'permanently delete' };
                      if (!confirm('Are you sure you want to ' + labels[action] + ' ' + selected.length + ' product(s)?')) return;
                      $refs.bulkIds.value = JSON.stringify(selected);
                      $el.submit();
                  ">
                @csrf
                <input type="hidden" name="ids" x-ref="bulkIds" value="">
                <div class="flex items-center gap-2">
                    <a :href="'{{ route('admin.products.bulk-edit') }}?ids=' + selected.join(',')"
                       class="inline-flex items-center px-2.5 py-1 text-xs font-medium bg-white hover:bg-gray-50 border border-gray-300 text-gray-800 rounded-md cursor-pointer no-underline">
                        Bulk edit
                    </a>
                    <select name="action" x-ref="bulkAction"
                            class="px-2 py-1 text-xs border border-gray-300 rounded-md outline-none bg-white focus:border-blue-600 focus:ring-1 focus:ring-blue-600">
                        <option value="">Bulk action</option>
                        <option value="activate">Set as active</option>
                        <option value="deactivate">Set as draft</option>
                        <option value="delete">Delete</option>
                    </select>
                    <button type="submit"
                            class="px-2.5 py-1 text-xs font-medium bg-gray-900 hover:bg-gray-700 text-white border-0 rounded-md cursor-pointer">
                        Apply
                    </button>
                </div>
            </form>
        </div>

        {{-- Product table --}}
        <div class="overflow-x-auto">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="w-9 px-2 py-2 pl-3 text-center">
                            <input type="checkbox"
                                   class="w-4 h-4 cursor-pointer accent-gray-900"
                                   x-on:change="toggleAll($event.target.checked)"
                                   :checked="allChecked">
                        </th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">Product</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">Status</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">Inventory</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">Category</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">Type</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr class="border-b border-gray-100 cursor-pointer hover:bg-gray-50"
                            x-on:click="if(!$event.target.closest('input[type=checkbox]')) window.location.href='{{ route('admin.products.edit', $product) }}'">
                            {{-- Checkbox --}}
                            <td class="w-9 px-2 py-2 pl-3 text-center" x-on:click.stop>
                                <input type="checkbox"
                                       class="w-4 h-4 cursor-pointer accent-gray-900"
                                       :checked="selected.includes({{ $product->id }})"
                                       x-on:change="toggle({{ $product->id }})">
                            </td>
                            {{-- Product (image + name) --}}
                            <td class="px-3 py-2">
                                <div class="flex items-center gap-3">
                                    @if($product->primary_image_url)
                                        <img src="{{ $product->primary_image_url }}" alt="{{ $product->name }}"
                                             class="w-10 h-10 rounded-lg object-cover border border-gray-200 flex-shrink-0">
                                    @else
                                        <div class="w-10 h-10 rounded-lg bg-gray-100 border border-gray-200 flex items-center justify-center flex-shrink-0">
                                            <svg width="18" height="18" viewBox="0 0 20 20" fill="#b5b5b5"><path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/></svg>
                                        </div>
                                    @endif
                                    <span class="block text-[13px] font-medium text-gray-900 whitespace-nowrap overflow-hidden text-ellipsis max-w-[280px]" title="{{ $product->name }}">
                                        {{ $product->name }}
                                    </span>
                                </div>
                            </td>
                            {{-- Status --}}
                            <td class="px-3 py-2">
                                @if($product->is_active)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-800 inline-block"></span>
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-gray-200 text-gray-600">
                                        <span class="w-1.5 h-1.5 rounded-full bg-gray-500 inline-block"></span>
                                        Draft
                                    </span>
                                @endif
                            </td>
                            {{-- Inventory --}}
                            <td class="px-3 py-2 text-[13px] text-gray-800 whitespace-nowrap">
                                @php
                                    $variantCount = $product->variants_count ?? ($product->relationLoaded('variants') ? $product->variants->count() : 0);
                                    $variantSuffix = $variantCount > 0 ? " for {$variantCount} variants" : '';
                                @endphp
                                @if($product->stock_quantity <= 0)
                                    <span class="inline-flex items-center rounded bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800">Out of stock</span>{{ $variantSuffix }}
                                @elseif($product->stock_quantity <= $lowStockThreshold)
                                    <span class="inline-flex items-center rounded bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">Low stock: {{ $product->stock_quantity }}</span>{{ $variantSuffix }}
                                @else
                                    {{ $product->stock_quantity }} in stock{{ $variantSuffix }}
                                @endif
                            </td>
                            {{-- Category --}}
                            <td class="px-3 py-2 text-[13px] text-gray-800">
                                {{ $product->category->name ?? '--' }}
                            </td>
                            {{-- Type --}}
                            <td class="px-3 py-2 text-[13px] text-gray-800">
                                @if($product->is_featured)
                                    Featured
                                @else
                                    Standard
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <svg width="40" height="40" viewBox="0 0 20 20" fill="#c9c9c9" class="mb-3"><path fill-rule="evenodd" d="M10 2L3 7v6l7 5 7-5V7l-7-5zM5 7.5L10 4.5l5 3-5 3-5-3zm0 1.8v4.2l5 3.5v-4.2L5 9.3zm10 4.2v-4.2l-5 3.5v4.2l5-3.5z" clip-rule="evenodd"/></svg>
                                    <p class="text-sm font-medium text-gray-900 mb-1">No products found</p>
                                    <p class="text-[13px] text-gray-600 mb-4">Try changing the filters or add a new product.</p>
                                    <a href="{{ route('admin.products.create') }}"
                                       class="inline-flex items-center bg-gray-900 hover:bg-gray-700 text-white text-[13px] font-medium px-3.5 py-1.5 rounded-lg no-underline">
                                        Add product
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($products->total() > 0)
            <div class="flex items-center justify-center gap-4 p-3 border-t border-gray-200">
                <div class="flex items-center gap-2">
                    @if($products->onFirstPage())
                        <span class="p-1.5 text-gray-300 cursor-not-allowed">
                            <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        </span>
                    @else
                        <a href="{{ $products->previousPageUrl() }}" class="inline-flex p-1.5 text-gray-600 hover:bg-gray-100 rounded-md no-underline">
                            <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        </a>
                    @endif

                    <span class="text-[13px] text-gray-800">
                        {{ $products->firstItem() }}-{{ $products->lastItem() }} of {{ $products->total() }}
                    </span>

                    @if($products->hasMorePages())
                        <a href="{{ $products->nextPageUrl() }}" class="inline-flex p-1.5 text-gray-600 hover:bg-gray-100 rounded-md no-underline">
                            <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                        </a>
                    @else
                        <span class="p-1.5 text-gray-300 cursor-not-allowed">
                            <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                        </span>
                    @endif
                </div>
            </div>
        @endif
    </div>

    {{-- Import Modal --}}
    <div x-data="{ open: false }"
         @open-import-modal.window="open = true"
         x-show="open" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-black/50" @click="open = false"></div>

            <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                 class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">

                <div class="px-5 py-4 border-b border-gray-200 flex items-center">
                    <div class="flex items-center justify-between flex-1">
                        <h2 class="text-base font-semibold text-gray-900">Import products</h2>
                        <button @click="open = false" class="p-1 bg-transparent hover:bg-gray-100 border-0 cursor-pointer text-gray-600 rounded-md">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                        </button>
                    </div>
                </div>

                <form action="{{ route('admin.products.import') }}" method="POST" enctype="multipart/form-data" class="p-5">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-[13px] font-medium text-gray-900 mb-1">CSV File <span class="text-red-700">*</span></label>
                        <input type="file" name="csv_file" accept=".csv,.txt" required
                               class="w-full py-1.5 px-2.5 text-[13px] border border-gray-300 rounded-lg bg-white focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none">
                        <p class="text-xs text-gray-600 mt-1">Max 10MB. Must contain a header row with column names.</p>
                    </div>

                    <div class="p-3 bg-gray-50 rounded-lg mb-4">
                        <p class="text-xs font-semibold text-gray-900 mb-1.5">Required columns</p>
                        <div class="flex flex-wrap gap-1 mb-2.5">
                            <span class="px-1.5 py-0.5 bg-blue-50 text-blue-700 rounded text-[11px] font-medium">name</span>
                            <span class="px-1.5 py-0.5 bg-blue-50 text-blue-700 rounded text-[11px] font-medium">sku</span>
                            <span class="px-1.5 py-0.5 bg-blue-50 text-blue-700 rounded text-[11px] font-medium">price</span>
                        </div>
                        <p class="text-xs font-semibold text-gray-900 mb-1.5">Optional columns</p>
                        <div class="flex flex-wrap gap-1">
                            @foreach(['slug','category','seller','sale_price','cost_price','stock_quantity','description','short_description','is_active','is_featured','image_url','meta_title','meta_description'] as $col)
                                <span class="px-1.5 py-0.5 bg-gray-200 text-gray-600 rounded text-[11px]">{{ $col }}</span>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2">
                        <button type="button" @click="open = false"
                                class="px-3.5 py-1.5 text-[13px] font-medium bg-white hover:bg-gray-50 border border-gray-500 text-gray-800 rounded-lg cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-3.5 py-1.5 text-[13px] font-medium bg-gray-900 hover:bg-gray-700 border border-gray-900 text-white rounded-lg cursor-pointer">
                            Import products
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.admin>
