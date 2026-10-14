<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Widen abandoned_checkouts.phone so the encrypted value actually fits.
 *
 * AbandonedCheckout encrypts phone on write (setPhoneAttribute -> Crypt::encryptString),
 * which produces a ~230 character payload, but the column was created as string(phone, 20).
 * MySQL/MariaDB is not in strict mode here, so every write was silently TRUNCATED to 20
 * characters — the ciphertext was cut mid-payload and became permanently undecryptable
 * ("The payload is invalid"). Every phone number captured since the table was created in
 * March 2026 was destroyed on write, which is why WhatsApp cart recovery never had a
 * single reachable number.
 *
 * email escaped this because its column is already varchar(255) and its ~228 char payload
 * fits. 255 is enough headroom for Crypt payloads of a phone number.
 *
 * Existing rows cannot be repaired — truncation is lossy and the plaintext is gone.
 * This only stops further data loss.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('abandoned_checkouts', function (Blueprint $table) {
            $table->string('phone', 255)->nullable()->change();
        });
    }

    public function down(): void
    {
        // Deliberately not narrowing back to 20: doing so would truncate and destroy
        // every correctly-encrypted phone written after this migration ran.
    }
};
