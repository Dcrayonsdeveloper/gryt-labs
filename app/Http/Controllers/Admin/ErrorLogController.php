<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ErrorLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = DB::table('error_logs')->orderByDesc('created_at');

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('message', 'like', "%{$search}%")
                  ->orWhere('url', 'like', "%{$search}%")
                  ->orWhere('file', 'like', "%{$search}%");
            });
        }
        if ($request->filled('resolved')) {
            $query->where('is_resolved', $request->resolved === '1');
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate(50)->withQueryString();

        $stats = [
            'total' => DB::table('error_logs')->count(),
            'today' => DB::table('error_logs')->whereDate('created_at', today())->count(),
            'critical' => DB::table('error_logs')->where('severity', 'critical')->where('is_resolved', false)->count(),
            'unresolved' => DB::table('error_logs')->where('is_resolved', false)->count(),
        ];

        $categories = DB::table('error_logs')->distinct()->pluck('category')->filter()->sort();
        $severities = ['critical', 'error', 'warning', 'info'];

        return view('admin.error-logs.index', compact('logs', 'stats', 'categories', 'severities'));
    }

    public function show(int $id): View
    {
        $log = DB::table('error_logs')->where('id', $id)->firstOrFail();

        // Find similar errors (same message hash)
        $similar = DB::table('error_logs')
            ->where('message_hash', $log->message_hash)
            ->where('id', '!=', $id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('admin.error-logs.show', compact('log', 'similar'));
    }

    public function resolve(Request $request, int $id): JsonResponse
    {
        DB::table('error_logs')->where('id', $id)->update([
            'is_resolved' => true,
            'resolved_by' => auth('admin')->id(),
            'resolved_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    public function bulkResolve(Request $request): JsonResponse
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return response()->json(['error' => 'No errors selected'], 422);
        }

        DB::table('error_logs')->whereIn('id', $ids)->update([
            'is_resolved' => true,
            'resolved_by' => auth('admin')->id(),
            'resolved_at' => now(),
        ]);

        return response()->json(['success' => true, 'count' => count($ids)]);
    }

    public function clear(Request $request): JsonResponse
    {
        $days = (int) $request->input('days', 30);
        $deleted = DB::table('error_logs')
            ->where('created_at', '<', now()->subDays($days))
            ->where('is_resolved', true)
            ->delete();

        return response()->json(['success' => true, 'deleted' => $deleted]);
    }
}
