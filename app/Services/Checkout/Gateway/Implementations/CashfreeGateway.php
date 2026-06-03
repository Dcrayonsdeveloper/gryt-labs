<?php

namespace App\Services\Checkout\Gateway\Implementations;

use App\Models\Payment;
use App\Models\Setting;
use App\Services\CashfreeService;
use App\Services\Checkout\Gateway\CheckoutSessionData;
use App\Services\Checkout\Gateway\Money;
use App\Services\Checkout\Gateway\PaymentAttemptResult;
use App\Services\Checkout\Gateway\PaymentGatewayInterface;
use App\Services\Checkout\Gateway\RefundResult;
use App\Services\Checkout\Gateway\VerificationResult;
use App\Services\Checkout\Gateway\WebhookResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Path A Phase 1 Day 4: Cashfree gateway implementation.
 *
 * Delegates the actual Cashfree API calls to the existing CashfreeService
 * (which knows about sandbox/production base URLs, the x-api-version header,
 * and the HMAC signature scheme). This class owns the orchestrator-facing
 * contract; CashfreeService owns the low-level HTTP.
 *
 * Like RazorpayGateway, credentials are resolved per-request from the
 * tenant's `settings` table — CashfreeService's constructor reads them via
 * Setting::get(). Safe to construct per-request with no static state.
 */
class CashfreeGateway implements PaymentGatewayInterface
{
    public function __construct(private readonly CashfreeService $cashfree)
    {
    }

    public function slug(): string
    {
        return 'cashfree';
    }

    public function supportsPartialCapture(): bool
    {
        return false;
    }

    public function createPaymentAttempt(CheckoutSessionData $session, Money $amount): PaymentAttemptResult
    {
        if (!$this->cashfree->isConfigured()) {
            return PaymentAttemptResult::failure('Cashfree is not configured for this tenant.');
        }
        if ($amount->isZero()) {
            return PaymentAttemptResult::failure('Cannot create a Cashfree order for zero amount.');
        }

        $contactPhone = $session->contactPhone ?? '';
        $contactEmail = $session->contactEmail ?? '';
        if ($contactEmail === '') {
            // Cashfree requires an email — synthesize a disposable one for guests
            // who didn't provide one. Matches the behaviour in CheckoutController.
            $contactEmail = 'guest_' . substr(md5($contactPhone . $session->cartId), 0, 10) . '@noreply.local';
        }

        $cfOrderId = 'CART_' . $session->cartId . '_' . time();
        $customerId = $session->userId !== null
            ? 'user_' . $session->userId
            : 'guest_' . substr(md5($contactPhone), 0, 12);

        $payload = [
            'order_id' => $cfOrderId,
            'order_amount' => $amount->major(),
            'order_currency' => $amount->currency,
            'customer_details' => [
                'customer_id' => $customerId,
                'customer_name' => $session->contactName ?? 'Customer',
                'customer_email' => $contactEmail,
                'customer_phone' => $contactPhone,
            ],
            'order_meta' => [
                'return_url' => route('checkout.cashfree.return') . '?cf_order_id={order_id}',
                'notify_url' => route('checkout.cashfree.webhook'),
            ],
            'order_note' => 'Order from ' . Setting::get('store_name', config('app.name')),
        ];

        $response = $this->cashfree->createOrder($payload);
        if (!$response || empty($response['payment_session_id'])) {
            return PaymentAttemptResult::failure('Cashfree order creation failed.');
        }

        return PaymentAttemptResult::success(
            externalId: $cfOrderId,
            clientPayload: [
                'payment_session_id' => $response['payment_session_id'],
                'cf_order_id' => $cfOrderId,
                'mode' => (string) Setting::get('cashfree_mode', 'production'),
                'payment_link' => $response['payment_link'] ?? null,
            ],
        );
    }

    public function verifyPayment(string $externalId, array $payload): VerificationResult
    {
        if (!$this->cashfree->isConfigured()) {
            return VerificationResult::failure('Cashfree is not configured for this tenant.');
        }

        $cfOrder = $this->cashfree->getOrder($externalId);
        if (!$cfOrder) {
            return VerificationResult::failure('Cashfree getOrder returned nothing.');
        }

        $status = (string) ($cfOrder['order_status'] ?? '');
        if ($status !== 'PAID') {
            return VerificationResult::failure("Cashfree order status is '{$status}', expected 'PAID'.");
        }

        $payments = $this->cashfree->getOrderPayments($externalId);
        $firstPayment = $payments[0] ?? null;
        $cfPaymentId = $firstPayment['cf_payment_id'] ?? null;

        // Prefer the actual captured amount from the payments endpoint; fall
        // back to the order's order_amount if Cashfree is late publishing.
        $amountMajor = $firstPayment['payment_amount']
            ?? $cfOrder['order_amount']
            ?? 0;
        $currency = (string) ($cfOrder['order_currency'] ?? 'INR');

        return VerificationResult::captured(
            externalPaymentId: (string) $cfPaymentId,
            amount: Money::fromMajor((float) $amountMajor, $currency),
            rawResponse: ['order' => $cfOrder, 'payments' => $payments],
        );
    }

    public function handleWebhook(Request $request): WebhookResult
    {
        $rawBody = $request->getContent();
        $timestamp = (string) $request->header('x-webhook-timestamp', '');
        $signature = (string) $request->header('x-webhook-signature', '');

        if (!$this->cashfree->verifyWebhookSignature($rawBody, $timestamp, $signature)) {
            Log::warning('CashfreeGateway webhook signature invalid');
            return WebhookResult::failure('Invalid Cashfree webhook signature.');
        }

        $payload = json_decode($rawBody, true) ?: $request->all();
        $rawEvent = (string) ($payload['type'] ?? '');
        $mapped = self::mapEvent($rawEvent);

        if ($mapped === null) {
            return WebhookResult::ignored("Unmapped Cashfree event: {$rawEvent}", $payload);
        }

        // Phase 1 keeps the existing CheckoutController::cashfreeWebhook as
        // the load-bearing processor (including the PR #21 PendingCheckout
        // recovery path). This method returns classification for metrics.
        return WebhookResult::handled($mapped, null, $payload);
    }

    public function refund(Payment $payment, Money $amount, string $reason): RefundResult
    {
        // Cashfree refund API requires a refund_id + order_id + amount. The
        // existing admin-side refund flow doesn't go through this method yet;
        // we ship a structural stub so the interface is complete and return
        // a clear failure until Phase 5 wires the admin refund path through
        // the registry. Intentionally not calling Cashfree's API here without
        // the admin path in place — avoids a half-wired refund path.
        return RefundResult::failure(
            'Cashfree refund not yet routed through the registry — admin refund UI still uses the legacy path. Phase 5 completes the wiring.'
        );
    }

    /**
     * Map Cashfree's native webhook event names to the orchestrator's stable
     * vocabulary (same strings the other gateways emit so aggregations work
     * across gateways without a gateway-specific dictionary).
     */
    public static function mapEvent(string $cashfreeEvent): ?string
    {
        return match ($cashfreeEvent) {
            'PAYMENT_SUCCESS_WEBHOOK' => 'payment.captured',
            'PAYMENT_FAILED_WEBHOOK' => 'payment.failed',
            'PAYMENT_USER_DROPPED_WEBHOOK' => 'payment.failed',
            'REFUND_STATUS_WEBHOOK' => 'refund.processed',
            default => null,
        };
    }
}
