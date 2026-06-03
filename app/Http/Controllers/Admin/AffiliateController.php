<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\AffiliateRedemption;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AffiliateController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->get('status');

        $affiliates = Affiliate::with('user')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(20);

        $stats = [
            'total' => Affiliate::count(),
            'pending' => Affiliate::where('status', 'pending')->count(),
            'approved' => Affiliate::where('status', 'approved')->count(),
            'suspended' => Affiliate::where('status', 'suspended')->count(),
        ];

        return view('admin.affiliates.index', [
            'title' => 'Affiliates',
            'affiliates' => $affiliates,
            'stats' => $stats,
            'currentStatus' => $status,
        ]);
    }

    public function show(Affiliate $affiliate): View
    {
        $affiliate->load(['user', 'commissions' => fn ($q) => $q->latest()->take(20), 'commissions.order']);

        $commissionStats = [
            'total' => $affiliate->commissions()->sum('commission_amount'),
            'pending' => $affiliate->commissions()->where('status', 'pending')->sum('commission_amount'),
            'approved' => $affiliate->commissions()->where('status', 'approved')->sum('commission_amount'),
            'paid' => $affiliate->commissions()->where('status', 'paid')->sum('commission_amount'),
        ];

        return view('admin.affiliates.show', [
            'title' => $affiliate->user->full_name,
            'affiliate' => $affiliate,
            'commissionStats' => $commissionStats,
        ]);
    }

    public function approve(Affiliate $affiliate): RedirectResponse
    {
        $affiliate->update([
            'status' => 'approved',
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        return back()->with('success', "Affiliate {$affiliate->user->full_name} has been approved.");
    }

    public function reject(Request $request, Affiliate $affiliate): RedirectResponse
    {
        $request->validate(['reason' => 'nullable|string|max:500']);

        $affiliate->update([
            'status' => 'rejected',
            'rejection_reason' => $request->reason,
        ]);

        return back()->with('success', "Affiliate {$affiliate->user->full_name} has been rejected.");
    }

    public function suspend(Request $request, Affiliate $affiliate): RedirectResponse
    {
        $request->validate(['reason' => 'nullable|string|max:500']);

        $affiliate->update([
            'status' => 'suspended',
            'suspension_reason' => $request->reason,
            'suspended_at' => now(),
        ]);

        return back()->with('success', "Affiliate {$affiliate->user->full_name} has been suspended.");
    }

    public function redemptions(Request $request): View
    {
        $status = $request->get('status');

        $redemptions = AffiliateRedemption::with(['affiliate.user'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(20);

        $stats = [
            'pending' => AffiliateRedemption::where('status', 'pending')->sum('amount'),
            'processing' => AffiliateRedemption::where('status', 'processing')->sum('amount'),
            'completed' => AffiliateRedemption::where('status', 'completed')->sum('amount'),
        ];

        return view('admin.affiliates.redemptions', [
            'title' => 'Affiliate Redemptions',
            'redemptions' => $redemptions,
            'stats' => $stats,
            'currentStatus' => $status,
        ]);
    }

    public function processRedemption(AffiliateRedemption $redemption): RedirectResponse
    {
        if (!$redemption->isPending()) {
            return back()->with('error', 'This redemption is not in pending status.');
        }

        $redemption->markAsProcessing();

        return back()->with('success', "Redemption #{$redemption->id} marked as processing.");
    }

    public function completeRedemption(Request $request, AffiliateRedemption $redemption): RedirectResponse
    {
        if (!$redemption->isProcessing()) {
            return back()->with('error', 'This redemption is not in processing status.');
        }

        $request->validate(['transaction_id' => 'nullable|string|max:255']);

        $redemption->markAsCompleted($request->transaction_id);

        return back()->with('success', "Redemption #{$redemption->id} completed.");
    }

    public function failRedemption(Request $request, AffiliateRedemption $redemption): RedirectResponse
    {
        $request->validate(['reason' => 'required|string|max:500']);

        $redemption->markAsFailed($request->reason);

        return back()->with('success', "Redemption #{$redemption->id} marked as failed. Amount refunded to affiliate.");
    }
}
