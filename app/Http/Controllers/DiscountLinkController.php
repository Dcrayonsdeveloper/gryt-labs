<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DiscountLinkController extends Controller
{
    public function apply(string $code, Request $request)
    {
        $redirect = $request->query('redirect', '/');
        // Prevent open redirect — only allow local paths
        $path = parse_url($redirect, PHP_URL_PATH) ?? '/';
        $query = parse_url($redirect, PHP_URL_QUERY);
        $redirect = '/' . ltrim($path, '/') . ($query ? '?' . $query : '');

        return redirect($redirect)
            ->withCookie(cookie('discount_code', $code, 60 * 24 * 30, '/'))
            ->with('discount_code', $code);
    }
}
