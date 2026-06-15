<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The Order model's $fillable and several controllers/services/jobs (OrderController
 * triage stats, SyncShiprocketCustomerDetails, ShiprocketService, CheckoutController,
 * RazorpayWebhookController, etc.) reference these columns, but no migration ever added
 * them to the `orders` table — they were applied manually in production, so fresh tenant
 * databases (e.g. local grytdb) 500 with "column shiprocket_order_id does not exist".
 *
 * This backfills the schema idempotently and (re)creates the partial unique index that
 * 2026_04_27_120001_add_unique_index_shiprocket_order_id_on_orders skips when the column
 * is absent.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'shiprocket_order_id')) {
                $table->string('shiprocket_order_id', 100)->nullable()->after('tracking_url');
            }
            if (! Schema::hasColumn('orders', 'shiprocket_shipment_id')) {
                $table->string('shiprocket_shipment_id', 100)->nullable()->after('shiprocket_order_id');
            }
            if (! Schema::hasColumn('orders', 'shiprocket_awb')) {
                $table->string('shiprocket_awb', 100)->nullable()->after('shiprocket_shipment_id');
            }
            if (! Schema::hasColumn('orders', 'shiprocket_courier')) {
                $table->string('shiprocket_courier')->nullable()->after('shiprocket_awb');
            }
            if (! Schema::hasColumn('orders', 'shiprocket_pushed_at')) {
                $table->timestamp('shiprocket_pushed_at')->nullable()->after('shiprocket_courier');
            }
            if (! Schema::hasColumn('orders', 'razorpay_order_id')) {
                $table->string('razorpay_order_id')->nullable()->after('shiprocket_pushed_at');
            }
            if (! Schema::hasColumn('orders', 'razorpay_payment_id')) {
                $table->string('razorpay_payment_id')->nullable()->after('razorpay_order_id');
            }
        });

        // Mirror the partial unique index that the earlier index migration skips when the
        // column does not yet exist. Idempotent, so it is safe where it already ran.
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS orders_shiprocket_order_id_unique ON orders (shiprocket_order_id) WHERE shiprocket_order_id IS NOT NULL');
        } else {
            // MySQL: plain UNIQUE index already allows multiple NULLs. No "IF NOT EXISTS".
            $exists = collect(DB::select("SHOW INDEX FROM orders WHERE Key_name = 'orders_shiprocket_order_id_unique'"))->isNotEmpty();
            if (! $exists) {
                DB::statement('CREATE UNIQUE INDEX orders_shiprocket_order_id_unique ON orders (shiprocket_order_id)');
            }
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS orders_shiprocket_order_id_unique');
        } else {
            $exists = collect(DB::select("SHOW INDEX FROM orders WHERE Key_name = 'orders_shiprocket_order_id_unique'"))->isNotEmpty();
            if ($exists) {
                DB::statement('ALTER TABLE orders DROP INDEX orders_shiprocket_order_id_unique');
            }
        }

        Schema::table('orders', function (Blueprint $table) {
            foreach ([
                'shiprocket_order_id',
                'shiprocket_shipment_id',
                'shiprocket_awb',
                'shiprocket_courier',
                'shiprocket_pushed_at',
                'razorpay_order_id',
                'razorpay_payment_id',
            ] as $col) {
                if (Schema::hasColumn('orders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
