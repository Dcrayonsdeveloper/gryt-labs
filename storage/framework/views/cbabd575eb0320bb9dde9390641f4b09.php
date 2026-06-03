<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>" class="scroll-smooth overflow-x-hidden">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

    <?php
        $storeName = $theme->get('store_name', config('app.name', 'Store'));
        $storeLogo = $theme->get('store_logo', 'images/logo.png');
        $brandPrimary = $theme->get('primary_color', '') ?: '#334155';
        $gaId = $theme->get('google_analytics_id', config('services.ga4.measurement_id'));
        $gadsId = $theme->get('google_ads_conversion_id', config('services.google_ads.conversion_id'));
    ?>

    
    <?php if($gaId): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo e($gaId); ?>"></script>
    <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','<?php echo e($gaId); ?>');<?php if($gadsId): ?>gtag('config','<?php echo e($gadsId); ?>');<?php endif; ?></script>
    <?php endif; ?>

    <title><?php echo e($title ?? $storeName); ?></title>

    

    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="<?php echo e($brandPrimary); ?>">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="<?php echo e($storeName); ?>">
    <meta name="application-name" content="<?php echo e($storeName); ?>">
    <meta name="msapplication-TileColor" content="<?php echo e($brandPrimary); ?>">
    <meta name="format-detection" content="telephone=no">
    <link rel="manifest" href="/manifest.json">
    <?php
        $favicon = $theme->get('store_favicon', '') ?: $theme->get('store_logo', '');
        if ($favicon) {
            $faviconPath = str_starts_with($favicon, 'storage/') || str_starts_with($favicon, 'images/') ? $favicon : 'storage/' . $favicon;
            $faviconUrl = asset($faviconPath);
            // Cache-bust: append file mtime so browsers pick up new favicon immediately
            $faviconDisk = str_replace('storage/', '', $faviconPath);
            if (str_starts_with($faviconPath, 'storage/') && \Illuminate\Support\Facades\Storage::disk('public')->exists($faviconDisk)) {
                $faviconUrl .= '?v=' . \Illuminate\Support\Facades\Storage::disk('public')->lastModified($faviconDisk);
            }
        } else {
            $faviconUrl = '';
        }
    ?>
    <?php if($faviconUrl): ?>
    <link rel="icon" type="image/png" href="<?php echo e($faviconUrl); ?>">
    <link rel="shortcut icon" href="<?php echo e($faviconUrl); ?>">
    <?php endif; ?>

    <!-- SEO Meta Tags -->
    <?php if (! empty(trim($__env->yieldContent('meta')))): ?>
        <?php echo $__env->yieldContent('meta'); ?>
    <?php else: ?>
        <?php echo e($meta ?? ''); ?>

    <?php endif; ?>
    <?php echo $__env->yieldPushContent('meta'); ?>

    <!-- Default OG fallbacks (only if no page-specific meta pushed) -->
    <meta property="og:site_name" content="<?php echo e(config('app.name')); ?>">

    

    <!-- Image optimization hints -->
    <meta http-equiv="Accept-CH" content="DPR, Width, Viewport-Width">

    <!-- Performance: DNS prefetch + preconnect -->
    <link rel="dns-prefetch" href="https://fonts.bunny.net">
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>

    <!-- Fonts (display=swap for fast first paint) -->
    <link href="https://fonts.bunny.net/css?family=poppins:400,500,600|inter:400,500&display=swap" rel="stylesheet" />

    <!-- Frontend CSS + JS (per-tenant build) -->
    <?php $__fe = config('app.frontend', 'default'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(["frontends/{$__fe}/css/app.css", "frontends/{$__fe}/js/app.js"]); ?>

    <?php echo e($styles ?? ''); ?>


    
    <?php $customCss = $theme->get('custom_css', ''); ?>
    <?php if($customCss): ?>
        <style><?php echo $customCss; ?></style>
    <?php endif; ?>

    

    
    <?php
        $gtmId = $theme->get('google_tag_manager_id', '');
        $fbPixel = $theme->get('facebook_pixel_id', '');
        $pinterestId = $theme->get('pinterest_tag_id', '');
        $tiktokId = $theme->get('tiktok_pixel_id', '');
        $snapchatId = $theme->get('snapchat_pixel_id', '');
        $clarityId = $theme->get('clarity_project_id', '');
    ?>

    
    <?php if($gtmId): ?>
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','<?php echo e($gtmId); ?>');</script>
    <?php endif; ?>

    
    
    
    <?php if(str_ends_with(request()->getHost(), config('tenancy.subdomain_base', 'dcrayons.app')) || str_starts_with(Route::currentRouteName() ?? '', 'account.')): ?>
    <meta name="robots" content="noindex, nofollow">
    <?php endif; ?>

    <?php if($theme->get('meta_domain_verification')): ?>
    <meta name="facebook-domain-verification" content="<?php echo e($theme->get('meta_domain_verification')); ?>">
    <?php endif; ?>
    <?php if($theme->get('google_site_verification')): ?>
    <meta name="google-site-verification" content="<?php echo e($theme->get('google_site_verification')); ?>">
    <?php endif; ?>
    <?php if($theme->get('pinterest_domain_verification')): ?>
    <meta name="p:domain_verify" content="<?php echo e($theme->get('pinterest_domain_verification')); ?>">
    <?php endif; ?>

    
    <?php if($fbPixel): ?>
    <script>
    !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '<?php echo e($fbPixel); ?>');
    fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=<?php echo e($fbPixel); ?>&ev=PageView&noscript=1"/></noscript>
    <?php endif; ?>

    
    <?php if($pinterestId): ?>
    <script>!function(e){if(!window.pintrk){window.pintrk=function(){window.pintrk.queue.push(Array.prototype.slice.call(arguments))};var n=window.pintrk;n.queue=[],n.version="3.0";var t=document.createElement("script");t.async=!0,t.src=e;var r=document.getElementsByTagName("script")[0];r.parentNode.insertBefore(t,r)}}("https://s.pinimg.com/ct/core.js");pintrk('load','<?php echo e($pinterestId); ?>');pintrk('page');</script>
    <?php endif; ?>

    
    <?php if($tiktokId): ?>
    <script>!function(w,d,t){w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie"],ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e};ttq.load=function(e,n){var i="https://analytics.tiktok.com/i18n/pixel/events.js";ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=i,ttq._t=ttq._t||{},ttq._t[e]=+new Date,ttq._o=ttq._o||{},ttq._o[e]=n||{};var o=document.createElement("script");o.type="text/javascript",o.async=!0,o.src=i+"?sdkid="+e+"&lib="+t;var a=document.getElementsByTagName("script")[0];a.parentNode.insertBefore(o,a)};ttq.load('<?php echo e($tiktokId); ?>');ttq.page();}(window,document,'ttq');</script>
    <?php endif; ?>

    
    <?php if($snapchatId): ?>
    <script>(function(e,t,n){if(e.snaptr)return;var a=e.snaptr=function(){a.handleRequest?a.handleRequest.apply(a,arguments):a.queue.push(arguments)};a.queue=[];var s='script';r=t.createElement(s);r.async=!0;r.src=n;var u=t.getElementsByTagName(s)[0];u.parentNode.insertBefore(r,u);})(window,document,'https://sc-static.net/scevent.min.js');snaptr('init','<?php echo e($snapchatId); ?>');snaptr('track','PAGE_VIEW');</script>
    <?php endif; ?>

    
    <?php if($clarityId): ?>
    <script type="text/javascript">
        (function(c,l,a,r,i,t,y){
            c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
            t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
            y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
        })(window, document, "clarity", "script", "<?php echo e($clarityId); ?>");
    </script>
    <?php endif; ?>

</head>
<body class="font-sans antialiased bg-white text-[#222222] overflow-x-hidden" style="font-family: 'Poppins', sans-serif;" x-data data-authenticated="<?php echo e(auth()->check() ? 'true' : 'false'); ?>">
    <!-- Toast Notifications -->
    <div class="fixed top-4 right-4 z-50 flex flex-col gap-2 max-w-[calc(100vw-2rem)]" aria-live="polite">
        <template x-for="toast in $store.toast.items" :key="toast.id">
            <div x-show="true" role="alert"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-x-8"
                 x-transition:enter-end="opacity-100 translate-x-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-x-0"
                 x-transition:leave-end="opacity-0 translate-x-8"
                 class="card px-4 py-3 flex items-center gap-3 shadow-lg border"
                 :class="{
                     'bg-success-50 border-success-200 text-success-800': toast.type === 'success',
                     'bg-error-50 border-error-200 text-error-800': toast.type === 'error',
                     'bg-warning-50 border-warning-200 text-warning-800': toast.type === 'warning',
                     'bg-info-50 border-info-200 text-info-800': toast.type === 'info'
                 }">
                <span x-text="toast.message"></span>
                <button @click="$store.toast.remove(toast.id)" class="text-current opacity-60 hover:opacity-100" aria-label="Dismiss notification">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </template>
    </div>

    
    <?php if(session('success') || session('error') || session('warning')): ?>
    <script>
        document.addEventListener('alpine:init', () => {
            <?php if(session('success')): ?>Alpine.store('toast').add(<?php echo json_encode(session('success')); ?>, 'success');<?php endif; ?>
            <?php if(session('error')): ?>Alpine.store('toast').add(<?php echo json_encode(session('error')); ?>, 'error');<?php endif; ?>
            <?php if(session('warning')): ?>Alpine.store('toast').add(<?php echo json_encode(session('warning')); ?>, 'warning');<?php endif; ?>
        });
    </script>
    <?php endif; ?>

    <!-- Auth Login/Signup Modal -->
    <?php if(auth()->guard()->guest()): ?>
    <div x-show="$store.authModal.isOpen" x-cloak
         class="fixed inset-0 z-60 flex items-center justify-center p-4"
         @keydown.escape.window="$store.authModal.close()">

        
        <div x-show="$store.authModal.isOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="$store.authModal.close()"
             class="absolute inset-0 bg-black/50"></div>

        
        <div x-show="$store.authModal.isOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="relative bg-white rounded-lg shadow-xl w-full max-w-md overflow-hidden"
             @click.stop>

            
            <button @click="$store.authModal.close()" aria-label="Close dialog"
                    class="absolute top-3 right-3 w-8 h-8 flex items-center justify-center text-neutral-600 hover:text-neutral-800 rounded-full hover:bg-neutral-100 transition-colors z-10">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            
            <div class="pt-6 pb-2 px-6 text-center">
                <img src="<?php echo e(asset($storeLogo)); ?>" alt="<?php echo e($storeName); ?>" class="h-10 mx-auto mb-3 object-contain">
                <h2 class="text-2xl font-bold text-neutral-900"
                    x-text="$store.authModal.mode === 'login' ? 'Login or Signup' : 'Create Account'"></h2>
            </div>

            
            <template x-if="$store.authModal.message">
                <div class="mx-6 mt-2 px-3 py-2 bg-error-50 border border-error-200 rounded text-sm text-error-700" x-text="$store.authModal.message"></div>
            </template>

            
            <div x-show="$store.authModal.mode === 'login'"
                 x-data="{ email: '', password: '', remember: false }"
                 class="px-6 py-4">
                <form @submit.prevent="$store.authModal.login(email, password, remember)" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-neutral-700 mb-1.5">Email Address</label>
                        <input type="email" x-model="email" required autofocus autocomplete="email"
                               class="w-full px-3 py-2.5 bg-neutral-50 border border-neutral-300 rounded-lg text-sm text-neutral-900 placeholder-neutral-400 focus:outline-none focus:border-primary-600 focus:ring-0 transition-colors"
                               placeholder="you@example.com">
                        <template x-if="$store.authModal.errors.email">
                            <p class="mt-1 text-xs text-error-600" x-text="$store.authModal.errors.email[0]"></p>
                        </template>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="text-sm font-medium text-neutral-700">Password</label>
                            <a href="<?php echo e(route('password.request')); ?>" class="text-xs text-primary-600 hover:text-primary-700">Forgot password?</a>
                        </div>
                        <div class="relative" x-data="{ show: false }">
                            <input :type="show ? 'text' : 'password'" x-model="password" required autocomplete="current-password"
                                   class="w-full px-3 py-2.5 pr-10 bg-neutral-50 border border-neutral-300 rounded-lg text-sm text-neutral-900 placeholder-neutral-400 focus:outline-none focus:border-primary-600 focus:ring-0 transition-colors"
                                   placeholder="Enter your password">
                            <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-3 flex items-center text-neutral-600 hover:text-neutral-600">
                                <svg x-show="!show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <svg x-show="show" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                            </button>
                        </div>
                        <template x-if="$store.authModal.errors.password">
                            <p class="mt-1 text-xs text-error-600" x-text="$store.authModal.errors.password[0]"></p>
                        </template>
                    </div>

                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" x-model="remember" class="w-4 h-4 rounded border-neutral-300 text-primary-600 focus:ring-0">
                        <span class="text-sm text-neutral-600">Keep me signed in</span>
                    </label>

                    <button type="submit"
                            :disabled="$store.authModal.isLoading"
                            class="w-full py-2 bg-accent-500 hover:bg-accent-600 text-white font-semibold rounded-lg text-sm transition-colors disabled:opacity-50">
                        <span x-show="!$store.authModal.isLoading">CONTINUE</span>
                        <span x-show="$store.authModal.isLoading" x-cloak class="flex items-center justify-center gap-2">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            Signing in...
                        </span>
                    </button>
                </form>

                <p class="mt-4 text-center text-sm text-neutral-600">
                    New to <?php echo e($theme->get('store_name', config('app.name'))); ?>?
                    <button @click="$store.authModal.switchMode('register')" class="font-semibold text-primary-600 hover:text-primary-700">Create an account</button>
                </p>
            </div>

            
            <div x-show="$store.authModal.mode === 'register'" x-cloak
                 x-data="{ name: '', email: '', password: '', password_confirmation: '' }"
                 class="px-6 py-4">
                <form @submit.prevent="$store.authModal.register(name, email, password, password_confirmation)" class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-neutral-700 mb-1.5">Full Name</label>
                        <input type="text" x-model="name" required
                               class="w-full px-3 py-2.5 bg-neutral-50 border border-neutral-300 rounded-lg text-sm text-neutral-900 placeholder-neutral-400 focus:outline-none focus:border-primary-600 focus:ring-0 transition-colors"
                               placeholder="Enter your full name">
                        <template x-if="$store.authModal.errors.full_name">
                            <p class="mt-1 text-xs text-error-600" x-text="$store.authModal.errors.full_name[0]"></p>
                        </template>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-neutral-700 mb-1.5">Email Address</label>
                        <input type="email" x-model="email" required autocomplete="email"
                               class="w-full px-3 py-2.5 bg-neutral-50 border border-neutral-300 rounded-lg text-sm text-neutral-900 placeholder-neutral-400 focus:outline-none focus:border-primary-600 focus:ring-0 transition-colors"
                               placeholder="you@example.com">
                        <template x-if="$store.authModal.errors.email">
                            <p class="mt-1 text-xs text-error-600" x-text="$store.authModal.errors.email[0]"></p>
                        </template>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-neutral-700 mb-1.5">Password</label>
                        <input type="password" x-model="password" required autocomplete="new-password"
                               class="w-full px-3 py-2.5 bg-neutral-50 border border-neutral-300 rounded-lg text-sm text-neutral-900 placeholder-neutral-400 focus:outline-none focus:border-primary-600 focus:ring-0 transition-colors"
                               placeholder="Min 8 characters">
                        <template x-if="$store.authModal.errors.password">
                            <p class="mt-1 text-xs text-error-600" x-text="$store.authModal.errors.password[0]"></p>
                        </template>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-neutral-700 mb-1.5">Confirm Password</label>
                        <input type="password" x-model="password_confirmation" required autocomplete="new-password"
                               class="w-full px-3 py-2.5 bg-neutral-50 border border-neutral-300 rounded-lg text-sm text-neutral-900 placeholder-neutral-400 focus:outline-none focus:border-primary-600 focus:ring-0 transition-colors"
                               placeholder="Repeat password">
                    </div>

                    <button type="submit"
                            :disabled="$store.authModal.isLoading"
                            class="w-full py-2 bg-accent-500 hover:bg-accent-600 text-white font-semibold rounded-lg text-sm transition-colors disabled:opacity-50">
                        <span x-show="!$store.authModal.isLoading">CREATE ACCOUNT</span>
                        <span x-show="$store.authModal.isLoading" x-cloak class="flex items-center justify-center gap-2">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            Creating account...
                        </span>
                    </button>
                </form>

                <p class="mt-4 text-center text-sm text-neutral-600">
                    Already have an account?
                    <button @click="$store.authModal.switchMode('login')" class="font-semibold text-primary-600 hover:text-primary-700">Sign in</button>
                </p>
            </div>

            
            <div class="px-6 pb-5 pt-2 text-center">
                <p class="text-[11px] text-neutral-600 leading-relaxed">
                    By continuing, I agree to <?php echo e($theme->get('store_name', config('app.name'))); ?>'s
                    <a href="<?php echo e(route('terms')); ?>" class="text-neutral-600 underline">T&C</a>,
                    <a href="<?php echo e(route('privacy')); ?>" class="text-neutral-600 underline">Privacy Policy</a>
                </p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Skip to main content (AAA Accessibility) -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-[100] focus:bg-primary-600 focus:text-white focus:px-4 focus:py-2 focus:rounded-lg focus:text-sm focus:font-medium focus:shadow-lg">
        Skip to main content
    </a>

    <!-- Header -->
    <?php echo $__env->make('partials.header', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <!-- Mobile Navigation -->
    <?php echo $__env->make('partials.mobile-nav', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <!-- Main Content -->
    <main id="main-content" class="min-h-screen pb-24 sm:pb-16">
        <?php echo e($slot); ?>

    </main>

    <!-- Back to Top Button -->
    <div x-data="{ show: false }"
         x-init="window.addEventListener('scroll', () => { show = window.scrollY > 400 })"
         x-cloak>
        <button x-show="show"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-4"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 translate-y-4"
                @click="window.scrollTo({ top: 0, behavior: 'smooth' })"
                class="fixed bottom-36 sm:bottom-24 right-6 sm:right-8 z-40 w-10 h-10 bg-primary-600 hover:bg-primary-700 text-white rounded-full shadow-lg flex items-center justify-center transition-colors"
                aria-label="Back to top">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
        </button>
    </div>

    <!-- Footer -->
    <?php echo $__env->make('partials.footer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <!-- Mobile Bottom Navigation -->
    <?php echo $__env->make('partials.mobile-bottom-nav', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <!-- Quick View Modal -->
    <div x-data="quickViewModal()"
         x-show="open" x-cloak
         @quick-view.window="show($event.detail.productSlug)"
         @keydown.escape.window="close()"
         class="fixed inset-0 z-60 flex items-center justify-center p-4">

        
        <div x-show="open"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="close()"
             class="absolute inset-0 bg-black/50"></div>

        
        <div x-show="open"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="relative bg-white rounded-xl shadow-xl w-full max-w-2xl max-h-[85vh] overflow-hidden"
             @click.stop>

            
            <button @click="close()" aria-label="Close quick view"
                    class="absolute top-3 right-3 w-8 h-8 flex items-center justify-center text-neutral-600 hover:text-neutral-800 rounded-full hover:bg-neutral-100 transition-colors z-10">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            
            <div x-show="loading" class="flex items-center justify-center py-20">
                <svg class="w-8 h-8 animate-spin text-primary-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
            </div>

            
            <div x-show="!loading && product" class="overflow-y-auto max-h-[85vh]">
                <div class="grid grid-cols-1 sm:grid-cols-2">
                    
                    <div class="relative bg-neutral-50 aspect-square sm:aspect-auto sm:min-h-[400px]">
                        <img :src="activeImage" :alt="product?.name" class="w-full h-full object-contain" width="400" height="400" decoding="async" onerror="this.src='/images/placeholder-product.svg'">

                        
                        <template x-if="product?.images?.length > 1">
                            <div class="absolute bottom-3 left-3 right-3 flex gap-1.5 justify-center">
                                <template x-for="(img, i) in product.images.slice(0, 5)" :key="i">
                                    <button @click="activeImageIndex = i"
                                            class="w-10 h-10 rounded border-2 overflow-hidden bg-white transition-colors"
                                            :class="activeImageIndex === i ? 'border-primary-600' : 'border-white/80'">
                                        <img :src="img" class="w-full h-full object-cover" width="40" height="40" decoding="async" onerror="this.src='/images/placeholder-product.svg'">
                                    </button>
                                </template>
                            </div>
                        </template>
                    </div>

                    
                    <div class="p-5 flex flex-col">
                        
                        <p x-show="product?.brand" x-text="product?.brand"
                           class="text-[11px] text-neutral-600 uppercase tracking-wide mb-1"></p>

                        
                        <h2 x-text="product?.name"
                            class="text-lg font-semibold text-neutral-900 leading-snug mb-2"></h2>

                        
                        <template x-if="product?.rating > 0">
                            <div class="flex items-center gap-1.5 mb-3">
                                <span class="inline-flex items-center gap-0.5 bg-success-600 text-white text-xs font-bold px-1.5 py-0.5 rounded-sm">
                                    <span x-text="product?.rating?.toFixed(1)"></span>
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                </span>
                                <span class="text-xs text-neutral-600" x-text="'(' + product?.review_count + ' reviews)'"></span>
                            </div>
                        </template>

                        
                        <div class="flex items-baseline gap-2 mb-3">
                            <span class="text-xl font-bold text-neutral-900" x-text="formatCurrency(product?.price)"></span>
                            <template x-if="product?.price < product?.mrp">
                                <span class="text-sm text-neutral-600 line-through" x-text="formatCurrency(product?.mrp)"></span>
                            </template>
                            <template x-if="product?.discount_percentage > 0">
                                <span class="text-sm font-semibold text-success-600" x-text="Math.round(product?.discount_percentage) + '% off'"></span>
                            </template>
                        </div>

                        
                        <p x-show="product?.short_description" x-text="product?.short_description"
                           class="text-sm text-neutral-600 leading-relaxed mb-4 line-clamp-3"></p>

                        
                        <div class="mb-4">
                            <span x-show="product?.in_stock"
                                  class="inline-flex items-center gap-1 text-xs font-medium text-success-700 bg-success-50 px-2 py-1 rounded-full">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                In Stock
                            </span>
                            <span x-show="!product?.in_stock"
                                  class="inline-flex items-center text-xs font-medium text-error-700 bg-error-50 px-2 py-1 rounded-full">
                                Out of Stock
                            </span>
                        </div>

                        
                        <div class="mt-auto space-y-2">
                            <template x-if="product?.in_stock">
                                <button @click="$store.cart.add(product.id); close()"
                                        class="w-full py-1.5 bg-accent-500 hover:bg-accent-600 text-white font-semibold rounded-lg text-sm transition-colors">
                                    Add to Bag
                                </button>
                            </template>
                            <a :href="product?.url"
                               class="block w-full py-1.5 text-center text-sm font-medium text-primary-600 rounded-lg hover:bg-primary-600/5 transition-colors">
                                View Full Details
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function searchBar() {
            return {
                query: '',
                results: [],
                loading: false,
                showResults: false,
                listening: false,
                recognition: null,
                currentPlaceholder: '',
                placeholders: <?php echo \Illuminate\Support\Js::from(
                    \App\Models\Category::where('is_active', true)
                        ->whereNull('parent_id')
                        ->inRandomOrder()
                        ->limit(12)
                        ->pluck('name')
                        ->map(fn($n) => 'Search for ' . $n . '...')
                        ->values()
                        ->toArray() ?: ['Search for products...']
                )->toHtml() ?>,
                placeholderIndex: 0,
                charIndex: 0,
                isDeleting: false,
                typewriterTimer: null,
                typewriterActive: true,

                init() {
                    this.startTypewriter();
                    // Setup Speech Recognition
                    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                    if (SpeechRecognition) {
                        this.recognition = new SpeechRecognition();
                        this.recognition.lang = 'en-IN';
                        this.recognition.continuous = false;
                        this.recognition.interimResults = false;
                        this.recognition.onresult = (event) => {
                            const transcript = event.results[0][0].transcript;
                            this.query = transcript;
                            this.listening = false;
                            this.fetchSuggestions();
                            // Auto-submit after voice input
                            this.$nextTick(() => {
                                this.$refs.searchInput.closest('form').submit();
                            });
                        };
                        this.recognition.onerror = () => {
                            this.listening = false;
                        };
                        this.recognition.onend = () => {
                            this.listening = false;
                        };
                    }
                },

                startTypewriter() {
                    this.typewriterActive = true;
                    this.charIndex = 0;
                    this.isDeleting = false;
                    this.currentPlaceholder = '';
                    this.typewrite();
                },

                stopTypewriter() {
                    this.typewriterActive = false;
                    if (this.typewriterTimer) {
                        clearTimeout(this.typewriterTimer);
                        this.typewriterTimer = null;
                    }
                    this.currentPlaceholder = 'Search products...';
                },

                typewrite() {
                    if (!this.typewriterActive) return;
                    const current = this.placeholders[this.placeholderIndex];

                    if (!this.isDeleting) {
                        this.currentPlaceholder = current.substring(0, this.charIndex + 1);
                        this.charIndex++;
                        if (this.charIndex >= current.length) {
                            // Pause at full text, then start deleting
                            this.typewriterTimer = setTimeout(() => {
                                this.isDeleting = true;
                                this.typewrite();
                            }, 2000);
                            return;
                        }
                        this.typewriterTimer = setTimeout(() => this.typewrite(), 80);
                    } else {
                        this.currentPlaceholder = current.substring(0, this.charIndex - 1);
                        this.charIndex--;
                        if (this.charIndex <= 0) {
                            this.isDeleting = false;
                            this.placeholderIndex = (this.placeholderIndex + 1) % this.placeholders.length;
                            this.typewriterTimer = setTimeout(() => this.typewrite(), 400);
                            return;
                        }
                        this.typewriterTimer = setTimeout(() => this.typewrite(), 40);
                    }
                },

                async fetchSuggestions() {
                    if (this.query.length < 2) {
                        this.results = [];
                        return;
                    }
                    this.loading = true;
                    this.showResults = true;
                    try {
                        const response = await axios.get('/search/suggestions', { params: { q: this.query } });
                        this.results = response.data.suggestions || response.data.results || [];
                    } catch (e) {
                        this.results = [];
                    } finally {
                        this.loading = false;
                    }
                },

                toggleMic() {
                    if (!this.recognition) {
                        alert('Voice search is not supported in your browser. Please use Chrome or Edge.');
                        return;
                    }
                    if (this.listening) {
                        this.recognition.stop();
                        this.listening = false;
                    } else {
                        this.stopTypewriter();
                        this.query = '';
                        this.recognition.start();
                        this.listening = true;
                    }
                },

                destroy() {
                    this.stopTypewriter();
                    if (this.recognition && this.listening) {
                        this.recognition.stop();
                    }
                }
            };
        }

        function quickViewModal() {
            return {
                open: false,
                loading: false,
                product: null,
                activeImageIndex: 0,

                get activeImage() {
                    if (!this.product?.images?.length) return this.product?.primary_image || '';
                    return this.product.images[this.activeImageIndex] || this.product.primary_image;
                },

                async show(productSlug) {
                    this.open = true;
                    this.loading = true;
                    this.product = null;
                    this.activeImageIndex = 0;
                    document.body.style.overflow = 'hidden';

                    try {
                        const response = await axios.get(`/product/${productSlug}/quick-view`);
                        this.product = response.data;
                    } catch (error) {
                        console.error('Quick view failed:', error);
                        Alpine.store('toast').error('Could not load product details');
                        this.close();
                    } finally {
                        this.loading = false;
                    }
                },

                close() {
                    this.open = false;
                    document.body.style.overflow = '';
                }
            };
        }
    </script>

    <!-- Cart Sidebar Drawer -->
    <div x-data x-show="$store.cart.isOpen" x-cloak
         @keydown.escape.window="$store.cart.close()"
         class="fixed inset-0 z-50">

        
        <div x-show="$store.cart.isOpen"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="$store.cart.close()"
             class="absolute inset-0 bg-black/40"></div>

        
        <div x-show="$store.cart.isOpen"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full"
             class="absolute inset-y-0 right-0 w-full max-w-md bg-white shadow-xl flex flex-col">

            
            <div class="flex items-center justify-between px-5 py-4 border-b border-neutral-100">
                <h2 class="text-lg font-bold text-neutral-900 flex items-center gap-2">
                    <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                    Shopping Bag
                    <span class="text-sm font-normal text-neutral-500" x-text="'(' + $store.cart.count + ')'"></span>
                </h2>
                <button @click="$store.cart.close()" class="p-1.5 text-neutral-500 hover:text-neutral-700 rounded-full hover:bg-neutral-100 transition-colors" aria-label="Close cart">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            
            <div x-show="$store.cart.isLoading && $store.cart.items.length === 0" class="flex-1 flex items-center justify-center">
                <svg class="w-8 h-8 animate-spin text-primary-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
            </div>

            
            <div x-show="!$store.cart.isLoading && $store.cart.items.length === 0" class="flex-1 flex flex-col items-center justify-center px-6">
                <div class="w-20 h-20 bg-neutral-100 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-10 h-10 text-neutral-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </div>
                <p class="text-neutral-600 font-medium mb-1">Your bag is empty</p>
                <p class="text-sm text-neutral-500 mb-5">Add items to get started</p>
                <button @click="$store.cart.close()" class="text-sm font-semibold text-primary-600 hover:text-primary-700 transition-colors">Continue Shopping</button>
            </div>

            
            <div x-show="$store.cart.items.length > 0" class="flex-1 overflow-y-auto px-5 py-3 space-y-3">
                <template x-for="item in $store.cart.items" :key="item.id">
                    <div class="flex gap-3 py-3 border-b border-neutral-100 last:border-0">
                        
                        <a :href="item.url || '#'" class="shrink-0 w-20 h-20 bg-neutral-50 rounded-lg overflow-hidden">
                            <img :src="item.image" :alt="item.name" class="w-full h-full object-cover" width="80" height="80" decoding="async">
                        </a>

                        
                        <div class="flex-1 min-w-0">
                            <a :href="item.url || '#'" class="text-sm font-medium text-neutral-900 line-clamp-2 hover:text-primary-600 transition-colors" x-text="item.name"></a>
                            <div class="flex items-baseline gap-1.5 mt-1">
                                <span class="text-sm font-bold text-neutral-900" x-text="formatCurrency(item.price)"></span>
                                <template x-if="item.mrp && item.mrp > item.price">
                                    <span class="text-[11px] text-neutral-500 line-through" x-text="formatCurrency(item.mrp)"></span>
                                </template>
                            </div>

                            
                            <div class="flex items-center gap-2 mt-2">
                                <div class="flex items-center border border-neutral-200 rounded-md">
                                    <button @click="item.quantity > 1 ? $store.cart.update(item.id, item.quantity - 1) : $store.cart.remove(item.id)"
                                            class="w-7 h-7 flex items-center justify-center text-neutral-500 hover:text-neutral-700 transition-colors">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                                    </button>
                                    <span class="w-8 text-center text-sm font-medium text-neutral-900" x-text="item.quantity"></span>
                                    <button @click="$store.cart.update(item.id, item.quantity + 1)"
                                            class="w-7 h-7 flex items-center justify-center text-neutral-500 hover:text-neutral-700 transition-colors">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    </button>
                                </div>
                                <button @click="$store.cart.remove(item.id)" class="ml-auto text-neutral-500 hover:text-red-500 transition-colors" aria-label="Remove item">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            
            <div x-show="$store.cart.recommendations.length > 0 && $store.cart.items.length > 0" class="border-t border-neutral-100 px-5 py-3 shrink-0 max-h-[35vh] lg:max-h-[40vh] overflow-hidden">
                <h3 class="text-sm font-bold text-neutral-900 mb-2">You May Also Like</h3>
                <div class="flex gap-2.5 overflow-x-auto pb-2 -mx-1 px-1 snap-x" style="-webkit-overflow-scrolling: touch;">
                    <template x-for="rec in $store.cart.recommendations" :key="rec.id">
                        <div class="shrink-0 w-28 snap-start">
                            <a :href="rec.url" class="block" @click="$store.cart.close()">
                                <div class="relative aspect-square bg-neutral-50 rounded-lg overflow-hidden mb-1.5">
                                    <img :src="rec.image" :alt="rec.name" class="w-full h-full object-cover" width="112" height="112" loading="lazy" decoding="async">
                                    <span x-show="rec.has_discount" class="absolute top-1 left-1 bg-accent-500 text-white text-[8px] font-bold px-1 py-0.5 rounded" x-text="rec.discount_pct + '% Off'"></span>
                                </div>
                                <p class="text-[11px] text-neutral-900 font-medium line-clamp-2 leading-tight mb-0.5" x-text="rec.name"></p>
                                <div class="flex items-baseline gap-1">
                                    <span class="text-xs font-bold text-neutral-900" x-text="formatCurrency(rec.price)"></span>
                                    <span x-show="rec.has_discount" class="text-[9px] text-neutral-500 line-through" x-text="formatCurrency(rec.mrp)"></span>
                                </div>
                            </a>
                            <button @click="$store.cart.add(rec.id)"
                                    class="w-full mt-1.5 py-1 text-[10px] font-semibold text-primary-600 rounded-md hover:bg-primary-600 hover:text-white transition-colors">
                                Add to Bag
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            
            <div x-show="$store.cart.items.length > 0" class="border-t border-neutral-200 px-5 py-4 bg-white shrink-0">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm text-neutral-600">Subtotal</span>
                    <span class="text-lg font-bold text-neutral-900" x-text="formatCurrency($store.cart.subtotal)"></span>
                </div>
                <p class="text-[11px] text-neutral-500 mb-3">Shipping and taxes calculated at checkout</p>
                <div class="flex flex-col gap-2">
                    <?php if(\App\Models\Setting::get('shiprocket_checkout_enabled', false)): ?>
                        <button @click="$store.cart.close(); window.__srCartCheckout ? __srCartCheckout($el) : (window.location.href='<?php echo e(route('checkout.index')); ?>')"
                           class="w-full py-1.5 bg-accent-500 hover:bg-accent-600 text-white font-semibold rounded-lg text-sm text-center transition-colors shadow-sm disabled:opacity-70">
                            <span>Checkout</span>
                        </button>
                    <?php else: ?>
                        <a href="<?php echo e(route('checkout.index')); ?>" @click="$store.cart.close()"
                           class="w-full py-1.5 bg-accent-500 hover:bg-accent-600 text-white font-semibold rounded-lg text-sm text-center transition-colors shadow-sm">
                            Checkout
                        </a>
                    <?php endif; ?>
                    <a href="<?php echo e(route('cart.index')); ?>" @click="$store.cart.close()"
                       class="w-full py-1.5 text-center text-sm font-medium text-primary-600 rounded-lg hover:bg-primary-600/5 transition-colors">
                        View Bag
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- PWA Install Prompt (compact, bottom-left) -->
    <div x-data="pwaInstall()" x-show="showPrompt" x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-full"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-full"
         class="fixed bottom-20 lg:bottom-4 left-3 z-50 bg-white rounded-lg shadow-lg border border-[#E3E6E6] px-3 py-2.5 w-56">
        <div class="flex items-center gap-2">
            <div class="w-7 h-7 rounded bg-primary-600 flex items-center justify-center shrink-0">
                <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-[11px] font-semibold text-[#0F1111] leading-tight">Add <?php echo e($theme->get('store_name', config('app.name'))); ?> to Home Screen</p>
            </div>
            <button @click="dismiss()" class="shrink-0 text-[#565959] hover:text-[#0F1111]" aria-label="Dismiss">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="flex gap-1.5 mt-2">
            <button @click="dismiss()" class="flex-1 py-1 text-[10px] font-medium text-[#565959] bg-[#F7F8FA] rounded hover:bg-[#E3E6E6] transition-colors">Later</button>
            <button @click="install()" class="flex-1 py-1 text-[10px] font-medium text-white bg-primary-600 rounded hover:bg-primary-700 transition-colors">Install</button>
        </div>
    </div>

    <?php echo e($scripts ?? ''); ?>


    <!-- Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').catch(() => {});
            });
        }

        function pwaInstall() {
            return {
                showPrompt: false,
                deferredPrompt: null,
                init() {
                    if (window.matchMedia('(display-mode: standalone)').matches) return;
                    if (sessionStorage.getItem('pwa_dismissed')) return;
                    window.addEventListener('beforeinstallprompt', (e) => {
                        e.preventDefault();
                        this.deferredPrompt = e;
                        setTimeout(() => { this.showPrompt = true; }, 30000);
                    });
                },
                async install() {
                    if (!this.deferredPrompt) return;
                    this.deferredPrompt.prompt();
                    const { outcome } = await this.deferredPrompt.userChoice;
                    this.deferredPrompt = null;
                    this.showPrompt = false;
                    if (outcome === 'accepted') sessionStorage.setItem('pwa_dismissed', '1');
                },
                dismiss() {
                    this.showPrompt = false;
                    sessionStorage.setItem('pwa_dismissed', '1');
                }
            };
        }
    </script>

    <?php if (isset($component)) { $__componentOriginal4378b2eccec4e8470841be6441e66765 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4378b2eccec4e8470841be6441e66765 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.whatsapp-button','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('whatsapp-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal4378b2eccec4e8470841be6441e66765)): ?>
<?php $attributes = $__attributesOriginal4378b2eccec4e8470841be6441e66765; ?>
<?php unset($__attributesOriginal4378b2eccec4e8470841be6441e66765); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal4378b2eccec4e8470841be6441e66765)): ?>
<?php $component = $__componentOriginal4378b2eccec4e8470841be6441e66765; ?>
<?php unset($__componentOriginal4378b2eccec4e8470841be6441e66765); ?>
<?php endif; ?>
    <?php if (isset($component)) { $__componentOriginal068c9bea8de1c43e6a443f4afc109e85 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal068c9bea8de1c43e6a443f4afc109e85 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.instagram-button','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('instagram-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal068c9bea8de1c43e6a443f4afc109e85)): ?>
<?php $attributes = $__attributesOriginal068c9bea8de1c43e6a443f4afc109e85; ?>
<?php unset($__attributesOriginal068c9bea8de1c43e6a443f4afc109e85); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal068c9bea8de1c43e6a443f4afc109e85)): ?>
<?php $component = $__componentOriginal068c9bea8de1c43e6a443f4afc109e85; ?>
<?php unset($__componentOriginal068c9bea8de1c43e6a443f4afc109e85); ?>
<?php endif; ?>
    <?php if (isset($component)) { $__componentOriginal58c23a86edde2052917eede6c6b5323a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal58c23a86edde2052917eede6c6b5323a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.exit-intent-popup','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('exit-intent-popup'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal58c23a86edde2052917eede6c6b5323a)): ?>
<?php $attributes = $__attributesOriginal58c23a86edde2052917eede6c6b5323a; ?>
<?php unset($__attributesOriginal58c23a86edde2052917eede6c6b5323a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal58c23a86edde2052917eede6c6b5323a)): ?>
<?php $component = $__componentOriginal58c23a86edde2052917eede6c6b5323a; ?>
<?php unset($__componentOriginal58c23a86edde2052917eede6c6b5323a); ?>
<?php endif; ?>

    <!-- Keep session alive + refresh CSRF token (Shopify-style) -->
    <script>
        (function() {
            // Refresh CSRF token periodically to prevent 419
            function refreshToken() {
                fetch('/csrf-token', { credentials: 'same-origin' })
                    .then(r => r.json())
                    .then(d => {
                        if (d.token) {
                            document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', d.token);
                            document.querySelectorAll('input[name="_token"]').forEach(el => el.value = d.token);
                            if (window.axios) window.axios.defaults.headers.common['X-CSRF-TOKEN'] = d.token;
                        }
                    }).catch(() => {});
            }

            // Refresh every 10 minutes
            setInterval(refreshToken, 10 * 60 * 1000);

            // Also refresh on tab focus (user returns after being away)
            document.addEventListener('visibilitychange', function() {
                if (!document.hidden) refreshToken();
            });
        })();
    </script>

    
    <?php if(\App\Models\Setting::get('shiprocket_checkout_enabled', false)): ?>
        <?php if (isset($component)) { $__componentOriginal984d0e4b0c6b21994c1dbc9d4e2be618 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal984d0e4b0c6b21994c1dbc9d4e2be618 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.shiprocket-checkout-sdk','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('shiprocket-checkout-sdk'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal984d0e4b0c6b21994c1dbc9d4e2be618)): ?>
<?php $attributes = $__attributesOriginal984d0e4b0c6b21994c1dbc9d4e2be618; ?>
<?php unset($__attributesOriginal984d0e4b0c6b21994c1dbc9d4e2be618); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal984d0e4b0c6b21994c1dbc9d4e2be618)): ?>
<?php $component = $__componentOriginal984d0e4b0c6b21994c1dbc9d4e2be618; ?>
<?php unset($__componentOriginal984d0e4b0c6b21994c1dbc9d4e2be618); ?>
<?php endif; ?>
    <?php endif; ?>
</body>
</html>
<?php /**PATH D:\projects\grytlabs345\resources\views/components/layouts/app.blade.php ENDPATH**/ ?>