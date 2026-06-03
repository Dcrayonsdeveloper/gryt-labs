<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$licKey = 'sguz0fkmhnsvvtliuooorpsnrpfmkfhi';
$loginId = 'MAA00001';
$custCode = '099960';
$base = 'https://apigateway-sandbox.bluedart.com';

// Try getting JWT from various auth endpoints
$authEndpoints = [
    "$base/in/transportation/token/v1/login",
    "$base/in/transportation/authentication/v1/login",
    "$base/in/transportation/token/v1/GetJWTToken",
];

foreach ($authEndpoints as $url) {
    echo "--- POST $url ---\n";
    try {
        $r = Http::timeout(10)->asJson()->post($url, [
            'LoginID' => $loginId,
            'LicenceKey' => $licKey,
            'CustomerCode' => $custCode,
        ]);
        echo "Status: " . $r->status() . "\n";
        echo "Body: " . substr($r->body(), 0, 400) . "\n";

        $data = $r->json();
        if ($data) {
            foreach ($data as $k => $v) {
                if (is_string($v) && (stripos($k, 'token') !== false || stripos($k, 'jwt') !== false)) {
                    echo "FOUND TOKEN KEY: $k = " . substr($v, 0, 80) . "...\n";
                }
            }
        }
        // Check headers
        foreach ($r->headers() as $name => $vals) {
            if (stripos($name, 'token') !== false || stripos($name, 'jwt') !== false) {
                echo "HEADER: $name = " . implode(", ", $vals) . "\n";
            }
        }
    } catch (Exception $e) {
        echo "Error: " . substr($e->getMessage(), 0, 100) . "\n";
    }
    echo "\n";
}

// If the login endpoint returned 200, try using the JWTToken header
echo "--- Pincode with JWTToken header (using licKey as JWT) ---\n";
$r = Http::withHeaders(['JWTToken' => $licKey])->timeout(10)->asJson()
    ->post("$base/in/transportation/transit/v1/GetServiced", [
        'pinCode' => '500001',
        'profile' => [
            'LoginID' => $loginId,
            'LicenceKey' => $licKey,
            'Api_type' => 'S',
        ],
    ]);
echo "Status: " . $r->status() . "\nBody: " . substr($r->body(), 0, 300) . "\n\n";

// Try pincode with BOTH JWTToken header AND Profile in body
echo "--- Pincode with JWTToken=licKey + GET ---\n";
$r2 = Http::withHeaders(['JWTToken' => $licKey])->timeout(10)
    ->get("$base/in/transportation/transit/v1/GetServiced", ['pinCode' => '500001']);
echo "Status: " . $r2->status() . "\nBody: " . substr($r2->body(), 0, 300) . "\n";
