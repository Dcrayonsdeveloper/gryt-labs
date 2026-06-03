<?php

namespace App\Http\Controllers\Api\V1\Category;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Transform category into Shiprocket-compatible collection format.
     */
    private function transformCollection(Category $category): array
    {
        return [
            'id' => $category->id,
            'title' => $category->name,
            'handle' => $category->slug,
            'body_html' => $category->description ?? '',
            'created_at' => $category->created_at?->toIso8601String(),
            'updated_at' => $category->updated_at?->toIso8601String(),
            'image' => $category->image_url ? ['src' => url($category->image_url)] : null,
        ];
    }

    /**
     * Transform product into Shiprocket-compatible format.
     */
    private function transformProduct(Product $product): array
    {
        $images = $product->relationLoaded('images') ? $product->images : collect();
        $variants = $product->relationLoaded('variants') ? $product->variants : collect();
        $primaryImage = $images->firstWhere('is_primary', true) ?? $images->first();

        $variantData = [];
        if ($variants->isNotEmpty()) {
            foreach ($variants as $variant) {
                $variantData[] = [
                    'id' => $variant->id,
                    'title' => $variant->name ?? 'Default',
                    'price' => number_format((float) $variant->price, 2, '.', ''),
                    'compare_at_price' => $variant->mrp ? number_format((float) $variant->mrp, 2, '.', '') : null,
                    'sku' => $variant->sku ?? $product->sku,
                    'quantity' => $variant->stock_quantity ?? 0,
                    'created_at' => $variant->created_at?->toIso8601String(),
                    'updated_at' => $variant->updated_at?->toIso8601String(),
                    'taxable' => $product->is_taxable ?? true,
                    'option_values' => $variant->attributes ?? (object) [],
                    'grams' => $this->toGrams($product),
                    'image' => $primaryImage ? ['src' => url($primaryImage->url)] : null,
                    'weight' => (float) ($product->weight ?? 0),
                    'weight_unit' => $product->weight_unit ?? 'g',
                ];
            }
        } else {
            $variantData[] = [
                'id' => $product->id,
                'title' => 'Default',
                'price' => number_format((float) $product->price, 2, '.', ''),
                'compare_at_price' => $product->mrp ? number_format((float) $product->mrp, 2, '.', '') : null,
                'sku' => $product->sku,
                'quantity' => $product->stock_quantity ?? 0,
                'created_at' => $product->created_at?->toIso8601String(),
                'updated_at' => $product->updated_at?->toIso8601String(),
                'taxable' => $product->is_taxable ?? true,
                'option_values' => (object) [],
                'grams' => $this->toGrams($product),
                'image' => $primaryImage ? ['src' => url($primaryImage->url)] : null,
                'weight' => (float) ($product->weight ?? 0),
                'weight_unit' => $product->weight_unit ?? 'g',
            ];
        }

        $options = [];
        if ($variants->isNotEmpty()) {
            $allKeys = $variants->pluck('attributes')->filter()->flatMap(fn($a) => array_keys((array) $a))->unique();
            foreach ($allKeys as $key) {
                $values = $variants->pluck('attributes')->filter()->map(fn($a) => $a[$key] ?? null)->filter()->unique()->values()->toArray();
                $options[] = ['name' => $key, 'values' => $values];
            }
        }

        $tags = '';
        if (!empty($product->tags)) {
            $tags = is_array($product->tags) ? implode(', ', $product->tags) : (string) $product->tags;
        }

        return [
            'id' => $product->id,
            'title' => $product->name,
            'body_html' => $product->description,
            'vendor' => $product->brand?->name ?? config('app.name'),
            'product_type' => $product->category?->name ?? '',
            'created_at' => $product->created_at?->toIso8601String(),
            'handle' => $product->slug,
            'updated_at' => $product->updated_at?->toIso8601String(),
            'tags' => $tags,
            'status' => $product->is_active ? 'active' : 'draft',
            'variants' => $variantData,
            'image' => $primaryImage ? ['src' => url($primaryImage->url)] : null,
            'images' => $images->map(fn($img) => [
                'id' => $img->id,
                'src' => url($img->url),
                'position' => $img->position,
            ])->values()->toArray(),
            'options' => $options,
        ];
    }

    private function toGrams(Product $product): int
    {
        $weight = (float) ($product->weight ?? 0);
        return (int) match ($product->weight_unit ?? 'g') {
            'kg' => $weight * 1000,
            'lb' => $weight * 453.592,
            'oz' => $weight * 28.3495,
            default => $weight,
        };
    }

    /**
     * GET /api/v1/categories — Fetch Collections (Shiprocket catalog API)
     * Params: page, limit
     */
    public function index(Request $request): JsonResponse
    {
        $limit = (int) ($request->query('limit', 100));
        $page = (int) ($request->query('page', 1));

        $paginated = Category::where('is_active', true)
            ->orderBy('position')
            ->paginate($limit, ['*'], 'page', $page);

        return response()->json([
            'data' => [
                'total' => $paginated->total(),
                'collections' => collect($paginated->items())->map(fn($c) => $this->transformCollection($c))->toArray(),
            ],
        ]);
    }

    /**
     * GET /api/v1/categories/tree — Full category tree
     */
    public function tree(): JsonResponse
    {
        $categories = Category::where('is_active', true)
            ->whereNull('parent_id')
            ->with(['children' => fn($q) => $q->where('is_active', true)->with('children')])
            ->orderBy('position')
            ->get();

        return response()->json([
            'data' => $categories,
        ]);
    }

    /**
     * GET /api/v1/categories/{slug} — Single collection
     */
    public function show(Category $category): JsonResponse
    {
        if (!$category->is_active) {
            abort(404);
        }

        return response()->json([
            'data' => [
                'collection' => $this->transformCollection($category),
            ],
        ]);
    }

    /**
     * GET /api/v1/categories/products?collection_id=X — Fetch Products by Collection (Shiprocket catalog API)
     * Also supports: GET /api/v1/categories/{slug}/products
     * Params: collection_id, page, limit
     */
    public function products(Request $request, ?Category $category = null): JsonResponse
    {
        // Support both ?collection_id=X and /categories/{slug}/products
        if (!$category && $request->has('collection_id')) {
            $category = Category::find($request->query('collection_id'));
        }

        if (!$category || !$category->is_active) {
            abort(404, 'Collection not found');
        }

        $categoryIds = collect([$category->id]);
        $children = Category::where('parent_id', $category->id)->where('is_active', true)->pluck('id');
        if ($children->isNotEmpty()) {
            $categoryIds = $categoryIds->merge($children);
        }

        $limit = (int) ($request->query('limit', 100));
        $page = (int) ($request->query('page', 1));

        $paginated = Product::whereIn('category_id', $categoryIds)
            ->where('is_active', true)
            ->where('status', 'approved')
            ->with(['category:id,name,slug', 'brand:id,name,slug', 'images', 'variants'])
            ->paginate($limit, ['*'], 'page', $page);

        return response()->json([
            'data' => [
                'total' => $paginated->total(),
                'products' => collect($paginated->items())->map(fn($p) => $this->transformProduct($p))->toArray(),
            ],
        ]);
    }
}
