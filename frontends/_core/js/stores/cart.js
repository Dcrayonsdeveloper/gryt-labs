export default function registerCartStore(Alpine) {
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
            if (this.isLoading) return;
            this.isLoading = true;
            try {
                const response = await axios.post('/cart/add', {
                    product_id: productId,
                    variant_id: variantId,
                    quantity: quantity
                });
                if (response.data.cart_count !== undefined) {
                    this.itemCount = response.data.cart_count;
                }
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
                this.open();
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
                const response = await axios.put(`/cart/${itemId}`, { quantity: quantity });
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

        toggle() { this.isOpen = !this.isOpen; },
        open() { this.isOpen = true; },
        close() { this.isOpen = false; }
    });
}
