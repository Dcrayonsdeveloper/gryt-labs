<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('orders', 'shiprocket_order_id')) {
            return;
        }

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS orders_shiprocket_order_id_unique ON orders (shiprocket_order_id) WHERE shiprocket_order_id IS NOT NULL');

            return;
        }

        // MySQL: a plain UNIQUE index already permits multiple NULLs, matching the
        // partial-index intent. CREATE INDEX has no "IF NOT EXISTS", so guard manually.
        $exists = collect(DB::select("SHOW INDEX FROM orders WHERE Key_name = 'orders_shiprocket_order_id_unique'"))->isNotEmpty();
        if (! $exists) {
            DB::statement('CREATE UNIQUE INDEX orders_shiprocket_order_id_unique ON orders (shiprocket_order_id)');
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS orders_shiprocket_order_id_unique');

            return;
        }

        $exists = collect(DB::select("SHOW INDEX FROM orders WHERE Key_name = 'orders_shiprocket_order_id_unique'"))->isNotEmpty();
        if ($exists) {
            DB::statement('ALTER TABLE orders DROP INDEX orders_shiprocket_order_id_unique');
        }
    }
};
