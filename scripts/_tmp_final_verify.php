<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

tenancy()->initialize(\App\Models\Tenant::find('natually'));

// 1. Check remaining recovered orders have correct dates
echo "=== 7 Recovered orders — current state ===\n";
$kept = ['#NAT5159','#NAT5160','#NAT5161','#NAT5162','#NAT5165','#NAT5168','#NAT5174'];
$orders = \App\Models\Order::whereIn('order_number', $kept)->orderBy('created_at')->get();
foreach ($orders as $o) {
    echo "{$o->order_number} | {$o->guest_name} | ₹{$o->total} | {$o->created_at}\n";
}

// 2. Re-run the missing order check to see if anything else is still missing
echo "\n=== Re-checking: any more missing orders? ===\n";
$events = \App\Models\ShiprocketCheckoutEvent::where('is_duplicate', false)
    ->whereIn('stage', ['SUCCESS', 'ORDER_PLACED', 'Payment Complete'])
    ->orderByDesc('received_at')
    ->get()
    ->groupBy('cart_id');

$allOrders = \App\Models\Order::all();
$allAcs = \App\Models\AbandonedCheckout::where('source', 'shiprocket_checkout')->get();

$missing = [];
foreach ($events as $cartId => $group) {
    $best = $group->first(fn ($e) => $e->stage === 'SUCCESS') ?? $group->first();
    $payload = $best->raw_payload['payload'] ?? $best->raw_payload ?? [];
    $platformOrderId = $payload['platform_order_id'] ?? null;

    $found = $allOrders->first(function ($o) use ($cartId, $platformOrderId) {
        if ($o->shiprocket_order_id === $cartId) return true;
        if ($platformOrderId && $o->shiprocket_order_id === $platformOrderId) return true;
        $meta = $o->metadata ?? [];
        if (($meta['shiprocket_cart_id'] ?? '') === $cartId) return true;
        if (($meta['shiprocket_checkout_id'] ?? '') === $cartId) return true;
        if ($platformOrderId && ($meta['shiprocket_checkout_id'] ?? '') === $platformOrderId) return true;
        return false;
    });

    // Also check via phone+amount+time match (catch callback-created orders)
    if (!$found && $best->phone) {
        $cleanPhone = substr(preg_replace('/\D/', '', $best->phone), -10);
        $eventTime = $best->received_at;
        $eventTotal = $best->net_payable ?? $best->total_price ?? 0;

        $found = $allOrders->first(function ($o) use ($cleanPhone, $eventTime, $eventTotal) {
            if (empty($o->guest_phone)) return false;
            $orderPhone = substr(preg_replace('/\D/', '', $o->guest_phone), -10);
            if ($orderPhone !== $cleanPhone) return false;
            if (abs((float)$o->total - (float)$eventTotal) > 1.0) return false;
            if (abs($o->created_at->diffInMinutes($eventTime)) > 10) return false;
            return true;
        });
    }

    if (!$found) {
        $ac = $allAcs->first(function ($ac) use ($cartId, $platformOrderId) {
            if ($ac->shiprocket_order_id === $cartId) return true;
            if ($platformOrderId && $ac->shiprocket_order_id === $platformOrderId) return true;
            $meta = $ac->metadata ?? [];
            if (($meta['shiprocket_cart_id'] ?? '') === $cartId) return true;
            return false;
        });
        if ($ac && $ac->order_id) {
            $found = $allOrders->firstWhere('id', $ac->order_id);
        }
    }

    if (!$found) {
        $missing[] = [
            'cart_id' => $cartId,
            'name' => $best->full_name ?: '(unknown)',
            'phone' => $best->phone ?: '(unknown)',
            'total' => $payload['total_amount_payable'] ?? $best->net_payable ?? $best->total_price ?? 0,
            'date' => $best->received_at?->format('Y-m-d H:i'),
            'stage' => $best->stage,
        ];
    }
}

echo "Still missing: " . count($missing) . "\n";
foreach ($missing as $m) {
    echo "  {$m['name']} | {$m['phone']} | ₹{$m['total']} | {$m['stage']} | {$m['date']}\n";
}

// 3. Show full order list sorted by date
echo "\n=== All 60 Natually orders by date ===\n";
$all = \App\Models\Order::whereNotNull('shiprocket_order_id')
    ->orderBy('created_at')
    ->get(['order_number','guest_name','guest_phone','total','payment_status','status','created_at']);
foreach ($all as $o) {
    $tag = in_array($o->order_number, $kept) ? ' [RECOVERED]' : '';
    echo "{$o->order_number} | {$o->guest_name} | " . ($o->guest_phone ?: '(no phone)') . " | ₹{$o->total} | {$o->payment_status} | {$o->created_at}{$tag}\n";
}
