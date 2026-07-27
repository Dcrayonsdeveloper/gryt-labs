<x-layouts.admin title="Influencers">

    {{-- Summary --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="card p-6">
            <p class="text-sm text-neutral-600">Total Influencers</p>
            <p class="text-2xl font-bold text-neutral-900 mt-1">{{ $totals['total'] }}</p>
        </div>
        <div class="card p-6">
            <p class="text-sm text-neutral-600">Active</p>
            <p class="text-2xl font-bold text-success-600 mt-1">{{ $totals['active'] }}</p>
        </div>
        <div class="card p-6">
            <p class="text-sm text-neutral-600">Inactive</p>
            <p class="text-2xl font-bold text-neutral-500 mt-1">{{ $totals['inactive'] }}</p>
        </div>
    </div>

    {{-- Toolbar --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <form method="GET" action="{{ route('admin.influencers.index') }}" class="flex flex-wrap items-center gap-2">
            <input type="text" name="search" value="{{ $search }}" placeholder="Search name, username, coupon…" class="form-input text-sm w-56">
            <select name="status" class="form-select text-sm">
                <option value="">All statuses</option>
                <option value="active" @selected($status === 'active')>Active</option>
                <option value="inactive" @selected($status === 'inactive')>Inactive</option>
            </select>
            <span class="text-xs text-neutral-400">Sales in:</span>
            <input type="date" name="from" value="{{ $from }}" class="form-input text-sm" title="From (for the Orders/Sales columns)">
            <input type="date" name="to" value="{{ $to }}" class="form-input text-sm" title="To">
            <input type="hidden" name="range" value="custom">
            <button class="btn btn-secondary text-sm">Filter</button>
            @if($search || $status || $from || $to)
                <a href="{{ route('admin.influencers.index') }}" class="text-xs text-neutral-500 hover:underline">Clear</a>
            @endif
        </form>
        <a href="{{ route('admin.influencers.create') }}" class="btn btn-primary text-sm">+ Add Influencer</a>
    </div>

    @if($from || $to)
        <p class="text-xs text-neutral-500 mb-2">Orders / Sales / Discount columns are for the selected date range. Otherwise they're all-time.</p>
    @endif

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-neutral-50 text-left text-xs text-neutral-500">
                        <th class="px-4 py-2.5 font-medium">Name</th>
                        <th class="px-4 py-2.5 font-medium">Username</th>
                        <th class="px-4 py-2.5 font-medium">Coupon</th>
                        <th class="px-4 py-2.5 font-medium">Mobile</th>
                        <th class="px-4 py-2.5 font-medium">Email</th>
                        <th class="px-4 py-2.5 font-medium text-right">Orders</th>
                        <th class="px-4 py-2.5 font-medium text-right">Sales</th>
                        <th class="px-4 py-2.5 font-medium text-right">Discount</th>
                        <th class="px-4 py-2.5 font-medium">Status</th>
                        <th class="px-4 py-2.5 font-medium">Created</th>
                        <th class="px-4 py-2.5 font-medium text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @forelse($influencers as $inf)
                        @php $s = $statsByCode[$inf->coupon_code] ?? ['orders' => 0, 'sales' => 0, 'discount' => 0]; @endphp
                        <tr class="hover:bg-neutral-50 align-middle">
                            <td class="px-4 py-2.5 font-medium text-neutral-900 whitespace-nowrap">{{ $inf->full_name }}</td>
                            <td class="px-4 py-2.5 font-mono text-xs text-neutral-600">{{ $inf->username }}</td>
                            <td class="px-4 py-2.5"><span class="inline-block px-2 py-0.5 rounded bg-primary-50 text-primary-700 font-mono text-xs ring-1 ring-primary-100">{{ $inf->coupon_code }}</span></td>
                            <td class="px-4 py-2.5 text-neutral-600 whitespace-nowrap">{{ $inf->mobile ?: '—' }}</td>
                            <td class="px-4 py-2.5 text-neutral-600">{{ $inf->email ?: '—' }}</td>
                            <td class="px-4 py-2.5 text-right text-neutral-700">{{ number_format($s['orders']) }}</td>
                            <td class="px-4 py-2.5 text-right font-medium text-neutral-900">₹{{ number_format($s['sales'], 2) }}</td>
                            <td class="px-4 py-2.5 text-right text-amber-600">₹{{ number_format($s['discount'], 2) }}</td>
                            <td class="px-4 py-2.5">
                                @if($inf->isActive())
                                    <span class="inline-block px-2 py-0.5 rounded text-xs bg-success-100 text-success-700">Active</span>
                                @else
                                    <span class="inline-block px-2 py-0.5 rounded text-xs bg-neutral-100 text-neutral-600">Inactive</span>
                                @endif
                            </td>
                            <td class="px-4 py-2.5 text-neutral-500 whitespace-nowrap">{{ $inf->created_at?->format('d M Y') }}</td>
                            <td class="px-4 py-2.5">
                                <div class="flex items-center justify-end gap-2 whitespace-nowrap">
                                    <a href="{{ route('admin.influencers.analytics', $inf) }}" class="text-primary-600 hover:underline text-xs">Analytics</a>
                                    <a href="{{ route('admin.influencers.edit', $inf) }}" class="text-neutral-600 hover:underline text-xs">Edit</a>

                                    <form method="POST" action="{{ route('admin.influencers.toggle-status', $inf) }}" class="inline">
                                        @csrf @method('PUT')
                                        <button type="submit" class="text-xs text-neutral-600 hover:underline">{{ $inf->isActive() ? 'Disable' : 'Enable' }}</button>
                                    </form>

                                    <form method="POST" action="{{ route('admin.influencers.reset-password', $inf) }}" class="inline">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="password">
                                        <button type="button" class="text-xs text-neutral-600 hover:underline"
                                                onclick="const f=this.closest('form');const p=prompt('New password for {{ $inf->username }} (min 6 chars):');if(p===null)return;if(p.length<6){alert('Minimum 6 characters');return;}f.querySelector('input[name=password]').value=p;f.submit();">Reset&nbsp;PW</button>
                                    </form>

                                    <form method="POST" action="{{ route('admin.influencers.destroy', $inf) }}" class="inline"
                                          onsubmit="return confirm('Delete influencer {{ $inf->full_name }}? Their coupon stays for historical orders but is deactivated.');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-xs text-error-600 hover:underline">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="px-4 py-12 text-center text-neutral-400">No influencers yet. <a href="{{ route('admin.influencers.create') }}" class="text-primary-600 hover:underline">Add one</a>.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($influencers->hasPages())
            <div class="px-4 py-3 border-t border-neutral-200">{{ $influencers->withQueryString()->links() }}</div>
        @endif
    </div>
</x-layouts.admin>
