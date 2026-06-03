<x-layouts.admin>
    <x-slot name="title">Error Logs</x-slot>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <h1 class="text-lg font-bold text-neutral-800">Error Logs</h1>
                @if($stats['critical'] > 0)
                    <span class="px-2 py-0.5 text-xs font-bold bg-red-100 text-red-700 rounded-full animate-pulse">{{ $stats['critical'] }} Critical</span>
                @endif
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs text-neutral-500">{{ $stats['today'] }} today / {{ $stats['unresolved'] }} unresolved / {{ $stats['total'] }} total</span>
            </div>
        </div>
    </x-slot>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
        <div class="admin-card p-3 text-center">
            <p class="text-2xl font-bold text-red-600">{{ $stats['critical'] }}</p>
            <p class="text-[10px] uppercase text-neutral-500 font-medium tracking-wider">Critical</p>
        </div>
        <div class="admin-card p-3 text-center">
            <p class="text-2xl font-bold text-neutral-800">{{ $stats['unresolved'] }}</p>
            <p class="text-[10px] uppercase text-neutral-500 font-medium tracking-wider">Unresolved</p>
        </div>
        <div class="admin-card p-3 text-center">
            <p class="text-2xl font-bold text-amber-600">{{ $stats['today'] }}</p>
            <p class="text-[10px] uppercase text-neutral-500 font-medium tracking-wider">Today</p>
        </div>
        <div class="admin-card p-3 text-center">
            <p class="text-2xl font-bold text-neutral-400">{{ $stats['total'] }}</p>
            <p class="text-[10px] uppercase text-neutral-500 font-medium tracking-wider">Total</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="admin-card p-4 mb-4">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="text-[11px] font-medium text-neutral-500 uppercase tracking-wider block mb-1">Severity</label>
                <select name="severity" class="admin-input" style="width:auto;min-width:120px;">
                    <option value="">All</option>
                    @foreach($severities as $sev)
                        <option value="{{ $sev }}" {{ request('severity') === $sev ? 'selected' : '' }}>{{ ucfirst($sev) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-[11px] font-medium text-neutral-500 uppercase tracking-wider block mb-1">Category</label>
                <select name="category" class="admin-input" style="width:auto;min-width:120px;">
                    <option value="">All</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $cat)) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-[11px] font-medium text-neutral-500 uppercase tracking-wider block mb-1">Status</label>
                <select name="resolved" class="admin-input" style="width:auto;min-width:120px;">
                    <option value="">All</option>
                    <option value="0" {{ request('resolved') === '0' ? 'selected' : '' }}>Unresolved</option>
                    <option value="1" {{ request('resolved') === '1' ? 'selected' : '' }}>Resolved</option>
                </select>
            </div>
            <div class="flex-1 min-w-[200px]">
                <label class="text-[11px] font-medium text-neutral-500 uppercase tracking-wider block mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search error messages, URLs, files..."
                       class="admin-input w-full">
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            @if(request()->hasAny(['severity', 'category', 'search', 'resolved']))
                <a href="{{ route('admin.error-logs.index') }}" class="btn btn-secondary btn-sm">Clear</a>
            @endif
        </form>
    </div>

    {{-- Error List --}}
    <div class="admin-card overflow-hidden">
        @forelse($logs as $log)
            <div class="flex items-start gap-3 px-4 py-3 border-b border-neutral-100 hover:bg-neutral-50 transition-colors {{ $log->is_resolved ? 'opacity-50' : '' }}"
                 x-data="{ expanded: false }">
                {{-- Severity Badge --}}
                <div class="pt-0.5">
                    @switch($log->severity)
                        @case('critical')
                            <span class="inline-block w-2.5 h-2.5 rounded-full bg-red-500 animate-pulse" title="Critical"></span>
                            @break
                        @case('error')
                            <span class="inline-block w-2.5 h-2.5 rounded-full bg-orange-500" title="Error"></span>
                            @break
                        @case('warning')
                            <span class="inline-block w-2.5 h-2.5 rounded-full bg-amber-400" title="Warning"></span>
                            @break
                        @default
                            <span class="inline-block w-2.5 h-2.5 rounded-full bg-blue-400" title="Info"></span>
                    @endswitch
                </div>

                {{-- Error Content --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-0.5">
                        <span class="text-[10px] font-bold uppercase px-1.5 py-0.5 rounded
                            {{ $log->severity === 'critical' ? 'bg-red-100 text-red-700' : '' }}
                            {{ $log->severity === 'error' ? 'bg-orange-100 text-orange-700' : '' }}
                            {{ $log->severity === 'warning' ? 'bg-amber-100 text-amber-700' : '' }}
                            {{ $log->severity === 'info' ? 'bg-blue-100 text-blue-700' : '' }}
                        ">{{ $log->severity }}</span>
                        <span class="text-[10px] text-neutral-400 bg-neutral-100 px-1.5 py-0.5 rounded">{{ str_replace('_', ' ', $log->category) }}</span>
                        @if($log->is_resolved)
                            <span class="text-[10px] text-green-600 bg-green-50 px-1.5 py-0.5 rounded">Resolved</span>
                        @endif
                    </div>

                    <button @click="expanded = !expanded" class="text-left w-full" title="{{ $log->message }}">
                        <p class="text-xs text-neutral-800 font-medium leading-snug" :class="expanded ? '' : 'line-clamp-2'">{{ $log->message }}</p>
                    </button>

                    <div class="flex items-center gap-3 mt-1 text-[10px] text-neutral-400">
                        <span>{{ \Carbon\Carbon::parse($log->created_at)->diffForHumans() }}</span>
                        @if($log->url)
                            <span class="truncate max-w-[200px]" title="{{ $log->url }}">{{ parse_url($log->url, PHP_URL_PATH) ?: $log->url }}</span>
                        @endif
                        @if($log->file)
                            <span class="truncate max-w-[200px]" title="{{ $log->file }}">{{ basename($log->file) }}:{{ $log->line }}</span>
                        @endif
                        @if($log->user_id)
                            <span>User #{{ $log->user_id }}</span>
                        @endif
                    </div>

                    {{-- Expanded: Stack Trace --}}
                    <div x-show="expanded" x-cloak class="mt-2">
                        @if($log->trace)
                            <pre class="text-[10px] bg-neutral-900 text-green-400 p-3 rounded overflow-x-auto max-h-48 leading-relaxed">{{ $log->trace }}</pre>
                        @endif
                        @if($log->context)
                            <details class="mt-2">
                                <summary class="text-[10px] text-neutral-500 cursor-pointer">Context Data</summary>
                                <pre class="text-[10px] bg-neutral-100 p-2 rounded mt-1 overflow-x-auto">{{ json_encode(json_decode($log->context), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                            </details>
                        @endif
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex items-center gap-1 shrink-0">
                    @unless($log->is_resolved)
                        <button onclick="fetch('{{ route('admin.error-logs.resolve', $log->id) }}', {method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}}).then(()=>location.reload())"
                                class="text-[10px] text-green-600 hover:text-green-700 bg-green-50 hover:bg-green-100 px-2 py-1 rounded transition-colors"
                                title="Mark as resolved">
                            Resolve
                        </button>
                    @endunless
                </div>
            </div>
        @empty
            <div class="px-4 py-12 text-center">
                <svg class="w-12 h-12 mx-auto text-green-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm text-neutral-500">No errors found. Everything is running smoothly!</p>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($logs->hasPages())
        <div class="mt-4">{{ $logs->links() }}</div>
    @endif
</x-layouts.admin>
