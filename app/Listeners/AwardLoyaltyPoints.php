<?php

namespace App\Listeners;

use App\Events\OrderDelivered;
use App\Services\LoyaltyService;
use Illuminate\Support\Facades\Log;

class AwardLoyaltyPoints
{
    public function __construct(private LoyaltyService $loyaltyService) {}

    public function handle(OrderDelivered $event): void
    {
        $order = $event->order;

        if (!$order->user_id || !$this->loyaltyService->isEnabled()) {
            return;
        }

        try {
            $this->loyaltyService->awardForOrder($order->user, $order);
        } catch (\Exception $e) {
            Log::warning('Failed to award loyalty points', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
