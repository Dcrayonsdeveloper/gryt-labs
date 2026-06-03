<?php

namespace App\Http\Controllers;

use App\Events\OrderDelivered;
use App\Events\OrderShipped;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ShiprocketWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        // Optional shared-secret token verification
        $expected = Setting::get('shiprocket_webhook_token', '');
        if (!empty($expected)) {
            $provided = $request->header('X-Api-Key') ?? $request->input('token');
            if (!hash_equals($expected, (string) $provided)) {
                return response()->json(['status' => 'unauthorized'], 401);
            }
        }

        $payload = $request->all();
        Log::info('Shiprocket webhook received', ['payload' => $payload]);

        $awb = $payload['awb'] ?? $payload['awb_code'] ?? null;
        $orderNumber = $payload['order_id'] ?? null;
        $statusRaw = $payload['current_status'] ?? $payload['shipment_status'] ?? '';
        $courier = $payload['courier_name'] ?? null;
        $trackingUrl = $payload['etd'] ?? $payload['tracking_url'] ?? null;

        $order = null;
        if ($awb) {
            $order = Order::where('shiprocket_awb', $awb)->first();
        }
        if (!$order && $orderNumber) {
            $order = Order::where('order_number', $orderNumber)->first();
        }

        if (!$order) {
            Log::warning('Shiprocket webhook: order not found', ['awb' => $awb, 'order_number' => $orderNumber]);
            return response()->json(['status' => 'ignored', 'reason' => 'Order not found']);
        }

        // Persist tracking metadata
        $updates = [];
        if ($awb && empty($order->shiprocket_awb)) {
            $updates['shiprocket_awb'] = $awb;
        }
        if ($courier && empty($order->shiprocket_courier)) {
            $updates['shiprocket_courier'] = $courier;
        }
        if ($trackingUrl && empty($order->tracking_url)) {
            $updates['tracking_url'] = $trackingUrl;
        }

        $newStatus = $this->mapStatus(strtolower((string) $statusRaw));

        if ($newStatus && $newStatus !== $order->status) {
            $updates['status'] = $newStatus;
            if ($newStatus === 'shipped' && empty($order->shipped_at)) {
                $updates['shipped_at'] = now();
            }
            if ($newStatus === 'delivered') {
                $updates['delivered_at'] = now();
            }
        }

        if (!empty($updates)) {
            $order->forceFill($updates)->save();

            try {
                if (($updates['status'] ?? null) === 'shipped' && class_exists(OrderShipped::class)) {
                    OrderShipped::dispatch($order);
                }
                if (($updates['status'] ?? null) === 'delivered' && class_exists(OrderDelivered::class)) {
                    OrderDelivered::dispatch($order);
                }
            } catch (\Exception $e) {
                Log::error('Shiprocket webhook event dispatch failed', [
                    'order' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json(['status' => 'ok']);
    }

    private function mapStatus(string $status): ?string
    {
        return match (true) {
            str_contains($status, 'delivered') => 'delivered',
            str_contains($status, 'out for delivery') => 'out_for_delivery',
            str_contains($status, 'in transit') || str_contains($status, 'shipped') || str_contains($status, 'pickup generated') || str_contains($status, 'picked up') => 'shipped',
            str_contains($status, 'rto') || str_contains($status, 'returned') => 'returned',
            str_contains($status, 'cancel') => 'cancelled',
            default => null,
        };
    }
}
