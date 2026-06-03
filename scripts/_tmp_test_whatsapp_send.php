<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

tenancy()->initialize(\App\Models\Tenant::find('natually'));

$svc = new \App\Services\WhatsAppService();
$result = $svc->sendText('9667553520', 'Hi! This is a test message from Natually. Please ignore.');
echo 'Send result: ' . ($result ? 'SUCCESS' : 'FAILED') . "\n";
