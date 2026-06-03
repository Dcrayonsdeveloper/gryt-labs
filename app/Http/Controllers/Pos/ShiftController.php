<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\PosSale;
use App\Models\PosReturn;
use App\Models\Staff;
use App\Models\StaffShift;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    /**
     * Show shift open screen.
     */
    public function showOpen(Request $request)
    {
        // Check if shift already open
        $shiftId = $request->session()->get('pos_shift_id');
        if ($shiftId) {
            $shift = StaffShift::find($shiftId);
            if ($shift && $shift->isOpen()) {
                return redirect()->route('pos.dashboard');
            }
        }

        $storeId = $request->session()->get('pos_store_id');

        // Get last closed shift for context
        $lastShift = StaffShift::with('staff.user')
            ->where('store_id', $storeId)
            ->where('status', 'closed')
            ->latest('shift_end')
            ->first();

        $staffName = $request->session()->get('pos_staff_name', 'Staff');

        return view('pos.shift-open', compact('lastShift', 'staffName'));
    }

    /**
     * Open a new shift.
     */
    public function open(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'opening_cash' => ['required', 'numeric', 'min:0', 'max:999999'],
        ]);

        $staffId    = $request->session()->get('pos_staff_id');
        $storeId    = $request->session()->get('pos_store_id');
        $registerId = $request->session()->get('pos_register_id');

        // Check no open shift exists for this terminal
        $existingShift = StaffShift::where('store_id', $storeId)
            ->where('register_id', $registerId)
            ->where('status', 'open')
            ->first();

        if ($existingShift) {
            // If same staff, resume the shift
            if ($existingShift->staff_id === $staffId) {
                $request->session()->put('pos_shift_id', $existingShift->id);
                return response()->json([
                    'success'  => true,
                    'message'  => 'Resumed existing shift.',
                    'redirect' => route('pos.dashboard'),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Another shift is already open on this terminal. Close it first.',
            ], 409);
        }

        $shift = StaffShift::create([
            'staff_id'     => $staffId,
            'store_id'     => $storeId,
            'register_id'  => $registerId,
            'shift_start'  => now(),
            'opening_cash' => $validated['opening_cash'],
            'status'       => 'open',
        ]);

        $request->session()->put('pos_shift_id', $shift->id);

        return response()->json([
            'success'  => true,
            'shift_id' => $shift->id,
            'redirect' => route('pos.dashboard'),
        ]);
    }

    /**
     * Show shift close / Z-report screen.
     */
    public function showClose(Request $request)
    {
        $shiftId = $request->session()->get('pos_shift_id');
        $shift   = StaffShift::with('staff.user')->findOrFail($shiftId);

        $summary = $this->buildShiftSummary($shift);

        return view('pos.shift-close', compact('shift', 'summary'));
    }

    /**
     * Get shift summary data (for Z-report).
     */
    public function summary(Request $request): JsonResponse
    {
        $shiftId = $request->session()->get('pos_shift_id');
        $shift   = StaffShift::findOrFail($shiftId);

        return response()->json($this->buildShiftSummary($shift));
    }

    /**
     * Close the current shift.
     */
    public function close(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'closing_cash' => ['required', 'numeric', 'min:0', 'max:999999'],
            'notes'        => ['nullable', 'string', 'max:500'],
        ]);

        $shiftId = $request->session()->get('pos_shift_id');
        $shift   = StaffShift::findOrFail($shiftId);

        $summary = $this->buildShiftSummary($shift);

        $shift->update([
            'shift_end'        => now(),
            'closing_cash'     => $validated['closing_cash'],
            'register_summary' => $summary,
            'notes'            => $validated['notes'] ?? null,
            'status'           => 'closed',
        ]);

        $request->session()->forget('pos_shift_id');

        return response()->json([
            'success'  => true,
            'message'  => 'Shift closed successfully.',
            'redirect' => route('pos.login'),
        ]);
    }

    /**
     * Build the complete shift summary for Z-report.
     */
    private function buildShiftSummary(StaffShift $shift): array
    {
        $storeId  = $shift->store_id;
        $staffId  = $shift->staff_id;
        $from     = $shift->shift_start;
        $to       = $shift->shift_end ?? now();

        // Sales during this shift
        $sales = PosSale::where('store_id', $storeId)
            ->where('staff_id', $staffId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$from, $to]);

        $totalBills    = (clone $sales)->count();
        $grossSales    = (float) (clone $sales)->sum('total');
        $totalDiscount = (float) (clone $sales)->sum('discount');
        $totalTax      = (float) (clone $sales)->sum('tax');

        // Payment breakdown
        $cashSales = (float) (clone $sales)->where('payment_method', 'cash')->sum('total');
        $cardSales = (float) (clone $sales)->where('payment_method', 'card')->sum('total');
        $upiSales  = (float) (clone $sales)->where('payment_method', 'upi')->sum('total');
        // Split payments: total - single-method totals
        $splitSales = $grossSales - $cashSales - $cardSales - $upiSales;

        // Returns during this shift
        $returns = PosReturn::where('store_id', $storeId)
            ->where('staff_id', $staffId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$from, $to]);

        $totalReturns     = (clone $returns)->count();
        $totalRefundAmount = (float) (clone $returns)->sum('amount');
        $cashRefunds      = (float) (clone $returns)->where('refund_method', 'cash')->sum('amount');

        // Cash reconciliation
        $expectedCash = $shift->opening_cash + $cashSales - $cashRefunds;

        return [
            'total_bills'      => $totalBills,
            'gross_sales'      => round($grossSales, 2),
            'total_discount'   => round($totalDiscount, 2),
            'total_tax'        => round($totalTax, 2),
            'net_sales'        => round($grossSales - $totalRefundAmount, 2),
            'total_returns'    => $totalReturns,
            'total_refunds'    => round($totalRefundAmount, 2),
            'payments'         => [
                'cash'  => round($cashSales, 2),
                'card'  => round($cardSales, 2),
                'upi'   => round($upiSales, 2),
                'split' => round(max(0, $splitSales), 2),
            ],
            'cash_reconciliation' => [
                'opening_cash'  => round((float) $shift->opening_cash, 2),
                'cash_sales'    => round($cashSales, 2),
                'cash_refunds'  => round($cashRefunds, 2),
                'expected_cash' => round($expectedCash, 2),
            ],
            'shift_duration'   => $from->diffForHumans($to, true),
            'shift_start'      => $from->format('g:i A'),
            'shift_end'        => $to->format('g:i A'),
        ];
    }
}
