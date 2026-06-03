<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageSection extends Model
{
    protected $table = 'page_sections';

    protected $fillable = [
        'page', 'type', 'name', 'position', 'settings', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function scopeForPage($query, string $page)
    {
        return $query->where('page', $page)->where('is_active', true)->orderBy('position');
    }

    /**
     * Available section types with their default settings schema.
     */
    public static function availableTypes(): array
    {
        return [
            'hero_banner' => [
                'name' => 'Hero Banner',
                'icon' => 'photo',
                'defaults' => [
                    'slides' => [],
                    'autoplay' => true,
                    'interval' => 5000,
                    'show_dots' => true,
                ],
            ],
            'product_carousel' => [
                'name' => 'Product Carousel',
                'icon' => 'shopping-bag',
                'defaults' => [
                    'heading' => 'Featured Products',
                    'source' => 'bestsellers', // bestsellers, new, featured, category
                    'category_id' => null,
                    'count' => 12,
                    'show_price' => true,
                    'show_rating' => true,
                ],
            ],
            'category_grid' => [
                'name' => 'Category Grid',
                'icon' => 'grid',
                'defaults' => [
                    'heading' => 'Shop by Category',
                    'columns' => 4,
                    'category_ids' => [],
                    'style' => 'card', // card, circle, overlay
                ],
            ],
            'trust_badges' => [
                'name' => 'Trust Badges',
                'icon' => 'shield-check',
                'defaults' => [
                    'badges' => [
                        ['icon' => 'truck', 'title' => 'Free Shipping', 'text' => 'On orders above ₹499'],
                        ['icon' => 'shield', 'title' => 'Secure Payment', 'text' => '100% protected'],
                        ['icon' => 'refresh', 'title' => 'Easy Returns', 'text' => '7-day return policy'],
                        ['icon' => 'cash', 'title' => 'COD Available', 'text' => 'Pay on delivery'],
                    ],
                ],
            ],
            'testimonials' => [
                'name' => 'Testimonials',
                'icon' => 'chat-bubble',
                'defaults' => [
                    'heading' => 'What Our Customers Say',
                    'source' => 'auto', // auto (highest rated) or manual
                    'count' => 6,
                    'review_ids' => [],
                ],
            ],
            'text_with_image' => [
                'name' => 'Text + Image',
                'icon' => 'document-text',
                'defaults' => [
                    'heading' => '',
                    'body' => '',
                    'image' => '',
                    'cta_text' => '',
                    'cta_link' => '',
                    'image_position' => 'right', // left, right
                ],
            ],
            'newsletter' => [
                'name' => 'Newsletter Signup',
                'icon' => 'envelope',
                'defaults' => [
                    'heading' => 'Stay Updated',
                    'description' => 'Subscribe for exclusive deals and new arrivals.',
                    'button_text' => 'Subscribe',
                    'bg_color' => 'primary',
                ],
            ],
            'instagram_reels' => [
                'name' => 'Instagram Reels',
                'icon' => 'video-camera',
                'defaults' => [
                    'heading' => 'Shop Our Reels',
                    'count' => 8,
                ],
            ],
            'countdown_timer' => [
                'name' => 'Countdown Timer',
                'icon' => 'clock',
                'defaults' => [
                    'heading' => 'Sale Ends In',
                    'end_date' => '',
                    'bg_color' => '#CC0C39',
                    'text_color' => '#ffffff',
                    'cta_text' => 'Shop Now',
                    'cta_link' => '/products',
                ],
            ],
            'brand_logos' => [
                'name' => 'Brand Logos',
                'icon' => 'building-storefront',
                'defaults' => [
                    'heading' => 'Our Brands',
                    'brand_ids' => [],
                    'style' => 'scroll', // scroll, grid
                ],
            ],
            'custom_html' => [
                'name' => 'Custom HTML',
                'icon' => 'code-bracket',
                'defaults' => [
                    'html' => '',
                ],
            ],
        ];
    }
}
