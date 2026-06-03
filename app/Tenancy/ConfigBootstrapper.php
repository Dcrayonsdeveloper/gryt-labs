<?php

namespace App\Tenancy;

use Illuminate\Support\Facades\Mail;
use Stancl\Tenancy\Contracts\TenancyBootstrapper;
use Stancl\Tenancy\Contracts\Tenant;

/**
 * Overrides Laravel config values with tenant-specific settings.
 *
 * When tenancy initializes for a request, this bootstrapper reads
 * the tenant's `data` JSON and overrides config keys so that
 * services like Razorpay, mail, Delhivery etc. use tenant-specific credentials.
 */
class ConfigBootstrapper implements TenancyBootstrapper
{
    protected array $originalConfig = [];

    public function bootstrap(Tenant $tenant): void
    {
        // Force Laravel to recreate the mailer/SMTP transport for this tenant.
        // Without this, MailManager caches the transport from the previous tenant,
        // causing cross-tenant mail leaks (e.g. natually email sent via getsetnova's config).
        Mail::forgetMailers();

        // Save original config so we can revert
        $this->originalConfig = [
            'app.name' => config('app.name'),
            'app.url' => config('app.url'),
            'app.tenant_id' => config('app.tenant_id'),
            'mail.from.name' => config('mail.from.name'),
            'mail.from.address' => config('mail.from.address'),
            'mail.mailers.smtp.host' => config('mail.mailers.smtp.host'),
            'mail.mailers.smtp.port' => config('mail.mailers.smtp.port'),
            'mail.mailers.smtp.username' => config('mail.mailers.smtp.username'),
            'mail.mailers.smtp.password' => config('mail.mailers.smtp.password'),
            'mail.mailers.smtp.encryption' => config('mail.mailers.smtp.encryption'),
            // Razorpay: read directly from Setting::get() per tenant — not via config()
            'services.instagram.handle' => config('services.instagram.handle'),
            'services.instagram.user_id' => config('services.instagram.user_id'),
            'services.instagram.access_token' => config('services.instagram.access_token'),
            'services.whatsapp.phone_number_id' => config('services.whatsapp.phone_number_id'),
            'services.whatsapp.token' => config('services.whatsapp.token'),
            'services.facebook.pixel_id' => config('services.facebook.pixel_id'),
            'services.facebook.page_id' => config('services.facebook.page_id'),
            'services.facebook.page_access_token' => config('services.facebook.page_access_token'),
            'services.delhivery.token' => config('services.delhivery.token'),
            'services.ga4.measurement_id' => config('services.ga4.measurement_id'),
            'mail.reply_to.address' => config('mail.reply_to.address'),
            'mail.reply_to.name' => config('mail.reply_to.name'),
        ];

        // Map tenant data keys → Laravel config keys
        // All API keys/tokens are per-tenant to prevent cross-tenant leaks
        $configMap = [
            'brand_name' => ['app.name', 'mail.from.name'],
            'support_email' => ['mail.from.address'],
            // Meta
            'meta_app_id' => ['services.meta.app_id'],
            'meta_app_secret' => ['services.meta.app_secret'],
            // Payment: Razorpay reads directly from Setting::get() — not via config()
            // Instagram
            'instagram_handle' => ['services.instagram.handle'],
            'instagram_user_id' => ['services.instagram.user_id'],
            'instagram_access_token' => ['services.instagram.access_token'],
            // WhatsApp
            'whatsapp_phone_id' => ['services.whatsapp.phone_number_id'],
            'whatsapp_token' => ['services.whatsapp.token'],
            // Facebook
            'facebook_pixel_id' => ['services.facebook.pixel_id'],
            'facebook_page_id' => ['services.facebook.page_id'],
            'fb_page_access_token' => ['services.facebook.page_access_token'],
            // Shipping
            'delhivery_api_token' => ['services.delhivery.token'],
            // Analytics
            'ga4_measurement_id' => ['services.ga4.measurement_id'],
        ];

        // Fail-closed: ALWAYS override config for mapped keys.
        // If tenant data is missing, set to null so .env values don't leak cross-tenant.
        foreach ($configMap as $tenantKey => $configKeys) {
            $value = $tenant->$tenantKey ?: null;
            foreach ((array) $configKeys as $configKey) {
                config([$configKey => $value]);
            }
        }

        // Phase 2: Override config from tenant's settings table (not central data JSON)
        // Email SMTP settings — each tenant can have their own mail server
        // If tenant has mail_host configured, use their full SMTP config.
        // If not, keep the platform default from .env but ONLY override from_address and from_name.
        $settingsMap = [
            'mail_host'         => 'mail.mailers.smtp.host',
            'mail_port'         => 'mail.mailers.smtp.port',
            'mail_username'     => 'mail.mailers.smtp.username',
            'mail_password'     => 'mail.mailers.smtp.password',
            'mail_encryption'   => 'mail.mailers.smtp.encryption',
            'mail_from_address' => 'mail.from.address',
            'mail_from_name'    => 'mail.from.name',
        ];

        // Also map integration settings (Google/Facebook OAuth) to config
        $integrationMap = [
            'google_client_id'       => 'services.google.client_id',
            'google_client_secret'   => 'services.google.client_secret',
            'facebook_client_id'     => 'services.facebook.client_id',
            'facebook_client_secret' => 'services.facebook.client_secret',
        ];

        // Query settings directly from DB — cannot use Setting::get() here because
        // it calls Cache::remember() which goes through Stancl's CacheManager,
        // and getTenantKey() fails during early bootstrap.
        // Wrapped in try-catch: during first migration the settings table doesn't exist yet.
        $allSettingKeys = array_merge(array_keys($settingsMap), array_keys($integrationMap));
        try {
            $dbSettings = \Illuminate\Support\Facades\DB::table('settings')
                ->whereIn('key', $allSettingKeys)
                ->pluck('value', 'key');
        } catch (\Illuminate\Database\QueryException $e) {
            $dbSettings = collect();
        }

        // Override Google/Facebook config from tenant settings
        foreach ($integrationMap as $settingKey => $configKey) {
            $value = $dbSettings[$settingKey] ?? null;
            if ($value !== null && $value !== '') {
                config([$configKey => $value]);
            }
        }

        // Keys that are stored encrypted in the database
        $encryptedKeys = ['mail_password'];

        // Check if tenant has their own SMTP server configured
        $tenantHasOwnSmtp = !empty($dbSettings['mail_host'] ?? '');

        // SMTP credentials — only override if tenant has their own mail_host
        // This prevents tenant A from sending through tenant B's SMTP
        $smtpKeys = ['mail_host', 'mail_port', 'mail_username', 'mail_password', 'mail_encryption'];

        foreach ($settingsMap as $settingKey => $configKey) {
            $value = $dbSettings[$settingKey] ?? null;
            if ($value !== null && $value !== '') {
                // Skip SMTP credentials if tenant doesn't have their own mail server
                if (in_array($settingKey, $smtpKeys) && !$tenantHasOwnSmtp) {
                    continue;
                }

                // Decrypt if this is an encrypted key
                if (in_array($settingKey, $encryptedKeys)) {
                    try {
                        $value = \Illuminate\Support\Facades\Crypt::decryptString($value);
                    } catch (\Exception $e) {
                        // Value might be unencrypted (legacy) — use as-is
                    }
                }
                config([$configKey => $value]);
            }
        }

        // If tenant doesn't have their own SMTP, reset SMTP config to platform defaults
        // and use the platform's authenticated sender. Store tenant's email as reply-to.
        if (!$tenantHasOwnSmtp) {
            $tenantEmail = config('mail.from.address'); // tenant's support_email set in Phase 1
            config([
                'mail.from.address' => $this->originalConfig['mail.from.address'],
                'mail.mailers.smtp.host' => $this->originalConfig['mail.mailers.smtp.host'],
                'mail.mailers.smtp.port' => $this->originalConfig['mail.mailers.smtp.port'],
                'mail.mailers.smtp.username' => $this->originalConfig['mail.mailers.smtp.username'],
                'mail.mailers.smtp.password' => $this->originalConfig['mail.mailers.smtp.password'],
                'mail.mailers.smtp.encryption' => $this->originalConfig['mail.mailers.smtp.encryption'],
                'mail.reply_to.address' => $tenantEmail,
                'mail.reply_to.name' => config('app.name'),
            ]);
        }

        // Set tenant ID so code can reference the current tenant (e.g. template name prefixes)
        config(['app.tenant_id' => $tenant->id]);

        // Set app URL from the CURRENT request domain (not first registered domain).
        // In queue workers / artisan commands, request()->getHost() returns APP_URL's host
        // (e.g. jikra.in) — NOT the tenant's domain. Use runningInConsole() to detect this
        // and resolve the tenant's actual domain from the DB instead.
        if (app()->runningInConsole()) {
            // Queue worker / artisan: resolve URL from tenant's domain table
            try {
                $domain = $tenant->domains()
                    ->orderByRaw("CASE WHEN domain LIKE '%.dcrayons.app' THEN 1 ELSE 0 END")
                    ->first()?->domain;
                if ($domain) {
                    $url = 'https://' . $domain;
                    config(['app.url' => $url]);
                    \Illuminate\Support\Facades\URL::forceRootUrl($url);
                }
            } catch (\Throwable $e) {
                // Silently ignore if domains table not yet available (e.g. during migrations)
            }
        } else {
            // Web request: use the actual request domain
            $url = request()->getSchemeAndHttpHost();
            config(['app.url' => $url]);
            \Illuminate\Support\Facades\URL::forceRootUrl($url);
        }
    }

    public function revert(): void
    {
        // Restore original config (includes SMTP keys to prevent cross-tenant leaks)
        foreach ($this->originalConfig as $key => $value) {
            config([$key => $value]);
        }

        // Force mailer recreation so next tenant doesn't reuse this tenant's SMTP transport
        Mail::forgetMailers();

        // Reset forced URL
        \Illuminate\Support\Facades\URL::forceRootUrl(config('app.url'));
    }
}
