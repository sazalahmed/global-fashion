@php
    $testimonials = \Modules\Ecommerce\Models\Testimonial::where('is_active', true)->latest()->get();
@endphp
@if ($testimonials->count() > 0)
    <section class="testimonials mt_60 mb_60">
        <div class="container">
            <div class="row">
                <div class="col-xl-12">
                    <div class="section_heading mb_40">
                        <h3>What Our Customers Say</h3>
                    </div>
                </div>
            </div>
            <div class="row testimonial_slider">
                @foreach ($testimonials as $testimonial)
                    <div class="col-xl-4 px-3 h-100">
                        <div class="single_testimonial text-center"
                            style="background: #f9f9f9; padding: 30px; border-radius: 8px;">
                            <div class="testimonial_img mb-3">
                                <img src="{{ upload_url($testimonial->image, asset('website/assets/images/user_placeholder.png')) }}"
                                    alt="user" class="img-fluid rounded-circle mx-auto"
                                    style="width: 80px; height: 80px; object-fit: cover;">
                            </div>
                            <h4 style="font-size: 18px; margin-bottom: 5px; font-weight: 600;">{{ $testimonial->name }}
                            </h4>
                            <div class="testimonial_text">
                                <p class="rating mb-2">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <i
                                            class="fas fa-star {{ $i <= $testimonial->rating ? 'text-warning' : 'text-muted' }}"></i>
                                    @endfor
                                </p>
                                <p class="description mb-3" style="font-style: italic; color: #555;">
                                    "{{ $testimonial->message }}"</p>
                                <span style="font-size: 14px; color: #777;">{{ $testimonial->designation }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    @push('scripts')
        <script>
            $(document).ready(function() {
                if ($('.testimonial_slider').length > 0) {
                    $('.testimonial_slider').slick({
                        dots: false,
                        arrows: true,
                        infinite: true,
                        speed: 1000,
                        slidesToShow: 5,
                        slidesToScroll: 1,
                        nextArrow: '<i class="fas fa-arrow-right nextArrow"></i>',
                        prevArrow: '<i class="fas fa-arrow-left prevArrow"></i>',
                        responsive: [{
                                breakpoint: 1599,
                                settings: {
                                    slidesToShow: 4,
                                }
                            },
                            {
                                breakpoint: 1399,
                                settings: {
                                    slidesToShow: 3,
                                }
                            },
                            {
                                breakpoint: 1199,
                                settings: {
                                    slidesToShow: 3,
                                }
                            },
                            {
                                breakpoint: 991,
                                settings: {
                                    slidesToShow: 2,
                                }
                            },
                            {
                                breakpoint: 767,
                                settings: {
                                    slidesToShow: 2,
                                }
                            },
                            {
                                breakpoint: 575,
                                settings: {
                                    arrows: false,
                                    slidesToShow: 1,
                                }
                            }
                        ]
                    });
                }
            });
        </script>
    @endpush
@endif
