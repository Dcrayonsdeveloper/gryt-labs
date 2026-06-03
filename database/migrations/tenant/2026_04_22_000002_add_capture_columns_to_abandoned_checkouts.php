<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Track A from the 2026-04-22 P0 PIR: capture customer context BEFORE handing off
 * to any external checkout (Shiprocket, etc.) so we never lose another anupam-style
 * orphan order. All columns are nullable + idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('abandoned_checkouts', function (Blueprint $table) {
            if (! Schema::hasColumn('abandoned_checkouts', 'source')) {
                $table->string('source', 50)->nullable()->index()->after('step');
            }
            if (! Schema::hasColumn('abandoned_checkouts', 'ip_address')) {
                $table->string('ip_address', 45)->nullable()->after('source');
            }
            if (! Schema::hasColumn('abandoned_checkouts', 'user_agent')) {
                $table->text('user_agent')->nullable()->after('ip_address');
            }
            if (! Schema::hasColumn('abandoned_checkouts', 'metadata')) {
                $table->jsonb('metadata')->nullable()->after('user_agent');
            }
            if (! Schema::hasColumn('abandoned_checkouts', 'shiprocket_cart_token')) {
                $table->string('shiprocket_cart_token', 255)->nullable()->index()->after('metadata');
            }
            if (! Schema::hasColumn('abandoned_checkouts', 'shiprocket_order_id')) {
                $table->string('shiprocket_order_id', 100)->nullable()->index()->after('shiprocket_cart_token');
            }
        });
    }

    public function down(): void
    {
        Schema::table('abandoned_checkouts', function (Blueprint $table) {
            $cols = ['shiprocket_order_id', 'shiprocket_cart_token', 'metadata', 'user_agent', 'ip_address', 'source'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('abandoned_checkouts', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
