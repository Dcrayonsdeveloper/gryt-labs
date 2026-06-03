<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('checkout_token', 64)->nullable()->unique()->after('order_number');
        });

        // Generate tokens for existing orders
        \App\Models\Order::whereNull('checkout_token')->each(function ($order) {
            $order->update(['checkout_token' => Str::random(32)]);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('checkout_token');
        });
    }
};
