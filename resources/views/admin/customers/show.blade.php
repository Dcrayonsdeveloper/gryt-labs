<x-layouts.admin>
    <x-slot name="title">{{ $customer->full_name }}</x-slot>

    <div class="flex items-center gap-2 text-sm text-neutral-600 mb-6">
        <a href="{{ route('admin.customers.index') }}" class="hover:text-primary-600">Customers</a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
        <span class="text-neutral-900">{{ $customer->full_name }}</span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Customer Info Card -->
            <div class="card p-6">
                <div class="flex items-start justify-between mb-6">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center">
                            <span class="text-2xl font-bold text-primary-600">{{ substr($customer->first_name, 0, 1) }}</span>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-neutral-900">{{ $customer->full_name }}</h1>
                            <p class="text-neutral-600">{{ $customer->email }}</p>
                            @if($customer->phone)
                                <p class="text-neutral-600">{{ $customer->phone }}</p>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        @if($customer->is_active)
                            <span class="badge badge-success">Active</span>
                        @else
                            <span class="badge badge-neutral">Inactive</span>
                        @endif
                        <a href="{{ route('admin.customers.edit', $customer) }}" class="btn btn-secondary btn-sm">Edit</a>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4 p-4 bg-neutral-50 rounded-lg">
                    <div class="text-center">
                        <p class="text-2xl font-bold text-neutral-900">{{ $stats['total_orders'] }}</p>
                        <p class="text-sm text-neutral-600">Total Orders</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-neutral-900">@price($stats['total_spent'])</p>
                        <p class="text-sm text-neutral-600">Total Spent</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-neutral-900">@price($stats['avg_order_value'])</p>
                        <p class="text-sm text-neutral-600">Avg. Order Value</p>
                    </div>
                </div>
            </div>

            <!-- Admin Notes -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-semibold text-neutral-900">Notes</h2>
                    <span class="text-xs text-neutral-500">Admin-only · not visible to customer</span>
                </div>
                <form action="{{ route('admin.customers.notes.update', $customer) }}" method="POST" x-data="{ notes: @js($customer->admin_notes ?? '') }">
                    @csrf
                    @method('PATCH')
                    <textarea
                        name="admin_notes"
                        x-model="notes"
                        rows="5"
                        maxlength="5000"
                        placeholder="Anything the team should know about this customer — preferences, past issues, reminders…"
                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 focus:border-gray-400"></textarea>
                    <div class="flex items-center justify-between mt-2">
                        <span class="text-xs text-neutral-500"><span x-text="notes.length"></span>/5000</span>
                        <button type="submit" class="btn btn-primary btn-sm">Save notes</button>
                    </div>
                </form>
            </div>

            <!-- Admin Tags -->
            <div class="bg-white rounded-xl shadow-sm p-6"
                 x-data="{
                    tags: @js($customer->admin_tags ?? []),
                    input: '',
                    add() {
                        const val = this.input.trim();
                        if (!val) return;
                        if (val.length > 40) return;
                        if (this.tags.includes(val)) { this.input = ''; return; }
                        if (this.tags.length >= 20) return;
                        this.tags.push(val);
                        this.input = '';
                    },
                    remove(i) { this.tags.splice(i, 1); }
                 }">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-semibold text-neutral-900">Tags</h2>
                    <span class="text-xs text-neutral-500"><span x-text="tags.length"></span>/20</span>
                </div>
                <form action="{{ route('admin.customers.tags.update', $customer) }}" method="POST">
                    @csrf
                    @method('PATCH')

                    <div class="flex flex-wrap gap-2 min-h-[2rem] p-2 border border-gray-300 rounded-lg">
                        <template x-for="(tag, i) in tags" :key="i">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium bg-primary-50 text-primary-700 rounded-full">
                                <input type="hidden" name="admin_tags[]" :value="tag">
                                <span x-text="tag"></span>
                                <button type="button" @click="remove(i)" class="text-primary-600 hover:text-primary-900">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </span>
                        </template>
                        <input
                            type="text"
                            x-model="input"
                            @keydown.enter.prevent="add()"
                            @keydown.comma.prevent="add()"
                            maxlength="40"
                            placeholder="Add tag (e.g. VIP, fraud-risk)…"
                            class="flex-1 min-w-[10rem] text-sm border-0 focus:outline-none focus:ring-0 p-0">
                    </div>

                    <div class="flex items-center justify-between mt-3">
                        <span class="text-xs text-neutral-500">Press Enter to add a tag</span>
                        <button type="submit" class="btn btn-primary btn-sm">Save tags</button>
                    </div>
                </form>
            </div>

            <!-- Recent Orders -->
            <div class="card">
                <div class="p-4 border-b border-neutral-200 flex items-center justify-between">
                    <h2 class="font-semibold text-neutral-900">Recent Orders</h2>
                    <a href="{{ route('admin.customers.orders', $customer) }}" class="text-sm text-primary-600 hover:text-primary-700">View All</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-neutral-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Order</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Payment</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-neutral-600 uppercase">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-200">
                            @forelse($recentOrders as $order)
                                @php
                                    $oPaidOnline = (float) ($order->paid_amount ?? 0);
                                    $oCodBalance = max(0, (float) $order->total - $oPaidOnline);
                                    $oIsPartial  = $oCodBalance > 0 && $oPaidOnline > 0;
                                @endphp
                                <tr class="hover:bg-neutral-50">
                                    <td class="px-4 py-3">
                                        <a href="{{ route('admin.orders.show', $order) }}" class="font-medium text-primary-600 hover:text-primary-700">
                                            {{ $order->order_number }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-neutral-600">{{ $order->created_at->format('M d, Y') }}</td>
                                    <td class="px-4 py-3">
                                        <span class="badge {{ $order->status === 'completed' ? 'badge-success' : ($order->status === 'pending' ? 'badge-warning' : 'badge-info') }}">
                                            {{ ucfirst($order->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-neutral-600">
                                        @if($oIsPartial)
                                            <span class="text-green-600 font-medium">@price($oPaidOnline) paid</span>
                                            <span class="block text-xs text-amber-600">@price($oCodBalance) COD due</span>
                                        @elseif($oPaidOnline > 0)
                                            <span class="badge badge-success">Paid</span>
                                        @else
                                            <span class="badge badge-warning">COD</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm font-medium text-right">@price($order->total)</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-neutral-600">
                                        No orders yet
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Account Details -->
            <div class="card p-6">
                <h2 class="font-semibold text-neutral-900 mb-4">Account Details</h2>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-neutral-600">Member Since</dt>
                        <dd class="font-medium">{{ $customer->created_at->format('M d, Y') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-neutral-600">Last Login</dt>
                        <dd class="font-medium">{{ $customer->last_login_at?->diffForHumans() ?? 'Never' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-neutral-600">Email Verified</dt>
                        <dd>
                            @if($customer->email_verified_at)
                                <span class="text-success-600">Yes</span>
                            @else
                                <span class="text-warning-600">No</span>
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>

            <!-- Addresses -->
            <div class="card p-6">
                <h2 class="font-semibold text-neutral-900 mb-4">Saved Addresses</h2>
                @if($customer->addresses && $customer->addresses->count())
                    <div class="space-y-4">
                        @foreach($customer->addresses as $address)
                            <div class="p-3 bg-neutral-50 rounded-lg text-sm">
                                @if($address->is_default)
                                    <span class="badge badge-primary mb-2">Default</span>
                                @endif
                                <p class="font-medium text-neutral-900">{{ $address->name }}</p>
                                <p class="text-neutral-600">{{ $address->address_line1 }}</p>
                                @if($address->address_line2)
                                    <p class="text-neutral-600">{{ $address->address_line2 }}</p>
                                @endif
                                <p class="text-neutral-600">{{ $address->city }}, {{ $address->state }} {{ $address->postal_code }}</p>
                                <p class="text-neutral-600">{{ $address->country }}</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-neutral-600 text-sm">No saved addresses</p>
                @endif
            </div>

            <!-- Actions -->
            <div class="card p-6">
                <h2 class="font-semibold text-neutral-900 mb-4">Actions</h2>
                <div class="space-y-2">
                    <form action="{{ route('admin.customers.toggle-status', $customer) }}" method="POST">
                        @csrf
                        @method('PUT')
                        @if($customer->is_active)
                            <button type="submit" class="btn btn-secondary w-full text-warning-600">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                </svg>
                                Deactivate Account
                            </button>
                        @else
                            <button type="submit" class="btn btn-secondary w-full text-success-600">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Activate Account
                            </button>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
