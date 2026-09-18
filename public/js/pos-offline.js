'use strict';

/* ============================================================
   BizPOS Pro - POS Offline Queue Manager
   Manages service worker registration, online/offline status,
   IndexedDB queue, and sync of offline sales.
   ============================================================ */

(function () {

  var DB_NAME = 'bizpos_offline';
  var DB_VERSION = 1;
  var STORE_NAME = 'pending_sales';
  var SYNC_ENDPOINT = '/pos/process-sale';

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

  function getAllPendingSales() {
    return openDB().then(function (db) {
      return new Promise(function (resolve, reject) {
        var tx = db.transaction(STORE_NAME, 'readonly');
        var store = tx.objectStore(STORE_NAME);
        var results = [];
        var cursorReq = store.openCursor();
        cursorReq.onsuccess = function (e) {
          var cursor = e.target.result;
          if (cursor) {
            results.push({ key: cursor.key, value: cursor.value });
            cursor.continue();
          } else {
            resolve(results);
          }
        };
        cursorReq.onerror = function (e) { reject(e.target.error); };
      });
    });
  }

  function deleteSale(key) {
    return openDB().then(function (db) {
      return new Promise(function (resolve, reject) {
        var tx = db.transaction(STORE_NAME, 'readwrite');
        var store = tx.objectStore(STORE_NAME);
        store.delete(key);
        tx.oncomplete = function () { resolve(); };
        tx.onerror = function (e) { reject(e.target.error); };
      });
    });
  }

  function markSaleFailed(key) {
    return openDB().then(function (db) {
      return new Promise(function (resolve, reject) {
        var tx = db.transaction(STORE_NAME, 'readwrite');
        var store = tx.objectStore(STORE_NAME);
        var getReq = store.get(key);
        getReq.onsuccess = function () {
          var record = getReq.result;
          if (record) {
            record.status = 'failed';
            store.put(record, key);
          }
          tx.oncomplete = function () { resolve(); };
        };
        tx.onerror = function (e) { reject(e.target.error); };
      });
    });
  }

  function getPendingCount() {
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

  /* ---- UI helpers ---- */

  function showOfflineBar() {
    var bar = document.getElementById('offlineBar');
    if (bar) {
      bar.classList.add('active');
    }
  }

  function hideOfflineBar() {
    var bar = document.getElementById('offlineBar');
    if (bar) {
      bar.classList.remove('active');
    }
  }

  function updateQueueBadge() {
    getPendingCount().then(function (count) {
      var badge = document.getElementById('offlineQueueBadge');
      if (!badge) return;
      if (count > 0) {
        badge.textContent = count;
        badge.classList.remove('d-none');
      } else {
        badge.textContent = '0';
        badge.classList.add('d-none');
      }
    }).catch(function () {
      /* IndexedDB not available — ignore */
    });
  }

  function showNotification(message, type) {
    /* Use existing BizPOS alert pattern if available, else simple alert bar */
    var alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    var $alert = $(
      '<div class="alert ' + alertClass + ' alert-dismissible fade show" ' +
      'role="alert">' +
      message +
      '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
      '</div>'
    );
    var $container = $('.bp-pos-layout');
    if ($container.length) {
      $container.prepend($alert);
      setTimeout(function () { $alert.alert('close'); }, 5000);
    }
  }

  /* ---- Sync logic ---- */

  function syncPendingSales() {
    if (!navigator.onLine) return Promise.resolve();

    return getAllPendingSales().then(function (sales) {
      var pending = sales.filter(function (s) {
        return s.value.status === 'pending' || s.value.status === 'failed';
      });

      if (pending.length === 0) return;

      var csrfToken = $('meta[name="csrf-token"]').attr('content') || '';
      var synced = 0;
      var failed = 0;

      /* Process sales sequentially to avoid race conditions */
      var chain = Promise.resolve();
      pending.forEach(function (sale) {
        chain = chain.then(function () {
          return $.ajax({
            url: SYNC_ENDPOINT,
            method: 'POST',
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            data: JSON.stringify(sale.value.payload),
            timeout: 15000
          }).then(function (response) {
            if (response && response.success) {
              synced++;
              return deleteSale(sale.key);
            } else {
              failed++;
              return markSaleFailed(sale.key);
            }
          }).catch(function () {
            failed++;
            return markSaleFailed(sale.key);
          });
        });
      });

      return chain.then(function () {
        updateQueueBadge();
        if (synced > 0) {
          showNotification(
            '<i class="fa-solid fa-check-circle me-1"></i>' +
            synced + ' offline sale(s) synced successfully.',
            'success'
          );
        }
        if (failed > 0) {
          showNotification(
            '<i class="fa-solid fa-exclamation-triangle me-1"></i>' +
            failed + ' offline sale(s) failed to sync. They will be retried.',
            'danger'
          );
        }
      });
    }).catch(function (err) {
      console.error('BizPOS: Offline sync error', err);
    });
  }

  /* ---- Online/Offline event listeners ---- */

  function handleOnlineStatus() {
    if (navigator.onLine) {
      hideOfflineBar();
      syncPendingSales();
    } else {
      showOfflineBar();
    }
  }

  window.addEventListener('online', handleOnlineStatus);
  window.addEventListener('offline', handleOnlineStatus);

  /* ---- Service Worker registration ---- */

  function registerServiceWorker() {
    if (!('serviceWorker' in navigator)) {
      console.warn('BizPOS: Service workers not supported.');
      return;
    }

    navigator.serviceWorker.register('/sw-pos.js', { scope: '/pos' })
      .then(function (registration) {
        console.log('BizPOS: SW registered, scope:', registration.scope);

        registration.addEventListener('updatefound', function () {
          var newWorker = registration.installing;
          if (newWorker) {
            newWorker.addEventListener('statechange', function () {
              if (newWorker.state === 'activated') {
                console.log('BizPOS: New service worker activated.');
              }
            });
          }
        });
      })
      .catch(function (err) {
        console.error('BizPOS: SW registration failed:', err);
      });
  }

  /* ---- Initialize ---- */

  $(document).ready(function () {
    registerServiceWorker();
    handleOnlineStatus();
    updateQueueBadge();
  });

  /* Expose for external use (e.g., after a sale is queued from SW) */
  window.BizPOS = window.BizPOS || {};
  window.BizPOS.offline = {
    syncPendingSales: syncPendingSales,
    updateQueueBadge: updateQueueBadge,
    getPendingCount: getPendingCount
  };

})();
