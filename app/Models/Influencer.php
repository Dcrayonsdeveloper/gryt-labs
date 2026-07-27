<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * An influencer authenticates on the dedicated `influencer` guard (username + password).
 * Orders are attributed to an influencer through their coupon: an order stores
 * `coupon_id`, and the influencer's `coupon_code` matches that coupon's `code`.
 */
class Influencer extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'full_name',
        'username',
        'password',
        'email',
        'mobile',
        'coupon_code',
        'coupon_discount',
        'instagram',
        'youtube',
        'commission_percentage',
        'notes',
        'status',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password'              => 'hashed', // auto-bcrypt on set
            'coupon_discount'       => 'decimal:2',
            'commission_percentage' => 'decimal:2',
            'last_login_at'         => 'datetime',
        ];
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /** The discount coupon linked to this influencer (matched by code). */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class, 'coupon_code', 'code');
    }

    /**
     * Eloquent query of every order placed using this influencer's coupon,
     * from BOTH sources:
     *   - platform coupons applied on our checkout  → orders.coupon_id → coupons.code
     *   - Shiprocket-checkout coupons               → orders.metadata.sr_pricing.coupon_codes[]
     * (the latter is the same field the order Payment Summary reads.)
     */
    public function ordersQuery(): Builder
    {
        $code     = $this->coupon_code;
        $couponId = Coupon::where('code', $code)->value('id');

        return Order::query()->where(function ($q) use ($code, $couponId) {
            if ($couponId) {
                $q->orWhere('coupon_id', $couponId);
            }
            $q->orWhereJsonContains('metadata->sr_pricing->coupon_codes', $code);
        });
    }
}
