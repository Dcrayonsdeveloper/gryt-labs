<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class LowStockAlert extends Command
{
    protected $signature = 'inventory:low-stock-alert';
    protected $description = 'Send daily digest email when products are low or out of stock';

    public function handle(): int
    {
        $lowStockProducts = Product::active()
            ->where('stock_quantity', '>', 0)
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->orderBy('stock_quantity')
            ->get(['id', 'name', 'sku', 'stock_quantity', 'low_stock_threshold']);

        $outOfStockProducts = Product::active()
            ->where('stock_quantity', '<=', 0)
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'stock_quantity', 'low_stock_threshold']);

        if ($lowStockProducts->isEmpty() && $outOfStockProducts->isEmpty()) {
            $this->info('No low or out-of-stock products found.');
            return self::SUCCESS;
        }

        $adminEmail = Setting::get('support_email', '');
        if (!$adminEmail) {
            $this->warn('No support_email configured in settings. Skipping alert.');
            return self::SUCCESS;
        }

        $storeName = Setting::get('store_name', config('app.name'));
        $lowCount = $lowStockProducts->count();
        $outCount = $outOfStockProducts->count();
        $subject = "Inventory Alert: {$lowCount} low stock, {$outCount} out of stock — {$storeName}";

        $lowRows = '';
        foreach ($lowStockProducts as $product) {
            $sku = e($product->sku ?: 'N/A');
            $name = e($product->name);
            $lowRows .= "<tr>
                <td style='padding:8px 12px;border-bottom:1px solid #eee;font-size:13px;'>{$name}</td>
                <td style='padding:8px 12px;border-bottom:1px solid #eee;font-size:13px;'>{$sku}</td>
                <td style='padding:8px 12px;border-bottom:1px solid #eee;font-size:13px;text-align:center;color:#B45309;font-weight:600;'>{$product->stock_quantity}</td>
                <td style='padding:8px 12px;border-bottom:1px solid #eee;font-size:13px;text-align:center;'>{$product->low_stock_threshold}</td>
            </tr>";
        }

        $outRows = '';
        foreach ($outOfStockProducts as $product) {
            $sku = e($product->sku ?: 'N/A');
            $name = e($product->name);
            $outRows .= "<tr>
                <td style='padding:8px 12px;border-bottom:1px solid #eee;font-size:13px;'>{$name}</td>
                <td style='padding:8px 12px;border-bottom:1px solid #eee;font-size:13px;'>{$sku}</td>
                <td style='padding:8px 12px;border-bottom:1px solid #eee;font-size:13px;text-align:center;color:#DC2626;font-weight:600;'>0</td>
            </tr>";
        }

        $lowSection = '';
        if ($lowStockProducts->isNotEmpty()) {
            $lowSection = "
                <h2 style='color:#B45309;font-size:16px;margin:24px 0 8px;'>Low Stock ({$lowCount})</h2>
                <table style='width:100%;border-collapse:collapse;'>
                    <thead>
                        <tr style='background:#FEF3C7;'>
                            <th style='padding:8px 12px;text-align:left;font-size:12px;text-transform:uppercase;color:#92400E;'>Product</th>
                            <th style='padding:8px 12px;text-align:left;font-size:12px;text-transform:uppercase;color:#92400E;'>SKU</th>
                            <th style='padding:8px 12px;text-align:center;font-size:12px;text-transform:uppercase;color:#92400E;'>Stock</th>
                            <th style='padding:8px 12px;text-align:center;font-size:12px;text-transform:uppercase;color:#92400E;'>Threshold</th>
                        </tr>
                    </thead>
                    <tbody>{$lowRows}</tbody>
                </table>";
        }

        $outSection = '';
        if ($outOfStockProducts->isNotEmpty()) {
            $outSection = "
                <h2 style='color:#DC2626;font-size:16px;margin:24px 0 8px;'>Out of Stock ({$outCount})</h2>
                <table style='width:100%;border-collapse:collapse;'>
                    <thead>
                        <tr style='background:#FEE2E2;'>
                            <th style='padding:8px 12px;text-align:left;font-size:12px;text-transform:uppercase;color:#991B1B;'>Product</th>
                            <th style='padding:8px 12px;text-align:left;font-size:12px;text-transform:uppercase;color:#991B1B;'>SKU</th>
                            <th style='padding:8px 12px;text-align:center;font-size:12px;text-transform:uppercase;color:#991B1B;'>Stock</th>
                        </tr>
                    </thead>
                    <tbody>{$outRows}</tbody>
                </table>";
        }

        $html = "
            <div style='font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;max-width:640px;margin:0 auto;'>
                <div style='padding:24px;background:#fff;'>
                    <h1 style='color:#111;font-size:20px;margin:0 0 4px;'>Inventory Alert</h1>
                    <p style='color:#666;font-size:13px;margin:0 0 16px;'>" . now()->format('l, j F Y') . "</p>
                    {$lowSection}
                    {$outSection}
                </div>
                <div style='padding:16px 24px;background:#f5f5f5;text-align:center;'>
                    <p style='color:#999;font-size:11px;margin:0;'>— {$storeName}</p>
                </div>
            </div>";

        try {
            Mail::send([], [], function ($m) use ($adminEmail, $subject, $html) {
                $m->to($adminEmail)->subject($subject)->html($html);
            });

            $this->info("Low stock alert sent to {$adminEmail} ({$lowCount} low, {$outCount} out of stock).");
        } catch (\Exception $e) {
            Log::error('Low stock alert email failed', ['error' => $e->getMessage()]);
            $this->error('Failed to send alert: ' . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
