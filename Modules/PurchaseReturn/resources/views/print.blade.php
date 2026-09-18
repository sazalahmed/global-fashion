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
  @endphp
  <title>Purchase Return {{ $return->return_number }} — {{ $bizName }}</title>
  <link href="{{ asset('vendor/nunito-sans/nunito-sans.css') }}" rel="stylesheet">
  <link href="{{ asset('vendor/fontawesome/css/all.min.css') }}" rel="stylesheet">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Nunito Sans', sans-serif; font-size: 13px; color: #232c3d; background: #e9ecef; }

    .inv-actions {
      position: fixed; top: 0; left: 0; right: 0; background: #E67E22; color: #fff;
      padding: 10px 24px; display: flex; justify-content: center; gap: 10px; z-index: 100;
      box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    }
    .inv-actions button, .inv-actions a {
      padding: 8px 20px; border: 2px solid #fff; border-radius: 6px; background: transparent;
      color: #fff; font-size: 13px; font-weight: 700; cursor: pointer; text-decoration: none;
      display: inline-flex; align-items: center; gap: 6px; transition: all 0.15s;
    }
    .inv-actions button:hover, .inv-actions a:hover { background: #fff; color: #E67E22; }
    .inv-actions .btn-print { background: #fff; color: #E67E22; }

    .inv-page {
      position: relative; max-width: 800px; margin: 70px auto 40px; padding: 50px;
      background: #fff; box-shadow: 0 0 8px rgba(0,0,0,0.06); border-radius: 6px;
    }

    .inv-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }
    .inv-biz-name { font-size: 18px; font-weight: 800; color: #1B4F72; margin-bottom: 4px; }
    .inv-prop { font-size: 13px; color: #333; line-height: 1.6; }
    .inv-prop .key { font-weight: 700; }
    .inv-right { text-align: right; }
    .inv-label { font-size: 22px; font-weight: 800; color: #E67E22; margin-bottom: 2px; }

    .inv-billing-badge {
      display: inline-block; border: 2px solid #117A65; border-radius: 4px;
      padding: 1px 14px; font-size: 13px; font-weight: 700; color: #117A65;
      margin-top: 14px; margin-bottom: 8px;
    }

    .inv-status {
      display: inline-block; padding: 3px 14px; border-radius: 20px; font-size: 11px;
      font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 6px;
    }
    .inv-status.draft { background: #FDEBD0; color: #E67E22; }
    .inv-status.completed { background: #D4EFDF; color: #1E8449; }

    .inv-table {
      width: 100%; border-collapse: collapse; margin-top: 20px; margin-bottom: 10px;
      border-top: 2px solid #E67E22;
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

    .inv-summary-table { margin-left: auto; border-collapse: collapse; min-width: 280px; }
    .inv-summary-table td { padding: 5px 8px; font-size: 13px; }
    .inv-summary-table .label-col { text-align: right; padding-right: 12px; color: #555; }
    .inv-summary-table .value-col { text-align: right; font-weight: 600; min-width: 110px; }
    .inv-summary-table .row-grand { border-top: 2px solid #E67E22; }
    .inv-summary-table .row-grand td { font-weight: 900; font-size: 15px; color: #E67E22; padding-top: 8px; }

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

@if(!($isPdf ?? false))
<div class="inv-actions">
  <button onclick="window.print()" class="btn-print"><i class="fa-solid fa-print"></i> Print</button>
  <a href="{{ route('purchase-returns.show', $return) }}"><i class="fa-solid fa-arrow-left"></i> Back</a>
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
      @if($return->purchase)
        <div class="inv-prop"><span class="key">Original PO:</span> {{ $return->purchase->po_number }}</div>
      @endif
    </div>
    <div class="inv-right">
      <div class="inv-label">Purchase Return</div>
      <div class="inv-prop" style="font-weight:800">{{ $return->return_number }}</div>
      <div class="inv-prop">Date: {{ $return->return_date?->format('d M Y') ?? '—' }}</div>

      <div class="inv-status {{ $return->status }}">{{ strtoupper($return->status) }}</div>

      <div class="inv-billing-badge">Supplier</div>
      <div class="inv-prop"><span class="key">Company:</span> {{ $return->supplier->company_name ?? '—' }}</div>
      @if($return->supplier?->contact_person)
        <div class="inv-prop"><span class="key">Contact:</span> {{ $return->supplier->contact_person }}</div>
      @endif
      @if($return->supplier?->phone)
        <div class="inv-prop"><span class="key">Phone:</span> {{ \App\Helpers\PhoneHelper::format($return->supplier->phone) }}</div>
      @endif
    </div>
  </div>

  <table class="inv-table">
    <thead>
      <tr>
        <th style="width:5%" class="text-center">SL.</th>
        <th style="width:35%">Product</th>
        <th style="width:12%" class="text-center">Qty</th>
        <th style="width:15%" class="text-end">Unit Price</th>
        <th style="width:10%" class="text-end">Tax</th>
        <th style="width:15%" class="text-end">Line Total</th>
      </tr>
    </thead>
    <tbody>
      @foreach($return->items as $index => $item)
      <tr>
        <td class="text-center" style="color:#888">{{ $index + 1 }}</td>
        <td>
          <span style="font-weight:700">{{ $item->product->name ?? 'Unknown' }}</span>
          @if($item->variant)
            <span style="font-size:11px; color:#888"> — {{ $item->variant->variant_name ?? '' }}</span>
          @endif
        </td>
        <td class="text-center" style="font-weight:700">{{ $item->quantity }}</td>
        <td class="text-end">{{ $currency }} {{ number_format($item->unit_price) }}</td>
        <td class="text-end">{{ $item->tax_amount > 0 ? $currency . ' ' . number_format($item->tax_amount) : '—' }}</td>
        <td class="text-end" style="font-weight:700">{{ $currency }} {{ number_format($item->line_total) }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>

  <div style="display:flex; margin-top:10px; margin-bottom:20px;">
    <div style="flex:1"></div>
    <table class="inv-summary-table">
      <tr>
        <td class="label-col"><b>Subtotal :</b></td>
        <td class="value-col"><b>{{ $currency }} {{ number_format($return->subtotal) }}</b></td>
      </tr>
      @if($return->tax_amount > 0)
      <tr>
        <td class="label-col">Tax :</td>
        <td class="value-col">{{ $currency }} {{ number_format($return->tax_amount) }}</td>
      </tr>
      @endif
      <tr class="row-grand">
        <td class="label-col">Return Total :</td>
        <td class="value-col">{{ $currency }} {{ number_format($return->total) }}</td>
      </tr>
    </table>
  </div>

  @if($return->reason)
  <div class="inv-note"><b>Reason:</b> {{ $return->reason }}</div>
  @endif
  @if($return->notes)
  <div class="inv-note"><b>Notes:</b> {{ $return->notes }}</div>
  @endif

  <div class="inv-signatures">
    <div class="inv-sig">Supplier Signature</div>
    <div></div>
    <div class="inv-sig">Authorised By</div>
  </div>

  <div class="inv-footer">
    <strong>{{ $bizName }}</strong>@if($bizAddress) — {{ $bizAddress }}@endif<br>
    @if($bizPhone)Phone: {{ \App\Helpers\PhoneHelper::format($bizPhone) }}@endif
    @if($bizEmail) | Email: {{ $bizEmail }}@endif
  </div>

</div>

@if(!($isPdf ?? false))
<script>
  'use strict';
  window.onload = function () { window.print(); };
</script>
@endif

</body>
</html>
