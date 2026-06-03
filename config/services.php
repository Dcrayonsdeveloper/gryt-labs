<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Razorpay: credentials stored per-tenant in settings table (not .env)
    // Use Setting::get('razorpay_key_id'), Setting::get('razorpay_key_secret'), etc.

    'anthropic' => [
        'key'   => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-haiku-4-5-20251001'),
    ],

    'ga4' => [
        'measurement_id' => env('GA4_MEASUREMENT_ID'),
        'api_secret' => env('GA4_API_SECRET'),
    ],

    'facebook' => [
        'pixel_id' => env('FB_PIXEL_ID'),
        'access_token' => env('FB_ACCESS_TOKEN'),
        'test_event_code' => env('FB_TEST_EVENT_CODE'),
        'client_id' => env('FACEBOOK_CLIENT_ID', env('META_APP_ID')),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET', env('META_APP_SECRET')),
        'redirect' => env('FACEBOOK_REDIRECT_URL', '/auth/facebook/callback'),
    ],

    'google_ads' => [
        'conversion_id' => env('GOOGLE_ADS_CONVERSION_ID'),
    ],

    'meta' => [
        'app_id'                   => env('META_APP_ID'),
        'app_secret'               => env('META_APP_SECRET'),
        'page_access_token'        => env('META_PAGE_ACCESS_TOKEN'),
        'verify_token'             => env('META_VERIFY_TOKEN'),
        'whatsapp_phone_number_id' => env('META_WHATSAPP_PHONE_NUMBER_ID'),
    ],

    'whatsapp' => [
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'business_id'     => env('WHATSAPP_BUSINESS_ID'),
        'token'           => env('WHATSAPP_API_TOKEN'),
        'verify_token'    => env('WHATSAPP_VERIFY_TOKEN'),
    ],

    'instagram' => [
        'handle' => env('INSTAGRAM_HANDLE'),
        'user_id' => env('INSTAGRAM_USER_ID'),
        'access_token' => env('INSTAGRAM_ACCESS_TOKEN'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URL', '/auth/google/callback'),
    ],

    'delhivery' => [
        'token' => env('DELHIVERY_API_TOKEN'),
    ],

    'webpush' => [
        'public_key' => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
    ],

];
