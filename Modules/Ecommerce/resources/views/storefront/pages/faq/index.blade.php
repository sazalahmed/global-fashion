@extends('ecommerce::storefront.layouts.master')

@section('title', 'FAQ')
@section('breadcrumb_title', "FAQ's")

@section('breadcrumb')
    <li>FAQ's</li>
@endsection

@section('content')
    <section class="faq_page mb_70">
        <div class="container">
            <div class="accordion" id="accordionExample">
                <div class="row align-items-center">
                    <div class="col-xxl-5 col-lg-6 col-md-10 mt_50 wow fadeInLeft">
                        <div class="faq_img pr_50">
                            <img src="{{ $faqHeader['image'] }}" alt="FAQ's" class="img-fluid w-100">
                        </div>
                    </div>
                    <div class="col-xxl-7 col-lg-6 mt_70 wow fadeInRight">
                        <h6 class="faq_sub_title">{{ $faqHeader['sub_title'] }}</h6>
                        <h3 class="faq_title">{{ $faqHeader['title'] }}</h3>

                        @forelse($faqs as $i => $faq)
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button {{ $i === 0 ? '' : 'collapsed' }}" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#faq{{ $faq->id }}"
                                        aria-expanded="{{ $i === 0 ? 'true' : 'false' }}"
                                        aria-controls="faq{{ $faq->id }}">
                                        {{ $faq->question }}
                                    </button>
                                </h2>
                                <div id="faq{{ $faq->id }}"
                                    class="accordion-collapse collapse {{ $i === 0 ? 'show' : '' }}"
                                    data-bs-parent="#accordionExample">
                                    <div class="accordion-body">
                                        {{-- Rich-text answer (TinyMCE). Rendered through the same
                                             tag-allowlist strip_tags() used for pages/blog content. --}}
                                        {!! strip_tags($faq->answer, '<p><br><strong><em><ul><ol><li><h2><h3><h4><h5><h6><a><img><table><thead><tbody><tr><td><th><blockquote><span><div>') !!}
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p>No FAQs available yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
