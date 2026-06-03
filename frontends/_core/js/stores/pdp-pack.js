export default function registerPdpPackStore(Alpine) {
    Alpine.store('pdpPack', {
        enabled: false,
        packs: [],
        selected: 1,
        init(packs, defaultQty = 1) {
            this.packs = Array.isArray(packs) ? packs : [];
            this.enabled = this.packs.length > 0;
            this.selected = defaultQty;
        },
        select(qty) {
            if (this.packs.find(p => p.qty === qty)) {
                this.selected = qty;
            }
        },
        get current() {
            return this.packs.find(p => p.qty === this.selected) || this.packs[0] || null;
        },
        get currentQty() { return this.current ? this.current.qty : 1; },
        get currentPrice() { return this.current ? this.current.price : 0; },
        get currentMrp() { return this.current ? this.current.mrp : 0; },
        get currentSavings() { return this.current ? this.current.savings : 0; },
        get currentSavingsPct() { return this.current ? this.current.savingsPct : 0; },
        get pricePerPack() {
            if (!this.current || this.current.qty < 1) return 0;
            return Math.round(this.current.price / this.current.qty);
        },
        formatPrice(n) {
            return '₹' + Math.round(n).toLocaleString('en-IN');
        }
    });
}
