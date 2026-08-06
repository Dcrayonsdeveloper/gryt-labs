<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $hidden = [
        'checkout_token',
        'ip_address',
        'user_agent',
        'admin_notes',
        'payment_collected_by',
        // Internal scratchpad: Shiprocket ids, raw webhook payloads, CAPI receipts
        // and Facebook error bodies. The customer order API returns the whole model
        // as JSON (Api/V1/Order/OrderController), so keep it out of responses.
        // Server-side property access ($order->metadata) and Blade are unaffected.
        'metadata',
    ];

    protected $fillable = [
        'order_number',
        'checkout_token',
        'user_id',
        'seller_id',
        'delivery_partner_id',
        'shipping_address_id',
        'billing_address_id',
        'coupon_id',
        'affiliate_id',
        'affiliate_referral_code',
        'status',
        'payment_status',
        'subtotal',
        'discount',
        'tax',
        'shipping_cost',
        'total',
        'paid_amount',
        'payment_collected',
        'payment_collected_at',
        'payment_collected_by',
        'currency',
        'shipping_address_snapshot',
        'billing_address_snapshot',
        'notes',
        'admin_notes',
        'ip_address',
        'user_agent',
        'source',
        'metadata',
        'confirmed_at',
        'packed_at',
        'shipped_at',
        'out_for_delivery_at',
        'delivered_at',
        'cancelled_at',
        'expected_delivery_date',
        'guest_email',
        'guest_name',
        'guest_phone',
        'shiprocket_order_id',
        'shiprocket_shipment_id',
        'shiprocket_awb',
        'shiprocket_courier',
        'shiprocket_pushed_at',
        'tracking_url',
        // PR #18 — Phase 0: these columns exist in the DB but were missing from $fillable,
        // so every Order::create([..., 'razorpay_order_id' => ...]) silently dropped them.
        // Result: idempotency lookups (CheckoutController L915, L1412) always returned null
        // → duplicate orders possible; webhook lookups (RazorpayWebhookController) always
        // failed → payments ledger empty by default.
        'razorpay_order_id',
        'razorpay_payment_id',
        'tracking_number',
        'carrier',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'shipping_address_snapshot' => 'array',
            'billing_address_snapshot' => 'array',
            'metadata' => 'array',
            'confirmed_at' => 'datetime',
            'packed_at' => 'datetime',
            'shipped_at' => 'datetime',
            'out_for_delivery_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'payment_collected' => 'boolean',
            'payment_collected_at' => 'datetime',
            'expected_delivery_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = static::generateOrderNumber();
            }
            if (empty($order->checkout_token)) {
                $order->checkout_token = Str::random(32);
            }
        });
    }

    public static function generateOrderNumber(): string
    {
        $tenantPrefix = Setting::get('order_number_prefix', '');

        if ($tenantPrefix) {
            // Atomic increment via DB row lock to prevent duplicate order numbers.
            // Previous code used Setting::get/set which reads from a 5-min cache blob,
            // causing race conditions when concurrent orders read the same counter value.
            $nextNumber = DB::transaction(function () {
                $row = DB::table('settings')
                    ->where('key', 'order_number_counter')
                    ->lockForUpdate()
                    ->first();

                $nextNumber = ((int) ($row->value ?? 0)) + 1;

                if ($row) {
                    DB::table('settings')
                        ->where('key', 'order_number_counter')
                        ->update(['value' => (string) $nextNumber, 'updated_at' => now()]);
                } else {
                    DB::table('settings')->insert([
                        'key' => 'order_number_counter',
                        'value' => (string) $nextNumber,
                        'type' => 'string',
                        'group' => 'general',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                return $nextNumber;
            });

            // Bust the settings cache so Setting::get() reads fresh on next call
            $cacheKey = 'settings.all.' . DB::connection()->getDatabaseName();
            Cache::store(config('cache.default'))->forget($cacheKey);

            return '#' . $tenantPrefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        }

        $prefix = 'ORD';
        $date = now()->format('Ymd');
        $random = strtoupper(substr(uniqid(), -5));

        return "{$prefix}-{$date}-{$random}";
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(UserAddress::class, 'shipping_address_id');
    }

    public function billingAddress(): BelongsTo
    {
        return $this->belongsTo(UserAddress::class, 'billing_address_id');
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function deliveryPartner(): BelongsTo
    {
        return $this->belongsTo(DeliveryPartner::class);
    }

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(OrderShipment::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(OrderReturn::class, 'order_id');
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    // Helper methods
    public function isGuest(): bool
    {
        return is_null($this->user_id);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isConfirmed(): bool
    {
        return in_array($this->status, ['confirmed', 'processing', 'shipped', 'delivered']);
    }

    public function isPacked(): bool
    {
        return in_array($this->status, ['packed', 'shipped', 'out_for_delivery', 'delivered']);
    }

    public function isShipped(): bool
    {
        return in_array($this->status, ['shipped', 'out_for_delivery', 'delivered']);
    }

    public function isOutForDelivery(): bool
    {
        return in_array($this->status, ['out_for_delivery', 'delivered']);
    }

    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'confirmed', 'processing']);
    }

    public function canBeReturned(): bool
    {
        return $this->status === 'delivered'
            && $this->delivered_at
            && $this->delivered_at->addHours(24)->isPast()
            && $this->delivered_at->diffInDays(now()) <= 7;
    }

    /**
     * Valid status transitions — enforced at model level.
     */
    public const ALLOWED_TRANSITIONS = [
        'pending'          => ['confirmed', 'cancelled'],
        'confirmed'        => ['processing', 'packed', 'shipped', 'cancelled'],
        'processing'       => ['packed', 'shipped', 'cancelled'],
        'packed'           => ['shipped', 'cancelled'],
        'shipped'          => ['out_for_delivery', 'delivered', 'returned'],
        'out_for_delivery' => ['delivered', 'returned'],
        'delivered'        => ['returned'],
        'cancelled'        => [],
        'returned'         => [],
    ];

    public function canTransitionTo(string $newStatus): bool
    {
        $allowed = self::ALLOWED_TRANSITIONS[$this->status] ?? [];

        return in_array($newStatus, $allowed, true);
    }

    public function updateStatus(string $status, ?int $userId = null, ?string $comment = null): void
    {
        if (!$this->canTransitionTo($status)) {
            throw new \InvalidArgumentException(
                "Cannot transition order from \"{$this->status}\" to \"{$status}\"."
            );
        }

        $oldStatus = $this->status;
        $this->update(['status' => $status]);

        $this->statusHistory()->create([
            'status' => $status,
            'comment' => $comment,
            'created_by' => $userId,
        ]);

        // Update timestamps
        match ($status) {
            'confirmed' => $this->update(['confirmed_at' => now()]),
            'packed' => $this->update(['packed_at' => now()]),
            'shipped' => $this->update(['shipped_at' => now()]),
            'out_for_delivery' => $this->update(['out_for_delivery_at' => now()]),
            'delivered' => $this->update(['delivered_at' => now()]),
            'cancelled' => $this->update([
                'cancelled_at' => now(),
                'payment_status' => $this->payment_status === 'paid' ? 'refunded' : $this->payment_status,
            ]),
            default => null,
        };

        // Restore stock on cancellation or return (only if coming from a non-cancelled/returned state)
        if (in_array($status, ['cancelled', 'returned']) && !in_array($oldStatus, ['cancelled', 'returned'])) {
            $this->restoreStock();
        }
    }

    /**
     * Restore stock for all items in this order.
     * Called when order is cancelled or returned.
     */
    public function restoreStock(): void
    {
        $this->loadMissing('items');

        foreach ($this->items as $item) {
            if ($item->variant_id) {
                \Illuminate\Support\Facades\DB::table('product_variants')
                    ->where('id', $item->variant_id)
                    ->increment('stock_quantity', $item->quantity);
            } else {
                \Illuminate\Support\Facades\DB::table('products')
                    ->where('id', $item->product_id)
                    ->increment('stock_quantity', $item->quantity);
            }

            // Auto-update stock_status back to in_stock
            \Illuminate\Support\Facades\DB::table('products')
                ->where('id', $item->product_id)
                ->where('stock_status', 'out_of_stock')
                ->update(['stock_status' => 'in_stock']);
        }
    }

    public function getTrackingSteps(): array
    {
        $steps = [
            [
                'key' => 'confirmed',
                'label' => 'Ordered',
                'icon' => 'clipboard-check',
                'completed' => $this->isConfirmed(),
                'current' => $this->status === 'confirmed',
                'timestamp' => $this->confirmed_at,
            ],
            [
                'key' => 'processing',
                'label' => 'Processing',
                'icon' => 'clipboard-check',
                'completed' => in_array($this->status, ['packed', 'shipped', 'out_for_delivery', 'delivered'], true),
                'current' => $this->status === 'processing',
                'timestamp' => null,
            ],
            [
                'key' => 'packed',
                'label' => 'Packed',
                'icon' => 'cube',
                'completed' => $this->isPacked(),
                'current' => $this->status === 'packed',
                'timestamp' => $this->packed_at,
            ],
            [
                'key' => 'shipped',
                'label' => 'Shipped',
                'icon' => 'truck',
                'completed' => $this->isShipped(),
                'current' => $this->status === 'shipped',
                'timestamp' => $this->shipped_at,
            ],
            [
                'key' => 'out_for_delivery',
                'label' => 'Out for Delivery',
                'icon' => 'map-pin',
                'completed' => $this->isOutForDelivery(),
                'current' => $this->status === 'out_for_delivery',
                'timestamp' => $this->out_for_delivery_at,
            ],
            [
                'key' => 'delivered',
                'label' => 'Delivered',
                'icon' => 'check-circle',
                'completed' => $this->isDelivered(),
                'current' => $this->status === 'delivered',
                'timestamp' => $this->delivered_at,
            ],
        ];

        return $steps;
    }

    public function getBalanceDueAttribute(): float
    {
        return max(0, $this->total - $this->paid_amount);
    }
}
