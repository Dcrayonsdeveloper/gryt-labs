import './bootstrap';

// Alpine.js Core
import Alpine from 'alpinejs';

// Alpine.js Plugins
import focus from '@alpinejs/focus';
import collapse from '@alpinejs/collapse';
import intersect from '@alpinejs/intersect';

// Register plugins
Alpine.plugin(focus);
Alpine.plugin(collapse);
Alpine.plugin(intersect);

// Make Alpine available globally
window.Alpine = Alpine;

// ========================================
// Global Utilities
// ========================================

/**
 * Format currency (INR by default)
 */
window.formatCurrency = function(amount, currency = 'INR') {
    return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: currency,
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(amount);
};

/**
 * Debounce function
 */
window.debounce = function(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
};

/**
 * Throttle function
 */
window.throttle = function(func, limit) {
    let inThrottle;
    return function(...args) {
        if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
};

// ========================================
// Alpine.js Global Data/Stores
// ========================================

/**
 * Toast notification store
 */
Alpine.store('toast', {
    items: [],

    show(message, type = 'info', duration = 3000) {
        const id = Date.now();
        this.items.push({ id, message, type });

        if (duration > 0) {
            setTimeout(() => this.remove(id), duration);
        }

        return id;
    },

    success(message, duration = 3000) {
        return this.show(message, 'success', duration);
    },

    error(message, duration = 5000) {
        return this.show(message, 'error', duration);
    },

    warning(message, duration = 4000) {
        return this.show(message, 'warning', duration);
    },

    info(message, duration = 3000) {
        return this.show(message, 'info', duration);
    },

    remove(id) {
        this.items = this.items.filter(item => item.id !== id);
    },

    clear() {
        this.items = [];
    }
});

/**
 * Cart store
 */
Alpine.store('cart', {
    items: [],
    recommendations: [],
    itemCount: 0,
    isOpen: false,
    isLoading: false,

    get count() {
        return this.itemCount;
    },

    get subtotal() {
        return this.items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    },

    _updateCount() {
        this.itemCount = this.items.reduce((sum, item) => sum + item.quantity, 0);
    },

    async fetch() {
        this.isLoading = true;
        try {
            const response = await axios.get('/cart/data');
            this.items = response.data.items || [];
            this.recommendations = response.data.recommendations || [];
            this.itemCount = response.data.cart_count || this.items.reduce((sum, item) => sum + item.quantity, 0);
        } catch (error) {
            console.error('Failed to fetch cart:', error);
        } finally {
            this.isLoading = false;
        }
    },

    async add(productId, quantity = 1, variantId = null) {
        if (this.isLoading) return; // Prevent double-click
        this.isLoading = true;
        try {
            const response = await axios.post('/cart/add', {
                product_id: productId,
                variant_id: variantId,
                quantity: quantity
            });
            // Update count INSTANTLY from response (no re-fetch needed)
            if (response.data.cart_count !== undefined) {
                this.itemCount = response.data.cart_count;
            }
            // Show instant success feedback
            Alpine.store('toast').success(response.data.message || 'Added to cart');
            // GA4: add_to_cart
            if (typeof gtag !== 'undefined' && response.data.ga4_item) {
                gtag('event', 'add_to_cart', {
                    currency: 'INR',
                    value: response.data.ga4_item.price * response.data.ga4_item.quantity,
                    items: [response.data.ga4_item]
                });
            }
            // Facebook Pixel: AddToCart
            if (typeof fbq !== 'undefined' && response.data.fb_event) {
                fbq('track', 'AddToCart', {
                    content_ids: response.data.fb_event.content_ids,
                    content_name: response.data.fb_event.content_name,
                    content_type: response.data.fb_event.content_type,
                    value: response.data.fb_event.value,
                    currency: response.data.fb_event.currency,
                }, {eventID: response.data.fb_event.event_id});
            }
            // Open cart sidebar immediately
            this.open();
            // Fetch cart details in background
            this.fetch();
        } catch (error) {
            const msg = error.response?.data?.error || 'Failed to add to cart';
            Alpine.store('toast').error(msg);
            console.error('Failed to add to cart:', error);
        } finally {
            this.isLoading = false;
        }
    },

    async update(itemId, quantity) {
        this.isLoading = true;
        try {
            const response = await axios.put(`/cart/${itemId}`, {
                quantity: quantity
            });
            if (response.data.cart_count !== undefined) {
                this.itemCount = response.data.cart_count;
            }
            await this.fetch();
        } catch (error) {
            Alpine.store('toast').error('Failed to update cart');
            console.error('Failed to update cart:', error);
        } finally {
            this.isLoading = false;
        }
    },

    async remove(itemId) {
        this.isLoading = true;
        try {
            const response = await axios.delete(`/cart/${itemId}`);
            // GA4: remove_from_cart
            if (typeof gtag !== 'undefined' && response.data.ga4_removed_item) {
                gtag('event', 'remove_from_cart', {
                    currency: 'INR',
                    value: response.data.ga4_removed_item.price * response.data.ga4_removed_item.quantity,
                    items: [response.data.ga4_removed_item]
                });
            }
            Alpine.store('toast').info('Item removed from cart');
            await this.fetch();
        } catch (error) {
            Alpine.store('toast').error('Failed to remove item');
            console.error('Failed to remove from cart:', error);
        } finally {
            this.isLoading = false;
        }
    },

    toggle() {
        this.isOpen = !this.isOpen;
    },

    open() {
        this.isOpen = true;
    },

    close() {
        this.isOpen = false;
    }
});

/**
 * Wishlist store
 */
Alpine.store('wishlist', {
    items: [],
    isLoading: false,

    get count() {
        return this.items.length;
    },

    has(productId) {
        return this.items.some(item => item.product_id === productId);
    },

    async fetch() {
        this.isLoading = true;
        try {
            const response = await axios.get('/wishlist', {
                headers: { 'Accept': 'application/json' }
            });
            this.items = response.data.items || [];
        } catch (error) {
            console.error('Failed to fetch wishlist:', error);
        } finally {
            this.isLoading = false;
        }
    },

    async toggle(productId) {
        // Show login modal if not authenticated
        if (document.body.dataset.authenticated !== 'true') {
            Alpine.store('authModal').open();
            return;
        }

        this.isLoading = true;
        try {
            if (this.has(productId)) {
                await axios.delete(`/wishlist/${productId}`);
                this.items = this.items.filter(item => item.product_id !== productId);
                Alpine.store('toast').info('Removed from wishlist');
            } else {
                const response = await axios.post(`/wishlist/${productId}`);
                this.items.push({ product_id: productId });
                Alpine.store('toast').success('Added to wishlist');
                // Facebook Pixel: AddToWishlist
                if (typeof fbq !== 'undefined' && response.data.fb_event) {
                    fbq('track', 'AddToWishlist', {
                        content_ids: response.data.fb_event.content_ids,
                        content_name: response.data.fb_event.content_name,
                        content_type: response.data.fb_event.content_type,
                        value: response.data.fb_event.value,
                        currency: response.data.fb_event.currency,
                    }, {eventID: response.data.fb_event.event_id});
                }
            }
        } catch (error) {
            if (error.response && error.response.status === 401) {
                Alpine.store('authModal').open();
                return;
            }
            Alpine.store('toast').error('Failed to update wishlist');
            console.error('Failed to toggle wishlist:', error);
        } finally {
            this.isLoading = false;
        }
    }
});

/**
 * Auth Modal store
 */
Alpine.store('authModal', {
    isOpen: false,
    isLoading: false,
    mode: 'login',
    errors: {},
    message: '',

    open(mode = 'login') {
        this.mode = mode;
        this.errors = {};
        this.message = '';
        this.isOpen = true;
        document.body.style.overflow = 'hidden';
    },

    close() {
        this.isOpen = false;
        this.errors = {};
        this.message = '';
        document.body.style.overflow = '';
    },

    switchMode(mode) {
        this.mode = mode;
        this.errors = {};
        this.message = '';
    },

    async login(email, password, remember) {
        this.isLoading = true;
        this.errors = {};
        try {
            const response = await axios.post('/login', {
                email: email,
                password: password,
                remember: remember
            });
            this.close();
            window.location.reload();
        } catch (error) {
            if (error.response && error.response.status === 422) {
                this.errors = error.response.data.errors || {};
                if (error.response.data.message) {
                    this.message = error.response.data.message;
                }
            } else {
                this.message = 'Something went wrong. Please try again.';
            }
        } finally {
            this.isLoading = false;
        }
    },

    async register(name, email, password, passwordConfirmation) {
        this.isLoading = true;
        this.errors = {};
        try {
            const response = await axios.post('/register', {
                full_name: name,
                email: email,
                password: password,
                password_confirmation: passwordConfirmation,
                terms: true
            });
            this.close();
            window.location.reload();
        } catch (error) {
            if (error.response && error.response.status === 422) {
                this.errors = error.response.data.errors || {};
                if (error.response.data.message) {
                    this.message = error.response.data.message;
                }
            } else {
                this.message = 'Something went wrong. Please try again.';
            }
        } finally {
            this.isLoading = false;
        }
    }
});

// ========================================
// Alpine.js Reusable Components
// ========================================

/**
 * Dropdown component
 */
Alpine.data('dropdown', () => ({
    open: false,

    toggle() {
        this.open = !this.open;
    },

    close() {
        this.open = false;
    }
}));

/**
 * Modal component
 */
Alpine.data('modal', (initialOpen = false) => ({
    open: initialOpen,

    show() {
        this.open = true;
        document.body.classList.add('overflow-hidden');
    },

    hide() {
        this.open = false;
        document.body.classList.remove('overflow-hidden');
    },

    toggle() {
        if (this.open) {
            this.hide();
        } else {
            this.show();
        }
    }
}));

/**
 * Tabs component
 */
Alpine.data('tabs', (initialTab = null) => ({
    activeTab: initialTab,

    isActive(tab) {
        return this.activeTab === tab;
    },

    select(tab) {
        this.activeTab = tab;
    }
}));

/**
 * Accordion component
 */
Alpine.data('accordion', (allowMultiple = false) => ({
    openItems: [],
    allowMultiple: allowMultiple,

    isOpen(item) {
        return this.openItems.includes(item);
    },

    toggle(item) {
        if (this.isOpen(item)) {
            this.openItems = this.openItems.filter(i => i !== item);
        } else {
            if (this.allowMultiple) {
                this.openItems.push(item);
            } else {
                this.openItems = [item];
            }
        }
    }
}));

/**
 * Quantity selector component
 */
Alpine.data('quantitySelector', (initialValue = 1, min = 1, max = 99) => ({
    quantity: initialValue,
    min: min,
    max: max,

    increment() {
        if (this.quantity < this.max) {
            this.quantity++;
        }
    },

    decrement() {
        if (this.quantity > this.min) {
            this.quantity--;
        }
    },

    set(value) {
        const num = parseInt(value) || this.min;
        this.quantity = Math.max(this.min, Math.min(this.max, num));
    }
}));

/**
 * Product Pack Selector Store
 * Shared state between "Choose Your Pack" UI and the buy box.
 * Selecting a pack drives the buy-box price, Add to Cart and Buy Now quantities.
 */
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

/**
 * Image gallery component
 */
Alpine.data('imageGallery', (images = []) => ({
    images: images,
    currentIndex: 0,

    get currentImage() {
        return this.images[this.currentIndex] || null;
    },

    get hasMultiple() {
        return this.images.length > 1;
    },

    select(index) {
        if (index >= 0 && index < this.images.length) {
            this.currentIndex = index;
        }
    },

    next() {
        this.currentIndex = (this.currentIndex + 1) % this.images.length;
    },

    prev() {
        this.currentIndex = (this.currentIndex - 1 + this.images.length) % this.images.length;
    }
}));

/**
 * Search component with debounce
 */
Alpine.data('search', (endpoint = '/api/search') => ({
    query: '',
    results: [],
    isLoading: false,
    isOpen: false,
    selectedIndex: -1,
    endpoint: endpoint,

    async search() {
        if (this.query.length < 2) {
            this.results = [];
            this.isOpen = false;
            return;
        }

        this.isLoading = true;
        this.isOpen = true;

        try {
            const response = await axios.get(this.endpoint, {
                params: { q: this.query }
            });
            this.results = response.data.results || [];
        } catch (error) {
            console.error('Search failed:', error);
            this.results = [];
        } finally {
            this.isLoading = false;
        }
    },

    clear() {
        this.query = '';
        this.results = [];
        this.isOpen = false;
        this.selectedIndex = -1;
    },

    close() {
        this.isOpen = false;
        this.selectedIndex = -1;
    },

    selectNext() {
        if (this.selectedIndex < this.results.length - 1) {
            this.selectedIndex++;
        }
    },

    selectPrev() {
        if (this.selectedIndex > 0) {
            this.selectedIndex--;
        }
    },

    selectCurrent() {
        if (this.selectedIndex >= 0 && this.results[this.selectedIndex]) {
            window.location.href = this.results[this.selectedIndex].url;
        }
    }
}));

// ========================================
// Initialize on page load
// ========================================

function initStores() {
    // Always fetch cart (works for both guests and authenticated users)
    Alpine.store('cart').fetch();

    // Wishlist only for authenticated users
    if (document.body.dataset.authenticated === 'true') {
        Alpine.store('wishlist').fetch();
    }
}

// Handle timing: if DOM already loaded (module scripts can run late), init immediately
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initStores);
} else {
    initStores();
}

// ========================================
// Start Alpine.js (MUST be after all stores and components are registered)
// ========================================
Alpine.start();
