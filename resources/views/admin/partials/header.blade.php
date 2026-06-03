<header class="h-14 flex items-center justify-between px-4 gap-4 bg-neutral-900">
    <!-- Left: Mobile menu -->
    <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden p-2 -ml-2 text-neutral-400 hover:text-white" aria-label="Toggle menu">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>

    <!-- Center: Search bar (Shopify style) -->
    <div class="flex-1 max-w-xl mx-auto" x-data="adminSearch()" @keydown.ctrl.k.window.prevent="openSearch()">
        <button @click="openSearch()"
                class="w-full flex items-center gap-2 px-4 py-2 rounded-lg text-sm transition-colors bg-neutral-800 text-neutral-400">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <span class="flex-1 text-left">Search</span>
            <span class="hidden sm:flex items-center gap-0.5 text-xs text-neutral-400">
                <kbd class="px-1 py-0.5 rounded text-xs bg-neutral-700 text-neutral-400">Ctrl</kbd>
                <kbd class="px-1 py-0.5 rounded text-xs bg-neutral-700 text-neutral-400">K</kbd>
            </span>
        </button>

        <!-- Search Modal (Shopify-style centered overlay) -->
        <div x-cloak x-show="open" class="fixed inset-0 z-50 flex items-start justify-center pt-20" @keydown.escape.window="open = false">
            <div x-show="open" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" @click="open = false" class="absolute inset-0 bg-black/40"></div>
            <div x-show="open" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="relative w-full max-w-[680px] bg-white overflow-hidden rounded-2xl shadow-2xl">
                <!-- Search input -->
                <div class="flex items-center gap-3 px-5 py-3.5 border-b border-gray-200">
                    <svg class="w-5 h-5 text-neutral-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" x-ref="searchInput" x-model="query" @keydown.enter.prevent="search()" placeholder="Search and press Enter" class="flex-1 text-base border-0 outline-none bg-transparent text-neutral-900 placeholder-neutral-400">
                    <button @click="open = false" class="p-1 text-neutral-400 hover:text-neutral-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Category tabs -->
                <div class="flex items-center gap-1 px-5 py-2 border-b border-gray-100">
                    <template x-for="tab in tabs" :key="tab.key">
                        <button @click="section = tab.key; search()"
                                class="px-3 py-1 rounded-lg text-sm font-medium transition-colors"
                                :class="section === tab.key ? 'bg-gray-200 text-gray-900' : 'text-gray-600'"
                                x-text="tab.label"></button>
                    </template>
                </div>

                <!-- Results -->
                <div class="px-5 py-4 min-h-30 max-h-100 overflow-y-auto">
                    <template x-if="!query && !loading">
                        <div class="flex flex-col items-center py-6 text-center">
                            <svg class="w-10 h-10 text-neutral-300 mb-3" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <p class="text-sm text-neutral-500">Find anything in {{ config('app.name') }}</p>
                        </div>
                    </template>
                    <template x-if="loading">
                        <div class="flex items-center justify-center py-8">
                            <svg class="animate-spin h-5 w-5 text-neutral-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        </div>
                    </template>
                    <template x-if="query && !loading && results.length === 0">
                        <div class="py-6 text-center">
                            <p class="text-sm text-neutral-500">No results for "<span x-text="query" class="font-medium text-neutral-700"></span>"</p>
                        </div>
                    </template>
                    <template x-if="results.length > 0">
                        <div class="space-y-1">
                            <template x-for="item in results" :key="item.url">
                                <a :href="item.url" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-neutral-50 transition-colors">
                                    <template x-if="item.image">
                                        <img :src="item.image" class="w-8 h-8 rounded object-cover shrink-0 border border-gray-200">
                                    </template>
                                    <template x-if="!item.image">
                                        <div class="w-8 h-8 rounded flex items-center justify-center shrink-0 bg-gray-100">
                                            <svg class="w-4 h-4 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                        </div>
                                    </template>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm text-neutral-900 truncate" :title="item.title" x-text="item.title"></p>
                                        <p class="text-xs text-neutral-400" x-text="item.subtitle"></p>
                                    </div>
                                </a>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Attention badges (orders / low stock / reviews needing work) -->
    @php
        $attention = $adminAttentionCounts ?? ['orders' => 0, 'low_stock' => 0, 'reviews' => 0];
    @endphp
    @if(($attention['orders'] ?? 0) + ($attention['low_stock'] ?? 0) + ($attention['reviews'] ?? 0) > 0)
    <div class="hidden md:flex items-center gap-1.5 shrink-0" aria-label="Items needing attention">
        @if(!empty($attention['orders']))
        <a href="{{ route('admin.orders.index', ['tab' => 'needs_attention']) }}"
           title="Orders awaiting payment confirmation"
           class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium bg-red-50 text-red-700 hover:bg-red-100 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            <span>{{ $attention['orders'] }}</span>
            <span class="hidden lg:inline">Orders</span>
        </a>
        @endif
        @if(!empty($attention['low_stock']))
        <a href="{{ route('admin.inventory.low-stock') }}"
           title="Products low on stock"
           class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium bg-amber-50 text-amber-700 hover:bg-amber-100 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <span>{{ $attention['low_stock'] }}</span>
            <span class="hidden lg:inline">Low stock</span>
        </a>
        @endif
        @if(!empty($attention['reviews']))
        <a href="{{ route('admin.reviews.pending') }}"
           title="Reviews pending moderation"
           class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium bg-neutral-200 text-neutral-700 hover:bg-neutral-300 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.967a1 1 0 00.95.69h4.175c.969 0 1.371 1.24.588 1.81l-3.38 2.455a1 1 0 00-.364 1.118l1.287 3.967c.3.922-.755 1.688-1.54 1.118l-3.38-2.454a1 1 0 00-1.175 0l-3.38 2.454c-.784.57-1.838-.196-1.539-1.118l1.287-3.967a1 1 0 00-.364-1.118L2.05 9.394c-.783-.57-.38-1.81.588-1.81h4.175a1 1 0 00.95-.69l1.286-3.967z"/></svg>
            <span>{{ $attention['reviews'] }}</span>
            <span class="hidden lg:inline">Reviews</span>
        </a>
        @endif
    </div>
    @endif

    <!-- Right: Notifications + User -->
    <div class="flex items-center gap-1">
        <!-- Notifications -->
        @php
            $adminUser = auth('admin')->user();
            $unreadNotifications = \App\Models\Notification::where('user_id', $adminUser->id)->unread()->latest()->limit(5)->get();
            $unreadCount = \App\Models\Notification::where('user_id', $adminUser->id)->unread()->count();
        @endphp
        <div class="relative" x-data="{ open: false }">
            <button @click="open = !open" class="relative p-2 text-neutral-400 hover:text-white rounded-lg" aria-label="Notifications">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/></svg>
                @if($unreadCount > 0)
                <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-red-500"></span>
                @endif
            </button>
            <div x-cloak x-show="open" x-transition @click.away="open = false" class="absolute right-0 mt-2 w-80 bg-white border border-neutral-200 rounded-xl shadow-xl z-50">
                <div class="px-4 py-3 flex items-center justify-between border-b border-gray-200">
                    <h3 class="font-semibold text-neutral-900 text-sm">Notifications</h3>
                    @if($unreadCount > 0)
                    <span class="text-xs font-medium text-blue-700">{{ $unreadCount }} new</span>
                    @endif
                </div>
                <div class="max-h-80 overflow-y-auto">
                    @forelse($unreadNotifications as $notification)
                    <a href="{{ route('admin.notifications.read', $notification) }}" class="block px-4 py-3 hover:bg-neutral-50 border-b border-gray-100 last:border-b-0">
                        <p class="text-sm font-medium text-neutral-900">{{ $notification->title }}</p>
                        <p class="text-xs text-neutral-500 mt-0.5 truncate" title="{{ $notification->content }}">{{ $notification->content }}</p>
                        <p class="text-xs text-neutral-400 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                    </a>
                    @empty
                    <div class="p-4 text-center text-sm text-neutral-400">No new notifications</div>
                    @endforelse
                </div>
                <div class="px-4 py-2.5 border-t border-gray-200">
                    <a href="{{ route('admin.notifications') }}" class="text-xs font-medium text-blue-700">View all</a>
                </div>
            </div>
        </div>

        <!-- User menu -->
        <div class="relative" x-data="{ open: false }">
            <button @click="open = !open" class="flex items-center gap-1.5" aria-label="User menu">
                <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-semibold text-white bg-green-600">
                    {{ strtoupper(substr(auth('admin')->user()->full_name ?? auth('admin')->user()->email ?? 'A', 0, 1)) }}
                </div>
                <svg class="w-3.5 h-3.5 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-cloak x-show="open" x-transition @click.away="open = false" class="absolute right-0 mt-2 w-48 bg-white border border-neutral-200 rounded-xl shadow-xl z-50">
                <div class="px-4 py-3 border-b border-gray-200">
                    <div class="text-sm font-medium text-neutral-900">{{ auth('admin')->user()->full_name ?? 'Admin' }}</div>
                    <div class="text-xs text-neutral-500">{{ auth('admin')->user()->email }}</div>
                </div>
                <a href="{{ route('admin.profile') }}" class="block px-4 py-2 text-sm text-neutral-700 hover:bg-neutral-50">Profile</a>
                <a href="{{ url('/') }}" target="_blank" class="block px-4 py-2 text-sm text-neutral-700 hover:bg-neutral-50">View Store</a>
                <form action="{{ route('admin.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full text-left px-4 py-2 text-sm text-neutral-700 hover:bg-neutral-50 border-t border-gray-200">Logout</button>
                </form>
            </div>
        </div>
    </div>
</header>

<script>
function adminSearch() {
    return {
        open: false,
        query: '',
        section: 'products',
        loading: false,
        results: [],
        tabs: [
            { key: 'products', label: 'Products' },
            { key: 'orders', label: 'Orders' },
            { key: 'customers', label: 'Customers' },
        ],
        openSearch() {
            this.open = true;
            this.$nextTick(() => this.$refs.searchInput.focus());
        },
        async search() {
            if (!this.query.trim()) { this.results = []; return; }
            this.loading = true;
            try {
                const prefix = document.querySelector('meta[name="admin-prefix"]')?.content || '/admin';
                const res = await fetch(`${prefix}/search/${this.section}?search=${encodeURIComponent(this.query)}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) { this.results = []; this.loading = false; return; }
                const data = await res.json();
                this.results = (data.data || data || []).slice(0, 8).map(item => {
                    if (this.section === 'products') {
                        return { title: item.name, subtitle: '₹' + item.price, image: item.primary_image_url || null, url: `${prefix}/products/${item.id}/edit` };
                    } else if (this.section === 'orders') {
                        return { title: item.order_number, subtitle: '₹' + item.total, image: null, url: `${prefix}/orders/${item.id}` };
                    } else {
                        return { title: (item.first_name || '') + ' ' + (item.last_name || ''), subtitle: item.email, image: null, url: `${prefix}/customers/${item.id}` };
                    }
                });
            } catch(e) { this.results = []; }
            this.loading = false;
        }
    };
}
</script>
