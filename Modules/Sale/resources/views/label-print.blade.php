{{--
  Courier shipping labels (4x6in thermal). Self-contained page (no layout) —
  opened in a new tab from the Sales list, single (auto-print) or bulk.
  Each label: business logo + name, invoice-number barcode, and the
  Name / Phone / Address / Paid / COD address block.
--}}
@php
    use Modules\Setting\Models\Setting;
    $bizName = Setting::get('business', 'company_name', config('app.name', 'BizPOS Pro'));
    $logoUrl = upload_url(Setting::get('business', 'logo'));

    // Plain amount: drop ".00" on whole numbers, keep 2 decimals otherwise.
    $amount = static fn($v): string => number_format((float) $v, fmod((float) $v, 1.0) === 0.0 ? 0 : 2);
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Shipping Labels') }}</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
            margin: 0;
            background: #e9ecef;
            color: #1a1a1a;
        }

        /* One 4x6 inch label */
        .label {
            width: 4in;
            background: #fff;
            margin: 16px auto;
            padding: 12px;
            position: relative;
        }

        .label-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .btn-print {
            background: #1E8449;
            color: #fff;
            border: 0;
            padding: 6px 14px;
            font-size: 13px;
            font-weight: 700;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-print-all {
            background: #1B4F72;
            color: #fff;
            border: 0;
            padding: 8px 18px;
            font-size: 14px;
            font-weight: 700;
            border-radius: 4px;
            cursor: pointer;
        }

        .toolbar-all {
            text-align: center;
            margin: 16px 0;
        }

        .label-head {
            text-align: center;
            border-bottom: 1px dashed #999;
            padding-bottom: 8px;
        }

        .label-logo {
            max-height: 46px;
            max-width: 45%;
            object-fit: contain;
            margin-bottom: 10px;
        }

        .label-biz {
            font-size: 14px;
            font-weight: 800;
            margin: 4px 0 2px;
        }

        .label-invoice {
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .label-barcode {
            display: block;
            margin: 4px auto 0;
            max-width: 100%;
        }

        .label-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 13px;
        }

        .label-table th,
        .label-table td {
            border: 1px solid #333;
            padding: 6px 8px;
            text-align: left;
            vertical-align: top;
        }

        .label-table th {
            width: 34%;
            font-weight: 700;
            background: #f3f3f3;
            white-space: nowrap;
        }

        .label-empty {
            text-align: center;
        }

        @media print {
            body {
                background: #fff;
            }

            .no-print {
                display: none !important;
            }

            .label {
                margin: 0;
                padding: 10px;
                min-height: 6in;
                page-break-after: always;
            }

            .label:last-child {
                page-break-after: auto;
            }

            /* When printing a single isolated label, hide the others. */
            body.printing-one .label:not(.is-printing) {
                display: none !important;
            }

            @page {
                size: 4in 6in;
                margin: 0;
            }
        }
    </style>
</head>

<body>

    @if ($sales->count() > 1)
        <div class="toolbar-all no-print">
            <button type="button" class="btn-print-all" onclick="window.print()">
                {{ __('Print All') }} ({{ $sales->count() }})
            </button>
        </div>
    @endif

    @forelse($sales as $sale)
        @php
            $name = $sale->customer?->name ?? ($sale->customer_name_snapshot ?? __('Walk-in Customer'));
            $phone = $sale->customer?->phone ?? ($sale->customer_phone_snapshot ?? '');
            $address = $sale->customer_address ?? '';
        @endphp
        <div class="label" id="label-{{ $sale->id }}">
            <div class="label-toolbar no-print">
                <button type="button" class="btn-print" onclick="printLabel('label-{{ $sale->id }}')">
                    {{ __('Print') }}
                </button>
            </div>

            <div class="label-head">
                @if ($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $bizName }}" class="label-logo">
                @endif
                <div class="label-biz">{{ $bizName }}</div>
                <div class="label-invoice">{{ __('Invoice ID') }}: {{ $sale->invoice_number }}</div>
                <svg class="label-barcode" data-code="{{ $sale->invoice_number }}"></svg>
            </div>

            <table class="label-table">
                <tr>
                    <th>{{ __('Name') }}:</th>
                    <td>{{ $name }}</td>
                </tr>
                <tr>
                    <th>{{ __('Phone') }}:</th>
                    <td>{{ $phone }}</td>
                </tr>
                <tr>
                    <th>{{ __('Address') }}:</th>
                    <td>{{ $address }}</td>
                </tr>
                <tr>
                    <th>{{ __('Paid/Less') }}:</th>
                    <td>{{ $amount($sale->paid_amount) }}</td>
                </tr>
                <tr>
                    <th>{{ __('COD') }}:</th>
                    <td>{{ $amount($sale->due_amount) }}</td>
                </tr>
            </table>
        </div>
    @empty
        <div class="label">
            <p class="label-empty">{{ __('No sales selected.') }}</p>
        </div>
    @endforelse

    <script src="{{ asset('vendor/jsbarcode/jsbarcode.min.js') }}"></script>
    <script>
        'use strict';

        // Render every label's barcode from its invoice number.
        document.querySelectorAll('.label-barcode').forEach(function(el) {
            var code = el.getAttribute('data-code') || '';
            if (!code) return;
            try {
                JsBarcode(el, code, {
                    format: 'CODE128',
                    displayValue: false,
                    height: 45,
                    width: 1.6,
                    margin: 0
                });
            } catch (e) {
                /* leave blank on failure */
            }
        });

        // Print a single label by isolating it (others hidden via print CSS).
        function printLabel(id) {
            var el = document.getElementById(id);
            if (!el) return;
            document.body.classList.add('printing-one');
            el.classList.add('is-printing');
            window.print();
        }

        window.addEventListener('afterprint', function() {
            document.body.classList.remove('printing-one');
            document.querySelectorAll('.label.is-printing').forEach(function(el) {
                el.classList.remove('is-printing');
            });
        });

        @if ($autoPrint ?? false)
            // Single-label view: open the print dialog automatically once the
            // barcode has rendered.
            window.addEventListener('load', function() {
                setTimeout(function() {
                    window.print();
                }, 250);
            });
        @endif
    </script>
</body>

</html>
