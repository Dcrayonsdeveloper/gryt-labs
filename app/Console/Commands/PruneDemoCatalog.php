<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * One-time maintenance: remove leftover demo catalog data (phone/electronics
 * seed products and their categories) so the admin only shows real categories.
 *
 * Keeps every ACTIVE category and every product that belongs to one. Deletes
 * all other (inactive, demo) categories and the products that reference them,
 * along with those products' child rows (images, reviews, etc.).
 *
 * Dry-run by default; pass --force to actually delete. Writes a JSON backup of
 * everything it removes before deleting.
 */
class PruneDemoCatalog extends Command
{
    protected $signature = 'catalog:prune-demo {--force : Actually delete (otherwise dry-run)}';

    protected $description = 'Delete leftover demo categories and their products, keeping only real (active) categories';

    public function handle(): int
    {
        $keepCats = Category::where('is_active', true)->pluck('id');
        $removeCats = Category::whereNotIn('id', $keepCats)->pluck('id');
        $removeProducts = Product::whereIn('category_id', $removeCats)->pluck('id');

        $this->info('Keeping categories: '.$keepCats->implode(', ').' ('.$keepCats->count().')');
        $this->info('Removing categories: '.$removeCats->count());
        $this->info('Removing products:   '.$removeProducts->count());

        if ($removeCats->isEmpty() && $removeProducts->isEmpty()) {
            $this->info('Nothing to prune.');
            return self::SUCCESS;
        }

        // Discover child tables that reference these products (so we don't orphan rows).
        $childTables = collect(DB::select(
            "SELECT DISTINCT TABLE_NAME AS t FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME = 'product_id'"
        ))->pluck('t')->filter(fn ($t) => $t !== 'products')->values();

        $this->line('Child tables referencing product_id: '.$childTables->implode(', '));

        if (! $this->option('force')) {
            $this->warn('DRY RUN — nothing deleted. Re-run with --force to apply.');
            return self::SUCCESS;
        }

        // --- Backup everything we're about to delete ---
        $backup = [
            'generated_at' => now()->toIso8601String(),
            'categories'   => Category::whereIn('id', $removeCats)->get()->toArray(),
            'products'     => Product::whereIn('id', $removeProducts)->get()->toArray(),
            'child_rows'   => [],
        ];
        foreach ($childTables as $t) {
            $backup['child_rows'][$t] = DB::table($t)->whereIn('product_id', $removeProducts)->get()->toArray();
        }
        $file = 'backups/demo-catalog-prune-'.now()->format('Ymd_His').'.json';
        Storage::disk('local')->put($file, json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->info('Backup written: storage/app/'.$file);

        // --- Delete (FK checks off so order/self-references don't block us) ---
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            foreach ($childTables as $t) {
                $n = DB::table($t)->whereIn('product_id', $removeProducts)->delete();
                if ($n) $this->line("  {$t}: deleted {$n}");
            }
            DB::table('category_product')->whereIn('category_id', $removeCats)->delete();
            $dp = Product::whereIn('id', $removeProducts)->delete();
            $dc = Category::whereIn('id', $removeCats)->delete();
            $this->info("Deleted {$dp} products and {$dc} categories.");
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $this->newLine();
        $this->info('Remaining categories:');
        foreach (Category::orderBy('id')->get() as $c) {
            $this->line("  #{$c->id} {$c->name} (".($c->is_active ? 'active' : 'inactive').')');
        }

        return self::SUCCESS;
    }
}
