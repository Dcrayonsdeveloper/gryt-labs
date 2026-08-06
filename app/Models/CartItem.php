<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    protected $fillable = [
        'cart_id',
        'product_id',
        'variant_id',
        'quantity',
        'price',
        'total',
        'attributes',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'total' => 'decimal:2',
            'attributes' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function ($item) {
            // Bundle products price by TOTAL (e.g. 2 for 999, 3 for 1598), which
            // isn't always price×qty — use the exact bundle total so odd quantities
            // don't drift by a paisa. Falls back to price×qty for normal products.
            $product = $item->product;
            $item->total = ($product && $product->hasPackOffer())
                ? $product->getPackTotalPrice((int) $item->quantity)
                : $item->price * $item->quantity;
        });

        static::saved(function ($item) {
            $item->cart->recalculate();
        });

        static::deleted(function ($item) {
            $item->cart->recalculate();
        });
    }

    /**
     * Physical units represented by ONE unit of quantity on this line.
     * A "Pack of 2" combo product is 2 units; everything else is 1.
     */
    public function unitsPerQuantity(): int
    {
        return ! empty($this->product?->pack_config['combo_of']) ? 2 : 1;
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function updateQuantity(int $quantity): void
    {
        $this->update(['quantity' => $quantity]);
    }
}
