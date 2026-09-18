@extends('landingpage::layouts.landing')

@section('content')
    <!--============================
        BEAUTY PRODUCT START
    ==============================-->
    <section class="beauty_banner">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-4">
                    <div class="beauty_banner_logo">
                        <a href="#">
                            <img src="{{ asset('vendor/landing/images/logo_1.png') }}" alt="img-fluid w-100">
                        </a>
                    </div>
                </div>
            </div>
            <div class="beauty_banner_area">
                <div class="row justify-content-center align-items-center">
                    <div class="col-lg-6 col-xl-6">
                        <div class="beauty_banner_text">
                            <h1>{{ $page->hero_title }}</h1>
                            <h6>{{ $page->hero_subtitle }}</h6>
                            @if(!empty($page->sections['ingredients']))
                            <ul class="d-flex flex-wrap">
                                @foreach($page->sections['ingredients'] ?? [] as $item)
                                <li>{{ $item['text'] }}</li>
                                @endforeach
                            </ul>
                            @endif
                            <a href="#orderForm" class="beauty_btn">
                                অর্ডার করতে চাই
                                <i class="fas fa-arrow-down"></i>
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-6 col-xl-5">
                        <div class="beauty_banner_img">
                            <img src="{{ $page->hero_image ? upload_url($page->hero_image) : asset('vendor/landing/images/beauty_pearl_combo.png') }}" alt="img-fluid w-100">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if($products->isNotEmpty())
    <section class="beauty_product">
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
    <div class="beauty_video">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-8">
                    <div class="beauty_video_ifrem">
                        <iframe src="{{ $page->video_url }}" allowfullscreen></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if(!empty($page->sections['benefits']))
    <div class="beauty_quality">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-11">
                    @foreach($page->sections['benefits'] as $benefit)
                    <div class="beauty_heading">
                        <h2>{{ $benefit['text'] }}</h2>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif

    @if(!empty($page->sections['details']))
    <div class="beauty_details">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-11">
                    @foreach($page->sections['details'] as $detail)
                    <div class="beauty_single_details">
                        <div class="row justify-content-between align-items-center">
                            <div class="col-md-7 col-xl-7">
                                <div class="beauty_about_details_text">
                                    <h3>{{ $detail['title'] }}</h3>
                                    {{ $detail['text'] }}
                                </div>
                            </div>
                            @if(!empty($detail['image']))
                            <div class="col-md-5 col-xl-4">
                                <div class="beauty_about_details_img">
                                    <img src="{{ upload_url($detail['image']) }}" alt="img-fluid w-100">
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif

    <section class="shoes_review beauty_review">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-11">
                    <div class="beauty_heading">
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

    @include('landingpage::templates.partials.order-form', ['formClass' => 'beauty_form'])

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
        BEAUTY PRODUCT END
    ==============================-->
@endsection
