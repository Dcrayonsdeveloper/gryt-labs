<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Setting;
use App\Models\Tenant;
use App\Services\AnalyticsService;
use Illuminate\Console\Command;

/**
 * Send Meta CAPI Purchase events for orders that were missed — e.g. every order
 * placed before the Conversions API token was configured. Meta accepts events up
 * to 7 days old (event_time = the order's real created_at), and event_id =
 * order_number keeps browser-pixel dedup intact, so re-sending is safe.
 *
 * --reset-false-stamps clears "sent" flags written by the old code, which stamped
 * capi_sent_at WITHOUT verifying delivery (it stamped even with no token set).
 * Those legacy stamps are identifiable by having no capi_fbtrace_id (Facebook's
 * receipt) — every genuine send since the fix carries one.
 *
 *   php artisan capi:backfill --tenant=gryt --dry-run
 *   php artisan capi:backfill --tenant=gryt --reset-false-stamps
 *   php artisan capi:backfill --tenant=gryt          # send, report per order
 */
class BackfillCapiPurchases extends Command
{
    protected $signature = 'capi:backfill
        {--tenant= : Only this tenant id (default: all tenants)}
        {--days=2 : Look-back window. Default 2 (Meta pixel↔server dedup only spans ~48h); up to 7 with --force}
        {--order=* : Only these order_number(s) — bypasses the date window for selection}
        {--reset-false-stamps : Clear legacy "sent" stamps that have no Facebook receipt}
        {--force : Allow >48h window / sending when Shiprocket handles the pixel (risk: double-counted conversions)}
        {--dry-run : Preview without sending or writing}';

    protected $description = 'Send missed Meta CAPI Purchase events for recent orders (and clear false legacy stamps)';

    public function handle(AnalyticsService $analytics): int
    {
        $tenants = $this->option('tenant')
            ? Tenant::where('id', $this->option('tenant'))->get()
            : Tenant::all();

        if ($tenants->isEmpty()) {
            $this->error('No matching tenant.');
            return self::FAILURE;
        }

        foreach ($tenants as $tenant) {
            tenancy()->initialize($tenant);
            try {
                $this->runTenant($analytics, (string) $tenant->id);
            } catch (\Throwable $e) {
                $this->error("[{$tenant->id}] backfill failed: {$e->getMessage()}");
            } finally {
                tenancy()->end();
            }
        }

        return self::SUCCESS;
    }

    private function runTenant(AnalyticsService $analytics, string $tenantId): void
    {
        $dry   = (bool) $this->option('dry-run');
        $reset = (bool) $this->option('reset-false-stamps');
        $force = (bool) $this->option('force');
        $days  = min(7, max(1, (int) $this->option('days'))); // Meta hard limit: 7 days

        // Meta only dedups browser-pixel vs server events within ~48h. Beyond that,
        // a backfilled Purchase for a customer whose pixel already fired = counted twice.
        if ($days > 2 && ! $force) {
            $this->warn("[{$tenantId}] --days={$days} exceeds Meta's ~48h dedup window — orders whose browser pixel already reported would be DOUBLE-COUNTED. Re-run with --force to accept, or keep --days=2.");
            $days = 2;
        }

        $numbers = array_filter((array) $this->option('order'));

        $query = Order::query()
            ->whereNotIn('status', ['cancelled', 'refunded', 'returned'])
            ->orderBy('id');
        if ($numbers) {
            // Explicitly named orders are selected regardless of age; the 7-day
            // send limit below still applies (Meta rejects older event_time).
            $query->whereIn('order_number', $numbers);
        } else {
            $query->where('created_at', '>=', now()->subDays($days));
        }
        $orders = $query->get();

        if ($numbers && ($missing = array_diff($numbers, $orders->pluck('order_number')->all()))) {
            $this->warn("[{$tenantId}] not found (or excluded status): " . implode(', ', $missing));
        }

        // ── Phase 1: clear legacy stamps (sent_at without Facebook's fbtrace receipt)
        if ($reset) {
            $legacy = $orders->filter(fn ($o) => data_get($o->metadata, 'capi_sent_at') && ! data_get($o->metadata, 'capi_fbtrace_id'));
            foreach ($legacy as $o) {
                $this->line(sprintf('  %s[%s] %s: clearing unverified stamp (source=%s)',
                    $dry ? '[DRY] ' : '', $tenantId, $o->order_number, data_get($o->metadata, 'capi_source', '-')));
                if (! $dry) {
                    $meta = (array) $o->metadata;
                    unset($meta['capi_sent_at'], $meta['capi_source'], $meta['fb_event_id']);
                    $o->metadata = $meta;
                    $o->saveQuietly();
                }
            }
            $this->info("[{$tenantId}] cleared " . $legacy->count() . ' unverified stamp(s)' . ($dry ? ' (dry-run)' : ''));
            if (! $dry) {
                $orders = $query->get(); // re-read so phase 2 sees the cleared flags
            }
        }

        // ── Phase 2: send Purchase for orders with no (verified) send
        $pixel = Setting::get('facebook_pixel_id');
        $token = Setting::get('facebook_capi_token') ?: Setting::get('facebook_capi_access_token');
        if (! $pixel || ! $token) {
            $this->warn("[{$tenantId}] pixel/token not configured — skipping send phase (set the CAPI token in Settings → SEO, then re-run).");
            return;
        }

        // Shiprocket's own pixel already reports these purchases — sending ours too
        // would double-count. Explicit --force (or --order) overrides.
        if (Setting::get('fastrr_handles_purchase_pixel', false) && ! $force && ! $numbers) {
            $this->warn("[{$tenantId}] fastrr_handles_purchase_pixel is ON (Shiprocket sends Purchase) — skipping to avoid double-counting. Use --force to override.");
            return;
        }

        // In a dry run the phase-1 clears weren't written, so treat legacy-stamped
        // orders as pending too — otherwise the preview reports far fewer than a
        // real `--reset-false-stamps` run would actually send.
        $pending = $orders->filter(function ($o) use ($dry, $reset) {
            $sent  = data_get($o->metadata, 'capi_sent_at');
            $trace = data_get($o->metadata, 'capi_fbtrace_id');

            return ! $sent || ($dry && $reset && ! $trace);
        });
        if ($pending->isEmpty()) {
            $this->info("[{$tenantId}] nothing to send — all orders in the window already have a verified send.");
            return;
        }

        $this->info("[{$tenantId}] sending " . $pending->count() . " Purchase event(s)…" . ($dry ? ' (dry-run, nothing sent)' : ''));
        foreach ($pending as $o) {
            // Meta rejects event_time older than 7 days outright.
            if ($o->created_at->lt(now()->subDays(7))) {
                $this->line("  {$o->order_number}: SKIPPED — older than Meta's 7-day event_time limit ({$o->created_at->toDateString()})");
                continue;
            }

            if ($dry) {
                $this->line("  [DRY] {$o->order_number}: would send (event_time=" . $o->created_at->toDateTimeString() . ')');
                continue;
            }

            // Console runs synchronously, so the verdict is on the order right after.
            // fbOnly: GA4/Google Ads already fired at checkout — don't re-send those.
            $o->load('items.product', 'user');
            $outcome = $analytics->trackPurchase($o, null, $o->order_number, [], 'backfill', $o->created_at->timestamp, true);

            $fresh = $o->fresh();
            $trace = data_get($fresh->metadata, 'capi_fbtrace_id');
            $error = data_get($fresh->metadata, 'capi_error');
            $this->line(sprintf('  %s: %s%s',
                $o->order_number,
                $trace ? "SENT ✓ (fbtrace {$trace})" : ($error ? 'FAILED — ' . \Illuminate\Support\Str::limit($error, 90) : $outcome),
                $outcome === 'skipped_excluded' ? ' (cancelled/test order)' : ''
            ));
        }
    }
}
