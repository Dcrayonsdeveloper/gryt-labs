<?php

namespace App\Http\Controllers\Affiliate;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $affiliate = Auth::user()->affiliate;
        abort_unless($affiliate, 403);

        $recentCommissions = $affiliate->commissions()
            ->with('order')
            ->latest()
            ->take(10)
            ->get();

        $thisMonthEarnings = $affiliate->commissions()
            ->whereIn('status', ['approved', 'paid'])
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('commission_amount');

        $conversionRate = $affiliate->total_clicks > 0
            ? round(($affiliate->total_orders / $affiliate->total_clicks) * 100, 2)
            : 0;

        return view('affiliate.dashboard.index', [
            'title' => 'Dashboard',
            'affiliate' => $affiliate,
            'recentCommissions' => $recentCommissions,
            'thisMonthEarnings' => $thisMonthEarnings,
            'conversionRate' => $conversionRate,
        ]);
    }
}
