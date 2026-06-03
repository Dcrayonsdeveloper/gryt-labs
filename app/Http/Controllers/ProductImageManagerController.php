<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductImageManagerController extends Controller
{
    public function index(Request $request): View
    {
        $query = Product::withCount('images')->where('is_active', false);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Filter by image count
        if ($request->filled('images')) {
            if ($request->images === '1') {
                $query->has('images', '=', 1);
            } elseif ($request->images === '0') {
                $query->has('images', '=', 0);
            } elseif ($request->images === 'multiple') {
                $query->has('images', '>', 1);
            }
        }

        // Sort by ID to keep stable order
        $products = $query->with('images')
            ->orderBy('images_count', 'asc')
            ->orderBy('id', 'asc')
            ->paginate(20)
            ->withQueryString();

        $inactive = Product::where('is_active', false);
        $stats = [
            'total' => Product::count(),
            'inactive' => (clone $inactive)->count(),
            'no_images' => (clone $inactive)->has('images', '=', 0)->count(),
            'single_image' => (clone $inactive)->has('images', '=', 1)->count(),
        ];

        return view('tools.image-manager', compact('products', 'stats'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'amazon_title' => 'nullable|string|max:500',
            'amazon_price' => 'nullable|numeric|min:0',
            'amazon_images' => 'nullable|array|max:7',
            'amazon_images.*' => 'nullable|url|max:2000',
        ]);

        $updates = [];
        if ($request->filled('amazon_title')) {
            $updates['name'] = $validated['amazon_title'];
            $updates['slug'] = Str::slug($validated['amazon_title']);
        }
        if ($request->filled('amazon_price')) {
            $updates['cost_price'] = $validated['amazon_price'];
        }

        // Save Amazon image URLs as JSON in specifications field (don't create image records)
        if ($request->filled('amazon_images')) {
            $urls = array_values(array_filter($validated['amazon_images']));
            if (!empty($urls)) {
                $specs = $product->specifications ?? [];
                $specs['amazon_image_urls'] = $urls;
                $updates['specifications'] = $specs;
            }
        }

        if (!empty($updates)) {
            $product->update($updates);
        }

        if ($request->ajax()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', "Updated \"{$product->name}\" successfully.");
    }

    public function toggleStatus(Product $product)
    {
        $product->update(['is_active' => !$product->is_active]);

        $status = $product->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Product {$status} successfully.");
    }

    public function deleteImage(ProductImage $image)
    {
        $productName = $image->product->name;
        $image->delete();

        return back()->with('success', "Image removed from \"{$productName}\".");
    }

    public function destroyProduct(Product $product)
    {
        $name = $product->name;

        // Delete all related records
        $product->images()->delete();
        $product->variants()->delete();
        $product->attributeValues()->delete();
        $product->reviews()->delete();
        $product->questions()->delete();
        $product->wishlists()->delete();
        $product->views()->delete();
        $product->inventoryStocks()->delete();
        $product->tags()->detach();
        $product->relatedProducts()->detach();

        // Force delete (complete removal from DB)
        $product->forceDelete();

        return back()->with('success', "Product \"{$name}\" has been permanently deleted.");
    }
}
