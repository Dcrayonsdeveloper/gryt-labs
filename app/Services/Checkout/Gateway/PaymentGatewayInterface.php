<?php

namespace App\Services\Checkout\Gateway;

use App\Models\Payment;
use Illuminate\Http\Request;

/**
 * Path A Phase 1 — single contract every payment gateway implements.
 *
 * Implementations live alongside this interface. Phase 1 (Week 1 of the
 * Path A sprint) ships thin wrappers around the existing CheckoutController
 * methods so the rest of the codebase can start depending on the interface
 * without behaviour change. Later phases (orchestrator, idempotency,
 * fraud-pre-auth) replace those wrappers with first-class implementations.
 *
 * Stability contract:
 *   - Method signatures are load-bearing and may not change without bumping
 *     a major version of this interface (each gateway impl + orchestrator +
 *     webhook controllers all dispatch through here).
 *   - Implementations MUST be safe to construct per-request (no static
 *     caches that survive the request — multi-tenant safety, see
 *     2026-04-23-path-a-execution-plan.md Layer 2).
 *   - Implementations MUST resolve their gateway credentials from the
 *     tenant's `Setting::get(...)` only — never from `.env` directly —
 *     so the per-tenant ConfigBootstrapper isolation holds.
 */
interface PaymentGatewayInterface
{
    /**
     * Stable identifier used in registry lookups, settings keys, metric
     * labels, and the `payment_attempts.gateway` column. Examples:
     * 'razorpay', 'cashfree', 'cod', 'partial_cod'.
     */
    public function slug(): string;

    /**
     * Whether the gateway can capture less than the order total at attempt
     * time. True for partial-COD (advance only), false for everything else.
     */
    public function supportsPartialCapture(): bool;

    /**
     * Initiate a payment with the gateway. Returns the data the JS SDK needs
     * to launch the customer-facing flow (Razorpay options blob, Cashfree
     * session id, COD synthetic id, etc).
     */
    public function createPaymentAttempt(CheckoutSessionData $session, Money $amount): PaymentAttemptResult;

    /**
     * Verify a payment after the customer returns from the gateway. Called
     * synchronously from the return URL handler. Implementations re-check
     * with the gateway's API even if the client sent a signature — never
     * trust the client alone.
     */
    public function verifyPayment(string $externalId, array $payload): VerificationResult;

    /**
     * Process a webhook payload. The orchestrator passes the inbound Request
     * through verbatim — implementations are responsible for signature
     * verification and idempotency.
     */
    public function handleWebhook(Request $request): WebhookResult;

    /**
     * Issue a refund against a previously captured payment. Implementations
     * record the gateway-side refund id on success; the orchestrator handles
     * the local Refund/Payment ledger updates.
     */
    public function refund(Payment $payment, Money $amount, string $reason): RefundResult;
}
