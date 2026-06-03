<?php

namespace App\Console\Commands;

use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RefreshInstagramToken extends Command
{
    protected $signature = 'instagram:refresh-token';
    protected $description = 'Refresh the long-lived Instagram access token (valid 60 days, refresh every 50 days)';

    public function handle(): int
    {
        // Try DB setting first, fall back to .env
        $currentToken = Setting::get('instagram_access_token') ?: config('services.instagram.access_token');

        if (empty($currentToken)) {
            $this->error('No Instagram access token found in DB or .env');
            return self::FAILURE;
        }

        try {
            $response = Http::timeout(15)->get('https://graph.instagram.com/refresh_access_token', [
                'grant_type' => 'ig_refresh_token',
                'access_token' => $currentToken,
            ]);

            if ($response->failed()) {
                $body = $response->json();
                $error = $body['error']['message'] ?? $response->body();
                $this->error("Token refresh failed: {$error}");
                Log::error('Instagram token refresh failed', ['response' => $response->body()]);
                return self::FAILURE;
            }

            $data = $response->json();
            $newToken = $data['access_token'] ?? null;
            $expiresIn = $data['expires_in'] ?? null;

            if (empty($newToken)) {
                $this->error('No access_token in response');
                return self::FAILURE;
            }

            // Store in DB so it persists without touching .env
            Setting::set('instagram_access_token', $newToken, 'string', 'instagram');
            Setting::set('instagram_token_last_refresh', now()->toDateTimeString(), 'string', 'instagram');

            $days = $expiresIn ? round($expiresIn / 86400) : '?';
            $this->info("Instagram token refreshed successfully. Expires in {$days} days.");
            Log::info("Instagram access token refreshed. Expires in {$days} days.");

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Exception: ' . $e->getMessage());
            Log::error('Instagram token refresh exception: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
