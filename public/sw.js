/*
 * Service worker minimo della UI operatore Roosko (scope `/roosko/`).
 *
 * Mette in cache SOLO asset statici (bundle vite, css/js/font di Filament, icone), con
 * strategia stale-while-revalidate: la UI si apre velocemente anche con rete debole.
 * Pagine HTML, richieste Livewire (POST) e API passano sempre dalla rete: niente dati
 * di dominio in cache. L'outbox offline delle scansioni e' un passo successivo (docs/README.md).
 */
const CACHE = 'roosko-static-v1'
const PRECACHE = ['/assets/roosko/manifest.webmanifest', '/assets/roosko/icon.svg']
const STATIC_PATH = /^\/(assets|build|js|css|fonts|vendor)\//
const STATIC_EXTENSION = /\.(?:js|css|woff2?|ttf|svg|png|jpe?g|webp|ico|webmanifest)$/

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE)
            .then((cache) => cache.addAll(PRECACHE))
            .catch(() => undefined)
            .then(() => self.skipWaiting()),
    )
})

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((key) => key.startsWith('roosko-static-') && key !== CACHE).map((key) => caches.delete(key))))
            .then(() => self.clients.claim()),
    )
})

self.addEventListener('fetch', (event) => {
    const request = event.request
    if (request.method !== 'GET') {
        return
    }

    const url = new URL(request.url)
    if (url.origin !== self.location.origin || ! STATIC_PATH.test(url.pathname) || ! STATIC_EXTENSION.test(url.pathname)) {
        return
    }

    event.respondWith(
        caches.open(CACHE).then(async (cache) => {
            const cached = await cache.match(request)
            const network = fetch(request)
                .then((response) => {
                    if (response.ok) {
                        cache.put(request, response.clone())
                    }
                    return response
                })
                .catch(() => cached)

            return cached ?? network
        }),
    )
})
