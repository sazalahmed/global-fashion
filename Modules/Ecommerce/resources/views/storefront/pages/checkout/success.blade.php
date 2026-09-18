@extends('ecommerce::storefront.layouts.master')

@section('title', 'Order Confirmed')
@section('breadcrumb_title', 'Order Confirmed')

@section('breadcrumb')
    <li>Order Confirmed</li>
@endsection

@push('styles')
    <link href="{{ asset('css/invoice-sheet.css') }}?v={{ filemtime(public_path('css/invoice-sheet.css')) }}" rel="stylesheet">
    <style>
        .bp-success-body {
            padding: 70px 0 60px;
            background: #f6f7fa;
        }

        .bp-success-state {
            background: #fff;
            border-radius: 14px;
            padding: 35px;
            text-align: center;
            box-shadow: 0 4px 18px rgba(0, 0, 0, .05);
            margin-bottom: 20px;
        }

        .bp-success-check {
            width: 84px;
            height: 84px;
            margin: 0 auto 18px;
            background: #E8F8EF;
            border: 3px solid #1E8449;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: bpPop .5s cubic-bezier(.16, 1.1, .3, 1.1) both;
        }

        @keyframes bpPop {
            from {
                transform: scale(0);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        .bp-success-check svg {
            width: 44px;
            height: 44px;
        }

        .bp-success-state h2 {
            font-weight: 700;
            margin-bottom: 6px;
            font-size: 26px;
            color: #333333;
        }

        .bp-success-state p {
            color: #6B7280;
            margin-bottom: 18px;
        }

        .bp-success-orderno {
            display: inline-block;
            background: #f5f6fa;
            border: 1px solid #e3e6ec;
            border-radius: 30px;
            padding: 9px 22px;
            font-size: 15px;
            color: #333333;
        }

        .bp-success-orderno strong {
            font-weight: 700;
            color: #1B4F72;
        }

        .bp-success-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px 22px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, .04);
            margin-bottom: 20px;
        }

        .bp-success-card h5 {
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 14px;
            color: #333333;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .bp-success-card h5 i {
            color: #117A65;
        }

        .bp-success-item {
            display: flex;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid #eef0f3;
        }

        .bp-success-item:last-child {
            border-bottom: none;
        }

        .bp-success-item-thumb {
            width: 70px;
            height: auto;
            border-radius: 8px;
            flex-shrink: 0;
            overflow: hidden;
        }

        .bp-success-item-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .bp-success-item-body {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .bp-success-item-name {
            font-size: 13.5px;
            font-weight: 600;
            color: #333333;
            line-height: 1.3;
        }

        .bp-success-item-qty {
            font-size: 12px;
            color: #7F8C8D;
            margin-top: 2px;
        }

        .bp-success-item-price {
            font-size: 16px;
            font-weight: 700;
            color: #333333;
            align-self: center;
        }

        .bp-success-totals {
            margin-top: 14px;
        }

        .bp-success-totals .row-line {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            margin-bottom: 6px;
            color: #4a5568;
        }

        .bp-success-totals .row-line.discount {
            color: #1E8449;
        }

        .bp-success-totals .row-line.grand-total {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #eef0f3;
            font-size: 17px;
            font-weight: 700;
            color: #333333;
        }

        .bp-success-info {
            font-size: 14px;
            line-height: 1.55;
            color: #4a5568;
        }

        .bp-success-info strong {
            color: #333333;
        }

        .bp-success-info .row-line {
            margin-bottom: 4px;
        }

        .bp-success-info i {
            color: #7F8C8D;
            margin-right: 6px;
        }

        .bp-success-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .bp-success-badge.cod {
            background: #FEF3C7;
            color: #92400E;
        }

        .bp-success-badge.paid {
            background: #D1FAE5;
            color: #065F46;
        }

        .bp-success-eta {
            background: linear-gradient(135deg, #fff8e1 0%, #fffae0 100%);
            border: 1px solid #f5e9b5;
            border-radius: 10px;
            padding: 14px 16px;
            display: flex;
            gap: 14px;
            align-items: center;
            margin-bottom: 18px;
        }

        .bp-success-eta-icon {
            width: 44px;
            height: 44px;
            background: #f5c542;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #5b4400;
            font-size: 20px;
            flex-shrink: 0;
        }

        .bp-success-eta-body {
            line-height: 1.4;
            font-size: 14px;
        }

        .bp-success-eta strong {
            color: #5b4400;
        }

        .bp-success-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 22px;
        }

        .bp-success-btn {
            padding: 11px 22px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: transform .15s ease, box-shadow .15s ease;
            border: none;
            font-size: 14px;
            cursor: pointer;
        }

        .bp-success-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(0, 0, 0, .12);
        }

        .bp-success-btn.primary {
            background: #1B4F72;
            color: #fff;
        }

        .bp-success-btn.primary:hover {
            background: #2E86C1;
            color: #fff;
        }

        .bp-success-btn.outline {
            background: #fff;
            color: #1B4F72;
            border: 1px solid #1B4F72;
        }

        .bp-success-btn.outline:hover {
            background: #1B4F72;
            color: #fff;
        }

        .bp-success-btn.ghost {
            background: transparent;
            color: #4a5568;
        }

        .bp-success-btn.ghost:hover {
            color: #1B4F72;
        }

        @media (max-width: 576px) {
            .bp-success-state {
                padding: 24px 16px;
            }

            .bp-success-state h2 {
                font-size: 22px;
            }

            .bp-success-check {
                width: 68px;
                height: 68px;
            }

            .bp-success-check svg {
                width: 36px;
                height: 36px;
            }

            .bp-success-card {
                padding: 16px;
            }

            .bp-success-actions .outline {
                padding: 8px 15px;
                font-size: 12px;
            }
        }

        .bp-invoice-print {
            display: none;
        }

        /* Print isolation: the page content (incl. .bp-invoice-print) is yielded
           directly into <body>, so hide every top-level block and show only the
           invoice sheet. Using display:none (not visibility) means the hidden
           order summary contributes no height — so no trailing blank page.
           @page margin:0 removes the browser's own header/footer (date, title,
           URL, page number); page margins come from the sheet's own padding. */
        @media print {
            @page {
                size: A4;
                margin: 0;
            }

            body {
                background: #fff !important;
            }

            body > * {
                display: none !important;
            }

            body > .bp-invoice-print {
                display: block !important;
            }
        }
    </style>
@endpush

@section('content')
    @if (isset($order) && $order)
        <section class="bp-success-body">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-xl-9">

                        <div class="bp-success-state">
                            <div class="bp-success-check">
                                <svg viewBox="0 0 24 24" fill="none" stroke="#1E8449" stroke-width="3" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <polyline points="4 12 10 18 20 6" />
                                </svg>
                            </div>
                            <h2>Order Placed Successfully!</h2>
                            <p>Thank you, {{ explode(' ', $order->customer_name)[0] }} — we've received your order.</p>
                            <div class="bp-success-orderno">
                                Order Number: <strong>{{ $order->order_number }}</strong>
                            </div>
                        </div>

                        @if ($order->status === 'pending' || $order->status === 'confirmed')
                            @php
                                // Use the order's shipping zone estimated delivery time; fall
// back to a generic range for legacy orders with no zone.
$etaDays = $order->shippingZone?->estimated_days;
$etaText = $etaDays
    ? $etaDays . ' ' . \Illuminate\Support\Str::plural('day', $etaDays)
    : '1–5 days';
                            @endphp
                            <div class="bp-success-eta">
                                <div class="bp-success-eta-icon"><i class="fas fa-truck-fast"></i></div>
                                <div class="bp-success-eta-body">
                                    <div><strong>Expected delivery: {{ $etaText }}</strong></div>
                                    <small class="text-muted">We'll call you on
                                        <strong>{{ $order->customer_phone }}</strong> to confirm the order shortly.</small>
                                </div>
                            </div>
                        @endif

                        <div class="row g-3">
                            {{-- Order Summary --}}
                            <div class="col-md-7">
                                <div class="bp-success-card mb-0">
                                    <h5><i class="fas fa-bag-shopping"></i> Order Summary</h5>

                                    @if ($order->items && $order->items->count())
                                        @php
                                            // Group combo components under one combo line; plain items stay individual.
                                            $orderGroups = $order->items->groupBy(
                                                fn ($i) => $i->combo_id ? 'combo-' . $i->combo_id : 'item-' . $i->id,
                                            );
                                        @endphp
                                        @foreach ($orderGroups as $group)
                                            @php $first = $group->first(); @endphp
                                            @if ($first->combo_id)
                                                @php
                                                    $cp = $first->product ?? null;
                                                    $cImg = $cp && $cp->image
                                                        ? upload_url($cp->image)
                                                        : asset('website/assets/images/placeholder.png');
                                                @endphp
                                                <div class="bp-success-item">
                                                    <div class="bp-success-item-thumb">
                                                        <img src="{{ $cImg }}" alt="{{ $first->combo_name }}">
                                                    </div>
                                                    <div class="bp-success-item-body">
                                                        <div class="bp-success-item-name">{{ $first->combo_name ?? 'Combo' }}</div>
                                                        @if ($first->variant_name)
                                                            <div class="bp-success-item-qty">Size:
                                                                {{ $first->variant_name }}</div>
                                                        @endif
                                                    </div>
                                                    <div class="bp-success-item-price">{{ currency_symbol() }}
                                                        {{ bd_price($group->sum('subtotal')) }}</div>
                                                </div>
                                            @else
                                                @php
                                                    $product = $first->product ?? null;
                                                    $img = $product && $product->image
                                                        ? upload_url($product->image)
                                                        : asset('website/assets/images/placeholder.png');
                                                @endphp
                                                <div class="bp-success-item">
                                                    <div class="bp-success-item-thumb">
                                                        <img src="{{ $img }}" alt="{{ $first->product_name }}">
                                                    </div>
                                                    <div class="bp-success-item-body">
                                                        <div class="bp-success-item-name">{{ $first->product_name }}</div>
                                                        <div class="bp-success-item-qty">Qty: {{ $first->quantity }} ×
                                                            {{ currency_symbol() }}
                                                            {{ bd_price($first->unit_price) }}</div>
                                                    </div>
                                                    <div class="bp-success-item-price">{{ currency_symbol() }}
                                                        {{ bd_price($first->subtotal) }}</div>
                                                </div>
                                            @endif
                                        @endforeach
                                    @endif

                                    <div class="bp-success-totals">
                                        <div class="row-line">
                                            <span>Subtotal</span>
                                            <span>{{ currency_symbol() }} {{ bd_price($order->subtotal) }}</span>
                                        </div>
                                        @if ($order->discount_amount > 0)
                                            <div class="row-line discount">
                                                <span>Discount{{ $order->coupon_code ? ' (' . $order->coupon_code . ')' : '' }}</span>
                                                <span>− {{ currency_symbol() }}
                                                    {{ bd_price($order->discount_amount) }}</span>
                                            </div>
                                        @endif
                                        <div class="row-line">
                                            <span>Shipping</span>
                                            <span>
                                                @if ($order->shipping_charge > 0)
                                                    {{ currency_symbol() }}
                                                    {{ bd_price($order->shipping_charge) }}
                                                @else
                                                    <span style="color:#1E8449; font-weight:600;">Free</span>
                                                @endif
                                            </span>
                                        </div>
                                        @if ($order->tax_amount > 0)
                                            <div class="row-line">
                                                <span>VAT</span>
                                                <span>{{ currency_symbol() }}
                                                    {{ bd_price($order->tax_amount) }}</span>
                                            </div>
                                        @endif
                                        <div class="row-line grand-total">
                                            <span>Total</span>
                                            <span>{{ currency_symbol() }}
                                                {{ bd_price($order->grand_total) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Shipping + Payment --}}
                            <div class="col-md-5">
                                @if ($order->shipping_address)
                                    <div class="bp-success-card">
                                        <h5><i class="fas fa-location-dot"></i> Shipping Details</h5>
                                        <div class="bp-success-info">
                                            <div class="row-line"><strong>{{ $order->customer_name }}</strong></div>
                                            <div class="row-line">{{ $order->shipping_address }}</div>
                                            @if ($order->customer_phone)
                                                <div class="row-line"><i
                                                        class="fas fa-phone"></i>{{ $order->customer_phone }}</div>
                                            @endif
                                            @if ($order->customer_email)
                                                <div class="row-line"><i
                                                        class="fas fa-envelope"></i>{{ $order->customer_email }}</div>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                <div class="bp-success-card">
                                    <h5><i class="fas fa-wallet"></i> Payment</h5>
                                    <div class="bp-success-info">
                                        <div class="row-line">
                                            <strong>
                                                @if ($order->payment_method === 'cod')
                                                    Cash on Delivery
                                                @elseif($order->payment_method === 'bkash')
                                                    bKash
                                                @elseif($order->payment_method === 'nagad')
                                                    Nagad
                                                @else
                                                    {{ ucfirst($order->payment_method) }}
                                                @endif
                                            </strong>
                                        </div>
                                        <div class="row-line">
                                            @if ($order->payment_status === 'paid')
                                                <span class="bp-success-badge paid">Paid</span>
                                            @else
                                                <span
                                                    class="bp-success-badge cod">{{ ucfirst($order->payment_status) }}</span>
                                            @endif
                                        </div>
                                        @if ($order->payment_method === 'cod')
                                            <small class="text-muted">Pay <strong>{{ currency_symbol() }}
                                                    {{ bd_price($order->grand_total) }}</strong> in cash to the
                                                delivery agent.</small>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bp-success-actions">
                            <a href="{{ route('storefront.shop.index') }}" class="common_btn">
                                Continue Shopping <i class="fas fa-long-arrow-right"></i>
                            </a>
                            <button type="button" class="bp-success-btn outline" onclick="window.print()">
                                <i class="fas fa-print"></i> Print Invoice
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ─────────── PRINT INVOICE — hidden on screen, shown in @media print ─────────── --}}
        {{-- Build the normalised invoice payload from the order, then render the
             shared invoice sheet so it looks identical to the admin print page. --}}
        @php
            $invMoney = static fn ($v): string => 'TK. ' . number_format((float) $v);

            $inv = [
                'bizName'       => \Modules\Setting\Models\Setting::get('business', 'company_name', config('app.name')),
                'bizAddress'    => \Modules\Setting\Models\Setting::get('business', 'address', ''),
                'bizPhone'      => \Modules\Setting\Models\Setting::get('business', 'company_phone', ''),
                'bizEmail'      => \Modules\Setting\Models\Setting::get('business', 'email', ''),
                'logoUrl'       => upload_url(\Modules\Setting\Models\Setting::get('business', 'logo')),
                'billName'      => $order->customer_name,
                'billPhone'     => $order->customer_phone,
                'billAddress'   => $order->shipping_address,
                'parcelId'      => $order->courier_consignment_id ?? '',
                'invoiceNumber' => $order->order_number,
                'invoiceDate'   => $order->created_at?->format('d-m-Y') ?? '',
                'items'         => $order->items->map(static fn ($item) => [
                    'name'      => $item->product_name,
                    'variation' => $item->variant_name,
                    'qty'       => $item->quantity,
                    'price'     => $invMoney($item->unit_price),
                    'amount'    => $invMoney($item->subtotal),
                ])->all(),
                'subTotal'      => $invMoney($order->subtotal),
                'discount'      => $invMoney($order->discount_amount),
                'shipping'      => $invMoney($order->shipping_charge ?? 0),
                'total'         => $invMoney($order->grand_total),
                'due'           => $invMoney($order->payment_status === 'paid' ? 0 : $order->grand_total),
                'barcode'       => $order->order_number,
            ];
        @endphp

        {{-- PRINT INVOICE — hidden on screen, shown only in the print popup --}}
        <div class="bp-invoice-print">
            @include('sale::partials.invoice-sheet', ['inv' => $inv])
        </div>
    @else
        <section class="bp-success-body">
            <div class="container">
                <div class="bp-success-state">
                    <h2>Order Not Found</h2>
                    <p>We couldn't find this order. Please check the link or contact support.</p>
                </div>
            </div>
        </section>
    @endif
@endsection

@push('scripts')
    <script src="{{ asset('vendor/jsbarcode/jsbarcode.min.js') }}"></script>
    <script>
        'use strict';

        // Render the invoice barcode (matches the admin invoice print page).
        document.querySelectorAll('.inv-bc').forEach(function (el) {
            var code = el.getAttribute('data-code') || '';
            if (!code) return;
            try {
                JsBarcode(el, code, { format: 'CODE128', displayValue: false, height: 45, width: 1.6, margin: 0 });
            } catch (e) { /* leave blank on failure */ }
        });
    </script>
@endpush

@if(!empty($purchasePayload))
    @push('scripts')
        <script>
            'use strict';

            // Browser-side Purchase — rendered only on the first arrival from
            // checkout (session flash) and only for fraud-clean orders. The
            // eventID matches the server-side CAPI event so Meta dedups them.
            BizPOS.track('Purchase', @json($purchasePayload['ga']), {
                eventID: @json($purchasePayload['event_id']),
                fbData: @json($purchasePayload['fb']),
                userData: @json($purchasePayload['user']),
                customerData: @json($purchasePayload['customer'])
            });
        </script>
    @endpush
@endif
