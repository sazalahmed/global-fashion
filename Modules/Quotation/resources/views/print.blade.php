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
  <title>Quotation {{ $quotation->quotation_number }} — {{ $brand['name'] }}</title>
  @include('core::partials.print.letterhead-styles', ['isPdf' => $isPdf])
</head>
<body>

@if(!$isPdf)
<div class="inv-actions">
  <button onclick="window.print()" class="btn-print">Print</button>
  <a href="{{ route('quotations.show', $quotation) }}">Back</a>
</div>
@endif

  <div class="page">

    <!-- ===================== CONTENT ===================== -->
    <div class="content">

      <div class="page-head">

      @include('core::partials.print.letterhead-header', ['brand' => $brand, 'docTitle' => 'Quotation'])

      <!-- VENDOR / META -->
      <table class="layout-table">
        <tr>
          <td class="col-left line">
            <div class="fw-bold" style="text-transform:uppercase;">{{ $brand['name'] }}</div>
            @if($brand['address'])<div>{{ $brand['address'] }}</div>@endif
            @if($brand['phone'])<div>{{ \App\Helpers\PhoneHelper::format($brand['phone']) }}</div>@endif
            @if($brand['email'])<div>{{ $brand['email'] }}</div>@endif
          </td>
          <td class="col-right text-right line">
            <div><strong>Prepared by:</strong> {{ $quotation->creator->name ?? '—' }}</div>
            <div><strong>Quotation ID:</strong> {{ $quotation->quotation_number }}</div>
            <div><strong>Date:</strong> {{ $quotation->quotation_date->format('d M Y') }}</div>
            <div><strong>Valid Until:</strong> {{ $quotation->valid_until->format('d M Y') }}</div>
          </td>
        </tr>
      </table>

      </div>

      <!-- QUOTATION FOR (first page only — outside .page-head so the
           sheet paginator doesn't repeat it on subsequent pages) -->
      <table class="layout-table" style="margin-top:18px;">
        <tr>
          <td class="col-left line">
            <div class="section-head">Quotation For</div>
            <div><strong>Name:</strong> {{ $quotation->customer->name ?? 'Walk-in Customer' }}</div>
            @if($quotation->customer?->address)
              <div><strong>Address:</strong> {{ $quotation->customer->address }}</div>
            @endif
            @if($quotation->customer?->phone)
              <div><strong>Contact:</strong> {{ \App\Helpers\PhoneHelper::format($quotation->customer->phone) }}</div>
            @endif
          </td>
          <td class="col-right line"></td>
        </tr>
      </table>

      <!-- ITEMS TABLE -->
      <table class="table-items">
        <thead>
          <tr>
            <th style="width:6%;">Sl.</th>
            <th style="width:44%;">Product Name</th>
            <th style="width:10%;">Qnty</th>
            <th style="width:14%;">Unit Price</th>
            <th style="width:12%;">Discount</th>
            <th style="width:14%;">Total</th>
          </tr>
        </thead>
        <tbody>
          @foreach($quotation->items as $index => $item)
            @php
              $variantLabel = $item->variant?->variant_name;
              $breakdown = is_array($item->variant_breakdown) ? $item->variant_breakdown : [];
            @endphp
            <tr>
              <td class="text-center">{{ $index + 1 }}</td>
              <td>
                <span>{{ $item->product_name ?: $item->product?->name ?? '—' }}</span>
                @if(count($breakdown))
                  {{-- Compact one-line breakdown, e.g. M-1 Pcs, L-1 Pcs, XL-1 Pcs --}}
                  <div class="variant-row">
                    {{ collect($breakdown)->map(fn ($b) => ($b['name'] ?? '') . '-' . ($b['qty'] ?? 0) . ' Pcs')->implode(', ') }}
                  </div>
                @elseif($variantLabel)
                  <div class="variant-row">{{ $variantLabel }}</div>
                @elseif($item->custom_note)
                  <div class="variant-row">{{ $item->custom_note }}</div>
                @endif
              </td>
              <td class="text-center">{{ $item->quantity }}</td>
              <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
              <td class="text-right">{{ $item->discount_amount > 0 ? number_format($item->discount_amount, 2) : '' }}</td>
              <td class="text-right">{{ number_format($item->subtotal, 2) }}</td>
            </tr>
          @endforeach
          <tr class="fw-bold">
            <td colspan="2" class="text-right">Total Qty</td>
            <td class="text-center">{{ $quotation->items->sum('quantity') }}</td>
            <td></td>
            <td></td>
            <td class="text-right">{{ number_format($quotation->items->sum('subtotal'), 2) }}</td>
          </tr>
        </tbody>
      </table>

      <!-- FINANCIAL SUMMARY -->
      <table class="layout-table" style="margin-top:16px;">
        <tr>
          <td style="width:42%; vertical-align:top; padding-top:6px;">
            @if($quotation->notes)
              <div class="notes-block"><strong>Notes:</strong> {{ $quotation->notes }}</div>
            @endif
          </td>
          <td style="width:58%; vertical-align:top; padding-left:40px;">
            <table class="sum-table">
              <tr><td class="lbl">Subtotal</td><td class="val">{{ number_format($quotation->subtotal, 2) }}</td></tr>
              @if($quotation->discount_amount > 0)
                <tr><td class="lbl">Discount{{ $quotation->discount_type === 'percentage' ? ' (' . number_format($quotation->discount_value) . '%)' : '' }}</td><td class="val">{{ number_format($quotation->discount_amount, 2) }}</td></tr>
              @endif
              @if($quotation->tax_amount > 0)
                <tr class="thin"><td class="lbl">Vat{{ $quotation->tax_rate > 0 ? ' (' . number_format($quotation->tax_rate) . '%)' : '' }}</td><td class="val">{{ number_format($quotation->tax_amount, 2) }}</td></tr>
              @endif
              @if($quotation->shipping_charge > 0)
                <tr><td class="lbl">Delivery Charge</td><td class="val">{{ number_format($quotation->shipping_charge, 2) }}</td></tr>
              @endif
              <tr class="thick grand"><td class="lbl">Total</td><td class="val">{{ number_format($quotation->grand_total, 2) }}</td></tr>
            </table>
          </td>
        </tr>
      </table>

      <!-- TERMS -->
      @if($quotation->terms)
        <div class="notes-block"><strong>Terms &amp; Conditions:</strong><br>{{ $quotation->terms }}</div>
      @endif

      <!-- SIGNATURES -->
      <table class="sign-table">
        <tr>
          <td style="width:50%; text-align:left;">
            <span class="signature-mark">Customer Signature</span>
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
