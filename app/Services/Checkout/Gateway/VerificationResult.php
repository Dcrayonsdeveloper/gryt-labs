<?php

namespace App\Services\Checkout\Gateway;

/**
 * Result of {@see PaymentGatewayInterface::verifyPayment()}.
 *
 * `captured = true` means the gateway has confirmed the money is irrevocably
 * with us (auth+capture, not auth-only). The orchestrator only creates an
 * Order when `captured === true`.
 */
final class VerificationResult
{
    public function __construct(
        public readonly bool $captured,
        public readonly ?string $externalPaymentId,
        public readonly ?Money $amount,
        public readonly array $rawResponse,
        public readonly ?string $error = null,
    ) {
    }

    public static function captured(string $externalPaymentId, Money $amount, array $rawResponse): self
    {
        return new self(true, $externalPaymentId, $amount, $rawResponse, null);
    }

    public static function failure(string $error, array $rawResponse = []): self
    {
        return new self(false, null, null, $rawResponse, $error);
    }
}
