<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Credit Note {{ $creditNote->cn_number }} — Mushak 6.5</title>
    <style>
        /* =============================================
           BizPOS Pro — Credit Note Print Layout
           Mushak 6.5 (NBR Bangladesh Compliance)
        ============================================= */

        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            color: #111;
            background: #fff;
            padding: 20px;
        }

        .cn-print-page {
            max-width: 800px;
            margin: 0 auto;
        }

        /* ---- Header ---- */
        .cn-header {
            border-bottom: 2px solid #111;
            padding-bottom: 12px;
            margin-bottom: 12px;
        }

        .cn-header-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .cn-company-name {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 3px;
        }

        .cn-company-info {
            font-size: 11px;
            color: #444;
            line-height: 1.6;
        }

        .cn-title-block {
            text-align: right;
        }

        .cn-title-block .cn-mushak-label {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #555;
            margin-bottom: 2px;
        }

        .cn-title-block .cn-doc-title {
            font-size: 20px;
            font-weight: 700;
            color: #111;
            margin-bottom: 4px;
        }

        .cn-title-block .cn-number-badge {
            font-size: 13px;
            font-weight: 700;
            border: 1px solid #111;
            display: inline-block;
            padding: 2px 10px;
        }

        /* ---- Meta Info ---- */
        .cn-meta {
            display: flex;
            gap: 16px;
            margin-bottom: 14px;
        }

        .cn-meta-col {
            flex: 1;
            border: 1px solid #ccc;
            padding: 8px 10px;
        }

        .cn-meta-col h4 {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #555;
            border-bottom: 1px solid #ddd;
            padding-bottom: 4px;
            margin-bottom: 6px;
        }

        .cn-meta-row {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            margin-bottom: 3px;
        }

        .cn-meta-row .label {
            font-weight: 600;
            color: #555;
            min-width: 90px;
        }

        .cn-meta-row .value {
            text-align: right;
            font-weight: 400;
        }

        /* ---- Items Table ---- */
        .cn-items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 11px;
        }

        .cn-items-table thead tr {
            background: #f0f0f0;
        }

        .cn-items-table th,
        .cn-items-table td {
            border: 1px solid #bbb;
            padding: 5px 7px;
            vertical-align: top;
        }

        .cn-items-table th {
            font-weight: 700;
            font-size: 10px;
            text-transform: uppercase;
            text-align: center;
        }

        .cn-items-table td.text-right {
            text-align: right;
        }

        .cn-items-table td.text-center {
            text-align: center;
        }

        .cn-items-table tfoot tr td {
            font-weight: 700;
            background: #f9f9f9;
        }

        .cn-items-table tfoot .totals-label {
            text-align: right;
            font-size: 11px;
        }

        .cn-items-table tfoot .grand-total-row td {
            background: #eaeaea;
            font-size: 12px;
        }

        /* ---- Totals Block ---- */
        .cn-totals-block {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 16px;
        }

        .cn-totals-inner {
            width: 260px;
            border: 1px solid #bbb;
        }

        .cn-totals-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 10px;
            font-size: 11px;
            border-bottom: 1px solid #ddd;
        }

        .cn-totals-row:last-child {
            border-bottom: none;
            font-weight: 700;
            font-size: 13px;
            background: #eaeaea;
        }

        .cn-totals-row .tl {
            font-weight: 600;
            color: #555;
        }

        /* ---- Reason Box ---- */
        .cn-reason-box {
            border: 1px solid #ccc;
            padding: 8px 10px;
            margin-bottom: 16px;
            font-size: 11px;
        }

        .cn-reason-box strong {
            display: block;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #555;
            margin-bottom: 4px;
        }

        /* ---- Amount in Words ---- */
        .cn-amount-words {
            border: 1px solid #ccc;
            padding: 7px 10px;
            margin-bottom: 16px;
            font-size: 11px;
        }

        .cn-amount-words strong {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #555;
        }

        /* ---- Signatures ---- */
        .cn-signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            gap: 16px;
        }

        .cn-sig-block {
            flex: 1;
            text-align: center;
        }

        .cn-sig-line {
            border-top: 1px solid #111;
            margin-bottom: 5px;
        }

        .cn-sig-title {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #444;
        }

        .cn-sig-name {
            font-size: 11px;
            color: #222;
            margin-top: 2px;
        }

        /* ---- Footer ---- */
        .cn-print-footer {
            margin-top: 20px;
            padding-top: 8px;
            border-top: 1px solid #ccc;
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            color: #888;
        }

        /* ---- Print Media ---- */
        @media print {
            body {
                padding: 10px;
            }

            .cn-no-print {
                display: none !important;
            }

            @page {
                margin: 0;
                size: A4 portrait;
            }
            body { margin: 15mm 10mm; }
        }
    </style>
</head>
<body>

@php
    $subtotal   = $creditNote->items->sum(fn($item) => $item->quantity * $item->unit_price);
    $totalTax   = $creditNote->items->sum('tax_amount');
    $grandTotal = $subtotal + $totalTax;
@endphp

{{-- Print Button (hidden on actual print) --}}
@if(!($isPdf ?? false))
<div class="cn-no-print" style="margin-bottom:16px;">
    <button onclick="window.print()" style="padding:8px 20px;font-size:13px;cursor:pointer;border:1px solid #333;background:#fff;font-weight:700;">
        &#128438; Print / Save as PDF
    </button>
    <button onclick="window.close()" style="margin-left:8px;padding:8px 16px;font-size:13px;cursor:pointer;border:1px solid #aaa;background:#fff;">
        Close
    </button>
</div>
@endif

<div class="cn-print-page">

    {{-- Company Header --}}
    <div class="cn-header">
        <div class="cn-header-top">
            <div>
                <div class="cn-company-name">{{ config('app.company_name', 'Your Company Name') }}</div>
                <div class="cn-company-info">
                    {{ config('app.company_address', 'Dhaka, Bangladesh') }}<br>
                    BIN: {{ config('app.company_bin', 'N/A') }} &nbsp;|&nbsp;
                    VAT Reg: {{ config('app.company_vat', 'N/A') }}<br>
                    Tel: {{ config('app.company_phone', '') }} &nbsp;|&nbsp;
                    Email: {{ config('app.company_email', '') }}
                </div>
            </div>
            <div class="cn-title-block">
                <div class="cn-mushak-label">Mushak Form 6.5</div>
                <div class="cn-doc-title">Credit Note</div>
                <div class="cn-number-badge">{{ $creditNote->cn_number }}</div>
            </div>
        </div>
    </div>

    {{-- CN Meta: Issue Details + Customer --}}
    <div class="cn-meta">
        <div class="cn-meta-col">
            <h4>Credit Note Details</h4>
            <div class="cn-meta-row">
                <span class="label">CN Number:</span>
                <span class="value">{{ $creditNote->cn_number }}</span>
            </div>
            <div class="cn-meta-row">
                <span class="label">Issue Date:</span>
                <span class="value">{{ $creditNote->issue_date->format('d M Y') }}</span>
            </div>
            @if($creditNote->sale_id)
            <div class="cn-meta-row">
                <span class="label">Against Sale:</span>
                <span class="value">{{ $creditNote->sale_id }}</span>
            </div>
            @endif
            <div class="cn-meta-row d-none">
                <span class="label">Branch:</span>
                <span class="value">{{ $creditNote->branch->name ?? 'N/A' }}</span>
            </div>
            <div class="cn-meta-row">
                <span class="label">Issued By:</span>
                <span class="value">{{ $creditNote->creator->name ?? 'N/A' }}</span>
            </div>
        </div>

        <div class="cn-meta-col">
            <h4>Customer / Buyer</h4>
            <div class="cn-meta-row">
                <span class="label">Name:</span>
                <span class="value">{{ $creditNote->customer_name ?? 'N/A' }}</span>
            </div>
            <div class="cn-meta-row">
                <span class="label">BIN:</span>
                <span class="value">{{ $creditNote->customer_bin ?? 'N/A' }}</span>
            </div>
            <div class="cn-meta-row">
                <span class="label">Address:</span>
                <span class="value">{{ $creditNote->customer_address ?? 'N/A' }}</span>
            </div>
            <div class="cn-meta-row">
                <span class="label">Phone:</span>
                <span class="value">{{ $creditNote->customer_phone ?? 'N/A' }}</span>
            </div>
        </div>
    </div>

    {{-- Reason for Credit Note --}}
    <div class="cn-reason-box">
        <strong>Reason for Credit Note:</strong>
        {{ $creditNote->reason }}
    </div>

    {{-- Items Table --}}
    <table class="cn-items-table">
        <thead>
            <tr>
                <th style="width:32px;">Sl.</th>
                <th>Description of Goods / Services</th>
                <th style="width:70px;">Qty</th>
                <th style="width:80px;">Unit</th>
                <th style="width:95px;">Unit Price ({{ currency_symbol() }})</th>
                <th style="width:85px;">VAT / Tax ({{ currency_symbol() }})</th>
                <th style="width:100px;">Total ({{ currency_symbol() }})</th>
            </tr>
        </thead>
        <tbody>
            @foreach($creditNote->items as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>
                    <strong>{{ $item->product->name ?? '—' }}</strong>
                    @if($item->description)
                    <br><span style="color:#555;font-size:10px;">{{ $item->description }}</span>
                    @endif
                </td>
                <td class="text-right">{{ num($item->quantity) }}</td>
                <td class="text-center">{{ $item->product->unit ?? 'Pcs' }}</td>
                <td class="text-right">{{ num($item->unit_price) }}</td>
                <td class="text-right">{{ num($item->tax_amount) }}</td>
                <td class="text-right">{{ num($item->quantity * $item->unit_price + $item->tax_amount) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4"></td>
                <td class="totals-label">Subtotal:</td>
                <td></td>
                <td class="text-right">{{ num($subtotal) }}</td>
            </tr>
            <tr>
                <td colspan="4"></td>
                <td class="totals-label">Total VAT / Tax:</td>
                <td></td>
                <td class="text-right">{{ num($totalTax) }}</td>
            </tr>
            <tr class="grand-total-row">
                <td colspan="4"></td>
                <td class="totals-label" style="font-size:12px;">Grand Total:</td>
                <td></td>
                <td class="text-right" style="font-size:13px;">{{ money($grandTotal) }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- Additional Notes --}}
    @if($creditNote->notes)
    <div class="cn-reason-box">
        <strong>Additional Notes:</strong>
        {{ $creditNote->notes }}
    </div>
    @endif

    {{-- Signatures --}}
    <div class="cn-signatures">
        <div class="cn-sig-block">
            <div style="height:40px;"></div>
            <div class="cn-sig-line"></div>
            <div class="cn-sig-title">Prepared By</div>
            <div class="cn-sig-name">{{ $creditNote->creator->name ?? '' }}</div>
        </div>
        <div class="cn-sig-block">
            <div style="height:40px;"></div>
            <div class="cn-sig-line"></div>
            <div class="cn-sig-title">Checked By</div>
            <div class="cn-sig-name">&nbsp;</div>
        </div>
        <div class="cn-sig-block">
            <div style="height:40px;"></div>
            <div class="cn-sig-line"></div>
            <div class="cn-sig-title">Authorized Signatory</div>
            <div class="cn-sig-name">&nbsp;</div>
        </div>
        <div class="cn-sig-block">
            <div style="height:40px;"></div>
            <div class="cn-sig-line"></div>
            <div class="cn-sig-title">Customer Acknowledgement</div>
            <div class="cn-sig-name">&nbsp;</div>
        </div>
    </div>

    {{-- Print Footer --}}
    <div class="cn-print-footer">
        <span>{{ $creditNote->cn_number }} &mdash; Printed: {{ now()->format('d M Y, h:i A') }}</span>
        <span>Mushak Form 6.5 &mdash; National Board of Revenue, Bangladesh</span>
        <span>{{ \Modules\Setting\Models\Setting::get('business', 'company_name', 'BizPOS Pro') }}</span>
    </div>

</div>

@if(!($isPdf ?? false))
<script>
'use strict';
window.onload = function () {
    window.print();
};
</script>
@endif

</body>
</html>
