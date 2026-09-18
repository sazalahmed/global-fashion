@extends('ecommerce::storefront.layouts.master')

@section('title', 'Forgot Password')

@section('content')
    <!--========================= FORGOT PASSWORD PAGE START ==========================-->
    <section class="forgot_password mt_70 mb_70">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xxl-3 col-lg-4 col-xl-4 d-none d-lg-block wow fadeInLeft">
                    <div class="sign_in_img">
                        <img src="{{ storefront_image('auth_forgot_image', 'website/assets/images/sign_in_img.jpg') }}" alt="Forgot Password"
                            class="img-fluid w-100">
                    </div>
                </div>
                <div class="col-xxl-4 col-lg-5 col-xl-5 col-sm-10 col-md-7 col-11 wow fadeInRight">
                    <div class="sign_in_form">
                        <h3>Forgot Password 👋</h3>

                        @if (session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif
                        @if (session('error'))
                            <div class="alert alert-danger">{{ session('error') }}</div>
                        @endif

                        <form action="{{ route('storefront.customer.password.email') }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-xl-12">
                                    <div class="single_input">
                                        <label>Phone Number *</label>
                                        <input type="tel" name="phone" placeholder="01XXXXXXXXX"
                                            value="{{ old('phone') }}" required autofocus
                                            class="@error('phone') is-invalid @enderror">
                                        @error('phone')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-xl-12 mt_15">
                                    <button type="submit" class="common_btn">Send Code <i
                                            class="fas fa-long-arrow-right"></i></button>
                                </div>
                            </div>
                        </form>

                        <p class="dont_account">Remember your password? <a
                                href="{{ route('storefront.customer.login') }}">Sign In</a></p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!--========================= FORGOT PASSWORD PAGE END ==========================-->
@endsection
