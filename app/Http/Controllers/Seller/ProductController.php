<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $query = Product::where('seller_id', auth()->user()->seller->id)
            ->with(['category', 'primaryImage']);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('sku', 'like', "%{$request->search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        $products = $query->latest()->paginate(20)->withQueryString();
        $categories = Category::whereNull('parent_id')->with('children')->get();

        return view('seller.products.index', compact('products', 'categories'));
    }

    public function create(): View
    {
        $categories = Category::whereNull('parent_id')->with('children')->get();
        $brands = Brand::where('is_active', true)->orderBy('name')->get();

        return view('seller.products.create', compact('categories', 'brands'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'sku' => ['required', 'string', 'max:50', 'unique:products'],
            'description' => ['required', 'string'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'price' => ['required', 'numeric', 'min:0'],
            'mrp' => ['nullable', 'numeric', 'min:0', 'gte:price'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'images' => ['required', 'array', 'min:1'],
            'images.*' => ['image', 'max:5120'],
        ]);

        $product = Product::create([
            'seller_id' => auth()->user()->seller->id,
            'category_id' => $validated['category_id'],
            'brand_id' => $validated['brand_id'] ?? null,
            'name' => $validated['name'],
            'sku' => $validated['sku'],
            'description' => $validated['description'],
            'short_description' => $validated['short_description'] ?? null,
            'price' => $validated['price'],
            'mrp' => $validated['mrp'] ?? $validated['price'],
            'cost_price' => $validated['cost_price'] ?? null,
            'stock_quantity' => $validated['stock_quantity'],
            'weight' => $validated['weight'] ?? null,
            'is_active' => $validated['is_active'] ?? false,
        ]);

        // Handle images
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                $path = $image->store('products', 'public');
                ProductImage::create([
                    'product_id' => $product->id,
                    'url' => '/storage/' . $path,
                    'position' => $index,
                    'is_primary' => $index === 0,
                ]);
            }
        }

        return redirect()->route('seller.products.index')
            ->with('success', 'Product created successfully.');
    }

    public function edit(Product $product): View
    {
        abort_unless($product->seller_id === auth()->user()->seller->id, 403);

        $product->load(['images', 'variants']);
        $categories = Category::whereNull('parent_id')->with('children')->get();
        $brands = Brand::where('is_active', true)->orderBy('name')->get();

        return view('seller.products.edit', compact('product', 'categories', 'brands'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->seller_id === auth()->user()->seller->id, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'sku' => ['required', 'string', 'max:50', 'unique:products,sku,' . $product->id],
            'description' => ['required', 'string'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'price' => ['required', 'numeric', 'min:0'],
            'mrp' => ['nullable', 'numeric', 'min:0', 'gte:price'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'main_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:5120'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'mimes:jpeg,jpg,png,webp,gif', 'max:5120'],
            'delete_images' => ['nullable', 'array'],
            'delete_images.*' => ['integer', 'exists:product_images,id'],
        ]);

        // Only persist the plain product columns — image keys are handled separately.
        unset($validated['main_image'], $validated['images'], $validated['delete_images']);

        $product->update($validated);

        // Delete images the seller marked for removal (scoped to this product).
        if ($request->filled('delete_images')) {
            $imagesToDelete = ProductImage::whereIn('id', $request->delete_images)
                ->where('product_id', $product->id)
                ->get();

            foreach ($imagesToDelete as $image) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $image->url));
                $image->delete();
            }
        }

        // Replace the main image when a new one is uploaded.
        if ($request->hasFile('main_image')) {
            $oldPrimary = $product->images()->where('is_primary', true)->first();
            if ($oldPrimary) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $oldPrimary->url));
                $oldPrimary->delete();
            }

            ProductImage::create([
                'product_id' => $product->id,
                'url' => $request->file('main_image')->store('products', 'public'),
                'is_primary' => true,
                'position' => 0,
            ]);
        }

        // Append any additional gallery images.
        if ($request->hasFile('images')) {
            $maxPosition = $product->images()->max('position') ?? 0;
            foreach ($request->file('images') as $index => $file) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'url' => $file->store('products', 'public'),
                    'is_primary' => false,
                    'position' => $maxPosition + $index + 1,
                ]);
            }
        }

        // Guarantee the product always has a primary image after edits.
        if (! $product->images()->where('is_primary', true)->exists()) {
            $product->images()->orderBy('position')->first()?->update(['is_primary' => true]);
        }

        \App\Http\Middleware\CacheResponse::bustAll();

        return redirect()->route('seller.products.index')
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        abort_unless($product->seller_id === auth()->user()->seller->id, 403);

        $product->delete();

        return redirect()->route('seller.products.index')
            ->with('success', 'Product deleted successfully.');
    }
}
