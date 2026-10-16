<?php

namespace App\Console\Commands;

use App\Models\OfferLead;
use App\Models\Tenant;
use App\Services\OfferCouponService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Re-send offer-popup coupons that never reached the shopper.
 *
 * The code goes out on WhatsApp only, so a failed send leaves someone who
 * handed over a verified number with nothing. The usual cause is a template
 * that is still awaiting Meta approval - which means the backlog becomes
 * deliverable at a moment nobody is watching. This clears it on its own.
 */
class RetryOfferCoupons extends Command
{
    protected $signature = 'offer-leads:retry-failed
                            {--dry-run : List what would be re-sent without sending}
                            {--days=14 : Only retry leads verified within this many days}';

    protected $description = 'Re-send offer-popup coupon codes whose WhatsApp delivery failed';

    /**
     * Give up on a tenant after this many consecutive failures. When the
     * template genuinely does not exist every send fails, and hammering Meta
     * once an hour for the whole backlog earns nothing but rate limiting.
     */
    private const FAILURE_STREAK_LIMIT = 3;

    public function handle(): int
    {
        foreach (Tenant::all() as $tenant) {
            tenancy()->initialize($tenant);
            $this->processForTenant((string) $tenant->id);
            tenancy()->end();
        }

        return self::SUCCESS;
    }

    private function processForTenant(string $tenantId): void
    {
        $dryRun = (bool) $this->option('dry-run');
        $days   = max(1, (int) $this->option('days'));

        $leads = OfferLead::verified()
            ->where('whatsapp_status', 'failed')
            ->where('verified_at', '>=', now()->subDays($days))
            // Leave a gap between attempts so an hourly run does not retry a
            // lead that was already tried minutes ago from the admin screen.
            ->where(function ($q) {
                $q->whereNull('coupon_sent_at')
                  ->orWhere('coupon_sent_at', '<=', now()->subHour());
            })
            ->orderBy('verified_at')
            ->get();

        if ($leads->isEmpty()) {
            return;
        }

        $this->info("[{$tenantId}] {$leads->count()} undelivered coupon(s) to retry.");

        if ($dryRun) {
            foreach ($leads as $lead) {
                $this->line("  would re-send to +{$lead->country_code}{$lead->phone}");
            }

            return;
        }

        $sender = app(OfferCouponService::class);
        $sent   = 0;
        $streak = 0;

        foreach ($leads as $lead) {
            if ($sender->send($lead)) {
                $sent++;
                $streak = 0;
                continue;
            }

            if (++$streak >= self::FAILURE_STREAK_LIMIT) {
                Log::warning('Offer coupon retry stopped early', [
                    'tenant'    => $tenantId,
                    'sent'      => $sent,
                    'remaining' => $leads->count() - $sent,
                    'reason'    => 'consecutive failures - template likely still unapproved',
                ]);
                $this->warn("  stopped after {$streak} consecutive failures; will try again next run.");

                return;
            }
        }

        $this->info("[{$tenantId}] re-sent {$sent} of {$leads->count()}.");

        if ($sent > 0) {
            Log::info('Offer coupon retry delivered backlog', ['tenant' => $tenantId, 'sent' => $sent]);
        }
    }
}
