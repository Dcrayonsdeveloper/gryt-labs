<?php

namespace App\Http\Controllers\Api\V1\Product;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Transform a product into Shopify-compatible format for Shiprocket.
     */
    private function transformProduct(Product $product): array
    {
        $variants = $product->relationLoaded('variants') ? $product->variants : collect();
        $images = $product->relationLoaded('images') ? $product->images : collect();
        $primaryImage = $images->firstWhere('is_primary', true) ?? $images->first();

        // Build variants array (always include at least the default variant)
        $variantData = [];
        if ($variants->isNotEmpty()) {
            foreach ($variants as $variant) {
                $variantImage = $variant->relationLoaded('images') ? $variant->images->first() : null;
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
                    'image' => $variantImage
                        ? ['src' => url($variantImage->url)]
                        : ($primaryImage ? ['src' => url($primaryImage->url)] : null),
                    'weight' => (float) ($product->weight ?? 0),
                    'weight_unit' => $product->weight_unit ?? 'g',
                ];
            }
        } else {
            // Default variant from main product
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

        // Build options from variant attributes
        $options = [];
        if ($variants->isNotEmpty()) {
            $allKeys = $variants->pluck('attributes')->filter()->flatMap(fn($a) => array_keys((array) $a))->unique();
            foreach ($allKeys as $key) {
                $values = $variants->pluck('attributes')->filter()->map(fn($a) => $a[$key] ?? null)->filter()->unique()->values()->toArray();
                $options[] = ['name' => $key, 'values' => $values];
            }
        }

        // Tags
        $tags = '';
        if (!empty($product->tags)) {
            $tags = is_array($product->tags) ? implode(', ', $product->tags) : (string) $product->tags;
        }

        return [
            'id' => $product->id,
            'title' => $product->name,
            'body_html' => $product->description,
            'vendor' => $product->brand?->name ?? $product->seller?->business_name ?? config('app.name'),
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

    /**
     * Convert product weight to grams.
     */
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

    public function index(Request $request): JsonResponse
    {
        $query = Product::query()
            ->where('is_active', true)
            ->where('status', 'approved')
            ->with(['category:id,name,slug', 'brand:id,name,slug', 'images', 'variants']);

        // Filter by collection_id (category ID) — includes child categories
        if ($request->has('collection_id')) {
            $category = \App\Models\Category::find($request->query('collection_id'));
            if ($category && $category->is_active) {
                $categoryIds = collect([$category->id]);
                $children = \App\Models\Category::where('parent_id', $category->id)->where('is_active', true)->pluck('id');
                if ($children->isNotEmpty()) {
                    $categoryIds = $categoryIds->merge($children);
                }
                $query->whereIn('category_id', $categoryIds);
            } else {
                abort(404, 'Collection not found');
            }
        }

        if ($request->has('category')) {
            $query->whereHas('category', fn($q) => $q->where('slug', $request->category));
        }

        if ($request->has('brand')) {
            $query->whereHas('brand', fn($q) => $q->where('slug', $request->brand));
        }

        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        if ($request->has('sort')) {
            match ($request->sort) {
                'price_low' => $query->orderBy('price', 'asc'),
                'price_high' => $query->orderBy('price', 'desc'),
                'newest' => $query->orderBy('created_at', 'desc'),
                'rating' => $query->orderBy('rating', 'desc'),
                'popular' => $query->orderBy('sales_count', 'desc'),
                default => $query->orderBy('created_at', 'desc'),
            };
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $limit = (int) ($request->query('limit', $request->query('per_page', 100)));
        $page = (int) ($request->query('page', 1));

        $paginated = $query->paginate($limit, ['*'], 'page', $page);

        return response()->json([
            'data' => [
                'total' => $paginated->total(),
                'products' => collect($paginated->items())->map(fn($p) => $this->transformProduct($p))->toArray(),
            ],
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    public function show(Product $product): JsonResponse
    {
        if (!$product->is_active || $product->status !== 'approved') {
            abort(404);
        }

        $product->load([
            'category:id,name,slug,parent_id',
            'brand:id,name,slug',
            'seller:id,business_name,slug,rating',
            'variants',
            'images',
        ]);

        $product->increment('view_count');

        return response()->json([
            'data' => [
                'product' => $this->transformProduct($product),
            ],
        ]);
    }

    public function featured(): JsonResponse
    {
        $products = Product::where('is_active', true)
            ->where('status', 'approved')
            ->where('is_featured', true)
            ->with(['category:id,name,slug', 'brand:id,name,slug', 'images', 'variants'])
            ->limit(12)
            ->get();

        return response()->json([
            'data' => [
                'total' => $products->count(),
                'products' => $products->map(fn($p) => $this->transformProduct($p))->toArray(),
            ],
        ]);
    }

    public function bestsellers(): JsonResponse
    {
        $products = Product::where('is_active', true)
            ->where('status', 'approved')
            ->with(['category:id,name,slug', 'brand:id,name,slug', 'images', 'variants'])
            ->orderBy('sales_count', 'desc')
            ->limit(12)
            ->get();

        return response()->json([
            'data' => [
                'total' => $products->count(),
                'products' => $products->map(fn($p) => $this->transformProduct($p))->toArray(),
            ],
        ]);
    }

    public function newArrivals(): JsonResponse
    {
        $products = Product::where('is_active', true)
            ->where('status', 'approved')
            ->where('is_new_arrival', true)
            ->with(['category:id,name,slug', 'brand:id,name,slug', 'images', 'variants'])
            ->orderBy('created_at', 'desc')
            ->limit(12)
            ->get();

        return response()->json([
            'data' => [
                'total' => $products->count(),
                'products' => $products->map(fn($p) => $this->transformProduct($p))->toArray(),
            ],
        ]);
    }

    public function reviews(Product $product): JsonResponse
    {
        $reviews = $product->reviews()
            ->where('is_approved', true)
            ->with('user:id,first_name,last_name')
            ->latest()
            ->paginate(10);

        return response()->json($reviews);
    }

    public function questions(Product $product): JsonResponse
    {
        $questions = $product->questions()
            ->where('is_published', true)
            ->with(['user:id,first_name,last_name', 'answers'])
            ->latest()
            ->paginate(10);

        return response()->json($questions);
    }
}
