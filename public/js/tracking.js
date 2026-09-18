'use strict';

/**
 * BizPOS Tracking Helper
 * Fires events to both the GTM dataLayer (GA4-shaped ecommerce) and Facebook Pixel.
 *
 * Usage:
 *   BizPOS.track('AddToCart', { value: 1190, currency: 'BDT', items: [...] },
 *                { fbData: { content_ids: ['7'], contents: [...], value: 1190, currency: 'BDT' } });
 *
 * options.userData (enhanced-conversions shape: email_address, phone_number,
 * address) rides along as user_data on the dataLayer push and is set on gtag
 * for GA4 enhanced conversions.
 */
window.BizPOS = window.BizPOS || {};

window.BizPOS.track = function (eventName, params, options) {
    params = params || {};
    options = options || {};

    var gtmEventMap = {
        'ViewContent': 'view_item',
        'ViewCategory': 'view_item_list',
        'Search': 'search',
        'AddToCart': 'add_to_cart',
        'RemoveFromCart': 'remove_from_cart',
        'ViewCart': 'view_cart',
        'InitiateCheckout': 'begin_checkout',
        'AddShippingInfo': 'add_shipping_info',
        'AddPaymentInfo': 'add_payment_info',
        'Purchase': 'purchase',
        'Lead': 'generate_lead',
        'AddToWishlist': 'add_to_wishlist',
        'AddToCompare': 'add_to_compare',
        'CompleteRegistration': 'sign_up',
        'Login': 'login',
        'SelectItem': 'select_item',
        'RemoveFromWishlist': 'remove_from_wishlist',
        'ViewPromotion': 'view_promotion',
        'SelectPromotion': 'select_promotion'
    };

    // ── GTM dataLayer (GA4 ecommerce shape) ──
    if (window.dataLayer) {
        var gtmEvent = options.gtmEvent || gtmEventMap[eventName] || eventName;
        // Clear the previous ecommerce object so events don't bleed into each other.
        window.dataLayer.push({ ecommerce: null });

        var payload = { event: gtmEvent };
        var ecommerce = {};
        if (params.currency) { ecommerce.currency = params.currency; }
        if (typeof params.value !== 'undefined') { ecommerce.value = params.value; }
        if (params.items) { ecommerce.items = params.items; }
        if (params.transaction_id) { ecommerce.transaction_id = params.transaction_id; }
        if (params.coupon) { ecommerce.coupon = params.coupon; }
        if (params.shipping_tier) { ecommerce.shipping_tier = params.shipping_tier; }
        if (params.payment_type) { ecommerce.payment_type = params.payment_type; }
        if (typeof params.shipping !== 'undefined') { ecommerce.shipping = params.shipping; }
        if (typeof params.tax !== 'undefined') { ecommerce.tax = params.tax; }
        if (params.customer_type) { payload.customer_type = params.customer_type; }
        if (params.search_term) { payload.search_term = params.search_term; }
        if (params.item_list_id) { ecommerce.item_list_id = params.item_list_id; }
        if (params.item_list_name) { ecommerce.item_list_name = params.item_list_name; }
        if (params.promotion_id) { ecommerce.promotion_id = params.promotion_id; }
        if (params.promotion_name) { ecommerce.promotion_name = params.promotion_name; }
        if (params.creative_name) { ecommerce.creative_name = params.creative_name; }
        if (params.creative_slot) { ecommerce.creative_slot = params.creative_slot; }
        if (params.method) { payload.method = params.method; }
        if (options.userData && Object.keys(options.userData).length) { payload.user_data = options.userData; }
        if (options.customerData && Object.keys(options.customerData).length) { payload.customer = options.customerData; }
        if (Object.keys(ecommerce).length) { payload.ecommerce = ecommerce; }
        // Exposed so a Facebook Pixel tag configured *inside GTM* can be set
        // to use this as its Event ID, matching the server-side CAPI event
        // (TrackingService::reportPurchase) for Meta's deduplication.
        if (options.eventID) { payload.event_id = options.eventID; }

        window.dataLayer.push(payload);
    }

    // ── Direct GA4 (gtag.js) ──
    if (typeof window.gtag === 'function') {
        var ga4Event = options.gtmEvent || gtmEventMap[eventName] || eventName;
        var ga4Params = {};
        if (params.currency) { ga4Params.currency = params.currency; }
        if (typeof params.value !== 'undefined') { ga4Params.value = params.value; }
        if (params.items) { ga4Params.items = params.items; }
        if (params.transaction_id) { ga4Params.transaction_id = params.transaction_id; }
        if (params.coupon) { ga4Params.coupon = params.coupon; }
        if (params.shipping_tier) { ga4Params.shipping_tier = params.shipping_tier; }
        if (params.payment_type) { ga4Params.payment_type = params.payment_type; }
        if (typeof params.shipping !== 'undefined') { ga4Params.shipping = params.shipping; }
        if (typeof params.tax !== 'undefined') { ga4Params.tax = params.tax; }
        if (params.customer_type) { ga4Params.customer_type = params.customer_type; }
        if (params.search_term) { ga4Params.search_term = params.search_term; }
        if (params.item_list_id) { ga4Params.item_list_id = params.item_list_id; }
        if (params.item_list_name) { ga4Params.item_list_name = params.item_list_name; }
        if (params.promotion_id) { ga4Params.promotion_id = params.promotion_id; }
        if (params.promotion_name) { ga4Params.promotion_name = params.promotion_name; }
        if (params.creative_name) { ga4Params.creative_name = params.creative_name; }
        if (params.creative_slot) { ga4Params.creative_slot = params.creative_slot; }
        if (params.method) { ga4Params.method = params.method; }
        if (options.userData && Object.keys(options.userData).length) {
            // Enhanced conversions — gtag hashes these before sending.
            window.gtag('set', 'user_data', options.userData);
        }
        window.gtag('event', ga4Event, ga4Params);
    }

    // ── Facebook Pixel ──
    // Gated on this app's own Pixel embed being enabled, not merely on
    // whether window.fbq happens to exist — a Facebook Pixel tag configured
    // inside GTM defines the same window.fbq, and firing this too then
    // double-reports every event straight to Facebook, uncoordinated with
    // whatever GTM's own tag sends.
    var fbPixelEnabled = !!(window.BizPOSTracking && window.BizPOSTracking.fbPixelEnabled);
    if (fbPixelEnabled && typeof fbq === 'function') {
        var fbEvent = options.fbEvent || eventName;
        var customEvents = ['RemoveFromCart', 'ViewCart', 'AddToCompare', 'Login', 'SelectItem',
            'RemoveFromWishlist', 'ViewPromotion', 'SelectPromotion', 'ViewCategory'];
        var isCustom = options.fbCustom || customEvents.indexOf(eventName) !== -1;
        var fbData = options.fbData || {};
        var fbOpts = options.eventID ? { eventID: options.eventID } : undefined;

        if (isCustom) {
            fbq('trackCustom', fbEvent, fbData, fbOpts);
        } else {
            fbq('track', fbEvent, fbData, fbOpts);
        }
    }
};
