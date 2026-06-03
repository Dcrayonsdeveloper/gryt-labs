<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

foreach (['natually', 'ayurvexa'] as $tid) {
    $t = \App\Models\Tenant::find($tid);
    if (!$t) continue;
    tenancy()->initialize($t);

    echo "=== {$tid} ===\n";

    $orders = \App\Models\Order::whereNotNull('shiprocket_order_id')
        ->whereNull('user_id')
        ->where(function ($q) {
            $q->whereNull('guest_name')->orWhere('guest_name', '');
        })
        ->orderByDesc('created_at')
        ->get();

    $fixed = 0;
    foreach ($orders as $order) {
        $srId = $order->shiprocket_order_id;

        // Try to find webhook event with customer data for this order
        $event = \App\Models\ShiprocketCheckoutEvent::where('cart_id', $srId)
            ->whereNotNull('phone')
            ->where('is_duplicate', false)
            ->latest()
            ->first();

        // Also try via abandoned checkout
        $ac = \App\Models\AbandonedCheckout::where('shiprocket_order_id', $srId)->first()
            ?? \App\Models\AbandonedCheckout::where('source', 'shiprocket_checkout')
                ->whereJsonContains('metadata->shiprocket_cart_id', $srId)
                ->first();

        // Also check via bridge table
        if (!$ac) {
            $ac = \App\Models\AbandonedCheckout::findByShiprocketId($srId);
        }

        $name = null;
        $email = null;
        $phone = null;
        $address = null;

        // Extract from event
        if ($event) {
            $payload = $event->raw_payload ?? [];
            $data = $payload['payload'] ?? $payload;
            $shipping = $data['shipping_address'] ?? [];
            $name = trim(($shipping['first_name'] ?? '') . ' ' . ($shipping['last_name'] ?? ''));
            $email = $data['email'] ?? $shipping['email'] ?? null;
            $phone = $data['phone'] ?? $data['phone_number'] ?? $shipping['phone'] ?? $event->phone;
            $address = [
                'name' => $name,
                'phone' => $phone,
                'address_line_1' => $shipping['line1'] ?? '',
                'address_line_2' => $shipping['line2'] ?? '',
                'city' => $shipping['city'] ?? '',
                'state' => $shipping['state'] ?? '',
                'postal_code' => $shipping['pincode'] ?? '',
                'country' => $shipping['country'] ?? 'India',
            ];
        }

        // Fallback to AC data
        if (empty($name) && $ac) {
            $name = $ac->name;
            $email = $email ?: $ac->email;
            $phone = $phone ?: $ac->phone;

            $meta = $ac->metadata ?? [];
            $webhookData = $meta['webhook_success'] ?? $meta['webhook_order_placed'] ?? $meta['webhook_payment_complete'] ?? [];
            if (!empty($webhookData['customer'])) {
                $c = $webhookData['customer'];
                $name = $name ?: $c['name'] ?? null;
                $email = $email ?: $c['email'] ?? null;
                $phone = $phone ?: $c['phone'] ?? null;
            }
        }

        if (empty($name) && empty($phone)) {
            echo "  SKIP {$order->order_number} (sr_id={$srId}) — no data found\n";
            continue;
        }

        $updates = [];
        if ($name) $updates['guest_name'] = $name;
        if ($email) $updates['guest_email'] = $email;
        if ($phone) $updates['guest_phone'] = preg_replace('/\D/', '', $phone);
        if ($address && !empty($address['city'])) {
            $snap = $order->shipping_address_snapshot ?? [];
            if (empty($snap['city'])) {
                $updates['shipping_address_snapshot'] = $address;
            }
        }

        $order->update($updates);
        $fixed++;
        echo "  FIXED {$order->order_number} (sr_id={$srId}) → name={$name} phone={$phone}\n";
    }

    echo "Fixed: {$fixed}/{$orders->count()}\n\n";
}
