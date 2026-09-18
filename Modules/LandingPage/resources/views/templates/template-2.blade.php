@extends('landingpage::layouts.landing')

@section('content')
    <!--============================
        SHOES PAGE START
    ==============================-->
    <section class="shoes_banner">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-11">
                    <div class="shoes_banner_text">
                        <a href="#" class="logo">
                            <img src="{{ asset('vendor/landing/images/logo_1.png') }}" alt="img-fluid w-100">
                        </a>
                        <h2>{{ $page->hero_title }}</h2>
                        <h1>{{ $page->hero_subtitle }}</h1>
                        <a href="#order-form" class="shoes_btn">
                            <span>অর্ডার করতে চাই</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="shoes_product">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-11">
                    <div class="row">
                        @foreach($products as $product)
                        <div class="col-sm-6 col-xl-3">
                            <div class="shoes_single_product">
                                <img src="{{ $product->image }}" alt="{{ $product->name }}">
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if(!empty($page->sections['details']))
        @foreach($page->sections['details'] ?? [] as $i => $detail)
        @if($i === 0)
        <section class="shoes_details">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-xl-7">
                        <div class="shoes_details_text">
                            <div class="shoes_heading">
                                <h2>{{ $detail['title'] }}</h2>
                            </div>
                            <p>{{ $detail['text'] }}</p>
                            <a href="#order-form" class="shoes_btn">
                                <span>অর্ডার করতে চাই</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        @elseif($i % 2 === 1)
        <section class="shoes_details_tow">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-xl-11">
                        <div class="row align-items-center">
                            <div class="col-md-6 col-xl-6">
                                <div class="shoes_details_text">
                                    <div class="shoes_heading">
                                        <h2>{{ $detail['title'] }}</h2>
                                    </div>
                                    <p>{{ $detail['text'] }}</p>
                                    <a href="#order-form" class="shoes_btn">
                                        <span>অর্ডার করতে চাই</span>
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-6 col-xl-6">
                                <div class="shoes_details_img">
                                    <img src="{{ $detail['image'] ?? asset('vendor/landing/images/shoes.png') }}" alt="{{ $detail['title'] }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        @else
        <section class="shoes_details_three">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-xl-11">
                        <div class="row align-items-center">
                            <div class="col-md-6 col-xl-6">
                                <div class="shoes_details_img">
                                    <img src="{{ $detail['image'] ?? asset('vendor/landing/images/shoes.png') }}" alt="{{ $detail['title'] }}">
                                </div>
                            </div>
                            <div class="col-md-6 col-xl-6">
                                <div class="shoes_details_text">
                                    <div class="shoes_heading">
                                        <h2>{{ $detail['title'] }}</h2>
                                    </div>
                                    <p>{{ $detail['text'] }}</p>
                                    <a href="#order-form" class="shoes_btn">
                                        <span>অর্ডার করতে চাই</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        @endif
        @endforeach
    @endif

    @if(!empty($page->sections['sizes']))
    <section class="shoes_size">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-7">
                    <div class="shoes_heading center_heading">
                        <h2>সাইজ গাইড</h2>
                    </div>
                </div>
            </div>
            <div class="row justify-content-center">
                <div class="col-xl-7">
                    <div class="row">
                        <div class="col-12">
                            <div class="table-responsive">
                                <table class="table">
                                    <tbody class="tshirt_size_table">
                                        <tr>
                                            <th class="size">SIZE (সাইজ)</th>
                                            <th class="chest">CHEST (বুকের মাপ)</th>
                                            <th class="details">LENGTH (চওড়া)</th>
                                        </tr>
                                        @foreach($page->sections['sizes'] ?? [] as $size)
                                        <tr>
                                            <td class="size">{{ $size['label'] ?? '' }}</td>
                                            <td class="chest">{{ $size['chest'] ?? '' }}</td>
                                            <td class="details">{{ $size['length'] ?? '' }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row justify-content-center">
                <div class="col-xl-7">
                    <div class="shoes_size_btn">
                        <a href="#order-form" class="shoes_btn">
                            <span>অর্ডার করতে চাই</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    @endif

    <section class="shoes_offer">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-7">
                    <div class="shoes_offer_text">
                        @if($page->original_price)
                        <h4>জনপ্রিয় এই লোফারের পূর্বের মূল্য <del>{{ number_format($page->original_price) }}/-</del> টাকা</h4>
                        @endif
                        @if($page->offer_price)
                        <h3>আজকের অফার মূল্য মাত্র <span>{{ number_format($page->offer_price) }}/-</span> টাকা</h3>
                        @endif
                        <h5>অফারটি লুফে নিতে এখনি <span>"অর্ডার করতে চাই"</span> বাটনে ক্লিক করুন</h5>
                        <a href="#order-form" class="shoes_btn">
                            <span>অর্ডার করতে চাই</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if(!empty($page->sections['reviews']))
    <section class="shoes_review">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-7">
                    <div class="shoes_heading center_heading">
                        <h2>কাস্টমার রিভিউ</h2>
                    </div>
                </div>
            </div>
            <div class="row justify-content-center">
                <div class="col-xl-11">
                    <div class="row tshirt_review_slide">
                        @foreach($page->sections['reviews'] ?? [] as $review)
                        <div class="col-xl-3">
                            <div class="tshirt_single_review">
                                <img src="{{ $review['image'] ?? asset('vendor/landing/images/tshirt_review_1.jpg') }}" alt="Customer Review">
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>
    @endif

    <section class="billing_form shoes_form" id="order-form">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-7">
                    <div class="shoes_heading center_heading">
                        <h2>অর্ডার করতে নিচের ফর্মটি পূরন করুন</h2>
                        <p>প্রয়োজনে ফোন করুন - <span>{{ $page->contact_phone }}</span></p>
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
                            @foreach($products as $index => $product)
                            <div class="col-lg-6 col-xl-6">
                                <div class="product_select">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="product_id"
                                            id="product_{{ $product->id }}" value="{{ $product->id }}" {{ $index === 0 ? 'checked' : '' }}>
                                        <div class="product_select_details">
                                            <div class="img">
                                                <img src="{{ $product->image }}" alt="{{ $product->name }}">
                                            </div>
                                            <div class="text">
                                                <h6>{{ $product->name }}</h6>
                                                <span>৳ {{ number_format($product->sell_price) }}</span>
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
                                        <select class="select_2" name="customer_district">
                                            <option value="">জেলা নির্বাচন করুন</option>
                                            @foreach(config('landing.districts', []) as $district)
                                            <option value="{{ $district }}">{{ $district }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="billing_single_input">
                                        <label>আপনার বিভাগ<span>*</span></label>
                                        <select class="select_2" name="customer_division">
                                            <option value="">বিভাগ নির্বাচন করুন</option>
                                            @foreach(config('landing.divisions', []) as $division)
                                            <option value="{{ $division }}">{{ $division }}</option>
                                            @endforeach
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
                                        <li>
                                            <div class="product">
                                                <div class="img">
                                                    <img src="{{ $products->first()->image ?? asset('vendor/landing/images/shoes.jpg') }}" alt="Product" class="order-product-img">
                                                </div>
                                                <div class="text">
                                                    <h5 class="order-product-name">{{ $products->first()->name ?? '' }}</h5>
                                                    <div class="product_quantity">
                                                        <button class="minus" type="button"><i class="fas fa-minus" aria-hidden="true"></i></button>
                                                        <input type="text" name="quantity" value="1" readonly>
                                                        <button class="plus" type="button"><i class="fas fa-plus" aria-hidden="true"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                            <h6 class="order-product-price">{{ number_format($products->first()->price ?? 0) }}৳</h6>
                                        </li>
                                    </ul>
                                    <div class="billing_orders_subtotal">
                                        <div class="subtotal">
                                            <h5>Subtotal</h5>
                                            <h6 class="order-subtotal">{{ number_format($products->first()->price ?? 0) }}৳</h6>
                                        </div>
                                        <div class="subtotal">
                                            <h5>Shipping</h5>
                                            <div class="charge">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio"
                                                        name="shipping_area" id="shipping_inside" value="inside_dhaka" checked>
                                                    <label class="form-check-label" for="shipping_inside">
                                                        ঢাকার ভিতরে ডেলিভারি চার্জ: {{ number_format($page->delivery_inside_dhaka) }}৳
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio"
                                                        name="shipping_area" id="shipping_outside" value="outside_dhaka">
                                                    <label class="form-check-label" for="shipping_outside">
                                                        ঢাকার বাইরে ডেলিভারি চার্জ: {{ number_format($page->delivery_outside_dhaka) }}৳
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="billing_orders_total">
                                        <h5>Total</h5>
                                        <h6 class="order-total">{{ number_format(($products->first()->price ?? 0) + $page->delivery_inside_dhaka) }}৳</h6>
                                    </div>
                                    <div class="product_payment">
                                        <div class="accordion product_payment_accordion" id="accordionExample">
                                            <div class="accordion-item">
                                                <h2 class="accordion-header">
                                                    <button class="accordion-button" type="button"
                                                        data-bs-toggle="collapse" data-bs-target="#collapse11"
                                                        aria-expanded="true" aria-controls="collapse11">
                                                        Cash on delivery
                                                    </button>
                                                </h2>
                                                <div id="collapse11" class="accordion-collapse collapse show"
                                                    data-bs-parent="#accordionExample">
                                                    <div class="accordion-body">
                                                        <p>Pay with cash upon delivery.</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="accordion-item">
                                                <h2 class="accordion-header">
                                                    <button class="accordion-button collapsed" type="button"
                                                        data-bs-toggle="collapse" data-bs-target="#collapse12"
                                                        aria-expanded="false" aria-controls="collapse12">
                                                        bKash
                                                        <img src="{{ asset('vendor/landing/images/bkash.png') }}" alt="bKash">
                                                    </button>
                                                </h2>
                                                <div id="collapse12" class="accordion-collapse collapse"
                                                    data-bs-parent="#accordionExample">
                                                    <div class="accordion-body">
                                                        <p>Please complete your bKash Send Money at first, then fill up
                                                            the form below.</p>
                                                        <span>bKash Personal Number : {{ $page->contact_phone }}</span>
                                                        <div class="pay_info">
                                                            <label>bKash Number</label>
                                                            <input type="text" name="bkash_number" placeholder="016XXXXXXXXX">
                                                        </div>
                                                        <div class="pay_info">
                                                            <label>bKash Transaction ID</label>
                                                            <input type="text" name="bkash_trx_id" placeholder="SDD4674GH77J7">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="accordion-item">
                                                <h2 class="accordion-header">
                                                    <button class="accordion-button collapsed" type="button"
                                                        data-bs-toggle="collapse" data-bs-target="#collapse13"
                                                        aria-expanded="false" aria-controls="collapse13">
                                                        Rocket
                                                        <img src="{{ asset('vendor/landing/images/rocket.png') }}" alt="Rocket">
                                                    </button>
                                                </h2>
                                                <div id="collapse13" class="accordion-collapse collapse"
                                                    data-bs-parent="#accordionExample">
                                                    <div class="accordion-body">
                                                        <p>Please complete your Rocket Send Money at first, then fill up
                                                            the form below.</p>
                                                        <span>Rocket Personal Number : {{ $page->contact_phone }}</span>
                                                        <div class="pay_info">
                                                            <label>Rocket Number</label>
                                                            <input type="text" name="rocket_number" placeholder="016XXXXXXXXX">
                                                        </div>
                                                        <div class="pay_info">
                                                            <label>Rocket Transaction ID</label>
                                                            <input type="text" name="rocket_trx_id" placeholder="SDD4674GH77J7">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="accordion-item">
                                                <h2 class="accordion-header">
                                                    <button class="accordion-button collapsed" type="button"
                                                        data-bs-toggle="collapse" data-bs-target="#collapse14"
                                                        aria-expanded="false" aria-controls="collapse14">
                                                        Nagad
                                                        <img src="{{ asset('vendor/landing/images/nagad.png') }}" alt="Nagad">
                                                    </button>
                                                </h2>
                                                <div id="collapse14" class="accordion-collapse collapse"
                                                    data-bs-parent="#accordionExample">
                                                    <div class="accordion-body">
                                                        <p>Please complete your Nagad Send Money at first, then fill up
                                                            the form below.</p>
                                                        <span>Nagad Personal Number : {{ $page->contact_phone }}</span>
                                                        <div class="pay_info">
                                                            <label>Nagad Number</label>
                                                            <input type="text" name="nagad_number" placeholder="016XXXXXXXXX">
                                                        </div>
                                                        <div class="pay_info">
                                                            <label>Nagad Transaction ID</label>
                                                            <input type="text" name="nagad_trx_id" placeholder="SDD4674GH77J7">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="product_payment_btn">
                                        <p>Your personal data will be used to process your order, support your
                                            experience throughout this website, and for other purposes described in our
                                            <a href="#">privacy policy</a>.
                                        </p>
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

    <section class="tshirt_copy_right">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-11">
                    <div class="tshirt_copy_right_text">
                        <p>Copyright &copy; {{ date('Y') }} | All rights reserved</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!--============================
        SHOES PAGE END
    ==============================-->
@endsection
