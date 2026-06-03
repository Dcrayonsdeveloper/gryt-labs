<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Converts "New Arrivals" from automatic (date-based) to a manually-curated
 * flag — admins now pick which products appear, exactly like Featured.
 *
 * `is_new_arrival` = true  → product shows in the New Arrivals sections.
 *
 * The old subtract-only `exclude_from_new_arrivals` column is left in place
 * (dead/unused) so the deploy has no window where live code references a
 * dropped column; it can be removed in a later cleanup migration.
 *
 * Backfill: every product that currently qualifies as a date-based new
 * arrival is flagged, so the section is not emptied — admins curate from here.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Add the manual flag.
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'is_new_arrival')) {
                $table->boolean('is_new_arrival')->default(false);
            }
        });

        // 2. Backfill — preserve whatever currently shows as New Arrivals.
        $days = 30;
        try {
            $val = DB::table('settings')->where('key', 'homepage_new_arrivals_days')->value('value');
            if (is_numeric($val) && (int) $val > 0) {
                $days = (int) $val;
            }
        } catch (\Throwable) {
            // settings table unavailable — fall back to 30 days
        }

        $query = DB::table('products')
            ->where('is_active', true)
            ->where('stock_quantity', '>', 0)
            ->where('created_at', '>=', now()->subDays($days));

        if (Schema::hasColumn('products', 'exclude_from_new_arrivals')) {
            $query->where('exclude_from_new_arrivals', false);
        }

        $query->update(['is_new_arrival' => true]);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'is_new_arrival')) {
                $table->dropColumn('is_new_arrival');
            }
        });
    }
};
