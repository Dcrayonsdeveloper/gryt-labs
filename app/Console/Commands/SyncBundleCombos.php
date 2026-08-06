<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Console\Command;

/**
 * Create/refresh the "Pack of 2" combo product for every bundle product.
 *
 * WHY: Shiprocket Checkout prices strictly linearly (unit price × qty, and the
 * customer can edit qty inside the SR popup), so a non-linear rule like
 * "1 → ₹299 but 2 → ₹499" cannot be represented with one unit price. The fix is
 * Amazon-style: a real combo product priced at the pair price. A pair is then
 * qty 1 of the combo (₹499) and a single is qty 1 of the base (₹299) — always
 * linear, so SR can never miscompute.
 *
 * Links: base.pack_config.bundle.combo_product_id → combo,
 *        combo.pack_config.combo_of → base. Cart::normalizeBundles() uses these
 * to auto-convert "2 singles" into "1 pack" in the cart.
 *
 *   php artisan bundles:sync-combos --tenant=gryt
 */
class SyncBundleCombos extends Command
{
    protected $signature = 'bundles:sync-combos {--tenant= : Only this tenant id (default: all)}';

    protected $description = 'Create/refresh the Pack-of-2 combo product for every bundle product';

    public function handle(): int
    {
        $tenants = $this->option('tenant')
            ? Tenant::where('id', $this->option('tenant'))->get()
            : Tenant::all();

        foreach ($tenants as $tenant) {
            tenancy()->initialize($tenant);
            try {
                $this->syncTenant();
            } catch (\Throwable $e) {
                $this->error("[{$tenant->id}] " . $e->getMessage());
            } finally {
                tenancy()->end();
            }
        }

        return self::SUCCESS;
    }

    private function syncTenant(): void
    {
        $bases = Product::whereNotNull('pack_config')->get()
            ->filter(fn ($p) => $p->packBundle() !== null && empty($p->pack_config['combo_of']));

        foreach ($bases as $base) {
            $bundle = $base->packBundle();
            $pair   = (float) ($bundle['pair'] ?? ($bundle['tiers'][2] ?? 0));
            if ($pair <= 0) {
                $this->warn("  {$base->name}: no pair price — skipped.");
                continue;
            }

            $comboSku = ($base->sku ?: ('SKU-' . $base->id)) . '-PACK2';

            $combo = null;
            if ($cid = $base->pack_config['bundle']['combo_product_id'] ?? null) {
                $combo = Product::find($cid);
            }
            $combo ??= Product::where('sku', $comboSku)->first();

            $attrs = [
                // "Pack of 2" alone reads as one item at Shiprocket checkout, where
                // the line shows quantity 1 — spell out that the pack contains 2 units.
                'name'              => $base->name . ' (Pack of 2 — contains 2 units)',
                'sku'               => $comboSku,
                'price'             => $pair,
                'mrp'               => (float) $base->mrp * 2,
                'category_id'       => $base->category_id,
                'short_description' => 'Combo pack: 2 x ' . $base->name . ' at a bundled price.',
                'description'       => $base->description,
                'stock_quantity'    => (int) floor($base->stock_quantity / 2),
                'weight'            => $base->weight ? $base->weight * 2 : null,
                'weight_unit'       => $base->weight_unit,
                'is_taxable'        => $base->is_taxable,
                'tax_rate'          => $base->tax_rate,
                'is_active'         => $base->is_active,
                'status'            => $base->status,
                'pack_config'       => ['combo_of' => $base->id],
            ];

            if ($combo) {
                $combo->fill($attrs)->save();
                $this->line("  updated combo: {$combo->name} (#{$combo->id}) @ ₹{$pair}");
            } else {
                $combo = Product::create($attrs);
                $this->line("  created combo: {$combo->name} (#{$combo->id}) @ ₹{$pair}");
            }

            // Copy the base product's primary image if the combo has none.
            if ($combo->images()->count() === 0 && ($img = $base->images()->first())) {
                $combo->images()->create([
                    'url'        => $img->getRawOriginal('url'),
                    'alt_text'   => $combo->name,
                    'position'   => 0,
                    'is_primary' => true,
                ]);
            }

            // Link back on the base product (fires ProductObserver → SR catalog push
            // for the base too, refreshing its now-unfloored catalog price).
            $pc = $base->pack_config;
            $pc['bundle']['combo_product_id'] = $combo->id;
            $base->pack_config = $pc;
            $base->save();
        }

        $this->info('  done: ' . $bases->count() . ' bundle product(s) processed.');
    }
}
