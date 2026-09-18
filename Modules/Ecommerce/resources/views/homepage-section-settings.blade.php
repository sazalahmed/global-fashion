@extends('core::layouts.master')

@php use Modules\Ecommerce\Support\HomepageSectionSchema; @endphp

@section('title', __('Section Settings — Website'))
@section('page-title', 'Section Settings: ' . $section->title)

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('ecommerce.index') }}">Website</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('ecommerce.homepage-sections') }}">Manage Sections</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>{{ $section->title }}</span>
@endsection

@section('page-actions')
    <a href="{{ route('ecommerce.homepage-sections') }}" class="bp-btn bp-btn-primary">
        <i class="fa-solid fa-arrow-left me-1"></i> Back
    </a>
@endsection

@section('content')

    @php
        $schema = HomepageSectionSchema::for($section->section_type);
        $groups = collect($schema)->groupBy('group');
        $presets = HomepageSectionSchema::linkPresets();
    @endphp

    <form action="{{ route('ecommerce.homepage-sections.settings.update', $section) }}" method="POST"
        enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row g-4">
            <div class="col-xl-8">

                @if ($groups->isEmpty())
                    <div class="bp-card mb-4">
                        <div class="bp-card-body text-muted">
                            This section has no editable content fields.
                            @if ($section->section_type === 'hero_slider' || $section->section_type === 'promo_banners')
                                Slides/banners are managed under <a href="{{ route('ecommerce.banners') }}">Banners</a>.
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Schema-driven content groups --}}
                @foreach ($groups as $groupName => $fields)
                    <div class="bp-card mb-4">
                        <div class="bp-card-header">
                            <h5 class="bp-card-title"><i class="fa-solid fa-sliders me-2"></i>{{ $groupName }}</h5>
                        </div>
                        <div class="bp-card-body">
                            <div class="row g-3 category_img">
                                @foreach ($fields as $f)
                                    @php
                                        $key = $f['key'];
                                        $cur = old("fields.$key", $section->getSetting($key, $f['default']));
                                        $col = in_array($f['type'], ['textarea', 'image']) ? 'col-12' : 'col-md-6';
                                    @endphp
                                    <div class="{{ $col }}">
                                        <label class="bp-form-label">{{ $f['label'] }}</label>

                                        @switch($f['type'])
                                            @case('textarea')
                                                <textarea class="bp-form-control" name="fields[{{ $key }}]" rows="3">{{ $cur }}</textarea>
                                            @break

                                            @case('number')
                                                <input type="number" class="bp-form-control" name="fields[{{ $key }}]"
                                                    value="{{ $cur }}" min="0">
                                            @break

                                            @case('switch')
                                                <div class="form-check form-switch">
                                                    <input type="hidden" name="fields[{{ $key }}]" value="0">
                                                    <input class="form-check-input" type="checkbox"
                                                        name="fields[{{ $key }}]" value="1"
                                                        {{ $cur ? 'checked' : '' }}>
                                                </div>
                                            @break

                                            @case('image')
                                                @php $imgUrl = $cur ? upload_url($cur) : asset($f['default']); @endphp
                                                <x-core::image-upload name="fields[{{ $key }}]"
                                                    id="secimg_{{ $key }}" :label="false"
                                                    removeName="remove[{{ $key }}]" :current="$imgUrl"
                                                    hint="JPG, PNG, WebP — max 2MB" />
                                            @break

                                            @case('link')
                                                @php $isPreset = array_key_exists($cur, $presets); @endphp
                                                <input type="hidden" name="fields[{{ $key }}]"
                                                    id="linkval_{{ $key }}" value="{{ $cur }}">
                                                <select class="bp-form-select w-100 bp-link-preset" data-key="{{ $key }}">
                                                    @foreach ($presets as $route => $label)
                                                        <option value="{{ $route }}"
                                                            {{ $cur === $route ? 'selected' : '' }}>{{ $label }}</option>
                                                    @endforeach
                                                    <option value="__custom__" {{ !$isPreset ? 'selected' : '' }}>Custom URL…
                                                    </option>
                                                </select>
                                                <input type="text"
                                                    class="bp-form-control mt-2 bp-link-custom {{ $isPreset ? 'd-none' : '' }}"
                                                    data-key="{{ $key }}" value="{{ !$isPreset ? $cur : '' }}"
                                                    placeholder="https://...">
                                            @break

                                            @default
                                                <input type="text" class="bp-form-control" name="fields[{{ $key }}]"
                                                    value="{{ $cur }}">
                                        @endswitch
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach

                @bpCan('ecommerce.edit')
                <div class="text-end">
                    <button type="submit" class="bp-btn bp-btn-success">
                        <i class="fa-solid fa-save me-1"></i> Save Settings
                    </button>
                </div>
                @endbpCan
            </div>

            <div class="col-xl-4">
                <div class="bp-card">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-circle-info me-2"></i>Section Info</h5>
                    </div>
                    <div class="bp-card-body">
                        <div class="mb-2"><span class="fw-600 fs-12 text-muted">Type:</span>
                            <code>{{ $section->section_type }}</code>
                        </div>
                        <div class="mb-2"><span class="fw-600 fs-12 text-muted">Status:</span>
                            @if ($section->is_active)
                            <span class="bp-badge bp-badge-success">Active</span>@else<span
                                    class="bp-badge bp-badge-dark">Inactive</span>
                            @endif
                        </div>
                        <div class="mb-2"><span class="fw-600 fs-12 text-muted">Sort Order:</span>
                            {{ $section->sort_order }}</div>
                        @if ($section->isProductSection())
                            <a href="{{ route('ecommerce.homepage-sections.products', $section) }}"
                                class="bp-btn bp-btn-sm bp-btn-outline w-100 mt-2">
                                <i class="fa-solid fa-boxes-stacked me-1"></i> Manage Products &amp; Combos
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

    </form>

@endsection

@push('scripts')
    <script>
        'use strict';
        $(function() {
            function syncLink(key) {
                var sel = $('.bp-link-preset[data-key="' + key + '"]').val();
                var $custom = $('.bp-link-custom[data-key="' + key + '"]');
                if (sel === '__custom__') {
                    $custom.removeClass('d-none');
                    $('#linkval_' + key).val($custom.val());
                } else {
                    $custom.addClass('d-none');
                    $('#linkval_' + key).val(sel);
                }
            }
            $(document).on('change', '.bp-link-preset', function() {
                syncLink($(this).data('key'));
            });
            $(document).on('input', '.bp-link-custom', function() {
                var k = $(this).data('key');
                if ($('.bp-link-preset[data-key="' + k + '"]').val() === '__custom__') $('#linkval_' + k)
                    .val($(this).val());
            });
            // Image preview is handled by the image-upload component (app.js).
        });
    </script>
@endpush
