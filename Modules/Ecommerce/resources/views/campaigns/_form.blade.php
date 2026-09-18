@php
    $c = $campaign ?? null;
    $selCats = $selectedCategoryIds ?? [];
    $selProds = $selectedProductIds ?? [];
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label class="bp-form-label">{{ __('Campaign Name') }} *</label>
        <input type="text" name="name" class="bp-form-control" required maxlength="150"
            value="{{ old('name', $c?->name) }}" placeholder="{{ __('e.g. Black Friday Sale') }}">
        @error('name')
            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6">
        <label class="bp-form-label">{{ __('Slug') }} *</label>
        <input type="text" name="slug" id="campaignSlug" class="bp-form-control" required maxlength="150"
            value="{{ old('slug', $c?->slug) }}" placeholder="black-friday-2026">
        <div class="fs-11 text-muted mt-1">{{ __('Lowercase letters, numbers, and hyphens only.') }}</div>
        @error('slug')
            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-12">
        <label class="bp-form-label">{{ __('Description') }}</label>
        <textarea name="description" class="bp-form-control" rows="2" maxlength="2000"
            placeholder="{{ __('Internal note about this campaign...') }}">{{ old('description', $c?->description) }}</textarea>
    </div>
    <div class="col-md-6 col-lg-4">
        <label class="bp-form-label">{{ __('Starts At') }} *</label>
        <input type="datetime-local" name="starts_at" class="bp-form-control" required
            value="{{ old('starts_at', $c?->starts_at?->format('Y-m-d H:i')) }}">
        @error('starts_at')
            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6 col-lg-4">
        <label class="bp-form-label">{{ __('Ends At') }} *</label>
        <input type="datetime-local" name="ends_at" class="bp-form-control" required
            value="{{ old('ends_at', $c?->ends_at?->format('Y-m-d H:i')) }}">
        @error('ends_at')
            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6 col-lg-4">
        <label class="bp-form-label">{{ __('Discount Type') }} *</label>
        <select name="discount_type" id="campaignDiscountType" class="bp-form-select w-100" required>
            <option value="percentage"
                {{ old('discount_type', $c?->discount_type) === 'percentage' ? 'selected' : '' }}>
                {{ __('Percentage (%)') }}
            </option>
            <option value="flat" {{ old('discount_type', $c?->discount_type) === 'flat' ? 'selected' : '' }}>
                {{ __('Flat (BDT)') }}
            </option>
        </select>
    </div>
    <div class="col-md-6 col-lg-4">
        <label class="bp-form-label">{{ __('Discount Value') }} *</label>
        <input type="number" name="discount_value" class="bp-form-control" required min="0" step="0.01"
            value="{{ old('discount_value', num_input($c?->discount_value)) }}">
        @error('discount_value')
            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6 col-lg-4">
        <label class="bp-form-label">{{ __('Priority') }}</label>
        <input type="number" name="priority" class="bp-form-control" min="0" max="1000"
            value="{{ old('priority', $c?->priority ?? 0) }}">
        <div class="fs-11 text-muted mt-1">{{ __('Higher wins same-scope ties (default 0).') }}</div>
    </div>
    <div class="col-md-6 col-lg-4">
        <label class="bp-form-label">{{ __('Badge Label') }}</label>
        <input type="text" name="badge_label" class="bp-form-control" maxlength="50"
            value="{{ old('badge_label', $c?->badge_label) }}" placeholder="{{ __('Black Friday') }}">
        <div class="fs-11 text-muted mt-1">{{ __('Optional. Shows on the storefront product card.') }}</div>
    </div>

    <div class="col-md-6 col-lg-4">
        <label class="bp-form-label">{{ __('Apply Discount To') }} *</label>
        <div class="d-flex gap-3 flex-wrap">
            @php $scope = old('scope', $c?->scope ?? 'all'); @endphp
            <div class="form-check">
                <input class="form-check-input campaign-scope-radio" type="radio" name="scope" id="scopeAll"
                    value="all" {{ $scope === 'all' ? 'checked' : '' }}>
                <label class="form-check-label" for="scopeAll">{{ __('All products') }}</label>
            </div>
            <div class="form-check">
                <input class="form-check-input campaign-scope-radio" type="radio" name="scope" id="scopeCategories"
                    value="categories" {{ $scope === 'categories' ? 'checked' : '' }}>
                <label class="form-check-label" for="scopeCategories">{{ __('Selected categories') }}</label>
            </div>
            <div class="form-check">
                <input class="form-check-input campaign-scope-radio" type="radio" name="scope" id="scopeProducts"
                    value="products" {{ $scope === 'products' ? 'checked' : '' }}>
                <label class="form-check-label" for="scopeProducts">{{ __('Specific products') }}</label>
            </div>
        </div>
    </div>
    <div class="col-12 campaign-scope-pane" data-pane="categories">
        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
            <label class="bp-form-label mb-0">
                {{ __('Categories') }} *
                <span class="bp-badge bp-badge-primary ms-2" id="campaignCategoryCount">0 {{ __('selected') }}</span>
            </label>
            <div class="d-flex gap-2 flex-wrap">
                <div class="bp-table-search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="campaignCategorySearch"
                        placeholder="{{ __('Search categories...') }}">
                </div>
                <button type="button" class="bp-btn bp-btn-sm bp-btn-outline" id="campaignCategoryClear">
                    {{ __('Clear all') }}
                </button>
            </div>
        </div>

        <div class="bp-card p-0">
            <div class="bp-table-wrapper" style="max-height: 420px; overflow-y: auto;">
                <table class="bp-table mb-0" id="campaignCategoryTable">
                    <thead style="position: sticky; top: 0; z-index: 1;">
                        <tr>
                            <th style="width: 40px;">
                                <input type="checkbox" class="form-check-input" id="campaignCategorySelectAll"
                                    title="{{ __('Select visible') }}">
                            </th>
                            <th>{{ __('Category') }}</th>
                            <th class="text-end">{{ __('Products') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $preselectedCats = old('category_ids', $selCats); @endphp
                        @forelse($categories as $cat)
                            <tr class="campaign-category-row" data-search="{{ strtolower($cat->name) }}">
                                <td>
                                    <input type="checkbox" class="form-check-input campaign-category-cb"
                                        name="category_ids[]" value="{{ $cat->id }}"
                                        id="campaignCategoryCb-{{ $cat->id }}"
                                        {{ in_array($cat->id, $preselectedCats) ? 'checked' : '' }}>
                                </td>
                                <td>
                                    <label for="campaignCategoryCb-{{ $cat->id }}" class="fw-600 mb-0"
                                        style="cursor:pointer;">
                                        {{ $cat->name }}
                                    </label>
                                </td>
                                <td class="text-end fs-12 text-muted">{{ number_format($cat->products_count ?? 0) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">{{ __('No categories yet.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="fs-11 text-muted mt-1">
            {{ __('All products in the selected categories will inherit this campaign discount.') }}
        </div>
    </div>
    <div class="col-12 campaign-scope-pane" data-pane="products">
        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
            <label class="bp-form-label mb-0">
                {{ __('Products') }} *
                <span class="bp-badge bp-badge-primary ms-2" id="campaignProductCount">0 {{ __('selected') }}</span>
            </label>
            <div class="d-flex gap-2 flex-wrap">
                <div class="bp-table-search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="campaignProductSearch"
                        placeholder="{{ __('Search by name, SKU, or category...') }}">
                </div>
                <button type="button" class="bp-btn bp-btn-sm bp-btn-outline" id="campaignProductClear">
                    {{ __('Clear all') }}
                </button>
            </div>
        </div>

        <div class="bp-card p-0">
            <div class="bp-table-wrapper" style="max-height: 420px; overflow-y: auto;">
                <table class="bp-table mb-0" id="campaignProductTable">
                    <thead style="position: sticky; top: 0; z-index: 1;">
                        <tr>
                            <th style="width: 40px;">
                                <input type="checkbox" class="form-check-input" id="campaignProductSelectAll"
                                    title="{{ __('Select visible') }}">
                            </th>
                            <th>{{ __('Product') }}</th>
                            <th>{{ __('SKU') }}</th>
                            <th>{{ __('Category') }}</th>
                            <th class="text-end">{{ __('Price') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $preselected = old('product_ids', $selProds); @endphp
                        @forelse($products as $p)
                            <tr class="campaign-product-row"
                                data-search="{{ strtolower($p->name . ' ' . $p->sku . ' ' . ($p->category?->name ?? '')) }}">
                                <td>
                                    <input type="checkbox" class="form-check-input campaign-product-cb"
                                        name="product_ids[]" value="{{ $p->id }}"
                                        id="campaignProductCb-{{ $p->id }}"
                                        {{ in_array($p->id, $preselected) ? 'checked' : '' }}>
                                </td>
                                <td>
                                    <label for="campaignProductCb-{{ $p->id }}" class="fw-600 mb-0"
                                        style="cursor:pointer;">
                                        {{ $p->name }}
                                    </label>
                                </td>
                                <td class="fs-12 text-muted">{{ $p->sku ?: '—' }}</td>
                                <td class="fs-12">{{ $p->category?->name ?: '—' }}</td>
                                <td class="text-end fw-700">{{ currency_symbol() }}
                                    {{ number_format($p->sell_price, 0) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">{{ __('No products yet.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="fs-11 text-muted mt-1">
            {{ __('Tick the rows you want this campaign to apply to. Use the header checkbox to toggle every visible row.') }}
        </div>
    </div>
    <div class="col-md-6 ol-lg-4 d-flex align-items-end">
        <div class="form-check form-switch">
            <input type="hidden" name="is_active" value="0">
            <input class="form-check-input" type="checkbox" name="is_active" id="campaignActive" value="1"
                {{ old('is_active', $c ? $c->is_active : true) ? 'checked' : '' }}>
            <label class="form-check-label" for="campaignActive">{{ __('Active') }}</label>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        'use strict';
        $(function() {
            function applyScope() {
                var scope = $('input[name="scope"]:checked').val();
                $('.campaign-scope-pane').each(function() {
                    var pane = $(this).data('pane');
                    $(this).toggle(scope === pane);
                });
            }
            $('input[name="scope"]').on('change', applyScope);
            applyScope();

            // ── Generic table-picker wiring (used by products + categories) ──
            // Each picker exposes the same shape via a config object so we don't
            // duplicate handler code per scope.
            function wireTablePicker(opts) {
                function refresh() {
                    var n = $(opts.cbSelector + ':checked').length;
                    $(opts.countSelector).text(n + ' {{ __('selected') }}');
                }
                refresh();

                $(opts.searchSelector).on('input', function() {
                    var q = $(this).val().toLowerCase().trim();
                    $(opts.rowSelector).each(function() {
                        var match = q === '' || $(this).data('search').toString().indexOf(q) !== -1;
                        $(this).toggle(match);
                    });
                    $(opts.selectAllSelector).prop('checked', false);
                });

                $(opts.selectAllSelector).on('change', function() {
                    var on = $(this).prop('checked');
                    $(opts.rowSelector + ':visible ' + opts.cbSelector).prop('checked', on);
                    refresh();
                });

                $(opts.clearSelector).on('click', function() {
                    $(opts.cbSelector).prop('checked', false);
                    $(opts.selectAllSelector).prop('checked', false);
                    refresh();
                });

                $(document).on('change', opts.cbSelector, refresh);
            }

            wireTablePicker({
                cbSelector: '.campaign-product-cb',
                rowSelector: '.campaign-product-row',
                countSelector: '#campaignProductCount',
                searchSelector: '#campaignProductSearch',
                selectAllSelector: '#campaignProductSelectAll',
                clearSelector: '#campaignProductClear',
            });

            wireTablePicker({
                cbSelector: '.campaign-category-cb',
                rowSelector: '.campaign-category-row',
                countSelector: '#campaignCategoryCount',
                searchSelector: '#campaignCategorySearch',
                selectAllSelector: '#campaignCategorySelectAll',
                clearSelector: '#campaignCategoryClear',
            });

            // Auto-slugify the name into the slug field on create (when slug is empty).
            $('input[name="name"]').on('input', function() {
                var $slug = $('#campaignSlug');
                if ($slug.data('user-touched')) return;
                var v = $(this).val().toLowerCase()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '');
                $slug.val(v);
            });
            $('#campaignSlug').on('input', function() {
                $(this).data('user-touched', true);
            });
            @if ($c)
                $('#campaignSlug').data('user-touched', true);
            @endif
        });
    </script>
@endpush
