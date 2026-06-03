<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'carrier')) {
                $table->string('carrier')->nullable()->after('shipped_at');
            }
            if (!Schema::hasColumn('orders', 'tracking_number')) {
                $table->string('tracking_number')->nullable()->after('carrier');
            }
            if (!Schema::hasColumn('orders', 'affiliate_id')) {
                $table->unsignedBigInteger('affiliate_id')->nullable()->after('coupon_id');
            }
            if (!Schema::hasColumn('orders', 'affiliate_referral_code')) {
                $table->string('affiliate_referral_code')->nullable()->after('affiliate_id');
            }
            if (!Schema::hasColumn('orders', 'invoice_number')) {
                $table->string('invoice_number')->nullable()->after('order_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['carrier', 'tracking_number', 'affiliate_id', 'affiliate_referral_code', 'invoice_number']);
        });
    }
};
