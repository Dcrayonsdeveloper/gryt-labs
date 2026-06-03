<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\NewsletterSubscriber;
use App\Models\Setting;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NewsletterController extends Controller
{
    public function subscribe(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'name'  => 'nullable|string|max:100',
        ]);

        $existing = NewsletterSubscriber::where('email', $validated['email'])->first();

        if ($existing) {
            if ($existing->is_active) {
                return $this->respond($request, 'This email is already subscribed!');
            }

            $existing->update([
                'is_active'        => true,
                'subscribed_at'    => now(),
                'unsubscribed_at'  => null,
                'source'           => $request->input('source', 'homepage'),
                'ip_address'       => $request->ip(),
            ]);

            return $this->respond($request, 'Welcome back! You have been re-subscribed.');
        }

        NewsletterSubscriber::create([
            'email'         => $validated['email'],
            'name'          => $validated['name'] ?? null,
            'source'        => $request->input('source', 'homepage'),
            'is_active'     => true,
            'subscribed_at' => now(),
            'ip_address'    => $request->ip(),
        ]);

        // Send welcome discount email
        try {
            $this->sendDiscountEmail($validated['email'], $validated['name'] ?? 'there');
        } catch (\Exception $e) {
            Log::warning('Newsletter discount email failed', ['email' => $validated['email'], 'error' => $e->getMessage()]);
        }

        // Facebook CAPI: Subscribe
        try {
            app(AnalyticsService::class)->trackSubscribe($validated['email'], $request);
        } catch (\Exception $e) {
            // Don't block subscription for analytics
        }

        return $this->respond($request, "You're subscribed! Check your inbox for your discount code.");
    }

    private function sendDiscountEmail(string $email, string $name): void
    {
        $storeName = Setting::get('store_name', config('app.name', 'Store'));
        $brandColor = Setting::get('primary_color', '') ?: '#334155';
        $baseUrl = function_exists('tenant') && tenant()
            ? 'https://' . (tenant()->domains->first()->domain ?? request()->getHost())
            : url('/');
        $logoUrl = $baseUrl . '/' . ltrim(Setting::get('store_logo', 'images/logo.png'), '/');

        // Find or use a default welcome coupon code
        $coupon = Coupon::where('is_active', true)
            ->where('code', 'WELCOME10')
            ->first();

        $couponCode = $coupon->code ?? 'WELCOME10';
        $discountText = $coupon
            ? ($coupon->type === 'percentage' ? intval($coupon->value) . '%' : '₹' . intval($coupon->value))
            : '10%';

        Mail::send([], [], function ($m) use ($email, $name, $storeName, $brandColor, $logoUrl, $couponCode, $discountText) {
            $m->to($email)
              ->subject("{$discountText} Off — Welcome to {$storeName}!")
              ->html("
                <div style='font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;max-width:500px;margin:0 auto;padding:0;'>
                    <div style='background:{$brandColor};padding:24px;text-align:center;border-radius:8px 8px 0 0;'>
                        <img src='{$logoUrl}' alt='{$storeName}' style='height:40px;object-fit:contain;' />
                    </div>
                    <div style='padding:32px 24px;background:#fff;'>
                        <h1 style='color:#111;font-size:22px;margin:0 0 8px;'>Welcome, {$name}!</h1>
                        <p style='color:#555;font-size:14px;line-height:1.6;margin:0 0 24px;'>
                            Thank you for subscribing to {$storeName}. Here's your exclusive discount code:
                        </p>
                        <div style='background:#f8f8f8;border:2px dashed {$brandColor};border-radius:8px;padding:20px;text-align:center;margin:0 0 24px;'>
                            <p style='color:#888;font-size:12px;margin:0 0 8px;text-transform:uppercase;letter-spacing:1px;'>Your Discount Code</p>
                            <p style='color:{$brandColor};font-size:28px;font-weight:800;margin:0 0 4px;letter-spacing:2px;'>{$couponCode}</p>
                            <p style='color:#555;font-size:14px;margin:0;'>{$discountText} off your first order</p>
                        </div>
                        <a href='{$baseUrl}/products' style='display:block;background:{$brandColor};color:#fff;padding:14px 24px;border-radius:8px;text-decoration:none;font-size:15px;font-weight:600;text-align:center;'>
                            Shop Now
                        </a>
                    </div>
                    <div style='padding:16px 24px;background:#f5f5f5;text-align:center;border-radius:0 0 8px 8px;'>
                        <p style='color:#999;font-size:11px;margin:0;'>You're receiving this because you subscribed to {$storeName}. <a href='{$baseUrl}' style='color:#999;'>Unsubscribe</a></p>
                    </div>
                </div>
              ");
        });
    }

    private function respond(Request $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return back()->with('success', $message);
    }
}
