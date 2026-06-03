<x-layouts.admin>
    <x-slot name="title">{{ $seller->store_name }}</x-slot>

    <div class="flex items-center justify-between mb-6">
        <div>
            <div class="flex items-center gap-2 text-sm text-neutral-600 mb-1">
                <a href="{{ route('admin.sellers.index') }}" class="hover:text-primary-600">Sellers</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <span class="text-neutral-900">{{ $seller->store_name }}</span>
            </div>
            <h1 class="text-2xl font-bold text-neutral-900">{{ $seller->store_name }}</h1>
            <p class="text-neutral-600">{{ $seller->user->email ?? 'N/A' }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.sellers.products', $seller) }}" class="btn btn-secondary btn-sm">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                Products
            </a>
            <a href="{{ route('admin.sellers.payouts', $seller) }}" class="btn btn-secondary btn-sm">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                Payouts
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Info -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="card p-4 sm:p-5 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-primary-50 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-neutral-900">{{ number_format($stats['total_products']) }}</p>
                        <p class="text-xs text-neutral-600">Products</p>
                    </div>
                </div>
                <div class="card p-4 sm:p-5 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-info-50 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-info-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-neutral-900">{{ number_format($stats['total_orders']) }}</p>
                        <p class="text-xs text-neutral-600">Orders</p>
                    </div>
                </div>
                <div class="card p-4 sm:p-5 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-success-50 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-success-600">@price($stats['total_revenue'])</p>
                        <p class="text-xs text-neutral-600">Revenue</p>
                    </div>
                </div>
                <div class="card p-4 sm:p-5 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-warning-50 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-warning-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-warning-600">@price($stats['pending_payouts'])</p>
                        <p class="text-xs text-neutral-600">Pending Payouts</p>
                    </div>
                </div>
            </div>

            <!-- Seller Details Form -->
            <div class="card">
                <div class="p-4 border-b border-neutral-200">
                    <h2 class="font-semibold text-neutral-900">Seller Details</h2>
                </div>
                <form action="{{ route('admin.sellers.update', $seller) }}" method="POST" class="p-4 space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Store Name</label>
                            <input type="text" name="store_name" value="{{ old('store_name', $seller->store_name) }}" required
                                   class="form-input w-full">
                        </div>
                        <div>
                            <label class="form-label">Business Name</label>
                            <input type="text" name="business_name" value="{{ old('business_name', $seller->business_name) }}"
                                   class="form-input w-full">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Commission Rate (%)</label>
                            <input type="number" name="commission_rate" value="{{ old('commission_rate', $seller->commission_rate ?? 15) }}"
                                   min="0" max="100" step="0.1" required
                                   class="form-input w-full">
                        </div>
                        <div>
                            <label class="form-label">Status</label>
                            <select name="status" required class="form-input w-full">
                                <option value="pending" @selected($seller->status === 'pending')>Pending</option>
                                <option value="approved" @selected($seller->status === 'approved')>Approved</option>
                                <option value="suspended" @selected($seller->status === 'suspended')>Suspended</option>
                                <option value="rejected" @selected($seller->status === 'rejected')>Rejected</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>

            <!-- Recent Products -->
            <div class="card">
                <div class="p-4 border-b border-neutral-200 flex items-center justify-between">
                    <h2 class="font-semibold text-neutral-900">Recent Products</h2>
                    <a href="{{ route('admin.sellers.products', $seller) }}" class="text-sm text-primary-600 hover:text-primary-700">View All</a>
                </div>
                <div class="divide-y divide-neutral-200">
                    @forelse($recentProducts as $product)
                        <div class="p-4 flex items-center gap-4">
                            <div class="w-12 h-12 bg-neutral-100 rounded-lg flex items-center justify-center shrink-0">
                                @if($product->image)
                                    <img src="{{ $product->image }}" alt="{{ $product->name }}" class="w-full h-full object-cover rounded-lg">
                                @else
                                    <svg class="w-6 h-6 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-neutral-900 truncate" title="{{ $product->name }}">{{ $product->name }}</p>
                                <p class="text-sm text-neutral-600">@price($product->price)</p>
                            </div>
                            @if($product->is_active)
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge badge-neutral">Inactive</span>
                            @endif
                        </div>
                    @empty
                        <div class="p-4 text-center text-neutral-600">No products yet</div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Status Card -->
            <div class="card p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-neutral-900">Status</h3>
                    @switch($seller->status)
                        @case('approved')
                            <span class="badge badge-success">Approved</span>
                            @break
                        @case('pending')
                            <span class="badge badge-warning">Pending</span>
                            @break
                        @case('suspended')
                            <span class="badge badge-error">Suspended</span>
                            @break
                        @case('rejected')
                            <span class="badge badge-neutral">Rejected</span>
                            @break
                    @endswitch
                </div>

                @if($seller->status === 'pending')
                    <div class="space-y-2">
                        <form action="{{ route('admin.sellers.approve', $seller) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-success w-full">Approve Seller</button>
                        </form>
                        <button x-data @click="$dispatch('open-reject-modal')" class="btn btn-secondary w-full text-error-600">Reject</button>
                    </div>
                @elseif($seller->status === 'approved')
                    <button x-data @click="$dispatch('open-suspend-modal')" class="btn btn-secondary w-full text-error-600">Suspend Seller</button>
                @elseif($seller->status === 'suspended')
                    <form action="{{ route('admin.sellers.approve', $seller) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary w-full">Reactivate Seller</button>
                    </form>
                @endif
            </div>

            <!-- Contact Info -->
            <div class="card p-6">
                <h3 class="font-semibold text-neutral-900 mb-4">Contact Information</h3>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-neutral-600">Owner</dt>
                        <dd class="font-medium">{{ $seller->user->name ?? 'N/A' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-neutral-600">Email</dt>
                        <dd class="font-medium">{{ $seller->user->email ?? 'N/A' }}</dd>
                    </div>
                    @if($seller->phone)
                        <div class="flex justify-between">
                            <dt class="text-neutral-600">Phone</dt>
                            <dd class="font-medium">{{ $seller->phone }}</dd>
                        </div>
                    @endif
                    @if($seller->address)
                        <div class="flex justify-between">
                            <dt class="text-neutral-600">Address</dt>
                            <dd class="font-medium text-right">{{ $seller->address }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            <!-- Payout Info -->
            <div class="card p-6">
                <h3 class="font-semibold text-neutral-900 mb-4">Payout Information</h3>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-neutral-600">Method</dt>
                        <dd class="font-medium">{{ ucfirst(str_replace('_', ' ', $seller->payout_method ?? 'Not set')) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-neutral-600">Available Balance</dt>
                        <dd class="font-medium text-success-600">@price($seller->available_balance ?? 0)</dd>
                    </div>
                </dl>
            </div>

            <!-- Timeline -->
            <div class="card p-6">
                <h3 class="font-semibold text-neutral-900 mb-4">Timeline</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-2 h-2 bg-neutral-300 rounded-full"></div>
                        <div>
                            <p class="text-neutral-900">Joined</p>
                            <p class="text-neutral-600">{{ $seller->created_at->format('M d, Y') }}</p>
                        </div>
                    </div>
                    @if($seller->approved_at)
                        <div class="flex items-center gap-3">
                            <div class="w-2 h-2 bg-success-500 rounded-full"></div>
                            <div>
                                <p class="text-neutral-900">Approved</p>
                                <p class="text-neutral-600">{{ $seller->approved_at->format('M d, Y') }}</p>
                            </div>
                        </div>
                    @endif
                    @if($seller->suspended_at)
                        <div class="flex items-center gap-3">
                            <div class="w-2 h-2 bg-error-500 rounded-full"></div>
                            <div>
                                <p class="text-neutral-900">Suspended</p>
                                <p class="text-neutral-600">{{ $seller->suspended_at->format('M d, Y') }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div x-data="{ open: false }" @open-reject-modal.window="open = true">
        <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="fixed inset-0 bg-black/50" @click="open = false"></div>
            <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6">
                <h3 class="text-lg font-semibold text-neutral-900 mb-4">Reject Seller</h3>
                <form action="{{ route('admin.sellers.reject', $seller) }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label class="form-label">Reason</label>
                        <textarea name="rejection_reason" rows="4" required
                                  class="form-input w-full" placeholder="Please provide a reason for rejection..."></textarea>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button" @click="open = false" class="btn btn-secondary">Cancel</button>
                        <button type="submit" class="btn btn-danger">Reject</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Suspend Modal -->
    <div x-data="{ open: false }" @open-suspend-modal.window="open = true">
        <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="fixed inset-0 bg-black/50" @click="open = false"></div>
            <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6">
                <h3 class="text-lg font-semibold text-neutral-900 mb-4">Suspend Seller</h3>
                <form action="{{ route('admin.sellers.suspend', $seller) }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label class="form-label">Reason</label>
                        <textarea name="suspension_reason" rows="4" required
                                  class="form-input w-full" placeholder="Please provide a reason for suspension..."></textarea>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button" @click="open = false" class="btn btn-secondary">Cancel</button>
                        <button type="submit" class="btn btn-danger">Suspend</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.admin>
