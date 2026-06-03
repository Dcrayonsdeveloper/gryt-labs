<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

tenancy()->initialize(App\Models\Tenant::find('natually'));

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

$clientId = App\Models\Setting::get('bluedart_client_id', '');
$clientSecret = App\Models\Setting::get('bluedart_client_secret', '');
$loginId = App\Models\Setting::get('bluedart_login_id', '');
$licenceKey = App\Models\Setting::get('bluedart_licence_key', '');
$customerCode = App\Models\Setting::get('bluedart_customer_code', '');
$baseUrl = 'https://apigateway.bluedart.com';

// Step 1: Get JWT
echo "=== Step 1: JWT Token ===\n";
$tokenResp = Http::withHeaders([
    'clientID' => $clientId,
    'clientSecret' => $clientSecret,
])->timeout(15)->get($baseUrl . '/in/transportation/token/v1/login', [
    'LoginID' => $loginId,
    'LicenceKey' => $licenceKey,
]);
echo "Status: {$tokenResp->status()}\n";
$token = $tokenResp->json()['JWTToken'] ?? '';
echo "Token: " . ($token ? 'OK' : 'FAILED') . "\n\n";

if (!$token) exit("No token, aborting\n");

$headers = [
    'JWTToken' => $token,
    'Content-Type' => 'application/json',
    'Accept' => 'application/json',
    'clientID' => $clientId,
];

// Step 2: Pincode serviceability
echo "=== Step 2: Pincode Serviceability (110055) ===\n";
$resp = Http::withHeaders($headers)->timeout(30)->post(
    $baseUrl . '/in/transportation/finder/v1/GetServicesforPincode',
    [
        'pinCode' => '110055',
        'profile' => [
            'LoginID' => $loginId,
            'LicenceKey' => $licenceKey,
            'Api_type' => 'S',
        ],
    ]
);
echo "Status: {$resp->status()}\n";
echo "Body: " . substr($resp->body(), 0, 500) . "\n\n";

// Step 3: Shipping cost
echo "=== Step 3: Shipping Cost (533101 → 110055) ===\n";
$resp = Http::withHeaders($headers)->timeout(30)->post(
    $baseUrl . '/in/transportation/transit/v1/GetEstimate',
    [
        'OriginPincode' => '533101',
        'DestinationPincode' => '110055',
        'ActualWeight' => 0.5,
        'PaymentType' => 'P',
        'CodAmount' => 0,
        'CustomerCode' => $customerCode,
        'ProductType' => 'A',
        'SubProductType' => 'P',
    ]
);
echo "Status: {$resp->status()}\n";
echo "Body: " . substr($resp->body(), 0, 500) . "\n\n";

// Step 4: Waybill generation (matching the exact sample format)
echo "=== Step 4: GenerateWayBill ===\n";
$resp = Http::withHeaders($headers)->timeout(30)->post(
    $baseUrl . '/in/transportation/waybill/v1/GenerateWayBill',
    [
        'Request' => [
            'Consignee' => [
                'ConsigneeName' => 'Test Customer',
                'ConsigneeAddress1' => '12 Kartar Nagar',
                'ConsigneeAddress2' => 'New Delhi',
                'ConsigneeAddress3' => '',
                'ConsigneePincode' => '110055',
                'ConsigneeAttention' => 'Test',
                'ConsigneeMobile' => '9667553520',
                'ConsigneeTelephone' => '',
                'ConsigneeEmailID' => '',
            ],
            'Returnadds' => [
                'ReturnAddress1' => '12 Kartar Nagar',
                'ReturnAddress2' => 'New Delhi',
                'ReturnAddress3' => '',
                'ReturnContact' => 'Natually',
                'ReturnMobile' => '9667553520',
                'ReturnPincode' => '533101',
                'ReturnEmailID' => '',
            ],
            'Services' => [
                'AWBNo' => '',
                'ActualWeight' => '0.50',
                'CollectableAmount' => 0,
                'Commodity' => [
                    'CommodityDetail1' => 'Beauty Products',
                    'CommodityDetail2' => '',
                    'CommodityDetail3' => '',
                ],
                'CreditReferenceNo' => 'TEST001',
                'DeclaredValue' => 500,
                'ItemCount' => 1,
                'PDFOutputNotRequired' => true,
                'PackType' => '',
                'PickupDate' => '/Date(' . (strtotime('+1 day midnight') * 1000) . ')/',
                'PickupTime' => '1600',
                'PieceCount' => '1',
                'ProductCode' => 'A',
                'ProductType' => 1,
                'RegisterPickup' => true,
                'SubProductCode' => 'P',
                'noOfDCGiven' => 0,
            ],
            'Shipper' => [
                'CustomerAddress1' => '12 Kartar Nagar, New Delhi',
                'CustomerCode' => $customerCode,
                'CustomerMobile' => '9667553520',
                'CustomerName' => 'Natually',
                'CustomerPincode' => '533101',
                'IsToPayCustomer' => false,
                'OriginArea' => 'RJY',
                'Sender' => 'Natually',
                'VendorCode' => '',
            ],
        ],
        'Profile' => [
            'LoginID' => $loginId,
            'LicenceKey' => $licenceKey,
            'Api_type' => 'S',
        ],
    ]
);
echo "Status: {$resp->status()}\n";
echo "Body: " . substr($resp->body(), 0, 800) . "\n";
