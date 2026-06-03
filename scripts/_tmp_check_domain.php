<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$tenant = App\Models\Tenant::find('natually');
echo "Tenant: " . $tenant->id . PHP_EOL;
echo "Domain(s): " . PHP_EOL;
foreach ($tenant->domains as $d) {
    echo "  " . $d->domain . PHP_EOL;
}

// Check all tenants and their domains
echo PHP_EOL . "=== ALL TENANTS ===" . PHP_EOL;
$tenants = App\Models\Tenant::with('domains')->get();
foreach ($tenants as $t) {
    $domains = $t->domains->pluck('domain')->implode(', ');
    echo "  {$t->id}: {$domains}" . PHP_EOL;
}
