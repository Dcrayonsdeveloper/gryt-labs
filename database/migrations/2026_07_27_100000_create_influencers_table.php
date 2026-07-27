<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('influencers')) {
            return;
        }

        Schema::create('influencers', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('username')->unique();
            $table->string('password');
            $table->string('email')->nullable();
            $table->string('mobile', 20)->nullable();
            $table->string('coupon_code')->unique();
            // % discount the coupon gives customers (used to auto-create the linked coupon)
            $table->decimal('coupon_discount', 5, 2)->default(10);
            $table->string('instagram')->nullable();
            $table->string('youtube')->nullable();
            // What the influencer earns (informational)
            $table->decimal('commission_percentage', 5, 2)->nullable();
            $table->text('notes')->nullable();
            // VARCHAR, not ENUM — deliberately, to avoid the MySQL enum-truncation issue.
            $table->string('status', 20)->default('active'); // active | inactive
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->index('coupon_code');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('influencers');
    }
};
