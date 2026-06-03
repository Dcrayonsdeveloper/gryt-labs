<?php

namespace App\Listeners;

use App\Events\OrderDelivered;
use App\Events\OrderPlaced;
use App\Events\OrderShipped;
use App\Events\OrderStatusChanged;
use App\Events\RefundProcessed;
use App\Events\ReturnRequested;
use App\Mail\OrderCancelled;
use App\Mail\OrderConfirmation;
use App\Mail\OrderDelivered as OrderDeliveredMail;
use App\Mail\OrderShipped as OrderShippedMail;
use App\Mail\RefundProcessed as RefundProcessedMail;
use App\Mail\ReturnApproved;
use App\Models\Order;
use App\Models\Setting;
use App\Services\NotificationService;
use App\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendOrderNotification implements ShouldQueue
{
    /**
     * Max retry attempts if job fails.
     */
    public int $tries = 3;

    /**
     * Retry after 30 seconds on failure.
     */
    public int $backoff = 30;

    public function __construct(
        private NotificationService $notificationService,
        private WhatsAppService $whatsapp,
    ) {}

    /**
     * Per-event delay. 30s for Shiprocket Checkout (down from 150s) — covers
     * the most common race where the webhook arrives 0-10s after the callback
     * creates the order, without making the customer wait minutes.
     *
     * When the order already has customer data at creation time (callback
     * merged it from earlier-arrived events), fire IMMEDIATELY: the data is
     * there, no point waiting.
     *
     * Webhook arriving AFTER this listener fires is OK — it enriches the order
     * and re-dispatches OrderPlaced; idempotency via metadata.confirmation_sent_at
     * prevents double-send.
     */
    public function withDelay(object $event): int
    {
        if ($event instanceof OrderPlaced && $event->source === 'shiprocket_checkout') {
            $order = $event->order;
            $hasData = !empty($order->guest_email)
                || !empty($order->guest_phone)
                || !empty($order->guest_name)
                || !empty($order->user_id);
            return $hasData ? 0 : 30;
        }
        return 0;
    }

    public function handleOrderPlaced(OrderPlaced $event): void
    {
        $order = $event->order->fresh() ?? $event->order;
        $user = $order->user;

        // Idempotency: skip if already notified (handles late re-trigger from
        // SyncShiprocketCustomerDetails after Shiprocket finally surfaces the customer).
        if (!empty($order->metadata['confirmation_sent_at'] ?? null)) {
            return;
        }

        // Defer when Shiprocket Checkout has not yet given us any customer identifier.
        // Without this guard the admin email goes out with Name/Email/Phone all blank.
        // SyncShiprocketCustomerDetails re-triggers this listener once the data arrives.
        if ($event->source === 'shiprocket_checkout'
            && !$user
            && empty($order->guest_phone)
            && empty($order->guest_email)
            && empty($order->guest_name)
        ) {
            Log::info('Order notification deferred — Shiprocket customer details not yet captured', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);
            return;
        }

        // Pick the customer's email. ALWAYS prefer order.guest_email (captured fresh
        // on this checkout) over user.email (may be a stale test address — Shiprocket
        // phone-match has linked orders to old accounts with throwaway emails like
        // caneli1656@nyspring.com). Fall back to user.email only when guest_email
        // is missing.
        $customerEmail = $order->guest_email ?: $user?->email;

        if ($user) {
            // In-app for the linked user (always)
            $this->notificationService->notifyInApp(
                $user,
                'order_placed',
                'Order Confirmed',
                "Your order #{$order->order_number} has been confirmed.",
                ['order_id' => $order->id]
            );
        }

        if ($customerEmail && filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            try {
                Mail::to($customerEmail)->send(new OrderConfirmation($order));
            } catch (\Throwable $e) {
                Log::error('Customer order email failed', [
                    'order'    => $order->id,
                    'to'       => $customerEmail,
                    'error'    => $e->getMessage(),
                ]);
                // Mark a failure marker so the idempotency check won't suppress retry,
                // and so we can detect silent failures later.
                $meta = $order->metadata ?? [];
                $meta['confirmation_failed_at'] = now()->toIso8601String();
                $meta['confirmation_failed_reason'] = $e->getMessage();
                $order->update(['metadata' => $meta]);
                throw $e; // re-throw so the queued listener retries on backoff
            }
        }

        $customerPhone = $user?->phone ?? $order->guest_phone;
        $customerName = $user?->first_name ?? $order->guest_name ?? 'Customer';

        // Email to admin
        $adminEmail = Setting::get('admin_email', '') ?: Setting::get('mail_from_address', '');
        if ($adminEmail) {
            try {
                Mail::to($adminEmail)->send(new OrderConfirmation($order, true));
            } catch (\Throwable $e) {
                \Log::error('Admin order email failed', ['order' => $order->id, 'error' => $e->getMessage()]);
            }
        }

        // WhatsApp to customer — order confirmation
        if ($customerPhone) {
            $brand = Setting::get('site_name', config('app.name'));
            $itemsSummary = $order->items->map(fn($item) => "• {$item->product_name} x{$item->quantity} — Rs" . number_format($item->total, 0))->implode("\n");
            $paymentInfo = match ($order->payment_status) {
                'paid' => 'Paid online',
                'partial' => 'Rs' . number_format($order->paid_amount, 0) . ' paid, Rs' . number_format($order->total - $order->paid_amount, 0) . ' COD',
                default => 'Cash on Delivery',
            };

            $this->whatsapp->sendText(
                $customerPhone,
                "Hi {$customerName}! Thank you for your {$brand} order.\n\n"
                . "Order: #{$order->order_number}\n"
                . "Items:\n{$itemsSummary}\n"
                . "Total: Rs" . number_format($order->total, 0) . "\n"
                . "Payment: {$paymentInfo}\n\n"
                . "We'll notify you once it's shipped!"
            );
        }

        // WhatsApp to admin — free-form is OK since admin has messaged the number
        $adminPhone = Setting::get('admin_whatsapp_phone', '');
        if ($adminPhone) {
            $itemsSummary = $order->items->map(fn($item) => "• {$item->product_name} x{$item->quantity} — Rs" . number_format($item->total, 0))->implode("\n");
            $address = $order->shipping_address_snapshot ?? [];
            $addressLine = ($address['address_line_1'] ?? '') . ', ' . ($address['city'] ?? '') . ' ' . ($address['postal_code'] ?? '');

            $this->whatsapp->sendText(
                $adminPhone,
                "NEW ORDER #{$order->order_number}\n\n"
                . "Customer: {$customerName}\n"
                . "Phone: {$customerPhone}\n"
                . "Payment: " . ($order->payment_status === 'paid' ? 'Prepaid' : 'COD') . "\n\n"
                . "Items:\n{$itemsSummary}\n\n"
                . "Total: Rs" . number_format($order->total, 0) . "\n"
                . "Ship to: {$addressLine}\n\n"
                . "Manage: " . url("/admin/orders/{$order->id}")
            );
        }

        // Mark notified so a late re-trigger (after customer-details sync) doesn't double-send.
        $meta = $order->metadata ?? [];
        $meta['confirmation_sent_at'] = now()->toIso8601String();
        $order->update(['metadata' => $meta]);
    }

    public function handleOrderShipped(OrderShipped $event): void
    {
        $order = $event->order;
        $user = $order->user;

        if ($user) {
            $this->notificationService->notify($user, 'order_shipped', [
                'title' => 'Order Shipped',
                'content' => "Your order #{$order->order_number} has been shipped.",
                'order_id' => $order->id,
                'tracking_number' => $event->trackingNumber,
            ], new OrderShippedMail($order, $event->trackingNumber));
        } elseif ($order->guest_email) {
            Mail::to($order->guest_email)->send(new OrderShippedMail($order, $event->trackingNumber));
        }

        // WhatsApp — order shipped template
        $customerPhone = $user?->phone ?? $order->guest_phone;
        $customerName = $user?->first_name ?? $order->guest_name ?? 'Customer';
        $tenantId = config('app.tenant_id', '');
        if ($customerPhone) {
            $tracking = $event->trackingNumber ?? 'N/A';
            $delivery = $order->expected_delivery_date?->format('d M Y') ?? 'Soon';
            $brand = Setting::get('site_name', config('app.name'));
            $trackUrl = $order->tracking_url ?: url("/track-order");
            $this->whatsapp->sendTemplate(
                $customerPhone,
                Setting::get('whatsapp_order_shipped_template', '') ?: ($tenantId . '_order_shipped'),
                [
                    ['type' => 'text', 'text' => $customerName],
                    ['type' => 'text', 'text' => $order->order_number],
                    ['type' => 'text', 'text' => $tracking],
                    ['type' => 'text', 'text' => $delivery],
                ],
                "Hi {$customerName}! Your {$brand} order #{$order->order_number} has been shipped. Tracking: {$tracking}. Expected delivery: {$delivery}. Track at: {$trackUrl}"
            );
        }
    }

    public function handleOrderDelivered(OrderDelivered $event): void
    {
        $order = $event->order;
        $user = $order->user;

        if ($user) {
            $this->notificationService->notify($user, 'order_delivered', [
                'title' => 'Order Delivered',
                'content' => "Your order #{$order->order_number} has been delivered.",
                'order_id' => $order->id,
            ], new OrderDeliveredMail($order));
        } elseif ($order->guest_email) {
            Mail::to($order->guest_email)->send(new OrderDeliveredMail($order));
        }

        // WhatsApp — order delivered template
        $customerPhone = $user?->phone ?? $order->guest_phone;
        $customerName = $user?->first_name ?? $order->guest_name ?? 'Customer';
        if ($customerPhone) {
            $brand = Setting::get('site_name', config('app.name'));
            $this->whatsapp->sendTemplate(
                $customerPhone,
                Setting::get('whatsapp_delivered_template', '') ?: (config('app.tenant_id', '') . '_delivered'),
                [
                    ['type' => 'text', 'text' => $customerName],
                    ['type' => 'text', 'text' => $order->order_number],
                ],
                "Hi {$customerName}! Your {$brand} order #{$order->order_number} has been delivered. We hope you love it! Thank you for shopping with us."
            );
        }
    }

    public function handleOrderStatusChanged(OrderStatusChanged $event): void
    {
        $order = $event->order;
        $user = $order->user;
        $customerPhone = $user?->phone ?? $order->guest_phone;
        $customerName = $user?->first_name ?? $order->guest_name ?? 'Customer';
        $tenantId = config('app.tenant_id', '');

        if ($event->newStatus === 'cancelled') {
            // Email + in-app for registered users; email for guests.
            if ($user) {
                $this->notificationService->notify($user, 'order_cancelled', [
                    'title' => 'Order Cancelled',
                    'content' => "Your order #{$order->order_number} has been cancelled.",
                    'order_id' => $order->id,
                ], new OrderCancelled($order));
            } elseif ($order->guest_email) {
                Mail::to($order->guest_email)->send(new OrderCancelled($order));
            }

            // WhatsApp — order cancelled (registered + guest)
            if ($customerPhone) {
                $brand = Setting::get('site_name', config('app.name'));
                $amount = number_format($order->total, 0);
                $this->whatsapp->sendTemplate(
                    $customerPhone,
                    Setting::get('whatsapp_order_cancelled_template', '') ?: ($tenantId . '_order_cancelled'),
                    [
                        ['type' => 'text', 'text' => $customerName],
                        ['type' => 'text', 'text' => $order->order_number],
                        ['type' => 'text', 'text' => $amount],
                    ],
                    "Hi {$customerName}, your {$brand} order #{$order->order_number} (Rs{$amount}) has been cancelled. If you didn't request this, please contact us."
                );
            }
        } elseif ($event->newStatus === 'out_for_delivery') {
            if ($user) {
                $this->notificationService->notifyInApp($user, 'order_out_for_delivery',
                    'Out for Delivery',
                    "Your order #{$order->order_number} is out for delivery today!",
                    ['order_id' => $order->id]
                );
            }

            // WhatsApp — out for delivery (registered + guest)
            if ($customerPhone) {
                $brand = Setting::get('site_name', config('app.name'));
                $this->whatsapp->sendTemplate(
                    $customerPhone,
                    Setting::get('whatsapp_out_for_delivery_template', '') ?: ($tenantId . '_out_for_delivery'),
                    [
                        ['type' => 'text', 'text' => $customerName],
                        ['type' => 'text', 'text' => $order->order_number],
                    ],
                    "Hi {$customerName}! Great news - your {$brand} order #{$order->order_number} is out for delivery today. Please keep your phone handy."
                );
            }
        } elseif ($event->newStatus === 'returned') {
            if ($user) {
                $this->notificationService->notifyInApp($user, 'order_returned',
                    'Delivery Failed',
                    "Your order #{$order->order_number} could not be delivered and is being returned. Our team will contact you.",
                    ['order_id' => $order->id]
                );
            }

            // WhatsApp — RTO notification (registered + guest)
            if ($customerPhone) {
                $brand = Setting::get('site_name', config('app.name'));
                $this->whatsapp->sendTemplate(
                    $customerPhone,
                    Setting::get('whatsapp_order_rto_template', '') ?: ($tenantId . '_order_rto'),
                    [
                        ['type' => 'text', 'text' => $customerName],
                        ['type' => 'text', 'text' => $order->order_number],
                    ],
                    "Hi {$customerName}, unfortunately your {$brand} order #{$order->order_number} could not be delivered and is being returned. Our team will contact you shortly."
                );
            }
        } elseif ($event->newStatus === 'packed') {
            if ($user) {
                $this->notificationService->notifyInApp($user, 'order_packed',
                    'Order Packed',
                    "Your order #{$order->order_number} has been packed and is ready for dispatch.",
                    ['order_id' => $order->id]
                );
            }

            // WhatsApp — order packed (registered + guest)
            if ($customerPhone) {
                $brand = Setting::get('site_name', config('app.name'));
                $itemsSummary = $order->items->map(fn($item) => "• {$item->product_name} x{$item->quantity}")->implode("\n");
                $this->whatsapp->sendTemplate(
                    $customerPhone,
                    Setting::get('whatsapp_order_packed_template', '') ?: ($tenantId . '_order_packed'),
                    [
                        ['type' => 'text', 'text' => $customerName],
                        ['type' => 'text', 'text' => $order->order_number],
                    ],
                    "Hi {$customerName}! Your {$brand} order #{$order->order_number} has been packed and is ready for dispatch.\n\n{$itemsSummary}\n\nWe'll notify you with tracking once it ships."
                );
            }
        }
    }

    public function handleReturnRequested(ReturnRequested $event): void
    {
        $return = $event->return;
        $user = $return->order?->user;
        if (!$user) return;

        if ($return->status === 'approved') {
            $this->notificationService->notify($user, 'return_approved', [
                'title' => 'Return Approved',
                'content' => "Your return request #{$return->return_number} has been approved.",
                'return_id' => $return->id,
            ], new ReturnApproved($return));
        } else {
            $this->notificationService->notifyInApp($user, 'return_' . $return->status,
                'Return Update',
                "Your return request #{$return->return_number} status: {$return->status}.",
                ['return_id' => $return->id]
            );
        }
    }

    public function handleRefundProcessed(RefundProcessed $event): void
    {
        $return = $event->return;
        $order = $return->order;
        $user = $order?->user;

        // Customer email — registered or guest
        if ($user) {
            $this->notificationService->notify($user, 'refund_processed', [
                'title' => 'Refund Processed',
                'content' => 'Your refund of ' . format_price($event->amount) . ' has been processed.',
                'return_id' => $return->id,
                'amount' => $event->amount,
            ], new RefundProcessedMail($return, $event->amount));
        } elseif ($order?->guest_email) {
            Mail::to($order->guest_email)->send(new RefundProcessedMail($return, $event->amount));
        }

        // Admin email + WhatsApp
        $adminEmail = Setting::get('admin_email', '') ?: Setting::get('mail_from_address', '');
        if ($adminEmail) {
            try {
                Mail::to($adminEmail)->send(new RefundProcessedMail($return, $event->amount));
            } catch (\Throwable $e) {
                Log::error('Admin refund email failed', ['return' => $return->id, 'error' => $e->getMessage()]);
            }
        }

        $adminPhone = Setting::get('admin_whatsapp_phone', '');
        if ($adminPhone) {
            $customerName = $user?->full_name ?? $order?->guest_name ?? 'Guest';
            $this->whatsapp->sendText(
                $adminPhone,
                "REFUND PROCESSED\n\n"
                . "Return: #{$return->return_number}\n"
                . "Order: #{$order?->order_number}\n"
                . "Customer: {$customerName}\n"
                . "Amount: " . format_price($event->amount) . "\n"
                . "Method: {$event->method}\n\n"
                . "View: " . url("/admin/returns/{$return->id}")
            );
        }
    }

}
