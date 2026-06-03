<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

tenancy()->initialize(App\Models\Tenant::find('natually'));

// Update LicenceKey, phone, address, and switch to sandbox mode
App\Models\Setting::set('bluedart_licence_key', 'olgvxurfpoifeuthvlipilpsiiliijiee');
App\Models\Setting::set('bluedart_return_phone', '9985155851');
App\Models\Setting::set('bluedart_contact_person', 'Curaskin Solutions Pvt Ltd');
App\Models\Setting::set('bluedart_return_address', 'D.no -33-1-9, Townhall road, Beside SBI Bank (Bazar Branch), Rajahmundry, Andhra Pradesh 533101');
App\Models\Setting::set('bluedart_mode', 'sandbox');

echo "Updated settings:\n";
echo "  bluedart_licence_key = olgvxurfpoifeuthvlipilpsiiliijiee\n";
echo "  bluedart_return_phone = 9985155851\n";
echo "  bluedart_contact_person = Curaskin Solutions Pvt Ltd\n";
echo "  bluedart_return_address = D.no -33-1-9, Townhall road, Beside SBI Bank (Bazar Branch), Rajahmundry, AP 533101\n";
echo "  bluedart_mode = sandbox\n";

// Clear cached JWT token
Illuminate\Support\Facades\Cache::forget('bluedart_jwt_' . md5(App\Models\Setting::get('bluedart_client_id', '')));
echo "\nCleared JWT cache\n";

// Verify
echo "\n=== All Settings ===\n";
$keys = ['bluedart_mode','bluedart_login_id','bluedart_licence_key','bluedart_customer_code',
         'bluedart_origin_area','bluedart_origin_pin','bluedart_return_address','bluedart_return_phone',
         'bluedart_contact_person'];
foreach ($keys as $k) {
    $v = App\Models\Setting::get($k, '');
    echo "  {$k} = " . (strlen($v) > 30 ? substr($v, 0, 25) . '...' : $v) . "\n";
}
