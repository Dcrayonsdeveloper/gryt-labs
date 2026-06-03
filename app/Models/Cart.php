<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    protected $fillable = [
        'user_id',
        'session_id',
        'coupon_id',
        'applied_coupons',
        'subtotal',
        'discount',
        'tax',
        'shipping',
        'total',
        'metadata',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax' => 'decimal:2',
            'shipping' => 'decimal:2',
            'total' => 'decimal:2',
            'applied_coupons' => 'array',
            'metadata' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function recalculate(bool $skipAutoApply = false): void
    {
        $this->load(['items.product', 'coupon']);
        $subtotal = $this->items->sum('total');
        $discount = 0;

        // Calculate discount from stacked coupons (if any)
        $appliedCouponIds = $this->applied_coupons ?? [];
        if (!empty($appliedCouponIds)) {
            $stackedCoupons = Coupon::whereIn('id', $appliedCouponIds)->get();
            $validIds = [];
            foreach ($stackedCoupons as $stackedCoupon) {
                if ($stackedCoupon->isValid()) {
                    $couponDiscount = $stackedCoupon->calculateDiscount($subtotal, $this->items);
                    if ($couponDiscount > 0 || $stackedCoupon->type === 'free_shipping') {
                        $discount += $couponDiscount;
                        $validIds[] = $stackedCoupon->id;
                    }
                }
            }
            // Remove invalid coupons from the stack
            if (count($validIds) !== count($appliedCouponIds)) {
                $appliedCouponIds = $validIds;
            }
            // Keep coupon_id in sync with the primary (first) coupon
            $this->coupon_id = !empty($appliedCouponIds) ? $appliedCouponIds[0] : null;
        } elseif ($this->coupon && $this->coupon->isValid()) {
            // Legacy single coupon path (backward compatible)
            $discount = $this->coupon->calculateDiscount($subtotal, $this->items);
        }

        // Auto-apply: if no manual coupon, find the best auto-apply coupon
        if (!$skipAutoApply && !$this->coupon_id && empty($appliedCouponIds) && $subtotal > 0) {
            $autoCoupon = Coupon::findBestAutoApply($this);
            if ($autoCoupon) {
                $this->coupon_id = $autoCoupon->id;
                $appliedCouponIds = [$autoCoupon->id];
                $discount = $autoCoupon->calculateDiscount($subtotal, $this->items);
            }
        }

        // If current coupon no longer gives a discount, remove it
        if ($this->coupon_id && $discount == 0 && empty($appliedCouponIds) && $this->coupon && $this->coupon->type !== 'free_shipping') {
            $this->coupon_id = null;
            $appliedCouponIds = [];
        }

        // Cap discount so the cart total never reaches 0 — customer must always pay at least ₹1.
        // Prevents fixed-amount coupons (e.g. ₹500 off on ₹400 cart) from triggering the
        // free-order bypass in CheckoutController which skips the payment gateway entirely.
        if ($subtotal > 0 && $discount >= $subtotal) {
            $discount = max(0, $subtotal - 1);
        }

        // Tax calculation respects the tenant's tax_calculation setting
        $taxMode = Setting::get('tax_calculation', 'inclusive');

        $tax = $this->items->sum(function ($item) use ($taxMode) {
            if (!$item->product->is_taxable) {
                return 0;
            }
            $rate = $item->product->tax_rate;
            if ($taxMode === 'inclusive') {
                // Price already includes tax — extract the tax component
                return round($item->total * $rate / (100 + $rate), 2);
            }
            // Exclusive: tax is on top of price
            return round($item->total * $rate / 100, 2);
        });

        if ($taxMode === 'inclusive') {
            // Inclusive: total = subtotal - discount + shipping (tax already in price)
            $total = $subtotal - $discount + $this->shipping;
        } else {
            // Exclusive: total = subtotal - discount + tax + shipping
            $total = $subtotal - $discount + $tax + $this->shipping;
        }

        $this->update([
            'coupon_id' => $this->coupon_id,
            'applied_coupons' => !empty($appliedCouponIds) ? $appliedCouponIds : null,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax' => $tax,
            'total' => $total,
        ]);
    }

    /**
     * Get all applied coupons (stacked or single).
     */
    public function getAppliedCoupons(): \Illuminate\Database\Eloquent\Collection
    {
        $ids = $this->applied_coupons ?? [];
        if (empty($ids) && $this->coupon_id) {
            $ids = [$this->coupon_id];
        }
        if (empty($ids)) {
            return Coupon::query()->whereRaw('1=0')->get(); // empty collection
        }
        return Coupon::whereIn('id', $ids)->get();
    }

    public function isEmpty(): bool
    {
        return $this->items->isEmpty();
    }

    public function getItemCount(): int
    {
        return $this->items->sum('quantity');
    }
}
