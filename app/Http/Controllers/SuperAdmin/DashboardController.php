<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;

class DashboardController extends Controller
{
    public function index()
    {
        $tenants = Tenant::with('domains')->get();
        $stats = [
            'total_tenants' => $tenants->count(),
            'active_tenants' => $tenants->where('is_active', true)->count(),
            'total_domains' => $tenants->sum(fn ($t) => $t->domains->count()),
        ];

        // Get per-tenant stats by initializing each tenant briefly
        $tenantStats = [];
        foreach ($tenants as $tenant) {
            try {
                tenancy()->initialize($tenant);
                $tenantStats[$tenant->id] = [
                    'products' => \App\Models\Product::where('is_active', true)->count(),
                    'orders' => \App\Models\Order::count(),
                    'users' => \App\Models\User::count(),
                    'revenue' => \App\Models\Order::where('payment_status', 'paid')->sum('total'),
                ];
                tenancy()->end();
            } catch (\Exception $e) {
                $tenantStats[$tenant->id] = ['error' => $e->getMessage()];
                tenancy()->end();
            }
        }

        return view('super-admin.dashboard', compact('tenants', 'stats', 'tenantStats'));
    }
}
