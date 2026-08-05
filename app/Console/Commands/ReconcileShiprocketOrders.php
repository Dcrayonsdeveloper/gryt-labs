<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\ShiprocketCheckoutEvent;
use App\Models\Tenant;
use App\Services\ShiprocketCheckout\OrderSyncEngine;
use App\Services\ShiprocketCheckout\ReconcileIgnoreList;
use App\Services\ShiprocketService;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Reconcile Shiprocket Checkout orders against the local database.
 *
 * A Shiprocket Checkout order only becomes a local Order via the success-page
 * callback (/checkout/success/shiprocket) or the webhook (/webhook/shipping-updates).
 * When the customer pays and never returns to the site — and the webhook is also
 * missed (auth header unset, endpoint unreachable, early-stage-only webhooks) — no
 * Order row is created. No Order means no OrderPlaced event: no confirmation email,
 * no WhatsApp, no fulfilment, while Shiprocket has already collected the money.
 *
 * This command cross-references Shiprocket's orders against local Orders and, with
 * --create, rebuilds the missing ones and dispatches OrderPlaced so the customer +
 * admin notifications and fulfilment finally run. Run the dry-run report first.
 *
 *   php artisan shiprocket:reconcile-orders                        # dry-run, all tenants
 *   php artisan shiprocket:reconcile-orders --tenant=ayurvexa
 *   php artisan shiprocket:reconcile-orders --tenant=ayurvexa --create
 *   php artisan shiprocket:reconcile-orders --order=6a10...d12 --create
 */
class ReconcileShiprocketOrders extends Command
{
    protected $signature = 'shiprocket:reconcile-orders
                            {--tenant= : Limit to one tenant id (default: every tenant)}
                            {--days=7 : How many days back to scan}
                            {--order= : Reconcile a single Shiprocket order id (hex)}
                            {--create : Create the missing orders (omit for a dry-run report)}
                            {--alert : WhatsApp the admin when missing orders are found}';

    protected $description = 'Detect (and optionally recover) Shiprocket Checkout orders missing from the local DB';

    private bool $create = false;
    private int $days = 7;
    private OrderSyncEngine $engine;

    public function handle(): int
    {
        $this->create = (bool) $this->option('create');
        $this->days = max(1, (int) $this->option('days'));

        $tenants = $this->option('tenant')
            ? Tenant::where('id', $this->option('tenant'))->get()
            : Tenant::all();

        if ($tenants->isEmpty()) {
            $this->error('No matching tenant.');
            return self::FAILURE;
        }

        if (! $this->create) {
            $this->warn('DRY RUN — reporting only. Re-run with --create to recover the orders.');
        }

        foreach ($tenants as $tenant) {
            tenancy()->initialize($tenant);
            try {
                // Resolved per tenant: the engine's ShiprocketService caches its
                // auth token from tenant settings at construction time.
                $this->engine = app(OrderSyncEngine::class);
                $this->reconcileTenant((string) $tenant->id);
            } catch (\Throwable $e) {
                $this->error("[{$tenant->id}] reconcile failed: {$e->getMessage()}");
                Log::error('shiprocket:reconcile-orders failed', [
                    'tenant' => $tenant->id,
                    'error' => $e->getMessage(),
                ]);
            } finally {
                tenancy()->end();
            }
        }

        return self::SUCCESS;
    }

    private function reconcileTenant(string $tenantId): void
    {
        $missing = $this->findMissingOrders();

        if (empty($missing)) {
            $this->info("[{$tenantId}] No missing Shiprocket orders.");
            return;
        }

        $this->warn("[{$tenantId}] " . count($missing) . ' Shiprocket order(s) missing locally:');
        $this->table(
            ['Shiprocket ID', 'Customer', 'Phone', 'Amount', 'Payment', 'Source', 'Recoverable'],
            array_map(fn ($m) => [
                $m['shiprocket_id'],
                $m['customer']['name'] ?: '—',
                $m['customer']['phone'] ?: '—',
                number_format($m['amounts']['total'], 2),
                $m['payment_mode'],
                $m['source'],
                empty($m['items']) ? 'NO — create manually' : 'yes',
            ], $missing)
        );

        if (! $this->create) {
            $this->line('Re-run with --create to recover the recoverable ones.');
            $this->alertAdmin($tenantId, $missing);
            return;
        }

        $recovered = 0;
        foreach ($missing as $m) {
            if (empty($m['items'])) {
                $this->warn("  {$m['shiprocket_id']} — skipped: no line items in any source, create manually.");
                continue;
            }
            // Shipping-API discoveries are REPORT-ONLY, never auto-created. That list
            // mixes manual/test orders from the Shiprocket panel with duplicates of
            // Checkout orders keyed under a different numeric channel_order_id (its
            // totals are pre-discount, so they don't dedupe by amount either), and its
            // items carry no product ids. Real website sales are Checkout orders and
            // are recovered via Source C above. Anything left here needs human eyes.
            if ($m['source'] === 'shipping API') {
                $this->line("  {$m['shiprocket_id']} — shipping-API only: listed for review, not auto-created.");
                continue;
            }
            $res = $this->engine->createFromShape($m);
            match ($res['status']) {
                'created' => $this->info("  {$m['shiprocket_id']} — {$res['message']} (OrderPlaced dispatched)."),
                'exists' => $this->line("  {$m['shiprocket_id']} — {$res['message']}, skipped."),
                'skipped_product' => $this->warn("  {$m['shiprocket_id']} — skipped: {$res['message']}."),
                default => $this->error("  {$m['shiprocket_id']} — creation failed: {$res['message']}"),
            };
            if ($res['status'] === 'created') {
                $recovered++;
            }
        }
        $this->info("[{$tenantId}] Recovered {$recovered} of " . count($missing) . ' order(s).');
    }

    /**
     * Build the list of Shiprocket orders that have no matching local Order.
     * Source A — local webhook events that reached a payment-confirmed stage.
     * Source B — the Shiprocket Shipping API (catches orders with no webhook events).
     *
     * @return array<int, array<string, mixed>>
     */
    private function findMissingOrders(): array
    {
        $singleId = $this->option('order');
        $found = [];

        // Source A — webhook events at a payment-confirmed stage with no linked order.
        $eventQuery = ShiprocketCheckoutEvent::query()
            ->where('is_duplicate', false)
            ->whereIn('stage', ['ORDER_PLACED', 'Payment Complete', 'SUCCESS'])
            ->where('received_at', '>=', now()->subDays($this->days));
        if ($singleId) {
            $eventQuery->where('cart_id', $singleId);
        }

        foreach ($eventQuery->orderBy('received_at')->get()->groupBy('cart_id') as $cartId => $events) {
            // Never recreate an order that was intentionally deleted (orders:delete).
            if (ReconcileIgnoreList::has((string) $cartId)) {
                continue;
            }
            // Skip if any event already carries an order_id, or an order exists by any id.
            if ($events->contains(fn ($e) => $e->order_id !== null)) {
                continue;
            }
            if ($this->engine->findLocalOrder((string) $cartId)) {
                continue;
            }
            $found[(string) $cartId] = $this->buildFromEvents((string) $cartId, $events);
        }

        // Source B — Shiprocket Shipping API order list.
        foreach ($this->fetchShippingOrders() as $srOrder) {
            $hex = (string) ($srOrder['channel_order_id'] ?? '');
            if ($hex === '' || isset($found[$hex]) || ReconcileIgnoreList::has($hex)) {
                continue;
            }
            if ($singleId && $hex !== $singleId) {
                continue;
            }
            if ($this->engine->findLocalOrder($hex)) {
                continue;
            }
            $found[$hex] = $this->buildFromShippingOrder($srOrder);
        }

        // Source C — Shiprocket CHECKOUT order-details API, seeded by the checkout tokens
        // we issued (shiprocket_checkout_ids). The Shipping API (Source B) does NOT yet
        // contain freshly-placed Checkout orders, and the success callback/webhook can be
        // missed entirely — so a paid order can exist in Shiprocket with no trace in either
        // source above. Every checkout token we generate is tracked; ask the Checkout API
        // about the recent ones that have no local order and recover the ones that actually
        // completed (status SUCCESS). This is the path that catches the "order is in
        // Shiprocket but never reached the website" case automatically.
        $checkoutService = app(\App\Services\ShiprocketCheckout\ShiprocketCheckoutService::class);

        $candidateIds = $singleId
            ? [$singleId]
            : DB::table('shiprocket_checkout_ids')
                ->where('id_type', 'token')
                ->where('created_at', '>=', now()->subDays($this->days))
                ->orderByDesc('id')
                ->pluck('shiprocket_id')
                ->map(fn ($v) => (string) $v)
                ->unique()
                ->all();

        foreach ($candidateIds as $cid) {
            $cid = (string) $cid;
            if ($cid === '' || isset($found[$cid]) || ReconcileIgnoreList::has($cid) || $this->engine->findLocalOrder($cid)) {
                continue;
            }

            try {
                $sr = $checkoutService->getOrder($cid);
            } catch (\Throwable $e) {
                continue;
            }

            // Only recover COMPLETED orders — skip abandoned/failed/pending checkouts.
            if (! is_array($sr)
                || strtoupper((string) ($sr['status'] ?? '')) !== 'SUCCESS'
                || empty($sr['cart_data']['items'])) {
                continue;
            }

            // De-dupe across every id form Shiprocket may key this order under, so we never
            // create a second copy of an order that Source A/B already queued or that
            // already exists locally under a different id.
            $altIds = array_filter(array_map('strval', [
                $cid,
                $sr['cart_id'] ?? '',
                $sr['platform_order_id'] ?? '',
                $sr['fastrr_order_id'] ?? '',
            ]));
            if (array_intersect($altIds, array_keys($found))) {
                continue;
            }
            // Any id form on the ignore-list → intentionally deleted, never recreate.
            if (array_intersect($altIds, ReconcileIgnoreList::all())) {
                continue;
            }
            foreach ($altIds as $aid) {
                if ($this->engine->findLocalOrder($aid)) {
                    continue 2;
                }
            }

            $found[$cid] = $this->engine->buildFromCheckoutApi($cid, $sr);
        }

        return array_values($found);
    }

    /** Pull the Shipping API order list for the scan window (paged, capped). */
    private function fetchShippingOrders(): array
    {
        $srService = app(ShiprocketService::class);
        if (! $srService->isConfigured()) {
            $this->line('  Shipping API not configured — using webhook events only.');
            return [];
        }

        $filters = [
            'from' => now()->subDays($this->days)->format('Y-m-d'),
            'to' => now()->format('Y-m-d'),
        ];
        $all = [];
        for ($page = 1; $page <= 10; $page++) {
            $data = $srService->getShippingOrders($page, 100, $filters);
            if (! $data || empty($data['orders'])) {
                break;
            }
            $all = array_merge($all, $data['orders']);
            if (count($data['orders']) < 100) {
                break;
            }
        }
        return $all;
    }

    /** Normalise a checkout session's webhook events into a recoverable-order shape. */
    private function buildFromEvents(string $cartId, Collection $events): array
    {
        $latest = $events->last();
        $withItems = $events->first(fn ($e) => ! empty($e->items));
        $named = $events->first(fn ($e) => ! empty($e->full_name)) ?? $latest;

        $items = [];
        foreach (($withItems?->items ?? []) as $i) {
            $items[] = [
                'product_id' => $i['product_id'] ?? $i['variant_id'] ?? null,
                'name' => $i['name'] ?? $i['title'] ?? 'Product',
                'sku' => $i['sku'] ?? '',
                'quantity' => (int) ($i['quantity'] ?? 1),
                'price' => (float) ($i['price'] ?? 0),
            ];
        }
        $subtotal = array_sum(array_map(fn ($i) => $i['price'] * $i['quantity'], $items));

        return [
            'shiprocket_id' => $cartId,
            'source' => 'webhook event',
            'customer' => [
                'name' => $named->full_name,
                'email' => $events->first(fn ($e) => ! empty($e->email))?->email,
                'phone' => $events->first(fn ($e) => ! empty($e->phone))?->phone,
                'address' => $named->address_line_1,
                'address_2' => $named->address_line_2,
                'city' => $named->city,
                'state' => $named->state,
                'pincode' => $named->pincode,
                'country' => $named->country ?: 'India',
            ],
            'items' => $items,
            'payment_mode' => strtolower($latest->payment_mode ?: 'prepaid'),
            'amounts' => [
                'subtotal' => $subtotal,
                'discount' => (float) $latest->total_discount,
                'shipping' => (float) $latest->shipping_price,
                'tax' => (float) $latest->tax,
                'total' => (float) ($latest->net_payable ?: $latest->total_price ?: $subtotal),
                'paid' => (float) ($latest->payment_amount ?: 0),
            ],
        ];
    }

    /** Normalise a Shiprocket Shipping API order row into a recoverable-order shape. */
    private function buildFromShippingOrder(array $sr): array
    {
        $c = $sr['customer_details'] ?? $sr;

        $items = [];
        foreach (($sr['products'] ?? []) as $p) {
            $items[] = [
                'product_id' => null,
                'name' => $p['name'] ?? 'Product',
                'sku' => $p['sku'] ?? $p['master_sku'] ?? '',
                'quantity' => (int) ($p['units'] ?? $p['quantity'] ?? 1),
                'price' => (float) ($p['selling_price'] ?? $p['price'] ?? 0),
            ];
        }
        $subtotal = array_sum(array_map(fn ($i) => $i['price'] * $i['quantity'], $items));
        $total = (float) ($sr['total'] ?? $sr['net_total'] ?? $subtotal);
        $isCod = str_contains(strtolower((string) ($sr['payment_method'] ?? 'prepaid')), 'cod');

        return [
            'shiprocket_id' => (string) ($sr['channel_order_id'] ?? ''),
            'source' => 'shipping API',
            'customer' => [
                'name' => trim(($c['billing_customer_name'] ?? $c['customer_name'] ?? $c['name'] ?? '')
                    . ' ' . ($c['billing_last_name'] ?? '')),
                'email' => $c['billing_email'] ?? $c['customer_email'] ?? $c['email'] ?? null,
                'phone' => $c['billing_phone'] ?? $c['customer_phone'] ?? $c['phone'] ?? null,
                'address' => $c['billing_address'] ?? $c['address'] ?? '',
                'address_2' => $c['billing_address_2'] ?? '',
                'city' => $c['billing_city'] ?? $c['city'] ?? '',
                'state' => $c['billing_state'] ?? $c['state'] ?? '',
                'pincode' => $c['billing_pincode'] ?? $c['pincode'] ?? '',
                'country' => $c['billing_country'] ?? 'India',
            ],
            'items' => $items,
            'payment_mode' => $isCod ? 'cod' : 'prepaid',
            'amounts' => [
                'subtotal' => $subtotal,
                'discount' => 0.0,
                'shipping' => 0.0,
                'tax' => 0.0,
                'total' => $total,
                'paid' => $isCod ? 0.0 : $total,
            ],
        ];
    }

    /** WhatsApp the admin so missing orders never go unnoticed (scheduled runs). */
    private function alertAdmin(string $tenantId, array $missing): void
    {
        if (! $this->option('alert')) {
            return;
        }
        $phone = Setting::get('admin_whatsapp_phone', '');
        if (! $phone) {
            return;
        }
        $whatsapp = app(WhatsAppService::class);
        if (! $whatsapp->isConfigured()) {
            return;
        }

        $count = count($missing);
        $value = array_sum(array_map(fn ($m) => $m['amounts']['total'], $missing));
        $brand = Setting::get('site_name', config('app.name'));

        $whatsapp->sendText(
            $phone,
            "SHIPROCKET ORDER SYNC ALERT\n\n"
            . "{$count} order(s) are in Shiprocket Checkout but missing from {$brand}'s admin panel "
            . '(approx Rs' . number_format($value, 0) . " in orders).\n\n"
            . "These customers have NOT received an order confirmation.\n\n"
            . "Recover them:\nphp artisan shiprocket:reconcile-orders --tenant={$tenantId} --create"
        );
    }
}
