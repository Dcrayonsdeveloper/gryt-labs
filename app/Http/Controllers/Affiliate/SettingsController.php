<?php

namespace App\Http\Controllers\Affiliate;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(): View
    {
        $affiliate = Auth::user()->affiliate;
        abort_unless($affiliate, 403);

        return view('affiliate.settings.index', [
            'title' => 'Settings',
            'affiliate' => $affiliate,
            'user' => Auth::user(),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $affiliate = Auth::user()->affiliate;
        abort_unless($affiliate, 403);

        $validated = $request->validate([
            'channel' => 'required|string|in:youtube,instagram,tiktok,blog,other',
            'channel_url' => 'nullable|url|max:500',
            'bio' => 'nullable|string|max:1000',
        ]);

        $affiliate->update($validated);

        return back()->with('success', 'Profile updated successfully.');
    }

    public function updatePayout(Request $request): RedirectResponse
    {
        $affiliate = Auth::user()->affiliate;
        abort_unless($affiliate, 403);

        $validated = $request->validate([
            'payout_method' => 'required|string|in:bank_transfer,upi',
            'bank_name' => 'required_if:payout_method,bank_transfer|nullable|string|max:255',
            'bank_account' => 'required_if:payout_method,bank_transfer|nullable|string|max:50',
            'bank_ifsc' => 'required_if:payout_method,bank_transfer|nullable|string|max:20',
            'bank_holder_name' => 'required_if:payout_method,bank_transfer|nullable|string|max:255',
            'upi_id' => 'required_if:payout_method,upi|nullable|string|max:100',
        ]);

        $data = ['payout_method' => $validated['payout_method']];

        if ($validated['payout_method'] === 'bank_transfer') {
            $data['bank_details'] = [
                'bank_name' => $validated['bank_name'],
                'account_number' => $validated['bank_account'],
                'ifsc_code' => $validated['bank_ifsc'],
                'holder_name' => $validated['bank_holder_name'],
            ];
        } else {
            $data['upi_id'] = $validated['upi_id'];
        }

        $affiliate->update($data);

        return back()->with('success', 'Payout settings updated successfully.');
    }
}
