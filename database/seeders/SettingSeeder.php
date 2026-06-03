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
            ['group' => 'general', 'key' => 'site_name', 'value' => 'Jikra', 'type' => 'string'],
            ['group' => 'general', 'key' => 'site_tagline', 'value' => 'Your Go-To Store for Mobile Accessories', 'type' => 'string'],
            ['group' => 'general', 'key' => 'site_email', 'value' => 'support@jikra.in', 'type' => 'string'],
            ['group' => 'general', 'key' => 'site_phone', 'value' => '+91 98765 43210', 'type' => 'string'],
            ['group' => 'general', 'key' => 'site_address', 'value' => 'Mumbai, Maharashtra, India', 'type' => 'string'],
            ['group' => 'general', 'key' => 'timezone', 'value' => 'Asia/Kolkata', 'type' => 'string'],
            ['group' => 'general', 'key' => 'date_format', 'value' => 'M d, Y', 'type' => 'string'],
            ['group' => 'general', 'key' => 'currency', 'value' => 'INR', 'type' => 'string'],
            ['group' => 'general', 'key' => 'currency_symbol', 'value' => '₹', 'type' => 'string'],
            ['group' => 'general', 'key' => 'currency_position', 'value' => 'before', 'type' => 'string'],
            ['group' => 'general', 'key' => 'announcement_text', 'value' => 'Free Shipping on Orders Above ₹499 | COD Available | 7-Day Easy Returns', 'type' => 'string'],
            ['group' => 'general', 'key' => 'footer_about', 'value' => 'Your one-stop shop for mobile accessories, Bluetooth speakers, earphones, chargers, and more. Quality tech accessories at great prices.', 'type' => 'string'],

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
            ['group' => 'email', 'key' => 'mail_from_address', 'value' => 'noreply@jikra.in', 'type' => 'string'],
            ['group' => 'email', 'key' => 'mail_from_name', 'value' => 'Jikra', 'type' => 'string'],

            // SEO Settings
            ['group' => 'seo', 'key' => 'meta_title', 'value' => 'Jikra - Your Go-To Store for Mobile Accessories', 'type' => 'string'],
            ['group' => 'seo', 'key' => 'meta_description', 'value' => 'Shop mobile accessories, Bluetooth speakers, earphones, chargers, power banks, phone cases and more at Jikra. Best prices with fast shipping across India.', 'type' => 'string'],
            ['group' => 'seo', 'key' => 'meta_keywords', 'value' => 'mobile accessories, bluetooth speakers, earphones, phone cases, chargers, power banks, headphones, smartwatch, India', 'type' => 'string'],

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
