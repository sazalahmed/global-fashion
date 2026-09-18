/* ============================================================
   BizPOS Pro - Main Application JavaScript
   Dependencies: jQuery 3.7+, Bootstrap 5.3+, Chart.js 4+
   ============================================================ */

$(document).ready(function () {

  // ============================================================
  // SIDEBAR TOGGLE
  // ============================================================
  $('#sidebarToggle').on('click', function () {
    var $body = $('body');
    var $sidebar = $('.bp-sidebar');

    if ($(window).width() <= 992) {
      $sidebar.toggleClass('mobile-open');
      $('.bp-overlay').toggleClass('active');
    } else {
      $sidebar.toggleClass('collapsed');
      $body.toggleClass('sidebar-collapsed');
      localStorage.setItem('sidebarCollapsed', $sidebar.hasClass('collapsed'));
    }
  });

  // Close sidebar on overlay click (mobile)
  $('.bp-overlay').on('click', function () {
    $('.bp-sidebar').removeClass('mobile-open');
    $(this).removeClass('active');
  });

  // Restore sidebar state
  if (localStorage.getItem('sidebarCollapsed') === 'true' && $(window).width() > 992) {
    $('.bp-sidebar').addClass('collapsed');
    $('body').addClass('sidebar-collapsed');
  }

  // ============================================================
  // SIDEBAR SUBMENU TOGGLE
  // ============================================================
  $('.bp-sidebar-menu .menu-link[data-toggle="submenu"]').on('click', function (e) {
    e.preventDefault();
    var $item = $(this).closest('.menu-item');

    if ($('.bp-sidebar').hasClass('collapsed') && $(window).width() > 992) {
      return;
    }

    // Close other open submenus at same level
    $item.siblings('.menu-item.open').removeClass('open');
    $item.toggleClass('open');
  });

  // ============================================================
  // SIDEBAR COLLAPSED FLYOUT SUBMENU POSITIONING
  // ============================================================
  $('.bp-sidebar-menu .menu-item').on('mouseenter', function () {
    var $sidebar = $('.bp-sidebar');
    if (!$sidebar.hasClass('collapsed') || $(window).width() <= 992) {
      return;
    }

    var $submenu = $(this).children('.submenu');
    if (!$submenu.length) {
      return;
    }

    var itemTop = $(this).offset().top;
    var submenuHeight = $submenu.outerHeight();
    var windowHeight = $(window).height();

    // If submenu would overflow below viewport, align to bottom
    if (itemTop + submenuHeight > windowHeight) {
      $submenu.css('top', Math.max(0, windowHeight - submenuHeight) + 'px');
    } else {
      $submenu.css('top', itemTop + 'px');
    }
  });

  // ============================================================
  // DARK MODE TOGGLE
  // ============================================================
  $('#darkModeToggle').on('click', function () {
    var $html = $('html');
    var isDark = $html.attr('data-theme') === 'dark';

    if (isDark) {
      $html.removeAttr('data-theme');
      $(this).find('i').removeClass('fa-sun').addClass('fa-moon');
      localStorage.setItem('theme', 'light');
    } else {
      $html.attr('data-theme', 'dark');
      $(this).find('i').removeClass('fa-moon').addClass('fa-sun');
      localStorage.setItem('theme', 'dark');
    }
  });

  // Restore theme
  if (localStorage.getItem('theme') === 'dark') {
    $('html').attr('data-theme', 'dark');
    $('#darkModeToggle').find('i').removeClass('fa-moon').addClass('fa-sun');
  }

  // ============================================================
  // ACTIVE MENU HIGHLIGHT
  // ============================================================
  var currentPage = window.location.pathname.split('/').pop() || 'index.html';
  $('.bp-sidebar-menu .menu-link').each(function () {
    var href = $(this).attr('href');
    if (href === currentPage) {
      $(this).addClass('active');
      $(this).closest('.submenu').closest('.menu-item').addClass('open');
    }
  });

  // Global search is now handled by global-search.js

  // ============================================================
  // TABLE SELECT ALL CHECKBOX
  // ============================================================
  // The shared table header renders the select-all checkbox as `.bp-check-all`
  // (see components/table/header.blade.php), so bind to that class — the old
  // `.select-all-checkbox` selector matched nothing, leaving select-all dead on
  // every component-based list.
  $(document).on('change', '.bp-check-all', function () {
    var isChecked = $(this).prop('checked');
    $(this).closest('table').find('.row-checkbox').prop('checked', isChecked);
    updateBulkActions();
  });

  $(document).on('change', '.row-checkbox', function () {
    var $table = $(this).closest('table');
    var total = $table.find('.row-checkbox').length;
    var checked = $table.find('.row-checkbox:checked').length;
    $table.find('.bp-check-all').prop('checked', total === checked);
    updateBulkActions();
  });

  function updateBulkActions() {
    var count = $('.row-checkbox:checked').length;
    if (count > 0) {
      $('.bulk-action-bar').addClass('show');
      $('.bulk-count').text(count);
    } else {
      $('.bulk-action-bar').removeClass('show');
    }
  }

  // ============================================================
  // PHONE INPUT FORMATTING
  // ============================================================
  var phoneConfig = (window.BizPOS && window.BizPOS.phone) ? window.BizPOS.phone : null;

  if (phoneConfig) {
    // Set placeholder and maxlength on all phone inputs
    $('input[name="phone"], input[data-phone]').each(function () {
      var $input = $(this);
      if (!$input.attr('placeholder') || $input.attr('placeholder').indexOf('01') > -1 || $input.attr('placeholder').indexOf('XXX') > -1) {
        $input.attr('placeholder', 'e.g., ' + phoneConfig.example);
      }
      $input.attr('maxlength', phoneConfig.length + 5); // allow dashes/spaces
      $input.attr('data-phone', phoneConfig.code);
    });

    // Format on blur
    $(document).on('blur', 'input[data-phone]', function () {
      var val = $(this).val().replace(/\D/g, '');
      if (!val) return;
      $(this).val(formatPhone(val, phoneConfig));
    });
  }

  function formatPhone(digits, cfg) {
    if (!cfg || !digits) return digits;
    switch (cfg.code) {
      case 'BD':
        return digits.length === 11 ? digits.substr(0, 5) + '-' + digits.substr(5) : digits;
      case 'IN':
        return digits.length === 10 ? digits.substr(0, 5) + ' ' + digits.substr(5) : digits;
      case 'PK':
        return digits.length === 11 ? digits.substr(0, 4) + '-' + digits.substr(4) : digits;
      case 'US':
        return digits.length === 10 ? '(' + digits.substr(0, 3) + ') ' + digits.substr(3, 3) + '-' + digits.substr(6) : digits;
      case 'GB':
        return digits.length === 11 ? digits.substr(0, 5) + ' ' + digits.substr(5) : digits;
      case 'AE': case 'SA':
        return digits.length === 10 ? digits.substr(0, 3) + ' ' + digits.substr(3, 3) + ' ' + digits.substr(6) : digits;
      case 'SG': case 'QA': case 'KW': case 'OM': case 'BH':
        return digits.length === 8 ? digits.substr(0, 4) + ' ' + digits.substr(4) : digits;
      case 'MY':
        return digits.length >= 10 ? digits.substr(0, 3) + '-' + digits.substr(3, 3) + ' ' + digits.substr(6) : digits;
      default:
        return digits;
    }
  }

  // ============================================================
  // TABLE SORT
  // ============================================================
  $(document).on('click', '.bp-table thead th[data-sort]', function () {
    var $th = $(this);
    var $table = $th.closest('.bp-table');
    var colIndex = $th.index();
    var isAsc = $th.hasClass('sorted-asc');

    $table.find('thead th').removeClass('sorted sorted-asc sorted-desc');
    $th.addClass('sorted');

    if (isAsc) {
      $th.addClass('sorted-desc');
    } else {
      $th.addClass('sorted-asc');
    }

    var $rows = $table.find('tbody tr').get();
    $rows.sort(function (a, b) {
      var valA = $(a).children('td').eq(colIndex).text().trim();
      var valB = $(b).children('td').eq(colIndex).text().trim();

      // Try numeric sort
      var numA = parseFloat(valA.replace(/[^0-9.-]/g, ''));
      var numB = parseFloat(valB.replace(/[^0-9.-]/g, ''));

      if (!isNaN(numA) && !isNaN(numB)) {
        return isAsc ? numB - numA : numA - numB;
      }

      return isAsc ? valB.localeCompare(valA) : valA.localeCompare(valB);
    });

    $.each($rows, function (i, row) {
      $table.find('tbody').append(row);
    });
  });

  // ============================================================
  // FILTER BAR — server-side filters
  // ============================================================

  // Submit form when any filter select changes
  $(document).on('change', '.bp-filter-bar select', function () {
    $(this).closest('.bp-filter-bar').submit();
  });

  // Submit form on Enter key in search input
  $(document).on('keydown', '.bp-filter-bar .bp-table-search input', function (e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      $(this).closest('.bp-filter-bar').submit();
    }
  });

  // Reset all filters — navigate to the current path with NO query string.
  // Clearing inputs and re-submitting the form left date inputs, status tabs,
  // pagination and any hidden/preserved params in the URL; dropping the whole
  // query string clears every filter in one shot for every table.
  $(document).on('click', '.bp-filter-reset', function () {
    window.location.href = window.location.pathname;
  });

  // ============================================================
  // TABLE ACTION DROPDOWNS — fix overflow clipping
  // ============================================================
  document.querySelectorAll('.bp-table-wrapper [data-bs-toggle="dropdown"]').forEach(function (el) {
    new bootstrap.Dropdown(el, {
      popperConfig: function (defaultConfig) {
        defaultConfig.strategy = 'fixed';
        return defaultConfig;
      }
    });
  });

  // ============================================================
  // TOOLTIP INIT
  // ============================================================
  if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (el) {
      return new bootstrap.Tooltip(el);
    });
  }

  // ============================================================
  // NOTIFICATION DROPDOWN
  // ============================================================
  // Routes come from the layout (window.BizPOS.notifications) rather than
  // being hardcoded here: these paths were literal '/notifications' and
  // broke the moment the routes moved under /admin.
  function notifUrl(key, id) {
    var routes = (window.BizPOS && window.BizPOS.notifications) || {};
    var url = routes[key] || '/admin/notifications';
    return id ? url.replace('__ID__', encodeURIComponent(id)) : url;
  }

  function loadNotifications() {
    $.ajax({
      url: notifUrl('index'),
      type: 'GET',
      dataType: 'json',
      success: function (data) {
        if (!data || !data.notifications) return;

        var count = data.unread_count || 0;
        $('#notificationCount').text(count);
        count > 0 ? $('#notificationCount').show() : $('#notificationCount').hide();

        var $items = $('#notificationItems');
        $items.empty();

        if (data.notifications.length === 0) {
          $items.html(
            '<div class="bp-notif-empty">' +
            '  <i class="fa-solid fa-bell-slash"></i>' +
            '  <div class="fs-13">No notifications yet</div>' +
            '</div>'
          );
          return;
        }

        data.notifications.forEach(function (n) {
          var readClass = n.read ? '' : ' unread';
          var href = n.url || 'javascript:void(0)';
          var html =
            '<a href="' + href + '" class="bp-notification-item' + readClass + '" data-notification-id="' + n.id + '">' +
              '<div class="bp-notif-icon icon-' + (n.color || 'primary') + '">' +
                '<i class="fa-solid ' + (n.icon || 'fa-bell') + '"></i>' +
              '</div>' +
              '<div class="flex-grow-1 overflow-hidden">' +
                '<div class="fw-700 fs-13">' + $('<span>').text(n.title).html() + '</div>' +
                '<div class="fs-12 text-muted text-truncate">' + $('<span>').text(n.message).html() + '</div>' +
                '<div class="fs-11 text-muted mt-1"><i class="fa-solid fa-clock me-1"></i>' + $('<span>').text(n.created_at).html() + '</div>' +
              '</div>' +
            '</a>';
          $items.append(html);
        });
      },
      error: function () {
        // Silently ignore — user may not be authenticated
      }
    });
  }

  // Load on page load (delayed to avoid blocking)
  setTimeout(loadNotifications, 1000);
  // Refresh every 60 seconds
  setInterval(loadNotifications, 60000);

  // Reload notifications when Bootstrap dropdown opens
  $('#notificationBtn').on('shown.bs.dropdown', function () {
    loadNotifications();
  });

  // Mark all as read
  $('#markAllRead').on('click', function (e) {
    e.preventDefault();
    e.stopPropagation();
    $.post(notifUrl('markAllRead'), function () {
      loadNotifications();
      showToast('All notifications marked as read', 'success');
    });
  });

  // Mark individual notification as read on click
  $(document).on('click', '[data-notification-id]', function () {
    var id = $(this).data('notification-id');
    $.post(notifUrl('read', id));
  });

  // ============================================================
  // DASHBOARD CHARTS (initialized if on dashboard page)
  // ============================================================
  if ($('#salesChart').length && typeof Chart !== 'undefined') {
    initDashboardCharts();
  }

  function initDashboardCharts() {
    var ctxSales = document.getElementById('salesChart');
    if (ctxSales) {
      new Chart(ctxSales, {
        type: 'line',
        data: {
          labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
          datasets: [{
            label: 'Sales (BDT)',
            data: [285000, 312000, 298000, 345000, 378000, 356000, 412000, 389000, 425000, 445000, 398000, 478000],
            borderColor: '#1B4F72',
            backgroundColor: 'rgba(27,79,114,0.08)',
            fill: true,
            tension: 0.4,
            pointRadius: 4,
            pointBackgroundColor: '#1B4F72',
            borderWidth: 2
          }, {
            label: 'Purchases (BDT)',
            data: [195000, 218000, 205000, 234000, 256000, 241000, 279000, 263000, 287000, 301000, 268000, 323000],
            borderColor: '#E67E22',
            backgroundColor: 'rgba(230,126,34,0.08)',
            fill: true,
            tension: 0.4,
            pointRadius: 4,
            pointBackgroundColor: '#E67E22',
            borderWidth: 2
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'top',
              labels: { usePointStyle: true, padding: 20, font: { size: 12, family: "'Nunito Sans', sans-serif" } }
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                callback: function (val) {
                  return 'BDT ' + (val / 1000) + 'K';
                },
                font: { size: 11 }
              },
              grid: { color: 'rgba(0,0,0,0.04)' }
            },
            x: {
              grid: { display: false },
              ticks: { font: { size: 11 } }
            }
          }
        }
      });
    }

    var ctxPayment = document.getElementById('paymentChart');
    if (ctxPayment) {
      new Chart(ctxPayment, {
        type: 'doughnut',
        data: {
          labels: ['Cash', 'bKash', 'Nagad', 'Card', 'Bank', 'Rocket'],
          datasets: [{
            data: [42, 28, 14, 8, 5, 3],
            backgroundColor: ['#1B4F72', '#D4145A', '#F26522', '#2E86C1', '#117A65', '#8E44AD'],
            borderWidth: 0,
            hoverOffset: 6
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          cutout: '65%',
          plugins: {
            legend: {
              position: 'bottom',
              labels: { usePointStyle: true, padding: 12, font: { size: 11, family: "'Nunito Sans', sans-serif" } }
            }
          }
        }
      });
    }

    var ctxTopProducts = document.getElementById('topProductsChart');
    if (ctxTopProducts) {
      new Chart(ctxTopProducts, {
        type: 'bar',
        data: {
          labels: ['Samsung A55', 'iPhone 15', 'Xiaomi 14', 'Realme GT', 'Oppo Reno', 'Vivo V30', 'OnePlus 12'],
          datasets: [{
            label: 'Units Sold',
            data: [145, 98, 134, 87, 76, 92, 65],
            backgroundColor: '#1B4F72',
            borderRadius: 4,
            barThickness: 28
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          indexAxis: 'y',
          plugins: {
            legend: { display: false }
          },
          scales: {
            x: {
              grid: { color: 'rgba(0,0,0,0.04)' },
              ticks: { font: { size: 11 } }
            },
            y: {
              grid: { display: false },
              ticks: { font: { size: 11, family: "'Nunito Sans', sans-serif" } }
            }
          }
        }
      });
    }

    var ctxExpense = document.getElementById('expenseChart');
    if (ctxExpense) {
      new Chart(ctxExpense, {
        type: 'bar',
        data: {
          labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
          datasets: [{
            label: 'Income',
            data: [45000, 52000, 48000, 61000, 55000, 72000, 38000],
            backgroundColor: '#1E8449',
            borderRadius: 4,
            barThickness: 20
          }, {
            label: 'Expense',
            data: [32000, 28000, 35000, 41000, 38000, 45000, 22000],
            backgroundColor: '#C0392B',
            borderRadius: 4,
            barThickness: 20
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'top',
              labels: { usePointStyle: true, padding: 16, font: { size: 11 } }
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                callback: function (val) { return (val / 1000) + 'K'; },
                font: { size: 11 }
              },
              grid: { color: 'rgba(0,0,0,0.04)' }
            },
            x: {
              grid: { display: false },
              ticks: { font: { size: 11 } }
            }
          }
        }
      });
    }
  }

  // ============================================================
  // DELETE CONFIRM
  // ============================================================
  $(document).on('click', '.delete-confirm', function (e) {
    e.preventDefault();
    var $el = $(this);
    var name = $el.data('name') || 'this item';
    if (confirm('Are you sure you want to delete "' + name + '"? This cannot be undone.')) {
      $el.closest('form').submit();
    }
  });

  // ============================================================
  // TOAST NOTIFICATION
  // ============================================================
  window.showToast = function (message, type) {
    type = type || 'info';
    var icons = {
      success: 'fa-circle-check',
      danger: 'fa-circle-xmark',
      warning: 'fa-triangle-exclamation',
      info: 'fa-circle-info'
    };

    var $toast = $(
      '<div class="toast-notification toast-' + type + '">' +
      '  <i class="fa-solid ' + (icons[type] || icons.info) + '"></i>' +
      '  <span>' + message + '</span>' +
      '  <i class="fa-solid fa-xmark toast-close"></i>' +
      '</div>'
    );

    if (!$('.toast-container').length) {
      $('body').append('<div class="toast-container"></div>');
    }

    $('.toast-container').append($toast);
    setTimeout(function () { $toast.addClass('show'); }, 50);
    setTimeout(function () {
      $toast.removeClass('show');
      setTimeout(function () { $toast.remove(); }, 300);
    }, 4000);
  };

  $(document).on('click', '.toast-close', function () {
    $(this).closest('.toast-notification').removeClass('show');
  });

  // ============================================================
  // INLINE STATUS TOGGLE (active/inactive switch on list pages)
  // Shared handler for the <x-core::status-toggle> component. PATCHes the
  // input's data-url and expects JSON { is_active, message }. Reverts the
  // switch on failure. CSRF header comes from the layout's $.ajaxSetup.
  // ============================================================
  $(document).on('change', '.bp-status-toggle', function () {
    var $input = $(this);
    var $label = $input.closest('.bp-status-switch').find('.bp-status-switch-label');
    var activeText = $input.data('active-text') || 'Active';
    var inactiveText = $input.data('inactive-text') || 'Inactive';
    $input.prop('disabled', true);
    $.ajax({
      url: $input.data('url'),
      method: 'PATCH',
      success: function (res) {
        var isActive = !!res.is_active;
        $input.prop('checked', isActive);
        $label.text(isActive ? activeText : inactiveText);
        if (typeof showToast === 'function') {
          showToast(res.message || 'Status updated.', 'success');
        }
      },
      error: function () {
        // Revert the visual state on failure.
        $input.prop('checked', !$input.prop('checked'));
        if (typeof showToast === 'function') {
          showToast('Failed to update status. Please try again.', 'danger');
        }
      },
      complete: function () { $input.prop('disabled', false); }
    });
  });

  // ============================================================
  // FORMAT HELPERS
  // ============================================================
  window.formatNumber = function (num) {
    return parseFloat(num).toLocaleString('en-BD', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  };

  window.formatBDT = function (num) {
    return 'BDT ' + formatNumber(num);
  };

  // Trim trailing ".00": whole numbers render as integers, fractional values
  // keep two decimals — mirrors the PHP num()/money() helpers. Pass
  // withSymbol = true to prefix "BDT ". Use for DISPLAY only, never for values
  // fed back into further arithmetic.
  window.fmtAmount = function (value, withSymbol) {
    var n = parseFloat(value);
    if (!isFinite(n)) { n = 0; }
    // Round to 2dp first so floating-point residue isn't shown as ".00".
    n = Math.round(n * 100) / 100;
    var decimals = (n % 1 === 0) ? 0 : 2;
    var s = n.toLocaleString('en-BD', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
    return withSymbol ? 'BDT ' + s : s;
  };

  // Plain numeric string for <input> values and data-* attributes: trims a
  // trailing ".00" (whole numbers become integers) but keeps two decimals for
  // fractional values, and NEVER adds a thousands separator (which would break
  // numeric parsing). The JS mirror of the PHP num_input() helper.
  window.numInput = function (value) {
    var n = parseFloat(value);
    if (!isFinite(n)) { return ''; }
    n = Math.round(n * 100) / 100;
    return (n % 1 === 0) ? String(n) : n.toFixed(2);
  };

  // ============================================================
  // FULLSCREEN TOGGLE
  // ============================================================
  $('#fullscreenToggle').on('click', function () {
    if (!document.fullscreenElement) {
      document.documentElement.requestFullscreen();
      $(this).find('i').removeClass('fa-expand').addClass('fa-compress');
    } else {
      document.exitFullscreen();
      $(this).find('i').removeClass('fa-compress').addClass('fa-expand');
    }
  });

  // ============================================================
  // BULK ACTION HANDLERS
  // ============================================================
  $(document).on('click', '[data-bulk-action]', function (e) {
    e.preventDefault();
    var action = $(this).data('bulk-action');
    var ids = [];
    $('.row-checkbox:checked').each(function () {
      ids.push($(this).val());
    });

    if (ids.length === 0) {
      showToast('No items selected.', 'warning');
      return;
    }

    // Derive module from URL
    var pathParts = window.location.pathname.replace(/^\/+/, '').split('/');
    var module = pathParts[0] === 'admin' ? pathParts[1] : pathParts[0];

    if (action === 'delete') {
      if (!confirm('Are you sure you want to delete ' + ids.length + ' selected items? This cannot be undone.')) {
        return;
      }
      submitBulkAction('/bulk/' + module + '/delete', { ids: ids });
    } else if (action === 'status') {
      var status = $(this).data('bulk-status');
      submitBulkAction('/bulk/' + module + '/status', { ids: ids, status: status });
    }
  });

  function submitBulkAction(url, data) {
    var $form = $('<form method="POST" action="' + url + '"></form>');
    $form.append('<input type="hidden" name="_token" value="' + $('meta[name="csrf-token"]').attr('content') + '">');
    for (var key in data) {
      if (Array.isArray(data[key])) {
        data[key].forEach(function (val) {
          $form.append('<input type="hidden" name="' + key + '[]" value="' + val + '">');
        });
      } else {
        $form.append('<input type="hidden" name="' + key + '" value="' + data[key] + '">');
      }
    }
    $('body').append($form);
    $form.submit();
  }

  // Export is now handled via direct href links in <x-core::export-dropdown> component.
  // No JS handler needed — clicking the link navigates directly to the export URL.

  // ============================================================
  // FORM VALIDATION HELPER
  // ============================================================
  window.validateForm = function ($form) {
    var isValid = true;
    $form.find('[required]').each(function () {
      var $field = $(this);
      if (!$field.val() || $field.val().trim() === '') {
        $field.addClass('is-invalid');
        isValid = false;
      } else {
        $field.removeClass('is-invalid');
      }
    });
    return isValid;
  };

  $(document).on('input', '.is-invalid', function () {
    if ($(this).val().trim() !== '') {
      $(this).removeClass('is-invalid');
    }
  });

  // ============================================================
  // PRINT HANDLER
  // ============================================================
  $(document).on('click', '[data-print]', function () {
    window.print();
  });

  // ============================================================
  // AUTO-RESIZE TEXTAREA
  // ============================================================
  $(document).on('input', 'textarea.auto-resize', function () {
    this.style.height = 'auto';
    this.style.height = (this.scrollHeight) + 'px';
  });

  // ============================================================
  // AUTO-CLEAR DEFAULT ZERO ON FOCUS (project-wide)
  // ------------------------------------------------------------
  // Number fields that default to 0 (qty, price, discount, tax, …) are
  // annoying to edit — the "0" stays and you get values like "0444".
  // On focus, move a zero-like value into the placeholder and clear the
  // field so typing starts fresh; on blur, if untouched, restore the 0
  // (empty reads as 0 in every calc, so totals are unaffected).
  // Uses focusin/focusout (which bubble) so dynamically-added rows are
  // covered without re-binding. Opt out per-field with data-keep-zero.
  // ============================================================
  var ZERO_RE = /^0+(\.0+)?$/;   // "0", "00", "0.00", …

  $(document).on('focusin', 'input[type="number"]:not([data-keep-zero])', function () {
    var el = this;
    if (el.readOnly || el.disabled) return;
    if (ZERO_RE.test(el.value)) {
      // Stash the original placeholder once, then hint the 0 and clear.
      if (el.getAttribute('data-zc-ph') === null) {
        el.setAttribute('data-zc-ph', el.getAttribute('placeholder') || '');
      }
      el.setAttribute('data-zc', '1');
      el.placeholder = '0';
      el.value = '';
    }
  });

  $(document).on('focusout', 'input[type="number"]', function () {
    var el = this;
    if (el.getAttribute('data-zc') !== '1') return;
    el.removeAttribute('data-zc');
    // Left untouched → put the canonical 0 back so required/totals hold.
    if (el.value === '') el.value = '0';
    // Restore the field's original placeholder.
    var ph = el.getAttribute('data-zc-ph');
    if (ph !== null) { el.placeholder = ph; el.removeAttribute('data-zc-ph'); }
  });

}); // END document.ready


/* ============================================================
   TOAST CSS (injected via JS to keep external)
   ============================================================ */
(function () {
  var style = document.createElement('style');
  style.textContent = [
    '.toast-container{position:fixed;top:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:8px;}',
    '.toast-notification{display:flex;align-items:center;gap:10px;padding:12px 18px;border-radius:8px;font-size:13px;font-weight:600;color:#fff;transform:translateX(120%);transition:transform 0.3s ease;box-shadow:0 4px 16px rgba(0,0,0,0.15);min-width:280px;max-width:420px;}',
    '.toast-notification.show{transform:translateX(0);}',
    '.toast-success{background-color:#1E8449;}',
    '.toast-danger{background-color:#C0392B;}',
    '.toast-warning{background-color:#E67E22;}',
    '.toast-info{background-color:#2E86C1;}',
    '.toast-close{margin-left:auto;cursor:pointer;opacity:0.7;}',
    '.toast-close:hover{opacity:1;}'
  ].join('');
  document.head.appendChild(style);
})();

// ──────────────────────────────────────────────────────────────────
// Reorderable table rows (.bp-reorderable tbody with .bp-drag-handle cells).
// Native HTML5 drag-and-drop, no extra library. Persists the new order
// via POST { ordered_ids: [...] } to data-reorder-url on the tbody.
// ──────────────────────────────────────────────────────────────────
(function () {
  'use strict';

  function init(tbody) {
    if (tbody._bpReorderInit) return;
    tbody._bpReorderInit = true;
    if (tbody.dataset.reorderTree) {
      initTree(tbody);
    } else {
      initFlat(tbody);
    }
  }

  function initFlat(tbody) {
    var rows = tbody.querySelectorAll('tr[data-id]');
    rows.forEach(function (row) {
      row.setAttribute('draggable', 'true');

      row.addEventListener('dragstart', function (e) {
        // Only initiate from the drag handle cell.
        var handle = tbody.querySelector('.bp-drag-handle:hover');
        if (!handle || !row.contains(handle)) {
          // Allow drag started anywhere within the row when handle is hovered;
          // many browsers don't reliably report :hover here, so accept the drag.
        }
        row.classList.add('bp-dragging');
        e.dataTransfer.effectAllowed = 'move';
        try { e.dataTransfer.setData('text/plain', row.dataset.id); } catch (err) {}
      });

      row.addEventListener('dragend', function () {
        row.classList.remove('bp-dragging');
        tbody.querySelectorAll('.bp-drop-target').forEach(function (r) {
          r.classList.remove('bp-drop-target');
        });
        persist(tbody);
      });

      row.addEventListener('dragover', function (e) {
        e.preventDefault();
        var dragging = tbody.querySelector('.bp-dragging');
        if (!dragging || dragging === row) return;
        var rect = row.getBoundingClientRect();
        var midway = rect.top + rect.height / 2;
        if (e.clientY < midway) {
          tbody.insertBefore(dragging, row);
        } else {
          tbody.insertBefore(dragging, row.nextSibling);
        }
      });
    });
  }

  // Tree-aware reorder: each row carries data-parent (sibling group) and
  // data-depth. Dragging a row moves its whole subtree (descendants with a
  // greater depth that immediately follow it), and dropping is constrained to
  // rows sharing the same parent — so a child can never be moved out of, or
  // above, its parent. The DOM stays a valid depth-first tree, so the global
  // sequential persist still rebuilds the hierarchy correctly.
  function initTree(tbody) {
    var dragRow = null;
    var dragBlock = null;

    function allRows() {
      return Array.prototype.slice.call(tbody.querySelectorAll('tr[data-id]'));
    }
    function depthOf(row) { return parseInt(row.dataset.depth || '0', 10); }
    function parentOf(row) { return row.dataset.parent || '0'; }

    // A row plus the contiguous run of deeper rows that follow it.
    function subtreeOf(row) {
      var rows = allRows();
      var i = rows.indexOf(row);
      var block = [row];
      var base = depthOf(row);
      for (var j = i + 1; j < rows.length; j++) {
        if (depthOf(rows[j]) > base) block.push(rows[j]);
        else break;
      }
      return block;
    }

    function insertBlockBefore(block, ref) {
      block.forEach(function (r) { tbody.insertBefore(r, ref); });
    }

    allRows().forEach(function (row) {
      row.setAttribute('draggable', 'true');

      row.addEventListener('dragstart', function (e) {
        dragRow = row;
        dragBlock = subtreeOf(row);
        dragBlock.forEach(function (r) { r.classList.add('bp-dragging'); });
        e.dataTransfer.effectAllowed = 'move';
        try { e.dataTransfer.setData('text/plain', row.dataset.id); } catch (err) {}
      });

      row.addEventListener('dragend', function () {
        if (dragBlock) {
          dragBlock.forEach(function (r) { r.classList.remove('bp-dragging'); });
        }
        dragRow = null;
        dragBlock = null;
        persist(tbody);
      });

      row.addEventListener('dragover', function (e) {
        e.preventDefault();
        if (!dragRow || row === dragRow || !dragBlock) return;
        // Only reorder relative to a sibling that isn't part of the dragged subtree.
        if (parentOf(row) !== parentOf(dragRow)) return;
        if (dragBlock.indexOf(row) !== -1) return;

        var rect = row.getBoundingClientRect();
        var midway = rect.top + rect.height / 2;
        if (e.clientY < midway) {
          insertBlockBefore(dragBlock, row);
        } else {
          var targetBlock = subtreeOf(row);
          var lastOfTarget = targetBlock[targetBlock.length - 1];
          insertBlockBefore(dragBlock, lastOfTarget.nextSibling);
        }
      });
    });
  }

  function persist(tbody) {
    var url = tbody.dataset.reorderUrl;
    if (!url) return;
    var rows = tbody.querySelectorAll('tr[data-id]');
    var payload;
    if (tbody.dataset.reorderMode === 'catalog') {
      // Unified products + combos ordering: send typed items plus the active
      // category context (null = the global "All Categories" bucket).
      var items = Array.prototype.map.call(rows, function (r) {
        return { type: r.dataset.type || 'product', id: parseInt(r.dataset.id, 10) };
      });
      var sel = document.querySelector('[data-category-select]');
      var categoryId = sel && sel.value ? parseInt(sel.value, 10) : null;
      payload = { category_id: categoryId, items: items };
    } else {
      payload = {
        ordered_ids: Array.prototype.map.call(rows, function (r) {
          return parseInt(r.dataset.id, 10);
        })
      };
    }
    if (typeof $ !== 'undefined' && typeof $.ajax === 'function') {
      $.ajax({
        url: url,
        method: 'POST',
        data: payload,
        success: function () {
          if (typeof window.bpToast === 'function') {
            window.bpToast('Order updated', 'success');
          }
        },
        error: function () {
          if (typeof window.bpToast === 'function') {
            window.bpToast('Failed to save order', 'error');
          }
        }
      });
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('tbody.bp-reorderable').forEach(init);
  });
})();

/* ============================================================
   REUSABLE PRODUCT SEARCH (autocomplete)
   Shared "type product code or name and select" widget used on
   transaction forms (Purchase, Quotation, ...) so the look + behaviour
   match the Sale create page everywhere. Renders a flat "Name — SKU"
   list, exactly like the Sale create dropdown.

   Markup: @include('core::partials.product-search', ['id' => '...'])
   Usage:
     window.BpProductSearch.init({
       input: '#productSearch',                    // selector or jQuery element
       catalog: [{ id, name, model, sku, price, type, variants }],  // client-side source, OR
       ajaxUrl: '/admin/.../search-products',       // server-side source (GET ?q=term)
       queryParam: 'q',                             // optional, default 'q'
       showPrice: false,                            // show price on the right (default false)
       currencySymbol: 'BDT',                       // optional, default 'BDT'
       minChars: 0,                                 // default 0 (catalog) / 2 (ajax)
       onSelect: function (product) { ... }         // page decides what to do.
                                                    // Return true to keep the dropdown open
                                                    // (e.g. after calling api.showList for variants).
     });

   The returned handle exposes:
     focus()                       — focus the input
     setCatalog(arr)               — swap the client-side catalog
     showList(items, onPick)       — render a custom list (e.g. variant drill-down);
                                      items use the same {name, sku, type} shape.

   The page owns the line-item row markup + totals; this module owns only
   the search box, querying, dropdown rendering and selection.
   ============================================================ */
(function () {
  'use strict';

  function esc(str) {
    if (str === null || str === undefined) return '';
    var d = document.createElement('div');
    d.appendChild(document.createTextNode(String(str)));
    return d.innerHTML;
  }

  function money(num) {
    if (typeof window.fmtAmount === 'function') return window.fmtAmount(num);
    return (parseFloat(num) || 0).toFixed(2);
  }

  function nameOf(p) { return p.name || ''; }
  function skuOf(p)  { return p.sku || ''; }
  function modelOf(p)   { return p.model || ''; }
  function barcodeOf(p) { return p.barcode || ''; }
  function typeOf(p) { return p.type || p.product_type || 'simple'; }
  function priceOf(p) { return p.price != null ? p.price : (p.sell_price != null ? p.sell_price : 0); }

  function init(opts) {
    opts = opts || {};
    var $input = opts.input instanceof jQuery ? opts.input : $(opts.input);
    if (!$input.length) return null;

    var catalog = opts.catalog || null;
    var ajaxUrl = opts.ajaxUrl || null;
    var queryParam = opts.queryParam || 'q';
    var onSelect = typeof opts.onSelect === 'function' ? opts.onSelect : function () {};
    var limit = opts.limit || 15;
    var minChars = opts.minChars != null ? opts.minChars : (ajaxUrl ? 2 : 0);
    var debounceMs = opts.debounce != null ? opts.debounce : 250;
    var showPrice = !!opts.showPrice;
    var currency = opts.currencySymbol || 'BDT';
    var emptyText = opts.emptyText || 'No products found';

    var $wrapper = $input.closest('.bp-product-search-wrapper');
    var $results = $wrapper.find('.bp-product-search-results');
    var current = [];          // items currently rendered
    var activeIndex = -1;
    var mode = 'search';       // 'search' (catalog/ajax) | 'custom' (showList drill-down)
    var customPick = null;
    var timer = null;
    var reqSeq = 0;
    var api;

    function hide() { $results.hide().empty(); activeIndex = -1; }

    function paint(items) {
      current = items || [];
      activeIndex = -1;
      $results.empty();

      if (!current.length) {
        $results.append('<div class="bp-search-item text-muted fs-12">' + esc(emptyText) + '</div>');
        $results.show();
        return;
      }

      current.forEach(function (p, i) {
        var label = esc(nameOf(p));
        if (modelOf(p)) label += ' <span class="text-muted">(' + esc(modelOf(p)) + ')</span>';
        if (typeOf(p) === 'variable') label += ' <span class="bp-badge bp-badge-info bp-badge-xs ms-1">Variable</span>';
        // Combo entries are opt-in: only catalogs that mix products and combos
        // (e.g. homepage section curation) tag items with `kind`.
        if (p.kind === 'combo') label += ' <span class="bp-badge bp-badge-secondary bp-badge-xs ms-1">Combo</span>';
        var priceHtml = showPrice ? '<div class="fw-700 text-nowrap ms-2">' + currency + ' ' + money(priceOf(p)) + '</div>' : '';
        var $item = $('<div class="bp-search-item" data-idx="' + i + '"></div>').html(
          '<div class="d-flex justify-content-between align-items-center">' +
            '<div class="bp-search-item-label">' + label + '</div>' +
            priceHtml +
          '</div>'
        );
        $results.append($item);
      });
      $results.show();
    }

    function searchCatalog(term) {
      term = term.toLowerCase();
      return catalog.filter(function (p) {
        if (!term) return true;
        return (p.name || '').toLowerCase().indexOf(term) >= 0
            || (p.sku || '').toLowerCase().indexOf(term) >= 0
            || (p.model || '').toLowerCase().indexOf(term) >= 0
            || (p.barcode || '').toLowerCase().indexOf(term) >= 0;
      }).slice(0, limit);
    }

    function runSearch(term) {
      mode = 'search';
      customPick = null;
      term = (term || '').trim();
      if (term.length < minChars) { hide(); return; }

      if (ajaxUrl) {
        var seq = ++reqSeq;
        var data = {}; data[queryParam] = term;
        $.get(ajaxUrl, data, function (rows) {
          if (seq !== reqSeq) return;           // ignore stale responses
          paint((rows || []).slice(0, limit));
        });
      } else {
        paint(searchCatalog(term));
      }
    }

    function highlight() {
      $results.find('.bp-search-item').removeClass('is-active');
      if (activeIndex >= 0) {
        $results.find('.bp-search-item[data-idx="' + activeIndex + '"]').addClass('is-active');
      }
    }

    function reset() {
      mode = 'search';
      customPick = null;
      $input.val('');
      hide();
    }

    function choose(i) {
      var item = current[i];
      if (!item) return;
      if (mode === 'custom') {
        var cb = customPick;
        reset();
        if (cb) cb(item);
        return;
      }
      var handled = onSelect(item);
      if (handled !== true) { reset(); }
    }

    // Focusing the box always shows the list (browse), like the Sale create page.
    $input.on('focus', function () { mode = 'search'; customPick = null; runSearch($(this).val()); });
    // Re-open on an explicit click of an already-focused input: after a selection
    // the input keeps focus (mousedown is prevented), so clicking it again fires
    // no 'focus' event and the list would otherwise stay hidden. Guard on
    // visibility so picking an item (which doesn't click the input) won't reopen.
    $input.on('click', function () {
      if ($results.is(':visible')) return;
      mode = 'search'; customPick = null; runSearch($(this).val());
    });
    $input.on('input', function () {
      mode = 'search';
      var val = $(this).val();
      clearTimeout(timer);
      if (ajaxUrl) { timer = setTimeout(function () { runSearch(val); }, debounceMs); }
      else { runSearch(val); }
    });
    $input.on('keydown', function (e) {
      var max = current.length - 1;
      if (e.key === 'ArrowDown') { e.preventDefault(); activeIndex = Math.min(max, activeIndex + 1); highlight(); }
      else if (e.key === 'ArrowUp') { e.preventDefault(); activeIndex = Math.max(0, activeIndex - 1); highlight(); }
      else if (e.key === 'Enter') {
        if (activeIndex >= 0) { e.preventDefault(); choose(activeIndex); }
        else if (current.length === 1) { e.preventDefault(); choose(0); }
      } else if (e.key === 'Escape') { hide(); }
    });

    // mousedown (not click) so selection fires before the input blurs.
    $results.on('mousedown', '.bp-search-item[data-idx]', function (e) {
      e.preventDefault();
      choose(parseInt($(this).attr('data-idx'), 10));
    });

    $(document).on('click', function (e) {
      if (!$(e.target).closest($wrapper).length) hide();
    });

    api = {
      focus: function () { $input.trigger('focus'); },
      setCatalog: function (c) { catalog = c || []; },
      // Render a custom list (e.g. variants); onPick(item) fires on selection.
      showList: function (items, onPick) {
        mode = 'custom';
        customPick = typeof onPick === 'function' ? onPick : null;
        paint(items || []);
      }
    };
    return api;
  }

  // Barcode prefix button → focus its paired search input.
  $(document).on('click', '[data-bp-barcode-for]', function () {
    $('#' + $(this).attr('data-bp-barcode-for')).trigger('focus');
  });

  window.BpProductSearch = { init: init };
})();

/* ============================================================
   IMAGE UPLOAD DROPZONE  (x-core::image-upload component)
   Delegated preview + drag-and-drop for any styled single-image
   upload zone marked with [data-image-upload].
   ============================================================ */
(function () {
  'use strict';

  // Show the picked image, switch the zone into its "has-image" state, and
  // clear any pending removal flag (a new file overrides a prior delete).
  function showPreview(zone, file) {
    var preview = zone.querySelector('.js-thumb-preview');
    if (!file || !preview) { return; }
    if (file.type && file.type.indexOf('image/') !== 0) { return; }
    var reader = new FileReader();
    reader.onload = function (e) {
      preview.src = e.target.result;
      zone.classList.add('has-image');
      var flag = zone.querySelector('.js-thumb-remove-flag');
      if (flag) { flag.value = '0'; }
    };
    reader.readAsDataURL(file);
  }

  $(document).on('change', '[data-image-upload] input[type="file"]', function () {
    showPreview($(this).closest('[data-image-upload]')[0], this.files && this.files[0]);
  });

  // Click anywhere on the zone (image or empty box) opens the file picker —
  // except clicks on the remove (X) button.
  $(document).on('click', '[data-image-upload]', function (e) {
    if ($(e.target).closest('.js-thumb-remove').length) { return; }
    var input = this.querySelector('.js-thumb-input');
    if (!input || e.target === input) { return; }
    input.click();
  });

  // Remove (X): clear the selection, drop back to the empty state, and flag
  // an existing saved image for server-side deletion when a remove field exists.
  $(document).on('click', '[data-image-upload] .js-thumb-remove', function (e) {
    e.preventDefault();
    e.stopPropagation();
    var zone = $(this).closest('[data-image-upload]')[0];
    var input = zone.querySelector('.js-thumb-input');
    var preview = zone.querySelector('.js-thumb-preview');
    var flag = zone.querySelector('.js-thumb-remove-flag');
    if (input) { input.value = ''; }
    if (preview) { preview.removeAttribute('src'); }
    if (flag) { flag.value = '1'; }
    zone.classList.remove('has-image');
  });

  $(document).on('dragover', '[data-image-upload]', function (e) {
    e.preventDefault();
    this.classList.add('drag-over');
  });

  $(document).on('dragleave dragend drop', '[data-image-upload]', function () {
    this.classList.remove('drag-over');
  });

  $(document).on('drop', '[data-image-upload]', function (e) {
    var dt = e.originalEvent && e.originalEvent.dataTransfer;
    if (!dt || !dt.files || !dt.files.length) { return; }
    e.preventDefault();
    var input = this.querySelector('.js-thumb-input');
    if (!input) { return; }
    input.files = dt.files;
    $(input).trigger('change');
  });
})();

/**
 * Gallery image manager — used by product create/edit pages.
 *
 * Renders existing images and newly-picked files in ONE reorderable grid.
 * Drag order = the order images appear in the storefront gallery (the backend
 * sets the first image as primary/main). On form submit it rebuilds the file
 * input in grid order and emits hidden `image_order[]` (tokens "e<id>" for
 * existing, "n<k>" for the k-th new file) plus `remove_images[]` for any
 * existing image dragged out / removed.
 *
 * Requires SortableJS (loaded per-page after app.js).
 */
(function () {
  'use strict';

  window.initGalleryManager = function (gridId, inputId, dropzoneId, hiddenId, hintId, formId) {
    var grid = document.getElementById(gridId);
    var input = document.getElementById(inputId);
    if (!grid || !input) { return; }

    var dropzone = document.getElementById(dropzoneId);
    var hidden = document.getElementById(hiddenId);
    var hint = document.getElementById(hintId);
    var form = document.getElementById(formId);

    var uid = 0;
    var newFiles = {}; // uid -> File (only pending uploads)

    // Existing image ids present at load — used to detect removals on submit.
    var originalIds = Array.prototype.map.call(
      grid.querySelectorAll('[data-existing-id]'),
      function (el) { return el.getAttribute('data-existing-id'); }
    );

    function updateHint() {
      var has = grid.children.length > 0;
      if (hint) { hint.style.display = has ? '' : 'none'; }
      // Toggle compact prompt state when the grid (nested inside the dropzone)
      // holds images, mirroring the thumbnail field's has-image behaviour.
      if (dropzone) { dropzone.classList.toggle('has-images', has); }
    }
    updateHint();

    function addFiles(fileList) {
      for (var i = 0; i < fileList.length; i++) {
        (function (file) {
          var id = ++uid;
          newFiles[id] = file;
          var reader = new FileReader();
          reader.onload = function (e) {
            var item = document.createElement('div');
            item.className = 'bp-gallery-item';
            item.setAttribute('data-new-id', String(id));
            item.innerHTML =
              '<img src="' + e.target.result + '" alt="Preview">' +
              '<button type="button" class="bp-gallery-remove" title="Remove image"><i class="fa-solid fa-xmark"></i></button>';
            grid.appendChild(item);
            updateHint();
          };
          reader.readAsDataURL(file);
        })(fileList[i]);
      }
    }

    input.addEventListener('change', function () {
      if (this.files && this.files.length) {
        addFiles(this.files);
        this.value = ''; // allow re-picking the same file; pending files held in newFiles
      }
    });

    grid.addEventListener('click', function (e) {
      var btn = e.target.closest('.bp-gallery-remove');
      if (!btn) { return; }
      var item = btn.closest('.bp-gallery-item');
      var newId = item.getAttribute('data-new-id');
      if (newId) { delete newFiles[newId]; }
      item.remove();
      updateHint();
    });

    if (dropzone) {
      // Click anywhere in the box (except on a thumbnail, so drag/remove still
      // work) opens the file picker — no separate Browse button needed.
      dropzone.addEventListener('click', function (e) {
        if (e.target.closest('.bp-gallery-item')) { return; }
        input.click();
      });

      ['dragenter', 'dragover'].forEach(function (ev) {
        dropzone.addEventListener(ev, function (e) {
          e.preventDefault(); e.stopPropagation(); this.classList.add('drag-over');
        });
      });
      ['dragleave', 'drop'].forEach(function (ev) {
        dropzone.addEventListener(ev, function (e) {
          e.preventDefault(); e.stopPropagation(); this.classList.remove('drag-over');
        });
      });
      dropzone.addEventListener('drop', function (e) {
        var dt = e.dataTransfer;
        if (dt && dt.files && dt.files.length) { addFiles(dt.files); }
      });
    }

    if (window.Sortable) {
      window.Sortable.create(grid, { animation: 150, ghostClass: 'bp-gallery-ghost' });
    }

    if (form) {
      form.addEventListener('submit', function () {
        if (!hidden) { return; }
        hidden.innerHTML = '';
        var dt = new DataTransfer();
        var newCounter = 0;
        var present = {};

        Array.prototype.forEach.call(grid.children, function (item) {
          var existingId = item.getAttribute('data-existing-id');
          var newId = item.getAttribute('data-new-id');
          if (existingId) {
            present[existingId] = true;
            addHidden('image_order[]', 'e' + existingId);
          } else if (newId && newFiles[newId]) {
            dt.items.add(newFiles[newId]);
            addHidden('image_order[]', 'n' + newCounter);
            newCounter++;
          }
        });

        input.files = dt.files; // submit new files in grid order

        originalIds.forEach(function (id) {
          if (!present[id]) { addHidden('remove_images[]', id); }
        });
      });
    }

    function addHidden(name, value) {
      var el = document.createElement('input');
      el.type = 'hidden';
      el.name = name;
      el.value = value;
      hidden.appendChild(el);
    }
  };
})();

// ──────────────────────────────────────────────────────────────────
// Global double-submit guard.
// On any real (non-prevented) form submit, disable that form's submit
// buttons so a slow/duplicate click can't fire the request twice.
//
// Why setTimeout(0): disabling a submit button synchronously in the
// submit handler bars it from submission, dropping its name/value
// (e.g. a "Save as Draft" button's status=draft). Deferring by one tick
// lets the browser collect the form data (incl. the submitter) first,
// then disables — the navigation is already in flight, so the disabled
// state only blocks a second click.
//
// AJAX forms call preventDefault(); we detect that and skip them (they
// manage their own button state). Opt out per-form with data-no-disable.
// ──────────────────────────────────────────────────────────────────
(function () {
  'use strict';

  function submitControls(form) {
    var $btns = $(form).find('button[type="submit"], button:not([type]), input[type="submit"]');
    if (form.id) {
      // Buttons placed outside the form but bound to it via the form= attribute.
      $btns = $btns.add($('[form="' + form.id + '"]').filter('button:not([type="button"]), input[type="submit"]'));
    }
    return $btns;
  }

  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!form || form.tagName !== 'FORM') return;
    if (form.hasAttribute('data-no-disable')) return;
    // Another handler cancelled this submit (e.g. AJAX form) — leave buttons alone.
    if (e.defaultPrevented) return;

    setTimeout(function () {
      submitControls(form).each(function () {
        this.disabled = true;
        this.classList.add('bp-btn-submitting');
      });
    }, 0);
  }, false);
})();

/* ============================================================
   EMPTY TABLE LAYOUT
   When a list table has no data rows (its only body row is the shared
   <x-core::table.empty> cell), tag the table so CSS can switch it to a
   fixed layout — the header then spans the full width instead of the
   columns clustering on the left. Class-based (added here) rather than the
   CSS :has() selector, so it works in every browser.
   ============================================================ */
(function () {
  'use strict';

  function tagEmptyTables() {
    document.querySelectorAll('.bp-table').forEach(function (table) {
      var isEmpty = !!table.querySelector('td.bp-table-empty');
      table.classList.toggle('bp-table-is-empty', isEmpty);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', tagEmptyTables);
  } else {
    tagEmptyTables();
  }
})();
