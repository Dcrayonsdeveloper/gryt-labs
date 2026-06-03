<x-layouts.admin>
    <x-slot name="title">Bulk Edit Products</x-slot>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.products.index') }}"
                   class="inline-flex p-1.5 text-gray-600 hover:bg-gray-100 rounded-md"
                   title="Back to products">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                </a>
                <h1 class="text-xl font-semibold text-gray-900 leading-tight">Bulk Edit ({{ $products->count() }} products)</h1>
            </div>
            <button type="submit" form="bulk-edit-form"
                    class="bg-gray-900 hover:bg-gray-700 border border-gray-900 text-white text-[13px] font-medium px-3.5 py-1.5 rounded-lg cursor-pointer">
                Save all
            </button>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="px-3.5 py-2.5 bg-green-100 text-green-800 rounded-lg text-[13px] mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="px-3.5 py-2.5 bg-red-100 text-red-800 rounded-lg text-[13px] mb-4">
            <ul class="m-0 pl-4 list-disc">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <form id="bulk-edit-form" method="POST" action="{{ route('admin.products.bulk-update') }}">
            @csrf
            @method('PUT')

            <div class="overflow-x-auto">
                <table class="w-full border-collapse min-w-[900px]">
                    <thead class="bg-gray-50">
                        <tr class="border-b border-gray-200">
                            <th class="px-3 py-2.5 text-left text-xs font-medium text-gray-600 whitespace-nowrap">Image</th>
                            <th class="px-3 py-2.5 text-left text-xs font-medium text-gray-600 whitespace-nowrap min-w-[250px]">Name</th>
                            <th class="px-3 py-2.5 text-left text-xs font-medium text-gray-600 whitespace-nowrap min-w-[100px]">Price</th>
                            <th class="px-3 py-2.5 text-left text-xs font-medium text-gray-600 whitespace-nowrap min-w-[100px]">MRP</th>
                            <th class="px-3 py-2.5 text-left text-xs font-medium text-gray-600 whitespace-nowrap min-w-[90px]">Stock</th>
                            <th class="px-3 py-2.5 text-left text-xs font-medium text-gray-600 whitespace-nowrap min-w-[160px]">Category</th>
                            <th class="px-3 py-2.5 text-center text-xs font-medium text-gray-600 whitespace-nowrap min-w-[70px]">Active</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($products as $product)
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                {{-- Image (read-only) --}}
                                <td class="px-3 py-2">
                                    @if($product->primary_image_url)
                                        <img src="{{ $product->primary_image_url }}" alt="{{ $product->name }}"
                                             class="w-10 h-10 rounded-lg object-cover border border-gray-200">
                                    @else
                                        <div class="w-10 h-10 rounded-lg bg-gray-100 border border-gray-200 flex items-center justify-center">
                                            <svg width="18" height="18" viewBox="0 0 20 20" fill="#b5b5b5"><path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/></svg>
                                        </div>
                                    @endif
                                </td>
                                {{-- Name --}}
                                <td class="px-3 py-2">
                                    <input type="text" name="products[{{ $product->id }}][name]"
                                           value="{{ old("products.{$product->id}.name", $product->name) }}"
                                           class="w-full px-2 py-1.5 text-[13px] border border-gray-300 rounded-md outline-none text-gray-900 bg-white focus:border-blue-600 focus:ring-1 focus:ring-blue-600"
                                           required>
                                </td>
                                {{-- Price --}}
                                <td class="px-3 py-2">
                                    <input type="number" name="products[{{ $product->id }}][price]"
                                           value="{{ old("products.{$product->id}.price", $product->price) }}"
                                           step="0.01" min="0"
                                           class="w-full px-2 py-1.5 text-[13px] border border-gray-300 rounded-md outline-none text-gray-900 bg-white focus:border-blue-600 focus:ring-1 focus:ring-blue-600"
                                           required>
                                </td>
                                {{-- MRP --}}
                                <td class="px-3 py-2">
                                    <input type="number" name="products[{{ $product->id }}][mrp]"
                                           value="{{ old("products.{$product->id}.mrp", $product->mrp) }}"
                                           step="0.01" min="0"
                                           class="w-full px-2 py-1.5 text-[13px] border border-gray-300 rounded-md outline-none text-gray-900 bg-white focus:border-blue-600 focus:ring-1 focus:ring-blue-600">
                                </td>
                                {{-- Stock --}}
                                <td class="px-3 py-2">
                                    <input type="number" name="products[{{ $product->id }}][stock_quantity]"
                                           value="{{ old("products.{$product->id}.stock_quantity", $product->stock_quantity) }}"
                                           min="0" step="1"
                                           class="w-full px-2 py-1.5 text-[13px] border border-gray-300 rounded-md outline-none text-gray-900 bg-white focus:border-blue-600 focus:ring-1 focus:ring-blue-600"
                                           required>
                                </td>
                                {{-- Category --}}
                                <td class="px-3 py-2">
                                    <select name="products[{{ $product->id }}][category_id]"
                                            class="w-full px-2 py-1.5 text-[13px] border border-gray-300 rounded-md outline-none text-gray-900 bg-white cursor-pointer focus:border-blue-600 focus:ring-1 focus:ring-blue-600"
                                            required>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}"
                                                {{ old("products.{$product->id}.category_id", $product->category_id) == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                {{-- Active --}}
                                <td class="px-3 py-2 text-center">
                                    <input type="checkbox" name="products[{{ $product->id }}][is_active]" value="1"
                                           {{ old("products.{$product->id}.is_active", $product->is_active) ? 'checked' : '' }}
                                           class="w-4 h-4 cursor-pointer accent-gray-900">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Bottom save bar --}}
            <div class="flex items-center justify-between px-4 py-3 border-t border-gray-200 bg-gray-50">
                <a href="{{ route('admin.products.index') }}"
                   class="inline-flex items-center px-3.5 py-1.5 text-[13px] font-medium bg-white hover:bg-gray-50 border border-gray-500 text-gray-800 rounded-lg no-underline">
                    Cancel
                </a>
                <button type="submit"
                        class="bg-gray-900 hover:bg-gray-700 border border-gray-900 text-white text-[13px] font-medium px-3.5 py-1.5 rounded-lg cursor-pointer">
                    Save all changes
                </button>
            </div>
        </form>
    </div>
</x-layouts.admin>
