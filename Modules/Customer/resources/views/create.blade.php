@extends('core::layouts.master')

@section('title', __('Add Customer'))
@section('page-title', __('Add Customer'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('customers.index') }}">Customers</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Add Customer</span>
@endsection

@section('page-actions')
    <a href="{{ route('customers.index') }}" class="bp-btn bp-btn-primary">
        <i class="fa-solid fa-arrow-left"></i> Back to Customers
    </a>
@endsection

@section('content')

    <form action="{{ route('customers.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="bp-card">
            <div class="bp-card-header">
                <h5 class="bp-card-title"><i class="fa-solid fa-user-plus me-2"></i>Customer Information</h5>
            </div>
            <div class="bp-card-body">
                <div class="row g-3">
                    <div class="col-12 category_img">
                        <x-core::image-upload name="photo" label="Photo" />
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <label class="bp-form-label">Full Name *</label>
                        <input type="text" class="bp-form-control @error('name') is-invalid @enderror" name="name"
                            value="{{ old('name') }}" required placeholder="Customer full name">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <label class="bp-form-label">Phone Number *</label>
                        <input type="text" class="bp-form-control @error('phone') is-invalid @enderror" name="phone"
                            value="{{ old('phone') }}" data-phone required>
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <label class="bp-form-label">Email</label>
                        <input type="email" class="bp-form-control @error('email') is-invalid @enderror" name="email"
                            value="{{ old('email') }}" placeholder="customer@email.com">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <label class="bp-form-label">Customer Group</label>
                        <select class="bp-form-select w-100 @error('customer_group_id') is-invalid @enderror"
                            name="customer_group_id">
                            <option value="">Select Group</option>
                            @foreach ($customerGroups as $group)
                                <option value="{{ $group->id }}"
                                    {{ old('customer_group_id') == $group->id ? 'selected' : '' }}>
                                    {{ $group->name }}{{ $group->discount_percentage > 0 ? ' (' . $group->discount_percentage . '% off)' : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('customer_group_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <label class="bp-form-label">District</label>
                        <select class="bp-form-select w-100" name="district" id="districtSelect"
                            data-thana-url="{{ url('admin/customers/thanas') }}">
                            <option value="">Select District</option>
                            @foreach ($districts as $d)
                                <option value="{{ $d->district_name }}" data-id="{{ $d->id }}"
                                    {{ old('district') == $d->district_name ? 'selected' : '' }}>{{ $d->district_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <label class="bp-form-label">Thana</label>
                        <select class="bp-form-select w-100" name="upazila" id="thanaSelect"
                            data-old="{{ old('upazila') }}">
                            <option value="">Select District first</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="bp-form-label">{{ __('Billing Address') }}</label>
                        <input type="text" class="bp-form-control" name="address" value="{{ old('address') }}"
                            placeholder="House, Road, Area">
                    </div>
                    <div class="col-md-6">
                        <label class="bp-form-label">{{ __('Shipping Address') }}</label>
                        <input type="text" class="bp-form-control" name="shipping_address"
                            value="{{ old('shipping_address') }}" placeholder="{{ __('Leave blank if same as billing') }}">
                    </div>
                    <div class="col-12">
                        <label class="bp-form-label">Notes</label>
                        <textarea class="bp-form-control" name="notes" rows="2" placeholder="Internal notes about this customer...">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>
            <div class="bp-card-footer text-end">
                <a href="{{ route('customers.index') }}" class="bp-btn bp-btn-danger me-2"><i
                        class="fa-solid fa-xmark me-1"></i>Cancel</a>
                <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-check me-1"></i> Save
                    Customer</button>
            </div>
        </div>
    </form>

@endsection

@push('scripts')
    <script>
        'use strict';
        $(function() {
            var $district = $('#districtSelect');
            var $thana = $('#thanaSelect');
            var oldThana = $thana.data('old') || '';
            var baseUrl = $district.data('thana-url');

            function loadThanas(districtId, preselect) {
                if (!districtId) {
                    $thana.html('<option value="">Select District first</option>');
                    return;
                }
                $thana.html('<option value="">Loading…</option>');
                $.getJSON(baseUrl + '/' + districtId, function(rows) {
                    var html = '<option value="">Select Thana</option>';
                    rows.forEach(function(t) {
                        var sel = (preselect && preselect === t.thana_name) ? ' selected' : '';
                        html += '<option value="' + t.thana_name + '"' + sel + '>' + t.thana_name +
                            '</option>';
                    });
                    $thana.html(html);
                }).fail(function() {
                    $thana.html('<option value="">Could not load thanas</option>');
                });
            }

            $district.on('change', function() {
                var districtId = $(this).find(':selected').data('id');
                loadThanas(districtId, '');
            });

            // On page load (validation re-render), restore the previously-selected
            // district's thana list and re-select the old thana.
            var initialId = $district.find(':selected').data('id');
            if (initialId) loadThanas(initialId, oldThana);
        });
    </script>
@endpush
