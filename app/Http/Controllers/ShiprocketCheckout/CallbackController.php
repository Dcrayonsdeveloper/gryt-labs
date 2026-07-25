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
use App\Services\ShiprocketCheckout\OstMapper;
use App\Services\ShiprocketCheckout\ShiprocketCheckoutService;
use App\Services\ShiprocketCheckout\StockHoldService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Handles the Shiprocket Checkout SDK return (success / COD / failed).
 *
 * After the customer completes (or abandons) checkout, Shiprocket redirects to:
 *   GET /checkout/success/shiprocket?oid=<hex>&ost=<status>
 *
 * ost values and their meaning:
 *   SUCCESS           → full prepaid (UPI / card / net banking)
 *   COD / COD_PLACED / ORDER_PLACED → cash on delivery
 *   PARTIAL_PAID / PARTIAL_COD / ADVANCE_PAID → COD with advance paid
 *   FAILED / CANCELLED / PAYMENT_FAILED / PAYMENT_CANCELLED → terminal failure
 *   (empty)           → treat as COD
 *
 * This controller:
 *   1. Validates ost (shows failed view for terminal failures)
 *   2. Creates an Order from the AbandonedCheckout cart_snapshot (idempotent)
 *   3. Sets payment_status / payment_method correctly via OstMapper
 *   4. Syncs customer details from Shiprocket API (best-effort + queued retry)
 *   5. Dispatches OrderPlaced event for email + analytics
 *
 * Route: GET /checkout/success/shiprocket
 */
class CallbackController extends Controller
{
    public function __construct(
        private OstMapper                 $ostMapper,
        private ShiprocketCheckoutService $checkout,
        private StockHoldService          $stockHold,
    ) {}

    public function __invoke(Request $request): View
    {
        $shiprocketOrderId = $request->query('oid');
        $ost               = (string) $request->query('ost', '');

        Log::info('ShiprocketCheckout: callback received', [
            'params' => $request->query(),
        ]);

        // Terminal failures — show failed view, do NOT create an order
        if (!$shiprocketOrderId || $this->ostMapper->isFailed($ost)) {
            return view('checkout.failed');
        }

        // Find the AbandonedCheckout that tracks this session.
        // 1. Bridge lookup — matches ANY Shiprocket ID (token order_id, webhook cart_id, or prior callback oid)
        $abandoned = AbandonedCheckout::findByShiprocketId($shiprocketOrderId);

        // 2. Direct match by shiprocket_order_id column (legacy)
        if (!$abandoned) {
            $abandoned = AbandonedCheckout::where('shiprocket_order_id', $shiprocketOrderId)->first();
        }

        // 3. Fallback: match by session when shiprocket_order_id wasn't stored at token-time
        if (!$abandoned) {
            $abandoned = AbandonedCheckout::where('session_id', session()->getId())
                ->where('source', 'shiprocket_checkout')
                ->whereNull('order_id')
                ->latest()
                ->first();

            if ($abandoned) {
                Log::info('ShiprocketCheckout: matched abandoned checkout by session', [
                    'abandoned_id'       => $abandoned->id,
                    'shiprocket_order_id'=> $shiprocketOrderId,
                ]);
            }
        }

        // Register callback oid in bridge + update AC's shiprocket_order_id
        if ($abandoned) {
            $abandoned->registerShiprocketId($shiprocketOrderId, 'callback');
            if ($abandoned->shiprocket_order_id !== $shiprocketOrderId) {
                $abandoned->update(['shiprocket_order_id' => $shiprocketOrderId]);
            }
        }

        // Shiprocket sometimes issues different order IDs for the same browser session:
        // the ID sent in webhook events differs from the final `oid` in the callback URL.
        // If the matched checkout has no customer data, look for a sibling checkout on the
        // same session that was enriched by earlier webhook events.
        $siblingWithData = null;
        if ($abandoned && empty($abandoned->name) && empty($abandoned->email) && empty($abandoned->phone)) {
            $siblingWithData = AbandonedCheckout::where('session_id', $abandoned->session_id)
                ->where('source', 'shiprocket_checkout')
                ->where('id', '!=', $abandoned->id)
                ->whereNotNull('name')
                ->latest()
                ->first();

            if ($siblingWithData) {
                Log::info('ShiprocketCheckout: using sibling checkout for customer data', [
                    'matched_id' => $abandoned->id,
                    'sibling_id' => $siblingWithData->id,
                ]);
            }
        }

        // Resolve customer identity (callback params → abandoned checkout → sibling → logged-in user)
        $loggedUser    = auth()->user();
        $customerName  = $request->query('customer_name') ?? $request->query('name')
            ?? $abandoned?->name ?? $siblingWithData?->name ?? ($loggedUser?->full_name) ?: null;
        $customerEmail = $request->query('customer_email') ?? $request->query('email')
            ?? $abandoned?->email ?? $siblingWithData?->email ?? $loggedUser?->email ?? null;
        $customerPhone = $request->query('customer_phone') ?? $request->query('phone')
            ?? $abandoned?->phone ?? $siblingWithData?->phone ?? $loggedUser?->phone ?? null;

        // Extract address from webhook data stored in abandoned checkout metadata.
        // The webhook fires BEFORE the callback (during PAYMENT_INITIATED), so the
        // address is already in metadata by the time this callback runs.
        // Check sibling first if the matched checkout has no webhook address data.
        // Accept partial address (city/pincode without street) — Shiprocket may not
        // always send address_line_1, but city+pincode is enough for shipping.
        $webhookAddress = $this->extractAddressFromWebhookMeta($abandoned);
        $hasAddress = !empty($webhookAddress['address_line_1']) || !empty($webhookAddress['city']) || !empty($webhookAddress['postal_code']);
        if (!$hasAddress && $siblingWithData) {
            $siblingAddress = $this->extractAddressFromWebhookMeta($siblingWithData);
            if (!empty($siblingAddress['address_line_1']) || !empty($siblingAddress['city']) || !empty($siblingAddress['postal_code'])) {
                $webhookAddress = $siblingAddress;
                $hasAddress = true;
            }
        }

        // Ask Shiprocket directly for anything still missing.
        //
        // Every source above depends on the webhook having arrived (it carries the
        // customer data into AbandonedCheckout). When webhooks are not delivered —
        // which is the norm on this account — orders land with no name, phone, email
        // or address, and no confirmation email can be sent.
        //
        // The order-details API always has the data once checkout completes, so it is
        // the reliable source, not a fallback. Best-effort: never let it break the
        // order, which already exists and is paid for by this point.
        // Fetched unconditionally: this is also the only trustworthy source for what
        // the customer ACTUALLY bought (see cart reconciliation in the transaction).
        $srOrder = null;
        try {
            $srOrder = $this->checkout->getOrder($shiprocketOrderId);
            if ($srOrder) {
                $api = $this->checkout->extractCustomer($srOrder);
                $customerName  = $customerName  ?: $api['name'];
                $customerPhone = $customerPhone ?: $api['phone'];
                $customerEmail = $customerEmail ?: $api['email'];

                if (!$hasAddress && !empty($api['address'])) {
                    $webhookAddress = $api['address'];
                    $hasAddress = true;
                }

                Log::info('ShiprocketCheckout: customer details fetched from API', [
                    'oid'         => $shiprocketOrderId,
                    'got_name'    => !empty($api['name']),
                    'got_phone'   => !empty($api['phone']),
                    'got_email'   => !empty($api['email']),
                    'got_address' => !empty($api['address']),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('ShiprocketCheckout: API customer fetch failed — ' . $e->getMessage(), [
                'oid' => $shiprocketOrderId,
            ]);
        }

        Log::info('ShiprocketCheckout: address extraction', [
            'abandoned_id' => $abandoned?->id,
            'has_address'  => $hasAddress,
            'fields'       => array_filter([
                'address_line_1' => $webhookAddress['address_line_1'] ?? null,
                'city'           => $webhookAddress['city'] ?? null,
                'postal_code'    => $webhookAddress['postal_code'] ?? null,
            ]),
        ]);

        // Backfill name/email/phone from webhook if callback URL didn't have them
        if (empty($customerName) && !empty($webhookAddress['name'])) {
            $customerName = $webhookAddress['name'];
        }
        if (empty($customerEmail) && !empty($webhookAddress['email'])) {
            $customerEmail = $webhookAddress['email'];
        }
        if (empty($customerPhone) && !empty($webhookAddress['phone'])) {
            $customerPhone = $webhookAddress['phone'];
        }

        // Last-resort backfill: query shiprocket_checkout_events directly. The webhook
        // can race and arrive with PAYMENT_INITIATED (the only event carrying email)
        // before the AC metadata block has been merged. The events table is the
        // source of truth — read from it newest-first and pluck whichever stage
        // carries each field. Without this, callbacks land empty even when the
        // webhook has already delivered the customer payload.
        if (empty($customerEmail) || empty($customerPhone) || empty($customerName)) {
            $candidateAcIds = array_filter([$abandoned?->id, $siblingWithData?->id]);
            if (!empty($candidateAcIds)) {
                $stagedEvents = ShiprocketCheckoutEvent::whereIn('abandoned_checkout_id', $candidateAcIds)
                    ->where('is_duplicate', false)
                    ->orderByRaw("CASE stage
                        WHEN 'PAYMENT_INITIATED' THEN 1
                        WHEN 'ORDER_PLACED'      THEN 2
                        WHEN 'PHONE_RECEIVED'    THEN 3
                        WHEN 'INIT'              THEN 4 ELSE 5 END")
                    ->orderByDesc('received_at')
                    ->get();
                $customerEmail = $customerEmail ?: $stagedEvents->pluck('email')->filter()->first();
                $customerPhone = $customerPhone ?: $stagedEvents->pluck('phone')->filter()->first();
                $customerName  = $customerName  ?: $stagedEvents->pluck('full_name')->filter()->first();
                Log::info('ShiprocketCheckout: customer fields merged from events table', [
                    'ac_ids' => $candidateAcIds,
                    'has_email' => !empty($customerEmail),
                    'has_phone' => !empty($customerPhone),
                    'has_name'  => !empty($customerName),
                ]);
            }
        }

        // Determine payment status from ost.
        // For partial COD, extract advance amount from webhook metadata or setting.
        $orderTotal  = (float) ($abandoned?->cart_total ?? 0);
        $advancePaid = 0.0;
        if ($this->ostMapper->isPartial($ost)) {
            $acMeta      = $abandoned?->metadata ?? [];
            $advancePaid = (float) ($acMeta['cashfree_advance']['amount'] ?? 0);
            if ($advancePaid <= 0) {
                $advancePaid = (float) \App\Models\Setting::get('cod_advance_amount', 0);
            }
        }
        $payment = $this->ostMapper->resolve($ost, $orderTotal, $advancePaid);

        // Cross-check: Shiprocket sends ost=SUCCESS in the callback for COD orders
        // too — the callback URL carries NO payment-type information. The webhook
        // (which stores the real payment_type in ShiprocketCheckoutEvent.payment_mode)
        // is the only authoritative source. We therefore treat ost=SUCCESS as
        // "paid" ONLY when a webhook explicitly confirms PREPAID. Without that
        // confirmation we mark the order 'pending' — a later webhook promotes it
        // to 'paid' (prepaid) or keeps it 'pending' (COD). This guarantees a COD
        // order is never shown as paid.
        if ($payment['payment_status'] === 'paid' && $abandoned) {
            $lookupIds = array_unique(array_filter([
                $shiprocketOrderId,
                $abandoned->metadata['shiprocket_cart_id'] ?? null,
                $abandoned->shiprocket_order_id,
            ]));

            $webhookPaymentEvent = ShiprocketCheckoutEvent::whereIn('cart_id', $lookupIds)
                ->whereNotNull('payment_mode')
                ->where('is_duplicate', false)
                ->latest()
                ->first();

            // payment_mode is the normalised order type: COD | PARTIAL | PREPAID.
            $eventMode = strtoupper((string) ($webhookPaymentEvent->payment_mode ?? ''));

            if ($eventMode === 'PREPAID') {
                // Webhook explicitly confirms a full online payment — keep 'paid'.
                Log::info('ShiprocketCheckout: ost=SUCCESS confirmed PREPAID by webhook', [
                    'oid' => $shiprocketOrderId, 'event_id' => $webhookPaymentEvent->id,
                ]);
            } elseif ($webhookPaymentEvent && in_array($eventMode, ['COD', 'PARTIAL'], true)) {
                $codAdvance = (float) ($webhookPaymentEvent->payment_amount ?? 0);
                if ($eventMode === 'PARTIAL' && $codAdvance > 0) {
                    $payment = [
                        'payment_status' => 'pending',
                        'payment_method' => 'shiprocket_cod_partial',
                        'paid_amount'    => $codAdvance,
                    ];
                } else {
                    $payment = [
                        'payment_status' => 'pending',
                        'payment_method' => 'shiprocket_cod',
                        'paid_amount'    => 0.0,
                    ];
                }
                Log::info('ShiprocketCheckout: overriding ost=SUCCESS with COD/partial from webhook event', [
                    'oid'            => $shiprocketOrderId,
                    'event_id'       => $webhookPaymentEvent->id,
                    'payment_mode'   => $eventMode,
                    'advance_amount' => $codAdvance,
                ]);
            } else {
                // No webhook has confirmed payment yet. Do NOT assume 'paid' —
                // ost=SUCCESS is sent for COD too. Mark pending; the webhook
                // (handled in WebhookController::syncOrder) will resolve it.
                $payment = [
                    'payment_status' => 'pending',
                    'payment_method' => 'shiprocket_checkout',
                    'paid_amount'    => 0.0,
                ];
                Log::info('ShiprocketCheckout: ost=SUCCESS unconfirmed (no webhook payment event) — marking pending until webhook confirms', [
                    'oid' => $shiprocketOrderId,
                ]);
            }
        }

        try {
            $cartIdForLock = $abandoned?->metadata['shiprocket_cart_id'] ?? null;
            $order = DB::transaction(function () use (
                $abandoned, $shiprocketOrderId, $cartIdForLock, $ost, $payment,
                $customerName, $customerEmail, $customerPhone, $loggedUser, $webhookAddress,
                $srOrder
            ) {
                // Lock on cart_id when available — Shiprocket may emit multiple
                // order_ids for the same cart (pending+confirmed), but cart_id is stable.
                $lockKey = $cartIdForLock ?: $shiprocketOrderId;
                DbCompat::advisoryLock($lockKey);

                // Idempotency: check by order_id, then by cart_id within 30 min
                $existing = Order::where('shiprocket_order_id', $shiprocketOrderId)->first()
                    ?? Order::where('source', 'api')
                        ->whereJsonContains('metadata->shiprocket_checkout_id', $shiprocketOrderId)
                        ->first()
                    ?? ($cartIdForLock
                        ? Order::where('source', 'api')
                            ->whereJsonContains('metadata->shiprocket_cart_id', $cartIdForLock)
                            ->where('created_at', '>', now()->subMinutes(30))
                            ->first()
                        : null);

                // Fuzzy fallback — catches the reverse race where the webhook
                // already created an order for this checkout (different cart_id
                // than the callback's oid). Match by: same total within 1 paisa,
                // recent, pending, source='api', AND the customer phone we have
                // matches what the webhook stored. Without this, the callback
                // creates a SECOND order for the same checkout (saw it 2026-06-02
                // 22:20 with Himanshu Chalana → orders 132 + 133).
                if (!$existing && $abandoned) {
                    $candidateTotal = (float) ($abandoned->cart_total ?? 0);
                    if ($candidateTotal <= 0) {
                        $snap = $abandoned->cart_snapshot ?? [];
                        foreach ($snap as $i) {
                            $candidateTotal += ((float)($i['price'] ?? 0)) * ((int)($i['quantity'] ?? 1));
                        }
                    }
                    if ($candidateTotal > 0) {
                        $candidatePhone = $customerPhone ?: $abandoned->phone ?: null;
                        $existing = Order::where('source', 'api')
                            ->where('payment_status', 'pending')
                            ->whereBetween('total', [$candidateTotal - 0.01, $candidateTotal + 0.01])
                            ->where('created_at', '>', now()->subMinutes(10))
                            ->where(function ($q) use ($candidatePhone) {
                                $q->whereJsonContains('metadata->created_from', 'webhook_fallback');
                                if ($candidatePhone) {
                                    $q->orWhere('guest_phone', $candidatePhone);
                                }
                            })
                            ->latest()
                            ->first();
                        if ($existing) {
                            Log::info('ShiprocketCheckout: fuzzy-matched existing webhook-created order (no duplicate)', [
                                'order_id'        => $existing->id,
                                'order_number'    => $existing->order_number,
                                'callback_oid'    => $shiprocketOrderId,
                                'candidate_total' => $candidateTotal,
                            ]);
                            // Stamp the callback's oid onto the existing order so
                            // future lookups by oid succeed without needing the
                            // fuzzy path.
                            $meta = $existing->metadata ?? [];
                            if (empty($meta['shiprocket_checkout_id'])) {
                                $meta['shiprocket_checkout_id'] = $shiprocketOrderId;
                                $existing->update(['metadata' => $meta]);
                            }
                        }
                    }
                }
                if ($existing) {
                    // Link this AC + session-sibling ACs to the existing order so
                    // the admin "Abandoned Checkouts" view doesn't show the
                    // customer-bearing AC as "Not recovered".
                    if ($abandoned && empty($abandoned->order_id)) {
                        $abandoned->update([
                            'order_id'     => $existing->id,
                            'recovered'    => true,
                            'recovered_at' => now(),
                            'step'         => 'completed',
                        ]);
                    }
                    if ($abandoned && $abandoned->session_id) {
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
                    return $existing;
                }

                if (!$abandoned || empty($abandoned->cart_snapshot)) return null;

                $cartSnapshot = $abandoned->cart_snapshot;

                // Reconcile against what Shiprocket ACTUALLY charged for.
                //
                // cart_snapshot is frozen at token-generation time, but the customer can
                // still change quantities (or remove lines) inside Shiprocket's checkout.
                // Shiprocket bills its own cart, so trusting the snapshot silently creates
                // orders for more than was paid — e.g. buy 2, drop to 1 at checkout, and
                // we ship 2 having collected for 1.
                //
                // Shiprocket's cart_data is the authority on what was purchased.
                if (!empty($srOrder['cart_data']['items'])) {
                    $srQty = [];
                    foreach ($srOrder['cart_data']['items'] as $srItem) {
                        $key = (string) ($srItem['variant_id'] ?? $srItem['product_id'] ?? '');
                        if ($key !== '') {
                            $srQty[$key] = (int) ($srItem['quantity'] ?? 1);
                        }
                    }

                    if (!empty($srQty)) {
                        $reconciled = [];
                        foreach ($cartSnapshot as $item) {
                            $key = (string) ($item['variant_id'] ?? $item['product_id'] ?? '');

                            // Line removed at checkout — drop it.
                            if (!isset($srQty[$key])) {
                                Log::info('ShiprocketCheckout: line dropped at checkout', [
                                    'oid'        => $shiprocketOrderId,
                                    'product_id' => $item['product_id'] ?? null,
                                ]);
                                continue;
                            }

                            if ((int) ($item['quantity'] ?? 1) !== $srQty[$key]) {
                                Log::info('ShiprocketCheckout: quantity changed at checkout', [
                                    'oid'        => $shiprocketOrderId,
                                    'product_id' => $item['product_id'] ?? null,
                                    'ours'       => (int) ($item['quantity'] ?? 1),
                                    'shiprocket' => $srQty[$key],
                                ]);
                                $item['quantity'] = $srQty[$key];
                            }

                            $reconciled[] = $item;
                        }

                        if (!empty($reconciled)) {
                            $cartSnapshot = $reconciled;
                        }
                    }
                }

                $subtotal = 0.0;
                foreach ($cartSnapshot as $item) {
                    $subtotal += ((float) ($item['price'] ?? 0)) * ((int) ($item['quantity'] ?? 1));
                }

                // Apply Shiprocket pricing (discount, shipping, tax) from webhook data.
                // Source 1: AC metadata (set by WebhookController)
                // Source 2: Latest ShiprocketCheckoutEvent linked to this AC
                $acMeta      = $abandoned->metadata ?? [];
                $srPricing   = $acMeta['sr_pricing'] ?? [];

                if (empty($srPricing)) {
                    $latestEvent = ShiprocketCheckoutEvent::where('abandoned_checkout_id', $abandoned->id)
                        ->where('total_discount', '>', 0)
                        ->orderBy('created_at', 'desc')
                        ->first();

                    if (!$latestEvent) {
                        // Also try matching via cart_id stored in AC metadata
                        $acCartId = $acMeta['shiprocket_cart_id'] ?? null;
                        if ($acCartId) {
                            $latestEvent = ShiprocketCheckoutEvent::where('cart_id', $acCartId)
                                ->where('total_discount', '>', 0)
                                ->orderBy('created_at', 'desc')
                                ->first();
                        }
                    }

                    if ($latestEvent) {
                        $srPricing = [
                            'total_price'    => $latestEvent->total_price,
                            'total_discount' => $latestEvent->total_discount,
                            'shipping_price' => $latestEvent->shipping_price,
                            'tax'            => $latestEvent->tax,
                            'net_payable'    => $latestEvent->net_payable,
                        ];
                    }
                }

                $discount     = (float) ($srPricing['total_discount'] ?? 0);
                $shippingCost = (float) ($srPricing['shipping_price'] ?? 0);
                $tax          = (float) ($srPricing['tax'] ?? 0);

                // Shiprocket sends total_discount == total_price for COD orders
                // (their internal "platform subsidy" concept, NOT a customer discount).
                // If we subtract it, the order total becomes ₹0. Ignore it for COD.
                $isCodPayment = in_array($payment['payment_method'], ['cod', 'partial_cod']);
                if ($isCodPayment && $discount > 0 && $discount >= $subtotal) {
                    Log::info('ShiprocketCheckout: ignoring COD subsidy as discount', [
                        'total_discount' => $discount,
                        'subtotal'       => $subtotal,
                        'oid'            => $shiprocketOrderId,
                    ]);
                    $discount = 0;
                }

                // If Shiprocket reports a different subtotal (customer changed cart
                // after our token was generated), use Shiprocket's total_price.
                $srSubtotal = (float) ($srPricing['total_price'] ?? 0);
                if ($srSubtotal > 0 && abs($srSubtotal - $subtotal) > 0.01) {
                    Log::info('ShiprocketCheckout: cart changed after token', [
                        'our_subtotal' => $subtotal,
                        'sr_subtotal'  => $srSubtotal,
                        'oid'          => $shiprocketOrderId,
                    ]);
                    $subtotal = $srSubtotal;
                    // Re-check COD subsidy after subtotal update
                    if ($isCodPayment && $discount >= $subtotal) {
                        $discount = 0;
                    }
                }

                $total = $subtotal - $discount + $shippingCost + $tax;

                // Sanity: total should never be negative
                if ($total < 0) {
                    $total = $subtotal;
                    $discount = 0;
                }

                // COD charge is NOT added by us. Shiprocket Checkout runs the
                // checkout and is the sole authority on what the customer pays —
                // the delivery agent collects Shiprocket's amount, not ours.
                // A COD fee must be configured in the Shiprocket Checkout dashboard
                // so it arrives inside the pricing fields above (total_price /
                // shipping_price). Adding our own here only inflates the admin
                // total beyond what the customer is actually billed.
                $codCharge = 0.0;

                // For prepaid orders, paid_amount = total (after discount)
                $paidAmount = $payment['paid_amount'];
                if ($payment['payment_status'] === 'paid') {
                    $paidAmount = $total;
                }

                // Create the order
                // source='api' satisfies the PostgreSQL source enum constraint
                // shiprocket_order_id is set so PushOrderToShiprocket listener skips this
                $orderData = [
                    'user_id'              => $abandoned->user_id ?? $loggedUser?->id,
                    'guest_name'           => $customerName,
                    'guest_email'          => $customerEmail,
                    'guest_phone'          => $customerPhone,
                    'status'               => 'confirmed',
                    'payment_status'       => $payment['payment_status'],
                    'subtotal'             => $subtotal,
                    'discount'             => $discount,
                    'shipping_cost'        => $shippingCost,
                    'tax'                  => $tax,
                    'total'                => $total,
                    'paid_amount'          => $paidAmount,
                    'source'               => 'api',
                    'shiprocket_order_id'  => $shiprocketOrderId,
                    'metadata'             => array_filter([
                        'payment_method'      => $payment['payment_method'],
                        'payment_gateway'     => 'shiprocket',
                        'shiprocket_checkout_id' => $shiprocketOrderId,
                        'shiprocket_cart_id'  => $acMeta['shiprocket_cart_id'] ?? null,
                        'shiprocket_ost'      => $ost,
                        'sr_pricing'          => $srPricing ?: null,
                        'cod_charge'          => $codCharge ?: null,
                    ]),
                ];

                // Attach address from webhook metadata (arrived before callback).
                // Accept partial address — city/pincode without street is still useful for shipping.
                if (!empty($webhookAddress['address_line_1']) || !empty($webhookAddress['city']) || !empty($webhookAddress['postal_code'])) {
                    $orderData['shipping_address_snapshot'] = [
                        'name'           => $customerName ?? '',
                        'phone'          => $customerPhone ?? '',
                        'address_line_1' => $webhookAddress['address_line_1'] ?? '',
                        'address_line_2' => $webhookAddress['address_line_2'] ?? '',
                        'city'           => $webhookAddress['city'] ?? '',
                        'state'          => $webhookAddress['state'] ?? '',
                        'postal_code'    => $webhookAddress['postal_code'] ?? '',
                        'country'        => $webhookAddress['country'] ?? 'India',
                    ];
                }

                $order = Order::create($orderData);

                // Create order items + decrement stock
                foreach ($cartSnapshot as $item) {
                    $product = Product::find($item['product_id'] ?? null)
                        ?? Product::find($item['variant_id'] ?? null);

                    OrderItem::create([
                        'order_id'    => $order->id,
                        'product_id'  => $product?->id,
                        'product_name'=> $product?->name ?? $item['name'] ?? 'Product',
                        'sku'         => $product?->sku ?? $item['sku'] ?? '',
                        'quantity'    => (int) ($item['quantity'] ?? 1),
                        'mrp'         => $product?->mrp ?? $item['price'] ?? 0,
                        'price'       => (float) ($item['price'] ?? 0),
                        'tax'         => 0,
                        'discount'    => 0,
                        'total'       => ((float) ($item['price'] ?? 0)) * ((int) ($item['quantity'] ?? 1)),
                    ]);

                    if ($product) {
                        $qty    = (int) ($item['quantity'] ?? 1);
                        $locked = Product::where('id', $product->id)->lockForUpdate()->first();

                        if ($locked && $locked->stock_quantity >= $qty) {
                            $locked->decrement('stock_quantity', $qty);
                            $locked->increment('sales_count', $qty);
                            if ($locked->fresh()->stock_quantity <= 0) {
                                $locked->update(['stock_status' => 'out_of_stock']);
                            }
                        } else {
                            Log::warning('ShiprocketCheckout: insufficient stock (order placed anyway)', [
                                'product_id' => $product->id,
                                'requested'  => $qty,
                                'available'  => $locked?->stock_quantity ?? 0,
                            ]);
                            $product->increment('sales_count', $qty);
                        }
                    }
                }

                // Release stock holds — real stock has been decremented above
                $holdSession = $abandoned->session_id ?? session()->getId();
                foreach ($cartSnapshot as $item) {
                    $pid = $item['product_id'] ?? $item['variant_id'] ?? null;
                    if ($pid) {
                        $this->stockHold->release((int) $pid, $holdSession);
                    }
                }

                return $order;
            });

            // Existing order (idempotency guard returned it)
            if ($order && !$order->wasRecentlyCreated) {
                return $this->renderSuccess($shiprocketOrderId, $order->guest_name, $order, $request, $abandoned);
            }

            // No abandoned checkout / no cart_snapshot — show generic confirmation
            if (!$order) {
                Log::warning('ShiprocketCheckout: no abandoned checkout found', ['oid' => $shiprocketOrderId]);
                return view('checkout.shiprocket-success', [
                    'shiprocketOrderId' => $shiprocketOrderId,
                    'customerName'      => null,
                    'order'             => null,
                    'fbPurchaseEventId' => null,
                ]);
            }

            // Mark abandoned checkout as recovered
            $abandoned?->update([
                'step'         => 'completed',
                'recovered'    => true,
                'order_id'     => $order->id,
                'recovered_at' => now(),
            ]);

            // Sync customer details from Shiprocket API (best-effort)
            $customerSynced = $this->syncCustomerFromApi($order, $shiprocketOrderId);

            // Queue delayed sync if immediate sync didn't get the name
            if (!$customerSynced && empty($order->fresh()->guest_name)) {
                \App\Jobs\SyncShiprocketCustomerDetails::dispatch($order)->delay(now()->addSeconds(30));
                Log::info('ShiprocketCheckout: queued delayed customer sync', ['order_id' => $order->id]);
            } elseif ($customerSynced && empty($order->fresh()->user_id)) {
                \App\Jobs\SyncShiprocketCustomerDetails::dispatch($order)->delay(now()->addSeconds(5));
                Log::info('ShiprocketCheckout: queued user account creation', ['order_id' => $order->id]);
            }

            // Dispatch OrderPlaced (email notifications + events)
            $order->load('items.product', 'user');
            try {
                OrderPlaced::dispatch($order, 'shiprocket_checkout');
            } catch (\Throwable $e) {
                Log::error('ShiprocketCheckout: OrderPlaced event failed', [
                    'order_id' => $order->id,
                    'error'    => $e->getMessage(),
                ]);
            }

            Log::info('ShiprocketCheckout: order created', [
                'order_id'           => $order->id,
                'order_number'       => $order->order_number,
                'shiprocket_ost'     => $ost,
                'payment_status'     => $payment['payment_status'],
                'payment_method'     => $payment['payment_method'],
                'total'              => $order->total,
            ]);

        } catch (\Throwable $e) {
            Log::error('ShiprocketCheckout: order creation failed', [
                'oid'   => $shiprocketOrderId,
                'error' => $e->getMessage(),
            ]);

            return view('checkout.failed', [
                'message' => 'We received your payment but could not create your order. '
                    . 'Please contact support with reference: ' . $shiprocketOrderId,
            ]);
        }

        return $this->renderSuccess($shiprocketOrderId, $customerName, $order ?? null, $request, $abandoned);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function syncCustomerFromApi(Order $order, string $shiprocketOrderId): bool
    {
        try {
            $srService = app(\App\Services\ShiprocketService::class);
            $srOrder   = $srService->getCheckoutOrder($shiprocketOrderId)
                ?? $srService->findShippingOrderByCheckoutId($shiprocketOrderId);

            if (!$srOrder) return false;

            $src     = $srOrder['customer_details'] ?? $srOrder;
            $updates = [];

            if (empty($order->guest_name)) {
                $name = trim(($src['billing_customer_name'] ?? $src['customer_name'] ?? '') . ' ' . ($src['billing_last_name'] ?? ''));
                if ($name) $updates['guest_name'] = $name;
            }
            if (empty($order->guest_email) && !empty($src['billing_email'] ?? $src['customer_email'] ?? null)) {
                $updates['guest_email'] = $src['billing_email'] ?? $src['customer_email'];
            }
            if (empty($order->guest_phone) && !empty($src['billing_phone'] ?? $src['customer_phone'] ?? null)) {
                $updates['guest_phone'] = $src['billing_phone'] ?? $src['customer_phone'];
            }
            // Only build snapshot if the API actually returned address data.
            // Shiprocket's Checkout API returns name/email but rarely returns address fields.
            // If we build an empty snapshot here, the webhook (which has the real address)
            // will see a non-empty snapshot and skip the address update entirely.
            $apiAddress = $src['billing_address'] ?? $src['customer_address'] ?? '';
            if (empty($order->shipping_address_snapshot) && !empty($apiAddress)) {
                $updates['shipping_address_snapshot'] = [
                    'name'           => $updates['guest_name']  ?? $order->guest_name  ?? '',
                    'phone'          => $updates['guest_phone'] ?? $order->guest_phone ?? '',
                    'address_line_1' => $apiAddress,
                    'address_line_2' => $src['billing_address_2'] ?? '',
                    'city'           => $src['billing_city']    ?? $src['customer_city']    ?? '',
                    'state'          => $src['billing_state']   ?? $src['customer_state']   ?? '',
                    'postal_code'    => $src['billing_pincode'] ?? $src['customer_pincode'] ?? '',
                    'country'        => $src['billing_country'] ?? 'India',
                ];
            }
            if (empty($order->user_id)) {
                $user = $this->findOrCreateUserForOrder(
                    $updates['guest_phone'] ?? $order->guest_phone,
                    $updates['guest_email'] ?? $order->guest_email,
                    $order->guest_name,
                );
                if ($user) $updates['user_id'] = $user->id;
            }

            // Correct payment status if Shipping API reveals COD but callback set paid.
            // payment_method lives at the top level of the shipping order, not in customer_details.
            $srPaymentMethod = strtolower($srOrder['payment_method'] ?? $srOrder['payment_type'] ?? '');
            if ($order->payment_status === 'paid' && in_array($srPaymentMethod, ['cod', 'cash on delivery', 'cash_on_delivery'])) {
                $updates['payment_status'] = 'pending';
                $updates['paid_amount']    = 0;
                $meta = $order->metadata ?? [];
                $meta['payment_method'] = 'shiprocket_cod';
                $meta['payment_status_corrected_at'] = now()->toIso8601String();
                $updates['metadata'] = $meta;
                Log::info('ShiprocketCheckout: corrected COD payment status from Shipping API', [
                    'order_id'          => $order->id,
                    'sr_payment_method' => $srPaymentMethod,
                ]);
            }

            if (!empty($updates)) {
                $order->update($updates);
                Log::info('ShiprocketCheckout: customer synced from API', [
                    'order_id' => $order->id,
                    'fields'   => array_keys($updates),
                ]);
                return true;
            }
        } catch (\Throwable $e) {
            Log::warning('ShiprocketCheckout: API customer sync failed', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);
        }
        return false;
    }

    private function renderSuccess(string $oid, ?string $name, ?Order $order, Request $request, ?AbandonedCheckout $abandoned = null): View
    {
        $fbEventId = null;
        if ($order) {
            $order->load('items.product', 'user');

            // Use order_number as event_id so browser pixel + CAPI + webhook all dedup to 1 Purchase
            $fbEventId = $order->order_number;

            // Guard: only fire CAPI if not already sent (webhook may have fired first)
            $meta = $order->metadata ?? [];
            if (empty($meta['capi_sent_at'])) {
                // Pass stored fbc/fbp from abandoned checkout as fallback
                // (cookies may be lost after Shiprocket cross-domain redirect)
                $acMeta = $abandoned?->metadata ?? [];
                $fbCookieFallback = array_filter([
                    'fbc' => $acMeta['fbc'] ?? null,
                    'fbp' => $acMeta['fbp'] ?? null,
                ]);
                app(AnalyticsService::class)->trackPurchase($order, $request, $fbEventId, $fbCookieFallback);

                $meta['capi_sent_at'] = now()->toIso8601String();
                $meta['fb_event_id']  = $fbEventId;
                $meta['capi_source']  = 'callback';
                $order->metadata = $meta;
                $order->save();
            }
        }

        return view('checkout.shiprocket-success', [
            'shiprocketOrderId' => $oid,
            'customerName'      => $name,
            'order'             => $order,
            'fbPurchaseEventId' => $fbEventId,
        ]);
    }

    /**
     * Extract customer address from the webhook metadata stored on the abandoned checkout.
     *
     * The Shiprocket webhook fires during PAYMENT_INITIATED (before the callback),
     * and stores billing/shipping address in abandoned_checkout.metadata under keys
     * like "webhook_payment_initiated" with customer data nested in a "customer" sub-array.
     *
     * IMPORTANT: PostgreSQL jsonb sorts keys by length, so "webhook_unknown" (15 chars)
     * comes before "webhook_payment_initiated" (26 chars). We must iterate ALL webhook
     * stages and pick the one with the most complete address — not just the first match.
     */
    private function extractAddressFromWebhookMeta(?AbandonedCheckout $abandoned): array
    {
        if (!$abandoned || empty($abandoned->metadata)) {
            return [];
        }

        $meta = $abandoned->metadata;
        $best = [];
        $bestScore = 0;

        foreach ($meta as $key => $data) {
            if (!is_array($data) || !str_starts_with($key, 'webhook_')) {
                continue;
            }

            $customer = $data['customer'] ?? [];
            $addr     = $customer['address'] ?? '';
            $city     = $customer['city'] ?? '';
            $pincode  = $customer['pincode'] ?? '';

            if (empty($addr) && empty($city) && empty($pincode)) {
                continue;
            }

            // Score: prefer entries with street address, then city, then pincode
            $score = (!empty($addr) ? 4 : 0) + (!empty($city) ? 2 : 0) + (!empty($pincode) ? 1 : 0);

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = [
                    'name'           => $customer['name'] ?? '',
                    'email'          => $customer['email'] ?? '',
                    'phone'          => $customer['phone'] ?? '',
                    'address_line_1' => $addr,
                    'address_line_2' => $customer['address_2'] ?? '',
                    'city'           => $city,
                    'state'          => $customer['state'] ?? '',
                    'postal_code'    => $pincode,
                    'country'        => $customer['country'] ?? 'India',
                ];
            }
        }

        return $best;
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
            return $this->findUserByPhoneOrEmail($phone, $email);
        }
    }
}
