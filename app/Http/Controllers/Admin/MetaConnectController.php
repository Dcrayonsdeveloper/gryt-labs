<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaConnectController extends Controller
{
    /**
     * Marketing Hub dashboard - shows connected accounts & actions.
     */
    public function index()
    {
        $connections = [
            'facebook' => [
                'connected' => (bool) Setting::get('meta_fb_page_token'),
                'name' => Setting::get('meta_fb_page_name', ''),
                'page_id' => Setting::get('meta_fb_page_id', ''),
            ],
            'instagram' => [
                'connected' => (bool) Setting::get('instagram_access_token', config('services.instagram.access_token')),
                'handle' => Setting::get('instagram_handle', config('services.instagram.handle')),
                'user_id' => Setting::get('instagram_user_id', config('services.instagram.user_id')),
            ],
            'whatsapp' => [
                'connected' => (bool) Setting::get('meta_wa_phone_id'),
                'phone' => Setting::get('meta_wa_phone_display', ''),
                'phone_id' => Setting::get('meta_wa_phone_id', ''),
            ],
            'ads' => [
                'connected' => (bool) Setting::get('meta_ad_account_id'),
                'account_id' => Setting::get('meta_ad_account_id', ''),
                'account_name' => Setting::get('meta_ad_account_name', ''),
            ],
        ];

        return view('admin.marketing.hub', compact('connections'));
    }

    /**
     * Redirect admin to Meta OAuth login.
     */
    public function redirect(Request $request)
    {
        $appId = config('services.meta.app_id');

        $scopes = implode(',', [
            'pages_show_list',
            'pages_read_engagement',
            'instagram_basic',
            'instagram_manage_comments',
            'read_insights',
        ]);

        $redirectUri = route('admin.marketing.meta.callback');
        $state = csrf_token();

        session(['meta_oauth_state' => $state]);

        $url = "https://www.facebook.com/dialog/oauth?" . http_build_query([
            'client_id' => $appId,
            'redirect_uri' => $redirectUri,
            'scope' => $scopes,
            'state' => $state,
            'response_type' => 'code',
        ]);

        return redirect($url);
    }

    /**
     * Handle the OAuth callback from Meta.
     */
    public function callback(Request $request)
    {
        if ($request->has('error')) {
            return redirect()->route('admin.marketing.hub')
                ->with('error', 'Meta login cancelled: ' . $request->input('error_description', 'Unknown error'));
        }

        $state = $request->input('state');
        if ($state !== session('meta_oauth_state')) {
            return redirect()->route('admin.marketing.hub')
                ->with('error', 'Invalid state parameter. Please try again.');
        }

        $code = $request->input('code');
        if (!$code) {
            return redirect()->route('admin.marketing.hub')
                ->with('error', 'No authorization code received.');
        }

        $appId = config('services.meta.app_id');
        $appSecret = config('services.meta.app_secret', env('META_APP_SECRET'));

        // Exchange code for short-lived token
        $response = Http::get('https://graph.facebook.com/v19.0/oauth/access_token', [
            'client_id' => $appId,
            'redirect_uri' => route('admin.marketing.meta.callback'),
            'client_secret' => $appSecret,
            'code' => $code,
        ]);

        if (!$response->successful()) {
            Log::error('Meta OAuth token exchange failed', ['response' => $response->json()]);
            return redirect()->route('admin.marketing.hub')
                ->with('error', 'Failed to exchange token: ' . ($response->json('error.message') ?? 'Unknown'));
        }

        $shortToken = $response->json('access_token');

        // Exchange for long-lived token (60 days)
        $longResponse = Http::get('https://graph.facebook.com/v19.0/oauth/access_token', [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $appId,
            'client_secret' => $appSecret,
            'fb_exchange_token' => $shortToken,
        ]);

        $accessToken = $longResponse->successful()
            ? $longResponse->json('access_token')
            : $shortToken;

        $expiresIn = $longResponse->json('expires_in', 5184000); // default 60 days

        // Store the user token
        Setting::set('meta_user_token', $accessToken);
        Setting::set('meta_token_expires_at', now()->addSeconds($expiresIn)->toIso8601String());

        // Fetch connected accounts
        $this->fetchConnectedAccounts($accessToken);

        return redirect()->route('admin.marketing.hub')
            ->with('success', 'Meta account connected successfully! All permissions granted.');
    }

    /**
     * Disconnect Meta account.
     */
    public function disconnect()
    {
        $keys = [
            'meta_user_token', 'meta_token_expires_at',
            'meta_fb_page_id', 'meta_fb_page_name', 'meta_fb_page_token',
            'meta_ig_account_id', 'meta_ig_username',
            'meta_wa_phone_id', 'meta_wa_phone_display', 'meta_wa_business_id',
            'meta_ad_account_id', 'meta_ad_account_name',
        ];

        foreach ($keys as $key) {
            Setting::where('key', $key)->delete();
        }

        return redirect()->route('admin.marketing.hub')
            ->with('success', 'Meta account disconnected.');
    }

    /**
     * Fetch all connected FB pages, Instagram accounts, WhatsApp, and Ad accounts.
     */
    private function fetchConnectedAccounts(string $token): void
    {
        try {
            // Get user info
            $me = Http::get("https://graph.facebook.com/v19.0/me", [
                'access_token' => $token,
                'fields' => 'id,name',
            ])->json();

            // Facebook Pages
            $pages = Http::get("https://graph.facebook.com/v19.0/me/accounts", [
                'access_token' => $token,
                'fields' => 'id,name,access_token,instagram_business_account{id,username}',
            ])->json();

            if (!empty($pages['data'][0])) {
                $page = $pages['data'][0];
                Setting::set('meta_fb_page_id', $page['id']);
                Setting::set('meta_fb_page_name', $page['name']);
                Setting::set('meta_fb_page_token', $page['access_token']);

                // Instagram Business Account from page
                if (!empty($page['instagram_business_account'])) {
                    $ig = $page['instagram_business_account'];
                    Setting::set('meta_ig_account_id', $ig['id']);
                    Setting::set('meta_ig_username', $ig['username'] ?? '');
                }
            }

            // WhatsApp Business Accounts
            $businesses = Http::get("https://graph.facebook.com/v19.0/me/businesses", [
                'access_token' => $token,
                'fields' => 'id,name',
            ])->json();

            if (!empty($businesses['data'][0])) {
                $bizId = $businesses['data'][0]['id'];
                Setting::set('meta_wa_business_id', $bizId);

                $waPhones = Http::get("https://graph.facebook.com/v19.0/{$bizId}/phone_numbers", [
                    'access_token' => $token,
                    'fields' => 'id,display_phone_number,verified_name',
                ])->json();

                if (!empty($waPhones['data'][0])) {
                    Setting::set('meta_wa_phone_id', $waPhones['data'][0]['id']);
                    Setting::set('meta_wa_phone_display', $waPhones['data'][0]['display_phone_number'] ?? '');
                }
            }

            // Ad Accounts
            $adAccounts = Http::get("https://graph.facebook.com/v19.0/me/adaccounts", [
                'access_token' => $token,
                'fields' => 'id,name,account_status',
            ])->json();

            if (!empty($adAccounts['data'][0])) {
                Setting::set('meta_ad_account_id', $adAccounts['data'][0]['id']);
                Setting::set('meta_ad_account_name', $adAccounts['data'][0]['name'] ?? '');
            }
        } catch (\Exception $e) {
            Log::error('Meta fetch connected accounts failed', ['error' => $e->getMessage()]);
        }
    }
}
