@extends('core::layouts.master')

@section('title', __('Add New Product'))
@section('page-title', __('Add New Product'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('products.index') }}">Products</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Add New</span>
@endsection

@section('page-actions')
    <a href="{{ route('products.index') }}" class="bp-btn bp-btn-danger"><i class="fa-solid fa-arrow-left me-1"></i>
        Cancel</a>
    {{-- <button class="bp-btn bp-btn-outline" id="saveDraftBtn" form="addProductForm" name="status" value="draft"><i
            class="fa-solid fa-floppy-disk me-1"></i> Save as Draft</button> --}}
    <button class="bp-btn bp-btn-success" id="saveProductBtn" form="addProductForm" type="submit"><i
            class="fa-solid fa-check me-1"></i> Save Product</button>
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
        $selectedCategoryIds = collect(old('categories', []))->map(fn($v) => (int) $v)->all();
    @endphp

    <form id="addProductForm" action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data"
        novalidate>
        @csrf
        <div class="row g-4">

            <!-- =============LEFT COLUMN -- Main form sections ========== -->
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
                                    value="{{ old('name') }}">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="bp-form-label">Model</label>
                                <input type="text" class="bp-form-control @error('model') is-invalid @enderror"
                                    id="productModel" name="model" placeholder="e.g., GCS-04" value="{{ old('model') }}">
                                @error('model')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <label class="bp-form-label">SKU</label>
                                <div class="input-group">
                                    <input type="text" class="bp-form-control @error('sku') is-invalid @enderror"
                                        id="productSku" name="sku" placeholder="Auto-generated"
                                        value="{{ old('sku') }}">
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
                                        id="productBarcode" name="barcode" placeholder="Auto-generated"
                                        value="{{ old('barcode') }}">
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
                                    value="{{ old('position', 0) }}">
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
                                                {{ old('brand_id') == $brand->id ? 'selected' : '' }}>{{ $brand->name }}
                                            </option>
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
                                                {{ old('purchase_unit_id', strtolower($unit->short_name) === 'pcs' ? $unit->id : '') == $unit->id ? 'selected' : '' }}>
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
                                                {{ old('sale_unit_id', strtolower($unit->short_name) === 'pcs' ? $unit->id : '') == $unit->id ? 'selected' : '' }}>
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
                                    <option value="" {{ old('warranty') == '' ? 'selected' : '' }}>No Warranty
                                    </option>
                                    <option value="3_months" {{ old('warranty') == '3_months' ? 'selected' : '' }}>3
                                        Months</option>
                                    <option value="6_months" {{ old('warranty') == '6_months' ? 'selected' : '' }}>6
                                        Months</option>
                                    <option value="1_year" {{ old('warranty') == '1_year' ? 'selected' : '' }}>1 Year
                                    </option>
                                    <option value="2_years" {{ old('warranty') == '2_years' ? 'selected' : '' }}>2 Years
                                    </option>
                                    <option value="3_years" {{ old('warranty') == '3_years' ? 'selected' : '' }}>3 Years
                                    </option>
                                </select>
                                @error('warranty')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 col-lg-3" id="openingStockField">
                                <label class="bp-form-label">Opening Stock</label>
                                <input type="number"
                                    class="bp-form-control @error('stock.0.quantity') is-invalid @enderror"
                                    id="openingStock" name="stock[0][quantity]" placeholder="e.g., 100" min="0"
                                    value="{{ old('stock.0.quantity') }}">
                                @error('stock.0.quantity')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <label class="bp-form-label">Min Stock Alert</label>
                                <input type="number"
                                    class="bp-form-control @error('min_stock_alert') is-invalid @enderror" id="minStock"
                                    name="min_stock_alert" placeholder="e.g., 10" min="0"
                                    value="{{ old('min_stock_alert') }}">
                                <input type="hidden" name="stock[0][min_alert]" id="openingStockMinAlert"
                                    value="{{ old('min_stock_alert') }}">
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
                            value="{{ old('product_type', 'simple') }}">
                        <div class="bp-product-type-toggle">
                            <button type="button"
                                class="bp-type-btn {{ old('product_type', 'simple') === 'simple' ? 'active' : '' }}"
                                id="typeSimple">
                                <div class="bp-type-btn-icon"><i class="fa-solid fa-box"></i></div>
                                <div class="bp-type-btn-text">
                                    <div class="fw-700 fs-14">Simple Product</div>
                                    <div class="fs-12 text-muted">Single price & stock -- no variants needed</div>
                                </div>
                                <div class="bp-type-btn-check"><i class="fa-solid fa-circle-check"></i></div>
                            </button>
                            <button type="button"
                                class="bp-type-btn {{ old('product_type') === 'variable' ? 'active' : '' }}"
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
                <div class="bp-card mb-4" id="variantInfoCard" hidden>
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-layer-group me-2 text-warning"></i>Variant
                            Attributes</h5>
                        <button type="button" class="bp-btn bp-btn-sm bp-btn-outline" id="loadVariantAttrsBtn">
                            <i class="fa-solid fa-plus me-1"></i> Add Attribute
                        </button>
                    </div>
                    <div class="bp-card-body">
                        <div id="variantAttrsContainer">
                            <div class="text-center py-3 text-muted" id="variantAttrsEmpty">
                                <i class="fa-solid fa-sliders fa-2x mb-2 d-block"></i>
                                <p class="fs-13 mb-1">Click "Add Attribute" to select variant attributes (Color, Size,
                                    etc.)</p>
                                <small class="text-muted">Variant combinations will be generated after saving the
                                    product.</small>
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

                <!-- Variant Matrix (Variable Product only) -->
                <div class="bp-card mb-4" id="variantMatrixCard" hidden>
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-table-cells me-2 text-primary"></i>Variants <span
                                class="fs-12 text-muted fw-400 ms-1">— SKU, Pricing & Opening Stock</span></h5>
                        <span class="bp-badge bp-badge-primary" id="variantMatrixCountBadge">0 Variants</span>
                    </div>
                    <div class="bp-card-body p-0">
                        <div class="bp-table-wrapper">
                            <table class="bp-table bp-variation-table" id="variantMatrixTable">
                                <thead>
                                    <tr>
                                        <th class="text-center" style="width:48px;">#</th>
                                        <th>Variant</th>
                                        <th style="min-width:180px;">SKU</th>
                                        <th style="width:120px;">Cost ({{ currency_symbol() }})</th>
                                        <th style="width:120px;">Sell ({{ currency_symbol() }})</th>
                                        <th class="text-center" style="width:110px;">Opening Stock</th>
                                        <th class="text-center" style="width:70px;"
                                            title="Default variant shown first on the storefront">Default</th>
                                        <th class="text-center" style="width:70px;">Active</th>
                                    </tr>
                                </thead>
                                <tbody id="variantMatrixBody"></tbody>
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
                    <div class="bp-card-body" id="sizeOverrideBody">
                        <p class="fs-12 text-muted mb-3">
                            Defaults come from the global <strong>Size</strong> chart. Edit any cell to override for this
                            product only — clear it to fall back to the default.
                        </p>
                        <div class="bp-table-wrapper">
                            <table class="bp-table bp-size-override-table" id="sizeOverrideTable">
                                <thead>
                                    <tr>
                                        <th class="bp-size-col">Size</th><!-- chart row columns injected by JS -->
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                        <div id="sizeOverrideEmpty" class="text-muted fs-12">Add <strong>Size</strong> values above to
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
                                        value="{{ old('cost_price') }}">
                                </div>
                                @error('cost_price')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="bp-form-label">Retail Price ({{ currency_symbol() }}) *</label>
                                <div class="input-group">
                                    <span class="bp-input-addon">{{ currency_symbol() }}</span>
                                    <input type="number"
                                        class="bp-form-control @error('sell_price') is-invalid @enderror" id="sellPrice"
                                        name="sell_price" required placeholder="0.00" min="0" step="0.01"
                                        value="{{ old('sell_price') }}">
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
                                        step="0.01" value="{{ old('wholesale_price') }}">
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
                                        step="0.01" value="{{ old('resell_price') }}">
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
                                        {{ old('discount_type', 'fixed') == 'fixed' ? 'selected' : '' }}>Fixed Amount
                                        ({{ currency_symbol() }})</option>
                                    <option value="percentage"
                                        {{ old('discount_type') == 'percentage' ? 'selected' : '' }}>Percentage (%)
                                    </option>
                                </select>
                                @error('discount_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4" id="discountAmountField">
                                <label class="bp-form-label"
                                    id="discountAmountLabel">{{ old('discount_type') == 'percentage' ? __('Discount (%)') : __('Discount Amount') . ' (' . currency_symbol() . ')' }}</label>
                                <div class="input-group">
                                    <span class="bp-input-addon"
                                        id="discountAddon">{{ old('discount_type') == 'percentage' ? '%' : currency_symbol() }}</span>
                                    <input type="number"
                                        class="bp-form-control @error('discount_value') is-invalid @enderror"
                                        id="discountAmount" name="discount_value" placeholder="0" min="0"
                                        step="0.01" value="{{ old('discount_value') }}">
                                </div>
                                @error('discount_value')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            {{-- Final price after the discount is applied (Bug_83). --}}
                            <div class="col-md-4" id="discountedPriceField">
                                <label class="bp-form-label">{{ __('Price After Discount') }}
                                    ({{ currency_symbol() }})</label>
                                <div class="bp-price-card p-1 text-start ps-3 bp-price-card-sell p-1">
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
                                    id="longDescription">{{ old('long_description') }}</textarea>
                                @error('long_description')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="bp-form-label">SEO Title</label>
                                <input type="text" class="bp-form-control @error('seo_title') is-invalid @enderror"
                                    name="seo_title" data-seo-title
                                    placeholder="e.g., Buy Premium Cotton Casual Shirt Online in Bangladesh"
                                    value="{{ old('seo_title') }}">
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
                                    value="{{ old('seo_description') }}">
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
                                    value="{{ old('tags') }}">
                                @error('tags')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- ======================== RIGHT COLUMN -- Images, Status, Quick info ====================== -->
            <div class="col-xl-4">

                <!-- Thumbnail Image -->
                <div class="bp-card mb-4">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-image me-2 text-primary"></i>Thumbnail Image</h5>
                    </div>
                    <div class="bp-card-body">
                        <x-core::image-upload name="thumbnail" id="thumbnailInput" label="Thumbnail"
                            hint="JPG, PNG, WebP — max 2MB" help="Main display image for the product." />
                    </div>
                </div>

                <!-- Gallery Images -->
                <div class="bp-card mb-4">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-images me-2 text-info"></i>Gallery Images</h5>
                    </div>
                    <div class="bp-card-body">
                        <!-- Drop zone holds the gallery thumbnails inside it. -->
                        <div class="bp-image-dropzone bp-gallery-dropzone" id="imageDropzone">
                            <div class="bp-gallery-grid" id="galleryGrid"></div>
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
                        <small class="text-muted fs-11 mt-2 d-block" id="galleryHint" style="display:none;">
                            <i class="fa-solid fa-circle-info me-1"></i> Drag to reorder &mdash; the first image shows
                            first in the product gallery.
                        </small>

                        <!-- Submit-time hidden inputs (image_order[]) -->
                        <div id="galleryHidden" class="d-none"></div>
                    </div>
                </div>

                <!-- Categories (multi-select sidebar) -->
                <div class="bp-card mb-4" id="categoriesCard">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-tags me-2 text-primary"></i>Categories <span
                                class="text-danger">*</span></h5>
                        <button type="button" class="bp-btn bp-btn-sm bp-btn-icon bp-btn-outline" id="addCategoryBtn"
                            title="Add new category"><i class="fa-solid fa-plus"></i></button>
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
                                    name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }}>
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
                                    value="1" {{ old('allow_negative_stock') ? 'checked' : '' }}>
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
                            <div class="fs-12 text-muted" id="barcodeText">Enter barcode above to preview</div>
                        </div>
                        <button class="bp-btn bp-btn-sm bp-btn-outline mt-3 w-100" type="button" id="printLabelBtn">
                            <i class="fa-solid fa-print me-1"></i> Print Label
                        </button>
                    </div>
                </div>

                <!-- Sticky Save (mobile) -->
                <div class="bp-card create_pro_buttons">
                    <div class="bp-card-body d-flex flex-wrap justify-content-between gap-2">
                        <button class="bp-btn bp-btn-success w-100 justify-content-center bp-btn-lg save" type="submit"
                            id="saveProductBtn2">
                            <i class="fa-solid fa-check me-2"></i> Save Product
                        </button>
                        <button class="bp-btn bp-btn-warning justify-content-center draft" type="submit" name="status"
                            value="draft" id="saveDraftBtn2">
                            <i class="fa-solid fa-floppy-disk me-2"></i> Save as Draft
                        </button>
                        <a href="{{ route('products.index') }}"
                            class="bp-btn justify-content-center bp-btn-danger cancel">
                            <i class="fa-solid fa-xmark me-2"></i> Discard & Cancel
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </form>

    <!-- Quick Add Category Modal -->
    <div class="modal fade" id="quickCategoryModal" tabindex="-1">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title fw-800"><i class="fa-solid fa-folder-plus me-2"></i>New Category</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="bp-form-label">Category Name *</label>
                    <input type="text" class="bp-form-control" id="quickCategoryName" placeholder="e.g., Electronics"
                        maxlength="255">
                    <div class="invalid-feedback" id="quickCategoryError"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="bp-btn bp-btn-danger bp-btn-sm" data-bs-dismiss="modal"><i
                            class="fa-solid fa-xmark me-1"></i>Cancel</button>
                    <button type="button" class="bp-btn bp-btn-success bp-btn-sm" id="quickCategorySave"><i
                            class="fa-solid fa-check me-1"></i>Save</button>
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
            var productType = '{{ old('product_type', 'simple') }}';

            // ================================================================
            //  PRODUCT TYPE TOGGLE
            // ================================================================
            function setProductType(type) {
                productType = type;
                document.getElementById('productTypeInput').value = type;
                document.getElementById('typeSimple').classList.toggle('active', type === 'simple');
                document.getElementById('typeVariable').classList.toggle('active', type === 'variable');
                document.getElementById('variantInfoCard').hidden = type !== 'variable';
                var matrixCard = document.getElementById('variantMatrixCard');
                if (type !== 'variable' && matrixCard) matrixCard.hidden = true;
                // Opening stock is captured per-variant in the matrix for variable products.
                var openingStockField = document.getElementById('openingStockField');
                if (openingStockField) {
                    openingStockField.hidden = type === 'variable';
                    var openingStockInput = document.getElementById('openingStock');
                    if (openingStockInput) openingStockInput.disabled = type === 'variable';
                }
            }

            // Keep the hidden stock min-alert in sync with the Min Stock Alert input so
            // the opening-stock row stores a reorder level.
            var minStockEl = document.getElementById('minStock');
            var openingStockMinAlertEl = document.getElementById('openingStockMinAlert');
            if (minStockEl && openingStockMinAlertEl) {
                minStockEl.addEventListener('input', function() {
                    openingStockMinAlertEl.value = minStockEl.value;
                });
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
                    parts.length ? parts.join(' \u00B7 ') : 'Enter opening quantities above';
            }

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
            document.getElementById('discountAmount').addEventListener('input', updateDiscountedPrice);
            document.getElementById('discountType').addEventListener('change', updateDiscountedPrice);

            document.getElementById('costPrice').addEventListener('input', updateProfit);
            document.getElementById('sellPrice').addEventListener('input', updateProfit);
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

            // ================================================================
            //  BARCODE + AUTO-SKU
            // ================================================================
            document.getElementById('productBarcode').addEventListener('input', function() {
                var code = this.value.trim();
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
                    text.textContent = code || '{{ __('Enter barcode above to preview') }}';
                }
            });

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
                printWin.document.write('<div class="name">' + (name || '') + '</div>');
                if (price) printWin.document.write('<div class="price">{{ currency_symbol() }} ' + price +
                    '</div>');
                printWin.document.write(svgHtml);
                printWin.document.write('</body></html>');
                printWin.document.close();
                printWin.focus();
                printWin.print();
            });

            // Fetches a server-generated SKU (category-aware) and fills the field.
            // When the AJAX fails it falls back to a name-based code so the user
            // is never left with an empty SKU. `btn` is optional (omitted on the
            // pre-generation call that runs on page load).
            function generateSku(btn) {
                var name = document.getElementById('productName').value.trim();
                var firstChecked = document.querySelector('.cat-checkbox:checked');
                var catId = firstChecked ? firstChecked.value : null;
                if (btn) btn.disabled = true;
                $.post('{{ route('products.generate-sku') }}', {
                    category_id: catId || null
                }, function(data) {
                    if (data && data.sku) {
                        document.getElementById('productSku').value = data.sku;
                        updateSummary();
                    }
                }).fail(function() {
                    var sku = name ?
                        name.split(' ').filter(Boolean).map(function(w) {
                            return w.substring(0, 3).toUpperCase();
                        }).join('-') + '-' + Math.floor(Math.random() * 900 + 100) :
                        'PRD-' + Math.floor(Math.random() * 90000 + 10000);
                    document.getElementById('productSku').value = sku;
                    updateSummary();
                }).always(function() {
                    if (btn) btn.disabled = false;
                });
            }

            // Fetches a unique server-generated barcode and fills the field.
            function generateBarcode(btn) {
                if (btn) btn.disabled = true;
                $.post('{{ route('products.generate-barcode') }}', function(data) {
                    if (data && data.barcode) {
                        document.getElementById('productBarcode').value = data.barcode;
                        var barcodeText = document.getElementById('barcodeText');
                        if (barcodeText) barcodeText.textContent = data.barcode;
                    }
                }).fail(function() {
                    if (btn) alert('Failed to generate barcode. Please try again.');
                }).always(function() {
                    if (btn) btn.disabled = false;
                });
            }

            document.getElementById('autoSkuBtn').addEventListener('click', function() {
                generateSku(this);
            });

            document.getElementById('genBarcodeBtn').addEventListener('click', function() {
                generateBarcode(this);
            });

            // Pre-generate SKU and barcode on page load so the fields are never
            // blank. Skips any field already populated (e.g. validation redirect
            // re-fills via old() input), so user/validation values are preserved.
            if (!document.getElementById('productSku').value.trim()) {
                generateSku();
            }
            if (!document.getElementById('productBarcode').value.trim()) {
                generateBarcode();
            }

            // ================================================================
            //  QUICK ADD — Category, Brand, Unit
            // ================================================================
            var quickUnitTarget = null;

            document.getElementById('addCategoryBtn').addEventListener('click', function() {
                document.getElementById('quickCategoryName').value = '';
                document.getElementById('quickCategoryError').textContent = '';
                document.getElementById('quickCategoryName').classList.remove('is-invalid');
                new bootstrap.Modal('#quickCategoryModal').show();
                setTimeout(function() {
                    document.getElementById('quickCategoryName').focus();
                }, 300);
            });

            document.getElementById('quickCategorySave').addEventListener('click', function() {
                var name = document.getElementById('quickCategoryName').value.trim();
                if (!name) {
                    document.getElementById('quickCategoryName').classList.add('is-invalid');
                    document.getElementById('quickCategoryError').textContent = 'Category name is required.';
                    return;
                }
                var btn = this;
                btn.disabled = true;
                $.post('{{ route('categories.quick-store') }}', {
                    name: name
                }, function(data) {
                    var listEl = document.getElementById('categoryList');
                    var emptyEl = document.getElementById('categoryEmpty');
                    var html = '<label class="bp-cat-item bp-cat-depth-0" data-depth="0" data-name="' +
                        (data.name || '').toLowerCase() + '" style="--depth: 0;">' +
                        '<input type="checkbox" name="categories[]" value="' + data.id +
                        '" class="cat-checkbox" checked>' +
                        '<span class="bp-check-box"></span>' +
                        '<span class="bp-cat-label">' + $('<span>').text(data.name).html() + '</span>' +
                        '</label>';
                    if (emptyEl && emptyEl.parentNode === listEl) {
                        listEl.insertBefore($(html)[0], emptyEl);
                    } else {
                        listEl.insertAdjacentHTML('beforeend', html);
                    }
                    bootstrap.Modal.getInstance('#quickCategoryModal').hide();
                    updateSummary();
                }).fail(function(xhr) {
                    var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message :
                        'Failed to create category.';
                    document.getElementById('quickCategoryName').classList.add('is-invalid');
                    document.getElementById('quickCategoryError').textContent = msg;
                }).always(function() {
                    btn.disabled = false;
                });
            });

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

            // Toggle sub-unit fields in quick modal
            document.querySelectorAll('input[name="quick_unit_type"]').forEach(function(radio) {
                radio.addEventListener('change', function() {
                    document.getElementById('quickSubUnitFields').classList.toggle('d-none', this
                        .value !== 'sub');
                });
            });

            // Conversion preview in quick modal
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
            document.getElementById('quickCategoryName').addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    document.getElementById('quickCategorySave').click();
                }
            });
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

            // ================================================================
            //  CATEGORY SIDEBAR — live search filter
            // ================================================================
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
            //  GALLERY IMAGES (unified, reorderable) — shared manager in app.js
            // ================================================================
            initGalleryManager('galleryGrid', 'imageInput', 'imageDropzone', 'galleryHidden', 'galleryHint',
                'addProductForm');

            // Thumbnail preview is handled by the shared image-upload component.

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

            // ================================================================
            //  VARIANT ATTRIBUTE PICKER
            // ================================================================
            var variantAttrsData = [];
            var selectedAttrs = {};
            var attrsLoaded = false;

            document.getElementById('loadVariantAttrsBtn').addEventListener('click', function() {
                document.getElementById('variantAttrPicker').classList.remove('d-none');
                document.getElementById('variantAttrsEmpty').classList.add('d-none');
                if (!attrsLoaded) {
                    $.get('{{ route('products.ajax.variant-attributes') }}', function(data) {
                        variantAttrsData = data.attributes || [];
                        var select = document.getElementById('variantAttrSelect');
                        select.innerHTML = '<option value="">{{ __('Select Attribute') }}</option>';
                        variantAttrsData.forEach(function(attr) {
                            if (!selectedAttrs[attr.id]) {
                                select.innerHTML += '<option value="' + attr.id + '">' + attr
                                    .name + '</option>';
                            }
                        });
                        attrsLoaded = true;
                    });
                }
            });

            document.getElementById('cancelVariantAttr').addEventListener('click', function() {
                document.getElementById('variantAttrPicker').classList.add('d-none');
                if (Object.keys(selectedAttrs).length === 0) {
                    document.getElementById('variantAttrsEmpty').classList.remove('d-none');
                }
            });

            document.getElementById('variantAttrSelect').addEventListener('change', function() {
                var attrId = parseInt(this.value);
                var valuesContainer = document.getElementById('variantValuesContainer');
                var valuesList = document.getElementById('variantValuesList');
                if (!attrId) {
                    valuesContainer.classList.add('d-none');
                    return;
                }
                var attr = variantAttrsData.find(function(a) {
                    return a.id === attrId;
                });
                if (!attr) return;
                valuesContainer.classList.remove('d-none');
                valuesList.innerHTML = '';
                var isColorAttr = attr.display_type === 'color_swatch';
                attr.values.forEach(function(v) {
                    var colorDot = (isColorAttr && v.color_code) ?
                        '<span class="d-inline-block rounded-circle me-1" style="width:12px;height:12px;background:' +
                        v.color_code + '"></span>' : '';
                    valuesList.innerHTML +=
                        '<label class="bp-variant-value-check"><input type="checkbox" value="' + v.id +
                        '" data-label="' + v.value + '"> ' + colorDot + v.value + '</label>';
                });
            });

            document.getElementById('addVariantAttrConfirm').addEventListener('click', function() {
                var attrId = parseInt(document.getElementById('variantAttrSelect').value);
                if (!attrId) return;
                var attr = variantAttrsData.find(function(a) {
                    return a.id === attrId;
                });
                if (!attr) return;
                var checkedValues = [];
                document.querySelectorAll('#variantValuesList input:checked').forEach(function(cb) {
                    checkedValues.push({
                        id: cb.value,
                        label: cb.dataset.label
                    });
                });
                if (checkedValues.length === 0) {
                    alert('{{ __('Select at least one value') }}');
                    return;
                }

                selectedAttrs[attrId] = {
                    name: attr.name,
                    values: checkedValues
                };
                renderSelectedAttrs();
                document.getElementById('variantAttrPicker').classList.add('d-none');

                // Refresh the select to remove the already-added attribute
                var select = document.getElementById('variantAttrSelect');
                var opt = select.querySelector('option[value="' + attrId + '"]');
                if (opt) opt.remove();
                select.value = '';
                document.getElementById('variantValuesContainer').classList.add('d-none');
            });

            function renderSelectedAttrs() {
                var container = document.getElementById('selectedVariantAttrs');
                container.innerHTML = '';
                var keys = Object.keys(selectedAttrs);
                if (keys.length === 0) {
                    buildVariantMatrix();
                    return;
                }

                keys.forEach(function(attrId) {
                    var attr = selectedAttrs[attrId];
                    var valuesHtml = attr.values.map(function(v) {
                        return '<span class="bp-badge bp-badge-primary">' + v.label + '</span>';
                    }).join(' ');
                    container.innerHTML += '<div class="bp-selected-variant-attr mt-2">' +
                        '<div class="d-flex justify-content-between align-items-center mb-1">' +
                        '<span class="fw-700 fs-13">' + attr.name + '</span>' +
                        '<button type="button" class="bp-btn bp-btn-sm bp-btn-icon bp-btn-outline text-danger remove-variant-attr" data-attr-id="' +
                        attrId + '"><i class="fa-solid fa-xmark"></i></button>' +
                        '</div>' +
                        '<div class="d-flex flex-wrap gap-1">' + valuesHtml + '</div>' +
                        '</div>';
                });

                buildVariantMatrix();
                syncSizeChart();
            }

            // ── Per-product Size Chart Override ───────────────────────────────
            var sizeChart = {
                attributeId: null,
                rows: [],
                defaults: {},
                loaded: false
            };

            // Pick the attribute whose measurement chart should drive the override
            // table: the one open in the picker (mid-pick preview) if it has a chart,
            // otherwise the first committed attribute that has a chart. This lets ANY
            // chart-bearing attribute work (e.g. "Size (Pant)"), not just "Size".
            function resolveChartAttrId() {
                var picker = document.getElementById('variantAttrPicker');
                var pickerOpen = picker && !picker.classList.contains('d-none');
                if (pickerOpen) {
                    var pickerAttrId = parseInt(document.getElementById('variantAttrSelect').value || '0', 10);
                    var pMeta = variantAttrsData.find(function(a) {
                        return a.id === pickerAttrId;
                    });
                    if (pMeta && pMeta.has_chart) return pickerAttrId;
                }
                var found = null;
                Object.keys(selectedAttrs).forEach(function(id) {
                    if (found) return;
                    var meta = variantAttrsData.find(function(a) {
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

            // Returns the sizes to render in the override table.
            // 1. If user is currently mid-pick on the Size attribute (the picker is
            //    visible, Size is selected in the dropdown, values checked but Add
            //    not clicked yet) — preview those tentative values immediately.
            // 2. Otherwise, fall back to the committed selectedAttrs[Size].
            function currentSizeSelection() {
                if (!sizeChart.attributeId) return [];
                var picker = document.getElementById('variantAttrPicker');
                var pickerOpen = picker && !picker.classList.contains('d-none');
                var pickerAttrId = parseInt(document.getElementById('variantAttrSelect').value || '0', 10);

                if (pickerOpen && pickerAttrId === sizeChart.attributeId) {
                    var tentative = [];
                    document.querySelectorAll('#variantValuesList input:checked').forEach(function(cb) {
                        tentative.push({
                            id: cb.value,
                            label: cb.dataset.label
                        });
                    });
                    if (tentative.length > 0) return tentative;
                }
                var committed = selectedAttrs[sizeChart.attributeId];
                return (committed && committed.values) ? committed.values : [];
            }

            function renderSizeOverrideTable() {
                var $card = $('#sizeOverrideCard');
                if (!sizeChart.attributeId || sizeChart.rows.length === 0) {
                    $card.attr('hidden', true);
                    return;
                }

                var values = currentSizeSelection();
                if (values.length === 0) {
                    $card.attr('hidden', true);
                    return;
                }

                $card.removeAttr('hidden');

                var $thead = $('#sizeOverrideTable thead tr').empty().append('<th class="bp-size-col">Size</th>');
                sizeChart.rows.forEach(function(r) {
                    $thead.append('<th>' + $('<div>').text(r.label).html() + '</th>');
                });

                var $tbody = $('#sizeOverrideTable tbody').empty();
                $('#sizeOverrideEmpty').hide();

                values.forEach(function(v) {
                    var vid = parseInt(v.id, 10);
                    var $tr = $('<tr data-value-id="' + vid + '"></tr>');
                    $tr.append('<th class="bp-size-cell">' + $('<div>').text(v.label).html() + '</th>');
                    sizeChart.rows.forEach(function(r) {
                        var defaultVal = (sizeChart.defaults[vid] && sizeChart.defaults[vid][r.id]) ||
                            '';
                        var name = 'size_chart_overrides[' + vid + '][' + r.id + ']';
                        var inputHtml = '<input type="text" class="bp-form-control bp-fc-sm" name="' +
                            name + '" ' +
                            'value="' + $('<div>').text(defaultVal).html() + '" ' +
                            'placeholder="' + $('<div>').text(defaultVal || '—').html() +
                            '" maxlength="64">';
                        $tr.append('<td>' + inputHtml + '</td>');
                    });
                    $tbody.append($tr);
                });
            }

            // Live-update the override table as the user checks Size value pills,
            // before they click "Add". Required by the spec — selecting a value should
            // open the override accordion immediately.
            $(document).on('change', '#variantValuesList input[type="checkbox"]', function() {
                syncSizeChart();
            });
            // Cancel button: clear the tentative preview if it wasn't committed.
            document.getElementById('cancelVariantAttr').addEventListener('click', function() {
                setTimeout(syncSizeChart, 0);
            });

            // Collapse / expand the override card.
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

            // Trigger a defaults fetch as soon as variant attributes are loaded.
            // The "Add Attribute" button is what kicks off attrsLoaded; piggyback there.
            document.getElementById('loadVariantAttrsBtn').addEventListener('click', syncSizeChart);
            // Evaluate on page load too (hidden until a chart-bearing attribute is picked).
            syncSizeChart();

            var defaultComboKey = null;

            function escAttr(s) {
                return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;');
            }

            function abbreviateValue(s) {
                var t = String(s || '').trim();
                if (t.length <= 3) return t.toUpperCase();
                var m = t.match(/(\d+)/);
                if (m) return m[1];
                return t.substring(0, 3).toUpperCase();
            }

            function collectMatrixState() {
                // Snapshot user edits keyed by sorted attribute-value-id combo
                var state = {};
                document.querySelectorAll('#variantMatrixBody tr').forEach(function(tr) {
                    var key = tr.dataset.comboKey;
                    if (!key) return;
                    var row = {};
                    tr.querySelectorAll('input[data-field]').forEach(function(inp) {
                        row[inp.dataset.field] = inp.type === 'checkbox' ? inp.checked : inp.value;
                    });
                    var stockInp = tr.querySelector('input[data-stock]');
                    if (stockInp) row.stock = stockInp.value;
                    state[key] = row;
                });
                // Capture which combo is currently the default before re-render.
                var checkedRadio = document.querySelector('#variantMatrixBody .default-variant-radio:checked');
                if (checkedRadio) defaultComboKey = checkedRadio.dataset.comboKey;
                return state;
            }

            function buildVariantMatrix() {
                var card = document.getElementById('variantMatrixCard');
                var tbody = document.getElementById('variantMatrixBody');
                var saved = collectMatrixState();

                var keys = Object.keys(selectedAttrs);
                var hasValues = keys.length > 0 && keys.every(function(k) {
                    return selectedAttrs[k].values.length > 0;
                });
                if (!hasValues) {
                    tbody.innerHTML = '';
                    card.hidden = true;
                    return;
                }

                // Cartesian product
                var combos = [
                    []
                ];
                keys.forEach(function(attrId) {
                    var attr = selectedAttrs[attrId];
                    var next = [];
                    combos.forEach(function(combo) {
                        attr.values.forEach(function(val) {
                            next.push(combo.concat([{
                                attrId: attrId,
                                attrName: attr.name,
                                valId: val.id,
                                valLabel: val.label
                            }]));
                        });
                    });
                    combos = next;
                });

                var parentSku = document.getElementById('productSku').value.trim();
                var parentCost = document.getElementById('costPrice').value;
                var parentSell = document.getElementById('sellPrice').value;

                tbody.innerHTML = '';
                combos.forEach(function(combo, idx) {
                    var label = combo.map(function(c) {
                        return escAttr(c.valLabel);
                    }).join(' / ');
                    var comboKey = combo.map(function(c) {
                        return c.valId;
                    }).slice().sort(function(a, b) {
                        return a - b;
                    }).join('-');
                    var prior = saved[comboKey] || {};

                    var sku = prior.sku;
                    if (sku === undefined) {
                        sku = (parentSku ? parentSku + '-' : '') + combo.map(function(c) {
                            return abbreviateValue(c.valLabel);
                        }).join('-');
                    }
                    var cost = prior.cost_price !== undefined ? prior.cost_price : parentCost;
                    var sell = prior.sell_price !== undefined ? prior.sell_price : parentSell;
                    var active = prior.is_active === undefined ? true : !!prior.is_active;

                    var hiddenIds = combo.map(function(c) {
                        return '<input type="hidden" name="variants[' + idx +
                            '][attribute_value_ids][]" value="' + c.valId + '">';
                    }).join('');

                    var stockQty = prior.stock !== undefined ? prior.stock : '';
                    var stockCell = '<td class="text-center">' +
                        '<input type="number" min="0" class="bp-form-control bp-form-control-sm text-center" ' +
                        'data-stock="1" ' +
                        'name="variants[' + idx + '][stock]" ' +
                        'value="' + escAttr(stockQty) + '" placeholder="0">' +
                        '</td>';

                    var isDefault = (defaultComboKey === null && idx === 0) || defaultComboKey === comboKey;

                    var row = '<tr data-combo-key="' + comboKey + '">' +
                        '<td class="text-center text-muted fs-12">' + (idx + 1) + '</td>' +
                        '<td><div class="fw-700 fs-13">' + label + '</div>' + hiddenIds + '</td>' +
                        '<td><input type="text" name="variants[' + idx +
                        '][sku]" data-field="sku" class="bp-form-control bp-form-control-sm" value="' + escAttr(
                            sku) + '"></td>' +
                        '<td><input type="number" name="variants[' + idx +
                        '][cost_price]" data-field="cost_price" step="0.01" min="0" class="bp-form-control bp-form-control-sm text-center" value="' +
                        escAttr(cost) + '"></td>' +
                        '<td><input type="number" name="variants[' + idx +
                        '][sell_price]" data-field="sell_price" step="0.01" min="0" class="bp-form-control bp-form-control-sm text-center" value="' +
                        escAttr(sell) + '"></td>' +
                        stockCell +
                        '<td class="text-center">' +
                        '<label class="bp-radio" title="Mark as default">' +
                        '<input type="radio" name="default_variant_index" value="' + idx +
                        '" data-combo-key="' + comboKey + '" class="default-variant-radio"' + (isDefault ?
                            ' checked' : '') + '>' +
                        '<span class="bp-radio-dot"></span>' +
                        '</label>' +
                        '</td>' +
                        '<td class="text-center">' +
                        '<input type="hidden" name="variants[' + idx + '][is_active]" value="0">' +
                        '<label class="bp-check">' +
                        '<input type="checkbox" data-field="is_active" name="variants[' + idx +
                        '][is_active]" value="1"' + (active ? ' checked' : '') + '>' +
                        '<span class="bp-check-box"></span>' +
                        '</label>' +
                        '</td>' +
                        '</tr>';
                    tbody.insertAdjacentHTML('beforeend', row);
                });

                // If the previously-default combo no longer exists, the first row's radio handles it via initial checked state above.
                // Sync state for next render.
                var checkedRadio = tbody.querySelector('.default-variant-radio:checked');
                defaultComboKey = checkedRadio ? checkedRadio.dataset.comboKey : null;

                document.getElementById('variantMatrixCountBadge').textContent = combos.length + ' Variant' + (combos
                    .length === 1 ? '' : 's');
                card.hidden = false;
            }

            // Re-default cost/sell columns when parent product cost/sell changes
            ['costPrice', 'sellPrice', 'productSku'].forEach(function(id) {
                document.getElementById(id).addEventListener('input', function() {
                    if (!document.getElementById('variantMatrixCard').hidden) buildVariantMatrix();
                });
            });

            $(document).on('click', '.remove-variant-attr', function() {
                var attrId = $(this).data('attr-id');
                delete selectedAttrs[attrId];
                renderSelectedAttrs();
                if (Object.keys(selectedAttrs).length === 0) {
                    document.getElementById('variantAttrsEmpty').classList.remove('d-none');
                }
                // Re-add removed attribute to select
                attrsLoaded = false;
                document.getElementById('loadVariantAttrsBtn').click();
            });

            // Run initial calculations
            updateSimpleStockTotals();
            updateProfit();

        })();
    </script>
@endpush
