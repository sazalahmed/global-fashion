@extends('core::layouts.master')

@section('title', __('Create Sale'))
@section('page-title', __('Create New Sale'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('sales.index') }}">{{ __('Sales') }}</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>{{ __('Create Sale') }}</span>
@endsection

@section('page-actions')
    <a href="{{ route('sales.index') }}" class="bp-btn bp-btn-primary">
        <i class="fa-solid fa-arrow-left me-1"></i> {{ __('Back to Sales') }}
    </a>
@endsection

@push('styles')
    <link href="{{ asset('vendor/venobox/venobox.min.css') }}" rel="stylesheet">
@endpush

@section('content')

    <div class="bp-sale-form-wrapper">

        @if ($errors->any())
            <div class="alert alert-danger">
                <strong><i class="fa-solid fa-triangle-exclamation me-1"></i> {{ __('Please fix the following:') }}</strong>
                <ul class="mb-0 mt-1 ps-3">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('sales.store') }}" method="POST" id="newInvoiceForm">
            @csrf

            {{-- ── Sale Details ── --}}
            <div class="bp-card mb-4">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-file-invoice me-2"></i>{{ __('Order Details') }}</h5>
                </div>
                <div class="bp-card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="bp-form-label">{{ __('Date') }} *</label>
                            <input type="date" class="bp-form-control" name="invoice_date"
                                value="{{ old('invoice_date', date('Y-m-d')) }}" required>
                            @error('invoice_date')
                                <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="bp-form-label d-flex justify-content-between align-items-center">
                                <span>{{ __('Customer') }} *</span>
                                <a href="#" id="btnNewCustomer" class="fs-12 text-primary fw-600 text-decoration-none"
                                    title="{{ __('Create new customer') }}">
                                    <i class="fa-solid fa-plus me-1"></i>{{ __('New Customer') }}
                                </a>
                            </label>
                            <select class="bp-form-select w-100 select2-search" name="customer_id" id="customerSelect">
                                <option value="">{{ __('Walk-in Customer') }}</option>
                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->id }}" data-name="{{ $customer->name }}"
                                        data-phone="{{ $customer->phone ?? '' }}"
                                        data-address="{{ $customer->address ?? '' }}"
                                        data-billing="{{ $customer->address ?? '' }}"
                                        data-shipping="{{ $customer->shipping_address ?? '' }}"
                                        data-due="{{ $previousDueMap[$customer->id] ?? 0 }}"
                                        {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                        {{ $customer->name }}{{ $customer->phone ? ' (' . $customer->phone . ')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('customer_id')
                                <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="bp-form-label">{{ __('Price Type') }}</label>
                            <select class="bp-form-select w-100" name="price_type">
                                <option value="regular" {{ old('price_type', 'regular') === 'regular' ? 'selected' : '' }}>
                                    {{ __('Regular Price') }}</option>
                                <option value="wholesale" {{ old('price_type') === 'wholesale' ? 'selected' : '' }}>
                                    {{ __('Wholesale Price') }}</option>
                                <option value="resell" {{ old('price_type') === 'resell' ? 'selected' : '' }}>
                                    {{ __('Reseller Price') }}</option>
                            </select>
                        </div>

                        <div class="col-md-2 d-none">
                            <label class="bp-form-label">{{ __('Branch') }}</label>
                            <select class="bp-form-select w-100" name="branch_id">
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}"
                                        {{ old('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Address quick view (read-only cards) + Edit toggle --}}
                        <x-core::address-quickview :show-contact="true" />

                        {{-- Editable name/phone/address fields — revealed by the Edit button --}}
                        <div class="col-12 d-none" id="addrEditFields">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="bp-form-label">{{ __('Recipient Name') }}</label>
                                    <input type="text" class="bp-form-control" name="customer_name_snapshot"
                                        id="recipientName" value="{{ old('customer_name_snapshot') }}"
                                        placeholder="{{ __('Recipient name') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="bp-form-label">{{ __('Phone') }}</label>
                                    <input type="text" class="bp-form-control" name="customer_phone_snapshot"
                                        id="recipientPhone" value="{{ old('customer_phone_snapshot') }}"
                                        placeholder="{{ __('Recipient phone') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="bp-form-label">{{ __('Billing Address') }}</label>
                                    <textarea class="bp-form-control" name="billing_address" id="billingAddress" rows="3"
                                        placeholder="{{ __('Billing address') }}">{{ old('billing_address') }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="bp-form-label">{{ __('Shipping Address') }}</label>
                                    <textarea class="bp-form-control" name="customer_address" id="customerAddress" rows="3"
                                        placeholder="{{ __('Delivery address') }}">{{ old('customer_address') }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="bp-form-label">{{ __('Select Product') }}</label>
                            <div class="bp-sale-product-search">
                                <button type="button" class="bp-barcode-btn" title="{{ __('Scan barcode') }}"><i
                                        class="fa-solid fa-barcode"></i></button>
                                <input type="text" id="productSearchInput" class="bp-form-control"
                                    placeholder="{{ __('Please type product code or name and select...') }}"
                                    autocomplete="off">
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- ── Order Items ── --}}
            <div class="bp-card mb-4">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-list me-2"></i>{{ __('Order Items') }} *</h5>
                </div>
                <div class="bp-card-body p-0">
                    <div class="bp-table-wrapper">
                        <table class="bp-table bp-order-table bp-has-variant-col" id="orderTable">
                            <thead>
                                <tr>
                                    <th class="bp-col-img">{{ __('Image') }}</th>
                                    <th class="bp-col-product">{{ __('Product') }}</th>
                                    <th class="bp-col-variants">{{ __('Variant Details') }}</th>
                                    <th>{{ __('Total Quantity') }}</th>
                                    <th>{{ __('Discount') }}</th>
                                    <th>{{ __('Subtotal') }}</th>
                                    <th>{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="empty-row">
                                    <td colspan="7">
                                        {{ __('No products added yet. Search and select a product above.') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- ── Order Adjustments ── --}}
            <div class="bp-card mb-4">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-calculator me-2"></i>{{ __('Order Adjustments') }}
                    </h5>
                </div>
                <div class="bp-card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="bp-form-label">{{ __('Order Discount') }}</label>
                            <div class="input-group">
                                <select class="bp-form-select bp-discount-type-addon" name="discount_type"
                                    id="orderDiscountType">
                                    <option value="fixed"
                                        {{ old('discount_type', 'fixed') === 'fixed' ? 'selected' : '' }}>
                                        Fixed Amount ({{ currency_symbol() }})</option>
                                    <option value="percentage"
                                        {{ old('discount_type') === 'percentage' ? 'selected' : '' }}>Percentage (%)
                                    </option>
                                </select>
                                <input type="number" class="bp-form-control" name="discount_value"
                                    id="orderDiscountValue" value="{{ old('discount_value', 0) }}" step="0.01"
                                    min="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="bp-form-label">{{ __('Shipping Cost') }}</label>
                            <input type="number" class="bp-form-control" name="shipping_charge" id="shippingCharge"
                                value="{{ old('shipping_charge', 0) }}" step="0.01" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="bp-form-label">{{ __('Sale Status') }} *</label>
                            <select class="bp-form-select w-100" name="sale_status" id="saleStatus" required>
                                @foreach ($saleStatuses as $val => $label)
                                    <option value="{{ $val }}"
                                        {{ old('sale_status', 'pending') === $val ? 'selected' : '' }}>{{ __($label) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row mt-1 g-3">
                        <div class="col-md-6">
                            <label class="bp-form-label">{{ __('District') }}</label>
                            <select class="bp-form-select w-100" name="district_id" id="districtSelect"
                                data-thanas-url="{{ route('locations.districts.thanas', ['district' => '__ID__']) }}">
                                <option value="">{{ __('Select district...') }}</option>
                                @foreach ($districts as $district)
                                    <option value="{{ $district->id }}"
                                        {{ old('district_id') == $district->id ? 'selected' : '' }}>
                                        {{ $district->district_name }}</option>
                                @endforeach
                            </select>
                            @error('district_id')
                                <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="bp-form-label">{{ __('Thana') }}</label>
                            <select class="bp-form-select w-100" name="thana_id" id="thanaSelect">
                                <option value="">{{ __('Select thana...') }}</option>
                            </select>
                            @error('thana_id')
                                <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="bp-form-label">{{ __('Note') }}</label>
                            <textarea class="bp-form-control" name="notes" rows="3"
                                placeholder="{{ __('Customer-facing note (sent to courier)') }}">{{ old('notes') }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="bp-form-label">{{ __('Item Description') }}</label>
                            <textarea class="bp-form-control" name="item_description" rows="3"
                                placeholder="{{ __('Parcel contents sent to courier (auto-filled from items if left blank)') }}">{{ old('item_description') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Advance ── --}}
            <div class="bp-card mb-4">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-credit-card me-2"></i>{{ __('Advance') }}</h5>
                </div>
                <div class="bp-card-body">
                    <div class="row g-3 align-items-start">
                        <div class="col-lg-8">
                            <div id="salePaymentRows">
                                <div class="roe bp-split-payment-row" data-index="0">
                                    <div class="bp-pay-amount-col">
                                        <input type="number" class="bp-form-control sale-pay-amount"
                                            name="payments[0][amount]" placeholder="{{ __('Amount') }}" step="0.01"
                                            min="0">
                                    </div>
                                    <div class="bp-pay-method-col">
                                        <select class="bp-form-select sale-pay-account"
                                            name="payments[0][payment_account_id]">
                                            <option value="">{{ __('Select Account') }}</option>
                                            @foreach ($paymentAccounts as $pa)
                                                <option value="{{ $pa->id }}" data-type="{{ $pa->account_type }}">
                                                    {{ $pa->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="bp-pay-ref-col">
                                        <input type="text" class="bp-form-control" name="payments[0][reference]"
                                            placeholder="{{ __('Ref / TXN ID') }}">
                                    </div>
                                    <button type="button" class="remove-split d-none" title="{{ __('Remove') }}"><i
                                            class="fa-solid fa-times"></i></button>
                                </div>
                            </div>
                            <button type="button" class="bp-btn bp-btn-sm bp-btn-info mt-2" id="saleAddPayment"><i
                                    class="fa-solid fa-plus me-1"></i> {{ __('Add Split Advance') }}</button>
                            <div class="fs-11 text-muted mt-2">
                                <i
                                    class="fa-solid fa-circle-info me-1"></i>{{ __('Record any amount paid in advance. No account selected = default Cash account. Leave blank for a fully-due (COD) order.') }}
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="bp-advance-summary">
                                <div class="bp-adv-row bp-adv-prevdue d-none" id="salePrevDueRow">
                                    <span>{{ __('Previous Due') }}</span>
                                    <span class="bp-adv-prevdue-val" id="salePrevDue">{{ currency_symbol() }} 0</span>
                                </div>
                                <div class="bp-adv-row">
                                    <span>{{ __('Advance') }}</span>
                                    <span class="bp-adv-advance" id="salePaidTotal">{{ currency_symbol() }} 0</span>
                                </div>
                                <div class="bp-adv-row">
                                    <span>{{ __('Due') }}</span>
                                    <span class="bp-adv-due" id="saleDueDisplay">{{ currency_symbol() }} 0</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Form Actions ── --}}
            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('sales.index') }}" class="bp-btn bp-btn-danger"><i
                        class="fa-solid fa-xmark me-1"></i>{{ __('Cancel') }}</a>
                <button type="submit" class="bp-btn bp-btn-warning" name="action" value="draft"><i
                        class="fa-solid fa-save me-1"></i> {{ __('Save Draft') }}</button>
                <button type="submit" class="bp-btn bp-btn-success" name="action" value="create" id="submitButton"><i
                        class="fa-solid fa-check me-1"></i> {{ __('Submit') }}</button>
            </div>
        </form>
    </div>

    {{-- ── Sticky bottom totals bar ── --}}
    <div class="bp-sale-totals-bar" id="saleTotalsBar">
        <div class="bp-totals-grid">
            <div class="bp-totals-cell"><span class="bp-totals-label">{{ __('Items') }}</span><span
                    class="bp-totals-value" id="footerItems">0</span></div>
            <div class="bp-totals-cell"><span class="bp-totals-label">{{ __('Total') }}</span><span
                    class="bp-totals-value" id="footerTotal">0.00</span></div>
            <div class="bp-totals-cell"><span class="bp-totals-label">{{ __('Order Discount') }}</span><span
                    class="bp-totals-value" id="footerOrderDiscount">0.00</span></div>
            <div class="bp-totals-cell"><span class="bp-totals-label">{{ __('Shipping Cost') }}</span><span
                    class="bp-totals-value" id="footerShipping">0.00</span></div>
            <div class="bp-totals-cell grand"><span class="bp-totals-label">{{ __('Grand Total') }}</span><span
                    class="bp-totals-value" id="footerGrandTotal">0.00</span></div>
        </div>
    </div>

    {{-- Hidden product catalog used by the autocomplete --}}
    @php
        $productCatalogJson = $products
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'image' => $p->display_image ? upload_url($p->display_image) : null,
                    'model' => $p->model,
                    'sku' => $p->sku,
                    'barcode' => $p->barcode,
                    'price' => (float) ($p->sell_price ?? 0),
                    'wholesale' => (float) ($p->wholesale_price ?? ($p->sell_price ?? 0)),
                    'resell' => (float) ($p->resell_price ?? ($p->wholesale_price ?? ($p->sell_price ?? 0))),
                    'tax_rate' => (float) ($p->vat_rate ?? 0),
                    'discount_percent' => (function () use ($p) {
                        $dp = $p->displayPrice();
                        return $dp->has_discount && $dp->sell > 0
                            ? round((1 - $dp->effective / $dp->sell) * 100, 6)
                            : 0;
                    })(),
                    'type' => $p->product_type,
                    'variants' => $p->relationLoaded('variants')
                        ? $p->variants
                            ->map(
                                fn($v) => [
                                    'id' => $v->id,
                                    'sku' => $v->sku,
                                    'name' => $v->variant_name,
                                    'price' => (float) $v->effective_sell_price,
                                    'wholesale' => (float) $v->effective_wholesale_price,
                                    'resell' => (float) $v->effective_resell_price,
                                    'stock' => (int) ($v->stock_qty ?? 0),
                                ],
                            )
                            ->values()
                        : [],
                ];
            })
            ->values()
            ->toJson(JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    @endphp
    <script type="application/json" id="productCatalogJson">{!! $productCatalogJson !!}</script>
    <script type="application/json" id="comboCatalogJson">{!! json_encode($comboCatalog ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>

    <div class="modal fade" id="saleVariantModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa-solid fa-layer-group me-2"></i>{{ __('Select Variants') }} —
                        <span id="saleVariantModalProduct" class="fw-700"></span>
                        <span id="saleVariantDiscountNote" class="bp-badge bp-badge-warning ms-2 d-none"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="saleVariantError" class="alert alert-danger d-none fs-13"></div>
                    <div class="bp-table-wrapper">
                        <table class="bp-table">
                            <thead>
                                <tr>
                                    <th>{{ __('Variant') }}</th>
                                    <th>{{ __('SKU') }}</th>
                                    <th class="bp-col-110">{{ __('Qty') }}</th>
                                    <th class="bp-col-140">{{ __('Unit Price') }} ({{ currency_symbol() }})</th>
                                    <th class="bp-col-140 sv-afterdisc-col d-none">{{ __('After Disc.') }}
                                        ({{ currency_symbol() }})</th>
                                </tr>
                            </thead>
                            <tbody id="saleVariantRows"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="bp-btn bp-btn-danger" data-bs-dismiss="modal"><i
                            class="fa-solid fa-xmark me-1"></i>{{ __('Cancel') }}</button>
                    <button type="button" class="bp-btn bp-btn-success" id="saleVariantConfirm"><i
                            class="fa-solid fa-cart-plus me-1"></i> {{ __('Add to Order') }}</button>
                </div>
            </div>
        </div>
    </div>

    @include('sale::partials.combo-manager-modal')

    {{-- New Customer modal — mirrors the full customer create form --}}
    <div class="modal fade" id="quickCustomerModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa-solid fa-user-plus me-2"></i>{{ __('New Customer') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="qcForm" enctype="multipart/form-data">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="bp-form-label">{{ __('Full Name') }} *</label>
                                <input type="text" class="bp-form-control" name="name" id="qcName" required
                                    placeholder="{{ __('Customer full name') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="bp-form-label">{{ __('Phone Number') }} *</label>
                                <input type="text" class="bp-form-control" name="phone" id="qcPhone" required
                                    placeholder="01XXXXXXXXX">
                            </div>
                            <div class="col-md-6">
                                <label class="bp-form-label">{{ __('Email') }}</label>
                                <input type="email" class="bp-form-control" name="email" id="qcEmail"
                                    placeholder="customer@email.com">
                            </div>
                            <div class="col-md-6">
                                <label class="bp-form-label">{{ __('Customer Group') }}</label>
                                <select class="bp-form-select w-100" name="customer_group_id" id="qcGroup"
                                    data-no-select2>
                                    <option value="">{{ __('Select Group') }}</option>
                                    @foreach ($customerGroups as $group)
                                        <option value="{{ $group->id }}">
                                            {{ $group->name }}{{ $group->discount_percentage > 0 ? ' (' . $group->discount_percentage . '% off)' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="bp-form-label">{{ __('District') }}</label>
                                <select class="bp-form-select w-100" name="district" id="qcDistrict" data-no-select2
                                    data-thana-url="{{ url('admin/customers/thanas') }}">
                                    <option value="">{{ __('Select District') }}</option>
                                    @foreach ($districts as $d)
                                        <option value="{{ $d->district_name }}" data-id="{{ $d->id }}">
                                            {{ $d->district_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="bp-form-label">{{ __('Thana') }}</label>
                                <select class="bp-form-select w-100" name="upazila" id="qcThana" data-no-select2>
                                    <option value="">{{ __('Select Thana first') }}</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="bp-form-label">{{ __('Street Address') }}</label>
                                <input type="text" class="bp-form-control" name="address" id="qcAddress"
                                    placeholder="{{ __('House, Road, Area') }}">
                            </div>
                            <div class="col-md-12">
                                <label class="bp-form-label">{{ __('Photo') }}</label>
                                <input type="file" class="bp-form-control" name="photo" id="qcPhoto"
                                    accept="image/*">
                            </div>
                            <div class="col-12">
                                <label class="bp-form-label">{{ __('Notes') }}</label>
                                <textarea class="bp-form-control" name="notes" id="qcNotes" rows="2"
                                    placeholder="{{ __('Internal notes about this customer...') }}"></textarea>
                            </div>
                        </div>
                        <div class="text-danger fs-12 d-none mt-2" id="qcError"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="bp-btn bp-btn-danger" data-bs-dismiss="modal"><i
                            class="fa-solid fa-xmark me-1"></i>{{ __('Cancel') }}</button>
                    <button type="button" class="bp-btn bp-btn-success" id="qcSave"><i
                            class="fa-solid fa-check me-1"></i> {{ __('Save & Select') }}</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Payment account options template (advance rows) --}}
    <template id="paymentAccountOptionsTemplate">
        <option value="">{{ __('Select Account') }}</option>
        @foreach ($paymentAccounts as $pa)
            <option value="{{ $pa->id }}" data-type="{{ $pa->account_type }}">{{ $pa->name }}</option>
        @endforeach
    </template>

@endsection

@push('scripts')
    <script src="{{ asset('vendor/venobox/venobox.min.js') }}"></script>
    <script>
        'use strict';

        // Order line items to restore after a validation error (empty otherwise).
        window.salePrefillItems = @json($prefillItems ?? []);

        $(function() {

            // ── Product catalog and row state ──
            var productCatalog = JSON.parse($('#productCatalogJson').text() || '[]');
            var comboCatalog = JSON.parse($('#comboCatalogJson').text() || '[]');
            var itemIndex = 0;

            // ── Combos: one row per combo, managed via a modal ──
            // A combo is a single order row showing the package price only. Its
            // components live in the row's data-combo-components attribute and are
            // expanded into per-product items[] (with prices auto-allocated to sum
            // to the combo price) on submit, so stock/accounting stay per product.
            function comboGroupId() {
                return 'cmb-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 10);
            }

            // Base catalog unit price for a product (variant price wins).
            function catalogUnitPrice(productId, variantId) {
                var p = productCatalog.find(function(x) {
                    return x.id === parseInt(productId, 10);
                });
                if (!p) return 0;
                if (variantId && p.variants) {
                    var v = p.variants.find(function(x) {
                        return String(x.id) === String(variantId);
                    });
                    if (v) return v.price || 0;
                }
                return p.price || 0;
            }

            function comboTotalQty(components) {
                return components.reduce(function(s, c) {
                    return s + (parseInt(c.qty, 10) || 1);
                }, 0);
            }

            // Distribute the combo price across components (weighted by catalog
            // price × qty), last component absorbs the rounding remainder so the
            // line totals sum EXACTLY to the combo price. Mirrors ComboService.
            function allocateCombo(components, comboPrice) {
                comboPrice = parseFloat(comboPrice) || 0;
                var n = components.length;
                var weights = components.map(function(c) {
                    return catalogUnitPrice(c.productId, c.variantId) * (parseInt(c.qty, 10) || 1);
                });
                var weightTotal = weights.reduce(function(a, b) {
                    return a + b;
                }, 0);
                var lineTotals = [],
                    running = 0;
                components.forEach(function(c, i) {
                    var lt;
                    if (i === n - 1) {
                        lt = Math.round((comboPrice - running) * 100) / 100;
                    } else {
                        var share = weightTotal > 0 ? weights[i] / weightTotal : 1 / Math.max(n, 1);
                        lt = Math.round(comboPrice * share * 100) / 100;
                        running += lt;
                    }
                    lineTotals[i] = lt;
                });
                return components.map(function(c, i) {
                    var qty = parseInt(c.qty, 10) || 1;
                    // Round the unit price UP so qty×unit ≥ lineTotal; the tiny excess
                    // is posted as a reconciling line discount, keeping the stored
                    // subtotal exactly equal to the allocated line total.
                    var unit = qty > 0 ? Math.ceil(lineTotals[i] / qty * 100) / 100 : 0;
                    return {
                        unitPrice: unit,
                        lineTotal: lineTotals[i]
                    };
                });
            }

            // Read a combo row's state back into an object.
            function comboFromRow($row) {
                var comps = [];
                try {
                    comps = JSON.parse($row.attr('data-combo-components') || '[]');
                } catch (e) {
                    comps = [];
                }
                return {
                    comboId: $row.attr('data-combo-id'),
                    group: $row.attr('data-combo-group'),
                    name: $row.attr('data-combo-name'),
                    price: parseFloat($row.attr('data-combo-price')) || 0,
                    components: comps
                };
            }

            // Render (or replace) a single combo row in the order table.
            function renderComboRow(combo, $replace) {
                var totalQty = comboTotalQty(combo.components);
                var img = combo.image || (combo.components[0] && combo.components[0].image) || '';
                var imgCell = img ?
                    '<a class="venobox" data-gall="saleline" href="' + img + '"><img src="' + img +
                    '" class="bp-line-item-img" alt=""></a>' :
                    '<div class="bp-line-item-img bp-line-item-img-ph"><i class="fa-solid fa-layer-group"></i></div>';
                var compHtml = combo.components.map(function(c) {
                    var name = c.productName || '';
                    var vl = c.variantLabel || '';
                    // Skip the variant chip when it's already baked into the
                    // product name (or vice-versa) — avoids the duplicated text
                    // seen with mirrored/variant-named products.
                    var extra = (vl && name.indexOf(vl) === -1 && vl.indexOf(name) === -1) ?
                        '<span class="bp-combo-comp-variant">' + escapeHtml(vl) + '</span>' : '';
                    return '<div class="bp-combo-comp-line"><span class="bp-combo-comp-qty">' +
                        (parseInt(c.qty, 10) || 1) + '×</span> ' + escapeHtml(name) + extra + '</div>';
                }).join('');
                var row = '<tr data-combo-row="1" data-combo-id="' + combo.comboId +
                    '" data-combo-group="' + escapeHtml(combo.group) + '" data-combo-name="' + escapeHtml(combo
                        .name) +
                    '" data-combo-price="' + (parseFloat(combo.price) || 0) + '" data-combo-components="' +
                    escapeHtml(JSON.stringify(combo.components)) + '">' +
                    '<td class="bp-col-img">' + imgCell + '</td>' +
                    '<td class="bp-col-product"><div class="fw-600">' + escapeHtml(combo.name) + '</div>' +
                    '<span class="bp-badge bp-badge-secondary fs-11 mt-1 d-inline-block"><i class="fa-solid fa-layer-group me-1"></i>{{ __('Combo') }} · ' +
                    combo.components.length + ' {{ __('products') }}</span></td>' +
                    '<td class="bp-col-variants"><div class="bp-combo-comp-list">' + compHtml + '</div></td>' +
                    '<td><span class="fw-600">' + totalQty + '</span></td>' +
                    '<td><span class="text-muted">—</span></td>' +
                    '<td class="fw-700 sale-line-subtotal text-nowrap">{{ currency_symbol() }} ' + formatBDT(
                        parseFloat(combo.price) || 0) + '</td>' +
                    '<td class="text-nowrap">' +
                    '<button type="button" class="bp-btn bp-btn-sm bp-btn-icon bp-btn-success btn-edit-combo me-1" title="{{ __('Edit combo') }}"><i class="fa-solid fa-pen"></i></button>' +
                    '<button type="button" class="bp-btn bp-btn-sm bp-btn-icon bp-btn-danger row-remove" title="{{ __('Remove') }}"><i class="fa-solid fa-xmark"></i></button></td>' +
                    '</tr>';
                var $row = $(row);
                $('#orderTable tbody .empty-row').remove();
                if ($replace && $replace.length) $replace.replaceWith($row);
                // Newest product on top for interactive adds; prefill (edit /
                // validation restore) keeps its saved order via appendTo.
                else if (window.__salePrefilling) $row.appendTo('#orderTable tbody');
                else $row.prependTo('#orderTable tbody');
                bindVenobox($row);
                recalculate();
                return $row;
            }

            $(document).on('click', '#addComboBtn', function() {
                var cid = parseInt($('#comboPicker').val(), 10);
                if (!cid) return;
                var combo = comboCatalog.find(function(c) {
                    return c.id === cid;
                });
                if (!combo || !combo.items || !combo.items.length) return;
                var components = combo.items.map(function(it) {
                    return {
                        productId: it.product_id,
                        productName: it.product_name,
                        image: it.image || '',
                        variantId: it.variant_id || '',
                        variantLabel: it.variant_label || '',
                        qty: parseInt(it.quantity, 10) || 1
                    };
                });
                renderComboRow({
                    comboId: combo.id,
                    group: comboGroupId(),
                    name: combo.name,
                    image: combo.image || '',
                    price: combo.price,
                    components: components
                });
                $('#comboPicker').val('').trigger('change');
            });

            // ── Combo manager modal: set the package price and add/edit/remove
            //    the products inside a combo. Works on a copy of the row's
            //    components and writes back to the row on Save. ──
            var bsComboManager = new bootstrap.Modal(document.getElementById('comboManagerModal'));
            var comboMgr = {
                row: null,
                components: []
            };

            function cmgrVariantOptions(pid, selVid) {
                var p = productCatalog.find(function(x) {
                    return String(x.id) === String(pid);
                });
                var html = '<option value="">{{ __('No variant') }}</option>';
                if (p && p.variants) p.variants.forEach(function(v) {
                    html += '<option value="' + v.id + '"' + (String(v.id) === String(selVid) ?
                            ' selected' : '') +
                        '>' + escapeHtml(v.name) + '</option>';
                });
                return html;
            }

            function cmgrRenderComponents() {
                var $tb = $('#comboMgrItems tbody');
                $tb.empty();
                if (!comboMgr.components.length) {
                    $tb.append(
                        '<tr class="cmgr-empty"><td colspan="4" class="text-center text-muted py-3">{{ __('No products in this combo yet.') }}</td></tr>'
                    );
                    return;
                }
                comboMgr.components.forEach(function(c, i) {
                    var p = productCatalog.find(function(x) {
                        return x.id === parseInt(c.productId, 10);
                    });
                    var hasVariants = p && p.variants && p.variants.length;
                    var variantCell = hasVariants ?
                        '<select class="bp-form-select cmgr-variant" data-idx="' + i + '">' +
                        cmgrVariantOptions(c.productId, c.variantId) + '</select>' :
                        '<span class="text-muted">—</span>';
                    // Stored product_name carries the variant suffix (" — L"); the
                    // variant has its own column here, so drop the suffix to avoid
                    // showing it twice.
                    var pname = c.productName || '';
                    var vl = c.variantLabel || '';
                    if (vl && pname.slice(-(vl.length + 3)) === ' — ' + vl) {
                        pname = pname.slice(0, -(vl.length + 3));
                    }
                    $tb.append('<tr data-idx="' + i + '">' +
                        '<td class="fw-600">' + escapeHtml(pname) + '</td>' +
                        '<td>' + variantCell + '</td>' +
                        '<td><input type="number" class="bp-form-control bp-input-narrow cmgr-qty" data-idx="' +
                        i + '" value="' + (parseInt(c.qty, 10) || 1) + '" min="1" step="1"></td>' +
                        '<td class="text-end"><button type="button" class="bp-btn bp-btn-sm bp-btn-icon bp-btn-danger cmgr-remove" data-idx="' +
                        i + '"><i class="fa-solid fa-xmark"></i></button></td>' +
                        '</tr>');
                });
            }

            function openComboManager($row) {
                comboMgr.row = $row;
                var data = comboFromRow($row);
                comboMgr.components = data.components.map(function(c) {
                    return $.extend({}, c);
                });
                $('#comboMgrTitle').text(data.name || '{{ __('Combo') }}');
                $('#comboMgrPrice').val(data.price || 0);
                cmgrRenderComponents();
                // The master layout auto-inits any .select2-search on load — when
                // empty. Always (re)populate the product options and re-init so the
                // dropdown isn't stuck on "No results found".
                if ($('#comboMgrAddProduct').hasClass('select2-hidden-accessible')) {
                    $('#comboMgrAddProduct').select2('destroy');
                }
                $('#comboMgrAddProduct').html('<option value="">{{ __('Select product') }}</option>' +
                    productCatalog.map(function(p) {
                        return '<option value="' + p.id + '">' + escapeHtml(p.name) + '</option>';
                    }).join(''));
                if (window.bpInitSelect2) window.bpInitSelect2($('#comboMgrAddProduct'));
                $('#comboMgrAddVariant').html('<option value="">{{ __('No variant') }}</option>');
                $('#comboMgrAddQty').val(1);
                bsComboManager.show();
            }

            $(document).on('click', '.btn-edit-combo', function() {
                openComboManager($(this).closest('tr'));
            });

            $(document).on('change', '.cmgr-variant', function() {
                var i = parseInt($(this).data('idx'), 10);
                var vid = $(this).val();
                comboMgr.components[i].variantId = vid;
                var p = productCatalog.find(function(x) {
                    return x.id === parseInt(comboMgr.components[i].productId, 10);
                });
                var v = p && p.variants ? p.variants.find(function(x) {
                        return String(x.id) === String(vid);
                    }) :
                    null;
                comboMgr.components[i].variantLabel = v ? v.name : '';
            });
            $(document).on('input', '.cmgr-qty', function() {
                var i = parseInt($(this).data('idx'), 10);
                comboMgr.components[i].qty = parseInt($(this).val(), 10) || 1;
            });
            $(document).on('click', '.cmgr-remove', function() {
                var i = parseInt($(this).data('idx'), 10);
                comboMgr.components.splice(i, 1);
                cmgrRenderComponents();
            });

            $('#comboMgrAddProduct').on('change', function() {
                $('#comboMgrAddVariant').html(cmgrVariantOptions($(this).val(), ''));
            });
            $('#comboMgrAddBtn').on('click', function() {
                var pid = parseInt($('#comboMgrAddProduct').val(), 10);
                if (!pid) return;
                var p = productCatalog.find(function(x) {
                    return x.id === pid;
                });
                if (!p) return;
                var vid = $('#comboMgrAddVariant').val() || '';
                var vlabel = '';
                if (vid && p.variants) {
                    var v = p.variants.find(function(x) {
                        return String(x.id) === String(vid);
                    });
                    if (v) vlabel = v.name;
                }
                comboMgr.components.push({
                    productId: pid,
                    productName: p.name,
                    image: p.image || '',
                    variantId: vid,
                    variantLabel: vlabel,
                    qty: parseInt($('#comboMgrAddQty').val(), 10) || 1
                });
                cmgrRenderComponents();
                $('#comboMgrAddProduct').val('').trigger('change');
                $('#comboMgrAddVariant').html('<option value="">{{ __('No variant') }}</option>');
                $('#comboMgrAddQty').val(1);
            });

            $('#comboMgrSave').on('click', function() {
                if (!comboMgr.row || !comboMgr.components.length) return;
                var data = comboFromRow(comboMgr.row);
                data.components = comboMgr.components;
                data.price = parseFloat($('#comboMgrPrice').val()) || 0;
                renderComboRow(data, comboMgr.row);
                bsComboManager.hide();
            });

            // ── Price-type helper ──
            // Returns the regular or wholesale price for a product/variant based on
            // the currently selected Price Type. Falls back to the regular price
            // when a wholesale price is not available.
            function currentPriceType() {
                return $('[name="price_type"]').val() || 'regular';
            }

            function priceFor(product, variant) {
                var src = variant || product || {};
                var type = currentPriceType();
                if (type === 'resell') {
                    return src.resell != null ? src.resell :
                        (src.wholesale != null ? src.wholesale : (src.price || 0));
                }
                if (type === 'wholesale') {
                    return src.wholesale != null ? src.wholesale : (src.price || 0);
                }
                return src.price || 0;
            }

            // Round an after-discount amount UP to a whole BDT, matching
            // Product::displayPrice(). Settles to paisa first: a discount
            // carried as a percentage lands a hair either side of the exact
            // figure, and 690.0000000000001 must not ceil to 691.
            function ceilMoney(value) {
                return Math.ceil(Math.round((parseFloat(value) || 0) * 100) / 100);
            }

            function roundMoney(value) {
                return Math.round((parseFloat(value) || 0) * 100) / 100;
            }

            // Taka off ONE unit for a storefront percentage, rounded the same way
            // the line total and the shop both round, so converting the percentage
            // to an amount does not shift the price by a paisa.
            function unitDiscountFor(price, percent) {
                price = parseFloat(price) || 0;
                percent = parseFloat(percent) || 0;
                if (price <= 0 || percent <= 0) return 0;
                return roundMoney(price - ceilMoney(price * (1 - percent / 100)));
            }

            // ── Currency formatter (BD lakh system) ──
            function formatBDT(num) {
                num = Math.round((parseFloat(num) || 0) * 100) / 100;
                var str = num.toFixed(2);
                var parts = str.split('.');
                var intPart = parts[0];
                var lastThree = intPart.substring(intPart.length - 3);
                var otherNumbers = intPart.substring(0, intPart.length - 3);
                if (otherNumbers !== '') lastThree = ',' + lastThree;
                return otherNumbers.replace(/\B(?=(\d{2})+(?!\d))/g, ',') + lastThree + (parts[1] === '00' ? '' :
                    '.' + parts[1]);
            }

            // Escape for safe insertion into HTML (incl. double-quoted attribute
            // values) — quotes must be escaped so JSON/labels can't corrupt markup.
            function escapeHtml(str) {
                if (str === null || str === undefined) return '';
                return String(str)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#39;');
            }

            // ── Product autocomplete (simple list filter on focus/keyup) ──
            var $searchInput = $('#productSearchInput');
            var $dropdown = $('<div class="bp-autocomplete-dropdown"></div>')
                .addClass('list-group position-absolute w-100 shadow-sm')
                .css({
                    zIndex: 1050,
                    top: '100%',
                    left: 0,
                    maxHeight: '280px',
                    overflowY: 'auto'
                });
            $searchInput.parent().css('position', 'relative').append($dropdown);
            $dropdown.hide();

            function renderSuggestions(term) {
                term = (term || '').toLowerCase();
                var matches = productCatalog.filter(function(p) {
                    return !term ||
                        (p.name || '').toLowerCase().indexOf(term) >= 0 ||
                        (p.model || '').toLowerCase().indexOf(term) >= 0 ||
                        (p.sku || '').toLowerCase().indexOf(term) >= 0 ||
                        (p.barcode || '').toLowerCase().indexOf(term) >= 0;
                }).slice(0, 12);

                $dropdown.empty();
                if (matches.length === 0) {
                    $dropdown.append(
                        '<div class="list-group-item text-muted fs-12">{{ __('No products found') }}</div>');
                } else {
                    matches.forEach(function(p) {
                        var item = $(
                                '<button type="button" class="list-group-item list-group-item-action"></button>'
                            )
                            .text(p.name)
                            .data('product', p);
                        $dropdown.append(item);
                    });
                }
                $dropdown.show();
            }

            // Bind 'click' as well as 'focus': after adding a product we re-focus the
            // input programmatically, so a subsequent click on the already-focused
            // input fires no 'focus' event — without 'click' the list would stay hidden.
            $searchInput.on('focus click', function() {
                renderSuggestions($(this).val());
            });
            $searchInput.on('input', function() {
                renderSuggestions($(this).val());
            });
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.bp-sale-product-search').length) $dropdown.hide();
            });
            $dropdown.on('click', '.list-group-item-action', function() {
                var p = $(this).data('product');
                // Variable products with variants must be configured via the picker modal.
                if (p && p.type === 'variable' && Array.isArray(p.variants) && p.variants.length) {
                    openVariantPicker(p);
                } else if (p) {
                    addLine({
                        productId: p.id,
                        productName: p.name,
                        image: p.image || '',
                        variantId: '',
                        variantLabel: '',
                        quantity: 1,
                        unitPrice: priceFor(p)
                    });
                }
                $searchInput.val('').focus();
                $dropdown.hide();
            });

            // ── Add a consolidated line row ──
            // A variable product becomes ONE row whose `breakdown` ([{id,name,qty,
            // price}]) is rendered in the Variant Details cell; a simple product is
            // a single line. Inputs carry no name= — the row is expanded into flat
            // per-variant items[] on submit (see the submit handler), so the sale
            // backend keeps receiving one entry per variant for correct stock.
            function addLine(d) {
                var hasBreakdown = Array.isArray(d.breakdown) && d.breakdown.length > 0;

                // Simple products de-dupe (bump qty); variable rows are picker-driven.
                if (!hasBreakdown) {
                    var existing = $('#orderTable tbody tr[data-product-id="' + d.productId +
                        '"][data-variant-id="' + (d.variantId || '') + '"]').filter(function() {
                        return !$(this).attr('data-breakdown') && !$(this).attr('data-combo-row');
                    });
                    if (existing.length) {
                        var $q = existing.find('.sale-line-qty');
                        $q.val((parseInt($q.val(), 10) || 0) + (d.quantity || 1));
                        recalculate();
                        return;
                    }
                }

                $('#orderTable tbody .empty-row').remove();
                var qty = d.quantity || 1;
                var price = d.unitPrice || 0;
                var imgCell = d.image ?
                    '<a class="venobox" data-gall="saleline" href="' + d.image + '"><img src="' + d.image +
                    '" class="bp-line-item-img" alt=""></a>' :
                    '<div class="bp-line-item-img bp-line-item-img-ph"><i class="fa-solid fa-box"></i></div>';

                var variantCell = '<span class="text-muted">—</span>';
                if (hasBreakdown) {
                    var rowsHtml = '';
                    d.breakdown.forEach(function(b) {
                        rowsHtml += '<div class="bp-qline-v-row">' +
                            '<span class="bp-qline-v-name">' + escapeHtml(b.name) + '</span>' +
                            '<span class="bp-qline-v-qty">' + b.qty + ' pcs</span>' +
                            '<span class="bp-qline-v-price">{{ currency_symbol() }} ' + formatBDT(b
                                .price) +
                            '<span class="bp-qline-v-unit">/pcs</span></span>' +
                            '</div>';
                    });
                    variantCell = '<div class="bp-qline-variants">' + rowsHtml + '</div>';
                }

                var qtyAttr = hasBreakdown ? ' readonly title="{{ __('Total of variant quantities') }}"' : '';
                var editBtn = hasBreakdown ?
                    '<button type="button" class="bp-btn bp-btn-sm bp-btn-icon bp-btn-success btn-edit-variant me-1" title="{{ __('Edit variants') }}"><i class="fa-solid fa-pen"></i></button>' :
                    '';

                // Model number comes from the catalog so every add path
                // (search, variant picker, barcode, prefilled items) shows it.
                var catalogEntry = productCatalog.find(function(x) {
                    return String(x.id) === String(d.productId);
                }) || {};
                var modelHtml = catalogEntry.model ?
                    '<div class="fs-12 text-muted">{{ __('Model') }}: ' + escapeHtml(catalogEntry.model) +
                    '</div>' : '';

                var row = '<tr data-product-id="' + d.productId + '" data-variant-id="' + (d.variantId || '') +
                    '" data-variant-label="' + escapeHtml(d.variantLabel || '') + '">' +
                    '<td class="bp-col-img">' + imgCell + '</td>' +
                    '<td class="bp-col-product"><div class="fw-600">' + escapeHtml(d.productName) + '</div>' +
                    modelHtml + '</td>' +
                    '<td class="bp-col-variants">' + variantCell + '</td>' +
                    '<td><input type="number" class="bp-form-control bp-input-narrow sale-line-qty" value="' + qty +
                    '" min="1" step="1"' + qtyAttr + '>' +
                    '<input type="hidden" class="sale-line-price" value="' + price + '"></td>' +
                    '<td>' +
                    '<div class="bp-line-discount gap-2">' +
                    '<select class="bp-form-select sale-line-disc-type">' +
                    '<option value="fixed">{{ currency_symbol() }}</option>' +
                    '<option value="percentage">%</option>' +
                    '</select>' +
                    '<input type="number" class="bp-form-control sale-line-disc-input" value="0" min="0" step="0.01">' +
                    '</div>' +
                    '<input type="hidden" class="sale-line-disc-amount" value="0">' +
                    '</td>' +
                    '<td class="fw-700 sale-line-subtotal text-nowrap">{{ currency_symbol() }} 0</td>' +
                    '<td class="text-nowrap">' + editBtn +
                    '<button type="button" class="bp-btn bp-btn-sm bp-btn-icon bp-btn-danger row-remove" title="{{ __('Remove') }}"><i class="fa-solid fa-xmark"></i></button></td>' +
                    '</tr>';
                var $row = $(row);
                if (hasBreakdown) $row.attr('data-breakdown', JSON.stringify(d.breakdown));
                if (typeof d.discount === 'number') {
                    // Explicit discount passed (e.g. prefill restore after a
                    // validation error) — honour it as-is, never auto-override.
                    if (d.discount > 0) $row.find('.sale-line-disc-input').val(d.discount);
                } else if (!d.replaceRow && currentPriceType() === 'regular') {
                    // Fresh add (not an edit/replace) at the regular price tier:
                    // pre-fill the line discount from the product's storefront
                    // discount so the order matches the shop. Wholesale/reseller
                    // sales don't get the storefront discount.
                    var catItem = productCatalog.find(function(x) {
                        return x.id === d.productId;
                    });
                    if (catItem && catItem.discount_percent > 0) {
                        // Sellers work in taka, so the storefront's percentage is
                        // converted to what it comes to on this line and the row
                        // stays on ৳. A flat amount cannot scale on its own, so the
                        // per-unit figure is kept on the row and the amount follows
                        // the quantity until someone types their own — otherwise
                        // raising the qty would quietly stop matching the shop.
                        var unitOff = unitDiscountFor(price, catItem.discount_percent);
                        if (unitOff > 0) {
                            $row.attr('data-auto-disc-unit', unitOff);
                            $row.find('.sale-line-disc-input').val(roundMoney(unitOff * qty));
                        }
                    }
                }
                if (d.replaceRow && d.replaceRow.length) {
                    d.replaceRow.replaceWith($row);
                } else if (window.__salePrefilling) {
                    $row.appendTo('#orderTable tbody'); // prefill keeps saved order
                } else {
                    $row.prependTo('#orderTable tbody'); // newest product on top
                }
                bindVenobox($row);
                recalculate();
            }

            // Open line-item product images in a VenoBox lightbox. Re-bind per new row.
            function bindVenobox($scope) {
                var $links = ($scope || $('#orderTable')).find('a.venobox');
                if ($links.length && $.fn.venobox) $links.venobox();
            }

            // ── Variant picker modal ──
            var variantPickerProduct = null;
            var variantPickerRow = null; // set when editing an existing line
            var bsSaleVariantModal = new bootstrap.Modal(document.getElementById('saleVariantModal'));

            // `prefill` (optional) is a breakdown array used to restore qty/price
            // when re-opening the picker to edit an existing variant line.
            function openVariantPicker(product, prefill, editingRow) {
                variantPickerProduct = product;
                variantPickerRow = editingRow || null;
                var prefillMap = {};
                (prefill || []).forEach(function(b) {
                    prefillMap[b.id] = b;
                });
                $('#saleVariantModalProduct').text(product.name + (product.model ? ' — ' + product.model : ''));
                $('#saleVariantError').addClass('d-none').text('');
                var esc = function(s) {
                    return $('<div/>').text(s == null ? '' : s).html();
                };
                // Product-level storefront discount (% off). Drives the read-only
                // "After Disc." column and the header note. The actual discount is
                // applied once, on the consolidated order line, when added.
                // Only the regular price tier gets the storefront discount —
                // wholesale/reseller pricing sells at its own (already-lower) rate.
                var disc = currentPriceType() === 'regular' ? (parseFloat(product.discount_percent) || 0) : 0;
                var effPrice = function(p) {
                    // Ceil to a whole BDT to match the storefront and the order
                    // line's rounded subtotal (Product::displayPrice uses ceil).
                    return disc > 0 ? ceilMoney(p * (1 - disc / 100)) : p;
                };
                var html = '';
                product.variants.forEach(function(v) {
                    var pre = prefillMap[v.id];
                    var qVal = pre ? pre.qty : 0;
                    var pVal = pre ? pre.price : priceFor(product, v);
                    // Out-of-stock size/color: flag the row red so the seller sees
                    // it's unavailable (still selectable — backorder is allowed).
                    var stock = parseInt(v.stock, 10) || 0;
                    var oos = stock <= 0;
                    var stockHtml = oos ?
                        '<span class="bp-badge bp-badge-danger fs-11">{{ __('Out of stock') }}</span>' :
                        '<span class="fs-11 text-muted">{{ __('Stock') }}: ' + stock + '</span>';
                    html += '<tr data-variant-id="' + v.id + '"' + (oos ? ' class="bp-variant-oos"' : '') +
                        '>' +
                        '<td class="fw-600">' + esc(v.name) + '<div class="mt-1">' + stockHtml +
                        '</div></td>' +
                        '<td>' + esc(v.sku || '') + '</td>' +
                        '<td><input type="number" class="bp-form-control sv-qty" value="' + qVal +
                        '" min="0" step="1"></td>' +
                        '<td><input type="number" class="bp-form-control sv-price" value="' + pVal +
                        '" min="0" step="0.01"></td>' +
                        '<td class="sv-afterdisc-col fw-600 sv-after d-none">{{ currency_symbol() }} ' +
                        formatBDT(effPrice(pVal)) + '</td>' +
                        '</tr>';
                });
                $('#saleVariantRows').html(html);
                $('#saleVariantRows').data('disc', disc);

                // Show the discount note + After Disc. column only when discounted.
                if (disc > 0) {
                    $('#saleVariantDiscountNote').text((Math.round(disc * 100) / 100) + '% off').removeClass(
                        'd-none');
                    $('#saleVariantModal').find('.sv-afterdisc-col').removeClass('d-none');
                } else {
                    $('#saleVariantDiscountNote').addClass('d-none');
                    $('#saleVariantModal').find('.sv-afterdisc-col').addClass('d-none');
                }
                bsSaleVariantModal.show();
            }

            // Recompute a row's "After Disc." cell live when its unit price changes.
            $('#saleVariantRows').on('input', '.sv-price', function() {
                var disc = parseFloat($('#saleVariantRows').data('disc')) || 0;
                if (disc <= 0) return;
                var price = parseFloat($(this).val()) || 0;
                var eff = ceilMoney(price * (1 - disc / 100));
                $(this).closest('tr').find('.sv-after').text('{{ currency_symbol() }} ' + formatBDT(eff));
            });

            $('#saleVariantConfirm').on('click', function() {
                if (!variantPickerProduct) return;
                var p = variantPickerProduct;
                var chosen = [];
                $('#saleVariantRows tr').each(function() {
                    var qty = parseInt($(this).find('.sv-qty').val(), 10) || 0;
                    if (qty > 0) {
                        var vid = parseInt($(this).attr('data-variant-id'), 10);
                        var variant = p.variants.find(function(x) {
                            return x.id === vid;
                        });
                        chosen.push({
                            id: vid,
                            name: variant ? variant.name : '',
                            qty: qty,
                            price: parseFloat($(this).find('.sv-price').val()) || 0
                        });
                    }
                });
                if (chosen.length === 0) {
                    $('#saleVariantError').removeClass('d-none').text(
                        '{{ __('Enter a quantity for at least one variant.') }}');
                    return;
                }
                var totalQty = 0,
                    totalAmount = 0;
                chosen.forEach(function(c) {
                    totalQty += c.qty;
                    totalAmount += c.qty * c.price;
                });
                var firstPrice = chosen[0].price;
                var allSame = chosen.every(function(c) {
                    return c.price === firstPrice;
                });
                var unitPrice = allSame ? firstPrice : Math.round((totalAmount / totalQty) * 100) / 100;
                addLine({
                    productId: p.id,
                    productName: p.name,
                    image: p.image || '',
                    variantId: '',
                    variantLabel: 'Multiple',
                    quantity: totalQty,
                    unitPrice: unitPrice,
                    breakdown: chosen,
                    replaceRow: variantPickerRow
                });
                variantPickerRow = null;
                bsSaleVariantModal.hide();
            });

            // ── Edit an existing variant line — re-open the picker pre-filled ──
            $(document).on('click', '#orderTable .btn-edit-variant', function() {
                var $row = $(this).closest('tr');
                var pid = parseInt($row.attr('data-product-id'), 10);
                var product = productCatalog.find(function(p) {
                    return p.id === pid;
                });
                if (!product) return;
                var breakdown = [];
                try {
                    breakdown = JSON.parse($row.attr('data-breakdown') || '[]');
                } catch (e) {
                    breakdown = [];
                }
                openVariantPicker(product, breakdown, $row);
            });

            $(document).on('click', '#orderTable .row-remove', function() {
                $(this).closest('tr').remove();
                if (!$('#orderTable tbody tr').length) {
                    $('#orderTable tbody').append(
                        '<tr class="empty-row"><td colspan="7">{{ __('No products added yet. Search and select a product above.') }}</td></tr>'
                    );
                }
                recalculate();
            });

            $(document).on('input',
                '#orderTable .sale-line-qty, #orderTable .sale-line-disc-input, #orderDiscountValue, #shippingCharge',
                function() {
                    var $row = $(this).closest('tr');
                    if ($(this).hasClass('sale-line-disc-input')) {
                        // Typed over: the amount belongs to the seller now, so it
                        // stops tracking the quantity.
                        $row.removeAttr('data-auto-disc-unit');
                    } else if ($(this).hasClass('sale-line-qty')) {
                        var unitOff = parseFloat($row.attr('data-auto-disc-unit')) || 0;
                        if (unitOff > 0) {
                            var q = parseFloat($row.find('.sale-line-qty').val()) || 0;
                            $row.find('.sale-line-disc-input').val(roundMoney(unitOff * q));
                        }
                    }
                    recalculate();
                });
            $(document).on('change', '#orderDiscountType, #orderTable .sale-line-disc-type', function() {
                // Switching to % (or back) is a deliberate choice — leave the value
                // alone from here rather than overwriting it on the next qty change.
                $(this).closest('tr').removeAttr('data-auto-disc-unit');
                recalculate();
            });

            // ── Price type change: re-apply regular/wholesale price to simple rows.
            // Variant (breakdown) rows keep the prices chosen in the picker. ──
            $('[name="price_type"]').on('change', function() {
                $('#orderTable tbody tr[data-product-id]').each(function() {
                    var $row = $(this);
                    if ($row.attr('data-breakdown')) return;
                    var product = productCatalog.find(function(p) {
                        return p.id === parseInt($row.attr('data-product-id'), 10);
                    });
                    if (product) $row.find('.sale-line-price').val(priceFor(product));
                });
                recalculate();
            });

            // ── Customer's previous due (display-only) ──
            function updatePrevDue() {
                var due = parseFloat($('#customerSelect').find('option:selected').data('due')) || 0;
                if (due > 0) {
                    $('#salePrevDue').text('{{ currency_symbol() }} ' + formatBDT(due));
                    $('#salePrevDueRow').removeClass('d-none');
                } else {
                    $('#salePrevDueRow').addClass('d-none');
                }
            }

            // ── Customer change: auto-fill billing/shipping address + show previous due ──
            // Prefill only when the target field is currently empty — never clobber a
            // value the user already typed or edited.
            function prefillCustomerAddresses() {
                var $opt = $('#customerSelect').find('option:selected');
                var billing = $opt.data('billing') || $opt.data('address') || '';
                var shipping = $opt.data('shipping') || billing;
                var name = $opt.data('name') || '';
                var phone = $opt.data('phone') || '';
                if (billing && !$('#billingAddress').val()) {
                    $('#billingAddress').val(billing);
                }
                if (shipping && !$('#customerAddress').val()) {
                    $('#customerAddress').val(shipping);
                }
                if (name && !$('#recipientName').val()) {
                    $('#recipientName').val(name);
                }
                if (phone && !$('#recipientPhone').val()) {
                    $('#recipientPhone').val(phone);
                }
            }

            // Render the read-only address cards from the editable field values.
            function renderAddrCards() {
                var billing = ($('#billingAddress').val() || '').trim();
                var shipping = ($('#customerAddress').val() || '').trim();
                var name = ($('#recipientName').val() || '').trim();
                var phone = ($('#recipientPhone').val() || '').trim();
                var contact = [name, phone].filter(Boolean).join(' • ') || '—';
                $('#billingContactText').text(contact);
                $('#shippingContactText').text(contact);
                $('#billingCardText').text(billing || '—');
                if (!shipping || shipping === billing) {
                    $('#shipSameBadge').removeClass('d-none');
                    $('#shippingCardText').text(billing || '—');
                } else {
                    $('#shipSameBadge').addClass('d-none');
                    $('#shippingCardText').text(shipping);
                }
            }

            // Edit toggle: reveal/hide the editable fields; cards stay visible as
            // a live preview. Button label flips Edit <-> Done.
            $('#addrEditToggle').on('click', function() {
                var opening = $('#addrEditFields').hasClass('d-none');
                $('#addrEditFields').toggleClass('d-none', !opening);
                $(this).html(opening ?
                    '<i class="fa-solid fa-check me-1"></i>{{ __('Done') }}' :
                    '<i class="fa-solid fa-pen me-1"></i>{{ __('Edit') }}');
                if (!opening) renderAddrCards();
            });
            // Keep cards in sync while typing.
            $('#billingAddress, #customerAddress, #recipientName, #recipientPhone').on('input', renderAddrCards);

            $('#customerSelect').on('change', function() {
                prefillCustomerAddresses();
                renderAddrCards();
                updatePrevDue();
            });
            updatePrevDue();
            prefillCustomerAddresses();
            renderAddrCards();

            // ── New Customer modal — full customer form ──
            var qcModal = new bootstrap.Modal(document.getElementById('quickCustomerModal'));

            $('#btnNewCustomer').on('click', function(e) {
                e.preventDefault();
                document.getElementById('qcForm').reset();
                $('#qcThana').html('<option value="">{{ __('Select District first') }}</option>');
                $('#qcError').addClass('d-none').text('');
                qcModal.show();
                setTimeout(function() {
                    $('#qcName').trigger('focus');
                }, 250);
            });

            // Cascading thana inside the modal (same pattern as the customer create page).
            $('#qcDistrict').on('change', function() {
                var $thana = $('#qcThana');
                var districtId = $(this).find(':selected').data('id');
                if (!districtId) {
                    $thana.html('<option value="">{{ __('Select District first') }}</option>');
                    return;
                }
                var url = $('#qcDistrict').data('thana-url') + '/' + districtId;
                $thana.html('<option value="">{{ __('Loading...') }}</option>');
                $.getJSON(url, function(rows) {
                    var html = '<option value="">{{ __('Select Thana') }}</option>';
                    rows.forEach(function(t) {
                        html += '<option value="' + t.thana_name + '">' + t.thana_name +
                            '</option>';
                    });
                    $thana.html(html);
                }).fail(function() {
                    $thana.html('<option value="">{{ __('Could not load thanas') }}</option>');
                });
            });

            $('#qcSave').on('click', function() {
                var $btn = $(this);
                $('#qcError').addClass('d-none').text('');

                // Client-side guard for required fields — server still validates.
                if (!$('#qcName').val().trim() || !$('#qcPhone').val().trim()) {
                    $('#qcError').removeClass('d-none').text('{{ __('Name and phone are required.') }}');
                    return;
                }

                var formData = new FormData(document.getElementById('qcForm'));

                $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i>');
                $.ajax({
                        url: '{{ route('customers.quick-store') }}',
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    })
                    .done(function(res) {
                        var c = res.customer || {};
                        $('#customerSelect').append(
                            $('<option/>').val(c.id)
                            .text(c.name + (c.phone ? ' (' + c.phone + ')' : ''))
                            .attr('data-name', c.name)
                            .attr('data-phone', c.phone || '')
                            .attr('data-address', c.address || '')
                            .attr('data-billing', c.address || '')
                            .attr('data-shipping', c.shipping_address || '')
                        );
                        $('#customerSelect').val(c.id).trigger('change');
                        qcModal.hide();
                    })
                    .fail(function(xhr) {
                        var msg = '{{ __('Failed to create customer.') }}';
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            msg = Object.values(xhr.responseJSON.errors).flat().join(' ');
                        }
                        $('#qcError').removeClass('d-none').text(msg);
                    })
                    .always(function() {
                        $btn.prop('disabled', false).html(
                            '<i class="fa-solid fa-check me-1"></i> {{ __('Save & Select') }}');
                    });
            });

            // ── Barcode quick-add: focus search input ──
            $(document).on('click', '.bp-barcode-btn', function() {
                $('#productSearchInput').focus();
            });

            // ── Recalculate totals (per-line subtotals + sticky bar) ──
            function recalculate() {
                var totalQty = 0;
                var subtotal = 0;
                var totalItemDiscount = 0;

                $('#orderTable tbody tr').each(function() {
                    if ($(this).hasClass('empty-row')) return;
                    var $row = $(this);

                    // Combo rows contribute their package price as a fixed line
                    // total (no per-line discount); the components are priced on
                    // submit. Show the combo price as the row subtotal.
                    if ($row.attr('data-combo-row')) {
                        var cComps = [];
                        try {
                            cComps = JSON.parse($row.attr('data-combo-components') || '[]');
                        } catch (e) {}
                        var cPrice = parseFloat($row.attr('data-combo-price')) || 0;
                        totalQty += cComps.reduce(function(s, c) {
                            return s + (parseInt(c.qty, 10) || 1);
                        }, 0);
                        subtotal += cPrice;
                        $row.find('.sale-line-subtotal').text('{{ currency_symbol() }} ' + formatBDT(
                            cPrice));
                        return;
                    }

                    var qty = parseFloat($row.find('.sale-line-qty').val()) || 0;
                    var price = parseFloat($row.find('.sale-line-price').val()) || 0;
                    var gross = qty * price;

                    // Per-line discount: flat amount or percentage of gross.
                    var discType = $row.find('.sale-line-disc-type').val();
                    var discInput = parseFloat($row.find('.sale-line-disc-input').val()) || 0;
                    var rawDisc = discType === 'percentage' ? gross * (discInput / 100) : discInput;
                    rawDisc = Math.min(gross, Math.max(0, rawDisc));

                    // Match the storefront (Product::displayPrice): round the
                    // after-discount UNIT price UP to a whole BDT, so subtotals are
                    // clean integers (e.g. 850 − 18.24% = 694.96 → 695). Back out
                    // the actual discount from the rounded subtotal so the posted
                    // discount and the displayed subtotal always agree.
                    var lineSub, disc;
                    if (qty > 0) {
                        lineSub = ceilMoney((gross - rawDisc) / qty) * qty;
                        disc = gross - lineSub;
                    } else {
                        lineSub = 0;
                        disc = 0;
                    }

                    // Store the resolved flat discount so the submit handler can post it.
                    $row.find('.sale-line-disc-amount').val(window.numInput(disc));

                    totalQty += qty;
                    subtotal += gross;
                    totalItemDiscount += disc;

                    $row.find('.sale-line-subtotal').text('{{ currency_symbol() }} ' + formatBDT(
                        lineSub));
                });

                var afterItemDiscount = subtotal - totalItemDiscount;

                // Order-level discount
                var orderDiscountType = $('#orderDiscountType').val();
                var orderDiscountValue = parseFloat($('#orderDiscountValue').val()) || 0;
                var orderDiscount = orderDiscountType === 'percentage' ?
                    afterItemDiscount * (orderDiscountValue / 100) :
                    orderDiscountValue;
                orderDiscount = Math.min(afterItemDiscount, Math.max(0, orderDiscount));

                var shipping = parseFloat($('#shippingCharge').val()) || 0;

                var totalDiscount = totalItemDiscount + orderDiscount;
                var grandTotal = subtotal - totalDiscount + shipping;

                // Sticky bar
                $('#footerItems').text(totalQty);
                $('#footerTotal').text(formatBDT(subtotal));
                $('#footerOrderDiscount').text(formatBDT(totalDiscount));
                $('#footerShipping').text(formatBDT(shipping));
                $('#footerGrandTotal').text(formatBDT(grandTotal));

                saleRecalcPayments(grandTotal);
            }

            // ── Expand consolidated rows into flat per-variant items[] on submit ──
            // The widget shows one row per product; the sale backend wants one entry
            // per variant. Build hidden items[] inputs here so stock/totals are
            // computed per variant. The line discount goes on the first variant so
            // the line total is unchanged.
            function expandOrderItems() {
                $('#orderItemsExpanded').remove();
                var $hold = $('<div id="orderItemsExpanded"></div>');
                var k = 0;
                var esc = function(s) {
                    return $('<div/>').text(s == null ? '' : s).html();
                };

                function addHidden(idx, field, val) {
                    $hold.append('<input type="hidden" name="items[' + idx + '][' + field + ']" value="' + esc(
                        val) + '">');
                }

                $('#orderTable tbody tr').each(function() {
                    var $row = $(this);
                    if ($row.hasClass('empty-row')) return;

                    // Combo row → expand into one tagged item per component, with
                    // unit prices auto-allocated so the line totals sum to the
                    // combo price. The tiny rounding excess rides as a discount so
                    // each stored subtotal matches its allocated line total.
                    if ($row.attr('data-combo-row')) {
                        var comps = [];
                        try {
                            comps = JSON.parse($row.attr('data-combo-components') || '[]');
                        } catch (e) {
                            comps = [];
                        }
                        var cPrice = parseFloat($row.attr('data-combo-price')) || 0;
                        var alloc = allocateCombo(comps, cPrice);
                        comps.forEach(function(c, i) {
                            var qn = parseInt(c.qty, 10) || 1;
                            var disc = Math.round((alloc[i].unitPrice * qn - alloc[i].lineTotal) *
                                100) / 100;
                            addHidden(k, 'product_id', c.productId);
                            addHidden(k, 'variant_id', c.variantId || '');
                            addHidden(k, 'variant_label', c.variantLabel || '');
                            addHidden(k, 'quantity', qn);
                            addHidden(k, 'price', alloc[i].unitPrice);
                            addHidden(k, 'discount', disc > 0 ? disc : 0);
                            addHidden(k, 'combo_id', $row.attr('data-combo-id'));
                            addHidden(k, 'combo_group', $row.attr('data-combo-group') || '');
                            addHidden(k, 'combo_name', $row.attr('data-combo-name') || '');
                            addHidden(k, 'combo_price', cPrice);
                            k++;
                        });
                        return;
                    }

                    var pid = $row.attr('data-product-id');
                    if (!pid) return;
                    var lineDisc = parseFloat($row.find('.sale-line-disc-amount').val()) || 0;
                    var breakdown = [];
                    try {
                        breakdown = JSON.parse($row.attr('data-breakdown') || '[]');
                    } catch (e) {
                        breakdown = [];
                    }

                    if (breakdown.length) {
                        breakdown.forEach(function(b, i) {
                            addHidden(k, 'product_id', pid);
                            addHidden(k, 'variant_id', b.id || '');
                            addHidden(k, 'variant_label', b.name || '');
                            addHidden(k, 'quantity', b.qty || 0);
                            addHidden(k, 'price', b.price || 0);
                            addHidden(k, 'discount', i === 0 ? lineDisc : 0);
                            k++;
                        });
                    } else {
                        addHidden(k, 'product_id', pid);
                        addHidden(k, 'variant_id', $row.attr('data-variant-id') || '');
                        addHidden(k, 'variant_label', $row.attr('data-variant-label') || '');
                        addHidden(k, 'quantity', parseInt($row.find('.sale-line-qty').val(), 10) || 1);
                        addHidden(k, 'price', parseFloat($row.find('.sale-line-price').val()) || 0);
                        addHidden(k, 'discount', lineDisc);
                        if ($row.attr('data-combo-id')) {
                            addHidden(k, 'combo_id', $row.attr('data-combo-id'));
                            addHidden(k, 'combo_group', $row.attr('data-combo-group') || '');
                            addHidden(k, 'combo_name', $row.attr('data-combo-name') || '');
                            addHidden(k, 'combo_price', $row.attr('data-combo-price') || '');
                        }
                        k++;
                    }
                });

                $('#newInvoiceForm').append($hold);
                return k;
            }

            // ── Advance (split payment) logic ──
            var salePayIndex = 1;
            var payAccountOptions = $('#paymentAccountOptionsTemplate').html();

            function saleRecalcPayments(grandTotal) {
                var paid = 0;
                $('#salePaymentRows .sale-pay-amount').each(function() {
                    paid += parseFloat($(this).val()) || 0;
                });
                if (typeof grandTotal === 'undefined') {
                    grandTotal = parseFloat(($('#footerGrandTotal').text() || '0').replace(/,/g, '')) || 0;
                }
                var due = Math.max(0, grandTotal - paid);
                $('#salePaidTotal').text('{{ currency_symbol() }} ' + formatBDT(paid));
                $('#saleDueDisplay').text('{{ currency_symbol() }} ' + formatBDT(due));
            }

            $('#saleAddPayment').on('click', function() {
                var idx = salePayIndex++;
                var paid = 0;
                $('#salePaymentRows .sale-pay-amount').each(function() {
                    paid += parseFloat($(this).val()) || 0;
                });
                var grand = parseFloat(($('#footerGrandTotal').text() || '0').replace(/,/g, '')) || 0;
                var remaining = Math.max(0, grand - paid);

                var html = '<div class="bp-split-payment-row" data-index="' + idx + '">' +
                    '<div class="bp-pay-amount-col"><input type="number" class="bp-form-control sale-pay-amount" name="payments[' +
                    idx + '][amount]" placeholder="{{ __('Amount') }}" step="0.01" min="0" value="' + (
                        remaining > 0 ? remaining : '') + '"></div>' +
                    '<div class="bp-pay-method-col"><select class="bp-form-select sale-pay-account" name="payments[' +
                    idx + '][payment_account_id]">' + payAccountOptions + '</select></div>' +
                    '<div class="bp-pay-ref-col"><input type="text" class="bp-form-control" name="payments[' +
                    idx + '][reference]" placeholder="{{ __('Ref / TXN ID') }}"></div>' +
                    '<button type="button" class="remove-split" title="{{ __('Remove') }}"><i class="fa-solid fa-times"></i></button>' +
                    '</div>';
                $('#salePaymentRows').append(html);
                $('#salePaymentRows .remove-split').removeClass('d-none');
                saleRecalcPayments();
            });

            $(document).on('click', '#salePaymentRows .remove-split', function() {
                $(this).closest('.bp-split-payment-row').remove();
                var $rows = $('#salePaymentRows .bp-split-payment-row');
                if ($rows.length === 1) $rows.find('.remove-split').addClass('d-none');
                saleRecalcPayments();
            });

            $(document).on('input', '.sale-pay-amount', function() {
                saleRecalcPayments();
            });

            // ── Block submit when no items added (also tries to add a typed product) ──
            $('#newInvoiceForm').on('submit', function(e) {
                // If user typed in the product search but never clicked a suggestion,
                // try to auto-add the best match (simple products only) before counting.
                var typed = ($('#productSearchInput').val() || '').trim();
                if (typed) {
                    var match = productCatalog.find(function(p) {
                        return (p.name || '').toLowerCase() === typed.toLowerCase() ||
                            (p.sku || '').toLowerCase() === typed.toLowerCase();
                    });
                    if (match && !(match.type === 'variable' && Array.isArray(match.variants) && match
                            .variants
                            .length)) {
                        addLine({
                            productId: match.id,
                            productName: match.name,
                            image: match.image || '',
                            variantId: '',
                            variantLabel: '',
                            quantity: 1,
                            unitPrice: priceFor(match)
                        });
                    }
                    $('#productSearchInput').val('');
                }

                var hasItems = $('#orderTable tbody tr').not('.empty-row').length > 0;
                if (!hasItems) {
                    e.preventDefault();
                    alert(
                        '{{ __("Please add at least one product. Type a product name or code in the \"Select Product\" search box and click a suggestion to add it to the Order Items.") }}'
                    );
                    $('html, body').animate({
                        scrollTop: $('#productSearchInput').offset().top - 100
                    }, 200);
                    $('#productSearchInput').trigger('focus');
                    return false;
                }

                // Expand consolidated rows into the flat per-variant items[] the backend expects.
                expandOrderItems();
            });

            // ── District → Thana dependent dropdown ──
            var $districtSelect = $('#districtSelect');
            var $thanaSelect = $('#thanaSelect');
            var oldThanaId = '{{ old('thana_id') }}';

            function loadThanas(districtId, selectedThanaId) {
                if (!districtId) {
                    $thanaSelect.empty().append('<option value="">{{ __('Select thana...') }}</option>').trigger(
                        'change');
                    return;
                }
                var urlTpl = $districtSelect.data('thanas-url');
                var url = urlTpl.replace('__ID__', districtId);
                $thanaSelect.prop('disabled', true).empty().append(
                    '<option value="">{{ __('Loading...') }}</option>').trigger('change');
                $.getJSON(url)
                    .done(function(res) {
                        $thanaSelect.empty().append('<option value="">{{ __('Select thana...') }}</option>');
                        (res.data || []).forEach(function(t) {
                            var $opt = $('<option/>').val(t.id).text(t.thana_name);
                            if (selectedThanaId && String(selectedThanaId) === String(t.id)) $opt.prop(
                                'selected', true);
                            $thanaSelect.append($opt);
                        });
                        // Notify Select2 (and any other listeners) that the options have changed
                        // so the searchable dropdown redraws with the new list.
                        $thanaSelect.trigger('change');
                    })
                    .fail(function() {
                        $thanaSelect.empty().append(
                            '<option value="">{{ __('Failed to load thanas') }}</option>').trigger(
                            'change');
                    })
                    .always(function() {
                        $thanaSelect.prop('disabled', false);
                    });
            }

            $districtSelect.on('change', function() {
                loadThanas($(this).val(), null);
            });
            if ($districtSelect.val()) loadThanas($districtSelect.val(), oldThanaId);

            // Restore order rows after a validation error — group the flat
            // per-variant items back into consolidated rows (one per product).
            window.__salePrefilling = true;
            renderPrefillItems(window.salePrefillItems);
            window.__salePrefilling = false;

            function renderPrefillItems(items) {
                if (!Array.isArray(items) || !items.length) return;

                // Combo items first: regroup the flattened component items back
                // into a single combo row per combo_group.
                var comboItems = items.filter(function(i) {
                    return i.combo_group || i.combo_id;
                });
                var plainItems = items.filter(function(i) {
                    return !(i.combo_group || i.combo_id);
                });
                if (comboItems.length) {
                    var cgroups = {},
                        corder = [];
                    comboItems.forEach(function(it) {
                        var g = it.combo_group || ('cmb-' + it.combo_id);
                        if (!cgroups[g]) {
                            cgroups[g] = [];
                            corder.push(g);
                        }
                        cgroups[g].push(it);
                    });
                    corder.forEach(function(g) {
                        var rows = cgroups[g];
                        var f = rows[0];
                        // Price the combo row from what its components actually
                        // total (Σ price×qty − discount) so a re-render after a
                        // validation error preserves the submitted amount.
                        var comboPrice = rows.reduce(function(s, it) {
                            var qn = parseInt(it.quantity, 10) || 1;
                            return s + ((parseFloat(it.price) || 0) * qn - (parseFloat(it
                                .discount) || 0));
                        }, 0);
                        comboPrice = Math.round(comboPrice * 100) / 100;
                        // Snap away per-unit rounding drift from legacy allocations:
                        // if the total is a hair off a clean multiple of the stored
                        // combo price, use the clean multiple (e.g. 2000.02 → 2000).
                        var comboUnit = parseFloat(f.combo_price) || 0;
                        if (comboUnit > 0) {
                            var mult = Math.round(comboPrice / comboUnit);
                            if (mult >= 1 && Math.abs(comboUnit * mult - comboPrice) < 1) {
                                comboPrice = Math.round(comboUnit * mult * 100) / 100;
                            }
                        }
                        renderComboRow({
                            comboId: f.combo_id,
                            group: g,
                            name: f.combo_name || '{{ __('Combo') }}',
                            image: '',
                            price: comboPrice,
                            components: rows.map(function(it) {
                                return {
                                    productId: parseInt(it.id, 10),
                                    productName: it.name,
                                    image: it.image || '',
                                    variantId: it.variant_id || '',
                                    variantLabel: it.variant_label || '',
                                    qty: parseInt(it.quantity, 10) || 1
                                };
                            })
                        });
                    });
                }

                var groups = {},
                    order = [];
                plainItems.forEach(function(it) {
                    var key = it.id;
                    if (!groups[key]) {
                        groups[key] = [];
                        order.push(key);
                    }
                    groups[key].push(it);
                });
                order.forEach(function(key) {
                    var rows = groups[key];
                    var first = rows[0];
                    var isVariable = rows.some(function(i) {
                        return i.variant_id;
                    });
                    if (isVariable) {
                        var breakdown = rows.map(function(i) {
                            return {
                                id: parseInt(i.variant_id, 10) || '',
                                name: i.variant_label || '',
                                qty: parseInt(i.quantity, 10) || 0,
                                price: parseFloat(i.price) || 0
                            };
                        });
                        var totalQty = 0,
                            totalAmount = 0;
                        breakdown.forEach(function(b) {
                            totalQty += b.qty;
                            totalAmount += b.qty * b.price;
                        });
                        var firstPrice = breakdown[0].price;
                        var allSame = breakdown.every(function(b) {
                            return b.price === firstPrice;
                        });
                        var unitPrice = allSame ? firstPrice : Math.round((totalAmount / totalQty) * 100) /
                            100;
                        var lineDisc = rows.reduce(function(s, i) {
                            return s + (parseFloat(i.discount) || 0);
                        }, 0);
                        addLine({
                            productId: first.id,
                            productName: first.name,
                            image: first.image || '',
                            variantId: '',
                            variantLabel: 'Multiple',
                            quantity: totalQty,
                            unitPrice: unitPrice,
                            breakdown: breakdown,
                            discount: lineDisc
                        });
                    } else {
                        addLine({
                            productId: first.id,
                            productName: first.name,
                            image: first.image || '',
                            variantId: first.variant_id || '',
                            variantLabel: first.variant_label || '',
                            quantity: parseInt(first.quantity, 10) || 1,
                            unitPrice: parseFloat(first.price) || 0,
                            discount: parseFloat(first.discount) || 0
                        });
                    }
                });
            }

            // Initial calc
            recalculate();
        });
    </script>
@endpush
