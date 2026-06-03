<?php

namespace App\Services\Checkout\Gateway;

/**
 * Result of {@see PaymentGatewayInterface::createPaymentAttempt()}.
 *
 * `clientPayload` is the gateway-shaped blob the frontend hands to the JS SDK
 * (Razorpay options, Cashfree session id, etc). The orchestrator passes it
 * through verbatim; only the gateway implementation knows its shape.
 */
final class PaymentAttemptResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $externalId,
        public readonly array $clientPayload,
        public readonly ?string $error = null,
    ) {
    }

    public static function success(string $externalId, array $clientPayload): self
    {
        return new self(true, $externalId, $clientPayload, null);
    }

    public static function failure(string $error, array $clientPayload = []): self
    {
        return new self(false, null, $clientPayload, $error);
    }
}
