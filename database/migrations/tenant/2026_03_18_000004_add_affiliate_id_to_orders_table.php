<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'affiliate_id')) {
                $table->foreignId('affiliate_id')->nullable()->after('coupon_id')->constrained('affiliates')->nullOnDelete();
            }
            if (!Schema::hasColumn('orders', 'affiliate_referral_code')) {
                $table->string('affiliate_referral_code', 15)->nullable()->after('affiliate_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'affiliate_id')) {
                $table->dropForeign(['affiliate_id']);
                $table->dropColumn('affiliate_id');
            }
            if (Schema::hasColumn('orders', 'affiliate_referral_code')) {
                $table->dropColumn('affiliate_referral_code');
            }
        });
    }
};
