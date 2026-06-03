<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$tenant = \App\Models\Tenant::find('natually');
tenancy()->initialize($tenant);

// Set FastWASMS credentials for Natually only
\App\Models\Setting::set('fastwasms_instance_id', '6A015F155C662');
\App\Models\Setting::set('fastwasms_api_key', '691eba99c99c2');
\App\Models\Setting::set('whatsapp_enabled', '1');
\App\Models\Setting::set('whatsapp_provider', 'fastwasms');

echo "Settings saved for Natually:\n";
echo "fastwasms_instance_id: " . \App\Models\Setting::get('fastwasms_instance_id', '') . "\n";
echo "fastwasms_api_key: " . \App\Models\Setting::get('fastwasms_api_key', '') . "\n";
echo "whatsapp_enabled: " . \App\Models\Setting::get('whatsapp_enabled', '') . "\n";
echo "whatsapp_provider: " . \App\Models\Setting::get('whatsapp_provider', '') . "\n";

// Verify other tenants NOT affected
echo "\n=== Verify other tenants unaffected ===\n";
foreach (['jikra', 'ayurvexa', 'getsetnova'] as $tid) {
    $t = \App\Models\Tenant::find($tid);
    if ($t) {
        tenancy()->initialize($t);
        $fid = \App\Models\Setting::get('fastwasms_instance_id', '');
        $fak = \App\Models\Setting::get('fastwasms_api_key', '');
        echo "{$tid}: instance_id=" . ($fid ?: '(empty)') . " api_key=" . ($fak ?: '(empty)') . "\n";
    }
}
