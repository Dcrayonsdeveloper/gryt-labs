<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AnalyticsService
{
    /**
     * Get the tenant's configured currency code for analytics events.
     */
    private function getCurrency(): string
    {
        return Setting::get('currency', '') ?: config('app.currency', 'INR');
    }

    /**
     * Generate a unique event ID for deduplication between client-side pixel and server-side CAPI.
     */
    public static function generateEventId(string $prefix = 'evt'): string
    {
        return $prefix . '_' . Str::uuid()->toString();
    }

    // ─── Purchase ────────────────────────────────────────────────────────

    /**
     * @return string  Meta CAPI outcome: 'dispatched' (send queued; the order's
     *                 capi_* metadata is written from Facebook's actual response),
     *                 'skipped_no_config' (pixel/token not set — NOTHING was sent),
     *                 or 'skipped_excluded' (cancelled/test order).
     */
    public function trackPurchase(Order $order, ?Request $request = null, ?string $eventId = null, array $fbCookieFallback = [], string $source = 'unknown', ?int $eventTime = null, bool $fbOnly = false): string
    {
        // Never send Purchase events for cancelled/refunded/returned or test orders
        if (in_array($order->status, ['cancelled', 'refunded', 'returned'])) {
            return 'skipped_excluded';
        }
        $email = strtolower(trim($order->user?->email ?? $order->guest_email ?? ''));
        if ($email && (str_contains($email, 'test') || str_contains($email, '@example.'))) {
            return 'skipped_excluded';
        }

        // GA4/GAds have no event-id dedup, so guard them with their own once-flag —
        // callers' capi_sent_at guard no longer covers them (it stays empty when the
        // FB token is missing, and every success-page reload would re-send).
        // capi:backfill passes fbOnly (GA4 already fired at checkout time).
        if (! $fbOnly) {
            $meta = is_array($order->metadata) ? $order->metadata : [];
            if (empty($meta['ga_purchase_sent_at'])) {
                $this->sendGA4PurchaseEvent($order);
                $this->sendGAdsPurchaseEvent($order, $request);
                self::stampOrderMeta($order->id, ['ga_purchase_sent_at' => now()->toIso8601String()]);
            }
        }

        return $this->sendFBPurchaseEvent($order, $request, $eventId, $fbCookieFallback, $source, $eventTime);
    }

    /**
     * Merge keys into orders.metadata via a JSON-path UPDATE — atomic per key, so a
     * concurrent whole-metadata save (pricing sync, webhook) can't be clobbered by
     * a stale read-modify-write here. Falls back to initialising NULL metadata first
     * (JSON_SET over SQL NULL would return NULL and drop the update).
     */
    private static function stampOrderMeta(int $orderId, array $keys): void
    {
        try {
            Order::whereKey($orderId)->whereNull('metadata')->update(['metadata' => '{}']);
            Order::whereKey($orderId)->update(
                collect($keys)->mapWithKeys(fn ($v, $k) => ["metadata->{$k}" => $v])->all()
            );
        } catch (\Throwable $e) {
            Log::warning('stampOrderMeta failed', ['order_id' => $orderId, 'error' => $e->getMessage()]);
        }
    }

    // ─── ViewContent ─────────────────────────────────────────────────────

    public function trackViewContent(Product $product, ?Request $request = null, ?string $eventId = null): void
    {
        $this->sendFBCAPIEvent('ViewContent', [
            'content_ids' => [(string) $product->id],
            'content_name' => $product->name,
            'content_category' => $product->category?->name ?? '',
            'content_type' => 'product',
            'value' => (float) $product->price,
            'currency' => $this->getCurrency(),
        ], $request, auth()->user(), $eventId);
    }

    // ─── AddToCart ────────────────────────────────────────────────────────

    public function trackAddToCart(Product $product, int $quantity, ?Request $request = null, ?string $eventId = null): void
    {
        $this->sendFBCAPIEvent('AddToCart', [
            'content_ids' => [(string) $product->id],
            'content_name' => $product->name,
            'content_type' => 'product',
            'value' => (float) $product->price * $quantity,
            'currency' => $this->getCurrency(),
            'num_items' => $quantity,
        ], $request, auth()->user(), $eventId);
    }

    // ─── InitiateCheckout ────────────────────────────────────────────────

    public function trackInitiateCheckout(float $value, int $numItems, array $contentIds = [], ?Request $request = null, ?string $eventId = null): void
    {
        $this->sendFBCAPIEvent('InitiateCheckout', [
            'content_ids' => $contentIds,
            'content_type' => 'product',
            'value' => $value,
            'currency' => $this->getCurrency(),
            'num_items' => $numItems,
        ], $request, auth()->user(), $eventId);
    }

    // ─── AddPaymentInfo ──────────────────────────────────────────────────

    public function trackAddPaymentInfo(float $value, string $paymentMethod, ?Request $request = null, ?string $eventId = null): void
    {
        $this->sendFBCAPIEvent('AddPaymentInfo', [
            'value' => $value,
            'currency' => $this->getCurrency(),
            'content_category' => $paymentMethod,
        ], $request, auth()->user(), $eventId);
    }

    // ─── Search ──────────────────────────────────────────────────────────

    public function trackSearch(string $searchString, int $resultsCount = 0, ?Request $request = null, ?string $eventId = null): void
    {
        $this->sendFBCAPIEvent('Search', [
            'search_string' => $searchString,
            'content_type' => 'product',
            'contents' => [],
        ], $request, auth()->user(), $eventId);
    }

    // ─── AddToWishlist ───────────────────────────────────────────────────

    public function trackAddToWishlist(Product $product, ?Request $request = null, ?string $eventId = null): void
    {
        $this->sendFBCAPIEvent('AddToWishlist', [
            'content_ids' => [(string) $product->id],
            'content_name' => $product->name,
            'content_type' => 'product',
            'value' => (float) $product->price,
            'currency' => $this->getCurrency(),
        ], $request, auth()->user(), $eventId);
    }

    // ─── CompleteRegistration ────────────────────────────────────────────

    public function trackCompleteRegistration(?User $user = null, ?Request $request = null, ?string $eventId = null): void
    {
        $this->sendFBCAPIEvent('CompleteRegistration', [
            'content_name' => 'registration',
            'status' => true,
        ], $request, $user, $eventId);
    }

    // ─── Contact ─────────────────────────────────────────────────────────

    public function trackContact(?Request $request = null, ?string $eventId = null): void
    {
        $this->sendFBCAPIEvent('Contact', [
            'content_name' => 'contact_form',
        ], $request, auth()->user(), $eventId);
    }

    // ─── Subscribe ───────────────────────────────────────────────────────

    public function trackSubscribe(string $email, ?Request $request = null, ?string $eventId = null): void
    {
        $userData = [
            'em' => [hash('sha256', strtolower(trim($email)))],
        ];

        $this->sendFBCAPIEvent('Subscribe', [
            'content_name' => 'newsletter',
        ], $request, null, $eventId, $userData);
    }

    // ─── Generic event ───────────────────────────────────────────────────

    public function trackEvent(string $event, array $params = [], ?User $user = null, ?Request $request = null, ?string $eventId = null): void
    {
        $this->sendGA4Event($event, $params, $user);
        $this->sendFBCAPIEvent($event, $params, $request, $user, $eventId);
    }

    // =====================================================================
    // Facebook Conversions API (CAPI)
    // =====================================================================

    private function sendFBCAPIEvent(string $eventName, array $customData, ?Request $request = null, ?User $user = null, ?string $eventId = null, array $extraUserData = []): void
    {
        $pixelId = Setting::get('facebook_pixel_id');
        $accessToken = Setting::get('facebook_capi_token') ?: Setting::get('facebook_capi_access_token');

        if (!$pixelId || !$accessToken) {
            return;
        }

        // Build user data with hashing
        $userData = $this->buildUserData($request, $user);
        $userData = array_merge($userData, $extraUserData);

        $event = [
            'event_name' => $eventName,
            'event_time' => now()->timestamp,
            'action_source' => 'website',
            'event_source_url' => $request?->fullUrl() ?? config('app.url'),
            'user_data' => $userData,
            'custom_data' => $customData,
        ];

        if ($eventId) {
            $event['event_id'] = $eventId;
        }

        $payload = ['data' => [$event]];

        // Test mode: cookie-based (set via ?fb_capi_test=CODE URL param).
        // Only test sessions get the test_event_code — real visitors' conversions
        // are never affected. This replaces the old Setting-based approach which
        // poisoned ALL events (including real purchases) with the test code.
        $testCode = $request?->cookie('fb_capi_test');
        if ($testCode) {
            $payload['test_event_code'] = $testCode;
        }

        dispatch(function () use ($pixelId, $accessToken, $payload, $eventName) {
            try {
                $response = Http::timeout(5)->post(
                    "https://graph.facebook.com/v22.0/{$pixelId}/events?access_token={$accessToken}",
                    $payload
                );

                if (!$response->successful()) {
                    Log::warning("Facebook CAPI '{$eventName}' rejected", [
                        'status' => $response->status(),
                        'body' => $response->body(),
                        'pixel_id' => $pixelId,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning("Facebook CAPI '{$eventName}' failed", ['error' => $e->getMessage()]);
            }
        })->afterResponse();
    }

    private function sendFBPurchaseEvent(Order $order, ?Request $request = null, ?string $eventId = null, array $fbCookieFallback = [], string $source = 'unknown', ?int $eventTime = null): string
    {
        $pixelId = Setting::get('facebook_pixel_id');
        $accessToken = Setting::get('facebook_capi_token') ?: Setting::get('facebook_capi_access_token');

        if (!$pixelId || !$accessToken) {
            // Loud, not silent: this is the money event — a missing token used to be
            // invisible (orders were even stamped "sent"). Callers surface this state.
            Log::info('Facebook CAPI Purchase skipped — pixel/token not configured', [
                'order' => $order->order_number,
                'missing' => !$pixelId ? 'facebook_pixel_id' : 'facebook_capi_token',
            ]);

            return 'skipped_no_config';
        }

        $user = $order->user;
        $contents = $order->items->map(fn ($item) => [
            'id' => (string) $item->product_id,
            'quantity' => $item->quantity,
            'item_price' => (float) $item->price,
        ])->toArray();

        $userData = $this->buildUserData($request, $user, $fbCookieFallback);

        // Add order-level user data (guest orders)
        if (!$user && $order->guest_email) {
            $userData['em'] = [hash('sha256', strtolower(trim($order->guest_email)))];
        }
        if (!$user && $order->guest_phone) {
            $userData['ph'] = [hash('sha256', preg_replace('/\D/', '', $order->guest_phone))];
        }

        $event = [
            'event_name' => 'Purchase',
            // Backfill passes the order's real placement time (Meta accepts ≤7 days old)
            'event_time' => $eventTime ?? now()->timestamp,
            'action_source' => 'website',
            'event_source_url' => $request?->fullUrl() ?? config('app.url'),
            'user_data' => $userData,
            'custom_data' => [
                'currency' => $this->getCurrency(),
                'value' => (float) $order->total,
                'content_type' => 'product',
                'contents' => $contents,
                'content_ids' => $order->items->pluck('product_id')->map(fn ($id) => (string) $id)->toArray(),
                'order_id' => $order->order_number,
                'num_items' => $order->items->sum('quantity'),
            ],
        ];

        if ($eventId) {
            $event['event_id'] = $eventId;
        }

        $payload = ['data' => [$event]];

        // Cookie-based test mode (same as sendFBCAPIEvent)
        $testCode = $request?->cookie('fb_capi_test');
        if ($testCode) {
            $payload['test_event_code'] = $testCode;
        }

        $orderId = $order->id;
        $finalEventId = $eventId;

        // Claim the send NOW (before the deferred HTTP call) so a concurrent
        // webhook/callback/success-page hit in the same seconds-wide window doesn't
        // also dispatch: their capi_sent_at guard can't see a stamp that is only
        // written after the response. Meta's event_id dedup is the backstop, but a
        // claim avoids the duplicate request entirely. 5-minute TTL so a crashed
        // terminate phase can be retried by capi:backfill.
        $claim = Cache::add("capi_purchase_claim.{$orderId}", $source, 300);
        if (! $claim) {
            Log::info('Facebook CAPI Purchase already in flight — skipping duplicate', [
                'order_id' => $orderId,
                'source'   => $source,
            ]);

            return 'skipped_in_flight';
        }

        $send = function () use ($pixelId, $accessToken, $payload, $orderId, $finalEventId, $source) {
            $stamp = fn (array $keys) => self::stampOrderMeta($orderId, $keys);
            // The token rides in the Graph URL, so cURL exception messages can embed
            // it — scrub before anything is stored on the order or logged.
            $scrub = fn (string $s) => str_replace($accessToken, '***', $s);

            try {
                $response = Http::timeout(5)->post(
                    "https://graph.facebook.com/v22.0/{$pixelId}/events?access_token={$accessToken}",
                    $payload
                );

                if ($response->successful()) {
                    // Only NOW is the event truly delivered — stamp with Facebook's receipt.
                    $stamp([
                        'capi_sent_at'         => now()->toIso8601String(),
                        'capi_source'          => $source,
                        'fb_event_id'          => $finalEventId,
                        'capi_events_received' => (int) $response->json('events_received', 0),
                        'capi_fbtrace_id'      => $response->json('fbtrace_id'),
                        'capi_error'           => null,
                        'capi_error_at'        => null,
                    ]);
                } else {
                    $stamp([
                        'capi_error'    => $scrub('HTTP ' . $response->status() . ': ' . mb_substr((string) $response->body(), 0, 300)),
                        'capi_error_at' => now()->toIso8601String(),
                    ]);
                    Log::warning('Facebook CAPI Purchase rejected', [
                        'order_id' => $orderId,
                        'status' => $response->status(),
                        'body' => $scrub((string) $response->body()),
                        'pixel_id' => $pixelId,
                    ]);
                }
            } catch (\Throwable $e) {
                $stamp([
                    'capi_error'    => $scrub(mb_substr($e->getMessage(), 0, 300)),
                    'capi_error_at' => now()->toIso8601String(),
                ]);
                Log::warning('Facebook CAPI Purchase failed', ['order_id' => $orderId, 'error' => $scrub($e->getMessage())]);
            } finally {
                // Release the claim: a failed send must stay retryable by capi:backfill.
                Cache::forget("capi_purchase_claim.{$orderId}");
            }
        };

        if (app()->runningInConsole()) {
            // capi:backfill needs the result immediately (it reports per order);
            // there's no HTTP response to defer behind in console anyway.
            $send();
        } else {
            // Never slow checkout: run after the response is sent to the customer.
            dispatch($send)->afterResponse();
        }

        return 'dispatched';
    }

    // =====================================================================
    // Google Ads Enhanced Conversions (server-side)
    // =====================================================================

    private function sendGAdsPurchaseEvent(Order $order, ?Request $request = null): void
    {
        $conversionId = Setting::get('google_ads_conversion_id');
        $conversionLabel = Setting::get('google_ads_conversion_label');

        if (!$conversionId || !$conversionLabel) {
            return;
        }

        // Google Ads enhanced conversions are sent via gtag on the client side
        // Server-side upload requires Google Ads API with offline conversion import
        // For now we ensure the client-side tag fires correctly by passing data
        // The actual server-side Google Ads conversion upload requires OAuth + Google Ads API
        // which is handled separately via the GoogleAdsService if needed

        Log::info('Google Ads purchase conversion tracked', [
            'order' => $order->order_number,
            'value' => $order->total,
            'conversion_id' => $conversionId,
        ]);
    }

    /**
     * Build hashed user_data for CAPI from request + user model.
     */
    private function buildUserData(?Request $request, ?User $user, array $fbCookieFallback = []): array
    {
        $data = [];

        // User model data
        if ($user) {
            $data['em'] = [hash('sha256', strtolower(trim($user->email)))];
            if ($user->phone) {
                $data['ph'] = [hash('sha256', preg_replace('/\D/', '', $user->phone))];
            }
            if ($user->first_name) {
                $data['fn'] = [hash('sha256', strtolower(trim($user->first_name)))];
            }
            if ($user->last_name) {
                $data['ln'] = [hash('sha256', strtolower(trim($user->last_name)))];
            }
            $data['external_id'] = [hash('sha256', (string) $user->id)];
        }

        // Request data
        if ($request) {
            $data['client_ip_address'] = $request->ip();
            $data['client_user_agent'] = $request->userAgent();

            // Facebook browser cookies for matching
            $fbp = $request->cookie('_fbp');
            if ($fbp) {
                $data['fbp'] = $fbp;
            }
            $fbc = $request->cookie('_fbc');
            if ($fbc) {
                $data['fbc'] = $fbc;
            }
        }

        // Fallback: use stored fbc/fbp from abandoned checkout when cookies are missing
        // (Shiprocket cross-domain redirect or mobile browsers may lose cookies)
        if (empty($data['fbc']) && !empty($fbCookieFallback['fbc'])) {
            $data['fbc'] = $fbCookieFallback['fbc'];
        }
        if (empty($data['fbp']) && !empty($fbCookieFallback['fbp'])) {
            $data['fbp'] = $fbCookieFallback['fbp'];
        }

        $countryCode = strtolower(Setting::get('country_code', '') ?: config('app.country_code', 'in'));
        $data['country'] = [hash('sha256', $countryCode)];

        return $data;
    }

    // =====================================================================
    // Google Analytics 4 (Measurement Protocol)
    // =====================================================================

    private function sendGA4PurchaseEvent(Order $order): void
    {
        $measurementId = Setting::get('google_analytics_id');
        $apiSecret = Setting::get('ga4_api_secret');

        if (!$measurementId || !$apiSecret) {
            return;
        }

        $items = $order->items->map(fn ($item) => [
            'item_id' => $item->sku ?? (string) $item->product_id,
            'item_name' => $item->product_name,
            'price' => (float) $item->price,
            'quantity' => $item->quantity,
        ])->toArray();

        $payload = [
            'client_id' => 'server.' . $order->user_id,
            'events' => [[
                'name' => 'purchase',
                'params' => [
                    'transaction_id' => $order->order_number,
                    'value' => (float) $order->total,
                    'currency' => $this->getCurrency(),
                    'tax' => (float) $order->tax,
                    'shipping' => (float) $order->shipping_cost,
                    'items' => $items,
                ],
            ]],
        ];

        dispatch(function () use ($measurementId, $apiSecret, $payload) {
            try {
                Http::post("https://www.google-analytics.com/mp/collect?measurement_id={$measurementId}&api_secret={$apiSecret}", $payload);
            } catch (\Throwable $e) {
                Log::warning('GA4 Measurement Protocol failed', ['error' => $e->getMessage()]);
            }
        })->afterResponse();
    }

    private function sendGA4Event(string $eventName, array $params, ?User $user): void
    {
        $measurementId = Setting::get('google_analytics_id');
        $apiSecret = Setting::get('ga4_api_secret');

        if (!$measurementId || !$apiSecret) {
            return;
        }

        $clientId = $user ? 'server.' . $user->id : 'server.' . uniqid();

        $payload = [
            'client_id' => $clientId,
            'events' => [[
                'name' => $eventName,
                'params' => $params,
            ]],
        ];

        dispatch(function () use ($measurementId, $apiSecret, $payload, $eventName) {
            try {
                Http::post("https://www.google-analytics.com/mp/collect?measurement_id={$measurementId}&api_secret={$apiSecret}", $payload);
            } catch (\Throwable $e) {
                Log::warning("GA4 event '{$eventName}' failed", ['error' => $e->getMessage()]);
            }
        })->afterResponse();
    }
}
