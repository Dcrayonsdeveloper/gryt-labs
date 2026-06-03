<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('abandoned_checkouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id')->nullable()->index();
            $table->string('email')->nullable();
            $table->string('name')->nullable();
            $table->string('phone', 20)->nullable()->index();
            $table->decimal('cart_total', 10, 2)->default(0);
            $table->integer('items_count')->default(0);
            $table->string('step')->default('checkout');
            $table->json('cart_snapshot')->nullable();
            $table->boolean('recovered')->default(false);
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abandoned_checkouts');
    }
};
