<?php

namespace App\Services\ShiprocketCheckout;

use App\Models\Category;
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
