<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_sections', function (Blueprint $table) {
            $table->id();
            $table->string('page')->default('home'); // home, product, category, etc.
            $table->string('type'); // hero_banner, product_carousel, category_grid, etc.
            $table->string('name'); // Display name in admin
            $table->integer('position')->default(0);
            $table->json('settings')->nullable(); // Section-specific config
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_sections');
    }
};
