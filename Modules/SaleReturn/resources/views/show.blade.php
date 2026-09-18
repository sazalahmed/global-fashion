@extends('core::layouts.master')

@section('title', 'Sale Return Detail — ' . $return->return_number)
@section('page-title', $return->return_number)

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('sale-returns.index') }}">Sale Returns</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>{{ $return->return_number }}</span>
@endsection

@section('page-actions')
@bpCan('sales.edit')
@if($return->isEditable())
  <a href="{{ route('sale-returns.edit', $return) }}" class="bp-btn bp-btn-outline"><i class="fa-solid fa-pen me-1"></i> Edit</a>
@endif
@endbpCan
<a href="{{ route('sale-returns.print', $return) }}" class="bp-btn bp-btn-outline" target="_blank"><i class="fa-solid fa-print me-1"></i> Print</a>
<a href="{{ route('sale-returns.pdf', $return) }}" class="bp-btn bp-btn-outline"><i class="fa-solid fa-download me-1"></i> PDF</a>
<a href="{{ route('sale-returns.index') }}" class="bp-btn bp-btn-primary"><i class="fa-solid fa-arrow-left me-1"></i> Back</a>
@endsection

@section('content')

<!-- Return Hero Banner -->
<div class="bp-invoice-hero mb-0">
  <div class="row align-items-center">
    <div class="col-md-7">
      <div class="inv-number">{{ $return->return_number }}</div>
      <div class="inv-meta">
        <span class="me-3"><i class="fa-solid fa-calendar me-1"></i> {{ $return->return_date->format('d M Y') }}</span>
        @if($return->sale)
          <span class="me-3"><i class="fa-solid fa-file-invoice me-1"></i> Original: <a href="#" class="text-white text-decoration-underline">{{ $return->sale->invoice_number }}</a></span>
        @endif
        @if($return->branch)
          <span><i class="fa-solid fa-code-branch me-1"></i> {{ $return->branch->name }}</span>
        @endif
      </div>
      <div class="d-flex gap-2 mt-3">
        @switch($return->status)
          @case('draft')
            <span class="bp-badge bp-badge-warning bp-badge-hero"><i class="fa-solid fa-clock me-1"></i> Draft</span>
            @break
          @case('approved')
            <span class="bp-badge bp-badge-info bp-badge-hero"><i class="fa-solid fa-check me-1"></i> Approved</span>
            @break
          @case('completed')
            <span class="bp-badge bp-badge-success bp-badge-hero"><i class="fa-solid fa-check-double me-1"></i> Completed</span>
            @break
          @case('cancelled')
            <span class="bp-badge bp-badge-danger bp-badge-hero"><i class="fa-solid fa-ban me-1"></i> Cancelled</span>
            @break
        @endswitch
        @if($return->reason)
          <span class="bp-badge bp-badge-danger bp-badge-hero"><i class="fa-solid fa-circle-exclamation me-1"></i> {{ ucfirst(str_replace('_', ' ', $return->reason)) }}</span>
        @endif
      </div>
    </div>
    <div class="col-md-5 mt-3 mt-md-0">
      <div class="bp-invoice-hero-stat">
        <div class="stat-label">Return Amount</div>
        <div class="stat-value">{{ currency_symbol() }} {{ number_format($return->total_amount, 0) }}</div>
        @if($return->status === 'draft')
          <div class="stat-sub"><i class="fa-solid fa-clock me-1"></i> Refund pending approval</div>
        @elseif($return->status === 'approved')
          <div class="stat-sub"><i class="fa-solid fa-check me-1"></i> Approved, awaiting completion</div>
        @elseif($return->status === 'completed')
          <div class="stat-sub"><i class="fa-solid fa-check-double me-1"></i> Refund processed</div>
        @elseif($return->status === 'cancelled')
          <div class="stat-sub"><i class="fa-solid fa-ban me-1"></i> Return cancelled</div>
        @endif
      </div>
    </div>
  </div>
</div>

<!-- Main Content Row -->
<div class="row g-4 mt-0">

  <!-- Left Column -->
  <div class="col-xl-8">

    <!-- Customer & Original Invoice -->
    <div class="row g-3 mb-4">
      <div class="col-md-6">
        <div class="bp-card h-100">
          <div class="bp-card-header">
            <h5 class="bp-card-title"><i class="fa-solid fa-user me-2 text-primary"></i>Customer</h5>
          </div>
          <div class="bp-card-body">
            @if($return->customer)
              <div class="fw-800 fs-14 mb-1">{{ $return->customer->name }}</div>
              @if($return->customer->phone)
                <div class="fs-13 text-muted mb-1"><i class="fa-solid fa-phone fa-sm me-1"></i> {{ $return->customer->phone }}</div>
              @endif
              @if($return->customer->email)
                <div class="fs-13 text-muted mb-1"><i class="fa-solid fa-envelope fa-sm me-1"></i> {{ $return->customer->email }}</div>
              @endif
              @if($return->customer->address)
                <div class="fs-13 text-muted"><i class="fa-solid fa-location-dot fa-sm me-1"></i> {{ $return->customer->address }}</div>
              @endif
            @else
              <div class="fw-700">{{ $return->customer_display_name }}</div>
            @endif
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="bp-card h-100">
          <div class="bp-card-header">
            <h5 class="bp-card-title"><i class="fa-solid fa-file-invoice me-2 text-info"></i>Original Invoice</h5>
          </div>
          <div class="bp-card-body">
            @if($return->sale)
              <div class="bp-info-row bp-info-row-compact">
                <div class="bp-info-label bp-info-label-md">Invoice #</div>
                <div class="bp-info-value fw-700">{{ $return->sale->invoice_number }}</div>
              </div>
              <div class="bp-info-row bp-info-row-compact">
                <div class="bp-info-label bp-info-label-md">Invoice Date</div>
                <div class="bp-info-value">{{ $return->sale->created_at ? $return->sale->created_at->format('d M Y') : '--' }}</div>
              </div>
              <div class="bp-info-row bp-info-row-compact">
                <div class="bp-info-label bp-info-label-md">Invoice Total</div>
                <div class="bp-info-value fw-700">{{ currency_symbol() }} {{ number_format($return->sale->grand_total, 0) }}</div>
              </div>
              <div class="bp-info-row bp-info-row-compact bp-info-row-last">
                <div class="bp-info-label bp-info-label-md">Payment</div>
                <div class="bp-info-value">
                  <span class="bp-badge bp-badge-success">{{ ucfirst($return->sale->payment_status ?? 'N/A') }}</span>
                </div>
              </div>
            @else
              <div class="text-muted">Invoice not available</div>
            @endif
          </div>
        </div>
      </div>
    </div>

    <!-- Returned Items Table -->
    <x-core::table class="mb-4">
      <x-slot:filters>
        <h5 class="bp-card-title"><i class="fa-solid fa-list me-2 text-primary"></i>Returned Items</h5>
        <span class="bp-badge bp-badge-info">{{ $return->items->count() }} {{ $return->items->count() === 1 ? 'Item' : 'Items' }}</span>
      </x-slot:filters>

      <x-core::table.header>
        <x-core::table.column>#</x-core::table.column>
        <x-core::table.column>Product</x-core::table.column>
        <x-core::table.column align="center">Return Qty</x-core::table.column>
        <x-core::table.column align="end">Unit Price</x-core::table.column>
        <x-core::table.column align="end">Tax</x-core::table.column>
        <x-core::table.column>Condition</x-core::table.column>
        <x-core::table.column align="end">Subtotal</x-core::table.column>
      </x-core::table.header>

      <tbody>
        @forelse($return->items as $index => $item)
          <tr>
            <td class="text-muted">{{ $index + 1 }}</td>
            <td>
              <div class="fw-700">{{ $item->product->name ?? 'Unknown Product' }}</div>
              @if($item->variant)
                <div class="fs-11 text-muted">{{ $item->variant->variant_name }}</div>
              @endif
              @if($item->variant && $item->variant->sku)
                <code class="fs-11">{{ $item->variant->sku }}</code>
              @elseif($item->product && $item->product->sku)
                <code class="fs-11">{{ $item->product->sku }}</code>
              @endif
            </td>
            <td class="text-center fw-700">{{ $item->quantity }}</td>
            <td class="text-end">{{ currency_symbol() }} {{ number_format($item->unit_price, 0) }}</td>
            <td class="text-end">{{ currency_symbol() }} {{ number_format($item->tax_amount, 0) }}</td>
            <td>
              @switch($item->condition)
                @case('good')
                  <span class="bp-badge bp-badge-success">Good</span>
                  @break
                @case('damaged')
                  <span class="bp-badge bp-badge-danger">Damaged</span>
                  @break
                @case('defective')
                  <span class="bp-badge bp-badge-danger">Defective</span>
                  @break
                @case('opened')
                  <span class="bp-badge bp-badge-warning">Opened/Used</span>
                  @break
                @default
                  <span class="bp-badge bp-badge-dark">{{ ucfirst($item->condition ?? '--') }}</span>
              @endswitch
            </td>
            <td class="text-end fw-800">{{ currency_symbol() }} {{ number_format($item->subtotal, 0) }}</td>
          </tr>
        @empty
          <x-core::table.empty colspan="7" icon="fa-solid fa-box-open" title="No items found." />
        @endforelse
      </tbody>

      <!-- Totals Summary -->
      <x-slot:pagination>
        <div class="bp-card-footer">
          <div class="row justify-content-end">
            <div class="col-md-5">
              <div class="bp-cart-summary-row">
                <span>Return Subtotal</span>
                <span class="fw-700">{{ currency_symbol() }} {{ number_format($return->subtotal, 0) }}</span>
              </div>
              <div class="bp-cart-summary-row">
                <span>VAT / Tax</span>
                <span>{{ currency_symbol() }} {{ number_format($return->tax_amount, 0) }}</span>
              </div>
              <div class="bp-cart-summary-row total">
                <span>Total Refund</span>
                <span>{{ currency_symbol() }} {{ number_format($return->total_amount, 0) }}</span>
              </div>
              <div class="bp-cart-summary-row">
                <span>Refund Method</span>
                <span class="fw-600">{{ ucfirst(str_replace('_', ' ', $return->refund_method ?? '--')) }}</span>
              </div>
              <div class="bp-cart-summary-row">
                <span>Refund Status</span>
                <span>
                  @switch($return->status)
                    @case('draft')
                      <span class="bp-badge bp-badge-warning">Pending</span>
                      @break
                    @case('approved')
                      <span class="bp-badge bp-badge-info">Approved</span>
                      @break
                    @case('completed')
                      <span class="bp-badge bp-badge-success">Refunded</span>
                      @break
                    @case('cancelled')
                      <span class="bp-badge bp-badge-danger">Cancelled</span>
                      @break
                  @endswitch
                </span>
              </div>
            </div>
          </div>
        </div>
      </x-slot:pagination>
    </x-core::table>

    <!-- Notes -->
    @if($return->notes || $return->condition_notes)
      <div class="bp-card mb-4">
        <div class="bp-card-header">
          <h5 class="bp-card-title"><i class="fa-solid fa-note-sticky me-2 text-muted"></i>Return Notes</h5>
        </div>
        <div class="bp-card-body">
          <div class="row g-3">
            @if($return->notes)
              <div class="{{ $return->condition_notes ? 'col-md-6' : 'col-12' }}">
                <label class="bp-form-label">Return Notes</label>
                <div class="bp-form-control bp-form-control-static">
                  {{ $return->notes }}
                </div>
              </div>
            @endif
            @if($return->condition_notes)
              <div class="{{ $return->notes ? 'col-md-6' : 'col-12' }}">
                <label class="bp-form-label">Condition of Items</label>
                <div class="bp-form-control bp-form-control-static">
                  {{ $return->condition_notes }}
                </div>
              </div>
            @endif
          </div>
        </div>
      </div>
    @endif

  </div>

  <!-- Right Column -->
  <div class="col-xl-4">

    <!-- Return Info -->
    <div class="bp-card mb-4">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-undo me-2 text-primary"></i>Return Details</h5>
      </div>
      <div class="bp-card-body">
        <div class="bp-info-row">
          <div class="bp-info-label bp-info-label-lg">Return #</div>
          <div class="bp-info-value fw-700">{{ $return->return_number }}</div>
        </div>
        <div class="bp-info-row">
          <div class="bp-info-label bp-info-label-lg">Return Date</div>
          <div class="bp-info-value">{{ $return->return_date->format('d M Y') }}</div>
        </div>
        <div class="bp-info-row">
          <div class="bp-info-label bp-info-label-lg">Status</div>
          <div class="bp-info-value">
            @switch($return->status)
              @case('draft')
                <span class="bp-badge bp-badge-warning">Draft</span>
                @break
              @case('approved')
                <span class="bp-badge bp-badge-info">Approved</span>
                @break
              @case('completed')
                <span class="bp-badge bp-badge-success">Completed</span>
                @break
              @case('cancelled')
                <span class="bp-badge bp-badge-danger">Cancelled</span>
                @break
            @endswitch
          </div>
        </div>
        <div class="bp-info-row">
          <div class="bp-info-label bp-info-label-lg">Reason</div>
          <div class="bp-info-value">{{ ucfirst(str_replace('_', ' ', $return->reason ?? '--')) }}</div>
        </div>
        <div class="bp-info-row">
          <div class="bp-info-label bp-info-label-lg">Refund Method</div>
          <div class="bp-info-value fw-600">{{ ucfirst(str_replace('_', ' ', $return->refund_method ?? '--')) }}</div>
        </div>
        <div class="bp-info-row">
          <div class="bp-info-label bp-info-label-lg">Processed By</div>
          <div class="bp-info-value fw-600">{{ $return->creator->name ?? '--' }}</div>
        </div>
        @if($return->branch)
          <div class="bp-info-row">
            <div class="bp-info-label bp-info-label-lg">Branch</div>
            <div class="bp-info-value">{{ $return->branch->name }}</div>
          </div>
        @endif
        @if($return->journalEntry)
          <div class="bp-info-row">
            <div class="bp-info-label bp-info-label-lg">Journal Entry</div>
            <div class="bp-info-value"><a href="#">{{ $return->journalEntry->entry_number ?? 'View' }}</a></div>
          </div>
        @endif
        @if($return->creditNote)
          <div class="bp-info-row bp-info-row-last">
            <div class="bp-info-label bp-info-label-lg">Credit Note</div>
            <div class="bp-info-value"><a href="#">{{ $return->creditNote->credit_note_number ?? 'View' }}</a></div>
          </div>
        @endif
      </div>
    </div>

    <!-- Action Buttons -->
    @if($return->status === 'draft' || $return->status === 'approved')
      <div class="d-flex flex-column gap-2 mb-4">
          @if($return->isEditable())
            <form action="{{ route('sale-returns.approve', $return) }}" method="POST">
              @csrf
              <button type="submit" class="bp-btn bp-btn-success w-100 justify-content-center"><i class="fa-solid fa-check me-2"></i> Approve Return</button>
            </form>
          @endif
          @if($return->isCompletable())
            <form action="{{ route('sale-returns.complete', $return) }}" method="POST">
              @csrf
              <button type="submit" class="bp-btn bp-btn-primary w-100 justify-content-center"><i class="fa-solid fa-check-double me-2"></i> Complete & Process Refund</button>
            </form>
          @endif
          @if($return->status === 'draft' || $return->status === 'approved')
            <form action="{{ route('sale-returns.cancel', $return) }}" method="POST">
              @csrf
              <button type="submit" class="bp-btn bp-btn-danger w-100 justify-content-center"><i class="fa-solid fa-ban me-2"></i> Cancel Return</button>
            </form>
          @endif
          @bpCan('sales.delete')
          @if($return->isEditable())
            <form action="{{ route('sale-returns.destroy', $return) }}" method="POST">
              @csrf
              @method('DELETE')
              <button type="submit" class="bp-btn bp-btn-danger w-100 justify-content-center delete-confirm" data-name="{{ $return->return_number }}"><i class="fa-solid fa-trash me-2"></i> Delete Return</button>
            </form>
          @endif
          @endbpCan
      </div>
    @endif

  </div>
</div>

@endsection

@push('scripts')
<script>
'use strict';

$(function () {
    $('[data-action="print"]').on('click', function () {
        window.print();
    });
});
</script>
@endpush
