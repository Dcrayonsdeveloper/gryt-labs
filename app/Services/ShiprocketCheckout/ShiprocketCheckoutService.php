<?php

namespace App\Services\ShiprocketCheckout;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * All Shiprocket Checkout API calls (checkout-api.shiprocket.com).
 *
 * NOTE: This is SEPARATE from ShiprocketService which handles the Shipping API
 * (apiv2.shiprocket.in — order creation, AWB, tracking). The two APIs use
 * different auth mechanisms and are independent Shiprocket products.
 *
 * Setting keys required per-tenant (admin Settings UI):
 *   shiprocket_checkout_api_key      — Headless Checkout API key
 *   shiprocket_checkout_api_secret   — HMAC-SHA256 signing secret
 *   shiprocket_checkout_enabled      — boolean gate for the SDK
 *
 * Webhook auth keys:
 *   shiprocket_checkout_webhook_header_key   — custom header name  (e.g. "ayurvexa")
 *   shiprocket_checkout_webhook_header_value — expected header value
 */
class ShiprocketCheckoutService
{
    private const TOKEN_ENDPOINT   = 'https://checkout-api.shiprocket.com/api/v1/access-token/checkout';
    private const ORDER_ENDPOINT   = 'https://checkout-api.shiprocket.com/api/v1/orders';
    private const LEGACY_ORDER_URL = 'https://checkout-dashboard.shiprocket.in/api/v1/orders';

    // Order details — POST + HMAC. Returns customer name/phone/email/address for a
    // completed checkout, so we never depend on webhooks to learn who bought.
    private const ORDER_DETAILS_ENDPOINT = 'https://checkout-api.shiprocket.com/api/v1/custom-platform-order/details';

    // Catalog push webhooks — Shiprocket charges from its own cached catalog,
    // so every product/collection update must be pushed to these.
    private const CATALOG_PRODUCT_ENDPOINT    = 'https://checkout-api.shiprocket.com/wh/v1/custom/product';
    private const CATALOG_COLLECTION_ENDPOINT = 'https://checkout-api.shiprocket.com/wh/v1/custom/collection';

    // ─── Token Generation ─────────────────────────────────────────────────────

    /**
     * Generate a Shiprocket Checkout session token for the SDK.
     *
     * Calls: POST https://checkout-api.shiprocket.com/api/v1/access-token/checkout
     * Auth:  X-Api-Key header + HMAC-SHA256 signature of the JSON request body.
     *
     * @param  array   $cartItems    Items built via buildCartItem()
     * @param  string  $redirectUrl  Our success page URL Shiprocket redirects to after checkout
     * @return array{success: bool, token?: string, order_id?: string, message?: string}
     */
    public function generateToken(array $cartItems, string $redirectUrl, float $totalDiscount = 0): array
    {
        [$apiKey, $secretKey] = $this->getApiCredentials();

        if (empty($apiKey) || empty($secretKey)) {
            Log::warning('ShiprocketCheckout: no API credentials configured. Set shiprocket_checkout_api_key and shiprocket_checkout_api_secret in Settings.');
            return ['success' => false, 'message' => 'Shiprocket Checkout is not configured for this store.'];
        }

        $cartData = [
            'items'      => $cartItems,
            'mobile_app' => false,
        ];
        if ($totalDiscount > 0) {
            $cartData['total_discount'] = round($totalDiscount, 2);
        }

        $body = [
            'cart_data'    => $cartData,
            'redirect_url' => $redirectUrl,
            'timestamp'    => now()->toIso8601ZuluString(),
        ];
        $jsonBody  = json_encode($body);
        Log::info('ShiprocketCheckout: token request payload', ['body' => $body]);
        $signature = base64_encode(hash_hmac('sha256', $jsonBody, $secretKey, true));

        try {
            $response = Http::withHeaders([
                'X-Api-Key'         => $apiKey,
                'X-Api-HMAC-SHA256' => $signature,
                'Content-Type'      => 'application/json',
            ])->withBody($jsonBody, 'application/json')
              ->timeout(15)
              ->post(self::TOKEN_ENDPOINT);

            $data = $response->json();

            if ($response->successful() && ($data['ok'] ?? false)) {
                return [
                    'success'  => true,
                    'token'    => $data['result']['token'] ?? '',
                    'order_id' => $data['result']['data']['order_id'] ?? null,
                ];
            }

            Log::warning('ShiprocketCheckout: token generation failed', [
                'status'   => $response->status(),
                'response' => $data,
            ]);
            return ['success' => false, 'message' => $data['error'] ?? $data['message'] ?? 'Token generation failed'];

        } catch (\Exception $e) {
            Log::error('ShiprocketCheckout: token exception — ' . $e->getMessage());
            return ['success' => false, 'message' => 'Checkout service temporarily unavailable.'];
        }
    }

    /**
     * Build a single cart item in the format Shiprocket Checkout SDK expects.
     *
     * @param float|null $unitPrice  Effective per-unit price (pack-discounted or cart-stored). Falls back to $product->price.
     */
    public function buildCartItem(Product $product, int $quantity, ?float $unitPrice = null): array
    {
        return [
            'variant_id'   => (string) $product->id,
            'product_id'   => $product->id,
            'quantity'     => $quantity,
            'price'        => $unitPrice ?? (float) $product->price,
            'name'         => $product->name,
            'product_name' => $product->name,
            'sku'          => $product->sku ?? ('SKU-' . $product->id),
            'image'        => url($product->primary_image_url),
        ];
    }

    // ─── Order Fetch ──────────────────────────────────────────────────────────

    /**
     * Fetch a Shiprocket Checkout order by its hex ID.
     *
     * Order IDs from the SDK are hex strings (e.g. 69ea1cb3eee8cb18f8ae6ee0),
     * NOT the numeric IDs used by the Shipping API.
     */
    public function getOrder(string $checkoutOrderId): ?array
    {
        [$apiKey, $secretKey] = $this->getApiCredentials();
        if (empty($apiKey) || empty($secretKey)) {
            return null;
        }

        // POST + HMAC — NOT a GET. The old GET /api/v1/orders/{id} form 404s:
        // that endpoint does not exist. This is the documented shape.
        $body = json_encode([
            'order_id'  => $checkoutOrderId,
            'timestamp' => gmdate('Y-m-d\TH:i:s.v\Z'),
        ]);
        $signature = base64_encode(hash_hmac('sha256', $body, $secretKey, true));

        try {
            $response = Http::withHeaders([
                'X-Api-Key'         => $apiKey,
                'X-Api-HMAC-SHA256' => $signature,
                'Content-Type'      => 'application/json',
            ])->withBody($body, 'application/json')
              ->timeout(15)
              ->post(self::ORDER_DETAILS_ENDPOINT);

            if ($response->successful()) {
                $data = $response->json();
                // Shape: {"ok":true,"result":{...}}
                if (!empty($data['result']) && is_array($data['result'])) {
                    return $data['result'];
                }
                Log::warning('ShiprocketCheckout: getOrder returned no result', [
                    'order_id' => $checkoutOrderId,
                    'body'     => substr((string) $response->body(), 0, 300),
                ]);
                return null;
            }

            Log::warning('ShiprocketCheckout: getOrder failed', [
                'order_id' => $checkoutOrderId,
                'status'   => $response->status(),
                'body'     => substr((string) $response->body(), 0, 300),
            ]);
        } catch (\Throwable $e) {
            Log::warning('ShiprocketCheckout: getOrder exception — ' . $e->getMessage(), [
                'order_id' => $checkoutOrderId,
            ]);
        }

        return null;
    }

    /**
     * Pull real pricing for ONE order from the order-details API and write it onto
     * the order: coupon/discount/COD → money columns (subtotal/discount/shipping/
     * total) + metadata['sr_pricing'] (what the Payment Summary reads) + payment
     * state. Shiprocket does not push pricing on this account, so this is how the
     * discount/coupon gets captured — shared by the OrderObserver (instant, at
     * creation), the per-order job, and the scheduled shiprocket:sync-pricing
     * command so every path produces identical figures.
     *
     * @return array|null  Summary (order/old_total/new_total/old_discount/new_discount/
     *                     old_pay/new_pay/codes/changed), or null if the API had no data.
     */
    public function syncOrderPricing(Order $order, bool $dry = false, ?array $prefetched = null): ?array
    {
        if (empty($order->shiprocket_order_id)) {
            return null;
        }

        // The sync engine passes the already-fetched order-details payload so a
        // combined verify pass costs one API call, not two.
        $r = $prefetched ?? $this->getOrder((string) $order->shiprocket_order_id);
        if (!is_array($r)) {
            return null;
        }

        $subtotal    = (float) ($r['subtotal_price'] ?? $order->subtotal);
        $shipping    = (float) ($r['shipping_charges'] ?? $r['shipping_price'] ?? $order->shipping_cost);
        $codCharge   = array_key_exists('cod_charges', $r) ? (float) $r['cod_charges'] : null;
        $prepaid     = isset($r['prepaid_discount']) ? (float) $r['prepaid_discount'] : null;
        $couponDisc  = (float) ($r['coupon_discount'] ?? $r['total_discount'] ?? 0);
        $couponCodes = array_values(array_filter((array) ($r['coupon_codes'] ?? [])));
        $total       = (float) ($r['total_amount_payable'] ?? ($subtotal - $couponDisc + $shipping));
        $payments    = array_values(array_filter((array) ($r['payments'] ?? [])));

        // Payment state: sum successful online payments → mark paid / collected.
        $onlineReceived = 0.0; $txnDate = null;
        foreach ($payments as $pay) {
            if (strtolower((string) ($pay['payment_status'] ?? '')) === 'success') {
                $onlineReceived += (float) ($pay['amount_received'] ?? $pay['amount'] ?? 0);
                $txnDate = $txnDate ?: ($pay['created_at'] ?? null);
            }
        }
        $newPayStatus   = $order->payment_status;
        $newPaidAmount  = (float) $order->paid_amount;
        $newCollectedAt = $order->payment_collected_at;
        $newCollected   = (bool) $order->payment_collected;
        if ($onlineReceived > 0) {
            $newPaidAmount = $onlineReceived;
            if (!$newCollectedAt && $txnDate) { $newCollectedAt = \Illuminate\Support\Carbon::parse($txnDate); }
            if ($onlineReceived + 0.01 >= $total) { $newPayStatus = 'paid'; $newCollected = true; }
        }

        $srPricing = [
            'total_price'          => $subtotal,
            'total_discount'       => (float) ($r['total_discount'] ?? $couponDisc),
            'coupon_discount'      => $couponDisc,
            'coupon_codes'         => $couponCodes,
            'prepaid_discount'     => $prepaid,
            'cod_charges'          => $codCharge,
            'shipping_price'       => $shipping,
            'tax'                  => 0.0,
            'total_amount_payable' => $total,
            'net_payable'          => $total,
            'synced_at'            => now()->toIso8601String(),
        ];

        $summary = [
            'order'        => $order->order_number,
            'old_total'    => (float) $order->total,   'new_total'    => $total,
            'old_discount' => (float) $order->discount, 'new_discount' => $couponDisc,
            'old_pay'      => $order->payment_status,   'new_pay'      => $newPayStatus,
            'codes'        => $couponCodes,
            'changed'      => false,
        ];

        // Nothing meaningful to record and everything already captured → no-op.
        // Coupon presence alone is NOT a change: compare the stored codes too,
        // otherwise every couponed order re-reports "repaired" on each verify pass.
        $columnsMatch     = abs((float) $order->total - $total) < 0.01
            && abs((float) $order->discount - $couponDisc) < 0.01;
        $pricingCaptured  = !empty($order->metadata['sr_pricing']);
        $codesMatch       = $couponCodes == array_values((array) ($order->metadata['sr_pricing']['coupon_codes'] ?? []));
        $paymentsCaptured = !$payments || !empty($order->metadata['sr_payments']);
        $paymentMatches   = $newPayStatus === $order->payment_status
            && abs($newPaidAmount - (float) $order->paid_amount) < 0.01
            && $newCollected === (bool) $order->payment_collected;
        if ($columnsMatch && $pricingCaptured && $codesMatch && $paymentsCaptured && $paymentMatches) {
            return $summary; // changed = false
        }

        $summary['changed'] = true;

        if (!$dry) {
            $meta = is_array($order->metadata) ? $order->metadata : [];
            $meta['sr_pricing'] = $srPricing;
            if ($payments) { $meta['sr_payments'] = $payments; }
            $order->update([
                'subtotal'             => $subtotal,
                'discount'             => $couponDisc,
                'shipping_cost'        => $shipping,
                'total'                => $total,
                'payment_status'       => $newPayStatus,
                'paid_amount'          => $newPaidAmount,
                'payment_collected'    => $newCollected,
                'payment_collected_at' => $newCollectedAt,
                'metadata'             => $meta,
            ]);
        }

        return $summary;
    }

    /**
     * Flatten a getOrder() payload into the customer fields we store on an Order.
     *
     * @return array{name: ?string, phone: ?string, email: ?string, address: ?array}
     */
    public function extractCustomer(array $order): array
    {
        $addr = $order['shipping_address'] ?? $order['billing_address'] ?? [];

        $name = trim(($addr['first_name'] ?? '') . ' ' . ($addr['last_name'] ?? ''));

        return [
            'name'    => $name !== '' ? $name : null,
            'phone'   => $order['phone'] ?? $addr['phone'] ?? null,
            'email'   => $order['email'] ?? $addr['email'] ?? null,
            'address' => empty($addr) ? null : [
                'name'           => $name,
                'phone'          => $addr['phone'] ?? '',
                'address_line_1' => $addr['line1'] ?? '',
                'address_line_2' => $addr['line2'] ?? '',
                'city'           => $addr['city'] ?? '',
                'state'          => $addr['state'] ?? '',
                'postal_code'    => $addr['pincode'] ?? '',
                'country'        => $addr['country'] ?? 'India',
            ],
        ];
    }

    // ─── Catalog Push (us → Shiprocket) ───────────────────────────────────────

    /**
     * Push a single product to Shiprocket Checkout's custom catalog webhook.
     *
     * Shiprocket charges from its own cached catalog copy, so every product
     * create/update must be pushed here or checkout keeps stale prices.
     * Endpoint: POST wh/v1/custom/product   Auth: X-Api-Key + HMAC-SHA256.
     */
    public function pushCatalogProduct(Product $product): bool
    {
        return $this->signedCatalogPush(
            self::CATALOG_PRODUCT_ENDPOINT,
            (new ShiprocketCatalogFormatter())->product($product),
            'product',
            (string) $product->id,
        );
    }

    /**
     * Push a single collection (category) to Shiprocket Checkout.
     * Endpoint: POST wh/v1/custom/collection.
     */
    public function pushCatalogCollection(Category $category): bool
    {
        return $this->signedCatalogPush(
            self::CATALOG_COLLECTION_ENDPOINT,
            (new ShiprocketCatalogFormatter())->collection($category),
            'collection',
            (string) $category->id,
        );
    }

    /**
     * HMAC-sign a JSON payload and POST it to a Shiprocket catalog webhook.
     * Same auth scheme as generateToken(): X-Api-Key + base64 HMAC-SHA256 of body.
     */
    private function signedCatalogPush(string $url, array $payload, string $type, string $id): bool
    {
        [$apiKey, $secretKey] = $this->getApiCredentials();

        if (empty($apiKey) || empty($secretKey)) {
            Log::warning('ShiprocketCheckout: catalog push skipped — no API credentials', [
                'type' => $type,
                'id'   => $id,
            ]);
            return false;
        }

        $jsonBody  = json_encode($payload);
        $signature = base64_encode(hash_hmac('sha256', $jsonBody, $secretKey, true));

        try {
            $response = Http::withHeaders([
                'X-Api-Key'         => $apiKey,
                'X-Api-HMAC-SHA256' => $signature,
                'Content-Type'      => 'application/json',
            ])->withBody($jsonBody, 'application/json')
              ->timeout(20)
              ->post($url);

            if ($response->successful()) {
                Log::info('ShiprocketCheckout: catalog push ok', [
                    'type'   => $type,
                    'id'     => $id,
                    'status' => $response->status(),
                ]);
                return true;
            }

            Log::warning('ShiprocketCheckout: catalog push failed', [
                'type'   => $type,
                'id'     => $id,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return false;

        } catch (\Throwable $e) {
            Log::error('ShiprocketCheckout: catalog push exception — ' . $e->getMessage(), [
                'type' => $type,
                'id'   => $id,
            ]);
            return false;
        }
    }

    // ─── Webhook Auth ─────────────────────────────────────────────────────────

    /**
     * Verify the custom auth header Shiprocket sends with every webhook request.
     * Configured in Shiprocket → Settings → Webhooks → Headers.
     *
     * Header key and value are stored per-tenant in Settings:
     *   shiprocket_checkout_webhook_header_key   → header name
     *   shiprocket_checkout_webhook_header_value → expected value
     */
    public function verifyWebhookAuth(Request $request): bool
    {
        $headerKey   = Setting::get('shiprocket_checkout_webhook_header_key', '');
        $headerValue = Setting::get('shiprocket_checkout_webhook_header_value', '');

        if (empty($headerKey) || empty($headerValue)) {
            Log::warning('ShiprocketCheckout: webhook auth not configured. '
                . 'Set shiprocket_checkout_webhook_header_key and _value in Settings.');
            return false; // misconfiguration — reject until properly configured
        }

        $provided = $request->header($headerKey);
        if (empty($provided)) {
            Log::warning('ShiprocketCheckout: expected auth header missing', [
                'expected_header' => $headerKey,
                'ip'              => $request->ip(),
            ]);
            return false;
        }

        return hash_equals($headerValue, $provided);
    }

    // ─── Private Helpers ──────────────────────────────────────────────────────

    /** @return array{string, string} [apiKey, secretKey] */
    private function getApiCredentials(): array
    {
        $apiKey    = Setting::get('shiprocket_checkout_api_key', '');
        $secretKey = Setting::get('shiprocket_checkout_api_secret', '');

        // Legacy fallback for tenants using generic api_key/api_secret before the
        // checkout-specific keys were introduced (2026-04-22 audit).
        if (empty($apiKey) || empty($secretKey)) {
            $legacyKey    = Setting::get('api_key', '');
            $legacySecret = Setting::get('api_secret', '');
            if (!empty($legacyKey) && !empty($legacySecret)) {
                Log::warning('ShiprocketCheckout: using deprecated api_key/api_secret. '
                    . 'Migrate to shiprocket_checkout_api_key / shiprocket_checkout_api_secret.');
                return [$legacyKey, $legacySecret];
            }
        }

        return [$apiKey, $secretKey];
    }

    private function getCheckoutApiKey(): ?string
    {
        $key = Setting::get('shiprocket_checkout_api_key', '');
        if (empty($key)) {
            $key = Setting::get('api_key', '');
        }
        return empty($key) ? null : $key;
    }
}
