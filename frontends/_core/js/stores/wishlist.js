export default function registerWishlistStore(Alpine) {
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
}
