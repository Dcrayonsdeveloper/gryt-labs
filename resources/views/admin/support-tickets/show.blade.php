<x-layouts.admin>
    <x-slot name="title">Ticket #{{ $supportTicket->id }}</x-slot>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.support-tickets.index') }}" class="text-neutral-600 hover:text-neutral-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-neutral-900">Ticket #{{ $supportTicket->id }}</h1>
                    <p class="text-sm text-neutral-600 mt-1">{{ $supportTicket->created_at->format('M d, Y h:i A') }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <form action="{{ route('admin.support-tickets.status', $supportTicket) }}" method="POST" class="flex items-center gap-2">
                    @csrf
                    @method('PUT')
                    <select name="status" class="px-3 py-1.5 border border-neutral-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        <option value="open" @selected($supportTicket->status === 'open')>Open</option>
                        <option value="answered" @selected($supportTicket->status === 'answered')>Answered</option>
                        <option value="closed" @selected($supportTicket->status === 'closed')>Closed</option>
                    </select>
                    <button type="submit" class="btn btn-secondary text-sm">Update</button>
                </form>
                <form action="{{ route('admin.support-tickets.destroy', $supportTicket) }}" method="POST"
                      onsubmit="return confirm('Delete this ticket?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-secondary text-error-600 text-sm">Delete</button>
                </form>
            </div>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Conversation -->
        <div class="lg:col-span-2 space-y-4">
            <!-- Original Message -->
            <div class="card">
                <div class="p-4 border-b border-neutral-200">
                    <h2 class="font-semibold text-neutral-900">{{ $supportTicket->subject }}</h2>
                </div>
                <div class="p-4">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center shrink-0">
                            <span class="text-xs font-semibold text-primary-600">{{ strtoupper(substr($supportTicket->user->first_name, 0, 1)) }}</span>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-sm font-medium text-neutral-900">{{ $supportTicket->user->full_name }}</span>
                                <span class="text-xs text-neutral-600">{{ $supportTicket->created_at->diffForHumans() }}</span>
                            </div>
                            <div class="text-sm text-neutral-700 leading-relaxed">
                                {!! nl2br(e($supportTicket->message)) !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Replies -->
            @foreach($supportTicket->replies as $reply)
                <div class="card {{ $reply->is_admin ? 'border-l-4 border-l-primary-500' : '' }}">
                    <div class="p-4">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 {{ $reply->is_admin ? 'bg-primary-600' : 'bg-primary-100' }}">
                                <span class="text-xs font-semibold {{ $reply->is_admin ? 'text-white' : 'text-primary-600' }}">
                                    {{ strtoupper(substr($reply->user->first_name ?? 'A', 0, 1)) }}
                                </span>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-sm font-medium text-neutral-900">{{ $reply->user->full_name ?? 'Admin' }}</span>
                                    @if($reply->is_admin)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-primary-100 text-primary-700">Staff</span>
                                    @endif
                                    <span class="text-xs text-neutral-600">{{ $reply->created_at->diffForHumans() }}</span>
                                </div>
                                <div class="text-sm text-neutral-700 leading-relaxed">
                                    {!! nl2br(e($reply->message)) !!}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            <!-- Admin Reply Form -->
            <div class="card">
                <div class="p-4 border-b border-neutral-200">
                    <h3 class="font-semibold text-neutral-900">Reply to Customer</h3>
                </div>
                <form action="{{ route('admin.support-tickets.reply', $supportTicket) }}" method="POST">
                    @csrf
                    <div class="p-4 space-y-3">
                        <textarea name="message" rows="4" required
                                  class="w-full px-3 py-2 border border-neutral-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                  placeholder="Type your reply to the customer...">{{ old('message') }}</textarea>
                        @error('message')
                            <p class="text-sm text-error-600">{{ $message }}</p>
                        @enderror
                        <div class="flex justify-end">
                            <button type="submit" class="btn btn-primary text-sm">Send Reply</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Customer Details -->
            <div class="card">
                <div class="p-4 border-b border-neutral-200">
                    <h2 class="font-semibold text-neutral-900">Customer</h2>
                </div>
                <div class="p-4 space-y-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center shrink-0">
                            <span class="text-sm font-semibold text-primary-600">{{ strtoupper(substr($supportTicket->user->first_name, 0, 1)) }}</span>
                        </div>
                        <div>
                            <p class="font-medium text-neutral-900">{{ $supportTicket->user->full_name }}</p>
                            <p class="text-xs text-neutral-600">{{ $supportTicket->user->email }}</p>
                        </div>
                    </div>
                    <a href="{{ route('admin.customers.show', $supportTicket->user) }}" class="text-xs text-primary-600 hover:underline">View Customer Profile</a>
                </div>
            </div>

            <!-- Ticket Info -->
            <div class="card">
                <div class="p-4 border-b border-neutral-200">
                    <h2 class="font-semibold text-neutral-900">Ticket Details</h2>
                </div>
                <div class="p-4 space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-neutral-600">Status</span>
                        @switch($supportTicket->status)
                            @case('open')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-warning-100 text-warning-700">Open</span>
                                @break
                            @case('answered')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-success-100 text-success-700">Answered</span>
                                @break
                            @case('closed')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-neutral-100 text-neutral-600">Closed</span>
                                @break
                        @endswitch
                    </div>
                    <div class="flex justify-between">
                        <span class="text-neutral-600">Priority</span>
                        @switch($supportTicket->priority)
                            @case('high')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-error-100 text-error-700">High</span>
                                @break
                            @case('normal')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-info-100 text-info-700">Normal</span>
                                @break
                            @case('low')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-neutral-100 text-neutral-600">Low</span>
                                @break
                        @endswitch
                    </div>
                    <div class="flex justify-between">
                        <span class="text-neutral-600">Category</span>
                        <span class="text-neutral-900 capitalize">{{ $supportTicket->category }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-neutral-600">Created</span>
                        <span class="text-neutral-900">{{ $supportTicket->created_at->format('M d, Y') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-neutral-600">Replies</span>
                        <span class="text-neutral-900">{{ $supportTicket->replies->count() }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
