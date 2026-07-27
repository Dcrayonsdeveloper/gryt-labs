<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Convert the order status ENUM columns to VARCHAR (MySQL only).
 *
 * create_orders_table defined orders.status / orders.payment_status and
 * order_status_history.status as MySQL ENUMs whose value lists never grew to match
 * the app: Order::ALLOWED_TRANSITIONS uses 'packed' and 'out_for_delivery' (absent
 * from the status enum) and payments use 'partial' (absent from the payment enum).
 * On non-strict MySQL, writing a value outside an ENUM is silently truncated to '',
 * which blanked the status and broke admin "Update Status" (2026-07 incident).
 *
 * PostgreSQL already stores these as varchar/text — values are enforced at the
 * application level — so this is a MySQL-only correction and a no-op on PG. It is
 * also safe/idempotent on the prod DB that was already converted by hand.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return; // Postgres: columns are already varchar/text
        }

        DB::statement("ALTER TABLE orders MODIFY status VARCHAR(32) NOT NULL DEFAULT 'pending'");
        DB::statement("ALTER TABLE orders MODIFY payment_status VARCHAR(32) NOT NULL DEFAULT 'pending'");

        if (Schema::hasTable('order_status_history')) {
            DB::statement("ALTER TABLE order_status_history MODIFY status VARCHAR(32) NOT NULL");
        }

        // Repair any rows the old ENUM had blanked, using the furthest fulfillment step reached.
        DB::statement("UPDATE orders SET status = 'delivered'        WHERE status = '' AND delivered_at IS NOT NULL");
        DB::statement("UPDATE orders SET status = 'out_for_delivery' WHERE status = '' AND out_for_delivery_at IS NOT NULL");
        DB::statement("UPDATE orders SET status = 'shipped'          WHERE status = '' AND shipped_at IS NOT NULL");
        DB::statement("UPDATE orders SET status = 'packed'           WHERE status = '' AND packed_at IS NOT NULL");
        DB::statement("UPDATE orders SET status = 'confirmed'        WHERE status = ''");
    }

    public function down(): void
    {
        // Intentionally irreversible: reverting to the incomplete ENUM would
        // re-truncate valid statuses to ''. No-op.
    }
};
