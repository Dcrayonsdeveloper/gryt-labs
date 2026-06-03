<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin editor for the three abandoned-cart email templates (1h / 24h / 72h).
 *
 * Storage: key-based Settings (no migration needed). Each reminder template has
 * five editable fields stored under the prefix abandoned_cart_r{N}_*:
 *   - abandoned_cart_r{N}_subject    (email subject line)
 *   - abandoned_cart_r{N}_preheader  (optional preview text)
 *   - abandoned_cart_r{N}_heading    (H1 inside the email)
 *   - abandoned_cart_r{N}_body       (markdown / paragraphs)
 *   - abandoned_cart_r{N}_cta        (CTA button label)
 *
 * Defaults live in the Blade views (emails.abandoned-cart-reminder-*), so an
 * empty Setting value falls back to the original hardcoded copy.
 */
class AbandonedCartTemplateController extends Controller
{
    /**
     * Shape definition for all three reminders. Also used to provide defaults
     * when a Setting row has not been saved yet.
     */
    private function reminders(): array
    {
        return [
            'r1' => [
                'label' => 'Reminder 1 — 1 hour (no discount)',
                'defaults' => [
                    'subject'   => 'You left something behind!',
                    'preheader' => 'Your cart is waiting for you',
                    'heading'   => 'You left something behind!',
                    'body'      => "Hi {name},\n\nWe noticed you didn't finish checking out. No worries — your items are still saved and ready for you!",
                    'cta'       => 'Complete Your Order',
                ],
            ],
            'r2' => [
                'label' => 'Reminder 2 — 24 hours (5% off)',
                'defaults' => [
                    'subject'   => "Still thinking? Here's 5% off to help you decide",
                    'preheader' => 'We saved your cart — and added a treat',
                    'heading'   => 'Still thinking about it?',
                    'body'      => "Hi {name},\n\nYour items are still in your cart. To make it easier, here's a special discount just for you.",
                    'cta'       => 'Claim {discount}% Off Now',
                ],
            ],
            'r3' => [
                'label' => 'Reminder 3 — 72 hours (10% off, final)',
                'defaults' => [
                    'subject'   => 'Last chance! 10% off your cart — expiring soon',
                    'preheader' => 'This is our biggest offer for you',
                    'heading'   => 'Last chance — your cart is expiring!',
                    'body'      => "Hi {name},\n\nThis is your final reminder. Your cart items may not be available much longer. We're giving you our best discount to help you decide.",
                    'cta'       => 'Get {discount}% Off — Last Chance!',
                ],
            ],
        ];
    }

    public function index(): View
    {
        $reminders = $this->reminders();
        $values = [];

        foreach ($reminders as $slot => $cfg) {
            foreach ($cfg['defaults'] as $field => $default) {
                $values[$slot][$field] = Setting::get("abandoned_cart_{$slot}_{$field}", $default);
            }
        }

        return view('admin.abandoned-cart-templates.edit', [
            'reminders' => $reminders,
            'values'    => $values,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $reminders = $this->reminders();
        $rules = [];
        foreach (array_keys($reminders) as $slot) {
            $rules["{$slot}.subject"]   = 'required|string|max:200';
            $rules["{$slot}.preheader"] = 'nullable|string|max:200';
            $rules["{$slot}.heading"]   = 'required|string|max:200';
            $rules["{$slot}.body"]      = 'required|string|max:5000';
            $rules["{$slot}.cta"]       = 'required|string|max:80';
        }
        $validated = $request->validate($rules);

        foreach ($validated as $slot => $fields) {
            foreach ($fields as $field => $value) {
                Setting::set(
                    "abandoned_cart_{$slot}_{$field}",
                    (string) ($value ?? ''),
                    'string',
                    'email_templates'
                );
            }
        }

        return back()->with('success', 'Abandoned-cart templates saved.');
    }
}
