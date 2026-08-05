<?php

namespace App\Services\ShiprocketCheckout;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;

/**
 * Builds product/collection payloads in the Shopify-compatible format that
 * Shiprocket Checkout expects.
 *
 * Single source of truth for two consumers:
 *   - CatalogController  — the pull endpoints (/sr/catalog/*) Shiprocket crawls
 *   - ShiprocketCheckoutService::pushCatalog* — the push webhooks we call on
 *     every product/collection update (wh/v1/custom/product, .../collection)
 *
 * Keeping one formatter guarantees the pull and push catalogs never diverge.
 */
class ShiprocketCatalogFormatter
{
    /**
     * Full product object in Shiprocket/Shopify format.
     */
    public function product(Product $product): array
    {
        $primaryImage = $this->resolvePrimaryImage($product);
        $imageObj     = $primaryImage ? ['src' => url($primaryImage->url)] : null;

        $categoryName = $product->relationLoaded('category') && $product->category
            ? $product->category->name
            : ($product->category?->name ?? '');

        $tags = '';
        if (is_array($product->tags)) {
            $tags = implode(', ', $product->tags);
        }

        [$variants, $options] = $this->buildVariantsAndOptions($product, $imageObj);

        return [
            'id'           => $product->id,
            'title'        => $product->name,
            'body_html'    => $product->description ?? '',
            'vendor'       => config('app.name'),
            'product_type' => $categoryName,
            'created_at'   => $product->created_at?->toIso8601String(),
            'handle'       => $product->slug,
            'updated_at'   => $product->updated_at?->toIso8601String(),
            'tags'         => $tags,
            // Pull endpoint only ever passes active products; for push we send
            // single products that may have been deactivated — reflect that.
            'status'       => $product->is_active ? 'active' : 'archived',
            'variants'     => $variants,
            'image'        => $imageObj,
            'options'      => $options,
        ];
    }

    /**
     * Collection (category) object for the wh/v1/custom/collection push webhook.
     */
    public function collection(Category $category): array
    {
        return [
            'id'         => $category->id,
            'title'      => $category->name,
            'handle'     => $category->slug,
            'body_html'  => $category->description ?? '',
            'image'      => $category->image_url ? ['src' => url($category->image_url)] : null,
            'created_at' => $category->created_at?->toIso8601String(),
            'updated_at' => $category->updated_at?->toIso8601String(),
        ];
    }

    // ─── Internals ────────────────────────────────────────────────────────────

    private function buildVariantsAndOptions(Product $product, ?array $fallbackImage): array
    {
        $weight     = (float) ($product->weight ?? 0);
        $weightUnit = $product->weight_unit ?? 'g';
        $grams      = $this->toGrams($weight, $weightUnit);

        if ($product->relationLoaded('variants') && $product->variants->isNotEmpty()) {
            $active = $product->variants->where('is_active', true)->values();

            if ($active->isNotEmpty()) {
                $optionMap = [];
                $variants  = [];

                foreach ($active as $variant) {
                    $attrs = is_array($variant->attributes) ? $variant->attributes : [];
                    foreach ($attrs as $key => $value) {
                        $optionMap[$key][] = (string) $value;
                    }

                    $variantImage = null;
                    if ($variant->relationLoaded('images') && $variant->images->isNotEmpty()) {
                        $variantImage = ['src' => url($variant->images->first()->url)];
                    }

                    $price = $variant->price ?? $product->price;
                    $mrp   = $variant->mrp ?? $product->mrp;

                    $variants[] = [
                        'id'               => $variant->id,
                        'title'            => $variant->name ?? 'Default Title',
                        'price'            => number_format((float) $price, 2, '.', ''),
                        'compare_at_price' => $mrp && $mrp > $price
                            ? number_format((float) $mrp, 2, '.', '') : null,
                        'sku'              => $variant->sku ?? ('SKU-' . $product->id),
                        'quantity'         => max(0, (int) $variant->stock_quantity),
                        'created_at'       => $variant->created_at?->toIso8601String(),
                        'updated_at'       => $variant->updated_at?->toIso8601String(),
                        'taxable'          => (bool) $product->is_taxable,
                        'option_values'    => !empty($attrs) ? $attrs : new \stdClass(),
                        'grams'            => $grams,
                        'image'            => $variantImage ?? $fallbackImage,
                        'weight'           => $weight,
                        'weight_unit'      => $weightUnit,
                    ];
                }

                $options = [];
                foreach ($optionMap as $name => $values) {
                    $options[] = [
                        'name'   => $name,
                        'values' => array_values(array_unique($values)),
                    ];
                }

                return [$variants, $options];
            }
        }

        // Fallback: single default variant from product data.
        // Use the best pack-tier price so it matches what TokenController sends
        // to Shiprocket (cart items use getPackUnitPrice). Without this, Shiprocket
        // overrides the token price with the higher catalog price.
        $effectivePrice = $this->resolvePackPrice($product);

        return [
            [
                [
                    'id'               => $product->id,
                    'title'            => 'Default Title',
                    'price'            => number_format($effectivePrice, 2, '.', ''),
                    'compare_at_price' => $product->mrp && $product->mrp > $effectivePrice
                        ? number_format((float) $product->mrp, 2, '.', '') : null,
                    'sku'              => $product->sku ?? ('SKU-' . $product->id),
                    'quantity'         => max(0, (int) ($product->stock_quantity ?? 0)),
                    'created_at'       => $product->created_at?->toIso8601String(),
                    'updated_at'       => $product->updated_at?->toIso8601String(),
                    'taxable'          => (bool) $product->is_taxable,
                    'option_values'    => new \stdClass(),
                    'grams'            => $grams,
                    'image'            => $fallbackImage,
                    'weight'           => $weight,
                    'weight_unit'      => $weightUnit,
                ],
            ],
            [],
        ];
    }

    private function resolvePrimaryImage(Product $product): ?object
    {
        if ($product->relationLoaded('images') && $product->images->isNotEmpty()) {
            return $product->images->firstWhere('is_primary', true)
                ?? $product->images->first();
        }

        $fallback = $product->primary_image_url;
        if ($fallback) {
            return (object) ['url' => $fallback];
        }

        return null;
    }

    private function toGrams(float $weight, string $unit): int
    {
        return (int) match ($unit) {
            'kg' => $weight * 1000,
            'lb' => $weight * 453.592,
            'oz' => $weight * 28.3495,
            default => $weight,
        };
    }

    /**
     * Return the lowest pack-tier price for this product.
     *
     * Shiprocket Checkout uses catalog prices as the source of truth for
     * charging. The cart/token sends pack-discounted prices via
     * buildCartItem(), so the catalog MUST return the same (or lower) price,
     * otherwise Shiprocket overrides the token price with the higher catalog
     * price and the customer is overcharged.
     */
    private function resolvePackPrice(Product $product): float
    {
        $base = (float) $product->price;

        // Per-product bundle: the cheapest per-unit price is the pair price / 2.
        // Shiprocket charges from the catalog price, so it must be <= any unit price
        // the token sends, or Shiprocket overrides upward and overcharges.
        if ($bundle = $product->packBundle()) {
            return min($base, round($bundle['pair'] / 2, 2));
        }

        if (!Setting::get('product_packs_enabled', false) || $product->mrp <= 0) {
            return $base;
        }

        $tiersJson = Setting::get('pack_tiers', '');
        $tiers     = $tiersJson ? json_decode($tiersJson, true) : null;

        if (!is_array($tiers) || empty($tiers)) {
            return $base;
        }

        $lowest = $base;
        foreach ($tiers as $tier) {
            $discountPct = (float) ($tier['discount'] ?? 0);
            if ($discountPct > 0) {
                $tierPrice = round($base * (1 - $discountPct / 100), 2);
                $lowest    = min($lowest, $tierPrice);
            }
        }

        return $lowest;
    }
}
