<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['style' => 'default']));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['style' => 'default']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>


<section <?php echo e($attributes->merge(['class' => 'py-8 border-t border-b border-[#E3E6E6] bg-white'])); ?>>
    <div class="container mx-auto px-4">
        
        <div class="flex md:hidden gap-4 overflow-x-auto pb-2" style="scrollbar-width:none;-ms-overflow-style:none;-webkit-overflow-scrolling:touch;">
            <style>.trust-mobile-scroll::-webkit-scrollbar{display:none;}</style>
            <?php $__currentLoopData = [
                ['icon' => '<svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/></svg>', 'title' => '24-Hour Dispatch', 'desc' => 'Fast shipping'],
                ['icon' => '<svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-2.25-1.313M21 7.5v2.25m0-2.25l-2.25 1.313M3 7.5l2.25-1.313M3 7.5l2.25 1.313M3 7.5v2.25m9 3l2.25-1.313M12 12.75l-2.25-1.313M12 12.75V15m0 6.75l2.25-1.313M12 21.75V15m0 0l-2.25-1.313M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-18 0l9 5.25 9-5.25"/></svg>', 'title' => 'Easy Returns', 'desc' => $theme->get('return_policy_days', 7) . '-day hassle-free'],
                ['icon' => '<svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>', 'title' => 'Secure Payment', 'desc' => '100% encrypted'],
                ['icon' => '<svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/></svg>', 'title' => 'Quality Assured', 'desc' => '100% genuine'],
            ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $badge): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="flex items-center gap-3 shrink-0 bg-[#f8f6f3] rounded-xl px-4 py-3" style="min-width:200px;">
                    <div class="shrink-0"><?php echo $badge['icon']; ?></div>
                    <div>
                        <p class="text-xs font-semibold text-[#0F1111]"><?php echo e($badge['title']); ?></p>
                        <p class="text-[10px] text-[#3a3a3a]"><?php echo e($badge['desc']); ?></p>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        
        <div class="hidden md:grid md:grid-cols-4 gap-8">
            
            <div class="flex flex-col items-center text-center gap-2.5 opacity-0 translate-y-4 transition-all duration-500 ease-out"
                 x-data x-intersect.once="$el.classList.add('opacity-100', 'translate-y-0'); $el.classList.remove('opacity-0', 'translate-y-4')"
                 style="transition-delay: 0ms">
                <div class="w-14 h-14 flex items-center justify-center">
                    <svg class="w-10 h-10 text-primary-600" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-[#0F1111]">24-Hour Dispatch</p>
                    <p class="text-xs text-[#3a3a3a] mt-0.5">Fast shipping on all orders</p>
                </div>
            </div>

            
            <div class="flex flex-col items-center text-center gap-2.5 opacity-0 translate-y-4 transition-all duration-500 ease-out"
                 x-data x-intersect.once="$el.classList.add('opacity-100', 'translate-y-0'); $el.classList.remove('opacity-0', 'translate-y-4')"
                 style="transition-delay: 100ms">
                <div class="w-14 h-14 flex items-center justify-center">
                    <svg class="w-10 h-10 text-primary-600" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-2.25-1.313M21 7.5v2.25m0-2.25l-2.25 1.313M3 7.5l2.25-1.313M3 7.5l2.25 1.313M3 7.5v2.25m9 3l2.25-1.313M12 12.75l-2.25-1.313M12 12.75V15m0 6.75l2.25-1.313M12 21.75V15m0 0l-2.25-1.313M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-18 0l9 5.25 9-5.25"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-[#0F1111]">Easy Returns</p>
                    <p class="text-xs text-[#3a3a3a] mt-0.5"><?php echo e($theme->get('return_policy_days', 7)); ?>-day hassle-free returns</p>
                </div>
            </div>

            
            <div class="flex flex-col items-center text-center gap-2.5 opacity-0 translate-y-4 transition-all duration-500 ease-out"
                 x-data x-intersect.once="$el.classList.add('opacity-100', 'translate-y-0'); $el.classList.remove('opacity-0', 'translate-y-4')"
                 style="transition-delay: 200ms">
                <div class="w-14 h-14 flex items-center justify-center">
                    <svg class="w-10 h-10 text-primary-600" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-[#0F1111]">Secure Payment</p>
                    <p class="text-xs text-[#3a3a3a] mt-0.5">100% safe & encrypted</p>
                </div>
            </div>

            
            <div class="flex flex-col items-center text-center gap-2.5 opacity-0 translate-y-4 transition-all duration-500 ease-out"
                 x-data x-intersect.once="$el.classList.add('opacity-100', 'translate-y-0'); $el.classList.remove('opacity-0', 'translate-y-4')"
                 style="transition-delay: 300ms">
                <div class="w-14 h-14 flex items-center justify-center">
                    <svg class="w-10 h-10 text-primary-600" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-[#0F1111]">Quality Assured</p>
                    <p class="text-xs text-[#3a3a3a] mt-0.5">100% genuine products</p>
                </div>
            </div>
        </div>
    </div>
</section>
<?php /**PATH D:\projects\grytlabs345\resources\views/components/trust-badges.blade.php ENDPATH**/ ?>