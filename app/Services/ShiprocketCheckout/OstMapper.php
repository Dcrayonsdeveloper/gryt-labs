<?php

namespace App\Services\ShiprocketCheckout;

/**
 * Maps the Shiprocket Checkout callback `ost` (order status) parameter
 * to our internal payment_status, payment_method, and paid_amount values.
 *
 * After the customer completes (or abandons) checkout, the Shiprocket SDK
 * redirects to our success URL:
 *   /checkout/success/shiprocket?oid=<hex>&ost=<status>
 *
 * Known `ost` values (observed in production logs + Shiprocket docs):
 *
 *   SUCCESS           → full prepaid (UPI / card / net banking via Shiprocket)
 *   COD               → cash on delivery
 *   COD_PLACED        → COD variant used by some Shiprocket plans
 *   ORDER_PLACED      → COD variant
 *   PARTIAL_PAID      → COD with advance (partial prepay, balance on delivery)
 *   PARTIAL_COD       → alias for PARTIAL_PAID
 *   ADVANCE_PAID      → alias for PARTIAL_PAID
 *   FAILED            → payment failed — show failed view, do NOT create order
 *   CANCELLED         → customer cancelled — show failed view
 *   PAYMENT_FAILED    → explicit payment failure
 *   PAYMENT_CANCELLED → customer cancelled payment
 *   (empty/unknown)   → treat as COD (Shiprocket may omit ost for some COD flows)
 */
class OstMapper
{
    /** ost values that mean "terminal failure — show failed view, no order created" */
    public const FAILED_STATUSES = [
        'FAILED',
        'CANCELLED',
        'PAYMENT_FAILED',
        'PAYMENT_CANCELLED',
    ];

    /** ost values that mean "COD with advance paid; balance collected on delivery" */
    public const PARTIAL_STATUSES = [
        'PARTIAL_PAID',
        'PARTIAL_COD',
        'ADVANCE_PAID',
    ];

    /** ost values that mean full COD (nothing collected upfront) */
    public const COD_STATUSES = [
        'COD',
        'COD_PLACED',
        'ORDER_PLACED',
    ];

    /**
     * Returns true when the ost indicates a terminal failure.
     * No order should be created; show checkout.failed view.
     */
    public function isFailed(string $ost): bool
    {
        return in_array(strtoupper($ost), self::FAILED_STATUSES, true);
    }

    /**
     * Returns true when the ost indicates COD with advance payment.
     */
    public function isPartial(string $ost): bool
    {
        return in_array(strtoupper($ost), self::PARTIAL_STATUSES, true);
    }

    /**
     * Resolve payment_status, payment_method, and paid_amount for a new order.
     *
     * @param  string  $ost          Raw ost value from Shiprocket callback URL
     * @param  float   $orderTotal   Full order total (used when ost=SUCCESS)
     * @param  float   $advancePaid  Advance amount paid (for PARTIAL_PAID flows)
     * @return array{payment_status: string, payment_method: string, paid_amount: float}
     */
    public function resolve(string $ost, float $orderTotal, float $advancePaid = 0.0): array
    {
        $upper = strtoupper($ost);

        if (in_array($upper, self::PARTIAL_STATUSES, true)) {
            return [
                'payment_status' => 'pending',             // balance collected on delivery
                'payment_method' => 'shiprocket_cod_partial',
                'paid_amount'    => $advancePaid,
            ];
        }

        if ($upper === 'SUCCESS') {
            return [
                'payment_status' => 'paid',
                'payment_method' => 'shiprocket_checkout',
                'paid_amount'    => $orderTotal,
            ];
        }

        // COD, COD_PLACED, ORDER_PLACED, empty, or any unknown value → standard COD
        return [
            'payment_status' => 'pending',
            'payment_method' => 'shiprocket_cod',
            'paid_amount'    => 0.0,
        ];
    }

    /**
     * Human-readable label for logging / admin display.
     */
    public function label(string $ost): string
    {
        return match (strtoupper($ost)) {
            'SUCCESS'                        => 'Prepaid (Shiprocket)',
            'COD', 'COD_PLACED', 'ORDER_PLACED' => 'Cash on Delivery',
            'PARTIAL_PAID', 'PARTIAL_COD', 'ADVANCE_PAID' => 'Partial COD (advance paid)',
            'FAILED', 'PAYMENT_FAILED'       => 'Payment Failed',
            'CANCELLED', 'PAYMENT_CANCELLED' => 'Cancelled by Customer',
            default                          => "Unknown ({$ost})",
        };
    }
}
