<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_visits', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 100)->index();
            $table->string('source_type', 20)->index(); // ad, organic, social, direct, referral
            $table->string('utm_source', 100)->nullable();
            $table->string('utm_medium', 100)->nullable();
            $table->string('utm_campaign', 150)->nullable();
            $table->string('utm_content', 150)->nullable();
            $table->string('utm_term', 150)->nullable();
            $table->string('referrer', 500)->nullable();
            $table->string('referrer_domain', 100)->nullable();
            $table->string('landing_page', 500)->nullable();
            $table->string('device', 10)->nullable(); // mobile, tablet, desktop
            $table->string('ip_address', 45)->nullable();
            $table->string('country', 2)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_visits');
    }
};
