<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  @php
    use Modules\Setting\Models\Setting;

    $bizName    = Setting::get('business', 'company_name', 'BizPOS Pro');
    $bizAddress = Setting::get('business', 'address', '');
    $bizPhone   = Setting::get('business', 'company_phone', '');
    $bizEmail   = Setting::get('business', 'email', '');

    // Logo: DomPDF can't read WebP, so embed a PNG/JPG data URI (converting
    // WebP via GD when available). Falls back to no logo on any failure.
    $logoData = null;
    $logoPath = Setting::get('business', 'logo');
    if ($logoPath) {
        $abs = public_path($logoPath);
        if (is_file($abs)) {
            $ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
            try {
                if (in_array($ext, ['png', 'jpg', 'jpeg', 'gif'], true)) {
                    $mime = $ext === 'jpg' ? 'jpeg' : $ext;
                    $logoData = 'data:image/' . $mime . ';base64,' . base64_encode(file_get_contents($abs));
                } elseif ($ext === 'webp' && function_exists('imagecreatefromwebp')) {
                    $img = @imagecreatefromwebp($abs);
                    if ($img) {
                        imagepalettetotruecolor($img);
                        imagealphablending($img, true);
                        imagesavealpha($img, true);
                        ob_start();
                        imagepng($img);
                        $png = ob_get_clean();
                        imagedestroy($img);
                        $logoData = 'data:image/png;base64,' . base64_encode($png);
                    }
                }
            } catch (\Throwable $e) {
                $logoData = null;
            }
        }
    }

    $money   = static fn ($v): string => 'TK. ' . number_format((float) $v);
    $phone   = $sale->customer?->phone ?? $sale->customer_phone_snapshot;
    $address = $sale->customer_address ?: ($sale->customer?->address ?? '');
  @endphp
  <title>Invoice {{ $sale->invoice_number }}</title>
  <style>
    @page { margin: 14mm 12mm; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 12px; color: #232c3d; }

    /* Header */
    .inv-head-table { width: 100%; margin-bottom: 18px; }
    .inv-biz-name { font-size: 20px; font-weight: bold; margin-bottom: 6px; }
    .inv-biz div { font-weight: bold; line-height: 1.6; }
    .inv-logo { text-align: right; vertical-align: top; }
    .inv-logo img { max-height: 55px; max-width: 200px; }

    /* Bill To / meta box */
    .inv-billbox { width: 100%; background-color: #fdf1ea; margin-bottom: 18px; border-collapse: collapse; }
    .inv-billbox > tbody > tr > td { padding: 16px 18px; vertical-align: top; }
    .bill-label { font-weight: bold; }
    .bill-name { font-size: 16px; font-weight: bold; padding: 5px 0; }
    .bill-left div { line-height: 1.6; }
    .meta-table { width: 100%; }
    .meta-table td { padding: 3px 0; line-height: 1.6; }
    .meta-table .meta-k { font-weight: bold; }
    .meta-table .meta-v { text-align: right; }

    /* Items table */
    .inv-items { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
    .inv-items thead th {
      background-color: #f0883e; color: #2b2114; font-weight: bold;
      text-align: left; padding: 9px 10px; border: 1px solid #f0883e; font-size: 12px;
    }
    .inv-items tbody td {
      padding: 9px 10px; border: 1px solid #e6e6e6; font-weight: bold; vertical-align: top;
    }

    /* Barcode (left) + totals (right) */
    .inv-bottom-table { width: 100%; margin-top: 6px; }
    .inv-bottom-table > tbody > tr > td { vertical-align: top; }
    .inv-barcode img { height: 50px; }

    .totals-table { width: 100%; }
    .totals-table td { padding: 3px 0; font-weight: bold; line-height: 1.7; }
    .totals-table .t-k { width: 120px; }
    .totals-table .t-v { text-align: left; }
    .totals-table .due-row td { font-size: 16px; font-weight: bold; padding-top: 8px; }
    .totals-table .due-row .t-v { font-size: 18px; }
  </style>
</head>
<body>

  {{-- Header: business (left) + logo (right) --}}
  <table class="inv-head-table">
    <tr>
      <td style="width:62%; vertical-align:top;">
        <div class="inv-biz">
          <div class="inv-biz-name">{{ $bizName }}</div>
          @if($bizAddress)<div>{{ $bizAddress }}</div>@endif
          @if($bizPhone)<div>{{ $bizPhone }}</div>@endif
          @if($bizEmail)<div>{{ $bizEmail }}</div>@endif
        </div>
      </td>
      <td class="inv-logo">
        @if($logoData)<img src="{{ $logoData }}" alt="{{ $bizName }}">@endif
      </td>
    </tr>
  </table>

  {{-- Bill To / meta --}}
  <table class="inv-billbox">
    <tr>
      <td class="bill-left" style="width:55%;">
        <div class="bill-label">Bill To</div>
        <div class="bill-name">{{ $sale->customer_display_name }}</div>
        @if($phone)<div>{{ $phone }}</div>@endif
        @if($address)<div>{{ $address }}</div>@endif
      </td>
      <td>
        <table class="meta-table">
          <tr><td class="meta-k">Percel ID #</td><td class="meta-v">{{ $sale->courier_consignment_id ?? '' }}</td></tr>
          <tr><td class="meta-k">Invoice #</td><td class="meta-v">{{ $sale->invoice_number }}</td></tr>
          <tr><td class="meta-k">Date :</td><td class="meta-v">{{ $sale->sale_date?->format('d-m-Y') ?? '' }}</td></tr>
        </table>
      </td>
    </tr>
  </table>

  {{-- Items --}}
  <table class="inv-items">
    <thead>
      <tr>
        <th style="width:48%">Item</th>
        <th style="width:13%">Variation</th>
        <th style="width:13%">Quantity</th>
        <th style="width:13%">Price</th>
        <th style="width:13%">Amount</th>
      </tr>
    </thead>
    <tbody>
      @foreach($sale->items as $item)
        <tr>
          <td>{{ $item->product?->name ?: \Illuminate\Support\Str::beforeLast($item->product_name, ' — ') }}</td>
          <td>{{ $item->variant_label ?: '—' }}</td>
          <td>{{ $item->quantity }}</td>
          <td>{{ $money($item->unit_price) }}</td>
          <td>{{ $money($item->subtotal) }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>

  {{-- Barcode + totals --}}
  <table class="inv-bottom-table">
    <tr>
      <td class="inv-barcode" style="width:50%; padding-top:6px;">
        @php($barcode = \Modules\Sale\Support\Code128::dataUri($sale->invoice_number))
        @if($barcode)<img src="{{ $barcode }}" alt="{{ $sale->invoice_number }}">@endif
      </td>
      <td style="width:50%;">
        <table class="totals-table">
          <tr><td class="t-k">Sub Total</td><td class="t-v">{{ $money($sale->subtotal) }}</td></tr>
          <tr><td class="t-k">Discount</td><td class="t-v">{{ $money($sale->discount_amount) }}</td></tr>
          <tr><td class="t-k">Shipping (+)</td><td class="t-v">{{ $money($sale->shipping_charge ?? 0) }}</td></tr>
          <tr><td class="t-k">Total</td><td class="t-v">{{ $money($sale->grand_total) }}</td></tr>
          <tr class="due-row"><td class="t-k">Amount Due</td><td class="t-v">{{ $money($sale->due_amount) }}</td></tr>
        </table>
      </td>
    </tr>
  </table>

</body>
</html>
