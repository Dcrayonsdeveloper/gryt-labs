import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';
import collapse from '@alpinejs/collapse';
import intersect from '@alpinejs/intersect';

import registerToastStore from './stores/toast.js';
import registerCartStore from './stores/cart.js';
import registerWishlistStore from './stores/wishlist.js';
import registerAuthModalStore from './stores/auth-modal.js';
import registerPdpPackStore from './stores/pdp-pack.js';

// Register plugins
Alpine.plugin(focus);
Alpine.plugin(collapse);
Alpine.plugin(intersect);

// Make Alpine available globally
window.Alpine = Alpine;

// Register all stores (toast first — others reference it)
registerToastStore(Alpine);
registerCartStore(Alpine);
registerWishlistStore(Alpine);
registerAuthModalStore(Alpine);
registerPdpPackStore(Alpine);

// Alpine.js reusable components
Alpine.data('dropdown', () => ({
    open: false,
    toggle() { this.open = !this.open; },
    close() { this.open = false; }
}));

Alpine.data('modal', (initialOpen = false) => ({
    open: initialOpen,
    show() { this.open = true; document.body.classList.add('overflow-hidden'); },
    hide() { this.open = false; document.body.classList.remove('overflow-hidden'); },
    toggle() { this.open ? this.hide() : this.show(); }
}));

Alpine.data('tabs', (initialTab = null) => ({
    activeTab: initialTab,
    isActive(tab) { return this.activeTab === tab; },
    select(tab) { this.activeTab = tab; }
}));

Alpine.data('accordion', (allowMultiple = false) => ({
    openItems: [],
    allowMultiple,
    isOpen(item) { return this.openItems.includes(item); },
    toggle(item) {
        if (this.isOpen(item)) {
            this.openItems = this.openItems.filter(i => i !== item);
        } else {
            this.openItems = this.allowMultiple ? [...this.openItems, item] : [item];
        }
    }
}));

Alpine.data('quantitySelector', (initialValue = 1, min = 1, max = 99) => ({
    quantity: initialValue, min, max,
    increment() { if (this.quantity < this.max) this.quantity++; },
    decrement() { if (this.quantity > this.min) this.quantity--; },
    set(value) { this.quantity = Math.max(this.min, Math.min(this.max, parseInt(value) || this.min)); }
}));

Alpine.data('imageGallery', (images = []) => ({
    images, currentIndex: 0,
    get currentImage() { return this.images[this.currentIndex] || null; },
    get hasMultiple() { return this.images.length > 1; },
    select(index) { if (index >= 0 && index < this.images.length) this.currentIndex = index; },
    next() { this.currentIndex = (this.currentIndex + 1) % this.images.length; },
    prev() { this.currentIndex = (this.currentIndex - 1 + this.images.length) % this.images.length; }
}));

Alpine.data('search', (endpoint = '/api/search') => ({
    query: '', results: [], isLoading: false, isOpen: false, selectedIndex: -1, endpoint,
    async search() {
        if (this.query.length < 2) { this.results = []; this.isOpen = false; return; }
        this.isLoading = true; this.isOpen = true;
        try {
            const response = await axios.get(this.endpoint, { params: { q: this.query } });
            this.results = response.data.results || [];
        } catch (error) { console.error('Search failed:', error); this.results = []; }
        finally { this.isLoading = false; }
    },
    clear() { this.query = ''; this.results = []; this.isOpen = false; this.selectedIndex = -1; },
    close() { this.isOpen = false; this.selectedIndex = -1; },
    selectNext() { if (this.selectedIndex < this.results.length - 1) this.selectedIndex++; },
    selectPrev() { if (this.selectedIndex > 0) this.selectedIndex--; },
    selectCurrent() { if (this.selectedIndex >= 0 && this.results[this.selectedIndex]) window.location.href = this.results[this.selectedIndex].url; }
}));

/**
 * Initialize stores on page load
 */
export function initStores() {
    Alpine.store('cart').fetch();
    if (document.body.dataset.authenticated === 'true') {
        Alpine.store('wishlist').fetch();
    }
}

/**
 * Start Alpine and init stores
 */
export function boot() {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initStores);
    } else {
        initStores();
    }
    Alpine.start();
}

export { Alpine };
