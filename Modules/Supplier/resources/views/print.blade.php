<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier — {{ $supplier->company_name }}</title>
    <link href="{{ asset('vendor/nunito-sans/nunito-sans.css') }}" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Nunito Sans', sans-serif; font-size: 13px; color: #1a1a1a; padding: 30px 40px; max-width: 800px; margin: 0 auto; }

        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 3px solid #1B4F72; }
        .header h1 { font-size: 20px; font-weight: 800; color: #1B4F72; }
        .header p { font-size: 12px; color: #666; }
        .header .title { text-align: right; }
        .header .title h2 { font-size: 18px; font-weight: 800; color: #333; }
        .header .title p { font-size: 11px; color: #999; }

        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px; }
        .info-box { background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 6px; padding: 14px 16px; }
        .info-box h4 { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #1B4F72; margin-bottom: 8px; border-bottom: 1px solid #dee2e6; padding-bottom: 6px; }
        .info-box .row { display: flex; justify-content: space-between; padding: 3px 0; font-size: 12px; }
        .info-box .row .label { color: #666; }
        .info-box .row .value { font-weight: 700; }

        .stats { display: flex; gap: 16px; margin-bottom: 24px; }
        .stat { flex: 1; text-align: center; background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 6px; padding: 14px; }
        .stat .label { font-size: 10px; font-weight: 700; text-transform: uppercase; color: #666; }
        .stat .value { font-size: 18px; font-weight: 800; color: #1B4F72; }
        .stat .value.danger { color: #c0392b; }
        .stat .value.success { color: #1e8449; }

        h3 { font-size: 14px; font-weight: 800; color: #1B4F72; margin-bottom: 10px; padding-bottom: 6px; border-bottom: 1px solid #dee2e6; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        thead th { background: #1B4F72; color: #fff; font-size: 11px; font-weight: 700; text-transform: uppercase; padding: 8px 10px; text-align: left; }
        thead th.text-end { text-align: right; }
        thead th.text-center { text-align: center; }
        tbody td { padding: 8px 10px; border-bottom: 1px solid #e9ecef; font-size: 12px; }
        tbody td.text-end { text-align: right; }
        tbody td.text-center { text-align: center; }
        .fw-700 { font-weight: 700; }
        .text-success { color: #1e8449; }
        .text-danger { color: #c0392b; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: 700; }
        .badge-success { background: #e8f5e9; color: #1e8449; }
        .badge-warning { background: #fff3e0; color: #e67e22; }
        .badge-danger { background: #fce4ec; color: #c0392b; }

        .footer { text-align: center; font-size: 10px; color: #999; margin-top: 30px; padding-top: 16px; border-top: 1px solid #dee2e6; }

        .print-actions { text-align: center; margin-bottom: 20px; }
        .print-actions button { padding: 10px 24px; font-size: 14px; font-weight: 700; border: none; border-radius: 6px; cursor: pointer; }
        .btn-print { background: #1B4F72; color: #fff; margin-right: 8px; }
        .btn-print:hover { background: #154360; }
        .btn-close { background: #e9ecef; color: #333; }
        .btn-close:hover { background: #dee2e6; }
        @media print { .print-actions { display: none; } @page { margin: 0; } body { margin: 15mm 10mm; padding: 0; } }
    </style>
</head>
<body>

    @if(!($isPdf ?? false))
    <div class="print-actions">
        <button class="btn-print" onclick="window.print()">Print</button>
        <button class="btn-close" onclick="window.close()">Close</button>
    </div>
    @endif

    <!-- Header -->
    <div class="header">
        <div>
            <h1>{{ $settings['company_name'] }}</h1>
            @if($settings['address'])<p>{{ $settings['address'] }}</p>@endif
            @if($settings['phone'])<p>Phone: {{ $settings['phone'] }}</p>@endif
        </div>
        <div class="title">
            <h2>SUPPLIER DETAIL</h2>
            <p>Printed on {{ now()->format('d M Y, h:i A') }}</p>
        </div>
    </div>

    <!-- Supplier Info -->
    <div class="info-grid">
        <div class="info-box">
            <h4>Supplier Information</h4>
            <div class="row"><span class="label">Company</span><span class="value">{{ $supplier->company_name }}</span></div>
            @if($supplier->contact_person)<div class="row"><span class="label">Contact</span><span class="value">{{ $supplier->contact_person }}</span></div>@endif
            @if($supplier->phone)<div class="row"><span class="label">Phone</span><span class="value">{{ $supplier->phone }}</span></div>@endif
            @if($supplier->email)<div class="row"><span class="label">Email</span><span class="value">{{ $supplier->email }}</span></div>@endif
            @if($supplier->address)<div class="row"><span class="label">Address</span><span class="value">{{ $supplier->address }}</span></div>@endif
        </div>
        <div class="info-box">
            <h4>Business Details</h4>
            <div class="row"><span class="label">Payment Terms</span><span class="value">{{ $supplier->payment_terms ?? 'N/A' }}</span></div>
            <div class="row"><span class="label">Credit Limit</span><span class="value">{{ currency_symbol() }} {{ number_format($supplier->credit_limit ?? 0, 0) }}</span></div>
            @if($supplier->tin)<div class="row"><span class="label">TIN</span><span class="value">{{ $supplier->tin }}</span></div>@endif
            @if($supplier->bank_name)<div class="row"><span class="label">Bank</span><span class="value">{{ $supplier->bank_name }}</span></div>@endif
            @if($supplier->account_number)<div class="row"><span class="label">Account</span><span class="value">{{ $supplier->account_number }}</span></div>@endif
        </div>
    </div>

    <!-- Stats -->
    <div class="stats">
        <div class="stat">
            <div class="label">Total Purchases</div>
            <div class="value">{{ currency_symbol() }} {{ number_format($supplier->total_purchase, 0) }}</div>
        </div>
        <div class="stat">
            <div class="label">Due Balance</div>
            <div class="value danger">{{ currency_symbol() }} {{ number_format($supplier->due_balance, 0) }}</div>
        </div>
        <div class="stat">
            <div class="label">Total Paid</div>
            <div class="value success">{{ currency_symbol() }} {{ number_format($supplier->total_paid, 0) }}</div>
        </div>
        <div class="stat">
            <div class="label">Advance</div>
            <div class="value">{{ currency_symbol() }} {{ number_format($supplier->advance_balance ?? 0, 0) }}</div>
        </div>
    </div>

    <!-- Purchase History -->
    @if($purchases->isNotEmpty())
    <h3>Purchase History</h3>
    <table>
        <thead>
            <tr>
                <th>PO #</th>
                <th>Date</th>
                <th class="text-end">Total</th>
                <th class="text-end">Paid</th>
                <th class="text-end">Due</th>
                <th class="text-center">Status</th>
                <th class="text-center">Payment</th>
            </tr>
        </thead>
        <tbody>
            @foreach($purchases as $po)
            <tr>
                <td class="fw-700">{{ $po->po_number }}</td>
                <td>{{ $po->po_date->format('d M Y') }}</td>
                <td class="text-end fw-700">{{ currency_symbol() }} {{ number_format($po->grand_total, 0) }}</td>
                <td class="text-end text-success">{{ currency_symbol() }} {{ number_format($po->paid_amount, 0) }}</td>
                <td class="text-end text-danger">{{ currency_symbol() }} {{ number_format($po->due_amount, 0) }}</td>
                <td class="text-center"><span class="badge badge-{{ $po->status === 'cancelled' ? 'danger' : 'success' }}">{{ ucfirst(str_replace('_', ' ', $po->status)) }}</span></td>
                <td class="text-center">
                    @if($po->payment_status === 'paid')
                        <span class="badge badge-success">Paid</span>
                    @elseif($po->payment_status === 'partial')
                        <span class="badge badge-warning">Partial</span>
                    @else
                        <span class="badge badge-danger">Unpaid</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <!-- Payment History -->
    @if($supplier->payments->isNotEmpty())
    <h3>Payment History</h3>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Reference</th>
                <th class="text-end">Amount</th>
                <th>Method</th>
                <th>Note</th>
            </tr>
        </thead>
        <tbody>
            @foreach($supplier->payments->sortByDesc('payment_date') as $payment)
            <tr>
                <td>{{ $payment->payment_date->format('d M Y') }}</td>
                <td>{{ $payment->payment_number }}</td>
                <td class="text-end fw-700 text-success">{{ currency_symbol() }} {{ number_format($payment->amount, 0) }}</td>
                <td>{{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}</td>
                <td>{{ $payment->note ?? '--' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="footer">
        {{ $settings['company_name'] }} &middot; Supplier: {{ $supplier->company_name }} &middot; Generated {{ now()->format('d M Y') }}
    </div>

</body>
</html>
