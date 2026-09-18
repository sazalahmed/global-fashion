<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $purchase->po_number }} — Purchase Order</title>
    <link href="{{ asset('vendor/nunito-sans/nunito-sans.css') }}" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Nunito Sans', sans-serif; font-size: 13px; color: #1a1a1a; padding: 30px 40px; max-width: 800px; margin: 0 auto; }

        /* Header */
        .po-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 3px solid #1B4F72; }
        /* Capped by height so any logo aspect ratio sits on the letterhead
           without pushing the header out of shape. */
        .po-logo { max-height: 52px; max-width: 220px; margin-bottom: 8px; }
        .po-company h1 { font-size: 22px; font-weight: 800; color: #1B4F72; margin-bottom: 4px; }
        .po-company p { font-size: 12px; color: #666; line-height: 1.5; }
        .po-title { text-align: right; }
        .po-title h2 { font-size: 26px; font-weight: 900; color: #1B4F72; letter-spacing: -0.5px; }
        .po-title .po-number { font-size: 14px; font-weight: 700; color: #333; margin-top: 2px; }
        .po-title .po-date { font-size: 12px; color: #666; margin-top: 4px; }
        .po-title .po-status { display: inline-block; padding: 3px 12px; border-radius: 4px; font-size: 11px; font-weight: 700; text-transform: uppercase; margin-top: 6px; }
        .status-draft { background: #eee; color: #666; }
        .status-pending { background: #fff3e0; color: #e67e22; }
        .status-approved { background: #e3f2fd; color: #1B4F72; }
        .status-partial_received { background: #fff8e1; color: #f57f17; }
        .status-received { background: #e8f5e9; color: #1e8449; }
        .status-cancelled { background: #fce4ec; color: #c0392b; }

        /* Info Boxes */
        .po-info-row { display: flex; gap: 24px; margin-bottom: 24px; }
        .po-info-box { flex: 1; background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 6px; padding: 14px 16px; }
        .po-info-box h4 { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #1B4F72; margin-bottom: 8px; border-bottom: 1px solid #dee2e6; padding-bottom: 6px; }
        .po-info-box p { font-size: 12px; line-height: 1.6; color: #333; }
        .po-info-box .name { font-weight: 700; font-size: 13px; }

        /* Items Table */
        .po-items { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .po-items thead th { background: #1B4F72; color: #fff; font-size: 11px; font-weight: 700; text-transform: uppercase; padding: 10px 12px; text-align: left; }
        .po-items thead th.text-center { text-align: center; }
        .po-items thead th.text-end { text-align: right; }
        .po-items tbody td { padding: 10px 12px; border-bottom: 1px solid #e9ecef; font-size: 12px; vertical-align: top; }
        .po-items tbody td.text-center { text-align: center; }
        .po-items tbody td.text-end { text-align: right; }
        /* Money and counts must never break: with no widths set, a long
           product name took the space and "BDT 1,310" wrapped onto two
           lines. Fixing the numeric columns leaves the product name as the
           only thing that wraps, which is where the slack belongs. */
        .po-items td.text-end, .po-items td.text-center,
        .po-items th.text-end, .po-items th.text-center { white-space: nowrap; }
        .po-items col.col-idx { width: 28px; }
        .po-items col.col-qty { width: 46px; }
        .po-items col.col-tax { width: 52px; }
        .po-items col.col-money { width: 94px; }
        .po-items td.amount { font-weight: 700; }
        .po-items .product-name { font-weight: 700; font-size: 12px; }
        .po-items .product-variant { font-size: 11px; color: #666; }
        .po-items .product-sku { font-size: 10px; color: #999; font-family: monospace; }

        /* Totals */
        .po-totals { display: flex; justify-content: flex-end; margin-bottom: 24px; }
        .po-totals-box { width: 280px; }
        .po-totals-row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 12px; }
        .po-totals-row.total { border-top: 2px solid #1B4F72; margin-top: 6px; padding-top: 10px; font-size: 15px; font-weight: 800; color: #1B4F72; }
        .po-totals-row .label { color: #666; }
        .po-totals-row .value { font-weight: 600; }
        .po-totals-row.paid .value { color: #1e8449; }
        .po-totals-row.due .value { color: #c0392b; font-weight: 800; }

        /* Notes */
        .po-notes { background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 6px; padding: 14px 16px; margin-bottom: 24px; }
        .po-notes h4 { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #1B4F72; margin-bottom: 6px; }
        .po-notes p { font-size: 12px; color: #555; line-height: 1.6; }

        /* Footer */
        .po-footer { display: flex; justify-content: space-between; align-items: flex-end; margin-top: 40px; padding-top: 20px; }
        .po-signature { text-align: center; min-width: 180px; }
        .po-signature .line { border-top: 1px solid #333; margin-bottom: 6px; }
        .po-signature .title { font-size: 11px; color: #666; }
        .po-footer-info { font-size: 10px; color: #999; text-align: center; }

        /* Print */
        @media print {
            body { padding: 0; margin: 0; }
            @page { margin: 0; }
      body { margin: 15mm 10mm; }
        }

        /* Screen-only: print button */
        .po-print-actions { text-align: center; margin-bottom: 20px; }
        .po-print-actions button { padding: 10px 24px; font-size: 14px; font-weight: 700; border: none; border-radius: 6px; cursor: pointer; }
        .po-print-btn { background: #1B4F72; color: #fff; margin-right: 8px; }
        .po-print-btn:hover { background: #154360; }
        .po-close-btn { background: #e9ecef; color: #333; }
        .po-close-btn:hover { background: #dee2e6; }
        @media print { .po-print-actions { display: none; } }
    </style>
</head>
<body>

    <!-- Print Actions (screen only) -->
    @if(!($isPdf ?? false))
    <div class="po-print-actions">
        <button class="po-print-btn" onclick="window.print()"><i class="fa-solid fa-print"></i> Print</button>
        <button class="po-close-btn" onclick="window.close()">Close</button>
    </div>
    @endif

    <!-- Header -->
    <div class="po-header">
        <div class="po-company">
            @if (!empty($settings['logo_url']))
                <img src="{{ $settings['logo_url'] }}" alt="{{ $settings['company_name'] }}" class="po-logo">
            @endif
            <h1>{{ $settings['company_name'] }}</h1>
            @if($settings['address'])<p>{{ $settings['address'] }}</p>@endif
            @if($settings['phone'])<p>Phone: {{ $settings['phone'] }}</p>@endif
            @if($settings['email'])<p>Email: {{ $settings['email'] }}</p>@endif
        </div>
        <div class="po-title">
            <h2>PURCHASE ORDER</h2>
            <div class="po-number">{{ $purchase->po_number }}</div>
            <div class="po-date">Date: {{ $purchase->po_date->format('d M Y') }}</div>
            <span class="po-status status-{{ $purchase->status }}">{{ str_replace('_', ' ', $purchase->status) }}</span>
        </div>
    </div>

    <!-- Supplier & Order Info -->
    <div class="po-info-row">
        <div class="po-info-box">
            <h4>Supplier</h4>
            @if($purchase->supplier)
                <p class="name">{{ $purchase->supplier->company_name }}</p>
                @if($purchase->supplier->contact_person)<p>Attn: {{ $purchase->supplier->contact_person }}</p>@endif
                @if($purchase->supplier->phone)<p>Phone: {{ $purchase->supplier->phone }}</p>@endif
                @if($purchase->supplier->email)<p>Email: {{ $purchase->supplier->email }}</p>@endif
                @if($purchase->supplier->address)<p>{{ $purchase->supplier->address }}</p>@endif
            @else
                <p>N/A</p>
            @endif
        </div>
        <div class="po-info-box">
            <h4>Order Details</h4>
            @if($purchase->expected_delivery)<p>Expected Delivery: <strong>{{ $purchase->expected_delivery->format('d M Y') }}</strong></p>@endif
            <p>Payment Terms: <strong>{{ str_replace('_', ' ', ucfirst($purchase->payment_terms ?? 'N/A')) }}</strong></p>
            @if($purchase->branch)<p>Branch: <strong>{{ $purchase->branch->name }}</strong></p>@endif
            <p>Created By: <strong>{{ $purchase->createdBy->name ?? 'N/A' }}</strong></p>
            @if($purchase->approvedBy)<p>Approved By: <strong>{{ $purchase->approvedBy->name }}</strong></p>@endif
            @if($purchase->supplier_invoice_ref)<p>Supplier Ref: <strong>{{ $purchase->supplier_invoice_ref }}</strong></p>@endif
        </div>
    </div>

    <!-- Items -->
    <table class="po-items">
        <colgroup>
            <col class="col-idx">
            <col>
            <col class="col-qty">
            <col class="col-money">
            <col class="col-money">
            <col class="col-tax">
            <col class="col-money">
        </colgroup>
        <thead>
            <tr>
                <th>#</th>
                <th>Product</th>
                <th class="text-center">Qty</th>
                <th class="text-end">Unit Price</th>
                <th class="text-end">Discount</th>
                <th class="text-center">Tax</th>
                <th class="text-end">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($purchase->items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>
                    <div class="product-name">{{ $item->product->name ?? 'Unknown' }}</div>
                    @if($item->variant)
                        <div class="product-variant">{{ $item->variant->variant_name }}</div>
                    @endif
                    <div class="product-sku">{{ $item->variant ? $item->variant->sku : ($item->product->sku ?? '') }}</div>
                </td>
                <td class="text-center">{{ intval($item->quantity) }}</td>
                <td class="text-end">{{ currency_symbol() }} {{ number_format($item->unit_price, 0) }}</td>
                <td class="text-end">{{ currency_symbol() }} {{ number_format($item->discount_amount, 0) }}</td>
                <td class="text-center">{{ number_format($item->tax_rate, 0) }}%</td>
                <td class="text-end amount">{{ currency_symbol() }} {{ number_format($item->line_total, 0) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totals -->
    <div class="po-totals">
        <div class="po-totals-box">
            <div class="po-totals-row">
                <span class="label">Subtotal</span>
                <span class="value">{{ currency_symbol() }} {{ number_format($purchase->subtotal, 0) }}</span>
            </div>
            @if($purchase->discount_amount > 0)
            <div class="po-totals-row">
                <span class="label">Discount</span>
                <span class="value">- {{ currency_symbol() }} {{ number_format($purchase->discount_amount, 0) }}</span>
            </div>
            @endif
            @if($purchase->tax_amount > 0)
            <div class="po-totals-row">
                <span class="label">VAT / Tax</span>
                <span class="value">{{ currency_symbol() }} {{ number_format($purchase->tax_amount, 0) }}</span>
            </div>
            @endif
            @if($purchase->shipping_cost > 0)
            <div class="po-totals-row">
                <span class="label">Shipping</span>
                <span class="value">{{ currency_symbol() }} {{ number_format($purchase->shipping_cost, 0) }}</span>
            </div>
            @endif
            <div class="po-totals-row total">
                <span>Grand Total</span>
                <span>{{ currency_symbol() }} {{ number_format($purchase->grand_total, 0) }}</span>
            </div>
            @if($purchase->paid_amount > 0)
            <div class="po-totals-row paid">
                <span class="label">Paid</span>
                <span class="value">{{ currency_symbol() }} {{ number_format($purchase->paid_amount, 0) }}</span>
            </div>
            @endif
            @if($purchase->due_amount > 0)
            <div class="po-totals-row due">
                <span class="label">Balance Due</span>
                <span class="value">{{ currency_symbol() }} {{ number_format($purchase->due_amount, 0) }}</span>
            </div>
            @endif
        </div>
    </div>

    <!-- Notes -->
    @if($purchase->notes)
    <div class="po-notes">
        <h4>Notes / Instructions</h4>
        <p>{{ $purchase->notes }}</p>
    </div>
    @endif

    <!-- Footer: Signatures -->
    <div class="po-footer">
        <div class="po-signature">
            <div class="line"></div>
            <div class="title">Prepared By</div>
        </div>
        <div class="po-footer-info">
            <p>{{ $settings['company_name'] }} &middot; {{ $purchase->po_number }}</p>
            <p>Printed on {{ now()->format('d M Y, h:i A') }}</p>
        </div>
        <div class="po-signature">
            <div class="line"></div>
            <div class="title">Authorized Signature</div>
        </div>
    </div>

</body>
</html>
