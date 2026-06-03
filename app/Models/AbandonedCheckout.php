<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AbandonedCheckout extends Model
{
    protected $fillable = [
        'cart_id', 'user_id', 'session_id', 'email', 'name', 'phone', 'phone_hash',
        'cart_total', 'items_count', 'step', 'cart_snapshot', 'recovered',
        'order_id', 'recovered_at', 'notified_at', 'reminder_count',
        'source', 'ip_address', 'user_agent', 'metadata',
        'shiprocket_cart_token', 'shiprocket_order_id',
    ];

    protected function casts(): array
    {
        return [
            'cart_snapshot' => 'array',
            'metadata' => 'array',
            'recovered' => 'boolean',
            'notified_at' => 'datetime',
            'recovered_at' => 'datetime',
        ];
    }

    /**
     * Decrypt email on read, falling back to raw value for pre-encryption rows.
     */
    public function getEmailAttribute($value)
    {
        if ($value === null) {
            return null;
        }
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return $value;
        }
    }

    /**
     * Encrypt email on write.
     */
    public function setEmailAttribute($value)
    {
        $this->attributes['email'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Decrypt phone on read, falling back to raw value for pre-encryption rows.
     */
    public function getPhoneAttribute($value)
    {
        if ($value === null) {
            return null;
        }
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return $value;
        }
    }

    /**
     * Encrypt phone on write and store a searchable hash for lookups.
     */
    public function setPhoneAttribute($value)
    {
        $this->attributes['phone'] = $value ? Crypt::encryptString($value) : null;
        $this->attributes['phone_hash'] = $value ? hash('sha256', preg_replace('/\D/', '', $value)) : null;
    }

    /**
     * Find abandoned checkout by plaintext phone using the phone_hash column.
     */
    public static function findByPhone(string $phone): ?static
    {
        $hash = hash('sha256', preg_replace('/\D/', '', $phone));
        return static::where('phone_hash', $hash)->latest()->first();
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Find an abandoned checkout by ANY Shiprocket-issued ID (token order_id, webhook cart_id, callback oid).
     */
    public static function findByShiprocketId(string $shiprocketId): ?static
    {
        $mapping = DB::table('shiprocket_checkout_ids')
            ->where('shiprocket_id', $shiprocketId)
            ->first();

        return $mapping ? static::find($mapping->abandoned_checkout_id) : null;
    }

    /**
     * Register a Shiprocket ID in the bridge table, linking it to this AC.
     * Silently ignores duplicates (unique constraint on shiprocket_id).
     */
    public function registerShiprocketId(string $shiprocketId, string $type = 'unknown'): void
    {
        if (empty($shiprocketId)) return;

        try {
            DB::table('shiprocket_checkout_ids')->insertOrIgnore([
                'shiprocket_id'         => $shiprocketId,
                'abandoned_checkout_id' => $this->id,
                'id_type'               => $type,
                'created_at'            => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('ShiprocketCheckoutIds: bridge insert failed', [
                'shiprocket_id'  => $shiprocketId,
                'ac_id'          => $this->id,
                'error'          => $e->getMessage(),
            ]);
        }
    }
}
