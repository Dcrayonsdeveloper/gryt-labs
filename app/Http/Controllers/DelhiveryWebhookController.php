<?php

namespace App\Http\Controllers;

use App\Events\OrderDelivered;
use App\Events\OrderShipped;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DelhiveryWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();

        Log::info('Delhivery webhook received', ['payload' => $payload]);

        $waybill = $payload['waybill'] ?? $payload['Waybill'] ?? null;
        $status = $payload['current_status'] ?? $payload['Status'] ?? null;
        $statusType = strtolower($payload['current_status_type'] ?? $payload['StatusType'] ?? '');

        if (!$waybill) {
            return response()->json(['status' => 'ignored', 'reason' => 'No waybill']);
        }

        $order = Order::where('tracking_number', $waybill)->first();

        if (!$order) {
            Log::warning('Delhivery webhook: order not found for waybill', ['waybill' => $waybill]);
            return response()->json(['status' => 'ignored', 'reason' => 'Order not found']);
        }

        // Map Delhivery status to our order status
        $newStatus = $this->mapStatus($statusType, $status);

        if ($newStatus && $newStatus !== $order->status) {
            $oldStatus = $order->status;

            $order->update([
                'status' => $newStatus,
                'delivered_at' => $newStatus === 'delivered' ? now() : $order->delivered_at,
            ]);

            // Dispatch events for notifications (WhatsApp + email)
            try {
                if ($newStatus === 'delivered' && class_exists(OrderDelivered::class)) {
                    OrderDelivered::dispatch($order);
                }
            } catch (\Exception $e) {
                Log::error('OrderDelivered event failed (webhook)', ['order' => $order->id, 'error' => $e->getMessage()]);
            }

            Log::info("Delhivery webhook: order {$order->order_number} status {$oldStatus} → {$newStatus}");
        }

        return response()->json(['status' => 'ok']);
    }

    private function mapStatus(string $statusType, ?string $status): ?string
    {
        return match (true) {
            str_contains($statusType, 'delivered') => 'delivered',
            str_contains($statusType, 'out_for_delivery') || str_contains($statusType, 'out for delivery') => 'out_for_delivery',
            str_contains($statusType, 'in_transit') || str_contains($statusType, 'in transit') => 'shipped',
            str_contains($statusType, 'dispatched') || str_contains($statusType, 'manifested') => 'shipped',
            str_contains($statusType, 'rto') || str_contains($statusType, 'returned') => 'returned',
            str_contains($statusType, 'cancelled') => 'cancelled',
            str_contains($statusType, 'pending') => null, // Don't change
            default => null,
        };
    }
}
