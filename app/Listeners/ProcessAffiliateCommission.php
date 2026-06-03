<?php

namespace App\Listeners;

use App\Events\OrderDelivered;
use App\Events\OrderPlaced;
use App\Models\Affiliate;
use App\Models\AffiliateCommission;
use Illuminate\Support\Facades\DB;

class ProcessAffiliateCommission
{
    public function handleOrderPlaced(OrderPlaced $event): void
    {
        $order = $event->order;

        if (!$order->affiliate_id) {
            return;
        }

        $affiliate = Affiliate::find($order->affiliate_id);
        if (!$affiliate || !$affiliate->isApproved()) {
            return;
        }

        // Prevent self-referral
        if ($order->user_id && $order->user_id === $affiliate->user_id) {
            return;
        }

        // Prevent duplicate commission
        if (AffiliateCommission::where('affiliate_id', $affiliate->id)->where('order_id', $order->id)->exists()) {
            return;
        }

        $commissionRate = $affiliate->commission_rate;
        $commissionAmount = round($order->total * ($commissionRate / 100), 2);

        DB::transaction(function () use ($affiliate, $order, $commissionRate, $commissionAmount) {
            AffiliateCommission::create([
                'affiliate_id' => $affiliate->id,
                'order_id' => $order->id,
                'order_total' => $order->total,
                'commission_rate' => $commissionRate,
                'commission_amount' => $commissionAmount,
                'status' => 'pending',
            ]);

            $affiliate->increment('pending_balance', $commissionAmount);
        });
    }

    public function handleOrderDelivered(OrderDelivered $event): void
    {
        $order = $event->order;

        $commission = AffiliateCommission::where('order_id', $order->id)
            ->where('status', 'pending')
            ->first();

        if (!$commission) {
            return;
        }

        $affiliate = $commission->affiliate;

        DB::transaction(function () use ($affiliate, $commission) {
            $commission->markAsApproved();

            $affiliate->decrement('pending_balance', $commission->commission_amount);
            $affiliate->increment('available_balance', $commission->commission_amount);
            $affiliate->increment('total_earned', $commission->commission_amount);
            $affiliate->increment('total_orders');
        });
    }

    public static function handleOrderCancelled($order): void
    {
        $commission = AffiliateCommission::where('order_id', $order->id)
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        if (!$commission) {
            return;
        }

        $affiliate = $commission->affiliate;

        DB::transaction(function () use ($affiliate, $commission) {
            $previousStatus = $commission->status;
            $commission->markAsCancelled();

            if ($previousStatus === 'pending') {
                $affiliate->decrement('pending_balance', $commission->commission_amount);
            } elseif ($previousStatus === 'approved') {
                $affiliate->decrement('available_balance', $commission->commission_amount);
                $affiliate->decrement('total_earned', $commission->commission_amount);
                $affiliate->decrement('total_orders');
            }
        });
    }
}
