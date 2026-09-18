<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  @php
    $isPdf = $isPdf ?? false;
    $brand = $brand ?? \Modules\Setting\Services\SettingService::printBranding($isPdf);
    $currency = $brand['currency'];
  @endphp
  <title>Asset {{ $asset->asset_code }} — {{ $brand['name'] }}</title>
  @include('core::partials.print.letterhead-styles', ['isPdf' => $isPdf])
</head>
<body>

@if(!$isPdf)
<div class="inv-actions">
  <button onclick="window.print()" class="btn-print">Print</button>
  <a href="{{ route('assets.show', $asset) }}">Back</a>
</div>
@endif

  <div class="page">

    <!-- ===================== CONTENT ===================== -->
    <div class="content">

      <div class="page-head">

      @include('core::partials.print.letterhead-header', ['brand' => $brand, 'docTitle' => 'Asset Purchase'])

      <!-- BUSINESS / META -->
      <table class="layout-table">
        <tr>
          <td class="col-left line">
            <div class="fw-bold" style="text-transform:uppercase;">{{ $brand['name'] }}</div>
            @if($brand['address'])<div>{{ $brand['address'] }}</div>@endif
            @if($brand['phone'])<div>{{ \App\Helpers\PhoneHelper::format($brand['phone']) }}</div>@endif
            @if($brand['email'])<div>{{ $brand['email'] }}</div>@endif
          </td>
          <td class="col-right text-right line">
            <div><strong>Asset ID:</strong> {{ $asset->asset_code }}</div>
            <div><strong>Purchase Date:</strong> {{ $asset->purchase_date->format('d M Y') }}</div>
            @if($asset->vendor_invoice_no)
              <div><strong>Vendor Invoice:</strong> {{ $asset->vendor_invoice_no }}</div>
            @endif
            <div><strong>Payment Status:</strong> {{ strtoupper($asset->payment_status) }}</div>
          </td>
        </tr>
      </table>

      <!-- PURCHASED FROM -->
      <table class="layout-table" style="margin-top:18px;">
        <tr>
          <td class="col-left line">
            <div class="section-head">Purchased From</div>
            <div><strong>Vendor:</strong> {{ $asset->vendor_name ?? '—' }}</div>
          </td>
          <td class="col-right line"></td>
        </tr>
      </table>

      </div>

      <!-- ASSET TABLE -->
      <table class="table-items">
        <thead>
          <tr>
            <th style="width:6%;">Sl.</th>
            <th style="width:44%;">Asset</th>
            <th style="width:22%;">Category</th>
            <th style="width:14%;">Useful Life</th>
            <th style="width:14%;">Price</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td class="text-center">1</td>
            <td>
              <span>{{ $asset->name }}</span>
              @if($asset->serial_number)
                <div class="variant-row">Serial: {{ $asset->serial_number }}</div>
              @endif
            </td>
            <td>{{ $asset->category->name ?? '—' }}</td>
            <td class="text-center">{{ $asset->useful_life_years ? $asset->useful_life_years . ' ' . Str::plural('year', $asset->useful_life_years) : '—' }}</td>
            <td class="text-right">{{ number_format($asset->purchase_price, 2) }}</td>
          </tr>
        </tbody>
      </table>

      <!-- FINANCIAL SUMMARY -->
      <table class="layout-table" style="margin-top:16px;">
        <tr>
          <td style="width:42%; vertical-align:top; padding-top:6px;">
            @if($asset->description)
              <div class="notes-block"><strong>Notes:</strong> {{ $asset->description }}</div>
            @endif
          </td>
          <td style="width:58%; vertical-align:top; padding-left:40px;">
            <table class="sum-table">
              <tr class="thick grand"><td class="lbl">Total</td><td class="val">{{ $currency }} {{ number_format($asset->purchase_price, 2) }}</td></tr>
              <tr><td class="lbl">Paid</td><td class="val">{{ number_format($asset->paid_amount, 2) }}</td></tr>
              <tr class="thin"><td class="lbl">Due</td><td class="val">{{ number_format($asset->due_amount, 2) }}</td></tr>
            </table>
          </td>
        </tr>
      </table>

      <!-- PAYMENT HISTORY -->
      @if($asset->payments->count())
        <table class="table-items" style="margin-top:16px;">
          <thead>
            <tr>
              <th style="width:26%;">Receipt #</th>
              <th style="width:24%;">Date</th>
              <th style="width:30%;">Account</th>
              <th style="width:20%;">Amount</th>
            </tr>
          </thead>
          <tbody>
            @foreach($asset->payments as $payment)
              <tr>
                <td>{{ $payment->payment_number }}</td>
                <td class="text-center">{{ $payment->payment_date->format('d M Y') }}</td>
                <td>{{ $payment->paymentAccount->name ?? '—' }}</td>
                <td class="text-right">{{ number_format($payment->amount, 2) }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      @endif

      <!-- SIGNATURES -->
      <table class="sign-table">
        <tr>
          <td style="width:50%; text-align:left;">
            <span class="signature-mark">Received By</span>
          </td>
          <td style="width:50%; text-align:right;">
            <span class="signature-mark">Authorized by</span>
          </td>
        </tr>
      </table>

    </div>
    <!-- =================== /CONTENT =================== -->

  </div>

@if(!$isPdf)
  @include('core::partials.print.letterhead-scripts')
@endif

</body>
</html>
