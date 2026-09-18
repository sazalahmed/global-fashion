@push('scripts')
<script>
'use strict';

$(function () {
    // Auto-generate coupon code
    $('#btnGenerateCode').on('click', function () {
        var chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        var code = '';
        for (var i = 0; i < 8; i++) {
            code += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        $('#couponCode').val(code);
        updatePreview();
    });

    // Toggle discount value suffix based on type
    $('#discountType').on('change', function () {
        var type = $(this).val();
        if (type === 'percentage') {
            $('#discountSuffix').text('%');
            $('#discountValue').attr('max', 100);
        } else {
            $('#discountSuffix').text('{{ currency_symbol() }}');
            $('#discountValue').removeAttr('max');
        }
        updatePreview();
    });

    // Toggle applicable categories/products pane
    function applyAppliesTo() {
        var val = $('#appliesTo').val();
        $('#categoriesGroup').toggle(val === 'categories');
        $('#productsGroup').toggle(val === 'products');
    }
    $('#appliesTo').on('change', applyAppliesTo);
    applyAppliesTo();

    // ── Generic table-picker wiring (same shape as campaigns form) ──
    function wireTablePicker(opts) {
        function refresh() {
            var n = $(opts.cbSelector + ':checked').length;
            $(opts.countSelector).text(n + ' selected');
        }
        refresh();

        $(opts.searchSelector).on('input', function () {
            var q = $(this).val().toLowerCase().trim();
            $(opts.rowSelector).each(function () {
                var match = q === '' || $(this).data('search').toString().indexOf(q) !== -1;
                $(this).toggle(match);
            });
            $(opts.selectAllSelector).prop('checked', false);
        });

        $(opts.selectAllSelector).on('change', function () {
            var on = $(this).prop('checked');
            $(opts.rowSelector + ':visible ' + opts.cbSelector).prop('checked', on);
            refresh();
        });

        $(opts.clearSelector).on('click', function () {
            $(opts.cbSelector).prop('checked', false);
            $(opts.selectAllSelector).prop('checked', false);
            refresh();
        });

        $(document).on('change', opts.cbSelector, refresh);
    }

    wireTablePicker({
        cbSelector:        '.coupon-category-cb',
        rowSelector:       '.coupon-category-row',
        countSelector:     '#couponCategoryCount',
        searchSelector:    '#couponCategorySearch',
        selectAllSelector: '#couponCategorySelectAll',
        clearSelector:     '#couponCategoryClear',
    });

    wireTablePicker({
        cbSelector:        '.coupon-product-cb',
        rowSelector:       '.coupon-product-row',
        countSelector:     '#couponProductCount',
        searchSelector:    '#couponProductSearch',
        selectAllSelector: '#couponProductSelectAll',
        clearSelector:     '#couponProductClear',
    });

    // Update preview on input change
    $('#couponCode, input[name="name"], #discountType, #discountValue, input[name="start_date"], input[name="end_date"]').on('input change', function () {
        updatePreview();
    });

    function updatePreview() {
        var code = $('#couponCode').val() || 'XXXXXXXX';
        var description = $('input[name="name"]').val() || 'Coupon description';
        var type = $('#discountType').val();
        var value = $('#discountValue').val();
        var validFrom = $('input[name="start_date"]').val();
        var validTo = $('input[name="end_date"]').val();

        $('#previewCode').text(code.toUpperCase());
        $('#previewDescription').text(description);

        if (type === 'percentage' && value) {
            $('#previewDiscount').text(value + '% OFF');
        } else if (type === 'fixed' && value) {
            $('#previewDiscount').text('{{ currency_symbol() }} ' + value + ' OFF');
        } else {
            $('#previewDiscount').text('--');
        }

        if (validFrom && validTo) {
            $('#previewValidity').text('Valid: ' + formatDate(validFrom) + ' to ' + formatDate(validTo));
        }
    }

    function formatDate(dateStr) {
        var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        var d = new Date(dateStr);
        return ('0' + d.getDate()).slice(-2) + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
    }

    updatePreview();
});
</script>
@endpush
