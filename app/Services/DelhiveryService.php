<?php

namespace App\Services;

use App\Contracts\ShippingProviderInterface;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DelhiveryService implements ShippingProviderInterface
{
    private string $baseUrl = 'https://track.delhivery.com';
    private string $token;
    private string $pickupLocation;
    private string $originPin;

    public function name(): string
    {
        return 'delhivery';
    }

    public function label(): string
    {
        return 'Delhivery';
    }

    public function __construct()
    {
        $this->token = Setting::get('delhivery_api_token') ?? config('services.delhivery.token') ?? '';
        $this->pickupLocation = Setting::get('delhivery_pickup_location', config('app.name', '') . ' Warehouse');
        $this->originPin = Setting::get('delhivery_origin_pin', '');
    }

    public function isConfigured(): bool
    {
        return !empty($this->token);
    }

    // ─── Pincode Serviceability ───────────────────────────────

    public function checkPincode(string $pincode): array
    {
        // If Delhivery is not configured for this tenant, assume all pincodes are serviceable
        if (!$this->isConfigured()) {
            return ['serviceable' => true, 'message' => 'Delivery available', 'prepaid' => true, 'cod' => true];
        }

        return Cache::remember("delhivery_pin_{$pincode}", 86400, function () use ($pincode) {
            try {
                $response = $this->api('GET', '/c/api/pin-codes/json/', ['filter_codes' => $pincode]);

                if (!$response->successful()) {
                    return ['serviceable' => false, 'message' => 'Unable to check'];
                }

                $codes = $response->json('delivery_codes', []);
                if (empty($codes)) {
                    return ['serviceable' => false, 'message' => 'Pincode not serviceable'];
                }

                $pin = $codes[0]['postal_code'] ?? [];

                return [
                    'serviceable' => true,
                    'cod' => ($pin['cod'] ?? 'N') === 'Y',
                    'prepaid' => ($pin['pre_paid'] ?? 'N') === 'Y',
                    'pickup' => ($pin['pickup'] ?? 'N') === 'Y',
                    'city' => $pin['city'] ?? '',
                    'district' => $pin['district'] ?? '',
                    'state_code' => $pin['state_code'] ?? '',
                    'is_oda' => ($pin['is_oda'] ?? 'N') === 'Y',
                ];
            } catch (\Exception $e) {
                Log::error('Delhivery pincode check failed: ' . $e->getMessage());
                return ['serviceable' => false, 'message' => 'Service error'];
            }
        });
    }

    // ─── Shipping Cost Calculator ─────────────────────────────

    public function calculateCost(string $destPin, float $weightGrams = 500, string $paymentType = 'Pre-paid', float $codAmount = 0): array
    {
        try {
            $response = $this->api('GET', '/api/kinko/v1/invoice/charges/.json', [
                'md' => 'S',
                'ss' => 'Delivered',
                'd_pin' => $destPin,
                'o_pin' => $this->originPin,
                'cgm' => (int) $weightGrams,
                'pt' => $paymentType,
                'cod' => $codAmount,
            ]);

            if (!$response->successful()) {
                return ['success' => false, 'message' => 'Cost calculation failed'];
            }

            $data = $response->json();
            if (empty($data) || !is_array($data)) {
                return ['success' => false, 'message' => 'No data returned'];
            }

            $charge = $data[0] ?? [];

            return [
                'success' => true,
                'zone' => $charge['zone'] ?? '',
                'gross_amount' => $charge['gross_amount'] ?? 0,
                'total_amount' => $charge['total_amount'] ?? 0,
                'charged_weight' => $charge['charged_weight'] ?? $weightGrams,
                'tax' => $charge['tax_data'] ?? [],
            ];
        } catch (\Exception $e) {
            Log::error('Delhivery cost calc failed: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ─── Fetch Waybill ────────────────────────────────────────

    public function fetchWaybill(int $count = 1): array
    {
        try {
            $response = $this->api('GET', '/waybill/api/bulk/json/', ['count' => $count]);

            if ($response->successful()) {
                $waybills = $response->json();
                return is_array($waybills) ? $waybills : [$waybills];
            }
        } catch (\Exception $e) {
            Log::error('Delhivery waybill fetch failed: ' . $e->getMessage());
        }

        return [];
    }

    // ─── Create Shipment ──────────────────────────────────────

    public function createShipment(array $shipmentData): array
    {
        try {
            $payload = [
                'shipments' => [$shipmentData],
                'pickup_location' => ['name' => $this->pickupLocation],
            ];

            $response = Http::withHeaders([
                'Authorization' => "Token {$this->token}",
                'Content-Type' => 'application/json',
            ])->timeout(30)->asForm()->post("{$this->baseUrl}/api/cmu/create.json", [
                'format' => 'json',
                'data' => json_encode($payload),
            ]);

            $data = $response->json();

            if (!empty($data['packages'])) {
                $pkg = $data['packages'][0];
                return [
                    'success' => ($pkg['status'] ?? '') !== 'Fail',
                    'waybill' => $pkg['waybill'] ?? '',
                    'order_ref' => $pkg['refnum'] ?? '',
                    'status' => $pkg['status'] ?? 'Unknown',
                    'remarks' => $pkg['remarks'] ?? [],
                    'upload_wbn' => $data['upload_wbn'] ?? '',
                ];
            }

            return [
                'success' => false,
                'message' => $data['rmk'] ?? 'Unknown error',
            ];
        } catch (\Exception $e) {
            Log::error('Delhivery create shipment failed: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Book delivery for an order (one-click from admin).
     */
    public function bookDelivery(Order $order): array
    {
        // Use snapshot address (works for both guest and registered users)
        $snapshot = $order->shipping_address_snapshot;
        $userAddress = $order->shippingAddress ?? $order->billingAddress;

        if (!$snapshot && !$userAddress) {
            return ['success' => false, 'message' => 'No shipping address on order'];
        }

        // Determine payment mode from metadata (not from non-existent column)
        $paymentMethod = $order->metadata['payment_method'] ?? 'cod';
        $codMethods = ['cod', 'partial_pay', 'shiprocket_cod', 'shiprocket_cod_partial'];
        $isCodMethod = in_array($paymentMethod, $codMethods);

        if ($isCodMethod && isset($order->metadata['cod_balance'])) {
            $codBalance = (float) $order->metadata['cod_balance'];
        } elseif ($isCodMethod) {
            $codBalance = max(0, (float) $order->total - (float) ($order->paid_amount ?? 0));
        } else {
            $codBalance = 0;
        }
        $isCod = $isCodMethod && $codBalance > 0;

        $totalWeight = $order->items->sum(fn ($item) => ($item->product->weight ?? 500) * $item->quantity);

        // Build address from snapshot first, fallback to UserAddress model
        $name = $snapshot['name'] ?? $userAddress->name ?? $order->guest_name ?? 'Customer';
        $add = trim(($snapshot['address_line_1'] ?? $userAddress->address_line_1 ?? '') . ' ' . ($snapshot['address_line_2'] ?? $userAddress->address_line_2 ?? ''));
        $pin = $snapshot['postal_code'] ?? $userAddress->postal_code ?? '';
        $city = $snapshot['city'] ?? $userAddress->city ?? '';
        $state = $snapshot['state'] ?? $userAddress->state ?? '';
        $phone = $snapshot['phone'] ?? $order->guest_phone ?? $userAddress->phone ?? '';

        $shipmentData = [
            'name' => $name,
            'add' => $add,
            'pin' => $pin,
            'city' => $city,
            'state' => $state,
            'country' => Setting::get('shipping_country', '') ?: config('app.country', 'India'),
            'phone' => $phone,
            'order' => $order->order_number,
            'payment_mode' => $isCod ? 'COD' : 'Prepaid',
            'return_pin' => $this->originPin,
            'return_city' => Setting::get('delhivery_return_city', ''),
            'return_phone' => Setting::get('delhivery_return_phone', ''),
            'return_add' => Setting::get('delhivery_return_address', ''),
            'return_state' => Setting::get('delhivery_return_state', ''),
            'return_country' => Setting::get('shipping_country', '') ?: config('app.country', 'India'),
            'return_name' => Setting::get('delhivery_return_name', config('app.name', '') . ' Returns'),
            'products_desc' => $order->items->pluck('product_name')->implode(', '),
            'hsn_code' => '',
            'cod_amount' => $isCod ? (string) round($codBalance, 2) : '0',
            'order_date' => $order->created_at->format('Y-m-d'),
            'total_amount' => (string) $order->total,
            'seller_add' => Setting::get('delhivery_return_address', ''),
            'seller_name' => config('app.name', ''),
            'seller_inv' => $order->invoice_number ?? $order->order_number,
            'quantity' => (string) $order->items->sum('quantity'),
            'waybill' => '',
            'shipment_width' => '15',
            'shipment_height' => '10',
            'weight' => (string) max(500, $totalWeight),
            'shipment_length' => '20',
        ];

        return $this->createShipment($shipmentData);
    }

    // ─── Track Shipment ───────────────────────────────────────

    public function track(string $waybill): array
    {
        try {
            $response = $this->api('GET', '/api/v1/packages/json/', ['waybill' => $waybill]);

            if ($response->successful()) {
                $data = $response->json();
                $shipment = $data['ShipmentData'][0]['Shipment'] ?? null;

                if ($shipment) {
                    return [
                        'success' => true,
                        'status' => $shipment['Status']['Status'] ?? 'Unknown',
                        'status_location' => $shipment['Status']['StatusLocation'] ?? '',
                        'status_datetime' => $shipment['Status']['StatusDateTime'] ?? '',
                        'expected_date' => $shipment['ExpectedDeliveryDate'] ?? '',
                        'origin' => $shipment['Origin'] ?? '',
                        'destination' => $shipment['Destination'] ?? '',
                        'scans' => collect($shipment['Scans'] ?? [])->map(fn ($s) => [
                            'status' => $s['ScanDetail']['Scan'] ?? '',
                            'location' => $s['ScanDetail']['ScannedLocation'] ?? '',
                            'datetime' => $s['ScanDetail']['ScanDateTime'] ?? '',
                            'instructions' => $s['ScanDetail']['Instructions'] ?? '',
                        ])->toArray(),
                        'rto' => [
                            'is_rto' => ($shipment['ReverseInTransit'] ?? false),
                        ],
                    ];
                }
            }

            return ['success' => false, 'message' => $response->json('Error', 'Tracking failed')];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ─── Cancel Shipment ──────────────────────────────────────

    public function cancel(string $waybill): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Token {$this->token}",
            ])->timeout(15)->asForm()->post("{$this->baseUrl}/api/p/edit", [
                'waybill' => $waybill,
                'cancellation' => 'true',
            ]);

            return [
                'success' => $response->successful(),
                'message' => $response->json('status', $response->body()),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ─── Generate Label ───────────────────────────────────────

    public function generateLabel(string $waybill): ?string
    {
        try {
            $response = $this->api('GET', '/api/p/packing_slip', [
                'wbns' => $waybill,
                'pdf' => 'true',
            ]);

            if ($response->successful() && $response->header('content-type') === 'application/pdf') {
                $path = "labels/delhivery-{$waybill}.pdf";
                \Illuminate\Support\Facades\Storage::put($path, $response->body());
                return $path;
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Delhivery label generation failed: ' . $e->getMessage());
            return null;
        }
    }

    // ─── Pickup Request ───────────────────────────────────────

    public function requestPickup(int $packageCount = 1, ?string $pickupDate = null, ?string $pickupTime = null): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Token {$this->token}",
            ])->timeout(15)->asForm()->post("{$this->baseUrl}/fm/request/new/", [
                'pickup_location' => $this->pickupLocation,
                'expected_package_count' => $packageCount,
                'pickup_date' => $pickupDate ?? now()->addDay()->format('Y-m-d'),
                'pickup_time' => $pickupTime ?? '12:00:00',
            ]);

            return [
                'success' => $response->successful(),
                'data' => $response->json(),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ─── NDR Actions ──────────────────────────────────────────

    public function ndrAction(string $waybill, string $action, string $comment = ''): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Token {$this->token}",
            ])->timeout(15)->asForm()->post("{$this->baseUrl}/api/p/update", [
                'waybill' => $waybill,
                'act' => $action, // re-attempt, return, hold
                'ndr_comment' => $comment,
            ]);

            return [
                'success' => $response->successful(),
                'data' => $response->json(),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ─── Warehouse Management ─────────────────────────────────

    public function updateWarehouse(array $data): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Token {$this->token}",
                'Content-Type' => 'application/json',
            ])->timeout(15)->post("{$this->baseUrl}/api/backend/clientwarehouse/edit/", $data);

            return [
                'success' => str_contains($response->body(), 'True'),
                'data' => $response->body(),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ─── Status Mapping ──────────────────────────────────────

    public function mapStatus(string $rawStatus): ?string
    {
        $status = strtolower($rawStatus);

        return match (true) {
            str_contains($status, 'delivered') => 'delivered',
            str_contains($status, 'out for delivery') => 'out_for_delivery',
            str_contains($status, 'in transit'), str_contains($status, 'dispatched'), str_contains($status, 'manifested') => 'shipped',
            str_contains($status, 'rto'), str_contains($status, 'returned') => 'returned',
            str_contains($status, 'cancelled') => 'cancelled',
            default => null,
        };
    }

    // ─── API Helper ───────────────────────────────────────────

    private function api(string $method, string $endpoint, array $params = [])
    {
        $request = Http::withHeaders([
            'Authorization' => "Token {$this->token}",
        ])->timeout(15);

        return match (strtoupper($method)) {
            'POST' => $request->post("{$this->baseUrl}{$endpoint}", $params),
            default => $request->get("{$this->baseUrl}{$endpoint}", $params),
        };
    }
}
