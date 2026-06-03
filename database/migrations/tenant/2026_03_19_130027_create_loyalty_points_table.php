<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('points'); // positive = earned, negative = redeemed
            $table->string('type'); // earned, redeemed, expired, bonus, refunded
            $table->string('source'); // order, review, signup, referral, birthday, admin
            $table->morphs('reference'); // order_id, review_id, etc.
            $table->string('description');
            $table->integer('balance_after')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type']);
            $table->index('expires_at');
        });

        // Add points balance to users table for fast lookup
        if (!Schema::hasColumn('users', 'loyalty_points_balance')) {
            Schema::table('users', function (Blueprint $table) {
                $table->integer('loyalty_points_balance')->default(0)->after('role');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_points');
        if (Schema::hasColumn('users', 'loyalty_points_balance')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('loyalty_points_balance');
            });
        }
    }
};
