<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Models\Setting;
use App\Services\ShiprocketService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class PushOrderToShiprocket implements ShouldQueue
{
    public function __construct(
        private ShiprocketService $shiprocket
    ) {}

    public function handle(OrderPlaced $event): void
    {
        if (!Setting::get('shiprocket_enabled', false)) {
            return;
        }

        $order = $event->order;

        // Skip if already pushed
        if (!empty($order->shiprocket_order_id)) {
            return;
        }

        // Skip Shiprocket Checkout orders — they are already in Shiprocket's system.
        // Pushing again would create a duplicate shipment.
        $gateway = $order->metadata['payment_gateway'] ?? '';
        $method  = $order->metadata['payment_method'] ?? '';
        if ($gateway === 'shiprocket' || str_starts_with($method, 'shiprocket_')) {
            return;
        }

        // payment_method is stored inside the metadata JSON, not as a column
        $paymentMethod = $order->metadata['payment_method'] ?? 'cod';
        $isCod = in_array($paymentMethod, ['cod', 'partial_pay'], true);

        // For prepaid orders, only push when payment is confirmed
        if (!$isCod && $order->payment_status !== 'paid') {
            return;
        }

        if (!$this->shiprocket->isConfigured()) {
            Log::warning('Shiprocket enabled but not configured', ['order_id' => $order->id]);
            return;
        }

        $result = $this->shiprocket->createOrder($order);

        if (!($result['success'] ?? false)) {
            Log::error('Shiprocket order push failed', [
                'order_id' => $order->id,
                'message' => $result['message'] ?? 'unknown',
            ]);
            return;
        }

        $order->forceFill([
            'shiprocket_order_id' => $result['shiprocket_order_id'] ?? null,
            'shiprocket_shipment_id' => $result['shipment_id'] ?? null,
            'shiprocket_awb' => $result['awb'] ?? null,
            'shiprocket_pushed_at' => now(),
        ])->save();
    }
}
