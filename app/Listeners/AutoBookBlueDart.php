<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Models\Setting;
use App\Services\BlueDartService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class AutoBookBlueDart implements ShouldQueue
{
    public function __construct(
        private BlueDartService $bluedart
    ) {}

    public function handle(OrderPlaced $event): void
    {
        if (!Setting::get('bluedart_enabled', false)) {
            return;
        }

        if (!Setting::get('bluedart_auto_book', false)) {
            return;
        }

        $order = $event->order;

        // Skip if already booked with any carrier
        if (!empty($order->tracking_number)) {
            return;
        }

        // Skip if already pushed to Shiprocket (avoid double-booking)
        if (!empty($order->shiprocket_order_id)) {
            return;
        }

        $paymentMethod = $order->metadata['payment_method'] ?? 'cod';
        $isCod = in_array($paymentMethod, ['cod', 'partial_pay', 'shiprocket_cod', 'shiprocket_cod_partial'], true);

        // For prepaid orders, only book when payment is confirmed
        if (!$isCod && $order->payment_status !== 'paid') {
            return;
        }

        if (!$this->bluedart->isConfigured()) {
            Log::warning('BlueDart auto-book enabled but not configured', ['order_id' => $order->id]);
            return;
        }

        try {
            $result = $this->bluedart->bookDelivery($order);
        } catch (\Throwable $e) {
            Log::error('BlueDart auto-book exception', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            return;
        }

        if (!($result['success'] ?? false) || empty($result['waybill'])) {
            Log::error('BlueDart auto-book failed', [
                'order_id' => $order->id,
                'message' => $result['message'] ?? 'unknown',
            ]);
            return;
        }

        $order->update([
            'tracking_number' => $result['waybill'],
            'carrier' => 'BlueDart',
            'status' => 'shipped',
            'shipped_at' => now(),
        ]);

        if (class_exists(\App\Models\OrderShipment::class)) {
            \App\Models\OrderShipment::create([
                'order_id' => $order->id,
                'carrier' => 'BlueDart',
                'tracking_number' => $result['waybill'],
                'status' => 'shipped',
                'shipped_at' => now(),
            ]);
        }

        try {
            \App\Events\OrderShipped::dispatch($order, $result['waybill']);
        } catch (\Exception $e) {
            Log::error('OrderShipped event failed (BlueDart auto-book)', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('BlueDart auto-book success', [
            'order_id' => $order->id,
            'awb' => $result['waybill'],
        ]);
    }
}
