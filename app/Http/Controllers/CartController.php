<?php

namespace App\Http\Controllers;

use App\Models\AbandonedCheckout;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Product;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(Request $request): View
    {
        // Recover abandoned cart if recovery param present
        if ($recoveryId = $request->query('recovery')) {
            $this->recoverAbandonedCart($recoveryId, $request->query('code'));
        }

        $cart = $this->getOrCreateCart();
        $cart->load(['items.product.primaryImage', 'items.variant']);
        $recommendations = $this->getCartRecommendations($cart);

        // Pre-compute cart data for JS (Shopify-like: controller prepares all data)
        $cartItems = $cart->items->map(fn ($i) => [
            'id' => $i->id,
            'product_id' => $i->product_id,
            'name' => $i->product->name ?? '',
            'slug' => $i->product->slug ?? '',
            'image' => $i->product->primary_image_url ?? '',
            'price' => (float) $i->price,
            'mrp' => (float) ($i->product->mrp ?? $i->price),
            'quantity' => $i->quantity,
            'max_qty' => min(10, $i->product->stock_quantity ?? 10),
            'url' => $i->product ? route('products.show', $i->product->slug) : '#',
        ])->values();

        $cartCoupon = $cart->coupon ? [
            'code' => $cart->coupon->code,
            'label' => $cart->coupon->description ?: $cart->coupon->code,
        ] : null;

        $cartDiscount = (float) $cart->discount;

        // GA4 cart items
        $ga4CartItems = $cart->items->map(fn ($i) => [
            'item_id' => (string) $i->product_id,
            'item_name' => $i->product->name ?? '',
            'price' => (float) $i->price,
            'quantity' => $i->quantity,
        ])->values();

        return view('cart.index', compact(
            'cart', 'recommendations', 'cartItems', 'cartCoupon',
            'cartDiscount', 'ga4CartItems'
        ));
    }

    public function data(): JsonResponse
    {
        $cart = $this->getOrCreateCart();
        $cart->load(['items.product.primaryImage', 'items.variant']);

        $items = $cart->items->map(function ($item) {
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'variant_id' => $item->variant_id,
                'quantity' => $item->quantity,
                'price' => (float) $item->price,
                'name' => $item->product->name ?? '',
                'product_name' => $item->product->name ?? '',
                'variant_name' => $item->variant->name ?? null,
                'image' => $item->product->primary_image_url,
                'slug' => $item->product->slug ?? '',
                'url' => $item->product ? '/products/' . $item->product->slug : '#',
            ];
        });

        // Get cross-sell / upsell recommendations based on cart items
        $recommendations = $this->getCartRecommendations($cart);

        // Build applied coupons list
        $allCoupons = $cart->getAppliedCoupons();

        return response()->json([
            'items' => $items,
            'cart_count' => $cart->items->sum('quantity'),
            'subtotal' => (float) $cart->subtotal,
            'discount' => (float) $cart->discount,
            'total' => (float) $cart->total,
            'recommendations' => $recommendations,
            'applied_coupons' => $allCoupons->map(fn($c) => $this->formatCouponData($c, $cart))->values()->toArray(),
        ]);
    }

    public function add(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'variant_id' => ['nullable', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        $product = Product::with(['category', 'brand'])->findOrFail($validated['product_id']);

        // Check stock
        $variantId = $validated['variant_id'] ?? null;
        if ($variantId) {
            $variant = $product->variants()->find($variantId);
            if (!$variant) {
                $error = 'The selected variant does not belong to this product.';
                return $request->wantsJson()
                    ? response()->json(['error' => $error], 422)
                    : back()->with('error', $error);
            }
            $stockQuantity = $variant->stock_quantity;
        } else {
            $stockQuantity = $product->stock_quantity;
        }

        if ($stockQuantity < $validated['quantity']) {
            $error = $stockQuantity > 0
                ? "Only {$stockQuantity} item(s) available in stock."
                : 'This item is currently out of stock.';
            if ($request->wantsJson()) {
                return response()->json(['error' => $error, 'available' => $stockQuantity], 422);
            }
            return back()->with('error', $error);
        }

        $cart = $this->getOrCreateCart();

        // Check if item already in cart
        $existingItem = $cart->items()
            ->where('product_id', $validated['product_id'])
            ->where('variant_id', $variantId)
            ->first();

        if ($existingItem) {
            $newQuantity = $existingItem->quantity + $validated['quantity'];
            if ($newQuantity > $stockQuantity) {
                $inCart = $existingItem->quantity;
                $canAdd = $stockQuantity - $inCart;
                $error = $canAdd > 0
                    ? "You already have {$inCart} in your cart. You can add up to {$canAdd} more."
                    : "You already have all {$stockQuantity} available item(s) in your cart.";
                if ($request->wantsJson()) {
                    return response()->json(['error' => $error, 'available' => $stockQuantity, 'in_cart' => $inCart], 422);
                }
                return back()->with('error', $error);
            }
            $existingItem->update(['quantity' => $newQuantity]);
        } else {
            $price = $variantId
                ? $product->variants()->find($variantId)->price ?? $product->price
                : $product->getPackUnitPrice($validated['quantity']);

            $cart->items()->create([
                'product_id' => $validated['product_id'],
                'variant_id' => $variantId,
                'quantity' => $validated['quantity'],
                'price' => $price,
            ]);
        }

        $cart->recalculate();

        // Facebook CAPI: AddToCart
        $eventId = AnalyticsService::generateEventId('atc');
        app(AnalyticsService::class)->trackAddToCart($product, $validated['quantity'], $request, $eventId);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Product added to cart',
                'cart_count' => $cart->items->sum('quantity'),
                'cart_total' => $cart->total,
                'fb_event' => [
                    'event_id' => $eventId,
                    'content_ids' => [(string) $product->id],
                    'content_name' => $product->name,
                    'content_type' => 'product',
                    'value' => (float) $product->price * $validated['quantity'],
                    'currency' => \App\Models\Setting::get('currency', config('app.currency', 'INR')),
                ],
                'ga4_item' => [
                    'item_id' => $product->sku ?? (string) $product->id,
                    'item_name' => $product->name,
                    'item_category' => $product->category?->name ?? '',
                    'item_brand' => $product->brand?->name ?? '',
                    'price' => (float) $product->price,
                    'quantity' => (int) $validated['quantity'],
                ],
            ]);
        }

        return back()->with('success', 'Product added to cart.');
    }

    public function update(Request $request, CartItem $cartItem): JsonResponse|RedirectResponse
    {
        // Verify the cart item belongs to the current user's/session's cart
        $cart = $this->getOrCreateCart();
        if ($cartItem->cart_id !== $cart->id) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'This item is no longer in your cart. Please refresh the page.'], 403);
            }
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        // Check stock — eager-load the relations we're about to access
        $cartItem->loadMissing(['variant', 'product']);
        $stockQuantity = $cartItem->variant_id
            ? ($cartItem->variant?->stock_quantity ?? 0)
            : ($cartItem->product?->stock_quantity ?? 0);

        if ($validated['quantity'] > $stockQuantity) {
            $error = $stockQuantity > 0
                ? "Only {$stockQuantity} item(s) available in stock."
                : 'This item is currently out of stock.';
            if ($request->wantsJson()) {
                return response()->json(['error' => $error, 'available' => $stockQuantity], 422);
            }
            return back()->with('error', $error);
        }

        $cartItem->update(['quantity' => $validated['quantity']]);
        $cart = $cartItem->cart;
        $hadCoupon = $cart->coupon_id;
        $cart->recalculate();
        $cart->refresh();
        $cart->load('coupon');

        $couponRemoved = $hadCoupon && !$cart->coupon_id;
        $message = $couponRemoved
            ? 'Cart updated. Coupon was removed as it no longer applies.'
            : 'Cart updated';

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'coupon_removed' => $couponRemoved,
                'item_total' => $cartItem->quantity * $cartItem->price,
                'cart_count' => $cart->items->sum('quantity'),
                'cart_subtotal' => (float) $cart->subtotal,
                'cart_discount' => (float) $cart->discount,
                'cart_total' => (float) $cart->total,
                'coupon' => $cart->coupon ? $this->formatCouponData($cart->coupon, $cart) : null,
            ]);
        }

        return back()->with('success', $message);
    }

    public function destroy(CartItem $cartItem): JsonResponse|RedirectResponse
    {
        // Verify the cart item belongs to the current user's/session's cart
        $ownCart = $this->getOrCreateCart();
        if ($cartItem->cart_id !== $ownCart->id) {
            if (request()->wantsJson()) {
                return response()->json(['error' => 'This item is no longer in your cart. Please refresh the page.'], 403);
            }
            abort(403, 'Unauthorized');
        }

        $cart = $cartItem->cart;
        $hadCoupon = $cart->coupon_id;

        // Capture product info for GA4 before deleting
        $removedItem = [
            'item_id' => $cartItem->product->sku ?? (string) $cartItem->product_id,
            'item_name' => $cartItem->product->name,
            'price' => (float) $cartItem->price,
            'quantity' => $cartItem->quantity,
        ];

        $cartItem->delete();
        $cart->recalculate();
        $cart->refresh();
        $cart->load('coupon');

        $couponRemoved = $hadCoupon && !$cart->coupon_id;
        $message = $couponRemoved
            ? 'Item removed from cart. Coupon was removed as it no longer applies.'
            : 'Item removed from cart';

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'coupon_removed' => $couponRemoved,
                'cart_count' => $cart->items->sum('quantity'),
                'cart_subtotal' => (float) $cart->subtotal,
                'cart_discount' => (float) $cart->discount,
                'cart_total' => (float) $cart->total,
                'coupon' => $cart->coupon ? $this->formatCouponData($cart->coupon, $cart) : null,
                'ga4_removed_item' => $removedItem,
            ]);
        }

        return back()->with('success', $message);
    }

    public function clear(): JsonResponse|RedirectResponse
    {
        $cart = $this->getOrCreateCart();
        $cart->items()->delete();
        $cart->update([
            'coupon_id' => null,
            'applied_coupons' => null,
            'discount' => 0,
        ]);
        $cart->recalculate();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Cart cleared',
            ]);
        }

        return back()->with('success', 'Cart cleared.');
    }

    public function applyCoupon(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $cart = $this->getOrCreateCart();
        $cart->load(['items.product', 'coupon']);

        $newCode = strtoupper($validated['code']);
        $appliedCouponIds = $cart->applied_coupons ?? [];

        // Check if the same coupon is already applied
        if (!empty($appliedCouponIds)) {
            $existingCodes = Coupon::whereIn('id', $appliedCouponIds)->pluck('code')->map(fn($c) => strtoupper($c))->toArray();
            if (in_array($newCode, $existingCodes)) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Coupon already applied',
                        'cart_discount' => (float) $cart->discount,
                        'cart_total' => (float) $cart->total,
                    ]);
                }
                return back()->with('success', 'Coupon already applied.');
            }
        } elseif ($cart->coupon_id && $cart->coupon && strtoupper($cart->coupon->code) === $newCode) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Coupon already applied',
                    'cart_discount' => (float) $cart->discount,
                    'cart_total' => (float) $cart->total,
                ]);
            }
            return back()->with('success', 'Coupon already applied.');
        }

        $coupon = Coupon::whereRaw('UPPER(code) = ?', [$newCode])
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()))
            ->first();

        if (!$coupon) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Invalid or expired coupon code'], 422);
            }
            return back()->with('error', 'Invalid or expired coupon code.');
        }

        // Check minimum order amount (not for BOGO — BOGO checks quantity instead)
        if ($coupon->type !== 'buy_x_get_y' && $coupon->min_order_amount && $cart->subtotal < $coupon->min_order_amount) {
            $message = "This coupon requires a minimum order of " . format_price($coupon->min_order_amount);
            if ($request->wantsJson()) {
                return response()->json(['error' => $message], 422);
            }
            return back()->with('error', $message);
        }

        // Check global usage limit
        if ($coupon->usage_limit && $coupon->times_used >= $coupon->usage_limit) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'This coupon has reached its usage limit'], 422);
            }
            return back()->with('error', 'This coupon has reached its usage limit.');
        }

        // Check per-user usage limit
        if (auth()->check() && $coupon->usage_per_user) {
            $userUsage = \App\Models\Order::where('user_id', auth()->id())
                ->where('coupon_id', $coupon->id)
                ->count();
            if ($userUsage >= $coupon->usage_per_user) {
                $message = 'You have already used this coupon the maximum number of times.';
                if ($request->wantsJson()) {
                    return response()->json(['error' => $message], 422);
                }
                return back()->with('error', $message);
            }
        }

        // Calculate discount using the model
        $discount = $coupon->calculateDiscount((float) $cart->subtotal, $cart->items);

        if ($discount <= 0 && $coupon->type !== 'free_shipping') {
            $message = $coupon->type === 'buy_x_get_y'
                ? 'Your cart does not meet the quantity requirements for this offer.'
                : 'This coupon cannot be applied to your cart.';
            if ($request->wantsJson()) {
                return response()->json(['error' => $message], 422);
            }
            return back()->with('error', $message);
        }

        // Stacking logic: determine whether to stack or replace
        $existingCoupons = !empty($appliedCouponIds) ? Coupon::whereIn('id', $appliedCouponIds)->get() : collect();

        if ($coupon->is_stackable && ($existingCoupons->isEmpty() || $existingCoupons->every(fn($c) => $c->is_stackable))) {
            // Stack: new coupon is stackable AND all existing coupons are stackable
            $appliedCouponIds[] = $coupon->id;
        } else {
            // Replace: either new coupon is non-stackable, or existing coupons are non-stackable
            $appliedCouponIds = [$coupon->id];
        }

        $cart->update([
            'coupon_id' => $appliedCouponIds[0],
            'applied_coupons' => $appliedCouponIds,
        ]);
        $cart->recalculate();
        $cart->refresh();

        $allCoupons = $cart->getAppliedCoupons();
        $message = count($appliedCouponIds) > 1
            ? 'Coupon stacked successfully'
            : 'Coupon applied successfully';

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'cart_discount' => (float) $cart->discount,
                'cart_total' => (float) $cart->total,
                'coupon' => $allCoupons->isNotEmpty() ? $this->formatCouponData($allCoupons->first(), $cart) : null,
                'applied_coupons' => $allCoupons->map(fn($c) => $this->formatCouponData($c, $cart))->values()->toArray(),
            ]);
        }

        return back()->with('success', $message);
    }

    public function removeCoupon(Request $request): JsonResponse|RedirectResponse
    {
        $cart = $this->getOrCreateCart();
        $couponIdToRemove = $request->input('coupon_id');
        $appliedCouponIds = $cart->applied_coupons ?? [];

        if ($couponIdToRemove && !empty($appliedCouponIds)) {
            // Remove specific coupon from stack
            $appliedCouponIds = array_values(array_filter($appliedCouponIds, fn($id) => $id != $couponIdToRemove));
            $cart->update([
                'coupon_id' => !empty($appliedCouponIds) ? $appliedCouponIds[0] : null,
                'applied_coupons' => !empty($appliedCouponIds) ? $appliedCouponIds : null,
                'discount' => 0,
            ]);
        } else {
            // Remove all coupons
            $cart->update([
                'coupon_id' => null,
                'applied_coupons' => null,
                'discount' => 0,
            ]);
        }

        $cart->recalculate(skipAutoApply: true);
        $cart->refresh();
        $cart->load('coupon');

        $allCoupons = $cart->getAppliedCoupons();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Coupon removed',
                'cart_subtotal' => (float) $cart->subtotal,
                'cart_discount' => (float) $cart->discount,
                'cart_total' => (float) $cart->total,
                'coupon' => $allCoupons->isNotEmpty() ? $this->formatCouponData($allCoupons->first(), $cart) : null,
                'applied_coupons' => $allCoupons->map(fn($c) => $this->formatCouponData($c, $cart))->values()->toArray(),
            ]);
        }

        return back()->with('success', 'Coupon removed.');
    }

    protected function formatCouponData(Coupon $coupon, Cart $cart): array
    {
        $data = [
            'id' => $coupon->id,
            'code' => $coupon->code,
            'type' => $coupon->type,
            'value' => (float) $coupon->value,
            'auto_apply' => $coupon->auto_apply,
            'is_stackable' => (bool) $coupon->is_stackable,
        ];

        if ($coupon->type === 'buy_x_get_y' && $coupon->conditions) {
            $data['buy_qty'] = (int) ($coupon->conditions['buy_qty'] ?? 0);
            $data['get_qty'] = (int) ($coupon->conditions['get_qty'] ?? 0);
        }

        return $data;
    }

    protected function getCartRecommendations(Cart $cart): array
    {
        $cartProductIds = $cart->items->pluck('product_id')->toArray();

        if (empty($cartProductIds)) {
            return [];
        }

        $cartProducts = Product::whereIn('id', $cartProductIds)->get(['id', 'category_id', 'brand_id', 'price']);
        $categoryIds = $cartProducts->pluck('category_id')->filter()->unique()->toArray();
        $brandIds = $cartProducts->pluck('brand_id')->filter()->unique()->toArray();
        $avgPrice = $cartProducts->avg('price');

        // Fetch recommendations: same category/brand, not in cart, prioritize upsell (higher price)
        $recommendations = Product::active()
            ->whereNotIn('id', $cartProductIds)
            ->where(function ($q) use ($categoryIds, $brandIds) {
                $q->whereIn('category_id', $categoryIds)
                  ->orWhereIn('brand_id', $brandIds);
            })
            ->with(['primaryImage', 'brand'])
            ->orderByRaw('CASE WHEN price > ? THEN 0 ELSE 1 END', [$avgPrice])
            ->inRandomOrder()
            ->take(6)
            ->get();

        return $recommendations->map(function ($product) {
            $hasDiscount = $product->price < $product->mrp;
            return [
                'id' => $product->id,
                'name' => $product->name,
                'price' => (float) $product->price,
                'mrp' => (float) $product->mrp,
                'has_discount' => $hasDiscount,
                'discount_pct' => $hasDiscount ? round($product->discount_percentage ?? 0) : 0,
                'image' => $product->primary_image_url,
                'url' => route('products.show', $product),
                'rating' => (float) ($product->rating ?? 0),
                'brand' => $product->brand?->name,
            ];
        })->toArray();
    }

    protected function recoverAbandonedCart(string $recoveryId, ?string $couponCode): void
    {
        $abandoned = AbandonedCheckout::find($recoveryId);
        if (!$abandoned || empty($abandoned->cart_snapshot)) return;

        $cart = $this->getOrCreateCart();

        // Only restore if cart is empty (don't overwrite active cart)
        if ($cart->items()->count() > 0) return;

        foreach ($abandoned->cart_snapshot as $item) {
            $productId = $item['product_id'] ?? $item['variant_id'] ?? null;
            $product = $productId ? Product::find($productId) : null;
            if (!$product || $product->stock_quantity < 1) continue;

            $cart->items()->create([
                'product_id' => $product->id,
                'variant_id' => $item['variant_id'] ?? null,
                'quantity' => min((int) ($item['quantity'] ?? 1), $product->stock_quantity),
                'price' => $product->price,
            ]);
        }

        $cart->recalculate();

        // Auto-apply recovery coupon if provided
        if ($couponCode) {
            $coupon = Coupon::where('code', $couponCode)->where('is_active', true)->first();
            if ($coupon && (!$coupon->expires_at || $coupon->expires_at->isFuture())) {
                $cart->update(['coupon_id' => $coupon->id]);
                $cart->recalculate();
            }
        }
    }

    protected function getOrCreateCart(): Cart
    {
        if (auth()->check()) {
            return Cart::firstOrCreate(
                ['user_id' => auth()->id()],
                ['session_id' => null]
            );
        }

        $sessionId = session()->getId();
        return Cart::firstOrCreate(
            ['session_id' => $sessionId],
            ['user_id' => null]
        );
    }
}
