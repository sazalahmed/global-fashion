/**
 * Global Cart, Wishlist, and Compare JavaScript
 * Uses jQuery $.ajax (CSRF token set via $.ajaxSetup in master layout)
 */

(function ($) {
    'use strict';

    // Build an analytics item payload from a button's data-* attributes.
    function bpItemFromEl(el) {
        var $el = $(el);
        // Buttons don't carry discount; fall back to the enclosing card root.
        var discount = Number($el.data('discount')) || Number($el.closest('.js-bp-item').data('discount')) || 0;
        var item = {
            item_id: String($el.data('id') || $el.data('product-id') || ''),
            item_name: $el.data('name') || '',
            price: Number($el.data('price')) || 0,
            item_brand: $el.data('brand') || undefined,
            item_category: $el.data('category') || undefined,
            item_variant: $el.data('variant') || undefined,
            quantity: 1
        };
        if (discount > 0) { item.discount = discount; }
        return item;
    }

    // Toast notification system
    var Toast = {
        show: function (message, type) {
            type = type || 'success';
            $('.cart-toast').remove();

            var iconClass = type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle';
            var toast = $(
                '<div class="cart-toast cart-toast-' + type + '">' +
                '<div class="cart-toast-icon"><i class="fas fa-' + iconClass + '"></i></div>' +
                '<div class="cart-toast-message">' + message + '</div>' +
                '<button class="cart-toast-close"><i class="fas fa-times"></i></button>' +
                '</div>'
            );

            $('body').append(toast);

            toast.find('.cart-toast-close').on('click', function () {
                toast.removeClass('show');
                setTimeout(function () { toast.remove(); }, 300);
            });

            requestAnimationFrame(function () {
                toast.addClass('show');
            });

            setTimeout(function () {
                toast.removeClass('show');
                setTimeout(function () { toast.remove(); }, 300);
            }, 4000);
        }
    };

    // Cart Module
    var Cart = {
        add: function (productId, quantity, variantId) {
            quantity = quantity || 1;
            variantId = variantId || null;

            var btn = $('.add-to-cart[data-product-id="' + productId + '"]');
            btn.addClass('loading').css('pointer-events', 'none');

            $.ajax({
                url: '/cart/add',
                method: 'POST',
                data: {
                    product_id: productId,
                    quantity: quantity,
                    variant_id: variantId
                },
                success: function (data) {
                    if (data.success) {
                        Toast.show(data.message || 'Product added to cart!', 'success');

                        if (data.cart_count !== undefined) {
                            $('.cart-count').text(data.cart_count);
                            $('.cart-badge').text(data.cart_count).toggleClass('d-none', data.cart_count === 0);
                        }

                        // Replace the drawer body with the freshly-rendered HTML.
                        if (data.mini_cart_html !== undefined) {
                            $('#offcanvasRightBody').html(data.mini_cart_html);
                        }

                        btn.addClass('in-cart');

                        // Open mini cart offcanvas
                        var offcanvas = document.getElementById('offcanvasRight');
                        if (offcanvas && typeof bootstrap !== 'undefined') {
                            var bsOffcanvas = bootstrap.Offcanvas.getOrCreateInstance(offcanvas);
                            bsOffcanvas.show();
                        }

                        if (window.BizPOS) {
                            // Prefer the .add-to-cart btn; fall back to the buy-now-trigger
                            // (product cards use .buy-now-trigger with data-mode="cart"),
                            // then to the product-detail action buttons (#addToCartBtn /
                            // #buyNowBtn carry name/price/variant/discount data).
                            var triggerEl = btn.length ? btn
                                : $('[data-product-id="' + productId + '"].buy-now-trigger, ' +
                                    '#addToCartBtn[data-product-id="' + productId + '"], ' +
                                    '#buyNowBtn[data-product-id="' + productId + '"]').first();
                            var item = bpItemFromEl(triggerEl);
                            item.item_id = item.item_id || String(productId);
                            item.quantity = quantity || 1;
                            BizPOS.track('AddToCart',
                                { currency: 'BDT', value: item.price * item.quantity, items: [item] },
                                { fbData: { content_type: 'product', content_ids: [item.item_id], contents: [{ id: item.item_id, quantity: item.quantity, item_price: item.price }], value: item.price * item.quantity, currency: 'BDT' } });
                        }
                    } else {
                        Toast.show(data.message || 'Failed to add product', 'error');
                    }
                },
                error: function (xhr) {
                    var msg = 'Failed to add product to cart';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    Toast.show(msg, 'error');
                    console.error('Cart add error:', xhr.status, xhr.responseText);
                },
                complete: function () {
                    btn.removeClass('loading').css('pointer-events', '');
                }
            });
        },

        // Add a whole combo package to the cart as a single line. Combos have
        // fixed components (no variant picker), so this is a direct POST —
        // unlike Cart.add, which products route through the variant modal.
        // comboSize is only sent for size-required combos (resolved by
        // BuyNow.addComboWithDefaultSize before this is ever called with one).
        addCombo: function (comboId, quantity, comboSize) {
            quantity = quantity || 1;

            var btn = $('.combo-add-cart-trigger[data-combo-id="' + comboId + '"]');
            btn.addClass('loading').css('pointer-events', 'none');

            $.ajax({
                url: '/cart/add-combo',
                method: 'POST',
                data: {
                    combo_id: comboId,
                    quantity: quantity,
                    combo_size: comboSize || undefined
                },
                success: function (data) {
                    if (data.success) {
                        Toast.show(data.message || 'Combo added to cart!', 'success');

                        if (data.cart_count !== undefined) {
                            $('.cart-count').text(data.cart_count);
                            $('.cart-badge').text(data.cart_count).toggleClass('d-none', data.cart_count === 0);
                        }

                        if (data.mini_cart_html !== undefined) {
                            $('#offcanvasRightBody').html(data.mini_cart_html);
                        }

                        btn.addClass('in-cart');

                        var offcanvas = document.getElementById('offcanvasRight');
                        if (offcanvas && typeof bootstrap !== 'undefined') {
                            bootstrap.Offcanvas.getOrCreateInstance(offcanvas).show();
                        }
                    } else {
                        Toast.show(data.message || 'Failed to add combo', 'error');
                    }
                },
                error: function (xhr) {
                    var msg = 'Failed to add combo to cart';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    Toast.show(msg, 'error');
                    console.error('Combo add error:', xhr.status, xhr.responseText);
                },
                complete: function () {
                    btn.removeClass('loading').css('pointer-events', '');
                }
            });
        },

        remove: function (cartKey) {
            // Snapshot the line's tracking data from the mini-cart row (present
            // in the layout on every page) BEFORE the AJAX replaces the drawer.
            var $bpRow = $('.mini-cart-item[data-cart-key="' + cartKey + '"]').first();
            var removedItem = null;
            if ($bpRow.length && $bpRow.data('item-id')) {
                removedItem = {
                    item_id: String($bpRow.data('item-id')),
                    item_name: $bpRow.data('item-name') || '',
                    price: Number($bpRow.data('price')) || 0,
                    quantity: Number($bpRow.data('quantity')) || 1
                };
                var bpVariant = $bpRow.data('variant');
                if (bpVariant) { removedItem.item_variant = String(bpVariant); }
                var bpDiscount = Number($bpRow.data('discount')) || 0;
                if (bpDiscount > 0) { removedItem.discount = bpDiscount; }
            }

            $.ajax({
                url: '/cart/remove',
                method: 'POST',
                data: { cart_key: cartKey },
                success: function (data) {
                    if (data.success) {
                        Toast.show('Product removed from cart', 'success');

                        if (data.cart_count !== undefined) {
                            $('.cart-count').text(data.cart_count);
                            $('.cart-badge').text(data.cart_count).toggleClass('d-none', data.cart_count === 0);
                        }

                        // Server returns the updated drawer body — use it as
                        // the source of truth (also handles empty-cart state).
                        if (data.mini_cart_html !== undefined) {
                            $('#offcanvasRightBody').html(data.mini_cart_html);
                        } else {
                            var miniCartItem = $('.mini-cart-item[data-cart-key="' + cartKey + '"]');
                            miniCartItem.fadeOut(300, function () { $(this).remove(); });
                            if (data.cart_count === 0) {
                                Cart.showEmptyCart();
                            }
                        }

                        if (window.BizPOS) {
                            // Prefer the full item snapshot from the drawer row;
                            // fall back to the id-only shape if the row was gone.
                            var removedPayload = removedItem || {
                                item_id: data.product_id ? String(data.product_id) : String(cartKey),
                                quantity: 1
                            };
                            var removeParams = { currency: 'BDT', items: [removedPayload] };
                            if (removedPayload.price) {
                                removeParams.value = Math.round(removedPayload.price * removedPayload.quantity * 100) / 100;
                            }
                            BizPOS.track('RemoveFromCart', removeParams, {});
                        }
                    }
                },
                error: function (xhr) {
                    Toast.show('Failed to remove product', 'error');
                    console.error('Cart remove error:', xhr.status, xhr.responseText);
                }
            });
        },

        showEmptyCart: function () {
            var container = $('#mini-cart-items');
            if (container.length) {
                container.html(
                    '<li class="mini-cart-empty text-center py-5">' +
                    '<i class="fas fa-shopping-cart fa-3x text-muted mb-3 d-block"></i>' +
                    '<p class="text-muted mb-3">Your cart is empty</p>' +
                    '<a href="/shop" class="common_btn mini-cart-btn">Start Shopping</a>' +
                    '</li>'
                );
            }
            // Remove the subtotal + checkout footer when cart is empty.
            $('#mini-cart-subtotal').remove();
            $('#mini-cart-footer').remove();
        }
    };

    // Wishlist Module
    var Wishlist = {
        toggle: function (productId, clickedBtn) {
            var btns = $('.add-to-wishlist[data-product-id="' + productId + '"]');
            var isActive = clickedBtn ? $(clickedBtn).hasClass('active') : btns.first().hasClass('active');
            if (isActive) {
                Wishlist.remove(productId);
            } else {
                Wishlist.add(productId);
            }
        },

        add: function (productId) {
            var btns = $('.add-to-wishlist[data-product-id="' + productId + '"]');
            btns.addClass('loading');

            $.ajax({
                url: '/wishlist/add',
                method: 'POST',
                data: { product_id: productId },
                success: function (data) {
                    if (data.success) {
                        Toast.show(data.message || 'Added to wishlist!', 'success');
                        btns.addClass('active');

                        if (data.wishlist_count !== undefined) {
                            $('.wishlist-count').text(data.wishlist_count);
                        }

                        if (window.BizPOS) {
                            var w = bpItemFromEl(btns.first());
                            w.item_id = w.item_id || String(productId);
                            BizPOS.track('AddToWishlist',
                                { currency: 'BDT', value: w.price, items: [w] },
                                { fbData: { content_ids: [w.item_id], content_type: 'product', value: w.price, currency: 'BDT' } });
                        }
                    } else {
                        Toast.show(data.message || 'Failed to update wishlist', 'error');
                    }
                },
                error: function (xhr) {
                    Toast.show('Failed to update wishlist', 'error');
                    console.error('Wishlist add error:', xhr.status, xhr.responseText);
                },
                complete: function () {
                    btns.removeClass('loading');
                }
            });
        },

        remove: function (productId) {
            var btns = $('.add-to-wishlist[data-product-id="' + productId + '"]');
            btns.addClass('loading');

            $.ajax({
                url: '/wishlist/remove',
                method: 'POST',
                data: { product_id: productId },
                success: function (data) {
                    if (data.success) {
                        Toast.show(data.message || 'Removed from wishlist', 'success');
                        btns.removeClass('active');

                        if (data.wishlist_count !== undefined) {
                            $('.wishlist-count').text(data.wishlist_count);
                        }

                        if (window.BizPOS) {
                            var w = bpItemFromEl(btns.first());
                            w.item_id = w.item_id || String(productId);
                            BizPOS.track('RemoveFromWishlist',
                                { currency: 'BDT', value: w.price, items: [w] },
                                { fbData: { content_ids: [w.item_id], content_type: 'product', value: w.price, currency: 'BDT' } });
                        }
                    } else {
                        Toast.show(data.message || 'Failed to update wishlist', 'error');
                    }
                },
                error: function (xhr) {
                    Toast.show('Failed to update wishlist', 'error');
                    console.error('Wishlist remove error:', xhr.status, xhr.responseText);
                },
                complete: function () {
                    btns.removeClass('loading');
                }
            });
        },

        // ── Combo variants ─────────────────────────────────────────────
        // Combos POST combo_id (not product_id) and match buttons by
        // data-combo-id, but share the same endpoints, toast, and count badge.
        toggleCombo: function (comboId, clickedBtn) {
            var btns = $('.add-to-wishlist[data-combo-id="' + comboId + '"]');
            var isActive = clickedBtn ? $(clickedBtn).hasClass('active') : btns.first().hasClass('active');
            if (isActive) {
                Wishlist.removeCombo(comboId);
            } else {
                Wishlist.addCombo(comboId);
            }
        },

        addCombo: function (comboId) {
            var btns = $('.add-to-wishlist[data-combo-id="' + comboId + '"]');
            btns.addClass('loading');
            $.ajax({
                url: '/wishlist/add',
                method: 'POST',
                data: { combo_id: comboId },
                success: function (data) {
                    if (data.success) {
                        Toast.show(data.message || 'Added to wishlist!', 'success');
                        btns.addClass('active');
                        if (data.wishlist_count !== undefined) {
                            $('.wishlist-count').text(data.wishlist_count);
                        }

                        if (window.BizPOS) {
                            var wc = bpItemFromEl(btns.first());
                            wc.item_id = 'combo:' + comboId;
                            BizPOS.track('AddToWishlist',
                                { currency: 'BDT', value: wc.price, items: [wc] },
                                { fbData: { content_ids: [wc.item_id], content_type: 'product', value: wc.price, currency: 'BDT' } });
                        }
                    } else {
                        Toast.show(data.message || 'Failed to update wishlist', 'error');
                    }
                },
                error: function (xhr) {
                    Toast.show('Failed to update wishlist', 'error');
                    console.error('Wishlist combo add error:', xhr.status, xhr.responseText);
                },
                complete: function () {
                    btns.removeClass('loading');
                }
            });
        },

        removeCombo: function (comboId) {
            var btns = $('.add-to-wishlist[data-combo-id="' + comboId + '"]');
            btns.addClass('loading');
            $.ajax({
                url: '/wishlist/remove',
                method: 'POST',
                data: { combo_id: comboId },
                success: function (data) {
                    if (data.success) {
                        Toast.show(data.message || 'Removed from wishlist', 'success');
                        btns.removeClass('active');
                        if (data.wishlist_count !== undefined) {
                            $('.wishlist-count').text(data.wishlist_count);
                        }

                        if (window.BizPOS) {
                            var wc = bpItemFromEl(btns.first());
                            wc.item_id = 'combo:' + comboId;
                            BizPOS.track('RemoveFromWishlist',
                                { currency: 'BDT', value: wc.price, items: [wc] },
                                { fbData: { content_ids: [wc.item_id], content_type: 'product', value: wc.price, currency: 'BDT' } });
                        }
                    } else {
                        Toast.show(data.message || 'Failed to update wishlist', 'error');
                    }
                },
                error: function (xhr) {
                    Toast.show('Failed to update wishlist', 'error');
                    console.error('Wishlist combo remove error:', xhr.status, xhr.responseText);
                },
                complete: function () {
                    btns.removeClass('loading');
                }
            });
        }
    };

    // Compare Module — mirrors Wishlist: toggle add/remove, keep the
    // navigation .compare-count in sync, and reflect state on every button
    // for the same product (card hover + product detail).
    var Compare = {
        toggle: function (productId, clickedBtn) {
            var btns = $('.add-to-compare[data-product-id="' + productId + '"]');
            var isActive = clickedBtn ? $(clickedBtn).hasClass('active') : btns.first().hasClass('active');
            if (isActive) {
                Compare.remove(productId);
            } else {
                Compare.add(productId);
            }
        },

        add: function (productId) {
            var btns = $('.add-to-compare[data-product-id="' + productId + '"]');
            btns.addClass('loading');

            $.ajax({
                url: '/compare/add',
                method: 'POST',
                data: { product_id: productId },
                success: function (data) {
                    if (data.success) {
                        Toast.show(data.message || 'Added to compare', 'success');
                        btns.addClass('active');
                        if (data.compare_count !== undefined) {
                            $('.compare-count').text(data.compare_count);
                        }

                        if (window.BizPOS) {
                            var c = bpItemFromEl(btns.first());
                            c.item_id = c.item_id || String(productId);
                            BizPOS.track('AddToCompare', { items: [c] }, {});
                        }
                    } else {
                        Toast.show(data.message || 'Failed to update compare', 'error');
                    }
                },
                error: function (xhr) {
                    // 422 = compare list is full (or validation). Surface the
                    // server message rather than a generic error.
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Failed to update compare';
                    Toast.show(msg, 'error');
                },
                complete: function () {
                    btns.removeClass('loading');
                }
            });
        },

        remove: function (productId) {
            var btns = $('.add-to-compare[data-product-id="' + productId + '"]');
            btns.addClass('loading');

            $.ajax({
                url: '/compare/remove',
                method: 'POST',
                data: { product_id: productId },
                success: function (data) {
                    if (data.success) {
                        Toast.show(data.message || 'Removed from compare', 'success');
                        btns.removeClass('active');
                        if (data.compare_count !== undefined) {
                            $('.compare-count').text(data.compare_count);
                        }
                    } else {
                        Toast.show(data.message || 'Failed to update compare', 'error');
                    }
                },
                error: function (xhr) {
                    Toast.show('Failed to update compare', 'error');
                    console.error('Compare remove error:', xhr.status, xhr.responseText);
                },
                complete: function () {
                    btns.removeClass('loading');
                }
            });
        },

        // ── Combo variants ─────────────────────────────────────────────
        toggleCombo: function (comboId, clickedBtn) {
            var btns = $('.add-to-compare[data-combo-id="' + comboId + '"]');
            var isActive = clickedBtn ? $(clickedBtn).hasClass('active') : btns.first().hasClass('active');
            if (isActive) {
                Compare.removeCombo(comboId);
            } else {
                Compare.addCombo(comboId);
            }
        },

        addCombo: function (comboId) {
            var btns = $('.add-to-compare[data-combo-id="' + comboId + '"]');
            btns.addClass('loading');
            $.ajax({
                url: '/compare/add',
                method: 'POST',
                data: { combo_id: comboId },
                success: function (data) {
                    if (data.success) {
                        Toast.show(data.message || 'Added to compare', 'success');
                        btns.addClass('active');
                        if (data.compare_count !== undefined) {
                            $('.compare-count').text(data.compare_count);
                        }
                    } else {
                        Toast.show(data.message || 'Failed to update compare', 'error');
                    }
                },
                error: function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Failed to update compare';
                    Toast.show(msg, 'error');
                },
                complete: function () {
                    btns.removeClass('loading');
                }
            });
        },

        removeCombo: function (comboId) {
            var btns = $('.add-to-compare[data-combo-id="' + comboId + '"]');
            btns.addClass('loading');
            $.ajax({
                url: '/compare/remove',
                method: 'POST',
                data: { combo_id: comboId },
                success: function (data) {
                    if (data.success) {
                        Toast.show(data.message || 'Removed from compare', 'success');
                        btns.removeClass('active');
                        if (data.compare_count !== undefined) {
                            $('.compare-count').text(data.compare_count);
                        }
                    } else {
                        Toast.show(data.message || 'Failed to update compare', 'error');
                    }
                },
                error: function (xhr) {
                    Toast.show('Failed to update compare', 'error');
                    console.error('Compare combo remove error:', xhr.status, xhr.responseText);
                },
                complete: function () {
                    btns.removeClass('loading');
                }
            });
        }
    };

    // ─────────────────────────────────────────────────────────────
    // Buy Now flow — handles the product card's "Order Now" button.
    // Simple products: silently add to cart and redirect to checkout.
    // Variable products: fetch variant data, silently pick the default
    // variant (no picker shown), then add + redirect.
    // ─────────────────────────────────────────────────────────────
    var BuyNow = {
        currentProduct: null,
        selectedVariantId: null,
        mode: 'checkout',

        start: function (trigger) {
            var $btn = $(trigger);
            var productId = parseInt($btn.data('product-id'), 10);
            var slug = $btn.data('product-slug');
            var hasVariants = String($btn.data('has-variants')) === '1';
            // 'cart' = add to cart and stay; 'checkout' (default) = add + go to checkout.
            BuyNow.mode = (String($btn.data('mode')) === 'cart') ? 'cart' : 'checkout';

            if (!productId || !slug) return;

            if (!hasVariants) {
                // No variants to pick — act immediately.
                if (BuyNow.mode === 'cart') {
                    Cart.add(productId);
                } else {
                    BuyNow.confirmAndCheckout(productId, null, 1, parseFloat($btn.data('price')) || 0);
                }
                return;
            }
            BuyNow.addWithDefaultVariant(productId, slug, $btn);
        },

        // The lowest-sort-order variant that's actually sellable — mirrors the
        // order the server already returns (product-detail default) — falling
        // back to the first variant if every one is out of stock.
        pickDefaultVariant: function (variants) {
            for (var i = 0; i < variants.length; i++) {
                if (variants[i].in_stock) return variants[i];
            }
            return variants[0] || null;
        },

        // Variable products no longer show a picker: fetch the variant matrix,
        // silently take the default variant, and add/checkout exactly as if
        // the shopper had picked it themselves.
        addWithDefaultVariant: function (productId, slug, $btn) {
            $btn.addClass('loading').css('pointer-events', 'none');

            $.ajax({
                url: '/shop/' + encodeURIComponent(slug) + '/variant-data',
                method: 'GET',
                dataType: 'json',
            })
                .done(function (data) {
                    var defaultVariant = BuyNow.pickDefaultVariant(data.variants || []);
                    BuyNow.currentProduct = data;
                    BuyNow.selectedVariantId = defaultVariant ? defaultVariant.id : null;

                    if (!defaultVariant) {
                        // No variant on record despite has-variants — fall back to a plain add.
                        if (BuyNow.mode === 'cart') {
                            Cart.add(productId);
                        } else {
                            BuyNow.confirmAndCheckout(productId, null, 1, parseFloat(data.effective_price) || 0);
                        }
                        return;
                    }

                    BuyNow.finish(productId, defaultVariant.id, 1);
                })
                .fail(function () {
                    Toast.show('Could not add product to cart.', 'error');
                })
                .always(function () {
                    $btn.removeClass('loading').css('pointer-events', '');
                });
        },

        // The lowest-sort-order size that's actually sellable, falling back
        // to the first size if every one is out of stock — same rule as
        // pickDefaultVariant, applied to a combo's size list.
        pickDefaultSize: function (sizes) {
            for (var i = 0; i < sizes.length; i++) {
                if (sizes[i].in_stock) return sizes[i];
            }
            return sizes[0] || null;
        },

        // Size-required combos no longer send the shopper to the detail page:
        // fetch the size list, silently take the default size, and add to
        // cart exactly as if the shopper had picked it themselves.
        addComboWithDefaultSize: function (comboId, slug, $btn) {
            $btn.addClass('loading').css('pointer-events', 'none');

            $.ajax({
                url: '/combos/' + encodeURIComponent(slug) + '/size-data',
                method: 'GET',
                dataType: 'json',
            })
                .done(function (data) {
                    var defaultSize = BuyNow.pickDefaultSize(data.sizes || []);
                    if (!defaultSize) {
                        Toast.show('This combo has no available size.', 'error');
                        return;
                    }
                    Cart.addCombo(comboId, 1, defaultSize.value);
                })
                .fail(function () {
                    Toast.show('Could not add combo to cart.', 'error');
                })
                .always(function () {
                    $btn.removeClass('loading').css('pointer-events', '');
                });
        },

        // Mode-aware completion: 'cart' adds to cart (toast + mini-cart, stays
        // on page); 'checkout' adds then redirects to checkout.
        finish: function (productId, variantId, quantity) {
            if (BuyNow.mode === 'cart') {
                Cart.add(productId, quantity, variantId);
                return;
            }
            BuyNow.confirmAndCheckout(productId, variantId, quantity);
        },

        confirmAndCheckout: function (productId, variantId, quantity, fallbackPrice) {
            $.ajax({
                url: '/cart/add',
                method: 'POST',
                data: {
                    product_id: productId,
                    variant_id: variantId,
                    quantity: quantity,
                },
                dataType: 'json',
            })
                .done(function () {
                    if (window.BizPOS) {
                        // Resolve unit price: use the variant's effective_price if a
                        // variant was selected, otherwise use the base product price.
                        var unitPrice = 0;
                        var variantSku = null;
                        var sellPrice = 0;
                        var p = BuyNow.currentProduct;
                        if (p) {
                            if (variantId && BuyNow.selectedVariantId) {
                                var v = (p.variants || []).filter(function (x) { return x.id === variantId; })[0];
                                unitPrice = v ? (parseFloat(v.effective_price) || parseFloat(v.sell_price) || 0) : (parseFloat(p.effective_price) || 0);
                                if (v) {
                                    variantSku = v.sku || null;
                                    sellPrice = parseFloat(v.sell_price) || 0;
                                }
                            } else {
                                unitPrice = parseFloat(p.effective_price) || 0;
                                sellPrice = parseFloat(p.sell_price) || 0;
                            }
                        }
                        // Simple-product direct checkout has no loaded currentProduct;
                        // fall back to the trigger button's data-price.
                        if (!unitPrice && fallbackPrice) {
                            unitPrice = parseFloat(fallbackPrice) || 0;
                        }
                        var qty = quantity || 1;
                        var b = { item_id: String(productId), price: unitPrice, quantity: qty };
                        if (variantSku) { b.item_variant = String(variantSku); }
                        if (sellPrice > unitPrice) { b.discount = Math.round((sellPrice - unitPrice) * 100) / 100; }
                        BizPOS.track('AddToCart',
                            { currency: 'BDT', value: b.price * b.quantity, items: [b] },
                            { fbData: { content_type: 'product', content_ids: [b.item_id], value: b.price * b.quantity, currency: 'BDT' } });
                    }
                    window.location.href = '/checkout';
                })
                .fail(function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Could not start checkout.';
                    if (Toast && Toast.show) Toast.show(msg, 'error');
                });
        },
    };

    // Promotions: build the ViewPromotion/SelectPromotion params from a
    // .js-bp-promo element's data attributes (hero/promo banners, campaign
    // sections). Declared at IIFE scope so both the ready-time view fire and
    // the delegated click handler below can reuse it.
    function bpPromoParams(el) {
        var $el = $(el);
        var params = {
            promotion_id: String($el.data('promotion-id') || ''),
            promotion_name: $el.data('promotion-name') || '',
            creative_slot: $el.data('creative-slot') || ''
        };
        var creativeName = $el.data('creative-name');
        if (creativeName) { params.creative_name = String(creativeName); }
        // Sections that feature concrete products embed them as JSON
        // (data-bp-items) so promotion events carry items per the GA4 spec.
        var items = $el.data('bp-items');
        if (items && typeof items === 'string') {
            try { items = JSON.parse(items); } catch (e) { items = null; }
        }
        if (items && items.length) {
            params.items = items;
            params.currency = 'BDT';
        }
        return params;
    }

    // Initialize event handlers when DOM is ready
    $(function () {
        // Buy Now (Order Now) trigger on product card — must be delegated
        // since cards may be re-rendered after AJAX shop filters.
        $(document).on('click', '.buy-now-trigger', function (e) {
            e.preventDefault();
            e.stopPropagation();
            BuyNow.start(this);
        });

        // Add to cart buttons (delegated)
        $(document).on('click', '.add-to-cart', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var productId = $(this).data('product-id');
            if (productId) {
                Cart.add(productId);
            }
        });

        // Combo card add-to-cart (delegated) — adds the whole combo in one POST,
        // resolving a default size first when the combo requires one.
        $(document).on('click', '.combo-add-cart-trigger', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var $btn = $(this);
            var comboId = $btn.data('combo-id');
            if (!comboId) return;

            if (String($btn.data('size-required')) === '1') {
                BuyNow.addComboWithDefaultSize(comboId, $btn.data('combo-slug'), $btn);
            } else {
                Cart.addCombo(comboId);
            }
        });

        // Wishlist toggle (delegated) — add on first click, remove on second.
        // A combo button carries data-combo-id and routes to the combo flow;
        // everything else is a product (data-product-id).
        $(document).on('click', '.add-to-wishlist', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var comboId = $(this).data('combo-id');
            if (comboId) {
                Wishlist.toggleCombo(comboId, this);
                return;
            }
            var productId = $(this).data('product-id');
            if (productId) {
                Wishlist.toggle(productId, this);
            }
        });

        // Compare toggle (delegated) — add on first click, remove on second.
        // The compare page's own remove buttons (.remove-from-compare) are
        // handled on that page; exclude them here so we don't double-fire.
        $(document).on('click', '.add-to-compare:not(.remove-from-compare)', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var comboId = $(this).data('combo-id');
            if (comboId) {
                Compare.toggleCombo(comboId, this);
                return;
            }
            var productId = $(this).data('product-id');
            if (productId) {
                Compare.toggle(productId, this);
            }
        });

        // Mini cart remove buttons (delegated)
        $(document).on('click', '.mini-cart-remove', function (e) {
            e.preventDefault();
            var cartKey = $(this).data('cart-key');
            if (cartKey) {
                Cart.remove(cartKey);
            }
        });

        // view_cart: opening the mini-cart drawer is a cart view too. Item
        // rows carry tracking data (mini-cart-items.blade.php) and are
        // re-rendered by /cart/add|update|remove, so the payload stays fresh.
        $(document).on('shown.bs.offcanvas', '#offcanvasRight', function () {
            if (!window.BizPOS) { return; }
            var items = [];
            var value = 0;
            $(this).find('.mini-cart-item').each(function () {
                var $li = $(this);
                var it = {
                    item_id: String($li.data('item-id') || ''),
                    item_name: $li.data('item-name') || '',
                    price: Number($li.data('price')) || 0,
                    quantity: Number($li.data('quantity')) || 1
                };
                if (!it.item_id) { return; }
                var variant = $li.data('variant');
                if (variant) { it.item_variant = String(variant); }
                var discount = Number($li.data('discount')) || 0;
                if (discount > 0) { it.discount = discount; }
                value += it.price * it.quantity;
                items.push(it);
            });
            if (!items.length) { return; }
            BizPOS.track('ViewCart',
                { currency: 'BDT', value: Math.round(value * 100) / 100, items: items },
                {});
        });

        // select_item: any navigation link inside a catalog card (product or
        // combo). Excludes the card's own action buttons — wishlist, compare,
        // and the buy-now/add-to-cart triggers — since those already fire
        // their own tracking events (AddToWishlist, AddToCompare, AddToCart).
        $(document).on('click', '.js-bp-item a[href]', function () {
            if (!window.BizPOS) { return; }
            if ($(this).is('.add-to-wishlist, .add-to-compare, .buy-now-trigger, .combo-add-cart-trigger')) { return; }
            var $card = $(this).closest('.js-bp-item');
            var item = {
                item_id: String($card.data('item-id') || ''),
                item_name: $card.data('item-name') || '',
                price: Number($card.data('price')) || 0,
                item_category: $card.data('category') || undefined,
                quantity: 1
            };
            var cardDiscount = Number($card.data('discount')) || 0;
            if (cardDiscount > 0) { item.discount = cardDiscount; }
            if (!item.item_id) { return; }
            // List context (item_list_id/name) is published by list pages
            // as window.bpListContext; absent on non-list pages.
            var selectParams = { currency: 'BDT', items: [item] };
            var listCtx = window.bpListContext;
            if (listCtx && listCtx.id) { selectParams.item_list_id = listCtx.id; }
            if (listCtx && listCtx.name) { selectParams.item_list_name = listCtx.name; }
            BizPOS.track('SelectItem', selectParams, {});
        });

        // ── Promotions: one view_promotion per rendered block, select on click ──
        $('.js-bp-promo').each(function () {
            if (!window.BizPOS) { return; }
            BizPOS.track('ViewPromotion', bpPromoParams(this), {});
        });

        $(document).on('click', '.js-bp-promo a[href]', function () {
            if (!window.BizPOS) { return; }
            // Product-card interactions inside a promo section are item events
            // (select_item / cart / wishlist), not promotion clicks.
            if ($(this).closest('.js-bp-item').length) { return; }
            var promo = $(this).closest('.js-bp-promo')[0];
            if (!promo) { return; }
            BizPOS.track('SelectPromotion', bpPromoParams(promo), {});
        });
    });

    // Inject toast styles if not already present
    if (!$('#cart-toast-styles').length) {
        $('head').append(
            '<style id="cart-toast-styles">' +
            '.cart-toast{position:fixed;bottom:20px;right:20px;display:flex;align-items:center;gap:12px;background:#28a745;color:#fff;padding:16px 20px;border-radius:10px;box-shadow:0 8px 25px rgba(0,0,0,.15);z-index:99999;transform:translateY(120px);opacity:0;transition:all .4s cubic-bezier(.68,-.55,.265,1.55);max-width:350px}' +
            '.cart-toast.show{transform:translateY(0);opacity:1}' +
            '.cart-toast-success{background:#28a745}' +
            '.cart-toast-error{background:#dc3545}' +
            '.cart-toast-info{background:#17a2b8}' +
            '.cart-toast-icon{font-size:20px}' +
            '.cart-toast-message{flex:1;font-weight:500}' +
            '.cart-toast-close{background:transparent;border:none;color:rgba(255,255,255,.7);cursor:pointer;padding:0;font-size:14px;transition:color .2s}' +
            '.cart-toast-close:hover{color:#fff}' +
            '</style>'
        );
    }

    // Expose modules globally
    window.EcommerceCart = Cart;
    window.EcommerceWishlist = Wishlist;
    window.EcommerceCompare = Compare;
    window.Toast = Toast;

})(jQuery);
