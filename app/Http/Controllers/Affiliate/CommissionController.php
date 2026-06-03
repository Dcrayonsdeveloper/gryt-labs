<?php

namespace App\Http\Controllers\Affiliate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CommissionController extends Controller
{
    public function index(Request $request): View
    {
        $affiliate = Auth::user()->affiliate;
        abort_unless($affiliate, 403);

        $status = $request->get('status');

        $commissions = $affiliate->commissions()
            ->with('order')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(20);

        $stats = [
            'total' => $affiliate->commissions()->sum('commission_amount'),
            'pending' => $affiliate->commissions()->where('status', 'pending')->sum('commission_amount'),
            'approved' => $affiliate->commissions()->where('status', 'approved')->sum('commission_amount'),
            'paid' => $affiliate->commissions()->where('status', 'paid')->sum('commission_amount'),
        ];

        return view('affiliate.commissions.index', [
            'title' => 'Commissions',
            'commissions' => $commissions,
            'stats' => $stats,
            'currentStatus' => $status,
        ]);
    }
}
