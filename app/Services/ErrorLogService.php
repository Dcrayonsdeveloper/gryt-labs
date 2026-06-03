<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ErrorLogService
{
    /**
     * Log an error to the database for admin visibility.
     */
    public static function log(
        string $message,
        string $severity = 'error',
        string $category = 'general',
        ?string $file = null,
        ?int $line = null,
        ?string $trace = null,
        ?string $url = null,
        ?int $userId = null,
        ?array $context = null
    ): void {
        try {
            $messageHash = md5($message . $file . $line);

            DB::table('error_logs')->insert([
                'severity' => $severity,
                'category' => $category,
                'message' => mb_substr($message, 0, 1000),
                'message_hash' => $messageHash,
                'file' => $file ? mb_substr($file, 0, 500) : null,
                'line' => $line,
                'trace' => $trace ? mb_substr($trace, 0, 5000) : null,
                'url' => $url ? mb_substr($url, 0, 500) : null,
                'user_id' => $userId,
                'ip_address' => request()->ip() ?? null,
                'user_agent' => request()->userAgent() ? mb_substr(request()->userAgent(), 0, 500) : null,
                'context' => $context ? json_encode($context) : null,
                'is_resolved' => false,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Fallback to file log if DB logging fails
            Log::error('ErrorLogService failed: ' . $e->getMessage());
        }
    }

    /**
     * Categorize an exception into a user-friendly category.
     */
    public static function categorize(\Throwable $e): string
    {
        $class = get_class($e);
        $message = $e->getMessage();

        return match (true) {
            str_contains($class, 'QueryException'),
            str_contains($class, 'PDOException') => 'database',

            str_contains($class, 'ViewException'),
            str_contains($class, 'InvalidArgumentException') && str_contains($e->getFile(), 'views') => 'view',

            str_contains($class, 'ValidationException') => 'validation',

            str_contains($class, 'AuthenticationException'),
            str_contains($class, 'AuthorizationException') => 'auth',

            str_contains($class, 'NotFoundHttpException'),
            str_contains($class, 'ModelNotFoundException') => '404',

            str_contains($class, 'MethodNotAllowedHttpException') => 'routing',

            str_contains($message, 'SMTP'),
            str_contains($message, 'Mailer'),
            str_contains($message, 'mail'),
            str_contains($message, 'Sender address rejected') => 'email',

            str_contains($class, 'HttpException'),
            str_contains($message, 'cURL'),
            str_contains($message, 'Connection refused') => 'external_api',

            str_contains($message, 'Razorpay'),
            str_contains($message, 'razorpay') => 'payment',

            str_contains($message, 'Delhivery'),
            str_contains($message, 'shipping') => 'shipping',

            str_contains($class, 'TokenMismatchException') => 'csrf',

            str_contains($message, 'file_put_contents'),
            str_contains($message, 'Permission denied') => 'filesystem',

            default => 'general',
        };
    }

    /**
     * Map exception to severity level.
     */
    public static function severity(\Throwable $e): string
    {
        $class = get_class($e);

        return match (true) {
            str_contains($class, 'NotFoundHttpException'),
            str_contains($class, 'ModelNotFoundException'),
            str_contains($class, 'ValidationException'),
            str_contains($class, 'TokenMismatchException'),
            str_contains($class, 'MethodNotAllowedHttpException') => 'warning',

            str_contains($class, 'QueryException'),
            str_contains($class, 'PDOException') => 'critical',

            default => 'error',
        };
    }
}
