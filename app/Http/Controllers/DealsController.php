<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DealsController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $products = Product::where('is_active', true)
            ->where('status', 'approved')
            ->whereColumn('price', '<', 'mrp')
            ->with(['category:id,name,slug', 'brand:id,name,slug', 'primaryImage'])
            ->orderByRaw('(mrp - price) / mrp DESC')
            ->paginate(20);

        if ($request->ajax()) {
            $html = '';
            foreach ($products as $product) {
                $html .= view('components.product-card', ['product' => $product])->render();
            }
            return response()->json(['html' => $html, 'hasMore' => $products->hasMorePages()]);
        }

        return view('deals.index', compact('products'));
    }
}
