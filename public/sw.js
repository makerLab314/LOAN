/*
 * Service Worker: macht LOAN als App installierbar und zeigt ohne Verbindung eine Hinweisseite.
 * Seiten mit Daten werden bewusst NIE zwischengespeichert – Geräte werden geteilt (Kiosk),
 * und Ausleihstände müssen immer aktuell sein. Nur Build-Dateien (Hash im Namen) und die
 * Offline-Seite landen im Cache.
 */
const VERSION = 'loan-v1';
const OFFLINE = '/offline.html';

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(VERSION).then((cache) => cache.add(OFFLINE)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((key) => key !== VERSION).map((key) => caches.delete(key))))
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    if (request.method !== 'GET') return;

    const url = new URL(request.url);
    if (url.origin !== self.location.origin) return;

    // Seiten: immer aus dem Netz, nur ohne Verbindung die Offline-Seite
    if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(() => caches.match(OFFLINE)));
        return;
    }

    // Build-Dateien ändern nie ihren Inhalt (Hash im Dateinamen)
    if (url.pathname.startsWith('/build/assets/')) {
        event.respondWith(caches.open(VERSION).then(async (cache) => {
            const hit = await cache.match(request);
            if (hit) return hit;
            const response = await fetch(request);
            if (response.ok) cache.put(request, response.clone());
            return response;
        }));
    }
});
