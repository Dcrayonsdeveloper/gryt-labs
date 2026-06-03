<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

tenancy()->initialize(\App\Models\Tenant::find('natually'));

$recoveredNums = ['#NAT5159','#NAT5160','#NAT5161','#NAT5162','#NAT5163','#NAT5164',
    '#NAT5165','#NAT5166','#NAT5167','#NAT5168','#NAT5169','#NAT5170',
    '#NAT5171','#NAT5172','#NAT5173','#NAT5174'];

$recovered = \App\Models\Order::whereIn('order_number', $recoveredNums)->get();
$allOther = \App\Models\Order::whereNotIn('order_number', $recoveredNums)
    ->whereNotNull('shiprocket_order_id')
    ->get();

echo "=== Checking duplicates ===\n\n";

$dupes = [];
$unique = [];

foreach ($recovered as $rec) {
    $match = $allOther->first(function ($other) use ($rec) {
        if (empty($rec->guest_phone) || empty($other->guest_phone)) return false;
        $phonesMatch = substr(preg_replace('/\D/', '', $rec->guest_phone), -10)
            === substr(preg_replace('/\D/', '', $other->guest_phone), -10);
        $amountsClose = abs((float)$rec->total - (float)$other->total) < 1.0;
        $timesClose = abs($rec->created_at->diffInMinutes($other->created_at)) <= 5;
        return $phonesMatch && $amountsClose && $timesClose;
    });

    if ($match) {
        $dupes[] = ['recovered' => $rec, 'existing' => $match];
        echo "DUPE: {$rec->order_number} ({$rec->guest_name}, ₹{$rec->total}, {$rec->created_at})\n";
        echo "  OF: {$match->order_number} ({$match->guest_name}, ₹{$match->total}, {$match->created_at})\n\n";
    } else {
        $unique[] = $rec;
        echo "KEEP: {$rec->order_number} ({$rec->guest_name}, ₹{$rec->total}, {$rec->created_at})\n\n";
    }
}

echo "=== SUMMARY ===\n";
echo "Duplicates to remove: " . count($dupes) . "\n";
echo "Legitimate orders to keep: " . count($unique) . "\n";

echo "\nDELETE these:\n";
foreach ($dupes as $d) {
    echo "  {$d['recovered']->order_number} (id={$d['recovered']->id})\n";
}
echo "\nKEEP these:\n";
foreach ($unique as $u) {
    echo "  {$u->order_number} (id={$u->id}) | {$u->guest_name} | ₹{$u->total}\n";
}
