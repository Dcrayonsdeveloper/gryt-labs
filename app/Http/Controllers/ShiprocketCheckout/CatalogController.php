<?php

namespace App\Http\Controllers\ShiprocketCheckout;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Services\ShiprocketCheckout\ShiprocketCatalogFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Serves Shiprocket Checkout's Custom Endpoint catalog requests (pull model).
 *
 * Configured in Shiprocket Checkout → Settings → Custom Endpoints:
 *
 *   TYPE                    | URL
 *   ────────────────────────┼──────────────────────────────────────────────────
 *   PRODUCT FETCH           | https://{domain}/sr/catalog/products
 *   COLLECTION FETCH        | https://{domain}/sr/catalog/categories
 *   COLLECTION PRODUCT FETCH| https://{domain}/sr/catalog/categories/{slug}/products
 *                           | https://{domain}/sr/catalog/categories/products?collection_id=N
 *
 * NOTE: Shiprocket's primary sync is now the PUSH model — see
 * ShiprocketCheckoutService::pushCatalogProduct() / pushCatalogCollection(),
 * fired by ProductObserver / CategoryObserver on every catalog change.
 * These pull endpoints remain as a fallback and use the same
 * ShiprocketCatalogFormatter so the two never diverge.
 *
 * Auth: Shiprocket sends the requests without auth headers from these endpoints,
 * so we return only public, in-stock product data (no cost_price, no admin data).
 */
class CatalogController extends Controller
{
    private const PER_PAGE = 250;

    public function __construct(
        private ShiprocketCatalogFormatter $formatter,
    ) {}

    // ─── Products ─────────────────────────────────────────────────────────────

    /**
     * GET /sr/catalog/products?page=1&limit=250
     *
     * Returns all active products in Shopify-compatible format.
     */
    public function products(Request $request): JsonResponse
    {
        $page  = max(1, (int) $request->query('page', 1));
        $limit = $this->resolveLimit($request);

        $products = Product::query()
            ->where('is_active', true)
            ->with(['images', 'category', 'variants', 'variants.images'])
            ->orderBy('id')
            ->paginate($limit, ['*'], 'page', $page);

        return response()->json([
            'data' => [
                'total'    => $products->total(),
                'products' => $products->map(fn ($p) => $this->formatter->product($p))->values(),
            ],
        ]);
    }

    // ─── Categories ───────────────────────────────────────────────────────────

    /**
     * GET /sr/catalog/categories
     *
     * Returns all active categories (collections in Shopify terminology).
     */
    public function categories(): JsonResponse
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->withCount(['products' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => [
                'total'       => $categories->count(),
                'collections' => $categories->map(fn ($c) => $this->formatCategory($c))->values(),
            ],
        ]);
    }

    /**
     * GET /sr/catalog/categories/products?collection_id=2&page=1&limit=250
     *
     * Returns products for a category identified by numeric collection_id.
     */
    public function collectionProducts(Request $request): JsonResponse
    {
        $collectionId = (int) $request->query('collection_id');
        $category = Category::findOrFail($collectionId);

        return $this->buildCategoryProductsResponse($request, $category);
    }

    /**
     * GET /sr/catalog/categories/{slug}/products?page=1&limit=250
     *
     * Returns products belonging to a specific category by slug.
     */
    public function categoryProducts(Request $request, string $slug): JsonResponse
    {
        $category = Category::where('slug', $slug)->firstOrFail();

        return $this->buildCategoryProductsResponse($request, $category);
    }

    // ─── Shared builders ──────────────────────────────────────────────────────

    private function buildCategoryProductsResponse(Request $request, Category $category): JsonResponse
    {
        $page  = max(1, (int) $request->query('page', 1));
        $limit = $this->resolveLimit($request);

        $products = Product::query()
            ->where('category_id', $category->id)
            ->where('is_active', true)
            ->with(['images', 'category', 'variants', 'variants.images'])
            ->orderBy('id')
            ->paginate($limit, ['*'], 'page', $page);

        return response()->json([
            'data' => [
                'total'    => $products->total(),
                'products' => $products->map(fn ($p) => $this->formatter->product($p))->values(),
            ],
        ]);
    }

    // ─── Formatters ───────────────────────────────────────────────────────────

    private function formatCategory(Category $category): array
    {
        return [
            'id'             => $category->id,
            'title'          => $category->name,
            'handle'         => $category->slug,
            'description'    => $category->description ?? '',
            'image'          => $category->image_url ? ['src' => url($category->image_url)] : null,
            'products_count' => $category->products_count ?? 0,
        ];
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function resolveLimit(Request $request): int
    {
        $raw = $request->query('limit') ?? $request->query('per_page', self::PER_PAGE);

        return min(self::PER_PAGE, max(1, (int) $raw));
    }
}
