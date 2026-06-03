<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('abandoned_checkouts', function (Blueprint $table) {
            $table->foreignId('order_id')->nullable()->after('recovered')->constrained('orders')->nullOnDelete();
            $table->timestamp('recovered_at')->nullable()->after('order_id');
        });
    }

    public function down(): void
    {
        Schema::table('abandoned_checkouts', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->dropColumn(['order_id', 'recovered_at']);
        });
    }
};
