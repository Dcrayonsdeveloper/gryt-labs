<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

tenancy()->initialize(App\Models\Tenant::find('natually'));

App\Models\Setting::set('bluedart_return_address', '12 Kartar Nagar, New Delhi, Delhi 110055');
App\Models\Setting::set('bluedart_return_phone', '9667553520');
App\Models\Setting::set('bluedart_contact_person', 'Natually');

echo "Set bluedart_return_address = 12 Kartar Nagar, New Delhi, Delhi 110055\n";
echo "Set bluedart_return_phone = 9667553520\n";
echo "Set bluedart_contact_person = Natually\n";

// Verify all required settings
$keys = ['bluedart_origin_pin','bluedart_return_address','bluedart_return_phone','bluedart_contact_person',
         'bluedart_customer_code','bluedart_origin_area','bluedart_login_id','bluedart_licence_key'];
echo "\n=== Verify ===\n";
foreach ($keys as $k) {
    echo "  {$k} = " . App\Models\Setting::get($k, 'EMPTY') . "\n";
}
