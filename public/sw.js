const CACHE_NAME = 'ipna-pms-v3';
const urlsToCache = [
    '/assets/css/style.css',
    '/assets/js/app.js',
    '/assets/js/charts.js',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css',
    'https://cdn.jsdelivr.net/npm/vazirmatn@33.0.3/Vazirmatn-font-face.css',
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
    'https://cdn.jsdelivr.net/npm/chart.js@4.3.0/dist/chart.umd.min.js'
];

const isStaticAsset = (url) => url.origin === location.origin && /\.(css|js|png|jpg|jpeg|gif|svg|woff2?|json|ico)$/i.test(url.pathname);
const isApi = (url) => url.pathname.includes('/api/');

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => cache.addAll(urlsToCache))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(names =>
            Promise.all(
                names.filter(name => name !== CACHE_NAME)
                    .map(name => caches.delete(name))
            )
        ).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', event => {
    const req = event.request;
    const url = new URL(req.url);

    // Never cache state-changing requests; always go to network.
    if (req.method !== 'GET' || isApi(url)) {
        return; // let the browser handle it normally
    }

    // Cache-first for static assets (already cached).
    if (isStaticAsset(url)) {
        event.respondWith(
            caches.match(req).then(cached => cached || fetch(req).then(res => {
                if (res.ok && url.origin === location.origin) {
                    const copy = res.clone();
                    caches.open(CACHE_NAME).then(c => c.put(req, copy));
                }
                return res;
            }))
        );
        return;
    }

    // Network-first for page navigations/HTML: always fresh, fall back to cache offline.
    event.respondWith(
        fetch(req).then(res => {
            if (res.ok && url.origin === location.origin) {
                const copy = res.clone();
                caches.open(CACHE_NAME).then(c => c.put(req, copy));
            }
            return res;
        }).catch(() => caches.match(req).then(cached => cached || caches.match('/')))
    );
});
