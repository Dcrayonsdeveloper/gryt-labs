<header class="h-16 bg-white border-b border-neutral-200 flex items-center justify-between px-6">
    <!-- Left side -->
    <div class="flex items-center gap-4">
        <!-- Mobile menu toggle -->
        <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden p-2 -ml-2 text-neutral-600 hover:text-neutral-900">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        <!-- Page title -->
        <h1 class="text-lg font-semibold text-neutral-900">{{ $title ?? 'Dashboard' }}</h1>
    </div>

    <!-- Right side -->
    <div class="flex items-center gap-4">
        <!-- Referral link quick copy -->
        <div x-data="{ copied: false }" class="hidden md:flex items-center gap-2">
            <span class="text-xs text-neutral-500">Your link:</span>
            <code class="text-xs bg-neutral-100 px-2 py-1 rounded text-neutral-700">{{ auth()->user()->affiliate?->getReferralUrl() }}</code>
            <button @click="navigator.clipboard.writeText('{{ auth()->user()->affiliate?->getReferralUrl() }}'); copied = true; setTimeout(() => copied = false, 2000)"
                    class="p-1.5 text-neutral-600 hover:text-primary-600 rounded transition-colors"
                    :title="copied ? 'Copied!' : 'Copy link'">
                <svg x-show="!copied" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
                <svg x-show="copied" x-cloak class="w-4 h-4 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </button>
        </div>

        <!-- Visit Store -->
        <a href="{{ url('/') }}" target="_blank" class="hidden md:block p-2 text-neutral-600 hover:text-neutral-900" title="Visit Store">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
            </svg>
        </a>

        <!-- User menu -->
        <div class="relative" x-data="{ open: false }">
            <button @click="open = !open" class="flex items-center gap-2">
                <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center">
                    <span class="text-sm font-medium text-primary-600">
                        {{ substr(auth()->user()->first_name ?? 'A', 0, 1) }}
                    </span>
                </div>
                <div class="hidden md:block text-left">
                    <div class="text-sm font-medium text-neutral-900">{{ auth()->user()->full_name }}</div>
                    <div class="text-xs text-neutral-600">Affiliate</div>
                </div>
                <svg class="w-4 h-4 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <div x-show="open" x-transition @click.away="open = false"
                 class="absolute right-0 mt-2 w-56 bg-white border border-neutral-200 rounded-lg shadow-lg z-50">
                <div class="px-4 py-3 border-b border-neutral-100">
                    <div class="text-sm font-medium text-neutral-900">{{ auth()->user()->full_name }}</div>
                    <div class="text-xs text-neutral-600">{{ auth()->user()->email }}</div>
                </div>
                <a href="{{ route('affiliate.settings.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-neutral-700 hover:bg-neutral-50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Settings
                </a>
                <a href="{{ url('/') }}" target="_blank" class="flex items-center gap-2 px-4 py-2 text-sm text-neutral-700 hover:bg-neutral-50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                    Visit Store
                </a>
                <div class="border-t border-neutral-100">
                    <form action="{{ route('affiliate.logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="flex items-center gap-2 w-full px-4 py-2 text-sm text-neutral-700 hover:bg-neutral-50">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
