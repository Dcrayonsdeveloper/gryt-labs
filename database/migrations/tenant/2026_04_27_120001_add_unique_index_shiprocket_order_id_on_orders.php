<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('orders', 'shiprocket_order_id')) {
            return;
        }

        \Illuminate\Support\Facades\DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS orders_shiprocket_order_id_unique ON orders (shiprocket_order_id) WHERE shiprocket_order_id IS NOT NULL');
    }

    public function down(): void
    {
        \Illuminate\Support\Facades\DB::statement('DROP INDEX IF EXISTS orders_shiprocket_order_id_unique');
    }
};
