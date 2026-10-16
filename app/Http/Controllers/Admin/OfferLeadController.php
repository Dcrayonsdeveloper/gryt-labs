<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\OfferLead;
use App\Models\Setting;
use App\Services\WhatsAppService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Phone numbers captured by the storefront offer popup, plus the settings that
 * drive the popup itself (kept on the same screen so the copy, the code and the
 * numbers it collected are all in one place).
 */
class OfferLeadController extends Controller
{
    /** Settings the popup reads, with the defaults used when a row is absent. */
    private const SETTING_DEFAULTS = [
        'offer_popup_enabled'       => '1',
        'offer_popup_delay'         => '10',
        'offer_popup_title'         => 'Unlock your exclusive offer',
        'offer_popup_subtitle'      => 'Drop your WhatsApp number and we will send you a VIP code you can use right away.',
        'offer_popup_button_text'   => 'Get my code on WhatsApp',
        'offer_popup_consent_text'  => '',
        'offer_popup_footer_text'   => 'One code per number. You can opt out any time by replying STOP.',
        'offer_popup_coupon_code'   => 'GRYTVIP',
        'offer_popup_offer_text'    => 'your exclusive VIP discount',
        'whatsapp_offer_template'   => '',
    ];

    public function index(Request $request): View
    {
        $query = OfferLead::query();

        if ($search = trim((string) $request->input('search'))) {
            $digits = preg_replace('/\D/', '', $search);

            $query->where(function ($q) use ($search, $digits) {
                $q->where('name', 'like', "%{$search}%");
                if ($digits !== '') {
                    $q->orWhere('phone', 'like', "%{$digits}%");
                }
            });
        }

        $status = $request->input('status', 'all');
        if ($status === 'verified') {
            $query->verified();
        } elseif ($status === 'pending') {
            $query->pending();
        }

        if ($from = $request->input('from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->input('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $leads = $query->with('user')->latest()->paginate(25)->withQueryString();

        $couponCode = $this->couponCode();

        $stats = [
            'total'     => OfferLead::count(),
            'verified'  => OfferLead::verified()->count(),
            'pending'   => OfferLead::pending()->count(),
            'last_7'    => OfferLead::verified()->where('verified_at', '>=', now()->subDays(7))->count(),
            'delivered' => OfferLead::where('whatsapp_status', 'sent')->count(),
        ];

        return view('admin.offer-leads.index', [
            'leads'        => $leads,
            'stats'        => $stats,
            'settings'     => $this->currentSettings(),
            'couponCode'   => $couponCode,
            'couponExists' => Coupon::where('code', $couponCode)->exists(),
            'whatsappOk'   => app(WhatsAppService::class)->isConfigured(),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'offer_popup_title'        => 'nullable|string|max:120',
            'offer_popup_subtitle'     => 'nullable|string|max:255',
            'offer_popup_button_text'  => 'nullable|string|max:60',
            'offer_popup_consent_text' => 'nullable|string|max:255',
            'offer_popup_footer_text'  => 'nullable|string|max:255',
            'offer_popup_coupon_code'  => 'nullable|string|max:50',
            'offer_popup_offer_text'   => 'nullable|string|max:120',
            'whatsapp_offer_template'  => 'nullable|string|max:100',
            'offer_popup_delay'        => 'required|integer|min:0|max:300',
        ]);

        Setting::set('offer_popup_enabled', $request->boolean('offer_popup_enabled') ? '1' : '0', 'boolean', 'offer_popup');
        Setting::set('offer_popup_delay', (string) $validated['offer_popup_delay'], 'integer', 'offer_popup');

        foreach (['offer_popup_title', 'offer_popup_subtitle', 'offer_popup_button_text',
                  'offer_popup_consent_text', 'offer_popup_footer_text', 'offer_popup_offer_text',
                  'whatsapp_offer_template'] as $key) {
            Setting::set($key, (string) ($validated[$key] ?? ''), 'string', 'offer_popup');
        }

        // The code is what shoppers type at checkout - store it the way the
        // coupon table does, so a lowercase entry here still matches.
        Setting::set(
            'offer_popup_coupon_code',
            strtoupper(trim((string) ($validated['offer_popup_coupon_code'] ?? ''))) ?: 'GRYTVIP',
            'string',
            'offer_popup',
        );

        return back()->with('success', 'Popup settings saved.');
    }

    /**
     * Re-send the coupon to a lead that already verified.
     */
    public function resend(OfferLead $offerLead): RedirectResponse
    {
        if (!$offerLead->isVerified()) {
            return back()->with('error', 'This number has not verified its OTP yet.');
        }

        $code = $offerLead->coupon_code ?: $this->couponCode();
        $sent = false;

        try {
            $sent = app(WhatsAppService::class)->sendOfferCoupon(
                $offerLead->msisdn(),
                $offerLead->name ?: 'there',
                $code,
                url('/products'),
                Setting::get('offer_popup_offer_text', '') ?: 'your exclusive VIP discount',
            );
        } catch (\Throwable $e) {
            Log::warning('Offer lead resend failed', ['phone' => $offerLead->phone, 'error' => $e->getMessage()]);
        }

        $offerLead->forceFill([
            'coupon_code'     => $code,
            'coupon_sent_at'  => now(),
            'coupon_channel'  => $sent ? 'whatsapp' : 'onscreen',
            'whatsapp_status' => $sent ? 'sent' : 'failed',
        ])->save();

        return back()->with(
            $sent ? 'success' : 'error',
            $sent
                ? "Code {$code} sent to +91 {$offerLead->phone} on WhatsApp."
                : 'WhatsApp send failed. Check the WhatsApp template name and API settings.',
        );
    }

    public function destroy(OfferLead $offerLead): RedirectResponse
    {
        $offerLead->delete();

        return back()->with('success', 'Lead removed.');
    }

    public function bulkAction(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'action' => 'required|in:delete',
            'ids'    => 'required|array',
            'ids.*'  => 'integer',
        ]);

        $count = OfferLead::whereIn('id', $validated['ids'])->delete();

        return back()->with('success', "{$count} lead(s) deleted.");
    }

    public function export(Request $request): Response
    {
        $query = OfferLead::query();

        if ($status = $request->input('status')) {
            if ($status === 'verified') {
                $query->verified();
            } elseif ($status === 'pending') {
                $query->pending();
            }
        }
        if ($from = $request->input('from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->input('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $rows = $query->latest()->get();

        $csv = "Phone,Name,Status,Consent,Coupon,WhatsApp,Captured At,Verified At\n";
        foreach ($rows as $lead) {
            $csv .= implode(',', [
                '"+' . $lead->country_code . $lead->phone . '"',
                '"' . str_replace('"', '""', $this->csvSafe((string) $lead->name)) . '"',
                $lead->isVerified() ? 'Verified' : 'Pending',
                $lead->consent ? 'Yes' : 'No',
                $lead->coupon_code ?? '',
                $lead->whatsapp_status ?? '',
                $lead->created_at?->format('Y-m-d H:i'),
                $lead->verified_at?->format('Y-m-d H:i') ?? '',
            ]) . "\n";
        }

        $filename = 'offer-popup-leads-' . now()->format('Y-m-d') . '.csv';

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Names are shopper-supplied, and Excel executes a cell that opens with
     * = + - @ as a formula. A leading apostrophe keeps it as text.
     */
    private function csvSafe(string $value): string
    {
        return $value !== '' && str_contains("=+-@", $value[0]) ? "'" . $value : $value;
    }

    private function currentSettings(): array
    {
        $values = [];
        foreach (self::SETTING_DEFAULTS as $key => $default) {
            $values[$key] = (string) Setting::get($key, $default);
        }

        // Shown as the placeholder in the consent field when the store has not
        // written its own wording yet.
        $brand = Setting::get('site_name', '') ?: (Setting::get('store_name', '') ?: config('app.name'));
        $values['consent_placeholder'] = 'I agree to receive offers and updates from ' . $brand . ' on WhatsApp.';
        $values['template_placeholder'] = (config('app.tenant_id', '') ?: 'store') . '_vip_offer';

        return $values;
    }

    private function couponCode(): string
    {
        return strtoupper(trim((string) Setting::get('offer_popup_coupon_code', 'GRYTVIP'))) ?: 'GRYTVIP';
    }
}
