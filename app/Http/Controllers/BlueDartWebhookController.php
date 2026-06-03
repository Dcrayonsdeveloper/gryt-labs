<?php

namespace App\Http\Controllers;

use App\Events\OrderDelivered;
use App\Models\Order;
use App\Services\BlueDartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BlueDartWebhookController extends Controller
{
    public function handle(Request $request, BlueDartService $bluedart): JsonResponse
    {
        $payload = $request->all();

        Log::info('BlueDart webhook received', ['payload' => $payload]);

        $waybill = $payload['AWBNo'] ?? $payload['awb_no'] ?? $payload['waybill'] ?? null;
        $rawStatus = $payload['Status'] ?? $payload['status'] ?? $payload['StatusType'] ?? '';

        if (!$waybill) {
            return response()->json(['status' => 'ignored', 'reason' => 'No AWB number']);
        }

        $order = Order::where('tracking_number', $waybill)->first();

        if (!$order) {
            Log::warning('BlueDart webhook: order not found for AWB', ['awb' => $waybill]);
            return response()->json(['status' => 'ignored', 'reason' => 'Order not found']);
        }

        $newStatus = $bluedart->mapStatus($rawStatus);

        if ($newStatus && $newStatus !== $order->status) {
            $oldStatus = $order->status;

            $order->update([
                'status' => $newStatus,
                'delivered_at' => $newStatus === 'delivered' ? now() : $order->delivered_at,
            ]);

            try {
                if ($newStatus === 'delivered' && class_exists(OrderDelivered::class)) {
                    OrderDelivered::dispatch($order);
                }
            } catch (\Exception $e) {
                Log::error('OrderDelivered event failed (BlueDart webhook)', ['order' => $order->id, 'error' => $e->getMessage()]);
            }

            Log::info("BlueDart webhook: order {$order->order_number} status {$oldStatus} → {$newStatus}");
        }

        return response()->json(['status' => 'ok']);
    }
}
