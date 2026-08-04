<?php

namespace App\Http\Controllers\Admin;

use App\Events\OrderDelivered;
use App\Events\OrderShipped;
use App\Events\OrderStatusChanged;
use App\Helpers\DbCompat;
use App\Http\Controllers\Controller;
use App\Models\DeliveryPartner;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\Setting;
use App\Models\ShiprocketCheckoutEvent;
use App\Services\ShiprocketService;
use App\Services\ShiprocketCheckout\ShiprocketCheckoutService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderController extends Controller
{
    public function index(Request $request): View|StreamedResponse
    {
        $query = Order::with(['user', 'items']);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('order_number', 'like', "%{$request->search}%")
                  ->orWhereHas('user', fn($uq) => $uq->where('email', 'like', "%{$request->search}%"));
            });
        }

        // Triage bucket: payment failed + pending (COD unconfirmed / awaiting capture) + returns awaiting refund
        if ($request->input('tab') === 'needs_attention') {
            $query->where(function ($q) {
                $q->whereIn('payment_status', ['failed', 'pending'])
                  ->orWhere('status', 'pending')
                  ->orWhere(function ($rq) {
                      $rq->where('status', 'returned')
                         ->where('payment_status', '!=', 'refunded');
                  });
            });
        } elseif ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // CSV Export
        if ($request->input('export') === 'csv') {
            return $this->exportOrdersCsv($query);
        }

        $perPage = min((int) $request->input('per_page', 10), 100);
        $orders = $query
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();

        // Repeat-customer lookup (2 queries max for this page)
        $pageOrders  = $orders->getCollection();
        $userIds     = $pageOrders->pluck('user_id')->filter()->unique()->values();
        $guestEmails = $pageOrders->whereNull('user_id')->pluck('guest_email')->filter()->unique()->values();

        $repeatCustomers = [];

        if ($userIds->isNotEmpty()) {
            Order::whereIn('user_id', $userIds)
                ->select('user_id', DB::raw('count(*) as cnt'))
                ->groupBy('user_id')
                ->get()
                ->each(function ($r) use (&$repeatCustomers) {
                    $repeatCustomers["u_{$r->user_id}"] = (int) $r->cnt;
                });
        }

        if ($guestEmails->isNotEmpty()) {
            Order::whereIn('guest_email', $guestEmails)
                ->whereNull('user_id')
                ->select('guest_email', DB::raw('count(*) as cnt'))
                ->groupBy('guest_email')
                ->get()
                ->each(function ($r) use (&$repeatCustomers) {
                    $repeatCustomers["e_{$r->guest_email}"] = (int) $r->cnt;
                });
        }

        // Single aggregate query instead of 6 round-trips
        $counts = Order::select('status', DB::raw('count(*) as c'))
            ->groupBy('status')
            ->pluck('c', 'status');

        // Triage bucket count — one query, combined WHERE
        $attentionCount = (int) Order::where(function ($q) {
            $q->whereIn('payment_status', ['failed', 'pending'])
              ->orWhere('status', 'pending')
              ->orWhere(function ($rq) {
                  $rq->where('status', 'returned')
                     ->where('payment_status', '!=', 'refunded');
              });
        })->count();

        $shiprocketMissing = (int) Order::whereNotNull('shiprocket_order_id')
            ->whereNull('user_id')
            ->where(function ($q) {
                $q->whereNull('guest_name')->orWhere('guest_name', '');
            })->count();

        // Orders with customer name but empty/missing address snapshot
        $addressMissing = (int) Order::whereNotNull('shiprocket_order_id')
            ->where(function ($q) {
                $q->whereNotNull('guest_name')->where('guest_name', '!=', '');
            })
            ->where(function ($q) {
                $q->whereNull('shipping_address_snapshot')
                  ->orWhereJsonContains('shipping_address_snapshot->address_line_1', '')
                  ->orWhere('shipping_address_snapshot->address_line_1', null);
            })
            ->count();

        $stats = [
            'total' => (int) $counts->sum(),
            'confirmed' => (int) ($counts['confirmed'] ?? 0),
            'processing' => (int) (($counts['processing'] ?? 0) + ($counts['packed'] ?? 0)),
            'shipped' => (int) (($counts['shipped'] ?? 0) + ($counts['out_for_delivery'] ?? 0)),
            'completed' => (int) ($counts['delivered'] ?? 0),
            'cancelled' => (int) ($counts['cancelled'] ?? 0),
            'needs_attention' => $attentionCount,
            'shiprocket_missing' => $shiprocketMissing,
            'address_missing' => $addressMissing,
        ];

        return view('admin.orders.index', compact('orders', 'stats', 'repeatCustomers'));
    }

    private function exportOrdersCsv($query): StreamedResponse
    {
        $filename = 'orders_' . now()->format('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Order Number', 'Date', 'Customer Name', 'Email', 'Phone',
                'Status', 'Payment Status', 'Payment Method',
                'Subtotal', 'Discount', 'Shipping', 'Tax', 'Total',
                'Items', 'Shipping Address', 'City', 'State', 'Pincode',
                'Tracking Number', 'Courier',
            ]);

            // MySQL sorts NULLs last on DESC already, and its REGEXP_REPLACE is global by default.
            $orderByNumber = DbCompat::isPostgres()
                ? "CAST(NULLIF(REGEXP_REPLACE(order_number, '[^0-9]', '', 'g'), '') AS BIGINT) DESC NULLS LAST"
                : "CAST(NULLIF(REGEXP_REPLACE(order_number, '[^0-9]', ''), '') AS UNSIGNED) DESC";

            $query->with('user')->orderByRaw($orderByNumber)->chunk(500, function ($orders) use ($handle) {
                foreach ($orders as $order) {
                    $items = $order->items->map(fn($i) => ($i->product_name ?? 'Product') . ' x' . $i->quantity)->implode(', ');
                    $address = $order->shipping_address_snapshot ?? [];

                    fputcsv($handle, [
                        $order->order_number,
                        $order->created_at?->format('d M Y h:i A') ?? '',
                        $address['name'] ?? ($order->user?->full_name ?? $order->guest_name ?? ''),
                        $order->user?->email ?? $order->guest_email ?? '',
                        $address['phone'] ?? $order->guest_phone ?? '',
                        ucfirst($order->status),
                        ucfirst($order->payment_status),
                        $order->metadata['payment_method'] ?? '',
                        number_format($order->subtotal, 2),
                        number_format($order->discount ?? 0, 2),
                        number_format($order->shipping_cost ?? 0, 2),
                        number_format($order->tax ?? 0, 2),
                        number_format($order->total, 2),
                        $items,
                        trim(($address['address_line_1'] ?? '') . ' ' . ($address['address_line_2'] ?? '')),
                        $address['city'] ?? '',
                        $address['state'] ?? '',
                        $address['postal_code'] ?? '',
                        $order->tracking_number ?? '',
                        $order->carrier ?? '',
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function show(Order $order): View
    {
        $order->load([
            'user',
            'items.product',
            'items.variant',
            'statusHistory',
            'shipments',
            'coupon',
            'deliveryPartner.user',
        ]);

        $trackingSteps = $order->getTrackingSteps();
        $latestShipment = $order->shipments->first();
        $activePartners = DeliveryPartner::with('user')->where('is_active', true)->get();
        $orderReturns = $order->returns()->latest()->get();

        // Shiprocket Checkout event timeline — look up by order_id FK first,
        // then fall back to cart_id stored in order metadata.
        $checkoutEvents = collect();
        $cartId = $order->metadata['shiprocket_cart_id'] ?? null;
        if ($order->id || $cartId) {
            $checkoutEvents = ShiprocketCheckoutEvent::where(function ($q) use ($order, $cartId) {
                $q->where('order_id', $order->id);
                if ($cartId) $q->orWhere('cart_id', $cartId);
            })->orderBy('received_at')->get();
        }

        return view('admin.orders.show', compact('order', 'trackingSteps', 'latestShipment', 'activePartners', 'orderReturns', 'checkoutEvents'));
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:confirmed,processing,packed,shipped,out_for_delivery,delivered,cancelled,returned'],
            'comment' => ['nullable', 'string', 'max:500'],
            'carrier' => ['nullable', 'string', 'max:100'],
            'tracking_number' => ['nullable', 'string', 'max:100'],
        ]);

        $oldStatus = $order->status;

        // Validate state transitions using model's state machine
        if (!$order->canTransitionTo($validated['status'])) {
            return back()->with('error', "Cannot change status from \"{$oldStatus}\" to \"{$validated['status']}\".");
        }

        // If shipping, create shipment record
        if ($validated['status'] === 'shipped' && !empty($validated['tracking_number'])) {
            $order->shipments()->create([
                'carrier' => $validated['carrier'],
                'tracking_number' => $validated['tracking_number'],
                'status' => 'in_transit',
                'shipped_at' => now(),
            ]);
        }

        // Update shipment status for out_for_delivery and delivered
        if (in_array($validated['status'], ['out_for_delivery', 'delivered'])) {
            $shipment = $order->shipments()->latest()->first();
            if ($shipment) {
                $shipmentStatus = $validated['status'] === 'out_for_delivery' ? 'out_for_delivery' : 'delivered';
                $shipment->update(['status' => $shipmentStatus]);
                if ($validated['status'] === 'delivered') {
                    $shipment->update(['delivered_at' => now()]);
                }
            }
        }

        $order->updateStatus($validated['status'], auth('admin')->id(), $validated['comment'] ?? null);

        try {
            OrderStatusChanged::dispatch($order, $oldStatus, $validated['status']);

            if ($validated['status'] === 'shipped') {
                OrderShipped::dispatch($order, $validated['tracking_number'] ?? null);
            } elseif ($validated['status'] === 'delivered') {
                OrderDelivered::dispatch($order);
            }
        } catch (\Exception $e) {
            Log::error('Order event dispatch failed', ['order' => $order->id, 'error' => $e->getMessage()]);
        }

        return back()->with('success', "Order status updated from {$oldStatus} to {$validated['status']}");
    }

    public function bulkAction(Request $request): RedirectResponse
    {
        $request->validate([
            'action' => ['required', 'in:processing,shipped,delivered,cancelled'],
            'ids' => ['required', 'string'],
        ]);

        $ids = json_decode($request->ids, true);
        if (!is_array($ids) || empty($ids)) {
            return back()->with('error', 'No orders selected.');
        }

        $action = $request->action;
        $success = 0;
        $failed = 0;

        $orders = Order::whereIn('id', $ids)->get();

        foreach ($orders as $order) {
            try {
                if ($order->canTransitionTo($action)) {
                    $oldStatus = $order->status;
                    $order->updateStatus($action, auth('admin')->id(), "Bulk action: marked as {$action}");
                    OrderStatusChanged::dispatch($order, $oldStatus, $action);
                    $success++;
                } else {
                    $failed++;
                }
            } catch (\Exception $e) {
                $failed++;
                Log::error('Bulk order action failed', ['order' => $order->id, 'action' => $action, 'error' => $e->getMessage()]);
            }
        }

        $message = "{$success} order(s) updated to {$action}.";
        if ($failed > 0) {
            $message .= " {$failed} order(s) could not be updated (invalid status transition).";
        }

        return back()->with('success', $message);
    }

    public function ship(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'carrier' => ['required', 'string', 'max:100'],
            'carrier_custom' => ['nullable', 'required_if:carrier,other', 'string', 'max:100'],
            'tracking_number' => ['nullable', 'string', 'max:100'],
            'tracking_url' => ['nullable', 'url', 'max:500'],
            'notify_customer' => ['nullable'],
        ]);

        // Resolve carrier name — use custom name if "other" was selected
        $carrier = $validated['carrier'] === 'other'
            ? ($validated['carrier_custom'] ?? 'Other')
            : $validated['carrier'];
        $trackingNumber = $validated['tracking_number'] ?? null;
        $trackingUrl = $validated['tracking_url'] ?? null;

        // Create shipment record
        if ($trackingNumber) {
            $order->shipments()->create([
                'carrier' => $carrier,
                'tracking_number' => $trackingNumber,
                'status' => 'in_transit',
                'shipped_at' => now(),
            ]);
        }

        // Update order carrier & tracking
        $order->update(array_filter([
            'carrier' => $carrier,
            'tracking_number' => $trackingNumber,
            'tracking_url' => $trackingUrl,
        ]));

        $comment = "Shipped via {$carrier}" . ($trackingNumber ? " - Tracking: {$trackingNumber}" : '');

        if (!$order->canTransitionTo('shipped')) {
            return back()->with('error', "Cannot ship order — current status is \"{$order->status}\". Only confirmed, processing, or packed orders can be shipped.");
        }

        $order->updateStatus('shipped', auth('admin')->id(), $comment);

        // Notify customer if requested
        try {
            if ($request->boolean('notify_customer', true)) {
                OrderShipped::dispatch($order, $trackingNumber);
            }
        } catch (\Exception $e) {
            Log::error('OrderShipped event failed', ['order' => $order->id, 'error' => $e->getMessage()]);
        }

        return back()->with('success', 'Order fulfilled successfully!' . ($trackingNumber ? " Tracking: {$trackingNumber}" : ''));
    }

    /**
     * Add a custom shipping carrier to the reusable list (persisted in settings)
     * so it appears in the fulfilment carrier dropdown for all future orders.
     */
    public function storeCarrier(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
        ]);
        $name = trim($validated['name']);

        $custom = json_decode(Setting::get('custom_shipping_carriers', '[]'), true) ?: [];
        if ($name !== '' && !in_array($name, $custom, true) && !in_array($name, self::defaultCarriers(), true)) {
            $custom[] = $name;
            Setting::set('custom_shipping_carriers', json_encode(array_values($custom)), 'json', 'shipping');
        }

        return response()->json(['carriers' => self::allCarriers()]);
    }

    /** Built-in carriers offered in the fulfilment dropdown. */
    public static function defaultCarriers(): array
    {
        return ['BlueDart', 'Delhivery', 'DTDC', 'Ecom Express', 'Ekart', 'India Post', 'Xpressbees', 'Shadowfax', 'FedEx', 'DHL'];
    }

    /** Default + admin-added carriers for the fulfilment dropdown. */
    public static function allCarriers(): array
    {
        $custom = json_decode(Setting::get('custom_shipping_carriers', '[]'), true) ?: [];
        return array_values(array_unique(array_merge(self::defaultCarriers(), $custom)));
    }

    /**
     * Revert a cancelled order back to Confirmed (un-cancel). Re-deducts the stock
     * that cancellation had restored and undoes a paid→refunded flip. Admin override.
     */
    public function uncancel(Order $order): RedirectResponse
    {
        if ($order->status !== 'cancelled') {
            return back()->with('error', 'Only a cancelled order can be reverted.');
        }

        $oldStatus = $order->status;

        // Cancellation released stock via restoreStock(); re-deduct it now.
        $this->deductOrderStock($order);

        $updates = ['status' => 'confirmed', 'cancelled_at' => null];
        if ($order->payment_status === 'refunded') {
            $updates['payment_status'] = 'paid';
        }
        $order->update($updates);

        $order->statusHistory()->create([
            'status'     => 'confirmed',
            'comment'    => 'Cancellation reverted (order un-cancelled) by admin',
            'created_by' => auth('admin')->id(),
        ]);

        try {
            OrderStatusChanged::dispatch($order, $oldStatus, 'confirmed');
        } catch (\Exception $e) {
            Log::error('Order un-cancel event dispatch failed', ['order' => $order->id, 'error' => $e->getMessage()]);
        }

        return back()->with('success', 'Order reverted to Confirmed.');
    }

    /**
     * Undo a fulfillment: remove tracking/shipment records and set the order back
     * to Packed so it can be re-fulfilled. Works for any carrier. Admin override.
     */
    public function unfulfill(Order $order): RedirectResponse
    {
        if (!in_array($order->status, ['shipped', 'out_for_delivery'], true)) {
            return back()->with('error', 'Only a shipped or out-for-delivery order can be unfulfilled.');
        }

        $oldStatus = $order->status;

        $order->shipments()->delete();

        $order->update([
            'status'              => 'packed',
            'carrier'             => null,
            'tracking_number'     => null,
            'tracking_url'        => null,
            'shipped_at'          => null,
            'out_for_delivery_at' => null,
            'packed_at'           => $order->packed_at ?? now(),
        ]);

        $order->statusHistory()->create([
            'status'     => 'packed',
            'comment'    => 'Fulfillment reverted (order unfulfilled) by admin',
            'created_by' => auth('admin')->id(),
        ]);

        try {
            OrderStatusChanged::dispatch($order, $oldStatus, 'packed');
        } catch (\Exception $e) {
            Log::error('Order unfulfill event dispatch failed', ['order' => $order->id, 'error' => $e->getMessage()]);
        }

        return back()->with('success', 'Order unfulfilled — tracking removed, status set back to Packed.');
    }

    /**
     * Revert an order one stage back along the fulfilment flow:
     * delivered→out_for_delivery→shipped→packed→processing→confirmed, plus
     * cancelled→confirmed and returned→delivered. Cleans up side effects —
     * re-deducts stock when leaving cancelled/returned, strips tracking when
     * un-shipping — clears the left stage's timestamp, and logs history.
     * Admin override — bypasses the forward-only state machine.
     */
    public function revertStatus(Order $order): RedirectResponse
    {
        $prev = [
            'processing'       => 'confirmed',
            'packed'           => 'processing',
            'shipped'          => 'packed',
            'out_for_delivery' => 'shipped',
            'delivered'        => 'out_for_delivery',
            'cancelled'        => 'confirmed',
            'returned'         => 'delivered',
        ];

        $current = $order->status;
        if (!isset($prev[$current])) {
            return back()->with('error', 'This order is already at the first step — nothing to revert.');
        }
        $target  = $prev[$current];
        $updates = ['status' => $target];

        // Clear the timestamp of the stage we're leaving so the tracker reflects it.
        $tsCol = [
            'packed'           => 'packed_at',
            'shipped'          => 'shipped_at',
            'out_for_delivery' => 'out_for_delivery_at',
            'delivered'        => 'delivered_at',
            'cancelled'        => 'cancelled_at',
        ][$current] ?? null;
        if ($tsCol) {
            $updates[$tsCol] = null;
        }

        // Side effects for the stage being left.
        if ($current === 'cancelled') {
            $this->deductOrderStock($order); // cancel had restored stock
            if ($order->payment_status === 'refunded') {
                $updates['payment_status'] = 'paid';
            }
        } elseif ($current === 'returned') {
            $this->deductOrderStock($order); // return had restored stock
        } elseif ($current === 'shipped') {
            // Crossing back out of the shipped zone — drop tracking/shipment.
            $order->shipments()->delete();
            $updates['carrier']         = null;
            $updates['tracking_number'] = null;
            $updates['tracking_url']    = null;
        }

        $order->update($updates);

        $order->statusHistory()->create([
            'status'     => $target,
            'comment'    => 'Reverted from ' . str_replace('_', ' ', $current) . ' to ' . str_replace('_', ' ', $target) . ' by admin',
            'created_by' => auth('admin')->id(),
        ]);

        try {
            OrderStatusChanged::dispatch($order, $current, $target);
        } catch (\Exception $e) {
            Log::error('Order revert event dispatch failed', ['order' => $order->id, 'error' => $e->getMessage()]);
        }

        return back()->with('success', 'Order reverted to ' . ucfirst(str_replace('_', ' ', $target)) . '.');
    }

    /**
     * Deduct stock for every item in an order (inverse of Order::restoreStock()).
     * Used when un-cancelling an order whose stock was released on cancellation.
     */
    private function deductOrderStock(Order $order): void
    {
        $order->loadMissing('items');

        foreach ($order->items as $item) {
            if ($item->variant_id) {
                DB::table('product_variants')
                    ->where('id', $item->variant_id)
                    ->decrement('stock_quantity', $item->quantity);
            } elseif ($item->product_id) {
                DB::table('products')
                    ->where('id', $item->product_id)
                    ->decrement('stock_quantity', $item->quantity);
            }
        }
    }

    public function invoice(Order $order): View
    {
        $order->load(['user', 'items.product']);

        return view('admin.orders.invoice', compact('order'));
    }

    public function assignPartner(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'delivery_partner_id' => 'nullable|exists:delivery_partners,id',
        ]);

        $order->update(['delivery_partner_id' => $validated['delivery_partner_id']]);

        // Also update latest shipment
        $shipment = $order->shipments()->latest()->first();
        if ($shipment) {
            $shipment->update(['delivery_partner_id' => $validated['delivery_partner_id']]);
        }

        if ($validated['delivery_partner_id']) {
            $partner = DeliveryPartner::with('user')->find($validated['delivery_partner_id']);
            $order->statusHistory()->create([
                'status' => $order->status,
                'comment' => "Delivery partner assigned: {$partner->user?->full_name} ({$partner->partner_id})",
                'created_by' => auth('admin')->id(),
            ]);
        }

        return back()->with('success', 'Delivery partner assigned successfully.');
    }

    public function setExpectedDelivery(Request $request, Order $order): RedirectResponse
    {
        $request->validate([
            'expected_delivery_date' => 'nullable|date|after_or_equal:today',
        ]);

        $order->update(['expected_delivery_date' => $request->expected_delivery_date ?: null]);

        return back()->with('success', $request->expected_delivery_date
            ? 'Expected delivery date set to ' . \Carbon\Carbon::parse($request->expected_delivery_date)->format('M d, Y') . '.'
            : 'Expected delivery date cleared.');
    }

    public function packingSlip(Order $order): View
    {
        $order->load(['items.product']);

        return view('admin.orders.packing-slip', compact('order'));
    }

    /**
     * Manually push an order to Shiprocket (retry after failed auto-push).
     */
    public function pushToShiprocket(Order $order, ShiprocketService $shiprocket): RedirectResponse
    {
        if (!Setting::get('shiprocket_enabled', false)) {
            return back()->with('error', 'Shiprocket is not enabled. Configure it in Settings → Shipping.');
        }

        if (!$shiprocket->isConfigured()) {
            return back()->with('error', 'Shiprocket credentials are missing or invalid.');
        }

        if (!empty($order->shiprocket_order_id)) {
            return back()->with('error', 'This order has already been pushed to Shiprocket.');
        }

        $result = $shiprocket->createOrder($order);

        if (!($result['success'] ?? false)) {
            return back()->with('error', 'Shiprocket push failed: ' . ($result['message'] ?? 'unknown error'));
        }

        $order->forceFill([
            'shiprocket_order_id' => $result['shiprocket_order_id'] ?? null,
            'shiprocket_shipment_id' => $result['shipment_id'] ?? null,
            'shiprocket_awb' => $result['awb'] ?? null,
            'shiprocket_pushed_at' => now(),
        ])->save();

        return back()->with('success', 'Order pushed to Shiprocket successfully.');
    }

    /**
     * Bulk-sync missing customer details for all Shiprocket Checkout orders.
     *
     * First tries the Checkout API list endpoint (one call, all orders).
     * Falls back to per-order API calls for any still-missing after the list pass.
     */
    /**
     * Batched "Sync Orders" for the toolbar button. Fills missing pricing / discount /
     * payment on orders that have a Shiprocket id but no captured pricing yet.
     *
     * Idempotent + safe: reuses the shared ShiprocketCheckoutService::syncOrderPricing()
     * (missing-only updates, never overwrites manual edits, no duplicate rows, and the
     * order-details API call already handles timeout/retry). The button calls this
     * repeatedly with an `id` cursor (before_id) so every order is walked exactly once —
     * failures included — which keeps it safe over large datasets and avoids re-loops.
     */
    public function syncOrders(Request $request, ShiprocketCheckoutService $sr): JsonResponse
    {
        $beforeId = (int) $request->input('before_id', 0);

        // Candidate = has a Shiprocket order id but no pricing breakdown captured yet.
        $base = Order::query()
            ->whereNotNull('shiprocket_order_id')
            ->whereNull('metadata->sr_pricing');

        // Total is computed only on the first call (before the cursor starts moving).
        $total = $beforeId <= 0 ? (clone $base)->count() : null;

        $batch = (clone $base)
            ->when($beforeId > 0, fn ($q) => $q->where('id', '<', $beforeId))
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        $updated = 0; $skipped = 0; $failed = 0; $failedIds = []; $lastId = $beforeId;

        foreach ($batch as $order) {
            $lastId = $order->id;
            try {
                $r = $sr->syncOrderPricing($order); // safe, missing-only; null = no API data
                if ($r === null) {
                    $failed++; $failedIds[] = $order->order_number;
                } elseif (! empty($r['changed'])) {
                    $updated++;
                } else {
                    $skipped++;
                }
            } catch (\Throwable $e) {
                $failed++; $failedIds[] = $order->order_number;
                Log::warning('admin.sync-orders: order failed', ['order' => $order->order_number, 'error' => $e->getMessage()]);
            }
        }

        $done = $batch->count() < 50;

        Log::info('admin.sync-orders batch', [
            'admin'     => auth('admin')->id(),
            'before_id' => $beforeId,
            'processed' => $batch->count(),
            'updated'   => $updated,
            'skipped'   => $skipped,
            'failed'    => $failed,
            'done'      => $done,
        ]);

        return response()->json([
            'ok'         => true,
            'total'      => $total,
            'processed'  => $batch->count(),
            'updated'    => $updated,
            'skipped'    => $skipped,
            'failed'     => $failed,
            'failed_ids' => $failedIds,
            'last_id'    => $lastId,
            'done'       => $done,
        ]);
    }

    public function syncShiprocketCustomers(Request $request, ShiprocketService $shiprocket): JsonResponse
    {
        $synced  = 0;
        $failed  = 0;
        $skipped = 0;

        // Orders with shiprocket_order_id but missing guest_name
        $orders = Order::whereNotNull('shiprocket_order_id')
            ->whereNull('user_id')
            ->where(function ($q) {
                $q->whereNull('guest_name')->orWhere('guest_name', '');
            })
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        if ($orders->isEmpty()) {
            return response()->json(['message' => 'All orders already have customer details.', 'synced' => 0]);
        }

        // --- Pass 1: fill from stored webhook events (local DB, no API call) ---
        $srIds = $orders->pluck('shiprocket_order_id')->filter()->toArray();
        $eventsByCartId = ShiprocketCheckoutEvent::whereIn('cart_id', $srIds)
            ->where('is_duplicate', false)
            ->whereNotNull('phone')
            ->orderByDesc('received_at')
            ->get()
            ->groupBy('cart_id');

        foreach ($orders as $order) {
            $events = $eventsByCartId[$order->shiprocket_order_id] ?? collect();
            $event = $events->first(fn ($e) => !empty($e->full_name)) ?? $events->first();
            if (!$event) continue;

            $srOrder = [
                'name'             => $event->full_name,
                'email'            => $event->email,
                'phone'            => $event->phone,
                'billing_address'  => $event->address_line_1,
                'billing_address_2'=> $event->address_line_2,
                'city'             => $event->city,
                'state'            => $event->state,
                'pincode'          => $event->pincode,
                'billing_country'  => $event->country,
            ];

            $updated = $this->applyShiprocketCustomerData($order, $srOrder);
            $updated ? $synced++ : $skipped++;
        }

        // Reload to see which still need updating after pass 1
        $orders = $orders->filter(fn ($o) => empty($o->fresh()->guest_name));

        // --- Pass 3: Shiprocket Shipping API fallback ---
        // Every Checkout order is auto-pushed to Shiprocket Shipping, which DOES expose
        // full customer billing details. Match by channel_order_id = our order_number.
        if ($orders->isNotEmpty() && $shiprocket->isConfigured()) {
            $shippingIndex = [];
            $page = 1;

            do {
                $shippingData = $shiprocket->getShippingOrders($page, 100);
                if (!$shippingData || empty($shippingData['orders'])) {
                    break;
                }

                foreach ($shippingData['orders'] as $shOrder) {
                    // Index by channel_order_id (= our order_number when we pushed it)
                    $ref = (string) ($shOrder['channel_order_id'] ?? '');
                    if ($ref !== '') {
                        $shippingIndex[$ref] = $shOrder;
                    }
                    // Also index by Shiprocket's own order id for cross-matching
                    $sid = (string) ($shOrder['id'] ?? '');
                    if ($sid !== '') {
                        $shippingIndex[$sid] = $shOrder;
                    }
                }

                $page++;
                // Stop after 10 pages (1 000 orders) to avoid long requests
                $hasMore = count($shippingData['orders']) >= 100 && $page <= 10;
            } while ($hasMore);

            foreach ($orders as $order) {
                $shOrder = $shippingIndex[$order->order_number]
                    ?? $shippingIndex[(string) $order->shiprocket_order_id]
                    ?? null;

                if (!$shOrder) {
                    $failed++;
                    continue;
                }

                $updated = $this->applyShiprocketCustomerData($order->fresh(), $shOrder);
                $updated ? $synced++ : $skipped++;
            }
        } else {
            $failed += $orders->count();
        }

        return response()->json([
            'message' => "Sync complete. Updated: {$synced}, Already had data: {$skipped}, Not found in Shiprocket: {$failed}.",
            'synced'  => $synced,
            'failed'  => $failed,
            'skipped' => $skipped,
        ]);
    }

    /**
     * Fill in missing guest_name / guest_email / guest_phone / shipping_address_snapshot
     * from a Shiprocket order array. Returns true if anything was saved.
     */
    private function applyShiprocketCustomerData(Order $order, array $srOrder): bool
    {
        $c = $srOrder['customer_details'] ?? $srOrder;
        $updates = [];

        if (empty($order->guest_name)) {
            $name = trim(
                ($c['billing_customer_name'] ?? $c['customer_name'] ?? $c['name'] ?? '') . ' ' .
                ($c['billing_last_name'] ?? '')
            );
            if (!empty($name)) {
                $updates['guest_name'] = $name;
            }
        }

        if (empty($order->guest_email)) {
            $email = $c['billing_email'] ?? $c['customer_email'] ?? $c['email'] ?? null;
            if (!empty($email)) {
                $updates['guest_email'] = $email;
            }
        }

        if (empty($order->guest_phone)) {
            $phone = $c['billing_phone'] ?? $c['customer_phone'] ?? $c['phone'] ?? $c['mobile'] ?? null;
            if (!empty($phone)) {
                $updates['guest_phone'] = $phone;
            }
        }

        $apiAddress = $c['billing_address'] ?? $c['address'] ?? '';
        if ($this->shouldUpdateAddress($order) && !empty($apiAddress)) {
            $updates['shipping_address_snapshot'] = [
                'name'          => $updates['guest_name'] ?? $order->guest_name ?? '',
                'phone'         => $updates['guest_phone'] ?? $order->guest_phone ?? '',
                'address_line_1'=> $apiAddress,
                'address_line_2'=> $c['billing_address_2'] ?? '',
                'city'          => $c['billing_city'] ?? $c['city'] ?? '',
                'state'         => $c['billing_state'] ?? $c['state'] ?? '',
                'postal_code'   => $c['billing_pincode'] ?? $c['pincode'] ?? '',
                'country'       => $c['billing_country'] ?? 'India',
            ];
        }

        if (!empty($updates)) {
            $order->update($updates);
            return true;
        }

        return false;
    }

    /**
     * Backfill missing addresses for Shiprocket orders that already have customer name/phone.
     * Uses the Shipping API (which has full billing address) — safe, does not touch orders
     * that already have a populated address.
     */
    public function syncShiprocketAddresses(Request $request, ShiprocketService $shiprocket): JsonResponse
    {
        $synced = 0;
        $failed = 0;

        // Orders that have customer details but missing/empty address
        $orders = Order::whereNotNull('shiprocket_order_id')
            ->where(function ($q) {
                $q->whereNotNull('guest_name')->where('guest_name', '!=', '');
            })
            ->where(function ($q) {
                $q->whereNull('shipping_address_snapshot')
                  ->orWhereJsonContains('shipping_address_snapshot->address_line_1', '')
                  ->orWhere('shipping_address_snapshot->address_line_1', null);
            })
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        // Filter in PHP to catch edge cases (JSON column queries vary across PG versions)
        $orders = $orders->filter(fn ($o) => $this->shouldUpdateAddress($o));

        if ($orders->isEmpty()) {
            return response()->json(['message' => 'All orders already have addresses.', 'synced' => 0]);
        }

        foreach ($orders as $order) {
            // Try Shipping API (has full billing address)
            $srOrder = $shiprocket->findShippingOrderByCheckoutId($order->shiprocket_order_id);

            if (!$srOrder) {
                $failed++;
                continue;
            }

            $c = $srOrder['customer_details'] ?? $srOrder;
            $apiAddress = $c['billing_address'] ?? $c['customer_address'] ?? '';

            if (empty($apiAddress)) {
                $failed++;
                continue;
            }

            $order->update([
                'shipping_address_snapshot' => [
                    'name'           => $order->guest_name ?? '',
                    'phone'          => $order->guest_phone ?? '',
                    'address_line_1' => $apiAddress,
                    'address_line_2' => $c['billing_address_2'] ?? '',
                    'city'           => $c['billing_city'] ?? $c['customer_city'] ?? '',
                    'state'          => $c['billing_state'] ?? $c['customer_state'] ?? '',
                    'postal_code'    => $c['billing_pincode'] ?? $c['customer_pincode'] ?? '',
                    'country'        => $c['billing_country'] ?? 'India',
                ],
            ]);
            $synced++;
        }

        return response()->json([
            'message' => "Address sync complete. Updated: {$synced}, Not found: {$failed}.",
            'synced'  => $synced,
            'failed'  => $failed,
        ]);
    }

    private function shouldUpdateAddress(Order $order): bool
    {
        $snap = $order->shipping_address_snapshot;
        if (empty($snap)) {
            return true;
        }
        if (is_string($snap)) {
            $snap = json_decode($snap, true) ?? [];
        }
        return empty($snap['address_line_1']) && empty($snap['address']) && empty($snap['city']);
    }

    /**
     * Statuses that allow order editing.
     */
    private const EDITABLE_STATUSES = ['confirmed', 'processing', 'packed'];

    public function editOrder(Order $order): View|RedirectResponse
    {
        if (!in_array($order->status, self::EDITABLE_STATUSES)) {
            return redirect()->route('admin.orders.show', $order)
                ->with('error', 'Only confirmed, processing, or packed orders can be edited.');
        }

        $order->load(['user', 'items.product', 'statusHistory']);

        return view('admin.orders.edit', compact('order'));
    }

    public function updateOrder(Request $request, Order $order): RedirectResponse
    {
        if (!in_array($order->status, self::EDITABLE_STATUSES)) {
            return back()->with('error', 'This order cannot be edited in its current status.');
        }

        $validated = $request->validate([
            'shipping_name' => ['required', 'string', 'max:255'],
            'shipping_address' => ['required', 'string', 'max:500'],
            'shipping_city' => ['required', 'string', 'max:100'],
            'shipping_state' => ['required', 'string', 'max:100'],
            'shipping_postal_code' => ['required', 'string', 'max:20'],
            'shipping_phone' => ['required', 'string', 'max:20'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $oldSnapshot = $order->shipping_address_snapshot ?? [];
        $newSnapshot = array_merge($oldSnapshot, [
            'name' => $validated['shipping_name'],
            'address' => $validated['shipping_address'],
            'city' => $validated['shipping_city'],
            'state' => $validated['shipping_state'],
            'postal_code' => $validated['shipping_postal_code'],
            'phone' => $validated['shipping_phone'],
        ]);

        $changes = [];
        foreach (['name', 'address', 'city', 'state', 'postal_code', 'phone'] as $field) {
            $old = $oldSnapshot[$field] ?? '';
            $new = $newSnapshot[$field] ?? '';
            if ($old !== $new) {
                $changes[] = "{$field}: \"{$old}\" -> \"{$new}\"";
            }
        }

        $order->update([
            'shipping_address_snapshot' => $newSnapshot,
            'admin_notes' => $validated['admin_notes'],
        ]);

        $comment = 'Order edited by admin.';
        if (!empty($changes)) {
            $comment .= ' Address changes: ' . implode(', ', $changes);
        }

        $order->statusHistory()->create([
            'status' => $order->status,
            'comment' => $comment,
            'created_by' => auth('admin')->id(),
        ]);

        return redirect()->route('admin.orders.show', $order)->with('success', 'Order updated successfully.');
    }

    public function addItem(Request $request, Order $order): RedirectResponse
    {
        if (!in_array($order->status, self::EDITABLE_STATUSES)) {
            return back()->with('error', 'This order cannot be edited in its current status.');
        }

        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $product = Product::findOrFail($validated['product_id']);

        if ($product->stock_quantity < $validated['quantity']) {
            return back()->with('error', "Insufficient stock for {$product->name}. Available: {$product->stock_quantity}");
        }

        // Check if product already exists in order
        $existingItem = $order->items()->where('product_id', $product->id)->first();

        if ($existingItem) {
            $existingItem->update([
                'quantity' => $existingItem->quantity + $validated['quantity'],
                'total' => ($existingItem->quantity + $validated['quantity']) * $existingItem->price,
            ]);
        } else {
            $order->items()->create([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'sku' => $product->sku ?? '',
                'mrp' => $product->mrp,
                'price' => $product->price,
                'quantity' => $validated['quantity'],
                'tax' => 0,
                'discount' => 0,
                'total' => $product->price * $validated['quantity'],
            ]);
        }

        // Decrement stock
        $product->decrement('stock_quantity', $validated['quantity']);

        $this->recalculateOrderTotals($order);

        $order->statusHistory()->create([
            'status' => $order->status,
            'comment' => "Item added: {$product->name} x{$validated['quantity']}",
            'created_by' => auth('admin')->id(),
        ]);

        return back()->with('success', "Added {$product->name} to order.");
    }

    public function removeItem(Request $request, Order $order, $itemId): RedirectResponse
    {
        if (!in_array($order->status, self::EDITABLE_STATUSES)) {
            return back()->with('error', 'This order cannot be edited in its current status.');
        }

        $item = $order->items()->findOrFail($itemId);

        // Restore stock
        if ($item->variant_id) {
            DB::table('product_variants')->where('id', $item->variant_id)->increment('stock_quantity', $item->quantity);
        } else {
            DB::table('products')->where('id', $item->product_id)->increment('stock_quantity', $item->quantity);
        }

        $productName = $item->product_name;
        $item->delete();

        $this->recalculateOrderTotals($order);

        $order->statusHistory()->create([
            'status' => $order->status,
            'comment' => "Item removed: {$productName}",
            'created_by' => auth('admin')->id(),
        ]);

        return back()->with('success', "Removed {$productName} from order.");
    }

    public function updateItemQuantity(Request $request, Order $order, $itemId): RedirectResponse
    {
        if (!in_array($order->status, self::EDITABLE_STATUSES)) {
            return back()->with('error', 'This order cannot be edited in its current status.');
        }

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $item = $order->items()->findOrFail($itemId);
        $oldQuantity = $item->quantity;
        $newQuantity = $validated['quantity'];
        $diff = $newQuantity - $oldQuantity;

        // Adjust stock
        if ($diff > 0) {
            // Need more stock
            $stockColumn = $item->variant_id ? 'product_variants' : 'products';
            $stockId = $item->variant_id ?: $item->product_id;
            $currentStock = DB::table($stockColumn)->where('id', $stockId)->value('stock_quantity');

            if ($currentStock < $diff) {
                return back()->with('error', "Insufficient stock. Available: {$currentStock}");
            }

            DB::table($stockColumn)->where('id', $stockId)->decrement('stock_quantity', $diff);
        } elseif ($diff < 0) {
            // Restore stock
            if ($item->variant_id) {
                DB::table('product_variants')->where('id', $item->variant_id)->increment('stock_quantity', abs($diff));
            } else {
                DB::table('products')->where('id', $item->product_id)->increment('stock_quantity', abs($diff));
            }
        }

        $item->update([
            'quantity' => $newQuantity,
            'total' => $item->price * $newQuantity,
        ]);

        $this->recalculateOrderTotals($order);

        $order->statusHistory()->create([
            'status' => $order->status,
            'comment' => "Item quantity updated: {$item->product_name} ({$oldQuantity} -> {$newQuantity})",
            'created_by' => auth('admin')->id(),
        ]);

        return back()->with('success', 'Item quantity updated.');
    }

    /**
     * Recalculate order subtotal and total from items.
     */
    private function recalculateOrderTotals(Order $order): void
    {
        $order->load('items');
        $subtotal = $order->items->sum('total');

        $order->update([
            'subtotal' => $subtotal,
            'total' => max(0, $subtotal - $order->discount + $order->shipping_cost + $order->tax),
        ]);
    }
}
