<?php

namespace App\Http\Middleware;

use App\Models\PosRegister;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PosAuthenticate
{
    /**
     * Verify that the request comes from a registered POS terminal
     * AND has a valid staff session.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check for valid staff session
        if (! $request->session()->has('pos_staff_id')) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'POS session expired. Please log in.'], 401);
            }
            return redirect()->route('pos.login');
        }

        // Check device is registered and active
        $deviceId = $request->session()->get('pos_device_id');
        if ($deviceId) {
            $register = PosRegister::where('device_id', $deviceId)->first();
            if (! $register || ! $register->isActive()) {
                $request->session()->forget(['pos_staff_id', 'pos_device_id', 'pos_store_id', 'pos_register_id', 'pos_shift_id']);
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Terminal deactivated. Contact admin.'], 403);
                }
                return redirect()->route('pos.login')->with('error', 'This terminal has been deactivated. Contact your administrator.');
            }
        }

        return $next($request);
    }
}
