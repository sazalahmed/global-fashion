'use strict';
/* SEO snippet preview + char counters.
   Wire: data-seo-title on the title input, data-seo-desc on the description,
   [data-seo-title-count]/[data-seo-desc-count] counter spans, and a
   [data-seo-preview] block containing .seo-pv-title/.seo-pv-url/.seo-pv-desc. */
(function () {
    function cap(el, max, counter) {
        function update() {
            var len = (el.value || '').length;
            if (counter) { counter.textContent = len + '/' + max; counter.style.color = len > max ? '#C0392B' : ''; }
        }
        el.addEventListener('input', update);
        update();
    }
    document.addEventListener('DOMContentLoaded', function () {
        var t = document.querySelector('[data-seo-title]');
        var d = document.querySelector('[data-seo-desc]');
        var pv = document.querySelector('[data-seo-preview]');
        if (t) { cap(t, 60, document.querySelector('[data-seo-title-count]')); }
        if (d) { cap(d, 160, document.querySelector('[data-seo-desc-count]')); }
        if (pv && (t || d)) {
            var pt = pv.querySelector('.seo-pv-title');
            var pd = pv.querySelector('.seo-pv-desc');
            function sync() {
                if (pt && t) { pt.textContent = t.value || t.getAttribute('placeholder') || ''; }
                if (pd && d) { pd.textContent = d.value || d.getAttribute('placeholder') || ''; }
            }
            if (t) { t.addEventListener('input', sync); }
            if (d) { d.addEventListener('input', sync); }
            sync();
        }
    });
})();
