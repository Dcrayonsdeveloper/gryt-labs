<?php

namespace App\Http\Controllers\Influencer;

use App\Http\Controllers\Controller;
use App\Services\InfluencerAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $influencer = Auth::guard('influencer')->user();
        abort_unless($influencer, 403);

        [$start, $end] = InfluencerAnalyticsService::dateRange(
            $request->query('range'), $request->query('from'), $request->query('to')
        );

        // Scoped strictly to THIS influencer's coupon — never an id from the URL.
        $scoped = $influencer->ordersQuery();

        $analytics = InfluencerAnalyticsService::compute($scoped, $start, $end, (float) ($influencer->commission_percentage ?? 0));

        $orders = (clone $scoped)
            ->whereBetween('created_at', [$start, $end])
            ->with('user')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('influencer.dashboard.index', [
            'influencer' => $influencer,
            'a'          => $analytics,
            'orders'     => $orders,
            'range'      => $request->query('range', '30days'),
            'from'       => $request->query('from'),
            'to'         => $request->query('to'),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $influencer = Auth::guard('influencer')->user();
        abort_unless($influencer, 403);

        [$start, $end] = InfluencerAnalyticsService::dateRange(
            $request->query('range'), $request->query('from'), $request->query('to')
        );

        $query = $influencer->ordersQuery()
            ->whereBetween('created_at', [$start, $end])
            ->with('user')
            ->latest();

        $filename = 'influencer_orders_' . now()->format('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($query, $influencer) {
            $h = fopen('php://output', 'w');
            fputcsv($h, ['Order ID', 'Customer', 'Phone', 'Date', 'Coupon', 'Order Amount', 'Discount', 'Final Amount', 'Order Status', 'Payment Status']);
            $query->chunk(500, function ($orders) use ($h, $influencer) {
                foreach ($orders as $o) {
                    fputcsv($h, [
                        $o->order_number,
                        $o->user?->full_name ?: $o->guest_name,
                        $o->guest_phone ?: $o->user?->phone,
                        $o->created_at?->format('Y-m-d H:i'),
                        $influencer->coupon_code,
                        number_format((float) $o->subtotal, 2, '.', ''),
                        number_format((float) $o->discount, 2, '.', ''),
                        number_format((float) $o->total, 2, '.', ''),
                        ucfirst(str_replace('_', ' ', $o->status)),
                        ucfirst(str_replace('_', ' ', $o->payment_status)),
                    ]);
                }
            });
            fclose($h);
        }, $filename);
    }
}
