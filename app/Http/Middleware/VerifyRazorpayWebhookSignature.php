<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyRazorpayWebhookSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $signature = $request->header('X-Razorpay-Signature');
        $webhookSecret = \App\Models\Setting::get('razorpay_webhook_secret', '');

        // Skip verification if webhook secret isn't configured (dev mode)
        if (empty($webhookSecret)) {
            return $next($request);
        }

        if (empty($signature)) {
            abort(403, 'Missing Razorpay webhook signature');
        }

        $expected = hash_hmac('sha256', $request->getContent(), $webhookSecret);

        if (!hash_equals($expected, $signature)) {
            abort(403, 'Invalid Razorpay webhook signature');
        }

        return $next($request);
    }
}
