<?php

namespace App\Console\Commands;

use App\Models\AbandonedCheckout;
use App\Models\Coupon;
use App\Models\Setting;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendAbandonedCartReminders extends Command
{
    protected $signature = 'cart:remind-abandoned';
    protected $description = 'Send multi-touch email and WhatsApp reminders for abandoned carts (1h, 24h, 72h)';

    /**
     * Reminder schedule configuration.
     * Each touch defines: delay after cart creation, discount percentage, coupon validity, email template, subject line.
     */
    private function getReminderSchedule(): array
    {
        // Subject lines come from Setting keys so the admin editor
        // (admin/abandoned-cart-templates) can override them without a deploy.
        // Defaults match the original hardcoded copy.
        return [
            // Touch 1: 1 hour — gentle reminder, no discount
            0 => [
                'min_age_minutes' => 60,
                'max_age_hours' => 23,
                'discount_pct' => 0,
                'coupon_hours' => 0,
                'subject' => Setting::get('abandoned_cart_r1_subject', 'You left something behind!'),
                'email_template' => 'emails.abandoned-cart-reminder-1',
                'whatsapp_message' => fn ($name, $cartUrl) =>
                    "Hi {$name}! You left items in your cart.\n\nYour items are still waiting for you.\n\nComplete your order: {$cartUrl}",
            ],
            // Touch 2: 24 hours — second nudge with 5% discount
            1 => [
                'min_age_minutes' => 24 * 60,
                'max_age_hours' => 71,
                'discount_pct' => 5,
                'coupon_hours' => 24,
                'subject' => Setting::get('abandoned_cart_r2_subject', "Still thinking? Here's 5% off to help you decide"),
                'email_template' => 'emails.abandoned-cart-reminder-2',
                'whatsapp_message' => fn ($name, $cartUrl, $code) =>
                    "Hi {$name}! Still thinking about your cart?\n\nUse code *{$code}* for 5% OFF (valid 24 hours)!\n\nComplete your order: {$cartUrl}",
            ],
            // Touch 3: 72 hours — final push with 10% discount
            2 => [
                'min_age_minutes' => 72 * 60,
                'max_age_hours' => 168,
                'discount_pct' => 10,
                'coupon_hours' => 48,
                'subject' => Setting::get('abandoned_cart_r3_subject', 'Last chance! 10% off your cart — expiring soon'),
                'email_template' => 'emails.abandoned-cart-reminder-3',
                'whatsapp_message' => fn ($name, $cartUrl, $code) =>
                    "Hi {$name}! Last chance to grab your items!\n\nUse code *{$code}* for 10% OFF (valid 48 hours)!\n\nComplete your order now: {$cartUrl}\n\nDon't miss out!",
            ],
        ];
    }

    public function handle(): int
    {
        $tenants = Tenant::all();
        $grandTotal = 0;

        foreach ($tenants as $tenant) {
            tenancy()->initialize($tenant);
            $sent = $this->processForTenant($tenant->id);
            $grandTotal += $sent;
            tenancy()->end();
        }

        $this->info("Grand total reminders sent across all tenants: {$grandTotal}");
        return 0;
    }

    private function processForTenant(string $tenantId): int
    {
        $schedule = $this->getReminderSchedule();
        $totalSent = 0;

        foreach ($schedule as $touchIndex => $touch) {
            // De-duplicate by phone_hash: only the LATEST checkout per phone gets a message.
            // Without this, a customer who retries checkout 5 times gets 5× the messages
            // because each retry + webhook creates a new AC row.
            $latestPerPhone = AbandonedCheckout::query()
                ->selectRaw('MAX(id) as id')
                ->where(function ($q) {
                    $q->where('recovered', false)->orWhereNull('recovered');
                })
                ->where('created_at', '<=', now()->subMinutes($touch['min_age_minutes']))
                ->where('created_at', '>=', now()->subHours($touch['max_age_hours']))
                ->where(fn ($q) => $q->whereNotNull('email')->orWhereNotNull('phone'))
                ->whereNotNull('phone_hash')
                ->groupBy('phone_hash');

            // Also include checkouts without phone_hash (email-only) — no dedup needed
            $emailOnly = AbandonedCheckout::query()
                ->select('id')
                ->where(function ($q) {
                    $q->where('recovered', false)->orWhereNull('recovered');
                })
                ->where('created_at', '<=', now()->subMinutes($touch['min_age_minutes']))
                ->where('created_at', '>=', now()->subHours($touch['max_age_hours']))
                ->whereNotNull('email')
                ->whereNull('phone_hash');

            $checkouts = AbandonedCheckout::where(function ($q) use ($latestPerPhone, $emailOnly) {
                    $q->whereIn('id', $latestPerPhone)->orWhereIn('id', $emailOnly);
                })
                ->where(function ($q) use ($touchIndex) {
                    if ($touchIndex === 0) {
                        $q->where('reminder_count', 0)->orWhereNull('reminder_count');
                    } else {
                        $q->where('reminder_count', $touchIndex);
                    }
                })
                ->limit(50)
                ->get();

            if ($checkouts->isEmpty()) {
                continue;
            }

            $this->info("Touch " . ($touchIndex + 1) . ": Found {$checkouts->count()} carts to notify");

            foreach ($checkouts as $checkout) {
                try {
                    $customerName = $checkout->name ?? 'there';

                    // Skip if no reachable channel (fake email + no phone)
                    $hasRealEmail = $checkout->email
                        && !preg_match('/@phone\.|@fastrr\.com$/i', $checkout->email);
                    if (!$hasRealEmail && !$checkout->phone) {
                        continue;
                    }

                    $discountCode = null;

                    // Create coupon only if this touch offers a discount
                    if ($touch['discount_pct'] > 0) {
                        $discountCode = 'COMEBACK' . strtoupper(substr(md5($checkout->id . $touchIndex . time()), 0, 4));
                        Coupon::create([
                            'code' => $discountCode,
                            'name' => "Abandoned Cart Recovery (Touch " . ($touchIndex + 1) . ")",
                            'type' => 'percentage',
                            'value' => $touch['discount_pct'],
                            'min_order_amount' => 0,
                            'usage_limit' => 1,
                            'usage_per_user' => 1,
                            'is_active' => true,
                            'expires_at' => now()->addHours($touch['coupon_hours']),
                        ]);
                    }

                    $cartUrl = $discountCode
                        ? url("/cart?recovery={$checkout->id}&code={$discountCode}")
                        : url("/cart?recovery={$checkout->id}");

                    // Send email (skip auto-generated phone-based emails)
                    if ($hasRealEmail) {
                        Mail::send($touch['email_template'], [
                            'name' => $customerName,
                            'cartUrl' => $cartUrl,
                            'discountCode' => $discountCode,
                            'discountPct' => $touch['discount_pct'],
                            'cartTotal' => $checkout->cart_total,
                            'cartSnapshot' => $checkout->cart_snapshot ?? [],
                        ], function ($m) use ($checkout, $touch) {
                            $m->to($checkout->email)
                              ->from(config('mail.from.address'), config('app.name', 'Store'))
                              ->subject($touch['subject']);
                        });
                    }

                    // Send WhatsApp
                    if ($checkout->phone) {
                        $this->sendWhatsApp($checkout, $touch, $customerName, $cartUrl, $discountCode);
                    }

                    // Admin WhatsApp alert — max 1 per day across all carts
                    if ($touchIndex === 0 && !Cache::get('admin_ac_alert_' . date('Y-m-d'))) {
                        $this->notifyAdminWhatsApp($checkout, $customerName);
                        Cache::put('admin_ac_alert_' . date('Y-m-d'), true, now()->endOfDay());
                    }

                    $checkout->update([
                        'notified_at' => now(),
                        'reminder_count' => $touchIndex + 1,
                    ]);
                    $totalSent++;

                    $this->info("  Touch " . ($touchIndex + 1) . " sent: {$checkout->email}");

                } catch (\Exception $e) {
                    Log::warning('Abandoned cart reminder failed', [
                        'id' => $checkout->id,
                        'touch' => $touchIndex + 1,
                        'error' => $e->getMessage(),
                    ]);
                    $this->error("  Failed: {$checkout->email} - {$e->getMessage()}");
                }
            }
        }

        if ($totalSent > 0) {
            $this->info("[{$tenantId}] Total reminders sent: {$totalSent}");
        }

        return $totalSent;
    }

    private function notifyAdminWhatsApp(AbandonedCheckout $checkout, string $customerName): void
    {
        $adminPhone = Setting::get('admin_whatsapp_phone', '');
        if (!$adminPhone) return;

        $whatsapp = app(\App\Services\WhatsAppService::class);
        if (!$whatsapp->isConfigured()) return;

        $items = collect($checkout->cart_snapshot ?? [])->map(
            fn ($item) => '• ' . ($item['name'] ?? 'Product') . ' x' . ($item['quantity'] ?? 1)
        )->implode("\n");

        $brand = Setting::get('site_name', config('app.name'));
        $total = number_format($checkout->cart_total ?? 0, 0);

        $whatsapp->sendText($adminPhone,
            "ABANDONED CART ALERT\n\n"
            . "Customer: {$customerName}\n"
            . "Phone: {$checkout->phone}\n"
            . "Email: {$checkout->email}\n\n"
            . "Items:\n{$items}\n\n"
            . "Cart Value: Rs{$total}\n"
            . "Abandoned: {$checkout->created_at->diffForHumans()}\n\n"
            . "Manage: " . url('/admin/abandoned-checkouts')
        );
    }

    private function sendWhatsApp(AbandonedCheckout $checkout, array $touch, string $name, string $cartUrl, ?string $code): void
    {
        $whatsapp = app(\App\Services\WhatsAppService::class);
        if (!$whatsapp->isConfigured()) {
            return;
        }

        $messageFn = $touch['whatsapp_message'];
        $body = $code ? $messageFn($name, $cartUrl, $code) : $messageFn($name, $cartUrl);

        $whatsapp->sendText($checkout->phone, $body);
    }
}
