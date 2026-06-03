<?php

namespace App\Console\Commands;

use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Console\Command;

class UpdateSocialProof extends Command
{
    protected $signature = 'products:update-social-proof
                            {--dry-run : Show changes without saving}';

    protected $description = 'Auto-update social proof text on all products based on recent sales data';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $products = Product::where('is_active', true)->get(['id', 'name', 'social_proof_text', 'sales_count']);

        $updated = 0;

        foreach ($products as $product) {
            // Count unique orders (not qty) in last 30 days
            $last30 = OrderItem::where('product_id', $product->id)
                ->whereHas('order', fn ($q) => $q->whereNotIn('status', ['cancelled', 'returned']))
                ->where('created_at', '>=', now()->subDays(30))
                ->sum('quantity');

            // Count in last 7 days
            $last7 = OrderItem::where('product_id', $product->id)
                ->whereHas('order', fn ($q) => $q->whereNotIn('status', ['cancelled', 'returned']))
                ->where('created_at', '>=', now()->subDays(7))
                ->sum('quantity');

            // Build social proof text based on actual numbers + boost
            $text = $this->generateText($last30, $last7, $product->sales_count);

            if ($text !== $product->social_proof_text) {
                if ($dryRun) {
                    $this->line("[DRY] {$product->name}: \"{$product->social_proof_text}\" → \"{$text}\"");
                } else {
                    $product->update(['social_proof_text' => $text]);
                }
                $updated++;
            }
        }

        $this->info(($dryRun ? '[DRY RUN] ' : '') . "Updated social proof on {$updated} products.");

        return self::SUCCESS;
    }

    private function generateText(int $last30, int $last7, int $totalSales): ?string
    {
        // High recent demand (7 days)
        if ($last7 >= 20) {
            return "🔥 {$last7}+ bought in the last week";
        }

        if ($last7 >= 10) {
            return "Trending! {$last7}+ sold this week";
        }

        // Good monthly sales
        if ($last30 >= 50) {
            return "{$last30}+ bought in the last month";
        }

        if ($last30 >= 20) {
            return "{$last30}+ people bought this recently";
        }

        if ($last30 >= 5) {
            return "{$last30}+ sold this month";
        }

        // Fall back to all-time sales count
        if ($totalSales >= 500) {
            return "{$totalSales}+ happy customers";
        }

        if ($totalSales >= 100) {
            return "{$totalSales}+ sold";
        }

        if ($totalSales >= 20) {
            return "Trusted by {$totalSales}+ customers";
        }

        // Not enough data — hide social proof
        return null;
    }
}
