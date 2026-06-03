<?php
/**
 * Temporary script to test BlueDart API endpoints.
 * Run: php artisan tenants:run "tinker --execute=\"require 'scripts/_tmp_test_bluedart_api.php'\"" --tenants=natually
 * Or directly: php scripts/_tmp_test_bluedart_api.php
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Switch to natually tenant
$tenant = \App\Models\Tenant::find('natually');
if ($tenant) {
    tenancy()->initialize($tenant);
    echo "Tenant: natually initialized\n";
} else {
    echo "ERROR: Could not find natually tenant\n";
    exit(1);
}

$clientId = \App\Models\Setting::get('bluedart_client_id', '');
$clientSecret = \App\Models\Setting::get('bluedart_client_secret', '');
$loginId = \App\Models\Setting::get('bluedart_login_id', '');
$licenceKey = \App\Models\Setting::get('bluedart_licence_key', '');
$mode = \App\Models\Setting::get('bluedart_mode', 'sandbox');
$customerCode = \App\Models\Setting::get('bluedart_customer_code', '');

$apiToken = \App\Models\Setting::get('bluedart_api_token', '');
echo "Mode: {$mode}\n";
echo "ClientID: {$clientId}\n";
echo "LoginID: {$loginId}\n";
echo "CustomerCode: {$customerCode}\n";
echo "Static API Token: " . (empty($apiToken) ? '(empty)' : "'" . substr($apiToken, 0, 30) . "...' (len=" . strlen($apiToken) . ")") . "\n\n";

$baseUrl = $mode === 'live'
    ? 'https://apigateway.bluedart.com'
    : 'https://apigateway-sandbox.bluedart.com';

$tokenUrl = $baseUrl . '/in/transportation/token/v1/login';

// Step 1: Get JWT token
echo "=== STEP 1: JWT Token ===\n";
echo "URL: {$tokenUrl}\n";

$resp = \Illuminate\Support\Facades\Http::withHeaders([
    'clientID' => $clientId,
    'clientSecret' => $clientSecret,
])->timeout(15)->get($tokenUrl, [
    'LoginID' => $loginId,
    'LicenceKey' => $licenceKey,
]);

echo "Status: {$resp->status()}\n";
echo "Body: " . substr($resp->body(), 0, 300) . "\n";

$tokenData = $resp->json();
$token = $tokenData['JWTToken'] ?? $tokenData['JWToken'] ?? '';

if (empty($token)) {
    echo "ERROR: No JWT token received\n";
    exit(1);
}
echo "JWT: " . substr($token, 0, 50) . "...\n\n";

// Clear stale static API token - it bypasses the working JWT flow
if (!empty(\App\Models\Setting::get('bluedart_api_token', ''))) {
    \App\Models\Setting::set('bluedart_api_token', '');
    echo "CLEARED stale bluedart_api_token\n";
}

// Clear cached empty token in TENANT context
$cacheKey2 = 'bluedart_jwt_' . md5($clientId);
\Illuminate\Support\Facades\Cache::forget($cacheKey2);
echo "Cleared tenant cache key: {$cacheKey2}\n";

echo "\n=== Testing BlueDartService::track() after cache clear ===\n";
$service = new \App\Services\BlueDartService();
echo "isConfigured: " . ($service->isConfigured() ? 'YES' : 'NO') . "\n";
$result = $service->track('12345678901');
echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";

// Also test checkPincode
echo "\n=== Testing BlueDartService::checkPincode('110001') ===\n";
$result2 = $service->checkPincode('110001');
echo "Result: " . json_encode($result2, JSON_PRETTY_PRINT) . "\n";

echo "\n=== DONE ===\n";
