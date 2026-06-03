<?php

namespace App\Http\Controllers;

use App\Models\BackInStockRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BackInStockController extends Controller
{
    public function store(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
        ]);

        // Prevent duplicates: same email + same product that hasn't been notified yet
        $exists = BackInStockRequest::where('product_id', $product->id)
            ->where('email', $validated['email'])
            ->pending()
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => true,
                'message' => 'You are already subscribed for this product.',
            ]);
        }

        BackInStockRequest::create([
            'product_id' => $product->id,
            'email' => $validated['email'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'We will notify you when this product is back in stock!',
        ]);
    }
}
