<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Admin' }} - {{ config('app.name') }}</title>

    {{-- PWA / Add to Home Screen --}}
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{{ \App\Models\Setting::get('store_name', config('app.name')) }} Admin">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="application-name" content="{{ \App\Models\Setting::get('store_name', config('app.name')) }} Admin">
    <meta name="theme-color" content="#1a1a1a">
    <link rel="manifest" href="/admin-manifest.json">
    <link rel="apple-touch-icon" sizes="180x180" href="/images/icons/apple-touch-icon.png">
    @php
        $adminFavicon = \App\Models\Setting::get('store_favicon', '') ?: \App\Models\Setting::get('store_logo', '');
    @endphp
    @if($adminFavicon)
        <link rel="icon" href="/{{ ltrim($adminFavicon, '/') }}">
        <link rel="shortcut icon" href="/{{ ltrim($adminFavicon, '/') }}">
    @endif

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Shopify Polaris-like Admin Design Tokens --}}
    <style>
        :root {
            --admin-bg: #f1f1f1;
            --admin-surface: #ffffff;
            --admin-surface-secondary: #f6f6f7;
            --admin-border: #e1e1e1;
            --admin-border-subtle: #f0f0f0;
            --admin-text: #1a1a1a;
            --admin-text-secondary: #616161;
            --admin-text-tertiary: #8c9196;
            --admin-accent: #005bd3;
            --admin-accent-hover: #004299;
            --admin-success: #1a7431;
            --admin-success-bg: #e3f1df;
            --admin-warning: #856404;
            --admin-warning-bg: #fff3cd;
            --admin-critical: #c53030;
            --admin-critical-bg: #fde8e8;
            --admin-sidebar: #1a1a1a;
            --admin-sidebar-hover: #333333;
            --admin-sidebar-active: #404040;
            --admin-header: #1a1a1a;
            --admin-radius-sm: 6px;
            --admin-radius-md: 8px;
            --admin-radius-lg: 12px;
            --admin-radius-xl: 14px;
            --admin-shadow-card: 0 1px 2px 0 rgba(0,0,0,0.05);
            --admin-shadow-modal: 0 20px 60px rgba(0,0,0,0.15);
        }
        .layout-admin .admin-card {
            background: var(--admin-surface);
            border: 1px solid var(--admin-border);
            border-radius: var(--admin-radius-lg);
            box-shadow: var(--admin-shadow-card);
        }
        .layout-admin .admin-btn {
            font-size: 13px;
            font-weight: 600;
            padding: 6px 12px;
            border-radius: var(--admin-radius-sm);
            transition: all 0.15s;
            cursor: pointer;
        }
        .layout-admin .admin-btn-primary {
            background: var(--admin-text);
            color: #fff;
        }
        .layout-admin .admin-btn-primary:hover { background: var(--admin-sidebar-hover); }
        .layout-admin .admin-btn-outline {
            background: var(--admin-surface);
            color: var(--admin-text);
            border: 1px solid var(--admin-border);
        }
        .layout-admin .admin-btn-outline:hover { background: var(--admin-surface-secondary); }
        .layout-admin .admin-input {
            font-size: 13px;
            padding: 8px 12px;
            border: 1px solid #c9c9c9;
            border-radius: var(--admin-radius-sm);
            transition: border-color 0.15s, box-shadow 0.15s;
            width: 100%;
        }
        .layout-admin .admin-input:hover { border-color: #595959; }
        .layout-admin .admin-input:focus { border-color: var(--admin-accent); box-shadow: 0 0 0 1px var(--admin-accent); outline: none; }
        .layout-admin .admin-badge { font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 99px; display: inline-flex; align-items: center; }
        .layout-admin .admin-badge-success { background: var(--admin-success-bg); color: var(--admin-success); }
        .layout-admin .admin-badge-warning { background: var(--admin-warning-bg); color: var(--admin-warning); }
        .layout-admin .admin-badge-critical { background: var(--admin-critical-bg); color: var(--admin-critical); }
        .layout-admin .admin-badge-neutral { background: #e9ecef; color: #495057; }
        .layout-admin table th { font-size: 12px; font-weight: 600; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.02em; }
        .layout-admin table td { font-size: 13px; color: var(--admin-text); }
        .layout-admin table tr:hover td { background: #fafafa; }
    </style>

    {{ $styles ?? '' }}
    @stack('styles')
</head>
<body class="antialiased layout-admin" x-data="{ sidebarOpen: false }" style="background:var(--admin-bg);font-family:'Inter',system-ui,-apple-system,sans-serif">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        @include('admin.partials.sidebar')

        <!-- Content area -->
        <div class="flex flex-col flex-1 overflow-hidden" style="background:#f1f1f1">
            <!-- Admin Header -->
            @include('admin.partials.header')

            <!-- Contextual Save Bar -->
            <div x-data="{ dirty: false }"
                 @form-dirty.window="dirty = true"
                 @form-clean.window="dirty = false"
                 x-show="dirty" x-cloak
                 x-transition
                 class="z-50 bg-neutral-900 text-white px-5 py-2.5 flex items-center justify-between shadow-lg shrink-0">
                <div class="flex items-center gap-2 text-sm">
                    <div class="w-2 h-2 bg-yellow-400 rounded-full"></div>
                    Unsaved changes
                </div>
                <div class="flex gap-2">
                    <button @click="$dispatch('form-discard')" type="button" class="px-3 py-1 text-xs font-medium text-white/80 hover:text-white border border-white/30 rounded">Discard</button>
                    <button @click="$dispatch('form-save')" type="button" class="px-3 py-1 text-xs font-medium bg-green-600 hover:bg-green-700 text-white rounded">Save</button>
                </div>
            </div>

            <!-- Main content -->
            <main class="flex-1 overflow-y-auto" style="scrollbar-width:thin;scrollbar-color:#ccc transparent;background:#f1f1f1;padding:16px 20px">
                @isset($header)
                    <div class="mb-4">{{ $header }}</div>
                @endisset

                @isset($statsBar)
                    {{ $statsBar }}
                @endisset

                {{ $slot }}
            </main>
        </div>
    </div>

    {{ $scripts ?? '' }}
    @stack('scripts')

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        toastr.options = {
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right',
            timeOut: 4000,
            extendedTimeOut: 2000,
            showEasing: 'swing',
            hideEasing: 'linear',
            showMethod: 'fadeIn',
            hideMethod: 'fadeOut',
        };

        @if(session('success'))
            toastr.success(@json(session('success')));
        @endif

        @if(session('error'))
            toastr.error(@json(session('error')));
        @endif

        @if(session('warning'))
            toastr.warning(@json(session('warning')));
        @endif

        @if(session('info'))
            toastr.info(@json(session('info')));
        @endif
    </script>

    {{-- Form Change Tracker --}}
    <script>
    (function() {
        document.querySelectorAll('form[data-track-changes]').forEach(function(form) {
            var serialize = function() {
                var data = new FormData(form);
                var parts = [];
                data.forEach(function(value, key) { parts.push(key + '=' + value); });
                return parts.join('&');
            };
            var originalData = serialize();
            var checkDirty = function() {
                var current = serialize();
                if (current !== originalData) {
                    window.dispatchEvent(new Event('form-dirty'));
                } else {
                    window.dispatchEvent(new Event('form-clean'));
                }
            };
            form.addEventListener('input', checkDirty);
            form.addEventListener('change', checkDirty);
            window.addEventListener('form-discard', function() {
                form.reset();
                window.dispatchEvent(new Event('form-clean'));
            });
            window.addEventListener('form-save', function() {
                form.submit();
            });
        });
    })();
    </script>

    {{-- Confirmation Modal --}}
    <div x-data="confirmModal()" x-show="open" x-cloak @confirm.window="show($event.detail)" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/50" @click="cancel()"></div>
        <div class="relative bg-white rounded-xl shadow-2xl p-6 max-w-md w-full mx-4" x-transition>
            <h3 class="text-lg font-semibold text-neutral-900 mb-2" x-text="title"></h3>
            <p class="text-sm text-neutral-600 mb-5" x-text="message"></p>
            <div class="flex justify-end gap-2">
                <button @click="cancel()" class="admin-btn admin-btn-outline">Cancel</button>
                <button @click="confirm()" class="admin-btn" style="background:var(--admin-critical);color:#fff" x-text="confirmText"></button>
            </div>
        </div>
    </div>
    <script>
    function confirmModal() {
        return {
            open: false, title: '', message: '', confirmText: 'Delete', onConfirm: null,
            show(detail) {
                this.title = detail.title || 'Are you sure?';
                this.message = detail.message || 'This action cannot be undone.';
                this.confirmText = detail.confirmText || 'Delete';
                this.onConfirm = detail.onConfirm || null;
                this.open = true;
            },
            confirm() {
                if (this.onConfirm) this.onConfirm();
                this.open = false;
            },
            cancel() { this.open = false; }
        };
    }
    </script>

    {{-- New Order Notification System --}}
    <script>
    (function() {
        // Create notification sound (coin/cha-ching)
        var audioCtx = null;
        function playOrderSound() {
            try {
                if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                // Cha-ching sound: two quick high-pitched notes
                [0, 0.15].forEach(function(delay) {
                    var osc = audioCtx.createOscillator();
                    var gain = audioCtx.createGain();
                    osc.connect(gain);
                    gain.connect(audioCtx.destination);
                    osc.frequency.value = delay === 0 ? 1200 : 1600;
                    osc.type = 'sine';
                    gain.gain.setValueAtTime(0.3, audioCtx.currentTime + delay);
                    gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + delay + 0.3);
                    osc.start(audioCtx.currentTime + delay);
                    osc.stop(audioCtx.currentTime + delay + 0.3);
                });
            } catch(e) {}
        }

        var lastCheck = '{{ now()->format('Y-m-d H:i:s') }}';
        var checkInterval = 30000; // 30 seconds

        function checkNewOrders() {
            fetch('{{ route("admin.orders.check-new") }}?since=' + encodeURIComponent(lastCheck), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.count > 0) {
                    playOrderSound();
                    data.orders.forEach(function(order) {
                        toastr.success(
                            '<strong>' + order.number + '</strong><br>₹' + order.total + ' from ' + order.name,
                            '🛒 New Order!',
                            { timeOut: 10000, onclick: function() { window.location.href = '/admin/orders/' + order.id; } }
                        );
                    });
                    // Update sidebar order count badge if exists
                    var badge = document.querySelector('.pending-orders-badge');
                    if (badge) {
                        var current = parseInt(badge.textContent) || 0;
                        badge.textContent = current + data.count;
                        badge.style.display = 'inline-flex';
                    }
                }
                lastCheck = data.checked_at || new Date().toISOString();
            })
            .catch(function() {});
        }

        // Start polling
        setInterval(checkNewOrders, checkInterval);
        // Also check on page load after a short delay
        setTimeout(checkNewOrders, 3000);

        // Resume AudioContext on first user interaction (browser policy)
        document.addEventListener('click', function() {
            if (audioCtx && audioCtx.state === 'suspended') audioCtx.resume();
        }, { once: true });
    })();
    </script>

    <!-- Alpine Toast Notifications -->
    <div x-data="{ toasts: [], add(msg, type='success') { const id = Date.now(); this.toasts.push({id, msg, type}); setTimeout(() => this.remove(id), 4000); }, remove(id) { this.toasts = this.toasts.filter(t => t.id !== id); } }"
         @admin-toast.window="add($event.detail.message, $event.detail.type || 'success')"
         class="fixed bottom-4 right-4 z-50 flex flex-col gap-2 max-w-sm">
        <template x-for="toast in toasts" :key="toast.id">
            <div x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="flex items-center gap-2 px-4 py-3 rounded-lg shadow-lg text-sm font-medium"
                 :class="{
                     'bg-green-600 text-white': toast.type === 'success',
                     'bg-red-600 text-white': toast.type === 'error',
                     'bg-yellow-500 text-white': toast.type === 'warning',
                     'bg-blue-600 text-white': toast.type === 'info',
                 }">
                <span x-text="toast.msg" class="flex-1"></span>
                <button @click="remove(toast.id)" class="text-white/70 hover:text-white shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </template>
    </div>

    <!-- Keep admin session alive + refresh CSRF (Shopify-style) -->
    <script>
        (function() {
            function refreshToken() {
                fetch('/csrf-token', { credentials: 'same-origin' })
                    .then(r => r.json())
                    .then(d => {
                        if (d.token) {
                            document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', d.token);
                            document.querySelectorAll('input[name="_token"]').forEach(el => el.value = d.token);
                        }
                    }).catch(() => {});
            }
            setInterval(refreshToken, 5 * 60 * 1000); // Every 5 min for admin
            document.addEventListener('visibilitychange', function() {
                if (!document.hidden) refreshToken();
            });
        })();
    </script>
</body>
</html>
