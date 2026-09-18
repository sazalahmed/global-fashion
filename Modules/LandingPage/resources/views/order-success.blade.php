<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>অর্ডার সফল - BizPOS</title>
    <meta name="robots" content="noindex,follow">
    <link rel="canonical" href="{{ url()->current() }}">
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/fontawesome/css/all.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/landing/css/style.css') }}" rel="stylesheet">
    <link href="{{ asset('css/style.css') }}" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center bp-lp-success-wrapper">
            <div class="col-md-6 text-center">
                <div class="bp-lp-success-card">
                    <div class="bp-lp-success-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <h2 class="bp-lp-success-title">অর্ডার সফল হয়েছে!</h2>
                    <p class="bp-lp-success-desc">আপনার অর্ডারটি সফলভাবে গ্রহণ করা হয়েছে।</p>
                    <div class="bp-lp-success-order">
                        <span class="bp-lp-success-order-label">অর্ডার নম্বর</span>
                        <h4 class="bp-lp-success-order-number">{{ $orderNumber }}</h4>
                    </div>
                    <p class="bp-lp-success-note">আমরা শীঘ্রই আপনার সাথে যোগাযোগ করব।</p>
                    <a href="{{ url('/') }}" class="bp-lp-success-btn">
                        <i class="fas fa-home me-2"></i> হোমপেজে ফিরে যান
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
