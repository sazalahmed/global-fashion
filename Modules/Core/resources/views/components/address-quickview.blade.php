{{-- Billing/Shipping address quick view: two read-only cards shown by default,
     with an Edit button that reveals the form's editable address fields
     (wrapped in #addrEditFields on the page). Card text + toggle are driven by
     each form's JS (field ids differ between sale and quotation).
     `show-contact` additionally renders a Name/Phone line in each card —
     only pass it from forms whose model has customer_name_snapshot /
     customer_phone_snapshot columns (Sale). --}}
@props(['showContact' => false])
<div class="col-12">
    <div class="bp-addr-quickview" id="addrCards">
        <div class="bp-addr-card">
            <div class="bp-addr-card-head">
                <span class="bp-addr-card-title"><i class="fa-solid fa-file-invoice-dollar me-1"></i>{{ __('Billing Address') }}</span>
            </div>
            @if ($showContact)
                <div class="bp-addr-card-contact" id="billingContactText"></div>
            @endif
            <div class="bp-addr-card-text" id="billingCardText"></div>
        </div>
        <div class="bp-addr-card">
            <div class="bp-addr-card-head">
                <span class="bp-addr-card-title">
                    <i class="fa-solid fa-truck me-1"></i>{{ __('Shipping Address') }}
                    <span class="bp-badge bp-badge-secondary fs-10 ms-1 d-none" id="shipSameBadge">{{ __('Same as billing') }}</span>
                </span>
                <button type="button" class="bp-btn bp-btn-sm bp-btn-outline" id="addrEditToggle">
                    <i class="fa-solid fa-pen me-1"></i>{{ __('Edit') }}
                </button>
            </div>
            @if ($showContact)
                <div class="bp-addr-card-contact" id="shippingContactText"></div>
            @endif
            <div class="bp-addr-card-text" id="shippingCardText"></div>
        </div>
    </div>
</div>
