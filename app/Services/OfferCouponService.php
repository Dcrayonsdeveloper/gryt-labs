<?php

namespace App\Services;

use App\Models\OfferLead;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

/**
 * Delivers the offer-popup coupon code over WhatsApp.
 *
 * The popup, the admin resend button and the hourly retry command all go
 * through here, so the code a shopper is given and the record of whether they
 * actually got it cannot drift between the three.
 */
class OfferCouponService
{
    /**
     * Products the welcome code is valid on, named in the WhatsApp message.
     *
     * Shortened from the catalog titles on purpose. These land mid-sentence in
     * "your code X is valid on {{3}}", so the full SEO names ("... for Gym and
     * Fitness Workouts" seven times over) would run past 380 characters and read
     * like a sitemap. The admin override for this is capped at 255 anyway.
     */
    public const DEFAULT_PRODUCTS = 'Vanilla Whey Protein, Creatine Monohydrate, Pre Workout, '
        . 'and our Steel, Storm, Blue and Red shaker bottles';

    public function __construct(private WhatsAppService $whatsapp) {}

    /** Why the last send failed, for the admin flash message. */
    public function lastError(): ?string
    {
        return $this->whatsapp->lastError();
    }

    /**
     * Send the code and record the outcome on the lead.
     */
    public function send(OfferLead $lead): bool
    {
        $code = $this->codeFor($lead);
        $sent = false;

        try {
            $sent = $this->whatsapp->sendOfferCoupon(
                $lead->msisdn(),
                $lead->name ?: 'there',
                $code,
                url('/products'),
                $this->products(),
            );
        } catch (\Throwable $e) {
            Log::warning('Offer coupon send failed', [
                'phone' => $lead->phone,
                'error' => $e->getMessage(),
            ]);
        }

        $lead->forceFill([
            'coupon_code'     => $code,
            'coupon_sent_at'  => now(),
            'coupon_channel'  => 'whatsapp',
            'whatsapp_status' => $sent ? 'sent' : 'failed',
        ])->save();

        return $sent;
    }

    /**
     * A lead that was stamped with a code it never actually received is sent the
     * current one instead - otherwise a backlog captured before the code changed
     * would be posted a string that no longer exists as a discount. A lead whose
     * send succeeded keeps the code it was already given.
     */
    public function codeFor(OfferLead $lead): string
    {
        return $lead->whatsapp_status === 'sent' && $lead->coupon_code
            ? $lead->coupon_code
            : $this->couponCode();
    }

    public function couponCode(): string
    {
        return strtoupper(trim((string) Setting::get('offer_popup_coupon_code', 'GRYTWELCOME'))) ?: 'GRYTWELCOME';
    }

    public function products(): string
    {
        return (string) Setting::get('offer_popup_products', '') ?: self::DEFAULT_PRODUCTS;
    }
}
