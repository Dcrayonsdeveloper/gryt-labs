<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Shared analytics for influencer coupon performance. Used by BOTH the
 * influencer dashboard and the admin analytics pages so the numbers never
 * diverge. All computation is done in PHP from a single query, keeping it
 * database-agnostic (MySQL + PostgreSQL).
 */
class InfluencerAnalyticsService
{
    /** Resolve a named range (or custom from/to) to [start, end] Carbon bounds. */
    public static function dateRange(?string $range, ?string $from = null, ?string $to = null): array
    {
        $today = Carbon::today();

        return match ($range) {
            'today'      => [$today->copy()->startOfDay(), $today->copy()->endOfDay()],
            'yesterday'  => [$today->copy()->subDay()->startOfDay(), $today->copy()->subDay()->endOfDay()],
            '7days'      => [$today->copy()->subDays(6)->startOfDay(), $today->copy()->endOfDay()],
            'this_month' => [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()],
            'custom'     => [
                ($from ? Carbon::parse($from) : $today->copy()->subDays(29))->startOfDay(),
                ($to ? Carbon::parse($to) : $today)->endOfDay(),
            ],
            default      => [$today->copy()->subDays(29)->startOfDay(), $today->copy()->endOfDay()], // 30days
        };
    }

    /**
     * Compute summary cards + chart datasets from an orders query already
     * scoped to one coupon/influencer.
     */
    public static function compute(Builder $scoped, Carbon $start, Carbon $end, float $commissionPct = 0): array
    {
        $rows = (clone $scoped)
            ->whereBetween('created_at', [$start, $end])
            ->get(['total', 'subtotal', 'discount', 'status', 'user_id', 'guest_phone', 'guest_email', 'created_at']);

        $orders    = $rows->count();
        $sales     = round((float) $rows->sum('total'), 2);
        $discount  = round((float) $rows->sum('discount'), 2);
        $customers = $rows->map(fn ($o) => $o->user_id ?: $o->guest_phone ?: $o->guest_email)
            ->filter()->unique()->count();
        $aov = $orders > 0 ? round($sales / $orders, 2) : 0.0;

        // Commission is earned on the value of real (non-cancelled) sales.
        $commissionableSales = round((float) $rows->reject(fn ($o) => $o->status === 'cancelled')->sum('total'), 2);
        $commission = round($commissionableSales * $commissionPct / 100, 2);

        // Daily buckets across the whole range (so the chart has a continuous x-axis).
        $days = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $days[$d->format('Y-m-d')] = ['orders' => 0, 'sales' => 0.0];
        }

        $monthly = [];
        foreach ($rows as $o) {
            $day = $o->created_at?->format('Y-m-d');
            if ($day && isset($days[$day])) {
                $days[$day]['orders']++;
                $days[$day]['sales'] += (float) $o->total;
            }
            $mo = $o->created_at?->format('Y-m');
            if ($mo) {
                $monthly[$mo] = ($monthly[$mo] ?? 0) + (float) $o->total;
            }
        }
        ksort($monthly);

        return [
            'cards' => compact('orders', 'sales', 'discount', 'customers', 'aov', 'commission') + ['commission_pct' => $commissionPct],
            'daily_labels'   => array_map(fn ($k) => Carbon::parse($k)->format('d M'), array_keys($days)),
            'daily_orders'   => array_map(fn ($v) => $v['orders'], array_values($days)),
            'daily_sales'    => array_map(fn ($v) => round($v['sales'], 2), array_values($days)),
            'monthly_labels' => array_map(fn ($k) => Carbon::parse($k . '-01')->format('M Y'), array_keys($monthly)),
            'monthly_sales'  => array_map(fn ($v) => round($v, 2), array_values($monthly)),
        ];
    }
}
