<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Services\AnalyticsService;
use Illuminate\Support\Facades\Log;

class TrackOrderPlacedAnalytics
{
    public function __construct(private AnalyticsService $analytics) {}

    public function handle(OrderPlaced $event): void
    {
        // Web + Shiprocket Checkout orders fire CAPI from their controllers
        // (CheckoutController / CallbackController / WebhookController) which have
        // access to fbc/fbp cookie fallback from abandoned checkout — critical for
        // Meta ad attribution. Skip here to let the controller handle it.
        if (in_array($event->source, ['web', 'shiprocket_checkout'])) {
            return;
        }

        try {
            $order = $event->order;
            $order->load('items', 'user');

            // Guard: skip if already sent by another path (webhook, callback)
            $meta = $order->metadata ?? [];
            if (!empty($meta['capi_sent_at'])) {
                return;
            }

            // Use order_number as eventId (same as controllers) so Meta dedup works.
            // The service stamps capi_* metadata itself from Facebook's actual response.
            $eventId = $order->order_number;
            $this->analytics->trackPurchase($order, request(), $eventId, [], 'event_listener');
        } catch (\Throwable $e) {
            Log::warning('TrackOrderPlacedAnalytics failed', [
                'order_id' => $event->order->id ?? null,
                'order_number' => $event->order->order_number ?? null,
                'source' => $event->source,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
