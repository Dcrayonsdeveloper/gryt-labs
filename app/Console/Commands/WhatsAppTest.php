<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;

/**
 * Send a single test WhatsApp message via the tenant's configured provider
 * (FastWASMs or Meta) to verify the integration is connected and able to
 * deliver messages.
 *
 *   php artisan tenants:run "whatsapp:test 7208482208" --tenants=natually
 *
 * Returns SUCCESS exit code if the provider accepted the send. The recipient
 * should receive the message within a minute — if not, the provider has
 * silently queued or failed it (check storage/logs/laravel.log).
 */
class WhatsAppTest extends Command
{
    protected $signature = 'whatsapp:test {phone : Phone number to send the test message to (10 digits, or 12 with country code)}';

    protected $description = 'Send a test WhatsApp message via this tenant\'s provider to verify the integration is working';

    public function handle(WhatsAppService $whatsapp): int
    {
        $phone = (string) $this->argument('phone');

        if (!$whatsapp->isConfigured()) {
            $this->error('WhatsApp is not configured for this tenant.');
            $this->line('Set whatsapp_provider + whatsapp_instance_id + whatsapp_api_token in Settings.');
            return self::FAILURE;
        }

        $provider = Setting::get('whatsapp_provider', 'meta');
        $brand    = Setting::get('site_name', config('app.name'));

        $message = "Test from {$brand}: WhatsApp integration check at "
            . now()->toDateTimeString()
            . ". If you received this, the connection is working.";

        $this->info("Sending test via {$provider} to {$phone}...");
        $ok = $whatsapp->sendText($phone, $message);

        if ($ok) {
            $this->info('Send returned SUCCESS — check the recipient WhatsApp for the test message.');
            $this->line('If the message does not arrive within 1-2 minutes the provider may have silently queued or failed it.');
            return self::SUCCESS;
        }

        $this->error('Send FAILED — the provider returned an error.');
        $this->line('Tail storage/logs/laravel.log for the exact reason (look for "WhatsApp: FastWASMs send failed").');
        $this->line('For FastWASMs: most likely the WhatsApp Web session is disconnected. Reconnect in the FastWASMs dashboard.');
        return self::FAILURE;
    }
}
