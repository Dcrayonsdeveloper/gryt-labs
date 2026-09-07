<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Manual "Sale" flag: which products appear on the /sale page.
 *
 * Deliberately NOT derived from price < mrp — almost every product carries a
 * discount, so an automatic rule would put the whole catalogue on sale. This
 * lets the admin curate the sale from Products -> Sale.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_on_sale')->default(false)->after('is_new_arrival');
            $table->index(['is_on_sale', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['is_on_sale', 'is_active']);
            $table->dropColumn('is_on_sale');
        });
    }
};
