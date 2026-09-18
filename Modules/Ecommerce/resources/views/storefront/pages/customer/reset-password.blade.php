@extends('ecommerce::storefront.layouts.master')

@section('title', 'Reset Password')

@section('content')
    <!--========================= RESET PASSWORD PAGE START ==========================-->
    <section class="sign_in mt_70 mb_70">
        <div class="container">
            <div class="row justify-content-center align-items-center">
                <div class="col-xxl-3 col-lg-4 col-xl-4 d-none d-lg-block wow fadeInLeft">
                    <div class="sign_in_img">
                        <img src="{{ storefront_image('auth_reset_image', 'website/assets/images/sign_in_img.jpg') }}"
                            alt="Reset Password" class="img-fluid w-100">
                    </div>
                </div>
                <div class="col-xxl-4 col-lg-5 col-xl-5 col-sm-10 col-md-7 col-11 wow fadeInRight">
                    <div class="sign_in_form">
                        <h3>Reset Password 🔒</h3>
                        <p class="dont_account mt-0 mb-3">Enter the code sent to your phone and choose a new password.</p>

                        @if (session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif
                        @if (session('error'))
                            <div class="alert alert-danger">{{ session('error') }}</div>
                        @endif

                        <form action="{{ route('storefront.customer.password.store') }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-xl-12">
                                    <div class="single_input">
                                        <label>Verification Code *</label>
                                        <input type="text" name="code" inputmode="numeric" maxlength="6"
                                            placeholder="6-digit code" required autofocus
                                            class="@error('code') is-invalid @enderror">
                                        @error('code')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-xl-12">
                                    <div class="single_input">
                                        <label>New Password *</label>
                                        <input type="password" name="password" placeholder="********" required
                                            class="@error('password') is-invalid @enderror">
                                        @error('password')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-xl-12">
                                    <div class="single_input">
                                        <label>Confirm Password *</label>
                                        <input type="password" name="password_confirmation" placeholder="********" required>
                                    </div>
                                </div>
                                <div class="col-xl-12">
                                    <button type="submit" class="common_btn">Reset Password <i
                                            class="fas fa-long-arrow-right"></i></button>
                                </div>
                            </div>
                        </form>

                        <p class="dont_account">Didn't get the code? <a
                                href="{{ route('storefront.customer.password.request') }}">Try again</a></p>
                        <p class="dont_account">Remember your password? <a
                                href="{{ route('storefront.customer.login') }}">Sign In</a></p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!--========================= RESET PASSWORD PAGE END ==========================-->
@endsection
