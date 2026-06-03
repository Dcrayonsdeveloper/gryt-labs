<?php

namespace App\Http\Controllers;

use App\Models\GiftCard;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GiftCardController extends Controller
{
    /**
     * Show the gift card balance check page.
     */
    public function balanceCheck(): View
    {
        return view('gift-cards.balance');
    }

    /**
     * Look up a gift card balance.
     */
    public function checkBalance(Request $request): View
    {
        $request->validate([
            'code' => 'required|string|max:20',
        ]);

        $code = strtoupper(trim($request->input('code')));
        $giftCard = GiftCard::where('code', $code)->first();

        return view('gift-cards.balance', [
            'code' => $code,
            'giftCard' => $giftCard,
            'searched' => true,
        ]);
    }
}
