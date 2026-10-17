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
     * Why the last send failed, in words a shop owner can act on.
     *
     * Callers used to get a bare false and showed "check the template name and
     * API settings", which sent people auditing credentials that were fine. The
     * API almost always says exactly what is wrong; this carries that out.
     */
    private ?string $lastError = null;

    public function lastError(): ?string
    {
        return $this->lastError;
    }

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
     * Send an abandoned-cart recovery message.
     *
     * The admin "send reminders" action (routes/admin.php) has always called this method,
     * but it was never defined — every click threw BadMethodCallException into the
     * surrounding try/catch and was logged as "WhatsApp abandoned reminder failed", so the
     * button silently did nothing.
     *
     * Template name resolution matches SendAbandonedCartWhatsApp so both paths use the
     * same configured template.
     */
    public function sendAbandonedCartReminder(
        string $phone,
        string $name,
        float|int|string $cartTotal,
        ?string $productName = null,
        ?string $fallbackText = null,
    ): bool {
        $templateName = Setting::get('whatsapp_cart_recovery_template', '')
            ?: (config('app.tenant_id', '') . '_cart_recovery');

        $total   = (string) round((float) $cartTotal);
        $product = trim((string) $productName) ?: 'your item';

        // Must stay in step with the cart_recovery template, which renders
        // "Hi {{1}}, you left {{2}} (Rs {{3}}) in your cart." A count mismatch here
        // is rejected by Meta outright, so all three are always sent.
        $bodyParams = [
            ['type' => 'text', 'text' => $name],     // {{1}} customer name
            ['type' => 'text', 'text' => $product],  // {{2}} first product in cart
            ['type' => 'text', 'text' => $total],    // {{3}} price
        ];

        // Callers with richer per-touch copy (the reminder sequence includes discount
        // codes) pass their own fallback, which non-Meta providers send verbatim.
        $fallback = $fallbackText ?: ("Hi {$name}, you left {$product} (Rs {$total}) in your cart. "
            . 'Complete your order before they sell out: ' . url('/cart'));

        return $this->sendTemplate($phone, $templateName, $bodyParams, $fallback);
    }

    /**
     * Send the storefront offer-popup coupon to a freshly verified number.
     *
     * Meta only delivers free-form text inside the 24-hour window the customer
     * opens by messaging the business first. Someone who just received an OTP
     * has not opened one, so this has to be a template - and since the popup
     * deliberately never prints the code on screen, this message is the only
     * place the shopper ever sees it.
     */
    public function sendOfferCoupon(
        string $phone,
        string $name,
        string $code,
        string $shopUrl,
        ?string $products = null,
    ): bool {
        $templateName = Setting::get('whatsapp_offer_template', '')
            ?: (config('app.tenant_id', '') . '_welcome_offer');

        $firstName = trim((string) $name) ?: 'there';
        // Meta rejects a parameter containing newlines or tabs, so the product
        // list has to arrive as one flat line however it is worded.
        $items = preg_replace('/\s+/', ' ', trim((string) $products)) ?: 'our bestsellers';

        // Must stay in step with the welcome_offer template body:
        // "Hi {{1}}, your code {{2}} is valid on {{3}}."
        $bodyParams = [
            ['type' => 'text', 'text' => $firstName],
            ['type' => 'text', 'text' => $code],
            ['type' => 'text', 'text' => $items],
        ];

        // Only non-Meta providers ever send this - sendTemplate() drops it on the
        // Meta path, where the wording lives in the approved template instead. It
        // is free text rather than a template parameter, so unlike $items it can
        // carry line breaks and *bold*.
        $fallback = "🎉 Hi {$firstName}, welcome to GRYT!\n\n"
            . "Your code *{$code}* is valid on {$items}.\n\n"
            . "🛒 Apply it at checkout: {$shopUrl}";

        return $this->sendTemplate($phone, $templateName, $bodyParams, $fallback);
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

    /**
     * Send a login/verification OTP via an AUTHENTICATION template.
     *
     * OtpService used to send the code as a free-form text message. Meta only
     * delivers free-form text inside the 24-hour window that opens when the
     * customer messages the business first, so for a sign-in OTP - where the
     * customer has typically never messaged us - the API returned 200 and the
     * message was then silently dropped. Nothing was logged, because nothing
     * failed at the API layer.
     *
     * AUTHENTICATION templates carry a copy-code button, and Meta rejects the
     * send with 131008 ("Button at index 0 of type Url requires a parameter")
     * unless the code is passed as a button parameter as well as a body one.
     */
    public function sendOtp(string $phone, string $code): bool
    {
        $provider = Setting::get('whatsapp_provider', 'meta');

        if ($provider !== 'meta') {
            return $this->sendText($phone, "Your verification code is {$code}. Valid for 10 minutes.");
        }

        $templateName = Setting::get('whatsapp_login_otp_template', '')
            ?: (config('app.tenant_id', '') . '_login_otp');

        return $this->sendMetaTemplate(
            $phone,
            $templateName,
            [['type' => 'text', 'text' => $code]],
            [[
                'type'       => 'button',
                'sub_type'   => 'url',
                'index'      => '0',
                'parameters' => [['type' => 'text', 'text' => $code]],
            ]],
        );
    }

    private function sendMetaTemplate(
        string $phone,
        string $templateName,
        array $bodyParams,
        array $extraComponents = [],
    ): bool {
        $token = Setting::get('whatsapp_api_token', '') ?: config('services.meta.page_access_token');
        $phoneId = Setting::get('whatsapp_phone_number_id', '') ?: config('services.meta.whatsapp_phone_number_id');

        if (empty($token) || empty($phoneId)) {
            return false;
        }

        $cleanPhone = $this->cleanPhone($phone);

        try {
            $post = fn (string $language) => Http::withToken($token)
                ->timeout(10)
                ->retry(2, 500, throw: false)
                ->post("https://graph.facebook.com/v21.0/{$phoneId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to' => $cleanPhone,
                    'type' => 'template',
                    'template' => [
                        'name' => $templateName,
                        'language' => ['code' => $language],
                        'components' => array_merge(
                            [[
                                'type' => 'body',
                                'parameters' => $bodyParams,
                            ]],
                            $extraComponents,
                        ),
                    ],
                ]);

            $this->lastError = null;
            $response = $post('en');

            if ($response->successful()) {
                return true;
            }

            // 132001 is "template name does not exist in the translation" - which
            // Meta returns both for a missing template AND for one that exists in
            // a different locale. Templates approved as "English (US)" are filed
            // under en_US, so a single retry rescues the most common misconfig.
            if ((int) $response->json('error.code') === 132001) {
                $retry = $post('en_US');

                if ($retry->successful()) {
                    Log::info('WhatsApp: Meta template sent as en_US', ['template' => $templateName]);
                    return true;
                }

                $response = $retry;
            }

            $this->lastError = $this->describeTemplateError($response->json(), $templateName);

            Log::warning('WhatsApp: Meta template failed', [
                'phone' => $cleanPhone,
                'template' => $templateName,
                'status' => $response->status(),
                'response' => $response->json(),
            ]);
            return false;
        } catch (\Exception $e) {
            $this->lastError = 'WhatsApp could not be reached: ' . $e->getMessage();

            Log::error('WhatsApp: Meta template exception', [
                'phone' => $cleanPhone,
                'template' => $templateName,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Turn a Meta error payload into an instruction rather than a code.
     */
    private function describeTemplateError(?array $json, string $templateName): string
    {
        $code    = (int) data_get($json, 'error.code');
        $message = (string) data_get($json, 'error.message', 'unknown error');

        return match ($code) {
            132001 => "The template \"{$templateName}\" does not exist in your WhatsApp account "
                . '(or is not approved yet). Create and get it approved in WhatsApp Manager, '
                . 'or point Popup settings at the name you did approve.',
            132000 => "The template \"{$templateName}\" was sent the wrong number of variables. "
                . 'Its body must have exactly three: name, code and products.',
            131047, 131026 => 'WhatsApp would not deliver to this number. It may not be on WhatsApp, '
                . 'or it has never messaged this business and the template is not eligible.',
            190, 102 => 'The WhatsApp API token has expired or been revoked. Reconnect it in Settings.',
            default => $message,
        };
    }
}
