<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  @php
    $bizName    = \Modules\Setting\Models\Setting::get('business', 'company_name', 'BizPOS Pro');
    $bizAddress = \Modules\Setting\Models\Setting::get('business', 'address', '');
    $bizPhone   = \Modules\Setting\Models\Setting::get('business', 'company_phone', '');
    $bizEmail   = \Modules\Setting\Models\Setting::get('business', 'email', '');
    $bizBin     = \Modules\Setting\Models\Setting::get('tax', 'bin', '');
    $currency   = \Modules\Setting\Models\Setting::get('localization', 'currency', 'BDT');
    $footerText = \Modules\Setting\Models\Setting::get('invoice', 'footer_text', 'Thank you for your business!');
  @endphp
  <title>Sale Return {{ $return->return_number }} — {{ $bizName }}</title>
  <link href="{{ asset('vendor/nunito-sans/nunito-sans.css') }}" rel="stylesheet">
  <link href="{{ asset('vendor/fontawesome/css/all.min.css') }}" rel="stylesheet">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Nunito Sans', sans-serif; font-size: 13px; color: #232c3d; background: #e9ecef; }

    /* --- Action Bar --- */
    .sr-actions {
      position: fixed; top: 0; left: 0; right: 0; background: #C0392B; color: #fff;
      padding: 10px 24px; display: flex; justify-content: center; gap: 10px; z-index: 100;
      box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    }
    .sr-actions button, .sr-actions a {
      padding: 8px 20px; border: 2px solid #fff; border-radius: 6px; background: transparent;
      color: #fff; font-size: 13px; font-weight: 700; cursor: pointer; text-decoration: none;
      display: inline-flex; align-items: center; gap: 6px; transition: all 0.15s;
    }
    .sr-actions button:hover, .sr-actions a:hover { background: #fff; color: #C0392B; }
    .sr-actions .btn-print { background: #fff; color: #C0392B; }

    /* --- Page --- */
    .sr-page {
      max-width: 800px; margin: 70px auto 40px; padding: 50px;
      background: #fff; box-shadow: 0 0 12px rgba(0,0,0,0.06); border-radius: 6px;
    }

    /* --- Header --- */
    .sr-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; padding-bottom: 16px; border-bottom: 2px solid #C0392B; }
    .sr-biz-name { font-size: 20px; font-weight: 900; color: #1B4F72; margin-bottom: 4px; }
    .sr-biz-info { font-size: 12px; color: #555; line-height: 1.7; }
    .sr-right { text-align: right; }
    .sr-doc-title { font-size: 24px; font-weight: 900; color: #C0392B; letter-spacing: -0.5px; }
    .sr-doc-number { font-size: 14px; font-weight: 800; color: #333; margin-top: 2px; }
    .sr-doc-date { font-size: 12px; color: #666; margin-top: 2px; }

    /* --- Status --- */
    .sr-status {
      display: inline-block; padding: 4px 16px; border-radius: 20px;
      font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.8px; margin-top: 8px;
    }
    .sr-status.draft { background: #FDEBD0; color: #E67E22; }
    .sr-status.approved { background: #D6EAF8; color: #2E86C1; }
    .sr-status.completed { background: #D4EFDF; color: #1E8449; }
    .sr-status.cancelled { background: #E5E7E9; color: #566573; }

    /* --- Info Grid --- */
    .sr-info-grid {
      display: grid; grid-template-columns: 1fr 1fr; gap: 20px;
      margin: 20px 0; padding: 16px 20px;
      background: #F8F9FA; border-radius: 6px; border-left: 4px solid #C0392B;
    }
    .sr-info-block h4 { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #888; letter-spacing: 0.5px; margin-bottom: 6px; }
    .sr-info-block .name { font-size: 14px; font-weight: 800; color: #232c3d; }
    .sr-info-block .detail { font-size: 12px; color: #555; line-height: 1.6; }
    .sr-info-block .detail .label { font-weight: 700; color: #444; }

    /* --- Items Table --- */
    .sr-table { width: 100%; border-collapse: collapse; margin-top: 24px; }
    .sr-table thead { background: #C0392B; }
    .sr-table thead th {
      font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;
      padding: 10px 12px; color: #fff;
    }
    .sr-table tbody td {
      padding: 10px 12px; font-size: 13px; border-bottom: 1px solid #eee; vertical-align: middle;
    }
    .sr-table tbody tr:last-child td { border-bottom: 2px solid #C0392B; }
    .sr-table .text-end { text-align: right; }
    .sr-table .text-center { text-align: center; }
    .sr-table .product-name { font-weight: 700; }
    .sr-table .product-variant { font-size: 11px; color: #888; }
    .sr-table .product-sku { font-size: 11px; color: #2E86C1; font-family: monospace; }
    .sr-table .condition-good { color: #1E8449; font-weight: 700; font-size: 11px; }
    .sr-table .condition-bad { color: #C0392B; font-weight: 700; font-size: 11px; }

    /* --- Summary --- */
    .sr-summary-wrap { display: flex; margin-top: 16px; margin-bottom: 24px; }
    .sr-summary-left { flex: 1; }
    .sr-reason-box {
      background: #FEF5E7; border: 1px solid #F0D9A0; border-radius: 6px;
      padding: 12px 16px; max-width: 320px;
    }
    .sr-reason-box .title { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #D4AC0D; margin-bottom: 4px; }
    .sr-reason-box .text { font-size: 12px; color: #7D6608; }
    .sr-summary-table { margin-left: auto; border-collapse: collapse; min-width: 280px; }
    .sr-summary-table td { padding: 6px 10px; font-size: 13px; }
    .sr-summary-table .label-col { text-align: right; padding-right: 14px; color: #555; }
    .sr-summary-table .value-col { text-align: right; font-weight: 600; min-width: 120px; }
    .sr-summary-table .row-grand { border-top: 2px solid #C0392B; }
    .sr-summary-table .row-grand td { font-weight: 900; font-size: 16px; color: #C0392B; padding-top: 10px; }

    /* --- Notes --- */
    .sr-note { margin-bottom: 16px; font-size: 12px; color: #555; padding: 10px 14px; background: #f8f9fa; border-radius: 4px; border-left: 3px solid #adb5bd; }

    /* --- Signatures --- */
    .sr-signatures { display: flex; justify-content: space-between; margin-top: 80px; }
    .sr-sig { font-weight: 700; font-size: 12px; border-top: 1px dashed #333; padding-top: 8px; min-width: 150px; text-align: center; color: #555; }

    /* --- Footer --- */
    .sr-footer { border-top: 1px solid #e9ecef; padding-top: 16px; margin-top: 30px; text-align: center; font-size: 11px; color: #adb5bd; line-height: 1.7; }
    .sr-footer strong { color: #6c757d; }

    /* --- Watermark --- */
    .sr-watermark {
      position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-35deg);
      font-size: 72px; font-weight: 900; color: rgba(192, 57, 43, 0.06); white-space: nowrap;
      pointer-events: none; z-index: 0;
    }

    /* --- Print --- */
    @media print {
      @page { margin: 0; }
      body { background: #fff; margin: 12mm 10mm; }
      .sr-actions { display: none !important; }
      .sr-page { max-width: 100%; margin: 0; padding: 0; box-shadow: none; border-radius: 0; }
      .sr-signatures { margin-top: 50px; }
      .sr-table thead { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }
  </style>
</head>
<body>

@if(!($isPdf ?? false))
<div class="sr-actions">
  <button onclick="window.print()" class="btn-print"><i class="fa-solid fa-print"></i> Print</button>
  <a href="{{ route('sale-returns.pdf', $return) }}"><i class="fa-solid fa-file-pdf"></i> Download PDF</a>
  <a href="{{ route('sale-returns.show', $return) }}"><i class="fa-solid fa-arrow-left"></i> Back</a>
</div>
@endif

<div class="sr-page" style="position: relative;">

  @if($return->status === 'cancelled')
  <div class="sr-watermark">CANCELLED</div>
  @endif

  <!-- Header -->
  <div class="sr-header">
    <div>
      <div class="sr-biz-name">{{ $bizName }}</div>
      <div class="sr-biz-info">
        @if($bizAddress){{ $bizAddress }}<br>@endif
        @if($bizPhone)Phone: {{ \App\Helpers\PhoneHelper::format($bizPhone) }}@endif
        @if($bizEmail) | {{ $bizEmail }}@endif
        @if($bizBin)<br>BIN: {{ $bizBin }}@endif
      </div>
    </div>
    <div class="sr-right">
      <div class="sr-doc-title">SALE RETURN</div>
      <div class="sr-doc-number">{{ $return->return_number }}</div>
      <div class="sr-doc-date">Date: {{ $return->return_date->format('d M Y') }}</div>
      <div class="sr-status {{ $return->status }}">{{ strtoupper($return->status) }}</div>
    </div>
  </div>

  <!-- Info Grid -->
  <div class="sr-info-grid">
    <div class="sr-info-block">
      <h4>Customer</h4>
      <div class="name">{{ $return->customer_display_name }}</div>
      @if($return->customer?->phone)
        <div class="detail">Phone: {{ \App\Helpers\PhoneHelper::format($return->customer->phone) }}</div>
      @endif
      @if($return->customer?->address)
        <div class="detail">{{ $return->customer->address }}</div>
      @endif
    </div>
    <div class="sr-info-block" style="text-align: right;">
      <h4>Return Details</h4>
      <div class="detail">
        <span class="label">Original Invoice:</span> {{ $return->sale->invoice_number ?? '—' }}<br>
        <span class="label">Refund Method:</span>
        @php
          $refundAccount = \Modules\Payment\Models\PaymentAccount::find($return->refund_method);
        @endphp
        {{ $refundAccount ? $refundAccount->name : ucfirst(str_replace('_', ' ', $return->refund_method)) }}<br>
        <span class="label">Branch:</span> {{ $return->branch->name ?? '—' }}
      </div>
    </div>
  </div>

  <!-- Items Table -->
  <table class="sr-table">
    <thead>
      <tr>
        <th style="width:5%" class="text-center">#</th>
        <th style="width:30%">Product</th>
        <th style="width:12%">SKU</th>
        <th style="width:10%" class="text-center">Condition</th>
        <th style="width:8%" class="text-center">Qty</th>
        <th style="width:14%" class="text-end">Unit Price</th>
        <th style="width:8%" class="text-end">Tax</th>
        <th style="width:14%" class="text-end">Total</th>
      </tr>
    </thead>
    <tbody>
      @foreach($return->items as $index => $item)
      <tr>
        <td class="text-center" style="color:#888">{{ $index + 1 }}</td>
        <td>
          <div class="product-name">{{ $item->product->name ?? 'Unknown' }}</div>
          @if($item->variant)
            <div class="product-variant">{{ $item->variant->variant_name }}</div>
          @endif
        </td>
        <td><span class="product-sku">{{ $item->product->sku ?? '—' }}</span></td>
        <td class="text-center">
          <span class="{{ $item->condition === 'good' ? 'condition-good' : 'condition-bad' }}">
            {{ ucfirst($item->condition ?? 'Good') }}
          </span>
        </td>
        <td class="text-center" style="font-weight:700">{{ $item->quantity }}</td>
        <td class="text-end">{{ $currency }} {{ number_format($item->unit_price) }}</td>
        <td class="text-end">{{ $item->tax_amount > 0 ? $currency . ' ' . number_format($item->tax_amount) : '—' }}</td>
        <td class="text-end" style="font-weight:800">{{ $currency }} {{ number_format($item->subtotal) }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>

  <!-- Summary -->
  <div class="sr-summary-wrap">
    <div class="sr-summary-left">
      @if($return->reason)
      <div class="sr-reason-box">
        <div class="title"><i class="fa-solid fa-exclamation-triangle"></i> Return Reason</div>
        <div class="text">{{ ucfirst(str_replace('_', ' ', $return->reason)) }}</div>
      </div>
      @endif
    </div>
    <table class="sr-summary-table">
      <tr>
        <td class="label-col">Subtotal</td>
        <td class="value-col">{{ $currency }} {{ number_format($return->subtotal) }}</td>
      </tr>
      @if($return->tax_amount > 0)
      <tr>
        <td class="label-col">Tax</td>
        <td class="value-col">{{ $currency }} {{ number_format($return->tax_amount) }}</td>
      </tr>
      @endif
      <tr class="row-grand">
        <td class="label-col">Return Total</td>
        <td class="value-col">{{ $currency }} {{ number_format($return->total_amount) }}</td>
      </tr>
    </table>
  </div>

  @if($return->notes)
  <div class="sr-note"><b>Notes:</b> {{ $return->notes }}</div>
  @endif

  <!-- Signatures -->
  <div class="sr-signatures">
    <div class="sr-sig">Customer Signature</div>
    <div class="sr-sig">Received By</div>
    <div class="sr-sig">Authorised By</div>
  </div>

  <!-- Footer -->
  <div class="sr-footer">
    <strong>{{ $bizName }}</strong>@if($bizAddress) — {{ $bizAddress }}@endif<br>
    @if($bizPhone)Phone: {{ \App\Helpers\PhoneHelper::format($bizPhone) }}@endif
    @if($bizEmail) | Email: {{ $bizEmail }}@endif<br>
    <em>{{ $footerText }}</em>
  </div>

</div>

</body>
</html>
