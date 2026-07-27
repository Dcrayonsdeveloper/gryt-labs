<?php

namespace App\Console\Commands;

use App\Models\Order;
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
        {--order= : Only this order_number}
        {--limit=100 : Max orders to process}
        {--dry-run : Preview changes without writing}';

    protected $description = 'Pull real Shiprocket pricing (coupon/discount/COD) onto orders from the order-details API';

    public function handle(ShiprocketCheckoutService $sr): int
    {
        $dry = (bool) $this->option('dry-run');

        $query = Order::query()->whereNotNull('shiprocket_order_id');
        if ($orderNo = $this->option('order')) {
            $query->where('order_number', $orderNo);
        } else {
            // Candidates: no sr_pricing captured yet, or a zero discount we may be able to correct.
            $query->orderByDesc('id')->limit((int) $this->option('limit'));
        }

        $orders = $query->get();
        if ($orders->isEmpty()) {
            $this->warn('No matching orders with a shiprocket_order_id.');
            return self::SUCCESS;
        }

        $this->info(($dry ? '[DRY RUN] ' : '') . "Processing {$orders->count()} order(s)…");
        $updated = 0; $skipped = 0; $failed = 0;

        foreach ($orders as $order) {
            $r = $sr->getOrder((string) $order->shiprocket_order_id);
            if (!is_array($r)) {
                $this->line("  {$order->order_number}: API returned no data — skipped");
                $failed++;
                continue;
            }

            $subtotal  = (float) ($r['subtotal_price'] ?? $order->subtotal);
            $shipping  = (float) ($r['shipping_charges'] ?? $r['shipping_price'] ?? $order->shipping_cost);
            $codCharge = array_key_exists('cod_charges', $r) ? (float) $r['cod_charges'] : null;
            $prepaid   = isset($r['prepaid_discount']) ? (float) $r['prepaid_discount'] : null;
            $couponDisc = (float) ($r['coupon_discount'] ?? $r['total_discount'] ?? 0);
            $couponCodes = array_values(array_filter((array) ($r['coupon_codes'] ?? [])));
            $total     = (float) ($r['total_amount_payable'] ?? ($subtotal - $couponDisc + $shipping));
            // Transactions (gateway/method/amount/status/date) — empty for COD.
            $payments  = array_values(array_filter((array) ($r['payments'] ?? [])));

            // Payment state: sum successful online payments → mark paid / collected.
            $onlineReceived = 0.0; $txnDate = null;
            foreach ($payments as $pay) {
                if (strtolower((string) ($pay['payment_status'] ?? '')) === 'success') {
                    $onlineReceived += (float) ($pay['amount_received'] ?? $pay['amount'] ?? 0);
                    $txnDate = $txnDate ?: ($pay['created_at'] ?? null);
                }
            }
            $newPayStatus   = $order->payment_status;
            $newPaidAmount  = (float) $order->paid_amount;
            $newCollectedAt = $order->payment_collected_at;
            $newCollected   = (bool) $order->payment_collected;
            if ($onlineReceived > 0) {
                $newPaidAmount = $onlineReceived;
                if (!$newCollectedAt && $txnDate) { $newCollectedAt = \Illuminate\Support\Carbon::parse($txnDate); }
                if ($onlineReceived + 0.01 >= $total) { $newPayStatus = 'paid'; $newCollected = true; }
            }

            $srPricing = [
                'total_price'          => $subtotal,           // pre-discount item total (Subtotal row)
                'total_discount'       => (float) ($r['total_discount'] ?? $couponDisc),
                'coupon_discount'      => $couponDisc,
                'coupon_codes'         => $couponCodes,
                'prepaid_discount'     => $prepaid,
                'cod_charges'          => $codCharge,
                'shipping_price'       => $shipping,
                'tax'                  => 0.0,
                'total_amount_payable' => $total,
                'net_payable'          => $total,
                'synced_at'            => now()->toIso8601String(),
            ];

            // Nothing meaningful to record and everything already captured → skip.
            $columnsMatch    = abs((float) $order->total - $total) < 0.01
                && abs((float) $order->discount - $couponDisc) < 0.01;
            $pricingCaptured = !empty($order->metadata['sr_pricing']);
            $paymentsCaptured = !$payments || !empty($order->metadata['sr_payments']);
            $paymentMatches   = $newPayStatus === $order->payment_status
                && abs($newPaidAmount - (float) $order->paid_amount) < 0.01
                && $newCollected === (bool) $order->payment_collected;
            if (!$couponCodes && $couponDisc <= 0 && $columnsMatch && $pricingCaptured && $paymentsCaptured && $paymentMatches) {
                $skipped++;
                continue;
            }

            $codeStr = $couponCodes ? implode('+', array_map(fn ($c) => is_array($c) ? ($c['code'] ?? $c['name'] ?? '?') : $c, $couponCodes)) : '—';
            $this->line(sprintf(
                '  %s: total %s→%s | discount %s→%s | pay %s→%s | codes=%s',
                $order->order_number,
                number_format((float) $order->total, 2), number_format($total, 2),
                number_format((float) $order->discount, 2), number_format($couponDisc, 2),
                $order->payment_status, $newPayStatus,
                $codeStr
            ));

            if (!$dry) {
                $meta = is_array($order->metadata) ? $order->metadata : [];
                $meta['sr_pricing'] = $srPricing;
                if ($payments) { $meta['sr_payments'] = $payments; }
                $order->update([
                    'subtotal'             => $subtotal,
                    'discount'             => $couponDisc,
                    'shipping_cost'        => $shipping,
                    'total'                => $total,
                    'payment_status'       => $newPayStatus,
                    'paid_amount'          => $newPaidAmount,
                    'payment_collected'    => $newCollected,
                    'payment_collected_at' => $newCollectedAt,
                    'metadata'             => $meta,
                ]);
            }
            $updated++;
        }

        $this->newLine();
        $this->info(($dry ? '[DRY RUN] would update' : 'Updated') . ": {$updated} | skipped: {$skipped} | failed: {$failed}");
        return self::SUCCESS;
    }
}
