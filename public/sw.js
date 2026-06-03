/**
 * Main App Service Worker
 *
 * Strategy:
 *  - Precache critical app shell (layout, CSS, JS)
 *  - Cache-first for static assets (images, fonts, CSS, JS)
 *  - Network-first for HTML pages with offline fallback
 *  - Stale-while-revalidate for API data & product images
 *  - Offline fallback page for navigation requests
 */

const CACHE_VERSION = 'store-v3';
const STATIC_CACHE = `static-${CACHE_VERSION}`;
const DYNAMIC_CACHE = `dynamic-${CACHE_VERSION}`;
const IMAGE_CACHE = `images-${CACHE_VERSION}`;

// Max items per cache to prevent storage bloat
const DYNAMIC_CACHE_LIMIT = 50;
const IMAGE_CACHE_LIMIT = 100;

// Assets to precache on install
const PRECACHE_URLS = [
    '/',
    '/offline',
    '/manifest.json',
];

// ─── Install ────────────────────────────────────────────────────
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then(cache => cache.addAll(PRECACHE_URLS))
            .then(() => self.skipWaiting())
    );
});

// ─── Activate — clean old caches ────────────────────────────────
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(
                keys
                    .filter(k => k !== STATIC_CACHE && k !== DYNAMIC_CACHE && k !== IMAGE_CACHE)
                    .map(k => caches.delete(k))
            )
        ).then(() => self.clients.claim())
    );
});

// ─── Fetch ──────────────────────────────────────────────────────
self.addEventListener('fetch', event => {
    const { request } = event;
    const url = new URL(request.url);

    // Skip non-GET, chrome-extension, external origins
    if (request.method !== 'GET') return;
    if (url.protocol === 'chrome-extension:') return;
    if (url.origin !== location.origin) return;

    // Skip admin, seller, POS, and API mutation routes
    if (url.pathname.startsWith('/admin') ||
        url.pathname.startsWith('/seller') ||
        url.pathname.startsWith('/pos') ||
        url.pathname.startsWith('/api/webhook')) {
        return;
    }

    // Product & storage images → cache-first with limit
    if (isImageRequest(url, request)) {
        event.respondWith(cacheFirstWithLimit(request, IMAGE_CACHE, IMAGE_CACHE_LIMIT));
        return;
    }

    // Static assets (CSS, JS, fonts) → cache-first
    if (isStaticAsset(url)) {
        event.respondWith(cacheFirst(request, STATIC_CACHE));
        return;
    }

    // API data routes → stale-while-revalidate
    if (url.pathname.startsWith('/api/')) {
        event.respondWith(staleWhileRevalidate(request, DYNAMIC_CACHE, DYNAMIC_CACHE_LIMIT));
        return;
    }

    // HTML pages → always fetch from network (never serve stale HTML)
    if (request.headers.get('accept')?.includes('text/html')) {
        event.respondWith(fetch(request).catch(() => caches.match('/offline')));
        return;
    }

    // Everything else → network with cache fallback
    event.respondWith(
        fetch(request)
            .then(response => {
                if (response.ok) {
                    const clone = response.clone();
                    caches.open(DYNAMIC_CACHE).then(c => c.put(request, clone));
                }
                return response;
            })
            .catch(() => caches.match(request))
    );
});

// ─── Strategies ─────────────────────────────────────────────────

function cacheFirst(request, cacheName) {
    return caches.match(request).then(cached => {
        if (cached) return cached;
        return fetch(request).then(response => {
            if (response.ok) {
                const clone = response.clone();
                caches.open(cacheName).then(c => c.put(request, clone));
            }
            return response;
        });
    });
}

function cacheFirstWithLimit(request, cacheName, limit) {
    return caches.match(request).then(cached => {
        if (cached) return cached;
        return fetch(request).then(response => {
            if (response.ok) {
                const clone = response.clone();
                caches.open(cacheName).then(c => {
                    c.put(request, clone);
                    trimCache(cacheName, limit);
                });
            }
            return response;
        }).catch(() => {
            // Return a transparent 1x1 pixel for failed image loads
            return new Response(
                'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg"/>',
                { headers: { 'Content-Type': 'image/svg+xml' } }
            );
        });
    });
}

function networkFirstWithFallback(request) {
    return fetch(request)
        .then(response => {
            if (response.ok) {
                const clone = response.clone();
                caches.open(DYNAMIC_CACHE).then(c => c.put(request, clone));
            }
            return response;
        })
        .catch(() =>
            caches.match(request).then(cached => cached || caches.match('/offline'))
        );
}

function staleWhileRevalidate(request, cacheName, limit) {
    return caches.open(cacheName).then(cache =>
        cache.match(request).then(cached => {
            const fetchPromise = fetch(request).then(response => {
                if (response.ok) {
                    cache.put(request, response.clone());
                    trimCache(cacheName, limit);
                }
                return response;
            }).catch(() => cached);

            return cached || fetchPromise;
        })
    );
}

// ─── Helpers ────────────────────────────────────────────────────

function isImageRequest(url, request) {
    return url.pathname.startsWith('/storage/') ||
        url.pathname.startsWith('/images/') ||
        /\.(jpg|jpeg|png|gif|webp|avif|svg|ico)$/i.test(url.pathname) ||
        request.destination === 'image';
}

function isStaticAsset(url) {
    return url.pathname.startsWith('/build/') ||
        /\.(css|js|woff2?|ttf|eot)$/i.test(url.pathname);
}

function trimCache(cacheName, maxItems) {
    caches.open(cacheName).then(cache =>
        cache.keys().then(keys => {
            if (keys.length > maxItems) {
                cache.delete(keys[0]).then(() => trimCache(cacheName, maxItems));
            }
        })
    );
}

// ─── Push Notifications ─────────────────────────────────────────

self.addEventListener('push', event => {
    if (!event.data) return;

    let data;
    try {
        data = event.data.json();
    } catch (e) {
        data = { title: 'New Notification', body: event.data.text() };
    }

    const options = {
        body: data.body || '',
        icon: data.icon || '/images/logo.png',
        badge: '/images/logo.png',
        image: data.image || undefined,
        data: { url: data.url || '/' },
        actions: data.actions || [],
        vibrate: [100, 50, 100],
        tag: data.tag || 'store-notification',
        renotify: true,
    };

    event.waitUntil(
        self.registration.showNotification(data.title || 'New Notification', options)
    );
});

self.addEventListener('notificationclick', event => {
    event.notification.close();

    const url = event.notification.data?.url || '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then(windowClients => {
                for (const client of windowClients) {
                    if (client.url === url && 'focus' in client) {
                        return client.focus();
                    }
                }
                return clients.openWindow(url);
            })
    );
});
