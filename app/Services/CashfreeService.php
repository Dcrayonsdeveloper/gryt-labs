<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CashfreeService
{
    private string $appId;
    private string $secretKey;
    private string $mode; // 'production' or 'sandbox'
    private string $apiVersion = '2023-08-01';

    public function __construct()
    {
        $this->appId = (string) Setting::get('cashfree_app_id', '');
        $this->secretKey = (string) Setting::get('cashfree_secret_key', '');
        $this->mode = (string) (Setting::get('cashfree_mode', '') ?: 'production');
    }

    public function isConfigured(): bool
    {
        return !empty($this->appId) && !empty($this->secretKey);
    }

    public function baseUrl(): string
    {
        return $this->mode === 'sandbox'
            ? 'https://sandbox.cashfree.com/pg'
            : 'https://api.cashfree.com/pg';
    }

    private function headers(): array
    {
        return [
            'x-api-version' => $this->apiVersion,
            'x-client-id' => $this->appId,
            'x-client-secret' => $this->secretKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    /**
     * Create a Cashfree order. Returns the API response array (with payment_session_id) or null on failure.
     */
    public function createOrder(array $payload): ?array
    {
        try {
            $response = Http::timeout(20)->withHeaders($this->headers())
                ->post($this->baseUrl() . '/orders', $payload);

            if (!$response->successful()) {
                Log::error('Cashfree order creation failed', [
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);
                return null;
            }

            return $response->json();
        } catch (\Throwable $e) {
            Log::error('Cashfree createOrder exception', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Fetch order details from Cashfree (used to verify payment status server-side).
     */
    public function getOrder(string $orderId): ?array
    {
        try {
            $response = Http::timeout(15)->withHeaders($this->headers())
                ->get($this->baseUrl() . '/orders/' . urlencode($orderId));

            if (!$response->successful()) {
                Log::warning('Cashfree getOrder failed', [
                    'order_id' => $orderId,
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);
                return null;
            }

            return $response->json();
        } catch (\Throwable $e) {
            Log::error('Cashfree getOrder exception', ['order_id' => $orderId, 'message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Fetch payments for an order.
     */
    public function getOrderPayments(string $orderId): ?array
    {
        try {
            $response = Http::timeout(15)->withHeaders($this->headers())
                ->get($this->baseUrl() . '/orders/' . urlencode($orderId) . '/payments');

            if (!$response->successful()) {
                Log::warning('Cashfree getOrderPayments failed', [
                    'order_id' => $orderId,
                    'status' => $response->status(),
                ]);
                return null;
            }

            return $response->json();
        } catch (\Throwable $e) {
            Log::error('Cashfree getOrderPayments exception', ['order_id' => $orderId, 'message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * List orders from Cashfree with optional filters.
     * See: https://docs.cashfree.com/reference/getorders
     */
    public function listOrders(array $params = []): ?array
    {
        try {
            $response = Http::timeout(30)->withHeaders($this->headers())
                ->get($this->baseUrl() . '/orders', $params);

            if (!$response->successful()) {
                Log::warning('Cashfree listOrders failed', [
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);
                return null;
            }

            return $response->json();
        } catch (\Throwable $e) {
            Log::error('Cashfree listOrders exception', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Verify webhook signature.
     * Cashfree sends `x-webhook-signature` header (base64 HMAC-SHA256 of timestamp+rawBody using secret_key).
     */
    public function verifyWebhookSignature(string $rawBody, string $timestamp, string $signature): bool
    {
        if (empty($this->secretKey) || empty($signature)) {
            return false;
        }
        $payload = $timestamp . $rawBody;
        $expected = base64_encode(hash_hmac('sha256', $payload, $this->secretKey, true));
        return hash_equals($expected, $signature);
    }
}
