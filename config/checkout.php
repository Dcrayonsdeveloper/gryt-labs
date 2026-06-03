<?php

/**
 * Path A unified checkout configuration. Read by GatewayRegistry, the
 * orchestrator (Phase 2+), and the EnforceIdempotency middleware (Phase 3+).
 *
 * Per-tenant overrides land in the `settings` table, NOT here:
 *   - Setting::get('checkout_primary_gateway')      — overrides 'gateways.primary'
 *   - Setting::get('checkout_enabled_gateways')     — comma-list, intersects 'gateways.enabled'
 *   - Setting::get('unified_checkout_v2_enabled')   — tri-state rollout flag (off|shadow|on)
 *   - Setting::get('unified_checkout_*_enabled')    — sub-flags per phase (Phase 4+)
 */
return [

    'gateways' => [
        'primary' => env('CHECKOUT_PRIMARY_GATEWAY', 'razorpay'),
        'fallback' => env('CHECKOUT_FALLBACK_GATEWAY'),
        'enabled' => ['razorpay', 'cashfree', 'cod', 'partial_cod'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Gateway implementation registrations
    |--------------------------------------------------------------------------
    |
    | Map each gateway slug to its implementation class. Phase 1 leaves the
    | values empty — the wrappers land in Phase 1 Day 3-5 (Razorpay first).
    | GatewayRegistry::for() throws InvalidArgumentException if a slug is
    | requested before its impl is registered, which is the desired fail-fast
    | behaviour during the rollout.
    |
    */
    'gateway_implementations' => [
        'razorpay'    => \App\Services\Checkout\Gateway\Implementations\RazorpayGateway::class,
        'cashfree'    => \App\Services\Checkout\Gateway\Implementations\CashfreeGateway::class,
        'cod'         => \App\Services\Checkout\Gateway\Implementations\CODGateway::class,
        'partial_cod' => \App\Services\Checkout\Gateway\Implementations\PartialCODGateway::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Idempotency
    |--------------------------------------------------------------------------
    |
    | Window after which a stored idempotency response is no longer reusable.
    | Matches the `expires_at` column default on `checkout_idempotency_keys`.
    |
    */
    'idempotency_ttl_minutes' => env('CHECKOUT_IDEMPOTENCY_TTL_MINUTES', 1440), // 24h

    /*
    |--------------------------------------------------------------------------
    | Inventory reservations (Phase 3)
    |--------------------------------------------------------------------------
    */
    'reservation_ttl_minutes' => env('CHECKOUT_RESERVATION_TTL_MINUTES', 15),

];
