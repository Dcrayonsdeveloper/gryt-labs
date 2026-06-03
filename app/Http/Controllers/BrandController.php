<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BrandController extends Controller
{
    public function index(): View
    {
        $brands = Brand::query()
            ->where('is_active', true)
            ->withCount('products')
            ->orderBy('name')
            ->get();

        return view('brands.index', compact('brands'));
    }

    public function show(Request $request, Brand $brand): View|JsonResponse
    {
        abort_unless($brand->is_active, 404);

        $query = Product::query()
            ->where('is_active', true)
            ->where('brand_id', $brand->id)
            ->with(['category', 'brand', 'primaryImage']);

        // Sorting
        $sortBy = $request->get('sort', 'newest');
        match ($sortBy) {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'rating' => $query->orderBy('rating', 'desc'),
            'bestselling' => $query->orderBy('sales_count', 'desc'),
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

        return view('brands.show', compact('brand', 'products'));
    }
}
