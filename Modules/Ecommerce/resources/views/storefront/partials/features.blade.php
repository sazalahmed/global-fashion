{{-- Features Section — Zenis Style --}}
@php
    $section = $section ?? null;
    $b1Icon = $section?->getSetting('badge_1_icon')
        ? upload_url($section->getSetting('badge_1_icon'))
        : asset('website/assets/images/feature-icon_1.svg');
    $b1Title = $section?->getSetting('badge_1_title', 'Return & Refund') ?? 'Return & Refund';
    $b1Sub = $section?->getSetting('badge_1_subtitle', 'Money back guarantee') ?? 'Money back guarantee';

    $b2Icon = $section?->getSetting('badge_2_icon')
        ? upload_url($section->getSetting('badge_2_icon'))
        : asset('website/assets/images/feature-icon_3.svg');
    $b2Title = $section?->getSetting('badge_2_title', 'Quality Support') ?? 'Quality Support';
    $b2Sub = $section?->getSetting('badge_2_subtitle', 'Always online 24/7') ?? 'Always online 24/7';

    $b3Icon = $section?->getSetting('badge_3_icon')
        ? upload_url($section->getSetting('badge_3_icon'))
        : asset('website/assets/images/feature-icon_2.svg');
    $b3Title = $section?->getSetting('badge_3_title', 'Secure Payment') ?? 'Secure Payment';
    $b3Sub = $section?->getSetting('badge_3_subtitle', 'Protected checkout') ?? 'Protected checkout';

    $b4Icon = $section?->getSetting('badge_4_icon')
        ? upload_url($section->getSetting('badge_4_icon'))
        : asset('website/assets/images/feature-icon_4.svg');
    $b4Title = $section?->getSetting('badge_4_title', 'Daily Offers') ?? 'Daily Offers';
    $b4Sub = $section?->getSetting('badge_4_subtitle', 'Discounts every day') ?? 'Discounts every day';
@endphp
<section class="features mt_40">
    <div class="container">
        <div class="row">
            <div class="col-xl-3 col-sm-6 wow fadeInUp">
                <div class="features_item purple">
                    <div class="icon">
                        <img src="{{ $b1Icon }}" alt="feature">
                    </div>
                    <div class="text">
                        <h3>{{ $b1Title }}</h3>
                        <p>{{ $b1Sub }}</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6 wow fadeInUp">
                <div class="features_item green">
                    <div class="icon">
                        <img src="{{ $b2Icon }}" alt="feature">
                    </div>
                    <div class="text">
                        <h3>{{ $b2Title }}</h3>
                        <p>{{ $b2Sub }}</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6 wow fadeInUp">
                <div class="features_item orange">
                    <div class="icon">
                        <img src="{{ $b3Icon }}" alt="feature">
                    </div>
                    <div class="text">
                        <h3>{{ $b3Title }}</h3>
                        <p>{{ $b3Sub }}</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6 wow fadeInUp">
                <div class="features_item">
                    <div class="icon">
                        <img src="{{ $b4Icon }}" alt="feature">
                    </div>
                    <div class="text">
                        <h3>{{ $b4Title }}</h3>
                        <p>{{ $b4Sub }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
