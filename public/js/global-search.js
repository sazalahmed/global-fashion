'use strict';

(function ($) {

    // ============================================================
    // CONFIGURATION
    // ============================================================
    var CONFIG = {
        minLength: 2,
        debounceMs: 300,
        maxRecentItems: 10,
        recentStorageKey: 'bp_recent_searches',
        searchUrl: '',
        fullSearchUrl: '',
        resultsUrl: ''
    };

    // ============================================================
    // STATE
    // ============================================================
    var state = {
        query: '',
        flatItems: [],
        activeIndex: -1,
        isOpen: false,
        isLoading: false,
        debounceTimer: null,
        currentXhr: null
    };

    // ============================================================
    // SELECTORS
    // ============================================================
    var $container, $input, $dropdown, $clearBtn, $shortcut;
    var $overlay, $overlayContainer, $overlayInput, $overlayDropdown;
    var $mobileBtn;

    // ============================================================
    // INITIALIZATION
    // ============================================================
    function init() {
        $container = $('#globalSearchContainer');
        $input = $('#globalSearchInput');
        $dropdown = $('#globalSearchDropdown');
        $clearBtn = $('#globalSearchClear');
        $shortcut = $container.find('.bp-global-search-shortcut');
        $mobileBtn = $('#mobileSearchBtn');

        if (!$container.length) { return; }

        CONFIG.searchUrl = $container.data('search-url') || '/search';
        CONFIG.fullSearchUrl = $container.data('full-search-url') || '/search/full';
        CONFIG.resultsUrl = $container.data('results-url') || '/search/results';

        // Build mobile overlay
        buildMobileOverlay();

        // Bind events
        bindEvents();
    }

    function buildMobileOverlay() {
        $overlay = $('<div class="bp-global-search-overlay" id="globalSearchOverlay"></div>');
        var $clone = $container.clone();
        $clone.attr('id', 'globalSearchOverlayContainer');
        $clone.find('#globalSearchInput').attr('id', 'globalSearchOverlayInput');
        $clone.find('#globalSearchDropdown').attr('id', 'globalSearchOverlayDropdown');
        $clone.find('#globalSearchClear').attr('id', 'globalSearchOverlayClear');
        $overlay.append($clone);
        $('body').append($overlay);

        $overlayContainer = $clone;
        $overlayInput = $('#globalSearchOverlayInput');
        $overlayDropdown = $('#globalSearchOverlayDropdown');
    }

    // ============================================================
    // EVENT BINDINGS
    // ============================================================
    function bindEvents() {
        // Ctrl+K / Cmd+K to focus
        $(document).on('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                if (window.innerWidth < 992) {
                    openMobileSearch();
                } else {
                    $input.focus().select();
                }
            }
        });

        // Input events for desktop
        $input.on('input', function () { handleInput($(this), $dropdown, $container); });
        $input.on('focus', function () { handleFocus($dropdown, $container); });
        $input.on('keydown', function (e) { handleKeyDown(e, $dropdown); });

        // Input events for mobile overlay
        $(document).on('input', '#globalSearchOverlayInput', function () {
            handleInput($(this), $overlayDropdown, $overlayContainer);
        });
        $(document).on('focus', '#globalSearchOverlayInput', function () {
            handleFocus($overlayDropdown, $overlayContainer);
        });
        $(document).on('keydown', '#globalSearchOverlayInput', function (e) {
            handleKeyDown(e, $overlayDropdown);
        });

        // Clear button
        $clearBtn.on('click', function () { clearSearch($input, $dropdown, $container); });
        $(document).on('click', '#globalSearchOverlayClear', function () {
            clearSearch($overlayInput, $overlayDropdown, $overlayContainer);
        });

        // Click outside to close
        $(document).on('click', function (e) {
            if (!$(e.target).closest('.bp-global-search, .bp-global-search-overlay').length) {
                closeDropdown($dropdown);
                closeMobileSearch();
            }
        });

        // Escape to close
        $(document).on('keydown', function (e) {
            if (e.key === 'Escape') {
                closeDropdown($dropdown);
                closeDropdown($overlayDropdown);
                closeMobileSearch();
                $input.blur();
            }
        });

        // Click on result item
        $(document).on('click', '.bp-global-search-item', function (e) {
            var url = $(this).data('url');
            if (url) {
                addRecentItem({
                    title: $(this).find('.bp-global-search-item-title').text(),
                    subtitle: $(this).find('.bp-global-search-item-subtitle').text(),
                    url: url,
                    icon: $(this).data('icon') || 'fa-magnifying-glass',
                    type: $(this).data('type') || ''
                });
                window.location.href = url;
            }
        });

        // Clear recent items
        $(document).on('click', '.bp-global-search-recent-clear', function (e) {
            e.stopPropagation();
            clearRecentItems();
            closeDropdown($dropdown);
            closeDropdown($overlayDropdown);
        });

        // Mobile search button
        $mobileBtn.on('click', function () { openMobileSearch(); });
    }

    // ============================================================
    // INPUT HANDLING
    // ============================================================
    function handleInput($inp, $dd, $cont) {
        var query = $inp.val().trim();
        state.query = query;

        // Toggle has-value class
        if (query.length > 0) {
            $cont.addClass('has-value');
        } else {
            $cont.removeClass('has-value');
        }

        // Cancel pending
        clearTimeout(state.debounceTimer);
        abortPending();

        if (query.length < CONFIG.minLength) {
            if (query.length === 0) {
                renderRecent($dd);
                openDropdown($dd);
            } else {
                closeDropdown($dd);
            }
            return;
        }

        // Show loading
        renderLoading($dd);
        openDropdown($dd);

        // Debounced search
        state.debounceTimer = setTimeout(function () {
            executeSearch(query, $dd);
        }, CONFIG.debounceMs);
    }

    function handleFocus($dd, $cont) {
        var query = ($cont.find('input').val() || '').trim();
        if (query.length < CONFIG.minLength) {
            renderRecent($dd);
            openDropdown($dd);
        } else if (state.flatItems.length > 0) {
            openDropdown($dd);
        }
    }

    function clearSearch($inp, $dd, $cont) {
        $inp.val('').focus();
        $cont.removeClass('has-value');
        state.query = '';
        state.flatItems = [];
        state.activeIndex = -1;
        abortPending();
        renderRecent($dd);
        openDropdown($dd);
    }

    // ============================================================
    // SEARCH EXECUTION
    // ============================================================
    function executeSearch(query, $dd) {
        abortPending();
        state.isLoading = true;

        state.currentXhr = $.ajax({
            url: CONFIG.searchUrl,
            type: 'GET',
            data: { q: query },
            dataType: 'json',
            success: function (data) {
                state.isLoading = false;
                renderResults(data, $dd);
            },
            error: function (xhr) {
                state.isLoading = false;
                if (xhr.statusText !== 'abort') {
                    renderEmpty($dd);
                }
            }
        });
    }

    function abortPending() {
        if (state.currentXhr && state.currentXhr.readyState !== 4) {
            state.currentXhr.abort();
        }
    }

    // ============================================================
    // RENDERING
    // ============================================================
    function renderResults(data, $dd) {
        var results = data.results || [];
        var navigation = data.navigation || [];
        state.flatItems = [];
        state.activeIndex = -1;

        if (results.length === 0 && navigation.length === 0) {
            renderEmpty($dd);
            return;
        }

        var html = '';

        // Navigation results first
        if (navigation.length > 0) {
            html += '<div class="bp-global-search-group">';
            html += '<div class="bp-global-search-group-header">';
            html += '<i class="fa-solid fa-compass"></i> Pages';
            html += '<span class="bp-global-search-group-count">' + navigation.length + '</span>';
            html += '</div>';

            for (var n = 0; n < navigation.length; n++) {
                var nav = navigation[n];
                state.flatItems.push(nav);
                html += buildNavItemHtml(nav, state.flatItems.length - 1);
            }
            html += '</div>';
        }

        // Entity groups
        for (var g = 0; g < results.length; g++) {
            var group = results[g];
            html += '<div class="bp-global-search-group">';
            html += '<div class="bp-global-search-group-header">';
            html += '<i class="fa-solid ' + escapeAttr(group.icon) + '"></i> ' + escapeHtml(group.type);
            html += '<span class="bp-global-search-group-count">' + group.count + '</span>';
            html += '</div>';

            for (var i = 0; i < group.items.length; i++) {
                var item = group.items[i];
                state.flatItems.push(item);
                html += buildItemHtml(item, state.flatItems.length - 1);
            }
            html += '</div>';
        }

        // "View all results" link → full results page
        html += '<a class="bp-global-search-view-all" href="' + escapeAttr(CONFIG.resultsUrl + '?q=' + encodeURIComponent(state.query)) + '">' +
            '<i class="fa-solid fa-list"></i> View all results for "' + escapeHtml(state.query) + '"' +
        '</a>';

        // Footer hint
        html += '<div class="bp-global-search-footer">';
        html += '<kbd>&uarr;</kbd> <kbd>&darr;</kbd> navigate &nbsp; <kbd>Enter</kbd> open &nbsp; <kbd>Esc</kbd> close';
        html += '</div>';

        $dd.html(html);
        openDropdown($dd);
    }

    function buildItemHtml(item, index) {
        return '<div class="bp-global-search-item" data-index="' + index + '" data-url="' + escapeAttr(item.url) + '" data-icon="' + escapeAttr(item.icon) + '" data-type="' + escapeAttr(item.type) + '">' +
            '<div class="bp-global-search-item-icon"><i class="fa-solid ' + escapeAttr(item.icon) + '"></i></div>' +
            '<div class="bp-global-search-item-content">' +
                '<div class="bp-global-search-item-title">' + item.title + '</div>' +
                '<div class="bp-global-search-item-subtitle">' + item.subtitle + '</div>' +
            '</div>' +
            '<span class="bp-global-search-item-badge">' + escapeHtml(item.type) + '</span>' +
        '</div>';
    }

    function buildNavItemHtml(item, index) {
        return '<div class="bp-global-search-item bp-global-search-nav-item" data-index="' + index + '" data-url="' + escapeAttr(item.url) + '" data-icon="' + escapeAttr(item.icon) + '" data-type="Page">' +
            '<div class="bp-global-search-item-icon"><i class="fa-solid ' + escapeAttr(item.icon) + '"></i></div>' +
            '<div class="bp-global-search-item-content">' +
                '<div class="bp-global-search-item-title">' + item.title + '</div>' +
                '<div class="bp-global-search-item-subtitle">' + escapeHtml(item.subtitle) + '</div>' +
            '</div>' +
            '<span class="bp-global-search-item-badge">Page</span>' +
        '</div>';
    }

    function renderLoading($dd) {
        $dd.html(
            '<div class="bp-global-search-loading">' +
                '<i class="fa-solid fa-spinner"></i>' +
                'Searching...' +
            '</div>'
        );
        openDropdown($dd);
    }

    function renderEmpty($dd) {
        $dd.html(
            '<div class="bp-global-search-empty">' +
                '<i class="fa-solid fa-magnifying-glass"></i>' +
                'No results found' +
            '</div>'
        );
        openDropdown($dd);
    }

    function renderRecent($dd) {
        var items = getRecentItems();
        state.flatItems = [];
        state.activeIndex = -1;

        if (items.length === 0) {
            $dd.html(
                '<div class="bp-global-search-empty">' +
                    '<i class="fa-solid fa-clock-rotate-left"></i>' +
                    'Start typing to search...' +
                '</div>'
            );
            return;
        }

        var html = '<div class="bp-global-search-recent-header">' +
            '<span>Recent</span>' +
            '<span class="bp-global-search-recent-clear">Clear</span>' +
        '</div>';

        for (var i = 0; i < items.length; i++) {
            var item = items[i];
            state.flatItems.push(item);
            html += '<div class="bp-global-search-item" data-index="' + i + '" data-url="' + escapeAttr(item.url) + '" data-icon="' + escapeAttr(item.icon) + '" data-type="' + escapeAttr(item.type) + '">' +
                '<div class="bp-global-search-item-icon"><i class="fa-solid ' + escapeAttr(item.icon) + '"></i></div>' +
                '<div class="bp-global-search-item-content">' +
                    '<div class="bp-global-search-item-title">' + escapeHtml(item.title) + '</div>' +
                    '<div class="bp-global-search-item-subtitle">' + escapeHtml(item.subtitle) + '</div>' +
                '</div>' +
                '<span class="bp-global-search-item-badge">' + escapeHtml(item.type) + '</span>' +
            '</div>';
        }

        $dd.html(html);
    }

    // ============================================================
    // KEYBOARD NAVIGATION
    // ============================================================
    function handleKeyDown(e, $dd) {
        if (!state.isOpen || state.flatItems.length === 0) {
            return;
        }

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            setActiveItem(state.activeIndex + 1, $dd);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            setActiveItem(state.activeIndex - 1, $dd);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (state.activeIndex >= 0 && state.activeIndex < state.flatItems.length) {
                var activeItem = state.flatItems[state.activeIndex];
                addRecentItem({
                    title: $('<span>').html(activeItem.title).text(),
                    subtitle: $('<span>').html(activeItem.subtitle).text(),
                    url: activeItem.url,
                    icon: activeItem.icon,
                    type: activeItem.type
                });
                window.location.href = activeItem.url;
            } else if (state.query.length >= CONFIG.minLength) {
                // No item highlighted — open the full results page.
                window.location.href = CONFIG.resultsUrl + '?q=' + encodeURIComponent(state.query);
            }
        }
    }

    function setActiveItem(index, $dd) {
        if (state.flatItems.length === 0) { return; }

        // Wrap around
        if (index < 0) { index = state.flatItems.length - 1; }
        if (index >= state.flatItems.length) { index = 0; }

        state.activeIndex = index;

        // Update visual
        $dd.find('.bp-global-search-item').removeClass('active');
        var $active = $dd.find('.bp-global-search-item[data-index="' + index + '"]');
        $active.addClass('active');

        // Scroll into view
        if ($active.length) {
            var container = $dd[0];
            var item = $active[0];
            if (item.offsetTop < container.scrollTop) {
                container.scrollTop = item.offsetTop;
            } else if (item.offsetTop + item.offsetHeight > container.scrollTop + container.clientHeight) {
                container.scrollTop = item.offsetTop + item.offsetHeight - container.clientHeight;
            }
        }
    }

    // ============================================================
    // DROPDOWN VISIBILITY
    // ============================================================
    function openDropdown($dd) {
        $dd.addClass('open');
        state.isOpen = true;
    }

    function closeDropdown($dd) {
        if ($dd && $dd.length) {
            $dd.removeClass('open');
        }
        state.isOpen = false;
        state.activeIndex = -1;
    }

    // ============================================================
    // MOBILE OVERLAY
    // ============================================================
    function openMobileSearch() {
        $overlay.addClass('open');
        setTimeout(function () {
            $overlayInput.focus();
        }, 100);
    }

    function closeMobileSearch() {
        if ($overlay) {
            $overlay.removeClass('open');
        }
    }

    // ============================================================
    // RECENT ITEMS (localStorage)
    // ============================================================
    function getRecentItems() {
        try {
            var data = localStorage.getItem(CONFIG.recentStorageKey);
            return data ? JSON.parse(data) : [];
        } catch (e) {
            return [];
        }
    }

    function addRecentItem(item) {
        if (!item || !item.url) { return; }

        var items = getRecentItems();

        // Remove duplicate
        items = items.filter(function (r) { return r.url !== item.url; });

        // Add to front
        items.unshift({
            title: item.title || '',
            subtitle: item.subtitle || '',
            url: item.url,
            icon: item.icon || 'fa-magnifying-glass',
            type: item.type || '',
            timestamp: Date.now()
        });

        // Trim to max
        if (items.length > CONFIG.maxRecentItems) {
            items = items.slice(0, CONFIG.maxRecentItems);
        }

        try {
            localStorage.setItem(CONFIG.recentStorageKey, JSON.stringify(items));
        } catch (e) {
            // localStorage full or unavailable
        }
    }

    function clearRecentItems() {
        try {
            localStorage.removeItem(CONFIG.recentStorageKey);
        } catch (e) {
            // ignore
        }
    }

    // ============================================================
    // HELPERS
    // ============================================================
    function escapeHtml(str) {
        if (!str) { return ''; }
        return $('<span>').text(str).html();
    }

    function escapeAttr(str) {
        if (!str) { return ''; }
        return String(str).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    // ============================================================
    // BOOT
    // ============================================================
    $(document).ready(init);

})(jQuery);
