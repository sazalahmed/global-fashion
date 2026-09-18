{{-- Shared JS for combo create/edit forms. Included inside a <script> block that
     already declares 'use strict' and the vars: catalog, oldItems, oldGallery,
     placeholder, currency. Exposes bootstrapComboForm(...). --}}

function bootstrapComboForm(catalog, initialItems, checkedGallery, placeholder, currency) {

    // Index catalog by product id for fast lookup.
    var catalogById = {};
    catalog.forEach(function (p) { catalogById[p.id] = p; });

    var rowIndex = 0;
    var checkedSet = {};
    (checkedGallery || []).forEach(function (path) { if (path) checkedSet[path] = true; });

    function esc(s) { return $('<span>').text(s == null ? '' : String(s)).html(); }

    function money(n) {
        return currency + ' ' + Number(n || 0).toLocaleString(undefined, {
            minimumFractionDigits: 2, maximumFractionDigits: 2
        });
    }

    // Build the <option> list for a product select, marking the chosen one.
    function productOptions(selectedId) {
        var html = '<option value="">Select product</option>';
        catalog.forEach(function (p) {
            var sel = (String(p.id) === String(selectedId)) ? ' selected' : '';
            // Show just the product name (incl. its colour/variant suffix) — no SKU.
            html += '<option value="' + p.id + '"' + sel + '>' + esc(p.name) + '</option>';
        });
        return html;
    }

    function addRow(item) {
        item = item || {};
        var i = rowIndex++;
        var pid = item.product_id || '';
        var qty = item.quantity || 1;

        var row = '' +
            '<tr class="combo-row" data-index="' + i + '">' +
              '<td>' +
                '<select class="bp-form-select select2-search w-100 combo-product" name="items[' + i + '][product_id]" required>' +
                  productOptions(pid) +
                '</select>' +
              '</td>' +
              '<td>' +
                '<input type="number" class="bp-form-control combo-qty" name="items[' + i + '][quantity]" ' +
                  'value="' + esc(qty) + '" min="1" step="1" required>' +
              '</td>' +
              '<td>' +
                '<button type="button" class="bp-btn bp-btn-sm bp-btn-icon bp-btn-danger remove-row">' +
                  '<i class="fa-solid fa-times"></i></button>' +
              '</td>' +
            '</tr>';

        var $row = $(row);
        $('#componentsBody').append($row);
        // These rows are added after page load, so init select2 (searchable
        // product dropdown) on the new row explicitly.
        if (window.bpInitSelect2) window.bpInitSelect2($row);
    }

    // ── Auto-managed categories driven by the selected products ──
    // The combo's categories track the products: selecting a product ticks its
    // category (and parent chain); changing/removing that product unticks the
    // categories it brought in, as long as no other selected product still
    // needs them. Categories the admin ticked by hand are NOT touched — only
    // product-derived ones (tracked in autoCats) are added/removed.
    var autoCats = {};

    // Ancestor checkbox values for a category, walking the flat pre-order list
    // (a node's parent is the nearest preceding row at depth-1). Mirrors the
    // categories-card ancestor logic.
    function ancestorCatValues(catVal) {
        var out = [];
        var $item = $('#comboCategoryList .cat-checkbox[value="' + catVal + '"]').closest('.bp-cat-item');
        if (!$item.length) { return out; }
        var depth = parseInt($item.attr('data-depth'), 10) || 0;
        var prev = $item[0].previousElementSibling;
        while (prev && depth > 0) {
            if (prev.classList.contains('bp-cat-item')) {
                var prevDepth = parseInt(prev.getAttribute('data-depth'), 10) || 0;
                if (prevDepth === depth - 1) {
                    var cb = prev.querySelector('.cat-checkbox');
                    if (cb) { out.push(cb.value); }
                    depth = prevDepth;
                }
            }
            prev = prev.previousElementSibling;
        }
        return out;
    }

    function syncCategoriesFromProducts() {
        // Categories (+ ancestors) implied by the CURRENT set of products.
        var desired = {};
        $('.combo-product').each(function () {
            var p = catalogById[$(this).val()];
            if (!p || !p.category_id) { return; }
            var val = String(p.category_id);
            if (!$('#comboCategoryList .cat-checkbox[value="' + val + '"]').length) { return; }
            desired[val] = true;
            ancestorCatValues(val).forEach(function (a) { desired[a] = true; });
        });

        // Untick product-derived categories that no longer apply.
        Object.keys(autoCats).forEach(function (val) {
            if (!desired[val]) {
                $('#comboCategoryList .cat-checkbox[value="' + val + '"]').prop('checked', false);
            }
        });
        // Tick everything the current products imply.
        Object.keys(desired).forEach(function (val) {
            $('#comboCategoryList .cat-checkbox[value="' + val + '"]').prop('checked', true);
        });

        autoCats = desired;
    }

    // Refresh the gallery + price preview when a product changes.
    $(document).on('change', '.combo-product', function () {
        // Tick all of the newly chosen product's gallery images by default
        // (the admin can untick any they don't want).
        var p = catalogById[$(this).val()];
        if (p && p.images) {
            p.images.forEach(function (img) { if (img.path) { checkedSet[img.path] = true; } });
        }
        syncCategoriesFromProducts();
        renderGallery();
        recalc();
    });

    $(document).on('change', '.combo-qty', recalc);

    // ── Add / remove rows ──
    $('#addComponent').on('click', function () {
        addRow();
    });

    $(document).on('click', '.remove-row', function () {
        $(this).closest('tr').remove();
        syncCategoriesFromProducts();
        renderGallery();
        recalc();
    });

    // ── Gallery: images of currently-selected component products ──
    function selectedProductIds() {
        var ids = [];
        $('.combo-product').each(function () {
            var v = $(this).val();
            if (v && ids.indexOf(v) === -1) ids.push(v);
        });
        return ids;
    }

    function renderGallery() {
        var ids = selectedProductIds();
        var html = '';
        var seen = {};
        ids.forEach(function (pid) {
            var p = catalogById[pid];
            if (!p || !p.images) return;
            p.images.forEach(function (img) {
                if (!img.path || seen[img.path]) return;
                seen[img.path] = true;
                var checked = checkedSet[img.path] ? ' checked' : '';
                html += '' +
                  '<label class="bp-combo-gallery-item">' +
                    '<input type="checkbox" name="gallery[]" value="' + esc(img.path) + '"' + checked + '>' +
                    '<img src="' + esc(img.url) + '" alt="' + esc(img.alt) + '" ' +
                      'onerror="this.onerror=null;this.src=\'' + placeholder + '\'">' +
                  '</label>';
            });
        });
        if (html) {
            $('#galleryGrid').html(html);
        } else {
            $('#galleryGrid').html('<div class="text-center text-muted py-3" id="noGalleryMsg">Select components to choose gallery images.</div>');
        }
    }

    // Keep the checked-state map in sync as the user toggles boxes (so re-render preserves it).
    $(document).on('change', '#galleryGrid input[type="checkbox"]', function () {
        if (this.checked) { checkedSet[this.value] = true; }
        else { delete checkedSet[this.value]; }
    });

    // ── Live price preview ──
    function recalc() {
        var summed = 0;
        $('.combo-row').each(function () {
            var $row = $(this);
            var pid = $row.find('.combo-product').val();
            var qty = parseFloat($row.find('.combo-qty').val()) || 0;
            var p = catalogById[pid];
            if (!p) return;
            summed += p.price * qty;
        });

        var comboPrice = parseFloat($('#comboPrice').val()) || 0;
        var effective = Math.max(0, Math.round(comboPrice * 100) / 100);

        var save = Math.max(0, Math.round((summed - effective) * 100) / 100);
        var savePct = summed > 0 ? Math.round((save / summed) * 100) : 0;

        $('#prevSummed').text(money(summed));
        $('#prevEffective').text(money(effective));
        $('#prevSave').text(money(save) + ' (' + savePct + '%)');
    }

    $('#comboPrice').on('input change', recalc);

    // Thumbnail preview/upload is handled by the shared image-upload component
    // (see public/js/app.js [data-image-upload] handlers).

    // ── Initial render ──
    if (initialItems && initialItems.length) {
        initialItems.forEach(function (it) { addRow(it); });
    } else {
        addRow();
    }
    renderGallery();
    recalc();
}
