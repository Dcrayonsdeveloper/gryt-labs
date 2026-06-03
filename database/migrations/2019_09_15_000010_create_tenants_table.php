<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTenantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->string('id')->primary(); // e.g. 'jikra', 'store2'

            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->string('plan')->default('standard'); // free, standard, premium, enterprise

            $table->timestamps();
            $table->json('data')->nullable(); // All config: razorpay keys, branding, tokens etc.
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
}
