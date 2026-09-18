@extends('ecommerce::storefront.layouts.master')

@section('title', 'Checkout')
@section('breadcrumb_title', 'Checkout')

@section('breadcrumb')
    <li><a href="{{ route('storefront.cart.index') }}">Cart</a></li>
    <li><a href="{{ route('storefront.checkout.index') }}">Checkout</a></li>
@endsection

@section('content')
    <section class="checkout_page mt_70 mb_70">
        <div class="container">
            @php
                $cartItems = $cartItems ?? session('cart', []);
                $cartSubtotal = $cartSubtotal ?? 0;
                $cartTotal = $cartTotal ?? 0;
                $discount = $discount ?? 0;

                $sc = $savedCustomer ?? [];
                $valName = old('customer_name', $sc['name'] ?? '');
                $valPhone = old('customer_phone', $sc['phone'] ?? '');
                $valEmail = old('customer_email', $sc['email'] ?? '');
                $valAddress = old('address', $sc['address'] ?? ($sc['address1'] ?? ''));
                $valDistrictId = old('district_id', $sc['district_id'] ?? '');
            @endphp

            @if (count($cartItems) > 0)
                <form action="{{ route('storefront.checkout.process') }}" method="POST" id="checkoutForm">
                    @csrf
                    <input type="hidden" name="payment_method" value="cod">
                    <input type="hidden" name="idempotency_token" value="{{ $idempotencyToken }}">
                    <input type="hidden" name="customer_email" value="{{ $valEmail }}">
                    <input type="hidden" name="district_id" value="{{ $valDistrictId }}">
                    <input type="hidden" name="alt_phone" value="{{ old('alt_phone') }}">

                    <div class="row justify-content-center">
                        <div class="col-xxl-6 col-lg-7 wow fadeInUp">
                            <div class="checkout_header">
                                <h3><i class="fas fa-map-marker-alt me-2"></i> Shipping Address</h3>
                            </div>
                            <div class="checkout_form_area">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="single_input">
                                            <label>Full Name *</label>
                                            <input type="text" name="customer_name" placeholder="Enter your full name"
                                                value="{{ $valName }}" required
                                                class="@error('customer_name') is-invalid @enderror">
                                            @error('customer_name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="single_input">
                                            <label>Phone Number *</label>
                                            <input type="tel" name="customer_phone" placeholder="01XXXXXXXXX"
                                                value="{{ $valPhone }}" required data-phone inputmode="numeric"
                                                maxlength="14" class="@error('customer_phone') is-invalid @enderror">
                                            @error('customer_phone')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="single_input">
                                            <label>Address *</label>
                                            <input type="text" name="address" placeholder="House, Road, Area"
                                                value="{{ $valAddress }}" required
                                                class="@error('address') is-invalid @enderror">
                                            @error('address')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="checkout_zone_field">
                                            <span class="checkout_zone_field__label">Delivery Charge *</span>
                                            <div id="zoneRadios" class="checkout_zone_options">
                                                @foreach ($zonesData as $zone)
                                                    <label class="checkout_zone_option stock-filter-label"
                                                        for="zone{{ $zone['id'] }}">
                                                        <input class="form-check-input" type="radio"
                                                            name="shipping_zone_id" id="zone{{ $zone['id'] }}"
                                                            value="{{ $zone['id'] }}" data-rate="{{ $zone['rate'] }}"
                                                            data-threshold="{{ $zone['threshold'] }}"
                                                            {{ old('shipping_zone_id') == $zone['id'] ? 'checked' : '' }}>
                                                        <span
                                                            class="checkout_zone_option__name">{{ $zone['label'] }}</span>
                                                        <span class="checkout_zone_option__rate">{{ currency_symbol() }}
                                                            {{ bd_price($zone['rate']) }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                            @error('shipping_zone_id')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="single_input">
                                            <label>Note for Delivery</label>
                                            <textarea rows="2" name="notes" placeholder="Special instructions (optional)">{{ old('notes') }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xxl-4 col-lg-5 col-md-9 wow fadeInRight d-flex flex-column">
                            <div class="cart_page_summary order-last order-md-first" id="orderSummary">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h3 class="mb-0">Order Summary</h3>
                                </div>

                                <ul id="checkoutItems">
                                    @foreach ($cartItems as $cartKey => $item)
                                        @php
                                            $isComboLine = ($item['type'] ?? 'product') === 'combo';
                                            $itemImage = upload_url(
                                                $item['image'] ?? ($item['thumbnail'] ?? null),
                                                asset('website/assets/images/product_placeholder.png'),
                                            );
                                            $itemUrl =
                                                $isComboLine && !empty($item['slug'])
                                                    ? route('storefront.combos.show', $item['slug'])
                                                    : route(
                                                        'storefront.shop.show',
                                                        $item['slug'] ?? ($item['product_id'] ?? '#'),
                                                    );
                                            $qty = (int) ($item['quantity'] ?? 1);
                                            $unitPrice = (float) ($item['price'] ?? 0);
                                        @endphp
                                        <li class="checkout-item" data-cart-key="{{ $cartKey }}"
                                            data-unit-price="{{ $unitPrice }}">
                                            <div class="img" href="{{ $itemUrl }}">
                                                <img src="{{ $itemImage }}" alt="{{ $item['name'] ?? 'Product' }}"
                                                    class="img-fluid w-100">
                                                <button type="button" class="checkout-remove-btn ms-auto" title="Remove"
                                                    aria-label="Remove">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                            <div class="text">
                                                <a class="title"
                                                    href="{{ $itemUrl }}">{{ $item['name'] ?? 'Product' }}</a>

                                                @if (isset($item['sell_price']) && $item['sell_price'] > $item['price'])
                                                    <p class="checkout-line-total mb-1">
                                                        {{ currency_symbol() }} <span
                                                            class="line-total-amount">{{ bd_price($unitPrice * $qty) }}</span>
                                                        <del>{{ currency_symbol() }}
                                                            {{ bd_price($item['sell_price'] * $qty) }}</del>
                                                    </p>
                                                @else
                                                    <p class="checkout-line-total mb-1">{{ currency_symbol() }} <span
                                                            class="line-total-amount">{{ bd_price($unitPrice * $qty) }}</span>
                                                    </p>
                                                @endif

                                                @if (!empty($item['variant_attributes']))
                                                    @foreach ($item['variant_attributes'] as $attr)
                                                        <p class="fs-12 text-muted mb-1"><b>{{ $attr['label'] }}:</b>
                                                            {{ $attr['value'] }}</p>
                                                    @endforeach
                                                @elseif (isset($item['variant_name']) && $item['variant_name'])
                                                    <p class="fs-12 text-muted mb-1">{{ $item['variant_name'] }}</p>
                                                @endif

                                                @if ($isComboLine && !empty($item['size']))
                                                    <p class="fs-12 text-muted mb-1"><b>Size:</b> {{ $item['size'] }}</p>
                                                @endif

                                                <div class="checkout_qty d-flex align-items-center gap-2 my-2">
                                                    <button type="button" class="checkout-qty-btn" data-action="dec"
                                                        aria-label="Decrease">−</button>
                                                    <input type="text" class="checkout-qty-input"
                                                        value="{{ $qty }}" readonly>
                                                    <button type="button" class="checkout-qty-btn" data-action="inc"
                                                        aria-label="Increase">+</button>
                                                </div>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>

                                <h6>Subtotal <span id="checkoutSubtotal">{{ currency_symbol() }}
                                        {{ bd_price($cartSubtotal) }}</span></h6>
                                @if ($discount > 0)
                                    <h6>Discount <span class="text-success">(-) {{ currency_symbol() }}
                                            {{ bd_price($discount) }}</span></h6>
                                @endif
                                <h6 id="shippingLine">Shipping <span id="shippingAmount">{{ currency_symbol() }}
                                        {{ bd_price($shippingCharge) }}</span></h6>

                                <h4>Total <span id="checkoutTotal">{{ currency_symbol() }}
                                        {{ bd_price($cartTotal) }}</span></h4>

                                <div class="alert alert-success d-flex align-items-center gap-2 mt-3 mb-0 p-2 fs-13"
                                    id="deliveryEstimate" style="display: none !important;">
                                    <i class="fas fa-calendar-check"></i>
                                    <span>Delivery within <strong id="deliveryDays">3-5 Days</strong> after
                                        confirmation</span>
                                </div>
                            </div>

                            <div class="checkout_payment order-first order-md-last">
                                <h3>Payment Method</h3>
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <i class="fas fa-money-bill-wave"></i>
                                    <span>Cash on Delivery</span>
                                </div>

                                <button type="submit" class="common_btn" id="placeOrderBtn">Place order <i
                                        class="fas fa-long-arrow-right"></i></button>
                            </div>
                        </div>
                    </div>
                </form>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
                    <h4>Your cart is empty</h4>
                    <p class="text-muted">Add items to your cart before proceeding to checkout.</p>
                    <a href="{{ route('storefront.shop.index') }}" class="common_btn mt-3">Browse Products</a>
                </div>
            @endif
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        'use strict';
        $(function() {
            var subtotal = {{ $cartSubtotal ?? 0 }};
            var discount = {{ $discount ?? 0 }};

            // Shipping zones data from server — keyed by id for O(1) lookup.
            var shippingZones = @json($zonesData ?? []);
            var zonesById = {};
            for (var i = 0; i < shippingZones.length; i++) zonesById[shippingZones[i].id] = shippingZones[i];

            // When the Delivery Charge radio changes, the picked zone is the
            // single source of truth for the shipping line. (Server still re-
            // validates from DB at submit; this is the live UI hint only.)
            $(document).on('change', 'input[name="shipping_zone_id"]', function() {
                $('#zoneRadios').removeClass('is-invalid')
                    .closest('.checkout_zone_field').find('.invalid-feedback.js-error').remove();
                var zoneId = parseInt($(this).val(), 10);
                var zone = zoneId ? zonesById[zoneId] : null;

                if (zone) {
                    var rate = zone.rate;
                    if (zone.threshold > 0 && subtotal >= zone.threshold) rate = 0;

                    var label = 'Shipping (' + (zone.bn_name ? zone.name + ' / ' + zone.bn_name : zone
                        .name) + ')';
                    $('#shippingLine').html(label + ' <span id="shippingAmount">' + window.bdMoney(rate) +
                        '</span>');
                    $('#shippingAmount').text(window.bdMoney(rate));
                    $('#checkoutTotal').text(fmt(subtotal - discount + rate));

                    if (zone.estimated_days) {
                        $('#deliveryDays').text(zone.estimated_days + ' Days');
                        $('#deliveryEstimate').css('display', '').show();
                    } else {
                        $('#deliveryEstimate').hide();
                    }
                } else {
                    $('#shippingLine').html('Shipping <span id="shippingAmount">' + window.bdMoney(0) +
                        '</span>');
                    $('#checkoutTotal').text(fmt(subtotal - discount));
                    $('#deliveryEstimate').hide();
                }
            });

            // Trigger on page load if a zone is pre-selected (validation re-render).
            if ($('input[name="shipping_zone_id"]:checked').length) {
                $('input[name="shipping_zone_id"]:checked').trigger('change');
            }

            // Phone inputs accept digits only, capped at the configured national
            // length (11 for BD) — or dial-code length when the shopper pastes
            // the +880 form — so an over-long number can't be typed at all.
            $(document).on('input', 'input[data-phone]', function() {
                var cfg = window.BizPOS.phone || {};
                var natLen = cfg.length || 11;
                var dial = String(cfg.dial_code || '').replace(/\D/g, '');
                var digits = this.value.replace(/\D/g, '');
                // Dial-code form drops the trunk "0": 880 + 10 digits for BD.
                var cap = (dial && digits.indexOf(dial) === 0) ? dial.length + natLen - 1 : natLen;
                if (digits.length > cap) digits = digits.slice(0, cap);
                if (this.value !== digits) this.value = digits;
            });

            // Inline field error — red-marks the field and shows the message
            // under it, mirroring the server-side validation error markup.
            function setFieldError($el, message) {
                $el.addClass('is-invalid');
                var $wrap = $el.closest('.single_input, .checkout_zone_field');
                if (!$wrap.length) $wrap = $el.parent();
                var $fb = $wrap.find('.invalid-feedback.js-error');
                if (!$fb.length) $fb = $('<div class="invalid-feedback js-error"></div>').appendTo($wrap);
                $fb.text(message).addClass('d-block');
            }

            // Form validation
            $('#checkoutForm').on('submit', function(e) {
                var form = this;

                // Re-entrancy guard: once a valid submission is under way, block
                // any further submit (double-click, Enter, programmatic) so a
                // second POST can't trip the server idempotency guard and bounce
                // the shopper to the cart (Bug_90).
                if (form._submitting) {
                    e.preventDefault();
                    return false;
                }

                var isValid = true;

                $(form).find('[required]').each(function() {
                    if (!$(this).val() || !$(this).val().toString().trim()) {
                        isValid = false;
                        setFieldError($(this), 'This field is required.');
                    } else {
                        $(this).removeClass('is-invalid');
                    }
                });

                // Phone validation — single source (window.bdPhoneValid).
                var phoneCfg = window.BizPOS.phone || {};
                var phoneMsg = 'Please enter a valid phone number, e.g. ' +
                    (phoneCfg.example || '01712-345678') + '.';
                var phone = $('input[name="customer_phone"]').val().trim();
                if (phone && !window.bdPhoneValid(phone)) {
                    isValid = false;
                    setFieldError($('input[name="customer_phone"]'), phoneMsg);
                }
                var altPhone = $('input[name="alt_phone"]').val().trim();
                if (altPhone && !window.bdPhoneValid(altPhone)) {
                    isValid = false;
                    setFieldError($('input[name="alt_phone"]'), phoneMsg);
                }

                // Delivery Charge is now a radio group — require one selection.
                if (!$('input[name="shipping_zone_id"]:checked').length) {
                    isValid = false;
                    setFieldError($('#zoneRadios'), 'Please select a delivery area.');
                }

                if (!isValid) {
                    e.preventDefault();
                    var firstInvalid = $(form).find('.is-invalid:first');
                    if (firstInvalid.length) {
                        $('html, body').animate({
                            scrollTop: firstInvalid.offset().top - 100
                        }, 300);
                    }
                    return false;
                }

                // Mark in-flight and disable submit button
                form._submitting = true;
                $('#placeOrderBtn').prop('disabled', true).html(
                    '<i class="fas fa-spinner fa-spin me-2"></i> Processing...');
            });

            // Clear invalid state (and its inline message) on input
            $(document).on('input change', '.is-invalid', function() {
                $(this).removeClass('is-invalid');
                $(this).closest('.single_input, .checkout_zone_field')
                    .find('.invalid-feedback.js-error').remove();
            });

            // ── Abandoned-checkout capture (best-effort) ──
            // Once the shopper has typed a name and a valid phone, send the
            // contact details (debounced) to the capture endpoint so an
            // unplaced order surfaces as Incompleted in the admin sales list.
            // Keyed by the same idempotency token as Place Order, so placing
            // the order converts the capture instead of duplicating it.
            var captureTimer = null;
            var lastCapturePayload = '';
            var captureOrderPlaced = false;

            $('#checkoutForm').on('submit', function() {
                captureOrderPlaced = true;
                clearTimeout(captureTimer);
            });

            function sendCheckoutCapture() {
                if (captureOrderPlaced) return;
                var name = $('input[name="customer_name"]').val().trim();
                var phone = $('input[name="customer_phone"]').val().trim();
                if (!name || !window.bdPhoneValid(phone)) return;

                var payload = {
                    _token: $('#checkoutForm input[name="_token"]').val(),
                    idempotency_token: $('#checkoutForm input[name="idempotency_token"]').val(),
                    customer_name: name,
                    customer_phone: phone,
                    customer_email: $('#checkoutForm input[name="customer_email"]').val() || '',
                    address: $('input[name="address"]').val() || '',
                    district_id: $('#checkoutForm input[name="district_id"]').val() || '',
                    shipping_zone_id: $('input[name="shipping_zone_id"]:checked').val() || ''
                };

                // Skip when nothing changed since the last capture.
                var key = JSON.stringify(payload);
                if (key === lastCapturePayload) return;
                lastCapturePayload = key;

                // Fire-and-forget: failures are invisible to the shopper.
                $.post('{{ route('storefront.checkout.capture') }}', payload);
            }

            $(document).on('input change blur',
                'input[name="customer_name"], input[name="customer_phone"], input[name="address"], input[name="shipping_zone_id"]',
                function() {
                    clearTimeout(captureTimer);
                    captureTimer = setTimeout(sendCheckoutCapture, 2000);
                });

            // ── Inline qty + remove handlers for the order summary ──
            // Round to 2 decimals first so float noise doesn't force a spurious
            // ".00"; window.bdPrice() then drops whole-number decimals and applies
            // the admin's separator preference — identical to the server's bd_price().
            function fmt(n) {
                return window.bdMoney(Math.round((Number(n) || 0) * 100) / 100);
            }

            function refreshTotals(serverSubtotal) {
                // Prefer authoritative subtotal from the server when provided.
                var sub = (typeof serverSubtotal === 'number') ? serverSubtotal : 0;
                if (typeof serverSubtotal !== 'number') {
                    $('#checkoutItems .checkout-item').each(function() {
                        var unit = parseFloat($(this).data('unit-price')) || 0;
                        var q = parseInt($(this).find('.checkout-qty-input').val(), 10) || 0;
                        sub += unit * q;
                    });
                }
                subtotal = sub;
                $('#checkoutSubtotal').text(fmt(sub));

                // Re-trigger shipping recalculation by faking a district change.
                var ship = parseFloat(($('#shippingAmount').text() || '0').replace(/[^\d.]/g, '')) || 0;
                var total = Math.max(0, sub - discount + ship);
                $('#checkoutTotal').text(fmt(total));
            }

            function postCart(url, payload) {
                return $.ajax({
                    url: url,
                    method: 'POST',
                    data: payload,
                });
            }

            // Increment / decrement
            $(document).on('click', '#checkoutItems .checkout-qty-btn', function() {
                var $btn = $(this);
                var $li = $btn.closest('.checkout-item');
                var key = String($li.data('cart-key'));
                var $input = $li.find('.checkout-qty-input');
                var current = parseInt($input.val(), 10) || 1;
                var action = $btn.data('action');
                var next = action === 'inc' ? current + 1 : current - 1;

                if (next < 1) {
                    // Hitting "−" at qty 1 → remove the line.
                    return $li.find('.checkout-remove-btn').trigger('click');
                }

                $btn.prop('disabled', true);
                postCart('{{ route('storefront.cart.update') }}', {
                        cart_key: key,
                        quantity: next
                    })
                    .done(function(data) {
                        if (!data || !data.success) {
                            return;
                        }
                        $input.val(next);
                        var unit = parseFloat($li.data('unit-price')) || 0;
                        $li.find('.line-total-amount').text(window.bdPrice(Math.round(unit * next *
                            100) / 100));
                        refreshTotals(typeof data.cart_total === 'number' ? data.cart_total :
                            undefined);
                    })
                    .fail(function() {
                        if (typeof window.bpToast === 'function') {
                            window.bpToast('Could not update quantity', 'error');
                        }
                    })
                    .always(function() {
                        $btn.prop('disabled', false);
                    });
            });

            // Remove a line
            $(document).on('click', '#checkoutItems .checkout-remove-btn', function() {
                var $li = $(this).closest('.checkout-item');
                var key = String($li.data('cart-key'));
                if (!confirm('Remove this item from your order?')) return;

                postCart('{{ route('storefront.cart.remove') }}', {
                        cart_key: key
                    })
                    .done(function(data) {
                        if (!data || !data.success) return;
                        $li.fadeOut(200, function() {
                            $(this).remove();
                            refreshTotals(typeof data.cart_total === 'number' ? data
                                .cart_total : undefined);
                            if ($('#checkoutItems .checkout-item').length === 0) {
                                // Cart emptied — bounce back to the cart page so the
                                // empty-state UX is consistent.
                                window.location.href = '{{ route('storefront.cart.index') }}';
                            }
                        });
                    })
                    .fail(function() {
                        alert('Could not remove item.');
                    });
            });
        });
    </script>
    @php
        $bpCheckoutItems = collect($cartItems)
            ->map(function ($i) {
                $item = [
                    'item_id' => (string) ($i['product_id'] ?? ($i['combo_id'] ?? '')),
                    'item_name' => $i['name'] ?? '',
                    'price' => (float) ($i['price'] ?? 0),
                    'quantity' => (int) ($i['quantity'] ?? 0),
                ];
                if (!empty($i['variant_name'])) {
                    $item['item_variant'] = $i['variant_name'];
                }
                $bpDiscount = round((float) ($i['sell_price'] ?? 0) - (float) ($i['price'] ?? 0), 2);
                if ($bpDiscount > 0) {
                    $item['discount'] = $bpDiscount;
                }
                return $item;
            })
            ->values()
            ->all();
    @endphp
    <script>
        'use strict';
        $(function() {
            var bpCheckoutValue = {{ (float) $cartTotal }};
            var bpCheckoutItems = @json($bpCheckoutItems);
            var bpCheckoutCoupon = @json(session('coupon')['code'] ?? null);

            // add_shipping_info — fires when the user picks a delivery zone.
            // Selector: input[name="shipping_zone_id"] radio group.
            $(document).on('change', 'input[name="shipping_zone_id"]', function() {
                if (window.BizPOS) {
                    // Zone label text = the human-readable shipping tier.
                    var tier = ($('label[for="' + this.id + '"]').text() || $(this).closest('label').text() || '').trim().replace(/\s+/g, ' ').slice(0, 100);
                    var params = {
                        currency: 'BDT',
                        value: bpCheckoutValue,
                        items: bpCheckoutItems
                    };
                    if (bpCheckoutCoupon) { params.coupon = bpCheckoutCoupon; }
                    if (tier) { params.shipping_tier = tier; }
                    BizPOS.track('AddShippingInfo', params, {
                        fbData: {}
                    });
                }
            });

            // add_payment_info — payment method is Cash on Delivery (hidden input,
            // no interactive radio group). Fire when the user clicks Place Order,
            // which is the only meaningful "payment method confirmed" interaction.
            // Selector: #placeOrderBtn (type="submit"), line 243 of this view.
            $('#placeOrderBtn').on('click', function() {
                if (window.BizPOS) {
                    var params = {
                        currency: 'BDT',
                        value: bpCheckoutValue,
                        payment_type: ($('input[name="payment_method"]').val() || 'Cash on Delivery'),
                        items: bpCheckoutItems
                    };
                    if (bpCheckoutCoupon) { params.coupon = bpCheckoutCoupon; }
                    BizPOS.track('AddPaymentInfo', params, {
                        fbData: {
                            value: bpCheckoutValue,
                            currency: 'BDT'
                        }
                    });
                }
            });
        });
    </script>
    @php
        $bpItems = collect($cartItems)
            ->map(function ($i) {
                // Combo cart lines carry combo_id (no product_id); fall back so
                // the analytics payload doesn't blow up on a combo in the cart.
                $item = [
                    'item_id' => (string) ($i['product_id'] ?? ($i['combo_id'] ?? '')),
                    'item_name' => $i['name'] ?? '',
                    'price' => (float) ($i['price'] ?? 0),
                    'quantity' => (int) ($i['quantity'] ?? 0),
                ];
                if (!empty($i['variant_name'])) {
                    $item['item_variant'] = $i['variant_name'];
                }
                $bpDiscount = round((float) ($i['sell_price'] ?? 0) - (float) ($i['price'] ?? 0), 2);
                if ($bpDiscount > 0) {
                    $item['discount'] = $bpDiscount;
                }
                return $item;
            })
    ->values()
    ->all();
$bpContentIds = array_map(fn($i) => $i['item_id'], $bpItems);
$bpContents = array_map(
    fn($i) => ['id' => $i['item_id'], 'quantity' => $i['quantity'], 'item_price' => $i['price']],
            $bpItems,
        );
    @endphp
    @include('ecommerce::storefront.partials.track-event', [
        'event' => 'InitiateCheckout',
        'ga' => [
            'currency' => 'BDT',
            'value' => (float) $cartTotal,
            'coupon' => session('coupon')['code'] ?? null,
            'items' => $bpItems,
        ],
        'fb' => [
            'data' => [
                'content_type' => 'product',
                'content_ids' => $bpContentIds,
                'contents' => $bpContents,
                'num_items' => count($bpItems),
                'value' => (float) $cartTotal,
                'currency' => 'BDT',
            ],
        ],
    ])
@endpush
