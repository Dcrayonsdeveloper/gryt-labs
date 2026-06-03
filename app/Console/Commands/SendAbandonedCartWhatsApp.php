<?php

namespace App\Console\Commands;

use App\Models\AbandonedCheckout;
use App\Models\Setting;
use App\Models\Tenant;
use App\Services\MessagingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Send WhatsApp recovery messages to customers who abandoned checkout.
 *
 * Runs every hour via scheduler. Sends to customers who:
 * - Have a phone number captured
 * - Abandoned 1-24 hours ago (not too soon, not too late)
 * - Haven't been notified yet (or max 2 reminders)
 * - Haven't recovered (completed purchase)
 *
 * Uses the approved 'jikra_cart_recovery' WhatsApp template.
 */
class SendAbandonedCartWhatsApp extends Command
{
    protected $signature = 'abandoned:whatsapp-recover {--dry-run : Show what would be sent without sending}';
    protected $description = 'Send WhatsApp recovery messages for abandoned carts';

    public function handle(): int
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            tenancy()->initialize($tenant);
            $this->processForTenant($tenant->id);
            tenancy()->end();
        }

        return 0;
    }

    private function processForTenant(string $tenantId): void
    {
        $dryRun = $this->option('dry-run');

        // Find abandoned checkouts eligible for WhatsApp recovery.
        // De-duplicate by phone_hash: only the LATEST checkout per phone gets a message.
        // This prevents spam when a customer retries checkout multiple times (each retry
        // + webhook creates a new AC row — one phone can have 10-20+ rows).
        $latestPerPhone = AbandonedCheckout::query()
            ->selectRaw('MAX(id) as id')
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->whereNotNull('phone_hash')
            ->where(function ($q) {
                $q->where('recovered', false)->orWhereNull('recovered');
            })
            ->where('created_at', '>=', now()->subHours(48))
            ->where('created_at', '<=', now()->subHour())
            ->groupBy('phone_hash');

        $abandoned = AbandonedCheckout::query()
            ->whereIn('id', $latestPerPhone)
            ->where('reminder_count', '<', 2) // Max 2 reminders
            ->where(function ($q) {
                $q->whereNull('notified_at')
                  ->orWhere('notified_at', '<=', now()->subHours(12)); // Min 12h between reminders
            })
            ->whereNotNull('cart_snapshot')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($abandoned->isEmpty()) {
            return;
        }

        $this->info("Found {$abandoned->count()} abandoned carts to recover.");

        $sent = 0;
        $failed = 0;

        foreach ($abandoned as $checkout) {
            try {
                $phone = $checkout->phone;

                // Ensure phone has country code
                $phone = preg_replace('/\D/', '', $phone);
                if (strlen($phone) === 10) {
                    $phone = '91' . $phone;
                }

                // Get first product from cart snapshot
                $items = is_array($checkout->cart_snapshot) ? $checkout->cart_snapshot : [];
                $firstItem = $items[0] ?? null;
                if (!$firstItem) continue;

                $productName = $firstItem['product_name'] ?? 'your item';
                $price = $firstItem['price'] ?? $checkout->cart_total;
                $customerName = $checkout->name ?: 'there';

                if ($dryRun) {
                    $this->line("  [DRY RUN] Would send to {$phone}: {$customerName} - {$productName} (₹{$price})");
                    $sent++;
                    continue;
                }

                // Send WhatsApp using template
                $templateName = Setting::get('whatsapp_cart_recovery_template', '') ?: (config('app.tenant_id', '') . '_cart_recovery');

                $result = $this->sendWhatsAppTemplate($phone, $templateName, [
                    $customerName,      // {{1}} Name
                    $productName,       // {{2}} Product
                    (string) round($price), // {{3}} Price
                ]);

                if ($result) {
                    $checkout->update([
                        'notified_at' => now(),
                        'reminder_count' => ($checkout->reminder_count ?? 0) + 1,
                    ]);
                    $sent++;
                    $this->line("  ✓ Sent to {$customerName} ({$phone})");
                } else {
                    $failed++;
                    $this->error("  ✗ Failed: {$customerName} ({$phone})");
                }
            } catch (\Throwable $e) {
                $failed++;
                Log::warning('Abandoned cart WhatsApp failed', [
                    'checkout_id' => $checkout->id,
                    'error' => $e->getMessage(),
                ]);
                $this->error("  ✗ Error: {$e->getMessage()}");
            }
        }

        $this->info("[{$tenantId}] Done. Sent: {$sent}, Failed: {$failed}");
    }

    /**
     * Send WhatsApp template message via Meta Business API.
     */
    private function sendWhatsAppTemplate(string $phone, string $template, array $params): bool
    {
        $whatsapp = app(\App\Services\WhatsAppService::class);
        if (!$whatsapp->isConfigured()) {
            \Log::warning('WhatsApp API not configured');
            return false;
        }

        $bodyParams = array_map(fn($p) => ['type' => 'text', 'text' => $p], $params);
        $fallback = implode(' | ', $params);
        return $whatsapp->sendTemplate($phone, $template, $bodyParams, $fallback);
    }
}
