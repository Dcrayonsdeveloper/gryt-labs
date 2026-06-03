<?php

namespace App\Services\Checkout\Gateway;

use App\Models\Order;

/**
 * Result of {@see PaymentGatewayInterface::handleWebhook()}.
 *
 * `handled = true` — the gateway recognised the payload and processed it.
 * `handled = false` — payload was acknowledged but not actionable (unknown
 * event type, duplicate, etc). Either way the orchestrator should HTTP-200
 * the gateway so it stops retrying.
 *
 * `event` is a normalised string (e.g. 'payment.captured', 'order.refunded')
 * — the orchestrator's metric / log aggregations key on this, so each gateway
 * impl must map its native event names to a stable vocabulary.
 */
final class WebhookResult
{
    public function __construct(
        public readonly bool $handled,
        public readonly ?string $event,
        public readonly ?Order $orderAffected,
        public readonly array $payload,
        public readonly ?string $error = null,
    ) {
    }

    public static function handled(string $event, ?Order $orderAffected, array $payload): self
    {
        return new self(true, $event, $orderAffected, $payload, null);
    }

    public static function ignored(string $reason, array $payload = []): self
    {
        return new self(false, null, null, $payload, $reason);
    }

    public static function failure(string $error, array $payload = []): self
    {
        return new self(false, null, null, $payload, $error);
    }
}
