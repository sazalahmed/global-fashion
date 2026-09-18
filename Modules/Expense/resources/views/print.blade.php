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
  <title>Expense {{ $expense->expense_number }} — {{ $bizName }}</title>
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
      display: inline-block; border: 2px solid #E67E22; border-radius: 4px;
      padding: 1px 14px; font-size: 13px; font-weight: 700; color: #E67E22;
      margin-top: 14px; margin-bottom: 8px;
    }

    .inv-status {
      display: inline-block; padding: 3px 14px; border-radius: 20px; font-size: 11px;
      font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 6px;
    }
    .inv-status.pending { background: #FDEBD0; color: #E67E22; }
    .inv-status.approved { background: #D6EAF8; color: #2E86C1; }
    .inv-status.paid { background: #D4EFDF; color: #1E8449; }
    .inv-status.rejected { background: #FADBD8; color: #C0392B; }
    .inv-status.cancelled { background: #E5E7E9; color: #566573; }

    .inv-details-card {
      border: 1px solid #e9ecef; border-radius: 6px; padding: 20px; margin-top: 20px; margin-bottom: 20px;
      border-top: 2px solid #E67E22;
    }
    .inv-details-card table { width: 100%; border-collapse: collapse; }
    .inv-details-card table td { padding: 8px 10px; font-size: 13px; border-bottom: 1px solid #e9ecef; vertical-align: middle; }
    .inv-details-card table tr:last-child td { border-bottom: none; }
    .inv-details-card .detail-label { font-weight: 700; color: #555; width: 40%; }
    .inv-details-card .detail-value { font-weight: 600; }
    .inv-details-card .detail-grand { border-top: 2px solid #E67E22; }
    .inv-details-card .detail-grand td { font-weight: 900; font-size: 15px; color: #E67E22; padding-top: 10px; }

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
  <a href="{{ route('expenses.show', $expense) }}"><i class="fa-solid fa-arrow-left"></i> Back</a>
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
    </div>
    <div class="inv-right">
      <div class="inv-label">Expense Voucher</div>
      <div class="inv-prop" style="font-weight:800">{{ $expense->expense_number }}</div>
      <div class="inv-prop">Date: {{ $expense->expense_date->format('d M Y') }}</div>

      <div class="inv-status {{ $expense->status }}">{{ strtoupper($expense->status) }}</div>
    </div>
  </div>

  <div class="inv-billing-badge">Expense Details</div>
  <div class="inv-prop"><span class="key">Category:</span> {{ $expense->category->name ?? '—' }}</div>
  @if($expense->description)
    <div class="inv-prop"><span class="key">Description:</span> {{ $expense->description }}</div>
  @endif
  @if($expense->reference)
    <div class="inv-prop"><span class="key">Reference:</span> {{ $expense->reference }}</div>
  @endif

  <div class="inv-details-card">
    <table>
      <tr>
        <td class="detail-label">Amount</td>
        <td class="detail-value">{{ $currency }} {{ number_format($expense->amount) }}</td>
      </tr>
      @if($expense->tax_amount > 0)
      <tr>
        <td class="detail-label">Tax</td>
        <td class="detail-value">{{ $currency }} {{ number_format($expense->tax_amount) }}</td>
      </tr>
      @endif
      <tr class="detail-grand">
        <td class="detail-label">Total</td>
        <td class="detail-value">{{ $currency }} {{ number_format($expense->total_amount) }}</td>
      </tr>
      @if($expense->paymentAccount)
      <tr>
        <td class="detail-label">Payment Account</td>
        <td class="detail-value">{{ $expense->paymentAccount->name }}</td>
      </tr>
      @endif
      <tr>
        <td class="detail-label">Payment Status</td>
        <td class="detail-value">{{ ucfirst($expense->payment_status) }}</td>
      </tr>
    </table>
  </div>

  @if($expense->description)
  <div class="inv-note"><b>Notes:</b> {{ $expense->description }}</div>
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
