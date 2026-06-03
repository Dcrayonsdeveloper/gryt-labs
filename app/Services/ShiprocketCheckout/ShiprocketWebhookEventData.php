<?php

namespace App\Services\ShiprocketCheckout;

/**
 * Value object representing a normalised Shiprocket Checkout webhook event.
 *
 * Populated by ShiprocketWebhookEventData::fromRaw() which accepts the
 * raw POST body and runs it through PayloadMapper internally.
 *
 * All fields are read-only. Consumers must not mutate this object.
 *
 * Recommended webhook fields (what Shiprocket sends per stage):
 *
 *  Stage              | cart_id | phone | name | email | address | items | paymentDetails | rtoPrediction
 *  ───────────────────┼─────────┼───────┼──────┼───────┼─────────┼───────┼────────────────┼──────────────
 *  INIT               |  ✅     |  ✅   |  ❌  |  ❌   |  ❌     |  ✅   |  ❌            |  ❌
 *  PAYMENT_INITIATED  |  ✅     |  ✅   |  ✅  |  ✅   |  ✅     |  ❌   |  ❌            |  ❌
 *  ORDER_PLACED       |  ✅     |  ✅   |  ✅  |  ❌   |  ✅     |  ✅   |  ✅ (COD/UPI)  |  ✅
 *  Payment Complete   |  ✅     |  ✅   |  ✅  |  ✅   |  ✅     |  ✅   |  ✅            |  ❌
 *  Abandoned Cart     |  ✅     |  ✅   |  ❌  |  ❌   |  ❌     |  ❌   |  ❌            |  ❌
 *
 * Known gaps (by design — Shiprocket does not send these):
 *  - state/province is never in billing_address for Fastrr flows → always null
 *  - email is absent in ORDER_PLACED stage
 *  - PAYMENT_INITIATED does not include items array
 *  - total_discount = total_price for COD (Shiprocket's 100% platform subsidy representation)
 *    → use net_payable (total_price - total_discount) for actual due amount
 */
readonly class ShiprocketWebhookEventData
{
    public function __construct(
        // ─── Identity ──────────────────────────────────────────────────────
        public string  $cartId,
        public string  $stage,
        public string  $sourceName,
        public string  $payloadHash,       // MD5 of normalised raw payload

        // ─── Customer ─────────────────────────────────────────────────────
        public ?string $phone,
        public bool    $phoneVerified,
        public ?string $email,
        public ?string $fullName,          // billing_address.name (preferred)
        public ?string $firstName,
        public ?string $lastName,

        // ─── Address ──────────────────────────────────────────────────────
        public ?string $addressLine1,
        public ?string $addressLine2,
        public ?string $city,
        public ?string $state,             // ⚠ always null for Fastrr — Shiprocket omits it
        public ?string $pincode,
        public ?string $country,
        public ?string $addressHash,       // MD5(addressLine1.city.pincode) — detect changes

        // ─── Cart ─────────────────────────────────────────────────────────
        public float   $totalPrice,
        public float   $shippingPrice,
        public float   $totalDiscount,
        public float   $netPayable,        // totalPrice - totalDiscount (actual charge)
        public float   $tax,
        public int     $itemCount,
        public array   $items,             // [{product_id, variant_id, name, price, quantity}]

        // ─── Payment (ORDER_PLACED / Payment Complete only) ───────────────
        public ?string $paymentMode,       // normalised order type: COD | PARTIAL | PREPAID | null
        public ?string $paymentGateway,    // Cashfree | Razorpay | Shiprocket | Phonepe
        public ?float  $paymentAmount,     // amount paid ONLINE; null when no online payment
        public ?string $transactionId,

        // ─── Risk ─────────────────────────────────────────────────────────
        public ?string $rtoPrediction,     // low | medium | high | null

        // ─── Meta ─────────────────────────────────────────────────────────
        public ?string $ipAddress,
        public array   $rawPayload,        // full original payload for audit
    ) {}

    /**
     * Build from the raw POST body Shiprocket sends.
     * Handles the outer `payload` wrapper that Fastrr uses.
     */
    public static function fromRaw(array $raw): self
    {
        // Unwrap Fastrr's `payload` envelope
        $data = isset($raw['payload']) && is_array($raw['payload']) ? $raw['payload'] : $raw;

        // Flatten billing_address
        $addr = $data['billing_address'] ?? $data['shipping_address'] ?? [];

        $fullName  = $addr['name'] ?? null;
        if (!$fullName && !empty($addr['first_name'])) {
            $fullName = trim(($addr['first_name'] ?? '') . ' ' . ($addr['last_name'] ?? ''));
        }

        $addrLine1 = isset($addr['address1']) ? trim(($addr['address1'] ?? '')) : null;
        $addrLine2 = $addr['address2'] ?? null;
        // Deduplicate: don't append address2 if it's already contained in address1
        if ($addrLine1 && $addrLine2 && str_contains(strtolower($addrLine1), strtolower($addrLine2))) {
            $addrLine2 = null;
        }

        $city    = $addr['city']     ?? null;
        $pincode = $addr['zip']      ?? $addr['pincode'] ?? null;
        $country = $addr['country']  ?? 'India';
        $state   = $addr['province'] ?? $addr['state']   ?? null; // typically null for Fastrr

        $addrHash = ($addrLine1 || $city || $pincode)
            ? md5(strtolower(($addrLine1 ?? '') . '|' . ($city ?? '') . '|' . ($pincode ?? '')))
            : null;

        // ── Payment details ──────────────────────────────────────────────
        // Shiprocket sends payment data in TWO different shapes:
        //   • ORDER_PLACED webhook → paymentDetails: {paymentMode:"UPI"|"Card", amount}
        //     paymentMode here is the ONLINE method used — it is NOT COD-vs-prepaid
        //     (a COD order's ₹X advance is also paid by UPI → paymentMode="UPI").
        //   • SUCCESS webhook → payment_type:"PREPAID"|"COD"|"PARTIAL_PAID"
        //                       + payments[]: [{payment_method, gateway, amount}, …]
        // We normalise `paymentMode` to the ORDER TYPE — COD | PARTIAL | PREPAID —
        // using payment_type / payments[] (authoritative), and expose
        // `paymentAmount` as the amount actually collected ONLINE.
        $pd          = $data['paymentDetails'] ?? [];
        $paymentType = strtoupper(trim((string) ($data['payment_type'] ?? '')));
        $payments    = is_array($data['payments'] ?? null) ? $data['payments'] : [];

        $onlinePaid = 0.0;
        $hasCodLine = false;
        foreach ($payments as $pmt) {
            $method = strtoupper((string) ($pmt['payment_method'] ?? $pmt['gateway'] ?? ''));
            if ($method === 'COD') {
                $hasCodLine = true;
            } else {
                $onlinePaid += (float) ($pmt['amount'] ?? 0);
            }
        }

        // Normalised order payment mode (null when this stage carries no payment info).
        // Shiprocket's payment_type has been observed as: PREPAID, CASH_ON_DELIVERY,
        // COD, PARTIAL_PAID. Underscores/spaces are normalised before matching.
        $ptNorm = str_replace(' ', '_', $paymentType);

        // ORDER_PLACED is the ONLY payment-bearing webhook this tenant receives.
        // It does NOT send payment_type or payments[]; payment info lives only in
        // paymentDetails (paymentMode = online instrument used, transactionId,
        // amount). A non-COD instrument + real transactionId proves the gateway
        // already collected — amount vs. net payable then decides PREPAID vs PARTIAL.
        $pdMode     = strtoupper(trim((string) ($pd['paymentMode'] ?? '')));
        $pdTxn      = trim((string) ($pd['transactionId'] ?? ''));
        $pdAmt      = (float) ($pd['amount'] ?? 0);
        $pdNetDue   = max(0, (float) ($data['total_price'] ?? 0) - (float) ($data['total_discount'] ?? 0));
        $pdIsCod    = in_array($pdMode, ['COD', 'CASH_ON_DELIVERY', 'CASH ON DELIVERY'], true);
        $pdHasOnlineProof = $pdTxn !== '' && !$pdIsCod;

        $paymentMode = match (true) {
            in_array($ptNorm, ['COD', 'CASH_ON_DELIVERY'], true)                    => 'COD',
            in_array($ptNorm, ['PARTIAL_PAID', 'PARTIAL_COD', 'ADVANCE_PAID', 'PARTIAL'], true) => 'PARTIAL',
            in_array($ptNorm, ['PREPAID', 'SUCCESS'], true)                         => 'PREPAID',
            $hasCodLine && $onlinePaid > 0                                          => 'PARTIAL',
            $hasCodLine                                                             => 'COD',
            !empty($payments)                                                       => 'PREPAID',
            $pdHasOnlineProof && $pdNetDue > 0 && $pdAmt + 0.01 >= $pdNetDue        => 'PREPAID',
            $pdHasOnlineProof && $pdAmt > 0                                         => 'PARTIAL',
            default                                                                 => null,
        };

        // Amount actually paid ONLINE (advance for PARTIAL, full for PREPAID, 0 for COD).
        $paymentAmount = $onlinePaid > 0
            ? $onlinePaid
            : (isset($pd['amount']) ? (float) $pd['amount'] : null);

        $paymentGateway = $pd['paymentGateway'] ?? ($payments[0]['gateway'] ?? null);
        $transactionId  = $pd['transactionId']
            ?? ($payments[0]['pg_transaction_id'] ?? $payments[0]['txn_id'] ?? null);

        $totalPrice    = (float) ($data['total_price']    ?? 0);
        $totalDiscount = (float) ($data['total_discount'] ?? 0);

        // Items — normalise to flat structure
        $items = array_map(fn($i) => [
            'product_id'  => $i['product_id']  ?? $i['variant_id'] ?? null,
            'variant_id'  => $i['variant_id']  ?? null,
            'name'        => $i['name']        ?? $i['title']       ?? 'Product',
            'sku'         => $i['sku']         ?? null,
            'price'       => (float) ($i['price'] ?? 0),
            'quantity'    => (int)   ($i['quantity'] ?? 1),
            'image'       => $i['image']       ?? null,
        ], $data['items'] ?? []);

        return new self(
            cartId:         $data['cart_id']         ?? '',
            stage:          $data['latest_stage']     ?? $data['stage'] ?? $data['status'] ?? 'unknown',
            sourceName:     $data['source_name']      ?? 'unknown',
            payloadHash:    md5(json_encode($data, JSON_UNESCAPED_UNICODE)),

            phone:          $data['phone_number']     ?? $addr['phone'] ?? null,
            phoneVerified:  (bool) ($data['phone_verified'] ?? false),
            email:          $data['email']            ?? $addr['email'] ?? null,
            fullName:       $fullName,
            firstName:      $addr['first_name']       ?? null,
            lastName:       $addr['last_name']        ?? null,

            addressLine1:   $addrLine1,
            addressLine2:   $addrLine2,
            city:           $city,
            state:          $state,
            pincode:        $pincode,
            country:        $country,
            addressHash:    $addrHash,

            totalPrice:     $totalPrice,
            shippingPrice:  (float) ($data['shipping_price'] ?? 0),
            totalDiscount:  $totalDiscount,
            netPayable:     max(0, $totalPrice - $totalDiscount),
            tax:            (float) ($data['tax'] ?? 0),
            itemCount:      (int)   ($data['item_count'] ?? count($items)),
            items:          $items,

            paymentMode:    $paymentMode,
            paymentGateway: $paymentGateway,
            paymentAmount:  $paymentAmount,
            transactionId:  $transactionId,

            rtoPrediction:  $data['rtoPrediction'] ?? null,
            ipAddress:      $data['cart_attributes']['ipv4_address'] ?? null,
            rawPayload:     $raw,
        );
    }

    /**
     * Compute a composite risk analysis from this event's signals.
     *
     * Score bands:  0-20 = low   21-50 = medium   51+ = high
     *
     * @param  bool  $isNewCustomer   True if no prior orders found for this phone/email
     * @param  bool  $addressChanged  True if address_hash differs from an earlier event
     */
    public function computeRiskAnalysis(bool $isNewCustomer = false, bool $addressChanged = false): array
    {
        $score   = 0;
        $signals = [];

        // Shiprocket's own RTO ML model (most reliable signal)
        if ($this->rtoPrediction === 'high') {
            $score += 45;
            $signals['rto_prediction_high'] = true;
        } elseif ($this->rtoPrediction === 'medium') {
            $score += 20;
            $signals['rto_prediction_medium'] = true;
        } else {
            $signals['rto_prediction_high']   = false;
            $signals['rto_prediction_medium'] = false;
        }

        // COD / partial COD → higher return risk
        $isCod = in_array(strtoupper($this->paymentMode ?? ''), ['COD', ''], true)
            && $this->paymentAmount === null;
        if ($isCod) {
            $score += 20;
            $signals['cod_payment'] = true;
        } else {
            $signals['cod_payment'] = false;
        }

        // Unverified phone
        if (!$this->phoneVerified) {
            $score += 15;
            $signals['phone_unverified'] = true;
        } else {
            $signals['phone_unverified'] = false;
        }

        // Address changed mid-checkout (suspicious)
        if ($addressChanged) {
            $score += 15;
            $signals['address_changed_mid_checkout'] = true;
        } else {
            $signals['address_changed_mid_checkout'] = false;
        }

        // First-time buyer
        if ($isNewCustomer) {
            $score += 10;
            $signals['new_customer'] = true;
        } else {
            $signals['new_customer'] = false;
        }

        // High-value order (>₹2000) with COD → extra scrutiny
        if ($this->totalPrice > 2000 && $isCod) {
            $score += 10;
            $signals['high_value_cod'] = true;
        } else {
            $signals['high_value_cod'] = false;
        }

        $level = match (true) {
            $score >= 51 => 'high',
            $score >= 21 => 'medium',
            default      => 'low',
        };

        return [
            'score'   => $score,
            'level'   => $level,
            'signals' => $signals,
        ];
    }

    /** True if this event has enough address data to build a shipping snapshot. */
    public function hasAddress(): bool
    {
        return !empty($this->addressLine1) || !empty($this->city) || !empty($this->pincode);
    }

    /** True if this event has customer identity (name + phone or email). */
    public function hasCustomerIdentity(): bool
    {
        return !empty($this->fullName) && (!empty($this->phone) || !empty($this->email));
    }
}
