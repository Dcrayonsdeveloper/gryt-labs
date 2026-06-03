<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

tenancy()->initialize(App\Models\Tenant::find('natually'));

App\Models\Setting::set('bluedart_origin_pin', '533101');
echo "Set bluedart_origin_pin = 533101\n";

// Verify
echo "Verify: " . App\Models\Setting::get('bluedart_origin_pin', '') . "\n";
