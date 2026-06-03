<?php

namespace App\Console\Commands;

use App\Models\AbandonedCheckout;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendAbandonedCartDailySummary extends Command
{
    protected $signature = 'cart:abandoned-summary';
    protected $description = 'Send daily abandoned cart summary to admin via email and WhatsApp';

    public function handle(): int
    {
        $yesterday = now()->subDay();

        $abandoned = AbandonedCheckout::where('recovered', false)
            ->where('created_at', '>=', $yesterday)
            ->get();

        $recovered = AbandonedCheckout::where('recovered', true)
            ->where('recovered_at', '>=', $yesterday)
            ->get();

        $totalAbandoned = $abandoned->count();
        $totalValue = number_format($abandoned->sum('cart_total'), 0);
        $totalRecovered = $recovered->count();
        $recoveredValue = number_format($recovered->sum('cart_total'), 0);

        if ($totalAbandoned === 0 && $totalRecovered === 0) {
            $this->info('No abandoned carts in the last 24h.');
            return 0;
        }

        $brand = Setting::get('site_name', config('app.name'));

        // Top 5 abandoned carts by value
        $topCarts = $abandoned->sortByDesc('cart_total')->take(5)->map(function ($c) {
            $name = $c->name ?? 'Unknown';
            $phone = $c->phone ?? 'N/A';
            $total = number_format($c->cart_total, 0);
            $items = collect($c->cart_snapshot ?? [])->count();
            return "  {$name} ({$phone}) — Rs{$total} ({$items} items)";
        })->implode("\n");

        // WhatsApp to admin
        $adminPhone = Setting::get('admin_whatsapp_phone', '');
        if ($adminPhone) {
            $whatsapp = app(\App\Services\WhatsAppService::class);
            if ($whatsapp->isConfigured()) {
                $whatsapp->sendText($adminPhone,
                    "DAILY ABANDONED CART SUMMARY\n"
                    . "({$brand} — " . now()->format('d M Y') . ")\n\n"
                    . "Abandoned: {$totalAbandoned} carts (Rs{$totalValue})\n"
                    . "Recovered: {$totalRecovered} carts (Rs{$recoveredValue})\n\n"
                    . "Top abandoned:\n{$topCarts}\n\n"
                    . "View all: " . url('/admin/abandoned-checkouts')
                );
            }
        }

        // Email to admin
        $adminEmail = Setting::get('admin_email', '') ?: Setting::get('mail_from_address', '');
        if ($adminEmail) {
            try {
                Mail::raw(
                    "Daily Abandoned Cart Summary — {$brand}\n"
                    . "Date: " . now()->format('d M Y') . "\n\n"
                    . "Abandoned: {$totalAbandoned} carts worth Rs{$totalValue}\n"
                    . "Recovered: {$totalRecovered} carts worth Rs{$recoveredValue}\n\n"
                    . "Top abandoned carts:\n{$topCarts}\n\n"
                    . "View all: " . url('/admin/abandoned-checkouts'),
                    function ($m) use ($adminEmail, $brand, $totalAbandoned, $totalValue) {
                        $m->to($adminEmail)
                          ->subject("{$brand}: {$totalAbandoned} abandoned carts (Rs{$totalValue}) — Daily Summary");
                    }
                );
            } catch (\Throwable $e) {
                $this->error("Admin email failed: {$e->getMessage()}");
            }
        }

        $this->info("Summary sent — Abandoned: {$totalAbandoned} (Rs{$totalValue}), Recovered: {$totalRecovered} (Rs{$recoveredValue})");

        return 0;
    }
}
