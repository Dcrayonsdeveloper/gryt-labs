<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    protected $fillable = [
        'product_id',
        'variant_id',
        'url',
        'alt_text',
        'position',
        'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * Always returns a usable URL regardless of how the path was stored:
     * - "products/xxx.jpg"    → "/storage/products/xxx.jpg"
     * - "/storage/..."        → as-is
     * - "https://..."         → as-is
     *
     * Override the raw column so callers never need a separate accessor.
     */
    public function getUrlAttribute(): string
    {
        $raw = $this->attributes['url'] ?? '';

        if (!$raw) {
            return asset('images/no-product-image.svg');
        }

        if (str_starts_with($raw, 'http')) {
            return $raw;
        }

        if (str_starts_with($raw, '/storage/') || str_starts_with($raw, '/images/')) {
            return $raw;
        }

        return '/storage/' . ltrim($raw, '/');
    }

    /**
     * @deprecated Use $image->url — it now always returns the corrected path.
     */
    public function getFullUrlAttribute(): string
    {
        return $this->url;
    }
}
