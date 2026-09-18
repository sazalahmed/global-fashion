@extends('core::layouts.master')

@section('title', __("Credit Notes"))
@section('page-title', __("Credit Notes"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Accounting</span>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Credit Notes</span>
@endsection

@section('page-actions')
<x-core::export-dropdown module="credit-notes" />
@bpCan('accounting.create')
<a href="{{ route('accounting.credit-notes.create') }}" class="bp-btn bp-btn-primary">
    <i class="fa-solid fa-plus me-1"></i> Create Credit Note
</a>
@endbpCan
@endsection

@section('content')

{{-- Stat Cards --}}
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-sm-6">
        <div class="bp-stat-card">
            <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-file-invoice"></i></div>
            <div class="bp-stat-content">
                <div class="bp-stat-label">Total Credit Notes</div>
                <div class="bp-stat-value">{{ $stats['total'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="bp-stat-card">
            <div class="bp-stat-icon icon-warning"><i class="fa-solid fa-file-pen"></i></div>
            <div class="bp-stat-content">
                <div class="bp-stat-label">Draft</div>
                <div class="bp-stat-value">{{ $stats['draft'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="bp-stat-card">
            <div class="bp-stat-icon icon-success"><i class="fa-solid fa-check-circle"></i></div>
            <div class="bp-stat-content">
                <div class="bp-stat-label">Issued</div>
                <div class="bp-stat-value">{{ $stats['issued'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="bp-stat-card">
            <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-bangladeshi-taka-sign"></i></div>
            <div class="bp-stat-content">
                <div class="bp-stat-label">Total Amount</div>
                <div class="bp-stat-value">{{ money($stats['total_amount']) }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Filter Bar --}}
<div class="bp-card mb-4">
    <div class="bp-card-body">
        <form method="GET" id="creditNoteFilterForm">
            <div class="row g-3 align-items-end bp-report-filters">
                <div class="col-md-3">
                    <label class="bp-form-label">Search</label>
                    <div class="bp-table-search">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" class="bp-form-control" name="search" value="{{ request('search') }}" placeholder="CN number, customer, reason...">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="bp-form-label">Status</label>
                    <select class="bp-form-select w-100" name="status">
                        <option value="">All Status</option>
                        <option value="draft"     {{ request('status') === 'draft'     ? 'selected' : '' }}>Draft</option>
                        <option value="issued"    {{ request('status') === 'issued'    ? 'selected' : '' }}>Issued</option>
                        <option value="applied"   {{ request('status') === 'applied'   ? 'selected' : '' }}>Applied</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="bp-form-label">Date From</label>
                    <input type="date" class="bp-form-control" name="date_from" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="bp-form-label">Date To</label>
                    <input type="date" class="bp-form-control" name="date_to" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="bp-btn bp-btn-primary" title="Apply Filters"><i class="fa-solid fa-filter"></i></button>
                    <a href="{{ route('accounting.credit-notes.index') }}" class="bp-btn bp-btn-danger" title="Reset Filters"><i class="fa-solid fa-rotate"></i></a>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Credit Notes Table --}}
<div class="bp-card">
    <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-file-invoice me-2"></i>Credit Notes</h5>
    </div>
    @bpCan('accounting.edit')
    <div class="bp-bulk-actions" id="bulkActionsBar" hidden>
        <div class="d-flex align-items-center gap-2 px-3 py-2">
            <span class="bp-bulk-count fw-600 fs-13"><span class="count">0</span> selected</span>
            <div class="d-flex gap-2 ms-auto">
                <button class="bp-btn bp-btn-sm bp-btn-danger" data-bulk-action="status" data-bulk-status="cancelled"><i class="fa-solid fa-ban me-1"></i> Cancel</button>
            </div>
        </div>
    </div>
    @endbpCan
    <div class="bp-card-body p-0">
        <div class="bp-table-wrapper">
            <table class="bp-table">
                <thead>
                    <tr>
                        <th><input type="checkbox" class="form-check-input bp-check-all"></th>
                        <th>CN Number</th>
                        <th>Issue Date</th>
                        <th>Customer</th>
                        <th>Reason</th>
                        <th class="text-end">Total Amount</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($creditNotes as $creditNote)
                    @php
                        $statusBadge = match($creditNote->status) {
                            'draft'     => 'bp-badge-dark',
                            'issued'    => 'bp-badge-success',
                            'applied'   => 'bp-badge-primary',
                            'cancelled' => 'bp-badge-danger',
                            default     => 'bp-badge-secondary',
                        };
                    @endphp
                    <tr>
                        <td><input type="checkbox" class="form-check-input row-checkbox" value="{{ $creditNote->id }}"></td>
                        <td>
                            <a href="{{ route('accounting.credit-notes.show', $creditNote) }}" class="fw-700">
                                {{ $creditNote->cn_number }}
                            </a>
                        </td>
                        <td>{{ $creditNote->issue_date->format('d M Y') }}</td>
                        <td>{{ $creditNote->customer_name ?? 'N/A' }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($creditNote->reason, 55) }}</td>
                        <td class="text-end fw-700">{{ money($creditNote->total_amount) }}</td>
                        <td class="text-center">
                            <span class="bp-badge {{ $statusBadge }}">{{ ucfirst($creditNote->status) }}</span>
                        </td>
                        <td class="text-center">
                            @bpCanAny('accounting.view','accounting.delete')
                            <div class="dropdown">
                                <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    @bpCan('accounting.view')
                                    <li>
                                        <a class="dropdown-item" href="{{ route('accounting.credit-notes.show', $creditNote) }}">
                                            <i class="fa-solid fa-eye me-2"></i>View
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('accounting.credit-notes.print', $creditNote) }}" target="_blank">
                                            <i class="fa-solid fa-print me-2"></i>Print
                                        </a>
                                    </li>
                                    @endbpCan
                                    @if($creditNote->status === 'draft')
                                    @bpCan('accounting.delete')
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="POST" action="{{ route('accounting.credit-notes.destroy', $creditNote) }}"
                                              onsubmit="return confirm('Delete credit note {{ $creditNote->cn_number }}? This cannot be undone.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="dropdown-item text-danger">
                                                <i class="fa-solid fa-trash me-2"></i>Delete
                                            </button>
                                        </form>
                                    </li>
                                    @endbpCan
                                    @endif
                                </ul>
                            </div>
                            @endbpCanAny
                        </td>
                    </tr>
                    @empty
                    <x-core::table.empty colspan="8" icon="fa-solid fa-file-invoice" title="No credit notes found.">
                        <a href="{{ route('accounting.credit-notes.create') }}">Create your first credit note</a>.
                    </x-core::table.empty>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <x-core::table.pagination :paginator="$creditNotes" itemLabel="credit notes" />
</div>

@endsection

@push('scripts')
<script>
'use strict';

$(function () {
    $('#creditNoteFilterForm select').on('change', function () {
        $('#creditNoteFilterForm').submit();
    });

    // Bulk select — check-all
    $('.bp-check-all').on('change', function () {
        var isChecked = $(this).prop('checked');
        $('.row-checkbox').prop('checked', isChecked);
        updateBulkBar();
    });

    // Bulk select — individual row
    $(document).on('change', '.row-checkbox', function () {
        updateBulkBar();
    });

    function updateBulkBar() {
        var count = $('.row-checkbox:checked').length;
        var $bar = $('#bulkActionsBar');
        if (count > 0) {
            $bar.removeAttr('hidden');
            $bar.find('.count').text(count);
        } else {
            $bar.attr('hidden', true);
        }
    }
});
</script>
@endpush
