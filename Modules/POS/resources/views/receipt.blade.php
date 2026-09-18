<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt — {{ $sale->invoice_number }}</title>
    <style>
        /* Thermal receipt styling - 80mm width */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Courier New', monospace; font-size: 12px; width: 80mm; margin: 0 auto; padding: 5mm; }
        .receipt-header { text-align: center; margin-bottom: 8px; }
        .receipt-header h2 { font-size: 16px; margin-bottom: 2px; }
        .receipt-divider { border-top: 1px dashed #000; margin: 6px 0; }
        .receipt-row { display: flex; justify-content: space-between; }
        .receipt-items { width: 100%; }
        .receipt-items th, .receipt-items td { text-align: left; padding: 2px 0; font-size: 11px; }
        .receipt-items th:last-child, .receipt-items td:last-child { text-align: right; }
        .receipt-total { font-size: 14px; font-weight: bold; }
        .receipt-footer { text-align: center; margin-top: 10px; font-size: 10px; }
        @media print { body { width: 80mm; } }
    </style>
</head>
<body onload="window.print()">
    <div class="receipt-header">
        <h2>{{ \Modules\Setting\Models\Setting::get('business', 'company_name', 'BizPOS Pro') }}</h2>
        <div class="d-none">{{ $sale->branch->name ?? 'Main Branch' }}</div>
        <div class="d-none">{{ $sale->branch->address ?? '' }}</div>
    </div>

    <div class="receipt-divider"></div>

    <!-- Invoice Info -->
    <div class="receipt-row"><span>Invoice:</span><span>{{ $sale->invoice_number }}</span></div>
    <div class="receipt-row"><span>Date:</span><span>{{ $sale->sale_date->format('d M Y H:i') }}</span></div>
    <div class="receipt-row"><span>Cashier:</span><span>{{ $sale->creator->name ?? '--' }}</span></div>
    @if($sale->customer)
    <div class="receipt-row"><span>Customer:</span><span>{{ $sale->customer->name }}</span></div>
    @endif

    <div class="receipt-divider"></div>

    <!-- Items -->
    <table class="receipt-items">
        <thead>
            <tr><th>Item</th><th>Qty</th><th>Price</th><th>Total</th></tr>
        </thead>
        <tbody>
            @foreach($sale->items as $item)
            <tr>
                <td>{{ Str::limit($item->product_name, 18) }}@if($item->variant_id && $item->variant)<br><small>{{ $item->variant->variant_name }}</small>@endif</td>
                <td>{{ $item->quantity }}</td>
                <td>{{ number_format($item->unit_price, 0) }}</td>
                <td>{{ number_format($item->subtotal, 0) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="receipt-divider"></div>

    <!-- Totals -->
    <div class="receipt-row"><span>Subtotal:</span><span>{{ currency_symbol() }} {{ number_format($sale->subtotal, 0) }}</span></div>
    @if($sale->discount_amount > 0)
    <div class="receipt-row"><span>Discount:</span><span>- {{ currency_symbol() }} {{ number_format($sale->discount_amount, 0) }}</span></div>
    @endif
    @if($sale->tax_amount > 0)
    <div class="receipt-row"><span>VAT:</span><span>{{ currency_symbol() }} {{ number_format($sale->tax_amount, 0) }}</span></div>
    @endif
    <div class="receipt-divider"></div>
    <div class="receipt-row receipt-total"><span>TOTAL:</span><span>{{ currency_symbol() }} {{ number_format($sale->grand_total, 0) }}</span></div>
    <div class="receipt-row"><span>Paid:</span><span>{{ currency_symbol() }} {{ number_format($sale->paid_amount, 0) }}</span></div>
    @if($sale->due_amount > 0)
    <div class="receipt-row"><span>Due:</span><span>{{ currency_symbol() }} {{ number_format($sale->due_amount, 0) }}</span></div>
    @else
    <div class="receipt-row"><span>Change:</span><span>{{ currency_symbol() }} {{ number_format($sale->paid_amount - $sale->grand_total, 0) }}</span></div>
    @endif

    <div class="receipt-divider"></div>

    <div class="receipt-footer">
        <div>Thank you for your purchase!</div>
        <div>Exchange within 7 days with receipt</div>
    </div>
</body>
</html>
