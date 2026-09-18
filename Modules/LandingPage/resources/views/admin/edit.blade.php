@extends('core::layouts.master')

@section('title', $isEdit ? 'Edit Landing Page' : 'Create Landing Page')
@section('page-title', $isEdit ? 'Edit: ' . $page->name : 'Create Landing Page')

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('landing-pages.index') }}">Landing Pages</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>{{ $isEdit ? 'Edit' : 'Create' }}</span>
@endsection

@section('page-actions')
@if($isEdit)
<a href="{{ route('landing-pages.preview', $page) }}" target="_blank" class="bp-btn bp-btn-outline"><i class="fa-solid fa-external-link me-1"></i> Full Preview</a>
@endif
<a href="{{ route('landing-pages.index') }}" class="bp-btn bp-btn-primary"><i class="fa-solid fa-arrow-left me-1"></i> Back</a>
@endsection

@section('content')

<form action="{{ $isEdit ? route('landing-pages.update', $page) : route('landing-pages.store') }}" method="POST" enctype="multipart/form-data" id="landingPageForm">
    @csrf
    @if($isEdit)
    @method('PUT')
    @endif

    <div class="row g-3">
        <!-- Left: Form Panel -->
        <div class="col-xl-5">

            <!-- Template Selection -->
            <div class="bp-card mb-3">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-palette me-2"></i>Template</h5>
                </div>
                <div class="bp-card-body">
                    <div class="row g-2">
                        @foreach(['template-1' => 'T-Shirt', 'template-2' => 'Shoes', 'template-3' => 'Perfume', 'template-4' => 'Beauty', 'template-5' => 'Fashion'] as $key => $label)
                        <div class="col">
                            <label class="bp-template-card {{ $page->template === $key ? 'active' : '' }}">
                                <input type="radio" name="template" value="{{ $key }}" {{ $page->template === $key ? 'checked' : '' }} class="d-none">
                                <div class="text-center p-2">
                                    <i class="fa-solid fa-file-code fs-4 d-block mb-1"></i>
                                    <span class="fs-11 fw-600">{{ $label }}</span>
                                </div>
                            </label>
                        </div>
                        @endforeach
                    </div>
                    @error('template') <div class="text-danger fs-12 mt-1">{{ $message }}</div> @enderror
                </div>
            </div>

            <!-- Basic Info -->
            <div class="bp-card mb-3">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-info-circle me-2"></i>Basic Info</h5>
                </div>
                <div class="bp-card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="bp-form-label">Page Name *</label>
                            <input type="text" class="bp-form-control" name="name" value="{{ old('name', $page->name) }}" required>
                            @error('name') <div class="text-danger fs-12">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label class="bp-form-label">Contact Phone</label>
                            <input type="text" class="bp-form-control" name="contact_phone" value="{{ old('contact_phone', $page->contact_phone) }}" placeholder="01XXXXXXXXX">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hero Section -->
            <div class="bp-card mb-3">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-star me-2"></i>Hero Section</h5>
                </div>
                <div class="bp-card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="bp-form-label">Hero Title *</label>
                            <textarea class="bp-form-control" name="hero_title" rows="2" required>{{ old('hero_title', $page->hero_title) }}</textarea>
                            @error('hero_title') <div class="text-danger fs-12">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label class="bp-form-label">Hero Subtitle</label>
                            <textarea class="bp-form-control" name="hero_subtitle" rows="2">{{ old('hero_subtitle', $page->hero_subtitle) }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="bp-form-label">Hero Image</label>
                            <input type="file" class="bp-form-control" name="hero_image" accept="image/*">
                            @if($page->hero_image)
                            <div class="mt-2"><img src="{{ upload_url($page->hero_image) }}" class="img-thumbnail bp-lp-hero-thumb"></div>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <label class="bp-form-label">Offer Price ({{ currency_symbol() }})</label>
                            <input type="number" class="bp-form-control" name="offer_price" value="{{ old('offer_price', $page->offer_price) }}" step="0.01" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="bp-form-label">Original Price ({{ currency_symbol() }})</label>
                            <input type="number" class="bp-form-control" name="original_price" value="{{ old('original_price', $page->original_price) }}" step="0.01" min="0">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Products -->
            <div class="bp-card mb-3">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-boxes-stacked me-2"></i>Products</h5>
                </div>
                <div class="bp-card-body">
                    <select class="bp-form-select w-100" name="product_ids[]" id="productPicker" multiple required>
                        @foreach(\Modules\Product\Models\Product::whereIn('id', $page->product_ids ?? [])->get() as $product)
                        <option value="{{ $product->id }}" selected>{{ $product->name }} ({{ $product->sku }}) — {{ currency_symbol() }} {{ number_format($product->sell_price) }}</option>
                        @endforeach
                    </select>
                    @error('product_ids') <div class="text-danger fs-12 mt-1">{{ $message }}</div> @enderror
                    <div class="fs-11 text-muted mt-1">Search and select 1-10 products</div>
                </div>
            </div>

            <!-- Video -->
            <div class="bp-card mb-3">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-video me-2"></i>Video</h5>
                </div>
                <div class="bp-card-body">
                    <label class="bp-form-label">YouTube Embed URL</label>
                    <input type="url" class="bp-form-control" name="video_url" value="{{ old('video_url', $page->video_url) }}" placeholder="https://www.youtube.com/embed/...">
                </div>
            </div>

            <!-- Benefits -->
            <div class="bp-card mb-3">
                <div class="bp-card-header d-flex justify-content-between align-items-center">
                    <h5 class="bp-card-title mb-0"><i class="fa-solid fa-check-circle me-2"></i>Benefits</h5>
                    <button type="button" class="bp-btn bp-btn-sm bp-btn-outline" id="addBenefit"><i class="fa-solid fa-plus me-1"></i> Add</button>
                </div>
                <div class="bp-card-body" id="benefitsContainer">
                    @foreach($page->sections['benefits'] ?? [['text' => '']] as $i => $benefit)
                    <div class="d-flex gap-2 mb-2 benefit-row">
                        <input type="text" class="bp-form-control" name="benefits[{{ $i }}][text]" value="{{ $benefit['text'] ?? '' }}" placeholder="Benefit text">
                        <button type="button" class="bp-btn bp-btn-sm bp-btn-icon bp-btn-danger remove-row"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- FAQ -->
            <div class="bp-card mb-3">
                <div class="bp-card-header d-flex justify-content-between align-items-center">
                    <h5 class="bp-card-title mb-0"><i class="fa-solid fa-circle-question me-2"></i>FAQ</h5>
                    <button type="button" class="bp-btn bp-btn-sm bp-btn-outline" id="addFaq"><i class="fa-solid fa-plus me-1"></i> Add</button>
                </div>
                <div class="bp-card-body" id="faqsContainer">
                    @foreach($page->sections['faqs'] ?? [['question' => '', 'answer' => '']] as $i => $faq)
                    <div class="mb-3 p-2 border rounded faq-row">
                        <input type="text" class="bp-form-control mb-2" name="faqs[{{ $i }}][question]" value="{{ $faq['question'] ?? '' }}" placeholder="Question">
                        <textarea class="bp-form-control" name="faqs[{{ $i }}][answer]" rows="2" placeholder="Answer">{{ $faq['answer'] ?? '' }}</textarea>
                        <button type="button" class="bp-btn bp-btn-sm bp-btn-danger mt-1 remove-row"><i class="fa-solid fa-trash me-1"></i> Remove</button>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Size Guide -->
            <div class="bp-card mb-3">
                <div class="bp-card-header d-flex justify-content-between align-items-center">
                    <h5 class="bp-card-title mb-0"><i class="fa-solid fa-ruler me-2"></i>Size Guide</h5>
                    <button type="button" class="bp-btn bp-btn-sm bp-btn-outline" id="addSize"><i class="fa-solid fa-plus me-1"></i> Add</button>
                </div>
                <div class="bp-card-body" id="sizesContainer">
                    @foreach($page->sections['sizes'] ?? [] as $i => $size)
                    <div class="d-flex gap-2 mb-2 size-row">
                        <input type="text" class="bp-form-control" name="sizes[{{ $i }}][size]" value="{{ $size['size'] ?? '' }}" placeholder="Size (M, L...)">
                        <input type="text" class="bp-form-control" name="sizes[{{ $i }}][chest]" value="{{ $size['chest'] ?? '' }}" placeholder="Chest">
                        <input type="text" class="bp-form-control" name="sizes[{{ $i }}][length]" value="{{ $size['length'] ?? '' }}" placeholder="Length">
                        <button type="button" class="bp-btn bp-btn-sm bp-btn-icon bp-btn-danger remove-row"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Delivery -->
            <div class="bp-card mb-3">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-truck me-2"></i>Delivery Charges</h5>
                </div>
                <div class="bp-card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="bp-form-label">Inside Dhaka ({{ currency_symbol() }}) *</label>
                            <input type="number" class="bp-form-control" name="delivery_inside_dhaka" value="{{ old('delivery_inside_dhaka', $page->delivery_inside_dhaka) }}" min="0" step="0.01" required>
                        </div>
                        <div class="col-md-6">
                            <label class="bp-form-label">Outside Dhaka ({{ currency_symbol() }}) *</label>
                            <input type="number" class="bp-form-control" name="delivery_outside_dhaka" value="{{ old('delivery_outside_dhaka', $page->delivery_outside_dhaka) }}" min="0" step="0.01" required>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SEO -->
            <div class="bp-card mb-3">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-magnifying-glass me-2"></i>SEO</h5>
                </div>
                <div class="bp-card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="bp-form-label">Meta Title</label>
                            <input type="text" class="bp-form-control" name="meta_title" value="{{ old('meta_title', $page->meta_title) }}">
                        </div>
                        <div class="col-12">
                            <label class="bp-form-label">Meta Description</label>
                            <textarea class="bp-form-control" name="meta_description" rows="2">{{ old('meta_description', $page->meta_description) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Custom CSS -->
            <div class="bp-card mb-3">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-code me-2"></i>Custom CSS</h5>
                </div>
                <div class="bp-card-body">
                    <textarea class="bp-form-control bp-lp-css-editor" name="custom_css" rows="4">{{ old('custom_css', $page->custom_css) }}</textarea>
                </div>
            </div>

            <div class="d-flex gap-2 mb-4">
                <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-save me-1"></i> {{ $isEdit ? 'Update' : 'Create' }} Landing Page</button>
                <a href="{{ route('landing-pages.index') }}" class="bp-btn bp-btn-danger"><i class="fa-solid fa-xmark me-1"></i>Cancel</a>
            </div>

        </div>

        <!-- Right: Live Preview -->
        <div class="col-xl-7">
            <div class="bp-card bp-lp-preview-sticky">
                <div class="bp-card-header d-flex justify-content-between align-items-center">
                    <h5 class="bp-card-title mb-0"><i class="fa-solid fa-eye me-2"></i>Live Preview</h5>
                    @if($isEdit)
                    <a href="{{ route('landing-pages.preview', $page) }}" target="_blank" class="bp-btn bp-btn-sm bp-btn-outline"><i class="fa-solid fa-external-link me-1"></i> New Tab</a>
                    @endif
                </div>
                <div class="bp-card-body p-0">
                    <iframe id="previewFrame" class="bp-lp-preview-frame" src="about:blank"></iframe>
                </div>
            </div>
        </div>
    </div>
</form>

@endsection

@push('scripts')
<script>
'use strict';

$(function () {
    // Product picker uses Select2 AJAX search — kept page-local because the
    // global Select2 init only handles static option lists.
    $('#productPicker').select2({
        ajax: {
            url: '{{ route("landing-pages.search-products") }}',
            dataType: 'json',
            delay: 300,
            data: function (params) { return { q: params.term }; },
            processResults: function (data) { return { results: data }; }
        },
        minimumInputLength: 1,
        placeholder: 'Search products...',
        allowClear: true
    });

    // Template card selection (works even with d-none radios)
    $('.bp-template-card').on('click', function () {
        $('.bp-template-card').removeClass('active');
        $(this).addClass('active');
        $(this).find('input[type="radio"]').prop('checked', true).trigger('change');
    });
    $('.bp-template-card input[type="radio"]').on('change', function () {
        refreshPreview();
    });

    // Repeater: Add Benefit
    var benefitIdx = {{ count($page->sections['benefits'] ?? []) }};
    $('#addBenefit').on('click', function () {
        var html = '<div class="d-flex gap-2 mb-2 benefit-row">' +
            '<input type="text" class="bp-form-control" name="benefits[' + benefitIdx + '][text]" placeholder="Benefit text">' +
            '<button type="button" class="bp-btn bp-btn-sm bp-btn-icon bp-btn-danger remove-row"><i class="fa-solid fa-xmark"></i></button>' +
            '</div>';
        $('#benefitsContainer').append(html);
        benefitIdx++;
    });

    // Repeater: Add FAQ
    var faqIdx = {{ count($page->sections['faqs'] ?? []) }};
    $('#addFaq').on('click', function () {
        var html = '<div class="mb-3 p-2 border rounded faq-row">' +
            '<input type="text" class="bp-form-control mb-2" name="faqs[' + faqIdx + '][question]" placeholder="Question">' +
            '<textarea class="bp-form-control" name="faqs[' + faqIdx + '][answer]" rows="2" placeholder="Answer"></textarea>' +
            '<button type="button" class="bp-btn bp-btn-sm bp-btn-danger mt-1 remove-row"><i class="fa-solid fa-trash me-1"></i> Remove</button>' +
            '</div>';
        $('#faqsContainer').append(html);
        faqIdx++;
    });

    // Repeater: Add Size
    var sizeIdx = {{ count($page->sections['sizes'] ?? []) }};
    $('#addSize').on('click', function () {
        var html = '<div class="d-flex gap-2 mb-2 size-row">' +
            '<input type="text" class="bp-form-control" name="sizes[' + sizeIdx + '][size]" placeholder="Size (M, L...)">' +
            '<input type="text" class="bp-form-control" name="sizes[' + sizeIdx + '][chest]" placeholder="Chest">' +
            '<input type="text" class="bp-form-control" name="sizes[' + sizeIdx + '][length]" placeholder="Length">' +
            '<button type="button" class="bp-btn bp-btn-sm bp-btn-icon bp-btn-danger remove-row"><i class="fa-solid fa-xmark"></i></button>' +
            '</div>';
        $('#sizesContainer').append(html);
        sizeIdx++;
    });

    // Remove row
    $(document).on('click', '.remove-row', function () {
        $(this).closest('.benefit-row, .faq-row, .size-row').remove();
    });

    // Live preview refresh (debounced) — works for create + edit
    var previewUrl = @json($isEdit ? route('landing-pages.preview-data', $page) : route('landing-pages.preview-blank'));
    var previewTimer = null;
    function refreshPreview() {
        clearTimeout(previewTimer);
        previewTimer = setTimeout(function () {
            var formData = new FormData(document.getElementById('landingPageForm'));
            formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

            $.ajax({
                url: previewUrl,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (html) {
                    var frame = document.getElementById('previewFrame');
                    frame.contentDocument.open();
                    frame.contentDocument.write(html);
                    frame.contentDocument.close();
                }
            });
        }, 600);
    }

    refreshPreview();

    $(document).on('input change', '#landingPageForm input, #landingPageForm textarea, #landingPageForm select', function () {
        refreshPreview();
    });
});
</script>
@endpush

