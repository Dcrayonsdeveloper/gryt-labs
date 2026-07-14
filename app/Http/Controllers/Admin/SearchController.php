<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\DbCompat;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function products(Request $request): JsonResponse
    {
        $query = $request->input('search', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $like = DbCompat::ilike();

        $products = Product::select('id', 'name', 'price', 'mrp', 'sku', 'stock_quantity')
            ->with('primaryImage')
            ->where('name', $like, "%{$query}%")
            ->orderBy('name')
            ->limit(15)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'price' => (float) $p->price,
                'mrp' => (float) $p->mrp,
                'sku' => $p->sku,
                'stock_quantity' => $p->stock_quantity,
                'primary_image_url' => $p->primary_image_url,
            ]);

        return response()->json($products);
    }

    public function orders(Request $request): JsonResponse
    {
        $query = $request->input('search', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $like = DbCompat::ilike();

        $orders = Order::with('user')
            ->where(function ($q) use ($query, $like) {
                $q->where('order_number', $like, "%{$query}%")
                  ->orWhereHas('user', fn ($uq) => $uq->where('email', $like, "%{$query}%"));
            })
            ->latest()
            ->limit(15)
            ->get()
            ->map(fn ($o) => [
                'id' => $o->id,
                'order_number' => $o->order_number,
                'total' => (float) $o->total,
            ]);

        return response()->json($orders);
    }

    public function customers(Request $request): JsonResponse
    {
        $query = $request->input('search', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $like = DbCompat::ilike();

        $customers = User::where('role', 'customer')
            ->where(function ($q) use ($query, $like) {
                $q->where('first_name', $like, "%{$query}%")
                  ->orWhere('last_name', $like, "%{$query}%")
                  ->orWhere('email', $like, "%{$query}%");
            })
            ->latest()
            ->limit(15)
            ->get()
            ->map(fn ($u) => [
                'id' => $u->id,
                'first_name' => $u->first_name,
                'last_name' => $u->last_name,
                'email' => $u->email,
            ]);

        return response()->json($customers);
    }
}
