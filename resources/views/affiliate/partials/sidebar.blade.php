<!-- Mobile sidebar backdrop -->
<div x-show="sidebarOpen"
     x-transition:enter="transition-opacity ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @click="sidebarOpen = false"
     class="fixed inset-0 bg-black/50 z-20 lg:hidden"></div>

<!-- Sidebar -->
<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
       class="fixed inset-y-0 left-0 z-30 w-64 bg-white border-r border-neutral-200 transform transition-transform duration-200 ease-in-out lg:translate-x-0 lg:static lg:inset-0">

    <!-- Logo -->
    <div class="flex items-center gap-3 h-16 px-6 border-b border-neutral-200">
        <x-application-logo class="w-8 h-8" />
        <span class="font-bold text-lg text-neutral-900">Affiliate Portal</span>
    </div>

    <!-- Affiliate info -->
    <div class="p-4 border-b border-neutral-200">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center">
                <span class="font-medium text-primary-600">{{ substr(auth()->user()->first_name ?? 'A', 0, 1) }}</span>
            </div>
            <div>
                <div class="font-medium text-neutral-900 text-sm">{{ auth()->user()->full_name }}</div>
                <div class="text-xs {{ auth()->user()->affiliate?->isApproved() ? 'text-success-600' : 'text-warning-600' }}">
                    {{ auth()->user()->affiliate?->isApproved() ? 'Approved Affiliate' : ucfirst(auth()->user()->affiliate?->status ?? 'Pending') }}
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="p-4 space-y-1 overflow-y-auto h-[calc(100vh-8rem)]">
        <!-- Dashboard -->
        <a href="{{ route('affiliate.dashboard') }}"
           class="flex items-center gap-3 px-3 py-2 rounded-lg {{ request()->routeIs('affiliate.dashboard') ? 'bg-primary-50 text-primary-600' : 'text-neutral-700 hover:bg-neutral-100' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
            </svg>
            Dashboard
        </a>

        <!-- Earnings Section -->
        <div class="pt-4">
            <p class="px-3 text-xs font-semibold text-neutral-600 uppercase tracking-wider mb-2">Earnings</p>
            <a href="{{ route('affiliate.commissions.index') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg {{ request()->routeIs('affiliate.commissions.*') ? 'bg-primary-50 text-primary-600' : 'text-neutral-700 hover:bg-neutral-100' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Commissions
            </a>
            <a href="{{ route('affiliate.redemptions.index') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg {{ request()->routeIs('affiliate.redemptions.*') ? 'bg-primary-50 text-primary-600' : 'text-neutral-700 hover:bg-neutral-100' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
                Redemptions
            </a>
        </div>

        <!-- Settings Section -->
        <div class="pt-4">
            <p class="px-3 text-xs font-semibold text-neutral-600 uppercase tracking-wider mb-2">Account</p>
            <a href="{{ route('affiliate.settings.index') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg {{ request()->routeIs('affiliate.settings.*') ? 'bg-primary-50 text-primary-600' : 'text-neutral-700 hover:bg-neutral-100' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Settings
            </a>
        </div>
    </nav>
</aside>
