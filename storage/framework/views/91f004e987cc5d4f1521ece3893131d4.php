<!-- Mobile Navigation Drawer -->
<div x-data="{ open: false }"
     @toggle-mobile-nav.window="open = !open"
     @keydown.escape.window="open = false"
     x-show="open"
     x-cloak
     class="lg:hidden fixed inset-0 z-50"
     role="dialog"
     aria-modal="true"
     aria-label="Navigation menu">

    <!-- Backdrop -->
    <div x-show="open"
         x-transition:enter="transition-opacity ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="open = false"
         class="fixed inset-0 bg-black/50"></div>

    <!-- Drawer -->
    <div x-show="open"
         x-transition:enter="transition-transform ease-out duration-150"
         x-transition:enter-start="-translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition-transform ease-in duration-100"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="-translate-x-full"
         class="fixed inset-y-0 left-0 w-[85vw] max-w-xs bg-white shadow-xl flex flex-col">

        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 border-b border-neutral-100 shrink-0">
            <a href="<?php echo e(url('/')); ?>" class="flex items-center">
                <img src="<?php echo e(asset($theme->get('store_logo', 'images/logo.png'))); ?>" alt="<?php echo e($theme->get('store_name', config('app.name'))); ?>" class="h-[29px] w-auto">
            </a>
            <button @click="open = false" class="p-2.5 text-neutral-600 hover:text-neutral-600 rounded-full hover:bg-neutral-100 focus:outline-none focus:ring-2 focus:ring-primary-600" aria-label="Close menu">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- User section -->
        <div class="px-4 py-3 border-b border-neutral-100 shrink-0">
            <?php if(auth()->guard()->guest()): ?>
                <div class="flex gap-2">
                    <a href="<?php echo e(route('login')); ?>" class="flex-1 py-2 text-center text-sm font-semibold text-white bg-black hover:bg-neutral-800 rounded-lg transition-colors">Login</a>
                    <a href="<?php echo e(route('register')); ?>" class="flex-1 py-2 text-center text-sm font-medium text-neutral-700 rounded-lg hover:bg-neutral-50 transition-colors">Register</a>
                </div>
            <?php else: ?>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-primary-600/10 rounded-full flex items-center justify-center shrink-0">
                        <?php if(auth()->user()->avatar_url): ?>
                            <img src="<?php echo e(auth()->user()->avatar_url); ?>" alt="<?php echo e(auth()->user()->full_name); ?>" class="w-full h-full rounded-full object-cover">
                        <?php else: ?>
                            <span class="text-sm font-semibold text-primary-600"><?php echo e(substr(auth()->user()->first_name, 0, 1)); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="min-w-0">
                        <div class="text-sm font-semibold text-neutral-900 truncate"><?php echo e(auth()->user()->full_name); ?></div>
                        <div class="text-xs text-neutral-600 truncate"><?php echo e(auth()->user()->email); ?></div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Search -->
        <div class="px-4 py-3 border-b border-neutral-100 shrink-0">
            <form action="<?php echo e(route('search')); ?>" method="GET">
                <div class="relative">
                    <svg class="w-4 h-4 text-neutral-600 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="q" placeholder="Search products..."
                           class="w-full pl-9 pr-3 py-2 text-sm bg-neutral-50 border border-neutral-200 rounded-lg focus:outline-none focus:border-primary-600 placeholder-neutral-400">
                </div>
            </form>
        </div>

        <!-- Scrollable Navigation -->
        <nav class="flex-1 overflow-y-auto">
            <div class="py-2">
                <!-- Quick Links -->
                <?php
                    $mobileNavItems = $theme->navigation('header');
                ?>
                <?php if($mobileNavItems->isNotEmpty()): ?>
                    <?php $__currentLoopData = $mobileNavItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $mNavItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php if($mNavItem->children->isNotEmpty()): ?>
                            <div x-data="{ open: false }">
                                <button @click="open = !open" class="flex items-center justify-between w-full px-4 py-3 text-sm text-neutral-700 hover:bg-neutral-50">
                                    <span><?php echo e($mNavItem->label); ?></span>
                                    <svg class="w-4 h-4 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <div x-show="open" x-collapse class="bg-neutral-50">
                                    <?php $__currentLoopData = $mNavItem->children; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $mChild): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <a href="<?php echo e($mChild->url); ?>" class="block pl-8 pr-4 py-2.5 text-sm text-neutral-600 hover:text-primary-600"><?php echo e($mChild->label); ?></a>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php $isMConsultBtn = \Illuminate\Support\Str::contains(strtolower($mNavItem->label), 'consultation'); ?>
                            <?php if($isMConsultBtn): ?>
                                <a href="<?php echo e($mNavItem->url); ?>" class="mx-4 my-2 flex items-center justify-center gap-2 px-4 py-3 bg-linear-to-r from-accent-500 to-accent-600 hover:from-accent-600 hover:to-accent-700 text-white text-sm font-bold rounded-full shadow-md shadow-accent-500/25 transition-all">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                    <?php echo e($mNavItem->label); ?>

                                </a>
                            <?php else: ?>
                                <a href="<?php echo e($mNavItem->url); ?>" class="flex items-center gap-3 px-4 py-3 text-sm text-neutral-700 hover:bg-neutral-50"><?php echo e($mNavItem->label); ?></a>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php else: ?>
                    <a href="<?php echo e(url('/')); ?>" class="flex items-center gap-3 px-4 py-3 text-sm text-neutral-700 hover:bg-neutral-50">Home</a>
                    <a href="<?php echo e(route('new-arrivals')); ?>" class="flex items-center gap-3 px-4 py-3 text-sm text-neutral-700 hover:bg-neutral-50">New Arrivals</a>
                    <a href="<?php echo e(route('bestsellers')); ?>" class="flex items-center gap-3 px-4 py-3 text-sm text-neutral-700 hover:bg-neutral-50">Bestsellers</a>
                    <a href="<?php echo e(route('offers')); ?>" class="flex items-center gap-3 px-4 py-3 text-sm text-error-600 hover:bg-error-50/50 font-medium">Deals & Offers</a>
                <?php endif; ?>

                <!-- Categories Section -->
                <div class="mt-2 pt-2 border-t border-neutral-100">
                    <p class="px-4 py-2 text-[11px] font-semibold text-neutral-600 uppercase tracking-wider">Shop by Category</p>

                    <?php $__currentLoopData = $navCategories ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php if($cat->children->count()): ?>
                            <div x-data="{ expanded: false }">
                                <button @click="expanded = !expanded"
                                        class="flex items-center justify-between w-full px-4 py-3 text-sm text-neutral-700 hover:bg-neutral-50 transition-colors">
                                    <span><?php echo e($cat->name); ?></span>
                                    <svg class="w-4 h-4 text-neutral-600 shrink-0 transition-transform duration-200" :class="expanded && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                                <div x-show="expanded" x-collapse>
                                    <div class="bg-neutral-50/50 py-1">
                                        <a href="<?php echo e(route('category.show', $cat)); ?>" class="block pl-8 pr-4 py-2 text-xs font-medium text-primary-600 hover:bg-neutral-100/50">
                                            View All <?php echo e($cat->name); ?>

                                        </a>
                                        <?php $__currentLoopData = $cat->children; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $child): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <a href="<?php echo e(route('category.show', $child)); ?>" class="block pl-8 pr-4 py-2 text-sm text-neutral-600 hover:text-primary-600 hover:bg-neutral-100/50">
                                                <?php echo e($child->name); ?>

                                            </a>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <a href="<?php echo e(route('category.show', $cat)); ?>" class="block px-4 py-3 text-sm text-neutral-700 hover:bg-neutral-50">
                                <?php echo e($cat->name); ?>

                            </a>
                        <?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                    <a href="<?php echo e(route('categories.index')); ?>" class="flex items-center gap-2 px-4 py-3 text-sm text-primary-600 hover:bg-primary-600/5 font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                        All Collections
                    </a>
                </div>

                <!-- Account Links -->
                <?php if(auth()->guard()->check()): ?>
                    <div class="mt-2 pt-2 border-t border-neutral-100">
                        <p class="px-4 py-2 text-[11px] font-semibold text-neutral-600 uppercase tracking-wider">My Account</p>

                        <a href="<?php echo e(route('account.dashboard')); ?>" class="flex items-center gap-3 px-4 py-3 text-sm text-neutral-700 hover:bg-neutral-50">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                            </svg>
                            Dashboard
                        </a>

                        <a href="<?php echo e(route('account.orders.index')); ?>" class="flex items-center gap-3 px-4 py-3 text-sm text-neutral-700 hover:bg-neutral-50">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                            My Orders
                        </a>

                        <a href="<?php echo e(route('wishlist')); ?>" class="flex items-center gap-3 px-4 py-3 text-sm text-neutral-700 hover:bg-neutral-50">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                            Wishlist
                        </a>

                        <a href="<?php echo e(route('account.profile')); ?>" class="flex items-center gap-3 px-4 py-3 text-sm text-neutral-700 hover:bg-neutral-50">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            Settings
                        </a>

                        <?php if(auth()->user()->deliveryPartner): ?>
                            <a href="<?php echo e(route('delivery.login')); ?>" class="flex items-center gap-3 px-4 py-3 text-sm text-primary-600 hover:bg-primary-600/5 font-medium">
                                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                                </svg>
                                Delivery Panel
                            </a>
                        <?php else: ?>
                            <a href="<?php echo e(route('account.become-delivery-partner')); ?>" class="flex items-center gap-3 px-4 py-3 text-sm text-primary-600 hover:bg-primary-600/5 font-medium">
                                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                                </svg>
                                Become Delivery Partner
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- Logout -->
                    <div class="mt-2 pt-2 border-t border-neutral-100 pb-4">
                        <form action="<?php echo e(route('logout')); ?>" method="POST">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="flex items-center gap-3 w-full px-4 py-3 text-sm text-neutral-600 hover:text-error-600 hover:bg-error-50/50 transition-colors">
                                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                                Logout
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </nav>
    </div>
</div>
<?php /**PATH D:\projects\grytlabs345\resources\views/partials/mobile-nav.blade.php ENDPATH**/ ?>