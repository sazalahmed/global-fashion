@extends('core::layouts.master')

@section('title', __('Section Products & Combos — :title', ['title' => $section->title]))
@section('page-title', __('Section Products & Combos — :title', ['title' => $section->title]))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('ecommerce.index') }}">Website</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('ecommerce.homepage-sections') }}">Manage Sections</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>{{ $section->title }}</span>
@endsection

@section('content')

    @php
        $placeholder = asset('website/assets/images/product_placeholder.png');
        // Thumbnail Override is only offered for the Best Selling section.
        $supportsThumbnail = $section->section_type === 'best_selling';
    @endphp

    <form action="{{ route('ecommerce.homepage-sections.products.update', $section) }}" method="POST"
        enctype="multipart/form-data" id="sectionProductsForm">
        @csrf

        <div class="row g-4">
            <div class="col-xl-8">

                <div class="bp-card mb-4">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-magnifying-glass me-2"></i>Search &amp; Add
                            Products or Combos</h5>
                    </div>
                    <div class="bp-card-body">
                        @include('core::partials.product-search', [
                            'id' => 'productSearch',
                            'label' => false,
                            'placeholder' => __('Search by name or SKU...'),
                        ])
                    </div>
                </div>

                <div class="bp-card">
                    <div class="bp-card-header d-flex justify-content-between align-items-center">
                        <h5 class="bp-card-title"><i class="fa-solid fa-boxes-stacked me-2"></i>Curated Products &amp;
                            Combos</h5>
                        <div class="d-flex align-items-center gap-2">
                            <span class="fs-12 text-muted"><i class="fa-solid fa-arrows-up-down me-1"></i>Drag to
                                reorder</span>
                            <span class="bp-badge bp-badge-primary"
                                id="productCountBadge">{{ $section->products->count() + $section->combos->count() }}</span>
                        </div>
                    </div>
                    <div class="bp-card-body">
                        @error('items')
                            <div class="text-danger fs-12 mb-2">{{ $message }}</div>
                        @enderror

                        @php
                            $curatedCount = $section->products->count() + $section->combos->count();
                        @endphp
                        <div class="text-center text-muted py-3 {{ $curatedCount ? 'd-none' : '' }}"
                            id="noProductsMsg">
                            {{ __('Nothing picked yet — this section falls back to its automatic list.') }}
                        </div>

                        <div class="bp-table-wrapper {{ $curatedCount ? '' : 'd-none' }}"
                            id="sectionProductsTable">
                            <table class="bp-table">
                                <thead>
                                    <tr>
                                        <th class="bp-reorder-col" title="Drag to reorder">&nbsp;</th>
                                        <th>Item</th>
                                        <th width="90">Type</th>
                                        @if ($supportsThumbnail)
                                            <th width="240">Thumbnail Override</th>
                                        @endif
                                        <th width="60">Remove</th>
                                    </tr>
                                </thead>
                                <tbody id="sectionProductsBody">
                                    @foreach ($section->products as $i => $product)
                                        @php
                                            $override = $product->pivot->thumbnail;
                                            // Resolve like the storefront: override > product primary image > thumbnail > placeholder.
                                            $previewUrl = upload_url(
                                                $override ?: ($product->image ?: $product->thumbnail),
                                                $placeholder,
                                            );
                                        @endphp
                                        <tr data-type="product" data-item-id="{{ $product->id }}" draggable="true">
                                            <td class="bp-drag-handle" title="Drag to reorder"><i
                                                    class="fa-solid fa-grip-vertical"></i></td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <img src="{{ $previewUrl }}" alt="" class="product-thumb"
                                                        onerror="this.onerror=null;this.src='{{ $placeholder }}'">
                                                    <div>
                                                        <div class="fw-700 fs-13">{{ $product->name }}</div>
                                                        <div class="fs-11 text-muted">{{ $product->sku }}</div>
                                                    </div>
                                                </div>
                                                <input type="hidden" name="items[{{ $i }}][type]" value="product">
                                                <input type="hidden" name="items[{{ $i }}][id]"
                                                    value="{{ $product->id }}">
                                                <input type="hidden"
                                                    name="items[{{ $i }}][existing_thumbnail]"
                                                    value="{{ $override }}">
                                                <input type="hidden" class="row-sort-order"
                                                    name="items[{{ $i }}][sort_order]"
                                                    value="{{ $i }}">
                                            </td>
                                            <td><span class="bp-badge bp-badge-primary">Product</span></td>
                                            @if ($supportsThumbnail)
                                                <td>
                                                    <input type="file"
                                                        class="bp-form-control bp-form-control-sm js-thumb-input"
                                                        name="items[{{ $i }}][thumbnail]"
                                                        accept="image/png,image/jpeg,image/webp">
                                                    @if ($override)
                                                        <label
                                                            class="d-flex align-items-center gap-1 fs-11 text-danger mt-1 mb-0">
                                                            <input type="checkbox"
                                                                name="items[{{ $i }}][remove_thumbnail]"
                                                                value="1"> {{ __('Remove override') }}
                                                        </label>
                                                    @endif
                                                </td>
                                            @endif
                                            <td>
                                                <button type="button"
                                                    class="bp-btn bp-btn-sm bp-btn-icon bp-btn-danger remove-section-item"><i
                                                        class="fa-solid fa-times"></i></button>
                                            </td>
                                        </tr>
                                    @endforeach
                                    @foreach ($section->combos as $j => $combo)
                                        @php
                                            $i = $section->products->count() + $j;
                                        @endphp
                                        <tr data-type="combo" data-item-id="{{ $combo->id }}" draggable="true">
                                            <td class="bp-drag-handle" title="Drag to reorder"><i
                                                    class="fa-solid fa-grip-vertical"></i></td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <img src="{{ upload_url($combo->thumbnail, $placeholder) }}" alt=""
                                                        class="product-thumb"
                                                        onerror="this.onerror=null;this.src='{{ $placeholder }}'">
                                                    <div class="fw-700 fs-13">{{ $combo->name }}</div>
                                                </div>
                                                <input type="hidden" name="items[{{ $i }}][type]" value="combo">
                                                <input type="hidden" name="items[{{ $i }}][id]"
                                                    value="{{ $combo->id }}">
                                                <input type="hidden" class="row-sort-order"
                                                    name="items[{{ $i }}][sort_order]"
                                                    value="{{ $i }}">
                                            </td>
                                            <td><span class="bp-badge bp-badge-secondary">Combo</span></td>
                                            @if ($supportsThumbnail)
                                                <td class="text-muted fs-11">—</td>
                                            @endif
                                            <td>
                                                <button type="button"
                                                    class="bp-btn bp-btn-sm bp-btn-icon bp-btn-danger remove-section-item"><i
                                                        class="fa-solid fa-times"></i></button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>

            <div class="col-xl-4">
                <div class="bp-card mb-4">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-circle-info me-2"></i>How It Works</h5>
                    </div>
                    <div class="bp-card-body">
                        <ul class="list-unstyled mb-0 fs-13">
                            <li class="mb-2"><i class="fa-solid fa-hand-pointer text-muted me-2"></i>Pick the exact
                                products and/or combo packages for the <strong>{{ $section->title }}</strong> row.</li>
                            <li class="mb-2"><i
                                    class="fa-solid fa-up-down-left-right text-muted me-2"></i><strong>Drag</strong> a row
                                by its handle to set the display order — products and combos can be mixed in any order.</li>
                            @if ($supportsThumbnail)
                                <li class="mb-2"><i class="fa-solid fa-image text-muted me-2"></i>Upload a
                                    <strong>Thumbnail Override</strong> to replace the image for a product row. Leave
                                    empty to use the product's own thumbnail (not available for combos).
                                </li>
                            @endif
                            <li class="mb-0"><i class="fa-solid fa-wand-magic-sparkles text-muted me-2"></i>Leave the list
                                empty to fall back to the automatic list.</li>
                        </ul>
                    </div>
                </div>

                <div class="bp-card">
                    <div class="bp-card-body d-flex flex-column gap-2">
                        <button type="submit" class="bp-btn bp-btn-success w-100 justify-content-center">
                            <i class="fa-solid fa-save me-2"></i> {{ __('Save') }}
                        </button>
                        <a href="{{ route('ecommerce.homepage-sections') }}"
                            class="bp-btn bp-btn-danger w-100 justify-content-center">
                            <i class="fa-solid fa-times me-2"></i> {{ __('Cancel') }}
                        </a>
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
            var itemIndex = {{ $section->products->count() + $section->combos->count() }};
            var placeholder = '{{ $placeholder }}';
            var supportsThumbnail = {{ $supportsThumbnail ? 'true' : 'false' }};

            // Compound key ('product:5' / 'combo:5') — products and combos each
            // have their own id-space, so a bare id can't tell them apart.
            var selected = {};
            function keyOf(type, id) {
                return type + ':' + id;
            }
            $('#sectionProductsBody tr').each(function() {
                selected[keyOf($(this).data('type'), $(this).data('item-id'))] = true;
            });

            // ── Full catalog for the focus/click search widget — products AND
            // combos in one list, exactly like the admin Products list. Each
            // entry carries `kind` so onSelect below can tell them apart.
            var fullCatalog = [
                @foreach ($products as $p)
                    @php $imgUrl = upload_url($p->image ?: $p->thumbnail, $placeholder); @endphp {
                        kind: 'product',
                        id: {{ $p->id }},
                        name: @json($p->name),
                        sku: @json($p->sku ?? ''),
                        model: @json($p->model ?? ''),
                        type: @json($p->product_type ?? 'simple'),
                        price: {{ (float) ($p->sell_price ?? 0) }},
                        image: @json($imgUrl)
                    },
                @endforeach
                @foreach ($combos as $c)
                    @php $imgUrl = upload_url($c->thumbnail, $placeholder); @endphp {
                        kind: 'combo',
                        id: {{ $c->id }},
                        name: @json($c->name),
                        price: {{ (float) $c->combo_price }},
                        image: @json($imgUrl)
                    },
                @endforeach
            ];

            // Hide already-curated products/combos from the search dropdown.
            function availableCatalog() {
                return fullCatalog.filter(function(p) {
                    return !selected[keyOf(p.kind, p.id)];
                });
            }

            var searchApi = window.BpProductSearch.init({
                input: '#productSearch',
                catalog: availableCatalog(),
                currencySymbol: '{{ currency_symbol() }}',
                showPrice: true,
                onSelect: function(sel) {
                    if (sel.kind === 'combo') {
                        addCuratedComboRow(sel);
                    } else {
                        addCuratedProductRow(sel);
                    }
                    searchApi.setCatalog(availableCatalog());
                }
            });

            // Append a picked product to the curated list.
            function addCuratedProductRow(p) {
                if (selected[keyOf('product', p.id)]) return;
                selected[keyOf('product', p.id)] = true;

                var name = $('<span>').text(p.name || '').html();
                var sku = $('<span>').text(p.sku || '').html();
                var image = p.image || placeholder;
                var i = itemIndex;

                var row = '<tr data-type="product" data-item-id="' + p.id + '" draggable="true">' +
                    '<td class="bp-drag-handle" title="Drag to reorder"><i class="fa-solid fa-grip-vertical"></i></td>' +
                    '<td><div class="d-flex align-items-center gap-2">' +
                    '<img src="' + image + '" alt="" class="product-thumb" onerror="this.onerror=null;this.src=\'' +
                    placeholder + '\'">' +
                    '<div><div class="fw-700 fs-13">' + name + '</div><div class="fs-11 text-muted">' + sku +
                    '</div></div>' +
                    '</div>' +
                    '<input type="hidden" name="items[' + i + '][type]" value="product">' +
                    '<input type="hidden" name="items[' + i + '][id]" value="' + p.id + '">' +
                    '<input type="hidden" class="row-sort-order" name="items[' + i + '][sort_order]" value="' +
                    i + '"></td>' +
                    '<td><span class="bp-badge bp-badge-primary">Product</span></td>' +
                    (supportsThumbnail ?
                        '<td><input type="file" class="bp-form-control bp-form-control-sm js-thumb-input" name="items[' +
                        i + '][thumbnail]" accept="image/png,image/jpeg,image/webp"></td>' : '') +
                    '<td><button type="button" class="bp-btn bp-btn-sm bp-btn-icon bp-btn-danger remove-section-item"><i class="fa-solid fa-times"></i></button></td>' +
                    '</tr>';

                $('#sectionProductsBody').append(row);
                $('#noProductsMsg').addClass('d-none');
                $('#sectionProductsTable').removeClass('d-none');
                itemIndex++;
                updateCount();
            }

            // Append a picked combo to the curated list.
            function addCuratedComboRow(c) {
                if (selected[keyOf('combo', c.id)]) return;
                selected[keyOf('combo', c.id)] = true;

                var name = $('<span>').text(c.name || '').html();
                var image = c.image || placeholder;
                var i = itemIndex;
                var row = '<tr data-type="combo" data-item-id="' + c.id + '" draggable="true">' +
                    '<td class="bp-drag-handle" title="Drag to reorder"><i class="fa-solid fa-grip-vertical"></i></td>' +
                    '<td><div class="d-flex align-items-center gap-2">' +
                    '<img src="' + image + '" alt="" class="product-thumb" onerror="this.onerror=null;this.src=\'' +
                    placeholder + '\'">' +
                    '<div class="fw-700 fs-13">' + name + '</div>' +
                    '</div>' +
                    '<input type="hidden" name="items[' + i + '][type]" value="combo">' +
                    '<input type="hidden" name="items[' + i + '][id]" value="' + c.id + '">' +
                    '<input type="hidden" class="row-sort-order" name="items[' + i + '][sort_order]" value="' +
                    i + '"></td>' +
                    '<td><span class="bp-badge bp-badge-secondary">Combo</span></td>' +
                    (supportsThumbnail ? '<td class="text-muted fs-11">—</td>' : '') +
                    '<td><button type="button" class="bp-btn bp-btn-sm bp-btn-icon bp-btn-danger remove-section-item"><i class="fa-solid fa-times"></i></button></td>' +
                    '</tr>';

                $('#sectionProductsBody').append(row);
                $('#noProductsMsg').addClass('d-none');
                $('#sectionProductsTable').removeClass('d-none');
                itemIndex++;
                updateCount();
            }

            $(document).on('click', '.remove-section-item', function() {
                var row = $(this).closest('tr');
                delete selected[keyOf(row.data('type'), row.data('item-id'))];
                row.remove();
                searchApi.setCatalog(availableCatalog());
                if ($('#sectionProductsBody tr').length === 0) {
                    $('#noProductsMsg').removeClass('d-none');
                    $('#sectionProductsTable').addClass('d-none');
                }
                updateCount();
            });

            // Live-preview a chosen thumbnail override on file change.
            // Use a base64 data: URL (FileReader) — the site CSP allows `data:` images
            // but blocks the `blob:` URLs that URL.createObjectURL would produce.
            $(document).on('change', '.js-thumb-input', function() {
                var file = this.files && this.files[0];
                if (!file) return;
                var img = $(this).closest('tr').find('.product-thumb')[0];
                if (!img) return;
                var reader = new FileReader();
                reader.onload = function(e) {
                    img.onerror = null;
                    img.src = e.target.result;
                };
                reader.readAsDataURL(file);
            });

            function updateCount() {
                $('#productCountBadge').text($('#sectionProductsBody tr').length);
            }

            // ── Drag-and-drop ordering (delegated, so it works for added rows too) ──
            var body = document.getElementById('sectionProductsBody');
            var dragEl = null;
            body.addEventListener('dragstart', function(e) {
                var tr = e.target.closest('tr');
                if (!tr) return;
                dragEl = tr;
                tr.classList.add('bp-dragging');
                e.dataTransfer.effectAllowed = 'move';
            });
            body.addEventListener('dragover', function(e) {
                e.preventDefault();
                if (!dragEl) return;
                var tr = e.target.closest('tr');
                if (!tr || tr === dragEl) return;
                var rect = tr.getBoundingClientRect();
                var after = e.clientY > rect.top + rect.height / 2;
                body.insertBefore(dragEl, after ? tr.nextSibling : tr);
            });
            body.addEventListener('dragend', function() {
                if (dragEl) dragEl.classList.remove('bp-dragging');
                dragEl = null;
                renumber();
            });

            // Keep each row's sort_order in sync with its visual position.
            function renumber() {
                $('#sectionProductsBody tr').each(function(idx) {
                    $(this).find('.row-sort-order').val(idx);
                });
            }
            $('#sectionProductsForm').on('submit', renumber);
        });
    </script>
@endpush
