<x-layouts.admin>
    <x-slot name="title">Pending Sellers</x-slot>

    <div class="flex items-center justify-between mb-6">
        <div>
            <div class="flex items-center gap-2 text-sm text-neutral-600 mb-1">
                <a href="{{ route('admin.sellers.index') }}" class="hover:text-primary-600">Sellers</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <span class="text-neutral-900">Pending Approvals</span>
            </div>
            <h1 class="text-2xl font-bold text-neutral-900">Pending Approvals</h1>
            <p class="text-neutral-600">Review and approve new seller applications</p>
        </div>
        <a href="{{ route('admin.sellers.index') }}" class="btn btn-secondary">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to Sellers
        </a>
    </div>

    @if($sellers->isEmpty())
        <div class="card p-8 text-center">
            <div class="w-16 h-16 bg-success-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-neutral-900 mb-2">All caught up!</h3>
            <p class="text-neutral-600">There are no pending seller applications to review.</p>
        </div>
    @else
        @if($sellers->total() > 0)
            <div class="card mb-4">
                <div class="px-5 py-3">
                    {{ $sellers->links('vendor.pagination.info-bar') }}
                </div>
            </div>
        @endif

        <div class="space-y-4">
            @foreach($sellers as $seller)
                <div class="card p-6" x-data="{ showRejectModal: false }">
                    <div class="flex items-start justify-between">
                        <div class="flex items-start gap-4">
                            <div class="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center shrink-0">
                                <span class="text-xl font-medium text-primary-600">
                                    {{ strtoupper(substr($seller->store_name, 0, 1)) }}
                                </span>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-neutral-900">{{ $seller->store_name }}</h3>
                                <p class="text-neutral-600">{{ $seller->user->name ?? 'N/A' }} &bull; {{ $seller->user->email ?? 'N/A' }}</p>
                                <p class="text-sm text-neutral-600 mt-1">Applied {{ $seller->created_at->diffForHumans() }}</p>

                                <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4">
                                    @if($seller->business_name)
                                        <div>
                                            <p class="text-xs text-neutral-600">Business Name</p>
                                            <p class="text-sm font-medium">{{ $seller->business_name }}</p>
                                        </div>
                                    @endif
                                    @if($seller->phone)
                                        <div>
                                            <p class="text-xs text-neutral-600">Phone</p>
                                            <p class="text-sm font-medium">{{ $seller->phone }}</p>
                                        </div>
                                    @endif
                                    @if($seller->address)
                                        <div class="col-span-2">
                                            <p class="text-xs text-neutral-600">Address</p>
                                            <p class="text-sm font-medium">{{ $seller->address }}</p>
                                        </div>
                                    @endif
                                </div>

                                @if($seller->store_description)
                                    <div class="mt-4">
                                        <p class="text-xs text-neutral-600">Store Description</p>
                                        <p class="text-sm text-neutral-700 mt-1">{{ Str::limit($seller->store_description, 200) }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <a href="{{ route('admin.sellers.show', $seller) }}" class="btn btn-secondary btn-sm">
                                View Details
                            </a>
                            <form action="{{ route('admin.sellers.approve', $seller) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm">
                                    Approve
                                </button>
                            </form>
                            <button @click="showRejectModal = true" class="btn btn-secondary btn-sm text-error-600">
                                Reject
                            </button>
                        </div>
                    </div>

                    <!-- Reject Modal -->
                    <div x-show="showRejectModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center">
                        <div class="fixed inset-0 bg-black/50" @click="showRejectModal = false"></div>
                        <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6">
                            <h3 class="text-lg font-semibold text-neutral-900 mb-4">Reject Seller Application</h3>
                            <form action="{{ route('admin.sellers.reject', $seller) }}" method="POST">
                                @csrf
                                <div class="mb-4">
                                    <label class="form-label">Rejection Reason</label>
                                    <textarea name="rejection_reason" rows="4" required
                                              class="form-input w-full"
                                              placeholder="Please provide a reason for rejection..."></textarea>
                                </div>
                                <div class="flex justify-end gap-3">
                                    <button type="button" @click="showRejectModal = false" class="btn btn-secondary">Cancel</button>
                                    <button type="submit" class="btn btn-danger">Reject</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($sellers->hasPages())
            <div class="mt-6">
                {{ $sellers->links() }}
            </div>
        @endif
    @endif
</x-layouts.admin>
