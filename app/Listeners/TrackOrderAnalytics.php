<?php

namespace App\Listeners;

use App\Events\OrderDelivered;
use App\Services\AnalyticsService;

class TrackOrderAnalytics
{
    public function __construct(private AnalyticsService $analytics) {}

    public function handle(OrderDelivered $event): void
    {
        // Purchase CAPI fires at order-creation time (CallbackController / WebhookController / CheckoutController).
        // Do NOT fire again at delivery — it causes duplicate Purchase events in Meta.
    }
}
