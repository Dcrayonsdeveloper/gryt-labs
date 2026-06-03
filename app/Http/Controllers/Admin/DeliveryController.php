<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\DbCompat;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Setting;
use App\Services\ShippingManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class DeliveryController extends Controller
{
    public function __construct(private ShippingManager $shipping) {}

    /**
     * Delivery dashboard — stats, pending shipments, NDR, RTO.
     */
    public function index(): View
    {
        $stats = [
            'pending' => Order::whereIn('status', ['confirmed', 'packed'])->count(),
            'shipped' => Order::where('status', 'shipped')->count(),
            'out_for_delivery' => Order::where('status', 'out_for_delivery')->count(),
            'delivered' => Order::where('status', 'delivered')->whereDate('delivered_at', '>=', now()->subDays(7))->count(),
            'rto' => Order::where('status', 'returned')->whereDate('updated_at', '>=', now()->subDays(30))->count(),
        ];

        // Analytics (30-day) — aggregate across all carriers
        $activeCarriers = collect($this->shipping->activeProviders())->map(fn($p) => $p->label())->values()->toArray();
        // Include 'Manual' as well for orders without a carrier API
        $carrierQuery = !empty($activeCarriers)
            ? Order::whereIn('carrier', $activeCarriers)
            : Order::whereNotNull('carrier');

        $totalShipped30d = (clone $carrierQuery)->whereDate('shipped_at', '>=', now()->subDays(30))->count();
        $delivered30d = (clone $carrierQuery)->where('status', 'delivered')
            ->whereDate('delivered_at', '>=', now()->subDays(30))->count();
        $rto30d = (clone $carrierQuery)->where('status', 'returned')
            ->whereDate('updated_at', '>=', now()->subDays(30))->count();

        $analytics = [
            'total_shipped' => $totalShipped30d,
            'delivery_rate' => $totalShipped30d > 0 ? round(($delivered30d / $totalShipped30d) * 100, 1) : 0,
            'rto_rate' => $totalShipped30d > 0 ? round(($rto30d / $totalShipped30d) * 100, 1) : 0,
            'avg_delivery_days' => (clone $carrierQuery)
                ->where('status', 'delivered')
                ->whereNotNull('shipped_at')
                ->whereNotNull('delivered_at')
                ->whereDate('delivered_at', '>=', now()->subDays(30))
                ->selectRaw('AVG(' . DbCompat::dateDiff('delivered_at', 'shipped_at') . ') as avg_days')
                ->value('avg_days') ?? 0,
        ];

        // Per-carrier breakdown
        $carrierBreakdown = [];
        foreach ($this->shipping->activeProviders() as $name => $provider) {
            $label = $provider->label();
            $shipped = Order::where('carrier', $label)->whereDate('shipped_at', '>=', now()->subDays(30))->count();
            $delivered = Order::where('carrier', $label)->where('status', 'delivered')->whereDate('delivered_at', '>=', now()->subDays(30))->count();
            $rto = Order::where('carrier', $label)->where('status', 'returned')->whereDate('updated_at', '>=', now()->subDays(30))->count();
            $carrierBreakdown[$name] = [
                'label' => $label,
                'shipped' => $shipped,
                'delivery_rate' => $shipped > 0 ? round(($delivered / $shipped) * 100, 1) : 0,
                'rto_rate' => $shipped > 0 ? round(($rto / $shipped) * 100, 1) : 0,
            ];
        }

        $pendingOrders = Order::whereIn('status', ['confirmed', 'packed'])
            ->with(['items.product', 'user'])
            ->latest()
            ->take(50)
            ->get();

        $shippedOrders = Order::whereIn('status', ['shipped', 'out_for_delivery'])
            ->whereNotNull('tracking_number')
            ->with(['items.product', 'user'])
            ->latest()
            ->take(30)
            ->get();

        $activeProviders = $this->shipping->activeProviders();
        $defaultProvider = $this->shipping->defaultProviderName();

        return view('admin.delivery.index', compact(
            'stats', 'pendingOrders', 'shippedOrders', 'analytics',
            'carrierBreakdown', 'activeProviders', 'defaultProvider'
        ));
    }

    /**
     * Book delivery for an order via the selected carrier.
     */
    public function book(Request $request, Order $order): RedirectResponse
    {
        // Determine which carrier to use: from request, or tenant default
        $carrierName = $request->input('carrier');
        $provider = null;

        if ($carrierName) {
            try {
                $provider = $this->shipping->provider($carrierName);
            } catch (\InvalidArgumentException $e) {
                return back()->with('error', "Unknown carrier: {$carrierName}");
            }
        } else {
            $provider = $this->shipping->defaultProvider();
        }

        // If the provider is configured, use its API
        if ($provider->isConfigured()) {
            $carrierLabel = $provider->label();

            try {
                $result = $provider->bookDelivery($order);
            } catch (\Throwable $e) {
                Log::error("{$carrierLabel} bookDelivery exception", ['order' => $order->id, 'error' => $e->getMessage()]);
                return back()->with('error', "{$carrierLabel} booking failed: " . $e->getMessage());
            }

            if ($result['success'] && !empty($result['waybill'])) {
                $order->updateStatus('shipped', auth('admin')->id(), "Shipped via {$carrierLabel} - AWB: {$result['waybill']}");
                $order->update([
                    'tracking_number' => $result['waybill'],
                    'carrier' => $carrierLabel,
                ]);

                if (class_exists(\App\Models\OrderShipment::class)) {
                    \App\Models\OrderShipment::create([
                        'order_id' => $order->id,
                        'carrier' => $carrierLabel,
                        'tracking_number' => $result['waybill'],
                        'status' => 'shipped',
                        'shipped_at' => now(),
                    ]);
                }

                try {
                    if ($request->boolean('notify_customer', true)) {
                        \App\Events\OrderShipped::dispatch($order, $result['waybill']);
                    }
                } catch (\Exception $e) {
                    Log::error("OrderShipped event failed ({$carrierLabel})", ['order' => $order->id, 'error' => $e->getMessage()]);
                }

                return back()->with('success', "Shipment booked via {$carrierLabel}! AWB: {$result['waybill']}");
            }

            $errorMsg = $result['message'] ?? implode(', ', $result['remarks'] ?? ['Unknown error']);
            Log::warning("{$carrierLabel} booking failed", ['order' => $order->order_number, 'result' => $result]);

            return back()->with('error', "{$carrierLabel} booking failed: {$errorMsg}");
        }

        // No delivery provider configured — manual dispatch
        $trackingNumber = $request->input('tracking_number', '');
        $manualCarrier = $request->input('manual_carrier', 'Manual');

        $order->updateStatus('shipped', auth('admin')->id(), "Shipped manually" . ($trackingNumber ? " - Tracking: {$trackingNumber}" : ''));

        if ($trackingNumber) {
            $order->update([
                'tracking_number' => $trackingNumber,
                'carrier' => $manualCarrier,
            ]);
        }

        if (class_exists(\App\Models\OrderShipment::class) && $trackingNumber) {
            \App\Models\OrderShipment::create([
                'order_id' => $order->id,
                'carrier' => $manualCarrier,
                'tracking_number' => $trackingNumber,
                'status' => 'shipped',
                'shipped_at' => now(),
            ]);
        }

        try {
            if ($request->boolean('notify_customer', true)) {
                \App\Events\OrderShipped::dispatch($order, $trackingNumber ?: null);
            }
        } catch (\Exception $e) {
            Log::error('OrderShipped event failed (manual)', ['order' => $order->id, 'error' => $e->getMessage()]);
        }

        return back()->with('success', 'Order marked as shipped.' . ($trackingNumber ? " Tracking: {$trackingNumber}" : ''));
    }

    /**
     * Bulk-book all pending orders via the selected carrier.
     */
    public function bulkBook(Request $request): RedirectResponse
    {
        $carrierName = $request->input('carrier');
        $provider = $carrierName
            ? $this->shipping->provider($carrierName)
            : $this->shipping->defaultProvider();

        if (!$provider->isConfigured()) {
            return back()->with('error', $provider->label() . ' is not configured.');
        }

        $orders = Order::whereIn('status', ['confirmed', 'packed'])
            ->whereNull('tracking_number')
            ->with(['items.product'])
            ->get();

        if ($orders->isEmpty()) {
            return back()->with('error', 'No pending orders to book.');
        }

        $success = 0;
        $failed = 0;
        $errors = [];

        foreach ($orders as $order) {
            try {
                $result = $provider->bookDelivery($order);

                if ($result['success'] && !empty($result['waybill'])) {
                    $order->updateStatus('shipped', auth('admin')->id(), "Shipped via {$provider->label()} - AWB: {$result['waybill']}");
                    $order->update([
                        'tracking_number' => $result['waybill'],
                        'carrier' => $provider->label(),
                    ]);

                    if (class_exists(\App\Models\OrderShipment::class)) {
                        \App\Models\OrderShipment::create([
                            'order_id' => $order->id,
                            'carrier' => $provider->label(),
                            'tracking_number' => $result['waybill'],
                            'status' => 'shipped',
                            'shipped_at' => now(),
                        ]);
                    }

                    try {
                        \App\Events\OrderShipped::dispatch($order, $result['waybill']);
                    } catch (\Exception $e) {
                        Log::error('OrderShipped event failed (bulk)', ['order' => $order->id, 'error' => $e->getMessage()]);
                    }

                    $success++;
                } else {
                    $failed++;
                    $errors[] = "{$order->order_number}: " . ($result['message'] ?? 'Unknown error');
                }
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = "{$order->order_number}: {$e->getMessage()}";
                Log::error('Bulk booking failed', ['order' => $order->id, 'error' => $e->getMessage()]);
            }
        }

        $msg = "{$success} orders booked via {$provider->label()}.";
        if ($failed > 0) {
            $msg .= " {$failed} failed: " . implode('; ', array_slice($errors, 0, 3));
        }

        return back()->with($failed > 0 && $success === 0 ? 'error' : 'success', $msg);
    }

    /**
     * Track a shipment via the order's carrier.
     */
    public function track(Order $order): JsonResponse
    {
        if (empty($order->tracking_number)) {
            return response()->json(['success' => false, 'message' => 'No tracking number']);
        }

        $provider = $this->shipping->forOrder($order);
        $result = $provider->track($order->tracking_number);

        return response()->json($result);
    }

    /**
     * Cancel a shipment via the order's carrier.
     */
    public function cancel(Order $order): RedirectResponse
    {
        if (empty($order->tracking_number)) {
            return back()->with('error', 'No tracking number to cancel.');
        }

        $provider = $this->shipping->forOrder($order);
        $result = $provider->cancel($order->tracking_number);

        if ($result['success']) {
            $order->update([
                'status' => 'confirmed',
                'tracking_number' => null,
                'carrier' => null,
                'shipped_at' => null,
            ]);
            return back()->with('success', 'Shipment cancelled.');
        }

        return back()->with('error', 'Cancel failed: ' . ($result['message'] ?? 'Unknown'));
    }

    /**
     * Download shipping label via the order's carrier.
     */
    public function label(Order $order)
    {
        if (empty($order->tracking_number)) {
            return back()->with('error', 'No tracking number.');
        }

        $provider = $this->shipping->forOrder($order);
        $path = $provider->generateLabel($order->tracking_number);

        if ($path) {
            return response()->download(storage_path("app/{$path}"));
        }

        return back()->with('error', 'Label generation failed.');
    }

    /**
     * Request pickup from the default carrier.
     */
    public function requestPickup(Request $request): RedirectResponse
    {
        $count = (int) $request->input('package_count', 1);
        $date = $request->input('pickup_date', now()->addDay()->format('Y-m-d'));

        $carrierName = $request->input('carrier');
        $provider = $carrierName ? $this->shipping->provider($carrierName) : $this->shipping->defaultProvider();
        $result = $provider->requestPickup($count, $date);

        return back()->with(
            $result['success'] ? 'success' : 'error',
            $result['success'] ? 'Pickup requested!' : ('Pickup request failed: ' . ($result['message'] ?? ''))
        );
    }

    /**
     * NDR action — re-attempt, return, hold. (Delhivery-specific for now)
     */
    public function ndrAction(Request $request, Order $order): RedirectResponse
    {
        $action = $request->input('action');
        $comment = $request->input('comment', '');

        if (empty($order->tracking_number)) {
            return back()->with('error', 'No tracking number.');
        }

        // NDR is currently Delhivery-specific; if the order is on another carrier, show error
        $provider = $this->shipping->forOrder($order);
        if ($provider->name() === 'delhivery') {
            /** @var \App\Services\DelhiveryService $provider */
            $result = $provider->ndrAction($order->tracking_number, $action, $comment);

            return back()->with(
                $result['success'] ? 'success' : 'error',
                $result['success'] ? "NDR action '{$action}' applied." : 'NDR action failed.'
            );
        }

        return back()->with('error', "NDR actions are not supported for {$provider->label()} yet.");
    }

    /**
     * Check pincode serviceability via the default carrier (AJAX).
     */
    public function checkPincode(Request $request): JsonResponse
    {
        $pincode = $request->input('pincode', '');

        if (strlen($pincode) !== 6) {
            return response()->json(['serviceable' => false, 'message' => 'Invalid pincode']);
        }

        $provider = $this->shipping->defaultProvider();
        $result = $provider->checkPincode($pincode);

        if ($result['serviceable']) {
            $cost = $provider->calculateCost($pincode);
            $result['estimated_cost'] = $cost['total_amount'] ?? null;
            $result['zone'] = $cost['zone'] ?? null;
        }

        return response()->json($result);
    }

    /**
     * Calculate shipping cost via the default carrier (AJAX).
     */
    public function calculateCost(Request $request): JsonResponse
    {
        $pin = $request->input('pincode');
        $weight = (float) $request->input('weight', 500);
        $paymentType = $request->input('payment_type', 'Pre-paid');
        $codAmount = (float) $request->input('cod_amount', 0);

        $provider = $this->shipping->defaultProvider();
        $result = $provider->calculateCost($pin, $weight, $paymentType, $codAmount);

        return response()->json($result);
    }
}
