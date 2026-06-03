<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\SearchLog;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function index(Request $request): View|JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $query = $request->get('q', '');

        if (empty(trim($query))) {
            return redirect()->route('products.index');
        }

        // Log search
        if ($query) {
            SearchLog::create([
                'user_id'       => auth()->id(),
                'session_id'    => $request->session()->getId(),
                'query'         => $query,
                'results_count' => 0, // Will be updated after search
            ]);
        }

        $productsQuery = Product::query()
            ->where('is_active', true)
            ->with(['category', 'brand', 'primaryImage']);

        // Full-text search using Scout if configured, otherwise basic search
        if (config('scout.driver')) {
            $productIds = Product::search($query)->keys();
            $productsQuery->whereIn('id', $productIds);
        } else {
            $productsQuery->where(function ($q) use ($query) {
                $q->where('name', 'ilike', "%{$query}%")
                  ->orWhere('description', 'ilike', "%{$query}%")
                  ->orWhere('sku', 'ilike', "%{$query}%")
                  ->orWhereHas('category', fn ($cq) => $cq->where('name', 'ilike', "%{$query}%"))
                  ->orWhereHas('brand', fn ($bq) => $bq->where('name', 'ilike', "%{$query}%"));
            });
        }

        // Apply filters
        if ($request->filled('category')) {
            $productsQuery->whereHas('category', fn ($q) => $q->where('slug', $request->category));
        }

        if ($request->filled('brand')) {
            $productsQuery->whereHas('brand', fn ($q) => $q->where('slug', $request->brand));
        }

        if ($request->filled('min_price')) {
            $productsQuery->where('price', '>=', $request->min_price);
        }

        if ($request->filled('max_price')) {
            $productsQuery->where('price', '<=', $request->max_price);
        }

        // Sorting
        $sortBy = $request->get('sort', 'relevance');
        match ($sortBy) {
            'price_asc' => $productsQuery->orderBy('price', 'asc'),
            'price_desc' => $productsQuery->orderBy('price', 'desc'),
            'rating' => $productsQuery->orderBy('rating', 'desc'),
            'newest' => $productsQuery->orderBy('created_at', 'desc'),
            default => $productsQuery->orderBy('sales_count', 'desc'),
        };

        $products = $productsQuery->paginate(24)->withQueryString();

        // Update search log with results count
        if ($query) {
            SearchLog::where('query', $query)
                ->where('created_at', '>=', now()->subMinute())
                ->latest()
                ->first()
                ?->update(['results_count' => $products->total()]);
        }

        // Get available filters
        $categories = Category::whereNull('parent_id')
            ->where('is_active', true)
            ->whereHas('products', fn ($q) => $q->where('is_active', true))
            ->get();

        $brands = Brand::where('is_active', true)
            ->whereHas('products', fn ($q) => $q->where('is_active', true))
            ->orderBy('name')
            ->get();

        // Facebook CAPI: Search
        $fbEventId = null;
        if (!empty($query)) {
            $fbEventId = AnalyticsService::generateEventId('srch');
            app(AnalyticsService::class)->trackSearch($query, $products->total(), $request, $fbEventId);
        }

        if ($request->ajax()) {
            $html = '';
            foreach ($products as $product) {
                $html .= view('components.product-card', ['product' => $product])->render();
            }
            return response()->json(['html' => $html, 'hasMore' => $products->hasMorePages()]);
        }

        return view('search.index', compact('products', 'query', 'categories', 'brands', 'fbEventId'));
    }

    public function suggestions(Request $request): JsonResponse
    {
        $query = $request->get('q', '');

        if (strlen($query) < 2) {
            return response()->json(['suggestions' => []]);
        }

        // Product suggestions (ILIKE for case-insensitive match on PostgreSQL)
        $products = Product::query()
            ->where('is_active', true)
            ->where('name', 'ilike', "%{$query}%")
            ->with(['category', 'primaryImage'])
            ->orderBy('sales_count', 'desc')
            ->take(5)
            ->get()
            ->map(fn ($product) => [
                'id' => $product->id,
                'name' => $product->name,
                'url' => route('products.show', $product),
                'image' => $product->primary_image_url,
                'price' => (float) $product->price,
                'category' => $product->category?->name,
                'type' => 'product',
            ]);

        // Category suggestions
        $categories = Category::query()
            ->where('is_active', true)
            ->where('name', 'ilike', "%{$query}%")
            ->take(3)
            ->get()
            ->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'url' => route('category.show', $category),
                'image' => $category->image_url,
                'type' => 'category',
            ]);

        // Brand suggestions
        $brands = Brand::query()
            ->where('is_active', true)
            ->where('name', 'ilike', "%{$query}%")
            ->take(3)
            ->get()
            ->map(fn ($brand) => [
                'id' => $brand->id,
                'name' => $brand->name,
                'url' => route('brands.show', $brand),
                'image' => $brand->logo_url,
                'type' => 'brand',
            ]);

        return response()->json([
            'suggestions' => $products->concat($categories)->concat($brands),
        ]);
    }
}
