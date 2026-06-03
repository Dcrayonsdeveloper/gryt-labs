<?php

namespace App\Http\Middleware;

use App\Models\AdminAuditLog as AuditLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuditLog
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only log state-changing requests from authenticated admin/staff
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return $response;
        }

        $user = auth('admin')->user() ?? auth()->user();
        if (!$user) {
            return $response;
        }

        // Skip login/logout routes (logged separately)
        $routeName = $request->route()?->getName() ?? '';
        if (str_contains($routeName, 'login') || str_contains($routeName, 'logout')) {
            return $response;
        }

        // Determine action from HTTP method
        $action = match ($request->method()) {
            'POST' => 'create',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'delete',
            default => 'action',
        };

        // Check for bulk actions
        if (str_contains($routeName, 'bulk')) {
            $action = 'bulk-action';
        }
        if (str_contains($routeName, 'export')) {
            $action = 'export';
        }
        if (str_contains($routeName, 'import')) {
            $action = 'import';
        }

        // Build description from route
        $description = ucfirst($action) . ': ' . ($routeName ?: $request->path());

        try {
            AuditLog::create([
                'user_id' => $user->id,
                'action' => $action,
                'description' => mb_substr($description, 0, 255),
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr($request->userAgent() ?? '', 0, 255),
            ]);
        } catch (\Throwable) {
            // Don't break the request if audit logging fails
        }

        return $response;
    }
}
