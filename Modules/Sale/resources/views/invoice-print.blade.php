<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  @php
    $isPdf = $isPdf ?? false;
    $brand = $brand ?? \Modules\Setting\Services\SettingService::printBranding($isPdf);
    $currency = $brand['currency'];

    $invoices = (isset($sales) && $sales && $sales->count())
        ? $sales
        : collect(array_filter([$sale ?? null]));
  @endphp
  <title>Invoice — {{ $brand['name'] }}</title>
  @include('core::partials.print.letterhead-styles', ['isPdf' => $isPdf])
</head>
<body>

@if(!$isPdf)
<div class="inv-actions">
  <button onclick="window.print()" class="btn-print">Print</button>
  @if($invoices->count() === 1)
    <a href="{{ route('sales.show', $invoices->first()) }}">Back</a>
  @endif
</div>
@endif

@foreach($invoices as $s)
  @php
    $billPhone = $s->customer?->phone ?? $s->customer_phone_snapshot;
    $billAddress = $s->customer?->address ?: $s->customer_address;
    $shipAddress = trim(implode(', ', array_filter([
        $s->customer_address,
        $s->thana?->thana_name,
        $s->district?->district_name,
    ]))) ?: $billAddress;

    $previousDue = null;
    if ($s->customer_id) {
        $totalDue = (float) \Modules\Sale\Models\Sale::where('customer_id', $s->customer_id)
            ->whereNotIn('status', \Modules\Customer\Services\CustomerService::HIDDEN_SALE_STATUSES)
            ->sum('due_amount');
        $previousDue = max(0, $totalDue - (float) $s->due_amount);
    }

    $displayRows = [];
    foreach ($s->items as $item) {
        $clean = $item->product?->name ?: \Illuminate\Support\Str::beforeLast($item->product_name, ' — ');
        $key = $item->combo_group
            ? 'combo-' . $item->id
            : 'p-' . $item->product_id . '-' . $item->unit_price . '-' . $clean;
        if (! isset($displayRows[$key])) {
            $displayRows[$key] = [
                'name'       => $clean,
                'combo'      => $item->combo_name,
                'variants'   => [],
                'qty'        => 0,
                'unit_price' => (float) $item->unit_price,
                'discount'   => 0.0,
                'subtotal'   => 0.0,
            ];
        }
        if ($item->variant_label) {
            $displayRows[$key]['variants'][] = $item->variant_label . '-' . (int) $item->quantity . ' Pcs';
        }
        $displayRows[$key]['qty'] += (int) $item->quantity;
        $displayRows[$key]['discount'] += (float) $item->discount_amount;
        $displayRows[$key]['subtotal'] += (float) $item->subtotal;
    }
    $displayRows = array_values($displayRows);
  @endphp

  <div class="page">

    <!-- ===================== CONTENT ===================== -->
    <div class="content">

      <div class="page-head">

      @include('core::partials.print.letterhead-header', ['brand' => $brand, 'docTitle' => 'Invoice'])

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
            <div><strong>Created by:</strong> {{ ($s->assignedStaff ?? $s->creator)?->name ?? '—' }}</div>
            <div><strong>Invoice ID:</strong> {{ $s->invoice_number }}</div>
            <div><strong>Date:</strong> {{ $s->sale_date?->format('d M Y') ?? '—' }}</div>
            @if($s->courier_consignment_id)
              <div><strong>Parcel ID:</strong> {{ $s->courier_consignment_id }}</div>
            @endif
          </td>
        </tr>
      </table>

      </div>

      <!-- BILL TO / SHIP TO (first page only — outside .page-head so the
           sheet paginator doesn't repeat it on subsequent pages) -->
      <table class="layout-table" style="margin-top:18px;">
        <tr>
          <td class="col-left line">
            <div class="section-head">Bill To</div>
            <div><strong>Name:</strong> {{ $s->customer_display_name }}</div>
            @if($billAddress)
              <div><strong>Address:</strong> {{ $billAddress }}</div>
            @endif
            @if($billPhone)
              <div><strong>Contact:</strong> {{ \App\Helpers\PhoneHelper::format($billPhone) }}</div>
            @endif
          </td>
          <td class="col-right line">
            <div class="section-head">Ship To</div>
            <div><strong>Name:</strong> {{ $s->customer_display_name }}</div>
            @if($shipAddress)
              <div><strong>Address:</strong> {{ $shipAddress }}</div>
            @endif
            @if($billPhone)
              <div><strong>Contact:</strong> {{ \App\Helpers\PhoneHelper::format($billPhone) }}</div>
            @endif
          </td>
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
          @foreach($displayRows as $index => $row)
            <tr>
              <td class="text-center">{{ $index + 1 }}</td>
              <td>
                <span>{{ $row['name'] }}</span>
                @if($row['combo'])
                  <div class="variant-row">{{ $row['combo'] }}</div>
                @endif
                @if(count($row['variants']))
                  <div class="variant-row">{{ implode(', ', $row['variants']) }}</div>
                @endif
              </td>
              <td class="text-center">{{ $row['qty'] }}</td>
              <td class="text-center">{{ number_format($row['unit_price'], 2) }}</td>
              <td class="text-center">{{ $row['discount'] > 0 ? number_format($row['discount'], 2) : '' }}</td>
              <td class="text-right">{{ number_format($row['subtotal'], 2) }}</td>
            </tr>
          @endforeach
          <tr class="fw-bold">
            <td colspan="2" class="text-right">Total Qty</td>
            <td class="text-center">{{ $s->items->sum('quantity') }}</td>
            <td></td>
            <td></td>
            <td class="text-right">{{ number_format(collect($displayRows)->sum('subtotal'), 2) }}</td>
          </tr>
        </tbody>
      </table>

      <!-- FINANCIAL SUMMARY -->
      <table class="layout-table" style="margin-top:16px;">
        <tr>
          <td style="width:42%; vertical-align:top; padding-top:6px;">
            @if($previousDue !== null && ($previousDue > 0 || $s->due_amount > 0))
              <table class="due-table">
                <tr><td class="fw-bold">Previous Due:</td><td class="val">{{ number_format($previousDue, 2) }}</td></tr>
                <tr><td class="fw-bold">Current Due:</td><td class="val">{{ number_format($s->due_amount, 2) }}</td></tr>
                <tr class="total-due"><td>Total Due:</td><td class="val">{{ number_format($previousDue + $s->due_amount, 2) }}</td></tr>
              </table>
            @endif
            @if($s->notes)
              <div class="notes-block"><strong>Notes:</strong> {{ $s->notes }}</div>
            @endif
          </td>
          <td style="width:58%; vertical-align:top; padding-left:40px;">
            <table class="sum-table">
              <tr><td class="lbl">Subtotal</td><td class="val">{{ number_format($s->subtotal, 2) }}</td></tr>
              @if($s->discount_amount > 0)
                <tr><td class="lbl">Discount</td><td class="val">{{ number_format($s->discount_amount, 2) }}</td></tr>
              @endif
              {{-- No VAT line: prices are quoted VAT-inclusive, so grand_total
                   already contains tax_amount. Listing it between Subtotal and
                   Total read as a charge being added and left the column not
                   adding up (725 + 94.57 shown against a 725 total). --}}
              @if($s->shipping_charge > 0)
                <tr><td class="lbl">Delivery Charge</td><td class="val">{{ number_format($s->shipping_charge, 2) }}</td></tr>
              @endif
              <tr class="thick grand"><td class="lbl">Total</td><td class="val">{{ number_format($s->grand_total, 2) }}</td></tr>
              @if($s->paid_amount > 0)
                <tr><td class="lbl">Paid</td><td class="val">{{ number_format($s->paid_amount, 2) }}</td></tr>
              @endif
              <tr class="grand"><td class="lbl">Balance Due</td><td class="val">{{ number_format($s->due_amount, 2) }}</td></tr>
            </table>
          </td>
        </tr>
      </table>

      <!-- SIGNATURES -->
      <table class="sign-table">
        <tr>
          <td style="width:50%; text-align:left;">
            <span class="signature-mark">Received by</span>
          </td>
          <td style="width:50%; text-align:right;">
            <span class="signature-mark">Authorized by</span>
          </td>
        </tr>
      </table>

    </div>
    <!-- =================== /CONTENT =================== -->

  </div>
@endforeach

@if(!$isPdf)
  @include('core::partials.print.letterhead-scripts')
@endif

</body>
</html>
