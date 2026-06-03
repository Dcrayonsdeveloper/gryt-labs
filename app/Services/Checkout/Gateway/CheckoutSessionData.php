<?php

namespace App\Services\Checkout\Gateway;

/**
 * Read-only snapshot of a checkout in progress, passed to gateway implementations.
 *
 * Phase 1 shape — backed by the existing Cart + Request flow inside
 * CheckoutController. Phase 3 introduces a `checkout_sessions` table and a
 * proper {@see \App\Models\CheckoutSession} Eloquent model; that model will
 * expose `toData(): CheckoutSessionData` so the gateway interface stays stable.
 *
 * Keeping the gateway contract on a value object (not the model) means the
 * gateway impls don't need to know whether the session is in-memory or
 * persisted — they just consume the snapshot.
 */
final class CheckoutSessionData
{
    /**
     * @param  array<string, mixed>  $metadata  utm/affiliate/coupon/gift_card/shipping_snapshot
     */
    public function __construct(
        public readonly string $token,
        public readonly int $cartId,
        public readonly ?int $userId,
        public readonly bool $isGuest,
        public readonly Money $subtotal,
        public readonly Money $discount,
        public readonly Money $shipping,
        public readonly Money $tax,
        public readonly Money $total,
        public readonly string $paymentMethod,
        public readonly ?string $contactEmail = null,
        public readonly ?string $contactName = null,
        public readonly ?string $contactPhone = null,
        public readonly array $metadata = [],
    ) {
    }
}
