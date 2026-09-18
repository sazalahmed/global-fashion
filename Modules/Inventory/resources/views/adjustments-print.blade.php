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
    $currency   = \Modules\Setting\Models\Setting::get('localization', 'currency', 'BDT');
    $footerText = \Modules\Setting\Models\Setting::get('invoice', 'footer_text', 'Thank you for your business!');
  @endphp
  <title>Stock Adjustment {{ $adjustment->adjustment_number }} — {{ $bizName }}</title>
  <link href="{{ asset('vendor/nunito-sans/nunito-sans.css') }}" rel="stylesheet">
  <link href="{{ asset('vendor/fontawesome/css/all.min.css') }}" rel="stylesheet">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Nunito Sans', sans-serif; font-size: 13px; color: #232c3d; background: #e9ecef; }

    .inv-actions {
      position: fixed; top: 0; left: 0; right: 0; background: #1B4F72; color: #fff;
      padding: 10px 24px; display: flex; justify-content: center; gap: 10px; z-index: 100;
      box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    }
    .inv-actions button, .inv-actions a {
      padding: 8px 20px; border: 2px solid #fff; border-radius: 6px; background: transparent;
      color: #fff; font-size: 13px; font-weight: 700; cursor: pointer; text-decoration: none;
      display: inline-flex; align-items: center; gap: 6px; transition: all 0.15s;
    }
    .inv-actions button:hover, .inv-actions a:hover { background: #fff; color: #1B4F72; }
    .inv-actions .btn-print { background: #fff; color: #1B4F72; }

    .inv-page {
      position: relative; max-width: 800px; margin: 70px auto 40px; padding: 50px;
      background: #fff; box-shadow: 0 0 8px rgba(0,0,0,0.06); border-radius: 6px;
    }

    .inv-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }
    .inv-biz-name { font-size: 18px; font-weight: 800; color: #1B4F72; margin-bottom: 4px; }
    .inv-prop { font-size: 13px; color: #333; line-height: 1.6; }
    .inv-prop .key { font-weight: 700; }
    .inv-right { text-align: right; }
    .inv-label { font-size: 22px; font-weight: 800; color: #1B4F72; margin-bottom: 2px; }

    .inv-status {
      display: inline-block; padding: 3px 14px; border-radius: 20px; font-size: 11px;
      font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 6px;
    }
    .inv-status.draft { background: #FDEBD0; color: #E67E22; }
    .inv-status.approved { background: #D6EAF8; color: #2E86C1; }
    .inv-status.completed { background: #D4EFDF; color: #1E8449; }
    .inv-status.cancelled { background: #E5E7E9; color: #566573; }
    .inv-status.pending { background: #FDEBD0; color: #E67E22; }

    .inv-table {
      width: 100%; border-collapse: collapse; margin-top: 20px; margin-bottom: 10px;
      border-top: 2px solid #1B4F72;
    }
    .inv-table thead th {
      font-size: 12px; font-weight: 700; text-transform: uppercase; padding: 8px 10px;
      border-bottom: 1px solid #ccc; color: #333;
    }
    .inv-table tbody td {
      padding: 8px 10px; font-size: 13px; border-bottom: 1px solid #e9ecef; vertical-align: middle;
    }
    .inv-table .text-end { text-align: right; }
    .inv-table .text-center { text-align: center; }

    .inv-summary-wrap { display: flex; margin-top: 10px; margin-bottom: 20px; }
    .inv-summary-table { margin-left: auto; border-collapse: collapse; min-width: 280px; }
    .inv-summary-table td { padding: 5px 8px; font-size: 13px; }
    .inv-summary-table .label-col { text-align: right; padding-right: 12px; color: #555; }
    .inv-summary-table .value-col { text-align: right; font-weight: 600; min-width: 110px; }
    .inv-summary-table .row-grand { border-top: 2px solid #1B4F72; }
    .inv-summary-table .row-grand td { font-weight: 900; font-size: 15px; color: #1B4F72; padding-top: 8px; }

    .inv-note { margin-bottom: 16px; font-size: 12px; color: #555; }
    .inv-signatures { display: flex; justify-content: space-between; margin-top: 100px; }
    .inv-sig { font-weight: 700; font-size: 13px; border-top: 1px dashed #333; padding-top: 6px; min-width: 140px; text-align: center; }
    .inv-footer { border-top: 1px solid #e9ecef; padding-top: 14px; margin-top: 30px; text-align: center; font-size: 11px; color: #adb5bd; line-height: 1.6; }
    .inv-footer strong { color: #6c757d; }

    @media print {
      @page { margin: 0; }
      body { margin: 15mm 10mm; }
      body { background: #fff; }
      .inv-actions { display: none !important; }
      .inv-page { max-width: 100%; margin: 0; padding: 20px 30px; box-shadow: none; border-radius: 0; }
      .inv-signatures { margin-top: 60px; }
    }
  </style>
</head>
<body>

@if(!isset($isPdf) || !$isPdf)
<div class="inv-actions">
  <button onclick="window.print()" class="btn-print"><i class="fa-solid fa-print"></i> Print</button>
  <a href="{{ route('inventory.adjustments.show', $adjustment) }}"><i class="fa-solid fa-arrow-left"></i> Back</a>
</div>
@endif

<div class="inv-page">

  <div class="inv-header">
    <div>
      <div class="inv-biz-name">{{ $bizName }}</div>
      <div class="inv-prop">{{ $bizAddress }}</div>
      @if($bizPhone)
        <div class="inv-prop"><span class="key">Mobile:</span> {{ \App\Helpers\PhoneHelper::format($bizPhone) }}</div>
      @endif
      @if($bizEmail)
        <div class="inv-prop"><span class="key">Email:</span> {{ $bizEmail }}</div>
      @endif
      <div class="inv-prop"><span class="key">Type:</span> {{ ucfirst($adjustment->type) }}</div>
      <div class="inv-prop"><span class="key">Reason:</span> {{ ucfirst(str_replace('_', ' ', $adjustment->reason)) }}</div>
      @if($adjustment->reference)
        <div class="inv-prop"><span class="key">Reference:</span> {{ $adjustment->reference }}</div>
      @endif
    </div>
    <div class="inv-right">
      <div class="inv-label">Stock Adjustment</div>
      <div class="inv-prop" style="font-weight:800">{{ $adjustment->adjustment_number }}</div>
      <div class="inv-prop">Date: {{ $adjustment->created_at->format('d M Y') }}</div>

      <div class="inv-status {{ $adjustment->status }}">{{ strtoupper($adjustment->status) }}</div>
    </div>
  </div>

  <table class="inv-table">
    <thead>
      <tr>
        <th style="width:5%" class="text-center">SL.</th>
        <th style="width:30%">Product</th>
        <th style="width:12%" class="text-center">Quantity</th>
        <th style="width:15%" class="text-end">Unit Cost</th>
        <th style="width:20%">Note</th>
        <th style="width:18%" class="text-end">Total Value</th>
      </tr>
    </thead>
    <tbody>
      @php $totalValue = 0; @endphp
      @foreach($adjustment->items as $index => $item)
      @php $rowTotal = $item->quantity * $item->unit_cost; $totalValue += $rowTotal; @endphp
      <tr>
        <td class="text-center" style="color:#888">{{ $index + 1 }}</td>
        <td>
          <span style="font-weight:700">{{ $item->product->name ?? 'Unknown' }}</span>
          @if($item->variant)
            <br><span style="font-size:11px; color:#888">{{ $item->variant->variant_name }}</span>
          @endif
        </td>
        <td class="text-center" style="font-weight:700">{{ $item->quantity }}</td>
        <td class="text-end">{{ $currency }} {{ number_format($item->unit_cost) }}</td>
        <td style="font-size:11px; color:#555">{{ $item->note ?? '—' }}</td>
        <td class="text-end" style="font-weight:700">{{ $currency }} {{ number_format($rowTotal) }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>

  <div class="inv-summary-wrap">
    <div style="flex:1"></div>
    <table class="inv-summary-table">
      <tr>
        <td class="label-col"><b>Total Items :</b></td>
        <td class="value-col"><b>{{ $adjustment->items->count() }}</b></td>
      </tr>
      <tr class="row-grand">
        <td class="label-col">Total Value :</td>
        <td class="value-col">{{ $currency }} {{ number_format($totalValue) }}</td>
      </tr>
    </table>
  </div>

  @if($adjustment->notes)
  <div class="inv-note"><b>Notes:</b> {{ $adjustment->notes }}</div>
  @endif

  <div class="inv-signatures">
    <div class="inv-sig">Prepared By</div>
    <div></div>
    <div class="inv-sig">Approved By</div>
  </div>

  <div class="inv-footer">
    <strong>{{ $bizName }}</strong>@if($bizAddress) — {{ $bizAddress }}@endif<br>
    @if($bizPhone)Phone: {{ \App\Helpers\PhoneHelper::format($bizPhone) }}@endif
    @if($bizEmail) | Email: {{ $bizEmail }}@endif<br>
    <em>{{ $footerText }}</em>
  </div>

</div>

@if(!isset($isPdf) || !$isPdf)
<script>
  'use strict';
  window.onload = function () { window.print(); };
</script>
@endif

</body>
</html>
