@extends('core::layouts.master')

@section('title', __('Edit Combo — Website'))
@section('page-title', __('Edit Combo'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('ecommerce.index') }}">Website</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('products.index') }}">Combo Packages</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Edit</span>
@endsection

@section('page-actions')
    <a href="{{ route('products.index') }}" class="bp-btn bp-btn-primary">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Combos
    </a>
@endsection

@section('content')

    @php
        $placeholder = asset('website/assets/images/product_placeholder.png');

        $catalog = $products
            ->map(function ($p) use ($placeholder) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'sku' => $p->sku ?? '',
                    'price' => (float) ($p->sell_price ?? 0),
                    // Drives the "auto-select category when a product is chosen" behaviour.
                    'category_id' => $p->category_id,
                    'variants' => $p->variants
                        ->map(function ($v) {
                            $label = $v->variant_name;
                            return [
                                'id' => $v->id,
                                'label' => $label !== '' ? $label : $v->sku ?? '#' . $v->id,
                                'price' => (float) $v->effective_sell_price,
                            ];
                        })
                        ->values(),
                    'images' => $p->images
                        ->map(function ($img) use ($placeholder) {
                            return [
                                'path' => $img->image_path,
                                'url' => upload_url($img->image_path, $placeholder),
                                'alt' => $img->alt_text ?? '',
                            ];
                        })
                        ->values(),
                ];
            })
            ->values();

        // Pre-fill component rows from the saved combo (or repopulate from old() on failure).
        if (old('items') !== null) {
            $initialItems = array_values(old('items'));
        } else {
            $initialItems = $combo->items
                ->map(
                    fn($it) => [
                        'product_id' => $it->product_id,
                        'variant_id' => $it->variant_id,
                        'quantity' => $it->quantity,
                    ],
                )
                ->values();
        }

        if (old('gallery') !== null) {
            $checkedGallery = array_values(old('gallery'));
        } else {
            $checkedGallery = $combo->galleryImages->pluck('image_path')->values();
        }

        $thumbUrl = upload_url($combo->thumbnail, $placeholder);
    @endphp

    <form action="{{ route('products.combos.update', $combo) }}" method="POST" enctype="multipart/form-data"
        id="comboForm">
        @csrf
        @method('PUT')

        <div class="row g-4">
            <!-- Left Column -->
            <div class="col-xl-8">

                <div class="bp-card mb-4">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-box-open me-2"></i>Combo Details</h5>
                    </div>
                    <div class="bp-card-body">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="bp-form-label">Name *</label>
                                <input type="text" class="bp-form-control" name="name"
                                    value="{{ old('name', $combo->name) }}" required>
                                @error('name')
                                    <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="bp-form-label">Sort Order</label>
                                <input type="number" class="bp-form-control" name="sort_order"
                                    value="{{ old('sort_order', $combo->sort_order) }}" min="0">
                                @error('sort_order')
                                    <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <label class="bp-form-label">Description</label>
                                <textarea class="bp-form-control bp-richtext" name="description" rows="3">{{ old('description', $combo->description) }}</textarea>
                                @error('description')
                                    <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <x-core::image-upload name="thumbnail" label="Thumbnail" :current="$combo->thumbnail ? upload_url($combo->thumbnail) : null"
                                    accept="image/png,image/jpeg,image/webp"
                                    hint="Leave empty to keep the current thumbnail. JPG, PNG or WEBP, max 2MB." />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bp-card mb-4">
                    <div class="bp-card-header d-flex justify-content-between align-items-center">
                        <h5 class="bp-card-title"><i class="fa-solid fa-layer-group me-2"></i>Products</h5>
                        <button type="button" class="bp-btn bp-btn-sm bp-btn-success" id="addComponent">
                            <i class="fa-solid fa-plus me-1"></i> Add Product
                        </button>
                    </div>
                    <div class="bp-card-body">
                        @error('items')
                            <div class="text-danger fs-12 mb-2">{{ $message }}</div>
                        @enderror
                        @error('items.*.product_id')
                            <div class="text-danger fs-12 mb-2">{{ $message }}</div>
                        @enderror
                        @error('items.*.quantity')
                            <div class="text-danger fs-12 mb-2">{{ $message }}</div>
                        @enderror
                        <div class="bp-table-wrapper">
                            <table class="bp-table">
                                <thead>
                                    <tr>
                                        <th>Product *</th>
                                        <th width="110">Qty *</th>
                                        <th width="60">Remove</th>
                                    </tr>
                                </thead>
                                <tbody id="componentsBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="bp-card mb-4">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-images me-2"></i>Gallery</h5>
                    </div>
                    <div class="bp-card-body">
                        <div class="fs-12 text-muted mb-2">Pick images from the selected components to show on the combo
                            page.</div>
                        <div id="galleryGrid" class="bp-combo-gallery">
                            <div class="text-center text-muted py-3" id="noGalleryMsg">Select components to choose gallery
                                images.</div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right Column -->
            <div class="col-xl-4">

                <div class="bp-card mb-4">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-bangladeshi-taka-sign me-2"></i>Pricing</h5>
                    </div>
                    <div class="bp-card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="bp-form-label">Combo Price ({{ currency_symbol() }}) *</label>
                                <input type="number" class="bp-form-control" name="combo_price" id="comboPrice"
                                    value="{{ old('combo_price', num_input($combo->combo_price)) }}" min="0" step="0.01"
                                    required>
                                @error('combo_price')
                                    <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="bp-combo-preview mt-3">
                            <div class="d-flex justify-content-between fs-13 mb-1">
                                <span class="text-muted">Summed price</span>
                                <span id="prevSummed">{{ currency_symbol() }} 0.00</span>
                            </div>
                            <div class="d-flex justify-content-between fw-700 mb-1">
                                <span>Combo price</span>
                                <span id="prevEffective">{{ currency_symbol() }} 0.00</span>
                            </div>
                            <div class="d-flex justify-content-between fs-12 bp-combo-save" id="prevSaveRow">
                                <span>You save</span>
                                <span id="prevSave">{{ currency_symbol() }} 0.00 (0%)</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bp-card mb-4">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-eye me-2"></i>Visibility</h5>
                    </div>
                    <div class="bp-card-body">
                        <label class="form-check form-switch mb-0">
                            <input type="hidden" name="is_active" value="0">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                {{ old('is_active', $combo->is_active) ? 'checked' : '' }}>
                            <span class="fs-13 fw-600 ms-1">Active</span>
                        </label>
                        <label class="form-check form-switch mb-0 mt-2">
                            <input type="hidden" name="size_required" value="0">
                            <input class="form-check-input" type="checkbox" name="size_required" value="1"
                                {{ old('size_required', $combo->size_required) ? 'checked' : '' }}>
                            <span class="fs-13 fw-600 ms-1">Let customer choose size</span>
                        </label>
                        <div class="fs-11 text-muted mt-2"><i class="fa-solid fa-circle-info me-1"></i> Offers the
                            sizes shared by all selected products; customer picks one on the combo page.</div>
                    </div>
                </div>

                @include('ecommerce::combos._categories-card', [
                    'categories' => $categories,
                    'selectedCategoryIds' => old('categories', $combo->categories->pluck('id')->all()),
                ])

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('products.index') }}" class="bp-btn bp-btn-danger justify-content-center">
                        <i class="fa-solid fa-times me-2"></i> Cancel
                    </a>
                    <button type="submit" class="bp-btn bp-btn-success justify-content-center">
                        <i class="fa-solid fa-save me-2"></i> Update Combo
                    </button>
                </div>

            </div>
        </div>

    </form>

@endsection

@push('scripts')
    <script>
        'use strict';

        $(function() {
            var catalog = @json($catalog);
            var oldItems = @json($initialItems);
            var oldGallery = @json($checkedGallery);
            var placeholder = '{{ $placeholder }}';
            var currency = '{{ currency_symbol() }}';

            @include('ecommerce::combos._combo-form-js')

            bootstrapComboForm(catalog, oldItems, oldGallery, placeholder, currency);
        });
    </script>
@endpush
