<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a per-product flag to manually exclude a product from the
 * "New Arrivals" sections (homepage, /new-arrivals listing, and API),
 * without affecting its visibility anywhere else.
 *
 * New Arrivals is otherwise purely date-based (created_at within the
 * configured window) — this flag is the only per-product override.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'exclude_from_new_arrivals')) {
                $table->boolean('exclude_from_new_arrivals')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'exclude_from_new_arrivals')) {
                $table->dropColumn('exclude_from_new_arrivals');
            }
        });
    }
};
