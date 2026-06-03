<x-layouts.admin>
    <x-slot name="title">Notifications</x-slot>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900">Notifications</h1>
            <p class="text-neutral-600">All system notifications</p>
        </div>
    </div>

    @if($notifications->total() > 0)
        <div class="card mb-4">
            <div class="px-5 py-3 border-b border-neutral-200">
                {{ $notifications->links('vendor.pagination.info-bar') }}
            </div>
        </div>
    @endif

    <div class="space-y-3">
        @forelse($notifications as $notification)
            <div class="card {{ !$notification->is_read ? 'border-l-4 border-l-primary-500' : '' }}">
                <div class="p-4 flex items-start justify-between">
                    <div class="flex items-start gap-3 flex-1">
                        <div class="mt-0.5">
                            @switch($notification->type)
                                @case('order')
                                    <div class="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center">
                                        <svg class="w-4 h-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                        </svg>
                                    </div>
                                    @break
                                @case('payment')
                                    <div class="w-8 h-8 rounded-full bg-success-100 flex items-center justify-center">
                                        <svg class="w-4 h-4 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    @break
                                @case('review')
                                    <div class="w-8 h-8 rounded-full bg-yellow-100 flex items-center justify-center">
                                        <svg class="w-4 h-4 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                        </svg>
                                    </div>
                                    @break
                                @case('stock')
                                    <div class="w-8 h-8 rounded-full bg-danger-100 flex items-center justify-center">
                                        <svg class="w-4 h-4 text-danger-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                        </svg>
                                    </div>
                                    @break
                                @default
                                    <div class="w-8 h-8 rounded-full bg-neutral-100 flex items-center justify-center">
                                        <svg class="w-4 h-4 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                        </svg>
                                    </div>
                            @endswitch
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <p class="font-medium text-neutral-900">{{ $notification->title }}</p>
                                @if(!$notification->is_read)
                                    <span class="w-2 h-2 rounded-full bg-primary-500"></span>
                                @endif
                            </div>
                            @if($notification->content)
                                <p class="text-sm text-neutral-600 mt-0.5">{{ $notification->content }}</p>
                            @endif
                            <div class="flex items-center gap-3 mt-1.5 text-xs text-neutral-600">
                                <span>{{ $notification->created_at->diffForHumans() }}</span>
                                <span class="badge badge-info">{{ $notification->channel }}</span>
                                <span class="badge badge-warning">{{ $notification->type }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="card">
                <div class="p-12 text-center text-neutral-600">
                    No notifications yet.
                </div>
            </div>
        @endforelse
    </div>

    @if($notifications->hasPages())
        <div class="mt-4">
            {{ $notifications->links() }}
        </div>
    @endif
</x-layouts.admin>
