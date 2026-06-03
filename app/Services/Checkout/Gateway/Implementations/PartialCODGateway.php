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

/**
 * Path A Phase 1 Day 4: Partial-COD (advance-online, balance-on-delivery).
 *
 * Hybrid gateway — the customer pays a small fixed advance (₹100 by default,
 * configurable via `cod_advance_amount`) through whichever online gateway
 * the tenant has as primary, and the remaining balance is collected by the
 * courier at delivery. This implements the model that Track 2 Fix 5
 * identified as the revenue-leak shape: PR #18's webhook fix stopped the
 * bleed, this class makes it a first-class payment method in the registry.
 *
 * Internally delegates:
 *   - Advance charge   -> primary online gateway (Razorpay or Cashfree)
 *   - Webhook handling -> primary online gateway (signed by its secret)
 *   - Balance tracking -> the Order's `metadata.cod_balance` column
 *                         (unchanged from existing behaviour)
 *
 * The tenant's primary online gateway is picked up via
 * Setting::get('checkout_primary_gateway') with a config fallback. The
 * orchestrator resolves the actual gateway via the same GatewayRegistry.
 *
 * supportsPartialCapture() returns TRUE for this class — the orchestrator
 * uses that flag to decide whether it's safe to mark payment_status='pending'
 * instead of 'paid' when the captured amount < order total. Matches the
 * 'partial_paid' payment_status enum value Track 2 called for.
 */
class PartialCODGateway implements PaymentGatewayInterface
{
    public function __construct(
        private readonly RazorpayGateway $razorpay,
        private readonly CashfreeGateway $cashfree,
    ) {
    }

    public function slug(): string
    {
        return 'partial_cod';
    }

    public function supportsPartialCapture(): bool
    {
        return true;
    }

    public function createPaymentAttempt(CheckoutSessionData $session, Money $amount): PaymentAttemptResult
    {
        // `$amount` is the TOTAL order amount; we only charge the advance
        // portion through the online gateway. Balance is collected offline.
        $advanceMajor = (int) Setting::get('cod_advance_amount', 100);
        $advanceMinor = min($advanceMajor * 100, $amount->minor);
        if ($advanceMinor <= 0) {
            return PaymentAttemptResult::failure('Advance amount must be positive for partial COD.');
        }
        $advance = Money::fromMinor($advanceMinor, $amount->currency);

        $underlying = $this->resolveOnlineGateway();
        $result = $underlying->createPaymentAttempt($session, $advance);

        if (!$result->success) {
            return $result;
        }

        // Annotate the client payload so the frontend can show "₹X advance now,
        // ₹Y on delivery" copy without having to re-derive.
        $balanceMinor = $amount->minor - $advance->minor;
        $clientPayload = array_merge($result->clientPayload, [
            'partial_cod' => true,
            'advance_amount_minor' => $advance->minor,
            'balance_on_delivery_minor' => $balanceMinor,
            'underlying_gateway' => $underlying->slug(),
        ]);

        return PaymentAttemptResult::success(
            externalId: $result->externalId,
            clientPayload: $clientPayload,
        );
    }

    public function verifyPayment(string $externalId, array $payload): VerificationResult
    {
        // The advance verification uses the underlying gateway's signature
        // scheme. Orchestrator passes the gateway-specific payload through.
        return $this->resolveOnlineGateway()->verifyPayment($externalId, $payload);
    }

    public function handleWebhook(Request $request): WebhookResult
    {
        // Webhook arrives at the underlying gateway's endpoint signed with
        // its secret — orchestrator routes by endpoint, not by payment method.
        // Keeping this method implemented so the interface is satisfied; in
        // practice the underlying gateway's handleWebhook() is what runs.
        return $this->resolveOnlineGateway()->handleWebhook($request);
    }

    public function refund(Payment $payment, Money $amount, string $reason): RefundResult
    {
        // Refunds for partial-COD: only the advance portion is on the gateway;
        // any balance refund is a bookkeeping entry (no gateway money to
        // return). The caller must split the amount if they want to refund
        // more than the captured advance.
        $advanceCaptured = Money::fromMajor((float) $payment->amount, $amount->currency);
        if ($amount->minor > $advanceCaptured->minor) {
            return RefundResult::failure(
                "Requested refund ({$amount}) exceeds captured advance ({$advanceCaptured}). Split into gateway refund + manual COD credit."
            );
        }

        return $this->resolveOnlineGateway()->refund($payment, $amount, $reason);
    }

    /**
     * Resolve the tenant's primary online gateway (Razorpay or Cashfree) for
     * the advance charge. Config fallback to Razorpay matches current tenant
     * defaults.
     */
    private function resolveOnlineGateway(): PaymentGatewayInterface
    {
        $slug = (string) Setting::get('checkout_primary_gateway', config('checkout.gateways.primary', 'razorpay'));
        return match ($slug) {
            'cashfree' => $this->cashfree,
            default => $this->razorpay,
        };
    }
}
