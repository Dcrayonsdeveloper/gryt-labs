<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Captures ?fb_capi_test=<code> from URL and persists it in a cookie.
 *
 * When the cookie is present, AnalyticsService sends CAPI events with the
 * test_event_code — visible in Meta Events Manager's Test Events tab but
 * NOT counted as real conversions. This allows testing CAPI without
 * affecting live ad optimization.
 *
 * Usage:
 *   Start test mode:  https://ayurvexa.com/?fb_capi_test=TEST16132
 *   Stop test mode:   https://ayurvexa.com/?fb_capi_test=off
 */
class CaptureFbTestParam
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $param = $request->query('fb_capi_test');

        if ($param === null) {
            return $response;
        }

        if (strtolower($param) === 'off' || $param === '') {
            $response->headers->setCookie(
                cookie()->forget('fb_capi_test')
            );
        } else {
            $response->headers->setCookie(
                cookie('fb_capi_test', $param, 60 * 24) // 24 hours
            );
        }

        return $response;
    }
}
