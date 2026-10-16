<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phone numbers captured by the storefront "exclusive offer" popup.
 *
 * Kept separate from `leads` on purpose: that table is the chatbot's
 * (platform / platform_id / bot_state), while these rows are consent-backed
 * marketing opt-ins that need verification and coupon-delivery state.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('offer_leads')) {
            return;
        }

        Schema::create('offer_leads', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->nullable();
            // Stored as the 10-digit national number, matching users.phone, so the
            // two can be matched without normalising on every lookup.
            $table->string('phone', 15)->unique();
            $table->string('country_code', 5)->default('91');
            $table->unsignedBigInteger('user_id')->nullable();

            // Consent is the whole point of the checkbox — store the wording that
            // was actually on screen, not just the boolean, so a later copy change
            // doesn't rewrite what past customers agreed to.
            $table->boolean('consent')->default(false);
            $table->string('consent_text', 255)->nullable();
            $table->timestamp('consent_at')->nullable();

            $table->timestamp('verified_at')->nullable();
            $table->timestamp('otp_sent_at')->nullable();
            $table->unsignedInteger('otp_send_count')->default(0);

            $table->string('coupon_code', 50)->nullable();
            $table->timestamp('coupon_sent_at')->nullable();
            // VARCHAR, not ENUM — MySQL enum truncation has bitten this schema before.
            $table->string('coupon_channel', 20)->nullable();   // whatsapp | onscreen
            $table->string('whatsapp_status', 20)->nullable();  // sent | failed | skipped

            $table->string('source', 50)->default('offer_popup');
            $table->string('page_url', 255)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamps();

            $table->index('verified_at');
            $table->index('created_at');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_leads');
    }
};
