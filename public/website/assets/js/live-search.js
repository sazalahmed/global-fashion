'use strict';

/*
 * Storefront live search (header + mobile search modal).
 * Fetches the active-product catalog once (AJAX, shared across every search
 * box), then uses Fuse.js for fuzzy, typo-tolerant matching on each keystroke.
 * Renders a dropdown of results (thumbnail, name with matched term bolded,
 * price, category, action icons). Falls back to the normal form submit when
 * Fuse isn't available.
 */
(function ($) {
    var $forms = $('.bp-livesearch-form');
    if (!$forms.length) return;

    // ── Shared Fuse index — fetched once, reused by every search box ──
    var fuse = null;
    var indexLoaded = false;
    var loading = false;
    var pending = [];

    function loadIndex(suggestUrl, cb) {
        if (indexLoaded) { if (cb) cb(); return; }
        if (cb) pending.push(cb);
        if (loading) return;
        loading = true;
        $.getJSON(suggestUrl)
            .done(function (data) {
                var products = (data && data.products) || [];
                if (typeof Fuse !== 'undefined') {
                    fuse = new Fuse(products, {
                        includeMatches: true,
                        threshold: 0.4,        // higher = more typo-tolerant
                        ignoreLocation: true,
                        minMatchCharLength: 1,
                        keys: [
                            { name: 'name', weight: 0.7 },
                            { name: 'sku', weight: 0.3 },
                            { name: 'category', weight: 0.2 }
                        ]
                    });
                }
                indexLoaded = true;
                loading = false;
                pending.splice(0).forEach(function (f) { f(); });
            })
            .fail(function () { loading = false; pending.length = 0; });
    }

    function esc(s) {
        return $('<div>').text(s == null ? '' : s).html();
    }

    // Bold the characters Fuse matched inside the product name.
    function highlightName(name, matches) {
        name = name || '';
        var nameMatch = (matches || []).filter(function (m) { return m.key === 'name'; })[0];
        if (!nameMatch || !nameMatch.indices || !nameMatch.indices.length) return esc(name);

        var ranges = nameMatch.indices.slice().sort(function (a, b) { return a[0] - b[0]; });
        var out = '', last = 0;
        ranges.forEach(function (r) {
            var s = r[0], e = r[1] + 1;
            if (s < last) return;
            out += esc(name.slice(last, s)) + '<b>' + esc(name.slice(s, e)) + '</b>';
            last = e;
        });
        out += esc(name.slice(last));
        return out;
    }

    // Shared data-* attributes the storefront's delegated handlers expect
    // (wishlist / compare / cart). Mirrors the product-card markup.
    function dataAttrs(p) {
        return 'data-product-id="' + esc(p.id) + '" data-id="' + esc(p.id) + '" ' +
            'data-sku="' + esc(p.sku || '') + '" data-name="' + esc(p.name || '') + '" ' +
            'data-price="' + esc(p.price) + '" data-category="' + esc(p.category || '') + '" ' +
            'data-brand="' + esc(p.brand || '') + '"';
    }

    // ── Wire each search box independently (header, mobile modal, …) ──
    $forms.each(function () {
        var $form = $(this);
        var $input = $form.find('.bp-livesearch-input');
        var $results = $form.find('.bp-livesearch-results');
        if (!$input.length || !$results.length) return;

        var suggestUrl = $form.data('suggest-url');
        var currency = ($form.data('currency') || '').toString();
        var placeholder = ($form.data('placeholder') || '').toString();
        var debounceTimer = null;
        var activeIdx = -1;

        function hide() {
            $results.prop('hidden', true).empty();
            activeIdx = -1;
        }

        function render(found, q) {
            activeIdx = -1;
            if (!found.length) {
                $results.html('<div class="bp-livesearch-empty">' +
                    'No products found for &ldquo;' + esc(q) + '&rdquo;</div>').prop('hidden', false);
                return;
            }

            var html = '<div class="bp-livesearch-head">Products search result</div>';
            found.forEach(function (r) {
                var p = r.item || r;
                var attrs = dataAttrs(p);

                html += '<div class="bp-livesearch-item">' +
                    '<a class="bp-livesearch-link" href="' + esc(p.url) + '">' +
                        '<img class="bp-livesearch-thumb" src="' + esc(p.image || placeholder) + '" alt="" ' +
                        'onerror="this.onerror=null;this.src=\'' + esc(placeholder) + '\'">' +
                        '<div class="bp-livesearch-info">' +
                            '<div class="bp-livesearch-name">' + highlightName(p.name, r.matches) + '</div>' +
                            '<div class="bp-livesearch-price">' + esc(currency) + ' ' +
                                esc(p.price_formatted != null ? p.price_formatted : p.price) + '</div>' +
                        '</div>' +
                    '</a>' +
                    '<div class="bp-livesearch-meta">' +
                        (p.category ? '<span class="bp-livesearch-cat">' + esc(p.category) + '</span>' : '<span></span>') +
                        '<div class="bp-livesearch-actions">' +
                            '<a href="' + esc(p.url) + '" class="bp-ls-act" title="View product"><i class="fas fa-eye"></i></a>' +
                            '<a href="#" class="bp-ls-act add-to-compare" ' + attrs + ' title="Compare"><i class="fas fa-code-compare"></i></a>' +
                            '<a href="#" class="bp-ls-act add-to-wishlist" ' + attrs + ' title="Wishlist"><i class="far fa-heart"></i></a>' +
                            '<a href="#" class="bp-ls-act buy-now-trigger" data-mode="cart" data-product-slug="' + esc(p.slug || '') + '" ' +
                                'data-has-variants="' + (p.has_variants ? '1' : '0') + '" ' + attrs + ' title="Add to cart"><i class="fas fa-cart-plus"></i></a>' +
                        '</div>' +
                    '</div>' +
                    '</div>';
            });
            $results.html(html).prop('hidden', false);
        }

        function search(q) {
            q = (q || '').trim();
            if (q.length < 1) { hide(); return; }
            loadIndex(suggestUrl, function () {
                if (!fuse) return;
                render(fuse.search(q, { limit: 12 }), q);
            });
        }

        $input.on('focus', function () {
            loadIndex(suggestUrl);
            var v = $input.val().trim();
            if (v) search(v);
        });

        $input.on('input', function () {
            var v = $input.val();
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function () { search(v); }, 180);
        });

        $input.on('keydown', function (e) {
            var $items = $results.find('.bp-livesearch-item');
            if (e.key === 'Escape') { hide(); return; }
            if (!$items.length) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                activeIdx = Math.min($items.length - 1, activeIdx + 1);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                activeIdx = Math.max(0, activeIdx - 1);
            } else if (e.key === 'Enter') {
                if (activeIdx >= 0) {
                    e.preventDefault();
                    var href = $items.eq(activeIdx).find('.bp-livesearch-link').attr('href');
                    if (href) window.location.href = href;
                }
                return; // otherwise let the form submit to the shop page
            } else {
                return;
            }
            $items.removeClass('is-active').eq(activeIdx).addClass('is-active');
        });

        // Close this box's results when clicking outside it.
        $(document).on('click', function (e) {
            if (!$(e.target).closest($form).length) hide();
        });
    });
})(jQuery);
