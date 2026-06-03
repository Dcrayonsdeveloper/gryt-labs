<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Response;

class GoogleMerchantController extends Controller
{
    public function __invoke(): Response
    {
        $products = Product::query()
            ->where('is_active', true)
            ->where('stock_quantity', '>', 0)
            ->with(['category', 'brand', 'primaryImage', 'images', 'approvedReviews'])
            ->orderBy('id')
            ->get();

        $appUrl = rtrim(request()->getSchemeAndHttpHost(), '/');
        $appName = \App\Models\Setting::get('store_name', config('app.name'));
        $freeShipThreshold = (float) \App\Models\Setting::get('free_shipping_threshold', 499);
        $shippingRate = (float) \App\Models\Setting::get('shipping_flat_rate', 50);
        $returnDays = \App\Models\Setting::get('return_days', '7');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<rss xmlns:g="http://base.google.com/ns/1.0" version="2.0">' . "\n";
        $xml .= "<channel>\n";
        $xml .= "  <title>{$this->esc($appName)} - Google Merchant Feed</title>\n";
        $xml .= "  <link>{$appUrl}</link>\n";
        $xml .= "  <description>Google Merchant Center product feed for {$this->esc($appName)}</description>\n";

        foreach ($products as $product) {
            $xml .= $this->buildItem($product, $appUrl, $appName, $freeShipThreshold, $shippingRate, $returnDays);
        }

        $xml .= "</channel>\n";
        $xml .= '</rss>';

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'no-cache',
        ]);
    }

    private function buildItem(Product $product, string $appUrl, string $appName, float $freeShipThreshold, float $shippingRate, string $returnDays): string
    {
        $availability = $product->isInStock() ? 'in_stock' : 'out_of_stock';

        // Fix: use /products/ (plural) — matches actual route
        $link = $appUrl . '/products/' . $product->slug;

        // Get primary image — Google only accepts JPEG, PNG, GIF (not WebP)
        $imageUrl = $this->getGoogleSafeImage($product, $appUrl);

        $description = strip_tags(html_entity_decode($product->short_description ?: mb_substr($product->description ?? '', 0, 5000)));
        if (empty(trim($description))) {
            $description = $product->name;
        }

        $item = "  <item>\n";
        $item .= "    <g:id>{$product->id}</g:id>\n";
        $item .= "    <g:title>{$this->esc($product->name)}</g:title>\n";
        $item .= "    <g:description>{$this->esc($description)}</g:description>\n";
        $item .= "    <g:link>{$link}</g:link>\n";
        $item .= "    <g:image_link>{$this->esc($imageUrl)}</g:image_link>\n";

        // Additional images — skip WebP, only JPEG/PNG/GIF
        foreach ($product->images->where('is_primary', false)->take(10) as $img) {
            if ($this->isWebp($img->url)) continue;
            $imgUrl = $this->resolveImageUrl($img->url, $appUrl);
            if ($imgUrl && $imgUrl !== $imageUrl) {
                $item .= "    <g:additional_image_link>{$this->esc($imgUrl)}</g:additional_image_link>\n";
            }
        }

        $item .= "    <g:availability>{$availability}</g:availability>\n";
        $item .= "    <g:condition>new</g:condition>\n";

        // Pricing
        if ($product->mrp && $product->price < $product->mrp) {
            $item .= "    <g:price>" . $this->fmtPrice($product->mrp) . "</g:price>\n";
            $item .= "    <g:sale_price>" . $this->fmtPrice($product->price) . "</g:sale_price>\n";
        } else {
            $item .= "    <g:price>" . $this->fmtPrice($product->price) . "</g:price>\n";
        }

        // Brand
        $brandName = $product->brand?->name ?: $appName;
        $item .= "    <g:brand>{$this->esc($brandName)}</g:brand>\n";

        // Identifiers — Google requires GTIN+brand for most categories
        if ($product->barcode) {
            $item .= "    <g:gtin>{$this->esc($product->barcode)}</g:gtin>\n";
        }
        if ($product->sku) {
            $item .= "    <g:mpn>{$this->esc($product->sku)}</g:mpn>\n";
        }
        // Always set identifier_exists=false when no GTIN — MPN alone is not enough
        if (!$product->barcode) {
            $item .= "    <g:identifier_exists>false</g:identifier_exists>\n";
        }

        // Google product category (taxonomy) — required for Shopping
        $googleCat = $product->google_product_category
            ?? $product->category?->google_product_category
            ?? $this->mapCategoryToGoogle($product->category?->name);
        if ($googleCat) {
            $item .= "    <g:google_product_category>{$this->esc($googleCat)}</g:google_product_category>\n";
        }

        // Store product type (internal category)
        if ($product->category) {
            $item .= "    <g:product_type>{$this->esc($product->category->name)}</g:product_type>\n";
        }

        // Shipping
        $shippingPrice = $product->price >= $freeShipThreshold ? 0 : $shippingRate;
        $item .= "    <g:shipping>\n";
        $item .= "      <g:country>IN</g:country>\n";
        $item .= "      <g:service>Standard</g:service>\n";
        $item .= "      <g:price>" . $this->fmtPrice($shippingPrice) . "</g:price>\n";
        $item .= "    </g:shipping>\n";

        // Return policy
        $item .= "    <g:return_policy_label>{$returnDays}-day-return</g:return_policy_label>\n";

        // Custom labels from product tags (custom_label_0 through custom_label_4)
        $tags = is_array($product->tags) ? array_values($product->tags) : [];
        foreach (array_slice($tags, 0, 5) as $i => $tag) {
            $item .= "    <g:custom_label_{$i}>{$this->esc($tag)}</g:custom_label_{$i}>\n";
        }

        return $item . "  </item>\n";
    }

    private function fmtPrice(float $price): string
    {
        return number_format($price, 2, '.', '') . ' INR';
    }

    private function esc(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /**
     * Get a Google-safe image (JPEG/PNG/GIF only — no WebP).
     * Falls back to first non-WebP image, then placeholder.
     */
    private function getGoogleSafeImage(Product $product, string $appUrl): string
    {
        // Try primary image first
        $primary = $product->images->firstWhere('is_primary', true);
        if ($primary && !$this->isWebp($primary->url)) {
            return $this->resolveImageUrl($primary->url, $appUrl);
        }

        // Try any non-WebP image
        foreach ($product->images as $img) {
            if (!$this->isWebp($img->url)) {
                return $this->resolveImageUrl($img->url, $appUrl);
            }
        }

        // Fallback to primary even if WebP (better than nothing)
        $url = $product->primary_image_url;
        if ($url && !str_starts_with($url, 'http')) {
            return $appUrl . '/' . ltrim($url, '/');
        }
        return $url ?: $appUrl . '/images/no-product-image.svg';
    }

    private function resolveImageUrl(?string $url, string $appUrl): string
    {
        if (!$url) return '';
        if (str_starts_with($url, 'http')) return $url;
        return $appUrl . '/storage/' . ltrim($url, '/');
    }

    private function isWebp(?string $url): bool
    {
        if (!$url) return false;
        return str_ends_with(strtolower($url), '.webp');
    }

    /**
     * Map store category names to Google Product Taxonomy IDs.
     * Full taxonomy: https://www.google.com/basepages/producttype/taxonomy-with-ids.en-US.txt
     */
    private function mapCategoryToGoogle(?string $categoryName): ?string
    {
        if (!$categoryName) return null;

        $name = strtolower(trim($categoryName));

        $map = [
            // Home & Kitchen
            'home & kitchen' => 'Home & Garden > Kitchen & Dining',
            'home and kitchen' => 'Home & Garden > Kitchen & Dining',
            'kitchen' => 'Home & Garden > Kitchen & Dining',
            'home' => 'Home & Garden',
            'home decor' => 'Home & Garden > Decor',

            // Electronics
            'electronics & gadgets' => 'Electronics',
            'electronics' => 'Electronics',
            'gadgets' => 'Electronics',

            // Personal Care
            'personal care & beauty' => 'Health & Beauty',
            'personal care' => 'Health & Beauty > Personal Care',
            'beauty' => 'Health & Beauty',
            'skincare' => 'Health & Beauty > Personal Care > Cosmetics > Skin Care',

            // Travel / Auto
            'car, bike & travel' => 'Vehicles & Parts',
            'car & bike' => 'Vehicles & Parts',
            'travel' => 'Luggage & Bags',

            // Kids
            'kids & toys' => 'Toys & Games',
            'kids, toys & stationery' => 'Toys & Games',
            'toys' => 'Toys & Games',
            'stationery' => 'Office Supplies',

            // Appliances
            'appliances' => 'Home & Garden > Kitchen & Dining > Small Kitchen Appliances',

            // Coffee / Drinkware
            'love over coffee' => 'Home & Garden > Kitchen & Dining > Tableware > Drinkware > Mugs',
            'drinkware' => 'Home & Garden > Kitchen & Dining > Tableware > Drinkware',
            'mugs' => 'Home & Garden > Kitchen & Dining > Tableware > Drinkware > Mugs',

            // Fitness
            'fitness' => 'Sporting Goods > Exercise & Fitness',
            'sports' => 'Sporting Goods',

            // Fashion
            'fashion' => 'Apparel & Accessories',
            'clothing' => 'Apparel & Accessories > Clothing',
            'accessories' => 'Apparel & Accessories > Clothing Accessories',

            // Health Supplements (Ayurvexa)
            'liver care' => 'Health & Beauty > Health Care',
            'skin care' => 'Health & Beauty > Personal Care > Cosmetics > Skin Care',
            'healthy heart' => 'Health & Beauty > Health Care',
            'general wellness' => 'Health & Beauty > Health Care',
            'high on energy' => 'Health & Beauty > Health Care',
            'combos' => 'Health & Beauty > Health Care',

            // Beauty & Personal Care (Natually)
            'body care' => 'Health & Beauty > Personal Care > Cosmetics > Skin Care',
            'hair care' => 'Health & Beauty > Personal Care > Hair Care',
            'lip care' => 'Health & Beauty > Personal Care > Cosmetics',
            'facial kits' => 'Health & Beauty > Personal Care > Cosmetics > Skin Care',
            'combos & kits' => 'Health & Beauty > Personal Care',
            'waxing & hair removal' => 'Health & Beauty > Personal Care > Hair Care > Hair Removal',
        ];

        // Try exact match first
        if (isset($map[$name])) return $map[$name];

        // Try partial match
        foreach ($map as $key => $value) {
            if (str_contains($name, $key) || str_contains($key, $name)) {
                return $value;
            }
        }

        // Keyword-based fallback for subcategories
        $keywords = [
            'hair' => 'Health & Beauty > Personal Care > Hair Care',
            'skin' => 'Health & Beauty > Personal Care > Cosmetics > Skin Care',
            'face' => 'Health & Beauty > Personal Care > Cosmetics > Skin Care',
            'body' => 'Health & Beauty > Personal Care > Cosmetics > Skin Care',
            'lip' => 'Health & Beauty > Personal Care > Cosmetics',
            'eye' => 'Health & Beauty > Personal Care > Cosmetics > Skin Care',
            'nail' => 'Health & Beauty > Personal Care > Cosmetics > Nail Care',
            'soap' => 'Health & Beauty > Personal Care > Cosmetics > Bath & Body',
            'bath' => 'Health & Beauty > Personal Care > Cosmetics > Bath & Body',
            'lotion' => 'Health & Beauty > Personal Care > Cosmetics > Skin Care',
            'cream' => 'Health & Beauty > Personal Care > Cosmetics > Skin Care',
            'serum' => 'Health & Beauty > Personal Care > Cosmetics > Skin Care',
            'mask' => 'Health & Beauty > Personal Care > Cosmetics > Skin Care',
            'wax' => 'Health & Beauty > Personal Care > Hair Care > Hair Removal',
            'sunscreen' => 'Health & Beauty > Personal Care > Cosmetics > Skin Care > Sunscreen',
            'deodorant' => 'Health & Beauty > Personal Care > Cosmetics > Bath & Body > Deodorant',
            'toner' => 'Health & Beauty > Personal Care > Cosmetics > Skin Care',
            'cleanser' => 'Health & Beauty > Personal Care > Cosmetics > Skin Care',
            'wash' => 'Health & Beauty > Personal Care > Cosmetics > Skin Care',
            'scrub' => 'Health & Beauty > Personal Care > Cosmetics > Skin Care',
            'color' => 'Health & Beauty > Personal Care > Hair Care > Hair Color',
            'styling' => 'Health & Beauty > Personal Care > Hair Care > Hair Styling',
            'foot' => 'Health & Beauty > Personal Care',
            'bridal' => 'Health & Beauty > Personal Care > Cosmetics > Skin Care',
            'facial' => 'Health & Beauty > Personal Care > Cosmetics > Skin Care',
            'glow' => 'Health & Beauty > Personal Care > Cosmetics > Skin Care',
            'whiten' => 'Health & Beauty > Personal Care > Cosmetics > Skin Care',
            'kit' => 'Health & Beauty > Personal Care',
            'gel' => 'Health & Beauty > Personal Care',
            'supplement' => 'Health & Beauty > Health Care',
            'vitamin' => 'Health & Beauty > Health Care',
            'capsule' => 'Health & Beauty > Health Care',
            'tablet' => 'Health & Beauty > Health Care',
            'fan' => 'Home & Garden > Household Appliances',
            'light' => 'Home & Garden > Lighting',
            'lamp' => 'Home & Garden > Lighting',
            'speaker' => 'Electronics > Audio',
            'watch' => 'Apparel & Accessories > Jewelry > Watches',
            'phone' => 'Electronics > Communications > Telephony',
            'charger' => 'Electronics > Electronics Accessories',
            'cable' => 'Electronics > Electronics Accessories',
            'clean' => 'Home & Garden > Household Supplies',
            'organizer' => 'Home & Garden > Storage & Organization',
        ];

        foreach ($keywords as $kw => $googleCat) {
            if (str_contains($name, $kw)) {
                return $googleCat;
            }
        }

        // Ultimate fallback
        return 'Home & Garden';
    }
}
