<?php

namespace App\Http\Controllers\ShiprocketCheckout;

use App\Events\OrderPlaced;
use App\Helpers\DbCompat;
use App\Http\Controllers\Controller;
use App\Models\AbandonedCheckout;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ShiprocketCheckoutEvent;
use App\Services\AnalyticsService;
use App\Services\ShiprocketCheckout\PayloadMapper;
use App\Services\ShiprocketCheckout\ShiprocketCheckoutService;
use App\Services\ShiprocketCheckout\ShiprocketWebhookEventData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Handles real-time and abandoned-cart webhooks from Shiprocket Checkout.
 *
 * Configured in Shiprocket Checkout → Settings → Webhooks.
 * Shiprocket fires a POST to this endpoint at each checkout stage:
 *
 *   Stage              | What Shiprocket sends
 *   ───────────────────┼──────────────────────────────────────────────────
 *   Cart Initiated     | cart_id, items, total_price
 *   Phone Received     | + phone_number
 *   Payment Initiated  | + billing_address (name, email, address1, city…)
 *   Order Placed       | + latest_stage = "Order Placed"
 *   Payment Complete   | + latest_stage = "Payment Complete"
 *   Abandoned Cart     | cart_id, phone_number (Abandon Cart webhook type)
 *
 * Auth: custom header verified via ShiprocketCheckoutService::verifyWebhookAuth().
 *       Configure key/value in Shiprocket → Webhooks → Headers,
 *       and mirror in Settings: shiprocket_checkout_webhook_header_key / _value.
 *
 * Route: POST /webhook/shipping-updates
 */
class WebhookController extends Controller
{
    public function __construct(
        private ShiprocketCheckoutService $checkout,
        private PayloadMapper             $mapper,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        // 1 — Auth
        if (!$this->checkout->verifyWebhookAuth($request)) {
            Log::warning('ShiprocketCheckoutWebhook: auth failed', [
                'headers' => $request->headers->all(),
                'ip'      => $request->ip(),
            ]);
            return response()->json(['status' => 'unauthorized'], 401);
        }

        $rawPayload = $request->all();

        Log::info('ShiprocketCheckoutWebhook: received', ['payload' => $rawPayload]);

        // 2 — Build typed DTO from raw payload
        $dto = ShiprocketWebhookEventData::fromRaw($rawPayload);

        if (!$dto->cartId) {
            Log::info('ShiprocketCheckoutWebhook: no cart_id in payload', ['stage' => $dto->stage]);
            return response()->json(['status' => 'ok', 'note' => 'no_cart_id']);
        }

        // 3 — Deduplicate: identical payload for same cart_id + stage = Shiprocket retry
        $isDuplicate = ShiprocketCheckoutEvent::where('cart_id', $dto->cartId)
            ->where('stage', $dto->stage)
            ->where('payload_hash', $dto->payloadHash)
            ->exists();

        // 4 — Check for address change mid-checkout (risk signal)
        $priorAddressHash = ShiprocketCheckoutEvent::where('cart_id', $dto->cartId)
            ->whereNotNull('address_hash')
            ->where('is_duplicate', false)
            ->value('address_hash');
        $addressChanged = $priorAddressHash && $dto->addressHash && $priorAddressHash !== $dto->addressHash;

        // 5 — Check if new customer (no prior order for this phone/email)
        $isNewCustomer = $this->isNewCustomer($dto->phone, $dto->email);

        // 6 — Store the event (insert-only — prevents race condition from duplicate INIT calls)
        $event = ShiprocketCheckoutEvent::fromDTO($dto, $isDuplicate, $isNewCustomer, $addressChanged);
        $event->save();

        // 7 — Skip AC/order processing for exact duplicates
        if ($isDuplicate) {
            Log::info('ShiprocketCheckoutWebhook: duplicate event skipped', [
                'event_id' => $event->id,
                'cart_id'  => $dto->cartId,
                'stage'    => $dto->stage,
            ]);
            return response()->json(['status' => 'ok', 'note' => 'duplicate']);
        }

        // 8 — Legacy mapper for AC sync (existing logic unchanged)
        $payload  = $this->mapper->map($rawPayload);
        $customer = $this->mapper->extractCustomer($payload);
        $orderId  = $dto->cartId;
        $stage    = $dto->stage;

        // 9 — If no phone and no name, nothing to sync
        if (!$orderId) {
            if ($dto->phone) {
                $this->updateRecentCheckoutByPhone($dto->phone, $customer);
            }
            $event->update(['processed_at' => now()]);
            return response()->json(['status' => 'ok', 'note' => 'no_order_id']);
        }

        // 10 — Sync AbandonedCheckout + link event to AC
        $abandoned = $this->syncAbandonedCheckout($orderId, $customer, $stage, $payload);
        if ($abandoned) {
            $event->update(['abandoned_checkout_id' => $abandoned->id]);
        }

        // 11 — Sync Order + link event to order
        $order = $this->syncOrder($orderId, $customer, $stage, $dto->paymentMode, $dto->paymentAmount);
        if ($order) {
            $event->update(['order_id' => $order->id]);
        }

        // 11b — Fallback: create order from webhook when callback never fires.
        //        The SUCCESS webhook (status=SUCCESS, payment_status=Success) contains
        //        full payment confirmation. Create the order even without an AC —
        //        the webhook payload has all needed data (items, customer, payment).
        if (!$order && $this->isPaymentConfirmedWebhook($rawPayload)) {
            // Validation gate: refuse to create ghost orders.
            // Shiprocket occasionally fires ORDER_PLACED for sessions that never
            // completed real checkout (browser closed mid-flow, bot retries, stale
            // cart push). Those events lack customer identity. Only create when we
            // have at least one of name / phone / email — without it the order is
            // un-actionable (no way to contact the customer or fulfil it).
            $hasCustomerIdentity =
                !empty($customer['name'])
                || !empty($customer['phone'])
                || !empty($customer['email']);

            if (!$hasCustomerIdentity) {
                Log::warning('ShiprocketCheckoutWebhook: refusing fallback order — no customer identity', [
                    'cart_id'  => $orderId,
                    'stage'    => $stage,
                    'event_id' => $event->id,
                ]);
            } else {
                // Create a temporary AC if none matched, so createOrderFromWebhook has a container
                if (!$abandoned) {
                    $abandoned = $this->createAbandonedCheckoutFromPayload($orderId, $customer, $payload);
                }
                if ($abandoned) {
                    $order = $this->createOrderFromWebhook($abandoned, $rawPayload, $customer);
                    if ($order) {
                        $event->update(['order_id' => $order->id]);
                        Log::info('ShiprocketCheckoutWebhook: order created from webhook fallback', [
                            'order_id'     => $order->id,
                            'order_number' => $order->order_number,
                            'abandoned_id' => $abandoned->id,
                            'cart_id'      => $orderId,
                            'had_prior_ac' => $abandoned->wasRecentlyCreated ? 'no' : 'yes',
                        ]);
                    }
                }
            }
        }

        // 12 — Fire Meta CAPI Purchase on payment-success stages (if not already sent)
        if ($order && in_array($stage, ['Payment Complete', 'ORDER_PLACED', 'SUCCESS'])) {
            $this->fireCapiPurchase($order, $request, $abandoned);
        }

        $event->update(['processed_at' => now()]);

        return response()->json(['status' => 'ok']);
    }

    // ─── Abandoned Checkout Sync ──────────────────────────────────────────────

    private function syncAbandonedCheckout(string $orderId, array $customer, string $stage, array $payload): ?AbandonedCheckout
    {
        // 1. Bridge lookup — matches ANY previously registered Shiprocket ID (token, webhook, callback)
        $abandoned = AbandonedCheckout::findByShiprocketId($orderId);

        // 2. Direct match by shiprocket_order_id (legacy, works when webhook cart_id == token oid)
        if (!$abandoned) {
            $abandoned = AbandonedCheckout::where('shiprocket_order_id', $orderId)->first();
        }

        // 3. Match by stored cart_id mapping (set by a previous webhook for this session)
        if (!$abandoned) {
            $abandoned = AbandonedCheckout::where('source', 'shiprocket_checkout')
                ->whereJsonContains('metadata->shiprocket_cart_id', $orderId)
                ->latest()
                ->first();
        }

        // 4. Match by phone_hash — but skip ACs that belong to a different checkout session
        //    (i.e. they already have a shiprocket_cart_id that differs from the current cart_id).
        //    Without this guard, a returning customer's new checkout matches their OLD AC,
        //    and the order gets created with the wrong cart_snapshot items.
        if (!$abandoned && $customer['phone']) {
            $phoneHash = hash('sha256', preg_replace('/\D/', '', $customer['phone']));
            $abandoned = AbandonedCheckout::where('source', 'shiprocket_checkout')
                ->where('phone_hash', $phoneHash)
                ->whereNull('order_id')
                ->where(function ($q) use ($orderId) {
                    $q->whereRaw("(metadata->>'shiprocket_cart_id') IS NULL")
                      ->orWhereRaw("(metadata->>'shiprocket_cart_id') = ?", [$orderId]);
                })
                ->latest()
                ->first();
        }

        // 5. Cart fingerprint match: deterministic match by sorted product_id:quantity.
        //    Only used when exactly ONE candidate exists — avoids cross-customer
        //    contamination when two customers buy the same product(s) in the same qty.
        //    Exclude ACs that already received ORDER_PLACED/Payment Complete webhooks
        //    (their order_id col is null until callback runs, so whereNull('order_id')
        //    alone is insufficient — they remain in the pool and cause false collisions).
        if (!$abandoned && !empty($payload['items'])) {
            $fingerprint  = TokenController::cartFingerprint($payload['items']);
            $fpCandidates = AbandonedCheckout::where('source', 'shiprocket_checkout')
                ->whereJsonContains('metadata->cart_fingerprint', $fingerprint)
                ->where('created_at', '>=', now()->subMinutes(30))
                ->whereNull('order_id')
                ->where(function ($q) {
                    $q->whereNull('metadata->webhook_order_placed')
                      ->whereNull('metadata->webhook_payment_complete');
                })
                ->latest()
                ->limit(2)
                ->get();

            if ($fpCandidates->count() === 1) {
                $abandoned = $fpCandidates->first();
                Log::info('ShiprocketCheckoutWebhook: matched abandoned checkout by cart fingerprint', [
                    'abandoned_id' => $abandoned->id,
                    'cart_id'      => $orderId,
                    'fingerprint'  => $fingerprint,
                ]);
            } elseif ($fpCandidates->count() > 1) {
                Log::warning('ShiprocketCheckoutWebhook: fingerprint collision — multiple candidates, skipping', [
                    'cart_id'     => $orderId,
                    'fingerprint' => $fingerprint,
                    'candidates'  => $fpCandidates->pluck('id')->toArray(),
                ]);
            }
        }

        // Timing fallback REMOVED — it caused cross-customer contamination when
        // two customers checked out within 5 minutes of each other.
        // The bridge table (shiprocket_checkout_ids) now handles ID linking reliably.

        // 6. No match found — create a new AC from webhook data (covers sessions where
        //    TokenController was never called, e.g. Fastrr direct widget, or where the
        //    webhook cart_id differs from every stored shiprocket_order_id).
        //    Only create at PAYMENT_INITIATED or ORDER_PLACED — these stages have full
        //    customer data (name, phone, address). INIT has phone only, which is
        //    insufficient for order recovery.
        if (!$abandoned && in_array($stage, ['PAYMENT_INITIATED', 'ORDER_PLACED', 'Payment Complete'])
            && ($customer['phone'] || $customer['name'])
        ) {
            $cartSnapshot = !empty($payload['items']) ? array_map(fn($i) => [
                'product_id'   => $i['product_id'] ?? $i['variant_id'] ?? null,
                'variant_id'   => $i['variant_id'] ?? null,
                'quantity'     => $i['quantity'] ?? 1,
                'price'        => $i['price'] ?? 0,
                'name'         => $i['name'] ?? $i['title'] ?? 'Product',
            ], $payload['items']) : [];

            $phoneClean = $customer['phone'] ? preg_replace('/\D/', '', $customer['phone']) : null;

            $abandoned = AbandonedCheckout::create([
                'source'              => 'shiprocket_checkout',
                'shiprocket_order_id' => $orderId,
                'name'                => $customer['name'],
                'email'               => $customer['email'],
                'phone'               => $customer['phone'],
                'phone_hash'          => $phoneClean ? hash('sha256', $phoneClean) : null,
                'cart_total'          => $payload['total_price'] ?? 0,
                'items_count'         => count($cartSnapshot),
                'step'                => 'webhook_initiated',
                'metadata'            => [
                    'shiprocket_cart_id'  => $orderId,
                    'cart_fingerprint'    => !empty($cartSnapshot)
                        ? TokenController::cartFingerprint($cartSnapshot) : null,
                ],
                'cart_snapshot'       => $cartSnapshot,
            ]);

            Log::info('ShiprocketCheckoutWebhook: created abandoned checkout from webhook (no prior AC found)', [
                'abandoned_id' => $abandoned->id,
                'cart_id'      => $orderId,
                'stage'        => $stage,
                'has_name'     => !empty($customer['name']),
                'has_phone'    => !empty($customer['phone']),
            ]);

            // Register in bridge so callback oid can find this AC
            $abandoned->registerShiprocketId($orderId, 'webhook');
        }

        if (!$abandoned) return null;

        // Register webhook cart_id in bridge so callback oid can find this AC
        $abandoned->registerShiprocketId($orderId, 'webhook');

        // Store the webhook's cart_id so future webhooks match directly (fallback 3)
        $meta = $abandoned->metadata ?? [];
        if (empty($meta['shiprocket_cart_id'])) {
            $meta['shiprocket_cart_id'] = $orderId;
        }

        $updates = [];
        if ($customer['name'] && empty($abandoned->name))   $updates['name']  = $customer['name'];
        if ($customer['email'] && empty($abandoned->email)) $updates['email'] = $customer['email'];
        if ($customer['phone'] && empty($abandoned->phone)) $updates['phone'] = $customer['phone'];

        // Capture Shiprocket pricing (discount, shipping, tax) — always keep latest values
        $totalDiscount = (float) ($payload['total_discount'] ?? $payload['payload']['total_discount'] ?? 0);
        $shippingPrice = (float) ($payload['shipping_price'] ?? $payload['payload']['shipping_price'] ?? 0);
        $taxAmount     = (float) ($payload['tax'] ?? $payload['payload']['tax'] ?? 0);
        $srTotalPrice  = (float) ($payload['total_price'] ?? $payload['payload']['total_price'] ?? 0);

        if ($totalDiscount > 0 || $shippingPrice > 0 || $taxAmount > 0 || $srTotalPrice > 0) {
            $meta['sr_pricing'] = [
                'total_price'    => $srTotalPrice,
                'total_discount' => $totalDiscount,
                'shipping_price' => $shippingPrice,
                'tax'            => $taxAmount,
                'net_payable'    => max(0, $srTotalPrice - $totalDiscount + $shippingPrice + $taxAmount),
                'updated_at'     => now()->toIso8601String(),
            ];
        }

        // Store full webhook data in metadata for debugging / recovery
        $meta['webhook_' . strtolower(str_replace(' ', '_', $stage))] = [
            'received_at' => now()->toIso8601String(),
            'stage'       => $stage,
            'customer'    => $customer,
        ];
        $updates['metadata'] = $meta;

        // Backfill cart_snapshot if currently empty and this webhook has items
        // (PAYMENT_INITIATED creates AC without items; ORDER_PLACED fills them in)
        if (empty($abandoned->cart_snapshot) && !empty($payload['items'])) {
            $updates['cart_snapshot'] = array_map(fn($i) => [
                'product_id'   => $i['product_id'] ?? $i['variant_id'] ?? null,
                'variant_id'   => $i['variant_id'] ?? null,
                'quantity'     => $i['quantity'] ?? 1,
                'price'        => $i['price'] ?? 0,
                'name'         => $i['name'] ?? $i['title'] ?? 'Product',
            ], $payload['items']);
            $updates['items_count'] = count($updates['cart_snapshot']);
        }

        if (!empty($updates)) {
            $abandoned->update($updates);
            Log::info('ShiprocketCheckoutWebhook: updated abandoned checkout', [
                'abandoned_id' => $abandoned->id,
                'order_id'     => $orderId,
                'stage'        => $stage,
                'fields'       => array_keys($updates),
            ]);
        }

        return $abandoned;
    }

    // ─── Order Sync ───────────────────────────────────────────────────────────

    private function syncOrder(string $orderId, array $customer, string $stage, ?string $paymentMode = null, ?float $paymentAmount = null): ?Order
    {
        $order = Order::where('shiprocket_order_id', $orderId)->first()
            ?? Order::whereJsonContains('metadata->shiprocket_checkout_id', $orderId)->first()
            ?? Order::whereJsonContains('metadata->shiprocket_cart_id', $orderId)->first();

        // Webhook cart_id != callback oid. Find the order through the bridge table.
        if (!$order) {
            $ac = AbandonedCheckout::findByShiprocketId($orderId);
            if ($ac && $ac->order_id) {
                $order = Order::find($ac->order_id);
            }
        }

        // Legacy fallback: metadata->shiprocket_cart_id (for pre-bridge ACs)
        if (!$order) {
            $ac = AbandonedCheckout::where('source', 'shiprocket_checkout')
                ->whereJsonContains('metadata->shiprocket_cart_id', $orderId)
                ->whereNotNull('order_id')
                ->first();
            if ($ac) {
                $order = Order::find($ac->order_id);
            }
        }

        if (!$order) return null;

        $updates = [];

        // Reconcile payment status against the webhook's authoritative payment_mode.
        // paymentMode is the normalised order type: COD | PARTIAL | PREPAID.
        // The callback sets ost=SUCCESS for COD too (and may run before any webhook),
        // so a freshly-created order may have the wrong status — the webhook fixes it
        // both ways: promote unconfirmed orders to 'paid', or demote wrong 'paid' to COD.
        $pmUpper = strtoupper((string) $paymentMode);

        if ($pmUpper === 'PREPAID' && $order->payment_status === 'pending') {
            // Webhook confirms a full online payment — promote to paid.
            // Strip any COD charge that was added while the order was unconfirmed.
            $meta = $order->metadata ?? [];
            $existingCod = (float) ($meta['cod_charge'] ?? 0);
            $newTotal = max(0, (float) $order->total - $existingCod);
            $updates['payment_status'] = 'paid';
            $updates['total']          = $newTotal;
            $updates['shipping_cost']  = max(0, (float) $order->shipping_cost - $existingCod);
            $updates['paid_amount']    = $newTotal;
            $meta['payment_method'] = 'shiprocket_checkout';
            $meta['cod_charge'] = null;
            $meta['payment_status_corrected_at'] = now()->toIso8601String();
            $updates['metadata'] = $meta;
            Log::info('ShiprocketCheckoutWebhook: confirmed PREPAID from webhook', [
                'order_id' => $order->id,
            ]);
        } elseif (in_array($pmUpper, ['COD', 'PARTIAL'], true) && $order->payment_status === 'paid') {
            // Webhook reveals COD/partial but the order was wrongly marked paid.
            // Correct the payment status only — do NOT add a COD charge; the order
            // total must keep mirroring what Shiprocket actually charged.
            $meta = $order->metadata ?? [];
            if ($paymentAmount && $paymentAmount > 0) {
                $updates['payment_status'] = 'pending';
                $updates['paid_amount']    = $paymentAmount;
            } else {
                $updates['payment_status'] = 'pending';
                $updates['paid_amount']    = 0;
            }
            $meta['payment_method'] = $paymentAmount > 0 ? 'shiprocket_cod_partial' : 'shiprocket_cod';
            $meta['payment_status_corrected_at'] = now()->toIso8601String();
            $updates['metadata'] = $meta;
            Log::info('ShiprocketCheckoutWebhook: corrected COD payment status from webhook', [
                'order_id'     => $order->id,
                'payment_mode' => $paymentMode,
                'advance'      => $paymentAmount,
            ]);
        }

        if ($customer['name'] && empty($order->guest_name))   $updates['guest_name']  = $customer['name'];
        if ($customer['email'] && empty($order->guest_email)) $updates['guest_email'] = $customer['email'];
        if ($customer['phone'] && empty($order->guest_phone)) $updates['guest_phone'] = $customer['phone'];

        // Build shipping address snapshot if we now have address data.
        // Accept partial address (city/pincode without street) — same as CallbackController.
        $snapshot = $order->shipping_address_snapshot ?? [];
        $snapshotMissingAddress = empty($snapshot['address_line_1'] ?? '') && empty($snapshot['city'] ?? '') && empty($snapshot['postal_code'] ?? '');
        if ($snapshotMissingAddress && ($customer['address'] || $customer['city'] || $customer['pincode'])) {
            $updates['shipping_address_snapshot'] = [
                'name'           => $customer['name']    ?? $order->guest_name    ?? '',
                'phone'          => $customer['phone']   ?? $order->guest_phone   ?? '',
                'address_line_1' => $customer['address'] ?? '',
                'address_line_2' => $customer['address_2'] ?? '',
                'city'           => $customer['city']    ?? '',
                'state'          => $customer['state']   ?? '',
                'postal_code'    => $customer['pincode'] ?? '',
                'country'        => $customer['country'] ?? 'India',
            ];
        }

        // Auto-link to an existing user account by phone or email
        if (empty($order->user_id)) {
            $matched = $this->findUserByPhoneOrEmail($customer['phone'], $customer['email']);
            if ($matched) {
                $updates['user_id'] = $matched->id;
                Log::info('ShiprocketCheckoutWebhook: linked order to existing user', [
                    'order_id' => $order->id,
                    'user_id'  => $matched->id,
                ]);
            }
        }

        if (!empty($updates)) {
            $order->update($updates);
            Log::info('ShiprocketCheckoutWebhook: updated order', [
                'order_id' => $order->id,
                'stage'    => $stage,
                'fields'   => array_keys($updates),
            ]);
        }

        return $order;
    }

    // ─── Phone-only Webhook (no order_id yet) ────────────────────────────────

    private function updateRecentCheckoutByPhone(string $phone, array $customer): void
    {
        // Match by phone_hash first (reliable). Fall back to most recent AC
        // only if exactly one exists (avoids cross-customer contamination).
        $phoneHash = hash('sha256', preg_replace('/\D/', '', $phone));
        $abandoned = AbandonedCheckout::where('source', 'shiprocket_checkout')
            ->where('phone_hash', $phoneHash)
            ->where('created_at', '>=', now()->subHour())
            ->whereNull('order_id')
            ->latest()
            ->first();

        // Timing fallback removed — caused cross-customer contamination.

        if (!$abandoned) return;

        $updates = [];
        if (empty($abandoned->phone))                   $updates['phone'] = $phone;
        if ($customer['name'] && empty($abandoned->name))   $updates['name']  = $customer['name'];
        if ($customer['email'] && empty($abandoned->email)) $updates['email'] = $customer['email'];

        if (!empty($updates)) {
            $abandoned->update($updates);
            Log::info('ShiprocketCheckoutWebhook: matched abandoned checkout by phone (no order_id)', [
                'abandoned_id' => $abandoned->id,
                'phone'        => $phone,
            ]);
        }
    }

    // ─── AC from Webhook Payload ─────────────────────────────────────────────

    /**
     * Create an AbandonedCheckout from a payment-confirmed webhook when no AC
     * was matched by any of the standard strategies. This prevents order loss
     * when fingerprint collisions or ID mismatches block AC matching.
     */
    private function createAbandonedCheckoutFromPayload(string $orderId, array $customer, array $payload): ?AbandonedCheckout
    {
        $items = $payload['items'] ?? $payload['cart_data']['items'] ?? [];
        $cartSnapshot = array_map(fn($i) => [
            'product_id'   => $i['product_id'] ?? $i['variant_id'] ?? null,
            'variant_id'   => $i['variant_id'] ?? null,
            'quantity'     => (int) ($i['quantity'] ?? 1),
            'price'        => (float) ($i['price'] ?? 0),
            'name'         => $i['name'] ?? $i['title'] ?? 'Product',
        ], $items);

        if (empty($cartSnapshot)) {
            Log::warning('ShiprocketCheckoutWebhook: cannot create AC from payload — no items', [
                'cart_id' => $orderId,
            ]);
            return null;
        }

        $phoneClean = $customer['phone'] ? preg_replace('/\D/', '', $customer['phone']) : null;

        $ac = AbandonedCheckout::create([
            'source'              => 'shiprocket_checkout',
            'shiprocket_order_id' => $orderId,
            'name'                => $customer['name'],
            'email'               => $customer['email'],
            'phone'               => $customer['phone'],
            'phone_hash'          => $phoneClean ? hash('sha256', $phoneClean) : null,
            'cart_total'          => $payload['total_price'] ?? 0,
            'items_count'         => count($cartSnapshot),
            'step'                => 'webhook_payment_confirmed',
            'metadata'            => [
                'shiprocket_cart_id' => $orderId,
                'created_from'       => 'payment_webhook_fallback',
            ],
            'cart_snapshot'       => $cartSnapshot,
        ]);

        $ac->registerShiprocketId($orderId, 'webhook_fallback');

        Log::info('ShiprocketCheckoutWebhook: created AC from payment webhook (no prior AC matched)', [
            'abandoned_id' => $ac->id,
            'cart_id'      => $orderId,
            'items_count'  => count($cartSnapshot),
        ]);

        return $ac;
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Returns true when no completed order exists for this phone/email —
     * i.e. this is the customer's first purchase on this tenant.
     */
    private function isNewCustomer(?string $phone, ?string $email): bool
    {
        if (empty($phone) && empty($email)) return true;

        $cleanPhone = $phone ? preg_replace('/\D/', '', $phone) : null;
        $shortPhone = $cleanPhone && strlen($cleanPhone) > 10 ? substr($cleanPhone, -10) : $cleanPhone;

        return !Order::where(function ($q) use ($email, $cleanPhone, $shortPhone) {
            if ($email)      $q->orWhere('guest_email', $email);
            if ($cleanPhone) $q->orWhere('guest_phone', $cleanPhone);
            if ($shortPhone && $shortPhone !== $cleanPhone) $q->orWhere('guest_phone', $shortPhone);
        })->whereIn('payment_status', ['paid', 'pending', 'partial'])->exists();
    }

    private function findUserByPhoneOrEmail(?string $phone, ?string $email): ?\App\Models\User
    {
        if (empty($phone) && empty($email)) return null;

        $cleanPhone = $phone ? preg_replace('/\D/', '', $phone) : null;
        $shortPhone = $cleanPhone && strlen($cleanPhone) > 10 ? substr($cleanPhone, -10) : $cleanPhone;

        return \App\Models\User::where(function ($q) use ($email, $cleanPhone, $shortPhone) {
            if ($email)      $q->orWhere('email', $email);
            if ($cleanPhone) $q->orWhere('phone', $cleanPhone);
            if ($shortPhone && $shortPhone !== $cleanPhone) $q->orWhere('phone', $shortPhone);
        })->first();
    }

    private function findOrCreateUserForOrder(?string $phone, ?string $email, ?string $name): ?\App\Models\User
    {
        $user = $this->findUserByPhoneOrEmail($phone, $email);
        if ($user) return $user;

        $cleanPhone = $phone ? preg_replace('/\D/', '', $phone) : null;
        $shortPhone = $cleanPhone && strlen($cleanPhone) > 10 ? substr($cleanPhone, -10) : $cleanPhone;

        if (empty($shortPhone)) return null;

        // Use real email if available (enables OTP login); fall back to placeholder
        $host = parse_url(config('app.url'), PHP_URL_HOST) ?: 'store.local';
        $userEmail = ($email && filter_var($email, FILTER_VALIDATE_EMAIL))
            ? $email
            : $shortPhone . '@phone.' . $host;

        try {
            return \App\Models\User::create([
                'name'     => $name ?: 'Customer',
                'phone'    => $shortPhone,
                'email'    => $userEmail,
                'password' => bcrypt(\Illuminate\Support\Str::random(32)),
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // Race condition — user was just created by another request
            return $this->findUserByPhoneOrEmail($phone, $email);
        }
    }

    // ─── Webhook Fallback Order Creation ─────────────────────────────────────

    /**
     * Detect if this is a payment-confirmed webhook.
     * Matches SUCCESS (status=SUCCESS + payment_status=Success),
     * ORDER_PLACED (order placed with payment collected), and
     * Payment Complete (final confirmation).
     */
    private function isPaymentConfirmedWebhook(array $rawPayload): bool
    {
        $data = $rawPayload['payload'] ?? $rawPayload;

        $status = strtoupper($data['status'] ?? '');
        $stage  = strtoupper(str_replace(' ', '_', $data['latest_stage'] ?? $data['stage'] ?? ''));

        // status=SUCCESS means the checkout completed and the order was placed.
        // This covers PREPAID *and* COD: a COD order's status is still SUCCESS even
        // though its payment_status is not "success" (no online payment was made).
        // The earlier `payment_status === 'success'` guard silently dropped every
        // COD order that arrived without a callback — they never became orders.
        if ($status === 'SUCCESS') {
            return true;
        }

        // ORDER_PLACED or PAYMENT_COMPLETE — Shiprocket confirmed the order was placed.
        if (in_array($stage, ['ORDER_PLACED', 'PAYMENT_COMPLETE'])) {
            return true;
        }

        return false;
    }

    /**
     * Create an order from a confirmed webhook (SUCCESS / ORDER_PLACED / Payment Complete)
     * when the callback redirect never fired.
     * Handles COD / partial / prepaid payment detection.
     */
    private function createOrderFromWebhook(AbandonedCheckout $abandoned, array $rawPayload, array $customer): ?Order
    {
        $data = $rawPayload['payload'] ?? $rawPayload;

        // The rich SUCCESS webhook carries platform_order_id / order_id; the
        // real-time ORDER_PLACED webhook carries only cart_id. Fall back to it
        // so stage-webhook-only tenants (e.g. Ayurvexa) still create orders.
        $shiprocketOrderId = $data['platform_order_id'] ?? $data['order_id'] ?? $data['cart_id'] ?? null;
        if (!$shiprocketOrderId) return null;

        // Merge customer fields across ALL events for this AC. Shiprocket's
        // ORDER_PLACED stage does NOT carry email — only PAYMENT_INITIATED does.
        // For prepaid orders the gap between PAYMENT_INITIATED (which has the
        // email) and ORDER_PLACED (which doesn't) is the payment-processing
        // time — up to 90+ seconds. Without this merge, the order is created
        // with empty guest_email and the listener falls back to user.email
        // (often a stale phone-matched team account like Ayurvexa@gmail.com).
        // Saw it 2026-06-02 22:20 with order 132 / Himanshu Chalana.
        if (empty($customer['email']) || empty($customer['phone']) || empty($customer['name'])) {
            $stagedEvents = ShiprocketCheckoutEvent::where('abandoned_checkout_id', $abandoned->id)
                ->where('is_duplicate', false)
                ->orderByRaw("CASE stage
                    WHEN 'PAYMENT_INITIATED' THEN 1
                    WHEN 'ORDER_PLACED'      THEN 2
                    WHEN 'PHONE_RECEIVED'    THEN 3
                    WHEN 'INIT'              THEN 4 ELSE 5 END")
                ->orderByDesc('received_at')
                ->get();
            $customer['email'] = $customer['email'] ?: $stagedEvents->pluck('email')->filter()->first();
            $customer['phone'] = $customer['phone'] ?: $stagedEvents->pluck('phone')->filter()->first();
            $customer['name']  = $customer['name']  ?: $stagedEvents->pluck('full_name')->filter()->first();
            Log::info('ShiprocketCheckoutWebhook: customer fields merged from events table in fallback', [
                'abandoned_id' => $abandoned->id,
                'has_email'    => !empty($customer['email']),
                'has_phone'    => !empty($customer['phone']),
                'has_name'     => !empty($customer['name']),
            ]);
        }

        // Cart snapshot — prefer webhook items (source of truth for THIS transaction)
        // over AC's cart_snapshot (which may be stale if the AC belongs to an older session
        // that got matched by phone_hash).
        $cartSnapshot = null;

        // Priority 1: webhook cart_data.items (SUCCESS webhook includes these)
        if (!empty($data['cart_data']['items'])) {
            $cartSnapshot = array_map(fn($i) => [
                'product_id' => $i['variant_id'] ?? null,
                'variant_id' => $i['variant_id'] ?? null,
                'quantity'   => (int) ($i['quantity'] ?? 1),
                'price'      => (float) ($i['price'] ?? 0),
                'name'       => $i['name'] ?? 'Product',
                'sku'        => $i['sku'] ?? '',
            ], $data['cart_data']['items']);
        }

        // Priority 2: webhook items array (ORDER_PLACED includes these)
        if (empty($cartSnapshot) && !empty($data['items'])) {
            $cartSnapshot = array_map(fn($i) => [
                'product_id' => $i['product_id'] ?? $i['variant_id'] ?? null,
                'variant_id' => $i['variant_id'] ?? null,
                'quantity'   => (int) ($i['quantity'] ?? 1),
                'price'      => (float) ($i['price'] ?? 0),
                'name'       => $i['name'] ?? $i['title'] ?? 'Product',
                'sku'        => $i['sku'] ?? '',
            ], $data['items']);
        }

        // Priority 3: stored checkout events (INIT events have items)
        if (empty($cartSnapshot)) {
            $cartIdForLookup = $data['cart_id'] ?? ($abandoned->metadata['shiprocket_cart_id'] ?? $abandoned->shiprocket_order_id);
            if ($cartIdForLookup) {
                $eventWithItems = ShiprocketCheckoutEvent::where('cart_id', $cartIdForLookup)
                    ->whereNotNull('items')
                    ->where('is_duplicate', false)
                    ->latest()
                    ->first();
                if ($eventWithItems && !empty($eventWithItems->items)) {
                    $cartSnapshot = $eventWithItems->items;
                }
            }
        }

        // Priority 4: AC's cart_snapshot (last resort — may be from an older session)
        if (empty($cartSnapshot)) {
            $cartSnapshot = $abandoned->cart_snapshot;
        }

        if (empty($cartSnapshot)) {
            Log::warning('ShiprocketCheckoutWebhook: cannot create fallback order — no cart_snapshot', [
                'abandoned_id'       => $abandoned->id,
                'shiprocket_order_id' => $shiprocketOrderId,
            ]);
            return null;
        }

        // Validate cart contains real products (positive price, valid quantity).
        // Filters out malformed payloads / bot probes that yield items with price=0.
        $validItems = array_filter($cartSnapshot, function ($i) {
            $hasId    = !empty($i['product_id'] ?? null) || !empty($i['variant_id'] ?? null);
            $hasQty   = (int) ($i['quantity'] ?? 0) > 0;
            $hasPrice = (float) ($i['price'] ?? 0) > 0;
            return $hasId && $hasQty && $hasPrice;
        });
        if (empty($validItems)) {
            Log::warning('ShiprocketCheckoutWebhook: refusing fallback order — cart has no valid items', [
                'abandoned_id'        => $abandoned->id,
                'shiprocket_order_id' => $shiprocketOrderId,
                'raw_item_count'      => count($cartSnapshot),
            ]);
            return null;
        }

        try {
            $cartIdForLock = $data['cart_id'] ?? ($abandoned->metadata['shiprocket_cart_id'] ?? null);
            return DB::transaction(function () use ($abandoned, $data, $shiprocketOrderId, $cartIdForLock, $cartSnapshot, $customer) {
                // Lock on cart_id when available — Shiprocket may emit multiple
                // order_ids for the same cart (pending+confirmed), but cart_id is stable.
                $lockKey = $cartIdForLock ?: $shiprocketOrderId;
                DbCompat::advisoryLock($lockKey);

                // Idempotency: check by order_id, then by cart_id within 30 min.
                // Expanded to kill the duplicate-order race where the browser callback
                // and this webhook fallback fire for the SAME checkout with DIFFERENT
                // identifiers (callback `oid` ≠ webhook `cart_id`, off by a char).
                // Look up via the abandoned_checkout's already-linked order, plus
                // every Shiprocket id we know of for this AC, plus a session_id
                // sweep covering the last 5 minutes.
                $lookupShiprocketIds = array_unique(array_filter([
                    $shiprocketOrderId,
                    $cartIdForLock,
                    $abandoned->shiprocket_order_id,
                    $abandoned->metadata['shiprocket_cart_id'] ?? null,
                ]));

                $existing = $abandoned->order_id
                        ? Order::find($abandoned->order_id)
                        : null;
                if (!$existing && !empty($lookupShiprocketIds)) {
                    $existing = Order::whereIn('shiprocket_order_id', $lookupShiprocketIds)->first();
                }
                if (!$existing && !empty($lookupShiprocketIds)) {
                    $existing = Order::where('source', 'api')
                        ->where(function ($q) use ($lookupShiprocketIds) {
                            foreach ($lookupShiprocketIds as $id) {
                                $q->orWhereJsonContains('metadata->shiprocket_checkout_id', $id)
                                  ->orWhereJsonContains('metadata->shiprocket_cart_id', $id);
                            }
                        })
                        ->where('created_at', '>', now()->subMinutes(30))
                        ->first();
                }
                if (!$existing) {
                    // Last resort — FUZZY MATCH. Shiprocket generates the callback `oid`
                    // and the webhook `cart_id` that differ in the last few characters
                    // for the same checkout (e.g. 6a1ed3b3…/6a1ed3b4…). When neither
                    // id-based nor session-id lookup catches it, fall back to:
                    //   recent + pending + empty-email + same total + source='api'
                    // This identifies the callback's "Pending Shiprocket sync" order
                    // so we attach to it instead of duplicating.
                    $expectedTotal = (float) ($data['total_payable']
                                           ?? $data['total_amount_payable']
                                           ?? $data['total_price']
                                           ?? 0);
                    if ($expectedTotal > 0) {
                        $existing = Order::where('source', 'api')
                            ->where('payment_status', 'pending')
                            ->whereNull('guest_email')
                            ->whereBetween('total', [$expectedTotal - 0.01, $expectedTotal + 0.01])
                            ->where('created_at', '>', now()->subMinutes(10))
                            ->latest()
                            ->first();
                        if ($existing) {
                            Log::info('ShiprocketCheckoutWebhook: fuzzy-matched empty order by total+time (callback/webhook id mismatch)', [
                                'order_id'        => $existing->id,
                                'order_number'    => $existing->order_number,
                                'webhook_cart_id' => $cartIdForLock,
                                'expected_total'  => $expectedTotal,
                            ]);
                        }
                    }
                }

                if ($existing) {
                    // Found a callback-created order. Enrich it with whatever the
                    // webhook now provides that the callback couldn't supply
                    // (typically email — only PAYMENT_INITIATED carries it).
                    $updates = [];
                    if (empty($existing->guest_email) && !empty($customer['email'])) {
                        $updates['guest_email'] = $customer['email'];
                    }
                    if (empty($existing->guest_phone) && !empty($customer['phone'])) {
                        $updates['guest_phone'] = $customer['phone'];
                    }
                    if (empty($existing->guest_name) && !empty($customer['name'])) {
                        $updates['guest_name'] = $customer['name'];
                    }
                    $existingMeta = $existing->metadata ?? [];
                    if (empty($existingMeta['shiprocket_cart_id']) && !empty($cartIdForLock)) {
                        $existingMeta['shiprocket_cart_id'] = $cartIdForLock;
                        $updates['metadata'] = $existingMeta;
                    }
                    if (!empty($updates)) {
                        $existing->update($updates);
                        Log::info('ShiprocketCheckoutWebhook: enriched existing order from webhook (no duplicate created)', [
                            'order_id' => $existing->id,
                            'order_number' => $existing->order_number,
                            'fields_updated' => array_keys($updates),
                            'matched_via' => $abandoned->order_id ? 'ac_order_id' : 'shiprocket_id_or_session',
                        ]);
                    }

                    // Link THIS AC to the order + mark recovered. Without this the
                    // admin "Abandoned Checkouts" page shows the customer-bearing AC
                    // as "Not recovered" while the anonymous early AC sits next to
                    // it as "Recovered" — confusing and operationally wrong.
                    if (empty($abandoned->order_id)) {
                        $abandoned->update([
                            'order_id'     => $existing->id,
                            'recovered'    => true,
                            'recovered_at' => now(),
                            'step'         => 'completed',
                        ]);
                    }
                    // Also link any session_id siblings that aren't yet linked —
                    // these are the page-load / mid-checkout ACs for the same
                    // browser session that should follow this AC's fate.
                    if ($abandoned->session_id) {
                        AbandonedCheckout::where('session_id', $abandoned->session_id)
                            ->where('id', '!=', $abandoned->id)
                            ->whereNull('order_id')
                            ->where('created_at', '>', now()->subHour())
                            ->update([
                                'order_id'     => $existing->id,
                                'recovered'    => true,
                                'recovered_at' => now(),
                                'step'         => 'completed',
                            ]);
                    }
                    // If the existing order hasn't been notified yet AND we now
                    // have the customer email, re-fire the listener so the
                    // confirmation goes out without waiting for the SyncShiprocket
                    // retry loop.
                    if (empty(($existing->metadata['confirmation_sent_at'] ?? null))
                        && !empty($existing->fresh()->guest_email)) {
                        try {
                            OrderPlaced::dispatch($existing->fresh(), 'shiprocket_checkout');
                        } catch (\Throwable $e) {
                            Log::warning('ShiprocketCheckoutWebhook: re-dispatch OrderPlaced failed after enrich', [
                                'order_id' => $existing->id,
                                'error'    => $e->getMessage(),
                            ]);
                        }
                    }
                    return $existing;
                }

                $subtotal = 0.0;
                foreach ($cartSnapshot as $item) {
                    $subtotal += ((float) ($item['price'] ?? 0)) * ((int) ($item['quantity'] ?? 1));
                }

                // Use Shiprocket's pricing if available. Field names differ between
                // the SUCCESS webhook (coupon_discount / shipping_charges) and the
                // real-time ORDER_PLACED webhook (total_discount / shipping_price).
                $discount     = (float) ($data['coupon_discount'] ?? $data['total_discount'] ?? 0);
                $shippingCost = (float) ($data['shipping_charges'] ?? $data['shipping_price'] ?? 0);
                $totalPayable = (float) ($data['total_amount_payable'] ?? 0);
                $total        = $totalPayable > 0 ? $totalPayable : ($subtotal - $discount + $shippingCost);

                // Payment info — detect COD / partial / prepaid.
                // Do NOT default to 'prepaid' — a missing paymentMode usually
                // means the webhook arrived early (ORDER_PLACED) before payment
                // was settled, OR Shiprocket sent a sparse payload for a COD
                // order. Defaulting to 'prepaid' wrongly marks COD orders as paid.
                $paymentDetails = $data['paymentDetails'] ?? [];
                $payments       = $data['payments'] ?? [];
                $firstPayment   = $payments[0] ?? [];
                $paymentMethod  = strtolower(
                    $paymentDetails['paymentMode']
                    ?? $firstPayment['payment_method']
                    ?? $data['payment_type']
                    ?? 'unknown'
                );

                // Determine actual amount paid ONLINE.
                // payments[] may contain a COD line (the balance due on delivery) —
                // that is NOT money collected online, so it must be excluded.
                $isCod = in_array($paymentMethod, ['cod', 'cash_on_delivery', 'cash on delivery']);
                $onlinePaid = isset($paymentDetails['amount'])
                    ? (float) $paymentDetails['amount']
                    : 0.0;
                if ($onlinePaid <= 0 && !empty($payments)) {
                    foreach ($payments as $pmt) {
                        $pmtMethod = strtolower((string) ($pmt['payment_method'] ?? $pmt['gateway'] ?? ''));
                        if (!in_array($pmtMethod, ['cod', 'cash_on_delivery', 'cash on delivery'], true)) {
                            $onlinePaid += (float) ($pmt['amount'] ?? 0);
                        }
                    }
                }

                if ($isCod && $onlinePaid <= 0) {
                    // Full COD — nothing paid online
                    $paidAmount         = 0;
                    $paymentStatusValue = 'pending';
                } elseif ($isCod || ($onlinePaid > 0 && $onlinePaid < $total)) {
                    // Partial COD / COD with advance payment
                    $paidAmount         = $onlinePaid;
                    $paymentStatusValue = 'partial';
                } elseif ($paymentMethod !== 'unknown' && $onlinePaid >= $total) {
                    // Fully prepaid (UPI / Card / NetBanking) — only when we have
                    // explicit method AND explicit paid amount evidence.
                    $paidAmount         = $total;
                    $paymentStatusValue = 'paid';
                } else {
                    // Insufficient evidence: method unknown OR no online payment recorded.
                    // Mark pending — a later webhook (or admin) can mark it paid once
                    // payment actually clears. This is safer than wrongly claiming paid.
                    $paidAmount         = 0;
                    $paymentStatusValue = 'pending';
                }

                // COD charge is NOT added by us — the order total mirrors what
                // Shiprocket reported (total_price / shipping_price / discount),
                // which is exactly what the customer is billed and the delivery
                // agent collects. A COD fee must be set in the Shiprocket Checkout
                // dashboard so it lands in those pricing fields.
                $codCharge = 0.0;

                $cartId = $data['cart_id'] ?? ($abandoned->metadata['shiprocket_cart_id'] ?? null);

                $order = Order::create([
                    'user_id'              => $abandoned->user_id,
                    'guest_name'           => $customer['name'],
                    'guest_email'          => $customer['email'] ?? $data['email'] ?? null,
                    'guest_phone'          => $customer['phone'] ?? $data['phone'] ?? null,
                    'status'               => 'confirmed',
                    'payment_status'       => $paymentStatusValue,
                    'subtotal'             => $subtotal,
                    'discount'             => $discount,
                    'shipping_cost'        => $shippingCost,
                    'tax'                  => 0,
                    'total'                => $total,
                    'paid_amount'          => $paidAmount,
                    'source'               => 'api',
                    'shiprocket_order_id'  => $shiprocketOrderId,
                    'shipping_address_snapshot' => array_filter([
                        'name'           => $customer['name'] ?? '',
                        'phone'          => $customer['phone'] ?? '',
                        'address_line_1' => $customer['address'] ?? '',
                        'address_line_2' => $customer['address_2'] ?? '',
                        'city'           => $customer['city'] ?? '',
                        'state'          => $customer['state'] ?? '',
                        'postal_code'    => $customer['pincode'] ?? '',
                        'country'        => $customer['country'] ?? 'India',
                    ]),
                    'metadata' => array_filter([
                        'payment_method'         => $paymentMethod,
                        'payment_gateway'        => $paymentDetails['paymentGateway'] ?? $firstPayment['gateway'] ?? 'shiprocket',
                        'transaction_id'         => $paymentDetails['transactionId'] ?? $firstPayment['pg_transaction_id'] ?? $firstPayment['txn_id'] ?? null,
                        'shiprocket_checkout_id' => $shiprocketOrderId,
                        'shiprocket_cart_id'     => $cartId,
                        'shiprocket_ost'         => $data['latest_stage'] ?? $data['stage'] ?? 'SUCCESS',
                        'cod_advance_amount'     => $paidAmount < $total ? $paidAmount : null,
                        'cod_charge'             => $codCharge ?: null,
                        'fastrr_order_id'        => $data['fastrr_order_id'] ?? null,
                        'created_from'           => 'webhook_fallback',
                    ]),
                ]);

                // Create order items + decrement stock
                foreach ($cartSnapshot as $item) {
                    $product = Product::find($item['product_id'] ?? null)
                        ?? Product::find($item['variant_id'] ?? null);

                    OrderItem::create([
                        'order_id'     => $order->id,
                        'product_id'   => $product?->id,
                        'product_name' => $product?->name ?? $item['name'] ?? 'Product',
                        'sku'          => $product?->sku ?? $item['sku'] ?? '',
                        'quantity'     => (int) ($item['quantity'] ?? 1),
                        'mrp'          => $product?->mrp ?? $item['price'] ?? 0,
                        'price'        => (float) ($item['price'] ?? 0),
                        'tax'          => 0,
                        'discount'     => 0,
                        'total'        => ((float) ($item['price'] ?? 0)) * ((int) ($item['quantity'] ?? 1)),
                    ]);

                    if ($product) {
                        $qty = (int) ($item['quantity'] ?? 1);
                        $locked = Product::where('id', $product->id)->lockForUpdate()->first();
                        if ($locked && $locked->stock_quantity >= $qty) {
                            $locked->decrement('stock_quantity', $qty);
                            $locked->increment('sales_count', $qty);
                            if ($locked->fresh()->stock_quantity <= 0) {
                                $locked->update(['stock_status' => 'out_of_stock']);
                            }
                        } else {
                            $product->increment('sales_count', $qty);
                        }
                    }
                }

                // Mark abandoned checkout as recovered
                $abandoned->update([
                    'step'         => 'completed',
                    'recovered'    => true,
                    'order_id'     => $order->id,
                    'recovered_at' => now(),
                ]);

                // Link to user account — find existing or auto-create
                if (empty($order->user_id)) {
                    $user = $this->findOrCreateUserForOrder($customer['phone'] ?? null, $customer['email'] ?? null, $customer['name'] ?? null);
                    if ($user) {
                        $order->update(['user_id' => $user->id]);
                    }
                }

                // Dispatch OrderPlaced event
                $order->load('items.product', 'user');
                try {
                    OrderPlaced::dispatch($order, 'shiprocket_checkout');
                } catch (\Throwable $e) {
                    Log::error('ShiprocketCheckoutWebhook: OrderPlaced event failed (fallback)', [
                        'order_id' => $order->id,
                        'error'    => $e->getMessage(),
                    ]);
                }

                return $order;
            });
        } catch (\Throwable $e) {
            Log::error('ShiprocketCheckoutWebhook: fallback order creation failed', [
                'abandoned_id'        => $abandoned->id,
                'shiprocket_order_id' => $shiprocketOrderId,
                'error'               => $e->getMessage(),
            ]);
            return null;
        }
    }

    // ─── Meta CAPI Purchase (dedup-guarded) ─────────────────────────────────

    private function fireCapiPurchase(Order $order, Request $request, ?AbandonedCheckout $abandoned = null): void
    {
        $meta = $order->metadata ?? [];

        // Guard: skip if callback or a previous webhook already fired CAPI
        if (!empty($meta['capi_sent_at'])) {
            Log::info('ShiprocketCheckoutWebhook: CAPI already sent, skipping', [
                'order_id'    => $order->id,
                'capi_source' => $meta['capi_source'] ?? 'unknown',
            ]);
            return;
        }

        $order->load('items.product', 'user');

        // Use order_number as event_id — matches browser pixel on success page for Meta dedup
        $eventId = $order->order_number;

        // Retrieve stored fbc/fbp from abandoned checkout (webhook has no browser cookies)
        $acMeta = $abandoned?->metadata ?? [];
        $fbCookieFallback = array_filter([
            'fbc' => $acMeta['fbc'] ?? null,
            'fbp' => $acMeta['fbp'] ?? null,
        ]);

        try {
            app(AnalyticsService::class)->trackPurchase($order, $request, $eventId, $fbCookieFallback);

            $meta['capi_sent_at'] = now()->toIso8601String();
            $meta['fb_event_id']  = $eventId;
            $meta['capi_source']  = 'webhook';
            $order->metadata = $meta;
            $order->save();

            Log::info('ShiprocketCheckoutWebhook: CAPI Purchase fired', [
                'order_id'     => $order->id,
                'order_number' => $order->order_number,
                'event_id'     => $eventId,
            ]);
        } catch (\Throwable $e) {
            Log::error('ShiprocketCheckoutWebhook: CAPI Purchase failed', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }
}
