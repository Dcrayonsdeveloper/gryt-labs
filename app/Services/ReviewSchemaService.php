<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Setting;

class ReviewSchemaService
{
    public function getProductSchema(Product $product): array
    {
        $description = strip_tags($product->short_description ?? $product->description ?? '');
        if (empty($description)) {
            $description = $product->name . ' - Available at ' . config('app.name', 'Store');
        }

        // Truncate description to avoid Google warnings (max ~5000 chars)
        $description = mb_substr($description, 0, 5000);

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'description' => $description,
            'sku' => $product->sku ?? (string) $product->id,
            'url' => route('products.show', $product),
            'productID' => (string) $product->id,
            'category' => $product->category?->name ?? '',
            'inLanguage' => config('app.locale', 'en-IN'),
        ];

        // Images (required by Google — must be present)
        $images = $product->images->pluck('url')->map(fn ($url) => str_starts_with($url, 'http') ? $url : asset('storage/' . $url))->toArray();
        if (!empty($images)) {
            $schema['image'] = $images;
        } elseif ($product->primary_image_url) {
            $schema['image'] = [$product->primary_image_url];
        }

        // Brand (recommended by Google to avoid warnings)
        if ($product->brand) {
            $schema['brand'] = [
                '@type' => 'Brand',
                'name' => $product->brand->name,
            ];
        } else {
            $schema['brand'] = [
                '@type' => 'Brand',
                'name' => config('app.name', 'Store'),
            ];
        }

        // MPN if SKU available
        if ($product->sku) {
            $schema['mpn'] = $product->sku;
        }

        // Offers (required — Google warns if missing priceValidUntil)
        $offer = [
            '@type' => 'Offer',
            'url' => route('products.show', $product),
            'priceCurrency' => Setting::get('currency', '') ?: config('app.currency', 'INR'),
            'price' => (float) number_format((float) $product->price, 2, '.', ''),
            'priceValidUntil' => now()->addMonths(6)->format('Y-m-d'),
            'itemCondition' => 'https://schema.org/NewCondition',
            'availability' => $product->isInStock()
                ? 'https://schema.org/InStock'
                : 'https://schema.org/OutOfStock',
            'seller' => [
                '@type' => 'Organization',
                'name' => config('app.name', 'Store'),
            ],
        ];

        // Shipping Details (Google Merchant requirement)
        $freeShipThreshold = (float) Setting::get('free_shipping_threshold', 499);
        $shippingCost = (float) $product->price >= $freeShipThreshold ? 0 : 49;

        $offer['shippingDetails'] = [
            '@type' => 'OfferShippingDetails',
            'shippingRate' => [
                '@type' => 'MonetaryAmount',
                'value' => $shippingCost,
                'currency' => Setting::get('currency', '') ?: config('app.currency', 'INR'),
            ],
            'shippingDestination' => [
                '@type' => 'DefinedRegion',
                'addressCountry' => Setting::get('country_code', '') ?: config('app.country_code', 'IN'),
            ],
            'deliveryTime' => [
                '@type' => 'ShippingDeliveryTime',
                'handlingTime' => [
                    '@type' => 'QuantitativeValue',
                    'minValue' => 0,
                    'maxValue' => 1,
                    'unitCode' => 'd',
                ],
                'transitTime' => [
                    '@type' => 'QuantitativeValue',
                    'minValue' => 3,
                    'maxValue' => 7,
                    'unitCode' => 'd',
                ],
            ],
        ];

        // Merchant Return Policy (Google Merchant requirement)
        $returnDays = (int) Setting::get('return_policy_days', 7);
        $offer['hasMerchantReturnPolicy'] = [
            '@type' => 'MerchantReturnPolicy',
            'applicableCountry' => Setting::get('country_code', '') ?: config('app.country_code', 'IN'),
            'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
            'merchantReturnDays' => $returnDays,
            'returnMethod' => 'https://schema.org/ReturnByMail',
            'returnFees' => 'https://schema.org/FreeReturn',
        ];

        $schema['offers'] = $offer;

        // Aggregate Rating + Reviews
        $approvedReviews = $product->approvedReviews;
        $reviewCount = $approvedReviews->count();

        if ($reviewCount > 0) {
            $avgRating = round($approvedReviews->avg('rating'), 1);

            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => $avgRating,
                'ratingCount' => $reviewCount,
                'reviewCount' => $reviewCount,
                'bestRating' => 5,
                'worstRating' => 1,
            ];

            // Include up to 10 most recent reviews with content
            // Google requires reviewBody to be present for valid review markup
            $reviewsForSchema = $approvedReviews
                ->filter(fn ($r) => !empty($r->content))
                ->sortByDesc('created_at')
                ->take(10);

            if ($reviewsForSchema->isNotEmpty()) {
                $schema['review'] = $reviewsForSchema->map(function ($review) {
                    $authorName = $review->user
                        ? trim($review->user->first_name . ' ' . strtoupper(substr($review->user->last_name ?? '', 0, 1)) . '.')
                        : ($review->guest_name ?? 'A Customer');

                    $reviewSchema = [
                        '@type' => 'Review',
                        'datePublished' => $review->created_at->format('Y-m-d'),
                        'reviewRating' => [
                            '@type' => 'Rating',
                            'ratingValue' => (int) $review->rating,
                            'bestRating' => 5,
                            'worstRating' => 1,
                        ],
                        'author' => [
                            '@type' => 'Person',
                            'name' => $authorName,
                        ],
                        'reviewBody' => $review->content,
                    ];

                    if (!empty($review->title)) {
                        $reviewSchema['name'] = $review->title;
                    }

                    return $reviewSchema;
                })->values()->toArray();
            }
        }

        return $schema;
    }

    public function getFaqSchema(Product $product): ?array
    {
        $questions = $product->questions->filter(fn ($q) => $q->is_answered && $q->answers->isNotEmpty());

        if ($questions->isEmpty()) {
            return null;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $questions->map(function ($question) {
                return [
                    '@type' => 'Question',
                    'name' => $question->question,
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => $question->answers->first()->answer,
                    ],
                ];
            })->values()->toArray(),
        ];
    }
}
