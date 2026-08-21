const CACHE_NAME = 'ipna-pms-v1';
const urlsToCache = [
    '/',
    '/assets/css/style.css',
    '/assets/js/app.js',
    '/assets/js/charts.js',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css',
    'https://cdn.jsdelivr.net/npm/vazirmatn@33.0.3/Vazirmatn-font-face.css',
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
    'https://cdn.jsdelivr.net/npm/chart.js@4.3.0/dist/chart.umd.min.js'
];

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
    // Network first for API calls, cache first for assets
    if (event.request.url.includes('/api/') || event.request.method === 'POST') {
        event.respondWith(
            fetch(event.request).catch(() => {
                return new Response(JSON.stringify({error: 'خطای شبکه'}), {
                    headers: { 'Content-Type': 'application/json' }
                });
            })
        );
    } else {
        event.respondWith(
            caches.match(event.request).then(response => {
                return response || fetch(event.request).then(fetchResponse => {
                    return caches.open(CACHE_NAME).then(cache => {
                        cache.put(event.request, fetchResponse.clone());
                        return fetchResponse;
                    });
                });
            })
        );
    }
});
