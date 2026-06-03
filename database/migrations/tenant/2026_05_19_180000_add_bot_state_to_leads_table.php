<?php

/**
 * Add bot_state to leads — per-customer state machine for the WhatsApp
 * button-driven flow (Nia v2: structured flow first, free-form Nia as Q&A
 * fallback).
 *
 * - bot_state: nullable string. Null = no active flow (Nia handles all text).
 *   When set, BotFlowService knows which step the customer is on and what
 *   to expect next (e.g. CASHBACK_AWAITING_ORDER_ID, COMPLAINT_AWAITING_DETAILS).
 *
 * - bot_state_data: nullable JSON. Per-flow scratch space — captured order
 *   IDs, UPI, partial complaint details — without proliferating columns.
 *
 * - bot_state_updated_at: nullable timestamp. Lets us expire stale flows
 *   (customer abandoned the cashback flow mid-way → after 24h, treat as
 *   INITIAL again on next inbound).
 *
 * Safety: hasColumn() guards so this migration is idempotent and a no-op
 * on tenants where it has already run.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('leads')) {
            return;
        }

        Schema::table('leads', function (Blueprint $table) {
            if (!Schema::hasColumn('leads', 'bot_state')) {
                $table->string('bot_state', 64)->nullable()->index();
            }
            if (!Schema::hasColumn('leads', 'bot_state_data')) {
                $table->json('bot_state_data')->nullable();
            }
            if (!Schema::hasColumn('leads', 'bot_state_updated_at')) {
                $table->timestamp('bot_state_updated_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('leads')) {
            return;
        }

        Schema::table('leads', function (Blueprint $table) {
            foreach (['bot_state_updated_at', 'bot_state_data', 'bot_state'] as $col) {
                if (Schema::hasColumn('leads', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
