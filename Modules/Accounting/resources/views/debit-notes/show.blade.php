@extends('core::layouts.master')

@section('title', 'Debit Note — ' . $debitNote->dn_number)
@section('page-title', $debitNote->dn_number)

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('accounting.chart-of-accounts') }}">Accounting</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('accounting.debit-notes.index') }}">Debit Notes</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>{{ $debitNote->dn_number }}</span>
@endsection

@section('page-actions')
@if($debitNote->status === 'issued')
  <a href="{{ route('accounting.debit-notes.print', $debitNote) }}" class="bp-btn bp-btn-outline" target="_blank">
    <i class="fa-solid fa-print me-1"></i> Print
  </a>
  <a href="{{ route('accounting.debit-notes.pdf', $debitNote) }}" class="bp-btn bp-btn-outline">
    <i class="fa-solid fa-download me-1"></i> PDF
  </a>
@endif
<a href="{{ route('accounting.debit-notes.index') }}" class="bp-btn bp-btn-primary">
  <i class="fa-solid fa-arrow-left me-1"></i> Back
</a>
@endsection

@section('content')

@php
  $subtotal  = $debitNote->items->sum(fn($item) => $item->quantity * $item->unit_price);
  $totalTax  = $debitNote->items->sum('tax_amount');
  $grandTotal = $subtotal + $totalTax;

  $statusBadgeClass = match($debitNote->status) {
    'issued'    => 'bp-badge-success',
    'draft'     => 'bp-badge-warning',
    'cancelled' => 'bp-badge-danger',
    default     => 'bp-badge-secondary',
  };
@endphp

<div class="row g-4">

  <!-- Left Column: Debit Note Info & Actions -->
  <div class="col-xl-4">

    <!-- Debit Note Information -->
    <div class="bp-card mb-4">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-circle-info me-2"></i>Debit Note Info</h5>
        <span class="bp-badge {{ $statusBadgeClass }}">{{ ucfirst($debitNote->status) }}</span>
      </div>
      <div class="bp-card-body">
        <div class="bp-info-row">
          <div class="bp-info-label bp-info-label-lg">DN Number</div>
          <div class="bp-info-value fw-800">{{ $debitNote->dn_number }}</div>
        </div>
        <div class="bp-info-row">
          <div class="bp-info-label bp-info-label-lg">Issue Date</div>
          <div class="bp-info-value">{{ $debitNote->issue_date->format('d M Y') }}</div>
        </div>
        <div class="bp-info-row">
          <div class="bp-info-label bp-info-label-lg">Supplier</div>
          <div class="bp-info-value fw-600">{{ $debitNote->supplier?->company_name ?? '—' }}</div>
        </div>
        @if($debitNote->supplier?->phone)
        <div class="bp-info-row">
          <div class="bp-info-label bp-info-label-lg">Supplier Phone</div>
          <div class="bp-info-value">{{ $debitNote->supplier->phone }}</div>
        </div>
        @endif
        <div class="bp-info-row">
          <div class="bp-info-label bp-info-label-lg">Purchase Ref</div>
          <div class="bp-info-value">
            @if($debitNote->purchase_id)
              <code class="fs-12">{{ $debitNote->purchase_id }}</code>
            @else
              <span class="text-muted">—</span>
            @endif
          </div>
        </div>
        <div class="bp-info-row">
          <div class="bp-info-label bp-info-label-lg">Reason</div>
          <div class="bp-info-value">{{ $debitNote->reason ?? '—' }}</div>
        </div>
        <div class="bp-info-row">
          <div class="bp-info-label bp-info-label-lg">Total Amount</div>
          <div class="bp-info-value fw-800">{{ money($grandTotal) }}</div>
        </div>
        @if($debitNote->branch)
        <div class="bp-info-row d-none">
          <div class="bp-info-label bp-info-label-lg">Branch</div>
          <div class="bp-info-value">{{ $debitNote->branch->name }}</div>
        </div>
        @endif
        <div class="bp-info-row">
          <div class="bp-info-label bp-info-label-lg">Created By</div>
          <div class="bp-info-value">{{ $debitNote->creator?->name ?? 'N/A' }}</div>
        </div>
        <div class="bp-info-row bp-info-row-last">
          <div class="bp-info-label bp-info-label-lg">Created At</div>
          <div class="bp-info-value">{{ $debitNote->created_at->format('d M Y, h:i A') }}</div>
        </div>
      </div>
    </div>

    <!-- Notes -->
    @if($debitNote->notes)
    <div class="bp-card mb-4">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-sticky-note me-2"></i>Notes</h5>
      </div>
      <div class="bp-card-body">
        <p class="fs-13 text-muted mb-0">{{ $debitNote->notes }}</p>
      </div>
    </div>
    @endif

    <!-- Actions Card -->
    @if($debitNote->status === 'draft')
    @bpCan('accounting.edit')
    <div class="d-flex flex-column gap-2 mb-4">
        <form method="POST" action="{{ route('accounting.debit-notes.issue', $debitNote) }}">
          @csrf
          <p class="fs-13 text-muted mb-3">Issue this debit note to make it official and generate the journal entry.</p>
          <button type="submit" class="bp-btn bp-btn-success w-100">
            <i class="fa-solid fa-check me-1"></i> Issue Debit Note
          </button>
        </form>
        <a href="{{ route('accounting.debit-notes.show', $debitNote) }}" class="bp-btn bp-btn-outline w-100 justify-content-center">
          <i class="fa-solid fa-pen me-2"></i> Edit Debit Note
        </a>
    </div>
    @endbpCan
    @endif

    @if($debitNote->status === 'issued')
    @bpCan('accounting.edit')
    <div class="bp-card mb-4">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-ban me-2"></i>Cancel Debit Note</h5>
      </div>
      <div class="bp-card-body">
        <form method="POST" action="{{ route('accounting.debit-notes.cancel', $debitNote) }}">
          @csrf
          <div class="mb-3">
            <label class="bp-form-label">Cancellation Reason *</label>
            <input type="text" name="cancel_reason" class="bp-form-control"
                   placeholder="Enter reason for cancellation..." required>
          </div>
          <button type="submit" class="bp-btn bp-btn-danger w-100"
                  onclick="return confirm('Cancel this debit note? This will reverse any associated journal entries.')">
            <i class="fa-solid fa-ban me-1"></i> Cancel Debit Note
          </button>
        </form>
      </div>
    </div>
    @endbpCan
    @endif

    @if($debitNote->status === 'draft')
    @bpCan('accounting.delete')
    <div class="d-flex flex-column gap-2 mb-4">
      <form method="POST" action="{{ route('accounting.debit-notes.destroy', $debitNote) }}"
            onsubmit="return confirm('Permanently delete {{ $debitNote->dn_number }}? This cannot be undone.')">
        @csrf
        @method('DELETE')
        <button type="submit" class="bp-btn bp-btn-danger w-100 justify-content-center">
          <i class="fa-solid fa-trash me-2"></i> Delete Debit Note
        </button>
      </form>
    </div>
    @endbpCan
    @endif

  </div>

  <!-- Right Column: Items & Journal Entry -->
  <div class="col-xl-8">

    <!-- Summary Stats -->
    <div class="row g-3 mb-4">
      <div class="col-sm-4">
        <div class="bp-stat-card">
          <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-boxes-stacked"></i></div>
          <div class="bp-stat-content">
            <div class="bp-stat-label">Total Items</div>
            <div class="bp-stat-value">{{ $debitNote->items->count() }}</div>
          </div>
        </div>
      </div>
      <div class="col-sm-4">
        <div class="bp-stat-card">
          <div class="bp-stat-icon icon-warning"><i class="fa-solid fa-percent"></i></div>
          <div class="bp-stat-content">
            <div class="bp-stat-label">Total Tax</div>
            <div class="bp-stat-value">{{ money($totalTax) }}</div>
          </div>
        </div>
      </div>
      <div class="col-sm-4">
        <div class="bp-stat-card">
          <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-bangladeshi-taka-sign"></i></div>
          <div class="bp-stat-content">
            <div class="bp-stat-label">Grand Total</div>
            <div class="bp-stat-value">{{ money($grandTotal) }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Items Table -->
    <div class="bp-card mb-4">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-list me-2"></i>Debit Note Items</h5>
        <span class="bp-badge bp-badge-info">{{ $debitNote->items->count() }} Items</span>
      </div>
      <div class="bp-card-body p-0">
        <div class="bp-table-wrapper">
          <table class="bp-table">
            <thead>
              <tr>
                <th class="text-center" style="width: 40px;">#</th>
                <th>Product</th>
                <th>Description</th>
                <th class="text-center">Qty</th>
                <th class="text-end">Unit Price</th>
                <th class="text-end">Tax</th>
                <th class="text-end">Line Total</th>
              </tr>
            </thead>
            <tbody>
              @foreach($debitNote->items as $index => $item)
              <tr>
                <td class="text-center fw-600">{{ $index + 1 }}</td>
                <td>
                  @if($item->product)
                    <div class="fw-700">{{ $item->product->name }}</div>
                    @if($item->product->sku)
                      <div class="fs-11 text-muted"><code>{{ $item->product->sku }}</code></div>
                    @endif
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
                <td class="fs-13">{{ $item->description ?? '—' }}</td>
                <td class="text-center fw-700">{{ num($item->quantity) }}</td>
                <td class="text-end">{{ money($item->unit_price) }}</td>
                <td class="text-end">
                  @if($item->tax_amount > 0)
                    {{ money($item->tax_amount) }}
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
                <td class="text-end fw-800">
                  {{ money(($item->quantity * $item->unit_price) + $item->tax_amount) }}
                </td>
              </tr>
              @endforeach
            </tbody>
            <tfoot>
              <tr class="bp-table-totals-row">
                <td colspan="5" class="text-end fw-800">Subtotal:</td>
                <td class="text-end fw-700">{{ money($totalTax) }}</td>
                <td class="text-end fw-800">{{ money($subtotal) }}</td>
              </tr>
              <tr class="bp-table-totals-row">
                <td colspan="6" class="text-end fw-800">Grand Total:</td>
                <td class="text-end fw-800 fs-14">{{ money($grandTotal) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>

    <!-- Journal Entry (conditional) -->
    @if($debitNote->journalEntry)
    <div class="bp-card mb-4">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-book me-2"></i>Journal Entry</h5>
        <a href="{{ route('accounting.journal-entries.show', $debitNote->journalEntry) }}" class="bp-btn bp-btn-sm bp-btn-outline">
          <i class="fa-solid fa-eye me-1"></i> View Full Entry
        </a>
      </div>
      <div class="bp-card-body p-0">
        <div class="bp-table-wrapper">
          <table class="bp-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Account Code</th>
                <th>Account Name</th>
                <th>Description</th>
                <th class="text-end">Debit ({{ currency_symbol() }})</th>
                <th class="text-end">Credit ({{ currency_symbol() }})</th>
              </tr>
            </thead>
            <tbody>
              @foreach($debitNote->journalEntry->lines as $idx => $line)
              <tr>
                <td class="text-center fw-600">{{ $idx + 1 }}</td>
                <td><code class="fs-12">{{ $line->account?->account_code ?? '—' }}</code></td>
                <td class="fw-600">{{ $line->account?->account_name ?? '—' }}</td>
                <td class="fs-13">{{ $line->description ?? '—' }}</td>
                <td class="text-end fw-700">
                  @if($line->debit_amount > 0)
                    {{ money($line->debit_amount) }}
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
                <td class="text-end fw-700">
                  @if($line->credit_amount > 0)
                    {{ money($line->credit_amount) }}
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
              </tr>
              @endforeach
            </tbody>
            <tfoot>
              <tr class="bp-table-totals-row">
                <td colspan="4" class="text-end fw-800">Totals:</td>
                <td class="text-end fw-800">
                  {{ money($debitNote->journalEntry->lines->sum('debit_amount')) }}
                </td>
                <td class="text-end fw-800">
                  {{ money($debitNote->journalEntry->lines->sum('credit_amount')) }}
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
      <div class="bp-card-footer d-flex justify-content-between align-items-center">
        <div class="fs-12 text-muted">
          Entry #: <strong>{{ $debitNote->journalEntry->entry_number }}</strong>
          &middot; Date: {{ $debitNote->journalEntry->entry_date->format('d M Y') }}
          &middot; Status: <span class="bp-badge bp-badge-success">{{ ucfirst($debitNote->journalEntry->status) }}</span>
        </div>
      </div>
    </div>
    @endif

  </div>

</div>

@endsection

@push('scripts')
<script>
'use strict';

$(function () {
    // No interactive JS needed for show view — actions use standard forms
});
</script>
@endpush
