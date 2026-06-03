<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = Setting::get('api_key');

        if (empty($apiKey)) {
            return response()->json(['error' => 'API access not configured.'], 503);
        }

        $provided = $request->header('X-API-Key')
            ?? $request->bearerToken()
            ?? $request->query('api_key');

        if (!$provided || !hash_equals((string) $apiKey, (string) $provided)) {
            return response()->json(['error' => 'Invalid or missing API key.'], 401);
        }

        return $next($request);
    }
}
