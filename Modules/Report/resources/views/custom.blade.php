@extends('core::layouts.master')

@section('title', __('Custom Report Builder'))
@section('page-title', __('Custom Report Builder'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="#">Reports</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Custom Report Builder</span>
@endsection

@section('page-actions')
    @bpCan('reports.export')
        <x-core::export-dropdown module="report-custom" class="d-none" id="exportDropdown" />
    @endbpCan
    <button class="bp-btn bp-btn-primary d-none" id="btnPrintReport">
        <i class="fa-solid fa-print"></i> Print
    </button>
@endsection

@section('content')

    <!-- Report Builder Form -->
    <div class="row g-4">
        <div class="col-lg-4">
            <!-- Report Configuration -->
            <div class="bp-card mb-4">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-gears me-2"></i>Report Configuration</h5>
                </div>
                <div class="bp-card-body">
                    <form id="customReportForm">
                        <!-- Report Type -->
                        <div class="mb-3">
                            <label class="bp-form-label">Select Report Type *</label>
                            <select class="bp-form-select w-100" name="report_type" id="reportType" required>
                                <option value="">Choose Report Type</option>
                                <option value="sales">Sales Report</option>
                                <option value="purchase">Purchase Report</option>
                                <option value="inventory">Inventory Report</option>
                                <option value="financial">Financial Report</option>
                            </select>
                        </div>

                        <!-- Date Range -->
                        <div class="row g-3 mb-3" id="dateRangeSection">
                            <div class="col-6">
                                <label class="bp-form-label">From Date</label>
                                <input type="date" class="bp-form-control" name="date_from"
                                    value="{{ now()->startOfMonth()->format('Y-m-d') }}">
                            </div>
                            <div class="col-6">
                                <label class="bp-form-label">To Date</label>
                                <input type="date" class="bp-form-control" name="date_to"
                                    value="{{ now()->format('Y-m-d') }}">
                            </div>
                        </div>

                        <!-- Group By -->
                        <div class="mb-3">
                            <label class="bp-form-label">Group By</label>
                            <select class="bp-form-select w-100" name="group_by" id="groupBy">
                                <option value="">No Grouping</option>
                                <option value="date">Date</option>
                                <option value="week">Week</option>
                                <option value="month">Month</option>
                                <option value="customer">Customer</option>
                                <option value="product">Product</option>
                                <option value="category">Category</option>
                            </select>
                        </div>

                        <!-- Dynamic Filters -->
                        <div id="dynamicFilters">
                            <!-- Populated by JS based on report type -->
                        </div>
                    </form>
                </div>
            </div>

            <!-- Select Columns -->
            <div class="bp-card">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-table-columns me-2"></i>Select Columns</h5>
                    <div>
                        <button type="button" class="bp-btn bp-btn-sm bp-btn-outline" id="selectAllCols">All</button>
                        <button type="button" class="bp-btn bp-btn-sm bp-btn-outline" id="deselectAllCols">None</button>
                    </div>
                </div>
                <div class="bp-card-body" id="columnSelection">
                    <!-- Empty state -->
                    <div id="columnEmptyState" class="text-center text-muted py-3">
                        <i class="fa-solid fa-table-columns fa-2x mb-2 d-block"></i>
                        <span class="fs-13">Select a report type first to see available columns</span>
                    </div>
                    <!-- Column checkboxes populated by JS -->
                    <div id="columnCheckboxes" class="d-none">
                    </div>
                </div>
                <div class="bp-card-footer text-end">
                    <button type="button" class="bp-btn bp-btn-outline me-2" id="btnResetBuilder">
                        <i class="fa-solid fa-rotate"></i> Reset
                    </button>
                    <button type="button" class="bp-btn bp-btn-primary" id="btnGenerateReport">
                        <i class="fa-solid fa-play"></i> Generate Report
                    </button>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <!-- Empty State / Preview Area -->
            <div id="reportEmptyState" class="bp-card">
                <div class="bp-card-body text-center py-5">
                    <div class="mb-4">
                        <i class="fa-solid fa-chart-column fa-4x bp-custom-report-empty-icon"></i>
                    </div>
                    <h4 class="fw-700 mb-2">Build Your Custom Report</h4>
                    <p class="text-muted mb-4 fs-13">
                        Create a tailored report by selecting a report type, choosing the columns you need,
                        and applying filters. Follow the steps below to get started.
                    </p>
                    <div class="row g-3 text-start mx-auto bp-custom-report-steps">
                        <div class="col-12">
                            <div class="d-flex align-items-start gap-3">
                                <span class="bp-badge bp-badge-primary fw-700 bp-step-badge">1</span>
                                <div>
                                    <div class="fw-700 fs-13">Select Report Type</div>
                                    <div class="fs-12 text-muted">Choose from Sales, Purchase, Inventory, or Financial
                                        reports</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex align-items-start gap-3">
                                <span class="bp-badge bp-badge-primary fw-700 bp-step-badge">2</span>
                                <div>
                                    <div class="fw-700 fs-13">Choose Columns</div>
                                    <div class="fs-12 text-muted">Select the data fields you want to include in your report
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex align-items-start gap-3">
                                <span class="bp-badge bp-badge-primary fw-700 bp-step-badge">3</span>
                                <div>
                                    <div class="fw-700 fs-13">Apply Filters & Group By</div>
                                    <div class="fs-12 text-muted">Narrow down results with date range, branch, and grouping
                                        options</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex align-items-start gap-3">
                                <span class="bp-badge bp-badge-primary fw-700 bp-step-badge">4</span>
                                <div>
                                    <div class="fw-700 fs-13">Generate & Export</div>
                                    <div class="fs-12 text-muted">Preview the report and export as PDF, Excel, or CSV</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Report Preview (hidden by default) -->
            <div id="reportPreview" class="d-none">
                <!-- Report Summary -->
                <div class="bp-card mb-4">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-file-lines me-2"></i><span
                                id="previewTitle">Report Preview</span></h5>
                        <span class="bp-badge bp-badge-primary" id="previewMeta"></span>
                    </div>
                    <div class="bp-card-body">
                        <div class="row g-3" id="previewStats">
                            <!-- Dynamically populated stats -->
                        </div>
                    </div>
                </div>

                <!-- Report Chart -->
                <div class="bp-card mb-4" id="previewChartCard">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-chart-bar me-2"></i>Visual Summary</h5>
                    </div>
                    <div class="bp-card-body">
                        <canvas id="previewChart" height="80"></canvas>
                    </div>
                </div>

                <!-- Report Table -->
                <div class="bp-card">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-table me-2"></i>Report Data</h5>
                        <span class="fs-12 text-muted" id="previewRowCount"></span>
                    </div>
                    <div class="bp-card-body p-0">
                        <div class="bp-table-wrapper">
                            <table class="bp-table" id="previewTable">
                                <thead id="previewTableHead">
                                </thead>
                                <tbody id="previewTableBody">
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="bp-card-footer">
                        <div class="bp-pagination">
                            <span class="page-info" id="previewPaginationInfo">Showing all results</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="{{ asset('vendor/chartjs/chart.min.js') }}"></script>
    <script>
        'use strict';

        $(function() {
            var previewChart = null;

            // Column definitions by report type
            var reportColumns = {
                sales: [{
                        key: 'date',
                        label: 'Date',
                        checked: true
                    },
                    {
                        key: 'invoice',
                        label: 'Invoice #',
                        checked: true
                    },
                    {
                        key: 'customer',
                        label: 'Customer',
                        checked: true
                    },
                    {
                        key: 'items',
                        label: 'Items Count',
                        checked: true
                    },
                    {
                        key: 'subtotal',
                        label: 'Subtotal',
                        checked: true,
                        isCurrency: true
                    },
                    {
                        key: 'discount',
                        label: 'Discount',
                        checked: false,
                        isCurrency: true
                    },
                    {
                        key: 'tax',
                        label: 'Tax (15%)',
                        checked: true,
                        isCurrency: true
                    },
                    {
                        key: 'total',
                        label: 'Total',
                        checked: true,
                        isCurrency: true
                    },
                    {
                        key: 'payment_method',
                        label: 'Payment Method',
                        checked: false
                    },
                    {
                        key: 'payment_status',
                        label: 'Payment Status',
                        checked: true
                    },
                    {
                        key: 'branch',
                        label: 'Branch',
                        checked: false
                    },
                    {
                        key: 'salesperson',
                        label: 'Salesperson',
                        checked: false
                    }
                ],
                purchase: [{
                        key: 'date',
                        label: 'Date',
                        checked: true
                    },
                    {
                        key: 'po_number',
                        label: 'PO #',
                        checked: true
                    },
                    {
                        key: 'supplier',
                        label: 'Supplier',
                        checked: true
                    },
                    {
                        key: 'items',
                        label: 'Items Count',
                        checked: true
                    },
                    {
                        key: 'total',
                        label: 'Total',
                        checked: true,
                        isCurrency: true
                    },
                    {
                        key: 'paid',
                        label: 'Paid',
                        checked: true,
                        isCurrency: true
                    },
                    {
                        key: 'due',
                        label: 'Due',
                        checked: true,
                        isCurrency: true
                    },
                    {
                        key: 'status',
                        label: 'Status',
                        checked: true
                    },
                    {
                        key: 'branch',
                        label: 'Branch',
                        checked: false
                    },
                    {
                        key: 'payment_method',
                        label: 'Payment Method',
                        checked: false
                    }
                ],
                inventory: [{
                        key: 'product',
                        label: 'Product Name',
                        checked: true
                    },
                    {
                        key: 'sku',
                        label: 'SKU',
                        checked: true
                    },
                    {
                        key: 'category',
                        label: 'Category',
                        checked: true
                    },
                    {
                        key: 'branch',
                        label: 'Branch',
                        checked: true
                    },
                    {
                        key: 'in_stock',
                        label: 'In Stock',
                        checked: true
                    },
                    {
                        key: 'min_stock',
                        label: 'Min Stock',
                        checked: false
                    },
                    {
                        key: 'cost_price',
                        label: 'Cost Price',
                        checked: false,
                        isCurrency: true
                    },
                    {
                        key: 'sell_price',
                        label: 'Sell Price',
                        checked: false,
                        isCurrency: true
                    },
                    {
                        key: 'stock_value',
                        label: 'Stock Value',
                        checked: true,
                        isCurrency: true
                    },
                    {
                        key: 'status',
                        label: 'Status',
                        checked: true,
                        isStatus: true
                    }
                ],
                financial: [{
                        key: 'month',
                        label: 'Month',
                        checked: true
                    },
                    {
                        key: 'revenue',
                        label: 'Revenue',
                        checked: true,
                        isCurrency: true
                    },
                    {
                        key: 'cogs',
                        label: 'COGS',
                        checked: true,
                        isCurrency: true
                    },
                    {
                        key: 'gross_profit',
                        label: 'Gross Profit',
                        checked: true,
                        isCurrency: true
                    },
                    {
                        key: 'operating_expenses',
                        label: 'Operating Expenses',
                        checked: true,
                        isCurrency: true
                    },
                    {
                        key: 'net_profit',
                        label: 'Net Profit',
                        checked: true,
                        isCurrency: true
                    },
                    {
                        key: 'margin',
                        label: 'Margin %',
                        checked: true
                    },
                    {
                        key: 'tax_amount',
                        label: 'Tax Amount',
                        checked: false
                    }
                ]
            };

            // Dynamic filter templates by report type
            var filterTemplates = {
                sales: '<div class="mb-3"><label class="bp-form-label">Payment Status</label><select class="bp-form-select w-100" name="payment_status"><option value="">All Status</option><option>Paid</option><option>Partial</option><option>Unpaid</option></select></div>',
                purchase: '<div class="mb-3"><label class="bp-form-label">Status</label><select class="bp-form-select w-100" name="status"><option value="">All Status</option><option>Received</option><option>Partial</option><option>Pending</option></select></div>',
                inventory: '<div class="mb-3"><label class="bp-form-label">Stock Status</label><select class="bp-form-select w-100" name="stock_status"><option value="">All Status</option><option>In Stock</option><option>Low Stock</option><option>Out of Stock</option></select></div>',
                financial: ''
            };

            /**
             * Format a number as BDT in Bangladesh lakh system (e.g., BDT 1,23,456).
             */
            function formatBDT(value) {
                var num = parseFloat(value) || 0;
                var isNegative = num < 0;
                num = Math.abs(num);

                var parts = num.toFixed(2).split('.');
                var intPart = parts[0];
                var decPart = parts[1];

                // Bangladesh lakh system: last 3 digits, then groups of 2
                var result = '';
                if (intPart.length <= 3) {
                    result = intPart;
                } else {
                    result = intPart.slice(-3);
                    intPart = intPart.slice(0, -3);
                    while (intPart.length > 2) {
                        result = intPart.slice(-2) + ',' + result;
                        intPart = intPart.slice(0, -2);
                    }
                    if (intPart.length > 0) {
                        result = intPart + ',' + result;
                    }
                }

                return (isNegative ? '-' : '') + '{{ currency_symbol() }} ' + result;
            }

            /**
             * Format a date string (YYYY-MM-DD) as DD MMM YYYY.
             */
            function formatDate(dateStr) {
                if (!dateStr || dateStr === '-') {
                    return '-';
                }
                var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                var parts = dateStr.split('-');
                if (parts.length !== 3) {
                    return dateStr;
                }
                return parseInt(parts[2], 10) + ' ' + months[parseInt(parts[1], 10) - 1] + ' ' + parts[0];
            }

            /**
             * Get the appropriate status badge HTML for inventory status.
             */
            function statusBadge(status) {
                var badgeMap = {
                    'In Stock': 'bp-badge-success',
                    'Low Stock': 'bp-badge-warning',
                    'Out of Stock': 'bp-badge-danger'
                };
                var cls = badgeMap[status] || 'bp-badge-secondary';
                return '<span class="bp-badge ' + cls + '">' + status + '</span>';
            }

            /**
             * Format a cell value based on column definition and raw value.
             */
            function formatCellValue(col, value) {
                if (value === null || value === undefined) {
                    return '-';
                }
                if (col.isStatus) {
                    return statusBadge(value);
                }
                if (col.isCurrency && typeof value === 'number') {
                    return formatBDT(value);
                }
                if (col.key === 'date') {
                    return formatDate(String(value));
                }
                return String(value);
            }

            // Report type change handler
            $('#reportType').on('change', function() {
                var type = $(this).val();

                if (!type) {
                    $('#columnEmptyState').removeClass('d-none');
                    $('#columnCheckboxes').addClass('d-none').empty();
                    $('#dynamicFilters').empty();
                    return;
                }

                // Populate columns
                var columns = reportColumns[type];
                var html = '';
                columns.forEach(function(col) {
                    html += '<div class="form-check mb-2">';
                    html +=
                        '<input class="form-check-input bp-column-check" type="checkbox" id="col_' +
                        col.key + '" value="' + col.key + '"' + (col.checked ? ' checked' : '') +
                        '>';
                    html += '<label class="form-check-label fs-13" for="col_' + col.key + '">' + col
                        .label + '</label>';
                    html += '</div>';
                });
                $('#columnEmptyState').addClass('d-none');
                $('#columnCheckboxes').removeClass('d-none').html(html);

                // Populate dynamic filters
                $('#dynamicFilters').html(filterTemplates[type] || '');

                // Show/hide date range for inventory
                if (type === 'inventory') {
                    $('#dateRangeSection').addClass('d-none');
                } else {
                    $('#dateRangeSection').removeClass('d-none');
                }
            });

            // Select/Deselect all columns
            $('#selectAllCols').on('click', function() {
                $('.bp-column-check').prop('checked', true);
            });
            $('#deselectAllCols').on('click', function() {
                $('.bp-column-check').prop('checked', false);
            });

            // Generate Report via AJAX
            $('#btnGenerateReport').on('click', function() {
                var type = $('#reportType').val();
                if (!type) {
                    alert('Please select a report type.');
                    return;
                }

                var selectedCols = [];
                $('.bp-column-check:checked').each(function() {
                    selectedCols.push($(this).val());
                });

                if (selectedCols.length === 0) {
                    alert('Please select at least one column.');
                    return;
                }

                var columns = reportColumns[type];
                var typeLabels = {
                    sales: 'Sales',
                    purchase: 'Purchase',
                    inventory: 'Inventory',
                    financial: 'Financial'
                };

                var $btn = $(this);
                $btn.prop('disabled', true).html(
                    '<i class="fa-solid fa-spinner fa-spin"></i> Generating...');

                var formData = {
                    report_type: type,
                    date_from: $('input[name="date_from"]').val(),
                    date_to: $('input[name="date_to"]').val()
                };

                $.ajax({
                    url: '{{ route('reports.custom.generate') }}',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (!response.success || !response.data) {
                            alert('No data returned from the server.');
                            return;
                        }

                        var data = response.data;

                        // Show preview, hide empty state
                        $('#reportEmptyState').addClass('d-none');
                        $('#reportPreview').removeClass('d-none');
                        $('#exportDropdown, #btnPrintReport').removeClass('d-none');

                        // Set title with current date
                        var today = new Date();
                        var todayStr = formatDate(today.getFullYear() + '-' + String(today
                            .getMonth() + 1).padStart(2, '0') + '-' + String(today
                            .getDate()).padStart(2, '0'));
                        $('#previewTitle').text('Custom ' + typeLabels[type] + ' Report');
                        $('#previewMeta').text('Generated: ' + todayStr);

                        // Build stats
                        var statsHtml = '';
                        if (data.stats && data.stats.length) {
                            data.stats.forEach(function(stat) {
                                var displayValue = stat.is_currency ? formatBDT(stat
                                    .value) : String(stat.value);
                                statsHtml += '<div class="col-sm-6 col-md-3">';
                                statsHtml +=
                                    '<div class="bp-stat-card"><div class="bp-stat-icon icon-' +
                                    stat.color + '"><i class="fa-solid ' + stat.icon +
                                    '"></i></div>';
                                statsHtml +=
                                    '<div class="bp-stat-content"><div class="bp-stat-label">' +
                                    stat.label + '</div>';
                                statsHtml += '<div class="bp-stat-value">' +
                                    displayValue + '</div></div></div></div>';
                            });
                        }
                        $('#previewStats').html(statsHtml);

                        // Build chart
                        if (previewChart) {
                            previewChart.destroy();
                        }

                        var chartLabels = data.chart && data.chart.labels ? data.chart.labels :
                            [];
                        var chartDataVals = data.chart && data.chart.data ? data.chart.data :
                    [];
                        var chartLabel = data.chart && data.chart.label ? data.chart.label : '';

                        if (chartLabels.length > 0) {
                            $('#previewChartCard').removeClass('d-none');
                            // Format chart labels (dates)
                            if (type !== 'inventory' && type !== 'financial') {
                                chartLabels = chartLabels.map(function(lbl) {
                                    return formatDate(String(lbl));
                                });
                            }

                            var chartCtx = document.getElementById('previewChart').getContext(
                                '2d');
                            previewChart = new Chart(chartCtx, {
                                type: type === 'inventory' ? 'doughnut' : 'bar',
                                data: {
                                    labels: chartLabels,
                                    datasets: [{
                                        label: chartLabel,
                                        data: chartDataVals,
                                        backgroundColor: type === 'inventory' ?
                                            ['#1B4F72', '#2E86C1', '#117A65',
                                                '#D4AC0D', '#E67E22', '#C0392B',
                                                '#8E44AD', '#2C3E50', '#1ABC9C',
                                                '#E74C3C'
                                            ] :
                                            'rgba(27, 79, 114, 0.7)',
                                        borderColor: type === 'inventory' ?
                                            undefined : '#1B4F72',
                                        borderWidth: type === 'inventory' ? 2 :
                                            1,
                                        borderRadius: type === 'inventory' ?
                                            undefined : 4
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    plugins: {
                                        legend: {
                                            position: 'bottom'
                                        },
                                        tooltip: {
                                            callbacks: {
                                                label: function(context) {
                                                    return (context.dataset.label ||
                                                            context.label) +
                                                        ': {{ currency_symbol() }} ' +
                                                        context.raw.toLocaleString(
                                                            'en-IN');
                                                }
                                            }
                                        }
                                    },
                                    scales: type === 'inventory' ? {} : {
                                        y: {
                                            beginAtZero: true,
                                            ticks: {
                                                callback: function(value) {
                                                    return '{{ currency_symbol() }} ' +
                                                        value.toLocaleString(
                                                            'en-IN');
                                                }
                                            }
                                        }
                                    }
                                }
                            });
                        } else {
                            $('#previewChartCard').addClass('d-none');
                        }

                        // Build table header
                        var selectedColumns = columns.filter(function(col) {
                            return selectedCols.indexOf(col.key) !== -1;
                        });

                        var thead = '<tr>';
                        selectedColumns.forEach(function(col) {
                            thead += '<th>' + col.label + '</th>';
                        });
                        thead += '</tr>';
                        $('#previewTableHead').html(thead);

                        // Build table body from row objects
                        var rows = data.rows || [];
                        var tbody = '';

                        if (rows.length === 0) {
                            tbody = '<tr><td colspan="' + selectedColumns.length +
                                '" class="text-center text-muted py-4">No data found for the selected criteria.</td></tr>';
                        } else {
                            rows.forEach(function(row) {
                                tbody += '<tr>';
                                selectedColumns.forEach(function(col) {
                                    var val = row[col.key];
                                    tbody += '<td>' + formatCellValue(col,
                                        val) + '</td>';
                                });
                                tbody += '</tr>';
                            });
                        }

                        $('#previewTableBody').html(tbody);
                        $('#previewRowCount').text(rows.length + ' rows');
                        $('#previewPaginationInfo').text(rows.length > 0 ? 'Showing 1-' + rows
                            .length + ' of ' + rows.length + ' results' : 'No results');
                    },
                    error: function(xhr) {
                        var msg = 'Failed to generate report.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            msg += ' ' + xhr.responseJSON.message;
                        }
                        alert(msg);
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html(
                            '<i class="fa-solid fa-play"></i> Generate Report');
                    }
                });
            });

            // Reset builder
            $('#btnResetBuilder').on('click', function() {
                $('#reportType').val('').trigger('change');
                $('#customReportForm')[0].reset();
                $('#reportEmptyState').removeClass('d-none');
                $('#reportPreview').addClass('d-none');
                $('#exportDropdown, #btnPrintReport').addClass('d-none');
                if (previewChart) {
                    previewChart.destroy();
                    previewChart = null;
                }
            });

            // Print report
            $('#btnPrintReport').on('click', function() {
                window.print();
            });
        });
    </script>
@endpush
