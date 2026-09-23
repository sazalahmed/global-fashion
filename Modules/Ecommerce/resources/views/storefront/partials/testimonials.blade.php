@php
    $testimonials = \Modules\Ecommerce\Models\Testimonial::where('is_active', true)->latest()->get();
@endphp
@if($testimonials->count() > 0)
<section class="testimonials mt_60 mb_60">
    <div class="container">
        <div class="row">
            <div class="col-xl-12">
                <div class="section_heading mb_40 text-center">
                    <h2>What Our Customers Say</h2>
                </div>
            </div>
        </div>
        <div class="row testimonial_slider">
            @foreach($testimonials as $testimonial)
            <div class="col-xl-4 px-3">
                <div class="single_testimonial text-center" style="background: #f9f9f9; padding: 30px; border-radius: 8px;">
                    <div class="testimonial_img mb-3">
                        <img src="{{ upload_url($testimonial->image, asset('website/assets/images/user_placeholder.png')) }}" alt="user" class="img-fluid rounded-circle mx-auto" style="width: 80px; height: 80px; object-fit: cover;">
                    </div>
                    <div class="testimonial_text">
                        <p class="rating mb-2">
                            @for($i=1; $i<=5; $i++)
                                <i class="fas fa-star {{ $i <= $testimonial->rating ? 'text-warning' : 'text-muted' }}"></i>
                            @endfor
                        </p>
                        <p class="description mb-3" style="font-style: italic; color: #555;">"{{ $testimonial->message }}"</p>
                        <h4 style="font-size: 18px; margin-bottom: 5px; font-weight: 600;">{{ $testimonial->name }}</h4>
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
        if($('.testimonial_slider').length > 0) {
            $('.testimonial_slider').slick({
                dots: true,
                arrows: false,
                infinite: true,
                speed: 300,
                slidesToShow: 3,
                slidesToScroll: 1,
                responsive: [
                    {
                        breakpoint: 992,
                        settings: {
                            slidesToShow: 2,
                        }
                    },
                    {
                        breakpoint: 768,
                        settings: {
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
