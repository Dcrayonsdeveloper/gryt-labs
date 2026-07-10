<?php

namespace App\Listeners;

use App\Mail\ReviewCouponReward;
use App\Models\Coupon;
use App\Models\Review;
use App\Models\Setting;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SendCouponAfterReview implements ShouldQueue
{
    public function handle(Review $review): void
    {
        if (!Setting::get('review_coupon_enabled', true)) {
            return;
        }

        // Only reward non-generated reviews (real human reviews)
        if ($review->is_generated) {
            return;
        }

        $email = $review->user?->email ?? $review->guest_email;
        if (!$email) {
            return;
        }

        // Only reward customers we actually invited (i.e. who bought and had the
        // order delivered), and only once per invitation. Without this, every
        // review from the same email minted a fresh discount coupon.
        $invitation = DB::table('review_invitations')
            ->where('email', $email)
            ->whereNull('reviewed_at')
            ->whereNull('coupon_id')
            ->orderBy('id')
            ->first();

        if (!$invitation) {
            return;
        }

        // Create unique coupon
        $couponValue = Setting::get('review_coupon_value', 5);
        $coupon = Coupon::create([
            'code' => 'THANKS-' . strtoupper(Str::random(6)),
            'name' => 'Review Reward - ' . $couponValue . '% Off',
            'description' => 'Thank you for your review! Enjoy ' . $couponValue . '% off your next order.',
            'type' => 'percentage',
            'value' => $couponValue,
            'min_order_amount' => 0,
            'usage_limit' => 1,
            'usage_per_user' => 1,
            'is_active' => true,
            'starts_at' => now(),
            'expires_at' => now()->addDays(60),
        ]);

        // Consume this invitation so it cannot be rewarded twice
        DB::table('review_invitations')
            ->where('id', $invitation->id)
            ->update([
                'reviewed_at' => now(),
                'coupon_id' => $coupon->id,
                'updated_at' => now(),
            ]);

        Mail::to($email)->queue(new ReviewCouponReward($review, $coupon));
    }
}
