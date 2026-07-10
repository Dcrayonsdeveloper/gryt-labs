<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * Files the GRYT shaker bottles under their own "Shakers & Bottles" category
 * instead of "Supplements" (they are drinkware/accessories, not supplements).
 *
 * Idempotent: safe to run repeatedly. Matches products by slug so it does not
 * depend on hardcoded IDs.
 */
class ShakerCategorySeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ensure the "Shakers & Bottles" category exists and is active.
        $category = Category::firstOrNew(['slug' => 'shakers-bottles']);
        $category->parent_id   = null;
        $category->name        = 'Shakers & Bottles';
        $category->position    = 2;
        $category->level       = 0;
        $category->is_active   = true;
        $category->is_featured = true;
        $category->save();

        // Root category path mirrors its own id (matches existing categories).
        if ($category->path !== (string) $category->id) {
            $category->path = (string) $category->id;
            $category->save();
        }

        // 2. Move the shaker bottles into it (matched by slug).
        $shakerSlugs = [
            'steel-shaker',
            'storm-shaker-plastic',
            'small-plastic-shaker-red',
            'small-plastic-shaker-blue',
        ];

        $moved = Product::whereIn('slug', $shakerSlugs)
            ->where('category_id', '!=', $category->id)
            ->update(['category_id' => $category->id]);

        $this->command?->info("Shakers & Bottles category id {$category->id}; {$moved} product(s) moved.");
    }
}
