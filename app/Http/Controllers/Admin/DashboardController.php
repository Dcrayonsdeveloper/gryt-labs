<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\Product;
use App\Models\User;
use App\Models\Seller;
use App\Helpers\DbCompat;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        // Date range filter
        $startDate = $request->filled('start_date') ? Carbon::parse($request->start_date)->startOfDay() : null;
        $endDate = $request->filled('end_date') ? Carbon::parse($request->end_date)->endOfDay() : null;
        $hasDateFilter = $startDate && $endDate;

        // Helper closure to apply date filter
        $dateFilter = function ($query) use ($startDate, $endDate, $hasDateFilter) {
            if ($hasDateFilter) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }
            return $query;
        };

        // Excluded statuses for revenue
        $excludedStatuses = ['cancelled', 'returned'];

        // Revenue filter: paid + not cancelled/returned
        $revenueFilter = fn ($query) => $query->where('payment_status', 'paid')->whereNotIn('status', $excludedStatuses);

        // Top-row stats: filtered when date filter active, otherwise today
        if ($hasDateFilter) {
            $topOrders = $dateFilter(Order::query())->count();
            $topRevenue = $revenueFilter($dateFilter(Order::query()))->sum('total');
        } else {
            $topOrders = Order::whereDate('created_at', today())->count();
            $topRevenue = $revenueFilter(Order::whereDate('created_at', today()))->sum('total');
        }

        // Filtered stats
        $totalOrders = $dateFilter(Order::query())->count();
        $totalRevenue = $revenueFilter($dateFilter(Order::query()))->sum('total');
        $totalCustomers = $dateFilter(User::where('role', 'customer'))->count();
        $totalProducts = Product::count();
        $totalSellers = Seller::count();
        $pendingOrders = $dateFilter(Order::where('status', 'confirmed'))->count();

        // Returns stats
        $totalReturns = $dateFilter(OrderReturn::query())->count();
        $pendingReturns = $dateFilter(OrderReturn::where('status', 'requested'))->count();

        // Recent orders (filtered)
        $recentOrders = $dateFilter(Order::with(['user', 'items']))
            ->latest()
            ->take(10)
            ->get();

        // Top selling products (from actual paid order data)
        $topProductsQuery = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.payment_status', 'paid')
            ->whereNotIn('orders.status', $excludedStatuses)
            ->select('products.id', 'products.name', 'products.price', DB::raw('SUM(order_items.quantity) as total_sold'));

        if ($hasDateFilter) {
            $topProductsQuery->whereBetween('orders.created_at', [$startDate, $endDate]);
        }

        $topProductIds = $topProductsQuery->groupBy('products.id', 'products.name', 'products.price')
            ->orderByDesc('total_sold')
            ->take(5)
            ->get();

        // Get full product models with images for display
        $topProducts = collect();
        if ($topProductIds->isNotEmpty()) {
            $productModels = Product::with('images')->whereIn('id', $topProductIds->pluck('id'))->get()->keyBy('id');
            foreach ($topProductIds as $tp) {
                $product = $productModels->get($tp->id);
                if ($product) {
                    $product->total_sold = $tp->total_sold;
                    $topProducts->push($product);
                }
            }
        }

        // Sales chart data (paid orders, exclude cancelled/returned)
        if ($hasDateFilter) {
            $daysDiff = $startDate->diffInDays($endDate);
            $salesData = Order::selectRaw(DbCompat::date('created_at') . ' as date, SUM(total) as total, COUNT(*) as count')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where('payment_status', 'paid')
                ->whereNotIn('status', $excludedStatuses)
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            $chartLabels = [];
            $chartRevenue = [];
            $chartOrders = [];

            if ($daysDiff <= 31) {
                // Show daily for ranges up to 31 days
                for ($d = $startDate->copy(); $d->lte($endDate); $d->addDay()) {
                    $dateStr = $d->format('Y-m-d');
                    $chartLabels[] = $d->format($daysDiff <= 7 ? 'D' : 'M d');
                    $dayData = $salesData->firstWhere('date', $dateStr);
                    $chartRevenue[] = $dayData ? round($dayData->total, 2) : 0;
                    $chartOrders[] = $dayData ? $dayData->count : 0;
                }
            } else {
                // Show weekly for longer ranges
                $weeklyData = Order::selectRaw(DbCompat::yearWeek('created_at') . ' as yw, MIN(' . DbCompat::date('created_at') . ') as week_start, SUM(total) as total, COUNT(*) as count')
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->where('payment_status', 'paid')
                    ->whereNotIn('status', $excludedStatuses)
                    ->groupBy('yw')
                    ->orderBy('yw')
                    ->get();
                foreach ($weeklyData as $week) {
                    $chartLabels[] = Carbon::parse($week->week_start)->format('M d');
                    $chartRevenue[] = round($week->total, 2);
                    $chartOrders[] = $week->count;
                }
            }
        } else {
            // Default: last 7 days
            $salesData = Order::selectRaw(DbCompat::date('created_at') . ' as date, SUM(total) as total, COUNT(*) as count')
                ->whereDate('created_at', '>=', now()->subDays(6))
                ->where('payment_status', 'paid')
                ->whereNotIn('status', $excludedStatuses)
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            $chartLabels = [];
            $chartRevenue = [];
            $chartOrders = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i)->format('Y-m-d');
                $chartLabels[] = now()->subDays($i)->format('D');
                $dayData = $salesData->firstWhere('date', $date);
                $chartRevenue[] = $dayData ? round($dayData->total, 2) : 0;
                $chartOrders[] = $dayData ? $dayData->count : 0;
            }
        }

        // Order status distribution (filtered)
        $orderStatusCounts = $dateFilter(Order::selectRaw('status, COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Monthly revenue (last 6 months or within filter range, paid only)
        $monthQuery = Order::selectRaw(DbCompat::month('created_at') . ' as month, ' . DbCompat::year('created_at') . ' as year, SUM(total) as total')
            ->where('payment_status', 'paid')
            ->whereNotIn('status', $excludedStatuses);
        if ($hasDateFilter) {
            $monthQuery->whereBetween('created_at', [$startDate, $endDate]);
        } else {
            $monthQuery->whereDate('created_at', '>=', now()->subMonths(5)->startOfMonth());
        }
        $monthlyRevenue = $monthQuery->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        $monthLabels = [];
        $monthData = [];
        if ($hasDateFilter) {
            $mStart = $startDate->copy()->startOfMonth();
            $mEnd = $endDate->copy()->startOfMonth();
            for ($m = $mStart; $m->lte($mEnd); $m->addMonth()) {
                $monthLabels[] = $m->format('M Y');
                $found = $monthlyRevenue->first(fn($r) => $r->month == $m->month && $r->year == $m->year);
                $monthData[] = $found ? round($found->total, 2) : 0;
            }
        } else {
            for ($i = 5; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $monthLabels[] = $date->format('M');
                $found = $monthlyRevenue->first(fn($r) => $r->month == $date->month && $r->year == $date->year);
                $monthData[] = $found ? round($found->total, 2) : 0;
            }
        }

        // Circle progress metrics (filtered)
        $completedOrders = $dateFilter(Order::where('status', 'delivered'))->count();
        $cancelledOrders = $dateFilter(Order::where('status', 'cancelled'))->count();
        $completionRate = $totalOrders > 0 ? round(($completedOrders / $totalOrders) * 100) : 0;
        $cancellationRate = $totalOrders > 0 ? round(($cancelledOrders / $totalOrders) * 100) : 0;
        $activeProducts = Product::where('is_active', true)->count();
        $productActiveRate = $totalProducts > 0 ? round(($activeProducts / $totalProducts) * 100) : 0;

        // KPI metrics — current month vs last month
        $curMonthStart = now()->startOfMonth();
        $curMonthEnd = now()->endOfMonth();
        $prevMonthStart = now()->subMonth()->startOfMonth();
        $prevMonthEnd = now()->subMonth()->endOfMonth();

        // AOV (Average Order Value)
        $curMonthRevenue = (float) Order::where('payment_status', 'paid')
            ->whereNotIn('status', $excludedStatuses)
            ->whereBetween('created_at', [$curMonthStart, $curMonthEnd])
            ->sum('total');
        $curMonthOrderCount = Order::where('payment_status', 'paid')
            ->whereNotIn('status', $excludedStatuses)
            ->whereBetween('created_at', [$curMonthStart, $curMonthEnd])
            ->count();
        $prevMonthRevenue = (float) Order::where('payment_status', 'paid')
            ->whereNotIn('status', $excludedStatuses)
            ->whereBetween('created_at', [$prevMonthStart, $prevMonthEnd])
            ->sum('total');
        $prevMonthOrderCount = Order::where('payment_status', 'paid')
            ->whereNotIn('status', $excludedStatuses)
            ->whereBetween('created_at', [$prevMonthStart, $prevMonthEnd])
            ->count();

        $aovCurrent = $curMonthOrderCount > 0 ? round($curMonthRevenue / $curMonthOrderCount, 2) : 0;
        $aovPrevious = $prevMonthOrderCount > 0 ? round($prevMonthRevenue / $prevMonthOrderCount, 2) : 0;
        $aovChange = $aovPrevious > 0 ? round((($aovCurrent - $aovPrevious) / $aovPrevious) * 100, 1) : 0;

        // Conversion Rate — orders / total product page views as approximation
        $curMonthViews = (int) DB::table('products')->sum('view_count'); // cumulative, so use orders/views ratio
        $curMonthAllOrders = Order::whereBetween('created_at', [$curMonthStart, $curMonthEnd])->count();
        $prevMonthAllOrders = Order::whereBetween('created_at', [$prevMonthStart, $prevMonthEnd])->count();
        $conversionCurrent = $curMonthViews > 0 ? round(($curMonthAllOrders / $curMonthViews) * 100, 2) : 0;
        // For change, compare order counts month-over-month as proxy
        $conversionChange = $prevMonthAllOrders > 0
            ? round((($curMonthAllOrders - $prevMonthAllOrders) / $prevMonthAllOrders) * 100, 1) : 0;

        // Return Rate
        $curMonthReturns = OrderReturn::whereBetween('created_at', [$curMonthStart, $curMonthEnd])->count();
        $prevMonthReturns = OrderReturn::whereBetween('created_at', [$prevMonthStart, $prevMonthEnd])->count();
        $returnRateCurrent = $curMonthAllOrders > 0 ? round(($curMonthReturns / $curMonthAllOrders) * 100, 1) : 0;
        $returnRatePrevious = $prevMonthAllOrders > 0 ? round(($prevMonthReturns / $prevMonthAllOrders) * 100, 1) : 0;
        $returnRateChange = $returnRatePrevious > 0
            ? round($returnRateCurrent - $returnRatePrevious, 1) : 0;

        // Repeat Customer Rate
        $curMonthRepeat = (int) DB::table('orders')
            ->select('user_id')
            ->whereBetween('created_at', [$curMonthStart, $curMonthEnd])
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) >= 2')
            ->get()
            ->count();
        $curMonthUniqueCustomers = (int) DB::table('orders')
            ->whereBetween('created_at', [$curMonthStart, $curMonthEnd])
            ->whereNotNull('user_id')
            ->distinct('user_id')
            ->count('user_id');
        $prevMonthRepeat = (int) DB::table('orders')
            ->select('user_id')
            ->whereBetween('created_at', [$prevMonthStart, $prevMonthEnd])
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) >= 2')
            ->get()
            ->count();
        $prevMonthUniqueCustomers = (int) DB::table('orders')
            ->whereBetween('created_at', [$prevMonthStart, $prevMonthEnd])
            ->whereNotNull('user_id')
            ->distinct('user_id')
            ->count('user_id');

        $repeatRateCurrent = $curMonthUniqueCustomers > 0
            ? round(($curMonthRepeat / $curMonthUniqueCustomers) * 100, 1) : 0;
        $repeatRatePrevious = $prevMonthUniqueCustomers > 0
            ? round(($prevMonthRepeat / $prevMonthUniqueCustomers) * 100, 1) : 0;
        $repeatRateChange = $repeatRatePrevious > 0
            ? round($repeatRateCurrent - $repeatRatePrevious, 1) : 0;

        // ---------------------------------------------------------------
        // Window-based KPIs (current vs previous period) + sparklines
        // Honours the active date filter; defaults to last 30 days.
        // ---------------------------------------------------------------
        $winEnd = $hasDateFilter ? $endDate->copy() : now()->endOfDay();
        $winStart = $hasDateFilter ? $startDate->copy() : now()->subDays(29)->startOfDay();
        $winDays = max(1, (int) $winStart->diffInDays($winEnd) + 1);
        $prevWinEnd = $winStart->copy()->subSecond();
        $prevWinStart = $prevWinEnd->copy()->subDays($winDays - 1)->startOfDay();

        // Sparkline buckets: cap at 14 to keep the inline SVG light
        $sparkBuckets = min($winDays, 14);
        $bucketSize = (int) ceil($winDays / $sparkBuckets);

        // Pull all paid+valid orders in the union [prevWinStart … winEnd] once
        $orderRows = Order::selectRaw(DbCompat::date('created_at') . ' as d, total, payment_status, status, user_id')
            ->whereBetween('created_at', [$prevWinStart, $winEnd])
            ->get();

        // All orders (any status) — used for conversion proxy + return-rate denominator
        $allOrderRows = Order::selectRaw(DbCompat::date('created_at') . ' as d, user_id')
            ->whereBetween('created_at', [$prevWinStart, $winEnd])
            ->get();

        $returnRows = OrderReturn::selectRaw(DbCompat::date('created_at') . ' as d')
            ->whereBetween('created_at', [$prevWinStart, $winEnd])
            ->get();

        $isPaidValid = function ($row) use ($excludedStatuses) {
            return $row->payment_status === 'paid' && !in_array($row->status, $excludedStatuses, true);
        };

        // Window helpers
        $inWindow = function ($d, $start, $end) {
            return $d >= $start->format('Y-m-d') && $d <= $end->format('Y-m-d');
        };

        // Aggregate window totals
        $winRevenue = $orderRows->filter(fn($r) => $isPaidValid($r) && $inWindow($r->d, $winStart, $winEnd))->sum('total');
        $winPaidOrders = $orderRows->filter(fn($r) => $isPaidValid($r) && $inWindow($r->d, $winStart, $winEnd))->count();
        $winAllOrders = $allOrderRows->filter(fn($r) => $inWindow($r->d, $winStart, $winEnd))->count();
        $winReturns = $returnRows->filter(fn($r) => $inWindow($r->d, $winStart, $winEnd))->count();

        $prevRevenue = $orderRows->filter(fn($r) => $isPaidValid($r) && $inWindow($r->d, $prevWinStart, $prevWinEnd))->sum('total');
        $prevPaidOrders = $orderRows->filter(fn($r) => $isPaidValid($r) && $inWindow($r->d, $prevWinStart, $prevWinEnd))->count();
        $prevAllOrders = $allOrderRows->filter(fn($r) => $inWindow($r->d, $prevWinStart, $prevWinEnd))->count();
        $prevReturns = $returnRows->filter(fn($r) => $inWindow($r->d, $prevWinStart, $prevWinEnd))->count();

        // AOV (window vs previous window)
        $aovWindowCurrent = $winPaidOrders > 0 ? round($winRevenue / $winPaidOrders, 2) : 0;
        $aovWindowPrevious = $prevPaidOrders > 0 ? round($prevRevenue / $prevPaidOrders, 2) : 0;
        $aovWindowDelta = $aovWindowPrevious > 0
            ? round((($aovWindowCurrent - $aovWindowPrevious) / $aovWindowPrevious) * 100, 1) : 0;

        // Conversion proxy (paid orders / all orders) — keeps the existing semantic on the dashboard
        $conversionWindowCurrent = $winAllOrders > 0 ? round(($winPaidOrders / $winAllOrders) * 100, 2) : 0;
        $conversionWindowPrevious = $prevAllOrders > 0 ? round(($prevPaidOrders / $prevAllOrders) * 100, 2) : 0;
        $conversionWindowDelta = $conversionWindowPrevious > 0
            ? round((($conversionWindowCurrent - $conversionWindowPrevious) / $conversionWindowPrevious) * 100, 1) : 0;

        // Return rate
        $returnRateWindowCurrent = $winAllOrders > 0 ? round(($winReturns / $winAllOrders) * 100, 1) : 0;
        $returnRateWindowPrevious = $prevAllOrders > 0 ? round(($prevReturns / $prevAllOrders) * 100, 1) : 0;
        $returnRateWindowDelta = round($returnRateWindowCurrent - $returnRateWindowPrevious, 1);

        // Repeat customer rate (per window, not month) — orders per customer >= 2
        $countRepeatRate = function ($rows, $start, $end) use ($inWindow) {
            $perUser = [];
            foreach ($rows as $r) {
                if (!$r->user_id) continue;
                if (!$inWindow($r->d, $start, $end)) continue;
                $perUser[$r->user_id] = ($perUser[$r->user_id] ?? 0) + 1;
            }
            $unique = count($perUser);
            $repeat = count(array_filter($perUser, fn($n) => $n >= 2));
            return $unique > 0 ? round(($repeat / $unique) * 100, 1) : 0;
        };
        $repeatRateWindowCurrent = $countRepeatRate($allOrderRows, $winStart, $winEnd);
        $repeatRateWindowPrevious = $countRepeatRate($allOrderRows, $prevWinStart, $prevWinEnd);
        $repeatRateWindowDelta = round($repeatRateWindowCurrent - $repeatRateWindowPrevious, 1);

        // Per-bucket sparkline series for each KPI
        $sparkConversion = [];
        $sparkAov = [];
        $sparkReturnRate = [];
        $sparkRepeatRate = [];
        for ($b = 0; $b < $sparkBuckets; $b++) {
            $bucketStart = $winStart->copy()->addDays($b * $bucketSize);
            $bucketEnd = $bucketStart->copy()->addDays($bucketSize - 1)->endOfDay();
            if ($bucketEnd->gt($winEnd)) $bucketEnd = $winEnd->copy();

            $bPaid = $orderRows->filter(fn($r) => $isPaidValid($r) && $inWindow($r->d, $bucketStart, $bucketEnd));
            $bAll = $allOrderRows->filter(fn($r) => $inWindow($r->d, $bucketStart, $bucketEnd));
            $bReturns = $returnRows->filter(fn($r) => $inWindow($r->d, $bucketStart, $bucketEnd));

            $bPaidCount = $bPaid->count();
            $bAllCount = $bAll->count();
            $bRevenue = $bPaid->sum('total');

            $sparkConversion[] = $bAllCount > 0 ? round(($bPaidCount / $bAllCount) * 100, 2) : 0;
            $sparkAov[] = $bPaidCount > 0 ? round($bRevenue / $bPaidCount, 2) : 0;
            $sparkReturnRate[] = $bAllCount > 0 ? round(($bReturns->count() / $bAllCount) * 100, 2) : 0;
            $sparkRepeatRate[] = $countRepeatRate($allOrderRows, $bucketStart, $bucketEnd);
        }

        return view('admin.dashboard.index', compact(
            'topOrders',
            'topRevenue',
            'totalOrders',
            'totalRevenue',
            'totalCustomers',
            'totalProducts',
            'totalSellers',
            'pendingOrders',
            'totalReturns',
            'pendingReturns',
            'recentOrders',
            'topProducts',
            'chartLabels',
            'chartRevenue',
            'chartOrders',
            'orderStatusCounts',
            'monthLabels',
            'monthData',
            'completionRate',
            'cancellationRate',
            'productActiveRate',
            'completedOrders',
            'cancelledOrders',
            'activeProducts',
            'startDate',
            'endDate',
            'hasDateFilter',
            'conversionCurrent',
            'conversionChange',
            'aovCurrent',
            'aovChange',
            'returnRateCurrent',
            'returnRateChange',
            'repeatRateCurrent',
            'repeatRateChange',
            'aovWindowCurrent',
            'aovWindowPrevious',
            'aovWindowDelta',
            'conversionWindowCurrent',
            'conversionWindowPrevious',
            'conversionWindowDelta',
            'returnRateWindowCurrent',
            'returnRateWindowPrevious',
            'returnRateWindowDelta',
            'repeatRateWindowCurrent',
            'repeatRateWindowPrevious',
            'repeatRateWindowDelta',
            'sparkConversion',
            'sparkAov',
            'sparkReturnRate',
            'sparkRepeatRate',
            'sparkBuckets'
        ));
    }
}
