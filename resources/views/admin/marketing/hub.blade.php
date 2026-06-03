<x-layouts.admin>
    <x-slot name="title">Marketing Hub</x-slot>

    <div class="space-y-6">
        {{-- Header --}}
        <div>
            <h1 class="text-2xl font-bold text-neutral-900">Integrations</h1>
            <p class="text-sm text-neutral-500 mt-1">Manage your connected platforms and marketing channels.</p>
        </div>

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 text-sm px-4 py-3 rounded-lg">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-800 text-sm px-4 py-3 rounded-lg">{{ session('error') }}</div>
        @endif

        {{-- Meta Platforms Card --}}
        <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
            {{-- Card Header --}}
            <div class="flex items-center justify-between px-6 py-5 border-b border-neutral-100">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full flex items-center justify-center bg-[#1877F2]">
                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-neutral-900">Meta Platforms</h2>
                        <p class="text-xs text-neutral-500">One login connects Facebook Page + Instagram + WhatsApp. Permissions: messaging, lead forms, content publishing, stories.</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0 ml-4">
                    @if($connections['facebook']['connected'] || $connections['instagram']['connected'])
                        <form action="{{ route('admin.marketing.meta.disconnect') }}" method="POST"
                              x-on:submit="return confirm('Disconnect all Meta accounts?')">
                            @csrf @method('DELETE')
                            <button class="px-4 py-2 text-sm font-medium text-red-600 border border-red-200 rounded-lg hover:bg-red-50 transition-colors">
                                Disconnect
                            </button>
                        </form>
                    @endif
                    <a href="{{ route('admin.marketing.meta.redirect') }}"
                       class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white rounded-lg transition-colors bg-[#1877F2] hover:bg-[#166fe0]">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                        {{ ($connections['facebook']['connected']) ? 'Reconnect' : 'Connect' }}
                    </a>
                </div>
            </div>

            {{-- Sub-accounts Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-0 divide-y md:divide-y-0 md:divide-x divide-neutral-100">
                {{-- Facebook --}}
                <div class="p-5">
                    <div class="flex items-center gap-2.5 mb-3">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center bg-[#1877F2]">
                            <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-neutral-900">Facebook</p>
                            <p class="text-xs text-neutral-400">Page messaging & posts</p>
                        </div>
                    </div>
                    @if($connections['facebook']['connected'])
                        <div class="flex items-center gap-1.5 mb-2">
                            <span class="w-2 h-2 rounded-full bg-green-500"></span>
                            <span class="text-xs font-medium text-green-500">Active</span>
                        </div>
                        <p class="text-xs text-neutral-400">{{ $connections['facebook']['page_id'] }}</p>
                    @else
                        <p class="text-xs text-neutral-400 italic">Not connected</p>
                    @endif
                </div>

                {{-- Instagram --}}
                <div class="p-5">
                    <div class="flex items-center gap-2.5 mb-3">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center bg-gradient-to-br from-[#833AB4] via-[#E1306C] to-[#F77737]">
                            <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913-.306-.789-.718-1.459-1.384-2.126C21.319 1.347 20.651.935 19.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm7.846-10.405a1.44 1.44 0 11-2.88 0 1.44 1.44 0 012.88 0z"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-neutral-900">Instagram</p>
                            <p class="text-xs text-neutral-400">DMs, stories & content</p>
                        </div>
                    </div>
                    @if($connections['instagram']['connected'])
                        <div class="flex items-center gap-1.5 mb-2">
                            <span class="w-2 h-2 rounded-full bg-green-500"></span>
                            <span class="text-xs font-medium text-green-500">Active</span>
                        </div>
                        <p class="text-xs text-neutral-400">{{ $connections['instagram']['user_id'] }}</p>
                    @else
                        <p class="text-xs text-neutral-400 italic">Not connected</p>
                    @endif
                </div>

                {{-- WhatsApp --}}
                <div class="p-5">
                    <div class="flex items-center gap-2.5 mb-3">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center bg-[#25D366]">
                            <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-neutral-900">WhatsApp</p>
                            <p class="text-xs text-neutral-400">Business messaging & templates</p>
                        </div>
                    </div>
                    @if($connections['whatsapp']['connected'])
                        <div class="flex items-center gap-1.5 mb-2">
                            <span class="w-2 h-2 rounded-full bg-green-500"></span>
                            <span class="text-xs font-medium text-green-500">Active</span>
                        </div>
                        <p class="text-xs text-neutral-400">{{ $connections['whatsapp']['phone_id'] }}</p>
                    @else
                        <p class="text-xs text-neutral-400 italic">Not connected</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Facebook Ads Card --}}
        <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
            <div class="flex items-center justify-between px-6 py-5 border-b border-neutral-100">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full flex items-center justify-center bg-[#0668E1]">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-neutral-900">Facebook Ads</h2>
                        <p class="text-xs text-neutral-500">Manage campaigns, budgets, and performance. Connected via Meta login above.</p>
                    </div>
                </div>
                @if($connections['ads']['connected'])
                    <span class="inline-flex items-center gap-1.5 text-xs font-medium text-green-500">
                        <span class="w-2 h-2 rounded-full bg-green-500"></span> Active
                    </span>
                @endif
            </div>
            <div class="px-6 py-5">
                @if($connections['ads']['connected'])
                    <div class="grid grid-cols-3 gap-6">
                        <div>
                            <p class="text-xs font-semibold text-neutral-400 uppercase tracking-wider mb-1">Account</p>
                            <p class="text-sm font-medium text-neutral-900">{{ $connections['ads']['account_name'] }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-neutral-400 uppercase tracking-wider mb-1">Account ID</p>
                            <p class="text-sm text-neutral-600">{{ $connections['ads']['account_id'] }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-neutral-400 uppercase tracking-wider mb-1">Status</p>
                            <span class="inline-flex items-center gap-1.5 text-xs font-medium text-green-500">
                                <span class="w-2 h-2 rounded-full bg-green-500"></span> Connected
                            </span>
                        </div>
                    </div>
                @else
                    <p class="text-sm text-neutral-400">Not connected. Click <strong>Connect</strong> on Meta Platforms above to link your Ad account.</p>
                @endif
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-neutral-100">
                <h2 class="text-base font-bold text-neutral-900">Quick Actions</h2>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 divide-x divide-neutral-100">
                <a href="{{ route('admin.coupons.index') }}" class="flex flex-col items-center gap-2 p-5 hover:bg-neutral-50 transition-colors text-center">
                    <svg class="w-6 h-6 text-neutral-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                    <span class="text-xs font-medium text-neutral-700">Coupons</span>
                </a>
                <a href="{{ route('admin.banners.index') }}" class="flex flex-col items-center gap-2 p-5 hover:bg-neutral-50 transition-colors text-center">
                    <svg class="w-6 h-6 text-neutral-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span class="text-xs font-medium text-neutral-700">Banners</span>
                </a>
                <a href="{{ route('admin.flash-sales.index') }}" class="flex flex-col items-center gap-2 p-5 hover:bg-neutral-50 transition-colors text-center">
                    <svg class="w-6 h-6 text-neutral-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    <span class="text-xs font-medium text-neutral-700">Flash Sales</span>
                </a>
                <a href="{{ route('admin.newsletter.index') }}" class="flex flex-col items-center gap-2 p-5 hover:bg-neutral-50 transition-colors text-center">
                    <svg class="w-6 h-6 text-neutral-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <span class="text-xs font-medium text-neutral-700">Newsletter</span>
                </a>
            </div>
        </div>

        {{-- Setup Info --}}
        <div class="bg-neutral-50 rounded-xl border border-neutral-200 p-5">
            <h3 class="text-sm font-semibold text-neutral-700 mb-2">Meta App Configuration</h3>
            <div class="text-xs text-neutral-500 space-y-1">
                <p><span class="font-medium">App ID:</span> {{ \App\Models\Setting::get('meta_app_id', config('services.meta.app_id', 'Not configured')) }}</p>
                <p><span class="font-medium">OAuth Redirect URI:</span> {{ route('admin.marketing.meta.callback') }}</p>
                <p><span class="font-medium">Webhook URL:</span> {{ url('/api/webhook/meta') }}</p>
            </div>
        </div>
    </div>
</x-layouts.admin>
