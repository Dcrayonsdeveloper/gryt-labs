/**
 * Default Frontend — Complete standalone JS
 * No shared _core imports. Fully self-contained.
 */

// ========================================
// Axios Bootstrap
// ========================================
import axios from 'axios';
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.headers.common['Accept'] = 'application/json';

let token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
}

window.axios.interceptors.response.use(
    response => response,
    async error => {
        const originalRequest = error.config;
        if (error.response?.status === 419 && !originalRequest._retried) {
            originalRequest._retried = true;
            try {
                const res = await axios.get('/csrf-token');
                const newToken = res.data.token;
                document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', newToken);
                window.axios.defaults.headers.common['X-CSRF-TOKEN'] = newToken;
                originalRequest.headers['X-CSRF-TOKEN'] = newToken;
                return window.axios(originalRequest);
            } catch (e) { return Promise.reject(error); }
        }
        return Promise.reject(error);
    }
);

// ========================================
// Alpine.js
// ========================================
import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';
import collapse from '@alpinejs/collapse';
import intersect from '@alpinejs/intersect';

Alpine.plugin(focus);
Alpine.plugin(collapse);
Alpine.plugin(intersect);
window.Alpine = Alpine;

// ========================================
// Utilities
// ========================================
window.formatCurrency = function(amount, currency = 'INR') {
    return new Intl.NumberFormat('en-IN', {
        style: 'currency', currency, minimumFractionDigits: 0, maximumFractionDigits: 2,
    }).format(amount);
};

window.debounce = function(func, wait) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func(...args), wait);
    };
};

window.throttle = function(func, limit) {
    let inThrottle;
    return function(...args) {
        if (!inThrottle) { func.apply(this, args); inThrottle = true; setTimeout(() => inThrottle = false, limit); }
    };
};

// ========================================
// Alpine Stores
// ========================================

Alpine.store('toast', {
    items: [],
    show(message, type = 'info', duration = 3000) {
        const id = Date.now();
        this.items.push({ id, message, type });
        if (duration > 0) setTimeout(() => this.remove(id), duration);
        return id;
    },
    success(msg, d = 3000) { return this.show(msg, 'success', d); },
    error(msg, d = 5000) { return this.show(msg, 'error', d); },
    warning(msg, d = 4000) { return this.show(msg, 'warning', d); },
    info(msg, d = 3000) { return this.show(msg, 'info', d); },
    remove(id) { this.items = this.items.filter(i => i.id !== id); },
    clear() { this.items = []; }
});

Alpine.store('cart', {
    items: [], recommendations: [], itemCount: 0, isOpen: false, isLoading: false,
    get count() { return this.itemCount; },
    get subtotal() { return this.items.reduce((s, i) => s + (i.price * i.quantity), 0); },
    async fetch() {
        this.isLoading = true;
        try {
            const r = await axios.get('/cart/data');
            this.items = r.data.items || [];
            this.recommendations = r.data.recommendations || [];
            this.itemCount = r.data.cart_count || this.items.reduce((s, i) => s + i.quantity, 0);
        } catch (e) { console.error('Failed to fetch cart:', e); }
        finally { this.isLoading = false; }
    },
    async add(productId, quantity = 1, variantId = null) {
        if (this.isLoading) return;
        this.isLoading = true;
        try {
            const r = await axios.post('/cart/add', { product_id: productId, variant_id: variantId, quantity });
            if (r.data.cart_count !== undefined) this.itemCount = r.data.cart_count;
            Alpine.store('toast').success(r.data.message || 'Added to cart');
            if (typeof gtag !== 'undefined' && r.data.ga4_item) gtag('event', 'add_to_cart', { currency: 'INR', value: r.data.ga4_item.price * r.data.ga4_item.quantity, items: [r.data.ga4_item] });
            if (typeof fbq !== 'undefined' && r.data.fb_event) fbq('track', 'AddToCart', { content_ids: r.data.fb_event.content_ids, content_name: r.data.fb_event.content_name, content_type: r.data.fb_event.content_type, value: r.data.fb_event.value, currency: r.data.fb_event.currency }, { eventID: r.data.fb_event.event_id });
            this.open();
            this.fetch();
        } catch (e) { Alpine.store('toast').error(e.response?.data?.error || 'Failed to add to cart'); }
        finally { this.isLoading = false; }
    },
    async update(itemId, quantity) {
        this.isLoading = true;
        try { const r = await axios.put(`/cart/${itemId}`, { quantity }); if (r.data.cart_count !== undefined) this.itemCount = r.data.cart_count; await this.fetch(); }
        catch (e) { Alpine.store('toast').error('Failed to update cart'); }
        finally { this.isLoading = false; }
    },
    async remove(itemId) {
        this.isLoading = true;
        try {
            const r = await axios.delete(`/cart/${itemId}`);
            if (typeof gtag !== 'undefined' && r.data.ga4_removed_item) gtag('event', 'remove_from_cart', { currency: 'INR', value: r.data.ga4_removed_item.price * r.data.ga4_removed_item.quantity, items: [r.data.ga4_removed_item] });
            Alpine.store('toast').info('Item removed from cart');
            await this.fetch();
        } catch (e) { Alpine.store('toast').error('Failed to remove item'); }
        finally { this.isLoading = false; }
    },
    toggle() { this.isOpen = !this.isOpen; },
    open() { this.isOpen = true; },
    close() { this.isOpen = false; }
});

Alpine.store('wishlist', {
    items: [], isLoading: false,
    get count() { return this.items.length; },
    has(productId) { return this.items.some(i => i.product_id === productId); },
    async fetch() {
        this.isLoading = true;
        try { const r = await axios.get('/wishlist', { headers: { 'Accept': 'application/json' } }); this.items = r.data.items || []; }
        catch (e) { console.error('Failed to fetch wishlist:', e); }
        finally { this.isLoading = false; }
    },
    async toggle(productId) {
        if (document.body.dataset.authenticated !== 'true') { Alpine.store('authModal').open(); return; }
        this.isLoading = true;
        try {
            if (this.has(productId)) { await axios.delete(`/wishlist/${productId}`); this.items = this.items.filter(i => i.product_id !== productId); Alpine.store('toast').info('Removed from wishlist'); }
            else { const r = await axios.post(`/wishlist/${productId}`); this.items.push({ product_id: productId }); Alpine.store('toast').success('Added to wishlist'); if (typeof fbq !== 'undefined' && r.data.fb_event) fbq('track', 'AddToWishlist', { content_ids: r.data.fb_event.content_ids, content_name: r.data.fb_event.content_name, content_type: r.data.fb_event.content_type, value: r.data.fb_event.value, currency: r.data.fb_event.currency }, { eventID: r.data.fb_event.event_id }); }
        } catch (e) { if (e.response?.status === 401) { Alpine.store('authModal').open(); return; } Alpine.store('toast').error('Failed to update wishlist'); }
        finally { this.isLoading = false; }
    }
});

Alpine.store('authModal', {
    isOpen: false, isLoading: false, mode: 'login', errors: {}, message: '',
    open(mode = 'login') { this.mode = mode; this.errors = {}; this.message = ''; this.isOpen = true; document.body.style.overflow = 'hidden'; },
    close() { this.isOpen = false; this.errors = {}; this.message = ''; document.body.style.overflow = ''; },
    switchMode(mode) { this.mode = mode; this.errors = {}; this.message = ''; },
    async login(email, password, remember) {
        this.isLoading = true; this.errors = {};
        try { await axios.post('/login', { email, password, remember }); this.close(); window.location.reload(); }
        catch (e) { if (e.response?.status === 422) { this.errors = e.response.data.errors || {}; if (e.response.data.message) this.message = e.response.data.message; } else this.message = 'Something went wrong. Please try again.'; }
        finally { this.isLoading = false; }
    },
    async register(name, email, password, passwordConfirmation) {
        this.isLoading = true; this.errors = {};
        try { await axios.post('/register', { full_name: name, email, password, password_confirmation: passwordConfirmation, terms: true }); this.close(); window.location.reload(); }
        catch (e) { if (e.response?.status === 422) { this.errors = e.response.data.errors || {}; if (e.response.data.message) this.message = e.response.data.message; } else this.message = 'Something went wrong. Please try again.'; }
        finally { this.isLoading = false; }
    }
});

Alpine.store('pdpPack', {
    enabled: false, packs: [], selected: 1,
    init(packs, defaultQty = 1) { this.packs = Array.isArray(packs) ? packs : []; this.enabled = this.packs.length > 0; this.selected = defaultQty; },
    select(qty) { if (this.packs.find(p => p.qty === qty)) this.selected = qty; },
    get current() { return this.packs.find(p => p.qty === this.selected) || this.packs[0] || null; },
    get currentQty() { return this.current ? this.current.qty : 1; },
    get currentPrice() { return this.current ? this.current.price : 0; },
    get currentMrp() { return this.current ? this.current.mrp : 0; },
    get currentSavings() { return this.current ? this.current.savings : 0; },
    get currentSavingsPct() { return this.current ? this.current.savingsPct : 0; },
    get pricePerPack() { if (!this.current || this.current.qty < 1) return 0; return Math.round(this.current.price / this.current.qty); },
    formatPrice(n) { return '₹' + Math.round(n).toLocaleString('en-IN'); }
});

// ========================================
// Alpine Components
// ========================================
Alpine.data('dropdown', () => ({ open: false, toggle() { this.open = !this.open; }, close() { this.open = false; } }));
Alpine.data('modal', (initialOpen = false) => ({ open: initialOpen, show() { this.open = true; document.body.classList.add('overflow-hidden'); }, hide() { this.open = false; document.body.classList.remove('overflow-hidden'); }, toggle() { this.open ? this.hide() : this.show(); } }));
Alpine.data('tabs', (initialTab = null) => ({ activeTab: initialTab, isActive(tab) { return this.activeTab === tab; }, select(tab) { this.activeTab = tab; } }));
Alpine.data('accordion', (allowMultiple = false) => ({ openItems: [], allowMultiple, isOpen(item) { return this.openItems.includes(item); }, toggle(item) { if (this.isOpen(item)) this.openItems = this.openItems.filter(i => i !== item); else this.openItems = this.allowMultiple ? [...this.openItems, item] : [item]; } }));
Alpine.data('quantitySelector', (initialValue = 1, min = 1, max = 99) => ({ quantity: initialValue, min, max, increment() { if (this.quantity < this.max) this.quantity++; }, decrement() { if (this.quantity > this.min) this.quantity--; }, set(value) { this.quantity = Math.max(this.min, Math.min(this.max, parseInt(value) || this.min)); } }));
Alpine.data('imageGallery', (images = []) => ({ images, currentIndex: 0, get currentImage() { return this.images[this.currentIndex] || null; }, get hasMultiple() { return this.images.length > 1; }, select(index) { if (index >= 0 && index < this.images.length) this.currentIndex = index; }, next() { this.currentIndex = (this.currentIndex + 1) % this.images.length; }, prev() { this.currentIndex = (this.currentIndex - 1 + this.images.length) % this.images.length; } }));
Alpine.data('search', (endpoint = '/api/search') => ({ query: '', results: [], isLoading: false, isOpen: false, selectedIndex: -1, endpoint, async search() { if (this.query.length < 2) { this.results = []; this.isOpen = false; return; } this.isLoading = true; this.isOpen = true; try { const r = await axios.get(this.endpoint, { params: { q: this.query } }); this.results = r.data.results || []; } catch (e) { this.results = []; } finally { this.isLoading = false; } }, clear() { this.query = ''; this.results = []; this.isOpen = false; this.selectedIndex = -1; }, close() { this.isOpen = false; this.selectedIndex = -1; }, selectNext() { if (this.selectedIndex < this.results.length - 1) this.selectedIndex++; }, selectPrev() { if (this.selectedIndex > 0) this.selectedIndex--; }, selectCurrent() { if (this.selectedIndex >= 0 && this.results[this.selectedIndex]) window.location.href = this.results[this.selectedIndex].url; } }));

// ========================================
// Init & Start
// ========================================
function initStores() {
    Alpine.store('cart').fetch();
    if (document.body.dataset.authenticated === 'true') Alpine.store('wishlist').fetch();
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initStores);
else initStores();

Alpine.start();
