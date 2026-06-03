<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderReturn extends Model
{
    protected $table = 'returns';

    protected $fillable = [
        'return_number',
        'order_id',
        'user_id',
        'type',
        'status',
        'reason',
        'reason_category',
        'description',
        'images',
        'refund_amount',
        'refund_method',
        'exchange_order_id',
        'processed_by',
        'pickup_partner_id',
        'approved_at',
        'pickup_scheduled_at',
        'picked_up_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'images' => 'array',
            'refund_amount' => 'decimal:2',
            'approved_at' => 'datetime',
            'pickup_scheduled_at' => 'datetime',
            'picked_up_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($return) {
            if (empty($return->return_number)) {
                $return->return_number = 'RET-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReturnItem::class, 'return_id');
    }

    public function creditNote(): HasOne
    {
        return $this->hasOne(CreditNote::class, 'return_id');
    }

    public function exchangeOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'exchange_order_id');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function pickupPartner(): BelongsTo
    {
        return $this->belongsTo(DeliveryPartner::class, 'pickup_partner_id');
    }

    public function approve(): void
    {
        $this->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);
    }

    public function reject(string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'description' => $reason,
        ]);
    }

    /**
     * Restore stock only for items in this return request (partial return safe).
     */
    public function restoreReturnedStock(): void
    {
        $this->loadMissing('items.orderItem');

        foreach ($this->items as $returnItem) {
            $orderItem = $returnItem->orderItem;
            if (!$orderItem) {
                continue;
            }

            if ($orderItem->variant_id) {
                \Illuminate\Support\Facades\DB::table('product_variants')
                    ->where('id', $orderItem->variant_id)
                    ->increment('stock_quantity', $returnItem->quantity);
            } else {
                \Illuminate\Support\Facades\DB::table('products')
                    ->where('id', $orderItem->product_id)
                    ->increment('stock_quantity', $returnItem->quantity);
            }

            // Auto-update stock_status back to in_stock
            \Illuminate\Support\Facades\DB::table('products')
                ->where('id', $orderItem->product_id)
                ->where('stock_status', 'out_of_stock')
                ->update(['stock_status' => 'in_stock']);
        }
    }
}
