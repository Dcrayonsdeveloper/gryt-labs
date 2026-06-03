<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $query = $request->user()->orders()->with('items.product');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->latest()->paginate(10)->withQueryString();

        return view('account.orders.index', compact('orders'));
    }

    public function show(Request $request, Order $order): View
    {
        // Ensure user owns this order
        abort_if($order->user_id !== $request->user()->id, 403);

        $order->load(['items.product', 'statusHistory', 'coupon', 'deliveryPartner.user']);

        return view('account.orders.show', compact('order'));
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        // Ensure user owns this order
        abort_if($order->user_id !== $request->user()->id, 403);

        // Can only cancel confirmed/processing orders
        if (!in_array($order->status, ['confirmed', 'processing'])) {
            return back()->with('error', 'This order cannot be cancelled.');
        }

        $order->updateStatus('cancelled', $request->user()->id, 'Cancelled by customer');

        return back()->with('success', 'Order cancelled successfully. Stock has been restored.');
    }

    public function invoice(Request $request, Order $order): View
    {
        // Ensure user owns this order
        abort_if($order->user_id !== $request->user()->id, 403);

        $order->load(['items.product', 'user']);

        return view('account.orders.invoice', compact('order'));
    }

    public function track(Request $request, Order $order): View
    {
        // Ensure user owns this order
        abort_if($order->user_id !== $request->user()->id, 403);

        $order->load(['statusHistory', 'shipments', 'items.product', 'deliveryPartner.user']);

        $trackingSteps = $order->getTrackingSteps();
        $latestShipment = $order->shipments->first();

        return view('account.orders.track', compact('order', 'trackingSteps', 'latestShipment'));
    }
}
