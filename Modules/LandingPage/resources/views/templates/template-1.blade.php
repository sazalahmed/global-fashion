@extends('landingpage::layouts.landing')

@section('content')
    <!--============================
        T-SHIRT PAGE START
    ==============================-->
    <section class="tshirt_banner">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-4">
                    <div class="tshirt_banner_logo">
                        <a href="#">
                            <img src="{{ asset('vendor/landing/images/logo_1.png') }}" alt="img-fluid w-100">
                        </a>
                    </div>
                </div>
            </div>
            <div class="tshirt_banner_area">
                <div class="row justify-content-center align-items-center">
                    <div class="col-lg-6 col-xl-5">
                        <div class="tshirt_banner_text">
                            <h1>{{ $page->hero_title }}</h1>
                            <p>{{ $page->hero_subtitle }}</p>
                            <a href="#orderForm" class="tshirt_btn">
                                <i class="fas fa-hand-pointer" aria-hidden="true"></i>
                                অর্ডার করতে চাই
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-6 col-xl-6">
                        <div class="tshirt_banner_img">
                            <div class="img">
                                <img src="{{ $page->hero_image ? upload_url($page->hero_image) : asset('vendor/landing/images/tshirt_banner_img.png') }}" alt="img-fluid w-100">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="tshirt_product">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-11">
                    <div class="row tshirt_slide">
                        @foreach($products as $product)
                        <div class="col-xl-4">
                            <div class="tshirt_single_product">
                                <img src="{{ $product->image }}" alt="img-fluid w-100">
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="tshirt_video">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-7">
                    <div class="tshirt_heading">
                        <h2>ZOTO Always comfort</h2>
                        <p>আমরা কোয়ালিটি ফেব্রিক্স, কালার গ্যারান্টি এবং সুয়িং গ্যারািন্ট দিচ্ছি, সাথে ১০দিনের
                            রিপ্লেসমেন্ট গ্যারান্টি। শুধু পণ্য বিক্রি নয়, আমরা চাই আপনােক বিক্রয়ত্বর সেবা দান করা এবং
                            আরও সব বাহারি ডিজাইন এবং কোয়ালিটি উপহার দেয়া</p>
                    </div>
                </div>
            </div>
            <div class="row justify-content-center">
                <div class="col-xl-8">
                    <div class="tshirt_video_area">
                        <div class="tshirt_video_ifram">
                            <iframe src="{{ $page->video_url }}"></iframe>
                        </div>
                        <div class="tshirt_video_bottom">
                            <div class="price">
                                <h3>৩ পিস টি-শার্টের মূল্য -------------- <span> ৳{{ number_format($page->offer_price ?? 0) }}</span></h3>
                                <p>ডেলিভারি চার্জ প্রযোজ্য</p>
                            </div>
                        </div>
                        <a href="#orderForm" class="tshirt_btn">
                            <i class="fas fa-hand-pointer" aria-hidden="true"></i>
                            অর্ডার করতে চাই
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <section class="tshirt_size">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-7">
                    <div class="tshirt_heading">
                        <h2>আমাদের টিশার্ট এর সাইজ সমূহ </h2>
                        <p>সাইজে প্রবলেম হলে অথবা অন্য কোন সমস্যার হলে রির্টান বা এক্সেচঞ্জ করে নিতে পারবেন ৭থেকে ১০
                            দিনের ভিতরে</p>
                    </div>
                </div>
            </div>
            <div class="row justify-content-center">
                <div class="col-xl-8">
                    <div class="row">
                        <div class="col-12">
                            <div class="table-responsive">
                                <table class="table">
                                    <tbody class="tshirt_size_table">
                                        <tr>
                                            <th class="size">
                                                SIZE (সাইজ)
                                            </th>
                                            <th class="chest">
                                                CHEST (বুকের মাপ)
                                            </th>
                                            <th class="details">
                                                LENGTH (চওড়া)
                                            </th>
                                        </tr>
                                        @foreach($page->sections['sizes'] ?? [] as $size)
                                        <tr>
                                            <td class="size">
                                                {{ $size['size'] }}
                                            </td>
                                            <td class="chest">
                                                {{ $size['chest'] }}
                                            </td>
                                            <td class="details">
                                                {{ $size['length'] }}
                                            </td>
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
                <div class="col-xl-8">
                    <div class="tshirt_size_btn">
                        <a href="#orderForm" class="tshirt_btn">
                            <i class="fas fa-hand-pointer" aria-hidden="true"></i>
                            অর্ডার করতে চাই
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="tshirt_faq">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-7">
                    <div class="tshirt_heading">
                        <h2>আপনাদের কিছু জিজ্ঞাসা</h2>
                        <p>প্রয়োজনে ফোন করুন - <span>{{ $page->contact_phone }}</span></p>
                    </div>
                </div>
            </div>
            <div class="row justify-content-center items-center">
                <div class="col-xl-8">
                    <div class="accordion accordion-flush tshirt_faq_text" id="accordionFlushExample">
                        @foreach($page->sections['faqs'] ?? [] as $i => $faq)
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#flush-collapse-{{ $i }}" aria-expanded="false"
                                    aria-controls="flush-collapse-{{ $i }}">
                                    {{ $faq['question'] }}
                                </button>
                            </h2>
                            <div id="flush-collapse-{{ $i }}" class="accordion-collapse collapse"
                                data-bs-parent="#accordionFlushExample">
                                <div class="accordion-body">{{ $faq['answer'] }}</div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="tshirt_review">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-7">
                    <div class="tshirt_heading">
                        <h2>আমাদের কাস্টমার রিভিউ</h2>
                        <p>আপনার রিভিউ দিন</p>
                    </div>
                </div>
            </div>
            <div class="row justify-content-center">
                <div class="col-xl-11">
                    <div class="row tshirt_review_slide">
                        <div class="col-xl-3">
                            <div class="tshirt_single_review">
                                <img src="{{ asset('vendor/landing/images/tshirt_review_1.jpg') }}" alt="img-fluid w-100">
                            </div>
                        </div>
                        <div class="col-xl-3">
                            <div class="tshirt_single_review">
                                <img src="{{ asset('vendor/landing/images/tshirt_review_2.jpg') }}" alt="img-fluid w-100">
                            </div>
                        </div>
                        <div class="col-xl-3">
                            <div class="tshirt_single_review">
                                <img src="{{ asset('vendor/landing/images/tshirt_review_3.jpg') }}" alt="img-fluid w-100">
                            </div>
                        </div>
                        <div class="col-xl-3">
                            <div class="tshirt_single_review">
                                <img src="{{ asset('vendor/landing/images/tshirt_review_4.jpg') }}" alt="img-fluid w-100">
                            </div>
                        </div>
                        <div class="col-xl-3">
                            <div class="tshirt_single_review">
                                <img src="{{ asset('vendor/landing/images/tshirt_review_3.jpg') }}" alt="img-fluid w-100">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="billing_form" id="orderForm">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-7">
                    <div class="tshirt_heading">
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
                                        <select class="select_2" name="district">
                                            <option value="">জেলা নির্বাচন করুন</option>
                                            <option value="ভোলা">ভোলা</option>
                                            <option value="বরগুনা">বরগুনা</option>
                                            <option value="কুমিল্লা">কুমিল্লা</option>
                                            <option value="যশোর">যশোর</option>
                                        </select>
                                    </div>
                                    <div class="billing_single_input">
                                        <label>আপনার বিভাগ<span>*</span></label>
                                        <select class="select_2" name="division">
                                            <option value="">বিভাগ নির্বাচন করুন</option>
                                            <option value="ঢাকা">ঢাকা</option>
                                            <option value="বরিশাল">বরিশাল</option>
                                            <option value="চট্টগ্রাম">চট্টগ্রাম</option>
                                            <option value="সিলেট">সিলেট</option>
                                        </select>
                                    </div>
                                    <div class="billing_single_input">
                                        <label>আপনার দেশ<span>*</span></label>
                                        <select class="select_2" name="country">
                                            <option value="">দেশ নির্বাচন করুন</option>
                                            <option value="বাংলাদেশ" selected>বাংলাদেশ</option>
                                            <option value="ভারত">ভারত</option>
                                            <option value="পাকিস্তান">পাকিস্তান</option>
                                            <option value="শ্রীলংকা">শ্রীলংকা</option>
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
                                                    <img src="{{ asset('vendor/landing/images/product.png') }}" alt="img-fluid w-100">
                                                </div>
                                                <div class="text">
                                                    <h5>{{ $page->hero_title }}</h5>
                                                    <div class="product_quantity">
                                                        <button class="minus" type="button"><i class="fas fa-minus"
                                                                aria-hidden="true"></i></button>
                                                        <input type="text" name="quantity" placeholder="1" value="1">
                                                        <button class="plus" type="button"><i class="fas fa-plus"
                                                                aria-hidden="true"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                            <h6>{{ number_format($page->offer_price ?? 0) }}৳</h6>
                                        </li>
                                    </ul>
                                    <div class="billing_orders_subtotal">
                                        <div class="subtotal">
                                            <h5>Subtotal</h5>
                                            <h6>{{ number_format($page->offer_price ?? 0) }}৳</h6>
                                        </div>
                                        <div class="subtotal">
                                            <h5>Shipping</h5>
                                            <div class="charge">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio"
                                                        name="shipping" id="shippingInsideDhaka" value="inside_dhaka" checked>
                                                    <label class="form-check-label" for="shippingInsideDhaka">
                                                        ঢাকার ভিতরে ডেলিভারি চার্জ: {{ number_format($page->delivery_inside_dhaka) }}৳
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio"
                                                        name="shipping" id="shippingOutsideDhaka" value="outside_dhaka">
                                                    <label class="form-check-label" for="shippingOutsideDhaka">
                                                        ঢাকার বাইরে ডেলিভারি চার্জ: {{ number_format($page->delivery_outside_dhaka) }}৳
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="billing_orders_total">
                                        <h5>Total</h5>
                                        <h6><span class="order-total">{{ number_format(($page->offer_price ?? 0) + ($page->delivery_inside_dhaka ?? 0)) }}</span>৳</h6>
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
                                                        <img src="{{ asset('vendor/landing/images/bkash.png') }}" alt="img-fluid w-100">
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
                                                        <img src="{{ asset('vendor/landing/images/rocket.png') }}" alt="img-fluid w-100">
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
                                                        <img src="{{ asset('vendor/landing/images/nagad.png') }}" alt="img-fluid w-100">
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

    @if(!empty($page->sections['benefits']))
    <section class="tshirt_benefits">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-8">
                    <ul>
                        @foreach($page->sections['benefits'] ?? [] as $benefit)
                        <li>{{ $benefit['text'] }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </section>
    @endif

    <section class="tshirt_copy_right">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-11">
                    <div class="tshirt_copy_right_text">
                        <p>Copyright &copy; {{ date('Y') }} | All rights reserved | Landing page made by <a href="#">BizPOS</a></p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!--============================
        T-SHIRT PAGE END
    ==============================-->
@endsection
