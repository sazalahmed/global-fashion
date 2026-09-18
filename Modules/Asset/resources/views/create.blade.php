@extends('core::layouts.master')

@section('title', __('Add Asset'))
@section('page-title', __('Add Asset'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('assets.index') }}">Assets</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Add</span>
@endsection

@section('page-actions')
    <a href="{{ route('assets.index') }}" class="bp-btn bp-btn-primary">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Assets
    </a>
@endsection

@section('content')

    <form action="{{ route('assets.store') }}" method="POST" enctype="multipart/form-data" id="assetForm">
        @csrf

        <div class="bp-card">
            <div class="bp-card-header">
                <h5 class="bp-card-title"><i class="fa-solid fa-building me-2"></i>Asset Details</h5>
            </div>
            <div class="bp-card-body">
                <div class="row g-3">
                    <div class="col-md-12 category_img">
                        <x-core::image-upload name="photo" label="Photo"
                            accept="image/jpg,image/jpeg,image/png,image/webp" />
                    </div>
                    <div class="col-md-4">
                        <label class="bp-form-label">Asset Name *</label>
                        <input type="text" class="bp-form-control" name="name" value="{{ old('name') }}"
                            placeholder="e.g. Desktop Computer" required>
                        @error('name')
                            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-2">
                        <label class="bp-form-label">Asset ID</label>
                        <input type="text" class="bp-form-control" value="Auto-generated" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="bp-form-label">Category *</label>
                        <x-core::select2 name="asset_category_id" placeholder="Select Category" required>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('asset_category_id') == $category->id)>{{ $category->name }}
                                </option>
                            @endforeach
                        </x-core::select2>
                        @error('asset_category_id')
                            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="bp-form-label">Serial Number</label>
                        <input type="text" class="bp-form-control" name="serial_number"
                            value="{{ old('serial_number') }}" placeholder="e.g. SN-123456789">
                        @error('serial_number')
                            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="bp-form-label">Vendor Name</label>
                        <input type="text" class="bp-form-control" name="vendor_name" value="{{ old('vendor_name') }}"
                            placeholder="e.g. Star Tech Ltd.">
                        @error('vendor_name')
                            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="bp-form-label">Vendor Invoice No</label>
                        <input type="text" class="bp-form-control" name="vendor_invoice_no"
                            value="{{ old('vendor_invoice_no') }}" placeholder="e.g. INV-2026-0091">
                        @error('vendor_invoice_no')
                            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="bp-form-label">Purchase Date *</label>
                        <input type="date" class="bp-form-control" name="purchase_date"
                            value="{{ old('purchase_date', date('Y-m-d')) }}" required>
                        @error('purchase_date')
                            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="bp-form-label">Price *</label>
                        <input type="number" class="bp-form-control" name="purchase_price"
                            value="{{ old('purchase_price') }}" placeholder="0" min="0" step="0.01" required>
                        @error('purchase_price')
                            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex align-items-center gap-2">
                            <label class="bp-form-label mb-1">Paid Amount</label>
                            <div class="fs-12 text-danger mb-1 text-nowrap">Due: <span
                                    id="duePreview">{{ currency_symbol() }} </div>
                        </div>

                        <input type="number" class="bp-form-control" name="paid_amount" id="paidAmount"
                            value="{{ old('paid_amount') }}" placeholder="0" min="0" step="0.01">

                        @error('paid_amount')
                            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="bp-form-label">Payment Account</label>
                        <select class="bp-form-select w-100" name="payment_account_id" id="paymentAccount">
                            <option value="">Select Account</option>
                            @if (isset($paymentAccounts))
                                @foreach ($paymentAccounts as $acc)
                                    <option value="{{ $acc->id }}"
                                        {{ old('payment_account_id') == $acc->id ? 'selected' : '' }}>{{ $acc->name }}
                                        ({{ ucfirst(str_replace('_', ' ', $acc->account_type)) }})
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="bp-form-label">Depreciable</label>
                        <select class="bp-form-select w-100" name="is_depreciable" id="isDepreciable">
                            <option value="1" {{ old('is_depreciable', '1') == '1' ? 'selected' : '' }}>Yes</option>
                            <option value="0" {{ old('is_depreciable') == '0' ? 'selected' : '' }}>No</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="bp-form-label">Depreciation Method</label>
                        <select class="bp-form-select w-100" name="depreciation_method" id="depreciationMethod">
                            <option value="straight_line"
                                {{ old('depreciation_method', 'straight_line') === 'straight_line' ? 'selected' : '' }}>
                                Straight Line</option>
                            <option value="declining_balance"
                                {{ old('depreciation_method') === 'declining_balance' ? 'selected' : '' }}>Declining
                                Balance</option>
                        </select>
                        @error('depreciation_method')
                            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="bp-form-label">Useful Life (Years)</label>
                        <input type="number" class="bp-form-control" name="useful_life_years" id="usefulLife"
                            value="{{ old('useful_life_years', 5) }}" min="1" max="50">
                        @error('useful_life_years')
                            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="bp-form-label">Warranty Expiry Date</label>
                        <input type="date" class="bp-form-control" name="warranty_expiry"
                            value="{{ old('warranty_expiry') }}">
                        @error('warranty_expiry')
                            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="bp-form-label">Warranty Info</label>
                        <input type="text" class="bp-form-control" name="warranty_info"
                            value="{{ old('warranty_info') }}" placeholder="e.g. 3-year on-site warranty">
                        @error('warranty_info')
                            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="bp-form-label">Next Maintenance Date</label>
                        <input type="date" class="bp-form-control" name="next_maintenance_date"
                            value="{{ old('next_maintenance_date') }}">
                        @error('next_maintenance_date')
                            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-12">
                        <label class="bp-form-label">Description / Notes</label>
                        <input type="text" class="bp-form-control" name="description"
                            value="{{ old('description') }}" placeholder="Any additional details about this asset...">
                        @error('description')
                            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            <div class="bp-card-footer d-flex justify-content-end gap-2">
                <a href="{{ route('assets.index') }}" class="bp-btn bp-btn-danger"><i
                        class="fa-solid fa-xmark me-1"></i>Cancel</a>
                <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-save me-1"></i> Save
                    Asset</button>
            </div>
        </div>

    </form>

@endsection

@push('scripts')
    <script>
        'use strict';

        $(function() {
            // Toggle depreciation fields based on depreciable selection
            $('#isDepreciable').on('change', function() {
                var isNo = $(this).val() === '0';
                $('#depreciationMethod, #usefulLife').prop('disabled', isNo);
            });

            // Live "due" preview. Blank paid amount means "fully paid" only
            // when a payment account is chosen — money must come from an
            // account. Blank + no account = the full price stays due.
            function updateDue() {
                var price = parseFloat($('input[name="purchase_price"]').val()) || 0;
                var paidRaw = $('#paidAmount').val();
                var hasAccount = $('#paymentAccount').val() !== '';
                var paid = paidRaw === '' ? (hasAccount ? price : 0) : (parseFloat(paidRaw) || 0);
                var due = Math.max(0, price - paid);
                $('#duePreview').text('{{ currency_symbol() }} ' + due.toLocaleString('en-IN'));
            }
            $('input[name="purchase_price"], #paidAmount').on('input', updateDue);
            $('#paymentAccount').on('change', updateDue);
            updateDue();
        });
    </script>
@endpush
