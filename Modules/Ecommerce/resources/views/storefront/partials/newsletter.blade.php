{{-- Newsletter Section — Zenis Style Subscription 2 (dynamic + functional) --}}
@php
    $section ??= null;
    $bgImg = $section?->getSetting('background_image') ?: null;
    $bgPath = $bgImg ?: 'website/assets/images/subscribe_2_bg.jpg';
    $nlHeading =
        $section?->getSetting('heading', 'Get Upto 70% Off Discount Coupon') ?? 'Get Upto 70% Off Discount Coupon';
    $nlHl = $section?->getSetting('highlight', '70%') ?? '70%';
    $nlSub = $section?->getSetting('subheading', 'by Subscribe our Newsletter') ?? 'by Subscribe our Newsletter';
    $nlPlace = $section?->getSetting('input_placeholder', 'Your email') ?? 'Your email';
    $nlBtn = $section?->getSetting('button_label', 'Subscribe') ?? 'Subscribe';
@endphp
<section class="subscription_2 mt_70 xs_mt_60" style="{!! bg_image_set($bgPath) !!}">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xxl-6 col-lg-8 wow fadeInUp">
                <div class="subscription_2_text">
                    <h2><x-ecommerce::section-heading :heading="$nlHeading" :highlight="$nlHl" /></h2>
                    <p>{{ $nlSub }}</p>
                    @if (session('newsletter_status'))
                        <p class="newsletter_status text-success fw-600">{{ session('newsletter_status') }}</p>
                    @endif
                    <form action="{{ route('storefront.newsletter.subscribe') }}" method="POST">
                        @csrf
                        <input type="email" name="email" placeholder="{{ $nlPlace }}"
                            value="{{ old('email') }}" required>
                        <button type="submit" class="common_btn">{{ $nlBtn }}</button>
                    </form>
                    @error('email')
                        <p class="newsletter_error text-danger fw-600 mt-2">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>
    </div>
</section>
