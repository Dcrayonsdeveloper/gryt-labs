<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class BusinessSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // Contact Information
            ['group' => 'contact', 'key' => 'contact_phone', 'value' => '+91 93545 67705', 'type' => 'string'],
            ['group' => 'contact', 'key' => 'contact_email', 'value' => 'support@jikra.in', 'type' => 'string'],
            ['group' => 'contact', 'key' => 'contact_whatsapp', 'value' => '919354567705', 'type' => 'string'],
            ['group' => 'contact', 'key' => 'company_address', 'value' => 'Jikra, G118 Deep Vihar, Rohini Sector 24, Delhi 110084', 'type' => 'string'],
            ['group' => 'contact', 'key' => 'business_hours', 'value' => 'Mon-Fri 9AM-6PM, Sat 10AM-4PM', 'type' => 'string'],

            // Shipping
            ['group' => 'shipping', 'key' => 'free_shipping_threshold', 'value' => '499', 'type' => 'integer'],
            ['group' => 'shipping', 'key' => 'standard_delivery_days', 'value' => '3-7', 'type' => 'string'],
            ['group' => 'shipping', 'key' => 'express_delivery_days', 'value' => '2-3', 'type' => 'string'],
            ['group' => 'shipping', 'key' => 'express_shipping_cost', 'value' => '99', 'type' => 'integer'],
            ['group' => 'shipping', 'key' => 'sameday_shipping_cost', 'value' => '149', 'type' => 'integer'],
            ['group' => 'shipping', 'key' => 'sameday_cutoff_hour', 'value' => '12', 'type' => 'integer'],
            ['group' => 'shipping', 'key' => 'dispatch_time', 'value' => '24 hours', 'type' => 'string'],
            ['group' => 'shipping', 'key' => 'below_threshold_shipping_cost', 'value' => '49', 'type' => 'integer'],
            ['group' => 'shipping', 'key' => 'delivery_attempts', 'value' => '3', 'type' => 'integer'],
            ['group' => 'shipping', 'key' => 'remote_area_extra_days', 'value' => '1-2', 'type' => 'string'],

            // COD / Payment
            ['group' => 'payment', 'key' => 'cod_advance_amount', 'value' => '100', 'type' => 'integer'],
            ['group' => 'payment', 'key' => 'cod_max_order_value', 'value' => '5000', 'type' => 'integer'],

            // Returns
            ['group' => 'returns', 'key' => 'return_policy_days', 'value' => '7', 'type' => 'integer'],
            ['group' => 'returns', 'key' => 'refund_processing_days', 'value' => '5-7', 'type' => 'string'],
            ['group' => 'returns', 'key' => 'damage_report_hours', 'value' => '48', 'type' => 'integer'],
            ['group' => 'returns', 'key' => 'damage_claim_days', 'value' => '7', 'type' => 'integer'],

            // Support
            ['group' => 'support', 'key' => 'email_response_time', 'value' => '24 hours', 'type' => 'string'],

            // Promotions
            ['group' => 'promotions', 'key' => 'video_review_reward', 'value' => '100', 'type' => 'integer'],
            ['group' => 'promotions', 'key' => 'video_review_processing_hours', 'value' => '48', 'type' => 'integer'],
        ];

        foreach ($settings as $setting) {
            Setting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
