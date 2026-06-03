<?php

namespace App\Http\Controllers\Admin;

use App\Events\OrderPlaced;
use App\Http\Controllers\Controller;
use App\Models\DraftOrder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DraftOrderController extends Controller
{
    public function index(Request $request): View
    {
        $query = DraftOrder::with(['admin', 'customer']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_email', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        $draftOrders = $query->latest()->paginate(15)->withQueryString();

        $stats = [
            'total' => DraftOrder::count(),
            'draft' => DraftOrder::where('status', 'draft')->count(),
            'sent' => DraftOrder::where('status', 'sent')->count(),
            'completed' => DraftOrder::where('status', 'completed')->count(),
        ];

        return view('admin.draft-orders.index', compact('draftOrders', 'stats'));
    }

    public function create(): View
    {
        return view('admin.draft-orders.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:users,id',
            'customer_name' => 'nullable|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.variant_id' => 'nullable|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:2000',
        ]);

        // Enrich items with product names
        $items = [];
        foreach ($validated['items'] as $item) {
            $product = Product::find($item['product_id']);
            $items[] = [
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'] ?? null,
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'product_name' => $product->name ?? '',
                'sku' => $product->sku ?? '',
            ];
        }

        // If customer_id is set, populate name/email/phone from user
        if (!empty($validated['customer_id'])) {
            $user = User::find($validated['customer_id']);
            if ($user) {
                $validated['customer_name'] = $validated['customer_name'] ?: ($user->first_name . ' ' . $user->last_name);
                $validated['customer_email'] = $validated['customer_email'] ?: $user->email;
                $validated['customer_phone'] = $validated['customer_phone'] ?: $user->phone;
            }
        }

        $draft = new DraftOrder([
            'admin_id' => auth('admin')->id(),
            'customer_id' => $validated['customer_id'] ?? null,
            'customer_name' => $validated['customer_name'] ?? '',
            'customer_email' => $validated['customer_email'] ?? '',
            'customer_phone' => $validated['customer_phone'] ?? '',
            'items' => $items,
            'discount' => $validated['discount'] ?? 0,
            'shipping_cost' => $validated['shipping_cost'] ?? 0,
            'tax' => $validated['tax'] ?? 0,
            'notes' => $validated['notes'] ?? null,
        ]);

        $draft->recalculateTotals();
        $draft->save();

        $action = $request->input('action', 'save');
        if ($action === 'send') {
            return $this->performSend($draft);
        }

        return redirect()->route('admin.draft-orders.index')->with('success', 'Draft order created successfully.');
    }

    public function show(DraftOrder $draftOrder): View
    {
        $draftOrder->load(['admin', 'customer', 'order']);

        // Resolve product details for display
        $productIds = collect($draftOrder->items ?? [])->pluck('product_id')->filter()->unique();
        $products = Product::with('primaryImage')->whereIn('id', $productIds)->get()->keyBy('id');

        return view('admin.draft-orders.show', compact('draftOrder', 'products'));
    }

    public function send(DraftOrder $draftOrder): RedirectResponse
    {
        if ($draftOrder->isCompleted() || $draftOrder->isCancelled()) {
            return back()->with('error', 'This draft order cannot be sent.');
        }

        return $this->performSend($draftOrder);
    }

    public function complete(DraftOrder $draftOrder): RedirectResponse
    {
        if ($draftOrder->isCompleted()) {
            return back()->with('error', 'This draft order is already completed.');
        }

        if ($draftOrder->isCancelled()) {
            return back()->with('error', 'Cannot complete a cancelled draft order.');
        }

        try {
            DB::beginTransaction();

            // Create real Order
            $order = Order::create([
                'user_id' => $draftOrder->customer_id,
                'guest_email' => $draftOrder->customer_id ? null : $draftOrder->customer_email,
                'guest_name' => $draftOrder->customer_id ? null : $draftOrder->customer_name,
                'guest_phone' => $draftOrder->customer_id ? null : $draftOrder->customer_phone,
                'status' => 'confirmed',
                'payment_status' => 'pending',
                'subtotal' => $draftOrder->subtotal,
                'discount' => $draftOrder->discount,
                'shipping_cost' => $draftOrder->shipping_cost,
                'tax' => $draftOrder->tax,
                'total' => $draftOrder->total,
                'paid_amount' => 0,
                'notes' => $draftOrder->notes,
                'source' => 'draft_order',
                'metadata' => [
                    'draft_order_id' => $draftOrder->id,
                    'created_by_admin' => $draftOrder->admin_id,
                ],
            ]);

            // Create OrderItems and decrement stock
            foreach ($draftOrder->items as $item) {
                $product = Product::find($item['product_id']);
                if (!$product) {
                    continue;
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'] ?? null,
                    'seller_id' => $product->seller_id,
                    'product_name' => $item['product_name'] ?? $product->name,
                    'sku' => $item['sku'] ?? $product->sku ?? '',
                    'quantity' => $item['quantity'],
                    'mrp' => $product->mrp ?? $item['price'],
                    'price' => $item['price'],
                    'tax' => 0,
                    'discount' => 0,
                    'total' => $item['price'] * $item['quantity'],
                ]);

                // Decrement stock
                if (!empty($item['variant_id'])) {
                    DB::table('product_variants')
                        ->where('id', $item['variant_id'])
                        ->decrement('stock_quantity', $item['quantity']);
                } else {
                    DB::table('products')
                        ->where('id', $item['product_id'])
                        ->decrement('stock_quantity', $item['quantity']);
                }

                // Update stock status if needed
                $product->refresh();
                if ($product->stock_quantity <= 0) {
                    $product->update(['stock_status' => 'out_of_stock']);
                }
            }

            // Add status history
            $order->statusHistory()->create([
                'status' => 'confirmed',
                'comment' => 'Order created from draft order #' . $draftOrder->id,
                'created_by' => auth('admin')->id(),
            ]);

            // Mark draft as completed
            $draftOrder->update([
                'status' => 'completed',
                'completed_at' => now(),
                'order_id' => $order->id,
            ]);

            DB::commit();

            // Dispatch OrderPlaced event
            try {
                OrderPlaced::dispatch($order, 'admin_draft');
            } catch (\Exception $e) {
                Log::error('OrderPlaced event dispatch failed for draft order', [
                    'draft_order_id' => $draftOrder->id,
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return redirect()->route('admin.orders.show', $order)
                ->with('success', 'Draft order completed. Order #' . $order->order_number . ' created.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Draft order completion failed', [
                'draft_order_id' => $draftOrder->id,
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Failed to complete draft order: ' . $e->getMessage());
        }
    }

    public function destroy(DraftOrder $draftOrder): RedirectResponse
    {
        if ($draftOrder->isCompleted()) {
            return back()->with('error', 'Cannot delete a completed draft order.');
        }

        $draftOrder->delete();

        return redirect()->route('admin.draft-orders.index')->with('success', 'Draft order deleted.');
    }

    /**
     * AJAX: Search customers for the draft order form.
     */
    public function searchCustomers(Request $request): JsonResponse
    {
        $query = $request->input('q', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $customers = User::where('role', 'customer')
            ->where(function ($q) use ($query) {
                $q->where('first_name', 'like', "%{$query}%")
                  ->orWhere('last_name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%")
                  ->orWhere('phone', 'like', "%{$query}%");
            })
            ->select('id', 'first_name', 'last_name', 'email', 'phone')
            ->limit(10)
            ->get()
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => trim($u->first_name . ' ' . $u->last_name),
                'email' => $u->email,
                'phone' => $u->phone,
            ]);

        return response()->json($customers);
    }

    protected function performSend(DraftOrder $draftOrder): RedirectResponse
    {
        // Generate a simple payment link (token-based)
        $token = Str::random(40);
        $paymentLink = url('/draft-order/pay/' . $token);

        $draftOrder->update([
            'status' => 'sent',
            'sent_at' => now(),
            'payment_link' => $paymentLink,
        ]);

        // Send email if customer has email
        if ($draftOrder->customer_email) {
            try {
                \Illuminate\Support\Facades\Mail::send(
                    'emails.draft-order-invoice',
                    ['draftOrder' => $draftOrder],
                    function ($message) use ($draftOrder) {
                        $message->to($draftOrder->customer_email, $draftOrder->customer_name)
                            ->subject('Invoice from ' . \App\Models\Setting::get('store_name', config('app.name')));
                    }
                );
            } catch (\Exception $e) {
                Log::error('Draft order email failed', [
                    'draft_order_id' => $draftOrder->id,
                    'error' => $e->getMessage(),
                ]);
                return redirect()->route('admin.draft-orders.index')
                    ->with('success', 'Draft order saved and marked as sent.')
                    ->with('warning', 'Email could not be sent: ' . $e->getMessage());
            }
        }

        return redirect()->route('admin.draft-orders.index')
            ->with('success', 'Invoice sent to ' . ($draftOrder->customer_email ?: 'customer') . '.');
    }
}
