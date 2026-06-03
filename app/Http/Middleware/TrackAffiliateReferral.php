<?php

namespace App\Http\Middleware;

use App\Models\Affiliate;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackAffiliateReferral
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->hasSession() || !$request->session()->isStarted()) {
            return $next($request);
        }

        $refCode = $request->query('ref');

        if ($refCode) {
            $affiliate = Affiliate::where('referral_code', $refCode)
                ->where('status', 'approved')
                ->first();

            if ($affiliate) {
                // Store in session for checkout
                session(['affiliate_ref' => $refCode]);

                // Debounce click tracking — only count once per session
                $sessionKey = 'affiliate_click_' . $refCode;
                if (!session()->has($sessionKey)) {
                    $affiliate->increment('total_clicks');
                    session([$sessionKey => true]);
                }

                // Set cookie and pass response through
                $response = $next($request);

                $cookieName = config('affiliate.cookie_name', 'store_ref');
                $cookieDays = config('affiliate.cookie_duration_days', 30);

                return $response->withCookie(
                    cookie($cookieName, $refCode, $cookieDays * 24 * 60)
                );
            }
        }

        // Also restore from cookie if no query param but cookie exists
        if (!session('affiliate_ref')) {
            $cookieName = config('affiliate.cookie_name', 'store_ref');
            $cookieRef = $request->cookie($cookieName);

            if ($cookieRef) {
                session(['affiliate_ref' => $cookieRef]);
            }
        }

        // Only track UTM/referrer if session is available
        if (!$request->hasSession() || !$request->session()->isStarted()) {
            return $next($request);
        }

        // Capture UTM params for order attribution
        foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'] as $utm) {
            if ($request->has($utm) && !$request->session()->has($utm)) {
                $request->session()->put($utm, $request->input($utm));
            }
        }

        // Auto-detect traffic channel from referrer
        if (!$request->session()->has('first_referrer') && $request->headers->get('referer')) {
            $ref = $request->headers->get('referer');
            $host = parse_url($ref, PHP_URL_HOST) ?? '';
            if ($host && $host !== $request->getHost()) {
                $request->session()->put('first_referrer', $ref);
                $ch = 'referral';
                if (str_contains($host, 'google')) $ch = 'google';
                elseif (str_contains($host, 'facebook') || str_contains($host, 'instagram')) $ch = 'facebook';
                elseif (str_contains($host, 'youtube')) $ch = 'youtube';
                elseif (str_contains($host, 'whatsapp')) $ch = 'whatsapp';
                $request->session()->put('traffic_channel', $ch);
            }
        }

        // UTM source overrides channel detection
        if ($request->session()->has('utm_source') && !$request->session()->has('traffic_channel')) {
            $src = strtolower($request->session()->get('utm_source'));
            if (str_contains($src, 'google')) $request->session()->put('traffic_channel', 'google_ads');
            elseif (str_contains($src, 'fb') || str_contains($src, 'facebook')) $request->session()->put('traffic_channel', 'facebook_ads');
            else $request->session()->put('traffic_channel', $src);
        }

        return $next($request);
    }
}
