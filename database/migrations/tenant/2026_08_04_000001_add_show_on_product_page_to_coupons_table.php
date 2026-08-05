<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('coupons', 'show_on_product_page')) {
            return;
        }

        Schema::table('coupons', function (Blueprint $table) {
            // Visibility on the product page's "Available Offers" section only.
            // A hidden coupon still applies normally when entered at cart/checkout.
            $table->boolean('show_on_product_page')->default(true)->after('is_stackable');
        });
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn('show_on_product_page');
        });
    }
};
