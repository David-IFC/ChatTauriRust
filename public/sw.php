<?php
// Servir el Service Worker con headers correctos para que Chrome lo acepte.
header('Content-Type: application/javascript; charset=utf-8');
header('Service-Worker-Allowed: /');
header('Cache-Control: no-cache, no-store');
?>
/* Service Worker - Chat PWA */
const CACHE = 'chat-pwa-v6';
const ASSETS = [
    './index.php',
    './assets/style.css',
    './assets/app.js',
    './icons/icon-192.png',
    './icons/icon-512.png',
];

// Instalar: pre-cache tolerante a fallos individuales
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE).then((cache) =>
            Promise.allSettled(
                ASSETS.map(url => cache.add(url).catch(e => console.warn('SW cache miss:', url, e)))
            )
        ).then(() => self.skipWaiting())
    );
});

// Activar: limpiar caches viejas
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k)))
        ).then(() => self.clients.claim())
    );
});

// Fetch (solo assets estáticos del mismo origen; el resto pasa directo a la red):
//  - JS / CSS  => NETWORK-FIRST (siempre la última versión; caché solo si no hay red).
//  - Imágenes / fuentes => CACHE-FIRST (rara vez cambian).
self.addEventListener('fetch', (event) => {
    const req = event.request;
    const url = new URL(req.url);

    if (req.method !== 'GET' || url.origin !== self.location.origin) {
        return;
    }

    const isCode  = /\.(css|js)$/i.test(url.pathname);
    const isMedia = /\.(png|svg|webp|ico|woff2?)$/i.test(url.pathname);
    if (!isCode && !isMedia) {
        return; // navegaciones, api.php, manifest, etc. => red directa
    }

    if (isCode) {
        // Network-first: red, y si falla, caché.
        event.respondWith(
            fetch(req).then((res) => {
                if (res && res.ok) {
                    const copy = res.clone();
                    caches.open(CACHE).then((c) => c.put(req, copy)).catch(() => {});
                }
                return res;
            }).catch(() => caches.match(req).then((c) => c || Response.error()))
        );
        return;
    }

    // Cache-first para imágenes/fuentes.
    event.respondWith(
        caches.match(req).then((cached) => {
            if (cached) return cached;
            return fetch(req).then((res) => {
                if (res && res.ok) {
                    const copy = res.clone();
                    caches.open(CACHE).then((c) => c.put(req, copy)).catch(() => {});
                }
                return res;
            }).catch(() => cached || Response.error());
        })
    );
});
