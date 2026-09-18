@props([
    'name',
    'id' => null,
    'label' => 'Image',
    'accept' => 'image/*',
    'hint' => 'JPG, PNG, WebP — max 2MB',
    'current' => null,
    'help' => null,
    'required' => false,
    'removeName' => null,
])
@php
    $inputId = $id ?? ($name . 'Input');
    $hasCurrent = ! empty($current);
@endphp
<div class="bp-image-upload">
    @if ($label)
        <label class="bp-form-label" for="{{ $inputId }}">{{ $label }}@if ($required)
                <span class="text-danger">*</span>
            @endif
        </label>
    @endif
    <div class="bp-image-dropzone bp-thumbnail-dropzone {{ $hasCurrent ? 'has-image' : '' }}" data-image-upload>
        <input type="file" {{ $attributes->merge(['class' => 'd-none js-thumb-input']) }} accept="{{ $accept }}"
            name="{{ $name }}" id="{{ $inputId }}" @if ($required && ! $hasCurrent) required @endif>

        @if ($removeName)
            <input type="hidden" name="{{ $removeName }}" value="0" class="js-thumb-remove-flag">
        @endif

        {{-- Image state: preview + delete button --}}
        <figure class="bp-thumb-figure js-thumb-figure">
            <img class="bp-thumbnail-preview js-thumb-preview" @if ($hasCurrent) src="{{ $current }}" @endif
                alt="{{ $label }} preview">
            <button type="button" class="bp-thumb-remove js-thumb-remove" aria-label="Remove image" title="Remove">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </figure>

        {{-- Empty state: placeholder + browse button --}}
        <div class="bp-thumb-placeholder js-thumb-placeholder">
            <i class="fa-solid fa-image fa-2x text-muted mb-2 d-block"></i>
            <div class="fw-700 fs-13 mb-1">Upload {{ $label }}</div>
            <div class="fs-11 text-muted mb-3">{{ $hint }}</div>
            <span class="bp-btn bp-btn-sm bp-btn-primary bp-btn-upload js-thumb-browse">
                <i class="fa-solid fa-upload me-1"></i> Browse
            </span>
        </div>
    </div>
    @if ($help)
        <small class="text-muted fs-11 mt-2 d-block">{{ $help }}</small>
    @endif
    @error($name)
        <div class="text-danger fs-12 mt-1">{{ $message }}</div>
    @enderror
</div>
