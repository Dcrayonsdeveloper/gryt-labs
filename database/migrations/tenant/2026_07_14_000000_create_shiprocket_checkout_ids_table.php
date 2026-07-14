<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bridge table linking every Shiprocket-issued ID to its AbandonedCheckout.
 *
 * Shiprocket hands out different IDs for the same session — the token's
 * order_id, the webhook's cart_id, and the callback's `oid` — so this table
 * maps ALL of them onto one AbandonedCheckout row.
 *
 * The table was referenced by AbandonedCheckout::findByShiprocketId() /
 * registerShiprocketId() but no migration ever created it, so the Shiprocket
 * success callback 500'd on every completed order (the lookup runs before the
 * order is created, so orders were lost entirely).
 *
 * Guarded with hasTable() because the table was created by hand on production
 * to stop the bleeding before this migration existed.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('shiprocket_checkout_ids')) {
            return;
        }

        Schema::create('shiprocket_checkout_ids', function (Blueprint $table) {
            $table->id();
            $table->string('shiprocket_id')->unique();
            $table->foreignId('abandoned_checkout_id')
                ->constrained('abandoned_checkouts')
                ->cascadeOnDelete();
            $table->string('id_type', 32)->default('unknown');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shiprocket_checkout_ids');
    }
};
