import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.headers.common['Accept'] = 'application/json';

let token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
}

// Auto-refresh CSRF token on 419 (token mismatch) and retry the request once
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
            } catch (e) {
                return Promise.reject(error);
            }
        }
        return Promise.reject(error);
    }
);
