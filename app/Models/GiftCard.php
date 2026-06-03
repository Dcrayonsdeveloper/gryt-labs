<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class GiftCard extends Model
{
    protected $fillable = [
        'code',
        'initial_balance',
        'current_balance',
        'currency',
        'purchaser_email',
        'recipient_email',
        'recipient_name',
        'message',
        'status',
        'purchased_at',
        'expires_at',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'initial_balance' => 'decimal:2',
            'current_balance' => 'decimal:2',
            'purchased_at' => 'datetime',
            'expires_at' => 'datetime',
            'last_used_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (GiftCard $giftCard) {
            if (empty($giftCard->code)) {
                $giftCard->code = self::generateUniqueCode();
            }
            if (is_null($giftCard->current_balance)) {
                $giftCard->current_balance = $giftCard->initial_balance;
            }
            if (empty($giftCard->currency)) {
                $giftCard->currency = 'INR';
            }
            if (empty($giftCard->status)) {
                $giftCard->status = 'active';
            }
            if (empty($giftCard->purchased_at)) {
                $giftCard->purchased_at = now();
            }
        });
    }

    /**
     * Generate a unique 16-character alphanumeric code.
     */
    public static function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(16));
        } while (self::where('code', $code)->exists());

        return $code;
    }

    // ── Scopes ──────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeValid(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->where('current_balance', '>', 0)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    // ── Relations ───────────────────────────────────────────

    public function usages(): HasMany
    {
        return $this->hasMany(GiftCardUsage::class);
    }

    // ── Methods ─────────────────────────────────────────────

    /**
     * Check if this gift card can be redeemed.
     */
    public function isValid(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->current_balance <= 0) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Deduct an amount from the gift card balance.
     */
    public function deduct(float $amount): void
    {
        if ($amount > $this->current_balance) {
            throw new \InvalidArgumentException('Insufficient gift card balance');
        }

        $this->current_balance -= $amount;
        $this->last_used_at = now();

        if ($this->current_balance <= 0) {
            $this->current_balance = 0;
            $this->status = 'depleted';
        }

        $this->save();
    }

    /**
     * Credit an amount back to the gift card (refund path).
     * Caps at the original initial_balance so a card can't grow beyond its face value.
     * Re-activates a 'depleted' card if the new balance is > 0.
     */
    public function credit(float $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        $newBalance = (float) $this->current_balance + $amount;
        $this->current_balance = min($newBalance, (float) $this->initial_balance);

        if ($this->status === 'depleted' && $this->current_balance > 0) {
            $this->status = 'active';
        }

        $this->save();
    }
}
