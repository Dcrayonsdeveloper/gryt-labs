<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ContentSecurityPolicy
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Detect which payment gateways are enabled for this tenant
        $razorpayEnabled = (bool) Setting::get('razorpay_enabled');
        $cashfreeEnabled = (bool) Setting::get('cashfree_enabled');

        // Base directives (always present)
        $scriptSrc = ["'self'", "'unsafe-inline'", "'unsafe-eval'",
            "https://www.googletagmanager.com", "https://www.google-analytics.com",
            "https://connect.facebook.net", "https://www.instagram.com",
            "https://cdn.jsdelivr.net", "https://cdn.ckeditor.com", "https://cdnjs.cloudflare.com",
            "https://www.clarity.ms", "https://*.clarity.ms"];

        $connectSrc = ["'self'",
            "https://www.google-analytics.com", "https://www.facebook.com",
            "https://graph.facebook.com", "https://graph.instagram.com",
            "https://api.postalpincode.in", "https://track.delhivery.com",
            "https://www.clarity.ms", "https://*.clarity.ms"];

        $frameSrc = ["'self'",
            "https://www.instagram.com", "https://www.google.com",
            "https://accounts.google.com", "https://www.facebook.com",
            "https://www.youtube.com", "https://www.youtube-nocookie.com"];

        $formAction = ["'self'", "https://accounts.google.com", "https://www.facebook.com"];

        // Add Razorpay domains only if enabled
        if ($razorpayEnabled) {
            $scriptSrc[] = "https://checkout.razorpay.com";
            $scriptSrc[] = "https://*.razorpay.com";
            $connectSrc[] = "https://api.razorpay.com";
            $connectSrc[] = "https://lumberjack.razorpay.com";
            $connectSrc[] = "https://*.razorpay.com";
            $frameSrc[] = "https://api.razorpay.com";
            $frameSrc[] = "https://checkout.razorpay.com";
            $frameSrc[] = "https://*.razorpay.com";
            $formAction[] = "https://api.razorpay.com";
        }

        // Add Cashfree domains only if enabled
        if ($cashfreeEnabled) {
            $scriptSrc[] = "https://sdk.cashfree.com";
            $scriptSrc[] = "https://*.cashfree.com";
            $connectSrc[] = "https://api.cashfree.com";
            $connectSrc[] = "https://sandbox.cashfree.com";
            $connectSrc[] = "https://*.cashfree.com";
            $frameSrc[] = "https://*.cashfree.com";
            $frameSrc[] = "https://payments.cashfree.com";
            $frameSrc[] = "https://payments-test.cashfree.com";
            $formAction[] = "https://api.cashfree.com";
            $formAction[] = "https://*.cashfree.com";
        }

        $csp = implode('; ', [
            "default-src 'self'",
            "script-src " . implode(' ', $scriptSrc),
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net https://fonts.googleapis.com https://cdnjs.cloudflare.com https://cdn.ckeditor.com",
            "img-src 'self' data: blob: https: http:",
            "font-src 'self' https://fonts.bunny.net https://fonts.gstatic.com",
            "connect-src " . implode(' ', $connectSrc),
            "frame-src " . implode(' ', $frameSrc),
            "media-src 'self' https: blob:",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action " . implode(' ', $formAction),
        ]);

        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
