<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DraftOrder extends Model
{
    protected $fillable = [
        'admin_id',
        'customer_id',
        'customer_email',
        'customer_name',
        'customer_phone',
        'items',
        'subtotal',
        'discount',
        'shipping_cost',
        'tax',
        'total',
        'notes',
        'status',
        'sent_at',
        'completed_at',
        'payment_link',
        'order_id',
    ];

    protected function casts(): array
    {
        return [
            'items' => 'array',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'tax' => 'decimal:2',
            'total' => 'decimal:2',
            'sent_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($draft) {
            if (empty($draft->status)) {
                $draft->status = 'draft';
            }
        });
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Recalculate totals from items array.
     */
    public function recalculateTotals(): void
    {
        $items = $this->items ?? [];
        $subtotal = 0;

        foreach ($items as $item) {
            $subtotal += ($item['price'] ?? 0) * ($item['quantity'] ?? 1);
        }

        $this->subtotal = $subtotal;
        $this->total = max(0, $subtotal - ($this->discount ?? 0) + ($this->shipping_cost ?? 0) + ($this->tax ?? 0));
    }
}
