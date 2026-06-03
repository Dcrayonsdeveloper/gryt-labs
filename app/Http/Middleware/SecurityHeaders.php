<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(self), payment=*');

        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
            // Report-Only: logs CSP violations in browser console but does NOT block.
            // Temporarily switched from enforcing to prove whether CSP causes the
            // Shiprocket PhonePe broken-page issue. Re-enable enforcing header once
            // confirmed. TODO: revert to Content-Security-Policy after testing.
            $response->headers->set('Content-Security-Policy-Report-Only', $this->buildCsp());
        }

        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin-allow-popups');
        $response->headers->set('Cross-Origin-Resource-Policy', 'cross-origin');

        return $response;
    }

    private function buildCsp(): string
    {
        $razorpay = (bool) Setting::get('razorpay_enabled');
        $cashfree = (bool) Setting::get('cashfree_enabled');

        $scriptSrc = "'self' 'unsafe-inline' 'unsafe-eval' https://www.googletagmanager.com https://www.google-analytics.com https://googleads.g.doubleclick.net https://www.googleadservices.com https://*.google.com https://connect.facebook.net https://www.instagram.com https://cdn.jsdelivr.net https://cdn.ckeditor.com https://cdnjs.cloudflare.com https://sc-static.net https://analytics.tiktok.com https://checkout-ui.shiprocket.com https://*.pickrr.com https://*.shiprocket.com https://*.shiprocket.in https://*.fastrr.com https://*.billdesk.com";
        // Note: `https://cred.club` and `https://*.cred.club` were added to connect-src
        // in commit 5da6a8b (Shiprocket Checkout integration) with no code referencing
        // them. Grep across the codebase (`*.php`, `*.blade.php`, `*.js`) confirmed no
        // usage. Removed 2026-04-22 reconcile PR. Re-add with justification if CRED Pay
        // is wired up in future.
        // cdnjs.cloudflare.com and cdn.jsdelivr.net are in script-src (jQuery, toastr,
        // Alpine.js, Chart.js). Their scripts embed sourceMappingURL comments; browsers
        // fetch those .map files via connect-src when DevTools is open. Without these
        // origins here the browser logs a CSP violation on every admin page load.
        $connectSrc = "'self' https://www.google-analytics.com https://*.google.com https://*.google.co.in https://googleads.g.doubleclick.net https://www.googleadservices.com https://www.facebook.com https://graph.facebook.com https://graph.instagram.com https://api.postalpincode.in https://nominatim.openstreetmap.org https://track.delhivery.com https://analytics.tiktok.com https://sc-static.net https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://checkout-api.shiprocket.com https://*.pickrr.com https://*.shiprocket.com https://*.shiprocket.in https://*.fastrr.com https://*.phonepe.com https://pay.google.com https://*.juspay.in https://*.juspay.io https://*.billdesk.com";
        // Payment gateways (Shiprocket, PhonePe, GPay, Juspay, BillDesk etc.) use
        // unpredictable subdomains for iframes and form redirects. Allow any HTTPS URL
        // for frame-src and form-action — this is safe because script-src and connect-src
        // still restrict what can execute. Same pattern we use for img-src.
        $frameSrc = "'self' blob: data: https:";
        $formAction = "'self' https:";

        if ($razorpay) {
            $scriptSrc .= " https://checkout.razorpay.com https://*.razorpay.com";
            $connectSrc .= " https://api.razorpay.com https://lumberjack.razorpay.com https://*.razorpay.com";
            $frameSrc .= " https://api.razorpay.com https://checkout.razorpay.com https://*.razorpay.com";
            $formAction .= " https://api.razorpay.com";
        }

        if ($cashfree) {
            $scriptSrc .= " https://sdk.cashfree.com https://*.cashfree.com";
            $connectSrc .= " https://api.cashfree.com https://sandbox.cashfree.com https://*.cashfree.com";
            $frameSrc .= " https://*.cashfree.com https://payments.cashfree.com https://payments-test.cashfree.com";
            $formAction .= " https://api.cashfree.com https://*.cashfree.com";
        }

        return implode('; ', [
            "default-src 'self'",
            "script-src {$scriptSrc}",
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net https://fonts.googleapis.com https://cdnjs.cloudflare.com https://cdn.ckeditor.com https://checkout-ui.shiprocket.com https://*.pickrr.com https://*.shiprocket.com https://*.shiprocket.in https://*.fastrr.com https://*.billdesk.com",
            "img-src 'self' data: blob: https: http:",
            "font-src 'self' https://fonts.bunny.net https://fonts.gstatic.com https://*.pickrr.com https://*.shiprocket.com https://*.fastrr.com",
            "connect-src {$connectSrc}",
            "frame-src {$frameSrc}",
            "media-src 'self' https: blob:",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action {$formAction}",
        ]);
    }
}
