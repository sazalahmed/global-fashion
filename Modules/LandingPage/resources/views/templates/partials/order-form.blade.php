{{-- Shared order form partial for all landing page templates --}}
<section class="billing_form {{ $formClass ?? '' }}" id="orderForm">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-11">
                <div class="{{ str_contains($formClass ?? '', 'perfume') ? 'perfume_heading' : 'tshirt_heading' }}">
                    <h2>অর্ডার করতে নিচের ফর্মটি পূরন করুন</h2>
                    @if($page->contact_phone)
                    <p>প্রয়োজনে ফোন করুন - <span>{{ $page->contact_phone }}</span></p>
                    @endif
                </div>
            </div>
        </div>
        <div class="row justify-content-center">
            <div class="col-xl-11">
                <form class="billing_checkout_form" action="{{ route('landing.order') }}" method="POST">
                    @csrf
                    <input type="hidden" name="landing_page_id" value="{{ $page->id }}">

                    <h3>প্রোডাক্ট সিলেক্ট করুন</h3>
                    <div class="row">
                        @foreach($products as $product)
                        <div class="col-lg-6 col-xl-6">
                            <div class="product_select">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="product_id"
                                        id="product_{{ $product->id }}" value="{{ $product->id }}" {{ $loop->first ? 'checked' : '' }}>
                                    <div class="product_select_details">
                                        <div class="img">
                                            <img src="{{ $product->image }}" alt="img-fluid w-100">
                                        </div>
                                        <div class="text">
                                            <h6>{{ $product->name }}</h6>
                                            <span>{{ currency_symbol() }} {{ number_format($product->sell_price) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <div class="row">
                        <div class="col-lg-6 col-xl-6">
                            <div class="billing_details">
                                <h4>আপনার বিলিং তথ্য দিন</h4>
                                <div class="billing_single_input">
                                    <label>আপনার নাম<span>*</span></label>
                                    <input type="text" name="customer_name" placeholder="আপনার নাম" required>
                                </div>
                                <div class="billing_single_input">
                                    <label>মোবাইল নাম্বার <span>*</span></label>
                                    <input type="text" name="customer_phone" placeholder="মোবাইল নাম্বার" required>
                                </div>
                                <div class="billing_single_input">
                                    <label>আপনার ঠিকানা<span>*</span></label>
                                    <input type="text" name="customer_address" placeholder="বাসা নং, রোড নং, গ্রাম/মহল্লা, থানা" required>
                                </div>
                                <div class="billing_single_input">
                                    <label>আপনার জেলা<span>*</span></label>
                                    <select class="select_2" name="district" required>
                                        <option value="">জেলা নির্বাচন করুন</option>
                                        <option value="ঢাকা">ঢাকা</option>
                                        <option value="চট্টগ্রাম">চট্টগ্রাম</option>
                                        <option value="রাজশাহী">রাজশাহী</option>
                                        <option value="খুলনা">খুলনা</option>
                                        <option value="বরিশাল">বরিশাল</option>
                                        <option value="সিলেট">সিলেট</option>
                                        <option value="রংপুর">রংপুর</option>
                                        <option value="ময়মনসিংহ">ময়মনসিংহ</option>
                                        <option value="কুমিল্লা">কুমিল্লা</option>
                                        <option value="গাজীপুর">গাজীপুর</option>
                                        <option value="নারায়ণগঞ্জ">নারায়ণগঞ্জ</option>
                                        <option value="যশোর">যশোর</option>
                                        <option value="ভোলা">ভোলা</option>
                                        <option value="বরগুনা">বরগুনা</option>
                                    </select>
                                </div>
                                <div class="billing_single_input">
                                    <label>আপনার বিভাগ<span>*</span></label>
                                    <select class="select_2" name="division" required>
                                        <option value="">বিভাগ নির্বাচন করুন</option>
                                        <option value="ঢাকা">ঢাকা</option>
                                        <option value="চট্টগ্রাম">চট্টগ্রাম</option>
                                        <option value="রাজশাহী">রাজশাহী</option>
                                        <option value="খুলনা">খুলনা</option>
                                        <option value="বরিশাল">বরিশাল</option>
                                        <option value="সিলেট">সিলেট</option>
                                        <option value="রংপুর">রংপুর</option>
                                        <option value="ময়মনসিংহ">ময়মনসিংহ</option>
                                    </select>
                                </div>
                                <div class="billing_single_input">
                                    <label>আপনার দেশ<span>*</span></label>
                                    <select class="select_2" name="country" required>
                                        <option value="">দেশ নির্বাচন করুন</option>
                                        <option value="বাংলাদেশ" selected>বাংলাদেশ</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6 col-xl-6">
                            <div class="billing_orders">
                                <h4>আপনার অর্ডার সমূহ</h4>
                                <div class="billing_orders_top">
                                    <h5>Product</h5>
                                    <h6>Subtotal</h6>
                                </div>
                                <ul class="billing_orders_product">
                                    @foreach($products->take(2) as $product)
                                    <li>
                                        <div class="product">
                                            <div class="img">
                                                <img src="{{ $product->image }}" alt="img-fluid w-100">
                                            </div>
                                            <div class="text">
                                                <h5>{{ $product->name }}</h5>
                                                <div class="product_quantity">
                                                    <button class="minus" type="button"><i class="fas fa-minus" aria-hidden="true"></i></button>
                                                    <input type="text" name="quantities[{{ $product->id }}]" placeholder="1" value="1">
                                                    <button class="plus" type="button"><i class="fas fa-plus" aria-hidden="true"></i></button>
                                                </div>
                                            </div>
                                        </div>
                                        <h6>{{ number_format($product->sell_price) }}৳</h6>
                                    </li>
                                    @endforeach
                                </ul>
                                <div class="billing_orders_subtotal">
                                    <div class="subtotal">
                                        <h5>Subtotal</h5>
                                        <h6 id="orderSubtotal">{{ number_format($page->offer_price ?? $products->sum('sell_price')) }}৳</h6>
                                    </div>
                                    <div class="subtotal">
                                        <h5>Shipping</h5>
                                        <div class="charge">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="shipping_zone" id="shippingInside" value="inside" checked>
                                                <label class="form-check-label" for="shippingInside">
                                                    ঢাকার ভিতরে ডেলিভারি চার্জ: {{ number_format($page->delivery_inside_dhaka) }}৳
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="shipping_zone" id="shippingOutside" value="outside">
                                                <label class="form-check-label" for="shippingOutside">
                                                    ঢাকার বাইরে ডেলিভারি চার্জ: {{ number_format($page->delivery_outside_dhaka) }}৳
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="billing_orders_total">
                                    <h5>Total</h5>
                                    <h6 id="orderTotal">{{ number_format(($page->offer_price ?? $products->sum('sell_price')) + $page->delivery_inside_dhaka) }}৳</h6>
                                </div>
                                <div class="product_payment">
                                    <div class="accordion product_payment_accordion" id="paymentAccordion">
                                        <div class="accordion-item">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#paymentCod" aria-expanded="true" aria-controls="paymentCod">
                                                    <input type="hidden" name="payment_method" value="cod">
                                                    Cash on delivery
                                                </button>
                                            </h2>
                                            <div id="paymentCod" class="accordion-collapse collapse show" data-bs-parent="#paymentAccordion">
                                                <div class="accordion-body"><p>Pay with cash upon delivery.</p></div>
                                            </div>
                                        </div>
                                        <div class="accordion-item">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#paymentBkash" aria-expanded="false" aria-controls="paymentBkash">
                                                    bKash <img src="{{ asset('vendor/landing/images/bkash.png') }}" alt="bKash">
                                                </button>
                                            </h2>
                                            <div id="paymentBkash" class="accordion-collapse collapse" data-bs-parent="#paymentAccordion">
                                                <div class="accordion-body">
                                                    <p>Please complete your bKash Send Money at first, then fill up the form below.</p>
                                                    <div class="pay_info">
                                                        <label>bKash Number</label>
                                                        <input type="text" name="bkash_number" placeholder="016XXXXXXXXX">
                                                    </div>
                                                    <div class="pay_info">
                                                        <label>bKash Transaction ID</label>
                                                        <input type="text" name="bkash_txn_id" placeholder="SDD4674GH77J7">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="accordion-item">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#paymentRocket" aria-expanded="false" aria-controls="paymentRocket">
                                                    Rocket <img src="{{ asset('vendor/landing/images/rocket.png') }}" alt="Rocket">
                                                </button>
                                            </h2>
                                            <div id="paymentRocket" class="accordion-collapse collapse" data-bs-parent="#paymentAccordion">
                                                <div class="accordion-body">
                                                    <p>Please complete your Rocket Send Money at first, then fill up the form below.</p>
                                                    <div class="pay_info">
                                                        <label>Rocket Number</label>
                                                        <input type="text" name="rocket_number" placeholder="016XXXXXXXXX">
                                                    </div>
                                                    <div class="pay_info">
                                                        <label>Rocket Transaction ID</label>
                                                        <input type="text" name="rocket_txn_id" placeholder="SDD4674GH77J7">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="accordion-item">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#paymentNagad" aria-expanded="false" aria-controls="paymentNagad">
                                                    Nagad <img src="{{ asset('vendor/landing/images/nagad.png') }}" alt="Nagad">
                                                </button>
                                            </h2>
                                            <div id="paymentNagad" class="accordion-collapse collapse" data-bs-parent="#paymentAccordion">
                                                <div class="accordion-body">
                                                    <p>Please complete your Nagad Send Money at first, then fill up the form below.</p>
                                                    <div class="pay_info">
                                                        <label>Nagad Number</label>
                                                        <input type="text" name="nagad_number" placeholder="016XXXXXXXXX">
                                                    </div>
                                                    <div class="pay_info">
                                                        <label>Nagad Transaction ID</label>
                                                        <input type="text" name="nagad_txn_id" placeholder="SDD4674GH77J7">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product_payment_btn">
                                    <p>Your personal data will be used to process your order, support your experience throughout this website, and for other purposes described in our <a href="#">privacy policy</a>.</p>
                                    <button type="submit">
                                        <i class="fas fa-lock-alt"></i>
                                        অর্ডার করুন
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

@push('scripts')
<script>
'use strict';
$(function () {
    // Payment method tracking via accordion
    $('#paymentAccordion .accordion-button').on('click', function () {
        var method = $(this).closest('.accordion-item').find('input[name$="_txn_id"]').length ?
            $(this).text().trim().toLowerCase().split(' ')[0] : 'cod';
        $('input[name="payment_method"]').remove();
        $(this).closest('.accordion-item').find('.accordion-body').prepend(
            '<input type="hidden" name="payment_method" value="' + method + '">'
        );
    });
    // Default payment method
    if (!$('input[name="payment_method"]').length) {
        $('#paymentCod .accordion-body').prepend('<input type="hidden" name="payment_method" value="cod">');
    }
});
</script>
@endpush
