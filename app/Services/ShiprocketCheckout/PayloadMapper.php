<?php

namespace App\Services\ShiprocketCheckout;

/**
 * Normalises the Shiprocket Checkout webhook payload into a flat structure.
 *
 * Shiprocket sends different field names across webhook stages:
 *   cart_id       → order_id
 *   latest_stage  → stage
 *   phone_number  → phone
 *   billing_address / shipping_address (nested objects) → flat top-level fields
 *
 * Webhook stages configured in Shiprocket Checkout → Settings → Webhooks:
 *   Cart Initiated, Phone Received, Payment Initiated, Order Placed,
 *   Payment Complete, Abandoned Cart
 */
class PayloadMapper
{
    /**
     * Normalise a raw Shiprocket Checkout webhook payload.
     *
     * @param  array  $raw  Raw payload received from Shiprocket
     * @return array        Flat, normalised array ready for business logic
     */
    public function map(array $raw): array
    {
        // Shiprocket wraps actual data under a 'payload' key — unwrap it
        if (isset($raw['payload']) && is_array($raw['payload'])) {
            $raw = $raw['payload'];
        }

        $out = $raw;

        // cart_id is the primary ID Shiprocket sends; map to order_id
        if (empty($out['order_id']) && !empty($raw['cart_id'])) {
            $out['order_id'] = $raw['cart_id'];
        }

        // latest_stage → stage
        if (empty($out['stage']) && !empty($raw['latest_stage'])) {
            $out['stage'] = $raw['latest_stage'];
        }

        // phone_number → phone
        if (empty($out['phone']) && !empty($raw['phone_number'])) {
            $out['phone'] = $raw['phone_number'];
        }

        // Flatten nested billing_address / shipping_address
        $addr = $raw['billing_address'] ?? $raw['shipping_address'] ?? null;
        if (is_array($addr)) {
            // Name: prefer full `name`, otherwise concat first + last
            if (empty($out['customer_name'])) {
                if (!empty($addr['name'])) {
                    $out['customer_name'] = $addr['name'];
                } elseif (!empty($addr['first_name'])) {
                    $out['customer_name'] = trim(($addr['first_name'] ?? '') . ' ' . ($addr['last_name'] ?? ''));
                }
            }

            if (empty($out['phone']) && !empty($addr['phone'])) {
                $out['phone'] = $addr['phone'];
            }
            if (empty($out['email']) && !empty($addr['email'])) {
                $out['email'] = $addr['email'];
            }

            // Address fields — Shiprocket sends different keys per webhook type:
            //   Checkout stages (INIT/PAYMENT_INITIATED/ORDER_PLACED): address1, address2, zip
            //   Order webhook (SUCCESS): line1, line2, pincode
            if (empty($out['address'])) {
                $street = $addr['address1'] ?? $addr['address'] ?? $addr['line1'] ?? '';
                if (!empty($street)) {
                    $out['address'] = trim($street . ' ' . ($addr['address2'] ?? $addr['line2'] ?? ''));
                }
            }
            if (empty($out['address_2'])) {
                $addr2 = $addr['address2'] ?? $addr['line2'] ?? '';
                if (!empty($addr2)) {
                    $out['address_2'] = $addr2;
                }
            }
            if (empty($out['city']) && !empty($addr['city'])) {
                $out['city'] = $addr['city'];
            }
            if (empty($out['state'])) {
                $out['state'] = $addr['province'] ?? $addr['state'] ?? null;
            }
            if (empty($out['pincode'])) {
                $out['pincode'] = $addr['zip'] ?? $addr['pincode'] ?? null;
            }
            if (empty($out['country']) && !empty($addr['country'])) {
                $out['country'] = $addr['country'];
            }
        }

        return $out;
    }

    /**
     * Extract a structured customer array from an already-normalised payload.
     */
    public function extractCustomer(array $payload): array
    {
        return [
            'order_id' => $payload['order_id'] ?? $payload['oid'] ?? $payload['id'] ?? null,
            'stage'    => $payload['stage'] ?? $payload['event'] ?? $payload['status'] ?? 'unknown',
            'name'     => $payload['customer_name'] ?? $payload['name'] ?? $payload['billing_customer_name'] ?? null,
            'email'    => $payload['customer_email'] ?? $payload['email'] ?? $payload['billing_email'] ?? null,
            'phone'    => $payload['customer_phone'] ?? $payload['phone'] ?? $payload['billing_phone']
                            ?? $payload['mobile'] ?? $payload['contact'] ?? null,
            'address'  => $payload['address'] ?? null,
            'address_2'=> $payload['address_2'] ?? $payload['billing_address_2'] ?? '',
            'city'     => $payload['city'] ?? $payload['billing_city'] ?? null,
            'state'    => $payload['state'] ?? $payload['billing_state'] ?? null,
            'pincode'  => $payload['pincode'] ?? $payload['billing_pincode'] ?? $payload['postal_code'] ?? null,
            'country'  => $payload['country'] ?? $payload['billing_country'] ?? 'India',
        ];
    }
}
