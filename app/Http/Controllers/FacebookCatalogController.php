<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Response;

class FacebookCatalogController extends Controller
{
    /**
     * Generate an XML product feed for Facebook Commerce Manager.
     * URL: /feeds/facebook-catalog.xml
     */
    public function __invoke(): Response
    {
        $products = Product::query()
            ->where('is_active', true)
            ->with(['category', 'brand', 'primaryImage', 'images'])
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
        $xml .= "  <title>{$this->escape($appName)} Product Catalog</title>\n";
        $xml .= "  <link>{$appUrl}</link>\n";
        $xml .= "  <description>Product catalog for {$this->escape($appName)}</description>\n";

        foreach ($products as $product) {
            $xml .= $this->buildItem($product, $appUrl, $appName, $freeShipThreshold, $shippingRate, $returnDays);
        }

        $xml .= "</channel>\n";
        $xml .= '</rss>';

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    private function buildItem(Product $product, string $appUrl, string $appName, float $freeShipThreshold, float $shippingRate, string $returnDays): string
    {
        $availability = $product->isInStock() ? 'in stock' : 'out of stock';
        $link = $appUrl . '/products/' . $product->slug;
        $imageUrl = $product->primary_image_url;

        // Ensure absolute image URL
        if ($imageUrl && !str_starts_with($imageUrl, 'http')) {
            $imageUrl = $appUrl . '/' . ltrim($imageUrl, '/');
        }

        $description = $product->short_description
            ?: strip_tags(mb_substr($product->description ?? '', 0, 5000));
        if (empty(trim($description))) {
            $description = $product->name;
        }

        $item = "  <item>\n";
        $item .= "    <g:id>{$product->id}</g:id>\n";
        $item .= "    <g:title>{$this->escape($product->name)}</g:title>\n";
        $item .= "    <g:description>{$this->escape($description)}</g:description>\n";
        $item .= "    <g:link>{$link}</g:link>\n";
        $item .= "    <g:image_link>{$this->escape($imageUrl)}</g:image_link>\n";

        // Additional images
        $additionalImages = $product->images->where('is_primary', false)->take(10);
        foreach ($additionalImages as $img) {
            $imgUrl = $img->url;
            if ($imgUrl && !str_starts_with($imgUrl, 'http')) {
                $imgUrl = $appUrl . '/' . ltrim($imgUrl, '/');
            }
            $item .= "    <g:additional_image_link>{$this->escape($imgUrl)}</g:additional_image_link>\n";
        }

        $item .= "    <g:availability>{$availability}</g:availability>\n";
        $item .= "    <g:condition>new</g:condition>\n";

        // Price: use MRP as base price if discounted, otherwise use price
        if ($product->mrp && $product->price < $product->mrp) {
            $item .= "    <g:price>" . $this->fmtPrice($product->mrp) . "</g:price>\n";
            $item .= "    <g:sale_price>" . $this->fmtPrice($product->price) . "</g:sale_price>\n";
        } else {
            $item .= "    <g:price>" . $this->fmtPrice($product->price) . "</g:price>\n";
        }

        // Brand
        $brandName = $product->brand?->name ?: $appName;
        $item .= "    <g:brand>{$this->escape($brandName)}</g:brand>\n";

        if ($product->sku) {
            $item .= "    <g:mpn>{$this->escape($product->sku)}</g:mpn>\n";
        }

        if ($product->barcode) {
            $item .= "    <g:gtin>{$this->escape($product->barcode)}</g:gtin>\n";
        }

        // Identifier exists — false when no GTIN/barcode
        if (!$product->barcode) {
            $item .= "    <g:identifier_exists>false</g:identifier_exists>\n";
        }

        // Google product category (taxonomy)
        $googleCat = $product->google_product_category
            ?? $product->category?->google_product_category
            ?? $this->mapCategoryToGoogle($product->category?->name);
        if ($googleCat) {
            $item .= "    <g:google_product_category>{$this->escape($googleCat)}</g:google_product_category>\n";
        }

        // Store product type (internal category)
        if ($product->category) {
            $item .= "    <g:product_type>{$this->escape($product->category->name)}</g:product_type>\n";
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
            $item .= "    <g:custom_label_{$i}>{$this->escape($tag)}</g:custom_label_{$i}>\n";
        }

        $item .= "  </item>\n";

        return $item;
    }

    private function fmtPrice(float $price): string
    {
        return number_format($price, 2, '.', '') . ' INR';
    }

    private function escape(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /**
     * Map store category names to Google Product Taxonomy.
     * Mirrors GoogleMerchantController logic for consistency.
     */
    private function mapCategoryToGoogle(?string $categoryName): ?string
    {
        if (!$categoryName) return null;

        $name = strtolower(trim($categoryName));

        $map = [
            'home & kitchen' => 'Home & Garden > Kitchen & Dining',
            'home and kitchen' => 'Home & Garden > Kitchen & Dining',
            'kitchen' => 'Home & Garden > Kitchen & Dining',
            'home' => 'Home & Garden',
            'home decor' => 'Home & Garden > Decor',
            'electronics & gadgets' => 'Electronics',
            'electronics' => 'Electronics',
            'gadgets' => 'Electronics',
            'personal care & beauty' => 'Health & Beauty',
            'personal care' => 'Health & Beauty > Personal Care',
            'beauty' => 'Health & Beauty',
            'skincare' => 'Health & Beauty > Personal Care > Cosmetics > Skin Care',
            'car, bike & travel' => 'Vehicles & Parts',
            'car & bike' => 'Vehicles & Parts',
            'travel' => 'Luggage & Bags',
            'kids & toys' => 'Toys & Games',
            'kids, toys & stationery' => 'Toys & Games',
            'toys' => 'Toys & Games',
            'stationery' => 'Office Supplies',
            'appliances' => 'Home & Garden > Kitchen & Dining > Small Kitchen Appliances',
            'love over coffee' => 'Home & Garden > Kitchen & Dining > Tableware > Drinkware > Mugs',
            'drinkware' => 'Home & Garden > Kitchen & Dining > Tableware > Drinkware',
            'mugs' => 'Home & Garden > Kitchen & Dining > Tableware > Drinkware > Mugs',
            'fitness' => 'Sporting Goods > Exercise & Fitness',
            'sports' => 'Sporting Goods',
            'fashion' => 'Apparel & Accessories',
            'clothing' => 'Apparel & Accessories > Clothing',
            'accessories' => 'Apparel & Accessories > Clothing Accessories',
            'liver care' => 'Health & Beauty > Health Care',
            'skin care' => 'Health & Beauty > Personal Care > Cosmetics > Skin Care',
            'healthy heart' => 'Health & Beauty > Health Care',
            'general wellness' => 'Health & Beauty > Health Care',
            'high on energy' => 'Health & Beauty > Health Care',
            'combos' => 'Health & Beauty > Health Care',
            'body care' => 'Health & Beauty > Personal Care > Cosmetics > Skin Care',
            'hair care' => 'Health & Beauty > Personal Care > Hair Care',
            'lip care' => 'Health & Beauty > Personal Care > Cosmetics',
            'facial kits' => 'Health & Beauty > Personal Care > Cosmetics > Skin Care',
            'combos & kits' => 'Health & Beauty > Personal Care',
            'waxing & hair removal' => 'Health & Beauty > Personal Care > Hair Care > Hair Removal',
        ];

        if (isset($map[$name])) return $map[$name];

        foreach ($map as $key => $value) {
            if (str_contains($name, $key) || str_contains($key, $name)) {
                return $value;
            }
        }

        $keywords = [
            'hair' => 'Health & Beauty > Personal Care > Hair Care',
            'skin' => 'Health & Beauty > Personal Care > Cosmetics > Skin Care',
            'face' => 'Health & Beauty > Personal Care > Cosmetics > Skin Care',
            'body' => 'Health & Beauty > Personal Care > Cosmetics > Skin Care',
            'lip' => 'Health & Beauty > Personal Care > Cosmetics',
            'supplement' => 'Health & Beauty > Health Care',
            'vitamin' => 'Health & Beauty > Health Care',
        ];

        foreach ($keywords as $kw => $googleCat) {
            if (str_contains($name, $kw)) {
                return $googleCat;
            }
        }

        return 'Home & Garden';
    }
}
