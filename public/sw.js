'use strict';

/* ============================================================
   BizPOS Pro - Main Service Worker (PWA)
   Handles app-wide caching, offline support, and push notifications
   ============================================================ */

// HTML pages are intentionally never cached (they carry per-session CSRF
// tokens), so there is only a static-asset cache here.
var STATIC_CACHE_NAME = 'bizpos-static-v5';

/* Static assets to pre-cache on install */
var STATIC_ASSETS = [
  '/vendor/bootstrap/css/bootstrap.min.css',
  '/vendor/fontawesome/css/all.min.css',
  '/vendor/nunito-sans/nunito-sans.css',
  '/css/style.css',
  '/vendor/jquery/jquery-3.7.1.min.js',
  '/vendor/bootstrap/js/bootstrap.bundle.min.js',
  '/js/app.js'
];
/* NOTE: /manifest.json is intentionally NOT pre-cached. It's generated from the
   business name in Settings, so it must be fetched fresh (see fetch handler). */

/* ── Install ── */
self.addEventListener('install', function (event) {
  event.waitUntil(
    caches.open(STATIC_CACHE_NAME).then(function (cache) {
      return cache.addAll(STATIC_ASSETS);
    })
  );
  self.skipWaiting();
});

/* ── Activate — clean old caches ── */
self.addEventListener('activate', function (event) {
  var validCaches = [STATIC_CACHE_NAME, 'bizpos-pos-v1'];
  event.waitUntil(
    caches.keys().then(function (cacheNames) {
      return Promise.all(
        cacheNames
          .filter(function (name) { return validCaches.indexOf(name) === -1; })
          .map(function (name) { return caches.delete(name); })
      );
    })
  );
  self.clients.claim();
});

/* ── Fetch — network-first for HTML, cache-first for static assets ── */
self.addEventListener('fetch', function (event) {
  var url = new URL(event.request.url);

  // Skip non-GET requests
  if (event.request.method !== 'GET') {
    return;
  }

  // Skip API requests — let them pass through
  if (url.pathname.startsWith('/api/')) {
    return;
  }

  // PWA manifest — network-first so a regenerated business name shows up
  // (it's a small file built from Settings; never pin it to the cache).
  if (url.pathname === '/manifest.json') {
    event.respondWith(
      fetch(event.request).catch(function () {
        return caches.match(event.request);
      })
    );
    return;
  }

  // Static assets (CSS, JS, fonts, images) — cache-first
  if (isStaticAsset(url.pathname)) {
    event.respondWith(
      caches.match(event.request).then(function (cached) {
        return cached || fetch(event.request).then(function (response) {
          if (response.ok) {
            var clone = response.clone();
            caches.open(STATIC_CACHE_NAME).then(function (cache) {
              cache.put(event.request, clone);
            });
          }
          return response;
        });
      })
    );
    return;
  }

  // HTML pages — NETWORK ONLY. Never cache or replay full pages: each one
  // embeds a per-session CSRF token, and a cached page carries a stale token
  // that triggers a 419 on the next POST. When offline, show a lightweight
  // generated page (never a stale real one) instead.
  if (event.request.mode === 'navigate' ||
      (event.request.headers.get('accept') || '').indexOf('text/html') !== -1) {
    event.respondWith(
      fetch(event.request).catch(function () {
        return new Response(
          '<!doctype html><html><head><meta charset="utf-8">' +
          '<meta name="viewport" content="width=device-width, initial-scale=1">' +
          '<title>Offline</title></head>' +
          '<body style="font-family:\'Roboto\',sans-serif;text-align:center;padding:3rem;color:#7d7b7b">' +
          '<h1 style="font-family:\'Jost\',sans-serif;color:#333333;font-weight:600">You’re offline</h1>' +
          '<p>Please check your connection and try again.</p>' +
          '<button onclick="location.reload()" ' +
          'style="padding:12px 25px;border:0;background:#0A69D8;color:#fff;font-weight:500;font-size:15px;text-transform:capitalize;cursor:pointer">' +
          'Retry</button></body></html>',
          { headers: { 'Content-Type': 'text/html; charset=utf-8' } }
        );
      })
    );
    return;
  }
});

/* ── Push notifications (for low-stock alerts, order updates) ── */
self.addEventListener('push', function (event) {
  if (!event.data) {
    return;
  }

  var data = event.data.json();
  var title = data.title || 'BizPOS Pro';
  var options = {
    body: data.body || '',
    icon: '/images/icons/icon-192x192.png',
    badge: '/images/icons/icon-72x72.png',
    tag: data.tag || 'bizpos-notification',
    data: data.url ? { url: data.url } : {}
  };

  event.waitUntil(self.registration.showNotification(title, options));
});

/* ── Notification click — open the app to the relevant page ── */
self.addEventListener('notificationclick', function (event) {
  event.notification.close();

  var url = event.notification.data && event.notification.data.url
    ? event.notification.data.url
    : '/';

  event.waitUntil(
    self.clients.matchAll({ type: 'window' }).then(function (clients) {
      for (var i = 0; i < clients.length; i++) {
        if (clients[i].url === url && 'focus' in clients[i]) {
          return clients[i].focus();
        }
      }
      return self.clients.openWindow(url);
    })
  );
});

/* ── Helper: check if URL is a static asset ── */
function isStaticAsset(pathname) {
  return /\.(css|js|woff2?|ttf|eot|svg|png|jpe?g|gif|ico|webp|json)$/i.test(pathname)
    || pathname.startsWith('/vendor/')
    || pathname.startsWith('/images/')
    || pathname.startsWith('/fonts/');
}
