<x-super-admin.layouts.app title="Dashboard">
    <div class="px-6 py-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Platform Dashboard</h1>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Tenants</p>
                <p class="text-3xl font-bold text-gray-900 mt-1">{{ $stats['total_tenants'] }}</p>
                <p class="text-xs text-green-600 mt-1">{{ $stats['active_tenants'] }} active</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Domains</p>
                <p class="text-3xl font-bold text-gray-900 mt-1">{{ $stats['total_domains'] }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Platform Revenue</p>
                <p class="text-3xl font-bold text-gray-900 mt-1">{{ currency_symbol() }}{{ number_format(collect($tenantStats)->sum('revenue'), 0) }}</p>
            </div>
        </div>

        <!-- Tenants Table -->
        <div class="bg-white rounded-xl border border-gray-200">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <h2 class="text-sm font-bold text-gray-900">All Tenants</h2>
                <a href="{{ route('super-admin.tenants.create') }}" class="px-4 py-1.5 bg-indigo-600 text-white text-xs font-semibold rounded-lg hover:bg-indigo-700">
                    + New Tenant
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-[11px] uppercase tracking-wider">
                        <tr>
                            <th class="px-5 py-3 text-left">Tenant</th>
                            <th class="px-5 py-3 text-left">Domain</th>
                            <th class="px-5 py-3 text-left">Plan</th>
                            <th class="px-5 py-3 text-right">Products</th>
                            <th class="px-5 py-3 text-right">Orders</th>
                            <th class="px-5 py-3 text-right">Revenue</th>
                            <th class="px-5 py-3 text-center">Status</th>
                            <th class="px-5 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($tenants as $tenant)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 font-medium text-gray-900">{{ $tenant->name }}</td>
                            <td class="px-5 py-3 text-gray-500">{{ $tenant->domains->pluck('domain')->implode(', ') }}</td>
                            <td class="px-5 py-3"><span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $tenant->plan === 'premium' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600' }}">{{ $tenant->plan }}</span></td>
                            <td class="px-5 py-3 text-right text-gray-600">{{ $tenantStats[$tenant->id]['products'] ?? '-' }}</td>
                            <td class="px-5 py-3 text-right text-gray-600">{{ $tenantStats[$tenant->id]['orders'] ?? '-' }}</td>
                            <td class="px-5 py-3 text-right font-medium">{{ isset($tenantStats[$tenant->id]['revenue']) ? currency_symbol() . number_format($tenantStats[$tenant->id]['revenue'], 0) : '-' }}</td>
                            <td class="px-5 py-3 text-center">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold {{ $tenant->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $tenant->is_active ? 'bg-green-500' : 'bg-red-500' }}"></span>
                                    {{ $tenant->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('super-admin.tenants.edit', $tenant) }}" class="text-gray-400 hover:text-indigo-600" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>
                                    <form method="POST" action="{{ route('super-admin.tenants.toggle', $tenant) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-gray-400 hover:text-amber-600" title="{{ $tenant->is_active ? 'Deactivate' : 'Activate' }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-super-admin.layouts.app>
