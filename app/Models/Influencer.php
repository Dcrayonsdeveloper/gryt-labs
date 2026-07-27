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
     * Eloquent query of every order placed using this influencer's coupon.
     * Returns an always-empty query if no matching coupon exists yet.
     */
    public function ordersQuery(): Builder
    {
        $couponId = Coupon::where('code', $this->coupon_code)->value('id');

        return Order::query()->when(
            $couponId,
            fn ($q) => $q->where('coupon_id', $couponId),
            fn ($q) => $q->whereRaw('1 = 0')
        );
    }
}
