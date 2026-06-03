
<?php if(auth()->guard()->guest()): ?>
<?php
    $discountPercent = (int) $theme->get('exit_popup_discount', 0);
    $hasDiscount = $discountPercent > 0;
    if (!$hasDiscount) return; // Don't render if no discount configured
    $storeLogo = $theme->get('store_logo', '');
    $storeName = $theme->get('store_name', config('app.name'));
    $popupTitle = $theme->get('exit_popup_title', "Get {$discountPercent}% Off Your First Order!");
    $popupButtonText = $theme->get('exit_popup_button_text', 'Claim Discount');
    $popupFooterText = $theme->get('exit_popup_footer_text', 'You are signing up to receive communication via email and can unsubscribe at any time.');
?>


<div x-data="exitIntent()" x-show="show" x-cloak
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-9999 flex items-center justify-center p-4"
     style="background:rgba(0,0,0,0.45);backdrop-filter:blur(3px);"
     @click.self="dismiss()"
     @keydown.escape.window="dismiss()">

    
    <div x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="bg-white rounded-2xl shadow-2xl w-full max-w-sm relative overflow-hidden">

        
        <button @click="dismiss()" class="absolute top-3 right-3 w-7 h-7 flex items-center justify-center rounded-full bg-neutral-100 hover:bg-neutral-200 transition-colors z-10">
            <svg class="w-4 h-4 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>

        <div class="px-6 pt-8 pb-6 text-center">
            
            <?php if($storeLogo): ?>
                <img src="<?php echo e(asset($storeLogo)); ?>" alt="<?php echo e($storeName); ?>" class="h-10 w-auto mx-auto mb-5 object-contain">
            <?php else: ?>
                <p class="text-base font-bold text-neutral-800 mb-5"><?php echo e($storeName); ?></p>
            <?php endif; ?>

            
            <div x-show="!success">
                <h2 class="text-xl sm:text-2xl font-extrabold text-neutral-900 leading-snug mb-5">"<?php echo e($popupTitle); ?>"</h2>

                <form @submit.prevent="submitEmail()" class="flex flex-col gap-3">
                    <input type="email" x-model="email" required autocomplete="email"
                           placeholder="Email address"
                           class="w-full px-4 py-3 border border-neutral-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary-400 placeholder-neutral-400">
                    <button type="submit" :disabled="submitting"
                            class="w-full py-3 rounded-lg font-semibold text-sm transition-colors disabled:opacity-60"
                            style="background-color:var(--color-primary-200,#fce7f3);color:#1a1a1a;">
                        <span x-show="!submitting"><?php echo e($popupButtonText); ?></span>
                        <span x-show="submitting" x-cloak>
                            <svg class="animate-spin w-4 h-4 inline" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        </span>
                    </button>
                </form>

                <button @click="dismiss()" class="mt-3 text-sm text-primary-600 hover:underline font-medium">No, thanks</button>
                <p class="text-[11px] text-neutral-400 mt-3 leading-relaxed"><?php echo e($popupFooterText); ?></p>
            </div>

            
            <div x-show="success" x-cloak class="py-4">
                <svg class="w-12 h-12 text-green-500 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p class="text-base font-semibold text-neutral-800">Check your inbox!</p>
                <p class="text-sm text-neutral-500 mt-1">Your <?php echo e($discountPercent); ?>% off code is on its way.</p>
            </div>
        </div>
    </div>
</div>

<script>
function exitIntent() {
    return {
        show: false, email: '', submitting: false, success: false,
        init() {
            if (localStorage.getItem('exit_popup_subscribed')) return;
            if (sessionStorage.getItem('exit_popup_shown')) return;
            const showPopup = () => { if (this.show) return; this.show = true; sessionStorage.setItem('exit_popup_shown', '1'); };
            setTimeout(showPopup, 8000);
            document.addEventListener('mouseout', (e) => { if (e.clientY < 5) showPopup(); }, { once: true });
        },
        dismiss() { this.show = false; sessionStorage.setItem('exit_popup_shown', '1'); },
        async submitEmail() {
            if (!this.email || this.submitting) return;
            this.submitting = true;
            try {
                await fetch('/newsletter/subscribe', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify({ email: this.email, source: 'exit_intent_popup', first_buyer_discount: true }),
                });
                this.success = true;
                localStorage.setItem('exit_popup_subscribed', '1');
                setTimeout(() => this.dismiss(), 4000);
            } catch (e) { console.error(e); }
            finally { this.submitting = false; }
        }
    };
}
</script>
<?php endif; ?>
<?php /**PATH D:\projects\grytlabs345\resources\views/components/exit-intent-popup.blade.php ENDPATH**/ ?>