<?php

namespace App\Services;

use App\Models\LoyaltyPoint;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class LoyaltyService
{
    /**
     * Points earning rates (configurable via settings).
     */
    public function getEarnRate(): int
    {
        return (int) Setting::get('loyalty_earn_rate', 1); // 1 point per ₹1 spent
    }

    public function getRedeemRate(): float
    {
        return (float) Setting::get('loyalty_redeem_rate', 0.25); // 1 point = ₹0.25
    }

    public function isEnabled(): bool
    {
        return (bool) Setting::get('loyalty_enabled', true);
    }

    public function getExpiryDays(): int
    {
        return (int) Setting::get('loyalty_expiry_days', 365);
    }

    /**
     * Award points to a user.
     */
    public function award(User $user, int $points, string $source, Model $reference, string $description): LoyaltyPoint
    {
        if ($points <= 0 || !$this->isEnabled()) {
            return new LoyaltyPoint();
        }

        $expiresAt = now()->addDays($this->getExpiryDays());

        return DB::transaction(function () use ($user, $points, $source, $reference, $description, $expiresAt) {
            $user->increment('loyalty_points_balance', $points);
            $newBalance = $user->fresh()->loyalty_points_balance;

            return LoyaltyPoint::create([
                'user_id' => $user->id,
                'points' => $points,
                'type' => 'earned',
                'source' => $source,
                'reference_type' => get_class($reference),
                'reference_id' => $reference->id,
                'description' => $description,
                'balance_after' => $newBalance,
                'expires_at' => $expiresAt,
            ]);
        });
    }

    /**
     * Award points for an order (based on order total).
     */
    public function awardForOrder(User $user, Model $order): ?LoyaltyPoint
    {
        if (!$this->isEnabled()) {
            return null;
        }

        $total = (float) $order->total;
        $points = (int) floor($total * $this->getEarnRate());

        if ($points <= 0) {
            return null;
        }

        return $this->award($user, $points, 'order', $order, "Earned {$points} points for order #{$order->order_number}");
    }

    /**
     * Award bonus points (signup, review, referral, birthday, admin).
     */
    public function awardBonus(User $user, int $points, string $source, Model $reference, string $description): LoyaltyPoint
    {
        if (!$this->isEnabled()) {
            return new LoyaltyPoint();
        }

        return DB::transaction(function () use ($user, $points, $source, $reference, $description) {
            $user->increment('loyalty_points_balance', $points);
            $newBalance = $user->fresh()->loyalty_points_balance;

            return LoyaltyPoint::create([
                'user_id' => $user->id,
                'points' => $points,
                'type' => 'bonus',
                'source' => $source,
                'reference_type' => get_class($reference),
                'reference_id' => $reference->id,
                'description' => $description,
                'balance_after' => $newBalance,
                'expires_at' => now()->addDays($this->getExpiryDays()),
            ]);
        });
    }

    /**
     * Redeem points at checkout.
     */
    public function redeem(User $user, int $points, Model $order): ?LoyaltyPoint
    {
        if (!$this->isEnabled() || $points <= 0) {
            return null;
        }

        if ($user->loyalty_points_balance < $points) {
            return null;
        }

        return DB::transaction(function () use ($user, $points, $order) {
            $user->decrement('loyalty_points_balance', $points);
            $newBalance = $user->fresh()->loyalty_points_balance;

            return LoyaltyPoint::create([
                'user_id' => $user->id,
                'points' => -$points,
                'type' => 'redeemed',
                'source' => 'order',
                'reference_type' => get_class($order),
                'reference_id' => $order->id,
                'description' => "Redeemed {$points} points on order #{$order->order_number}",
                'balance_after' => $newBalance,
            ]);
        });
    }

    /**
     * Refund points when order is cancelled/returned.
     */
    public function refund(User $user, int $points, Model $order): ?LoyaltyPoint
    {
        if ($points <= 0) {
            return null;
        }

        return DB::transaction(function () use ($user, $points, $order) {
            $user->increment('loyalty_points_balance', $points);
            $newBalance = $user->fresh()->loyalty_points_balance;

            return LoyaltyPoint::create([
                'user_id' => $user->id,
                'points' => $points,
                'type' => 'refunded',
                'source' => 'order',
                'reference_type' => get_class($order),
                'reference_id' => $order->id,
                'description' => "Refunded {$points} points for order #{$order->order_number}",
                'balance_after' => $newBalance,
            ]);
        });
    }

    /**
     * Calculate discount amount from points.
     */
    public function pointsToDiscount(int $points): float
    {
        return round($points * $this->getRedeemRate(), 2);
    }

    /**
     * Calculate how many points are needed for a discount amount.
     */
    public function discountToPoints(float $amount): int
    {
        return (int) ceil($amount / $this->getRedeemRate());
    }

    /**
     * Get user's points history.
     */
    public function getHistory(User $user, int $limit = 20)
    {
        return LoyaltyPoint::where('user_id', $user->id)
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Expire old points.
     */
    public function expireOldPoints(): int
    {
        $expired = LoyaltyPoint::where('type', 'earned')
            ->where('points', '>', 0)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->get();

        $totalExpired = 0;

        foreach ($expired as $point) {
            $user = $point->user;
            if ($user && $user->loyalty_points_balance > 0) {
                $deduct = min($point->points, $user->loyalty_points_balance);
                $user->decrement('loyalty_points_balance', $deduct);

                LoyaltyPoint::create([
                    'user_id' => $user->id,
                    'points' => -$deduct,
                    'type' => 'expired',
                    'source' => 'system',
                    'reference_type' => get_class($point),
                    'reference_id' => $point->id,
                    'description' => "Expired {$deduct} points",
                    'balance_after' => $user->fresh()->loyalty_points_balance,
                ]);

                $totalExpired += $deduct;
            }

            // Mark original as processed by setting expires_at to past
            $point->update(['expires_at' => now()->subDay()]);
        }

        return $totalExpired;
    }
}
