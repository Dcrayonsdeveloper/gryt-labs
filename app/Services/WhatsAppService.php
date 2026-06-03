<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class WhatsAppService
{
    /**
     * Send a text message via the configured WhatsApp provider.
     */
    public function sendText(string $phone, string $message): bool
    {
        $provider = Setting::get('whatsapp_provider', 'meta');

        return match ($provider) {
            'fastwasms' => $this->sendViaFastWasms($phone, $message),
            default => $this->sendViaMeta($phone, $message),
        };
    }

    /**
     * Send a template message (Meta only). Non-Meta providers send fallback text instead.
     */
    public function sendTemplate(string $phone, string $templateName, array $bodyParams, string $fallbackText): bool
    {
        $provider = Setting::get('whatsapp_provider', 'meta');

        if ($provider !== 'meta') {
            return $this->sendText($phone, $fallbackText);
        }

        return $this->sendMetaTemplate($phone, $templateName, $bodyParams);
    }

    /**
     * Check if WhatsApp is configured for the current tenant.
     */
    public function isConfigured(): bool
    {
        $provider = Setting::get('whatsapp_provider', 'meta');

        if ($provider === 'fastwasms') {
            return !empty(Setting::get('whatsapp_instance_id', ''))
                && !empty(Setting::get('whatsapp_api_token', ''));
        }

        $token = Setting::get('whatsapp_api_token', '') ?: config('services.meta.page_access_token');
        $phoneId = Setting::get('whatsapp_phone_number_id', '') ?: config('services.meta.whatsapp_phone_number_id');
        return !empty($token) && !empty($phoneId);
    }

    public function cleanPhone(string $phone): string
    {
        $clean = preg_replace('/\D/', '', $phone);
        if (!str_starts_with($clean, '91') && strlen($clean) === 10) {
            $clean = '91' . $clean;
        }
        return $clean;
    }

    private function sendViaFastWasms(string $phone, string $message): bool
    {
        $instanceId = Setting::get('whatsapp_instance_id', '');
        $token = Setting::get('whatsapp_api_token', '');

        if (empty($instanceId) || empty($token)) {
            Log::warning('WhatsApp: FastWASMs not configured');
            return false;
        }

        $cleanPhone = $this->cleanPhone($phone);

        try {
            $response = Http::timeout(10)
                ->retry(2, 500)
                ->get('https://fastwasms.in/api/send', [
                    'number' => $cleanPhone,
                    'type' => 'text',
                    'message' => $message,
                    'instance_id' => $instanceId,
                    'access_token' => $token,
                ]);

            $json = $response->json();
            if ($response->successful() && ($json['status'] ?? '') === 'success') {
                return true;
            }

            // Detect the specific failure pattern FastWASMs returns when its
            // WhatsApp Web session for this instance is disconnected — their
            // server throws "Cannot read properties of undefined (reading 'id')"
            // when it can't find an active session object.
            $errorMsg = (string) ($json['message'] ?? 'unknown');
            $disconnected = str_contains($errorMsg, "Cannot read properties of undefined")
                || stripos($errorMsg, 'not connected') !== false
                || stripos($errorMsg, 'session') !== false
                || stripos($errorMsg, 'instance') !== false;

            Log::warning('WhatsApp: FastWASMs send failed' . ($disconnected ? ' [LIKELY DISCONNECTED]' : ''), [
                'phone'       => $cleanPhone,
                'instance_id' => $instanceId,
                'http_status' => $response->status(),
                'response'    => $json,
            ]);

            $this->alertAdminOnFailure(
                $disconnected
                    ? "FastWASMs instance {$instanceId} appears disconnected: {$errorMsg}"
                    : "FastWASMs returned error: {$errorMsg}",
                $cleanPhone,
                $disconnected
            );
            return false;
        } catch (\Exception $e) {
            Log::error('WhatsApp: FastWASMs exception', [
                'phone' => $cleanPhone,
                'error' => $e->getMessage(),
            ]);
            $this->alertAdminOnFailure("FastWASMs request exception: {$e->getMessage()}", $cleanPhone, false);
            return false;
        }
    }

    /**
     * Email the tenant's admin when WhatsApp sends start failing — typically
     * because the FastWASMs WhatsApp Web session has disconnected. Rate-
     * limited to once per hour per tenant so a flood of failed sends produces
     * exactly one alert, not a hundred. Returns silently if no admin email
     * is configured or the alert was already sent recently.
     */
    private function alertAdminOnFailure(string $reason, string $phone, bool $disconnected): void
    {
        $tenantId = (string) config('app.tenant_id', 'unknown');
        $cacheKey = "whatsapp_failure_alert_sent_{$tenantId}";

        if (Cache::has($cacheKey)) {
            return;
        }
        Cache::put($cacheKey, true, now()->addHour());

        $adminEmail = Setting::get('admin_email', '') ?: Setting::get('mail_from_address', '');
        if (!$adminEmail) {
            return;
        }

        $provider   = Setting::get('whatsapp_provider', 'meta');
        $instanceId = Setting::get('whatsapp_instance_id', 'n/a');
        $brand      = Setting::get('site_name', config('app.name'));

        $body = "WhatsApp send failures detected on {$brand}.\n\n"
            . "Provider: {$provider}\n"
            . "Instance / phone id: {$instanceId}\n"
            . "Reason: {$reason}\n"
            . "Recent target phone: {$phone}\n\n";

        if ($provider === 'fastwasms' && $disconnected) {
            $body .= "Likely cause: the FastWASMs WhatsApp Web session for this instance is disconnected.\n\n"
                . "How to fix right now:\n"
                . "  1. Log into the FastWASMs dashboard (https://fastwasms.in).\n"
                . "  2. Find instance {$instanceId}.\n"
                . "  3. Click Reconnect / Scan QR and re-scan with your WhatsApp number.\n"
                . "  4. Confirm the instance shows Connected.\n\n";
        } elseif ($provider === 'fastwasms') {
            $body .= "FastWASMs API returned an error. Check the FastWASMs dashboard for instance {$instanceId}.\n\n";
        } else {
            $body .= "Check the Meta Business Suite — the WhatsApp Cloud API token may have expired or been revoked.\n\n";
        }

        $body .= "This alert is rate-limited to once per hour. Customer order notifications may be silently failing until WhatsApp is reconnected.";

        try {
            Mail::raw($body, function ($m) use ($adminEmail, $brand) {
                $m->to($adminEmail)->subject("[{$brand}] WhatsApp send failures — reconnect required");
            });
        } catch (\Throwable $e) {
            Log::error('WhatsApp admin alert email failed', ['error' => $e->getMessage()]);
        }
    }

    private function sendViaMeta(string $phone, string $message): bool
    {
        $token = Setting::get('whatsapp_api_token', '') ?: config('services.meta.page_access_token');
        $phoneId = Setting::get('whatsapp_phone_number_id', '') ?: config('services.meta.whatsapp_phone_number_id');

        if (empty($token) || empty($phoneId)) {
            Log::warning('WhatsApp: Meta API not configured');
            return false;
        }

        $cleanPhone = $this->cleanPhone($phone);

        try {
            $response = Http::withToken($token)
                ->timeout(10)
                ->retry(2, 500)
                ->post("https://graph.facebook.com/v21.0/{$phoneId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to' => $cleanPhone,
                    'type' => 'text',
                    'text' => ['body' => $message],
                ]);

            if ($response->successful()) {
                return true;
            }

            Log::warning('WhatsApp: Meta send failed', [
                'phone' => $cleanPhone,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('WhatsApp: Meta exception', [
                'phone' => $cleanPhone,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    private function sendMetaTemplate(string $phone, string $templateName, array $bodyParams): bool
    {
        $token = Setting::get('whatsapp_api_token', '') ?: config('services.meta.page_access_token');
        $phoneId = Setting::get('whatsapp_phone_number_id', '') ?: config('services.meta.whatsapp_phone_number_id');

        if (empty($token) || empty($phoneId)) {
            return false;
        }

        $cleanPhone = $this->cleanPhone($phone);

        try {
            $response = Http::withToken($token)
                ->timeout(10)
                ->retry(2, 500)
                ->post("https://graph.facebook.com/v21.0/{$phoneId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to' => $cleanPhone,
                    'type' => 'template',
                    'template' => [
                        'name' => $templateName,
                        'language' => ['code' => 'en'],
                        'components' => [
                            [
                                'type' => 'body',
                                'parameters' => $bodyParams,
                            ],
                        ],
                    ],
                ]);

            if ($response->successful()) {
                return true;
            }

            Log::warning('WhatsApp: Meta template failed', [
                'phone' => $cleanPhone,
                'template' => $templateName,
                'status' => $response->status(),
                'response' => $response->json(),
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('WhatsApp: Meta template exception', [
                'phone' => $cleanPhone,
                'template' => $templateName,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
