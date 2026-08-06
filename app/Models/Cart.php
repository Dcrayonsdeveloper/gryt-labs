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

    /**
     * Re-price every line item to the CURRENT product/variant price.
     *
     * cart_items.price is a snapshot taken when the item was added, so an admin
     * price change would otherwise never reach an existing cart — its display,
     * subtotal, or the Shiprocket checkout token (which reads $item->price).
     * Call this whenever the cart is loaded for display or checkout. Saving each
     * changed item recomputes its total (saving hook) and the cart totals (saved
     * hook), so no extra recalculate() is needed. Returns true if anything moved.
     */
    public function syncItemPrices(): bool
    {
        // First fold bundle lines into their linear form (combo packs + a single),
        // so every line's price is a plain unit price Shiprocket can also charge.
        $changed = $this->normalizeBundles();

        $this->loadMissing(['items.product', 'items.variant']);

        foreach ($this->items as $item) {
            if (! $item->product) {
                continue;
            }
            // Bundle products re-price by quantity (getPackUnitPrice); variants use
            // the variant price; everything else uses the product's live price.
            $live = $item->variant?->price
                ?? ($item->product->hasPackOffer()
                    ? $item->product->getPackUnitPrice((int) $item->quantity)
                    : $item->product->price);
            if ($live === null) {
                continue;
            }
            if (abs((float) $item->price - (float) $live) > 0.001) {
                $item->price = (float) $live; // saving hook recomputes total; saved hook recalculates the cart
                $item->save();
                $changed = true;
            }
        }

        return $changed;
    }

    /**
     * Fold bundle-product lines into their linear composition:
     * N units → floor(N/2) × "Pack of 2" combo product + (N%2) × single.
     *
     * WHY: Shiprocket prices linearly (unit × qty, qty editable in its popup), so
     * "2 for ₹499" must live on a real combo product (see bundles:sync-combos).
     * Normalizing here means the cart, checkout token and Shiprocket all see the
     * same plainly-priced lines — a customer adding 2 singles automatically gets
     * the ₹499 pack. Returns true when any line changed.
     */
    public function normalizeBundles(): bool
    {
        $this->load(['items.product']);

        // Group lines by bundle family: [baseId => ['base' => [...], 'combo' => [...], 'comboId' => int]]
        $families = [];
        foreach ($this->items as $item) {
            $p = $item->product;
            if (! $p || $item->variant_id) {
                continue;
            }
            if ($baseId = ($p->pack_config['combo_of'] ?? null)) {
                $families[$baseId]['combo'][] = $item;
            } elseif ($p->packBundle() && ($cid = $p->pack_config['bundle']['combo_product_id'] ?? null)) {
                $families[$p->id]['base'][] = $item;
                $families[$p->id]['comboId'] = $cid;
            }
        }

        $changed = false;

        foreach ($families as $baseId => $fam) {
            $baseItems  = $fam['base'] ?? [];
            $comboItems = $fam['combo'] ?? [];
            $comboId    = $fam['comboId'] ?? ($comboItems ? $comboItems[0]->product_id : null);

            $base  = Product::find($baseId);
            $combo = $comboId ? Product::find($comboId) : null;
            if (! $base || ! $combo || ! $combo->is_active) {
                continue; // combo missing — leave lines untouched
            }

            $units = collect($baseItems)->sum('quantity') + 2 * collect($comboItems)->sum('quantity');
            if ($units < 1) {
                continue;
            }
            // Mode-aware: 'even' bundles give odd unit-counts NO pair deal (all singles).
            $comp = $base->packComposition($units);
            $comboTarget  = $comp['combos'];
            $singleTarget = $comp['singles'];

            $currentCombo  = collect($comboItems)->sum('quantity');
            $currentSingle = collect($baseItems)->sum('quantity');
            $singlePrice   = $base->getPackTotalPrice(1);

            $alreadyNormal = $currentCombo === $comboTarget
                && $currentSingle === $singleTarget
                && count($baseItems) <= 1 && count($comboItems) <= 1
                && (! $baseItems || abs((float) $baseItems[0]->price - $singlePrice) < 0.001)
                && (! $comboItems || abs((float) $comboItems[0]->price - (float) $combo->price) < 0.001);
            if ($alreadyNormal) {
                continue;
            }

            // Rewrite: one combo line + at most one single line.
            foreach (array_slice($comboItems, 1) as $extra) { $extra->delete(); }
            foreach (array_slice($baseItems, 1) as $extra) { $extra->delete(); }

            $comboLine = $comboItems[0] ?? null;
            $baseLine  = $baseItems[0] ?? null;

            if ($comboTarget > 0) {
                if ($comboLine) {
                    $comboLine->fill(['quantity' => $comboTarget, 'price' => (float) $combo->price])->save();
                } else {
                    $this->items()->create([
                        'product_id' => $combo->id,
                        'quantity'   => $comboTarget,
                        'price'      => (float) $combo->price,
                    ]);
                }
            } elseif ($comboLine) {
                $comboLine->delete();
            }

            if ($singleTarget > 0) {
                if ($baseLine) {
                    $baseLine->fill(['quantity' => $singleTarget, 'price' => $singlePrice])->save();
                } else {
                    $this->items()->create([
                        'product_id' => $base->id,
                        'quantity'   => $singleTarget,
                        'price'      => $singlePrice,
                    ]);
                }
            } elseif ($baseLine) {
                $baseLine->delete();
            }

            $changed = true;
        }

        if ($changed) {
            $this->load(['items.product']);
        }

        return $changed;
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
        return $this->unitCount();
    }

    /**
     * Number of physical units in the cart.
     *
     * A "Pack of 2" combo line is ONE line of quantity N but 2N units, and the
     * money already reflects units (MRP = 2N × unit MRP). Counting lines instead
     * made the header read "2 items" for 4 bottles — inconsistent with the totals.
     */
    public function unitCount(): int
    {
        $this->loadMissing('items.product');

        return (int) $this->items->sum(fn ($i) => $i->quantity * $i->unitsPerQuantity());
    }
}
