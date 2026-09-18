'use strict';

/* ============================================================
   BizPOS Pro — Web Push subscription client
   - Reads VAPID public key from <meta name="vapid-public-key">
   - Subscribes the browser to the push service
   - Posts the subscription to the backend (push.subscribe route)
   - Reflects state on a toggle button: #pushToggle
   ============================================================ */

(function () {
  if (!window.BizPOS) {
    window.BizPOS = {};
  }

  var META_KEY = 'vapid-public-key';
  var SUBSCRIBE_URL   = '/push/subscribe';
  var UNSUBSCRIBE_URL = '/push/unsubscribe';
  var TEST_URL        = '/push/test';

  var api = {
    isSupported: function () {
      return 'serviceWorker' in navigator
        && 'PushManager' in window
        && 'Notification' in window;
    },

    permission: function () {
      if (!('Notification' in window)) return 'unsupported';
      return Notification.permission; // 'default' | 'granted' | 'denied'
    },

    getVapidKey: function () {
      var meta = document.querySelector('meta[name="' + META_KEY + '"]');
      var key = meta ? (meta.getAttribute('content') || '').trim() : '';
      return key || null;
    },

    /**
     * Returns the current PushSubscription if any, otherwise null.
     */
    getSubscription: function () {
      return navigator.serviceWorker.ready.then(function (reg) {
        return reg.pushManager.getSubscription();
      });
    },

    /**
     * Asks for permission (if needed) and subscribes the browser.
     * Returns the PushSubscription on success.
     */
    subscribe: function () {
      if (!api.isSupported()) {
        return Promise.reject(new Error('Push notifications are not supported in this browser.'));
      }

      var key = api.getVapidKey();
      if (!key) {
        return Promise.reject(new Error('VAPID public key missing — admin must run: php artisan webpush:vapid'));
      }

      return Notification.requestPermission().then(function (perm) {
        if (perm !== 'granted') {
          throw new Error('Notification permission ' + perm);
        }
        return navigator.serviceWorker.ready;
      }).then(function (reg) {
        return reg.pushManager.subscribe({
          userVisibleOnly: true,
          applicationServerKey: urlBase64ToUint8Array(key),
        });
      }).then(function (sub) {
        return postSubscription(sub).then(function () { return sub; });
      });
    },

    unsubscribe: function () {
      return api.getSubscription().then(function (sub) {
        if (!sub) return false;
        var endpoint = sub.endpoint;
        return sub.unsubscribe().then(function (ok) {
          if (!ok) return false;
          return deleteSubscription(endpoint).then(function () { return true; });
        });
      });
    },

    test: function () {
      return jQuery.ajax({
        url: TEST_URL,
        method: 'POST',
        dataType: 'json',
      });
    },
  };

  window.BizPOS.push = api;

  /* ── Toggle button wiring ── */
  jQuery(function ($) {
    var $toggle = $('#pushToggle');
    if (!$toggle.length) return;

    function render(state) {
      var label = $toggle.find('.bp-push-label');
      var icon  = $toggle.find('.bp-push-icon i');

      if (state === 'unsupported') {
        $toggle.attr('disabled', true).attr('title', 'This browser does not support push notifications');
        label.text('Not supported');
        icon.attr('class', 'fa-solid fa-bell-slash');
        return;
      }
      if (state === 'denied') {
        $toggle.attr('disabled', true).attr('title', 'Notifications blocked — enable in browser settings');
        label.text('Blocked in browser');
        icon.attr('class', 'fa-solid fa-bell-slash text-danger');
        return;
      }

      $toggle.removeAttr('disabled');
      if (state === 'enabled') {
        $toggle.attr('data-state', 'enabled').attr('title', 'Disable browser notifications');
        label.text('Notifications: On');
        icon.attr('class', 'fa-solid fa-bell text-success');
      } else {
        $toggle.attr('data-state', 'disabled').attr('title', 'Enable browser notifications');
        label.text('Enable notifications');
        icon.attr('class', 'fa-solid fa-bell-slash');
      }
    }

    function refresh() {
      if (!api.isSupported()) { render('unsupported'); return; }
      if (api.permission() === 'denied') { render('denied'); return; }
      api.getSubscription().then(function (sub) {
        render(sub ? 'enabled' : 'disabled');
      }).catch(function () { render('disabled'); });
    }

    $toggle.on('click', function (e) {
      e.preventDefault();
      var current = $toggle.attr('data-state');

      if (current === 'enabled') {
        api.unsubscribe().then(function () {
          render('disabled');
          if (window.bpToast) window.bpToast('Browser notifications disabled', 'info');
        });
      } else {
        api.subscribe().then(function () {
          render('enabled');
          if (window.bpToast) window.bpToast('Browser notifications enabled', 'success');
          return api.test();
        }).catch(function (err) {
          if (window.bpToast) window.bpToast(err.message || 'Could not enable notifications', 'danger');
          refresh();
        });
      }
    });

    refresh();
  });

  /* ── Helpers ── */

  function postSubscription(sub) {
    return jQuery.ajax({
      url: SUBSCRIBE_URL,
      method: 'POST',
      contentType: 'application/json',
      data: JSON.stringify(sub.toJSON()),
    });
  }

  function deleteSubscription(endpoint) {
    return jQuery.ajax({
      url: UNSUBSCRIBE_URL,
      method: 'DELETE',
      contentType: 'application/json',
      data: JSON.stringify({ endpoint: endpoint }),
    });
  }

  function urlBase64ToUint8Array(base64String) {
    var padding = '='.repeat((4 - base64String.length % 4) % 4);
    var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    var raw = window.atob(base64);
    var output = new Uint8Array(raw.length);
    for (var i = 0; i < raw.length; i++) {
      output[i] = raw.charCodeAt(i);
    }
    return output;
  }
})();
