'use strict';

/* ============================================================
   BizPOS Pro - POS Service Worker
   Handles offline caching and sale queue for POS terminal
   ============================================================ */

var CACHE_NAME = 'bizpos-pos-v1';
var DB_NAME = 'bizpos_offline';
var DB_VERSION = 1;
var STORE_NAME = 'pending_sales';

/* Assets to pre-cache on install */
var PRE_CACHE_ASSETS = [
  '/vendor/bootstrap/css/bootstrap.min.css',
  '/vendor/fontawesome/css/all.min.css',
  '/vendor/nunito-sans/nunito-sans.css',
  '/css/style.css',
  '/vendor/jquery/jquery-3.7.1.min.js',
  '/vendor/bootstrap/js/bootstrap.bundle.min.js',
  '/js/app.js',
  '/js/pos-offline.js',
  '/vendor/nunito-sans/fonts/nunito-sans-400.woff2',
  '/vendor/nunito-sans/fonts/nunito-sans-500.woff2',
  '/vendor/nunito-sans/fonts/nunito-sans-600.woff2',
  '/vendor/nunito-sans/fonts/nunito-sans-700.woff2',
  '/vendor/nunito-sans/fonts/nunito-sans-800.woff2',
  '/vendor/nunito-sans/fonts/nunito-sans-900.woff2'
];

/* FontAwesome webfont files to cache on first load */
var FONT_EXTENSIONS = ['.woff2', '.woff', '.ttf'];

/* ---- IndexedDB helpers ---- */

function openDB() {
  return new Promise(function (resolve, reject) {
    var request = indexedDB.open(DB_NAME, DB_VERSION);
    request.onupgradeneeded = function (e) {
      var db = e.target.result;
      if (!db.objectStoreNames.contains(STORE_NAME)) {
        db.createObjectStore(STORE_NAME, { autoIncrement: true });
      }
    };
    request.onsuccess = function (e) { resolve(e.target.result); };
    request.onerror = function (e) { reject(e.target.error); };
  });
}

function addPendingSale(payload) {
  return openDB().then(function (db) {
    return new Promise(function (resolve, reject) {
      var tx = db.transaction(STORE_NAME, 'readwrite');
      var store = tx.objectStore(STORE_NAME);
      store.add({
        payload: payload,
        timestamp: Date.now(),
        status: 'pending'
      });
      tx.oncomplete = function () { resolve(); };
      tx.onerror = function (e) { reject(e.target.error); };
    });
  });
}

function getPendingSalesCount() {
  return openDB().then(function (db) {
    return new Promise(function (resolve, reject) {
      var tx = db.transaction(STORE_NAME, 'readonly');
      var store = tx.objectStore(STORE_NAME);
      var countReq = store.count();
      countReq.onsuccess = function () { resolve(countReq.result); };
      countReq.onerror = function (e) { reject(e.target.error); };
    });
  });
}

/* ---- Install: pre-cache assets ---- */

self.addEventListener('install', function (event) {
  event.waitUntil(
    caches.open(CACHE_NAME).then(function (cache) {
      return cache.addAll(PRE_CACHE_ASSETS);
    }).then(function () {
      return self.skipWaiting();
    })
  );
});

/* ---- Activate: clean old caches ---- */

self.addEventListener('activate', function (event) {
  event.waitUntil(
    caches.keys().then(function (cacheNames) {
      return Promise.all(
        cacheNames.filter(function (name) {
          return name.startsWith('bizpos-pos-') && name !== CACHE_NAME;
        }).map(function (name) {
          return caches.delete(name);
        })
      );
    }).then(function () {
      return self.clients.claim();
    })
  );
});

/* ---- Fetch: intercept requests ---- */

self.addEventListener('fetch', function (event) {
  var url = new URL(event.request.url);

  /* POST to process-sale: queue when offline */
  if (event.request.method === 'POST' && url.pathname.indexOf('/pos/process-sale') !== -1) {
    event.respondWith(handleProcessSale(event.request));
    return;
  }

  /* GET requests only from here on */
  if (event.request.method !== 'GET') {
    return;
  }

  /* Font files and static assets: cache-first */
  if (isStaticAsset(url)) {
    event.respondWith(cacheFirst(event.request));
    return;
  }

  /* POS page HTML: network-first, fall back to cache */
  if (url.pathname === '/pos' || url.pathname === '/pos/') {
    event.respondWith(networkFirst(event.request));
    return;
  }

  /* API / search calls: network-first */
  if (url.pathname.indexOf('/pos/') === 0) {
    event.respondWith(networkFirst(event.request));
    return;
  }
});

/* ---- Strategy: cache-first ---- */

function cacheFirst(request) {
  return caches.match(request).then(function (cached) {
    if (cached) {
      return cached;
    }
    return fetch(request).then(function (response) {
      if (response && response.status === 200) {
        var clone = response.clone();
        caches.open(CACHE_NAME).then(function (cache) {
          cache.put(request, clone);
        });
      }
      return response;
    });
  }).catch(function () {
    return new Response('', { status: 503, statusText: 'Service Unavailable' });
  });
}

/* ---- Strategy: network-first ---- */

function networkFirst(request) {
  return fetch(request).then(function (response) {
    if (response && response.status === 200) {
      var clone = response.clone();
      caches.open(CACHE_NAME).then(function (cache) {
        cache.put(request, clone);
      });
    }
    return response;
  }).catch(function () {
    return caches.match(request).then(function (cached) {
      return cached || new Response('Offline', { status: 503, statusText: 'Service Unavailable' });
    });
  });
}

/* ---- Handle offline sale POST ---- */

function handleProcessSale(request) {
  return fetch(request.clone()).catch(function () {
    /* Network failed — queue the sale in IndexedDB */
    return request.clone().json().then(function (payload) {
      return addPendingSale(payload).then(function () {
        return getPendingSalesCount();
      }).then(function (count) {
        var offlineInvoice = 'OFF-' + Date.now();
        var syntheticResponse = {
          success: true,
          offline: true,
          message: 'Sale queued for sync. You are currently offline.',
          data: {
            invoice_number: offlineInvoice,
            queued_count: count
          }
        };
        return new Response(JSON.stringify(syntheticResponse), {
          status: 200,
          headers: { 'Content-Type': 'application/json' }
        });
      });
    }).catch(function () {
      return new Response(JSON.stringify({
        success: false,
        offline: true,
        message: 'Failed to queue sale offline.'
      }), {
        status: 500,
        headers: { 'Content-Type': 'application/json' }
      });
    });
  });
}

/* ---- Helpers ---- */

function isStaticAsset(url) {
  var path = url.pathname;
  if (path.indexOf('/vendor/') === 0) return true;
  if (path.indexOf('/css/') === 0) return true;
  if (path.indexOf('/js/') === 0) return true;
  if (path.indexOf('/images/') === 0) return true;
  for (var i = 0; i < FONT_EXTENSIONS.length; i++) {
    if (path.indexOf(FONT_EXTENSIONS[i]) !== -1) return true;
  }
  return false;
}

/* ---- Listen for sync messages from client ---- */

self.addEventListener('message', function (event) {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});
