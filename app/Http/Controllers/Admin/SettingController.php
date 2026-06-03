<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\ShiprocketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function general(): View
    {
        $settings = Setting::whereIn('group', ['general', 'store'])->pluck('value', 'key');

        return view('admin.settings.general', compact('settings'));
    }

    public function updateGeneral(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'site_name' => 'required|string|max:255',
            'site_tagline' => 'nullable|string|max:255',
            'site_email' => 'required|email',
            'site_phone' => 'nullable|string|max:20',
            'site_address' => 'nullable|string|max:500',
            'timezone' => 'required|string',
            'date_format' => 'required|string',
            'currency' => 'required|string|size:3',
            'currency_symbol' => 'required|string|max:5',
            'currency_position' => 'required|in:before,after',
        ]);

        foreach ($validated as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => 'general']
            );
            Cache::forget("setting.{$key}");
        }
        Cache::forget('currency_config');

        return back()->with('success', 'General settings updated successfully.');
    }

    public function payment(): View
    {
        $settings = Setting::where('group', 'payment')->pluck('value', 'key');

        return view('admin.settings.payment', compact('settings'));
    }

    public function updatePayment(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'razorpay_key_id'         => 'nullable|string|max:255',
            'razorpay_key_secret'     => 'nullable|string|max:255',
            'razorpay_webhook_secret' => 'nullable|string|max:255',
            'razorpay_mode'           => 'nullable|in:test,live',
            'cashfree_app_id'         => 'nullable|string|max:255',
            'cashfree_secret_key'     => 'nullable|string|max:255',
            'cashfree_webhook_secret' => 'nullable|string|max:255',
            'cashfree_mode'           => 'nullable|in:sandbox,production',
            'cod_instructions'        => 'nullable|string|max:1000',
            'cod_advance_amount'      => 'nullable|numeric|min:0',
            'cod_minimum_amount'      => 'nullable|numeric|min:0',
        ]);

        // Boolean toggles — use request->boolean() so unchecked checkboxes save '0'
        foreach (['razorpay_enabled', 'cashfree_enabled', 'cod_enabled'] as $key) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $request->boolean($key) ? '1' : '0', 'group' => 'payment']
            );
        }

        // Credential / text fields — skip blank secrets to keep existing value
        $secretFields = ['razorpay_key_secret', 'razorpay_webhook_secret', 'cashfree_secret_key', 'cashfree_webhook_secret'];
        foreach ($validated as $key => $value) {
            if (in_array($key, $secretFields) && empty($value)) {
                continue; // Keep existing secret
            }
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value ?? '', 'group' => 'payment']
            );
        }

        $store = Cache::store(config('cache.default'));
        $dbName = DB::connection()->getDatabaseName();
        $store->forget('settings.all.' . $dbName);

        return back()->with('success', 'Payment settings updated successfully.');
    }

    public function shipping(): View
    {
        $settings = Setting::where('group', 'shipping')->pluck('value', 'key');

        return view('admin.settings.shipping', compact('settings'));
    }

    public function updateShipping(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'free_shipping_enabled' => 'boolean',
            'free_shipping_threshold' => 'nullable|numeric|min:0',
            'flat_rate_enabled' => 'boolean',
            'flat_rate_amount' => 'nullable|numeric|min:0',
            'local_pickup_enabled' => 'boolean',
            'local_pickup_address' => 'nullable|string|max:500',
            'shipping_origin_country' => 'required|string|size:2',
            'shipping_origin_state' => 'nullable|string',
            'shipping_origin_zip' => 'nullable|string|max:20',
            // Default provider
            'shipping_default_provider' => 'nullable|string|in:,delhivery,bluedart',
            // Delhivery
            'delhivery_enabled' => 'boolean',
            'delhivery_api_token' => 'nullable|string|max:500',
            'delhivery_origin_pin' => 'nullable|string|max:10',
            'delhivery_pickup_location' => 'nullable|string|max:100',
            'delhivery_return_phone' => 'nullable|string|max:20',
            'delhivery_return_address' => 'nullable|string|max:500',
            // BlueDart
            'bluedart_enabled' => 'boolean',
            'bluedart_api_token' => 'nullable|string|max:500',
            'bluedart_login_id' => 'nullable|string|max:100',
            'bluedart_licence_key' => 'nullable|string|max:500',
            'bluedart_customer_code' => 'nullable|string|max:50',
            'bluedart_origin_area' => 'nullable|string|max:10',
            'bluedart_origin_pin' => 'nullable|string|max:10',
            'bluedart_return_phone' => 'nullable|string|max:20',
            'bluedart_return_address' => 'nullable|string|max:500',
            'bluedart_mode' => 'nullable|string|in:sandbox,live',
            'bluedart_product_type' => 'nullable|string|in:D,A,E',
            'bluedart_sub_product_type' => 'nullable|string|in:P,C',
            // Shiprocket Checkout
            'shiprocket_checkout_enabled' => 'boolean',
            'shiprocket_checkout_api_key' => 'nullable|string|max:255',
            'shiprocket_checkout_api_secret' => 'nullable|string|max:255',
            'shiprocket_checkout_webhook_header_key' => 'nullable|string|max:100',
            'shiprocket_checkout_webhook_header_value' => 'nullable|string|max:255',
            'shiprocket_enabled' => 'boolean',
            'shiprocket_email' => 'nullable|email|max:255',
            'shiprocket_password' => 'nullable|string|max:255',
            'shiprocket_pickup_location' => 'nullable|string|max:100',
            'shiprocket_pickup_pincode' => 'nullable|string|max:10',
            'default_package_length'   => 'nullable|numeric|min:0',
            'default_package_breadth'  => 'nullable|numeric|min:0',
            'default_package_height'   => 'nullable|numeric|min:0',
            'default_package_weight'   => 'nullable|numeric|min:0',
        ]);

        // Preserve existing passwords/secrets if fields left blank
        $secretFields = [
            'shiprocket_password',
            'shiprocket_checkout_api_secret',
            'delhivery_api_token',
            'bluedart_api_token',
            'bluedart_licence_key',
        ];
        foreach ($secretFields as $field) {
            if (empty($validated[$field] ?? '')) {
                $validated[$field] = Setting::get($field, '');
            }
        }

        // Checkbox normalization
        $validated['shiprocket_checkout_enabled'] = $request->boolean('shiprocket_checkout_enabled');
        $validated['shiprocket_enabled'] = $request->boolean('shiprocket_enabled');
        $validated['delhivery_enabled'] = $request->boolean('delhivery_enabled') ? '1' : '0';
        $validated['bluedart_enabled'] = $request->boolean('bluedart_enabled') ? '1' : '0';

        foreach ($validated as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value ?? '', 'group' => 'shipping']
            );
        }

        // Bust tenant-scoped caches so new credentials take effect immediately
        Cache::forget('settings.all.' . DB::connection()->getDatabaseName());
        Cache::forget('settings.group.shipping.' . DB::connection()->getDatabaseName());
        ShiprocketService::forgetToken();

        return back()->with('success', 'Shipping settings updated successfully.');
    }

    public function tax(): View
    {
        $settings = Setting::where('group', 'tax')->pluck('value', 'key');

        return view('admin.settings.tax', compact('settings'));
    }

    public function updateTax(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tax_enabled' => 'boolean',
            'tax_calculation' => 'in:exclusive,inclusive',
            'tax_based_on' => 'in:billing,shipping,store',
            'tax_display_cart' => 'in:excluding,including',
            'tax_display_checkout' => 'in:excluding,including',
            'tax_round_at_subtotal' => 'boolean',
        ]);

        foreach ($validated as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value ?? '', 'group' => 'tax']
            );
        }

        return back()->with('success', 'Tax settings updated successfully.');
    }

    public function email(): View
    {
        $settings = Setting::where('group', 'email')->pluck('value', 'key');

        return view('admin.settings.email', compact('settings'));
    }

    public function updateEmail(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mail_driver' => 'required|in:smtp,sendmail,mailgun,ses,postmark',
            'mail_host' => 'nullable|string',
            'mail_port' => 'nullable|integer',
            'mail_username' => 'nullable|string',
            'mail_password' => 'nullable|string',
            'mail_encryption' => 'nullable|in:tls,ssl',
            'mail_from_address' => 'required|email',
            'mail_from_name' => 'required|string|max:255',
        ]);

        foreach ($validated as $key => $value) {
            if ($key === 'mail_password' && empty($value)) {
                continue; // Keep existing password
            }
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value ?? '', 'group' => 'email']
            );
        }

        $dbName = DB::connection()->getDatabaseName();
        Cache::forget('settings.all.' . $dbName);

        return back()->with('success', 'Email settings updated successfully.');
    }

    public function seo(): View
    {
        $settings = Setting::where('group', 'seo')->pluck('value', 'key');

        return view('admin.settings.seo', compact('settings'));
    }

    public function updateSeo(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'meta_title' => 'nullable|string|max:70',
            'meta_description' => 'nullable|string|max:160',
            'meta_keywords' => 'nullable|string|max:255',
            'og_image' => 'nullable|string',
            'google_analytics_id' => 'nullable|string|max:50',
            'google_tag_manager_id' => 'nullable|string|max:50',
            'google_ads_conversion_id' => 'nullable|string|max:50',
            'facebook_pixel_id' => 'nullable|string|max:50',
            'facebook_capi_token' => 'nullable|string|max:500',
            'pinterest_tag_id' => 'nullable|string|max:50',
            'tiktok_pixel_id' => 'nullable|string|max:50',
            'snapchat_pixel_id' => 'nullable|string|max:100',
            'meta_domain_verification' => 'nullable|string|max:100',
            'google_site_verification' => 'nullable|string|max:100',
            'pinterest_domain_verification' => 'nullable|string|max:100',
            'robots_txt' => 'nullable|string',
        ]);

        foreach ($validated as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value ?? '', 'group' => 'seo']
            );
        }

        return back()->with('success', 'SEO settings updated successfully.');
    }

    public function productCard(): View
    {
        $settings = Setting::whereIn('group', ['product_card', 'features', 'product', 'pdp_content', 'packs', 'marketing'])->pluck('value', 'key');

        $defaults = [
            'product_card_quick_view' => '1',
            'product_card_add_to_cart' => '1',
            'product_card_wishlist' => '1',
            'support_tickets_enabled' => '1',
            'show_product_coupons' => '1',
            'show_product_compare' => '1',
        ];

        foreach ($defaults as $key => $default) {
            if (!isset($settings[$key])) {
                $settings[$key] = $default;
            }
        }

        return view('admin.settings.product-card', compact('settings'));
    }

    public function updateProductCard(Request $request): RedirectResponse
    {
        $productCardFields = ['product_card_quick_view', 'product_card_add_to_cart', 'product_card_wishlist'];
        foreach ($productCardFields as $key) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $request->boolean($key) ? '1' : '0', 'type' => 'boolean', 'group' => 'product_card']
            );
            Cache::forget("setting.{$key}");
        }

        $featureFields = ['support_tickets_enabled'];
        foreach ($featureFields as $key) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $request->boolean($key) ? '1' : '0', 'type' => 'boolean', 'group' => 'features']
            );
            Cache::forget("setting.{$key}");
        }

        $productPageFields = ['show_product_coupons', 'show_product_compare'];
        foreach ($productPageFields as $key) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $request->boolean($key) ? '1' : '0', 'type' => 'boolean', 'group' => 'product']
            );
            Cache::forget("setting.{$key}");
        }

        // Pack settings
        $packBool = ['product_packs_enabled'];
        foreach ($packBool as $key) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $request->boolean($key) ? '1' : '0', 'type' => 'boolean', 'group' => 'packs']
            );
            Cache::forget("setting.{$key}");
        }
        $packText = ['pack_unit_label', 'pack_units_per_qty', 'pack_months_per_qty', 'pack_tiers'];
        foreach ($packText as $key) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $request->input($key, '') ?? '', 'group' => 'packs']
            );
            Cache::forget("setting.{$key}");
        }

        // Product page content fields
        $pdpContentFields = [
            'reels_section_heading', 'reels_section_subheading',
            'pdp_social_proof_text', 'pdp_deal_badge_text', 'pdp_tax_text',
            'pdp_delivery_days', 'pdp_fastest_delivery_text', 'pdp_payment_methods',
            'pdp_trust_badges', 'product_stats_carousel', 'product_benefits',
        ];
        foreach ($pdpContentFields as $key) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $request->input($key, '') ?? '', 'group' => 'pdp_content']
            );
            Cache::forget("setting.{$key}");
        }

        // Marketing & engagement fields
        $marketingBool = ['review_coupon_enabled'];
        foreach ($marketingBool as $key) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $request->boolean($key) ? '1' : '0', 'type' => 'boolean', 'group' => 'marketing']
            );
            Cache::forget("setting.{$key}");
        }
        $marketingText = [
            'exit_popup_discount',
            'exit_popup_title', 'exit_popup_description', 'exit_popup_button_text',
            'exit_popup_footer_text', 'exit_popup_success_title', 'exit_popup_success_text',
        ];
        foreach ($marketingText as $key) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $request->input($key, '') ?? '', 'group' => 'marketing']
            );
            Cache::forget("setting.{$key}");
        }

        // Clear the main settings cache (Setting::get() uses this key)
        $store = Cache::store(config('cache.default'));
        $dbName = DB::connection()->getDatabaseName();
        $store->forget('settings.all.' . $dbName);

        return back()->with('success', 'Settings updated successfully.');
    }

    public function integrations(): View
    {
        $settings = Setting::where('group', 'integrations')->pluck('value', 'key');

        return view('admin.settings.integrations', compact('settings'));
    }

    public function updateIntegrations(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'google_client_id'       => 'nullable|string|max:255',
            'google_client_secret'   => 'nullable|string|max:255',
            'facebook_client_id'     => 'nullable|string|max:255',
            'facebook_client_secret' => 'nullable|string|max:255',
        ]);

        // Boolean toggles
        foreach (['google_login_enabled', 'facebook_login_enabled'] as $key) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $request->boolean($key) ? '1' : '0', 'group' => 'integrations']
            );
        }

        // Credential fields — skip blank secrets to keep existing value
        $secretFields = ['google_client_secret', 'facebook_client_secret'];
        foreach ($validated as $key => $value) {
            if (in_array($key, $secretFields) && empty($value)) {
                continue;
            }
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value ?? '', 'group' => 'integrations']
            );
        }

        $store = Cache::store(config('cache.default'));
        $dbName = DB::connection()->getDatabaseName();
        $store->forget('settings.all.' . $dbName);

        return back()->with('success', 'Integration settings updated successfully.');
    }
}
