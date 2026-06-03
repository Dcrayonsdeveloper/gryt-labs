<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('gift_card_usages')) {
            return;
        }
        Schema::table('gift_card_usages', function (Blueprint $table) {
            $table->timestamp('refunded_at')->nullable()->after('amount');
            $table->decimal('refunded_amount', 10, 2)->default(0)->after('refunded_at');
        });
    }

    public function down(): void
    {
        Schema::table('gift_card_usages', function (Blueprint $table) {
            $table->dropColumn(['refunded_at', 'refunded_amount']);
        });
    }
};
