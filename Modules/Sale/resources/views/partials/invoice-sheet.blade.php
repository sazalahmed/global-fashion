{{--
    Shared invoice document partial.
    Renders a single invoice "sheet" from a normalised $inv array so the exact
    same layout can be used by the admin print page and the storefront print
    popup. Styled by public/css/invoice-sheet.css.

    Expected $inv keys (all money/date values are pre-formatted strings):
      bizName, bizAddress, bizPhone, bizEmail, logoUrl
      billName, billPhone, billAddress
      parcelId, invoiceNumber, invoiceDate
      items[] => [ name, variation, qty, price, amount ]
      subTotal, discount, shipping, total, due
      barcode
--}}
@php
    $inv   = $inv ?? [];
    $items = $inv['items'] ?? [];
@endphp
<div class="inv-doc">

    <div class="inv-head">
        <div class="inv-biz">
            <div class="inv-biz-name">{{ $inv['bizName'] ?? '' }}</div>
            @if(!empty($inv['bizAddress']))<div>{{ $inv['bizAddress'] }}</div>@endif
            @if(!empty($inv['bizPhone']))<div>{{ $inv['bizPhone'] }}</div>@endif
            @if(!empty($inv['bizEmail']))<div>{{ $inv['bizEmail'] }}</div>@endif
        </div>
        @if(!empty($inv['logoUrl']))
            <div class="inv-logo"><img src="{{ $inv['logoUrl'] }}" alt="{{ $inv['bizName'] ?? '' }}"></div>
        @endif
    </div>

    <div class="inv-billbox">
        <div class="bill-left">
            <div class="bill-label">{{ __('Bill To') }}</div>
            <div class="bill-name">{{ $inv['billName'] ?? '' }}</div>
            @if(!empty($inv['billPhone']))<div>{{ $inv['billPhone'] }}</div>@endif
            @if(!empty($inv['billAddress']))<div>{{ $inv['billAddress'] }}</div>@endif
        </div>
        <div class="inv-meta">
            <div class="inv-row"><span class="k">{{ __('Percel ID #') }}</span><span class="v">{{ $inv['parcelId'] ?? '' }}</span></div>
            <div class="inv-row"><span class="k">{{ __('Invoice #') }}</span><span class="v">{{ $inv['invoiceNumber'] ?? '' }}</span></div>
            <div class="inv-row"><span class="k">{{ __('Date') }} :</span><span class="v">{{ $inv['invoiceDate'] ?? '' }}</span></div>
        </div>
    </div>

    <table class="inv-items">
        <thead>
            <tr>
                <th class="col-item">{{ __('Item') }}</th>
                <th class="col-variation">{{ __('Variation') }}</th>
                <th class="col-qty">{{ __('Quantity') }}</th>
                <th class="col-price">{{ __('Price') }}</th>
                <th class="col-amount">{{ __('Amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
                <tr>
                    <td>{{ $item['name'] ?? '—' }}</td>
                    <td>{{ ($item['variation'] ?? '') ?: '—' }}</td>
                    <td class="num">{{ $item['qty'] ?? '' }}</td>
                    <td class="num">{{ $item['price'] ?? '' }}</td>
                    <td class="num">{{ $item['amount'] ?? '' }}</td>
                </tr>
            @empty
                <tr><td colspan="5">{{ __('No items.') }}</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="inv-bottom">
        <div class="inv-barcode">
            <svg class="inv-bc" data-code="{{ $inv['barcode'] ?? '' }}"></svg>
        </div>
        <div class="inv-totals">
            <div class="inv-row"><span class="k">{{ __('Sub Total') }}</span><span class="v">{{ $inv['subTotal'] ?? '' }}</span></div>
            <div class="inv-row"><span class="k">{{ __('Discount') }}</span><span class="v">{{ $inv['discount'] ?? '' }}</span></div>
            <div class="inv-row"><span class="k">{{ __('Shipping') }} (+)</span><span class="v">{{ $inv['shipping'] ?? '' }}</span></div>
            <div class="inv-row"><span class="k">{{ __('Total') }}</span><span class="v">{{ $inv['total'] ?? '' }}</span></div>
            <div class="inv-row due"><span class="k">{{ __('Amount Due') }}</span><span class="v">{{ $inv['due'] ?? '' }}</span></div>
        </div>
    </div>

</div>
