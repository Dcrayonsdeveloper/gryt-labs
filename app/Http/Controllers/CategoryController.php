<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->with([
                'children' => fn ($q) => $q->where('is_active', true)
                    ->withCount(['products' => fn ($q2) => $q2->where('is_active', true)]),
                'products' => fn ($q) => $q->where('is_active', true)->with('primaryImage')->limit(1),
            ])
            ->withCount(['products' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('position')
            ->get()
            ->map(function ($category) {
                // Total products = direct + all children's products
                $category->total_products_count = $category->products_count
                    + $category->children->sum('products_count');
                return $category;
            })
            ->filter(fn ($c) => $c->total_products_count > 0);

        return view('categories.index', compact('categories'));
    }

    public function show(Request $request, Category $category): View|JsonResponse
    {
        abort_unless($category->is_active, 404);

        // Eager load relations needed for this page (children for filters, parent for breadcrumbs)
        $category->load(['children' => fn ($q) => $q->where('is_active', true), 'parent']);

        // Get all descendant category IDs
        $categoryIds = collect([$category->id]);
        if ($category->children->count()) {
            $categoryIds = $categoryIds->merge(
                $category->children->pluck('id')
            );
        }

        $hasPivot = \Illuminate\Support\Facades\Schema::hasTable('category_product');

        $query = Product::query()
            ->where('is_active', true)
            ->where(function ($q) use ($categoryIds, $hasPivot) {
                $q->whereIn('category_id', $categoryIds);
                if ($hasPivot) {
                    $q->orWhereHas('categories', fn($cq) => $cq->whereIn('categories.id', $categoryIds));
                }
            })
            ->with(['category', 'brand', 'primaryImage']);

        // Subcategory filter
        if ($request->filled('subcategory')) {
            $subSlugs = (array) $request->subcategory;
            $subIds = Category::whereIn('slug', $subSlugs)->pluck('id');
            if ($subIds->isNotEmpty()) {
                $query->where(function ($q) use ($subIds, $hasPivot) {
                    $q->whereIn('category_id', $subIds);
                    if ($hasPivot) {
                        $q->orWhereHas('categories', fn($cq) => $cq->whereIn('categories.id', $subIds));
                    }
                });
            }
        }

        // Brand filter
        if ($request->filled('brand')) {
            $brandSlugs = (array) $request->brand;
            $brandIds = \App\Models\Brand::whereIn('slug', $brandSlugs)->pluck('id');
            if ($brandIds->isNotEmpty()) {
                $query->whereIn('brand_id', $brandIds);
            }
        }

        // Price filter
        if ($request->filled('min_price')) {
            $query->where('price', '>=', (float) $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', (float) $request->max_price);
        }

        // Attributes filter (dynamic based on category)
        foreach ($request->except(['page', 'sort', 'brand', 'min_price', 'max_price', 'in_stock', 'on_sale']) as $key => $value) {
            if (str_starts_with($key, 'attr_')) {
                $attributeSlug = str_replace('attr_', '', $key);
                $values = is_array($value) ? $value : [$value];
                $query->whereHas('variants.attributeValues', function ($q) use ($attributeSlug, $values) {
                    $q->whereHas('attribute', function ($aq) use ($attributeSlug) {
                        $aq->where('slug', $attributeSlug);
                    })->whereIn('slug', $values);
                });
            }
        }

        // In stock filter
        if ($request->boolean('in_stock')) {
            $query->where('stock_quantity', '>', 0);
        }

        // On sale filter (price less than mrp)
        if ($request->boolean('on_sale')) {
            $query->whereNotNull('mrp')->whereColumn('price', '<', 'mrp');
        }

        // Sorting
        $sortBy = $request->get('sort', 'newest');
        match ($sortBy) {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'rating' => $query->orderBy('rating', 'desc'),
            'bestselling' => $query->orderBy('sales_count', 'desc'),
            'name' => $query->orderBy('name', 'asc'),
            default => $query->orderBy('created_at', 'desc'),
        };

        $products = $query->paginate(24)->withQueryString();

        if ($request->ajax()) {
            $html = '';
            foreach ($products as $product) {
                $html .= view('components.product-card', ['product' => $product])->render();
            }
            return response()->json(['html' => $html, 'hasMore' => $products->hasMorePages()]);
        }

        // Subcategories for filter sidebar
        $filterSubcategories = $category->children()->where('is_active', true)->withCount('products')->get();

        // Subcategories for pill nav
        $subcategories = $filterSubcategories;

        // Breadcrumbs
        $breadcrumbs = [];
        if ($category->parent) {
            $breadcrumbs[] = ['label' => $category->parent->name, 'url' => route('category.show', $category->parent)];
        }
        $breadcrumbs[] = ['label' => $category->name, 'url' => null];

        return view('categories.show', compact('category', 'products', 'filterSubcategories', 'subcategories', 'breadcrumbs'));
    }
}
