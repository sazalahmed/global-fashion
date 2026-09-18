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
    $emp = $item->employee;
    $period = \Carbon\Carbon::parse($item->payroll->month . '-01')->format('F Y');
  @endphp
  <title>Payslip {{ $emp->employee_id ?? '' }} — {{ $period }}</title>
  <link href="{{ asset('vendor/nunito-sans/nunito-sans.css') }}" rel="stylesheet">
  <link href="{{ asset('vendor/fontawesome/css/all.min.css') }}" rel="stylesheet">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Nunito Sans', sans-serif; font-size: 13px; color: #232c3d; background: #e9ecef; }
    .ps-actions { position: fixed; top: 0; left: 0; right: 0; background: #1B4F72; color: #fff; padding: 10px 24px; display: flex; justify-content: center; gap: 10px; z-index: 100; }
    .ps-actions button, .ps-actions a { padding: 8px 20px; border: 2px solid #fff; border-radius: 6px; background: transparent; color: #fff; font-size: 13px; font-weight: 700; cursor: pointer; text-decoration: none; }
    .ps-actions .btn-print { background: #fff; color: #1B4F72; }
    .ps-page { max-width: 760px; margin: 70px auto 40px; padding: 44px; background: #fff; box-shadow: 0 0 8px rgba(0,0,0,0.06); border-radius: 6px; }
    .ps-head { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #1B4F72; padding-bottom: 16px; margin-bottom: 20px; }
    .ps-biz { font-size: 18px; font-weight: 800; color: #1B4F72; }
    .ps-title { font-size: 20px; font-weight: 800; color: #1B4F72; text-align: right; }
    .ps-sub { font-size: 12px; color: #666; }
    .ps-meta { display: flex; justify-content: space-between; margin-bottom: 18px; }
    .ps-meta .k { font-weight: 700; color: #555; }
    table.ps { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
    table.ps th { background: #1B4F72; color: #fff; font-size: 11px; text-transform: uppercase; padding: 7px 10px; text-align: left; }
    table.ps td { padding: 7px 10px; border-bottom: 1px solid #eef1f4; font-size: 13px; }
    table.ps td.amt { text-align: right; }
    .ps-cols { display: flex; gap: 20px; }
    .ps-cols > div { flex: 1; }
    .ps-net { margin-top: 16px; background: #eaf4ec; border: 1px solid #cfe8d6; border-radius: 6px; padding: 12px 16px; display: flex; justify-content: space-between; align-items: center; }
    .ps-net .lbl { font-weight: 700; }
    .ps-net .val { font-size: 20px; font-weight: 900; color: #1E8449; }
    .ps-sign { display: flex; justify-content: space-between; margin-top: 80px; }
    .ps-sign div { border-top: 1px dashed #333; padding-top: 6px; font-weight: 700; font-size: 13px; min-width: 180px; text-align: center; }
    .ps-foot { text-align: center; font-size: 11px; color: #adb5bd; margin-top: 26px; }
    @media print { @page { margin: 0; } body { margin: 12mm; background: #fff; } .ps-actions { display: none !important; } .ps-page { margin: 0 auto; box-shadow: none; } }
  </style>
</head>
<body>

<div class="ps-actions">
  <button onclick="window.print()" class="btn-print"><i class="fa-solid fa-print"></i> Print</button>
  <a href="{{ route('payroll.show', $item->payroll_id) }}"><i class="fa-solid fa-arrow-left"></i> Back</a>
</div>

<div class="ps-page">
  <div class="ps-head">
    <div>
      <div class="ps-biz">{{ $bizName }}</div>
      <div class="ps-sub">{{ $bizAddress }}</div>
      @if($bizPhone)<div class="ps-sub">Phone: {{ \App\Helpers\PhoneHelper::format($bizPhone) }}</div>@endif
    </div>
    <div>
      <div class="ps-title">PAYSLIP</div>
      <div class="ps-sub" style="text-align:right">{{ $period }}</div>
      <div class="ps-sub" style="text-align:right">{{ $item->payroll->payroll_number }}</div>
    </div>
  </div>

  <div class="ps-meta">
    <div>
      <div><span class="k">Employee:</span> {{ $emp->name ?? '—' }}</div>
      <div><span class="k">ID:</span> {{ $emp->employee_id ?? '—' }}</div>
      <div><span class="k">Designation:</span> {{ $emp->designation ?? '—' }}</div>
    </div>
    <div style="text-align:right">
      <div><span class="k">Working Days:</span> {{ $item->working_days }}</div>
      <div><span class="k">Present:</span> {{ $item->present_days }}</div>
      <div><span class="k">Absent:</span> {{ $item->absent_days }}</div>
    </div>
  </div>

  <div class="ps-cols">
    <div>
      <table class="ps">
        <thead><tr><th>Earnings</th><th class="amt">{{ $currency }}</th></tr></thead>
        <tbody>
          <tr><td>Basic Salary</td><td class="amt">{{ number_format($item->basic_salary, 2) }}</td></tr>
          <tr><td>Overtime @if($item->overtime_hours > 0)<span class="ps-sub">({{ number_format($item->overtime_hours, 1) }} hrs)</span>@endif</td><td class="amt">{{ number_format($item->overtime, 2) }}</td></tr>
          <tr><td>Bonus</td><td class="amt">{{ number_format($item->bonus, 2) }}</td></tr>
          <tr><td>Commission</td><td class="amt">{{ number_format($item->commission, 2) }}</td></tr>
          <tr><td class="fw-800">Gross</td><td class="amt fw-800">{{ number_format($item->gross_salary, 2) }}</td></tr>
        </tbody>
      </table>
    </div>
    <div>
      <table class="ps">
        <thead><tr><th>Deductions</th><th class="amt">{{ $currency }}</th></tr></thead>
        <tbody>
          <tr><td>Advance Recovery</td><td class="amt">{{ number_format($item->advance_deduction, 2) }}</td></tr>
          <tr><td>Absent @if($item->absent_days)<span class="ps-sub">({{ $item->absent_days }} day)</span>@endif</td><td class="amt">{{ number_format($item->absent_deduction, 2) }}</td></tr>
          <tr><td class="fw-800">Total Deductions</td><td class="amt fw-800">{{ number_format($item->total_deductions, 2) }}</td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <div class="ps-net">
    <span class="lbl">Net Payable</span>
    <span class="val">{{ $currency }} {{ number_format($item->net_salary, 2) }}</span>
  </div>

  <div class="ps-sign">
    <div>Employee Signature</div>
    <div>Authorized Signature</div>
  </div>

  <div class="ps-foot">This is a computer-generated payslip.</div>
</div>

<script>
  'use strict';
</script>
</body>
</html>
