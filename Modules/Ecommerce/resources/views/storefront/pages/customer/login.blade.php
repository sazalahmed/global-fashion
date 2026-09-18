@extends('ecommerce::storefront.layouts.master')

@section('title', 'Login')

@section('content')
    <!--========================= SIGN IN PAGE START  ==========================-->
    <section class="sign_in mt_70 mb_70">
        <div class="container">
            <div class="row justify-content-center align-items-center">
                <div class="col-xxl-3 col-lg-4 col-xl-4 d-none d-lg-block wow fadeInLeft">
                    <div class="sign_in_img">
                        <img src="{{ storefront_image('auth_login_image', 'website/assets/images/sign_in_img.jpg') }}"
                            alt="Sign In" class="img-fluid w-100">
                    </div>
                </div>
                <div class="col-xxl-4 col-lg-5 col-xl-5 col-sm-10 col-md-7 col-11 wow fadeInRight">
                    <div class="sign_in_form">
                        <h3>Sign In to Continue 👋</h3>

                        @if (session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif

                        <form action="{{ route('storefront.customer.login.post') }}" method="POST">
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
                                <div class="col-xl-12">
                                    <div class="single_input">
                                        <label>Password *</label>
                                        <input type="password" name="password" placeholder="********" required
                                            class="@error('password') is-invalid @enderror">
                                        @error('password')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="forgot">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="remember" id="rememberMe"
                                                {{ old('remember') ? 'checked' : '' }}>
                                            <label class="form-check-label" for="rememberMe">Remember Me</label>
                                        </div>
                                        <a href="{{ route('storefront.customer.password.request') }}">Forgot Password?</a>
                                    </div>
                                </div>
                                <div class="col-xl-12">
                                    <button type="submit" class="common_btn">Sign In <i
                                            class="fas fa-long-arrow-right"></i></button>
                                </div>
                            </div>
                        </form>

                        <p class="dont_account">Don't have an account? <a
                                href="{{ route('storefront.customer.register') }}">Sign Up</a></p>
                        <p class="or">or</p>
                        <ul class="social_login_full">
                            <li>
                                <a href="{{ route('storefront.customer.google.redirect') }}">
                                    <span>
                                        <img src="{{ asset('website/assets/images/google_logo.png') }}" alt="google"
                                            class="img-fluid w-100">
                                    </span>
                                    Google
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!--========================= SIGN IN PAGE END ==========================-->
@endsection
