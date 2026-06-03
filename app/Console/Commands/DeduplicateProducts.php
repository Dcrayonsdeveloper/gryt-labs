<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeduplicateProducts extends Command
{
    protected $signature = 'products:deduplicate
                            {--tenant= : Tenant ID to run against (required)}
                            {--csv= : Shopify CSV export — only keep products whose Handle matches a slug in the CSV}
                            {--dry-run : Show what would be deleted without actually deleting}';

    protected $description = 'Soft-delete duplicate products. With --csv, keeps only products matching the CSV handles.';

    public function handle(): int
    {
        $tenantId = $this->option('tenant');
        if (!$tenantId) {
            $this->error('--tenant is required');
            return self::FAILURE;
        }

        $tenant = \App\Models\Tenant::find($tenantId);
        if (!$tenant) {
            $this->error("Tenant '{$tenantId}' not found");
            return self::FAILURE;
        }

        tenancy()->initialize($tenant);
        $this->info("Initialized tenant: {$tenantId}");

        $csvPath = $this->option('csv');
        if ($csvPath) {
            return $this->deduplicateFromCsv($csvPath);
        }

        return $this->deduplicateByName();
    }

    private function deduplicateByName(): int
    {
        $dryRun = $this->option('dry-run');

        $dupeGroups = DB::table('products')
            ->select('name', DB::raw('COUNT(*) as cnt'), DB::raw('MIN(id) as keep_id'))
            ->whereNull('deleted_at')
            ->groupBy('name')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($dupeGroups->isEmpty()) {
            $this->info('No duplicate products found.');
            return self::SUCCESS;
        }

        $this->info("Found {$dupeGroups->count()} duplicate groups.");

        $totalDeleted = 0;
        $orderItemsMoved = 0;

        foreach ($dupeGroups as $group) {
            $dupeIds = DB::table('products')
                ->where('name', $group->name)
                ->whereNull('deleted_at')
                ->where('id', '!=', $group->keep_id)
                ->pluck('id')
                ->toArray();

            $affectedOrders = DB::table('order_items')
                ->whereIn('product_id', $dupeIds)
                ->count();

            $action = $dryRun ? 'Would soft-delete' : 'Soft-deleting';
            $this->line("{$action} IDs [" . implode(',', $dupeIds) . "] — keeping ID {$group->keep_id} | \"{$group->name}\"");

            if ($affectedOrders > 0) {
                $this->warn("  ↳ {$affectedOrders} order item(s) — reassigning to ID {$group->keep_id}");
            }

            if (!$dryRun) {
                DB::transaction(function () use ($dupeIds, $group, $affectedOrders, &$orderItemsMoved) {
                    if ($affectedOrders > 0) {
                        DB::table('order_items')
                            ->whereIn('product_id', $dupeIds)
                            ->update(['product_id' => $group->keep_id]);
                        $orderItemsMoved += $affectedOrders;
                    }
                    Product::whereIn('id', $dupeIds)->delete();
                });
            }

            $totalDeleted += count($dupeIds);
        }

        $prefix = $dryRun ? '[DRY RUN] ' : '';
        $this->newLine();
        $this->info("{$prefix}Done. {$totalDeleted} duplicate products soft-deleted. {$orderItemsMoved} order items reassigned.");

        $remaining = DB::table('products')->whereNull('deleted_at')->count();
        $this->info("Active products remaining: {$remaining}");

        return self::SUCCESS;
    }

    private function deduplicateFromCsv(string $csvPath): int
    {
        if (!file_exists($csvPath)) {
            $this->error("CSV file not found: {$csvPath}");
            return self::FAILURE;
        }

        $dryRun = $this->option('dry-run');

        // Parse CSV handles + status
        $csvData = $this->parseCsvProducts($csvPath);
        $keepSlugs = array_keys($csvData);
        $this->info("CSV contains " . count($keepSlugs) . " unique product handles.");

        // Find products to remove (slug NOT in CSV)
        $toRemove = DB::table('products')
            ->whereNull('deleted_at')
            ->whereNotIn('slug', $keepSlugs)
            ->get(['id', 'name', 'slug']);

        // Find keeper products (slug IN CSV)
        $keepers = DB::table('products')
            ->whereNull('deleted_at')
            ->whereIn('slug', $keepSlugs)
            ->get(['id', 'name', 'slug', 'is_active'])
            ->keyBy('name');

        $this->info("Products to keep: {$keepers->count()}");
        $this->info("Products to remove: {$toRemove->count()}");

        // --- Step 1: Sync is_active status on keepers to match CSV ---
        $this->newLine();
        $this->info('--- Syncing is_active status ---');
        $statusFixed = 0;

        $keepersBySlug = DB::table('products')
            ->whereNull('deleted_at')
            ->whereIn('slug', $keepSlugs)
            ->get(['id', 'slug', 'is_active']);

        foreach ($keepersBySlug as $p) {
            $csvStatus = $csvData[$p->slug]['status'] ?? '';
            $shouldBeActive = ($csvStatus === 'active');
            $currentlyActive = (bool) $p->is_active;

            if ($shouldBeActive !== $currentlyActive) {
                $from = $currentlyActive ? 'active' : 'inactive';
                $to = $shouldBeActive ? 'active' : 'inactive';
                $action = $dryRun ? 'Would change' : 'Changing';
                $this->line("{$action}: {$p->slug} | {$from} → {$to}");

                if (!$dryRun) {
                    DB::table('products')->where('id', $p->id)->update(['is_active' => $shouldBeActive]);
                }
                $statusFixed++;
            }
        }

        if ($statusFixed === 0) {
            $this->info('All statuses already match CSV.');
        } else {
            $prefix = $dryRun ? '[DRY RUN] ' : '';
            $this->info("{$prefix}{$statusFixed} product statuses updated.");
        }

        // --- Step 2: Soft-delete products not in CSV ---
        if ($toRemove->isEmpty()) {
            $this->newLine();
            $this->info('No products to remove — DB already matches CSV.');
            $remaining = DB::table('products')->whereNull('deleted_at')->count();
            $this->info("Active products remaining: {$remaining}");
            return self::SUCCESS;
        }

        $this->newLine();
        $this->info('--- Removing products not in CSV ---');

        $totalDeleted = 0;
        $orderItemsMoved = 0;
        $reviewsMoved = 0;

        foreach ($toRemove as $product) {
            $keeper = $keepers->get($product->name);

            $orderCount = DB::table('order_items')->where('product_id', $product->id)->count();
            $reviewCount = DB::table('reviews')->where('product_id', $product->id)->count();

            $action = $dryRun ? 'Would remove' : 'Removing';
            $detail = "ID {$product->id} | {$product->slug}";

            if ($keeper) {
                $detail .= " → reassign to keeper ID {$keeper->id} ({$keeper->slug})";
            }
            if ($orderCount > 0) {
                $detail .= " | {$orderCount} order items";
            }
            if ($reviewCount > 0) {
                $detail .= " | {$reviewCount} reviews";
            }

            $this->line("{$action}: {$detail}");

            if (!$dryRun) {
                DB::transaction(function () use ($product, $keeper, $orderCount, $reviewCount, &$orderItemsMoved, &$reviewsMoved) {
                    if ($keeper) {
                        if ($orderCount > 0) {
                            DB::table('order_items')
                                ->where('product_id', $product->id)
                                ->update(['product_id' => $keeper->id]);
                            $orderItemsMoved += $orderCount;
                        }
                        if ($reviewCount > 0) {
                            DB::table('reviews')
                                ->where('product_id', $product->id)
                                ->update(['product_id' => $keeper->id]);
                            $reviewsMoved += $reviewCount;
                        }
                    }
                    Product::where('id', $product->id)->delete();
                });
            }

            $totalDeleted++;
        }

        $prefix = $dryRun ? '[DRY RUN] ' : '';
        $this->newLine();
        $this->info("{$prefix}Done. {$totalDeleted} products soft-deleted.");
        if ($orderItemsMoved > 0) {
            $this->info("{$prefix}{$orderItemsMoved} order items reassigned to keeper products.");
        }
        if ($reviewsMoved > 0) {
            $this->info("{$prefix}{$reviewsMoved} reviews reassigned to keeper products.");
        }
        if ($statusFixed > 0) {
            $this->info("{$prefix}{$statusFixed} product statuses synced to CSV.");
        }

        $remaining = DB::table('products')->whereNull('deleted_at')->count();
        $this->info("Active products remaining: {$remaining}");

        return self::SUCCESS;
    }

    private function parseCsvProducts(string $csvPath): array
    {
        $fh = fopen($csvPath, 'r');
        $header = fgetcsv($fh);
        $handleIdx = array_search('Handle', $header);
        $titleIdx = array_search('Title', $header);
        $statusIdx = array_search('Status', $header);

        if ($handleIdx === false) {
            fclose($fh);
            return [];
        }

        $products = [];
        while (($row = fgetcsv($fh)) !== false) {
            $h = trim($row[$handleIdx] ?? '');
            $t = trim($row[$titleIdx] ?? '');
            $s = trim($row[$statusIdx] ?? '');
            if ($h === '') continue;
            if (!isset($products[$h])) {
                $products[$h] = ['title' => $t, 'status' => $s];
            } elseif ($t !== '' && $products[$h]['title'] === '') {
                $products[$h]['title'] = $t;
                $products[$h]['status'] = $s;
            }
        }
        fclose($fh);

        return $products;
    }
}
