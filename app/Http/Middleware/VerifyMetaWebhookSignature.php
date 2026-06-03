<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyMetaWebhookSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        // GET requests (webhook verification) don't carry signatures
        if ($request->isMethod('GET')) {
            return $next($request);
        }

        $signature = $request->header('X-Hub-Signature-256');

        // Per-tenant app secret (Settings), fall back to global .env for single-tenant setups
        $appSecret = Setting::get('meta_app_secret', '') ?: config('services.meta.app_secret');

        // Skip verification if app secret isn't configured (dev mode)
        if (empty($appSecret)) {
            return $next($request);
        }

        if (empty($signature)) {
            abort(403, 'Missing webhook signature');
        }

        $expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), $appSecret);

        if (!hash_equals($expected, $signature)) {
            abort(403, 'Invalid webhook signature');
        }

        return $next($request);
    }
}
