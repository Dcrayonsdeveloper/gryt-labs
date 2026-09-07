<x-layouts.admin>
    <x-slot name="title">Sale</x-slot>

    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Sale</h1>
            <p class="text-sm text-gray-500 mt-0.5">
                Tick the products you want on the storefront
                <a href="{{ route('sale') }}" target="_blank" class="text-blue-600 hover:underline">Sale page</a>.
                Currently <span class="font-semibold text-gray-900">{{ $onSaleCount }}</span> on sale.
            </p>
        </div>
        <a href="{{ route('admin.products.index') }}" class="text-sm text-gray-600 hover:text-gray-900">All Products</a>
    </div>

    @if(session('success'))
        <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('admin.products.sale.update') }}">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            {{-- Search --}}
            <div class="px-4 py-3" style="border-bottom:1px solid #e1e1e1">
                <div class="flex items-center gap-2">
                    <input type="search" form="sale-search" name="search" value="{{ $search }}"
                           placeholder="Search by name or SKU"
                           class="w-full max-w-sm px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400">
                    @if($search)
                        <a href="{{ route('admin.products.sale') }}" class="text-xs text-gray-500 hover:text-gray-800">Clear</a>
                    @endif
                </div>
            </div>

            <div class="overflow-x-auto" x-data="{
                toggleAll(checked) { this.$root.querySelectorAll('input[name=&quot;on_sale[]&quot;]').forEach(c => c.checked = checked); }
            }">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider" style="border-bottom:1px solid #e1e1e1">
                            <th class="pl-4 pr-0 py-3 w-10">
                                <input type="checkbox" @change="toggleAll($event.target.checked)"
                                       class="rounded border-gray-300 text-gray-900 focus:ring-gray-500">
                            </th>
                            <th class="px-4 py-3">Product</th>
                            <th class="px-4 py-3">SKU</th>
                            <th class="px-4 py-3">Price</th>
                            <th class="px-4 py-3">Discount</th>
                            <th class="px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                            <tr class="hover:bg-gray-50" style="border-bottom:1px solid #e1e1e1">
                                <td class="pl-4 pr-0 py-3 w-10">
                                    <input type="hidden" name="page_ids[]" value="{{ $product->id }}">
                                    <input type="checkbox" name="on_sale[]" value="{{ $product->id }}"
                                           @checked($product->is_on_sale)
                                           class="rounded border-gray-300 text-gray-900 focus:ring-gray-500">
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2.5">
                                        <img src="{{ $product->primary_image_url }}" alt="" class="w-9 h-9 rounded object-contain border border-gray-100 bg-white shrink-0">
                                        <span class="font-medium text-gray-900">{{ $product->name }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $product->sku }}</td>
                                <td class="px-4 py-3 text-gray-900 font-medium">
                                    @price($product->price)
                                    @if($product->mrp > $product->price)
                                        <span class="text-xs text-gray-400 line-through ml-1">@price($product->mrp)</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    @if($product->mrp > $product->price)
                                        {{ round($product->discount_percentage) }}% off
                                    @else
                                        --
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if($product->is_active)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Inactive</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-12 text-center text-sm text-gray-500">No products found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-4 py-3 flex items-center justify-between" style="border-top:1px solid #e1e1e1">
                <div class="text-sm text-gray-600">
                    @if($products->hasPages())
                        Showing {{ $products->firstItem() }}-{{ $products->lastItem() }} of {{ $products->total() }}
                        <span class="text-xs text-gray-400 ml-1">(save before changing page)</span>
                    @else
                        {{ $products->total() }} product(s)
                    @endif
                </div>
                <button type="submit" class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
                    Save sale
                </button>
            </div>
        </div>
    </form>

    <form id="sale-search" method="GET" action="{{ route('admin.products.sale') }}"></form>

    @if($products->hasPages())
        <div class="mt-4">{{ $products->links() }}</div>
    @endif
</x-layouts.admin>
