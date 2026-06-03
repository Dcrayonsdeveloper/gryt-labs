<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Fix: getsetnova has empty support_email in central tenants table,
// causing mail.from.address to be set to null in ConfigBootstrapper Phase 1.
$tenant = App\Models\Tenant::find('getsetnova');
if ($tenant) {
    $current = $tenant->support_email ?? '';
    echo "getsetnova support_email: '{$current}'\n";
    if (empty($current)) {
        $tenant->support_email = 'noreply@getsetnova.com';
        $tenant->save();
        echo "FIXED: set support_email = noreply@getsetnova.com\n";
    } else {
        echo "Already set, no change needed.\n";
    }
}

// Verify natually has correct support_email
$natually = App\Models\Tenant::find('natually');
if ($natually) {
    echo "\nnatually support_email: '{$natually->support_email}'\n";
}

echo "\nDONE\n";
