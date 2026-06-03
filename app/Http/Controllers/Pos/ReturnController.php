<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\CreditNote;
use App\Models\PosSale;
use App\Models\PosReturn;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReturnController extends Controller
{
    /**
     * Returns page placeholder — returns are processed via modals from the billing screen.
     */
    public function index()
    {
        return response()->json(['message' => 'Use findSale to start a return.']);
    }

    /**
     * Find a sale by sale number or customer phone for return.
     */
    public function findSale(Request $request): JsonResponse
    {
        $query = $request->input('q', '');

        if (strlen($query) < 3) {
            return response()->json(['sales' => []]);
        }

        $storeId = $request->session()->get('pos_store_id');

        $sales = PosSale::with(['items', 'customer'])
            ->where('store_id', $storeId)
            ->where('status', 'completed')
            ->where(function ($q) use ($query) {
                $q->where('sale_number', 'like', "%{$query}%")
                  ->orWhereHas('customer', fn ($cq) => $cq->where('phone', 'like', "%{$query}%")
                      ->orWhere('first_name', 'like', "%{$query}%"));
            })
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn (PosSale $sale) => [
                'id'          => $sale->id,
                'sale_number' => $sale->sale_number,
                'date'        => $sale->created_at->format('d M Y, g:i A'),
                'total'       => (float) $sale->total,
                'customer'    => $sale->customer
                    ? ($sale->customer->first_name ?? $sale->customer->name)
                    : 'Walk-in',
                'items'       => $sale->items->map(fn ($item) => [
                    'id'           => $item->id,
                    'product_name' => $item->product_name,
                    'quantity'     => $item->quantity,
                    'price'        => (float) $item->price,
                    'total'        => (float) $item->total,
                    'product_id'   => $item->product_id,
                    'variant_id'   => $item->variant_id,
                ]),
            ]);

        return response()->json(['sales' => $sales]);
    }

    /**
     * Process a return.
     */
    public function process(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pos_sale_id'    => ['required', 'integer', 'exists:pos_sales,id'],
            'items'          => ['required', 'array', 'min:1'],
            'items.*.sale_item_id' => ['required', 'integer'],
            'items.*.quantity'     => ['required', 'integer', 'min:1'],
            'items.*.reason'       => ['nullable', 'string', 'max:100'],
            'items.*.condition'    => ['nullable', 'in:unused_with_tags,used,defective'],
            'refund_method'  => ['required', 'in:cash,original_payment,credit_note'],
            'reason'         => ['nullable', 'string', 'max:500'],
        ]);

        $staffId = $request->session()->get('pos_staff_id');
        $storeId = $request->session()->get('pos_store_id');

        $sale = PosSale::with('items')
            ->where('id', $validated['pos_sale_id'])
            ->where('store_id', $storeId)
            ->where('status', 'completed')
            ->firstOrFail();

        try {
            $result = DB::transaction(function () use ($sale, $validated, $staffId, $storeId) {
                $totalRefund = 0;
                $returnItems = [];

                foreach ($validated['items'] as $returnItemData) {
                    $saleItem = $sale->items->firstWhere('id', $returnItemData['sale_item_id']);
                    if (! $saleItem) continue;

                    $qty = min($returnItemData['quantity'], $saleItem->quantity);
                    $refundAmount = round($saleItem->price * $qty, 2);
                    $totalRefund += $refundAmount;

                    $returnItems[] = [
                        'product_id'    => $saleItem->product_id,
                        'variant_id'    => $saleItem->variant_id,
                        'product_name'  => $saleItem->product_name,
                        'quantity'      => $qty,
                        'price'         => $saleItem->price,
                        'refund_amount' => $refundAmount,
                        'reason'        => $returnItemData['reason'] ?? null,
                        'condition'     => $returnItemData['condition'] ?? 'unused_with_tags',
                    ];

                    // Restore stock
                    if ($saleItem->variant_id) {
                        ProductVariant::where('id', $saleItem->variant_id)->increment('stock_quantity', $qty);
                    } else {
                        Product::where('id', $saleItem->product_id)->increment('stock_quantity', $qty);
                    }
                }

                // Create return record
                $creditNote = null;
                $return = PosReturn::create([
                    'pos_sale_id'   => $sale->id,
                    'store_id'      => $storeId,
                    'staff_id'      => $staffId,
                    'customer_id'   => $sale->customer_id,
                    'amount'        => $totalRefund,
                    'refund_method' => $validated['refund_method'],
                    'reason'        => $validated['reason'] ?? null,
                    'status'        => 'completed',
                    'type'          => 'return',
                ]);

                // Create return line items
                foreach ($returnItems as $ri) {
                    $return->items()->create($ri);
                }

                // Generate credit note if refund method is credit_note
                if ($validated['refund_method'] === 'credit_note' && $sale->customer_id) {
                    $creditNote = CreditNote::create([
                        'user_id'          => $sale->customer_id,
                        'amount'           => $totalRefund,
                        'remaining_amount' => $totalRefund,
                        'status'           => 'active',
                        'expires_at'       => now()->addYear(),
                    ]);

                    $return->update(['credit_note_id' => $creditNote->id]);
                }

                return [
                    'return'       => $return,
                    'refund'       => $totalRefund,
                    'credit_note'  => $creditNote?->credit_note_number,
                ];
            });

            return response()->json([
                'success'       => true,
                'return_number' => $result['return']->return_number,
                'refund_amount' => $result['refund'],
                'credit_note'   => $result['credit_note'],
                'message'       => 'Return processed successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Return processing failed. ' . $e->getMessage(),
            ], 500);
        }
    }
}
