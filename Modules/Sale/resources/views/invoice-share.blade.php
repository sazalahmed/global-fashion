<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  @php
    $bizName = \Modules\Setting\Models\Setting::get('business', 'name') ?? 'BizPOS Pro';
    $bizAddress = \Modules\Setting\Models\Setting::get('business', 'address') ?? '';
    $bizPhone = \Modules\Setting\Models\Setting::get('business', 'phone') ?? '';
    $bizEmail = \Modules\Setting\Models\Setting::get('business', 'email') ?? '';
    $bizBin = \Modules\Setting\Models\Setting::get('tax', 'bin') ?? '';
  @endphp
  <title>Invoice {{ $sale->invoice_number }} — {{ $bizName }}</title>
  <meta name="description" content="Invoice {{ $sale->invoice_number }} for {{ currency_symbol() }} {{ number_format($sale->grand_total) }} from {{ $bizName }}">
  <meta name="robots" content="noindex,follow">
  <link rel="canonical" href="{{ url()->current() }}">
  <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
  <link href="{{ asset('vendor/fontawesome/css/all.min.css') }}" rel="stylesheet">
  <link href="{{ asset('vendor/nunito-sans/nunito-sans.css') }}" rel="stylesheet">
  <style>
    body {
      font-family: 'Nunito Sans', sans-serif;
      background: #F0F2F5;
      padding: 24px 16px;
    }
    .inv-share-container {
      max-width: 700px;
      margin: 0 auto;
    }
    .inv-share-actions {
      display: flex;
      justify-content: center;
      gap: 10px;
      margin-bottom: 20px;
      flex-wrap: wrap;
    }
    .inv-share-actions .btn-act {
      padding: 10px 20px;
      border: 2px solid #1B4F72;
      border-radius: 6px;
      background: #fff;
      color: #1B4F72;
      font-size: 13px;
      font-weight: 700;
      cursor: pointer;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: all 0.2s;
    }
    .inv-share-actions .btn-act:hover {
      background: #1B4F72;
      color: #fff;
    }
    .inv-share-actions .btn-act.primary {
      background: #1B4F72;
      color: #fff;
    }
    .inv-share-actions .btn-act.primary:hover {
      background: #154360;
    }
    .inv-share-actions .btn-act.whatsapp {
      border-color: #25D366;
      color: #25D366;
    }
    .inv-share-actions .btn-act.whatsapp:hover {
      background: #25D366;
      color: #fff;
    }
    .inv-card {
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 2px 12px rgba(0,0,0,0.06);
      overflow: hidden;
    }
    .inv-card-header {
      background: #1B4F72;
      color: #fff;
      padding: 24px 30px;
    }
    .inv-card-header .brand {
      font-size: 22px;
      font-weight: 900;
    }
    .inv-card-header .brand span { color: #D4AC0D; }
    .inv-card-header .business-info {
      font-size: 12px;
      opacity: 0.8;
      margin-top: 4px;
    }
    .inv-card-header .inv-badge {
      display: inline-block;
      padding: 4px 14px;
      background: rgba(255,255,255,0.2);
      border-radius: 20px;
      font-size: 24px;
      font-weight: 900;
      margin-top: 12px;
    }
    .inv-card-body {
      padding: 30px;
    }
    .inv-meta-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
      margin-bottom: 24px;
    }
    .inv-meta-item label {
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      color: #6C757D;
      display: block;
      margin-bottom: 2px;
    }
    .inv-meta-item span {
      font-size: 14px;
      font-weight: 700;
      color: #1C2833;
    }
    .inv-customer-box {
      background: #F8F9FA;
      border-radius: 8px;
      padding: 16px;
      margin-bottom: 24px;
    }
    .inv-customer-box h4 {
      font-size: 11px;
      font-weight: 800;
      text-transform: uppercase;
      color: #6C757D;
      margin-bottom: 6px;
    }
    .inv-customer-box .name {
      font-size: 16px;
      font-weight: 800;
    }
    .inv-customer-box .detail {
      font-size: 12px;
      color: #6C757D;
    }
    .inv-items-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 20px;
    }
    .inv-items-table thead th {
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      color: #6C757D;
      padding: 8px 10px;
      border-bottom: 2px solid #DEE2E6;
    }
    .inv-items-table tbody td {
      padding: 10px;
      border-bottom: 1px solid #F0F2F5;
      font-size: 13px;
    }
    .inv-items-table .fw-700 { font-weight: 700; }
    .inv-items-table .fw-800 { font-weight: 800; }
    .inv-items-table .text-end { text-align: right; }
    .inv-items-table .text-center { text-align: center; }
    .inv-items-table .text-muted { color: #6C757D; }
    .inv-items-table .prod-name { font-weight: 700; }
    .inv-items-table .prod-desc { font-size: 11px; color: #6C757D; }
    .inv-totals-section {
      display: flex;
      justify-content: flex-end;
      margin-bottom: 24px;
    }
    .inv-totals-box { width: 280px; }
    .inv-totals-row {
      display: flex;
      justify-content: space-between;
      padding: 5px 0;
      font-size: 13px;
    }
    .inv-totals-row.total {
      border-top: 3px solid #1B4F72;
      margin-top: 8px;
      padding-top: 10px;
      font-size: 20px;
      font-weight: 900;
      color: #1B4F72;
    }
    .inv-payment-status {
      background: #D4EFDF;
      border-radius: 8px;
      padding: 14px 20px;
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 20px;
    }
    .inv-payment-status i {
      font-size: 24px;
      color: #1E8449;
    }
    .inv-payment-status .label {
      font-size: 12px;
      font-weight: 700;
      color: #1E8449;
      text-transform: uppercase;
    }
    .inv-payment-status .method {
      font-size: 13px;
      font-weight: 600;
      color: #1C2833;
    }
    .inv-footer-note {
      text-align: center;
      font-size: 12px;
      color: #ADB5BD;
      padding: 16px;
      border-top: 1px solid #F0F2F5;
    }
    .inv-footer-note strong { color: #6C757D; }
    .inv-header-label { font-size: 12px; opacity: 0.7; text-transform: uppercase; font-weight: 700; }
    .inv-terms { font-size: 11px; color: #ADB5BD; line-height: 1.6; }
    .inv-terms strong { color: #6C757D; }
    .inv-status-paid { color: #1E8449; }
    .inv-status-partial { color: #E67E22; }
    .inv-status-unpaid { color: #C0392B; }

    @media (max-width: 576px) {
      .inv-card-body { padding: 20px; }
      .inv-meta-grid { grid-template-columns: 1fr; gap: 12px; }
      .inv-totals-box { width: 100%; }
      .inv-totals-section { justify-content: stretch; }
    }

    @media print {
      @page { margin: 0; }
      body { margin: 15mm 10mm; }
      body { background: #fff; padding: 0; }
      .inv-share-actions { display: none !important; }
      .inv-card { box-shadow: none; }
    }
  </style>
</head>
<body>

<div class="inv-share-container">

  <!-- Action Buttons -->
  <div class="inv-share-actions">
    <button class="btn-act primary" onclick="window.print()"><i class="fa-solid fa-print"></i> Print</button>
    <a href="{{ route('sales.pdf', $sale) }}" class="btn-act"><i class="fa-solid fa-download"></i> Download PDF</a>
    <a href="https://wa.me/?text=Invoice%20{{ $sale->invoice_number }}%20-%20BDT%20{{ number_format($sale->grand_total) }}%20{{ urlencode(request()->url()) }}" target="_blank" class="btn-act whatsapp"><i class="fa-brands fa-whatsapp"></i> Share</a>
  </div>

  <!-- Invoice Card -->
  <div class="inv-card">

    <!-- Header -->
    <div class="inv-card-header">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="brand">Biz<span>POS</span> Pro</div>
          <div class="business-info">{{ $bizName }} @if($bizAddress)&bull; {{ $bizAddress }}@endif @if($bizPhone)&bull; {{ $bizPhone }}@endif</div>
        </div>
        <div class="text-end">
          <div class="inv-header-label">Invoice</div>
          <div class="inv-badge">{{ $sale->invoice_number }}</div>
        </div>
      </div>
    </div>

    <!-- Body -->
    <div class="inv-card-body">

      <!-- Meta Info -->
      <div class="inv-meta-grid">
        <div class="inv-meta-item">
          <label>Invoice Date</label>
          <span>{{ $sale->sale_date?->format('d M Y') ?? '--' }}</span>
        </div>
        <div class="inv-meta-item">
          <label>Due Date</label>
          <span>{{ $sale->due_date?->format('d M Y') ?? '--' }}</span>
        </div>
        <div class="inv-meta-item">
          <label>Payment Status</label>
          @if($sale->payment_status === 'paid')
            <span class="inv-status-paid"><i class="fa-solid fa-check-circle me-1"></i> Paid</span>
          @elseif($sale->payment_status === 'partial')
            <span class="inv-status-partial"><i class="fa-solid fa-clock me-1"></i> Partial</span>
          @else
            <span class="inv-status-unpaid"><i class="fa-solid fa-times-circle me-1"></i> Unpaid</span>
          @endif
        </div>
        <div class="inv-meta-item">
          <label>Source</label>
          <span>{{ ucfirst($sale->source ?? '--') }}</span>
        </div>
      </div>

      <!-- Customer -->
      <div class="inv-customer-box">
        <h4>Bill To</h4>
        <div class="name">{{ $sale->customer_display_name }}</div>
        <div class="detail">
          @if($sale->customer?->phone)<i class="fa-solid fa-phone fa-sm me-1"></i> {{ $sale->customer->phone }}@endif
          @if($sale->customer?->phone && $sale->customer?->email) &bull; @endif
          @if($sale->customer?->email)<i class="fa-solid fa-envelope fa-sm me-1"></i> {{ $sale->customer->email }}@endif
          @if($sale->customer?->address)<br><i class="fa-solid fa-location-dot fa-sm me-1"></i> {{ $sale->customer->address }}@endif
        </div>
      </div>

      <!-- Items -->
      <table class="inv-items-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Item</th>
            <th class="text-center">Qty</th>
            <th class="text-end">Price</th>
            <th class="text-end">Total</th>
          </tr>
        </thead>
        <tbody>
          @foreach($sale->items as $index => $item)
          <tr>
            <td class="text-muted">{{ $index + 1 }}</td>
            <td>
              <div class="prod-name">{{ $item->product->name ?? '--' }}</div>
              <div class="prod-desc">@if($item->variant){{ $item->variant }} &middot; @endif SKU: {{ $item->product->sku ?? '--' }}</div>
            </td>
            <td class="text-center fw-700">{{ $item->quantity }}</td>
            <td class="text-end">{{ currency_symbol() }} {{ number_format($item->unit_price) }}</td>
            <td class="text-end fw-800">{{ currency_symbol() }} {{ number_format($item->total) }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>

      <!-- Totals -->
      <div class="inv-totals-section">
        <div class="inv-totals-box">
          <div class="inv-totals-row">
            <span>Subtotal</span>
            <span class="fw-700">{{ currency_symbol() }} {{ number_format($sale->subtotal) }}</span>
          </div>
          @if($sale->discount_amount > 0)
          <div class="inv-totals-row">
            <span>Discount</span>
            <span>{{ currency_symbol() }} {{ number_format($sale->discount_amount) }}</span>
          </div>
          @endif
          {{-- No VAT line: prices are quoted VAT-inclusive, so grand_total
               already contains tax_amount and listing it here read as a
               charge being added on top. --}}
          @if($sale->shipping_charge > 0)
          <div class="inv-totals-row">
            <span>Shipping</span>
            <span>{{ currency_symbol() }} {{ number_format($sale->shipping_charge) }}</span>
          </div>
          @endif
          <div class="inv-totals-row total">
            <span>Total</span>
            <span>{{ currency_symbol() }} {{ number_format($sale->grand_total) }}</span>
          </div>
        </div>
      </div>

      <!-- Payment Status -->
      <div class="inv-payment-status">
        @if($sale->payment_status === 'paid')
          <i class="fa-solid fa-circle-check"></i>
          <div>
            <div class="label">Paid in Full</div>
            <div class="method">{{ currency_symbol() }} {{ number_format($sale->paid_amount) }} received</div>
          </div>
        @elseif($sale->payment_status === 'partial')
          <i class="fa-solid fa-clock"></i>
          <div>
            <div class="label">Partially Paid</div>
            <div class="method">{{ currency_symbol() }} {{ number_format($sale->paid_amount) }} of {{ currency_symbol() }} {{ number_format($sale->grand_total) }} — Due: {{ currency_symbol() }} {{ number_format($sale->due_amount) }}</div>
          </div>
        @else
          <i class="fa-solid fa-times-circle"></i>
          <div>
            <div class="label">Unpaid</div>
            <div class="method">Due: {{ currency_symbol() }} {{ number_format($sale->due_amount) }}</div>
          </div>
        @endif
      </div>

      <!-- Notes -->
      @if($sale->notes)
      <div class="inv-terms">
        <strong>Notes:</strong> {{ $sale->notes }}
      </div>
      @endif

    </div>

    <!-- Footer -->
    <div class="inv-footer-note">
      <strong>{{ $bizName }}</strong>@if($bizAddress) &bull; {{ $bizAddress }}@endif<br>
      @if($bizPhone){{ $bizPhone }}@endif @if($bizEmail)&bull; {{ $bizEmail }}@endif @if($bizBin)&bull; BIN: {{ $bizBin }}@endif<br>
      <em>Thank you for your purchase!</em>
    </div>

  </div>
</div>

</body>
</html>
