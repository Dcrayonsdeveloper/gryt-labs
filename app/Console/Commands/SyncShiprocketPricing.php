<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Tenant;
use App\Services\ShiprocketCheckout\ShiprocketCheckoutService;
use Illuminate\Console\Command;

/**
 * Backfill/sync the real Shiprocket pricing onto orders.
 *
 * On this account Shiprocket webhooks are not delivered, so orders are created
 * without the coupon/discount/COD breakdown — they record the full pre-discount
 * total. This command pulls the authoritative pricing from the Shiprocket
 * order-details API (getOrder → `result`) and stores it on the order:
 *   - metadata['sr_pricing']  (what the Payment Summary card reads)
 *   - the money columns (subtotal / discount / shipping_cost / total) so lists,
 *     invoices and reports match what the customer actually paid.
 *
 * Confirmed live field shape (result.*):
 *   coupon_codes[] · coupon_discount · total_discount · prepaid_discount ·
 *   cod_charges · shipping_charges · subtotal_price · total_amount_payable
 *   (tax is absent → 0).
 *
 * Safe: --dry-run previews every change and writes nothing.
 */
class SyncShiprocketPricing extends Command
{
    protected $signature = 'shiprocket:sync-pricing
        {--tenant= : Only this tenant id (default: all tenants — used by the scheduler)}
        {--order= : Only this order_number}
        {--limit=100 : Max orders to process per tenant}
        {--dry-run : Preview changes without writing}';

    protected $description = 'Pull real Shiprocket pricing (coupon/discount/COD) onto orders from the order-details API';

    /**
     * Loop tenants (like shiprocket:reconcile-orders) so this can be scheduled
     * centrally. Each tenant is initialized, synced, then torn down.
     */
    public function handle(ShiprocketCheckoutService $sr): int
    {
        $dry = (bool) $this->option('dry-run');

        $tenants = $this->option('tenant')
            ? Tenant::where('id', $this->option('tenant'))->get()
            : Tenant::all();

        if ($tenants->isEmpty()) {
            $this->error('No matching tenant.');
            return self::FAILURE;
        }

        $updated = 0; $skipped = 0; $failed = 0;

        foreach ($tenants as $tenant) {
            tenancy()->initialize($tenant);
            try {
                [$u, $s, $f] = $this->syncTenant($sr, $dry);
                $updated += $u; $skipped += $s; $failed += $f;
            } catch (\Throwable $e) {
                $this->error("[{$tenant->id}] sync-pricing failed: {$e->getMessage()}");
            } finally {
                tenancy()->end();
            }
        }

        $this->newLine();
        $this->info(($dry ? '[DRY RUN] would update' : 'Updated') . ": {$updated} | skipped: {$skipped} | failed: {$failed}");
        return self::SUCCESS;
    }

    /**
     * Sync one (already-initialized) tenant. Returns [updated, skipped, failed].
     */
    private function syncTenant(ShiprocketCheckoutService $sr, bool $dry): array
    {
        $query = Order::query()->whereNotNull('shiprocket_order_id');
        if ($orderNo = $this->option('order')) {
            $query->where('order_number', $orderNo);
        } else {
            // Candidates: no sr_pricing captured yet, or a zero discount we may be able to correct.
            $query->orderByDesc('id')->limit((int) $this->option('limit'));
        }

        $orders = $query->get();
        if ($orders->isEmpty()) {
            return [0, 0, 0];
        }

        $this->info(($dry ? '[DRY RUN] ' : '') . tenant('id') . ": processing {$orders->count()} order(s)…");
        $updated = 0; $skipped = 0; $failed = 0;

        foreach ($orders as $order) {
            $s = $sr->syncOrderPricing($order, $dry); // shared with OrderObserver + job
            if ($s === null) {
                $this->line("  {$order->order_number}: API returned no data — skipped");
                $failed++;
                continue;
            }
            if (!$s['changed']) {
                $skipped++;
                continue;
            }

            $codeStr = $s['codes'] ? implode('+', array_map(fn ($c) => is_array($c) ? ($c['code'] ?? $c['name'] ?? '?') : $c, $s['codes'])) : '—';
            $this->line(sprintf(
                '  %s: total %s→%s | discount %s→%s | pay %s→%s | codes=%s',
                $s['order'],
                number_format($s['old_total'], 2), number_format($s['new_total'], 2),
                number_format($s['old_discount'], 2), number_format($s['new_discount'], 2),
                $s['old_pay'], $s['new_pay'],
                $codeStr
            ));
            $updated++;
        }

        return [$updated, $skipped, $failed];
    }
}
