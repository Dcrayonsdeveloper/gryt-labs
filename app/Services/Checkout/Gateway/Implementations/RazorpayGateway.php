<?php

namespace App\Services\Checkout\Gateway\Implementations;

use App\Models\Payment;
use App\Models\Setting;
use App\Services\Checkout\Gateway\CheckoutSessionData;
use App\Services\Checkout\Gateway\Money;
use App\Services\Checkout\Gateway\PaymentAttemptResult;
use App\Services\Checkout\Gateway\PaymentGatewayInterface;
use App\Services\Checkout\Gateway\RefundResult;
use App\Services\Checkout\Gateway\VerificationResult;
use App\Services\Checkout\Gateway\WebhookResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Path A Phase 1 Day 3: Razorpay gateway implementation.
 *
 * Credentials resolve from the tenant's `settings` table (razorpay_key_id /
 * razorpay_key_secret / razorpay_webhook_secret), so this class is safe to
 * construct per-request inside a multi-tenant process. No static state.
 *
 * Scope discipline — this wrapper owns **only** the Razorpay-specific API
 * interactions (create order, fetch payment, verify signature, map webhook
 * events, issue refund). Cart loading, totals calculation, Order creation,
 * and session persistence stay in {@see \App\Http\Controllers\CheckoutController}
 * until the orchestrator lands in Phase 2. By Day 5 the controller's
 * `createRazorpayOrder` / `verifyRazorpayPayment` methods delegate to this
 * class for the gateway-side work; the cart/totals/Order-create logic is
 * left in place.
 */
class RazorpayGateway implements PaymentGatewayInterface
{
    private const API_BASE = 'https://api.razorpay.com/v1';

    public function slug(): string
    {
        return 'razorpay';
    }

    public function supportsPartialCapture(): bool
    {
        // Razorpay captures the exact amount you ask for. Partial COD (advance
        // only) is modelled by the separate PartialCODGateway, not here.
        return false;
    }

    public function createPaymentAttempt(CheckoutSessionData $session, Money $amount): PaymentAttemptResult
    {
        $key = (string) Setting::get('razorpay_key_id', '');
        $secret = (string) Setting::get('razorpay_key_secret', '');

        if (empty($key) || empty($secret)) {
            return PaymentAttemptResult::failure('Razorpay is not configured for this tenant.');
        }

        if ($amount->isZero()) {
            return PaymentAttemptResult::failure('Cannot create a Razorpay order for zero amount.');
        }

        try {
            $response = Http::timeout(10)->retry(2, 500)
                ->withBasicAuth($key, $secret)
                ->post(self::API_BASE . '/orders', [
                    'amount' => $amount->minor,
                    'currency' => $amount->currency,
                    'receipt' => 'cart_' . $session->cartId . '_' . time(),
                    'notes' => array_filter([
                        'cart_id' => (string) $session->cartId,
                        'user_id' => $session->userId !== null ? (string) $session->userId : 'guest',
                        'checkout_token' => $session->token,
                    ]),
                ]);
        } catch (\Throwable $e) {
            Log::error('Razorpay createPaymentAttempt exception', [
                'cart_id' => $session->cartId,
                'error' => $e->getMessage(),
            ]);
            return PaymentAttemptResult::failure('Razorpay API unreachable: ' . $e->getMessage());
        }

        if (!$response->successful()) {
            Log::error('Razorpay createPaymentAttempt non-2xx', [
                'cart_id' => $session->cartId,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
            return PaymentAttemptResult::failure('Razorpay returned ' . $response->status() . ' on order create.');
        }

        $data = $response->json();
        $orderId = $data['id'] ?? null;
        if (!$orderId) {
            return PaymentAttemptResult::failure('Razorpay response missing order id.');
        }

        return PaymentAttemptResult::success(
            externalId: $orderId,
            clientPayload: [
                // Shape matches what the existing frontend reads — keeps Day 5 a
                // one-line swap with no JS changes.
                'order_id' => $orderId,
                'amount' => $amount->minor,
                'currency' => $amount->currency,
                'key' => $key,
                'receipt' => $data['receipt'] ?? null,
            ],
        );
    }

    public function verifyPayment(string $externalId, array $payload): VerificationResult
    {
        $secret = (string) Setting::get('razorpay_key_secret', '');
        $key = (string) Setting::get('razorpay_key_id', '');

        if (empty($secret) || empty($key)) {
            return VerificationResult::failure('Razorpay is not configured for this tenant.');
        }

        $paymentId = (string) ($payload['razorpay_payment_id'] ?? '');
        $signature = (string) ($payload['razorpay_signature'] ?? '');
        $orderIdInPayload = (string) ($payload['razorpay_order_id'] ?? $externalId);

        if ($orderIdInPayload !== $externalId) {
            return VerificationResult::failure('razorpay_order_id mismatch between URL and payload.');
        }
        if ($paymentId === '' || $signature === '') {
            return VerificationResult::failure('Missing razorpay_payment_id or razorpay_signature.');
        }

        $expectedSignature = hash_hmac('sha256', $externalId . '|' . $paymentId, $secret);
        if (!hash_equals($expectedSignature, $signature)) {
            return VerificationResult::failure('Razorpay signature mismatch.');
        }

        // Fetch the payment directly from Razorpay to read the captured amount
        // and status — don't trust the client's claim alone. This is the same
        // defence the existing verifyRazorpayPayment does.
        try {
            $response = Http::timeout(10)->retry(2, 500)
                ->withBasicAuth($key, $secret)
                ->get(self::API_BASE . '/payments/' . urlencode($paymentId));
        } catch (\Throwable $e) {
            return VerificationResult::failure('Razorpay payment fetch failed: ' . $e->getMessage());
        }

        if (!$response->successful()) {
            return VerificationResult::failure('Razorpay returned ' . $response->status() . ' on payment fetch.');
        }

        $data = $response->json();
        $status = $data['status'] ?? '';
        if (!in_array($status, ['authorized', 'captured'], true)) {
            return VerificationResult::failure("Payment status is '{$status}', expected 'authorized' or 'captured'.");
        }

        return VerificationResult::captured(
            externalPaymentId: $paymentId,
            amount: Money::fromMinor((int) ($data['amount'] ?? 0), (string) ($data['currency'] ?? 'INR')),
            rawResponse: $data,
        );
    }

    public function handleWebhook(Request $request): WebhookResult
    {
        $rawEvent = (string) $request->input('event', '');
        $mapped = self::mapEvent($rawEvent);

        if ($mapped === null) {
            return WebhookResult::ignored("Unmapped Razorpay event: {$rawEvent}");
        }

        // Phase 1 keeps the existing webhook controller as the load-bearing
        // processor (it already handles payment.captured with the COD-advance
        // revenue-leak fix from PR #18). This method returns a classification
        // result so callers can metric/log consistently across gateways.
        // Phase 3's orchestrator moves the actual processing here.
        return WebhookResult::handled($mapped, null, $request->all());
    }

    public function refund(Payment $payment, Money $amount, string $reason): RefundResult
    {
        $key = (string) Setting::get('razorpay_key_id', '');
        $secret = (string) Setting::get('razorpay_key_secret', '');

        if (empty($key) || empty($secret)) {
            return RefundResult::failure('Razorpay is not configured for this tenant.');
        }

        $paymentId = (string) ($payment->gateway_transaction_id ?? $payment->transaction_id ?? '');
        if ($paymentId === '') {
            return RefundResult::failure('Payment has no Razorpay transaction id to refund against.');
        }

        try {
            $response = Http::timeout(15)->retry(2, 500)
                ->withBasicAuth($key, $secret)
                ->post(self::API_BASE . '/payments/' . urlencode($paymentId) . '/refund', [
                    'amount' => $amount->minor,
                    'notes' => ['reason' => $reason],
                ]);
        } catch (\Throwable $e) {
            return RefundResult::failure('Razorpay refund exception: ' . $e->getMessage());
        }

        if (!$response->successful()) {
            return RefundResult::failure('Razorpay returned ' . $response->status() . ' on refund.');
        }

        $data = $response->json();
        $refundId = $data['id'] ?? null;
        if (!$refundId) {
            return RefundResult::failure('Razorpay refund response missing id.');
        }

        return RefundResult::success(
            externalRefundId: $refundId,
            amount: Money::fromMinor(
                (int) ($data['amount'] ?? $amount->minor),
                (string) ($data['currency'] ?? $amount->currency),
            ),
        );
    }

    /**
     * Map Razorpay's native event names to the orchestrator's stable vocabulary.
     * Keeping this as a method (not a private const) because some events
     * share a normalised tag (refund.processed + refund.created).
     */
    public static function mapEvent(string $razorpayEvent): ?string
    {
        return match (true) {
            $razorpayEvent === 'payment.authorized' => 'payment.authorized',
            $razorpayEvent === 'payment.captured' => 'payment.captured',
            $razorpayEvent === 'payment.failed' => 'payment.failed',
            $razorpayEvent === 'order.paid' => 'order.paid',
            $razorpayEvent === 'refund.processed',
            $razorpayEvent === 'refund.created' => 'refund.processed',
            default => null,
        };
    }
}
