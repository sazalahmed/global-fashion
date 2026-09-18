@extends('landingpage::layouts.landing')

@section('content')
    <!--============================
        PERFUME PAGE START
    ==============================-->
    <section class="perfume_banner">
        <div class="perfume_banner_overly">
            <div class="container">
                <div class="row">
                    <div class="col-xl-12">
                        <div class="perfume_banner_text">
                            <h4>{{ $page->hero_subtitle }}</h4>
                            <h1>{{ $page->hero_title }}</h1>
                            <div class="img">
                                <img src="{{ $page->hero_image ? upload_url($page->hero_image) : asset('vendor/landing/images/perfume_banner_img.png') }}" alt="img" class="img-fluid w-100">
                            </div>
                            @if($page->offer_price)
                            <h5>অফার মূল্য <span>{{ currency_symbol() }} {{ number_format($page->offer_price) }}</span></h5>
                            @endif
                            <a href="#orderForm" class="perfume_btn">
                                <i class="fas fa-shopping-cart"></i>
                                অর্ডার করতে চাই
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if(!empty($page->sections['benefits']))
    <section class="perfume_pakage">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-11">
                    <div class="perfume_pakage_text">
                        <div class="perfume_heading">
                            <h2>কেন প্রয়োজন আপনার জন্য এই প্যাকেজটি?</h2>
                        </div>
                        <div class="row">
                            @foreach($page->sections['benefits'] as $benefit)
                            <div class="col-md-6 col-xl-3">
                                <div class="perfume_single_pakage">
                                    <h6>{{ $benefit['title'] ?? $benefit['text'] }}</h6>
                                    <p>{{ $benefit['description'] ?? '' }}</p>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        <a href="#orderForm" class="perfume_btn">
                            <i class="fas fa-shopping-cart"></i>
                            অর্ডার করতে চাই
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    @endif

    @if(!empty($page->sections['details']))
    <section class="perfume_details">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-11">
                    <div class="perfume_details_area">
                        @foreach($page->sections['details'] as $detail)
                        <div class="perfume_heading">
                            <h2>{{ $detail['title'] }}</h2>
                        </div>
                        <div class="perfume_about_details">
                            <div class="row justify-content-between align-items-center">
                                <div class="col-md-7 col-xl-7">
                                    <div class="perfume_about_details_text">
                                        {{ $detail['text'] }}
                                    </div>
                                </div>
                                @if(!empty($detail['image']))
                                <div class="col-md-5 col-xl-4">
                                    <div class="perfume_about_details_img">
                                        <img src="{{ upload_url($detail['image']) }}" alt="img-fluid w-100">
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endforeach

                        <div class="perfume_price_details">
                            @if($page->original_price)
                            <h5>রেগুলার মূল্য <del>{{ number_format($page->original_price) }}</del> টাকা</h5>
                            @endif
                            @if($page->offer_price)
                            <h3>আজকের অফার মূল্য <span>{{ number_format($page->offer_price) }}</span> টাকা</h3>
                            @endif
                            <h5>ডেলিভারি চার্জ: ঢাকায় {{ number_format($page->delivery_inside_dhaka) }}৳ | ঢাকার বাইরে {{ number_format($page->delivery_outside_dhaka) }}৳</h5>
                            @if($page->contact_phone)
                            <h6>মোবাইল : {{ $page->contact_phone }}</h6>
                            @endif
                        </div>
                        <div class="perfume_details_btn">
                            <a href="#orderForm" class="perfume_btn">
                                <i class="fas fa-shopping-cart"></i>
                                অর্ডার করতে চাই
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    @endif

    @if(!empty($page->sections['faqs']))
    <section class="perfume_faq">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-11">
                    <div class="perfume_faq_area">
                        <div class="perfume_heading">
                            <h2>কমন কিছু প্রশ্নের উত্তর</h2>
                        </div>
                        <div class="accordion accordion-flush perfume_faq_text" id="accordionFlushExample">
                            @foreach($page->sections['faqs'] as $i => $faq)
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#flush-collapse{{ $i }}" aria-expanded="false"
                                        aria-controls="flush-collapse{{ $i }}">
                                        {{ $faq['question'] }}
                                    </button>
                                </h2>
                                <div id="flush-collapse{{ $i }}" class="accordion-collapse collapse"
                                    data-bs-parent="#accordionFlushExample">
                                    <div class="accordion-body">{{ $faq['answer'] }}</div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    @endif

    @if($products->isNotEmpty())
    <section class="perfume_product">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-11">
                    <div class="perfume_product_area">
                        <div class="perfume_product_top">
                            <div class="img">
                                <img src="{{ $products->first()->image ?? asset('vendor/landing/images/perfume_product.png') }}" alt="img-fluid w-100">
                            </div>
                            <h4>সারা বাংলাদেশে হোম ডেলিভারি</h4>
                            <a href="#orderForm" class="perfume_btn">
                                <i class="fas fa-shopping-cart"></i>
                                অর্ডার করতে চাই
                            </a>
                        </div>
                        @if($page->contact_phone)
                        <div class="perfume_product_bottom">
                            <h5>প্রয়োজনে ফোন করুন : {{ $page->contact_phone }}</h5>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
    @endif

    <section class="shoes_review perfume_review">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-11">
                    <div class="perfume_heading">
                        <h2>আমাদের কাস্টমার রিভিউ</h2>
                    </div>
                </div>
            </div>
            <div class="row justify-content-center">
                <div class="col-xl-11">
                    <div class="row tshirt_review_slide">
                        <div class="col-xl-3"><div class="tshirt_single_review"><img src="{{ asset('vendor/landing/images/tshirt_review_1.jpg') }}" alt="img-fluid w-100"></div></div>
                        <div class="col-xl-3"><div class="tshirt_single_review"><img src="{{ asset('vendor/landing/images/tshirt_review_2.jpg') }}" alt="img-fluid w-100"></div></div>
                        <div class="col-xl-3"><div class="tshirt_single_review"><img src="{{ asset('vendor/landing/images/tshirt_review_3.jpg') }}" alt="img-fluid w-100"></div></div>
                        <div class="col-xl-3"><div class="tshirt_single_review"><img src="{{ asset('vendor/landing/images/tshirt_review_4.jpg') }}" alt="img-fluid w-100"></div></div>
                        <div class="col-xl-3"><div class="tshirt_single_review"><img src="{{ asset('vendor/landing/images/tshirt_review_3.jpg') }}" alt="img-fluid w-100"></div></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @include('landingpage::templates.partials.order-form', ['formClass' => 'perfume_form'])

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
        PERFUME PAGE END
    ==============================-->
@endsection
