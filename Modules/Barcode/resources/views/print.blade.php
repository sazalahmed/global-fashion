<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print Barcode Labels - {{ \Modules\Setting\Models\Setting::get('business', 'company_name', 'BizPOS Pro') }}</title>
    <link href="{{ asset('vendor/nunito-sans/nunito-sans.css') }}" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Nunito Sans', sans-serif;
            background: #fff;
        }
        .label-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            padding: 8px;
        }
        .label {
            border: 1px dashed #ccc;
            padding: 6px 8px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            page-break-inside: avoid;
        }
        .label-name {
            font-size: 9px;
            font-weight: 700;
            line-height: 1.2;
            max-height: 20px;
            overflow: hidden;
            margin-bottom: 2px;
        }
        .label svg {
            display: block;
            margin: 0 auto;
        }
        .label-code {
            font-size: 8px;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-top: 1px;
        }
        .label-price {
            font-size: 10px;
            font-weight: 800;
            margin-top: 2px;
        }
        .label-business {
            font-size: 7px;
            font-weight: 600;
            color: #666;
            margin-bottom: 1px;
        }

        /* Label sizes */
        .size-38x25 .label { width: 38mm; height: 25mm; }
        .size-50x25 .label { width: 50mm; height: 25mm; }
        .size-50x30 .label { width: 50mm; height: 30mm; }

        @media print {
            body { background: #fff; }
            .label { border: none; }
            .no-print { display: none; }
            @page { margin: 0; }
            body { margin: 5mm; }
        }
    </style>
</head>
<body>
    @if(!($isPdf ?? false))
    <div class="no-print" style="padding:10px;background:#f0f0f0;text-align:center;font-size:13px;">
        <button onclick="window.print()" style="padding:8px 20px;font-size:14px;font-weight:700;cursor:pointer;border:none;background:#1B4F72;color:#fff;border-radius:4px;">
            <i class="fa-solid fa-print"></i> Print Now
        </button>
        <span style="margin-left:10px;color:#666;">{{ collect($quantities)->sum() }} labels ready</span>
    </div>
    @endif

    @php
        $labelSize = $settings['label_size'] ?? '50x25';
        $showName = ($settings['show_name'] ?? '1') == '1';
        $showPrice = ($settings['show_price'] ?? '1') == '1';
        $showBusiness = ($settings['show_business'] ?? '0') == '1';
        $barcodeType = $settings['barcode_type'] ?? 'code128';
    @endphp

    <div class="label-grid size-{{ $labelSize }}">
        @foreach($products as $product)
            @php $qty = $quantities[$product->id] ?? 1; @endphp
            @for($i = 0; $i < $qty; $i++)
            <div class="label">
                @if($showBusiness)
                    <div class="label-business">{{ \Modules\Setting\Models\Setting::get('business', 'company_name', 'BizPOS Pro') }}</div>
                @endif
                @if($showName)
                    <div class="label-name">{{ Str::limit($product->name, 30) }}</div>
                @endif
                <svg class="barcode-svg" data-value="{{ $product->barcode ?? $product->sku }}" data-format="{{ $barcodeType }}"></svg>
                @if($showPrice)
                    <div class="label-price">{{ currency_symbol() }} {{ $product->formatted_sell_price }}</div>
                @endif
            </div>
            @endfor
        @endforeach
    </div>

    <script src="{{ asset('vendor/jsbarcode/jsbarcode.min.js') }}"></script>
    <script>
    'use strict';
    document.querySelectorAll('.barcode-svg').forEach(function(svg) {
        var value = svg.getAttribute('data-value');
        var format = svg.getAttribute('data-format') || 'CODE128';

        var formatMap = {
            'code128': 'CODE128',
            'ean13': 'EAN13',
            'upc': 'UPC',
            'qrcode': 'CODE128'
        };

        try {
            JsBarcode(svg, value, {
                format: formatMap[format] || 'CODE128',
                width: 1.5,
                height: 35,
                displayValue: true,
                fontSize: 10,
                font: 'Nunito Sans',
                fontOptions: '600',
                margin: 0,
                textMargin: 1
            });
        } catch (e) {
            JsBarcode(svg, value, {
                format: 'CODE128',
                width: 1.5,
                height: 35,
                displayValue: true,
                fontSize: 10,
                font: 'Nunito Sans',
                margin: 0,
                textMargin: 1
            });
        }
    });

    @if(!($isPdf ?? false))
    window.onload = function() {
        setTimeout(function() { window.print(); }, 500);
    };
    @endif
    </script>
</body>
</html>
