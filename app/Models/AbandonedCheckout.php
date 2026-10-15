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

    /**
     * Record (or refresh) the abandoned-cart row for a cart.
     *
     * Previously this only happened when someone reached the checkout page, so a
     * shopper who added to cart and left was never captured and never reminded -
     * every row in the table read "Viewed checkout". Carts are now captured the
     * moment something is added, and refreshed on every change, so the snapshot
     * and total always match what is actually in the cart.
     *
     * Contact details are copied from the signed-in user. Adding to cart requires
     * an account, so phone and email are always present, which is what makes the
     * reminder queries (they require phone_hash or email) able to match at all.
     *
     * phone_hash is a plain sha256 of the digits, matching how the Shiprocket
     * webhook writes it, so both paths resolve to the same row.
     */
    public static function captureFromCart(\App\Models\Cart $cart, string $step = 'cart'): ?self
    {
        // load(), not loadMissing(): callers reach here straight after changing the
        // cart, and loadMissing leaves an already-loaded relation untouched, so the
        // snapshot and total would be written from pre-change quantities.
        $cart->load('items.product');

        if ($cart->items->isEmpty()) {
            return null;
        }

        $user = auth()->user();
        $phone = $user?->phone ? preg_replace('/\D/', '', (string) $user->phone) : null;
        if ($phone && strlen($phone) === 12 && str_starts_with($phone, '91')) {
            $phone = substr($phone, 2);
        }

        $attributes = [
            'user_id'       => $user?->id,
            'session_id'    => session()->getId(),
            // Distinguishes carts captured on our own site from the ones Shiprocket
            // reports ('shiprocket_checkout'), which is what the admin tabs split on.
            'source'        => 'cart',
            'cart_total'    => $cart->subtotal - $cart->discount,
            'items_count'   => $cart->items->count(),
            'step'          => $step,
            'cart_snapshot' => $cart->items->map(fn ($item) => [
                'product_id'   => $item->product_id,
                'product_name' => $item->product?->name ?? 'Item',
                'quantity'     => $item->quantity,
                'price'        => $item->price,
            ])->toArray(),
        ];

        if ($user) {
            $attributes['name']  = trim($user->first_name . ' ' . $user->last_name) ?: null;
            $attributes['email'] = $user->email;
            if ($phone) {
                $attributes['phone']      = $phone;
                $attributes['phone_hash'] = hash('sha256', $phone);
            }
        }

        return static::updateOrCreate(['cart_id' => $cart->id], $attributes);
    }

    /**
     * Drop the abandoned-cart row once the cart is empty again.
     *
     * Without this, clearing the cart or removing the last item would still leave
     * a row that keeps generating reminders for products the shopper no longer has.
     * Recovered rows are left alone - they are the record of a successful purchase.
     */
    public static function releaseForCart(int $cartId): void
    {
        static::where('cart_id', $cartId)
            ->where(fn ($q) => $q->where('recovered', false)->orWhereNull('recovered'))
            ->delete();
    }

    /**
     * Name of the first product in the cart, for recovery messages.
     *
     * The cart_recovery WhatsApp template renders "you left {{2}} (Rs {{3}})", so it
     * needs a product name. SendAbandonedCartWhatsApp already digs this out of
     * cart_snapshot inline; this keeps the admin reminder path using the same source
     * and the same 'your item' fallback for carts with no usable snapshot.
     */
    public function firstProductName(): string
    {
        $items = is_array($this->cart_snapshot) ? $this->cart_snapshot : [];
        $first = $items[0] ?? null;

        if (!is_array($first)) {
            return 'your item';
        }

        $name = trim((string) ($first['product_name'] ?? ''));

        return $name !== '' ? $name : 'your item';
    }
}
