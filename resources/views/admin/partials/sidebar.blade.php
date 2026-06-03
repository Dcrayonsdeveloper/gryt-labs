<!-- Mobile sidebar backdrop -->
<div x-show="sidebarOpen" x-transition:enter="transition-opacity ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" @click="sidebarOpen = false" class="fixed inset-0 z-20 lg:hidden" style="background:rgba(0,0,0,.5)"></div>

<!-- Sidebar -->
<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-30 w-60 text-neutral-800 transform transition-transform duration-200 ease-in-out lg:translate-x-0 lg:static lg:inset-0 flex flex-col" style="background:#ebebeb">

    @php $user = auth('admin')->user(); @endphp

    <!-- Logo (dark bar matching header) -->
    <div class="flex items-center h-14 px-4" style="background:#1a1a1a">
        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
            <x-application-logo class="h-5 w-auto brightness-0 invert" />
        </a>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto px-2 pt-2 pb-4 text-sm" style="scrollbar-width:thin;scrollbar-color:#ccc transparent">

        <style>
            .sb-link { transition: background .15s, color .15s; font-weight: 500; }
            .sb-link:hover { background: #d6d6d6; }
            .sb-link.active { background: #cfcfcf; color: #1a1a1a; font-weight: 600; }
            .sb-link.active:hover { background: #c4c4c4; }
            .sb-sub:hover { background: #ddd; }
            .sb-sub.active { font-weight: 600; }
        </style>

        @php
            // Helper: check if route matches
            $isActive = fn($patterns) => request()->routeIs(...(array)$patterns);
            $linkClass = 'sb-link flex items-center gap-3 px-2 py-1.5 rounded-lg mb-px';
            $activeClass = 'active';
            $normalClass = '';
            $subClass = 'sb-sub block px-2 py-1 rounded-md text-xs';
        @endphp

        <!-- Home -->
        @if($user->canAccessSection('dashboard'))
        <a href="{{ route('admin.dashboard') }}" class="{{ $linkClass }} {{ $isActive('admin.dashboard') ? $activeClass : $normalClass }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>
            Home
        </a>
        @endif

        <!-- Orders -->
        @if($user->canAccessSection('orders'))
        @php $pendingOrders = \App\Models\Order::where('status', 'pending')->count(); @endphp
        <div x-data="{ open: {{ $isActive(['admin.orders.*','admin.draft-orders.*','admin.returns.*','admin.credit-notes.*','admin.abandoned-checkouts']) ? 'true' : 'false' }} }">
            <button @click="open = !open" class="{{ $linkClass }} w-full text-left {{ $isActive(['admin.orders.*','admin.draft-orders.*','admin.returns.*','admin.credit-notes.*','admin.abandoned-checkouts']) ? $activeClass : $normalClass }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                Orders
                @if($pendingOrders > 0)
                <span class="ml-auto text-xs rounded-full px-1.5" style="background:#ddd;color:#666">{{ $pendingOrders }}</span>
                @endif
            </button>
            <div x-show="open" x-cloak class="ml-8 space-y-px mb-1">
                <a href="{{ route('admin.orders.index') }}" class="{{ $subClass }} {{ $isActive('admin.orders.*') ? 'text-neutral-800' : 'text-neutral-700 hover:text-neutral-900' }}">All Orders</a>
                <a href="{{ route('admin.draft-orders.index') }}" class="{{ $subClass }} {{ $isActive('admin.draft-orders.*') ? 'text-neutral-800' : 'text-neutral-700 hover:text-neutral-900' }}">Draft Orders</a>
                <a href="{{ route('admin.abandoned-checkouts') }}" class="{{ $subClass }} {{ $isActive('admin.abandoned-checkouts') ? 'text-neutral-800' : 'text-neutral-700 hover:text-neutral-900' }}">Abandoned Checkouts</a>
                <a href="{{ route('admin.returns.index') }}" class="{{ $subClass }} {{ $isActive('admin.returns.*') ? 'text-neutral-800' : 'text-neutral-700 hover:text-neutral-900' }}">Returns</a>
                <a href="{{ route('admin.credit-notes.index') }}" class="{{ $subClass }} {{ $isActive('admin.credit-notes.*') ? 'text-neutral-800' : 'text-neutral-700 hover:text-neutral-900' }}">Credit Notes</a>
            </div>
        </div>
        @endif

        <!-- Delivery -->
        @if($user->canAccessSection('orders'))
        <a href="{{ route('admin.delivery.index') }}" class="{{ $linkClass }} {{ $isActive(['admin.delivery.*']) ? $activeClass : $normalClass }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/></svg>
            Delivery
        </a>
        @endif

        <!-- Products -->
        @if($user->canAccessSection('catalog'))
        <div x-data="{ open: {{ $isActive(['admin.products.*','admin.categories.*','admin.brands.*','admin.attributes.*','admin.inventory.*']) ? 'true' : 'false' }} }">
            <button @click="open = !open" class="{{ $linkClass }} w-full text-left {{ $isActive(['admin.products.*','admin.categories.*','admin.brands.*','admin.attributes.*','admin.inventory.*']) ? $activeClass : $normalClass }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z"/></svg>
                Products
            </button>
            <div x-show="open" x-cloak class="ml-8 space-y-px mb-1">
                <a href="{{ route('admin.products.index') }}" class="{{ $subClass }} {{ $isActive('admin.products.*') ? 'text-neutral-800' : 'text-neutral-700 hover:text-neutral-900' }}">All Products</a>
                <a href="{{ route('admin.categories.index') }}" class="{{ $subClass }} {{ $isActive('admin.categories.*') ? 'text-neutral-800' : 'text-neutral-700 hover:text-neutral-900' }}">Collections</a>
                <a href="{{ route('admin.inventory.index') }}" class="{{ $subClass }} {{ $isActive('admin.inventory.*') ? 'text-neutral-800' : 'text-neutral-700 hover:text-neutral-900' }}">Inventory</a>
                <a href="{{ route('admin.brands.index') }}" class="{{ $subClass }} {{ $isActive('admin.brands.*') ? 'text-neutral-800' : 'text-neutral-700 hover:text-neutral-900' }}">Brands</a>
                <a href="{{ route('admin.attributes.index') }}" class="{{ $subClass }} {{ $isActive('admin.attributes.*') ? 'text-neutral-800' : 'text-neutral-700 hover:text-neutral-900' }}">Attributes</a>
            </div>
        </div>
        @endif

        <!-- Customers -->
        @if($user->canAccessSection('customers'))
        <a href="{{ route('admin.customers.index') }}" class="{{ $linkClass }} {{ $isActive('admin.customers.*') ? $activeClass : $normalClass }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg>
            Customers
        </a>
        @endif

        <!-- Marketing -->
        @if($user->canAccessSection('marketing'))
        <a href="{{ route('admin.marketing.hub') }}" class="{{ $linkClass }} {{ $isActive(['admin.marketing.*','admin.coupons.*','admin.flash-sales.*','admin.banners.*','admin.newsletter.*']) ? $activeClass : $normalClass }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 0 1-1.44-4.282m3.102.069a18.03 18.03 0 0 1-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 0 1 8.835 2.535M10.34 6.66a23.847 23.847 0 0 0 8.835-2.535m0 0A23.74 23.74 0 0 0 18.795 3m.38 1.125a23.91 23.91 0 0 1 1.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 0 0 1.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 0 1 0 3.46"/></svg>
            Marketing
        </a>
        @endif

        <!-- Social Calendar -->
        @if($user->canAccessSection('marketing'))
        <a href="{{ route('admin.social-calendar.index') }}" class="{{ $linkClass }} {{ $isActive(['admin.social-calendar.*']) ? $activeClass : $normalClass }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/></svg>
            Social Calendar
        </a>
        @endif

        <!-- Affiliates -->
        @if($user->canAccessSection('marketing'))
        <a href="{{ route('admin.affiliates.index') }}" class="{{ $linkClass }} {{ $isActive(['admin.affiliates.*']) ? $activeClass : $normalClass }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z"/></svg>
            Affiliates
        </a>
        @endif

        <!-- Discounts -->
        @if($user->canAccessSection('marketing'))
        <a href="{{ route('admin.coupons.index') }}" class="{{ $linkClass }} {{ $isActive(['admin.coupons.*','admin.flash-sales.*']) ? $activeClass : $normalClass }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z"/></svg>
            Discounts
        </a>
        @endif

        <!-- Content -->
        @if($user->canAccessSection('content'))
        <div x-data="{ open: {{ $isActive(['admin.pages.*','admin.blog-posts.*','admin.reviews.*']) ? 'true' : 'false' }} }">
            <button @click="open = !open" class="{{ $linkClass }} w-full text-left {{ $isActive(['admin.pages.*','admin.blog-posts.*','admin.reviews.*']) ? $activeClass : $normalClass }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                Content
            </button>
            <div x-show="open" x-cloak class="ml-8 space-y-px mb-1">
                <a href="{{ route('admin.pages.index') }}" class="{{ $subClass }} {{ $isActive('admin.pages.*') ? 'text-neutral-800' : 'text-neutral-700 hover:text-neutral-900' }}">Pages</a>
                <a href="{{ route('admin.blog-posts.index') }}" class="{{ $subClass }} {{ $isActive('admin.blog-posts.*') ? 'text-neutral-800' : 'text-neutral-700 hover:text-neutral-900' }}">Blog Posts</a>
                <a href="{{ route('admin.reviews.index') }}" class="{{ $subClass }} {{ $isActive('admin.reviews.*') ? 'text-neutral-800' : 'text-neutral-700 hover:text-neutral-900' }}">Reviews</a>
            </div>
        </div>
        @endif

        <!-- Online Store -->
        @if($user->canAccessSection('storefront'))
        <div x-data="{ open: {{ $isActive(['admin.homepage.*','admin.navigation*','admin.theme.*']) ? 'true' : 'false' }} }">
            <button @click="open = !open" class="{{ $linkClass }} w-full text-left {{ $isActive(['admin.homepage.*','admin.navigation*','admin.theme.*']) ? $activeClass : $normalClass }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l.134-.133L4.318 3.44A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72M6.75 18h3.75a.75.75 0 0 0 .75-.75V13.5a.75.75 0 0 0-.75-.75H6.75a.75.75 0 0 0-.75.75v3.75c0 .414.336.75.75.75Z"/></svg>
                Online Store
            </button>
            <div x-show="open" x-cloak class="ml-8 space-y-px mb-1">
                <a href="{{ route('admin.homepage.index') }}" class="{{ $subClass }} {{ $isActive('admin.homepage.index') ? 'text-neutral-800' : 'text-neutral-700 hover:text-neutral-900' }}">Pages</a>
                <a href="{{ route('admin.homepage.navigation') }}" class="{{ $subClass }} {{ $isActive('admin.homepage.navigation') ? 'text-neutral-800' : 'text-neutral-700 hover:text-neutral-900' }}">Navigation</a>
                <a href="{{ route('admin.theme.index') }}" class="{{ $subClass }} {{ $isActive('admin.theme.*') ? 'text-neutral-800' : 'text-neutral-700 hover:text-neutral-900' }}">Theme</a>
            </div>
        </div>
        @endif

        <!-- Analytics -->
        @if($user->canAccessSection('reports'))
        <a href="{{ route('admin.reports.analytics') }}" class="{{ $linkClass }} {{ $isActive('admin.reports.analytics') ? $activeClass : $normalClass }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/></svg>
            Analytics
        </a>
        @endif

        <!-- Support -->
        @php
            $unreadEnquiries = \App\Models\Enquiry::where('status', 'new')->count();
            $openTickets = \App\Models\SupportTicket::where('status', 'open')->count();
            $supportBadge = $unreadEnquiries + $openTickets;
        @endphp
        <div x-data="{ open: {{ $isActive(['admin.enquiries.*','admin.support-tickets.*','admin.fraud.*']) ? 'true' : 'false' }} }">
            <button @click="open = !open" class="{{ $linkClass }} w-full text-left {{ $isActive(['admin.enquiries.*','admin.support-tickets.*','admin.fraud.*']) ? $activeClass : $normalClass }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 0 1 .778-.332 48.294 48.294 0 0 0 5.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z"/></svg>
                Support
                @if($supportBadge > 0)
                <span class="ml-auto text-xs rounded-full px-1.5" style="background:#e74c3c;color:#fff">{{ $supportBadge }}</span>
                @endif
            </button>
            <div x-show="open" x-cloak class="ml-8 space-y-px mb-1">
                <a href="{{ route('admin.enquiries.index') }}" class="{{ $subClass }} {{ $isActive('admin.enquiries.*') ? 'text-neutral-800' : 'text-neutral-700 hover:text-neutral-900' }}">Enquiries</a>
                <a href="{{ route('admin.support-tickets.index') }}" class="{{ $subClass }} {{ $isActive('admin.support-tickets.*') ? 'text-neutral-800' : 'text-neutral-700 hover:text-neutral-900' }}">Tickets</a>
                <a href="{{ route('admin.fraud.index') }}" class="{{ $subClass }} {{ $isActive('admin.fraud.*') ? 'text-neutral-800' : 'text-neutral-700 hover:text-neutral-900' }}">Fraud Review</a>
            </div>
        </div>

        <!-- Sellers / Staff -->
        @if($user->canAccessSection('sellers') || $user->canAccessSection('staff') || $user->canAccessSection('delivery_partners'))
        <div class="mt-2 pt-2" style="border-top:1px solid #ddd">
            @if($user->canAccessSection('sellers'))
            <a href="{{ route('admin.sellers.index') }}" class="{{ $subClass }} mb-px {{ $isActive('admin.sellers.*') ? 'active' : '' }}">Sellers</a>
            @endif
            @if($user->canAccessSection('staff'))
            <a href="{{ route('admin.staff.index') }}" class="{{ $subClass }} mb-px {{ $isActive('admin.staff.*') ? 'active' : '' }}">Staff</a>
            @endif
            @if($user->canAccessSection('delivery_partners'))
            <a href="{{ route('admin.delivery-partners.index') }}" class="{{ $subClass }} mb-px {{ $isActive('admin.delivery-partners.*') ? 'active' : '' }}">Delivery Partners</a>
            @endif
        </div>
        @endif
    </nav>

    <!-- Settings & System (pinned bottom like Shopify) -->
    @if($user->canAccessSection('settings'))
    <div class="px-2 pb-3 pt-1 text-sm" style="border-top:1px solid #ddd">
        <a href="{{ route('admin.settings.general') }}" class="{{ $linkClass }} {{ $isActive('admin.settings.*') ? $activeClass : $normalClass }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 0 1 0 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 0 1 0-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
            Settings
        </a>
    </div>
    @endif
</aside>
