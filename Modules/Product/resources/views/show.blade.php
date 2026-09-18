@extends('core::layouts.master')

@section('title', $product->name ?? 'Product View')
@section('page-title', $product->name ?? 'Product View')

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('products.index') }}">Products</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>{{ $product->name ?? 'Product View' }}</span>
@endsection

@section('page-actions')
    @if ($product->barcode)
        <a href="{{ route('barcode.index', ['product_id' => $product->id]) }}" class="bp-btn bp-btn-info"><i
                class="fa-solid fa-barcode me-1"></i> Print Barcode</a>
    @endif
    @bpCan('products.create')
        <form action="{{ route('products.duplicate', $product) }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="bp-btn bp-btn-warning"><i class="fa-solid fa-clone me-1"></i> Duplicate</button>
        </form>
    @endbpCan
    @if ($product->status === 'active')
        <a href="{{ route('storefront.shop.show', $product->slug) }}" target="_blank" rel="noopener"
            class="bp-btn bp-btn-success" title="Open the public product page in a new tab">
            <i class="fa-solid fa-up-right-from-square me-1"></i> Public View
        </a>
    @endif
    @bpCan('products.edit')
        <a href="{{ route('products.edit', $product) }}" class="bp-btn bp-btn-danger">
            <i class="fa-solid fa-pen me-1"></i> Edit Product
        </a>
    @endbpCan
    <a href="{{ route('products.index') }}" class="bp-btn bp-btn-primary"><i class="fa-solid fa-arrow-left me-1"></i>
        Back</a>
@endsection

@section('content')

    <!-- Quick Status Bar -->
    <div class="row g-3 mb-4">
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-success"><i class="fa-solid fa-boxes-stacked"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Stock</div>
                    <div class="bp-stat-value">{{ $product->total_stock ?? 0 }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-warning"><i class="fa-solid fa-warehouse"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Purchase Price</div>
                    <div class="bp-stat-value">{{ currency_symbol() }} {{ $purchasePriceFmt ?? '0' }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-tag"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Retail Price</div>
                    <div class="bp-stat-value">{{ currency_symbol() }} {{ $product->formatted_sell_price ?? '0' }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-secondary"><i class="fa-solid fa-tags"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Whole Sale Price</div>
                    <div class="bp-stat-value">{{ currency_symbol() }} {{ $product->formatted_wholesale_price ?? '0' }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-info"><i class="fa-solid fa-cart-shopping"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Sold</div>
                    <div class="bp-stat-value">{{ $totalSold ?? 0 }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon {{ ($totalProfit ?? 0) < 0 ? 'icon-danger' : 'icon-success' }}"><i
                        class="fa-solid fa-arrow-trend-up"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Profit</div>
                    <div class="bp-stat-value">{{ currency_symbol() }} {{ $totalProfitFmt ?? '0' }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="row g-4">

        <!-- Left Column: Gallery + Stock -->
        <div class="col-xl-3">

            <!-- Product Gallery -->
            <div class="bp-card pro_view_card mb-4">
                <div class="bp-card-body p-0">
                    @php
                        // Slider order: the dedicated thumbnail first (the product's
// "Main display image"), then the gallery images by sort_order,
// skipping any gallery dupe of the thumbnail path (Bug_77).
$galleryPaths = $product->images->sortBy('sort_order')->pluck('image_path');
                        $sliderPaths = collect();
                        if ($product->thumbnail) {
                            $sliderPaths->push($product->thumbnail);
                        }
                        foreach ($galleryPaths as $p) {
                            if ($p !== $product->thumbnail) {
                                $sliderPaths->push($p);
                            }
                        }
                        $sliderPaths = $sliderPaths->values();
                        $mainPath = $sliderPaths->first();
                    @endphp
                    <div class="bp-product-main-img rpunded">
                        @if ($mainPath)
                            <img src="{{ upload_url($mainPath) }}" alt="{{ $product->name }}">
                        @else
                            <i class="fa-solid fa-mobile-screen"></i>
                        @endif
                    </div>
                    <div class="bp-product-thumbs">
                        @forelse($sliderPaths as $path)
                            <div class="bp-product-thumb {{ $loop->first ? 'active' : '' }}">
                                <img src="{{ upload_url($path) }}" alt="Thumbnail">
                            </div>
                        @empty
                            <div class="bp-product-thumb active"><i class="fa-solid fa-mobile-screen"></i></div>
                        @endforelse
                    </div>
                </div>
            </div>

        </div>

        <!-- Right Column: Details & Tabs -->
        <div class="col-xl-9">

            <!-- Product Info Card -->
            <div class="bp-card mb-4">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-circle-info me-2 text-primary"></i>Product Information
                    </h5>
                    <div class="d-flex gap-2">
                        @if ($product->category ?? null)
                            <span class="bp-badge bp-badge-primary">{{ $product->category->name }}</span>
                        @endif
                        @if ($product->status === 'active')
                            <span class="bp-badge bp-badge-success">Active</span>
                        @elseif($product->status === 'draft')
                            <span class="bp-badge bp-badge-dark">Draft</span>
                        @else
                            <span class="bp-badge bp-badge-danger">Inactive</span>
                        @endif
                    </div>
                </div>
                <div class="bp-card-body">
                    <div class="bp-info-row">
                        <div class="bp-info-label">Product Name</div>
                        <div class="bp-info-value fw-700">{{ $product->name ?? '' }}</div>
                    </div>
                    {{-- Model shown directly under the product name (Bug_79). --}}
                    <div class="bp-info-row">
                        <div class="bp-info-label">Model</div>
                        <div class="bp-info-value fw-600">{{ $product->model ?: '--' }}</div>
                    </div>
                    <div class="bp-info-row">
                        <div class="bp-info-label">SKU</div>
                        <div class="bp-info-value"><code class="bp-code">{{ $product->sku ?? '' }}</code></div>
                    </div>
                    <div class="bp-info-row">
                        <div class="bp-info-label">Barcode</div>
                        <div class="bp-info-value">
                            <span class="me-2">{{ $product->barcode ?? '' }}</span>
                            @if ($product->barcode ?? null)
                                <a href="{{ route('barcode.index', ['product_id' => $product->id]) }}"
                                    class="bp-btn bp-btn-sm bp-btn-outline" title="Print Barcode"><i
                                        class="fa-solid fa-barcode"></i></a>
                            @endif
                        </div>
                    </div>
                    <div class="bp-info-row">
                        <div class="bp-info-label">Category</div>
                        <div class="bp-info-value">
                            @if ($product->category ?? null)
                                <span class="bp-badge bp-badge-primary">{{ $product->category->name }}</span>
                            @else
                                <span class="text-muted">--</span>
                            @endif
                        </div>
                    </div>
                    <div class="bp-info-row">
                        <div class="bp-info-label">Brand</div>
                        <div class="bp-info-value fw-600">{{ $product->brand->name ?? '--' }}</div>
                    </div>
                    <div class="bp-info-row">
                        <div class="bp-info-label">Supplier</div>
                        <div class="bp-info-value">
                            @if ($supplier ?? null)
                                <a href="{{ route('supplier.show', $supplier->id) }}">{{ $supplier->company_name }}</a>
                            @else
                                <span class="text-muted">--</span>
                            @endif
                        </div>
                    </div>
                    <div class="bp-info-row">
                        <div class="bp-info-label">Warranty</div>
                        <div class="bp-info-value">
                            @if ($product->warranty ?? null)
                                <span
                                    class="bp-badge bp-badge-info">{{ $product->warranty_display ?? $product->warranty }}</span>
                            @else
                                <span class="text-muted">No Warranty</span>
                            @endif
                        </div>
                    </div>
                    <div class="bp-info-row">
                        <div class="bp-info-label">Created</div>
                        <div class="bp-info-value text-muted">
                            {{ $product->created_at ? $product->created_at->format('d M Y') : '' }}
                            {{ $product->creator ? '· by ' . $product->creator->name : '' }}</div>
                    </div>
                    <div class="bp-info-row">
                        <div class="bp-info-label">Last Updated</div>
                        <div class="bp-info-value text-muted">
                            {{ $product->updated_at ? $product->updated_at->format('d M Y') : '' }}
                            {{ $product->updater ? '· by ' . $product->updater->name : '' }}</div>
                    </div>
                </div>
            </div>

            <!-- Tabs: Variants, Sales History, Stock History, Purchase History -->
            <div class="bp-card">
                <div class="bp-card-header">
                    <ul class="nav nav-tabs bp-detail-tabs border-0 mb-0" role="tablist">
                        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab"
                                href="#tabVariantsView">Variants</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabSalesHistory">Sales
                                History</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabStockHistory">Stock
                                History</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab"
                                href="#tabPurchaseHistory">Purchase History</a></li>
                    </ul>
                </div>
                <div class="bp-card-body p-0">
                    <div class="tab-content">

                        <!-- Variants Tab -->
                        <div class="tab-pane fade show active" id="tabVariantsView">
                            <div class="bp-table-wrapper">
                                <table class="bp-table">
                                    <x-core::table.header>
                                        <x-core::table.column>#</x-core::table.column>
                                        <x-core::table.column>Variant</x-core::table.column>
                                        <x-core::table.column>SKU</x-core::table.column>
                                        <x-core::table.column>Cost ({{ currency_symbol() }})</x-core::table.column>
                                        <x-core::table.column>Sell ({{ currency_symbol() }})</x-core::table.column>
                                        <x-core::table.column align="center">Stock</x-core::table.column>
                                        <x-core::table.column align="center">Default</x-core::table.column>
                                        <x-core::table.column align="center">Active</x-core::table.column>
                                    </x-core::table.header>
                                    <tbody>
                                        @forelse($variants ?? [] as $i => $variant)
                                            <tr>
                                                <td class="text-muted fs-12 text-center">{{ $i + 1 }}</td>
                                                <td>
                                                    <div class="bp-variant-combo">
                                                        @foreach ($variant['attributes'] ?? [] as $a)
                                                            <span class="bp-variant-chip">
                                                                @if (!empty($a['color_code']))
                                                                    <span class="bp-attr-chip-color-dot"
                                                                        data-color="{{ $a['color_code'] }}"></span>
                                                                @endif
                                                                {{ $a['attribute_name'] ?? '' }}: {{ $a['value'] ?? '' }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                </td>
                                                <td><code class="bp-code">{{ $variant['sku'] ?? '' }}</code></td>
                                                <td>{{ num($variant['cost_price'] ?? 0) }}</td>
                                                <td class="fw-700">{{ num($variant['sell_price'] ?? 0) }}
                                                </td>
                                                <td class="text-center">
                                                    <span
                                                        class="bp-badge {{ ($variant['stock'] ?? 0) > 0 ? 'bp-badge-success' : 'bp-badge-danger' }}">{{ $variant['stock'] ?? 0 }}</span>
                                                </td>
                                                <td class="text-center">
                                                    @if (!empty($variant['is_default']))
                                                        <span class="bp-badge bp-badge-primary">Default</span>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    @if (!empty($variant['is_active']))
                                                        <span class="bp-badge bp-badge-success">Active</span>
                                                    @else
                                                        <span class="bp-badge bp-badge-danger">Inactive</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <x-core::table.empty :colspan="8" icon="fa-layer-group"
                                                :title="__('No variants defined for this product')" />
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Sales History Tab -->
                        <div class="tab-pane fade" id="tabSalesHistory">
                            <div class="bp-filter-bar bp-filter-bar-inner bp-hist-filter">
                                <div class="bp-table-search">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                    <input type="text" class="bp-hist-search" placeholder="Search sales...">
                                </div>
                                <input type="date" class="bp-form-control bp-form-control-date bp-hist-from"
                                    data-default="{{ now()->startOfYear()->format('Y-m-d') }}"
                                    value="{{ now()->startOfYear()->format('Y-m-d') }}">
                                <span class="text-muted">to</span>
                                <input type="date" class="bp-form-control bp-form-control-date bp-hist-to"
                                    data-default="{{ now()->format('Y-m-d') }}" value="{{ now()->format('Y-m-d') }}">
                                <button type="button" class="bp-btn bp-btn-sm bp-btn-primary bp-hist-apply"
                                    title="Apply Filters"><i class="fa-solid fa-filter"></i></button>
                                <button type="button" class="bp-btn bp-btn-sm bp-btn-danger bp-hist-reset"
                                    title="Reset Filters"><i class="fa-solid fa-rotate"></i></button>
                            </div>
                            <div class="bp-table-wrapper">
                                <table class="bp-table">
                                    <x-core::table.header>
                                        <x-core::table.column>Invoice #</x-core::table.column>
                                        <x-core::table.column>Date</x-core::table.column>
                                        <x-core::table.column>Customer</x-core::table.column>
                                        <x-core::table.column>Qty</x-core::table.column>
                                        <x-core::table.column>Unit Price</x-core::table.column>
                                        <x-core::table.column>Total</x-core::table.column>
                                        <x-core::table.column>Status</x-core::table.column>
                                    </x-core::table.header>
                                    <tbody>
                                        @forelse($salesHistory ?? [] as $sale)
                                            <tr
                                                data-date="{{ $sale->date ? \Carbon\Carbon::parse($sale->date)->format('Y-m-d') : '' }}">
                                                <td><a href="{{ route('sales.show', $sale) }}"
                                                        class="fw-700">{{ $sale->invoice_number ?? '' }}</a></td>
                                                <td>{{ $sale->date ? \Carbon\Carbon::parse($sale->date)->format('d M Y') : '' }}
                                                </td>
                                                <td class="fw-600">{{ $sale->customer_display_name }}</td>
                                                <td>{{ $sale->pivot->quantity ?? ($sale->quantity ?? 0) }}</td>
                                                <td>{{ currency_symbol() }}
                                                    {{ $sale->pivot->unit_price ?? ($sale->unit_price ?? '0') }}</td>
                                                <td class="fw-700">{{ currency_symbol() }}
                                                    {{ $sale->pivot->total ?? ($sale->total ?? '0') }}</td>
                                                <td>
                                                    @if (($sale->payment_status ?? '') === 'paid')
                                                        <span class="bp-badge bp-badge-success">Paid</span>
                                                    @elseif(($sale->payment_status ?? '') === 'partial')
                                                        <span class="bp-badge bp-badge-warning">Partial</span>
                                                    @else
                                                        <span class="bp-badge bp-badge-danger">Unpaid</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <x-core::table.empty :colspan="7" icon="fa-chart-line"
                                                :title="__('No sales history found')" />
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            @if (isset($salesHistory) && method_exists($salesHistory, 'hasPages'))
                                <x-core::table.pagination :paginator="$salesHistory" itemLabel="sales" />
                            @endif
                        </div>

                        <!-- Stock History Tab -->
                        <div class="tab-pane fade" id="tabStockHistory">
                            <div class="bp-filter-bar bp-filter-bar-inner bp-hist-filter">
                                <div class="bp-table-search">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                    <input type="text" class="bp-hist-search" placeholder="Search stock history...">
                                </div>
                                <input type="date" class="bp-form-control bp-form-control-date bp-hist-from"
                                    data-default="{{ now()->startOfYear()->format('Y-m-d') }}"
                                    value="{{ now()->startOfYear()->format('Y-m-d') }}">
                                <span class="text-muted">to</span>
                                <input type="date" class="bp-form-control bp-form-control-date bp-hist-to"
                                    data-default="{{ now()->format('Y-m-d') }}" value="{{ now()->format('Y-m-d') }}">
                                <button type="button" class="bp-btn bp-btn-sm bp-btn-primary bp-hist-apply"
                                    title="Apply Filters"><i class="fa-solid fa-filter"></i></button>
                                <button type="button" class="bp-btn bp-btn-sm bp-btn-danger bp-hist-reset"
                                    title="Reset Filters"><i class="fa-solid fa-rotate"></i></button>
                            </div>
                            <div class="bp-table-wrapper">
                                <table class="bp-table">
                                    <x-core::table.header>
                                        <x-core::table.column>Date</x-core::table.column>
                                        <x-core::table.column>Type</x-core::table.column>
                                        <x-core::table.column>Qty Change</x-core::table.column>
                                        <x-core::table.column>Balance</x-core::table.column>
                                        <x-core::table.column>Reference</x-core::table.column>
                                        <x-core::table.column>Branch</x-core::table.column>
                                        <x-core::table.column>By</x-core::table.column>
                                    </x-core::table.header>
                                    <tbody>
                                        @forelse($stockHistory ?? [] as $entry)
                                            <tr
                                                data-date="{{ $entry->created_at ? $entry->created_at->format('Y-m-d') : '' }}">
                                                <td>{{ $entry->created_at ? $entry->created_at->format('d M Y') : '' }}
                                                </td>
                                                <td>
                                                    @if ($entry->type === 'sale')
                                                        <span class="bp-badge bp-badge-danger"><i
                                                                class="fa-solid fa-arrow-down me-1"></i>Sale</span>
                                                    @elseif($entry->type === 'purchase')
                                                        <span class="bp-badge bp-badge-success"><i
                                                                class="fa-solid fa-arrow-up me-1"></i>Purchase</span>
                                                    @elseif($entry->type === 'transfer')
                                                        <span class="bp-badge bp-badge-info"><i
                                                                class="fa-solid fa-arrows-left-right me-1"></i>Transfer</span>
                                                    @elseif($entry->type === 'adjustment')
                                                        <span class="bp-badge bp-badge-warning"><i
                                                                class="fa-solid fa-sliders me-1"></i>Adjustment</span>
                                                    @elseif($entry->type === 'opening')
                                                        <span class="bp-badge bp-badge-success"><i
                                                                class="fa-solid fa-arrow-up me-1"></i>Opening Stock</span>
                                                    @else
                                                        <span
                                                            class="bp-badge bp-badge-muted">{{ ucfirst($entry->type) }}</span>
                                                    @endif
                                                </td>
                                                <td
                                                    class="{{ $entry->quantity_change > 0 ? 'text-success' : 'text-danger' }} fw-700">
                                                    {{ $entry->quantity_change > 0 ? '+' : '' }}{{ $entry->quantity_change }}
                                                </td>
                                                <td>{{ $entry->balance ?? '' }}</td>
                                                <td>
                                                    @if ($entry->reference_url ?? null)
                                                        <a
                                                            href="{{ $entry->reference_url }}">{{ $entry->reference ?? '' }}</a>
                                                    @else
                                                        {{ $entry->reference ?? '' }}
                                                    @endif
                                                </td>
                                                <td>{{ $entry->branch->name ?? '' }}</td>
                                                <td>{{ $entry->user->name ?? '' }}</td>
                                            </tr>
                                        @empty
                                            <x-core::table.empty :colspan="7" icon="fa-clock-rotate-left"
                                                :title="__('No stock history found')" />
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Purchase History Tab -->
                        <div class="tab-pane fade" id="tabPurchaseHistory">
                            <div class="bp-filter-bar bp-filter-bar-inner bp-hist-filter">
                                <div class="bp-table-search">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                    <input type="text" class="bp-hist-search" placeholder="Search purchases...">
                                </div>
                                <input type="date" class="bp-form-control bp-form-control-date bp-hist-from"
                                    data-default="{{ now()->startOfYear()->format('Y-m-d') }}"
                                    value="{{ now()->startOfYear()->format('Y-m-d') }}">
                                <span class="text-muted">to</span>
                                <input type="date" class="bp-form-control bp-form-control-date bp-hist-to"
                                    data-default="{{ now()->format('Y-m-d') }}" value="{{ now()->format('Y-m-d') }}">
                                <button type="button" class="bp-btn bp-btn-sm bp-btn-primary bp-hist-apply"
                                    title="Apply Filters"><i class="fa-solid fa-filter"></i></button>
                                <button type="button" class="bp-btn bp-btn-sm bp-btn-danger bp-hist-reset"
                                    title="Reset Filters"><i class="fa-solid fa-rotate"></i></button>
                            </div>
                            <div class="bp-table-wrapper">
                                <table class="bp-table">
                                    <x-core::table.header>
                                        <x-core::table.column>PO Number</x-core::table.column>
                                        <x-core::table.column>Date</x-core::table.column>
                                        <x-core::table.column>Supplier</x-core::table.column>
                                        <x-core::table.column>Qty</x-core::table.column>
                                        <x-core::table.column>Cost Price</x-core::table.column>
                                        <x-core::table.column>Total</x-core::table.column>
                                        <x-core::table.column>Status</x-core::table.column>
                                    </x-core::table.header>
                                    <tbody>
                                        @forelse($purchaseHistory ?? [] as $purchase)
                                            <tr
                                                data-date="{{ $purchase->date ? \Carbon\Carbon::parse($purchase->date)->format('Y-m-d') : '' }}">
                                                <td><a href="#" class="fw-700">{{ $purchase->po_number ?? '' }}</a>
                                                </td>
                                                <td>{{ $purchase->date ? \Carbon\Carbon::parse($purchase->date)->format('d M Y') : '' }}
                                                </td>
                                                <td class="fw-600">{{ $purchase->supplier->company_name ?? '' }}</td>
                                                <td>{{ $purchase->pivot->quantity ?? ($purchase->quantity ?? 0) }}</td>
                                                <td>{{ currency_symbol() }}
                                                    {{ $purchase->pivot->cost_price ?? ($purchase->cost_price ?? '0') }}
                                                </td>
                                                <td class="fw-700">{{ currency_symbol() }}
                                                    {{ $purchase->pivot->total ?? ($purchase->total ?? '0') }}</td>
                                                <td>
                                                    @if (($purchase->status ?? '') === 'received')
                                                        <span class="bp-badge bp-badge-success">Received</span>
                                                    @elseif(($purchase->status ?? '') === 'pending')
                                                        <span class="bp-badge bp-badge-warning">Pending</span>
                                                    @elseif(($purchase->status ?? '') === 'ordered')
                                                        <span class="bp-badge bp-badge-info">Ordered</span>
                                                    @else
                                                        <span
                                                            class="bp-badge bp-badge-muted">{{ ucfirst($purchase->status ?? 'Unknown') }}</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <x-core::table.empty :colspan="7" icon="fa-cart-shopping"
                                                :title="__('No purchase history found')" />
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

    <!-- Delete Form (hidden) -->
    <form id="deleteProductForm" action="{{ route('products.destroy', $product) }}" method="POST" class="d-none">
        @csrf
        @method('DELETE')
    </form>

@endsection

@push('scripts')
    <script>
        'use strict';

        $(function() {
            // Colour the variant attribute chip dots from their data-color.
            $('#tabVariantsView [data-color]').each(function() {
                $(this).css('background-color', $(this).attr('data-color'));
            });

            // Thumbnail gallery switching
            $(document).on('click', '.bp-product-thumb:not(.bp-product-thumb-add)', function() {
                $('.bp-product-thumb').removeClass('active');
                $(this).addClass('active');
                var img = $(this).find('img');
                if (img.length) {
                    var mainImg = $('.bp-product-main-img');
                    mainImg.html('<img src="' + img.attr('src') + '" alt="Product Image">');
                }
            });

            // Delete confirmation
            $(document).on('click', '.delete-product-btn', function(e) {
                e.preventDefault();
                if (confirm(
                        'Are you sure you want to delete this product? This action cannot be undone.')) {
                    $('#deleteProductForm').submit();
                }
            });

            // History tab filters (Sales / Stock / Purchase) — client-side filter of
            // the rendered rows by search text + date range (rows carry data-date).
            function applyHistoryFilter($bar) {
                var $pane = $bar.closest('.tab-pane');
                var q = ($pane.find('.bp-hist-search').val() || '').toLowerCase().trim();
                var from = $pane.find('.bp-hist-from').val();
                var to = $pane.find('.bp-hist-to').val();
                $pane.find('tbody tr').each(function() {
                    var $tr = $(this);
                    if ($tr.find('td').length <= 1) return; // skip the empty-state row
                    var matchText = !q || $tr.text().toLowerCase().indexOf(q) !== -1;
                    var date = $tr.attr('data-date') || '';
                    var matchDate = (!from || (date && date >= from)) && (!to || (date && date <= to));
                    $tr.toggle(matchText && matchDate);
                });
            }

            $(document).on('click', '.bp-hist-apply', function() {
                applyHistoryFilter($(this).closest('.bp-filter-bar'));
            });

            $(document).on('click', '.bp-hist-reset', function() {
                var $bar = $(this).closest('.bp-filter-bar');
                $bar.find('.bp-hist-search').val('');
                $bar.find('.bp-hist-from, .bp-hist-to').each(function() {
                    this.value = this.getAttribute('data-default') || '';
                });
                $bar.closest('.tab-pane').find('tbody tr').show();
            });
        });
    </script>
@endpush
