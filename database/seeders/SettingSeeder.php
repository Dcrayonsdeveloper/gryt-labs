<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // General Settings
            ['group' => 'general', 'key' => 'site_name', 'value' => 'GRYT Health Labs', 'type' => 'string'],
            ['group' => 'general', 'key' => 'site_tagline', 'value' => 'Built Through Purpose. Driven By Grit.', 'type' => 'string'],
            ['group' => 'general', 'key' => 'site_email', 'value' => 'info@gryt.co.in', 'type' => 'string'],
            ['group' => 'general', 'key' => 'site_phone', 'value' => '+91 70335864766', 'type' => 'string'],
            ['group' => 'general', 'key' => 'site_address', 'value' => 'Mumbai, Maharashtra, India', 'type' => 'string'],
            ['group' => 'general', 'key' => 'timezone', 'value' => 'Asia/Kolkata', 'type' => 'string'],
            ['group' => 'general', 'key' => 'date_format', 'value' => 'M d, Y', 'type' => 'string'],
            ['group' => 'general', 'key' => 'currency', 'value' => 'INR', 'type' => 'string'],
            ['group' => 'general', 'key' => 'currency_symbol', 'value' => '₹', 'type' => 'string'],
            ['group' => 'general', 'key' => 'currency_position', 'value' => 'before', 'type' => 'string'],
            ['group' => 'general', 'key' => 'announcement_text', 'value' => 'Free Shipping on Orders Above ₹499 | COD Available | 7-Day Easy Returns', 'type' => 'string'],
            ['group' => 'general', 'key' => 'footer_about', 'value' => 'Premium sports nutrition from GRYT Health Labs — whey protein, pre-workout and training essentials, made for people who show up every day. Built Through Purpose. Driven By Grit.', 'type' => 'string'],

            // Payment Settings
            ['group' => 'payment', 'key' => 'stripe_enabled', 'value' => '0', 'type' => 'boolean'],
            ['group' => 'payment', 'key' => 'paypal_enabled', 'value' => '0', 'type' => 'boolean'],
            ['group' => 'payment', 'key' => 'paypal_mode', 'value' => 'sandbox', 'type' => 'string'],
            ['group' => 'payment', 'key' => 'cod_enabled', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'payment', 'key' => 'upi_enabled', 'value' => '1', 'type' => 'boolean'],

            // Shipping Settings
            ['group' => 'shipping', 'key' => 'free_shipping_enabled', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'shipping', 'key' => 'free_shipping_threshold', 'value' => '499', 'type' => 'integer'],
            ['group' => 'shipping', 'key' => 'flat_rate_enabled', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'shipping', 'key' => 'flat_rate_amount', 'value' => '49', 'type' => 'string'],
            ['group' => 'shipping', 'key' => 'local_pickup_enabled', 'value' => '0', 'type' => 'boolean'],
            ['group' => 'shipping', 'key' => 'shipping_origin_country', 'value' => 'IN', 'type' => 'string'],

            // Tax Settings
            ['group' => 'tax', 'key' => 'tax_enabled', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'tax', 'key' => 'tax_calculation', 'value' => 'inclusive', 'type' => 'string'],
            ['group' => 'tax', 'key' => 'tax_based_on', 'value' => 'shipping', 'type' => 'string'],
            ['group' => 'tax', 'key' => 'tax_display_cart', 'value' => 'including', 'type' => 'string'],

            // Email Settings
            ['group' => 'email', 'key' => 'mail_driver', 'value' => 'smtp', 'type' => 'string'],
            ['group' => 'email', 'key' => 'mail_from_address', 'value' => 'noreply@gryt.co.in', 'type' => 'string'],
            ['group' => 'email', 'key' => 'mail_from_name', 'value' => 'GRYT Health Labs', 'type' => 'string'],

            // SEO Settings
            ['group' => 'seo', 'key' => 'meta_title', 'value' => 'GRYT Health Labs - Whey Protein, Pre-Workout & Sports Nutrition', 'type' => 'string'],
            ['group' => 'seo', 'key' => 'meta_description', 'value' => 'Shop GRYT Health Labs sports nutrition — whey protein concentrate, pre-workout and premium shakers. Built Through Purpose. Driven By Grit. Fast shipping across India.', 'type' => 'string'],
            ['group' => 'seo', 'key' => 'meta_keywords', 'value' => 'whey protein, pre-workout, sports nutrition, protein supplements, protein shaker, gym supplements, fitness nutrition, India', 'type' => 'string'],

            // Social
            ['group' => 'social', 'key' => 'social_facebook', 'value' => '#', 'type' => 'string'],
            ['group' => 'social', 'key' => 'social_instagram', 'value' => '#', 'type' => 'string'],
            ['group' => 'social', 'key' => 'social_twitter', 'value' => '#', 'type' => 'string'],
            ['group' => 'social', 'key' => 'social_youtube', 'value' => '#', 'type' => 'string'],
        ];

        foreach ($settings as $settingData) {
            Setting::updateOrCreate(
                ['group' => $settingData['group'], 'key' => $settingData['key']],
                $settingData
            );
        }
    }
}
