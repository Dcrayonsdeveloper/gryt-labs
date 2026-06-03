export default function registerAuthModalStore(Alpine) {
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
                await axios.post('/login', { email, password, remember });
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
                await axios.post('/register', {
                    full_name: name,
                    email,
                    password,
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
}
