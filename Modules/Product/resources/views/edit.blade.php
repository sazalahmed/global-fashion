@extends('core::layouts.master')

@section('title', __('Edit Product'))
@section('page-title', __('Edit Product'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('products.index') }}">Products</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Edit Product</span>
@endsection

@section('page-actions')
    <button class="bp-btn bp-btn-success" id="saveProductBtn" form="editProductForm" type="submit"><i
            class="fa-solid fa-check me-1"></i> Update Product</button>
    <a href="{{ route('products.index') }}" class="bp-btn bp-btn-danger"><i class="fa-solid fa-arrow-left me-1"></i>
        Cancel</a>
@endsection

{{-- TinyMCE loads its own skin CSS from the vendor folder; no external stylesheet needed. --}}

@section('content')

    @php
        $flattenCategoryTree = function ($items, $depth = 0) use (&$flattenCategoryTree) {
            $out = [];
            foreach ($items as $item) {
                $out[] = ['id' => $item->id, 'name' => $item->name, 'depth' => $depth];
                $children = $item->children ?? collect();
                if (count($children) > 0) {
                    $out = array_merge($out, $flattenCategoryTree($children, $depth + 1));
                }
            }
            return $out;
        };
        $flatCategories = $flattenCategoryTree($categories ?? collect());
        $existingCategoryIds = $product->categories->pluck('id')->all();
        if (empty($existingCategoryIds) && $product->category_id) {
            $existingCategoryIds = [$product->category_id];
        }
        $selectedCategoryIds = collect(old('categories', $existingCategoryIds))->map(fn($v) => (int) $v)->all();
    @endphp

    <form id="editProductForm" action="{{ route('products.update', $product) }}" method="POST"
        enctype="multipart/form-data" novalidate>
        @csrf
        @method('PUT')
        <div class="row g-4">

            <!-- ================================================================
                                                                                       LEFT COLUMN -- Main form sections
                                                                                       ================================================================ -->
            <div class="col-xl-8">

                <!-- General Information -->
                <div class="bp-card mb-4">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-circle-info me-2 text-primary"></i>General
                            Information</h5>
                    </div>
                    <div class="bp-card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="bp-form-label">Product Name *</label>
                                <input type="text" class="bp-form-control @error('name') is-invalid @enderror"
                                    id="productName" name="name" required placeholder="e.g., Samsung Galaxy A55 5G"
                                    value="{{ old('name', $product->name) }}">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="bp-form-label">Model</label>
                                <input type="text" class="bp-form-control @error('model') is-invalid @enderror"
                                    id="productModel" name="model" placeholder="e.g., GCS-04"
                                    value="{{ old('model', $product->model) }}">
                                @error('model')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="bp-form-label">SKU *</label>
                                <div class="input-group">
                                    <input type="text" class="bp-form-control @error('sku') is-invalid @enderror"
                                        id="productSku" name="sku" placeholder="e.g., SAM-A55" required
                                        value="{{ old('sku', $product->sku) }}">
                                    <button class="bp-btn bp-btn-outline" type="button" title="Auto Generate SKU"
                                        id="autoSkuBtn">
                                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                                    </button>
                                </div>
                                @error('sku')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="bp-form-label">Barcode</label>
                                <div class="input-group">
                                    <input type="text" class="bp-form-control @error('barcode') is-invalid @enderror"
                                        id="productBarcode" name="barcode" placeholder="EAN-13 / Code 128"
                                        value="{{ old('barcode', $product->barcode) }}">
                                    <button class="bp-btn bp-btn-outline" type="button" title="Generate Barcode"
                                        id="genBarcodeBtn">
                                        <i class="fa-solid fa-barcode"></i>
                                    </button>
                                </div>
                                @error('barcode')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="bp-form-label">Display Position</label>
                                <input type="number" class="bp-form-control @error('position') is-invalid @enderror"
                                    name="position" placeholder="e.g., 1 (lower = first)" min="0"
                                    value="{{ old('position', $product->position ?? 0) }}">
                                @error('position')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="bp-form-label">Brand</label>
                                <div class="input-group">
                                    <select class="bp-form-select select2-search @error('brand_id') is-invalid @enderror"
                                        id="productBrand" name="brand_id">
                                        <option value="">Select Brand</option>
                                        @foreach ($brands ?? [] as $brand)
                                            <option value="{{ $brand->id }}"
                                                {{ old('brand_id', $product->brand_id) == $brand->id ? 'selected' : '' }}>
                                                {{ $brand->name }}</option>
                                        @endforeach
                                    </select>
                                    <button class="bp-btn bp-btn-outline" type="button" title="Add new brand"
                                        id="addBrandBtn"><i class="fa-solid fa-plus"></i></button>
                                </div>
                                @error('brand_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="bp-form-label">Purchase Unit</label>
                                <div class="input-group">
                                    <select
                                        class="bp-form-select select2-search @error('purchase_unit_id') is-invalid @enderror"
                                        id="purchaseUnit" name="purchase_unit_id">
                                        <option value="">Select Unit</option>
                                        @foreach ($units ?? [] as $unit)
                                            <option value="{{ $unit->id }}"
                                                {{ old('purchase_unit_id', $product->purchase_unit_id ?? $product->unit_id) == $unit->id ? 'selected' : '' }}>
                                                {{ $unit->name }} ({{ $unit->short_name }})</option>
                                        @endforeach
                                    </select>
                                    <button class="bp-btn bp-btn-outline" type="button" title="Add new unit"
                                        id="addPurchaseUnitBtn"><i class="fa-solid fa-plus"></i></button>
                                </div>
                                @error('purchase_unit_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <label class="bp-form-label">Sales Unit</label>
                                <div class="input-group">
                                    <select
                                        class="bp-form-select select2-search @error('sale_unit_id') is-invalid @enderror"
                                        id="saleUnit" name="sale_unit_id">
                                        <option value="">Select Unit</option>
                                        @foreach ($units ?? [] as $unit)
                                            <option value="{{ $unit->id }}"
                                                {{ old('sale_unit_id', $product->sale_unit_id ?? $product->unit_id) == $unit->id ? 'selected' : '' }}>
                                                {{ $unit->name }} ({{ $unit->short_name }})</option>
                                        @endforeach
                                    </select>
                                    <button class="bp-btn bp-btn-outline" type="button" title="Add new unit"
                                        id="addSaleUnitBtn"><i class="fa-solid fa-plus"></i></button>
                                </div>
                                @error('sale_unit_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <label class="bp-form-label">Warranty</label>
                                <select class="bp-form-select w-100 @error('warranty') is-invalid @enderror"
                                    id="productWarranty" name="warranty">
                                    <option value=""
                                        {{ old('warranty', $product->warranty) == '' ? 'selected' : '' }}>No Warranty
                                    </option>
                                    <option value="3_months"
                                        {{ old('warranty', $product->warranty) == '3_months' ? 'selected' : '' }}>3 Months
                                    </option>
                                    <option value="6_months"
                                        {{ old('warranty', $product->warranty) == '6_months' ? 'selected' : '' }}>6 Months
                                    </option>
                                    <option value="1_year"
                                        {{ old('warranty', $product->warranty) == '1_year' ? 'selected' : '' }}>1 Year
                                    </option>
                                    <option value="2_years"
                                        {{ old('warranty', $product->warranty) == '2_years' ? 'selected' : '' }}>2 Years
                                    </option>
                                    <option value="3_years"
                                        {{ old('warranty', $product->warranty) == '3_years' ? 'selected' : '' }}>3 Years
                                    </option>
                                </select>
                                @error('warranty')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            @unless ($product->isVariable())
                                <div class="col-md-6 col-lg-3" id="currentStockField">
                                    <label class="bp-form-label">Stock Quantity</label>
                                    <input type="number"
                                        class="bp-form-control @error('stock.0.quantity') is-invalid @enderror"
                                        id="currentStock" name="stock[0][quantity]" placeholder="e.g., 100" min="0"
                                        value="{{ old('stock.0.quantity', $product->total_stock) }}">
                                    @error('stock.0.quantity')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            @endunless
                            <div class="col-md-6 col-lg-3">
                                <label class="bp-form-label">Min Stock Alert</label>
                                <input type="number"
                                    class="bp-form-control @error('min_stock_alert') is-invalid @enderror" id="minStock"
                                    name="min_stock_alert" placeholder="e.g., 10" min="0"
                                    value="{{ old('min_stock_alert', $product->min_stock_alert) }}">
                                @unless ($product->isVariable())
                                    <input type="hidden" name="stock[0][min_alert]" id="currentStockMinAlert"
                                        value="{{ old('min_stock_alert', $product->min_stock_alert) }}">
                                @endunless
                                @error('min_stock_alert')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Product Type -->
                <div class="bp-card mb-4" id="productTypeCard">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-tag me-2 text-secondary"></i>Product Type</h5>
                    </div>
                    <div class="bp-card-body">
                        <input type="hidden" name="product_type" id="productTypeInput"
                            value="{{ old('product_type', $product->product_type) }}">
                        <div class="bp-product-type-toggle">
                            <button type="button"
                                class="bp-type-btn {{ old('product_type', $product->product_type) === 'simple' ? 'active' : '' }}"
                                id="typeSimple">
                                <div class="bp-type-btn-icon"><i class="fa-solid fa-box"></i></div>
                                <div class="bp-type-btn-text">
                                    <div class="fw-700 fs-14">Simple Product</div>
                                    <div class="fs-12 text-muted">Single price & stock -- no variants needed</div>
                                </div>
                                <div class="bp-type-btn-check"><i class="fa-solid fa-circle-check"></i></div>
                            </button>
                            <button type="button"
                                class="bp-type-btn {{ old('product_type', $product->product_type) === 'variable' ? 'active' : '' }}"
                                id="typeVariable">
                                <div class="bp-type-btn-icon"><i class="fa-solid fa-layer-group"></i></div>
                                <div class="bp-type-btn-text">
                                    <div class="fw-700 fs-14">Variable Product</div>
                                    <div class="fs-12 text-muted">Multiple variants by Color, Size, Storage, etc.</div>
                                </div>
                                <div class="bp-type-btn-check"><i class="fa-solid fa-circle-check"></i></div>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Variant Attributes (Variable Product only) -->
                <div class="bp-card mb-4" id="attrBuilderCard" hidden>
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-layer-group me-2 text-warning"></i>Variant
                            Attributes</h5>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="fs-12 text-muted" id="comboCountLabel"></span>
                            <button type="button" class="bp-btn bp-btn-sm bp-btn-primary" id="loadVariantAttrsBtn">
                                <i class="fa-solid fa-plus me-1"></i> Add Attribute
                            </button>
                        </div>
                    </div>
                    <div class="bp-card-body">
                        <div id="variantAttrsContainer">
                            <div class="text-center py-3 text-muted" id="variantAttrsEmpty">
                                <i class="fa-solid fa-sliders fa-2x mb-2 d-block"></i>
                                <p class="fs-13 mb-1">Click "Add Attribute" to select variant attributes (Color, Size,
                                    etc.)</p>
                                <small class="text-muted">The variant matrix builds automatically as you add
                                    attributes.</small>
                            </div>
                        </div>
                        <div id="variantAttrPicker" class="d-none mt-3">
                            <label class="bp-form-label">Select Attribute</label>
                            <select class="bp-form-select w-100 mb-2" id="variantAttrSelect">
                                <option value="">Loading...</option>
                            </select>
                            <div id="variantValuesContainer" class="d-none">
                                <label class="bp-form-label">Select Values</label>
                                <div id="variantValuesList" class="d-flex flex-wrap gap-2 mb-2"></div>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="bp-btn bp-btn-sm bp-btn-primary"
                                    id="addVariantAttrConfirm">
                                    <i class="fa-solid fa-check me-1"></i> Add
                                </button>
                                <button type="button" class="bp-btn bp-btn-sm bp-btn-danger" id="cancelVariantAttr"><i
                                        class="fa-solid fa-xmark me-1"></i>Cancel</button>
                            </div>
                        </div>
                        <div id="selectedVariantAttrs"></div>
                    </div>
                </div>

                <!-- Variation Matrix (Variable Product only) -->
                <div class="bp-card mb-4" id="variationMatrixCard" hidden>
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-table-cells me-2 text-primary"></i>Variants <span
                                class="fs-12 text-muted fw-400 ms-1">-- SKU, Pricing & Status</span></h5>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="bp-badge bp-badge-primary" id="variantCountBadge">0 Variants</span>
                        </div>
                    </div>
                    <div class="bp-card-body p-0">
                        <div class="bp-table-wrapper">
                            <table class="bp-table bp-variation-table" id="variationMatrixTable">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Variant</th>
                                        <th>SKU</th>
                                        <th>Cost ({{ currency_symbol() }})</th>
                                        <th>Sell ({{ currency_symbol() }})</th>
                                        <th class="text-center" style="width:110px;">Stock</th>
                                        <th class="text-center" title="Default variant shown first on the storefront">
                                            Default</th>
                                        <th class="text-center">Active</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="variationMatrixBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Per-product Size Chart Override (Variable Product, only when Size has chart rows) -->
                <div class="bp-card mb-4" id="sizeOverrideCard" hidden>
                    <div class="bp-card-header d-flex align-items-center justify-content-between">
                        <h5 class="bp-card-title mb-0"><i class="fa-solid fa-ruler-combined me-2 text-info"></i>Size Chart
                            Override</h5>
                        <button type="button" class="bp-btn bp-btn-sm bp-btn-outline" id="sizeOverrideToggle"
                            aria-expanded="true" aria-controls="sizeOverrideBody">
                            <i class="fa-solid fa-chevron-up me-1"></i> Collapse
                        </button>
                    </div>
                    <div id="sizeOverrideBody">
                        <p class="fs-12 text-muted m-3">
                            Defaults come from the global <strong>Size</strong> chart. Edit any cell to override for this
                            product only — clear it to fall back to the default.
                        </p>
                        <div class="bp-table-wrapper">
                            <table class="bp-table bp-size-override-table" id="sizeOverrideTable">
                                <thead>
                                    <tr>
                                        <th class="bp-size-col">Size</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                        <div id="sizeOverrideEmpty" class="text-muted fs-12">Select <strong>Size</strong> values above to
                            populate this table.</div>
                    </div>
                </div>

                <!-- Pricing -->
                <div class="bp-card mb-4">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i
                                class="fa-solid fa-bangladeshi-taka-sign me-2 text-success"></i>Pricing</h5>
                        <span class="bp-badge bp-badge-warning" id="marginBadge">Margin: --</span>
                    </div>
                    <div class="bp-card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="bp-form-label">Cost / Purchase Price ({{ currency_symbol() }}) *</label>
                                <div class="input-group">
                                    <span class="bp-input-addon">{{ currency_symbol() }}</span>
                                    <input type="number"
                                        class="bp-form-control @error('cost_price') is-invalid @enderror" id="costPrice"
                                        name="cost_price" required placeholder="0.00" min="0" step="0.01"
                                        value="{{ old('cost_price', num_input($product->cost_price)) }}">
                                </div>
                                @error('cost_price')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="bp-form-label">Selling Price ({{ currency_symbol() }}) *</label>
                                <div class="input-group">
                                    <span class="bp-input-addon">{{ currency_symbol() }}</span>
                                    <input type="number"
                                        class="bp-form-control @error('sell_price') is-invalid @enderror" id="sellPrice"
                                        name="sell_price" required placeholder="0.00" min="0" step="0.01"
                                        value="{{ old('sell_price', num_input($product->sell_price)) }}">
                                </div>
                                @error('sell_price')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="bp-form-label">Wholesale Price ({{ currency_symbol() }})</label>
                                <div class="input-group">
                                    <span class="bp-input-addon">{{ currency_symbol() }}</span>
                                    <input type="number"
                                        class="bp-form-control @error('wholesale_price') is-invalid @enderror"
                                        id="wholesalePrice" name="wholesale_price" placeholder="0.00" min="0"
                                        step="0.01"
                                        value="{{ old('wholesale_price', num_input($product->wholesale_price)) }}">
                                </div>
                                @error('wholesale_price')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="bp-form-label">Reseller Price ({{ currency_symbol() }})</label>
                                <div class="input-group">
                                    <span class="bp-input-addon">{{ currency_symbol() }}</span>
                                    <input type="number"
                                        class="bp-form-control @error('resell_price') is-invalid @enderror"
                                        id="resellPrice" name="resell_price" placeholder="0.00" min="0"
                                        step="0.01"
                                        value="{{ old('resell_price', num_input($product->resell_price)) }}">
                                </div>
                                @error('resell_price')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Calculated Profit -->
                            <div class="col-12">
                                <div class="bp-profit-preview" id="profitPreview" hidden>
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <div class="bp-price-card">
                                                <div class="bp-price-card-label">Cost Price</div>
                                                <div class="bp-price-card-value text-danger" id="previewCost">--</div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="bp-price-card bp-price-card-sell">
                                                <div class="bp-price-card-label">Sell Price</div>
                                                <div class="bp-price-card-value text-primary" id="previewSell">--</div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="bp-price-card bp-price-card-profit">
                                                <div class="bp-price-card-label">Profit</div>
                                                <div class="bp-price-card-value text-success" id="previewProfit">--</div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="bp-price-card bp-price-card-profit">
                                                <div class="bp-price-card-label">Margin</div>
                                                <div class="bp-price-card-value text-success" id="previewMargin">--</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="bp-form-label">Discount Type</label>
                                <select class="bp-form-select w-100 @error('discount_type') is-invalid @enderror"
                                    id="discountType" name="discount_type">
                                    <option value="fixed"
                                        {{ old('discount_type', $product->discount_type ?: 'fixed') == 'fixed' ? 'selected' : '' }}>
                                        Fixed Amount ({{ currency_symbol() }})</option>
                                    <option value="percentage"
                                        {{ old('discount_type', $product->discount_type) == 'percentage' ? 'selected' : '' }}>
                                        Percentage (%)</option>
                                </select>
                                @error('discount_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            @php $discType = old('discount_type', $product->discount_type ?: 'fixed'); @endphp
                            <div class="col-md-4" id="discountAmountField">
                                <label class="bp-form-label"
                                    id="discountAmountLabel">{{ $discType == 'percentage' ? __('Discount (%)') : __('Discount Amount') . ' (' . currency_symbol() . ')' }}</label>
                                <div class="input-group">
                                    <span class="bp-input-addon"
                                        id="discountAddon">{{ $discType == 'percentage' ? '%' : currency_symbol() }}</span>
                                    <input type="number"
                                        class="bp-form-control @error('discount_value') is-invalid @enderror"
                                        id="discountAmount" name="discount_value" placeholder="0" min="0"
                                        step="0.01"
                                        value="{{ old('discount_value', num_input($product->discount_value)) }}">
                                </div>
                                @error('discount_value')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            {{-- Final price after the discount is applied (Bug_83). --}}
                            <div class="col-md-4" id="discountedPriceField">
                                <label class="bp-form-label">{{ __('Price After Discount') }}
                                    ({{ currency_symbol() }})</label>
                                <div class="bp-price-card p-1 text-start ps-3 bp-price-card-sell">
                                    <div class="bp-price-card-value text-success" id="discountedPrice">--</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SEO / eCommerce -->
                <div class="bp-card mb-4">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-globe me-2 text-secondary"></i>eCommerce & SEO
                        </h5>
                    </div>
                    <div class="bp-card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="bp-form-label">Long Description <span class="text-muted fw-400">(shown on
                                        product page)</span></label>
                                <textarea class="bp-form-control bp-richtext @error('long_description') is-invalid @enderror" name="long_description"
                                    id="longDescription">{{ old('long_description', $product->long_description) }}</textarea>
                                @error('long_description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="bp-form-label">SEO Title</label>
                                <input type="text" class="bp-form-control @error('seo_title') is-invalid @enderror"
                                    name="seo_title" data-seo-title
                                    placeholder="e.g., Buy Premium Cotton Casual Shirt Online in Bangladesh"
                                    value="{{ old('seo_title', $product->seo_title) }}">
                                <small class="fs-11 text-muted" data-seo-title-count></small>
                                @error('seo_title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="bp-form-label">SEO Meta Description</label>
                                <input type="text"
                                    class="bp-form-control @error('seo_description') is-invalid @enderror"
                                    name="seo_description" data-seo-desc
                                    placeholder="Short description for search engines (max 160 chars)"
                                    value="{{ old('seo_description', $product->seo_description) }}">
                                <small class="fs-11 text-muted" data-seo-desc-count></small>
                                @error('seo_description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <div class="bp-seo-snippet" data-seo-preview>
                                    <div class="seo-pv-title"></div>
                                    <div class="seo-pv-url">{{ url('/') }}/…</div>
                                    <div class="seo-pv-desc"></div>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="bp-form-label">Tags <span class="text-muted fw-400">(comma
                                        separated)</span></label>
                                <input type="text" class="bp-form-control @error('tags') is-invalid @enderror"
                                    name="tags" data-tagify placeholder="e.g., cotton shirt, casual, full sleeve"
                                    value="{{ old('tags', $product->tags->pluck('name')->implode(', ')) }}">
                                @error('tags')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- ================================================================
                                                                                       RIGHT COLUMN -- Images, Status, Quick info
                                                                                       ================================================================ -->
            <div class="col-xl-4">

                <!-- Thumbnail Image -->
                <div class="bp-card mb-4">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-image me-2 text-primary"></i>Thumbnail Image</h5>
                    </div>
                    <div class="bp-card-body">
                        <x-core::image-upload name="thumbnail" id="thumbnailInput" label="Thumbnail"
                            hint="JPG, PNG, WebP — max 2MB" help="Main display image for the product." :current="$product->thumbnail ? upload_url($product->thumbnail) : null"
                            remove-name="remove_thumbnail" />
                    </div>
                </div>

                <!-- Gallery Images -->
                <div class="bp-card mb-4">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-images me-2 text-info"></i>Gallery Images</h5>
                    </div>
                    <div class="bp-card-body">
                        <!-- Upload zone holds the gallery thumbnails inside it (like the
                                                                                             thumbnail field). Existing + new images share one reorderable
                                                                                             grid; order = storefront product-gallery order. -->
                        <div class="bp-image-dropzone bp-gallery-dropzone {{ $product->images->count() ? 'has-images' : '' }}"
                            id="imageDropzone">
                            <div class="bp-gallery-grid" id="galleryGrid">
                                @foreach ($product->images->sortBy('sort_order') as $image)
                                    <div class="bp-gallery-item" data-existing-id="{{ $image->id }}">
                                        <img src="{{ upload_url($image->image_path) }}" alt="{{ $product->name }}">
                                        <button type="button" class="bp-gallery-remove" title="Remove image"><i
                                                class="fa-solid fa-xmark"></i></button>
                                    </div>
                                @endforeach
                            </div>
                            <div class="bp-gallery-prompt">
                                <i
                                    class="fa-solid fa-cloud-arrow-up fa-2x text-muted mb-2 d-block bp-gallery-prompt-icon"></i>
                                <div class="fw-700 fs-13 mb-1 bp-gallery-prompt-title">Drag &amp; drop or click to upload
                                    images</div>
                                <div class="fs-11 text-muted mb-3 bp-gallery-prompt-hint">JPG, PNG, WebP -- max 2MB each
                                </div>
                                <span class="bp-btn bp-btn-sm bp-btn-primary bp-btn-upload bp-gallery-prompt-btn"><i
                                        class="fa-solid fa-upload me-1"></i> Browse Files</span>
                                <input type="file" accept="image/*" multiple name="images[]" id="imageInput"
                                    class="d-none">
                            </div>
                        </div>
                        <small class="text-muted fs-11 mt-2 d-block" id="galleryHint">
                            <i class="fa-solid fa-circle-info me-1"></i> Drag to reorder &mdash; the first image shows
                            first in the product gallery.
                        </small>

                        <!-- Submit-time hidden inputs (image_order[] + remove_images[]) -->
                        <div id="galleryHidden" class="d-none"></div>
                    </div>
                </div>

                <!-- Categories (multi-select sidebar) -->
                <div class="bp-card mb-4" id="categoriesCard">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-tags me-2 text-primary"></i>Categories <span
                                class="text-danger">*</span></h5>
                    </div>
                    <div class="bp-card-body">
                        <div class="bp-cat-search mb-2">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" class="bp-form-control" id="categorySearch"
                                placeholder="Search categories...">
                        </div>
                        <div class="bp-cat-list" id="categoryList">
                            @foreach ($flatCategories as $cat)
                                <label class="bp-cat-item bp-cat-depth-{{ $cat['depth'] }}"
                                    data-depth="{{ $cat['depth'] }}"
                                    data-name="{{ \Illuminate\Support\Str::lower($cat['name']) }}"
                                    style="--depth: {{ $cat['depth'] }};">
                                    <input type="checkbox" name="categories[]" value="{{ $cat['id'] }}"
                                        class="cat-checkbox"
                                        {{ in_array($cat['id'], $selectedCategoryIds, true) ? 'checked' : '' }}>
                                    <span class="bp-check-box"></span>
                                    <span class="bp-cat-label">{{ $cat['name'] }}</span>
                                </label>
                            @endforeach
                            <div class="bp-cat-empty text-center text-muted fs-12 py-3 d-none" id="categoryEmpty">No
                                matching categories.</div>
                        </div>
                        @error('categories')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        @error('category_id')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <div class="fs-11 text-muted mt-2"><i class="fa-solid fa-circle-info me-1"></i> The most specific
                            (deepest) selected category becomes the primary.</div>
                    </div>
                </div>

                <!-- Product Status -->
                <div class="bp-card mb-4">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-toggle-on me-2 text-success"></i>Product Status
                        </h5>
                    </div>
                    <div class="bp-card-body d-flex flex-column gap-3">
                        <div class="bp-toggle-card">
                            <div class="bp-toggle-card-icon icon-success"><i class="fa-solid fa-check-circle"></i></div>
                            <div class="bp-toggle-card-body">
                                <div class="fw-700 fs-13">Product Active</div>
                                <div class="fs-11 text-muted">Visible in POS & sales</div>
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" id="productActiveToggle"
                                    name="is_active" value="1"
                                    {{ old('is_active', $product->is_active) ? 'checked' : '' }}>
                            </div>
                        </div>
                        <input type="hidden" name="ecom_visible" value="1">
                        <input type="hidden" name="show_in_pos" value="1">
                        <input type="hidden" name="track_stock" value="1">
                        <div class="bp-toggle-card">
                            <div class="bp-toggle-card-icon icon-danger"><i class="fa-solid fa-ban"></i></div>
                            <div class="bp-toggle-card-body">
                                <div class="fw-700 fs-13">Allow Negative Stock</div>
                                <div class="fs-11 text-muted">Sell even when stock is 0</div>
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" name="allow_negative_stock"
                                    value="1"
                                    {{ old('allow_negative_stock', $product->allow_negative_stock) ? 'checked' : '' }}>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Summary -->
                <div class="bp-card mb-4" id="quickSummaryCard">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-receipt me-2 text-primary"></i>Quick Summary</h5>
                    </div>
                    <div class="bp-card-body">
                        <div class="bp-info-row">
                            <div class="bp-info-label bp-info-label-fixed">Name</div>
                            <div class="bp-info-value text-muted" id="sumName">--</div>
                        </div>
                        <div class="bp-info-row">
                            <div class="bp-info-label bp-info-label-fixed">SKU</div>
                            <div class="bp-info-value text-muted" id="sumSku">--</div>
                        </div>
                        <div class="bp-info-row">
                            <div class="bp-info-label bp-info-label-fixed">Category</div>
                            <div class="bp-info-value text-muted" id="sumCategory">--</div>
                        </div>
                        <div class="bp-info-row">
                            <div class="bp-info-label bp-info-label-fixed">Cost Price</div>
                            <div class="bp-info-value text-muted" id="sumCost">--</div>
                        </div>
                        <div class="bp-info-row">
                            <div class="bp-info-label bp-info-label-fixed">Sell Price</div>
                            <div class="bp-info-value text-muted" id="sumSell">--</div>
                        </div>
                        <div class="bp-info-row bp-info-row-last">
                            <div class="bp-info-label bp-info-label-fixed">Profit</div>
                            <div class="bp-info-value text-success fw-700" id="sumProfit">--</div>
                        </div>
                    </div>
                </div>

                <!-- Barcode Preview -->
                <div class="bp-card mb-4">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-barcode me-2 text-muted"></i>Barcode Label</h5>
                    </div>
                    <div class="bp-card-body text-center">
                        <div class="bp-barcode-preview" id="barcodePreview">
                            <svg id="barcodeSvg" class="d-none"></svg>
                            <i class="fa-solid fa-barcode fa-4x text-muted mb-2 d-block" id="barcodePlaceholderIcon"></i>
                            <div class="fs-12 text-muted" id="barcodeText">{{ $product->barcode ?? __('No barcode') }}
                            </div>
                        </div>
                        <button class="bp-btn bp-btn-sm bp-btn-outline mt-3 w-100" type="button" id="printLabelBtn">
                            <i class="fa-solid fa-print me-1"></i> Print Label
                        </button>
                    </div>
                </div>

                <!-- Delete & Save Actions -->
                <div class="bp-card create_pro_buttons">
                    <div class="bp-card-body d-flex flex-wrap gap-2">
                        <button class="bp-btn bp-btn-success w-100 justify-content-center bp-btn-lg save" type="submit"
                            id="saveProductBtn2">
                            <i class="fa-solid fa-check me-2"></i> Update Product
                        </button>
                        <button class="bp-btn bp-btn-warning justify-content-center draft" type="submit" name="status"
                            value="draft" id="saveDraftBtn2">
                            <i class="fa-solid fa-floppy-disk me-2"></i> Save as Draft
                        </button>
                        <a href="{{ route('products.index') }}"
                            class="bp-btn justify-content-center bp-btn-danger cancel">
                            <i class="fa-solid fa-xmark me-2"></i> Discard & Cancel
                        </a>
                        <hr class="my-1">
                        @bpCan('products.delete')
                            <button type="button" class="bp-btn bp-btn-danger w-100 justify-content-center"
                                id="deleteProductBtn">
                                <i class="fa-solid fa-trash me-2"></i> Delete Product
                            </button>
                        @endbpCan
                    </div>
                </div>

            </div>
        </div>
    </form>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteProductModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa-solid fa-triangle-exclamation text-danger me-2"></i>Delete
                        Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete <strong>{{ $product->name }}</strong>? This action cannot be undone.
                    </p>
                    <p class="text-muted fs-12">All stock records, sales history references, and variant data will be
                        permanently removed.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="bp-btn bp-btn-danger" data-bs-dismiss="modal"><i
                            class="fa-solid fa-xmark me-1"></i>Cancel</button>
                    <form action="{{ route('products.destroy', $product) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="bp-btn bp-btn-danger"><i class="fa-solid fa-trash me-1"></i> Yes,
                            Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Add Brand Modal -->
    <div class="modal fade" id="quickBrandModal" tabindex="-1">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title fw-800"><i class="fa-solid fa-tag me-2"></i>New Brand</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="bp-form-label">Brand Name *</label>
                    <input type="text" class="bp-form-control" id="quickBrandName" placeholder="e.g., Samsung"
                        maxlength="255">
                    <div class="invalid-feedback" id="quickBrandError"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="bp-btn bp-btn-danger bp-btn-sm" data-bs-dismiss="modal"><i
                            class="fa-solid fa-xmark me-1"></i>Cancel</button>
                    <button type="button" class="bp-btn bp-btn-success bp-btn-sm" id="quickBrandSave"><i
                            class="fa-solid fa-check me-1"></i>Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Add Unit Modal -->
    <div class="modal fade" id="quickUnitModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title fw-800"><i class="fa-solid fa-ruler me-2"></i>New Unit</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-7">
                            <label class="bp-form-label">Unit Name *</label>
                            <input type="text" class="bp-form-control" id="quickUnitName"
                                placeholder="e.g., Kilogram" maxlength="100">
                        </div>
                        <div class="col-5">
                            <label class="bp-form-label">Short Name *</label>
                            <input type="text" class="bp-form-control" id="quickUnitShortName" placeholder="e.g., kg"
                                maxlength="20">
                        </div>
                        <div class="col-12">
                            <label class="bp-form-label">Unit Type</label>
                            <div class="d-flex gap-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="quick_unit_type"
                                        id="quickUnitTypeBase" value="base" checked>
                                    <label class="form-check-label fw-600 fs-13" for="quickUnitTypeBase">Base Unit</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="quick_unit_type"
                                        id="quickUnitTypeSub" value="sub">
                                    <label class="form-check-label fw-600 fs-13" for="quickUnitTypeSub">Sub Unit</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 d-none" id="quickSubUnitFields">
                            <div class="row g-3">
                                <div class="col-6">
                                    <label class="bp-form-label">Base Unit *</label>
                                    <select class="bp-form-select w-100" id="quickBaseUnitSelect">
                                        <option value="">Select Base Unit</option>
                                        @foreach ($units ?? [] as $u)
                                            @if ($u->base_unit_id === null)
                                                <option value="{{ $u->id }}" data-short="{{ $u->short_name }}">
                                                    {{ $u->name }} ({{ $u->short_name }})</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="bp-form-label">Conversion</label>
                                    <div class="d-flex gap-2 align-items-center">
                                        <select class="bp-form-select" id="quickConvOperator">
                                            <option value="divide">÷</option>
                                            <option value="multiply">×</option>
                                        </select>
                                        <input type="number" class="bp-form-control" id="quickConvValue" min="1"
                                            step="any" placeholder="e.g., 1000">
                                    </div>
                                </div>
                                <div class="col-12" id="quickConversionPreview" hidden>
                                    <div class="fs-12 p-2 rounded bp-conversion-preview">
                                        <i class="fa-solid fa-check-circle me-1 text-success"></i>
                                        <span id="quickConversionText"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="quickAllowDecimal" value="1">
                                <label class="form-check-label fw-600 fs-13" for="quickAllowDecimal">Allow Decimal
                                    Quantities</label>
                                <div class="fs-11 text-muted">Enable for units like Kg, Litre, Meter</div>
                            </div>
                        </div>
                    </div>
                    <div class="invalid-feedback d-block mt-2" id="quickUnitError"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="bp-btn bp-btn-danger bp-btn-sm" data-bs-dismiss="modal"><i
                            class="fa-solid fa-xmark me-1"></i>Cancel</button>
                    <button type="button" class="bp-btn bp-btn-success bp-btn-sm" id="quickUnitSave"><i
                            class="fa-solid fa-check me-1"></i>Save</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="{{ asset('website/assets/js/seo-snippet.js') }}"></script>
    <script src="{{ asset('vendor/jsbarcode/jsbarcode.min.js') }}"></script>
    <script src="{{ asset('vendor/sortablejs/Sortable.min.js') }}"></script>
    <script>
        'use strict';

        (function() {

            // ================================================================
            //  STATE
            // ================================================================
            var productType = '{{ old('product_type', $product->product_type) }}';
            var productId = {{ $product->id }};
            var existingVariants = @json($variantMatrix ?? []);
            // Drop the trailing ".00" on whole numbers so price inputs show
            // integers (280.00 -> 280) to match the main pricing fields (Bug_84).
            function trimNum(n) {
                if (n === null || n === '' || typeof n === 'undefined') return '';
                var f = parseFloat(n);
                return isNaN(f) ? '' : String(f);
            }
            existingVariants.forEach(function(v) {
                v.cost_price = trimNum(v.cost_price);
                v.sell_price = trimNum(v.sell_price);
                if ('wholesale_price' in v) v.wholesale_price = trimNum(v.wholesale_price);
            });
            var allAttributes = [];
            var selectedAttributes = {};
            var variantSystemLoaded = false; // attribute GET has completed
            var variantSystemLoading = false; // attribute GET is in flight
            var variantSystemQueue = []; // callbacks waiting on the GET

            // Keep the hidden stock min-alert in sync with the Min Stock Alert input
            // (simple products only).
            var minStockEl = document.getElementById('minStock');
            var currentStockMinAlertEl = document.getElementById('currentStockMinAlert');
            if (minStockEl && currentStockMinAlertEl) {
                minStockEl.addEventListener('input', function() {
                    currentStockMinAlertEl.value = minStockEl.value;
                });
            }

            // Per-product Size Chart override state.
            var sizeChart = {
                attributeId: null,
                rows: [],
                defaults: {},
                loaded: false
            };
            // Existing overrides shaped as { value_id: { row_id: "string" } }
            var existingOverrides = (function() {
                var out = {};
                @foreach ($product->sizeChartOverrides ?? [] as $o)
                    out[{{ $o->variant_attribute_value_id }}] = out[{{ $o->variant_attribute_value_id }}] ||
                    {};
                    out[{{ $o->variant_attribute_value_id }}][{{ $o->variant_attribute_chart_row_id }}] =
                        @json($o->value);
                @endforeach
                return out;
            })();

            function escHtml(s) {
                return String(s || '')
                    .replace(/&/g, '&amp;').replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
            }

            // Drop a trailing parenthetical qualifier from an attribute name for
            // display, e.g. "Size (Shirt)" → "Size". The raw name is kept for
            // matching/selection; only labels shown to the user are cleaned.
            function cleanAttrName(s) {
                return String(s == null ? '' : s).replace(/\s*\([^)]*\)/g, '').trim();
            }

            // ================================================================
            //  PRODUCT TYPE TOGGLE
            // ================================================================
            function setProductType(type) {
                productType = type;
                document.getElementById('productTypeInput').value = type;
                document.getElementById('typeSimple').classList.toggle('active', type === 'simple');
                document.getElementById('typeVariable').classList.toggle('active', type === 'variable');
                document.getElementById('attrBuilderCard').hidden = type !== 'variable';
                if (type === 'variable') {
                    loadVariantSystem();
                } else {
                    document.getElementById('sizeOverrideCard').hidden = true;
                }
            }
            document.getElementById('typeSimple').addEventListener('click', function() {
                setProductType('simple');
            });
            document.getElementById('typeVariable').addEventListener('click', function() {
                setProductType('variable');
            });

            // Initialize product type on load
            setProductType(productType);

            // ================================================================
            //  SIMPLE STOCK -- live totals
            // ================================================================
            var simpleStockBodyEl = document.getElementById('simpleStockBody');
            if (simpleStockBodyEl) {
                simpleStockBodyEl.addEventListener('input', function(e) {
                    if (e.target.classList.contains('simple-stock-qty') || e.target.classList.contains(
                            'simple-min-alert')) {
                        updateSimpleStockTotals();
                    }
                });
            }

            function stockStatus(qty, minAlert) {
                if (qty === 0) return {
                    cls: 'bp-badge-danger',
                    text: 'Out of Stock'
                };
                if (minAlert > 0 && qty <= minAlert) return {
                    cls: 'bp-badge-warning',
                    text: 'Low Stock'
                };
                return {
                    cls: 'bp-badge-success',
                    text: 'In Stock'
                };
            }

            function updateSimpleStockTotals() {
                if (!document.getElementById('simpleStockBody')) return;
                var total = 0,
                    inStock = 0,
                    low = 0,
                    out = 0;
                document.querySelectorAll('#simpleStockBody .simple-stock-row').forEach(function(row) {
                    var qty = parseInt(row.querySelector('.simple-stock-qty').value) || 0;
                    var minAlert = parseInt(row.querySelector('.simple-min-alert').value) || 0;
                    var badge = row.querySelector('.simple-stock-status');
                    var st = stockStatus(qty, minAlert);
                    badge.textContent = st.text;
                    badge.className = 'bp-badge ' + st.cls + ' simple-stock-status';
                    total += qty;
                    if (st.cls === 'bp-badge-success') inStock++;
                    else if (st.cls === 'bp-badge-warning') low++;
                    else out++;
                });

                document.getElementById('simpleTotalStockFoot').textContent = total;
                document.getElementById('simpleTotalStock').textContent = total;
                document.getElementById('totalStockBadge').textContent = 'Total Stock: ' + total;

                var ib = document.getElementById('simpleInStockBadge');
                var lb = document.getElementById('simpleLowBadge');
                var ob = document.getElementById('simpleOutBadge');
                ib.textContent = inStock + ' In Stock';
                ib.hidden = !inStock;
                lb.textContent = low + ' Low';
                lb.hidden = !low;
                ob.textContent = out + ' Out';
                ob.hidden = !out;

                var parts = [];
                if (inStock) parts.push(inStock + ' branch' + (inStock > 1 ? 'es' : '') + ' in stock');
                if (low) parts.push(low + ' low');
                if (out) parts.push(out + ' out of stock');
                document.getElementById('simpleStockSummaryText').textContent =
                    parts.length ? parts.join(' \u00B7 ') : 'Enter quantities above';
            }

            // ================================================================
            //  VARIANT SYSTEM — DB-Driven (AJAX)
            // ================================================================
            function loadVariantSystem(done) {
                if (variantSystemLoaded) {
                    if (typeof done === 'function') done();
                    return;
                }
                if (typeof done === 'function') variantSystemQueue.push(done);
                if (variantSystemLoading) return;
                variantSystemLoading = true;

                $.get('{{ route('products.ajax.variant-attributes') }}', function(data) {
                    allAttributes = data.attributes || [];
                    variantSystemLoaded = true;
                    variantSystemLoading = false;
                    if (existingVariants.length > 0) {
                        preselectFromExistingVariants();
                        renderVariantMatrix(existingVariants);
                    } else {
                        renderSelectedAttrs(); // shows the empty state
                    }
                    syncSizeChart();
                    variantSystemQueue.forEach(function(cb) {
                        cb();
                    });
                    variantSystemQueue = [];
                });
            }

            function resolveChartAttrId() {
                var picker = document.getElementById('variantAttrPicker');
                var pickerOpen = picker && !picker.classList.contains('d-none');
                if (pickerOpen) {
                    var pickerAttrId = parseInt(document.getElementById('variantAttrSelect').value || '0', 10);
                    var pMeta = allAttributes.find(function(a) {
                        return a.id === pickerAttrId;
                    });
                    if (pMeta && pMeta.has_chart) return pickerAttrId;
                }
                var found = null;
                Object.keys(selectedAttributes).forEach(function(id) {
                    if (found) return;
                    var meta = allAttributes.find(function(a) {
                        return a.id === parseInt(id, 10);
                    });
                    if (meta && meta.has_chart) found = parseInt(id, 10);
                });
                return found;
            }

            function syncSizeChart() {
                var chartAttrId = resolveChartAttrId();
                if (!chartAttrId) {
                    sizeChart.attributeId = null;
                    renderSizeOverrideTable();
                    return;
                }
                if (sizeChart.attributeId === chartAttrId && sizeChart.loaded) {
                    renderSizeOverrideTable();
                    return;
                }
                $.get('{{ route('products.ajax.size-chart-defaults') }}', {
                    attribute_id: chartAttrId
                }, function(res) {
                    sizeChart.attributeId = res.attribute_id;
                    sizeChart.rows = res.rows || [];
                    sizeChart.defaults = res.defaults || {};
                    sizeChart.loaded = true;
                    renderSizeOverrideTable();
                });
            }

            function selectedSizeValueIds() {
                if (!sizeChart.attributeId) return [];
                // Live preview: if the picker is open and showing the Size attribute,
                // read the tentative checked pills so the override table populates as
                // the user toggles values — before they click Add.
                var picker = document.getElementById('variantAttrPicker');
                var pickerOpen = picker && !picker.classList.contains('d-none');
                var pickerAttrId = parseInt(document.getElementById('variantAttrSelect').value || '0', 10);
                if (pickerOpen && pickerAttrId === sizeChart.attributeId) {
                    var tentative = [];
                    document.querySelectorAll('#variantValuesList input:checked').forEach(function(cb) {
                        tentative.push(parseInt(cb.dataset.valueId, 10));
                    });
                    if (tentative.length > 0) return tentative;
                }
                return (selectedAttributes[sizeChart.attributeId] || []).slice();
            }

            function renderSizeOverrideTable() {
                var $card = $('#sizeOverrideCard');
                // Hide entirely when there's no chart configured on Size.
                if (!sizeChart.attributeId || sizeChart.rows.length === 0) {
                    $card.attr('hidden', true);
                    return;
                }
                // Only show on variable products.
                if (productType !== 'variable') {
                    $card.attr('hidden', true);
                    return;
                }

                // Hide the whole card when no Size values are picked yet — there's
                // nothing to override and an empty table just adds visual noise.
                var sizeIds = selectedSizeValueIds();
                if (sizeIds.length === 0) {
                    $card.attr('hidden', true);
                    return;
                }

                $card.removeAttr('hidden');

                // Build header
                var $thead = $('#sizeOverrideTable thead tr').empty().append('<th class="bp-size-col">Size</th>');
                sizeChart.rows.forEach(function(r) {
                    $thead.append('<th>' + escHtml(r.label) + '</th>');
                });

                var $tbody = $('#sizeOverrideTable tbody').empty();
                $('#sizeOverrideEmpty').hide();

                // Look up the size value labels from allAttributes
                var sizeAttr = allAttributes.find(function(a) {
                    return a.id === sizeChart.attributeId;
                });
                if (!sizeAttr) return;
                var valuesById = {};
                sizeAttr.values.forEach(function(v) {
                    valuesById[v.id] = v.value;
                });

                sizeIds.forEach(function(vid) {
                    var label = valuesById[vid] || ('#' + vid);
                    var $tr = $('<tr data-value-id="' + vid + '"></tr>');
                    $tr.append('<th class="bp-size-cell">' + escHtml(label) + '</th>');
                    sizeChart.rows.forEach(function(r) {
                        var defaultVal = (sizeChart.defaults[vid] && sizeChart.defaults[vid][r.id]) ||
                            '';
                        var override = (existingOverrides[vid] && existingOverrides[vid][r.id]);
                        var val = (override !== undefined && override !== null) ? override : defaultVal;
                        var name = 'size_chart_overrides[' + vid + '][' + r.id + ']';
                        $tr.append(
                            '<td>' +
                            '<input type="text" class="bp-form-control bp-fc-sm" name="' + name +
                            '" ' +
                            'value="' + escHtml(val) + '" placeholder="' + escHtml(defaultVal ||
                                '—') + '" maxlength="64">' +
                            '</td>'
                        );
                    });
                    $tbody.append($tr);
                });
            }

            // Toggle collapse on the override card.
            $('#sizeOverrideToggle').on('click', function() {
                var $body = $('#sizeOverrideBody');
                var $btn = $(this);
                if ($body.is(':visible')) {
                    $body.slideUp(150);
                    $btn.attr('aria-expanded', 'false').html(
                        '<i class="fa-solid fa-chevron-down me-1"></i> Expand');
                } else {
                    $body.slideDown(150);
                    $btn.attr('aria-expanded', 'true').html(
                        '<i class="fa-solid fa-chevron-up me-1"></i> Collapse');
                }
            });

            // ── Picker-based attribute builder ────────────────────────────────
            // Internal state stays {attrId: [valueIds]} for compatibility with the
            // Generate Variants payload and the size-chart hook. The picker UI just
            // wraps a different commit flow on top of that state.

            function populateAttrSelectDropdown() {
                var $sel = $('#variantAttrSelect');
                $sel.empty().append('<option value="">Select Attribute</option>');
                allAttributes.forEach(function(attr) {
                    if (!selectedAttributes[attr.id]) {
                        $sel.append('<option value="' + attr.id + '">' + escHtml(attr.name) + '</option>');
                    }
                });
            }

            $('#loadVariantAttrsBtn').on('click', function() {
                $('#variantAttrsEmpty').addClass('d-none');
                $('#variantAttrPicker').removeClass('d-none');
                $('#variantAttrSelect').html('<option value="">{{ __('Loading…') }}</option>');
                $('#variantValuesContainer').addClass('d-none');
                $('#variantValuesList').empty();
                // Ensure attributes are loaded before populating — handles simple→variable
                // conversions where the attribute GET may not have resolved yet.
                loadVariantSystem(function() {
                    populateAttrSelectDropdown();
                    $('#variantAttrSelect').val('');
                });
            });

            $('#cancelVariantAttr').on('click', function() {
                $('#variantAttrPicker').addClass('d-none');
                if (Object.keys(selectedAttributes).length === 0) {
                    $('#variantAttrsEmpty').removeClass('d-none');
                }
                // Picker just closed — clear any tentative Size preview from the override table.
                setTimeout(syncSizeChart, 0);
            });

            // Live-update the override table as Size value pills are toggled in the
            // picker, before the user clicks Add. Required so the override accordion
            // actually populates immediately instead of waiting on a commit.
            $(document).on('change', '#variantValuesList input[type="checkbox"]', function() {
                syncSizeChart();
            });

            $('#variantAttrSelect').on('change', function() {
                var attrId = parseInt(this.value, 10);
                var $list = $('#variantValuesList').empty();
                if (!attrId) {
                    $('#variantValuesContainer').addClass('d-none');
                    return;
                }
                var attr = allAttributes.find(function(a) {
                    return a.id === attrId;
                });
                if (!attr) return;
                $('#variantValuesContainer').removeClass('d-none');
                var isColorAttr = attr.display_type === 'color_swatch';
                attr.values.forEach(function(v) {
                    var dot = (isColorAttr && v.color_code) ?
                        '<span class="bp-attr-chip-color-dot" data-color="' + escHtml(v.color_code) +
                        '"></span> ' :
                        '';
                    var $lbl = $('<label class="bp-attr-value-toggle">' +
                        '<input type="checkbox" data-value-id="' + v.id + '" data-label="' +
                        escHtml(v.value) + '">' +
                        '<span class="bp-attr-chip bp-attr-chip-selectable">' + dot + escHtml(v
                            .value) + '</span>' +
                        '</label>');
                    $lbl.find('[data-color]').css('background-color', v.color_code);
                    $list.append($lbl);
                });
            });

            // Remove this product's variants that contain any of the given value ids.
            // Blocks (422) if any are referenced by sales/purchases; on success drops matching rows.
            function removeVariantValues(valueIds, onSuccess) {
                $.ajax({
                    url: '{{ route('products.remove-variant-values', $product) }}',
                    method: 'DELETE',
                    data: {
                        value_ids: valueIds
                    },
                    success: function() {
                        $('#variationMatrixBody tr').each(function() {
                            var rowIds = String($(this).data('value-ids') || '').split(',').map(
                                Number).filter(function(n) {
                                return n > 0;
                            });
                            if (valueIds.some(function(id) {
                                    return rowIds.indexOf(Number(id)) !== -1;
                                })) {
                                $(this).remove();
                            }
                        });
                        var cnt = $('#variationMatrixBody tr').length;
                        document.getElementById('variantCountBadge').textContent = cnt + ' Variant' + (
                            cnt !== 1 ? 's' : '');
                        if (cnt === 0) $('#variationMatrixCard').prop('hidden', true);
                        if (onSuccess) onSuccess();
                    },
                    error: function(xhr) {
                        var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON
                            .message : 'Failed to remove variants.';
                        alert(msg);
                    }
                });
            }

            $('#addVariantAttrConfirm').on('click', function() {
                var attrId = parseInt($('#variantAttrSelect').val(), 10);
                if (!attrId) return;
                var valueIds = [];
                $('#variantValuesList input:checked').each(function() {
                    valueIds.push(parseInt(this.dataset.valueId, 10));
                });
                if (valueIds.length === 0) {
                    alert('Select at least one value.');
                    return;
                }

                var previous = selectedAttributes[attrId] || [];
                var removed = previous.filter(function(id) {
                    return valueIds.indexOf(id) === -1;
                });

                function applySelection() {
                    selectedAttributes[attrId] = valueIds;
                    renderSelectedAttrs();
                    $('#variantAttrPicker').addClass('d-none');
                    populateAttrSelectDropdown();
                    updateComboCount();
                    syncSizeChart();
                    // Reconcile variants server-side against the full current selection:
                    // existing combos are kept, missing ones are created, and combos no longer
                    // in the matrix (e.g. Color-only rows after Size is added) are pruned.
                    generateAndRenderVariants();
                }

                if (removed.length > 0) {
                    removeVariantValues(removed, applySelection);
                } else {
                    applySelection();
                }
            });

            function renderSelectedAttrs() {
                var $wrap = $('#selectedVariantAttrs').empty();
                var $empty = $('#variantAttrsEmpty');
                var keys = Object.keys(selectedAttributes).filter(function(k) {
                    return selectedAttributes[k] && selectedAttributes[k].length > 0;
                });
                if (keys.length === 0) {
                    $empty.removeClass('d-none');
                    return;
                }
                $empty.addClass('d-none');

                keys.forEach(function(attrId) {
                    attrId = parseInt(attrId, 10);
                    var attr = allAttributes.find(function(a) {
                        return a.id === attrId;
                    });
                    if (!attr) return;
                    var selected = selectedAttributes[attrId] || [];
                    var isColorAttr = attr.display_type === 'color_swatch';

                    // Render EVERY value of the attribute as a toggle chip: "on" (taken — used
                    // by the variants) or "off" (available — click to add). Lets the admin add
                    // or remove values in place rather than reopening the Add Attribute picker.
                    var chipsHtml = attr.values.map(function(v) {
                        var on = selected.indexOf(v.id) !== -1;
                        var dot = (isColorAttr && v.color_code) ?
                            '<span class="bp-attr-chip-color-dot" data-color="' + escHtml(v
                                .color_code) + '"></span> ' :
                            '';
                        var icon = '<i class="fa-solid ' + (on ? 'fa-check' : 'fa-plus') +
                            ' ms-1 bp-variant-toggle-icon"></i>';
                        return '<button type="button" class="bp-variant-toggle ' + (on ?
                                'bp-variant-toggle-on' : 'bp-variant-toggle-off') + '" ' +
                            'data-attr-id="' + attrId + '" data-value-id="' + v.id + '" data-label="' +
                            escHtml(v.value) + '">' +
                            dot + escHtml(v.value) + icon +
                            '</button>';
                    }).join('');

                    var $card = $('<div class="bp-selected-variant-attr mt-2">' +
                        '<div class="d-flex justify-content-between align-items-center mb-1">' +
                        '<span class="fw-700 fs-13">' + escHtml(attr.name) + '</span>' +
                        '<button type="button" class="bp-btn bp-btn-sm bp-btn-icon bp-btn-outline text-danger remove-variant-attr" data-attr-id="' +
                        attrId + '"><i class="fa-solid fa-xmark"></i></button>' +
                        '</div>' +
                        '<div class="d-flex flex-wrap gap-1">' + chipsHtml + '</div>' +
                        '</div>');
                    $card.find('[data-color]').each(function() {
                        $(this).css('background-color', $(this).attr('data-color'));
                    });
                    $wrap.append($card);
                });
            }

            $(document).on('click', '.remove-variant-attr', function() {
                var attrId = parseInt($(this).data('attr-id'), 10);
                var valueIds = (selectedAttributes[attrId] || []).slice();

                function clearLocal() {
                    delete selectedAttributes[attrId];
                    renderSelectedAttrs();
                    updateComboCount();
                    syncSizeChart();
                }

                if (valueIds.length > 0) {
                    removeVariantValues(valueIds, clearLocal);
                } else {
                    clearLocal();
                }
            });

            // Toggle one attribute value on/off straight from its card chip.
            //   off → on:  add the value, then reconcile (its combos are generated).
            //   on  → off: confirm, then remove the value's combos (blocked if sold).
            // A busy flag guards the add (reconcile) path against double-fire; the remove
            // path is throttled by its confirm() dialog and is idempotent server-side.
            var variantToggleBusy = false;
            $(document).on('click', '.bp-variant-toggle', function() {
                if (variantToggleBusy) return;

                var $btn = $(this);
                var attrId = parseInt($btn.data('attr-id'), 10);
                var valueId = parseInt($btn.data('value-id'), 10);
                var label = $btn.data('label') || 'this value';
                var attr = allAttributes.find(function(a) {
                    return a.id === attrId;
                });
                var attrName = attr ? attr.name : 'attribute';
                var sel = selectedAttributes[attrId] || (selectedAttributes[attrId] = []);
                var isOn = sel.indexOf(valueId) !== -1;

                if (!isOn) {
                    variantToggleBusy = true;
                    sel.push(valueId);
                    renderSelectedAttrs();
                    updateComboCount();
                    syncSizeChart();
                    generateAndRenderVariants(function() {
                        variantToggleBusy = false;
                    });
                    return;
                }

                // Removing — keep at least one value per attribute (an empty dimension would
                // collapse the matrix). Dropping the whole attribute uses the × button.
                if (sel.length <= 1) {
                    alert('Keep at least one ' + attrName +
                        ' value. Use the × button to remove the whole attribute.');
                    return;
                }

                var count = $('#variationMatrixBody tr').filter(function() {
                    var ids = String($(this).data('value-ids') || '').split(',').map(Number);
                    return ids.indexOf(valueId) !== -1;
                }).length;
                if (!confirm('Remove "' + label + '"' + (count ? ' and its ' + count + ' variant(s)' : '') +
                        '?')) return;

                removeVariantValues([valueId], function() {
                    selectedAttributes[attrId] = sel.filter(function(id) {
                        return id !== valueId;
                    });
                    renderSelectedAttrs();
                    updateComboCount();
                    syncSizeChart();
                });
            });

            function updateComboCount() {
                var counts = [];
                Object.keys(selectedAttributes).forEach(function(k) {
                    if (selectedAttributes[k].length > 0) counts.push(selectedAttributes[k].length);
                });
                var total = counts.length ? counts.reduce(function(a, b) {
                    return a * b;
                }, 1) : 0;
                document.getElementById('comboCountLabel').textContent =
                    total > 0 ? total + ' combination' + (total !== 1 ? 's' : '') : '';
            }

            function preselectFromExistingVariants() {
                // Walk every existing variant and union its attribute_id → value_id pairs
                // into selectedAttributes. Then render the summary cards once.
                existingVariants.forEach(function(v) {
                    (v.attributes || []).forEach(function(a) {
                        if (!selectedAttributes[a.attribute_id]) {
                            selectedAttributes[a.attribute_id] = [];
                        }
                        if (selectedAttributes[a.attribute_id].indexOf(a.value_id) === -1) {
                            selectedAttributes[a.attribute_id].push(a.value_id);
                        }
                    });
                });
                renderSelectedAttrs();
                updateComboCount();
            }

            // Rebuild the variant matrix after each picker Add. Sends the full current
            // selection so the server reconciles it: existing combos are reused, new ones
            // created, and stale combos (from a smaller prior attribute set) pruned.
            function generateAndRenderVariants(onComplete) {
                var attrValueIds = [];
                Object.keys(selectedAttributes).forEach(function(k) {
                    if (selectedAttributes[k].length > 0) attrValueIds.push(selectedAttributes[k]);
                });
                if (attrValueIds.length === 0) {
                    if (onComplete) onComplete();
                    return;
                }

                $.ajax({
                    url: '{{ route('products.generate-variants', $product) }}',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        attribute_value_ids: attrValueIds
                    }),
                    success: function(data) {
                        existingVariants = data.variants;
                        renderVariantMatrix(data.variants);
                        if (onComplete) onComplete();
                    },
                    error: function(xhr) {
                        alert(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message :
                            'Failed to generate variants.');
                        if (onComplete) onComplete();
                    }
                });
            }

            function renderVariantMatrix(variants) {
                var $tbody = $('#variationMatrixBody');
                $tbody.empty();

                if (!variants || variants.length === 0) {
                    $('#variationMatrixCard').prop('hidden', true);
                    return;
                }

                var colorAttrNames = {};
                allAttributes.forEach(function(a) {
                    if (a.display_type === 'color_swatch') colorAttrNames[a.name] = true;
                });

                variants.forEach(function(v, idx) {
                    var chipsHtml = (v.values || v.attributes || []).map(function(a) {
                        var attrName = a.attribute || a.attribute_name;
                        var dot = (colorAttrNames[attrName] && a.color_code) ?
                            '<span class="bp-attr-chip-color-dot" data-color="' + escHtml(a
                                .color_code) + '"></span> ' : '';
                        return '<span class="bp-variant-chip">' + dot + escHtml(cleanAttrName(
                                attrName)) + ': ' +
                            escHtml(a.value) + '</span>';
                    }).join('');

                    var valueIds = (v.values || v.attributes || []).map(function(a) {
                        return a.value_id;
                    }).filter(function(x) {
                        return x != null;
                    }).join(',');
                    var row = '<tr class="bp-variation-row' + (v.is_active ? '' :
                            ' bp-variation-row-inactive') + '" data-variant-id="' + v.id +
                        '" data-value-ids="' +
                        valueIds + '">' +
                        '<td class="text-muted fs-12 text-center">' + (idx + 1) + '</td>' +
                        '<td><div class="bp-variant-combo">' + chipsHtml + '</div></td>' +
                        '<td><input type="text" class="bp-form-control bp-fc-sm var-field" data-field="sku" value="' +
                        escHtml(v.sku) + '"></td>' +
                        '<td><input type="number" class="bp-form-control bp-fc-sm var-field" data-field="cost_price" step="0.01" min="0" value="' +
                        (v.cost_price || '') + '" placeholder="0.00"></td>' +
                        '<td><input type="number" class="bp-form-control bp-fc-sm var-field" data-field="sell_price" step="0.01" min="0" value="' +
                        (v.sell_price || '') + '" placeholder="0.00"></td>' +
                        '<td class="text-center"><input type="number" class="bp-form-control bp-fc-sm var-stock text-center" min="0" value="' +
                        ((v.stock === null || typeof v.stock === 'undefined') ? '' : v.stock) +
                        '" placeholder="0"></td>' +
                        '<td class="text-center">' +
                        '<label class="bp-radio" title="Mark as default">' +
                        '<input type="radio" name="default_variant_id" value="' + v.id +
                        '" class="default-variant-radio var-field" data-field="is_default"' + (v.is_default ?
                            ' checked' : '') + '>' +
                        '<span class="bp-radio-dot"></span>' +
                        '</label>' +
                        '</td>' +
                        '<td class="text-center">' +
                        '<div class="form-check form-switch d-inline-flex justify-content-center mb-0">' +
                        '<input class="form-check-input var-active-toggle var-field" type="checkbox" role="switch" data-field="is_active" data-variant-id="' +
                        v.id + '"' + (v.is_active ? ' checked' : '') + '>' +
                        '</div>' +
                        '</td>' +
                        '<td>' +
                        '<button type="button" class="bp-btn bp-btn-sm bp-btn-icon bp-btn-danger var-del-btn" title="Delete variant"><i class="fa-solid fa-trash"></i></button>' +
                        '</td>' +
                        '</tr>';
                    $tbody.append(row);
                });

                // Apply color dots
                $tbody.find('.bp-attr-chip-color-dot[data-color]').each(function() {
                    $(this).css('background-color', this.dataset.color);
                });

                var cnt = variants.length;
                document.getElementById('variantCountBadge').textContent = cnt + ' Variant' + (cnt !== 1 ? 's' : '');
                $('#variationMatrixCard').prop('hidden', false);
            }

            // Collect the variant matrix and persist SKU / pricing / default /
            // active in one request. Returns a promise so the main product-form
            // submit can wait for it. Resolves immediately when there's nothing
            // to save (e.g. a simple product or unsaved rows only).
            function bulkSaveVariants() {
                var variants = [];
                var stock = [];
                $('#variationMatrixBody tr').each(function() {
                    var $row = $(this);
                    var vid = parseInt($row.data('variant-id'));
                    if (!vid) return; // skip unsaved rows
                    var data = {
                        id: vid
                    };
                    $row.find('.var-field').each(function() {
                        var field = $(this).data('field');
                        if (field === 'is_active' || field === 'is_default') {
                            data[field] = this.checked ? 1 : 0;
                        } else {
                            data[field] = $(this).val();
                        }
                    });
                    variants.push(data);
                    // Collect per-variant initial/updated stock (Bug_85). Only send
                    // rows where a quantity was entered so blanks don't zero stock.
                    var $stockEl = $row.find('.var-stock');
                    if ($stockEl.length && $stockEl.val() !== '') {
                        stock.push({
                            variant_id: vid,
                            quantity: parseInt($stockEl.val(), 10) || 0
                        });
                    }
                });
                if (variants.length === 0) {
                    return $.Deferred().resolve().promise();
                }

                return $.ajax({
                    url: '{{ route('products.bulk-update-variants', $product) }}',
                    method: 'PUT',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        variants: variants,
                        stock: stock
                    })
                });
            }

            // Variant SKU/pricing/status edits are now saved together with the
            // main product when "Update Product" (or "Save as Draft") is clicked:
            // persist the variant matrix first, then let the form submit.
            $('#editProductForm').on('submit', function(e) {
                var form = this;
                // Re-entrant pass after requestSubmit/submit() — let it through.
                if (form._variantsSaved) {
                    form._variantsSaved = false;
                    return;
                }
                if ($('#variationMatrixBody tr[data-variant-id]').length === 0) {
                    return; // no variants to sync — submit normally
                }

                e.preventDefault();
                var submitter = (e.originalEvent && e.originalEvent.submitter) || null;
                var $submits = $('#saveProductBtn, #saveProductBtn2, .draft').prop('disabled', true);

                bulkSaveVariants().done(function() {
                    // Re-enable first: a disabled submitter button is barred from
                    // submission, which would drop its name/value (e.g. status=draft).
                    $submits.prop('disabled', false);
                    form._variantsSaved = true;
                    if (form.requestSubmit) {
                        form.requestSubmit(submitter || undefined);
                    } else {
                        // Fallback: preserve the clicked submitter's name/value (e.g. Save as Draft).
                        if (submitter && submitter.name) {
                            $('<input type="hidden">').attr('name', submitter.name)
                                .val(submitter.value).appendTo(form);
                        }
                        form.submit();
                    }
                }).fail(function(xhr) {
                    $submits.prop('disabled', false);
                    alert(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message :
                        'Failed to save variants.');
                });
            });

            // Delete single variant
            $(document).on('click', '.var-del-btn', function() {
                if (!confirm('Delete this variant?')) return;
                var $row = $(this).closest('tr');
                var variantId = $row.data('variant-id');

                $.ajax({
                    url: '{{ route('products.destroy-variant', [$product, ':vid']) }}'.replace(':vid',
                        variantId),
                    method: 'DELETE',
                    success: function() {
                        $row.fadeOut(300, function() {
                            $(this).remove();
                            var cnt = $('#variationMatrixBody tr').length;
                            document.getElementById('variantCountBadge').textContent = cnt +
                                ' Variant' + (cnt !== 1 ? 's' : '');
                            if (cnt === 0) $('#variationMatrixCard').prop('hidden', true);
                        });
                    },
                    error: function(xhr) {
                        var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON
                            .message : 'Failed to delete variant.';
                        alert(msg);
                    }
                });
            });

            // Instant active/inactive toggle per variant — saves straight to the DB so
            // an inactive variant disappears from the storefront immediately (no full save).
            $(document).on('change', '.var-active-toggle', function() {
                var $cb = $(this);
                var variantId = $cb.data('variant-id');
                if (!variantId) return; // unsaved row — handled by the bulk save instead
                var active = $cb.is(':checked') ? 1 : 0;
                $cb.prop('disabled', true);
                $.ajax({
                    url: '{{ route('products.variant-active', [$product, ':vid']) }}'.replace(':vid',
                        variantId),
                    method: 'PATCH',
                    data: {
                        is_active: active
                    },
                    success: function() {
                        $cb.closest('tr').toggleClass('bp-variation-row-inactive', active === 0);
                    },
                    error: function() {
                        $cb.prop('checked', active === 0); // revert the toggle on failure
                        alert('{{ __('Failed to update variant status.') }}');
                    },
                    complete: function() {
                        $cb.prop('disabled', false);
                    }
                });
            });

            // ================================================================
            //  PRICING -- Quick Summary + Profit Preview
            // ================================================================
            function updateSummary() {
                var name = document.getElementById('productName').value.trim();
                var sku = document.getElementById('productSku').value.trim();
                var checked = document.querySelectorAll('.cat-checkbox:checked');
                // Primary = the most specific (deepest) selected category, matching
                // how the server picks product.category_id.
                var primaryCb = null,
                    maxDepth = -1;
                Array.prototype.forEach.call(checked, function(cb) {
                    var item = cb.closest('.bp-cat-item');
                    var d = item ? (parseInt(item.getAttribute('data-depth'), 10) || 0) : 0;
                    if (d > maxDepth) {
                        maxDepth = d;
                        primaryCb = cb;
                    }
                });
                var catNames = Array.prototype.map.call(checked, function(cb) {
                    var label = cb.closest('.bp-cat-item').querySelector('.bp-cat-label').textContent.trim();
                    return cb === primaryCb ? label + ' (primary)' : label;
                });
                var cost = parseFloat(document.getElementById('costPrice').value) || 0;
                var sell = parseFloat(document.getElementById('sellPrice').value) || 0;

                document.getElementById('sumName').textContent = name || '--';
                document.getElementById('sumSku').textContent = sku || '--';
                document.getElementById('sumCategory').textContent = catNames.length ? catNames.join(', ') : '--';
                document.getElementById('sumCost').textContent = cost ? '{{ currency_symbol() }} ' + cost
                    .toLocaleString('en-IN') : '--';
                document.getElementById('sumSell').textContent = sell ? '{{ currency_symbol() }} ' + sell
                    .toLocaleString('en-IN') : '--';

                if (cost > 0 && sell > 0) {
                    var profit = sell - cost;
                    var margin = ((profit / sell) * 100).toFixed(1);
                    document.getElementById('sumProfit').textContent =
                        '{{ currency_symbol() }} ' + profit.toLocaleString('en-IN') + ' (' + margin + '%)';
                } else {
                    document.getElementById('sumProfit').textContent = '--';
                }
            }

            function updateProfit() {
                var cost = parseFloat(document.getElementById('costPrice').value) || 0;
                var sell = parseFloat(document.getElementById('sellPrice').value) || 0;
                if (cost > 0 && sell > 0) {
                    var profit = sell - cost;
                    var margin = ((profit / sell) * 100).toFixed(1);
                    document.getElementById('profitPreview').hidden = false;
                    document.getElementById('previewCost').textContent = '{{ currency_symbol() }} ' + cost
                        .toLocaleString('en-IN');
                    document.getElementById('previewSell').textContent = '{{ currency_symbol() }} ' + sell
                        .toLocaleString('en-IN');
                    document.getElementById('previewProfit').textContent = '{{ currency_symbol() }} ' + profit
                        .toLocaleString('en-IN');
                    document.getElementById('previewMargin').textContent = margin + '%';
                    document.getElementById('marginBadge').textContent = 'Margin: ' + margin + '%';
                } else {
                    document.getElementById('profitPreview').hidden = true;
                    document.getElementById('marginBadge').textContent = 'Margin: --';
                }
                updateSummary();
                updateDiscountedPrice();
            }

            // Compute and show the final price after the discount (Bug_83).
            function updateDiscountedPrice() {
                var sell = parseFloat(document.getElementById('sellPrice').value) || 0;
                var type = document.getElementById('discountType').value;
                var amount = parseFloat(document.getElementById('discountAmount').value) || 0;
                var priceEl = document.getElementById('discountedPrice');
                if (sell <= 0 || amount <= 0) {
                    priceEl.textContent = sell > 0 ?
                        '{{ currency_symbol() }} ' + sell.toLocaleString('en-IN') : '--';
                    return;
                }
                var discountValue = type === 'percentage' ? (sell * amount / 100) : amount;
                if (discountValue > sell) discountValue = sell;
                var finalPrice = sell - discountValue;
                priceEl.textContent = '{{ currency_symbol() }} ' + finalPrice.toLocaleString('en-IN');
            }

            document.getElementById('costPrice').addEventListener('input', updateProfit);
            document.getElementById('sellPrice').addEventListener('input', updateProfit);
            document.getElementById('discountAmount').addEventListener('input', updateDiscountedPrice);
            document.getElementById('discountType').addEventListener('change', updateDiscountedPrice);
            ['productName', 'productSku'].forEach(function(id) {
                document.getElementById(id).addEventListener('input', updateSummary);
                document.getElementById(id).addEventListener('change', updateSummary);
            });
            // Selecting a sub/child category auto-selects its parent chain. In the flat
            // pre-order list a node's parent is the nearest preceding row at depth-1.
            function selectCategoryAncestors(checkbox) {
                var item = checkbox.closest('.bp-cat-item');
                if (!item) return;
                var depth = parseInt(item.getAttribute('data-depth'), 10) || 0;
                var prev = item.previousElementSibling;
                while (prev && depth > 0) {
                    if (prev.classList.contains('bp-cat-item')) {
                        var prevDepth = parseInt(prev.getAttribute('data-depth'), 10) || 0;
                        if (prevDepth === depth - 1) {
                            var cb = prev.querySelector('.cat-checkbox');
                            if (cb && !cb.checked) cb.checked = true;
                            depth = prevDepth;
                        }
                    }
                    prev = prev.previousElementSibling;
                }
            }

            $(document).on('change', '.cat-checkbox', function() {
                if (this.checked) selectCategoryAncestors(this);
                updateSummary();
            });

            // Categories sidebar — live search filter
            (function() {
                var input = document.getElementById('categorySearch');
                var list = document.getElementById('categoryList');
                var empty = document.getElementById('categoryEmpty');
                if (!input || !list) return;
                input.addEventListener('input', function() {
                    var q = this.value.trim().toLowerCase();
                    var visible = 0;
                    list.querySelectorAll('.bp-cat-item').forEach(function(item) {
                        var name = item.dataset.name || '';
                        var match = !q || name.indexOf(q) !== -1;
                        item.style.display = match ? '' : 'none';
                        if (match) visible++;
                    });
                    if (empty) empty.classList.toggle('d-none', visible > 0);
                });
            })();

            // ================================================================
            //  BARCODE + AUTO-SKU
            // ================================================================
            // Live barcode preview with JSBarcode
            function updateBarcodePreview(code) {
                var svg = document.getElementById('barcodeSvg');
                var icon = document.getElementById('barcodePlaceholderIcon');
                var text = document.getElementById('barcodeText');
                if (code && code.length >= 4) {
                    try {
                        JsBarcode(svg, code, {
                            format: 'CODE128',
                            width: 1.5,
                            height: 50,
                            displayValue: true,
                            fontSize: 12,
                            margin: 5
                        });
                        svg.classList.remove('d-none');
                        icon.classList.add('d-none');
                        text.textContent = '';
                    } catch (e) {
                        svg.classList.add('d-none');
                        icon.classList.remove('d-none');
                        text.textContent = code;
                    }
                } else {
                    svg.classList.add('d-none');
                    icon.classList.remove('d-none');
                    text.textContent = code || '{{ __('No barcode') }}';
                }
            }

            document.getElementById('productBarcode').addEventListener('input', function() {
                updateBarcodePreview(this.value.trim());
            });

            // Render existing barcode on load
            updateBarcodePreview('{{ $product->barcode ?? '' }}');

            // Print label
            document.getElementById('printLabelBtn').addEventListener('click', function() {
                var code = document.getElementById('productBarcode').value.trim();
                var name = document.getElementById('productName').value.trim();
                var price = document.getElementById('sellPrice').value.trim();
                if (!code) {
                    alert('{{ __('Please enter a barcode first.') }}');
                    return;
                }
                var printWin = window.open('', '_blank', 'width=400,height=300');
                var svgEl = document.getElementById('barcodeSvg');
                var svgHtml = svgEl.classList.contains('d-none') ? '' : svgEl.outerHTML;
                printWin.document.write(
                    '<html><head><title>{{ __('Print Label') }}</title><style>body{text-align:center;font-family:sans-serif;padding:20px;margin:0}svg{display:block;margin:10px auto}.name{font-weight:700;font-size:14px;margin-bottom:4px}.price{font-size:16px;font-weight:800;color:#1B4F72}@media print{body{padding:5px}}</style></head><body>'
                );
                printWin.document.write('<div class="name">' + escHtml(name) + '</div>');
                if (price) printWin.document.write('<div class="price">{{ currency_symbol() }} ' + price +
                    '</div>');
                printWin.document.write(svgHtml);
                printWin.document.write('</body></html>');
                printWin.document.close();
                printWin.focus();
                printWin.print();
            });

            // Thumbnail preview/remove is handled by the shared image-upload component.

            // Unified gallery: existing images + new uploads share one reorderable grid.
            // Grid order = storefront gallery order (first item = primary/main image).
            initGalleryManager('galleryGrid', 'imageInput', 'imageDropzone', 'galleryHidden', 'galleryHint',
                'editProductForm');

            document.getElementById('autoSkuBtn').addEventListener('click', function() {
                var name = document.getElementById('productName').value.trim();
                if (!name) {
                    alert('Enter a product name first.');
                    return;
                }
                var firstChecked = document.querySelector('.cat-checkbox:checked');
                var catId = firstChecked ? firstChecked.value : null;
                var btn = this;
                btn.disabled = true;
                $.post('{{ route('products.generate-sku') }}', {
                    category_id: catId || null
                }, function(data) {
                    if (data && data.sku) {
                        document.getElementById('productSku').value = data.sku;
                        updateSummary();
                    }
                }).fail(function() {
                    var sku = name.split(' ').filter(Boolean)
                        .map(function(w) {
                            return w.substring(0, 3).toUpperCase();
                        }).join('-');
                    sku += '-' + Math.floor(Math.random() * 900 + 100);
                    document.getElementById('productSku').value = sku;
                    updateSummary();
                }).always(function() {
                    btn.disabled = false;
                });
            });

            // ================================================================
            //  QUICK ADD — Brand & Unit
            // ================================================================
            var quickUnitTarget = null;

            document.getElementById('addBrandBtn').addEventListener('click', function() {
                document.getElementById('quickBrandName').value = '';
                document.getElementById('quickBrandError').textContent = '';
                document.getElementById('quickBrandName').classList.remove('is-invalid');
                new bootstrap.Modal('#quickBrandModal').show();
                setTimeout(function() {
                    document.getElementById('quickBrandName').focus();
                }, 300);
            });

            document.getElementById('quickBrandSave').addEventListener('click', function() {
                var name = document.getElementById('quickBrandName').value.trim();
                if (!name) {
                    document.getElementById('quickBrandName').classList.add('is-invalid');
                    document.getElementById('quickBrandError').textContent = 'Brand name is required.';
                    return;
                }
                var btn = this;
                btn.disabled = true;
                $.post('{{ route('brands.quick-store') }}', {
                    name: name
                }, function(data) {
                    var opt = new Option(data.name, data.id, true, true);
                    $('#productBrand').append(opt).trigger('change');
                    bootstrap.Modal.getInstance('#quickBrandModal').hide();
                }).fail(function(xhr) {
                    var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message :
                        'Failed to create brand.';
                    document.getElementById('quickBrandName').classList.add('is-invalid');
                    document.getElementById('quickBrandError').textContent = msg;
                }).always(function() {
                    btn.disabled = false;
                });
            });

            function openQuickUnitModal(target) {
                quickUnitTarget = target;
                document.getElementById('quickUnitName').value = '';
                document.getElementById('quickUnitShortName').value = '';
                document.getElementById('quickUnitError').textContent = '';
                document.getElementById('quickUnitName').classList.remove('is-invalid');
                document.getElementById('quickUnitShortName').classList.remove('is-invalid');
                document.getElementById('quickUnitTypeBase').checked = true;
                document.getElementById('quickSubUnitFields').classList.add('d-none');
                document.getElementById('quickBaseUnitSelect').value = '';
                document.getElementById('quickConvOperator').value = 'divide';
                document.getElementById('quickConvValue').value = '';
                document.getElementById('quickAllowDecimal').checked = false;
                document.getElementById('quickConversionPreview').hidden = true;
                new bootstrap.Modal('#quickUnitModal').show();
                setTimeout(function() {
                    document.getElementById('quickUnitName').focus();
                }, 300);
            }

            document.querySelectorAll('input[name="quick_unit_type"]').forEach(function(radio) {
                radio.addEventListener('change', function() {
                    document.getElementById('quickSubUnitFields').classList.toggle('d-none', this
                        .value !== 'sub');
                });
            });

            function updateQuickConversionPreview() {
                var subName = document.getElementById('quickUnitShortName').value.trim() || '?';
                var baseOpt = document.getElementById('quickBaseUnitSelect');
                var selOpt = baseOpt.options[baseOpt.selectedIndex];
                var baseName = (selOpt && selOpt.dataset.short) || 'base';
                var op = document.getElementById('quickConvOperator').value;
                var val = parseFloat(document.getElementById('quickConvValue').value);

                if (val > 0 && baseOpt.value) {
                    var factor = (op === 'divide') ? (1 / val) : val;
                    var displayFactor = factor < 1 ? factor.toFixed(6).replace(/0+$/, '').replace(/\.$/, '') : factor;
                    document.getElementById('quickConversionPreview').hidden = false;
                    document.getElementById('quickConversionText').innerHTML =
                        '<strong>1 ' + escHtml(subName) + '</strong> = <strong>' + displayFactor + ' ' + escHtml(
                            baseName) + '</strong>';
                } else {
                    document.getElementById('quickConversionPreview').hidden = true;
                }
            }

            document.getElementById('quickBaseUnitSelect').addEventListener('change', updateQuickConversionPreview);
            document.getElementById('quickConvOperator').addEventListener('change', updateQuickConversionPreview);
            document.getElementById('quickConvValue').addEventListener('input', updateQuickConversionPreview);
            document.getElementById('quickUnitShortName').addEventListener('input', updateQuickConversionPreview);

            document.getElementById('addPurchaseUnitBtn').addEventListener('click', function() {
                openQuickUnitModal('purchase');
            });
            document.getElementById('addSaleUnitBtn').addEventListener('click', function() {
                openQuickUnitModal('sale');
            });

            document.getElementById('quickUnitSave').addEventListener('click', function() {
                var name = document.getElementById('quickUnitName').value.trim();
                var shortName = document.getElementById('quickUnitShortName').value.trim();
                var unitType = document.querySelector('input[name="quick_unit_type"]:checked').value;
                var hasError = false;
                document.getElementById('quickUnitName').classList.remove('is-invalid');
                document.getElementById('quickUnitShortName').classList.remove('is-invalid');
                if (!name) {
                    document.getElementById('quickUnitName').classList.add('is-invalid');
                    hasError = true;
                }
                if (!shortName) {
                    document.getElementById('quickUnitShortName').classList.add('is-invalid');
                    hasError = true;
                }
                if (hasError) {
                    document.getElementById('quickUnitError').textContent = 'Name and short name are required.';
                    return;
                }

                var payload = {
                    name: name,
                    short_name: shortName,
                    unit_type: unitType,
                    allow_decimal: document.getElementById('quickAllowDecimal').checked ? '1' : '0'
                };
                if (unitType === 'sub') {
                    payload.base_unit_id = document.getElementById('quickBaseUnitSelect').value;
                    var qOp = document.getElementById('quickConvOperator').value;
                    var qVal = parseFloat(document.getElementById('quickConvValue').value);
                    if (!payload.base_unit_id || !qVal || qVal <= 0) {
                        document.getElementById('quickUnitError').textContent =
                            'Base unit and conversion value are required for sub units.';
                        return;
                    }
                    payload.conversion_factor = (qOp === 'divide') ? (1 / qVal) : qVal;
                }

                var btn = this;
                btn.disabled = true;
                $.post('{{ route('units.quick-store') }}', payload, function(data) {
                    var label = data.name + ' (' + data.short_name + ')';
                    var optPurchase = new Option(label, data.id, quickUnitTarget === 'purchase',
                        quickUnitTarget === 'purchase');
                    var optSale = new Option(label, data.id, quickUnitTarget === 'sale',
                        quickUnitTarget === 'sale');
                    $('#purchaseUnit').append(optPurchase).trigger('change');
                    $('#saleUnit').append(optSale).trigger('change');
                    bootstrap.Modal.getInstance('#quickUnitModal').hide();
                }).fail(function(xhr) {
                    var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message :
                        'Failed to create unit.';
                    document.getElementById('quickUnitError').textContent = msg;
                }).always(function() {
                    btn.disabled = false;
                });
            });

            // Allow Enter key to submit in quick-add modals
            document.getElementById('quickBrandName').addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    document.getElementById('quickBrandSave').click();
                }
            });
            document.getElementById('quickUnitShortName').addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    document.getElementById('quickUnitSave').click();
                }
            });

            // Generate Barcode — calls the same server endpoint the create page uses.
            // The barcode-preview SVG re-renders manually because setting .value
            // programmatically does not fire the input listener.
            document.getElementById('genBarcodeBtn').addEventListener('click', function() {
                var btn = this;
                btn.disabled = true;
                $.post('{{ route('products.generate-barcode') }}', function(data) {
                    if (data && data.barcode) {
                        document.getElementById('productBarcode').value = data.barcode;
                        updateBarcodePreview(data.barcode);
                    }
                }).fail(function() {
                    alert('Failed to generate barcode. Please try again.');
                }).always(function() {
                    btn.disabled = false;
                });
            });

            // Delete product confirmation
            document.getElementById('deleteProductBtn').addEventListener('click', function() {
                var modal = new bootstrap.Modal(document.getElementById('deleteProductModal'));
                modal.show();
            });

            // ================================================================
            //  EXISTING IMAGE REMOVAL TOGGLE
            // ================================================================
            document.querySelectorAll('.bp-image-remove-label').forEach(function(label) {
                label.addEventListener('click', function() {
                    var checkbox = this.querySelector('input[type="checkbox"]');
                    var thumb = this.closest('.bp-image-thumb-existing');
                    if (thumb) {
                        thumb.classList.toggle('bp-image-marked-remove', checkbox.checked);
                    }
                });
            });

            // ================================================================
            //  DISCOUNT TYPE TOGGLE
            // ================================================================
            document.getElementById('discountType').addEventListener('change', function() {
                var field = document.getElementById('discountAmountField');
                var label = document.getElementById('discountAmountLabel');
                var addon = document.getElementById('discountAddon');
                if (this.value === 'none') {
                    field.hidden = true;
                    document.getElementById('discountAmount').value = '';
                } else {
                    field.hidden = false;
                    if (this.value === 'percentage') {
                        label.textContent = '{{ __('Discount (%)') }}';
                        addon.textContent = '%';
                    } else {
                        label.textContent = '{{ __('Discount Amount') }} ({{ currency_symbol() }})';
                        addon.textContent = '{{ currency_symbol() }}';
                    }
                }
                updateDiscountedPrice();
            });

            // Run initial calculations
            updateSimpleStockTotals();
            updateProfit();

        })();
    </script>
@endpush
