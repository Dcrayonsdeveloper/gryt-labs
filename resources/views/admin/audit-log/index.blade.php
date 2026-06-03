<x-layouts.admin>
    <x-slot name="title">Activity Log</x-slot>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-lg font-bold text-neutral-800">Activity Log</h1>
            <span class="text-xs text-neutral-500">{{ $logs->total() }} entries</span>
        </div>
    </x-slot>

    {{-- Filters --}}
    <div class="admin-card p-4 mb-4">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="text-[11px] font-medium text-neutral-500 uppercase tracking-wider block mb-1">Action</label>
                <select name="action" class="admin-input" style="width:auto;min-width:140px;">
                    <option value="">All actions</option>
                    @foreach($actions as $action)
                        <option value="{{ $action }}" {{ request('action') === $action ? 'selected' : '' }}>{{ ucfirst($action) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-[11px] font-medium text-neutral-500 uppercase tracking-wider block mb-1">Staff</label>
                <select name="user_id" class="admin-input" style="width:auto;min-width:140px;">
                    <option value="">All staff</option>
                    @foreach($admins as $admin)
                        <option value="{{ $admin->id }}" {{ request('user_id') == $admin->id ? 'selected' : '' }}>{{ $admin->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-[11px] font-medium text-neutral-500 uppercase tracking-wider block mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search description..." class="admin-input" style="width:200px;">
            </div>
            <button type="submit" class="admin-btn admin-btn-primary">Filter</button>
            @if(request()->hasAny(['action', 'user_id', 'search']))
                <a href="{{ route('admin.audit-log.index') }}" class="admin-btn admin-btn-outline">Clear</a>
            @endif
        </form>
    </div>

    {{-- Log Table --}}
    <div class="admin-card overflow-hidden">
        <table class="w-full">
            <thead>
                <tr style="background:#fafafa;border-bottom:1px solid #e1e1e1;">
                    <th class="text-left px-4 py-2.5">When</th>
                    <th class="text-left px-4 py-2.5">Staff</th>
                    <th class="text-left px-4 py-2.5">Action</th>
                    <th class="text-left px-4 py-2.5">Description</th>
                    <th class="text-left px-4 py-2.5">IP</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr style="border-bottom:1px solid #f0f0f0;">
                    <td class="px-4 py-2.5 whitespace-nowrap">
                        <span class="text-xs text-neutral-500" title="{{ $log->created_at }}">{{ \Carbon\Carbon::parse($log->created_at)->diffForHumans() }}</span>
                    </td>
                    <td class="px-4 py-2.5">
                        <span class="text-sm font-medium">{{ $log->user_name ?? 'Unknown' }}</span>
                        @if($log->user_email)
                            <span class="text-xs text-neutral-400 block">{{ $log->user_email }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-2.5">
                        @php
                            $badgeClass = match($log->action) {
                                'create' => 'admin-badge-success',
                                'update' => 'admin-badge-warning',
                                'delete' => 'admin-badge-critical',
                                default => 'admin-badge-neutral',
                            };
                        @endphp
                        <span class="admin-badge {{ $badgeClass }}">{{ ucfirst($log->action) }}</span>
                    </td>
                    <td class="px-4 py-2.5">
                        <span class="text-sm">{{ Str::limit($log->description, 80) }}</span>
                        @if($log->model_type)
                            <span class="text-xs text-neutral-400 block">{{ class_basename($log->model_type) }} #{{ $log->model_id }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-2.5">
                        <span class="text-xs text-neutral-400 font-mono">{{ $log->ip_address }}</span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-neutral-500">No activity logs found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($logs->hasPages())
    <div class="mt-4">
        {{ $logs->withQueryString()->links() }}
    </div>
    @endif

</x-layouts.admin>
