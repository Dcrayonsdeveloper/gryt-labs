<?php

namespace App\Services\Checkout\Gateway\Implementations;

use App\Models\Payment;
use App\Services\Checkout\Gateway\CheckoutSessionData;
use App\Services\Checkout\Gateway\Money;
use App\Services\Checkout\Gateway\PaymentAttemptResult;
use App\Services\Checkout\Gateway\PaymentGatewayInterface;
use App\Services\Checkout\Gateway\RefundResult;
use App\Services\Checkout\Gateway\VerificationResult;
use App\Services\Checkout\Gateway\WebhookResult;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Path A Phase 1 Day 4: Cash-on-Delivery gateway.
 *
 * No external API. `createPaymentAttempt` synthesises a local attempt id
 * so COD orders flow through the same code path as online orders (one
 * payment_attempts row per checkout attempt — the ledger consistency
 * property Track 2 flagged as Shopify-parity).
 *
 * `verifyPayment` is a no-op that always confirms (captured=true) because
 * there is nothing to verify — the contract with the customer is "pay the
 * courier". The `paid_amount` on the Order will be 0 at create time; the
 * delivery team marks it paid when they collect cash, via the existing
 * admin "mark delivered" flow.
 */
class CODGateway implements PaymentGatewayInterface
{
    public function slug(): string
    {
        return 'cod';
    }

    public function supportsPartialCapture(): bool
    {
        // Pure COD is all-or-nothing — the partial-advance model lives in
        // PartialCODGateway which IS allowed to partial-capture.
        return false;
    }

    public function createPaymentAttempt(CheckoutSessionData $session, Money $amount): PaymentAttemptResult
    {
        // Local "attempt id" — deterministic within a checkout session so
        // the orchestrator can idempotently identify this attempt on retry.
        $attemptId = 'cod_' . $session->token . '_' . Str::random(8);

        return PaymentAttemptResult::success(
            externalId: $attemptId,
            clientPayload: [
                // The front-end gets a tiny payload that tells it "no gateway
                // modal to launch, just submit the order form". Consistent
                // shape with online gateways so JS can stay generic.
                'method' => 'cod',
                'attempt_id' => $attemptId,
                'amount_due_on_delivery' => $amount->major(),
                'currency' => $amount->currency,
            ],
        );
    }

    public function verifyPayment(string $externalId, array $payload): VerificationResult
    {
        // Nothing to verify server-side — the customer hasn't paid anything
        // yet. Contract: at order-creation time, treat this as "captured" so
        // the Order + Payment ledger entries exist; paid_amount stays 0 and
        // payment_status stays 'pending' until delivery collects cash.
        $amount = isset($payload['amount']) ? Money::fromMajor((float) $payload['amount']) : Money::zero();

        return VerificationResult::captured(
            externalPaymentId: $externalId,
            amount: $amount,
            rawResponse: ['method' => 'cod', 'verified_locally' => true],
        );
    }

    public function handleWebhook(Request $request): WebhookResult
    {
        // COD has no gateway webhook. If a request lands here it's a misroute
        // — don't error, just acknowledge and ignore.
        return WebhookResult::ignored('COD gateway does not receive webhooks.');
    }

    public function refund(Payment $payment, Money $amount, string $reason): RefundResult
    {
        // No gateway-side money to refund for COD. Admin-initiated refunds
        // for COD orders are handled inside the app (credit note / gift card
        // / bank transfer), not routed through a gateway. Return a success
        // with no external id so the caller can still record the refund in
        // our ledger without expecting a gateway receipt.
        return RefundResult::success(
            externalRefundId: 'cod_refund_' . $payment->id . '_' . time(),
            amount: $amount,
        );
    }
}
