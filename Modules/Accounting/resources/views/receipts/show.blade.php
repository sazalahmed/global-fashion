@extends('core::layouts.master')

@section('title', 'Receipt — ' . $receipt->receipt_number)
@section('page-title', 'Receipt — ' . $receipt->receipt_number)

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('accounting.receipts.index') }}">Receipts</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>{{ $receipt->receipt_number }}</span>
@endsection

@section('page-actions')
<a href="{{ route('accounting.receipts.print', $receipt) }}" class="bp-btn bp-btn-outline" target="_blank">
  <i class="fa-solid fa-print me-1"></i> Print
</a>
<a href="{{ route('accounting.receipts.index') }}" class="bp-btn bp-btn-primary">
  <i class="fa-solid fa-arrow-left me-1"></i> Back
</a>
@endsection

@section('content')

@php
  $typeBadge = $receipt->receipt_type === 'payment_received' ? 'bp-badge-success' : 'bp-badge-danger';
  $typeLabel  = $receipt->receipt_type === 'payment_received' ? 'Payment Received' : 'Payment Made';
  $typeIcon   = $receipt->receipt_type === 'payment_received' ? 'fa-arrow-down-to-bracket' : 'fa-arrow-up-from-bracket';
@endphp

<div class="row g-4">

  <!-- Left Column: Receipt Details -->
  <div class="col-xl-4">

    <!-- Receipt Information -->
    <div class="bp-card mb-4">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-receipt me-2"></i>Receipt Information</h5>
        <span class="bp-badge {{ $typeBadge }}">
          <i class="fa-solid {{ $typeIcon }} me-1"></i>{{ $typeLabel }}
        </span>
      </div>
      <div class="bp-card-body">
        <div class="bp-info-row">
          <div class="bp-info-label bp-info-label-lg">Receipt #</div>
          <div class="bp-info-value fw-800">{{ $receipt->receipt_number }}</div>
        </div>
        <div class="bp-info-row">
          <div class="bp-info-label bp-info-label-lg">Receipt Date</div>
          <div class="bp-info-value">{{ $receipt->receipt_date->format('d M Y') }}</div>
        </div>
        <div class="bp-info-row">
          <div class="bp-info-label bp-info-label-lg">Type</div>
          <div class="bp-info-value">
            <span class="bp-badge {{ $typeBadge }}">{{ $typeLabel }}</span>
          </div>
        </div>
        <div class="bp-info-row">
          <div class="bp-info-label bp-info-label-lg">Party Name</div>
          <div class="bp-info-value fw-600">{{ $receipt->party_name }}</div>
        </div>
        <div class="bp-info-row">
          <div class="bp-info-label bp-info-label-lg">Amount</div>
          <div class="bp-info-value fw-800 fs-15">{{ money($receipt->amount) }}</div>
        </div>
        <div class="bp-info-row">
          <div class="bp-info-label bp-info-label-lg">Payment Method</div>
          <div class="bp-info-value">{{ $receipt->payment_method ?? '—' }}</div>
        </div>
        <div class="bp-info-row bp-info-row-last">
          <div class="bp-info-label bp-info-label-lg">Description</div>
          <div class="bp-info-value">{{ $receipt->description ?? '—' }}</div>
        </div>
      </div>
    </div>

    <!-- Payment Reference -->
    <div class="bp-card mb-4">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-link me-2"></i>Payment Reference</h5>
      </div>
      <div class="bp-card-body">
        @if($receipt->payment)
          <div class="bp-info-row">
            <div class="bp-info-label bp-info-label-lg">Payment #</div>
            <div class="bp-info-value">
              <a href="{{ route('accounting.payments.show', $receipt->payment) }}" class="fw-700">
                {{ $receipt->payment->payment_number ?? '—' }}
              </a>
            </div>
          </div>
          @if($receipt->payment->paymentAccount)
          <div class="bp-info-row">
            <div class="bp-info-label bp-info-label-lg">Account</div>
            <div class="bp-info-value">{{ $receipt->payment->paymentAccount->account_name }}</div>
          </div>
          @endif
          <div class="bp-info-row bp-info-row-last">
            <div class="bp-info-label bp-info-label-lg">Payment Date</div>
            <div class="bp-info-value">{{ $receipt->payment->payment_date?->format('d M Y') ?? '—' }}</div>
          </div>
        @else
          <p class="text-muted fs-13 mb-0">No linked payment record.</p>
        @endif
      </div>
    </div>

    <!-- Branch & Creator -->
    <div class="bp-card">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-circle-info me-2"></i>Meta Information</h5>
      </div>
      <div class="bp-card-body">
        <div class="bp-info-row d-none">
          <div class="bp-info-label bp-info-label-lg">Branch</div>
          <div class="bp-info-value">{{ $receipt->branch?->name ?? '—' }}</div>
        </div>
        <div class="bp-info-row">
          <div class="bp-info-label bp-info-label-lg">Created By</div>
          <div class="bp-info-value">{{ $receipt->creator?->name ?? '—' }}</div>
        </div>
        <div class="bp-info-row bp-info-row-last">
          <div class="bp-info-label bp-info-label-lg">Created At</div>
          <div class="bp-info-value">{{ $receipt->created_at->format('d M Y, h:i A') }}</div>
        </div>
      </div>
    </div>

  </div>

  <!-- Right Column: Receipt Preview -->
  <div class="col-xl-8">

    <!-- Amount Summary -->
    <div class="row g-3 mb-4">
      <div class="col-sm-4">
        <div class="bp-stat-card">
          <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-bangladeshi-taka-sign"></i></div>
          <div class="bp-stat-content">
            <div class="bp-stat-label">Receipt Amount</div>
            <div class="bp-stat-value">{{ money($receipt->amount) }}</div>
          </div>
        </div>
      </div>
      <div class="col-sm-4">
        <div class="bp-stat-card">
          <div class="bp-stat-icon {{ $receipt->receipt_type === 'payment_received' ? 'icon-success' : 'icon-danger' }}">
            <i class="fa-solid {{ $typeIcon }}"></i>
          </div>
          <div class="bp-stat-content">
            <div class="bp-stat-label">Direction</div>
            <div class="bp-stat-value fs-14">{{ $typeLabel }}</div>
          </div>
        </div>
      </div>
      <div class="col-sm-4">
        <div class="bp-stat-card">
          <div class="bp-stat-icon icon-info"><i class="fa-solid fa-calendar-day"></i></div>
          <div class="bp-stat-content">
            <div class="bp-stat-label">Receipt Date</div>
            <div class="bp-stat-value fs-14">{{ $receipt->receipt_date->format('d M Y') }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Receipt Detail Card -->
    <div class="bp-card mb-4">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-file-invoice me-2"></i>Receipt Detail</h5>
        <div class="d-flex gap-2">
          <a href="{{ route('accounting.receipts.print', $receipt) }}" class="bp-btn bp-btn-sm bp-btn-outline" target="_blank">
            <i class="fa-solid fa-print me-1"></i> Print
          </a>
        </div>
      </div>
      <div class="bp-card-body">

        <!-- Receipt Header Block -->
        <div class="bp-receipt-preview-header text-center mb-4">
          <div class="fw-800 fs-18">{{ config('app.name', 'BizPOS Pro') }}</div>
          @if($receipt->branch)
            <div class="fs-13 text-muted">{{ $receipt->branch->address ?? '' }}</div>
          @endif
          <div class="fs-11 text-muted mt-1">Payment Receipt</div>
        </div>

        <!-- Divider -->
        <div class="bp-receipt-divider mb-4"></div>

        <!-- Receipt Fields -->
        <div class="row g-3 mb-4">
          <div class="col-sm-6">
            <div class="bp-info-row">
              <div class="bp-info-label">Receipt Number</div>
              <div class="bp-info-value fw-700">{{ $receipt->receipt_number }}</div>
            </div>
            <div class="bp-info-row">
              <div class="bp-info-label">Date</div>
              <div class="bp-info-value">{{ $receipt->receipt_date->format('d M Y') }}</div>
            </div>
            <div class="bp-info-row">
              <div class="bp-info-label">Type</div>
              <div class="bp-info-value">
                <span class="bp-badge {{ $typeBadge }}">{{ $typeLabel }}</span>
              </div>
            </div>
          </div>
          <div class="col-sm-6">
            <div class="bp-info-row">
              <div class="bp-info-label">Party</div>
              <div class="bp-info-value fw-600">{{ $receipt->party_name }}</div>
            </div>
            <div class="bp-info-row">
              <div class="bp-info-label">Method</div>
              <div class="bp-info-value">{{ $receipt->payment_method ?? '—' }}</div>
            </div>
            @if($receipt->payment)
            <div class="bp-info-row">
              <div class="bp-info-label">Reference</div>
              <div class="bp-info-value">
                <a href="{{ route('accounting.payments.show', $receipt->payment) }}" class="fw-600">
                  {{ $receipt->payment->payment_number }}
                </a>
              </div>
            </div>
            @endif
          </div>
        </div>

        <!-- Amount Block -->
        <div class="bp-receipt-amount-block mb-4">
          <div class="bp-receipt-amount-label">Amount</div>
          <div class="bp-receipt-amount-value">{{ money($receipt->amount) }}</div>
        </div>

        @if($receipt->description)
        <div class="bp-info-row">
          <div class="bp-info-label">Description</div>
          <div class="bp-info-value">{{ $receipt->description }}</div>
        </div>
        @endif

        <!-- Divider -->
        <div class="bp-receipt-divider mt-4 mb-3"></div>

        <!-- Footer info -->
        <div class="d-flex justify-content-between align-items-center fs-12 text-muted">
          <span>Generated by {{ $receipt->creator?->name ?? 'System' }}</span>
          <span>{{ $receipt->created_at->format('d M Y, h:i A') }}</span>
        </div>

      </div>
    </div>

  </div>

</div>

@endsection

@push('scripts')
<script>
'use strict';

$(function () {
    // Print button in page actions already uses target="_blank"
    // No additional JS needed here
});
</script>
@endpush
