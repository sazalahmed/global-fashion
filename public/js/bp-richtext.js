'use strict';

/**
 * Centralized rich-text editor (TinyMCE) bootstrap.
 *
 * Any admin page that renders a <textarea class="bp-richtext"> gets a TinyMCE
 * editor automatically — no per-page init required. TinyMCE itself is loaded
 * lazily, only on pages that actually contain a .bp-richtext field, so it adds
 * no weight to the rest of the admin panel.
 *
 * The TinyMCE script URL is provided by the layout via window.BP_TINYMCE_SRC
 * (so {{ asset() }} resolves correctly regardless of deploy path).
 */
(function () {
    var fields = document.querySelectorAll('.bp-richtext');
    if (!fields.length || !window.BP_TINYMCE_SRC) {
        return;
    }

    var isDark = document.documentElement.getAttribute('data-theme') === 'dark';

    function initRichText() {
        if (typeof window.tinymce === 'undefined') {
            return;
        }
        window.tinymce.init({
            selector: '.bp-richtext',
            height: 400,
            plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
            toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
            branding: false,
            promotion: false,
            // Don't wrap every line in its own <p> block — pressing Enter
            // inserts a <br> line break instead, so the rendered content has
            // no extra paragraph tags / spacing per line.
            forced_root_block: '',
            skin: isDark ? 'oxide-dark' : 'oxide',
            content_css: isDark ? 'dark' : 'default',
            // Keep the underlying textarea in sync so the value posts on submit
            // (including AJAX submits that read the textarea value directly).
            setup: function (editor) {
                editor.on('change keyup', function () {
                    editor.save();
                });
            }
        });
    }

    // Lazy-load TinyMCE only when a rich-text field is present.
    if (typeof window.tinymce === 'undefined') {
        var script = document.createElement('script');
        script.src = window.BP_TINYMCE_SRC;
        script.onload = initRichText;
        document.head.appendChild(script);
    } else {
        initRichText();
    }
})();
