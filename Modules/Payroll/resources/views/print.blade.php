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
  <title>Payroll {{ $payroll->payroll_number }} — {{ $bizName }}</title>
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
      position: relative; max-width: 1100px; margin: 70px auto 40px; padding: 50px;
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
    .inv-status.paid { background: #D4EFDF; color: #1E8449; }
    .inv-status.cancelled { background: #E5E7E9; color: #566573; }

    .inv-table {
      width: 100%; border-collapse: collapse; margin-top: 20px; margin-bottom: 10px;
      border-top: 2px solid #1B4F72;
    }
    .inv-table thead th {
      font-size: 11px; font-weight: 700; text-transform: uppercase; padding: 8px 6px;
      border-bottom: 1px solid #ccc; color: #333;
    }
    .inv-table tbody td {
      padding: 7px 6px; font-size: 12px; border-bottom: 1px solid #e9ecef; vertical-align: middle;
    }
    .inv-table .text-end { text-align: right; }
    .inv-table .text-center { text-align: center; }

    .inv-item-status {
      display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 10px;
      font-weight: 800; text-transform: uppercase; letter-spacing: 0.3px;
    }
    .inv-item-status.paid { background: #D4EFDF; color: #1E8449; }
    .inv-item-status.unpaid { background: #FADBD8; color: #C0392B; }
    .inv-item-status.partial { background: #FDEBD0; color: #E67E22; }
    .inv-item-status.pending { background: #FDEBD0; color: #E67E22; }

    .inv-summary-wrap { display: flex; margin-top: 10px; margin-bottom: 20px; }
    .inv-summary-table { margin-left: auto; border-collapse: collapse; min-width: 320px; }
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
      @page { margin: 0; size: landscape; }
      body { margin: 10mm; }
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
  <a href="{{ route('payroll.show', $payroll) }}"><i class="fa-solid fa-arrow-left"></i> Back</a>
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
      @if($payroll->branch)
        <div class="inv-prop d-none"><span class="key">Branch:</span> {{ $payroll->branch->name }}</div>
      @endif
      <div class="inv-prop"><span class="key">Month:</span> {{ $payroll->month }}</div>
    </div>
    <div class="inv-right">
      <div class="inv-label">Payroll Sheet</div>
      <div class="inv-prop" style="font-weight:800">{{ $payroll->payroll_number }}</div>

      <div class="inv-status {{ $payroll->status }}">{{ strtoupper($payroll->status) }}</div>
    </div>
  </div>

  @php
    $totalGross = 0;
    $totalDeductions = 0;
    $totalNet = 0;
  @endphp

  <table class="inv-table">
    <thead>
      <tr>
        <th style="width:4%" class="text-center">SL.</th>
        <th style="width:10%">Employee ID</th>
        <th style="width:16%">Employee Name</th>
        <th style="width:10%" class="text-end">Basic</th>
        <th style="width:10%" class="text-end">Earnings</th>
        <th style="width:10%" class="text-end">Deductions</th>
        <th style="width:10%" class="text-end">Advance Ded.</th>
        <th style="width:12%" class="text-end">Net Salary</th>
        <th style="width:8%" class="text-center">Status</th>
      </tr>
    </thead>
    <tbody>
      @foreach($payroll->items as $index => $item)
      @php
        $itemGross = $item->basic_salary + $item->total_earnings;
        $itemDeductions = $item->total_deductions + $item->advance_deduction;
        $totalGross += $itemGross;
        $totalDeductions += $itemDeductions;
        $totalNet += $item->net_salary;
      @endphp
      <tr>
        <td class="text-center" style="color:#888">{{ $index + 1 }}</td>
        <td style="font-weight:600">{{ $item->employee->employee_id ?? '—' }}</td>
        <td style="font-weight:700">{{ $item->employee->name ?? 'Unknown' }}</td>
        <td class="text-end">{{ $currency }} {{ number_format($item->basic_salary) }}</td>
        <td class="text-end">{{ $currency }} {{ number_format($item->total_earnings) }}</td>
        <td class="text-end">{{ $currency }} {{ number_format($item->total_deductions) }}</td>
        <td class="text-end">{{ $currency }} {{ number_format($item->advance_deduction) }}</td>
        <td class="text-end" style="font-weight:700">{{ $currency }} {{ number_format($item->net_salary) }}</td>
        <td class="text-center">
          <span class="inv-item-status {{ $item->payment_status }}">{{ ucfirst($item->payment_status) }}</span>
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>

  <div class="inv-summary-wrap">
    <div style="flex:1"></div>
    <table class="inv-summary-table">
      <tr>
        <td class="label-col"><b>Total Employees :</b></td>
        <td class="value-col"><b>{{ $payroll->items->count() }}</b></td>
      </tr>
      <tr>
        <td class="label-col">Total Gross :</td>
        <td class="value-col">{{ $currency }} {{ number_format($totalGross) }}</td>
      </tr>
      <tr>
        <td class="label-col">Total Deductions :</td>
        <td class="value-col">{{ $currency }} {{ number_format($totalDeductions) }}</td>
      </tr>
      <tr class="row-grand">
        <td class="label-col">Total Net :</td>
        <td class="value-col">{{ $currency }} {{ number_format($totalNet) }}</td>
      </tr>
    </table>
  </div>

  <div class="inv-signatures">
    <div class="inv-sig">Prepared By</div>
    <div class="inv-sig">Approved By</div>
    <div class="inv-sig">Authorised By</div>
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
