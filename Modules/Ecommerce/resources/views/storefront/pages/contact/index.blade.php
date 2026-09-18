@extends('ecommerce::storefront.layouts.master')

@section('title', 'Contact Us')
@section('breadcrumb_title', 'Contact Us')

@section('breadcrumb')
    <li>Contact Us</li>
@endsection

@section('content')
    <!--============================ CONTACT US START =============================-->
    <section class="contact_us mt_75">
        <div class="container">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            {{-- Contact info cards. Phones/emails come from eCommerce → Settings →
                 Contact Page; each falls back to the single Business Profile value. --}}
            @php
                $phones = !empty($contactPhones) ? $contactPhones : array_filter([$companyPhone ?? null]);
                $emails = !empty($contactEmails) ? $contactEmails : array_filter([$companyEmail ?? null]);
            @endphp
            <div class="row">
                @if (!empty($phones))
                    <div class="col-xl-4 col-md-6">
                        <div class="contact_info wow fadeInUp">
                            <span><img src="{{ asset('website/assets/images/call_icon_black.png') }}" alt="call"
                                    class="img-fluid"></span>
                            <h3>Call Us</h3>
                            @foreach ($phones as $phone)
                                <a class="d-block"
                                    href="tel:{{ \App\Helpers\PhoneHelper::formatIntl($phone) }}">{{ \App\Helpers\PhoneHelper::formatIntl($phone) }}</a>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if (!empty($emails))
                    <div class="col-xl-4 col-md-6">
                        <div class="contact_info wow fadeInUp">
                            <span><img src="{{ asset('website/assets/images/mail_icon_black.png') }}" alt="mail"
                                    class="img-fluid"></span>
                            <h3>Email Us</h3>
                            @foreach ($emails as $email)
                                <a class="d-block" href="mailto:{{ $email }}">{{ $email }}</a>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if (!empty($companyAddress))
                    <div class="col-xl-4 col-md-6">
                        <div class="contact_info wow fadeInUp">
                            <span><img src="{{ asset('website/assets/images/location_icon_black.png') }}" alt="location"
                                    class="img-fluid"></span>
                            <h3>Our Location</h3>
                            <p>{{ $companyAddress }}</p>
                        </div>
                    </div>
                @endif
            </div>

            <div class="row mt_25">
                <div class="col-lg-5">
                    <div class="contact_img wow fadeInLeft">
                        <img src="{{ $contactBanner }}" alt="contact" class="img-fluid w-100">
                        @if (!empty($companyPhone))
                            <div class="contact_hotline">
                                <h3>Hotline</h3>
                                <a
                                    href="tel:{{ \App\Helpers\PhoneHelper::formatIntl($companyPhone) }}">{{ \App\Helpers\PhoneHelper::formatIntl($companyPhone) }}</a>
                                <div class="icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" class="size-6">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0M3.124 7.5A8.969 8.969 0 0 1 5.292 3m13.416 0a8.969 8.969 0 0 1 2.168 4.5" />
                                    </svg>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Contact form --}}
                <div class="col-lg-7">
                    <div class="contact_form wow fadeInRight">
                        <h2>{{ $contactHeading }}</h2>
                        <form action="{{ route('storefront.contact.store') }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="single_input">
                                        <label>Name *</label>
                                        <input type="text" name="name" placeholder="Your name"
                                            value="{{ old('name') }}" required
                                            class="@error('name') is-invalid @enderror">
                                        @error('name')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="single_input">
                                        <label>Email *</label>
                                        <input type="email" name="email" placeholder="your@email.com"
                                            value="{{ old('email') }}" required
                                            class="@error('email') is-invalid @enderror">
                                        @error('email')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="single_input">
                                        <label>Phone</label>
                                        <input type="text" name="phone" placeholder="01XXXXXXXXX"
                                            value="{{ old('phone') }}" class="@error('phone') is-invalid @enderror">
                                        @error('phone')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="single_input">
                                        <label>Subject</label>
                                        <input type="text" name="subject" placeholder="Subject"
                                            value="{{ old('subject') }}" class="@error('subject') is-invalid @enderror">
                                        @error('subject')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="single_input">
                                        <label>Message *</label>
                                        <textarea name="message" rows="7" placeholder="Message..." required
                                            class="@error('message') is-invalid @enderror">{{ old('message') }}</textarea>
                                        @error('message')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <button type="submit" class="common_btn">Send Message <i
                                            class="fas fa-long-arrow-right"></i></button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        @php
            $mapSrc =
                $contactMapEmbed ?:
                (!empty($companyAddress)
                    ? 'https://maps.google.com/maps?q=' . urlencode($companyAddress) . '&output=embed'
                    : null);
        @endphp
        @if (!empty($mapSrc))
            <div class="contact_map mt_75 wow fadeInUp">
                <iframe src="{{ $mapSrc }}" width="600" height="450" allowfullscreen loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        @endif
    </section>
    <!--============================ CONTACT US END =============================-->
@endsection
