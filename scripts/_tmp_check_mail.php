<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

foreach (['natually', 'getsetnova'] as $tid) {
    $tenant = App\Models\Tenant::find($tid);
    echo "=== {$tid} ===\n";
    echo "  central support_email: " . ($tenant->support_email ?? 'NULL') . "\n";
    echo "  central brand_name: " . ($tenant->brand_name ?? 'NULL') . "\n";

    tenancy()->initialize($tenant);

    $keys = ['mail_host','mail_from_address','mail_from_name','mail_username','mail_port','mail_encryption'];
    $settings = DB::table('settings')->whereIn('key', $keys)->pluck('value', 'key');
    foreach ($keys as $k) {
        echo "  setting {$k}: " . ($settings[$k] ?? 'NOT SET') . "\n";
    }

    // Check runtime config after bootstrap
    echo "  config mail.from.address: " . config('mail.from.address') . "\n";
    echo "  config mail.from.name: " . config('mail.from.name') . "\n";
    echo "  config mail.mailers.smtp.host: " . config('mail.mailers.smtp.host') . "\n";
    echo "  config mail.mailers.smtp.username: " . config('mail.mailers.smtp.username') . "\n";
    echo "\n";

    tenancy()->end();
}
