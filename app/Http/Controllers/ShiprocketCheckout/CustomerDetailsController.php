<?php

namespace App\Http\Controllers\ShiprocketCheckout;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Saves customer details captured from the Shiprocket checkout success page.
 *
 * After Shiprocket redirects back to our success page, the page reads
 * sessionStorage['sr_checkout_customer'] (populated via postMessage from the SDK
 * iframe during checkout) and POSTs whatever it captured here.
 *
 * This fills in guest_name / guest_phone / guest_email / shipping_address_snapshot
 * on the order created by CallbackController, and auto-links to an existing user
 * account if one exists with the same phone or email.
 *
 * Route: POST /checkout/shiprocket-details
 */
class CustomerDetailsController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => 'required|integer',
            'name'     => 'required|string|max:100',
            'phone'    => 'required|string|max:20',
            'email'    => 'nullable|email|max:255',
            'address'  => 'nullable|string|max:500',
            'city'     => 'nullable|string|max:100',
            'state'    => 'nullable|string|max:100',
            'pincode'  => 'nullable|string|max:10',
        ]);

        $order = Order::find($validated['order_id']);
        if (!$order || $order->source !== 'api') {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        // Authorisation: only the session that placed the order within the last hour
        if ($order->created_at->diffInMinutes(now()) >= 60) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $updates = [];

        if (empty($order->guest_name))  $updates['guest_name']  = $validated['name'];
        if (empty($order->guest_phone)) $updates['guest_phone'] = $validated['phone'];
        if (empty($order->guest_email) && !empty($validated['email'])) {
            $updates['guest_email'] = $validated['email'];
        }

        if (empty($order->shipping_address_snapshot) && !empty($validated['address'])) {
            $updates['shipping_address_snapshot'] = [
                'name'           => $validated['name'],
                'phone'          => $validated['phone'],
                'address_line_1' => $validated['address'],
                'address_line_2' => '',
                'city'           => $validated['city']    ?? '',
                'state'          => $validated['state']   ?? '',
                'postal_code'    => $validated['pincode'] ?? '',
                'country'        => 'India',
            ];
        }

        // Auto-link to existing user account
        if (empty($order->user_id)) {
            $user = $this->findUserByPhoneOrEmail($validated['phone'], $validated['email'] ?? null);
            if ($user) {
                $updates['user_id'] = $user->id;
            }
        }

        if (!empty($updates)) {
            $order->update($updates);
        }

        return response()->json(['success' => true]);
    }

    private function findUserByPhoneOrEmail(?string $phone, ?string $email): ?\App\Models\User
    {
        if (empty($phone) && empty($email)) return null;

        $cleanPhone = $phone ? preg_replace('/\D/', '', $phone) : null;
        $shortPhone = $cleanPhone && strlen($cleanPhone) > 10 ? substr($cleanPhone, -10) : $cleanPhone;

        return \App\Models\User::where(function ($q) use ($email, $cleanPhone, $shortPhone) {
            if ($email)      $q->orWhere('email', $email);
            if ($cleanPhone) $q->orWhere('phone', $cleanPhone);
            if ($shortPhone && $shortPhone !== $cleanPhone) $q->orWhere('phone', $shortPhone);
        })->first();
    }
}
