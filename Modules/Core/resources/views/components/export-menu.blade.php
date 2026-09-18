@props([
    'pdf' => true,
    'excel' => true,
    'csv' => false,
    'pdfId' => 'btnExportPdf',
    'excelId' => 'btnExportExcel',
    'csvId' => 'btnExportCsv',
])

{{--
    Presentational "Export" dropdown for list pages whose export is triggered
    by page-specific JavaScript (e.g. `?…&export=pdf` on the page's own route),
    rather than the generic `/export/{module}` endpoint. The dropdown items keep
    the standard button ids so existing click handlers continue to work — only
    the markup is consolidated from separate buttons into one dropdown.

    For pages that use the generic export route, use <x-core::export-dropdown>.
--}}
<div class="dropdown">
    <button class="bp-btn bp-btn-warning dropdown-toggle" data-bs-toggle="dropdown" type="button">
        <i class="fa-solid fa-download me-1"></i> Export
    </button>
    <ul class="dropdown-menu">
        @if ($pdf)
            <li><button type="button" class="dropdown-item" id="{{ $pdfId }}"><i
                        class="fa-solid fa-file-pdf me-2"></i>PDF</button></li>
        @endif
        @if ($excel)
            <li><button type="button" class="dropdown-item" id="{{ $excelId }}"><i
                        class="fa-solid fa-file-excel me-2"></i>Excel</button></li>
        @endif
        @if ($csv)
            <li><button type="button" class="dropdown-item" id="{{ $csvId }}"><i
                        class="fa-solid fa-file-csv me-2"></i>CSV</button></li>
        @endif
    </ul>
</div>
