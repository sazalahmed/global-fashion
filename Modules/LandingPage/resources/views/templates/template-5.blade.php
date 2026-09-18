@extends('landingpage::layouts.landing')

@section('content')
    <!--============================
        FASHION PAGE START
    ==============================-->
    <section class="fashon_banner">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-4">
                    <div class="fashon_banner_logo">
                        <a href="#">
                            <img src="{{ asset('vendor/landing/images/logo_1.png') }}" alt="img-fluid w-100">
                        </a>
                    </div>
                </div>
            </div>
            <div class="fashon_banner_area">
                <div class="row justify-content-center align-items-center">
                    <div class="col-lg-6 col-xl-6">
                        <div class="fashon_banner_text">
                            <h1>{{ $page->hero_title }}</h1>
                            <p>{{ $page->hero_subtitle }}</p>
                            <a href="#orderForm" class="fashon_btn">
                                <i class="fas fa-hand-pointer" aria-hidden="true"></i>
                                অর্ডার করতে চাই
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-6 col-xl-5">
                        <div class="fashon_banner_img">
                            <img src="{{ $page->hero_image ? upload_url($page->hero_image) : asset('vendor/landing/images/fashon_banner_img.webp') }}" alt="img-fluid w-100">
                            <div class="shape">
                                <img src="{{ asset('vendor/landing/images/fashon_banner_shape.webp') }}" alt="img-fluid w-100">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if($products->isNotEmpty())
    <section class="fashon_product">
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
    @endif

    @if($page->video_url)
    <div class="fashon_video">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-8">
                    <div class="fashon_video_ifrem">
                        <iframe src="{{ $page->video_url }}" allowfullscreen></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if(!empty($page->sections['benefits']))
    <section class="fashon_details">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-11">
                    <div class="fashon_details_area">
                        <div class="row justify-content-between align-items-center">
                            <div class="col-md-7 col-xl-7">
                                <div class="fashon_about_details_text">
                                    <ul>
                                        @foreach($page->sections['benefits'] as $benefit)
                                        <li>{{ $benefit['text'] }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-5 col-xl-4">
                                <div class="fashon_about_details_img">
                                    <img src="{{ $products->first()->image ?? asset('vendor/landing/images/fashon_product.png') }}" alt="img-fluid w-100">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    @endif

    @if(!empty($page->sections['faqs']))
    <section class="perfume_faq fashon_faq">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-11">
                    <div class="perfume_faq_area">
                        <div class="fashon_heading">
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

    <section class="shoes_review fashon_review">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-11">
                    <div class="fashon_heading">
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

    @include('landingpage::templates.partials.order-form', ['formClass' => 'fashon_form'])

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
        FASHION PAGE END
    ==============================-->
@endsection
