<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  @php
    $bizName    = \Modules\Setting\Models\Setting::get('business', 'company_name', 'BizPOS Pro');
    $bizAddress = \Modules\Setting\Models\Setting::get('business', 'address', '');
    $bizPhone   = \Modules\Setting\Models\Setting::get('business', 'company_phone', '');
    $currency   = \Modules\Setting\Models\Setting::get('localization', 'currency', 'BDT');
    $footerText = \Modules\Setting\Models\Setting::get('invoice', 'footer_text', 'Thank you for your business!');
  @endphp
  <title>Receipt {{ $payment->payment_number }} — {{ $bizName }}</title>
  <link href="{{ asset('vendor/nunito-sans/nunito-sans.css') }}" rel="stylesheet">
  <link href="{{ asset('vendor/fontawesome/css/all.min.css') }}" rel="stylesheet">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Nunito Sans', sans-serif; font-size: 13px; color: #232c3d; background: #e9ecef; }
    .rc-actions { position: fixed; top: 0; left: 0; right: 0; background: #1B4F72; color: #fff; padding: 10px 24px; display: flex; justify-content: center; gap: 10px; z-index: 100; }
    .rc-actions button, .rc-actions a { padding: 8px 20px; border: 2px solid #fff; border-radius: 6px; background: transparent; color: #fff; font-size: 13px; font-weight: 700; cursor: pointer; text-decoration: none; }
    .rc-actions .btn-print { background: #fff; color: #1B4F72; }
    .rc-page { max-width: 480px; margin: 70px auto 40px; padding: 40px; background: #fff; box-shadow: 0 0 8px rgba(0,0,0,0.06); border-radius: 6px; border-top: 3px solid #1B4F72; }
    .rc-biz { font-size: 18px; font-weight: 800; color: #1B4F72; text-align: center; }
    .rc-title { text-align: center; font-size: 15px; font-weight: 800; margin: 8px 0 20px; letter-spacing: 1px; text-transform: uppercase; color: #1E8449; }
    table.rc { width: 100%; border-collapse: collapse; }
    table.rc td { padding: 8px 6px; border-bottom: 1px solid #eef1f4; font-size: 13px; }
    table.rc td.k { font-weight: 700; color: #555; width: 45%; }
    .rc-amount { font-size: 20px; font-weight: 900; color: #1E8449; text-align: center; margin: 18px 0; }
    .rc-foot { text-align: center; font-size: 11px; color: #adb5bd; margin-top: 28px; line-height: 1.6; }
    .rc-sign { margin-top: 60px; text-align: right; }
    .rc-sign span { border-top: 1px dashed #333; padding-top: 6px; font-weight: 700; }
    @media print { @page { margin: 0; } body { margin: 15mm; background: #fff; } .rc-actions { display: none !important; } .rc-page { margin: 0 auto; box-shadow: none; } }
  </style>
</head>
<body>

<div class="rc-actions">
  <button onclick="window.print()" class="btn-print"><i class="fa-solid fa-print"></i> Print</button>
  <a href="{{ route('assets.show', $payment->asset) }}"><i class="fa-solid fa-arrow-left"></i> Back</a>
</div>

<div class="rc-page">
  <div class="rc-biz">{{ $bizName }}</div>
  <div class="rc-title">Payment Receipt</div>

  <div class="rc-amount">{{ $currency }} {{ number_format($payment->amount, 2) }}</div>

  <table class="rc">
    <tr><td class="k">Receipt No</td><td>{{ $payment->payment_number }}</td></tr>
    <tr><td class="k">Date</td><td>{{ $payment->payment_date->format('d M Y') }}</td></tr>
    <tr><td class="k">Asset</td><td>{{ $payment->asset->name }} ({{ $payment->asset->asset_code }})</td></tr>
    @if($payment->asset->vendor_name)<tr><td class="k">Vendor</td><td>{{ $payment->asset->vendor_name }}</td></tr>@endif
    <tr><td class="k">Payment Account</td><td>{{ $payment->paymentAccount->name ?? '—' }}</td></tr>
    @if($payment->reference)<tr><td class="k">Reference</td><td>{{ $payment->reference }}</td></tr>@endif
    @if($payment->note)<tr><td class="k">Note</td><td>{{ $payment->note }}</td></tr>@endif
    <tr><td class="k">Received By</td><td>{{ $payment->creator->name ?? '—' }}</td></tr>
  </table>

  <div class="rc-sign"><span>Authorized Signature</span></div>

  <div class="rc-foot">
    <em>{{ $footerText }}</em>@if($bizPhone)<br>Phone: {{ \App\Helpers\PhoneHelper::format($bizPhone) }}@endif
  </div>
</div>

<script>
  'use strict';
</script>
</body>
</html>
