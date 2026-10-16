<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfferLead extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'country_code',
        'user_id',
        'consent',
        'consent_text',
        'consent_at',
        'verified_at',
        'otp_sent_at',
        'otp_send_count',
        'coupon_code',
        'coupon_sent_at',
        'coupon_channel',
        'whatsapp_status',
        'source',
        'page_url',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'consent'        => 'boolean',
            'consent_at'     => 'datetime',
            'verified_at'    => 'datetime',
            'otp_sent_at'    => 'datetime',
            'coupon_sent_at' => 'datetime',
            'otp_send_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->whereNotNull('verified_at');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('verified_at');
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    /**
     * Reduce any input to the 10-digit national number we store.
     *
     * Shoppers type +91, 0091, a leading 0, spaces and dashes; all of those must
     * collapse to the same row or the unique index stops catching repeat claims.
     */
    public static function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        return substr($digits, -10);
    }

    /**
     * The number in the form WhatsApp expects (country code + national number).
     */
    public function msisdn(): string
    {
        return ($this->country_code ?: '91') . $this->phone;
    }
}
