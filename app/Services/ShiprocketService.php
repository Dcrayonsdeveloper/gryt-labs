<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Setting;
use App\Models\ShiprocketCheckoutEvent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Shiprocket integration service.
 *
 * Setting keys used (per-tenant, stored in the `settings` table as key-value rows
 * — no migration needed; populate via admin Settings UI or seeder):
 *
 *   shiprocket_email                  — login email for the shipping API (order creation, tracking)
 *   shiprocket_password               — login password for the shipping API
 *   shiprocket_pickup_location        — named pickup location registered in Shiprocket dashboard
 *   shiprocket_checkout_api_key       — Headless Checkout API key (separate product from shipping API)
 *   shiprocket_checkout_api_secret    — Headless Checkout HMAC secret
 *   shiprocket_checkout_enabled       — boolean flag; gates the Checkout SDK in layouts/app.blade.php
 *
 * Legacy fallback: `api_key` / `api_secret` are read only if the Checkout-specific keys
 * are empty, logged as a deprecation warning so we can migrate and remove.
 */
class ShiprocketService
{
    private string $baseUrl = 'https://apiv2.shiprocket.in/v1/external';
    private ?string $token = null;

    public function __construct()
    {
        $this->token = $this->getToken();
    }

    /**
     * Authenticate and get/cache Shiprocket token.
     */
    private function getToken(): ?string
    {
        return Cache::remember(self::tokenCacheKey(), 86000, function () {
            $email = Setting::get('shiprocket_email', config('services.shiprocket.email'));
            $password = Setting::get('shiprocket_password', config('services.shiprocket.password'));

            if (empty($email) || empty($password)) {
                return null;
            }

            try {
                $response = Http::post("{$this->baseUrl}/auth/login", [
                    'email' => $email,
                    'password' => $password,
                ]);

                if ($response->successful()) {
                    return $response->json('token');
                }

                Log::warning('Shiprocket auth rejected', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            } catch (\Exception $e) {
                Log::error('Shiprocket auth failed: ' . $e->getMessage());
            }

            return null;
        });
    }

    /**
     * Tenant-scoped cache key so tenants don't share each other's tokens.
     */
    public static function tokenCacheKey(): string
    {
        try {
            return 'shiprocket_token.' . DB::connection()->getDatabaseName();
        } catch (\Exception $e) {
            return 'shiprocket_token.default';
        }
    }

    /**
     * Forget the cached token (call after credentials change).
     */
    public static function forgetToken(): void
    {
        Cache::forget(self::tokenCacheKey());
    }

    public function isConfigured(): bool
    {
        return !empty($this->token);
    }

    /**
     * Check serviceability and get shipping rates for a pincode.
     */
    public function checkServiceability(string $pickupPincode, string $deliveryPincode, float $weight = 0.5, ?float $codAmount = null): array
    {
        if (!$this->token) {
            return ['available' => false, 'message' => 'Shiprocket not configured'];
        }

        try {
            $params = [
                'pickup_postcode' => $pickupPincode,
                'delivery_postcode' => $deliveryPincode,
                'weight' => $weight,
                'cod' => $codAmount ? 1 : 0,
            ];

            $response = $this->api('GET', '/courier/serviceability/', $params);

            if (!$response->successful()) {
                return ['available' => false, 'message' => 'Service check failed'];
            }

            $data = $response->json('data.available_courier_companies', []);

            if (empty($data)) {
                return ['available' => false, 'message' => 'Delivery not available to this pincode'];
            }

            // Get cheapest and fastest options
            $cheapest = collect($data)->sortBy('rate')->first();
            $fastest = collect($data)->sortBy('estimated_delivery_days')->first();

            return [
                'available' => true,
                'couriers' => count($data),
                'cheapest' => [
                    'name' => $cheapest['courier_name'] ?? '',
                    'rate' => $cheapest['rate'] ?? 0,
                    'etd' => $cheapest['etd'] ?? '',
                    'estimated_days' => $cheapest['estimated_delivery_days'] ?? 5,
                ],
                'fastest' => [
                    'name' => $fastest['courier_name'] ?? '',
                    'rate' => $fastest['rate'] ?? 0,
                    'etd' => $fastest['etd'] ?? '',
                    'estimated_days' => $fastest['estimated_delivery_days'] ?? 3,
                ],
                'all' => collect($data)->map(fn ($c) => [
                    'id' => $c['courier_company_id'] ?? 0,
                    'name' => $c['courier_name'] ?? '',
                    'rate' => $c['rate'] ?? 0,
                    'etd' => $c['etd'] ?? '',
                    'estimated_days' => $c['estimated_delivery_days'] ?? 5,
                    'cod' => $c['cod'] ?? 0,
                ])->toArray(),
            ];
        } catch (\Exception $e) {
            Log::error('Shiprocket serviceability check failed: ' . $e->getMessage());
            return ['available' => false, 'message' => 'Service check error'];
        }
    }

    /**
     * Create a Shiprocket order from our order.
     */
    public function createOrder(Order $order): array
    {
        if (!$this->token) {
            return ['success' => false, 'message' => 'Shiprocket not configured'];
        }

        try {
            $order->loadMissing(['items.product', 'shippingAddress', 'billingAddress', 'user']);

            $items = $order->items->map(fn ($item) => [
                'name' => $item->product?->name ?? ('Item #' . $item->product_id),
                'sku' => $item->product?->sku ?? 'SKU-' . $item->product_id,
                'units' => $item->quantity,
                'selling_price' => (float) $item->price,
                'discount' => 0,
                'tax' => 0,
                'hsn' => '',
            ])->toArray();

            // Resolve address: relation first, then snapshot for guests
            $addr = $this->resolveAddress($order);

            // Resolve customer identity (registered user, otherwise guest fields)
            $firstName = $addr['first_name'] ?: ($order->user?->name ?? $order->guest_name ?? 'Customer');
            $lastName = $addr['last_name'] ?: '';
            $email = $order->user?->email ?? $order->guest_email ?? 'no-reply@example.com';
            $phone = $addr['phone'] ?: ($order->user?->phone ?? $order->guest_phone ?? '');

            // Payment method lives in metadata JSON, not as a column
            $paymentMethod = $order->metadata['payment_method'] ?? 'cod';
            $isCod = in_array($paymentMethod, ['cod', 'partial_pay'], true);

            $payload = [
                'order_id' => $order->order_number,
                'order_date' => $order->created_at->format('Y-m-d H:i'),
                'pickup_location' => Setting::get('shiprocket_pickup_location', '') ?: 'Primary',
                'billing_customer_name' => $firstName,
                'billing_last_name' => $lastName,
                'billing_address' => $addr['address_line_1'],
                'billing_address_2' => $addr['address_line_2'],
                'billing_city' => $addr['city'],
                'billing_pincode' => $addr['postal_code'],
                'billing_state' => $addr['state'],
                'billing_country' => $addr['country'] ?: (Setting::get('shipping_country', '') ?: config('app.country', 'India')),
                'billing_email' => $email,
                'billing_phone' => $phone,
                'shipping_is_billing' => true,
                'order_items' => $items,
                'payment_method' => $isCod ? 'COD' : 'Prepaid',
                'sub_total' => (float) $order->total,
                'length' => $this->resolveMaxDimension($order, 'length', (int) Setting::get('default_package_length', 20)),
                'breadth' => $this->resolveMaxDimension($order, 'width', (int) Setting::get('default_package_breadth', 15)),
                'height' => $this->resolveMaxDimension($order, 'height', (int) Setting::get('default_package_height', 10)),
                'weight' => $this->resolveTotalWeight($order, (float) Setting::get('default_package_weight', 0.5)),
            ];

            $response = $this->api('POST', '/orders/create/adhoc', $payload);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'shiprocket_order_id' => $data['order_id'] ?? null,
                    'shipment_id' => $data['shipment_id'] ?? null,
                    'awb' => $data['awb_code'] ?? null,
                ];
            }

            return ['success' => false, 'message' => $response->json('message', 'Order creation failed')];
        } catch (\Exception $e) {
            Log::error('Shiprocket create order failed: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get the maximum value of a dimension across all order items,
     * falling back to the given default.
     */
    private function resolveMaxDimension(Order $order, string $field, int $default): int
    {
        $max = $order->items
            ->map(fn ($item) => $item->product?->{$field})
            ->filter()
            ->max();

        return $max ? (int) $max : $default;
    }

    /**
     * Sum product weights across order items (weight * quantity),
     * falling back to the given default when no product has weight.
     */
    private function resolveTotalWeight(Order $order, float $default): float
    {
        $hasWeight = false;
        $total = 0.0;

        foreach ($order->items as $item) {
            $w = $item->product?->weight;
            if ($w) {
                $hasWeight = true;
                $total += (float) $w * $item->quantity;
            }
        }

        return $hasWeight ? round($total, 2) : $default;
    }

    /**
     * Resolve a shipping address from the relation, falling back to the snapshot
     * stored on guest orders. Always returns the same key set.
     */
    private function resolveAddress(Order $order): array
    {
        $rel = $order->shippingAddress ?? $order->billingAddress;
        if ($rel) {
            return [
                'first_name' => $rel->first_name ?? '',
                'last_name' => $rel->last_name ?? '',
                'phone' => $rel->phone ?? '',
                'address_line_1' => $rel->address_line_1 ?? '',
                'address_line_2' => $rel->address_line_2 ?? '',
                'city' => $rel->city ?? '',
                'state' => $rel->state ?? '',
                'postal_code' => $rel->postal_code ?? '',
                'country' => $rel->country ?? (Setting::get('shipping_country', '') ?: config('app.country', 'India')),
            ];
        }

        $snap = $order->shipping_address_snapshot ?? $order->billing_address_snapshot ?? [];

        return [
            'first_name' => $snap['first_name'] ?? '',
            'last_name' => $snap['last_name'] ?? '',
            'phone' => $snap['phone'] ?? '',
            'address_line_1' => $snap['address_line_1'] ?? ($snap['address'] ?? ''),
            'address_line_2' => $snap['address_line_2'] ?? '',
            'city' => $snap['city'] ?? '',
            'state' => $snap['state'] ?? '',
            'postal_code' => $snap['postal_code'] ?? ($snap['pincode'] ?? ''),
            'country' => $snap['country'] ?? (Setting::get('shipping_country', '') ?: config('app.country', 'India')),
        ];
    }

    /**
     * Fetch order details from Shiprocket by order ID.
     */
    public function getOrder(string $orderId): ?array
    {
        if (!$this->token) {
            Log::warning('Shiprocket getOrder skipped: no auth token. Check shiprocket_email/shiprocket_password settings.', [
                'order_id' => $orderId,
            ]);
            return null;
        }

        try {
            $response = $this->api('GET', '/orders/show/' . urlencode($orderId));

            if ($response->successful()) {
                return $response->json('data') ?? $response->json();
            }

            Log::warning('Shiprocket getOrder: API returned non-success', [
                'order_id' => $orderId,
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 500),
            ]);
        } catch (\Exception $e) {
            Log::error('Shiprocket getOrder failed: ' . $e->getMessage(), [
                'order_id' => $orderId,
            ]);
        }

        return null;
    }

    /**
     * Track a shipment by AWB number.
     */
    public function trackShipment(string $awb): array
    {
        if (!$this->token) {
            return ['success' => false];
        }

        try {
            $response = $this->api('GET', "/courier/track/awb/{$awb}");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json('tracking_data', []),
                ];
            }
        } catch (\Exception $e) {
            Log::error('Shiprocket tracking failed: ' . $e->getMessage());
        }

        return ['success' => false];
    }

    /**
     * Generate shipping label for a shipment.
     */
    public function generateLabel(int $shipmentId): ?string
    {
        if (!$this->token) {
            return null;
        }

        try {
            $response = $this->api('POST', '/courier/generate/label', [
                'shipment_id' => [$shipmentId],
            ]);

            if ($response->successful()) {
                return $response->json('label_url');
            }
        } catch (\Exception $e) {
            Log::error('Shiprocket label generation failed: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Fetch order details from the Shiprocket Checkout API (checkout-dashboard.shiprocket.in).
     *
     * Uses X-Api-Key auth (same credentials as getCheckoutToken).
     * The order IDs from the Checkout SDK are hex strings (e.g. 69ea1cb3eee8cb18f8ae6ee0),
     * NOT numeric Shipping API IDs — so this method must be used instead of getOrder().
     */
    public function getCheckoutOrder(string $checkoutOrderId): ?array
    {
        $apiKey = $this->getCheckoutApiKey();

        if (empty($apiKey)) {
            Log::warning('ShiprocketService: getCheckoutOrder skipped — no Checkout API key configured.', [
                'order_id' => $checkoutOrderId,
            ]);
            return null;
        }

        // Try multiple possible Checkout API endpoints
        $endpoints = [
            "https://checkout-api.shiprocket.com/api/v1/orders/{$checkoutOrderId}",
            "https://checkout-api.shiprocket.com/api/v1/order/{$checkoutOrderId}",
            "https://checkout-api.shiprocket.com/api/v1/orders/show/{$checkoutOrderId}",
        ];

        foreach ($endpoints as $url) {
            try {
                $response = Http::withHeaders([
                    'X-Api-Key' => $apiKey,
                    'Accept' => 'application/json',
                ])->timeout(15)->get($url);

                if ($response->successful()) {
                    $data = $response->json();
                    // Extract customer details from whichever response shape Shiprocket returns
                    $result = $data['data'] ?? $data['result'] ?? $data['order'] ?? $data;

                    if (!empty($result) && is_array($result)) {
                        Log::info('ShiprocketService: getCheckoutOrder succeeded', [
                            'order_id' => $checkoutOrderId,
                            'endpoint' => $url,
                            'keys' => array_keys($result),
                        ]);
                        return $result;
                    }
                }

                Log::info('ShiprocketService: getCheckoutOrder endpoint returned no data', [
                    'order_id' => $checkoutOrderId,
                    'endpoint' => $url,
                    'status' => $response->status(),
                ]);
            } catch (\Exception $e) {
                Log::warning('ShiprocketService: getCheckoutOrder endpoint failed', [
                    'order_id' => $checkoutOrderId,
                    'endpoint' => $url,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return null;
    }

    /**
     * Fetch orders from the Shiprocket SHIPPING API (apiv2.shiprocket.in).
     *
     * Every Shiprocket Checkout order is automatically pushed to the Shipping platform,
     * which exposes full customer billing data (name / phone / email / address) that the
     * Checkout API does NOT expose.  Use this as a fallback when the Checkout API returns
     * no data for guest orders.
     *
     * Response shape: Shiprocket wraps pages as data.data[].
     * Each row includes: channel_order_id, billing_customer_name, billing_phone,
     * billing_email, billing_address, billing_city, billing_state, billing_pincode.
     *
     * @param  array  $filters  Optional filters: 'from' / 'to' (Y-m-d), 'search' string.
     * @return array|null  ['orders' => [...], 'total' => int] or null on failure.
     */
    public function getShippingOrders(int $page = 1, int $perPage = 100, array $filters = []): ?array
    {
        if (!$this->token) {
            Log::warning('ShiprocketService: getShippingOrders skipped — no Shipping API token.');
            return null;
        }

        try {
            $params = array_filter(array_merge([
                'page'     => $page,
                'per_page' => $perPage,
            ], $filters));

            $response = $this->api('GET', '/orders', $params);

            if ($response->successful()) {
                $json   = $response->json();
                // Shipping API paginates as { data: { data: [...], total: N } }
                $orders = $json['data']['data'] ?? $json['data'] ?? null;
                $total  = $json['data']['total'] ?? $json['total'] ?? 0;

                if (is_array($orders)) {
                    return ['orders' => $orders, 'total' => (int) $total];
                }
            }

            Log::warning('ShiprocketService: getShippingOrders non-success', [
                'page'   => $page,
                'status' => $response->status(),
                'body'   => substr($response->body(), 0, 300),
            ]);
        } catch (\Exception $e) {
            Log::error('ShiprocketService: getShippingOrders error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Find a Shiprocket Shipping API order by its Checkout hex ID.
     *
     * Checkout orders are auto-pushed to the Shipping platform with the checkout hex ID
     * stored as `channel_order_id`. The Shipping API's /orders endpoint supports searching
     * by this field, so we can retrieve full customer billing details (name, phone, email,
     * address) that the Checkout API does not expose.
     */
    public function findShippingOrderByCheckoutId(string $checkoutOrderId): ?array
    {
        if (!$this->token) {
            Log::warning('ShiprocketService: findShippingOrderByCheckoutId skipped — no Shipping API token.');
            return null;
        }

        try {
            $response = $this->api('GET', '/orders', [
                'search' => $checkoutOrderId,
                'per_page' => 5,
            ]);

            if ($response->successful()) {
                $json = $response->json();
                $orders = $json['data']['data'] ?? $json['data'] ?? [];

                if (is_array($orders)) {
                    foreach ($orders as $order) {
                        $channelId = $order['channel_order_id'] ?? '';
                        if (strcasecmp($channelId, $checkoutOrderId) === 0) {
                            Log::info('ShiprocketService: findShippingOrderByCheckoutId matched', [
                                'checkout_id' => $checkoutOrderId,
                                'shipping_order_id' => $order['id'] ?? null,
                            ]);
                            return $order;
                        }
                    }

                    // No exact match on channel_order_id — do NOT fall through to first result.
                    // Shiprocket's search may return unrelated orders and contaminate the wrong
                    // customer's shipping address (PIR: Prasanna order got Jitendra's address).
                    Log::info('ShiprocketService: findShippingOrderByCheckoutId no exact channel_order_id match', [
                        'checkout_id'    => $checkoutOrderId,
                        'results_count'  => count($orders),
                        'channel_ids'    => array_column($orders, 'channel_order_id'),
                    ]);
                }
            }

            Log::info('ShiprocketService: findShippingOrderByCheckoutId no results', [
                'checkout_id' => $checkoutOrderId,
                'status' => $response->status(),
            ]);
        } catch (\Exception $e) {
            Log::warning('ShiprocketService: findShippingOrderByCheckoutId failed', [
                'checkout_id' => $checkoutOrderId,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Fetch a paginated list of orders from the Shiprocket Checkout API.
     *
     * Used for bulk backfill — returns raw order rows from Shiprocket so we can
     * match them to our orders by shiprocket_order_id and fill in missing customer details.
     *
     * @return array|null  Array with keys 'orders' (array) and 'total' (int), or null on failure.
     */
    public function getCheckoutOrdersList(int $page = 1, int $perPage = 50): ?array
    {
        // The Shiprocket Checkout API (checkout-api.shiprocket.com) does NOT have
        // a list-orders endpoint — it always returns 404. Instead, we query our own
        // stored webhook events which contain full customer data.
        $events = ShiprocketCheckoutEvent::where('is_duplicate', false)
            ->whereNotNull('phone')
            ->orderByDesc('received_at')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        if ($events->isEmpty()) {
            return null;
        }

        // Group by cart_id and pick best event per session (prefer ones with name)
        $grouped = $events->groupBy('cart_id');
        $orders = [];
        foreach ($grouped as $cartId => $group) {
            $best = $group->first(fn ($e) => !empty($e->full_name)) ?? $group->first();
            $orders[] = [
                'order_id'              => $cartId,
                'name'                  => $best->full_name,
                'email'                 => $best->email,
                'phone'                 => $best->phone,
                'billing_address'       => $best->address_line_1,
                'billing_address_2'     => $best->address_line_2,
                'city'                  => $best->city,
                'state'                 => $best->state,
                'pincode'               => $best->pincode,
                'billing_country'       => $best->country,
            ];
        }

        return [
            'orders' => $orders,
            'total'  => ShiprocketCheckoutEvent::where('is_duplicate', false)->whereNotNull('phone')->distinct('cart_id')->count('cart_id'),
        ];
    }

    /**
     * Resolve the Checkout API key from settings (with legacy fallback).
     */
    private function getCheckoutApiKey(): ?string
    {
        $apiKey = Setting::get('shiprocket_checkout_api_key');

        if (empty($apiKey)) {
            $apiKey = Setting::get('api_key');
        }

        return $apiKey ?: null;
    }

    /**
     * Generate a Shiprocket Checkout token for the HeadlessCheckout SDK.
     *
     * Calls: POST https://checkout-api.shiprocket.com/api/v1/access-token/checkout
     * Auth: X-Api-Key + HMAC-SHA256 of request body signed with secret key.
     */
    public function getCheckoutToken(array $cartItems, string $redirectUrl): array
    {
        // Preferred Checkout-specific keys. Fall back to legacy generic keys so any
        // in-flight tenant using `api_key`/`api_secret` doesn't 500 — log deprecation
        // so we can migrate them off the generic names (tracked in reconcile PR).
        $apiKey = Setting::get('shiprocket_checkout_api_key');
        $secretKey = Setting::get('shiprocket_checkout_api_secret');

        if (empty($apiKey) || empty($secretKey)) {
            $legacyKey = Setting::get('api_key');
            $legacySecret = Setting::get('api_secret');
            if (!empty($legacyKey) && !empty($legacySecret)) {
                Log::warning('ShiprocketService: falling back to legacy generic Setting keys (api_key/api_secret). Please migrate to shiprocket_checkout_api_key/shiprocket_checkout_api_secret.', [
                    'tenant' => DB::connection()->getDatabaseName(),
                ]);
                $apiKey = $legacyKey;
                $secretKey = $legacySecret;
            }
        }

        if (empty($apiKey) || empty($secretKey)) {
            return ['success' => false, 'message' => 'Shiprocket Checkout not configured'];
        }

        $body = [
            'cart_data' => [
                'items' => $cartItems,
                'mobile_app' => false,
            ],
            'redirect_url' => $redirectUrl,
            'timestamp' => now()->toIso8601ZuluString(),
        ];

        $jsonBody = json_encode($body);
        $hmac = base64_encode(hash_hmac('sha256', $jsonBody, $secretKey, true));

        try {
            $response = Http::withHeaders([
                'X-Api-Key' => $apiKey,
                'X-Api-HMAC-SHA256' => $hmac,
                'Content-Type' => 'application/json',
            ])->withBody($jsonBody, 'application/json')
              ->timeout(15)
              ->post('https://checkout-api.shiprocket.com/api/v1/access-token/checkout');

            $data = $response->json();

            if ($response->successful() && ($data['ok'] ?? false)) {
                return [
                    'success' => true,
                    'token' => $data['result']['token'] ?? '',
                    'order_id' => $data['result']['data']['order_id'] ?? '',
                ];
            }

            Log::warning('Shiprocket Checkout token failed', ['response' => $data]);
            return ['success' => false, 'message' => $data['error'] ?? 'Token generation failed'];
        } catch (\Exception $e) {
            Log::error('Shiprocket Checkout token error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Service error'];
        }
    }

    /**
     * Make authenticated API call to Shiprocket.
     */
    private function api(string $method, string $endpoint, array $data = [])
    {
        $request = Http::withToken($this->token)->timeout(15);

        return match (strtoupper($method)) {
            'GET' => $request->get("{$this->baseUrl}{$endpoint}", $data),
            'POST' => $request->post("{$this->baseUrl}{$endpoint}", $data),
            'PUT' => $request->put("{$this->baseUrl}{$endpoint}", $data),
            default => $request->get("{$this->baseUrl}{$endpoint}", $data),
        };
    }
}
