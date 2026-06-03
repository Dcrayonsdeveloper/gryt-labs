<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tenants = App\Models\Tenant::with('domains')->get();
foreach ($tenants as $t) {
    $domains = $t->domains->pluck('domain')->implode(', ');
    echo $t->id . ' | ' . $t->name . ' | ' . $t->plan . ' | domains: ' . $domains . PHP_EOL;
}
