<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('admin_audit_logs')
            ->leftJoin('users', 'admin_audit_logs.user_id', '=', 'users.id')
            ->select(
                'admin_audit_logs.*',
                DB::raw("CONCAT(users.first_name, ' ', users.last_name) as user_name"),
                'users.email as user_email'
            )
            ->orderByDesc('admin_audit_logs.created_at');

        // Filters
        if ($request->filled('action')) {
            $query->where('admin_audit_logs.action', $request->action);
        }
        if ($request->filled('user_id')) {
            $query->where('admin_audit_logs.user_id', $request->user_id);
        }
        if ($request->filled('search')) {
            $query->where('admin_audit_logs.description', 'like', '%' . $request->search . '%');
        }

        $logs = $query->paginate(50);

        // Get unique actions for filter dropdown
        $actions = DB::table('admin_audit_logs')->distinct()->pluck('action')->sort();

        // Get admin users for filter
        $admins = DB::table('users')->where('role', 'admin')
            ->select('id', DB::raw("CONCAT(first_name, ' ', last_name) as name"))
            ->get();

        return view('admin.audit-log.index', compact('logs', 'actions', 'admins'));
    }
}
