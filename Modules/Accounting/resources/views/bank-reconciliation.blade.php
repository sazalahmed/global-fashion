@extends('core::layouts.master')

@section('title', __("Bank Reconciliation"))
@section('page-title', __("Bank Reconciliation"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Accounting</span>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Bank Reconciliation</span>
@endsection

@section('page-actions')
<x-core::export-menu :excel="false" />
<button class="bp-btn bp-btn-outline" id="btnPrint"><i class="fa-solid fa-print me-1"></i> Print</button>
@endsection

@section('content')

<!-- Filter Bar -->
<div class="bp-card mb-4">
  <div class="bp-card-body">
    <form method="GET" id="reconFilterForm">
      <div class="row g-3 align-items-end bp-report-filters">
        <div class="col-md-4">
          <label class="bp-form-label">Bank Account *</label>
          <select class="bp-form-select w-100" name="bank_account" id="bankAccountSelect" required>
            <option value="">Select Bank Account</option>
            @foreach($bankAccounts as $account)
              <option value="{{ $account->id }}" {{ (string) $selectedAccountId === (string) $account->id ? 'selected' : '' }}>
                {{ $account->account_code }} &mdash; {{ $account->account_name }}
                @if($account->bank_name) ({{ $account->bank_name }}@if($account->bank_branch) — {{ $account->bank_branch }}@endif)@endif
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <label class="bp-form-label">Statement Date *</label>
          <input type="date" class="bp-form-control" name="statement_date" value="{{ request('statement_date', date('Y-m-d')) }}" required>
        </div>
        <div class="col-md-2">
          <label class="bp-form-label">Statement Closing Balance</label>
          <input type="number" class="bp-form-control" name="statement_balance" id="statementBalance" value="{{ request('statement_balance', '') }}" placeholder="BDT" step="0.01">
        </div>
        <div class="col-md-3 d-flex gap-2">
          <button type="submit" class="bp-btn bp-btn-primary" title="Apply Filters"><i class="fa-solid fa-filter"></i></button>
          <a href="{{ route('accounting.bank-reconciliation') }}" class="bp-btn bp-btn-danger" title="Reset Filters"><i class="fa-solid fa-rotate"></i></a>
        </div>
      </div>
    </form>
  </div>
</div>

@if($summary)
<!-- Reconciliation Summary Stats -->
<div class="row g-3 mb-4">
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-book-open"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Book Balance</div>
        <div class="bp-stat-value">{{ money($summary['book_balance'] ?? 0) }}</div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-success"><i class="fa-solid fa-building-columns"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Bank Statement Balance</div>
        <div class="bp-stat-value">{{ money($summary['statement_balance'] ?? 0) }}</div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-warning"><i class="fa-solid fa-triangle-exclamation"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Difference</div>
        <div class="bp-stat-value">{{ money(abs($summary['difference'] ?? 0)) }}</div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-list-check"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Unreconciled Items</div>
        <div class="bp-stat-value">{{ $summary['unreconciled_count'] ?? 0 }}</div>
      </div>
    </div>
  </div>
</div>

<div class="row g-4">

  <!-- Book Balance Side -->
  <div class="col-xl-6">
    <div class="bp-card">
      <div class="bp-card-header d-flex justify-content-between align-items-center">
        <h5 class="bp-card-title"><i class="fa-solid fa-book-open me-2"></i>Book Transactions</h5>
        @if($selectedAccount)
          <span class="bp-badge bp-badge-primary">{{ $selectedAccount->account_name }}@if($selectedAccount->bank_branch) &mdash; {{ $selectedAccount->bank_branch }}@endif</span>
        @endif
      </div>
      <div class="bp-card-body p-0">
        <div class="bp-table-wrapper">
          <table class="bp-table" id="bookTransactionsTable">
            <thead>
              <tr>
                <th class="text-center bp-recon-check-col">
                  <input type="checkbox" class="bp-check-all-book" title="Select All">
                </th>
                <th>Date</th>
                <th>Description</th>
                <th>Reference</th>
                <th class="text-end">Amount ({{ currency_symbol() }})</th>
                <th class="text-center">Status</th>
              </tr>
            </thead>
            <tbody>
              @forelse($bookItems as $item)
                <tr class="{{ $item->is_reconciled ? 'bp-recon-matched' : '' }}">
                  <td class="text-center">
                    <input type="checkbox"
                      class="bp-recon-check book-check"
                      {{ $item->is_reconciled ? 'checked disabled' : '' }}
                      @if(!$item->is_reconciled)
                        data-amount="{{ abs($item->amount) }}"
                        data-type="{{ $item->amount < 0 ? 'debit' : 'credit' }}"
                        data-item-id="{{ $item->id }}"
                      @endif
                    >
                  </td>
                  <td>{{ $item->transaction_date->format('d M Y') }}</td>
                  <td class="fw-600">{{ $item->description }}</td>
                  <td><code class="fs-12">{{ $item->reference ?? '—' }}</code></td>
                  <td class="text-end fw-700 {{ $item->amount >= 0 ? 'text-success' : 'text-danger' }}">
                    @if($item->amount < 0)
                      ({{ money(abs($item->amount)) }})
                    @else
                      {{ money($item->amount) }}
                    @endif
                  </td>
                  <td class="text-center">
                    @if($item->is_reconciled)
                      <span class="bp-badge bp-badge-success">Matched</span>
                    @else
                      <span class="bp-badge bp-badge-warning">Pending</span>
                    @endif
                  </td>
                </tr>
              @empty
                <x-core::table.empty colspan="6" icon="fa-solid fa-book-open" title="No book transactions found for the selected period." />
              @endforelse
            </tbody>
            <tfoot>
              <tr class="bp-table-totals-row">
                <td colspan="4" class="text-end fw-800">Book Balance:</td>
                <td class="text-end fw-800">{{ money($summary['book_balance'] ?? 0) }}</td>
                <td></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Bank Statement Side -->
  <div class="col-xl-6">
    <div class="bp-card">
      <div class="bp-card-header d-flex justify-content-between align-items-center">
        <h5 class="bp-card-title"><i class="fa-solid fa-building-columns me-2"></i>Bank Statement{{ $selectedAccount && $selectedAccount->bank_name ? ' (' . $selectedAccount->bank_name . ')' : '' }}</h5>
        @if($statementDate)
          <span class="bp-badge bp-badge-success">Statement Date: {{ \Carbon\Carbon::parse($statementDate)->format('d M Y') }}</span>
        @endif
      </div>
      <div class="bp-card-body p-0">
        <div class="bp-table-wrapper">
          <table class="bp-table" id="bankTransactionsTable">
            <thead>
              <tr>
                <th class="text-center bp-recon-check-col">
                  <input type="checkbox" class="bp-check-all-bank" title="Select All">
                </th>
                <th>Date</th>
                <th>Description</th>
                <th>Reference</th>
                <th class="text-end">Amount ({{ currency_symbol() }})</th>
                <th class="text-center">Status</th>
              </tr>
            </thead>
            <tbody>
              @forelse($statementItems as $item)
                <tr class="{{ $item->is_reconciled ? 'bp-recon-matched' : '' }}">
                  <td class="text-center">
                    <input type="checkbox"
                      class="bp-recon-check bank-check"
                      {{ $item->is_reconciled ? 'checked disabled' : '' }}
                      @if(!$item->is_reconciled)
                        data-amount="{{ abs($item->amount) }}"
                        data-type="{{ $item->amount < 0 ? 'debit' : 'credit' }}"
                        data-item-id="{{ $item->id }}"
                      @endif
                    >
                  </td>
                  <td>{{ $item->transaction_date->format('d M Y') }}</td>
                  <td class="fw-600">{{ $item->description }}</td>
                  <td><code class="fs-12">{{ $item->reference ?? '—' }}</code></td>
                  <td class="text-end fw-700 {{ $item->amount >= 0 ? 'text-success' : 'text-danger' }}">
                    @if($item->amount < 0)
                      ({{ money(abs($item->amount)) }})
                    @else
                      {{ money($item->amount) }}
                    @endif
                  </td>
                  <td class="text-center">
                    @if($item->is_reconciled)
                      <span class="bp-badge bp-badge-success">Matched</span>
                    @else
                      <span class="bp-badge bp-badge-danger">Not in Books</span>
                    @endif
                  </td>
                </tr>
              @empty
                <x-core::table.empty colspan="6" icon="fa-solid fa-building-columns" title="No bank statement entries found. Import a statement or start a reconciliation." />
              @endforelse
            </tbody>
            <tfoot>
              <tr class="bp-table-totals-row">
                <td colspan="4" class="text-end fw-800">Bank Statement Balance:</td>
                <td class="text-end fw-800">{{ money($summary['statement_balance'] ?? 0) }}</td>
                <td></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  </div>

</div>

<!-- Reconciliation Summary -->
<div class="bp-card mt-4">
  <div class="bp-card-header">
    <h5 class="bp-card-title"><i class="fa-solid fa-calculator me-2"></i>Reconciliation Summary</h5>
  </div>
  <div class="bp-card-body p-0">
    <div class="bp-table-wrapper">
      <table class="bp-table">
        <tbody>
          <tr>
            <td class="fw-700 ps-4 bp-recon-summary-label">Book Balance</td>
            <td class="text-end fw-800">{{ money($summary['book_balance'] ?? 0) }}</td>
          </tr>
          <tr class="bp-table-group-header">
            <td colspan="2" class="fw-700 fs-12">Add: Deposits in transit (recorded in books but not yet in bank)</td>
          </tr>
          @php
            $depositsInTransit = $bookItems->where('is_reconciled', false)->where('amount', '>', 0);
            $depositsInTransitTotal = $depositsInTransit->sum('amount');
          @endphp
          @forelse($depositsInTransit as $item)
            <tr>
              <td class="ps-5">{{ $item->description }} ({{ $item->transaction_date->format('d M') }})</td>
              <td class="text-end fw-700 text-success">{{ money($item->amount) }}</td>
            </tr>
          @empty
            <tr>
              <td class="ps-5 text-muted" colspan="2">No deposits in transit.</td>
            </tr>
          @endforelse
          <tr class="bp-table-group-header">
            <td colspan="2" class="fw-700 fs-12">Less: Outstanding cheques (recorded in books but not yet cleared)</td>
          </tr>
          @php
            $outstandingCheques = $bookItems->where('is_reconciled', false)->where('amount', '<', 0);
            $outstandingChequesTotal = $outstandingCheques->sum('amount');
          @endphp
          @forelse($outstandingCheques as $item)
            <tr>
              <td class="ps-5">{{ $item->description }} ({{ $item->transaction_date->format('d M') }})</td>
              <td class="text-end fw-700 text-danger">({{ money(abs($item->amount)) }})</td>
            </tr>
          @empty
            <tr>
              <td class="ps-5 text-muted" colspan="2">No outstanding cheques.</td>
            </tr>
          @endforelse
          @php
            $adjustedBookBalance = ($summary['book_balance'] ?? 0) + $depositsInTransitTotal + $outstandingChequesTotal;
          @endphp
          <tr class="bp-table-subtotal-row">
            <td class="fw-800 ps-4">Adjusted Book Balance</td>
            <td class="text-end fw-800">{{ money($adjustedBookBalance) }}</td>
          </tr>
          <tr>
            <td colspan="2">&nbsp;</td>
          </tr>
          <tr>
            <td class="fw-700 ps-4">Bank Statement Balance</td>
            <td class="text-end fw-800">{{ money($summary['statement_balance'] ?? 0) }}</td>
          </tr>
          <tr class="bp-table-group-header">
            <td colspan="2" class="fw-700 fs-12">Less: Items in bank but not in books</td>
          </tr>
          @php
            $unreconciledBankItems = $statementItems->where('is_reconciled', false);
            $unreconciledBankTotal = $unreconciledBankItems->sum('amount');
          @endphp
          @forelse($unreconciledBankItems as $item)
            <tr>
              <td class="ps-5">{{ $item->description }}</td>
              <td class="text-end fw-700 {{ $item->amount >= 0 ? 'text-success' : 'text-danger' }}">
                @if($item->amount < 0)
                  ({{ money(abs($item->amount)) }})
                @else
                  {{ money($item->amount) }}
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td class="ps-5 text-muted" colspan="2">No unreconciled bank items.</td>
            </tr>
          @endforelse
          @php
            $adjustedBankBalance = ($summary['statement_balance'] ?? 0) + $unreconciledBankTotal;
          @endphp
          <tr class="bp-table-subtotal-row">
            <td class="fw-800 ps-4">Adjusted Bank Balance</td>
            <td class="text-end fw-800">{{ money($adjustedBankBalance) }}</td>
          </tr>
          @php
            $unreconciledDifference = abs($adjustedBookBalance - $adjustedBankBalance);
          @endphp
          <tr class="bp-table-highlight-row">
            <td class="fw-800 fs-14"><i class="fa-solid fa-triangle-exclamation me-2"></i>Unreconciled Difference</td>
            <td class="text-end fw-800 fs-14" id="reconDifference">{{ money($unreconciledDifference) }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
  <div class="bp-card-footer d-flex justify-content-between align-items-center">
    <div>
      <span class="text-muted fs-12">Select matching items and click "Reconcile" to match transactions.</span>
    </div>
    <div class="d-flex gap-2">
      @bpCan('accounting.create')
      <button class="bp-btn bp-btn-outline" id="btnCreateAdjustment"><i class="fa-solid fa-plus me-1"></i> Create Journal Entry for Adjustments</button>
      @endbpCan
      @bpCan('accounting.edit')
      <button class="bp-btn bp-btn-success" id="btnReconcile"><i class="fa-solid fa-check me-1"></i> Reconcile Selected</button>
      @endbpCan
    </div>
  </div>
</div>
@else
<!-- No account selected yet — show prompt -->
<div class="bp-card">
  <div class="bp-card-body text-center py-5">
    <i class="fa-solid fa-building-columns fa-3x text-muted mb-3"></i>
    <h5 class="fw-700 mb-2">Select a Bank Account to Begin</h5>
    <p class="text-muted">Choose a bank account and statement date above, then click "Load" to start the reconciliation process.</p>
  </div>
</div>
@endif

@endsection

@push('scripts')
<script>
'use strict';

$(function () {
    // Select all book transactions
    $('.bp-check-all-book').on('change', function () {
        var isChecked = $(this).is(':checked');
        $('.book-check:not(:disabled)').prop('checked', isChecked);
        updateReconciliation();
    });

    // Select all bank transactions
    $('.bp-check-all-bank').on('change', function () {
        var isChecked = $(this).is(':checked');
        $('.bank-check:not(:disabled)').prop('checked', isChecked);
        updateReconciliation();
    });

    // Individual checkbox change
    $(document).on('change', '.bp-recon-check:not(:disabled)', function () {
        updateReconciliation();
    });

    function updateReconciliation() {
        var bookSelected = 0;
        var bankSelected = 0;

        $('.book-check:checked:not(:disabled)').each(function () {
            var amount = parseFloat($(this).data('amount')) || 0;
            var type = $(this).data('type');
            if (type === 'debit') {
                bookSelected -= amount;
            } else {
                bookSelected += amount;
            }
        });

        $('.bank-check:checked:not(:disabled)').each(function () {
            var amount = parseFloat($(this).data('amount')) || 0;
            var type = $(this).data('type');
            if (type === 'debit') {
                bankSelected -= amount;
            } else {
                bankSelected += amount;
            }
        });

        // Update reconciliation difference display
        var selectedCount = $('.bp-recon-check:checked:not(:disabled)').length;
        if (selectedCount > 0) {
            $('#btnReconcile').prop('disabled', false);
        } else {
            $('#btnReconcile').prop('disabled', true);
        }
    }

    // Reconcile button
    $('#btnReconcile').on('click', function () {
        var selectedItems = $('.bp-recon-check:checked:not(:disabled)').length;
        if (selectedItems === 0) {
            alert('Please select items to reconcile.');
            return;
        }
        if (confirm('Are you sure you want to reconcile ' + selectedItems + ' selected transaction(s)?')) {
            alert('Reconciliation saved successfully. In production, this will update the database.');
        }
    });

    // Create adjustment journal entry
    $('#btnCreateAdjustment').on('click', function () {
        window.location.href = '{{ route("accounting.journal-entries.create") }}';
    });

    // Print
    $('#btnPrint').on('click', function () {
        window.print();
    });

    // Export PDF
    $('#btnExportPdf').on('click', function () {
        var params = $('#reconFilterForm').serialize();
        window.location.href = '{{ route("accounting.bank-reconciliation") }}?' + params + '&export=pdf';
    });

    // Initial state
    updateReconciliation();
});
</script>
@endpush
