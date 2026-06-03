<?php

namespace App\Console\Commands;

use App\Models\AbandonedCheckout;
use App\Models\Order;
use Illuminate\Console\Command;

/**
 * Import customer details from a Shiprocket Checkout dashboard CSV export.
 *
 * Usage:
 *   php artisan shiprocket:import-csv /path/to/export.csv
 *
 * How to get the CSV:
 *   1. Log in at checkout-dashboard.shiprocket.in
 *   2. Go to Orders → click "Export" (top-right)
 *   3. Download the CSV and upload to the server (or run locally)
 */
class ImportShiprocketCheckoutCsv extends Command
{
    protected $signature = 'shiprocket:import-csv
                            {file : Path to the Shiprocket Checkout CSV export}
                            {--dry-run : Show what would be updated without saving}';

    protected $description = 'Import customer details from a Shiprocket Checkout dashboard CSV export';

    public function handle(): int
    {
        $file = $this->argument('file');

        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return 1;
        }

        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->warn('DRY RUN — no changes will be saved.');
        }

        $handle = fopen($file, 'r');
        $headers = array_map('trim', fgetcsv($handle));

        $this->info('CSV headers: ' . implode(', ', $headers));
        $this->newLine();

        // Normalise header names to lowercase snake_case for flexible matching
        $normalised = array_map(fn ($h) => strtolower(preg_replace('/[\s\-]+/', '_', $h)), $headers);

        $synced  = 0;
        $skipped = 0;
        $notFound = 0;
        $row = 0;

        while (($data = fgetcsv($handle)) !== false) {
            $row++;
            if (count($data) !== count($headers)) {
                continue;
            }

            $record = array_combine($normalised, array_map('trim', $data));

            // Try to extract the Shiprocket order ID (hex) — common column names from their CSV
            $srOrderId = $record['order_id']
                ?? $record['id']
                ?? $record['checkout_order_id']
                ?? $record['shiprocket_order_id']
                ?? null;

            // Customer fields — Shiprocket uses various column names
            $name   = $record['customer_name'] ?? $record['name'] ?? $record['billing_name'] ?? null;
            $email  = $record['customer_email'] ?? $record['email'] ?? $record['billing_email'] ?? null;
            $phone  = $record['customer_phone'] ?? $record['phone'] ?? $record['mobile'] ?? $record['billing_phone'] ?? null;
            $addr1  = $record['address'] ?? $record['billing_address'] ?? $record['address_line_1'] ?? null;
            $city   = $record['city'] ?? $record['billing_city'] ?? null;
            $state  = $record['state'] ?? $record['billing_state'] ?? null;
            $pin    = $record['pincode'] ?? $record['postal_code'] ?? $record['billing_pincode'] ?? null;

            if (!$srOrderId) {
                $this->warn("Row {$row}: no order_id found, skipping. (keys: " . implode(', ', array_keys($record)) . ")");
                $skipped++;
                continue;
            }

            // Match our order by shiprocket_order_id
            $order = Order::where('shiprocket_order_id', $srOrderId)->first();

            if (!$order) {
                // Try abandoned checkout as a secondary source
                $abandoned = AbandonedCheckout::where('shiprocket_order_id', $srOrderId)
                    ->whereNotNull('order_id')
                    ->first();
                if ($abandoned) {
                    $order = Order::find($abandoned->order_id);
                }
            }

            if (!$order) {
                $this->line("Row {$row}: SR #{$srOrderId} — not found in our DB.");
                $notFound++;
                continue;
            }

            $updates = [];

            if (empty($order->guest_name) && !empty($name)) {
                $updates['guest_name'] = $name;
            }
            if (empty($order->guest_email) && !empty($email)) {
                $updates['guest_email'] = $email;
            }
            if (empty($order->guest_phone) && !empty($phone)) {
                $updates['guest_phone'] = $phone;
            }
            if (empty($order->shipping_address_snapshot) && !empty($updates)) {
                $updates['shipping_address_snapshot'] = [
                    'name'           => $updates['guest_name'] ?? $order->guest_name ?? '',
                    'phone'          => $updates['guest_phone'] ?? $order->guest_phone ?? '',
                    'address_line_1' => $addr1 ?? '',
                    'city'           => $city ?? '',
                    'state'          => $state ?? '',
                    'postal_code'    => $pin ?? '',
                    'country'        => 'India',
                ];
            }

            if (empty($updates)) {
                $this->line("Row {$row}: {$order->order_number} — already has customer data.");
                $skipped++;
                continue;
            }

            if (!$dryRun) {
                $order->update($updates);
            }

            $this->info("Row {$row}: {$order->order_number} — " . ($dryRun ? '[DRY] would update: ' : 'updated: ') . implode(', ', array_keys($updates)));
            $synced++;
        }

        fclose($handle);

        $this->newLine();
        $this->info("Done. Rows processed: {$row} | Updated: {$synced} | Skipped: {$skipped} | Not found: {$notFound}");

        if ($notFound > 0) {
            $this->warn("Tip: {$notFound} CSV rows had no matching order. Column names in CSV may differ — check the header mapping above.");
        }

        return 0;
    }
}
