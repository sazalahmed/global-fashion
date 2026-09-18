<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Receipt {{ $receipt->receipt_number }}</title>
  <style>@media print { @page { margin: 0; } body { margin: 15mm 10mm; } }</style>
  <link href="{{ asset('vendor/nunito-sans/nunito-sans.css') }}" rel="stylesheet">
  <link href="{{ asset('css/receipt-print.css') }}" rel="stylesheet">
</head>
<body class="rp-body">

<div class="rp-wrapper">

  <!-- Company Header -->
  <div class="rp-header">
    <div class="rp-company-name">{{ config('app.name', 'BizPOS Pro') }}</div>
    @if($receipt->branch)
      <div class="rp-company-address d-none">{{ $receipt->branch->address ?? '' }}</div>
      @if($receipt->branch->phone)
        <div class="rp-company-phone d-none">{{ $receipt->branch->phone }}</div>
      @endif
    @endif
    <div class="rp-doc-title">PAYMENT RECEIPT</div>
  </div>

  <!-- Divider -->
  <div class="rp-divider-dashed"></div>

  <!-- Receipt Meta -->
  <div class="rp-row">
    <span class="rp-label">Receipt #</span>
    <span class="rp-value rp-value-bold">{{ $receipt->receipt_number }}</span>
  </div>
  <div class="rp-row">
    <span class="rp-label">Date</span>
    <span class="rp-value">{{ $receipt->receipt_date->format('d M Y') }}</span>
  </div>
  <div class="rp-row">
    <span class="rp-label">Type</span>
    <span class="rp-value">{{ $receipt->receipt_type === 'payment_received' ? 'Payment Received' : 'Payment Made' }}</span>
  </div>

  <!-- Divider -->
  <div class="rp-divider-dashed"></div>

  <!-- Party -->
  <div class="rp-row">
    <span class="rp-label">Party</span>
    <span class="rp-value rp-value-bold">{{ $receipt->party_name }}</span>
  </div>

  @if($receipt->payment)
  <div class="rp-row">
    <span class="rp-label">Reference</span>
    <span class="rp-value">{{ $receipt->payment->payment_number }}</span>
  </div>
  @endif

  <!-- Divider -->
  <div class="rp-divider-dashed"></div>

  <!-- Amount — large, prominent -->
  <div class="rp-amount-block">
    <div class="rp-amount-label">AMOUNT PAID</div>
    <div class="rp-amount-value">{{ money($receipt->amount) }}</div>
  </div>

  <!-- Payment Method -->
  <div class="rp-row">
    <span class="rp-label">Method</span>
    <span class="rp-value">{{ $receipt->payment_method ?? 'N/A' }}</span>
  </div>

  @if($receipt->description)
  <div class="rp-row">
    <span class="rp-label">Note</span>
    <span class="rp-value">{{ $receipt->description }}</span>
  </div>
  @endif

  <!-- Divider -->
  <div class="rp-divider-dashed"></div>

  <!-- Footer -->
  <div class="rp-footer">
    <div class="rp-thank-you">Thank You!</div>
    <div class="rp-footer-meta">Printed: {{ now()->format('d M Y, h:i A') }}</div>
    @if($receipt->creator)
      <div class="rp-footer-meta">By: {{ $receipt->creator->name }}</div>
    @endif
    <div class="rp-footer-brand">{{ \Modules\Setting\Models\Setting::get('business', 'company_name', 'BizPOS Pro') }}</div>
  </div>

</div>

@if(!($isPdf ?? false))
<script>
'use strict';

window.addEventListener('load', function () {
    window.print();
});
</script>
@endif

</body>
</html>
