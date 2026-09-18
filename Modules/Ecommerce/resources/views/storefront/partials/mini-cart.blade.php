{{-- Mini Cart Offcanvas Sidebar (Zenis Style) --}}
<div class="mini_cart">
    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasRight" aria-labelledby="offcanvasRightLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="offcanvasRightLabel">My Cart <span class="cart-count">
                    ({{ count(session('cart', [])) }})</span></h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"><i
                    class="fas fa-times"></i></button>
        </div>
        <div class="offcanvas-body" id="offcanvasRightBody">
            @include('ecommerce::storefront.partials.mini-cart-items', [
                'cartItems' => session('cart', []),
            ])
        </div>
    </div>
</div>

@push('scripts')
    <script>
        (function() {
            'use strict';
            if (window.__bpMiniCartHandlersBound) return;
            window.__bpMiniCartHandlersBound = true;

            // Self-contained delegated remove handler — works regardless of cart.js
            // load order, and survives drawer body replacement via $.html().
            jQuery(document).on('click', '.mini-cart-remove, .remove-cart-item', function(e) {
                e.preventDefault();
                e.stopPropagation();

                var $btn = jQuery(this);
                // Read both attribute names to be safe; force String to avoid jQuery
                // auto-casting numeric keys to integers.
                var cartKey = String($btn.attr('data-cart-key') || $btn.attr('data-key') || '').trim();
                if (!cartKey) return;

                $btn.css('pointer-events', 'none');

                jQuery.ajax({
                    url: '{{ route('storefront.cart.remove') }}',
                    method: 'POST',
                    data: {
                        cart_key: cartKey
                    },
                    success: function(data) {
                        if (data && data.success) {
                            if (data.cart_count !== undefined) {
                                jQuery('.cart-count').text(data.cart_count);
                                jQuery('.cart-badge').text(data.cart_count).toggleClass('d-none',
                                    data.cart_count === 0);
                            }
                            if (data.mini_cart_html !== undefined) {
                                jQuery('#offcanvasRightBody').html(data.mini_cart_html);
                            }
                            if (window.bpToast) {
                                window.bpToast('Item removed', 'success');
                            }
                        }
                    },
                    error: function(xhr) {
                        console.error('Mini-cart remove failed', xhr.status, xhr.responseText);
                        $btn.css('pointer-events', '');
                        alert(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON
                            .message : 'Could not remove item.');
                    }
                });
            });
        })();
    </script>
@endpush
