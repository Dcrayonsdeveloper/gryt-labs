<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RazorpayWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();
        $event = $payload["event"] ?? "";

        Log::info("Razorpay webhook received", ["event" => $event]);

        match ($event) {
            "payment.authorized", "payment.captured" => $this->handlePaymentCaptured($payload),
            "payment.failed" => $this->handlePaymentFailed($payload),
            "order.paid" => $this->handleOrderPaid($payload),
            default => null,
        };

        return response()->json(["status" => "ok"]);
    }

    private function handlePaymentCaptured(array $payload): void
    {
        $payment = $payload["payload"]["payment"]["entity"] ?? [];
        $razorpayOrderId = $payment["order_id"] ?? null;
        $razorpayPaymentId = $payment["id"] ?? null;
        $amount = ($payment["amount"] ?? 0) / 100;

        if (!$razorpayOrderId) return;

        $order = Order::where("razorpay_order_id", $razorpayOrderId)->first();
        if (!$order) {
            Log::warning("Webhook: Order not found", ["razorpay_order_id" => $razorpayOrderId]);
            return;
        }

        if ($order->payment_status !== "paid") {
            $order->update([
                "payment_status" => "paid",
                "razorpay_payment_id" => $razorpayPaymentId,
                "paid_amount" => $amount,
            ]);
            Log::info("Webhook: Payment confirmed", ["order" => $order->order_number, "payment_id" => $razorpayPaymentId]);
        }
    }

    private function handlePaymentFailed(array $payload): void
    {
        $payment = $payload["payload"]["payment"]["entity"] ?? [];
        $razorpayOrderId = $payment["order_id"] ?? null;
        if (!$razorpayOrderId) return;

        $order = Order::where("razorpay_order_id", $razorpayOrderId)->first();
        if ($order && $order->payment_status === "pending") {
            $order->update(["payment_status" => "failed"]);
            Log::info("Webhook: Payment failed", ["order" => $order->order_number]);
        }
    }

    private function handleOrderPaid(array $payload): void
    {
        $order_entity = $payload["payload"]["order"]["entity"] ?? [];
        $razorpayOrderId = $order_entity["id"] ?? null;
        if (!$razorpayOrderId) return;

        $order = Order::where("razorpay_order_id", $razorpayOrderId)->first();
        if ($order && $order->payment_status !== "paid") {
            $order->update(["payment_status" => "paid"]);
            Log::info("Webhook: Order paid", ["order" => $order->order_number]);
        }
    }
}
