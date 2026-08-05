<?php

namespace App\Http\Controllers\ShiprocketCheckout;

use App\Http\Controllers\Controller;
use App\Models\AbandonedCheckout;
use App\Models\Cart;
use App\Models\Product;
use App\Services\ShiprocketCheckout\ShiprocketCheckoutService;
use App\Services\ShiprocketCheckout\StockHoldService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Generates a Shiprocket Checkout SDK session token.
 *
 * Called via AJAX from any page (product detail Buy Now, cart drawer, cart page).
 * Returns a token the frontend passes to `window.shiprocketCheckoutCustomHandler()`.
 *
 * Route: POST /api/shiprocket-checkout-token
 *
 * Request params:
 *   source     = 'cart'     → send all cart items (multi-product)
 *   product_id + quantity   → single product Buy Now
 */
class TokenController extends Controller
{
    public function __construct(
        private ShiprocketCheckoutService $checkout,
        private StockHoldService          $stockHold,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'source'     => 'sometimes|in:cart',
            'product_id' => 'required_without:source|integer',
            'quantity'   => 'required_without:source|integer|min:1|max:10',
        ]);

        [$cartItems, $cartTotal, $itemsCount, $metaProducts, $packDiscount] = $request->input('source') === 'cart'
            ? $this->buildFromCart($request)
            : $this->buildFromProduct($request);

        if (empty($cartItems)) {
            return response()->json(['success' => false, 'message' => 'Cart is empty'], 422);
        }

        $redirectUrl = url('/checkout/success/shiprocket');
        $result      = $this->checkout->generateToken($cartItems, $redirectUrl, $packDiscount);

        // Always capture an AbandonedCheckout row before handing off to Shiprocket.
        // Even if Shiprocket orphans the session we have the cart for recovery.
        $this->captureAbandoned($request, $cartItems, $cartTotal, $itemsCount, $metaProducts, $result);

        if ($result['success']) {
            return response()->json([
                'success'  => true,
                'token'    => $result['token'],
                'order_id' => $result['order_id'] ?? '',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'] ?? 'Failed to generate checkout token',
        ], 422);
    }

    // ─── Cart Building ────────────────────────────────────────────────────────

    private function buildFromCart(Request $request): array
    {
        $cart = Cart::with('items.product')
            ->where(auth()->check()
                ? ['user_id' => auth()->id()]
                : ['session_id' => session()->getId()])
            ->first();

        if (!$cart || $cart->items->isEmpty()) {
            return [[], 0, 0, [], 0];
        }

        // Charge the CURRENT price: cart_items.price is a snapshot from when the
        // item was added, so re-sync to the live product price before we build the
        // Shiprocket cart (otherwise a since-changed price leaks into checkout and
        // shows up as a phantom "pack discount" below).
        $cart->syncItemPrices();

        $cartItems    = [];
        $cartTotal    = 0.0;
        $packDiscount = 0.0;
        $itemsCount   = 0;
        $metaProducts = [];

        $sessionId = session()->getId();

        foreach ($cart->items as $item) {
            $product = $item->product;
            $qty     = (int) $item->quantity;
            if (!$product || !$product->isInStock()) continue;

            // Atomic check + hold: reserves stock in Redis for 10 min
            if (!$this->stockHold->hold($product->id, $qty, $sessionId, $product->stock_quantity)) {
                Log::info('ShiprocketCheckout: stock hold failed, skipping item', [
                    'product_id' => $product->id,
                    'requested'  => $qty,
                    'db_stock'   => $product->stock_quantity,
                ]);
                continue;
            }

            $cartItems[]    = $this->checkout->buildCartItem($product, $qty, (float) $item->price);
            $baseTotal       = (float) $product->price * $qty;
            $packTotal       = (float) $item->price * $qty;
            $packDiscount   += max(0, $baseTotal - $packTotal);
            $cartTotal      += $packTotal;
            $itemsCount     += $qty;
            $metaProducts[]  = ['id' => $product->id, 'name' => $product->name, 'qty' => $qty];
        }

        return [$cartItems, $cartTotal, $itemsCount, $metaProducts, $packDiscount];
    }

    private function buildFromProduct(Request $request): array
    {
        $product = Product::findOrFail($request->product_id);
        $qty     = (int) $request->quantity;

        if (!$product->isInStock()) {
            return [[], 0, 0, [], 0];
        }

        // Atomic check + hold: reserves stock in Redis for 10 min
        if (!$this->stockHold->hold($product->id, $qty, session()->getId(), $product->stock_quantity)) {
            return [[], 0, 0, [], 0];
        }

        // Bundle product with a linked combo: send the linear composition
        // (floor(qty/2) × Pack-of-2 combo + remainder single) so Shiprocket —
        // which prices unit × qty and lets the customer edit qty in its popup —
        // always charges the exact bundle total. See bundles:sync-combos.
        $comboId = $product->pack_config['bundle']['combo_product_id'] ?? null;
        $combo   = ($product->packBundle() && $comboId) ? Product::find($comboId) : null;

        if ($combo && $combo->is_active) {
            $comp    = $product->packComposition($qty); // mode-aware (pairs vs even-only)
            $pairs   = $comp['combos'];
            $singles = $comp['singles'];
            $singlePrice = $product->getPackTotalPrice(1);

            $items = [];
            $meta  = [];
            if ($pairs > 0) {
                $items[] = $this->checkout->buildCartItem($combo, $pairs, (float) $combo->price);
                $meta[]  = ['id' => $combo->id, 'name' => $combo->name, 'qty' => $pairs];
            }
            if ($singles > 0) {
                $items[] = $this->checkout->buildCartItem($product, $singles, $singlePrice);
                $meta[]  = ['id' => $product->id, 'name' => $product->name, 'qty' => $singles];
            }

            $packTotal    = $pairs * (float) $combo->price + $singles * $singlePrice;
            $baseTotal    = (float) $product->price * $qty;

            return [$items, $packTotal, $qty, $meta, max(0, $baseTotal - $packTotal)];
        }

        $unitPrice    = $product->getPackUnitPrice($qty);
        $baseTotal    = (float) $product->price * $qty;
        $packTotal    = $unitPrice * $qty;
        $packDiscount = max(0, $baseTotal - $packTotal);

        return [
            [$this->checkout->buildCartItem($product, $qty, $unitPrice)],
            $packTotal,
            $qty,
            [['id' => $product->id, 'name' => $product->name, 'qty' => $qty]],
            $packDiscount,
        ];
    }

    // ─── Abandoned Checkout Capture ───────────────────────────────────────────

    private function captureAbandoned(
        Request $request,
        array   $cartItems,
        float   $cartTotal,
        int     $itemsCount,
        array   $metaProducts,
        array   $result,
    ): void {
        try {
            $referer = $request->headers->get('referer', '');
            $utm     = $this->extractUtm($referer);
            $user    = auth()->user();

            $ac = AbandonedCheckout::create([
                'session_id'            => $request->session()->getId(),
                'user_id'               => $user?->id,
                'name'                  => $user?->full_name,
                'email'                 => $user?->email,
                'phone'                 => $user?->phone,
                'cart_total'            => $cartTotal,
                'items_count'           => $itemsCount,
                'step'                  => 'shiprocket_handoff',
                'source'                => 'shiprocket_checkout',
                'ip_address'            => $request->ip(),
                'user_agent'            => substr((string) $request->userAgent(), 0, 500),
                'metadata'              => array_merge($utm, array_filter([
                    'referer'          => $referer,
                    'products'         => $metaProducts,
                    'cart_fingerprint' => self::cartFingerprint($cartItems),
                    'fbc'              => $request->cookie('_fbc'),
                    'fbp'              => $request->cookie('_fbp'),
                ])),
                'shiprocket_cart_token' => $result['token'] ?? null,
                'shiprocket_order_id'   => $result['order_id'] ?? null,
                'cart_snapshot'         => $cartItems,
            ]);

            // Register token order_id in bridge so webhook cart_id and callback oid can find this AC
            if (!empty($result['order_id'])) {
                $ac->registerShiprocketId($result['order_id'], 'token');
            }
        } catch (\Throwable $e) {
            Log::warning('ShiprocketCheckout: abandoned capture failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Deterministic fingerprint from cart items: sorted product_id:quantity pairs.
     * Used to match webhook (which has cart_id) to AC (which has oid) when
     * Shiprocket's two IDs don't match and timing/phone fallbacks fail.
     */
    public static function cartFingerprint(array $items): string
    {
        $pairs = [];
        foreach ($items as $item) {
            $pid = $item['product_id'] ?? $item['variant_id'] ?? 0;
            $qty = $item['quantity'] ?? 1;
            $pairs[] = "{$pid}:{$qty}";
        }
        sort($pairs);
        return md5(implode('|', $pairs));
    }

    private function extractUtm(string $referer): array
    {
        $utm = [];
        if (!$referer) return $utm;

        $parts = parse_url($referer);
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $qs);
            foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'utm_id', 'fbclid', 'gclid'] as $key) {
                if (!empty($qs[$key])) $utm[$key] = $qs[$key];
            }
        }
        return $utm;
    }
}
