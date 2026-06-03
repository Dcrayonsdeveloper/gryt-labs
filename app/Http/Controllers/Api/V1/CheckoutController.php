<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\OrderPlaced;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\UserAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function validate(Request $request): JsonResponse
    {
        $cart = Cart::where('user_id', auth()->id())
            ->with(['items.product', 'items.variant', 'coupon'])
            ->first();

        if (! $cart || $cart->items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Cart is empty.',
            ], 422);
        }

        $errors = [];
        foreach ($cart->items as $item) {
            $available = $item->variant_id
                ? $item->variant->stock_quantity
                : $item->product->stock_quantity;

            if ($available < $item->quantity) {
                $errors[] = [
                    'product' => $item->product->name,
                    'requested' => $item->quantity,
                    'available' => $available,
                ];
            }
        }

        if (! empty($errors)) {
            return response()->json([
                'success' => false,
                'message' => 'Some items are out of stock.',
                'errors' => $errors,
            ], 422);
        }

        $addresses = UserAddress::where('user_id', auth()->id())->get();

        return response()->json([
            'success' => true,
            'data' => [
                'cart' => [
                    'items_count' => $cart->items->count(),
                    'subtotal' => (float) $cart->subtotal,
                    'discount' => (float) $cart->discount,
                    'total' => (float) ($cart->subtotal - $cart->discount),
                    'coupon' => $cart->coupon?->code,
                ],
                'addresses' => $addresses,
            ],
        ]);
    }

    public function process(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shipping_address_id' => ['required', 'exists:user_addresses,id'],
            'billing_address_id' => ['nullable', 'exists:user_addresses,id'],
            'payment_method' => ['required', 'string'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $cart = Cart::where('user_id', auth()->id())
            ->with(['items.product', 'items.variant', 'coupon'])
            ->first();

        if (! $cart || $cart->items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Cart is empty.',
            ], 422);
        }

        $shippingAddress = UserAddress::where('user_id', auth()->id())
            ->findOrFail($validated['shipping_address_id']);
        $billingAddress = $validated['billing_address_id']
            ? UserAddress::where('user_id', auth()->id())->findOrFail($validated['billing_address_id'])
            : $shippingAddress;

        // Stock validation
        foreach ($cart->items as $item) {
            $available = $item->variant_id ? $item->variant->stock_quantity : $item->product->stock_quantity;
            if ($available < $item->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => "\"{$item->product->name}\" only has {$available} item(s) in stock.",
                ], 422);
            }
        }

        $order = DB::transaction(function () use ($cart, $shippingAddress, $billingAddress, $validated) {
            $order = Order::create([
                'user_id' => auth()->id(),
                'status' => 'confirmed',
                'payment_status' => 'pending',
                'subtotal' => $cart->subtotal,
                'discount' => $cart->discount,
                'shipping_cost' => 0,
                'tax' => 0,
                'total' => $cart->subtotal - $cart->discount,
                'coupon_id' => $cart->coupon_id,
                'shipping_address_id' => $shippingAddress->id,
                'billing_address_id' => $billingAddress->id,
                'shipping_address_snapshot' => [
                    'name' => $shippingAddress->full_name,
                    'phone' => $shippingAddress->phone,
                    'address_line_1' => $shippingAddress->address_line_1,
                    'address_line_2' => $shippingAddress->address_line_2,
                    'city' => $shippingAddress->city,
                    'state' => $shippingAddress->state,
                    'postal_code' => $shippingAddress->postal_code,
                    'country' => $shippingAddress->country,
                ],
                'billing_address_snapshot' => [
                    'name' => $billingAddress->full_name,
                    'address_line_1' => $billingAddress->address_line_1,
                    'city' => $billingAddress->city,
                    'state' => $billingAddress->state,
                    'postal_code' => $billingAddress->postal_code,
                    'country' => $billingAddress->country,
                ],
                'notes' => $validated['notes'] ?? null,
                'metadata' => ['payment_method' => $validated['payment_method'], 'source' => 'api'],
            ]);

            foreach ($cart->items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'variant_id' => $item->variant_id,
                    'seller_id' => $item->product->seller_id,
                    'product_name' => $item->product->name,
                    'sku' => $item->product->sku ?? '',
                    'variant_name' => $item->variant?->attributeValues->pluck('value')->join(' / '),
                    'quantity' => $item->quantity,
                    'mrp' => $item->product->mrp ?? $item->price,
                    'price' => $item->price,
                    'tax' => 0,
                    'discount' => 0,
                    'total' => $item->price * $item->quantity,
                ]);

                if ($item->variant_id) {
                    $item->variant->decrement('stock_quantity', $item->quantity);
                } else {
                    $item->product->decrement('stock_quantity', $item->quantity);
                }
                $item->product->increment('sales_count', $item->quantity);
            }

            if ($cart->coupon) {
                $cart->coupon->increment('times_used');
            }

            $cart->items()->delete();
            $cart->update(['coupon_id' => null, 'discount' => 0]);

            return $order;
        });

        try {
            OrderPlaced::dispatch($order, 'api');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('OrderPlaced event failed (API)', ['order' => $order->id, 'error' => $e->getMessage()]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order placed successfully.',
            'data' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'total' => (float) $order->total,
                'status' => $order->status,
            ],
        ], 201);
    }
}
