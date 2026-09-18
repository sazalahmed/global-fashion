@extends('core::layouts.master')

@section('title', 'Journal Entry — ' . $entry->entry_number)
@section('page-title', 'Journal Entry — ' . $entry->entry_number)

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('accounting.journal-entries') }}">Journal Entries</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>{{ $entry->entry_number }}</span>
@endsection

@section('page-actions')
<button class="bp-btn bp-btn-outline" id="btnPrint"><i class="fa-solid fa-print me-1"></i> Print</button>
<a href="{{ route('accounting.journal-entries') }}" class="bp-btn bp-btn-primary"><i class="fa-solid fa-arrow-left me-1"></i> Back</a>
@endsection

@section('content')

    @php
        $totalDebit = $entry->totalDebit();
        $totalCredit = $entry->totalCredit();
        $difference = $totalDebit - $totalCredit;

        $statusBadgeClass = match ($entry->status) {
            'posted' => 'bp-badge-success',
            'void' => 'bp-badge-danger',
            default => 'bp-badge-dark',
        };
    @endphp

    <div class="row g-4">

        <!-- Left Column: Entry Info -->
        <div class="col-xl-4">
            <div class="bp-card mb-4">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-circle-info me-2"></i>Entry Information</h5>
                </div>
                <div class="bp-card-body">
                    <div class="bp-info-row">
                        <div class="bp-info-label bp-info-label-lg">Entry #</div>
                        <div class="bp-info-value fw-800">{{ $entry->entry_number }}</div>
                    </div>
                    <div class="bp-info-row">
                        <div class="bp-info-label bp-info-label-lg">Date</div>
                        <div class="bp-info-value">
                            <span id="entryDateText">{{ $entry->entry_date->format('d M Y') }}</span>
                            @bpCan('accounting.edit')
                                @if ($entry->status !== 'voided')
                                    <button type="button" class="bp-btn bp-btn-sm bp-btn-icon bp-btn-outline ms-2"
                                        id="entryDateEditToggle" title="{{ __('Change date') }}">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                @endif
                            @endbpCan
                            <form action="{{ route('accounting.journal-entries.update-date', $entry) }}" method="POST"
                                class="d-none mt-2" id="entryDateForm">
                                @csrf
                                @method('PATCH')
                                <div class="d-flex gap-2">
                                    <input type="date" class="bp-form-control" name="entry_date"
                                        value="{{ $entry->entry_date->format('Y-m-d') }}" required>
                                    <button type="submit" class="bp-btn bp-btn-sm bp-btn-primary" title="{{ __('Save') }}">
                                        <i class="fa-solid fa-check"></i>
                                    </button>
                                    <button type="button" class="bp-btn bp-btn-sm bp-btn-outline" id="entryDateCancel"
                                        title="{{ __('Cancel') }}">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="bp-info-row">
                        <div class="bp-info-label bp-info-label-lg">Reference</div>
                        <div class="bp-info-value">
                            @if ($entry->reference)
                                <code class="fs-12">{{ $entry->reference }}</code>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </div>
                    </div>
                    <div class="bp-info-row">
                        <div class="bp-info-label bp-info-label-lg">Status</div>
                        <div class="bp-info-value">
                            <span class="bp-badge {{ $statusBadgeClass }}">{{ ucfirst($entry->status) }}</span>
                        </div>
                    </div>
                    <div class="bp-info-row">
                        <div class="bp-info-label bp-info-label-lg">Description</div>
                        <div class="bp-info-value">{{ $entry->description ?? '—' }}</div>
                    </div>
                    <div class="bp-info-row">
                        <div class="bp-info-label bp-info-label-lg">Source</div>
                        <div class="bp-info-value">{{ $entry->source ?? '—' }}</div>
                    </div>
                    <div class="bp-info-row">
                        <div class="bp-info-label bp-info-label-lg">Total Amount</div>
                        <div class="bp-info-value fw-800">{{ money($totalDebit) }}</div>
                    </div>
                    <div class="bp-info-row">
                        <div class="bp-info-label bp-info-label-lg">Created By</div>
                        <div class="bp-info-value">{{ $entry->creator->name ?? 'N/A' }}</div>
                    </div>
                    <div class="bp-info-row bp-info-row-last">
                        <div class="bp-info-label bp-info-label-lg">Created At</div>
                        <div class="bp-info-value">{{ $entry->created_at->format('d M Y, h:i A') }}</div>
                    </div>
                </div>
            </div>

            <!-- Post / Void Actions -->
            @if ($entry->status === 'draft')
                @bpCan('accounting.edit')
                    <div class="d-flex flex-column gap-2 mb-4">
                        <form method="POST" action="{{ route('accounting.journal-entries.post', $entry) }}">
                            @csrf
                            <p class="fs-13 text-muted mb-3">Post this entry to make it permanent in the ledger.</p>
                            <button type="submit" class="bp-btn bp-btn-success w-100">
                                <i class="fa-solid fa-check me-1"></i> Post Entry
                            </button>
                        </form>
                    </div>
                @endbpCan
            @endif

            @if ($entry->status === 'posted')
                @bpCan('accounting.edit')
                    <div class="bp-card mb-4">
                        <div class="bp-card-header">
                            <h5 class="bp-card-title"><i class="fa-solid fa-ban me-2"></i>Void Entry</h5>
                        </div>
                        <div class="bp-card-body">
                            <form method="POST" action="{{ route('accounting.journal-entries.void', $entry) }}">
                                @csrf
                                <div class="mb-3">
                                    <label class="bp-form-label">Void Reason *</label>
                                    <input type="text" name="void_reason" class="bp-form-control"
                                        placeholder="Enter reason for voiding…" required>
                                </div>
                                <button type="submit" class="bp-btn bp-btn-danger justify-content-center w-100">
                                    <i class="fa-solid fa-ban me-1"></i> Void Entry
                                </button>
                            </form>
                        </div>
                    </div>
                @endbpCan
            @endif

            <!-- Attachments -->
            <div class="bp-card mb-4">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-paperclip me-2"></i>Attachments</h5>
                </div>
                <div class="bp-card-body">
                    @if ($entry->attachment_path)
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <i class="fa-solid fa-file-pdf text-danger fs-5"></i>
                            <div>
                                <div class="fw-600 fs-13">{{ basename($entry->attachment_path) }}</div>
                            </div>
                            <a href="{{ upload_url($entry->attachment_path) }}"
                                class="bp-btn bp-btn-sm bp-btn-icon bp-btn-outline ms-auto" title="Download" download><i
                                    class="fa-solid fa-download"></i></a>
                        </div>
                    @else
                        <div class="text-muted fs-13">No attachments</div>
                    @endif
                </div>
            </div>

            <!-- Audit Trail -->
            <div class="bp-card">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-clock-rotate-left me-2"></i>Audit Trail</h5>
                </div>
                <div class="bp-card-body">
                    <div class="bp-timeline">
                        @if ($entry->status === 'void' && $entry->voidedByUser)
                            <div class="bp-timeline-item">
                                <div class="bp-timeline-marker bp-bg-danger"></div>
                                <div class="bp-timeline-content">
                                    <div class="fw-600 fs-13">Entry Voided</div>
                                    <div class="fs-12 text-muted">{{ $entry->voided_at?->format('d M Y, h:i A') }} —
                                        {{ $entry->voidedByUser->name }}</div>
                                </div>
                            </div>
                        @endif
                        @if ($entry->postedByUser)
                            <div class="bp-timeline-item">
                                <div class="bp-timeline-marker bp-bg-success"></div>
                                <div class="bp-timeline-content">
                                    <div class="fw-600 fs-13">Entry Posted</div>
                                    <div class="fs-12 text-muted">{{ $entry->posted_at?->format('d M Y, h:i A') }} —
                                        {{ $entry->postedByUser->name }}</div>
                                </div>
                            </div>
                        @endif
                        <div class="bp-timeline-item">
                            <div class="bp-timeline-marker bp-bg-primary"></div>
                            <div class="bp-timeline-content">
                                <div class="fw-600 fs-13">Entry Created</div>
                                <div class="fs-12 text-muted">{{ $entry->created_at->format('d M Y, h:i A') }} —
                                    {{ $entry->creator->name ?? 'System' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Line Items -->
        <div class="col-xl-8">

            <!-- Summary Stats -->
            <div class="row g-3 mb-4">
                <div class="col-sm-4">
                    <div class="bp-stat-card">
                        <div class="bp-stat-icon icon-success"><i class="fa-solid fa-arrow-down"></i></div>
                        <div class="bp-stat-content">
                            <div class="bp-stat-label">Total Debit</div>
                            <div class="bp-stat-value">{{ money($totalDebit) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="bp-stat-card">
                        <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-arrow-up"></i></div>
                        <div class="bp-stat-content">
                            <div class="bp-stat-label">Total Credit</div>
                            <div class="bp-stat-value">{{ money($totalCredit) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="bp-stat-card">
                        <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-scale-balanced"></i></div>
                        <div class="bp-stat-content">
                            <div class="bp-stat-label">Difference</div>
                            <div class="bp-stat-value">{{ money(abs($difference)) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Line Items Table -->
            <div class="bp-card mb-4">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-list me-2"></i>Debit &amp; Credit Lines</h5>
                </div>
                <div class="bp-card-body p-0">
                    <div class="bp-table-wrapper debit_credit_line">
                        <table class="bp-table">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width:40px;">#</th>
                                    <th>Account Code</th>
                                    <th>Account Name</th>
                                    <th>Description</th>
                                    <th>Debit ({{ currency_symbol() }})</th>
                                    <th>Credit ({{ currency_symbol() }})</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($entry->lines as $index => $line)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td><code>{{ $line->account->account_code }}</code></td>
                                        <td>{{ $line->account->account_name }}</td>
                                        <td>{{ $line->description ?? '—' }}</td>
                                        <td class="fw-700">
                                            @if ($line->debit_amount > 0)
                                                {{ money($line->debit_amount) }}
                                            @else
                                                <span></span>
                                            @endif
                                        </td>
                                        <td class="fw-700">
                                            @if ($line->credit_amount > 0)
                                                {{ money($line->credit_amount) }}
                                            @else
                                                <span></span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="bp-table-totals-row">
                                    <td colspan="4" class="text-end p-2">Totals:</td>
                                    <td class="p-2">{{ money($totalDebit) }}</td>
                                    <td class="p-2">{{ money($totalCredit) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div class="bp-card">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-sticky-note me-2"></i>Notes</h5>
                </div>
                <div class="bp-card-body">
                    @if ($entry->notes)
                        <p class="text-muted mb-0">{{ $entry->notes }}</p>
                    @else
                        <p class="text-muted mb-0">No notes recorded for this entry.</p>
                    @endif
                </div>
            </div>

        </div>

    </div>

@endsection

@push('scripts')
    <script>
        'use strict';

        $(function() {
            $('#btnPrint').on('click', function() {
                window.print();
            });

            $('#entryDateEditToggle').on('click', function() {
                $('#entryDateText, #entryDateEditToggle').addClass('d-none');
                $('#entryDateForm').removeClass('d-none');
            });
            $('#entryDateCancel').on('click', function() {
                $('#entryDateForm').addClass('d-none');
                $('#entryDateText, #entryDateEditToggle').removeClass('d-none');
            });
        });
    </script>
@endpush
