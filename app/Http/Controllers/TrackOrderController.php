<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\ReturnItem;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrackOrderController extends Controller
{
    public function index(): View
    {
        return view('track-order.index');
    }

    public function track(Request $request): View
    {
        if (auth()->check()) {
            $validated = $request->validate([
                'order_number' => 'required|string',
            ]);

            $order = Order::where('order_number', $validated['order_number'])
                ->where('user_id', auth()->id())
                ->with(['items.product', 'shipments', 'statusHistory', 'deliveryPartner.user'])
                ->first();

            $errorMsg = 'Order not found. Please check your order number.';
        } else {
            $validated = $request->validate([
                'order_number' => 'required|string',
                'contact'      => 'required|string',
            ]);

            $contact = trim($validated['contact']);

            $order = Order::where('order_number', $validated['order_number'])
                ->where(function ($q) use ($contact) {
                    $q->where('guest_email', $contact)
                      ->orWhere('guest_phone', $contact)
                      ->orWhereHas('user', fn ($u) => $u->where('email', $contact));
                })
                ->with(['items.product', 'shipments', 'statusHistory', 'deliveryPartner.user'])
                ->first();

            $errorMsg = 'Order not found. Please check your order number and email / phone.';
        }

        if (!$order) {
            return view('track-order.index', ['error' => $errorMsg]);
        }

        // Store a session key so cancel/return don't require re-authentication
        session(['order_access_' . $order->id => true]);

        $latestShipment = $order->shipments->first();

        return view('track-order.show', compact('order', 'latestShipment'));
    }

    /**
     * Cancel the order (guest or logged-in user).
     */
    public function cancel(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeOrderAccess($order);

        if (!in_array($order->status, ['pending', 'confirmed', 'processing'])) {
            return back()->with('error', 'This order cannot be cancelled.');
        }

        $actorId = auth()->check() ? auth()->id() : null;
        $order->updateStatus('cancelled', $actorId, 'Cancelled by customer');

        return back()->with('success', 'Your order has been cancelled.');
    }

    /**
     * Show the guest return request form.
     */
    public function returnForm(Request $request, Order $order): View|RedirectResponse
    {
        $this->authorizeOrderAccess($order);

        if ($order->status !== 'delivered') {
            return back()->with('error', 'Only delivered orders can be returned.');
        }

        $returnWindowDays = (int) Setting::get('return_window_days', 7);
        $returnMinHours   = (int) Setting::get('return_min_hours', 24);

        if (!$order->delivered_at
            || !$order->delivered_at->addHours($returnMinHours)->isPast()
            || $order->delivered_at->diffInDays(now()) > $returnWindowDays) {
            return back()->with('error', "Returns are available {$returnMinHours} hours after delivery, within a {$returnWindowDays}-day window.");
        }

        // Exclude items already in a non-rejected return
        $returnedItemIds = ReturnItem::whereHas('return', function ($q) use ($order) {
            $q->where('order_id', $order->id)->where('status', '!=', 'rejected');
        })->pluck('order_item_id')->toArray();

        $order->setRelation('items', $order->items->reject(fn ($i) => in_array($i->id, $returnedItemIds)));

        if ($order->items->isEmpty()) {
            return back()->with('error', 'All items in this order already have a return request.');
        }

        return view('track-order.return', compact('order', 'returnWindowDays', 'returnMinHours'));
    }

    /**
     * Store the guest return request.
     */
    public function storeReturn(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeOrderAccess($order);

        $validated = $request->validate([
            'type'            => 'required|in:return,exchange',
            'reason_category' => 'required|string|in:Product not as described,Received damaged/defective item,Wrong item received,Changed my mind,Better price available,Other',
            'reason'          => 'nullable|string|max:1000',
            'items'           => 'required|array|min:1',
            'items.*.order_item_id' => 'required|exists:order_items,id',
            'items.*.quantity'      => 'required|integer|min:1',
            'items.*.reason'        => 'nullable|string|max:500',
            'items.*.condition'     => 'required|in:unopened,opened,damaged',
        ]);

        $submittedItemIds = collect($validated['items'])->pluck('order_item_id')->toArray();
        $alreadyReturned  = ReturnItem::whereIn('order_item_id', $submittedItemIds)
            ->whereHas('return', fn ($q) => $q->where('status', '!=', 'rejected'))
            ->exists();

        if ($alreadyReturned) {
            return back()->withInput()->withErrors(['items' => 'One or more selected items already have a return request.']);
        }

        $return = OrderReturn::create([
            'order_id'        => $order->id,
            'user_id'         => $order->user_id,   // null for guest orders
            'type'            => $validated['type'],
            'reason_category' => $validated['reason_category'],
            'reason'          => $validated['reason'] ?? null,
            'status'          => 'requested',
        ]);

        foreach ($validated['items'] as $item) {
            $return->items()->create([
                'order_item_id' => $item['order_item_id'],
                'quantity'      => $item['quantity'],
                'reason'        => $item['reason'] ?? null,
                'condition'     => $item['condition'],
            ]);
        }

        return redirect()->route('track-order.return.confirmation', $order)
            ->with('success', 'Return request submitted. Our team will contact you within 24–48 hours.');
    }

    /**
     * Return request confirmation page.
     */
    public function returnConfirmation(Order $order): View
    {
        $this->authorizeOrderAccess($order);

        return view('track-order.return-confirmation', compact('order'));
    }

    /**
     * Verify the current visitor has previously looked up this order in this session.
     * For logged-in users, verify they own the order.
     */
    private function authorizeOrderAccess(Order $order): void
    {
        if (auth()->check()) {
            abort_if($order->user_id !== auth()->id(), 403);
            return;
        }

        abort_unless(session('order_access_' . $order->id), 403, 'Session expired. Please look up your order again.');
    }
}
