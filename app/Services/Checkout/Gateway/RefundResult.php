<?php

namespace App\Services\Checkout\Gateway;

/**
 * Result of {@see PaymentGatewayInterface::refund()}.
 */
final class RefundResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $externalRefundId,
        public readonly ?Money $amount,
        public readonly ?string $error = null,
    ) {
    }

    public static function success(string $externalRefundId, Money $amount): self
    {
        return new self(true, $externalRefundId, $amount, null);
    }

    public static function failure(string $error): self
    {
        return new self(false, null, null, $error);
    }
}
