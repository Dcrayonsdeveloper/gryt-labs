<?php

namespace App\Contracts;

use App\Models\Order;

interface ShippingProviderInterface
{
    /**
     * Provider identifier (e.g. 'delhivery', 'bluedart').
     */
    public function name(): string;

    /**
     * Human-readable label (e.g. 'Delhivery', 'BlueDart').
     */
    public function label(): string;

    /**
     * Whether this provider has valid credentials configured for the current tenant.
     */
    public function isConfigured(): bool;

    /**
     * Check if a pincode is serviceable.
     *
     * @return array{serviceable: bool, cod?: bool, prepaid?: bool, message?: string}
     */
    public function checkPincode(string $pincode): array;

    /**
     * Calculate shipping cost to a destination pincode.
     *
     * @return array{success: bool, total_amount?: float, zone?: string, message?: string}
     */
    public function calculateCost(string $destPin, float $weightGrams = 500, string $paymentType = 'Pre-paid', float $codAmount = 0): array;

    /**
     * Book delivery for an order. Returns waybill/AWB on success.
     *
     * @return array{success: bool, waybill?: string, message?: string}
     */
    public function bookDelivery(Order $order): array;

    /**
     * Track a shipment by waybill/AWB number.
     *
     * @return array{success: bool, status?: string, scans?: array, message?: string}
     */
    public function track(string $waybill): array;

    /**
     * Cancel a shipment by waybill/AWB number.
     *
     * @return array{success: bool, message?: string}
     */
    public function cancel(string $waybill): array;

    /**
     * Generate and store a shipping label PDF. Returns storage path or null.
     */
    public function generateLabel(string $waybill): ?string;

    /**
     * Request a pickup from the carrier.
     *
     * @return array{success: bool, message?: string}
     */
    public function requestPickup(int $packageCount = 1, ?string $pickupDate = null, ?string $pickupTime = null): array;

    /**
     * Map a carrier-specific raw status string to our internal order status.
     * Returns null if the status doesn't map to a known transition.
     */
    public function mapStatus(string $rawStatus): ?string;
}
