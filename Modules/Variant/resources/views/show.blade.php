@extends('core::layouts.master')

@section('title', __('Variant Attribute Detail'))
@section('page-title', __('Variant Attribute Detail'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('products.index') }}">Products</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('variants.index') }}">Variants</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>{{ $attribute->name }}</span>
@endsection

@section('page-actions')
    <a href="{{ route('variants.index') }}" class="bp-btn bp-btn-primary">
        <i class="fa-solid fa-arrow-left"></i> Back to Variants
    </a>
    @bpCan('variants.edit')
    <a href="{{ route('variants.edit', $attribute) }}" class="bp-btn bp-btn-primary">
        <i class="fa-solid fa-pen"></i> Edit Attribute
    </a>
    @endbpCan
@endsection

@section('content')

    <!-- Attribute Info Card -->
    <div class="bp-card mb-4">
        <div class="bp-card-header">
            <h5 class="bp-card-title"><i class="fa-solid fa-swatchbook me-2"></i>Attribute Information</h5>
        </div>
        <div class="bp-card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="fs-12 text-muted fw-600 mb-1">Attribute Name</div>
                    <div class="fw-700 fs-13">{{ $attribute->name }}</div>
                </div>
                <div class="col-md-4">
                    <div class="fs-12 text-muted fw-600 mb-1">Display Type</div>
                    <div><span
                            class="bp-badge {{ $attribute->display_type === 'color_swatch' ? 'bp-badge-info' : ($attribute->display_type === 'button' ? 'bp-badge-primary' : 'bp-badge-secondary') }}">{{ $attribute->display_type_label }}</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="fs-12 text-muted fw-600 mb-1">Status</div>
                    <div>
                        @if ($attribute->status === 'active')
                            <span class="bp-badge bp-badge-success">Active</span>
                        @else
                            <span class="bp-badge bp-badge-danger">Inactive</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Attribute Values Card -->
    <div class="bp-card mb-4">
        <div class="bp-card-header">
            <h5 class="bp-card-title"><i class="fa-solid fa-tags me-2"></i>Attribute Values
                ({{ $attribute->values->count() }})</h5>
        </div>
        <div class="bp-card-body p-0">
            <div class="bp-table-wrapper">
                <table class="bp-table">
                    <thead>
                        <tr>
                            <th>Value Name</th>
                            @if ($attribute->display_type === 'color_swatch')
                                <th>Color Swatch</th>
                            @endif
                            <th>Sort Order</th>
                            <th>Products Using</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attribute->values as $value)
                            <tr>
                                <td class="fw-700 fs-13">{{ $value->value }}</td>
                                @if ($attribute->display_type === 'color_swatch')
                                    <td><span class="bp-color-swatch" data-color="{{ $value->color_code }}"></span>
                                        {{ $value->color_code }}</td>
                                @endif
                                <td>{{ $value->sort_order }}</td>
                                <td>0</td>
                            </tr>
                        @empty
                            <x-core::table.empty :colspan="$attribute->display_type === 'color_swatch' ? 4 : 3" icon="fa-tags" :title="__('No values found for this attribute')" />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Products Using This Variant -->
    <div class="bp-card">
        <div class="bp-card-header">
            <h5 class="bp-card-title"><i class="fa-solid fa-boxes-stacked me-2"></i>Products Using This Variant</h5>
        </div>
        <div class="bp-card-body p-0">
            <div class="bp-table-wrapper">
                <table class="bp-table">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>SKU</th>
                            <th>Values Used</th>
                            <th>Price</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <x-core::table.empty :colspan="5" icon="fa-boxes-stacked" :title="__('No products are using this variant yet')" />
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        'use strict';

        $(function() {
            // Apply background color to color swatches from data attribute
            $('.bp-color-swatch[data-color]').each(function() {
                $(this).css('background-color', $(this).data('color'));
            });
        });
    </script>
@endpush
