// admin-notifications.js
// Powers the notification bell in the topbar:
//   1. Loads existing notifications from the DB on every page load (always).
//   2. Subscribes to the public 'admin.notifications' Pusher channel for live
//      events (only if Pusher is configured) and prepends them without a refresh.
//   3. Handles mark-as-read (per item) and mark-all-read.
'use strict';

(function () {
  var $wrap = jQuery('#notificationWrap');
  if (!$wrap.length) return;

  var listUrl    = $wrap.data('list-url');
  var markAllUrl = $wrap.data('mark-all-url');
  var markReadTpl = $wrap.data('mark-read-url'); // contains __ID__ placeholder

  var $items = jQuery('#notificationItems');
  var $count = jQuery('#notificationCount');

  // ── 1. Initial DB load ──
  loadFromDb();

  // ── 2. Pusher live subscription (optional) ──
  if (window.BizPOS && window.BizPOS.pusher && window.BizPOS.pusher.enabled && typeof Pusher !== 'undefined') {
    initPusher(window.BizPOS.pusher);
  }

  // ── 3. UI handlers ──
  jQuery(document).on('click', '#markAllRead', function (e) {
    e.preventDefault();
    if (!markAllUrl) return;
    jQuery.post(markAllUrl).done(function () {
      $items.find('.bp-notif-item').removeClass('is-unread');
      setBadge(0);
    });
  });

  jQuery(document).on('click', '.bp-notif-item', function () {
    var $row = jQuery(this);
    var id = $row.data('id');
    if (!id || !$row.hasClass('is-unread')) return;
    var url = (markReadTpl || '').replace('__ID__', id);
    if (!url) return;
    jQuery.post(url).done(function () {
      $row.removeClass('is-unread');
      setBadge(Math.max(0, (parseInt($count.text(), 10) || 0) - 1));
    });
    // navigation happens via the anchor href naturally
  });

  // ── Helpers ──

  function loadFromDb() {
    if (!listUrl) return;
    jQuery.ajax({
      url: listUrl,
      method: 'GET',
      dataType: 'json',
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
    }).done(function (res) {
      renderList(res.notifications || []);
      setBadge(parseInt(res.unread_count, 10) || 0);
    });
  }

  function renderList(list) {
    if (!$items.length) return;
    if (!list.length) return; // keep the existing empty-state placeholder

    $items.find('.bp-notif-empty').remove();
    $items.empty();

    list.forEach(function (n) {
      $items.append(buildRow({
        id: n.id,
        icon: n.icon || 'fa-bell',
        title: n.title || '',
        body: n.message || '',
        time: n.created_at || '',
        url: n.url || '#',
        unread: !n.read,
      }));
    });
  }

  function buildRow(n) {
    var $row = jQuery(
      '<a class="bp-notif-item" href="' + (n.url || '#') + '">' +
        '<div class="bp-notif-icon"><i class="fa-solid ' + (n.icon || 'fa-bell') + '"></i></div>' +
        '<div class="bp-notif-content">' +
          '<div class="bp-notif-title fw-700 fs-13"></div>' +
          '<div class="bp-notif-body-text fs-12 text-muted"></div>' +
          '<div class="bp-notif-time fs-11 text-muted"></div>' +
        '</div>' +
      '</a>'
    );
    $row.find('.bp-notif-title').text(n.title || '');
    $row.find('.bp-notif-body-text').text(n.body || '');
    $row.find('.bp-notif-time').text(n.time || '');
    if (n.id) $row.attr('data-id', n.id);
    if (n.unread) $row.addClass('is-unread');
    return $row;
  }

  function setBadge(n) {
    if (n > 0) {
      $count.text(n).show();
    } else {
      $count.text('0').hide();
    }
  }

  function initPusher(cfg) {
    var pusher;
    try {
      pusher = new Pusher(cfg.key, { cluster: cfg.cluster, forceTLS: true });
    } catch (err) {
      console.warn('Pusher init failed:', err);
      return;
    }

    var channel = pusher.subscribe('admin.notifications');

    channel.bind('order.placed', function (payload) {
      $items.find('.bp-notif-empty').remove();
      var $row = buildRow({
        icon: 'fa-cart-shopping',
        title: 'New online order',
        body: (payload.customer_name || 'Customer') +
              ' — BDT ' + Number(payload.grand_total || 0).toLocaleString('en-IN') +
              ' (' + (payload.item_count || 0) + ' items)',
        time: 'just now',
        url: payload.url || '#',
        unread: true,
      });
      $items.prepend($row);
      trim();

      if (typeof window.bpToast === 'function') {
        window.bpToast('New order ' + payload.order_number, 'success');
      }

      setBadge((parseInt($count.text(), 10) || 0) + 1);
      playChime();
    });
  }

  function trim() {
    var $all = $items.find('.bp-notif-item');
    if ($all.length > 25) $all.slice(25).remove();
  }

  function playChime() {
    try {
      var audioCtx = window.bpAudioCtx || (window.bpAudioCtx = new (window.AudioContext || window.webkitAudioContext)());
      var o = audioCtx.createOscillator();
      var g = audioCtx.createGain();
      o.type = 'sine';
      o.frequency.value = 880;
      g.gain.value = 0.05;
      o.connect(g).connect(audioCtx.destination);
      o.start();
      o.frequency.exponentialRampToValueAtTime(1320, audioCtx.currentTime + 0.15);
      g.gain.exponentialRampToValueAtTime(0.0001, audioCtx.currentTime + 0.4);
      o.stop(audioCtx.currentTime + 0.4);
    } catch (e) { /* audio not allowed yet */ }
  }
})();
