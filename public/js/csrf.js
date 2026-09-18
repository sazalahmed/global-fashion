'use strict';

/* ============================================================
   BizPOS Pro - CSRF token management (shared by all layouts)

   Keeps every AJAX request authenticated against the LIVE session,
   even when the page HTML (and its embedded <meta csrf-token>) is stale
   from the browser back/forward cache or the service-worker cache.

   Two layers:
   1. PROACTIVE - send the token from the XSRF-TOKEN cookie, which Laravel
      refreshes on every response, so a stale page still posts a current,
      valid token. Laravel decrypts the X-XSRF-TOKEN header automatically.
   2. REACTIVE  - if a request still returns 419 (expired token), fetch a
      fresh token from the csrf-refresh endpoint and retry the request once,
      so the user never sees the "session expired" message.
   ============================================================ */
(function () {
    if (typeof window.jQuery === 'undefined') { return; }
    var $ = window.jQuery;

    function readCookie(name) {
        var match = document.cookie.match('(^|;)\\s*' + name + '\\s*=\\s*([^;]+)');
        return match ? decodeURIComponent(match.pop()) : null;
    }

    function metaToken() {
        return $('meta[name="csrf-token"]').attr('content') || '';
    }

    function refreshUrl() {
        return $('meta[name="csrf-refresh-url"]').attr('content') || '';
    }

    // Attach a live token to every same-origin request at send time. The
    // XSRF-TOKEN cookie is always current (re-set on each response), so this
    // survives stale page HTML. Fall back to the embedded meta token only if
    // the cookie is unavailable. NOTE: we intentionally do NOT set a global
    // X-CSRF-TOKEN header from the meta tag, because that header takes
    // precedence over X-XSRF-TOKEN in Laravel and a stale meta would defeat
    // the purpose.
    $(document).ajaxSend(function (event, jqXHR, settings) {
        if (settings.crossDomain) { return; }
        var xsrf = readCookie('XSRF-TOKEN');
        if (xsrf) {
            jqXHR.setRequestHeader('X-XSRF-TOKEN', xsrf);
        } else {
            jqXHR.setRequestHeader('X-CSRF-TOKEN', metaToken());
        }
    });

    // Safety net: a 419 means the token was rejected anyway (e.g. the session
    // was garbage-collected and the cookie itself was stale). Pull a fresh
    // token - the GET also re-issues a fresh XSRF-TOKEN cookie - then replay
    // the original request once. The per-settings guard prevents a retry loop.
    $(document).ajaxError(function (event, jqXHR, settings) {
        if (jqXHR.status !== 419 || settings._bpCsrfRetried) { return; }
        var url = refreshUrl();
        if (!url) { return; }
        settings._bpCsrfRetried = true;
        $.get(url).done(function (res) {
            if (!res || !res.token) { return; }
            $('meta[name="csrf-token"]').attr('content', res.token);
            $.ajax(settings); // replay with the refreshed XSRF-TOKEN cookie
        });
    });
})();
