<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Product extends Model
{
    use HasSlug, Searchable, SoftDeletes;

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected $fillable = [
        'uuid',
        'seller_id',
        'brand_id',
        'category_id',
        'name',
        'slug',
        'meta_title',
        'meta_description',
        'short_description',
        'description',
        'sku',
        'barcode',
        'mrp',
        'price',
        'cost_price',
        'stock_quantity',
        'low_stock_threshold',
        'stock_status',
        'weight',
        'length',
        'width',
        'height',
        'weight_unit',
        'dimension_unit',
        'is_active',
        'is_featured',
        'is_new_arrival',
        'is_taxable',
        'tax_rate',
        'hsn_code',
        'rating',
        'review_count',
        'view_count',
        'sales_count',
        'social_proof_text',
        'stats_carousel',
        'pack_config',
        'wishlist_count',
        'seo_data',
        'attributes',
        'specifications',
        'faqs',
        'video_url',
        'amazon_url',
        'flipkart_url',
        'testimonial_videos',
        'status',
        'rejection_reason',
        'published_at',
        'tags',
    ];

    protected function casts(): array
    {
        return [
            'mrp' => 'decimal:2',
            'price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'weight' => 'decimal:2',
            'length' => 'decimal:2',
            'width' => 'decimal:2',
            'height' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'rating' => 'decimal:2',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'is_new_arrival' => 'boolean',
            'is_taxable' => 'boolean',
            'seo_data' => 'array',
            'attributes' => 'array',
            'specifications' => 'array',
            'faqs' => 'array',
            'stats_carousel' => 'array',
            'pack_config' => 'array',
            'testimonial_videos' => 'array',
            'tags' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    protected static function booted(): void
    {
        static::creating(function ($product) {
            if (empty($product->uuid)) {
                $product->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });

        // Auto-sync stock_status when stock_quantity changes
        static::saving(function ($product) {
            if ($product->isDirty('stock_quantity')) {
                $product->stock_status = ((int) $product->stock_quantity > 0) ? 'in_stock' : 'out_of_stock';
            }
        });

        // When stock_quantity changes from 0 to >0, notify back-in-stock subscribers
        static::updated(function ($product) {
            $originalStock = (int) $product->getOriginal('stock_quantity');
            $newStock = (int) $product->stock_quantity;

            if ($originalStock <= 0 && $newStock > 0) {
                \App\Jobs\SendBackInStockNotifications::dispatch($product->id);
            }
        });
    }

    /**
     * Tenant-prefixed search index name for multi-tenant isolation.
     */
    public function searchableAs(): string
    {
        $prefix = function_exists('tenant') && tenant() ? tenant('id') . '_' : '';
        return $prefix . 'products';
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'short_description' => $this->short_description,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'price' => $this->price,
            'category_id' => $this->category_id,
            'brand_id' => $this->brand_id,
            'rating' => $this->rating,
            'sales_count' => $this->sales_count,
        ];
    }

    // Relationships
    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_product');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('position');
    }

    public function primaryImage(): HasMany
    {
        return $this->hasMany(ProductImage::class)->where('is_primary', true);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function attributeValues(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ProductTag::class, 'product_tag_pivot', 'product_id', 'tag_id');
    }

    public function relatedProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'related_products', 'product_id', 'related_product_id')
            ->withPivot('type', 'position');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->hasMany(Review::class)
            ->where('is_approved', true)
            ->where('created_at', '<=', now());
    }

    public function questions(): HasMany
    {
        return $this->hasMany(ProductQuestion::class);
    }

    public function inventoryStocks(): HasMany
    {
        return $this->hasMany(InventoryStock::class);
    }

    public function backInStockRequests(): HasMany
    {
        return $this->hasMany(BackInStockRequest::class);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function views(): HasMany
    {
        return $this->hasMany(ProductView::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('status', 'approved');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('stock_status', 'in_stock');
    }

    // Accessors
    public function getDiscountPercentageAttribute(): int
    {
        if ($this->mrp <= 0 || $this->price >= $this->mrp) {
            return 0;
        }

        return (int) round((($this->mrp - $this->price) / $this->mrp) * 100);
    }

    public function getIsOnSaleAttribute(): bool
    {
        return $this->price < $this->mrp;
    }

    public function getPrimaryImageUrlAttribute(): string
    {
        // Prefer the eager-loaded primaryImage relation to avoid N+1 on listings
        if ($this->relationLoaded('primaryImage')) {
            $image = $this->primaryImage->first();
            if ($image) {
                return $image->full_url ?? asset('images/no-product-image.svg');
            }
            // primaryImage loaded but empty — fall back to images only if also loaded
            if ($this->relationLoaded('images')) {
                $image = $this->images->first();
                return $image?->full_url ?? asset('images/no-product-image.svg');
            }
        }

        $image = $this->images->firstWhere('is_primary', true)
            ?? $this->images->first();

        return $image?->full_url ?? asset('images/no-product-image.svg');
    }

    // Helper methods
    public function isInStock(): bool
    {
        return $this->stock_status === 'in_stock' && $this->stock_quantity > 0;
    }

    public function isLowStock(): bool
    {
        return $this->stock_quantity <= $this->low_stock_threshold;
    }

    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    public function incrementSalesCount(int $quantity = 1): void
    {
        $this->increment('sales_count', $quantity);
    }

    public function updateRating(): void
    {
        $reviews = $this->reviews()->where('is_approved', true);
        $this->update([
            'rating' => $reviews->avg('rating') ?? 0,
            'review_count' => $reviews->count(),
        ]);
    }

    /**
     * Get the effective per-unit price when buying $quantity units,
     * applying pack-tier discounts if enabled for this tenant.
     *
     * Returns the base price if packs are disabled or quantity doesn't match a tier.
     */
    /**
     * Per-product "buy more, save" bundle, stored in pack_config['bundle'] as
     * ['single' => 599, 'pair' => 999]. Total for N units = (N/2 pairs)·pair +
     * (leftover single)·single. e.g. 1→599, 2→999, 3→1598, 4→1998. Returns null
     * when the product has no bundle configured.
     *
     * @return array{single: float, pair: float}|null
     */
    public function packBundle(): ?array
    {
        $b = $this->pack_config['bundle'] ?? null;
        if (! is_array($b)) {
            return null;
        }

        // Explicit per-quantity prices win, e.g. tiers => [1=>299, 2=>499, 3=>897, 4=>998].
        $tiers = [];
        foreach ((array) ($b['tiers'] ?? []) as $k => $v) {
            if (is_array($v) && isset($v['qty'], $v['price'])) {
                $tiers[(int) $v['qty']] = (float) $v['price'];
            } elseif (is_numeric($k) && is_numeric($v)) {
                $tiers[(int) $k] = (float) $v;
            }
        }

        $single = isset($b['single']) ? (float) $b['single'] : null;
        $pair   = isset($b['pair']) ? (float) $b['pair'] : null;

        // Need either explicit tiers, or a single+pair formula.
        if (empty($tiers) && (! $single || ! $pair)) {
            return null;
        }

        return ['tiers' => $tiers, 'single' => $single, 'pair' => $pair];
    }

    public function hasPackOffer(): bool
    {
        return $this->packBundle() !== null;
    }

    /** Total price for buying $quantity units (bundle formula, or straight price). */
    public function getPackTotalPrice(int $quantity): float
    {
        $quantity = max(1, $quantity);
        $bundle = $this->packBundle();
        if (! $bundle) {
            return (float) $this->price * $quantity;
        }

        // 1) Explicit price for this exact quantity wins.
        if (isset($bundle['tiers'][$quantity])) {
            return (float) $bundle['tiers'][$quantity];
        }

        // 2) Pairs + singles formula (e.g. pre-workout), if configured.
        if ($bundle['single'] && $bundle['pair']) {
            return intdiv($quantity, 2) * $bundle['pair'] + ($quantity % 2) * $bundle['single'];
        }

        // 3) Beyond the defined tiers with no formula: extend at the largest tier's per-unit rate.
        if (! empty($bundle['tiers'])) {
            $maxQty  = max(array_keys($bundle['tiers']));
            $perUnit = $bundle['tiers'][$maxQty] / $maxQty;

            return round($perUnit * $quantity, 2);
        }

        return (float) $this->price * $quantity;
    }

    /**
     * Pack tiers for the PDP selector / add-to-cart popup (qty 1..$max), with the
     * struck-through "was" price derived from the product MRP.
     *
     * @return array<int, array{qty:int,total:int,unit:float,mrp:int,savings:int,savingsPct:int}>
     */
    public function packTiers(int $max = 4): array
    {
        if (! $this->hasPackOffer()) {
            return [];
        }

        $unitMrp = (float) $this->mrp;
        $tiers = [];
        for ($q = 1; $q <= $max; $q++) {
            $total = $this->getPackTotalPrice($q);
            $mrp   = $unitMrp > 0 ? $unitMrp * $q : 0;
            $save  = max(0, $mrp - $total);
            $tiers[] = [
                'qty'        => $q,
                'total'      => (int) round($total),
                'unit'       => round($total / $q, 2),
                'mrp'        => (int) round($mrp),
                'savings'    => (int) round($save),
                'savingsPct' => $mrp > 0 ? (int) round($save / $mrp * 100) : 0,
            ];
        }

        return $tiers;
    }

    public function getPackUnitPrice(int $quantity): float
    {
        if ($quantity < 1) {
            return (float) $this->price;
        }

        // Per-product bundle takes precedence over the global pack setting.
        if ($this->packBundle()) {
            return round($this->getPackTotalPrice($quantity) / $quantity, 2);
        }

        $packsEnabled = (bool) Setting::get('product_packs_enabled', false);
        if (!$packsEnabled || $this->mrp <= 0) {
            return (float) $this->price;
        }

        $tiersJson = Setting::get('pack_tiers', '');
        $tiers = $tiersJson ? json_decode($tiersJson, true) : null;
        if (!is_array($tiers) || empty($tiers)) {
            return (float) $this->price;
        }

        foreach ($tiers as $tier) {
            if ((int) ($tier['qty'] ?? 0) === $quantity) {
                $discountPct = (float) ($tier['discount'] ?? 0);
                if ($discountPct > 0) {
                    return round((float) $this->price * (1 - $discountPct / 100), 2);
                }
                return (float) $this->price;
            }
        }

        return (float) $this->price;
    }
}
