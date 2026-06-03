<x-layouts.admin>
    <x-slot name="title">{{ $deliveryPartner->user->full_name }}</x-slot>

    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm text-neutral-600 mb-1">
            <a href="{{ route('admin.delivery-partners.index') }}" class="hover:text-primary-600">Delivery Partners</a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-neutral-900">{{ $deliveryPartner->partner_id }}</span>
        </div>
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-neutral-900">{{ $deliveryPartner->user->full_name }}</h1>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.delivery-partners.edit', $deliveryPartner) }}" class="btn btn-primary btn-sm">Edit</a>
                <form action="{{ route('admin.delivery-partners.toggle-status', $deliveryPartner) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <button type="submit" class="btn {{ $deliveryPartner->is_active ? 'btn-warning' : 'btn-success' }} btn-sm">
                        {{ $deliveryPartner->is_active ? 'Deactivate' : 'Activate' }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Partner Details --}}
        <div class="space-y-6">
            <div class="card">
                <div class="card-header">
                    <h2 class="font-semibold text-neutral-900">Partner Details</h2>
                </div>
                <div class="p-4 space-y-3">
                    <div class="flex items-center gap-3 pb-3 border-b border-neutral-100">
                        <div class="w-12 h-12 rounded-full bg-primary-100 flex items-center justify-center">
                            <span class="text-lg font-bold text-primary-600">{{ strtoupper(substr($deliveryPartner->user->first_name, 0, 1) . substr($deliveryPartner->user->last_name, 0, 1)) }}</span>
                        </div>
                        <div>
                            <p class="font-semibold text-neutral-900">{{ $deliveryPartner->user->full_name }}</p>
                            <p class="text-xs text-neutral-600">{{ $deliveryPartner->partner_id }}</p>
                        </div>
                        <span class="badge {{ $deliveryPartner->is_active ? 'badge-success' : 'badge-error' }} ml-auto">
                            {{ $deliveryPartner->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-neutral-600">Email</span>
                        <span class="text-neutral-700">{{ $deliveryPartner->user->email }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-neutral-600">Phone</span>
                        <span class="text-neutral-700">{{ $deliveryPartner->phone ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-neutral-600">Vehicle</span>
                        <span class="text-neutral-700">{{ ucfirst($deliveryPartner->vehicle_type) }}</span>
                    </div>
                    @if($deliveryPartner->vehicle_number)
                    <div class="flex justify-between text-sm">
                        <span class="text-neutral-600">Vehicle No.</span>
                        <span class="font-mono text-neutral-700">{{ $deliveryPartner->vehicle_number }}</span>
                    </div>
                    @endif
                    @if($deliveryPartner->license_number)
                    <div class="flex justify-between text-sm">
                        <span class="text-neutral-600">License</span>
                        <span class="text-neutral-700">{{ $deliveryPartner->license_number }}</span>
                    </div>
                    @endif
                    @if($deliveryPartner->company_name)
                    <div class="flex justify-between text-sm">
                        <span class="text-neutral-600">Company</span>
                        <span class="text-neutral-700">{{ $deliveryPartner->company_name }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between text-sm">
                        <span class="text-neutral-600">Joined</span>
                        <span class="text-neutral-700">{{ $deliveryPartner->created_at->format('M d, Y') }}</span>
                    </div>
                    <div class="flex justify-between text-sm items-center">
                        <span class="text-neutral-600">Verification</span>
                        @php
                            $vBadge = match($deliveryPartner->verification_status) {
                                'verified' => 'badge-success',
                                'rejected' => 'badge-error',
                                default => 'badge-warning',
                            };
                        @endphp
                        <span class="badge {{ $vBadge }}">{{ ucfirst($deliveryPartner->verification_status) }}</span>
                    </div>
                    @if($deliveryPartner->verified_at)
                    <div class="flex justify-between text-sm">
                        <span class="text-neutral-600">Verified On</span>
                        <span class="text-neutral-700">{{ $deliveryPartner->verified_at->format('M d, Y') }}</span>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Documents --}}
            <div class="card">
                <div class="card-header">
                    <h2 class="font-semibold text-neutral-900">Documents</h2>
                </div>
                <div class="p-4 space-y-3">
                    <div class="flex justify-between text-sm items-center">
                        <span class="text-neutral-600">ID Proof</span>
                        @if($deliveryPartner->id_proof)
                            <a href="{{ asset('storage/' . $deliveryPartner->id_proof) }}" target="_blank" class="text-primary-600 hover:text-primary-700 font-medium text-xs">View</a>
                        @else
                            <span class="badge badge-error">Not Uploaded</span>
                        @endif
                    </div>
                    <div class="flex justify-between text-sm items-center">
                        <span class="text-neutral-600">Driving License</span>
                        @if($deliveryPartner->license_document)
                            <a href="{{ asset('storage/' . $deliveryPartner->license_document) }}" target="_blank" class="text-primary-600 hover:text-primary-700 font-medium text-xs">View</a>
                        @else
                            <span class="badge badge-error">Not Uploaded</span>
                        @endif
                    </div>
                    <div class="flex justify-between text-sm items-center">
                        <span class="text-neutral-600">Address Proof</span>
                        @if($deliveryPartner->address_proof)
                            <a href="{{ asset('storage/' . $deliveryPartner->address_proof) }}" target="_blank" class="text-primary-600 hover:text-primary-700 font-medium text-xs">View</a>
                        @else
                            <span class="text-xs text-neutral-600">Not uploaded</span>
                        @endif
                    </div>
                    @if($deliveryPartner->verification_note)
                        <div class="pt-2 border-t border-neutral-100">
                            <p class="text-xs text-neutral-600 font-medium mb-1">Verification Note</p>
                            <p class="text-sm text-neutral-700">{{ $deliveryPartner->verification_note }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Stats --}}
            <div class="card">
                <div class="card-header">
                    <h2 class="font-semibold text-neutral-900">Delivery Stats</h2>
                </div>
                <div class="p-4 space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-neutral-600">Active Deliveries</span>
                        <span class="font-semibold text-warning-600">{{ $stats['active'] }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-neutral-600">Completed</span>
                        <span class="font-semibold text-success-600">{{ $stats['delivered'] }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-neutral-600">Total Assigned</span>
                        <span class="font-semibold text-neutral-900">{{ $stats['total'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Orders --}}
        <div class="lg:col-span-2">
            <div class="card overflow-hidden">
                <div class="card-header">
                    <h2 class="font-semibold text-neutral-900">Assigned Orders</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-neutral-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Order</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Customer</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Total</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-200">
                            @forelse($orders as $order)
                                <tr class="hover:bg-neutral-50">
                                    <td class="px-4 py-3">
                                        <a href="{{ route('admin.orders.show', $order) }}" class="font-mono text-sm font-medium text-primary-600 hover:text-primary-700">
                                            {{ $order->order_number }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-neutral-600">{{ $order->user->full_name ?? '-' }}</td>
                                    <td class="px-4 py-3">
                                        @php
                                            $badgeClass = match($order->status) {
                                                'delivered' => 'badge-success',
                                                'shipped', 'out_for_delivery' => 'badge-warning',
                                                'cancelled' => 'badge-error',
                                                default => 'badge-info',
                                            };
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">{{ ucfirst(str_replace('_', ' ', $order->status)) }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-sm font-medium text-neutral-900">@price($order->total)</td>
                                    <td class="px-4 py-3 text-sm text-neutral-600">{{ $order->created_at->format('M d, Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-neutral-600">No orders assigned yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($orders->hasPages())
                    <div class="px-4 py-3 border-t border-neutral-200">
                        {{ $orders->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.admin>
