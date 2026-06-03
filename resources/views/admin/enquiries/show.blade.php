<x-layouts.admin>
    <x-slot name="title">Enquiry #{{ $enquiry->id }}</x-slot>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.enquiries.index') }}" class="text-neutral-600 hover:text-neutral-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-neutral-900">Enquiry #{{ $enquiry->id }}</h1>
                    <p class="text-sm text-neutral-600 mt-1">{{ $enquiry->created_at->format('M d, Y h:i A') }}</p>
                </div>
            </div>
            <form action="{{ route('admin.enquiries.destroy', $enquiry) }}" method="POST"
                  onsubmit="return confirm('Delete this enquiry?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-secondary text-error-600 text-sm">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Delete
                </button>
            </form>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-xl text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-4">
            <!-- Original Message -->
            <div class="card">
                <div class="px-4 py-3 border-b border-neutral-200">
                    <h2 class="font-semibold text-neutral-900">{{ $enquiry->subject }}</h2>
                </div>
                <div class="p-4">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center shrink-0">
                            <span class="text-xs font-semibold text-primary-600">{{ strtoupper(substr($enquiry->name, 0, 1)) }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-sm font-medium text-neutral-900">{{ $enquiry->name }}</span>
                                <span class="text-xs text-neutral-600">{{ $enquiry->created_at->diffForHumans() }}</span>
                            </div>
                            <div class="text-sm text-neutral-700 leading-relaxed">
                                {!! nl2br(e($enquiry->message)) !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Update Status & Notes -->
            <div class="card">
                <div class="px-4 py-3 border-b border-neutral-200">
                    <h2 class="font-semibold text-neutral-900">Update Status</h2>
                </div>
                <form action="{{ route('admin.enquiries.status', $enquiry) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="p-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-1">Status</label>
                            <select name="status" class="w-full px-3 py-2 border border-neutral-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                <option value="new" @selected($enquiry->status === 'new')>New</option>
                                <option value="read" @selected($enquiry->status === 'read')>Read</option>
                                <option value="replied" @selected($enquiry->status === 'replied')>Replied</option>
                                <option value="closed" @selected($enquiry->status === 'closed')>Closed</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-1">Admin Notes <span class="text-neutral-600 font-normal">(internal only)</span></label>
                            <textarea name="admin_notes" rows="3" placeholder="Internal notes (not visible to customer)..."
                                      class="w-full px-3 py-2 border border-neutral-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">{{ old('admin_notes', $enquiry->admin_notes) }}</textarea>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="btn btn-secondary text-sm">Update Status</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Sender Details -->
            <div class="card">
                <div class="p-4 border-b border-neutral-200">
                    <h2 class="font-semibold text-neutral-900">Sender Details</h2>
                </div>
                <div class="p-4 space-y-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center shrink-0">
                            <span class="text-sm font-semibold text-primary-600">{{ strtoupper(substr($enquiry->name, 0, 1)) }}</span>
                        </div>
                        <div>
                            <p class="font-medium text-neutral-900">{{ $enquiry->name }}</p>
                            <p class="text-xs text-neutral-600">{{ $enquiry->email }}</p>
                        </div>
                    </div>

                    @if($enquiry->phone)
                        <div class="flex items-center gap-2 text-sm text-neutral-600">
                            <svg class="w-4 h-4 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            <a href="tel:{{ $enquiry->phone }}" class="hover:text-primary-600">{{ $enquiry->phone }}</a>
                        </div>
                    @endif

                    <div class="flex items-center gap-2 text-sm text-neutral-600">
                        <svg class="w-4 h-4 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <a href="mailto:{{ $enquiry->email }}" class="text-primary-600 hover:underline">{{ $enquiry->email }}</a>
                    </div>
                </div>
            </div>

            <!-- Status Info -->
            <div class="card">
                <div class="p-4 border-b border-neutral-200">
                    <h2 class="font-semibold text-neutral-900">Status</h2>
                </div>
                <div class="p-4 space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-neutral-600">Current Status</span>
                        @switch($enquiry->status)
                            @case('new')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-error-100 text-error-700">New</span>
                                @break
                            @case('read')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-info-100 text-info-700">Read</span>
                                @break
                            @case('replied')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-success-100 text-success-700">Replied</span>
                                @break
                            @case('closed')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-neutral-100 text-neutral-700">Closed</span>
                                @break
                        @endswitch
                    </div>
                    <div class="flex justify-between">
                        <span class="text-neutral-600">Received</span>
                        <span class="text-neutral-900">{{ $enquiry->created_at->format('M d, Y') }}</span>
                    </div>
                    @if($enquiry->read_at)
                        <div class="flex justify-between">
                            <span class="text-neutral-600">Read at</span>
                            <span class="text-neutral-900">{{ $enquiry->read_at->format('M d, Y h:i A') }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
