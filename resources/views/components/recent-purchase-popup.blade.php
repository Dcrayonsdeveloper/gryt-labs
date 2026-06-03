{{-- Recent Purchase Social Proof Popup — bottom-left corner --}}
<div
    x-data="recentPurchasePopup()"
    x-init="init()"
    class="fixed bottom-24 sm:bottom-6 left-4 z-70 w-72 pointer-events-none"
    aria-live="polite"
>
    <template x-if="current">
        <div
            x-show="visible"
            x-transition:enter="transition ease-out duration-400"
            x-transition:enter-start="opacity-0 -translate-x-4"
            x-transition:enter-end="opacity-100 translate-x-0"
            x-transition:leave="transition ease-in duration-300"
            x-transition:leave-start="opacity-100 translate-x-0"
            x-transition:leave-end="opacity-0 -translate-x-4"
            class="bg-white rounded-xl shadow-xl border border-neutral-100 overflow-hidden pointer-events-auto"
        >
            {{-- Close --}}
            <button
                @click="dismiss()"
                class="absolute top-2 right-2 w-5 h-5 flex items-center justify-center rounded-full bg-neutral-100 hover:bg-neutral-200 text-neutral-400 hover:text-neutral-600 transition-colors"
                aria-label="Dismiss"
            >
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            <div class="flex gap-3 p-3 pr-7">
                {{-- Product image --}}
                <a :href="current.url" class="shrink-0 w-16 h-16 rounded-lg overflow-hidden bg-neutral-50 border border-neutral-100 block">
                    <img
                        :src="current.image"
                        :alt="current.product"
                        class="w-full h-full object-cover"
                        loading="lazy"
                        onerror="this.src='/images/no-product-image.svg'"
                    >
                </a>

                {{-- Info --}}
                <div class="flex-1 min-w-0">
                    <p class="text-xs text-neutral-500 leading-snug">
                        <span class="font-semibold text-neutral-800" x-text="current.name"></span>
                        from
                        <span x-text="current.location"></span>
                        purchased
                    </p>
                    <p class="text-xs font-bold text-neutral-900 mt-0.5 leading-snug line-clamp-2" x-text="current.product"></p>
                    <p class="text-[11px] text-neutral-400 mt-1" x-text="current.ago"></p>
                </div>
            </div>

            {{-- Buttons --}}
            <div class="flex border-t border-neutral-100">
                <a
                    :href="current.url"
                    class="flex-1 py-2.5 text-xs font-semibold text-center text-white bg-neutral-900 hover:bg-neutral-700 transition-colors"
                >
                    See Details
                </a>
                <div class="w-px bg-neutral-100"></div>
                <button
                    @click="addToCart()"
                    class="flex-1 py-2.5 text-xs font-semibold text-center text-white bg-neutral-900 hover:bg-neutral-700 transition-colors"
                    :disabled="adding"
                    x-text="adding ? 'Adding...' : 'Add to cart'"
                ></button>
            </div>
        </div>
    </template>
</div>

<script>
function recentPurchasePopup() {
    return {
        items: [],
        current: null,
        visible: false,
        adding: false,
        dismissed: false,
        index: 0,
        timer: null,

        async init() {
            if (sessionStorage.getItem('sp_dismissed')) return;
            try {
                const res = await fetch('/social-proof/recent');
                if (!res.ok) return;
                this.items = await res.json();
                if (!this.items.length) return;
                // Shuffle for variety
                this.items = this.items.sort(() => Math.random() - 0.5);
                // Start after 4s delay
                setTimeout(() => this.showNext(), 4000);
            } catch(e) {}
        },

        showNext() {
            if (this.dismissed || !this.items.length) return;
            this.current = this.items[this.index % this.items.length];
            this.index++;
            this.visible = true;
            // Hide after 6s, show next after 10s
            this.timer = setTimeout(() => {
                this.visible = false;
                setTimeout(() => this.showNext(), 4000);
            }, 6000);
        },

        dismiss() {
            this.dismissed = true;
            this.visible = false;
            clearTimeout(this.timer);
            sessionStorage.setItem('sp_dismissed', '1');
        },

        async addToCart() {
            if (this.adding || !this.current?.product_id) return;
            this.adding = true;
            try {
                const res = await fetch('/cart/add', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ product_id: this.current.product_id, quantity: 1 }),
                });
                if (res.ok) {
                    if (typeof Alpine !== 'undefined' && Alpine.store('cart')?.fetch) Alpine.store('cart').fetch();
                    if (typeof Alpine !== 'undefined' && Alpine.store('toast')?.add) Alpine.store('toast').add('Added to cart!', 'success');
                }
            } catch(e) {}
            finally { this.adding = false; }
        },
    };
}
</script>
