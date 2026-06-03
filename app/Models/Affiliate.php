<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Affiliate extends Model
{
    protected $fillable = [
        'user_id',
        'referral_code',
        'channel',
        'channel_url',
        'bio',
        'commission_rate',
        'available_balance',
        'pending_balance',
        'total_earned',
        'total_orders',
        'total_clicks',
        'status',
        'payout_method',
        'bank_details',
        'upi_id',
        'settings',
        'approved_at',
        'rejection_reason',
        'suspension_reason',
        'suspended_at',
    ];

    protected function casts(): array
    {
        return [
            'commission_rate' => 'decimal:2',
            'available_balance' => 'decimal:2',
            'pending_balance' => 'decimal:2',
            'total_earned' => 'decimal:2',
            'bank_details' => 'array',
            'settings' => 'array',
            'approved_at' => 'datetime',
            'suspended_at' => 'datetime',
        ];
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(AffiliateCommission::class);
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(AffiliateRedemption::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    // Status helpers
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    // Referral code generation
    public static function generateReferralCode(): string
    {
        $prefix = config('affiliate.referral_code_prefix', 'AFF');
        $length = config('affiliate.referral_code_length', 8);

        do {
            $code = $prefix . '-' . strtoupper(Str::random($length));
        } while (static::where('referral_code', $code)->exists());

        return $code;
    }

    public function getReferralUrl(): string
    {
        return url('/?ref=' . $this->referral_code);
    }
}
