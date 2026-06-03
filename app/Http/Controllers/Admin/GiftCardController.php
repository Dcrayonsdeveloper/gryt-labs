<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GiftCard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class GiftCardController extends Controller
{
    public function index(Request $request): View
    {
        $query = GiftCard::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('recipient_email', 'like', "%{$search}%")
                  ->orWhere('recipient_name', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $giftCards = $query->latest()->paginate(15)->withQueryString();

        $stats = [
            'total' => GiftCard::count(),
            'active' => GiftCard::where('status', 'active')->count(),
            'total_value' => GiftCard::where('status', 'active')->sum('current_balance'),
            'depleted' => GiftCard::where('status', 'depleted')->count(),
        ];

        return view('admin.gift-cards.index', compact('giftCards', 'stats'));
    }

    public function create(): View
    {
        return view('admin.gift-cards.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'initial_balance' => 'required|numeric|min:1|max:100000',
            'recipient_email' => 'required|email|max:255',
            'recipient_name' => 'required|string|max:255',
            'purchaser_email' => 'nullable|email|max:255',
            'message' => 'nullable|string|max:500',
            'expires_at' => 'nullable|date|after:today',
        ]);

        $giftCard = GiftCard::create($validated);

        // Send email notification to recipient
        try {
            Mail::send('emails.gift-card', [
                'giftCard' => $giftCard,
            ], function ($mail) use ($giftCard) {
                $mail->to($giftCard->recipient_email, $giftCard->recipient_name)
                     ->subject('You received a gift card!');
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Gift card email failed: ' . $e->getMessage());
        }

        return redirect()->route('admin.gift-cards.index')
            ->with('success', 'Gift card created successfully. Code: ' . $giftCard->code);
    }

    public function show(GiftCard $giftCard): View
    {
        $giftCard->load('usages.order');

        return view('admin.gift-cards.show', compact('giftCard'));
    }

    public function destroy(GiftCard $giftCard): RedirectResponse
    {
        $giftCard->delete();

        return redirect()->route('admin.gift-cards.index')
            ->with('success', 'Gift card deleted successfully.');
    }
}
