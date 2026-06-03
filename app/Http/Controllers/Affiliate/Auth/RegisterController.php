<?php

namespace App\Http\Controllers\Affiliate\Auth;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function index(): View
    {
        return view('affiliate.auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20|unique:users,phone',
            'password' => 'required|string|min:8|confirmed',
            'channel' => 'required|string|in:youtube,instagram,tiktok,blog,other',
            'channel_url' => 'nullable|url|max:500',
            'bio' => 'nullable|string|max:1000',
            'terms' => 'required|accepted',
        ]);

        DB::transaction(function () use ($validated) {
            $user = User::create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make($validated['password']),
                'role' => 'affiliate',
            ]);

            Affiliate::create([
                'user_id' => $user->id,
                'referral_code' => Affiliate::generateReferralCode(),
                'channel' => $validated['channel'],
                'channel_url' => $validated['channel_url'] ?? null,
                'bio' => $validated['bio'] ?? null,
                'commission_rate' => config('affiliate.default_commission_rate', 5.00),
                'status' => 'pending',
            ]);
        });

        return redirect()->route('affiliate.register')
            ->with('success', 'Your affiliate application has been submitted! We will review and get back to you shortly.');
    }
}
