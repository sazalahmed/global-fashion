@extends('ecommerce::storefront.layouts.master')

@section('title', $page->title)
@section('breadcrumb_title', $page->title)

@section('breadcrumb')
    <li>{{ $page->title }}</li>
@endsection

@section('content')
    <section class="cms_page mt_70 mb_70">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="cms_page_content">
                        {!! strip_tags(
                            $page->content,
                            '<p><br><strong><em><ul><ol><li><h2><h3><h4><h5><h6><a><img><table><thead><tbody><tr><td><th><blockquote><span><div>',
                        ) !!}
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
