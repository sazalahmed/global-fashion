@php $c = $coupon ?? null; @endphp

<div class="row g-4">
    <!-- Left Column: Main Fields -->
    <div class="col-xl-8">

        <!-- Coupon Details -->
        <div class="bp-card mb-4">
            <div class="bp-card-header">
                <h5 class="bp-card-title"><i class="fa-solid fa-ticket me-2"></i>Coupon Details</h5>
            </div>
            <div class="bp-card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="bp-form-label">Coupon Code *</label>
                        <div class="d-flex gap-2">
                            <input type="text" class="bp-form-control" name="code" id="couponCode"
                                value="{{ old('code', $c?->code) }}" placeholder="e.g. WELCOME10" required
                                style="text-transform: uppercase;">
                            <button type="button" class="bp-btn bp-btn-outline" id="btnGenerateCode"
                                title="Auto Generate">
                                <i class="fa-solid fa-wand-magic-sparkles"></i>
                            </button>
                        </div>
                        @error('code')
                            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="bp-form-label">Description *</label>
                        <input type="text" class="bp-form-control" name="name"
                            value="{{ old('name', $c?->name) }}" placeholder="e.g. 10% off for new customers" required>
                        @error('name')
                            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="bp-form-label">Discount Type *</label>
                        <select class="bp-form-select w-100" name="type" id="discountType" required>
                            <option value="">Select Type</option>
                            <option value="percentage" {{ old('type', $c?->type) == 'percentage' ? 'selected' : '' }}>
                                Percentage (%)</option>
                            <option value="fixed" {{ old('type', $c?->type) == 'fixed' ? 'selected' : '' }}>Fixed
                                Amount ({{ currency_symbol() }})</option>
                        </select>
                        @error('type')
                            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4" id="discountValueGroup">
                        <label class="bp-form-label">Discount Value *</label>
                        <div class="input-group">
                            <input type="number" class="bp-form-control" name="value" id="discountValue"
                                value="{{ old('value', num_input($c?->value)) }}" placeholder="0" min="0" step="0.01"
                                required>
                            <span class="input-group-text" id="discountSuffix">%</span>
                        </div>
                        @error('value')
                            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="bp-form-label">Maximum Discount Cap</label>
                        <div class="input-group">
                            <span class="input-group-text">{{ currency_symbol() }}</span>
                            <input type="number" class="bp-form-control" name="max_discount_amount"
                                value="{{ old('max_discount_amount', num_input($c?->max_discount_amount)) }}"
                                placeholder="e.g. 500" min="0">
                        </div>
                        <div class="fs-11 text-muted mt-1">Leave empty for no cap</div>
                        @error('max_discount_amount')
                            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Conditions -->
        <div class="bp-card mb-4">
            <div class="bp-card-header">
                <h5 class="bp-card-title"><i class="fa-solid fa-sliders me-2"></i>Conditions</h5>
            </div>
            <div class="bp-card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="bp-form-label">Minimum Order Amount</label>
                        <div class="input-group">
                            <span class="input-group-text">{{ currency_symbol() }}</span>
                            <input type="number" class="bp-form-control" name="min_order_amount"
                                value="{{ old('min_order_amount', num_input($c?->min_order_amount)) }}" placeholder="e.g. 1000"
                                min="0">
                        </div>
                        @error('min_order_amount')
                            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="bp-form-label">Total Usage Limit</label>
                        <input type="number" class="bp-form-control" name="usage_limit"
                            value="{{ old('usage_limit', $c?->usage_limit) }}"
                            placeholder="e.g. 500 (empty = unlimited)" min="1">
                        @error('usage_limit')
                            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="bp-form-label">Usage Limit Per Customer</label>
                        <input type="number" class="bp-form-control" name="per_customer_limit"
                            value="{{ old('per_customer_limit', $c?->per_customer_limit ?? 1) }}" placeholder="e.g. 1"
                            min="1">
                        @error('per_customer_limit')
                            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="bp-form-label">Valid From *</label>
                        <input type="date" class="bp-form-control" name="start_date"
                            value="{{ old('start_date', $c?->start_date?->format('Y-m-d') ?? date('Y-m-d')) }}"
                            required>
                        @error('start_date')
                            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="bp-form-label">Valid To *</label>
                        <input type="date" class="bp-form-control" name="end_date"
                            value="{{ old('end_date', $c?->end_date?->format('Y-m-d') ?? date('Y-m-d', strtotime('+30 days'))) }}"
                            required>
                        @error('end_date')
                            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Applicable Products/Categories -->
        <div class="bp-card mb-4">
            <div class="bp-card-header">
                <h5 class="bp-card-title"><i class="fa-solid fa-filter me-2"></i>Applicable To</h5>
            </div>
            <div class="bp-card-body">
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="bp-form-label">Apply To</label>
                        <select class="bp-form-select w-100" name="applies_to" id="appliesTo">
                            <option value="all" {{ old('applies_to', 'all') == 'all' ? 'selected' : '' }}>All
                                Products</option>
                            <option value="categories" {{ old('applies_to') == 'categories' ? 'selected' : '' }}>
                                Specific Categories</option>
                            <option value="products" {{ old('applies_to') == 'products' ? 'selected' : '' }}>Specific
                                Products</option>
                        </select>
                    </div>

                    <div class="col-12" id="categoriesGroup" style="display: none;">
                        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                            <label class="bp-form-label mb-0">
                                Select Categories *
                                <span class="bp-badge bp-badge-primary ms-2" id="couponCategoryCount">0
                                    selected</span>
                            </label>
                            <div class="d-flex gap-2 flex-wrap">
                                <div class="bp-table-search">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                    <input type="text" id="couponCategorySearch"
                                        placeholder="Search categories...">
                                </div>
                                <button type="button" class="bp-btn bp-btn-sm bp-btn-outline"
                                    id="couponCategoryClear">Clear all</button>
                            </div>
                        </div>
                        <div class="bp-card p-0">
                            <div class="bp-table-wrapper" style="max-height: 360px; overflow-y: auto;">
                                <table class="bp-table mb-0">
                                    <thead style="position: sticky; top: 0; z-index: 1;">
                                        <tr>
                                            <th style="width: 40px;">
                                                <input type="checkbox" class="form-check-input"
                                                    id="couponCategorySelectAll" title="Select visible">
                                            </th>
                                            <th>Category</th>
                                            <th class="text-end">Products</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $preCats = old('categories', $selectedCategoryIds ?? []); @endphp
                                        @forelse($categories as $cat)
                                            <tr class="coupon-category-row"
                                                data-search="{{ strtolower($cat->name) }}">
                                                <td>
                                                    <input type="checkbox" class="form-check-input coupon-category-cb"
                                                        name="categories[]" value="{{ $cat->id }}"
                                                        id="couponCategoryCb-{{ $cat->id }}"
                                                        {{ in_array($cat->id, $preCats) ? 'checked' : '' }}>
                                                </td>
                                                <td>
                                                    <label for="couponCategoryCb-{{ $cat->id }}"
                                                        class="fw-600 mb-0"
                                                        style="cursor:pointer;">{{ $cat->name }}</label>
                                                </td>
                                                <td class="text-end fs-12 text-muted">
                                                    {{ number_format($cat->products_count ?? 0) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-center text-muted py-4">No categories
                                                    yet.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-12" id="productsGroup" style="display: none;">
                        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                            <label class="bp-form-label mb-0">
                                Select Products *
                                <span class="bp-badge bp-badge-primary ms-2" id="couponProductCount">0 selected</span>
                            </label>
                            <div class="d-flex gap-2 flex-wrap">
                                <div class="bp-table-search">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                    <input type="text" id="couponProductSearch"
                                        placeholder="Search by name, SKU, or category...">
                                </div>
                                <button type="button" class="bp-btn bp-btn-sm bp-btn-outline"
                                    id="couponProductClear">Clear all</button>
                            </div>
                        </div>
                        <div class="bp-card p-0">
                            <div class="bp-table-wrapper" style="max-height: 360px; overflow-y: auto;">
                                <table class="bp-table mb-0">
                                    <thead style="position: sticky; top: 0; z-index: 1;">
                                        <tr>
                                            <th style="width: 40px;">
                                                <input type="checkbox" class="form-check-input"
                                                    id="couponProductSelectAll" title="Select visible">
                                            </th>
                                            <th>Product</th>
                                            <th>SKU</th>
                                            <th>Category</th>
                                            <th class="text-end">Price</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $preProds = old('products', $selectedProductIds ?? []); @endphp
                                        @forelse($products as $p)
                                            <tr class="coupon-product-row"
                                                data-search="{{ strtolower($p->name . ' ' . $p->sku . ' ' . ($p->category?->name ?? '')) }}">
                                                <td>
                                                    <input type="checkbox" class="form-check-input coupon-product-cb"
                                                        name="products[]" value="{{ $p->id }}"
                                                        id="couponProductCb-{{ $p->id }}"
                                                        {{ in_array($p->id, $preProds) ? 'checked' : '' }}>
                                                </td>
                                                <td>
                                                    <label for="couponProductCb-{{ $p->id }}"
                                                        class="fw-600 mb-0"
                                                        style="cursor:pointer;">{{ $p->name }}</label>
                                                </td>
                                                <td class="fs-12 text-muted">{{ $p->sku ?: '—' }}</td>
                                                <td class="fs-12">{{ $p->category?->name ?: '—' }}</td>
                                                <td class="text-end fw-700">{{ currency_symbol() }}
                                                    {{ number_format($p->sell_price, 0) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-4">No products
                                                    yet.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>

    <!-- Right Column: Summary & Status -->
    <div class="col-xl-4">

        <!-- Status -->
        <div class="bp-card mb-4">
            <div class="bp-card-header">
                <h5 class="bp-card-title"><i class="fa-solid fa-toggle-on me-2"></i>Status</h5>
            </div>
            <div class="bp-card-body">
                <div class="mb-3">
                    <label class="bp-form-label">Coupon Status *</label>
                    @php $activeVal = old('is_active', $c?->is_active === false ? '0' : '1'); @endphp
                    <select class="bp-form-select w-100" name="is_active" required>
                        <option value="1" {{ (string) $activeVal === '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ (string) $activeVal === '0' ? 'selected' : '' }}>Disabled</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Preview -->
        <div class="bp-card mb-4">
            <div class="bp-card-header">
                <h5 class="bp-card-title"><i class="fa-solid fa-eye me-2"></i>Coupon Preview</h5>
            </div>
            <div class="bp-card-body">
                <div class="bp-coupon-preview text-center p-3">
                    <div class="fs-11 text-muted mb-1">COUPON CODE</div>
                    <div class="fw-800 fs-18 mb-2" id="previewCode">XXXXXXXX</div>
                    <div class="fs-13 text-muted mb-2" id="previewDescription">Coupon description</div>
                    <div class="fw-700 fs-16" id="previewDiscount">--</div>
                    <div class="fs-11 text-muted mt-2" id="previewValidity">Valid: -- to --</div>
                </div>
            </div>
        </div>

        <!-- Save Actions -->
        <div class="bp-card">
            <div class="bp-card-body d-flex flex-column gap-2">
                <button type="submit" class="bp-btn bp-btn-success w-100 justify-content-center">
                    <i class="fa-solid fa-save me-2"></i> {{ $submitLabel ?? 'Save Coupon' }}
                </button>
                <a href="{{ route('ecommerce.coupons') }}" class="bp-btn bp-btn-danger w-100 justify-content-center">
                    <i class="fa-solid fa-times me-2"></i> Cancel
                </a>
            </div>
        </div>

    </div>
</div>
