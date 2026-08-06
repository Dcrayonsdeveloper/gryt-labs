{{-- Pack-picker popup: opened via $dispatch('open-pack-picker', { productId, name, image, tiers }).
     Lets the customer pick a bundle (1 for ₹599, 2 for ₹999, …) before it's added to cart. --}}
<div x-data="{
        show: false, productId: null, name: '', image: '', heading: '', tiers: [], selected: 1, adding: false,
        open(d) {
            this.productId = d.productId; this.name = d.name || ''; this.image = d.image || '';
            this.heading = d.heading || 'Choose your pack & save';
            this.tiers = Array.isArray(d.tiers) ? d.tiers : [];
            // Default to the 2-pack deal when present, else the first tier.
            const two = this.tiers.find(t => t.qty === 2);
            this.selected = two ? 2 : (this.tiers[0]?.qty || 1);
            this.show = true;
        },
        get current() { return this.tiers.find(t => t.qty === this.selected) || this.tiers[0] || null; },
        money(n) { return '₹' + Number(n || 0).toLocaleString('en-IN'); },
        async confirm() {
            if (this.adding || !this.productId) return;
            this.adding = true;
            try { await $store.cart.add(this.productId, this.selected); this.show = false; }
            finally { this.adding = false; }
        }
     }"
     @open-pack-picker.window="open($event.detail)"
     x-show="show" x-cloak
     class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center"
     style="display:none">
    <div class="absolute inset-0 bg-black/50" @click="show = false" x-show="show" x-transition.opacity></div>

    <div class="relative bg-white w-full sm:max-w-md rounded-t-2xl sm:rounded-2xl shadow-xl max-h-[85vh] overflow-y-auto"
         @click.stop
         x-show="show"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="translate-y-full sm:translate-y-4 opacity-0"
         x-transition:enter-end="translate-y-0 opacity-100">

        <div class="flex items-center gap-3 p-4 border-b border-neutral-100">
            <img :src="image" :alt="name" class="w-12 h-12 rounded object-contain border border-neutral-100 bg-white shrink-0">
            <div class="flex-1 min-w-0">
                <p class="font-semibold text-sm text-neutral-900 truncate" x-text="name"></p>
                <p class="text-xs text-neutral-500" x-text="heading"></p>
            </div>
            <button type="button" @click="show = false" class="text-neutral-400 hover:text-neutral-700 text-lg leading-none px-1">&times;</button>
        </div>

        <div class="p-4 space-y-2">
            <template x-for="tier in tiers" :key="tier.qty">
                <label @click="selected = tier.qty"
                       class="flex items-center gap-3 p-3 rounded-lg border-2 cursor-pointer transition-colors"
                       :class="selected === tier.qty ? 'border-primary-600 bg-primary-50/40' : 'border-neutral-200 hover:border-neutral-300'">
                    <span class="w-4 h-4 rounded-full border-2 flex items-center justify-center shrink-0"
                          :class="selected === tier.qty ? 'border-primary-600' : 'border-neutral-300'">
                        <span x-show="selected === tier.qty" class="w-2 h-2 rounded-full bg-primary-600"></span>
                    </span>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="font-bold text-sm text-neutral-900" x-text="tier.qty === 1 ? '1 item' : tier.qty + ' items'"></span>
                            <span x-show="tier.badge" class="text-[9px] font-bold text-white bg-[#067D62] px-1.5 py-0.5 rounded" x-text="tier.badge"></span>
                        </div>
                        <span x-show="tier.savings > 0" class="block text-[11px] font-semibold text-green-700"
                              x-text="'Save ' + money(tier.savings) + (tier.savingsPct ? ' (' + tier.savingsPct + '%)' : '')"></span>
                    </div>
                    <div class="text-right shrink-0">
                        <span x-show="tier.mrp > tier.total" class="block text-[11px] text-neutral-400 line-through" x-text="money(tier.mrp)"></span>
                        <span class="font-bold text-neutral-900" x-text="money(tier.total)"></span>
                    </div>
                </label>
            </template>
        </div>

        <div class="p-4 pt-0">
            <button type="button" @click="confirm()" :disabled="adding"
                    class="w-full py-3 rounded-lg bg-primary-600 hover:bg-primary-700 disabled:opacity-60 text-white font-semibold text-sm transition-colors">
                <span x-show="!adding">Add <span x-text="current ? money(current.total) : ''"></span> to Cart</span>
                <span x-show="adding" x-cloak>Adding…</span>
            </button>
        </div>
    </div>
</div>
