<?php

namespace App\Listeners;

use App\Events\RefundProcessed;
use App\Models\GiftCard;
use App\Models\GiftCardUsage;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * When a refund is processed for an order that used a gift card, re-credit the
 * unrefunded portion of the gift-card redemption back to the original card.
 *
 * Idempotent via gift_card_usages.refunded_at — running this twice for the same
 * order is a no-op on the second pass.
 */
class RestoreGiftCardOnRefund
{
    public function handle(RefundProcessed $event): void
    {
        $order = $event->return->order;
        if (! $order instanceof Order) {
            return;
        }

        self::reconcile($order, (float) $event->amount);
    }

    /**
     * Usable from both the admin-refund listener and the Razorpay webhook path.
     * Caps the credit at the unrefunded remainder of the gift-card usage AND
     * at the refund amount, so partial refunds credit proportionally.
     */
    public static function reconcile(Order $order, float $refundAmount): void
    {
        if ($refundAmount <= 0) {
            return;
        }

        $usage = GiftCardUsage::where('order_id', $order->id)
            ->whereNull('refunded_at')
            ->first();

        if (! $usage) {
            return;
        }

        $unrefunded = max(0.0, (float) $usage->amount - (float) $usage->refunded_amount);
        $credit = min($unrefunded, $refundAmount);

        if ($credit <= 0) {
            return;
        }

        try {
            DB::transaction(function () use ($usage, $credit) {
                $card = GiftCard::lockForUpdate()->find($usage->gift_card_id);
                if (! $card) {
                    return;
                }
                $card->credit($credit);

                $usage->update([
                    'refunded_at' => now(),
                    'refunded_amount' => (float) $usage->refunded_amount + $credit,
                ]);
            });

            Log::info('Gift card re-credited on refund', [
                'order_id' => $order->id,
                'gift_card_id' => $usage->gift_card_id,
                'credit_amount' => $credit,
            ]);
        } catch (\Throwable $e) {
            Log::error('Gift card refund re-credit failed', [
                'order_id' => $order->id,
                'gift_card_id' => $usage->gift_card_id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
