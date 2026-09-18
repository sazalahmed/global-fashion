@extends('ecommerce::storefront.layouts.master')

@section('title', 'Cart View')
@section('breadcrumb_title', 'Cart View')

{{-- Sticky-sidebar lib: used by this page's sidebar (perf) --}}
@push('pageVendorJs')<script src="{{ asset('website/assets/js/sticky_sidebar.js') }}"></script>@endpush

@section('breadcrumb')
    <li><a href="{{ route('storefront.cart.index') }}">Cart View</a></li>
@endsection

@section('content')
    <!--============================  CART PAGE START =============================-->
    <section class="cart_page mt_70 mb_70">
        <div class="container">
            @php
                $cartItems = $cartItems ?? session('cart', []);
                $cartSubtotal = $cartSubtotal ?? 0;
                $cartTotal = $cartTotal ?? 0;
                $discount = $discount ?? 0;
                $shipping = $shipping ?? 0;
                $coupon = $coupon ?? null;
            @endphp

            @if (count($cartItems) > 0)
                <div class="row">
                    <div class="col-lg-8 wow fadeInUp">
                        <div class="cart_table_area">
                            {{-- Cart Items --}}
                            <div class="table-responsive">
                                <div class="cart_bulk_bar">
                                    <button type="button" class="common_btn cart_remove_selected" id="cartRemoveSelected">
                                        <i class="fas fa-trash"></i> Remove
                                    </button>
                                </div>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th class="cart_page_check form-check"><input class="form-check-input"
                                                    type="checkbox" id="cartSelectAllHead"></th>
                                            <th class="cart_page_img">Image</th>
                                            <th class="cart_page_details">Details</th>
                                            <th class="cart_page_price">Unit Price</th>
                                            <th class="cart_page_quantity">Quantity</th>
                                            <th class="cart_page_total">Subtotal</th>
                                            <th class="cart_page_action">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($cartItems as $key => $item)
                                            @php
                                                $isCombo = ($item['type'] ?? 'product') === 'combo';
                                                $itemUrl = $isCombo
                                                    ? route('storefront.combos.show', $item['slug'] ?? '#')
                                                    : route('storefront.shop.show', $item['slug'] ?? ($item['product_id'] ?? '#'));
                                                $itemImage = upload_url(
                                                    $isCombo ? ($item['thumbnail'] ?? null) : ($item['image'] ?? null),
                                                    asset('website/assets/images/product_placeholder.png'),
                                                );
                                                $lineTotal = ($item['price'] ?? 0) * ($item['quantity'] ?? 1);
                                            @endphp
                                            <tr data-cart-key="{{ $key }}">
                                                <td class="cart_page_check form-check">
                                                    <input type="checkbox" class="cart_item_check form-check-input"
                                                        value="{{ $key }}">
                                                </td>
                                                <td class="cart_page_img">
                                                    <div class="img">
                                                        <a
                                                            href="{{ $itemUrl }}">
                                                            <img src="{{ $itemImage }}"
                                                                alt="{{ $item['name'] ?? 'Product' }}"
                                                                class="img-fluid w-100">
                                                        </a>
                                                    </div>
                                                </td>
                                                <td class="cart_page_details">
                                                    <a class="title"
                                                        href="{{ $itemUrl }}">{{ $item['name'] ?? 'Product' }}</a>
                                                    @if ($isCombo)
                                                        @if (!empty($item['size']))
                                                            <span class="cart_variant_attr"><b>Size:</b> {{ $item['size'] }}</span>
                                                        @endif
                                                    @elseif (!empty($item['variant_attributes']))
                                                        @foreach ($item['variant_attributes'] as $attr)
                                                            <span class="cart_variant_attr"><b>{{ $attr['label'] }}:</b>
                                                                {{ $attr['value'] }}</span>
                                                        @endforeach
                                                    @elseif (isset($item['variant_name']) && $item['variant_name'])
                                                        <span>{{ $item['variant_name'] }}</span>
                                                    @endif
                                                </td>
                                                <td class="cart_page_price">
                                                    <h3>{{ currency_symbol() }} {{ bd_price($item['price'] ?? 0) }}</h3>
                                                </td>
                                                <td class="cart_page_quantity">
                                                    <div class="details_qty_input">
                                                        <button class="minus cart_qty_change"
                                                            data-key="{{ $key }}" data-action="minus"><i
                                                                class="fas fa-minus" aria-hidden="true"></i></button>
                                                        <input type="text" value="{{ $item['quantity'] ?? 1 }}"
                                                            class="cart_qty_input" data-key="{{ $key }}">
                                                        <button class="plus cart_qty_change" data-key="{{ $key }}"
                                                            data-action="plus"><i class="fas fa-plus"
                                                                aria-hidden="true"></i></button>
                                                    </div>
                                                </td>
                                                <td class="cart_page_total">
                                                    <h3>{{ currency_symbol() }} {{ bd_price($lineTotal) }}</h3>
                                                </td>
                                                <td class="cart_page_action">
                                                    <a href="#" class="cart_remove_btn"
                                                        data-key="{{ $key }}"><i class="fas fa-times"></i>
                                                        Remove</a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Cart Summary Sidebar --}}
                    <div class="col-lg-4 col-md-9 wow fadeInRight">
                        <div id="sticky_sidebar">
                            <div class="cart_page_summary">
                                <h3>Billing summary</h3>

                                {{-- Cart Items Summary --}}
                                <ul>
                                    @foreach ($cartItems as $item)
                                        @php
                                            $isCombo = ($item['type'] ?? 'product') === 'combo';
                                            $itemUrl = $isCombo
                                                ? route('storefront.combos.show', $item['slug'] ?? '#')
                                                : route('storefront.shop.show', $item['slug'] ?? ($item['product_id'] ?? '#'));
                                            $itemImg = upload_url(
                                                $isCombo ? ($item['thumbnail'] ?? null) : ($item['image'] ?? null),
                                                asset('website/assets/images/product_placeholder.png'),
                                            );
                                        @endphp
                                        <li>
                                            <a class="img"
                                                href="{{ $itemUrl }}">
                                                <img src="{{ $itemImg }}" alt="{{ $item['name'] ?? 'Product' }}"
                                                    class="img-fluid w-100">
                                            </a>
                                            <div class="text">
                                                <a class="title"
                                                    href="{{ $itemUrl }}">{{ $item['name'] ?? 'Product' }}</a>
                                                <p>{{ currency_symbol() }} {{ bd_price($item['price'] ?? 0) }} x
                                                    {{ $item['quantity'] ?? 1 }}</p>
                                                @if ($isCombo)
                                                    @if (!empty($item['size']))
                                                        <p class="cart_variant_attr"><b>Size:</b> {{ $item['size'] }}</p>
                                                    @endif
                                                @elseif (!empty($item['variant_attributes']))
                                                    @foreach ($item['variant_attributes'] as $attr)
                                                        <p class="cart_variant_attr"><b>{{ $attr['label'] }}:</b>
                                                            {{ $attr['value'] }}</p>
                                                    @endforeach
                                                @elseif (isset($item['variant_name']) && $item['variant_name'])
                                                    <p>{{ $item['variant_name'] }}</p>
                                                @endif
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>

                                <h6>Subtotal <span id="cartSubtotal">{{ currency_symbol() }}
                                        {{ bd_price($cartSubtotal) }}</span></h6>
                                @if ($discount > 0)
                                    <h6>Discount <span id="cartDiscount" class="text-success">(-) {{ currency_symbol() }}
                                            {{ bd_price($discount) }}</span></h6>
                                @endif
                                @if ($shipping > 0)
                                    <h6>Shipping <span id="cartShipping">(+) {{ currency_symbol() }}
                                            {{ bd_price($shipping) }}</span></h6>
                                @endif
                                <h4>Total <span id="cartTotal">{{ currency_symbol() }} {{ bd_price($cartTotal) }}</span>
                                </h4>

                                {{-- Coupon Form --}}
                                <form action="#" id="couponForm">
                                    @if ($coupon)
                                        <p>
                                            Coupon Code: {{ $coupon['code'] ?? '' }}
                                            <a href="#" id="removeCouponBtn"><i class="fas fa-times"></i></a>
                                        </p>
                                    @else
                                        <input type="text" placeholder="Coupon code" id="couponCodeInput">
                                        <button type="submit" class="common_btn" id="applyCouponBtn">Apply</button>
                                    @endif
                                </form>
                            </div>
                            <div class="cart_summary_btn">
                                <a class="common_btn continue_shopping"
                                    href="{{ route('storefront.shop.index') }}">Continue shopping</a>
                                <a class="common_btn" href="{{ route('storefront.checkout.index') }}">Checkout <i
                                        class="fas fa-long-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                {{-- Empty Cart --}}
                <div class="text-center py-5">
                    <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
                    <h4>Your cart is empty</h4>
                    <p class="text-muted">Looks like you haven't added any items to your cart yet.</p>
                    <a href="{{ route('storefront.shop.index') }}" class="common_btn mt-3">
                        <i class="fas fa-arrow-left me-1"></i> Start Shopping
                    </a>
                </div>
            @endif
        </div>
    </section>
    <!--============================ CART PAGE END  =============================-->
@endsection

@push('scripts')
    @php
        $bpItems = collect($cartItems)->map(function ($i) {
            $item = [
                'item_id' => (string) (($i['type'] ?? 'product') === 'combo' ? 'combo:' . ($i['combo_id'] ?? '') : ($i['product_id'] ?? '')),
                'item_name' => $i['name'] ?? '',
                'price' => (float) ($i['price'] ?? 0),
                'quantity' => (int) ($i['quantity'] ?? 1),
            ];
            if (!empty($i['variant_name'])) {
                $item['item_variant'] = $i['variant_name'];
            }
            $bpDiscount = round((float) ($i['sell_price'] ?? 0) - (float) ($i['price'] ?? 0), 2);
            if ($bpDiscount > 0) {
                $item['discount'] = $bpDiscount;
            }
            return $item;
        })->values()->all();
    @endphp
    @if(count($bpItems))
        @include('ecommerce::storefront.partials.track-event', [
            'event' => 'ViewCart',
            'ga' => ['currency' => 'BDT', 'value' => (float) $cartTotal, 'items' => $bpItems],
            'fb' => ['data' => []],
        ])
    @endif
    <script>
        'use strict';
        $(function() {
            // Helper: show toast
            function showToast(message, type) {
                if (typeof Toast !== 'undefined' && Toast.show) {
                    Toast.show(message, type || 'success');
                } else {
                    alert(message);
                }
            }

            // Update quantity
            function updateCartItem(key, quantity) {
                $.ajax({
                    url: '{{ route('storefront.cart.update') }}',
                    method: 'POST',
                    data: {
                        cart_key: key,
                        quantity: quantity
                    },
                    success: function(res) {
                        if (res.success) {
                            location.reload();
                        } else {
                            showToast(res.message || 'Failed to update cart.', 'error');
                        }
                    },
                    error: function(xhr) {
                        showToast(xhr.responseJSON?.message || 'Error updating cart.', 'error');
                    }
                });
            }

            // Quantity change buttons
            $(document).on('click', '.cart_qty_change', function(e) {
                e.preventDefault();
                var key = $(this).data('key');
                var action = $(this).data('action');
                var input = $(this).siblings('.cart_qty_input');
                var val = parseInt(input.val()) || 1;

                if (action === 'minus' && val > 1) {
                    input.val(val - 1);
                    updateCartItem(key, val - 1);
                } else if (action === 'plus' && val < 999) {
                    input.val(val + 1);
                    updateCartItem(key, val + 1);
                }
            });

            // Quantity input change
            $(document).on('change', '.cart_qty_input', function() {
                var key = $(this).data('key');
                var val = parseInt($(this).val()) || 1;
                if (val < 1) val = 1;
                if (val > 999) val = 999;
                $(this).val(val);
                updateCartItem(key, val);
            });

            // Remove item
            $(document).on('click', '.cart_remove_btn', function(e) {
                e.preventDefault();
                var key = $(this).data('key');
                if (!confirm('Remove this item from cart?')) return;

                $.ajax({
                    url: '{{ route('storefront.cart.remove') }}',
                    method: 'POST',
                    data: {
                        cart_key: key
                    },
                    success: function(res) {
                        if (res.success) {
                            location.reload();
                        } else {
                            showToast(res.message || 'Failed to remove item.', 'error');
                        }
                    },
                    error: function(xhr) {
                        showToast(xhr.responseJSON?.message || 'Error removing item.', 'error');
                    }
                });
            });

            // ── Multi-select (Bug_12) ──
            function refreshSelectionState() {
                var total = $('.cart_item_check').length;
                var checked = $('.cart_item_check:checked').length;
                // Hidden by default; only revealed once at least one item is selected.
                $('#cartRemoveSelected').toggleClass('is-visible', checked > 0);
                var allChecked = total > 0 && checked === total;
                $('#cartSelectAll, #cartSelectAllHead').prop('checked', allChecked);
            }

            $(document).on('change', '#cartSelectAll, #cartSelectAllHead', function() {
                var checked = $(this).is(':checked');
                $('.cart_item_check').prop('checked', checked);
                refreshSelectionState();
            });

            $(document).on('change', '.cart_item_check', refreshSelectionState);

            $('#cartRemoveSelected').on('click', function(e) {
                e.preventDefault();
                var keys = $('.cart_item_check:checked').map(function() {
                    return $(this).val();
                }).get();
                if (!keys.length) return;
                if (!confirm('Remove ' + keys.length + ' selected item(s) from cart?')) return;

                var btn = $(this);
                btn.prop('disabled', true);
                $.ajax({
                    url: '{{ route('storefront.cart.remove.selected') }}',
                    method: 'POST',
                    data: {
                        cart_keys: keys
                    },
                    success: function(res) {
                        if (res.success) {
                            location.reload();
                        } else {
                            showToast(res.message || 'Failed to remove items.', 'error');
                            btn.prop('disabled', false);
                        }
                    },
                    error: function(xhr) {
                        showToast(xhr.responseJSON?.message || 'Error removing items.',
                            'error');
                        btn.prop('disabled', false);
                    }
                });
            });

            // Apply coupon
            $('#couponForm').on('submit', function(e) {
                e.preventDefault();
                var code = $('#couponCodeInput').val().trim();
                if (!code) {
                    showToast('Please enter a coupon code.', 'error');
                    return;
                }

                var btn = $('#applyCouponBtn');
                btn.prop('disabled', true);

                $.ajax({
                    url: '{{ route('storefront.cart.coupon.apply') }}',
                    method: 'POST',
                    data: {
                        coupon_code: code
                    },
                    success: function(res) {
                        if (res.success) {
                            showToast(res.message || 'Coupon applied!', 'success');
                            location.reload();
                        } else {
                            showToast(res.message || 'Invalid coupon code.', 'error');
                        }
                    },
                    error: function(xhr) {
                        showToast(xhr.responseJSON?.message || 'Could not apply coupon.',
                            'error');
                    },
                    complete: function() {
                        btn.prop('disabled', false);
                    }
                });
            });

            // Remove coupon
            $(document).on('click', '#removeCouponBtn', function(e) {
                e.preventDefault();
                $.ajax({
                    url: '{{ route('storefront.cart.coupon.remove') }}',
                    method: 'POST',
                    success: function() {
                        location.reload();
                    },
                    error: function(xhr) {
                        showToast(xhr.responseJSON?.message || 'Error removing coupon.',
                            'error');
                    }
                });
            });
        });
    </script>
@endpush
