export default function registerToastStore(Alpine) {
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
}
