<?php

namespace App\Http\Controllers;

use App\Models\OfferLead;
use App\Models\Setting;
use App\Models\User;
use App\Services\OtpService;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Storefront "exclusive offer" popup: capture a mobile number, prove it with a
 * WhatsApp OTP, then hand over the VIP coupon code.
 *
 * The OTP purpose is deliberately its own bucket ('offer_popup') so a shopper
 * claiming the offer never eats into the sign-in OTP allowance, and vice versa.
 */
class OfferPopupController extends Controller
{
    private const OTP_PURPOSE = 'offer_popup';

    public function __construct(
        private OtpService $otpService,
        private WhatsAppService $whatsapp,
    ) {}

    /**
     * Step 1 - validate the number + consent, then send the WhatsApp OTP.
     */
    public function sendOtp(Request $request): JsonResponse
    {
        if (!$this->popupEnabled()) {
            return response()->json(['success' => false, 'message' => 'This offer is no longer available.'], 404);
        }

        $request->validate([
            'phone'   => 'required|string|max:20',
            'name'    => 'nullable|string|max:60',
            'consent' => 'accepted',
        ], [
            'consent.accepted' => 'Please accept the WhatsApp updates checkbox to continue.',
        ]);

        $phone = OfferLead::normalizePhone($request->input('phone'));

        if (!preg_match('/^[6-9]\d{9}$/', $phone)) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter a valid 10-digit mobile number.',
            ], 422);
        }

        // One code per number. Someone who already claimed is turned away here,
        // at the number step, rather than being walked through an OTP that would
        // only end in "you already have this" - and their original row is left
        // untouched, so a repeat visit cannot overwrite the consent we recorded.
        $existing = OfferLead::where('phone', $phone)->first();

        if ($existing && $existing->isVerified()) {
            return response()->json([
                'success'  => true,
                'status'   => 'already_registered',
                'heading'  => $this->returningHeading(),
                'message'  => $this->returningText(),
                'shop_url' => $this->shopUrl(),
            ]);
        }

        // Record the attempt before the OTP goes out. A shopper who types their
        // number and then drops off at the OTP step is still a lead the store
        // wants in the list - the admin page separates verified from pending.
        $lead = OfferLead::firstOrNew(['phone' => $phone]);
        $lead->fill([
            'name'           => $request->input('name') ?: $lead->name,
            'country_code'   => '91',
            'consent'        => true,
            'consent_text'   => $this->consentText(),
            'consent_at'     => $lead->consent_at ?: now(),
            'source'         => substr((string) ($request->input('source') ?: 'offer_popup'), 0, 50),
            'page_url'       => substr((string) $request->input('page_url', (string) $request->headers->get('referer', '')), 0, 255),
            'ip_address'     => $request->ip(),
            'user_agent'     => substr((string) $request->userAgent(), 0, 255),
            'user_id'        => $lead->user_id ?: User::where('phone', $phone)->value('id'),
            'otp_sent_at'    => now(),
            'otp_send_count' => (int) $lead->otp_send_count + 1,
        ]);
        $lead->save();

        if (!$this->whatsapp->isConfigured()) {
            Log::warning('Offer popup: WhatsApp is not configured, OTP cannot be delivered.');

            return response()->json([
                'success' => false,
                'message' => 'We could not send the code right now. Please try again later.',
            ], 503);
        }

        // WhatsApp only - the number is the whole point of the capture, and the
        // placeholder @phone.* addresses created for phone-login users are not
        // real inboxes, so emailing the code would bounce.
        $result = $this->otpService->send($phone, self::OTP_PURPOSE, viaWhatsApp: true, viaEmail: false);

        if (!($result['success'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Could not send the code. Please try again.',
            ], 429);
        }

        return response()->json([
            'success'    => true,
            'message'    => 'We sent a 6-digit code to your WhatsApp.',
            'expires_in' => $result['expires_in'] ?? 10,
        ]);
    }

    /**
     * Step 2 - verify the OTP, then reveal (and WhatsApp) the coupon.
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        if (!$this->popupEnabled()) {
            return response()->json(['success' => false, 'message' => 'This offer is no longer available.'], 404);
        }

        $request->validate([
            'phone' => 'required|string|max:20',
            'otp'   => 'required|string|size:6',
            'name'  => 'nullable|string|max:60',
        ]);

        $phone = OfferLead::normalizePhone($request->input('phone'));

        if (!$this->otpService->verify($phone, $request->input('otp'), self::OTP_PURPOSE)) {
            return response()->json([
                'success' => false,
                'message' => 'That code is invalid or has expired. Please try again.',
            ], 422);
        }

        $lead = OfferLead::firstOrNew(['phone' => $phone]);

        // Someone claiming a second time keeps their original row, original
        // verified date and original code - the unique index on phone means
        // there is exactly one lead per number however many times they return.
        $returning = $lead->exists && $lead->isVerified();
        $code      = $returning && $lead->coupon_code ? $lead->coupon_code : $this->couponCode();

        $lead->fill([
            'name'         => $request->input('name') ?: $lead->name,
            'country_code' => '91',
            'consent'      => true,
            'consent_text' => $lead->consent_text ?: $this->consentText(),
            'consent_at'   => $lead->consent_at ?: now(),
            'verified_at'  => $lead->verified_at ?: now(),
            'coupon_code'  => $code,
            'source'       => $lead->source ?: 'offer_popup',
            'user_id'      => $lead->user_id ?: User::where('phone', $phone)->value('id'),
            'ip_address'   => $lead->ip_address ?: $request->ip(),
        ]);
        $lead->save();

        $delivered = $this->deliverCoupon($lead, $code);

        // The code itself is never returned to the browser. WhatsApp is the only
        // place it appears, which is what makes a working number worth giving.
        return response()->json([
            'success'    => true,
            'returning'  => $returning,
            'delivered'  => $delivered,
            'offer_text' => $this->offerText(),
            'shop_url'   => $this->shopUrl(),
            'heading'    => $returning ? 'You are already on the VIP list' : 'You are on the VIP list!',
            'message'    => $returning
                ? 'We have sent your code to WhatsApp again.'
                : 'We have sent your exclusive code to your WhatsApp.',
        ]);
    }

    /**
     * Send the code over WhatsApp - the only channel it goes out on - recording
     * the outcome so the admin list can show who actually received it and offer
     * a resend for the ones that did not.
     */
    private function deliverCoupon(OfferLead $lead, string $code): bool
    {
        $sent = false;

        try {
            $sent = $this->whatsapp->sendOfferCoupon(
                $lead->msisdn(),
                $lead->name ?: 'there',
                $code,
                $this->shopUrl(),
                $this->offerText(),
            );
        } catch (\Throwable $e) {
            Log::warning('Offer popup: coupon WhatsApp send failed', [
                'phone' => $lead->phone,
                'error' => $e->getMessage(),
            ]);
        }

        $lead->forceFill([
            'coupon_sent_at'  => now(),
            'coupon_channel'  => 'whatsapp',
            'whatsapp_status' => $sent ? 'sent' : 'failed',
        ])->save();

        return $sent;
    }

    private function popupEnabled(): bool
    {
        return filter_var(Setting::get('offer_popup_enabled', '1'), FILTER_VALIDATE_BOOLEAN);
    }

    private function couponCode(): string
    {
        return strtoupper(trim((string) Setting::get('offer_popup_coupon_code', 'GRYTVIP'))) ?: 'GRYTVIP';
    }

    private function offerText(): string
    {
        return (string) Setting::get('offer_popup_offer_text', '') ?: 'your exclusive VIP discount';
    }

    private function returningHeading(): string
    {
        return (string) Setting::get('offer_popup_returning_heading', '') ?: 'You are already a VIP!';
    }

    private function returningText(): string
    {
        return (string) Setting::get('offer_popup_returning_text', '')
            ?: 'This number is already on our VIP list, so there is nothing more to do. '
                . 'Your code is sitting in your WhatsApp chat - just apply it at checkout.';
    }

    private function consentText(): string
    {
        return (string) Setting::get('offer_popup_consent_text', '')
            ?: 'I agree to receive offers and updates from '
                . (Setting::get('site_name', '') ?: Setting::get('store_name', '') ?: config('app.name'))
                . ' on WhatsApp.';
    }

    private function shopUrl(): string
    {
        return url('/products');
    }
}
