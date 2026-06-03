<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Immutable event log for every Shiprocket Checkout webhook call.
 *
 * Design principles:
 *  - INSERT-ONLY: never update a row (prevents race conditions from duplicate INIT calls)
 *  - FULL PAYLOAD: raw_payload JSON captures everything Shiprocket sends
 *  - DEDUPLICATION: payload_hash detects identical retries (same cart_id + stage + body)
 *  - RISK SIGNALS: rto_prediction + payment mode + phone_verified stored per event
 *  - TIMELINE: order/ac linkage lets the admin show a Shopify-style activity feed
 *
 * Shiprocket fires 4-5 webhooks per checkout (all share the same cart_id):
 *   INIT (×2-3) → PAYMENT_INITIATED → ORDER_PLACED → Payment Complete
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shiprocket_checkout_events', function (Blueprint $table) {
            $table->id();

            // ─── Identity ──────────────────────────────────────────────────────
            // cart_id is Shiprocket's identifier for the checkout session.
            // It differs from the token order_id returned by generateToken().
            $table->string('cart_id', 100)->index();
            $table->string('stage', 100);                        // INIT, PAYMENT_INITIATED, ORDER_PLACED, Payment Complete, Abandoned Cart
            $table->string('source_name', 50)->nullable();       // fastrr, shiprocket, etc.
            $table->char('payload_hash', 32)->index();           // MD5(raw JSON) — dedup key

            // ─── Deduplication ────────────────────────────────────────────────
            // Shiprocket fires INIT 2-3× with identical payloads. We store all
            // of them but skip AC processing for exact duplicates.
            $table->boolean('is_duplicate')->default(false)->index();

            // ─── Customer ─────────────────────────────────────────────────────
            // Stored in plain text here (event log, not PII store).
            // Sensitive data in abandoned_checkouts is encrypted.
            $table->string('phone', 30)->nullable()->index();
            $table->boolean('phone_verified')->default(false);
            $table->string('email', 255)->nullable();
            $table->string('full_name', 255)->nullable();        // billing_address.name
            $table->string('first_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();

            // ─── Address ──────────────────────────────────────────────────────
            $table->text('address_line_1')->nullable();
            $table->text('address_line_2')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();            // often missing from Fastrr
            $table->string('pincode', 20)->nullable();
            $table->string('country', 100)->nullable()->default('India');
            $table->char('address_hash', 32)->nullable()->index(); // MD5 of address — detect mid-checkout address changes

            // ─── Cart / Order ─────────────────────────────────────────────────
            $table->decimal('total_price', 10, 2)->nullable();
            $table->decimal('shipping_price', 10, 2)->default(0);
            $table->decimal('total_discount', 10, 2)->default(0);
            $table->decimal('net_payable', 10, 2)->nullable();   // total_price - total_discount
            $table->decimal('tax', 10, 2)->default(0);
            $table->unsignedSmallInteger('item_count')->default(0);
            $table->jsonb('items')->nullable();                  // full items array with product_id, price, qty

            // ─── Payment ──────────────────────────────────────────────────────
            // Present only in ORDER_PLACED / Payment Complete stages.
            $table->string('payment_mode', 50)->nullable();      // UPI, COD, Card, NetBanking, Wallet
            $table->string('payment_gateway', 50)->nullable();   // Cashfree, Razorpay, Shiprocket
            $table->decimal('payment_amount', 10, 2)->nullable(); // advance paid (partial COD) or full amount
            $table->string('transaction_id', 255)->nullable()->index();

            // ─── Risk Signals ─────────────────────────────────────────────────
            // rto_prediction: Shiprocket's own RTO ML signal (low/medium/high).
            // risk_analysis: our computed composite score + signal breakdown.
            $table->string('rto_prediction', 20)->nullable()->index(); // low | medium | high | null
            $table->jsonb('risk_analysis')->nullable();
            // risk_analysis schema:
            // {
            //   "score": 0-100,
            //   "level": "low|medium|high",
            //   "signals": {
            //     "rto_prediction_high": bool,
            //     "rto_prediction_medium": bool,
            //     "cod_payment": bool,
            //     "phone_unverified": bool,
            //     "address_changed_mid_checkout": bool,
            //     "new_customer": bool,
            //     "high_value_order": bool
            //   }
            // }

            // ─── Meta ─────────────────────────────────────────────────────────
            $table->string('ip_address', 45)->nullable()->index();

            // FK to abandoned_checkouts (set after AC match/create in WebhookController)
            $table->unsignedBigInteger('abandoned_checkout_id')->nullable()->index();
            // FK to orders (set when order is created / found via syncOrder)
            $table->unsignedBigInteger('order_id')->nullable()->index();

            // Full raw webhook body — always stored, never trimmed
            $table->jsonb('raw_payload');

            // When the webhook arrived at our server (≠ created_at which is DB insert time)
            $table->timestampTz('received_at');
            // When we finished processing (AC update / order link) — null = unprocessed / duplicate
            $table->timestampTz('processed_at')->nullable();

            $table->timestamps();

            // Composite index for timeline queries: all events for a cart session
            $table->index(['cart_id', 'received_at']);
            // Composite index for deduplication check
            $table->index(['cart_id', 'stage', 'payload_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shiprocket_checkout_events');
    }
};
