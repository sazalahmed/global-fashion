<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Debit Note {{ $debitNote->dn_number }} — {{ \Modules\Setting\Models\Setting::get('business', 'company_name', 'BizPOS Pro') }}</title>
  <link href="{{ asset('vendor/nunito-sans/nunito-sans.css') }}" rel="stylesheet">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: 'Nunito Sans', sans-serif;
      font-size: 13px;
      color: #1C2833;
      background: #fff;
    }

    /* --- Print Action Bar --- */
    .dn-actions {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      background: #1B4F72;
      color: #fff;
      padding: 12px 24px;
      display: flex;
      justify-content: center;
      gap: 12px;
      z-index: 100;
      box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    }
    .dn-actions button,
    .dn-actions a {
      padding: 8px 20px;
      border: 2px solid #fff;
      border-radius: 6px;
      background: transparent;
      color: #fff;
      font-size: 13px;
      font-weight: 700;
      cursor: pointer;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: all 0.2s;
    }
    .dn-actions button:hover,
    .dn-actions a:hover {
      background: #fff;
      color: #1B4F72;
    }
    .dn-actions .btn-primary-action {
      background: #fff;
      color: #1B4F72;
    }
    .dn-actions .btn-primary-action:hover {
      background: #D4AC0D;
      border-color: #D4AC0D;
      color: #1C2833;
    }

    /* --- Page Layout --- */
    .dn-page {
      max-width: 800px;
      margin: 0 auto;
      padding: 40px;
      padding-top: 100px;
    }

    /* --- Header --- */
    .dn-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 30px;
    }
    .dn-brand h1 {
      font-size: 28px;
      font-weight: 900;
      color: #1B4F72;
      margin-bottom: 4px;
    }
    .dn-brand h1 span { color: #D4AC0D; }
    .dn-brand p {
      font-size: 12px;
      color: #6C757D;
      line-height: 1.6;
    }
    .dn-title-block { text-align: right; }
    .dn-title-block h2 {
      font-size: 32px;
      font-weight: 900;
      color: #1B4F72;
      text-transform: uppercase;
      letter-spacing: 2px;
    }
    .dn-number {
      font-size: 16px;
      font-weight: 700;
      color: #1C2833;
      margin-top: 4px;
    }
    .dn-status {
      display: inline-block;
      padding: 4px 14px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 700;
      margin-top: 8px;
    }
    .dn-status.issued   { background: #D4EFDF; color: #1E8449; }
    .dn-status.draft    { background: #FDEBD0; color: #E67E22; }
    .dn-status.cancelled { background: #FADBD8; color: #C0392B; }

    /* --- Divider --- */
    .dn-divider {
      border: none;
      border-top: 3px solid #1B4F72;
      margin-bottom: 24px;
    }

    /* --- Info Grid --- */
    .dn-info-grid {
      display: flex;
      gap: 24px;
      margin-bottom: 24px;
    }
    .dn-info-box {
      flex: 1;
      background: #F8F9FA;
      border-radius: 8px;
      padding: 16px;
    }
    .dn-info-box h4 {
      font-size: 11px;
      font-weight: 800;
      text-transform: uppercase;
      color: #6C757D;
      margin-bottom: 8px;
      letter-spacing: 0.5px;
    }
    .dn-info-box .name {
      font-size: 15px;
      font-weight: 800;
      color: #1C2833;
      margin-bottom: 4px;
    }
    .dn-info-box .detail {
      font-size: 12px;
      color: #6C757D;
      line-height: 1.6;
    }

    /* --- Dates Row --- */
    .dn-dates {
      display: flex;
      gap: 40px;
      margin-bottom: 24px;
    }
    .dn-dates .date-item label {
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      color: #6C757D;
    }
    .dn-dates .date-item span {
      display: block;
      font-size: 14px;
      font-weight: 700;
      color: #1C2833;
    }

    /* --- Reason Banner --- */
    .dn-reason {
      background: #FDEBD0;
      border-left: 4px solid #E67E22;
      border-radius: 6px;
      padding: 12px 16px;
      margin-bottom: 24px;
    }
    .dn-reason .label {
      font-size: 11px;
      font-weight: 800;
      text-transform: uppercase;
      color: #E67E22;
      margin-bottom: 4px;
    }
    .dn-reason .text {
      font-size: 13px;
      font-weight: 600;
      color: #1C2833;
    }

    /* --- Items Table --- */
    .dn-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 0;
    }
    .dn-table thead th {
      background: #1B4F72;
      color: #fff;
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      padding: 10px 12px;
      letter-spacing: 0.5px;
    }
    .dn-table thead th:first-child { border-radius: 6px 0 0 0; }
    .dn-table thead th:last-child  { border-radius: 0 6px 0 0; }
    .dn-table tbody td {
      padding: 10px 12px;
      border-bottom: 1px solid #E9ECEF;
      font-size: 13px;
      vertical-align: top;
    }
    .dn-table tbody tr:nth-child(even) { background: #F8F9FA; }
    .dn-table .text-end    { text-align: right; }
    .dn-table .text-center { text-align: center; }
    .dn-table .fw-700 { font-weight: 700; }
    .dn-table .fw-800 { font-weight: 800; }
    .dn-table .text-muted { color: #6C757D; }
    .dn-table .product-name { font-weight: 700; }
    .dn-table .product-sku  { font-size: 11px; color: #6C757D; }

    /* --- Totals --- */
    .dn-totals {
      display: flex;
      justify-content: flex-end;
      margin-top: 0;
      margin-bottom: 24px;
      border: 1px solid #E9ECEF;
      border-top: none;
      border-radius: 0 0 6px 6px;
      overflow: hidden;
    }
    .dn-totals-box {
      width: 300px;
      padding: 16px;
    }
    .dn-totals-row {
      display: flex;
      justify-content: space-between;
      padding: 5px 0;
      font-size: 13px;
    }
    .dn-totals-row.subtotal {
      border-bottom: 1px solid #DEE2E6;
      padding-bottom: 8px;
      margin-bottom: 4px;
    }
    .dn-totals-row.grand-total {
      border-top: 3px solid #1B4F72;
      margin-top: 8px;
      padding-top: 10px;
      font-size: 18px;
      font-weight: 900;
      color: #1B4F72;
    }

    /* --- Notes --- */
    .dn-notes {
      margin-bottom: 24px;
    }
    .dn-notes h4 {
      font-size: 11px;
      font-weight: 800;
      text-transform: uppercase;
      color: #6C757D;
      margin-bottom: 6px;
    }
    .dn-notes p {
      font-size: 12px;
      color: #6C757D;
      line-height: 1.6;
    }

    /* --- VAT / Mushak Reference --- */
    .dn-mushak {
      margin-bottom: 24px;
      padding: 12px 16px;
      background: #FEF9E7;
      border: 1px solid #F9E79F;
      border-radius: 6px;
      font-size: 11px;
      color: #7D6608;
    }
    .dn-mushak strong { font-weight: 800; }

    /* --- Signatures --- */
    .dn-signatures {
      display: flex;
      gap: 40px;
      margin-bottom: 24px;
      margin-top: 40px;
    }
    .dn-sig-box {
      flex: 1;
      border-top: 2px solid #1C2833;
      padding-top: 8px;
      font-size: 12px;
      color: #6C757D;
      text-align: center;
    }

    /* --- Footer --- */
    .dn-footer {
      border-top: 2px solid #E9ECEF;
      padding-top: 16px;
      text-align: center;
      color: #ADB5BD;
      font-size: 11px;
      line-height: 1.6;
    }
    .dn-footer strong { color: #6C757D; }

    /* --- Print Media --- */
    @media print {
      @page { margin: 0; }
      body { margin: 15mm 10mm; }
      .dn-actions { display: none !important; }
      .dn-page { max-width: 100%; padding: 20px; padding-top: 20px; }
      .dn-table thead th {
        background: #1B4F72 !important;
        color: #fff !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }
      .dn-table tbody tr:nth-child(even) {
        background: #F8F9FA !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }
      .dn-reason {
        background: #FDEBD0 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }
      .dn-mushak {
        background: #FEF9E7 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }
    }
  </style>
</head>
<body>

<!-- Action Bar (Hidden in Print) -->
@if(!($isPdf ?? false))
<div class="dn-actions">
  <button onclick="window.print()" class="btn-primary-action">
    <i class="fa-solid fa-print"></i> Print Debit Note
  </button>
  <a href="{{ route('accounting.debit-notes.show', $debitNote) }}">
    <i class="fa-solid fa-arrow-left"></i> Back to Debit Note
  </a>
</div>
@endif

@php
  $subtotal   = $debitNote->items->sum(fn($item) => $item->quantity * $item->unit_price);
  $totalTax   = $debitNote->items->sum('tax_amount');
  $grandTotal = $subtotal + $totalTax;

  $statusClass = match($debitNote->status) {
    'issued'    => 'issued',
    'draft'     => 'draft',
    'cancelled' => 'cancelled',
    default     => 'draft',
  };
@endphp

<div class="dn-page">

  <!-- Header -->
  <div class="dn-header">
    <div class="dn-brand">
      <h1>Biz<span>POS</span> Pro</h1>
      <p>
        {{ config('app.business_name', 'BizMart Super Shop') }}<br>
        {{ config('app.business_address', '123/A, Elephant Road, Dhaka-1205') }}<br>
        Phone: {{ config('app.business_phone', '01700-123456') }}<br>
        Email: {{ config('app.business_email', 'info@bizmart.com.bd') }}<br>
        BIN: {{ config('app.business_bin', '001234567-0401') }}
      </p>
    </div>
    <div class="dn-title-block">
      <h2>Debit Note</h2>
      <div class="dn-number">{{ $debitNote->dn_number }}</div>
      <div class="dn-status {{ $statusClass }}">{{ strtoupper($debitNote->status) }}</div>
    </div>
  </div>

  <hr class="dn-divider">

  <!-- Dates Row -->
  <div class="dn-dates">
    <div class="date-item">
      <label>Issue Date</label>
      <span>{{ $debitNote->issue_date->format('d M Y') }}</span>
    </div>
    @if($debitNote->purchase_id)
    <div class="date-item">
      <label>Purchase Ref</label>
      <span>{{ $debitNote->purchase_id }}</span>
    </div>
    @endif
    @if($debitNote->branch)
    <div class="date-item d-none">
      <label>Branch</label>
      <span>{{ $debitNote->branch->name }}</span>
    </div>
    @endif
    <div class="date-item">
      <label>Created By</label>
      <span>{{ $debitNote->creator?->name ?? 'N/A' }}</span>
    </div>
  </div>

  <!-- Supplier Info -->
  <div class="dn-info-grid">
    <div class="dn-info-box">
      <h4>Supplier</h4>
      <div class="name">{{ $debitNote->supplier?->company_name ?? 'N/A' }}</div>
      <div class="detail">
        @if($debitNote->supplier?->phone)Phone: {{ $debitNote->supplier->phone }}<br>@endif
        @if($debitNote->supplier?->email)Email: {{ $debitNote->supplier->email }}<br>@endif
        @if($debitNote->supplier?->address){{ $debitNote->supplier->address }}@endif
      </div>
    </div>
    <div class="dn-info-box">
      <h4>Debit Note Info</h4>
      <div class="detail">
        <strong>DN Number:</strong> {{ $debitNote->dn_number }}<br>
        <strong>Status:</strong> {{ ucfirst($debitNote->status) }}<br>
        <strong>Total Items:</strong> {{ $debitNote->items->count() }}<br>
        <strong>Grand Total:</strong> {{ money($grandTotal) }}
      </div>
    </div>
  </div>

  <!-- Reason -->
  <div class="dn-reason">
    <div class="label">Reason for Debit Note</div>
    <div class="text">{{ $debitNote->reason }}</div>
  </div>

  <!-- Items Table -->
  <table class="dn-table">
    <thead>
      <tr>
        <th style="width: 35px;">#</th>
        <th>Product / Description</th>
        <th class="text-center" style="width: 70px;">Qty</th>
        <th class="text-end" style="width: 130px;">Unit Price</th>
        <th class="text-end" style="width: 100px;">Tax</th>
        <th class="text-end" style="width: 130px;">Line Total</th>
      </tr>
    </thead>
    <tbody>
      @foreach($debitNote->items as $index => $item)
      <tr>
        <td class="text-muted">{{ $index + 1 }}</td>
        <td>
          @if($item->product)
            <div class="product-name">{{ $item->product->name }}</div>
            @if($item->product->sku)
              <div class="product-sku">SKU: {{ $item->product->sku }}</div>
            @endif
          @endif
          @if($item->description)
            <div class="product-sku">{{ $item->description }}</div>
          @endif
        </td>
        <td class="text-center fw-700">{{ num($item->quantity) }}</td>
        <td class="text-end">{{ money($item->unit_price) }}</td>
        <td class="text-end">
          @if($item->tax_amount > 0)
            {{ money($item->tax_amount) }}
          @else
            —
          @endif
        </td>
        <td class="text-end fw-800">
          {{ money(($item->quantity * $item->unit_price) + $item->tax_amount) }}
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>

  <!-- Totals -->
  <div class="dn-totals">
    <div class="dn-totals-box">
      <div class="dn-totals-row subtotal">
        <span>Subtotal (excl. Tax)</span>
        <span class="fw-700">{{ money($subtotal) }}</span>
      </div>
      <div class="dn-totals-row">
        <span>Total Tax</span>
        <span>{{ money($totalTax) }}</span>
      </div>
      <div class="dn-totals-row grand-total">
        <span>Grand Total</span>
        <span>{{ money($grandTotal) }}</span>
      </div>
    </div>
  </div>

  <!-- VAT / Mushak Reference -->
  <div class="dn-mushak">
    <strong>VAT/Mushak Information:</strong>
    This debit note may serve as a Credit Note reference (Mushak 6.5) as per NBR Bangladesh guidelines.
    BIN: {{ config('app.business_bin', '001234567-0401') }} | VAT Registration: Active | Total Tax: {{ money($totalTax) }}
  </div>

  <!-- Notes -->
  @if($debitNote->notes)
  <div class="dn-notes">
    <h4>Notes</h4>
    <p>{{ $debitNote->notes }}</p>
  </div>
  @endif

  <!-- Signatures -->
  <div class="dn-signatures">
    <div class="dn-sig-box">Prepared By</div>
    <div class="dn-sig-box">Authorised By</div>
    <div class="dn-sig-box">Supplier Acknowledgement</div>
  </div>

  <!-- Footer -->
  <div class="dn-footer">
    <strong>{{ config('app.business_name', 'BizMart Super Shop') }}</strong>
    — {{ config('app.business_address', '123/A, Elephant Road, Dhaka-1205') }}<br>
    Phone: {{ config('app.business_phone', '01700-123456') }}
    | Email: {{ config('app.business_email', 'info@bizmart.com.bd') }}<br>
    BIN: {{ config('app.business_bin', '001234567-0401') }}
    | Generated: {{ now()->format('d M Y, h:i A') }}
    <br><br>
    <em>This is a computer-generated debit note. No signature is required for electronic copies.</em>
  </div>

</div>

<!-- FontAwesome for action bar icons -->
<link href="{{ asset('vendor/fontawesome/css/all.min.css') }}" rel="stylesheet">

@if(!($isPdf ?? false))
@if(!isset($autoPrint) || $autoPrint !== false)
<script>
  'use strict';
  window.onload = function () { window.print(); };
</script>
@endif
@endif

</body>
</html>
