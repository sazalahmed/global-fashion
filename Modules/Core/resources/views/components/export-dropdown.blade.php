@props(['module', 'params' => [], 'print' => true])

@php
    $exportQuery = array_merge(request()->query(), $params);
    unset($exportQuery['format'], $exportQuery['page']);
@endphp

<div class="dropdown">
    <button class="bp-btn bp-btn-warning dropdown-toggle" data-bs-toggle="dropdown">
        <i class="fa-solid fa-download me-1"></i> Export
    </button>
    <ul class="dropdown-menu">
        <li><a class="dropdown-item"
                href="{{ route('export', array_merge(['module' => $module, 'format' => 'pdf'], $exportQuery)) }}"><i
                    class="fa-solid fa-file-pdf me-2"></i>PDF</a></li>
        <li><a class="dropdown-item"
                href="{{ route('export', array_merge(['module' => $module, 'format' => 'xlsx'], $exportQuery)) }}"><i
                    class="fa-solid fa-file-excel me-2"></i>Excel</a></li>
        <li><a class="dropdown-item"
                href="{{ route('export', array_merge(['module' => $module, 'format' => 'csv'], $exportQuery)) }}"><i
                    class="fa-solid fa-file-csv me-2"></i>CSV</a></li>
        @if ($print)
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" target="_blank" rel="noopener"
                    href="{{ route('export', array_merge(['module' => $module, 'format' => 'print'], $exportQuery)) }}"><i
                        class="fa-solid fa-print me-2"></i>Print</a></li>
        @endif
    </ul>
</div>
