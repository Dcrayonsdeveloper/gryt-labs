<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

class WebPushService
{
    /**
     * Send push notification to a specific user.
     */
    public function sendToUser(int $userId, array $payload): int
    {
        $subscriptions = PushSubscription::where('user_id', $userId)->get();

        return $this->sendToSubscriptions($subscriptions, $payload);
    }

    /**
     * Send push notification to all subscribers.
     */
    public function sendToAll(array $payload): int
    {
        $subscriptions = PushSubscription::all();

        return $this->sendToSubscriptions($subscriptions, $payload);
    }

    /**
     * Send to specific subscriptions using Web Push protocol.
     */
    private function sendToSubscriptions($subscriptions, array $payload): int
    {
        $vapidPublicKey = Setting::get('vapid_public_key', config('services.webpush.public_key', ''));
        $vapidPrivateKey = Setting::get('vapid_private_key', config('services.webpush.private_key', ''));

        if (empty($vapidPublicKey) || empty($vapidPrivateKey)) {
            Log::warning('VAPID keys not configured for web push');
            return 0;
        }

        $sent = 0;
        $jsonPayload = json_encode($payload);

        foreach ($subscriptions as $subscription) {
            try {
                $headers = $this->buildVapidHeaders($subscription->endpoint, $vapidPublicKey, $vapidPrivateKey);

                $response = \Illuminate\Support\Facades\Http::withHeaders($headers)
                    ->withBody($this->encrypt($jsonPayload, $subscription->p256dh, $subscription->auth), 'application/octet-stream')
                    ->timeout(10)
                    ->post($subscription->endpoint);

                if ($response->status() === 201 || $response->status() === 200) {
                    $sent++;
                } elseif ($response->status() === 410 || $response->status() === 404) {
                    // Subscription expired, remove it
                    $subscription->delete();
                }
            } catch (\Exception $e) {
                Log::debug('Push send failed: ' . $e->getMessage());
            }
        }

        return $sent;
    }

    /**
     * Build VAPID authorization headers.
     * Note: For production, use the web-push PHP library (minishlink/web-push).
     * This is a simplified implementation.
     */
    private function buildVapidHeaders(string $endpoint, string $publicKey, string $privateKey): array
    {
        // Simplified — in production, install minishlink/web-push for proper VAPID
        return [
            'TTL' => '86400',
            'Content-Encoding' => 'aesgcm',
            'Urgency' => 'normal',
        ];
    }

    /**
     * Encrypt push payload.
     * Note: For production, use minishlink/web-push library.
     */
    private function encrypt(string $payload, string $p256dh, string $auth): string
    {
        // Simplified — returns raw payload. Install minishlink/web-push for proper encryption.
        return $payload;
    }

    /**
     * Get VAPID public key for client-side subscription.
     */
    public static function getVapidPublicKey(): string
    {
        return Setting::get('vapid_public_key', config('services.webpush.public_key', ''));
    }
}
