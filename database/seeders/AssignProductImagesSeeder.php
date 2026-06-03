<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AssignProductImagesSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::where('sku', 'like', 'SYL-%')
            ->whereDoesntHave('images')
            ->select('id', 'sku', 'name')
            ->get();

        $this->command->info("Products needing images: {$products->count()}");

        $inserted = 0;
        $missing = 0;
        $records = [];

        foreach ($products as $p) {
            // Extract SN from SKU (SYL-0001 -> 1)
            $sn = (int) str_replace('SYL-', '', $p->sku);
            $filename = sprintf('products/product-%04d.jpg', $sn);
            $fullPath = storage_path('app/public/' . $filename);

            if (! file_exists($fullPath)) {
                $missing++;
                $this->command->warn("Missing: {$filename} for {$p->name}");
                continue;
            }

            $records[] = [
                'product_id' => $p->id,
                'url' => $filename,
                'alt_text' => $p->name,
                'position' => 0,
                'is_primary' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $inserted++;
        }

        // Bulk insert in chunks
        foreach (array_chunk($records, 100) as $chunk) {
            DB::table('product_images')->insert($chunk);
        }

        $this->command->info("Inserted: {$inserted} image records");
        $this->command->info("Missing files: {$missing}");
    }
}
