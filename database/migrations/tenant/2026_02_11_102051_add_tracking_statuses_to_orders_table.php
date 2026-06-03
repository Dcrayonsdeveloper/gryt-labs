<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Status columns are text/varchar in PostgreSQL — no ENUM modification needed.
        // Values are enforced at application level.

        if (!Schema::hasColumn('orders', 'packed_at')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->timestamp('packed_at')->nullable()->after('confirmed_at');
            });
        }
        if (!Schema::hasColumn('orders', 'out_for_delivery_at')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->timestamp('out_for_delivery_at')->nullable()->after('shipped_at');
            });
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['packed_at', 'out_for_delivery_at']);
        });
    }
};
