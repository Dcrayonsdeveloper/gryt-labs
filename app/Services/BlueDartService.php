<?php

namespace App\Services;

use App\Contracts\ShippingProviderInterface;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BlueDartService implements ShippingProviderInterface
{
    private string $baseUrl;
    private string $tokenApiUrl;
    private string $apiToken;
    private string $loginId;
    private string $licenceKey;
    private string $customerCode;
    private string $originArea;
    private string $originPin;
    private string $pickupLocation;
    private string $clientId;
    private string $clientSecret;

    public function __construct()
    {
        $isLive = Setting::get('bluedart_mode', 'sandbox') === 'live';
        $this->baseUrl = $isLive
            ? 'https://apigateway.bluedart.com'
            : 'https://apigateway-sandbox.bluedart.com';
        $this->tokenApiUrl = $isLive
            ? 'https://apigateway.bluedart.com/in/transportation/token/v1/login'
            : 'https://apigateway-sandbox.bluedart.com/in/transportation/token/v1/login';

        $this->apiToken = Setting::get('bluedart_api_token', '');
        $this->loginId = Setting::get('bluedart_login_id', '');
        $this->licenceKey = Setting::get('bluedart_licence_key', '');
        $this->customerCode = Setting::get('bluedart_customer_code', '');
        $this->originArea = Setting::get('bluedart_origin_area', '');
        $this->originPin = Setting::get('bluedart_origin_pin', '');
        $this->pickupLocation = Setting::get('bluedart_pickup_location', '');
        $this->clientId = Setting::get('bluedart_client_id', '');
        $this->clientSecret = Setting::get('bluedart_client_secret', '');
    }

    /**
     * Get JWT token from DHL/BlueDart API gateway.
     *
     * Auth flow (discovered via CORS + testing):
     *   GET /in/transportation/token/v1/login
     *   Headers: clientID, clientSecret
     *   Query:   LoginID, LicenceKey
     *   Returns: { "JWTToken": "eyJ..." }
     *
     * Caches the JWT for 23 hours (tokens expire after 24h).
     */
    private function getAuthToken(): string
    {
        // Use static token if manually set
        if (!empty($this->apiToken)) {
            return $this->apiToken;
        }

        // Auto-generate from Client ID + Secret + LoginID + LicenceKey
        if (empty($this->clientId) || empty($this->clientSecret)) {
            return '';
        }

        $cacheKey = 'bluedart_jwt_' . md5($this->clientId);

        $cached = Cache::get($cacheKey);
        if (!empty($cached)) {
            return $cached;
        }

        try {
            $response = Http::withHeaders([
                'clientID' => $this->clientId,
                'clientSecret' => $this->clientSecret,
            ])->timeout(15)->get($this->tokenApiUrl, [
                'LoginID' => $this->loginId,
                'LicenceKey' => $this->licenceKey,
            ]);

            $data = $response->json();
            $token = $data['JWTToken'] ?? $data['JWToken'] ?? $data['jwt_token'] ?? $data['token'] ?? null;

            if ($token) {
                Cache::put($cacheKey, $token, 82800); // 23 hours
                Log::info('BlueDart: JWT token generated successfully');
                return $token;
            }

            Log::warning('BlueDart: JWT response has no token', ['response' => $data]);
            return '';
        } catch (\Exception $e) {
            Log::error('BlueDart: JWT generation failed', ['error' => $e->getMessage()]);
            return '';
        }
    }

    public function name(): string
    {
        return 'bluedart';
    }

    public function label(): string
    {
        return 'BlueDart';
    }

    public function isConfigured(): bool
    {
        $hasAuth = !empty($this->apiToken) || (!empty($this->clientId) && !empty($this->clientSecret));
        return $hasAuth && !empty($this->licenceKey);
    }

    // ─── Pincode Serviceability ───────────────────────────────

    public function checkPincode(string $pincode): array
    {
        if (!$this->isConfigured()) {
            return ['serviceable' => true, 'message' => 'Delivery available', 'prepaid' => true, 'cod' => true];
        }

        return Cache::remember("bluedart_pin_{$pincode}", 86400, function () use ($pincode) {
            try {
                $response = $this->api('POST', '/in/transportation/finder/v1/GetServicesforPincode', [
                    'pinCode' => $pincode,
                    'profile' => [
                        'LoginID' => $this->loginId,
                        'LicenceKey' => $this->licenceKey,
                        'Api_type' => 'S',
                    ],
                ]);

                if (!$response->successful()) {
                    return ['serviceable' => false, 'message' => 'Unable to check'];
                }

                $data = $response->json();
                $result = $data['GetServicesforPincodeResult'] ?? $data['GetServicedResult'] ?? $data;

                $isError = ($result['IsError'] ?? true);
                $errorMsg = $result['ErrorMessage'] ?? '';

                if (empty($result) || $isError || !in_array($errorMsg, ['Valid', ''])) {
                    return ['serviceable' => false, 'message' => $errorMsg ?: 'Pincode not serviceable'];
                }

                $hasCod = in_array('Yes', [
                    $result['eTailCODAirOutbound'] ?? '',
                    $result['eTailCODGroundOutbound'] ?? '',
                    $result['DPCODServiceOutbound'] ?? '',
                ], true);

                $hasPrepaid = in_array('Yes', [
                    $result['eTailPrePaidAirOutound'] ?? '',
                    $result['eTailPrePaidGroundOutbound'] ?? '',
                    $result['DomesticPriorityOutbound'] ?? '',
                ], true);

                return [
                    'serviceable' => true,
                    'cod' => $hasCod,
                    'prepaid' => $hasPrepaid,
                    'city' => $result['CityDescription'] ?? '',
                    'state' => $result['State'] ?? '',
                    'area_code' => $result['AreaCode'] ?? '',
                    'message' => 'Serviceable',
                ];
            } catch (\Exception $e) {
                Log::error('BlueDart pincode check failed: ' . $e->getMessage());
                return ['serviceable' => false, 'message' => 'Service error'];
            }
        });
    }

    // ─── Shipping Cost Calculator ─────────────────────────────

    public function calculateCost(string $destPin, float $weightGrams = 500, string $paymentType = 'Pre-paid', float $codAmount = 0): array
    {
        try {
            $response = $this->api('POST', '/in/transportation/transit/v1/GetEstimate', [
                'OriginPincode' => $this->originPin,
                'DestinationPincode' => $destPin,
                'ActualWeight' => round($weightGrams / 1000, 2),
                'PaymentType' => $paymentType === 'Pre-paid' ? 'P' : 'C',
                'CodAmount' => $codAmount,
                'CustomerCode' => $this->customerCode,
                'ProductType' => Setting::get('bluedart_product_type', 'D'),
                'SubProductType' => Setting::get('bluedart_sub_product_type', 'P'),
            ]);

            if (!$response->successful()) {
                return ['success' => false, 'message' => 'Cost calculation failed'];
            }

            $data = $response->json();
            $estimate = $data['GetEstimateResult'] ?? $data;

            if ($estimate['ErrorMessage'] ?? null) {
                return ['success' => false, 'message' => $estimate['ErrorMessage']];
            }

            return [
                'success' => true,
                'total_amount' => $estimate['TotalAmount'] ?? $estimate['GrossAmount'] ?? 0,
                'gross_amount' => $estimate['GrossAmount'] ?? 0,
                'zone' => $estimate['Zone'] ?? '',
                'charged_weight' => $estimate['ChargedWeight'] ?? ($weightGrams / 1000),
            ];
        } catch (\Exception $e) {
            Log::error('BlueDart cost calc failed: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ─── Book Delivery ────────────────────────────────────────

    public function bookDelivery(Order $order): array
    {
        $snapshot = $order->shipping_address_snapshot;
        $userAddress = $order->shippingAddress ?? $order->billingAddress;

        if (!$snapshot && !$userAddress) {
            return ['success' => false, 'message' => 'No shipping address on order'];
        }

        // COD detection keys off payment_status — the authoritative field — not the
        // payment_method string. An order needs COD collection whenever it is not
        // fully paid online and still has an outstanding balance. (The method string
        // can be "shiprocket_checkout" even for COD orders whose type was not yet
        // confirmed by a webhook at order-creation time.)
        $codBalance = isset($order->metadata['cod_balance'])
            ? (float) $order->metadata['cod_balance']
            : max(0, (float) $order->total - (float) ($order->paid_amount ?? 0));
        $isCod = $order->payment_status !== 'paid' && $codBalance > 0;

        $totalWeight = $order->items->sum(fn($item) => ($item->product->weight ?? 500) * $item->quantity);

        $name = $snapshot['name'] ?? $userAddress->name ?? $order->guest_name ?? 'Customer';
        $add1 = $snapshot['address_line_1'] ?? $userAddress->address_line_1 ?? '';
        $add2 = $snapshot['address_line_2'] ?? $userAddress->address_line_2 ?? '';
        $pin = $snapshot['postal_code'] ?? $userAddress->postal_code ?? '';
        $city = $snapshot['city'] ?? $userAddress->city ?? '';
        $state = $snapshot['state'] ?? $userAddress->state ?? '';
        $phone = $snapshot['phone'] ?? $order->guest_phone ?? $userAddress->phone ?? '';

        try {
            $mobile = preg_replace('/\D/', '', $phone);
            $returnAddr = Setting::get('bluedart_return_address', '');
            $returnPhone = Setting::get('bluedart_return_phone', '');
            $pickupMs = strtotime('+1 day midnight') * 1000;
            $weightKg = round(max(500, $totalWeight) / 1000, 2);
            $pieceCount = '1'; // BlueDart Apex Etail requires 1 piece (single box)
            $declaredValue = (float) $order->total;

            $contactPerson = Setting::get('bluedart_contact_person', config('app.name', ''));
            $productCode = Setting::get('bluedart_product_type', 'A');
            // SubProductCode must reflect the shipment type: 'C' (COD) when a
            // collectable amount is sent, otherwise the configured prepaid code.
            // BlueDart rejects a CollectableAmount on a non-COD sub-product.
            $subProductCode = $isCod
                ? Setting::get('bluedart_cod_sub_product', 'C')
                : Setting::get('bluedart_sub_product_type', 'P');
            $email = $snapshot['email'] ?? $order->guest_email ?? '';

            $response = $this->api('POST', '/in/transportation/waybill/v1/GenerateWayBill', [
                'Request' => [
                    'Consignee' => [
                        'AvailableDays' => '',
                        'AvailableTiming' => '',
                        'ConsigneeAddress1' => $add1,
                        'ConsigneeAddress2' => $add2,
                        'ConsigneeAddress3' => $city,
                        'ConsigneeAddressType' => '',
                        'ConsigneeAddressinfo' => '',
                        'ConsigneeAttention' => $name,
                        'ConsigneeEmailID' => $email,
                        'ConsigneeFullAddress' => '',
                        'ConsigneeGSTNumber' => '',
                        'ConsigneeLatitude' => '',
                        'ConsigneeLongitude' => '',
                        'ConsigneeMaskedContactNumber' => '',
                        'ConsigneeMobile' => $mobile,
                        'ConsigneeName' => $name,
                        'ConsigneePincode' => $pin,
                        'ConsigneeTelephone' => '',
                    ],
                    'Returnadds' => [
                        'ManifestNumber' => '',
                        'ReturnAddress1' => $returnAddr,
                        'ReturnAddress2' => '',
                        'ReturnAddress3' => '',
                        'ReturnAddressinfo' => '',
                        'ReturnContact' => $contactPerson,
                        'ReturnEmailID' => '',
                        'ReturnLatitude' => '',
                        'ReturnLongitude' => '',
                        'ReturnMaskedContactNumber' => '',
                        'ReturnMobile' => $returnPhone,
                        'ReturnPincode' => $this->originPin,
                        'ReturnTelephone' => '',
                    ],
                    'Services' => [
                        'AWBNo' => '',
                        'ActualWeight' => (string) $weightKg,
                        'CollectableAmount' => $isCod ? round($codBalance, 2) : 0,
                        'Commodity' => [
                            'CommodityDetail1' => $order->items->first()?->product?->name ?? 'Products',
                            'CommodityDetail2' => '',
                            'CommodityDetail3' => '',
                        ],
                        'CreditReferenceNo' => $order->order_number,
                        'CreditReferenceNo2' => '',
                        'CreditReferenceNo3' => '',
                        'DeclaredValue' => $declaredValue,
                        'DeliveryTimeSlot' => '',
                        'Dimensions' => [
                            [
                                'Breadth' => (float) Setting::get('default_package_breadth', 15),
                                'Count' => (int) $pieceCount,
                                'Height' => (float) Setting::get('default_package_height', 10),
                                'Length' => (float) Setting::get('default_package_length', 20),
                            ],
                        ],
                        'FavouringName' => '',
                        'IsDedicatedDeliveryNetwork' => false,
                        'IsDutyTaxPaidByShipper' => false,
                        'IsForcePickup' => false,
                        'IsPartialPickup' => false,
                        'IsReversePickup' => false,
                        'ItemCount' => (int) $pieceCount,
                        'Officecutofftime' => '',
                        'PDFOutputNotRequired' => false,
                        'PackType' => '',
                        'ParcelShopCode' => '',
                        'PayableAt' => '',
                        'PickupDate' => '/Date(' . $pickupMs . ')/',
                        'PickupMode' => '',
                        'PickupTime' => '1600',
                        'PickupType' => '',
                        'PieceCount' => $pieceCount,
                        'PreferredPickupTimeSlot' => '',
                        'ProductCode' => $productCode,
                        'ProductFeature' => '',
                        'ProductType' => $isCod ? 2 : 1,
                        'RegisterPickup' => true,
                        'SpecialInstruction' => '',
                        'SubProductCode' => $subProductCode,
                        'TotalCashPaytoCustomer' => 0,
                        'itemdtl' => $order->items->map(fn($item) => [
                            'CGSTAmount' => 0,
                            'HSCode' => '',
                            'IGSTAmount' => 0,
                            'Instruction' => '',
                            'InvoiceDate' => '/Date(' . ($order->created_at->timestamp * 1000) . ')/',
                            'InvoiceNumber' => $order->order_number,
                            'ItemID' => substr((string) ($item->product?->sku ?? $item->product_id), 0, 15),
                            'ItemName' => $item->product?->name ?? 'Product',
                            'ItemValue' => (float) $item->price * $item->quantity,
                            'Itemquantity' => $item->quantity,
                            'PlaceofSupply' => '',
                            'ProductDesc1' => '',
                            'ProductDesc2' => '',
                            'ReturnReason' => '',
                            'SGSTAmount' => 0,
                            'SKUNumber' => substr($item->product?->sku ?? '', 0, 15),
                            'SellerGSTNNumber' => '',
                            'SellerName' => '',
                            'SubProduct1' => '',
                            'SubProduct2' => '',
                            'TaxableAmount' => 0,
                            'TotalValue' => (float) $item->price * $item->quantity,
                            'cessAmount' => '0.0',
                            'countryOfOrigin' => '',
                            'docType' => '',
                            'subSupplyType' => 0,
                            'supplyType' => '',
                        ])->values()->toArray(),
                        'noOfDCGiven' => 0,
                    ],
                    'Shipper' => [
                        'CustomerAddress1' => $returnAddr,
                        'CustomerAddress2' => '',
                        'CustomerAddress3' => '',
                        'CustomerAddressinfo' => '',
                        'CustomerBusinessPartyTypeCode' => '',
                        'CustomerCode' => $this->customerCode,
                        'CustomerEmailID' => '',
                        'CustomerGSTNumber' => '',
                        'CustomerLatitude' => '',
                        'CustomerLongitude' => '',
                        'CustomerMaskedContactNumber' => '',
                        'CustomerMobile' => $returnPhone,
                        'CustomerName' => $contactPerson,
                        'CustomerPincode' => $this->originPin,
                        'CustomerTelephone' => '',
                        'IsToPayCustomer' => false,
                        'OriginArea' => $this->originArea,
                        'Sender' => $contactPerson,
                        'VendorCode' => '',
                    ],
                ],
                'Profile' => [
                    'LoginID' => $this->loginId,
                    'LicenceKey' => $this->licenceKey,
                    'Api_type' => 'S',
                ],
            ]);

            $data = $response->json();
            $result = $data['GenerateWayBillResult'] ?? $data['GenerateWaybillResult'] ?? $data;

            if (!empty($result['AWBNo'])) {
                // Save shipping label PDF from response
                if (!empty($result['AWBPrintContent']) && is_array($result['AWBPrintContent'])) {
                    try {
                        $pdf = implode('', array_map('chr', $result['AWBPrintContent']));
                        Storage::put("labels/bluedart-{$result['AWBNo']}.pdf", $pdf);
                    } catch (\Exception $e) {
                        Log::warning('BlueDart: Failed to save label PDF', ['error' => $e->getMessage()]);
                    }
                }

                return [
                    'success' => true,
                    'waybill' => $result['AWBNo'],
                    'order_ref' => $order->order_number,
                    'status' => 'Booked',
                    'message' => $result['StatusMessage'] ?? 'Shipment booked',
                ];
            }

            // Handle BlueDart error-response format (HTTP 400)
            if (!empty($data['error-response']) && is_array($data['error-response'])) {
                $errItem = $data['error-response'][0] ?? [];
                $errStatuses = $errItem['Status'] ?? [];
                $errMsg = collect($errStatuses)->pluck('StatusInformation')->implode('; ');

                // "Waybill already generated" — extract AWB and treat as success
                if (preg_match('/Waybill\s+No\s*:\s*(\d+)/i', $errMsg, $m)) {
                    Log::info('BlueDart: reusing existing AWB', ['order' => $order->order_number, 'awb' => $m[1]]);
                    return [
                        'success' => true,
                        'waybill' => $m[1],
                        'order_ref' => $order->order_number,
                        'status' => 'Booked',
                        'message' => 'Shipment already booked (AWB reused)',
                    ];
                }

                Log::warning('BlueDart booking failed', [
                    'order' => $order->order_number,
                    'http_status' => $response->status(),
                    'error' => $errMsg,
                    'balance' => $errItem['AvailableBalance'] ?? null,
                ]);
                return [
                    'success' => false,
                    'message' => $errMsg ?: ($data['title'] ?? 'Bad Request'),
                ];
            }

            $statusInfo = '';
            if (!empty($result['Status'])) {
                $statusInfo = collect($result['Status'])->pluck('StatusInformation')->implode('; ');
            }

            Log::warning('BlueDart booking failed', [
                'order' => $order->order_number,
                'http_status' => $response->status(),
                'response' => array_slice($data, 0, 10),
            ]);

            return [
                'success' => false,
                'message' => $statusInfo ?: $result['StatusMessage'] ?? $result['ErrorMessage'] ?? 'Unknown error',
            ];
        } catch (\Exception $e) {
            Log::error('BlueDart booking failed: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ─── Track Shipment ───────────────────────────────────────

    public function track(string $waybill): array
    {
        try {
            // BlueDart tracking: /tracking/v1/GetShipmentTracking with lowercase params.
            // scan=1 for full scans, scan=0 for latest status only.
            $response = $this->api('GET', '/in/transportation/tracking/v1/GetShipmentTracking', [
                'handler' => 'tnt',
                'loginid' => $this->loginId,
                'lickey'  => $this->licenceKey,
                'numbers' => $waybill,
                'scan'    => '1',
            ]);

            if (!$response->successful()) {
                return ['success' => false, 'message' => 'Tracking request failed'];
            }

            // Response is XML per spec: <ShipmentData><Shipment>...</Shipment></ShipmentData>
            $xml = @simplexml_load_string($response->body());
            if (!$xml) {
                return ['success' => false, 'message' => 'Invalid tracking response'];
            }

            // Check for error at root level
            if (isset($xml->Error) && (string) $xml->Error !== '') {
                $errorText = (string) $xml->Error;
                if (str_contains($errorText, 'No information') || str_contains($errorText, 'Incorrect waybill')) {
                    return ['success' => false, 'message' => 'Tracking not available yet — shipment awaiting pickup by BlueDart'];
                }
                return ['success' => false, 'message' => $errorText];
            }

            $shipment = $xml->Shipment ?? null;
            if (!$shipment) {
                return ['success' => false, 'message' => 'No shipment data'];
            }

            $scans = [];
            if (isset($shipment->Scans->ScanDetail)) {
                foreach ($shipment->Scans->ScanDetail as $scan) {
                    $scans[] = [
                        'status'   => trim((string) ($scan->Scan ?? '')),
                        'type'     => (string) ($scan->ScanType ?? ''),
                        'location' => (string) ($scan->ScannedLocation ?? ''),
                        'datetime' => trim((string) ($scan->ScanDate ?? '')) . ' ' . trim((string) ($scan->ScanTime ?? '')),
                    ];
                }
            }

            $status = trim((string) ($shipment->Status ?? 'Unknown'));

            return [
                'success' => true,
                'status' => $status,
                'status_type' => (string) ($shipment->StatusType ?? ''),
                'status_location' => (string) ($shipment->StatusLocation ?? ''),
                'status_datetime' => trim((string) ($shipment->StatusDate ?? '')) . ' ' . trim((string) ($shipment->StatusTime ?? '')),
                'expected_date' => (string) ($shipment->ExpectedDeliveryDate ?? ''),
                'origin' => (string) ($shipment->Origin ?? ''),
                'destination' => (string) ($shipment->Destination ?? ''),
                'received_by' => (string) ($shipment->ReceivedBy ?? ''),
                'instructions' => (string) ($shipment->Instructions ?? ''),
                'scans' => $scans,
                'rto' => [
                    'is_rto' => str_contains(strtolower($status), 'rto') || str_contains(strtolower($status), 'return'),
                ],
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ─── Cancel Shipment ──────────────────────────────────────

    public function cancel(string $waybill): array
    {
        try {
            $response = $this->api('POST', '/in/transportation/waybill/v1/CancelWaybill', [
                'Request' => [
                    'AWBNo' => $waybill,
                ],
                'Profile' => [
                    'LoginID' => $this->loginId,
                    'LicenceKey' => $this->licenceKey,
                    'Api_type' => 'S',
                ],
            ]);

            $data = $response->json();

            // Handle gateway-level error (400 with error-response wrapper)
            if (isset($data['error-response'])) {
                $err = $data['error-response'][0] ?? [];
                $statusInfo = collect($err['Status'] ?? [])->pluck('StatusInformation')->implode('; ');
                return [
                    'success' => false,
                    'message' => $statusInfo ?: ($data['title'] ?? 'Cancel failed'),
                ];
            }

            $result = $data['CancelWaybillResult'] ?? $data;
            $isError = $result['IsError'] ?? true;
            $statusInfo = collect($result['Status'] ?? [])->pluck('StatusInformation')->implode('; ');
            $isSuccess = !$isError || str_contains($statusInfo, 'cancelled successfully');

            return [
                'success' => $isSuccess,
                'message' => $statusInfo ?: ($result['StatusMessage'] ?? 'Unknown response'),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ─── Generate Label ───────────────────────────────────────

    public function generateLabel(string $waybill): ?string
    {
        $path = "labels/bluedart-{$waybill}.pdf";

        // Label is saved during bookDelivery from AWBPrintContent
        if (Storage::exists($path)) {
            return $path;
        }

        Log::warning("BlueDart: Label not found for AWB {$waybill}. Labels are only available at booking time.");
        return null;
    }

    // ─── Pickup Request ───────────────────────────────────────

    public function requestPickup(int $packageCount = 1, ?string $pickupDate = null, ?string $pickupTime = null): array
    {
        try {
            $response = $this->api('POST', '/in/transportation/pickup/v1/RegisterPickup', [
                'Request' => [
                    'CustomerCode' => $this->customerCode,
                    'OriginArea' => $this->originArea,
                    'ProductCode' => Setting::get('bluedart_product_type', 'D'),
                    'ShipmentCount' => $packageCount,
                    'PickupDate' => $pickupDate ?? now()->addDay()->format('Y-m-d'),
                    'PickupTime' => $pickupTime ?? '1200',
                    'ContactPersonName' => Setting::get('bluedart_contact_person', config('app.name', '')),
                    'ContactPersonMobile' => Setting::get('bluedart_return_phone', ''),
                ],
                'Profile' => [
                    'LoginID' => $this->loginId,
                    'LicenceKey' => $this->licenceKey,
                    'Api_type' => 'S',
                ],
            ]);

            $data = $response->json();
            $result = $data['RegisterPickupResult'] ?? $data;

            return [
                'success' => !empty($result['TokenNumber'] ?? $result['PickupRegistrationNo'] ?? null),
                'data' => $result,
                'message' => $result['StatusMessage'] ?? ($result['TokenNumber'] ? "Pickup #{$result['TokenNumber']}" : 'Pickup request failed'),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ─── Status Mapping ──────────────────────────────────────

    public function mapStatus(string $rawStatus): ?string
    {
        $status = strtolower(trim($rawStatus));

        // BlueDart StatusType codes: DL=Delivered, UD=Out for delivery, IT=In transit, etc.
        return match (true) {
            $status === 'dl', str_contains($status, 'delivered') => 'delivered',
            $status === 'ud', str_contains($status, 'out for delivery'), str_contains($status, 'out_for_delivery') => 'out_for_delivery',
            in_array($status, ['it', 'pk', 'mf'], true), str_contains($status, 'in transit'), str_contains($status, 'dispatched'), str_contains($status, 'picked up'), str_contains($status, 'manifested') => 'shipped',
            str_contains($status, 'rto'), str_contains($status, 'returned'), str_contains($status, 'return') => 'returned',
            str_contains($status, 'cancelled'), str_contains($status, 'canceled') => 'cancelled',
            default => null,
        };
    }

    // ─── API Helper ───────────────────────────────────────────

    private function api(string $method, string $endpoint, array $params = [])
    {
        $token = $this->getAuthToken();

        $headers = [
            'JWTToken' => $token,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        // DHL gateway also accepts clientID header alongside the JWT
        if (!empty($this->clientId)) {
            $headers['clientID'] = $this->clientId;
        }

        $url = "{$this->baseUrl}{$endpoint}";
        $request = Http::withHeaders($headers)->timeout(30);

        if (strtoupper($method) === 'POST') {
            // Inject Profile into POST body if not already present
            if (!isset($params['Profile']) && !empty($this->loginId)) {
                $params['Profile'] = [
                    'LoginID' => $this->loginId,
                    'LicenceKey' => $this->licenceKey,
                    'Api_type' => 'S',
                ];
            }
            $response = $request->post($url, $params);
        } else {
            $response = $request->get($url, $params);
        }

        return $response;
    }
}
