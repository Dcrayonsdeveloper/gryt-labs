<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$tenant = \App\Models\Tenant::find('natually');
tenancy()->initialize($tenant);

// Fix: save under the correct keys that WhatsAppService.php reads
\App\Models\Setting::set('whatsapp_instance_id', '6A015F155C662');
\App\Models\Setting::set('whatsapp_api_token', '691eba99c99c2');
\App\Models\Setting::set('whatsapp_enabled', '1');
\App\Models\Setting::set('whatsapp_provider', 'fastwasms');

echo "=== Natually FastWASMS settings (corrected keys) ===\n";
echo "whatsapp_instance_id: " . \App\Models\Setting::get('whatsapp_instance_id', '') . "\n";
echo "whatsapp_api_token: " . \App\Models\Setting::get('whatsapp_api_token', '') . "\n";
echo "whatsapp_enabled: " . \App\Models\Setting::get('whatsapp_enabled', '') . "\n";
echo "whatsapp_provider: " . \App\Models\Setting::get('whatsapp_provider', '') . "\n";

// Verify WhatsAppService sees it as configured
$svc = new \App\Services\WhatsAppService();
echo "\nisConfigured(): " . ($svc->isConfigured() ? 'YES' : 'NO') . "\n";

// Verify other tenants NOT affected
echo "\n=== Other tenants (should be unaffected) ===\n";
foreach (['jikra', 'ayurvexa', 'getsetnova'] as $tid) {
    $t = \App\Models\Tenant::find($tid);
    if ($t) {
        tenancy()->initialize($t);
        $iid = \App\Models\Setting::get('whatsapp_instance_id', '');
        $tok = \App\Models\Setting::get('whatsapp_api_token', '');
        $prov = \App\Models\Setting::get('whatsapp_provider', '');
        echo "{$tid}: provider=" . ($prov ?: '(empty)') . " instance_id=" . ($iid ?: '(empty)') . " api_token=" . ($tok ?: '(empty)') . "\n";
    }
}
