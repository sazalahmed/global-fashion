<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Page Not Found - {{ config('app.name', 'BizPOS Pro') }}</title>
    <link rel="icon" type="image/png" href="{{ $companyFavicon ?? asset('website/assets/images/favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('website/assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('website/assets/css/animate.css') }}">
    <link rel="stylesheet" href="{{ asset('website/assets/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('website/assets/css/responsive.css') }}">
</head>

<body class="default_home">

    <!--=============================
        ERROR START
    =============================-->
    <section class="error_page" style="background: url({{ asset('website/assets/images/error_bg.jpg') }});">
        <div class="container">
            <div class="row">
                <div class="col-xl-7 m-auto">
                    <div class="error_text wow fadeInUp">
                        <h2>404</h2>
                        <h4>Page Not Found</h4>
                        <p>The page you are looking for might have been removed, had its name changed, or is temporarily
                            unavailable.</p>
                        <a class="common_btn" href="{{ url('/') }}">Go Back Home <i
                                class="fas fa-long-arrow-right"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!--=============================
        ERROR END
    =============================-->

    <script src="{{ asset('website/assets/js/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('website/assets/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('website/assets/js/wow.min.js') }}"></script>
    <script>
        'use strict';
        new WOW().init();
    </script>
</body>

</html>
