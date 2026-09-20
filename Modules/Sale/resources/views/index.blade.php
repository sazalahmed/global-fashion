@extends('core::layouts.master')

@section('title', __('Sales List'))
@section('page-title', __('Sales List'))

@push('styles')
    <link href="{{ asset('vendor/venobox/venobox.min.css') }}" rel="stylesheet">
@endpush

@section('breadcrumb')
    <span class="sep">
        <i class="fa-solid fa-chevron-right"></i></span>
    <span>Sales</span>
@endsection

@section('page-actions')
    @bpCan('sales.export')
    <x-core::export-dropdown module="sales" :params="['status' => request('status', 'pending')]" :print="false" />
    @endbpCan
    @bpCan('sales.create')
    <a href="{{ route('sales.create') }}" class="bp-btn bp-btn-primary">
        <i class="fa-solid fa-plus"></i> Create Order
    </a>
    @endbpCan
@endsection

@section('content')

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-bangladeshi-taka-sign"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Today's Sales</div>
                    <div class="bp-stat-value">{{ money($stats['today_sales']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-success"><i class="fa-solid fa-file-invoice"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">This Month</div>
                    <div class="bp-stat-value">{{ money($stats['month_sales']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-clock"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">This Month Total Due</div>
                    <div class="bp-stat-value">{{ money($stats['total_due']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-info"><i class="fa-solid fa-receipt"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">This Month Total Sales</div>
                    <div class="bp-stat-value">{{ number_format($stats['total_sales_count']) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Filter Tabs -->
    <div class="sales_filter mb-4">
        <div class="bp-status-tabs">
            @php
                $currentStatus = request('status', 'pending');
                $statusLabels = [
                    'pending' => 'Pending',
                    'packing' => 'Packing',
                    'courier' => 'Courier',
                    'delivered' => 'Delivered',
                    'partial_cancelled' => 'Partial Cancel',
                    'cancelled' => 'Cancelled',
                    'return_received' => 'Return Received',
                    'returned' => 'Returned',
                    'draft' => 'Draft',
                    'on_hold' => 'On-Hold',
                    'incompleted' => 'Incompleted',
                    'all' => 'All',
                ];
            @endphp
            @foreach ($statusLabels as $key => $label)
                <a href="{{ route('sales.index', array_merge(request()->except('status', 'page'), ['status' => $key])) }}"
                    class="bp-status-tab {{ $currentStatus === $key ? 'active' : '' }}">
                    <span class="bp-status-tab-count">{{ $statusCounts[$key] ?? 0 }}</span>
                    <span class="bp-status-tab-label">{{ $label }}</span>
                </a>
            @endforeach
        </div>
    </div>

    <!-- Sales Table -->
    <x-core::table :selectable="true" class="bp-table-compact bp-sales-table">
        <x-slot:filters>
            <div class="bp-bulk-toolbar mb-1" id="bulkActionsBar">
                <span class="bp-table-bulk-count"><span id="bulkCount">0</span> {{ __('selected') }}</span>
                <select class="bp-form-select bp-form-select-sm bp-bulk-status-select" id="bulkStatusChange" disabled>
                    <option value="">{{ __('Change Status') }}</option>
                    @foreach (\Modules\Sale\Models\Sale::selectableStatuses() as $val => $label)
                        <option value="{{ $val }}">{{ __($label) }}</option>
                    @endforeach
                </select>
                <button class="bp-btn bp-btn-sm bp-btn-primary" id="bulkAssignStaff" type="button"
                    title="{{ __('Assign Selected to Staff') }}" disabled>
                    <i class="fa-solid fa-user-tag me-1"></i> {{ __('Assign to Staff') }}
                </button>
                @php
                    // Hide the bulk courier button on tabs whose sales can never
                    // be dispatched (mirrors dispatchSaleToCourier's guard).
                    $bulkCourierHidden = in_array(
                        request('status', 'pending'),
                        ['delivered', 'partial_cancelled', 'cancelled', 'returned', 'return_received'],
                        true,
                    );
                @endphp
                @if ($couriers->count() >= 1 && !$bulkCourierHidden)
                    <button class="bp-btn bp-btn-sm bp-btn-secondary" id="bulkSendCourier" type="button"
                        title="{{ __('Send Selected to Courier') }}" disabled>
                        <i class="fa-solid fa-truck-fast me-1"></i>
                        @if ($couriers->count() === 1)
                            {{ __('Send to') }} {{ $couriers->first()->name }}
                        @else
                            {{ __('Send to Courier') }}
                        @endif
                    </button>
                @endif
                <button class="bp-btn bp-btn-sm bp-btn-info" id="bulkPrint" type="button"
                    title="{{ __('Print Selected') }}" disabled><i class="fa-solid fa-print me-1"></i>
                    {{ __('Print') }}</button>
                <button class="bp-btn bp-btn-sm bp-btn-warning" id="bulkLabel" type="button"
                    title="{{ __('Print Labels for Selected') }}" disabled><i class="fa-solid fa-tag me-1"></i>
                    {{ __('Labels') }}</button>
            </div>

            <x-core::table.filter-bar searchPlaceholder="Search invoice, customer...">
                {{-- Preserve the active status tab when filters are applied --}}
                <input type="hidden" name="status" value="{{ $currentStatus }}">
                <input type="date" class="bp-form-control bp-filter-date" name="date_from"
                    value="{{ request('date_from') }}">
                <span class="text-muted">to</span>
                <input type="date" class="bp-form-control bp-filter-date" name="date_to"
                    value="{{ request('date_to') }}">
                <select class="bp-form-select" name="payment_status">
                    <option value="">All Payment Status</option>
                    <option value="paid" {{ request('payment_status') === 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="partial" {{ request('payment_status') === 'partial' ? 'selected' : '' }}>Partial
                    </option>
                    <option value="unpaid" {{ request('payment_status') === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                </select>
                <select class="bp-form-select" name="source">
                    <option value="">All Sources</option>
                    <option value="pos" {{ request('source') === 'pos' ? 'selected' : '' }}>POS</option>
                    <option value="store" {{ request('source') === 'store' ? 'selected' : '' }}>Invoice</option>
                    <option value="ecommerce" {{ request('source') === 'ecommerce' ? 'selected' : '' }}>Website</option>
                </select>
            </x-core::table.filter-bar>
        </x-slot:filters>

        <x-core::table.header :selectable="true">
            <x-core::table.column>Action</x-core::table.column>
            <x-core::table.column>Date</x-core::table.column>
            <x-core::table.column>ID</x-core::table.column>
            <x-core::table.column>Image</x-core::table.column>
            <x-core::table.column>Details</x-core::table.column>
            <x-core::table.column>Staff</x-core::table.column>
            <x-core::table.column>Customer</x-core::table.column>
            <x-core::table.column>Sale Status</x-core::table.column>
            <x-core::table.column>Note</x-core::table.column>
            <x-core::table.column>Fraud Check</x-core::table.column>
            <x-core::table.column>Courier</x-core::table.column>
            <x-core::table.column>Courier Status</x-core::table.column>
            <x-core::table.column>Total</x-core::table.column>
            <x-core::table.column>Paid</x-core::table.column>
            <x-core::table.column>Due</x-core::table.column>
        </x-core::table.header>

        <tbody>
            @forelse($sales as $sale)
                @php
                    $customerPhone =
                        $sale->customer?->phone ??
                        ($sale->customer_phone_snapshot ?? $sale->ecommerceOrder?->customer_phone);

                    // Group flattened combo component items into one display row
                    // per combo (matching the sale edit page), so a combo shows as
                    // "Combo name + N products + component list" instead of N
                    // separate product rows. Plain items keep one row each.
                    $displayRows = [];
                    foreach ($sale->items as $item) {
                        if ($item->combo_group || $item->combo_id) {
                            $key = 'combo-' . ($item->combo_group ?: $item->combo_id);
                            if (! isset($displayRows[$key])) {
                                $displayRows[$key] = ['combo' => $item->combo_name ?: __('Combo'), 'items' => collect()];
                            }
                            $displayRows[$key]['items']->push($item);
                        } else {
                            $displayRows['item-' . $item->id] = ['combo' => null, 'items' => collect([$item])];
                        }
                    }
                    $displayRows = collect(array_values($displayRows));
                @endphp
                <tr class="bp-sale-row-{{ $sale->status }}">
                    <td><input type="checkbox" class="form-check-input row-checkbox" value="{{ $sale->id }}"></td>
                    {{-- Action --}}
                    <td>
                        @bpCan('sales.edit')
                        @if ($sale->status === 'partial_cancelled')
                            {{-- Courier is returning the parcel. Once the goods are
                                 physically back in hand, "Product Receive" cancels the
                                 sale (restoring stock) and moves it to the Cancelled list. --}}
                            <button type="button" class="bp-btn bp-btn-sm bp-btn-success mb-1 bp-product-receive-btn"
                                data-action="{{ route('sales.status', $sale) }}"
                                data-invoice="{{ $sale->invoice_number }}">
                                <i class="fa-solid fa-box-open me-1"></i>{{ __('Product Receive') }}
                            </button>
                        @endif
                        @endbpCan
                        @bpCanAny('sales.view','sales.edit','sales.create','sales.delete')
                        <div class="dropdown">
                            <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i
                                    class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                            <ul class="dropdown-menu">
                                @bpCan('sales.view')
                                <li>
                                    <button type="button" class="dropdown-item bp-quick-view-btn"
                                        data-action="{{ route('sales.quick-view', $sale) }}"
                                        data-sale-id="{{ $sale->id }}" data-invoice="{{ $sale->invoice_number }}">
                                        <i class="fa-solid fa-eye me-2"></i> Quick View
                                    </button>
                                </li>
                                @endbpCan
                                @php
                                    // Delivered is final — hide mutating actions (Edit,
                                    // Assign, Track/Send courier, Delete); only viewing,
                                    // printing and Sale Return remain.
                                    $isDelivered = $sale->status === 'delivered';
                                @endphp
                                @bpCan('sales.edit')
                                @if ($sale->status !== 'cancelled' && !$isDelivered)
                                    <li><a class="dropdown-item" href="{{ route('sales.edit', $sale) }}"><i
                                                class="fa-solid fa-pen me-2"></i> Edit</a></li>
                                @endif
                                @endbpCan
                                @bpCan('sales.edit')
                                @if (!$isDelivered)
                                    <li>
                                        <button type="button" class="dropdown-item bp-assign-staff-btn"
                                            data-action="{{ route('sales.assign', $sale) }}"
                                            data-sale-id="{{ $sale->id }}" data-current="{{ $sale->assigned_to }}">
                                            <i class="fa-solid fa-user-tag me-2"></i> Assign to Staff
                                        </button>
                                    </li>
                                @endif
                                @endbpCan
                                @bpCan('sales.view')
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item" href="{{ route('sales.print', $sale) }}" target="_blank"><i
                                            class="fa-solid fa-print me-2"></i> Print Invoice</a></li>
                                <li><a class="dropdown-item" href="{{ route('sales.label', $sale) }}" target="_blank"><i
                                            class="fa-solid fa-tag me-2"></i> Print Label</a></li>
                                @if ($sale->courier_tracking_link && !$isDelivered)
                                    <li>
                                        <a class="dropdown-item" href="{{ $sale->courier_tracking_link }}"
                                            target="_blank" rel="noopener">
                                            <i class="fa-solid fa-up-right-from-square me-2"></i> Track Parcel
                                        </a>
                                    </li>
                                @endif
                                @endbpCan
                                @php
                                    // Sending to a courier makes no sense once the sale is
                                    // finished — delivered or any returned/cancelled state.
                                    $courierSendable = !in_array(
                                        $sale->status,
                                        ['delivered', 'partial_cancelled', 'cancelled', 'returned', 'return_received'],
                                        true,
                                    );
                                @endphp
                                @if ($sale->status !== 'cancelled')
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    @bpCan('sales.edit')
                                    @if ($courierSendable && $couriers->count() === 1)
                                        @php($onlyCourier = $couriers->first())
                                        <li>
                                            <form action="{{ route('sales.send-to-courier', $sale) }}" method="POST"
                                                class="bp-send-to-courier-form">
                                                @csrf
                                                <button type="submit" class="dropdown-item">
                                                    <i class="fa-solid fa-truck-fast me-2"></i> Send to
                                                    {{ $onlyCourier->name }}
                                                </button>
                                            </form>
                                        </li>
                                    @elseif($courierSendable && $couriers->count() > 1)
                                        <li>
                                            <button type="button" class="dropdown-item bp-send-to-courier-btn"
                                                data-action="{{ route('sales.send-to-courier', $sale) }}"
                                                data-invoice="{{ $sale->invoice_number }}">
                                                <i class="fa-solid fa-truck-fast me-2"></i> Send to Courier
                                            </button>
                                        </li>
                                    @endif
                                    @endbpCan
                                    @bpCan('sales.create')
                                    <li>
                                        <a class="dropdown-item"
                                            href="{{ route('sale-returns.create', ['sale_id' => $sale->id]) }}">
                                            <i class="fa-solid fa-rotate-left me-2"></i> Sale Return
                                        </a>
                                    </li>
                                    @endbpCan
                                    @if (!$isDelivered)
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        @bpCan('sales.delete')
                                        <li>
                                            <form action="{{ route('sales.destroy', $sale) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger delete-confirm"
                                                    data-name="{{ $sale->invoice_number }}">
                                                    <i class="fa-solid fa-trash me-2"></i> Delete
                                                </button>
                                            </form>
                                        </li>
                                        @endbpCan
                                    @endif
                                @endif
                            </ul>
                        </div>
                        @endbpCanAny
                    </td>
                    {{-- Date --}}
                    <td class="fs-12">{{ $sale->sale_date->format('d-m-Y') }}</td>
                    {{-- ID --}}
                    <td><a href="{{ route('sales.show', $sale) }}" class="fw-700">{{ $sale->id }}</a></td>
                    {{-- Image — one thumbnail per display row (combos count as one),
                         stacked to line up with the product list in the Details column. --}}
                    <td>
                        @forelse ($displayRows->take(3) as $row)
                            @php($item = $row['items']->first())
                            {{-- Combos show the combo catalog thumbnail; fall back to the
                                 first component's product image for legacy combos. --}}
                            @php($comboThumb = $row['combo'] ? $item->combo?->thumbnail : null)
                            @php($itemThumb = $comboThumb ?: $item->product?->thumbnail)
                            @php($itemFull = $comboThumb ?: ($item->product?->image ?: $itemThumb))
                            <div class="mb-2">
                                @if ($itemThumb)
                                    <a class="venobox" data-gall="sale-{{ $sale->id }}"
                                        href="{{ asset($itemFull) }}" title="{{ $row['combo'] ?: $item->product?->name }}">
                                        <img src="{{ asset($itemThumb) }}" alt="" class="bp-sale-thumb">
                                    </a>
                                @else
                                    <div class="bp-sale-thumb-placeholder"><i class="fa-solid fa-image"></i></div>
                                @endif
                            </div>
                        @empty
                            <div class="bp-sale-thumb-placeholder"><i class="fa-solid fa-image"></i></div>
                        @endforelse
                    </td>
                    {{-- Product Details — title / model / variant (e.g. "Size: M").
                         Long orders are capped at 3 lines; the "+N more" button
                         opens the Quick View modal with the full item list. --}}
                    <td class="bp-sale-products">
                        @foreach ($displayRows->take(3) as $row)
                            @if ($row['combo'])
                                {{-- Combo — one entry per combo group: combo name + badge
                                     only (component breakdown lives in Quick View / edit). --}}
                                <div class="mb-2">
                                    <div class="fs-12 fw-700">{{ $row['combo'] }}</div>
                                    <span class="bp-badge bp-badge-secondary fs-11 mt-1 d-inline-block">
                                        <i class="fa-solid fa-layer-group me-1"></i>{{ __('Combo') }} · {{ $row['items']->count() }} {{ __('products') }}
                                    </span>
                                </div>
                            @else
                                @php($item = $row['items']->first())
                                {{-- Clean title (prefer live product name; strip the trailing " — variant" from the snapshot so size isn't duplicated) --}}
                                @php($title = $item->product?->name ?: \Illuminate\Support\Str::beforeLast($item->product_name, ' — '))
                                @php($model = $item->product?->model)
                                {{-- Variant as "Attribute: value" pairs (e.g. "Size: M"); fall back to the stored label --}}
                                @php($variantText = $item->variant ? $item->variant->attributeValues->map(fn($v) => trim(($v->attribute?->base_name ? $v->attribute->base_name . ': ' : '') . $v->value))->filter()->implode(', ') : $item->variant_label ?? '')
                                <div class="mb-2">
                                    <div class="fs-12 fw-700">
                                        <a href="{{ route('sales.show', $sale) }}" class="text-dark text-decoration-none">{{ $title }}</a>
                                    </div>
                                    @if ($model)
                                        <div class="fs-11 text-muted">{{ __('Model') }}: {{ $model }}</div>
                                    @endif
                                    @if ($variantText)
                                        <div class="fs-11 text-muted">{{ $variantText }}</div>
                                    @endif
                                </div>
                            @endif
                        @endforeach
                        @if ($displayRows->count() > 3)
                            @bpCan('sales.view')
                                <button type="button" class="bp-btn bp-btn-sm bp-btn-outline bp-quick-view-btn"
                                    data-action="{{ route('sales.quick-view', $sale) }}"
                                    data-sale-id="{{ $sale->id }}" data-invoice="{{ $sale->invoice_number }}">
                                    <i class="fa-solid fa-plus me-1"></i>{{ $displayRows->count() - 3 }} {{ __('more') }}
                                </button>
                            @else
                                <div class="fs-11 text-muted">+{{ $displayRows->count() - 3 }} {{ __('more items') }}</div>
                            @endbpCan
                        @endif
                    </td>
                    {{-- Staff (assigned owner; defaults to the creator when unassigned) --}}
                    <td class="fs-12">
                        @php($staffMember = $sale->assignedStaff ?? $sale->creator)
                        {{ $staffMember?->name ?? __('Super Admin') }}
                        @if ($sale->assignedStaff && $sale->creator)
                            <div class="text-muted fs-11">{{ __('by') }} {{ $sale->creator->name }}</div>
                        @endif
                    </td>
                    {{-- Customer --}}
                    <td class="bp-sale-customer">
                        <div class="fw-700">
                            {{ $sale->customer?->name ?? ($sale->customer_name_snapshot ?? 'Walk-in Customer') }}
                        </div>
                        @if ($customerPhone)
                            <div class="fs-12 text-muted"><i
                                    class="fa-solid fa-phone fs-11 me-1"></i>{{ $customerPhone }}</div>
                        @endif
                        @if ($sale->thana || $sale->district)
                            <div class="fs-12 text-muted">
                                <i class="fa-solid fa-map fs-11 me-1"></i>
                                {{ trim(($sale->thana?->thana_name ?? '') . ($sale->thana && $sale->district ? ', ' : '') . ($sale->district?->district_name ?? '')) }}
                            </div>
                        @endif
                        @if ($sale->customer_address)
                            <div class="fs-12 text-muted" title="{{ $sale->customer_address }}"><i
                                    class="fa-solid fa-location-dot fs-11 me-1"></i>{{ \Illuminate\Support\Str::limit($sale->customer_address, 60) }}
                            </div>
                        @endif
                    </td>
                    {{-- Sale Status (inline editable) --}}
                    <td>
                        {{-- Delivered is final: goods and money have moved, so the
                             status is locked. Reversals go through Sale Return. --}}
                        <select class="bp-form-select bp-form-select-sm bp-inline-status"
                            data-action="{{ route('sales.status', $sale) }}" data-current="{{ $sale->status }}"
                            @if ($sale->status === 'delivered') disabled
                                title="{{ __('Delivered sales cannot be changed — use Sale Return.') }}" @endif>
                            @foreach (\Modules\Sale\Models\Sale::selectableStatuses($sale->status) as $val => $label)
                                <option value="{{ $val }}" {{ $sale->status === $val ? 'selected' : '' }}>
                                    {{ __($label) }}</option>
                            @endforeach
                        </select>
                    </td>
                    {{-- Note --}}
                    <td class="fs-12" @if ($sale->notes) title="{{ $sale->notes }}" @endif>
                        {{ \Illuminate\Support\Str::limit($sale->notes ?? '', 40) }}</td>
                    {{-- Fraud Check --}}
                    <td class="text-center">
                        @if ($customerPhone)
                            {{-- Fraud screening is a pre-dispatch tool — pointless
                                 once the parcel is already delivered. --}}
                            <button type="button" class="bp-btn bp-btn-icon bp-btn-sm bp-btn-outline bp-fraud-check-btn"
                                title="{{ $sale->status === 'delivered' ? __('Sale already delivered — fraud check not needed.') : __('Run fraud check') }}"
                                data-action="{{ route('sales.fraud-check', $sale) }}" data-phone="{{ $customerPhone }}"
                                @if ($sale->status === 'delivered') disabled @endif>
                                <i class="fa-solid fa-magnifying-glass text-danger"></i>
                            </button>
                        @endif
                    </td>
                    {{-- Courier --}}
                    <td class="fs-12">
                        @if ($sale->courier_name)
                            <div class="fw-600">{{ $sale->courier_name }}</div>
                            @if ($sale->courier_consignment_id)
                                <div class="text-muted fs-11">{{ __('Consignment') }}:
                                    <a href="https://steadfast.com.bd/user/consignment/{{ $sale->courier_consignment_id }}"
                                        target="_blank" rel="noopener">{{ $sale->courier_consignment_id }}</a>
                                </div>
                            @endif
                        @elseif($defaultCourier && $courierSendable)
                            <form action="{{ route('sales.send-to-courier', $sale) }}" method="POST"
                                class="d-inline bp-send-default-courier-form">
                                @csrf
                                <input type="hidden" name="courier_provider_id" value="{{ $defaultCourier->id }}">
                                <button type="submit" class="bp-btn bp-btn-sm bp-btn-outline"
                                    title="{{ __('Send to') }} {{ $defaultCourier->name }}">
                                    <i class="fa-solid fa-truck-fast me-1"></i>{{ __('Send to') }}
                                    {{ $defaultCourier->name }}
                                </button>
                            </form>
                        @endif
                    </td>
                    {{-- Courier Status --}}
                    <td class="fs-12">
                        @if ($sale->courier_status)
                            <span class="bp-badge bp-badge-info">{{ $sale->courier_status }}</span>
                        @endif
                    </td>
                    {{-- Grand Total --}}
                    <td class="fw-800">{{ money($sale->grand_total) }}</td>
                    {{-- Paid (shows courier collected when set, else paid) --}}
                    <td>
                        @if (!is_null($sale->courier_collected_amount))
                            <span class="text-success fw-800"
                                title="{{ __('Collected by courier') }}">{{ money($sale->courier_collected_amount) }}</span>
                        @else
                            <span class="text-success fw-800">{{ money($sale->paid_amount) }}</span>
                        @endif
                    </td>
                    {{-- Due --}}
                    <td class="{{ $sale->due_amount > 0 ? 'text-danger fw-800' : 'text-muted fw-800' }}">
                        {{ money($sale->due_amount) }}</td>
                </tr>
            @empty
                <x-core::table.empty colspan="16" icon="fa-solid fa-file-invoice" title="No sales found"
                    description="There are no sales matching your filters." />
            @endforelse
        </tbody>

        @if ($sales->total() > 0)
            {{-- Calculation row: sums EVERY sale matching the active filters
                 (all pages), unlike the fixed today/this-month stat cards. --}}
            <tfoot>
                <tr class="bp-table-total-row">
                    <td colspan="12" class="text-end fw-800">
                        {{ __('Total') }} ({{ number_format($listTotals['sales_count']) }} {{ __('sales') }})</td>
                    <td class="fw-800">{{ money($listTotals['grand_total']) }}</td>
                    <td class="fw-800">{{ money($listTotals['paid_amount']) }}</td>
                    <td class="fw-800 d-none">0</td>
                    <td class="{{ $listTotals['due_amount'] > 0 ? 'text-danger' : 'text-muted' }} fw-800">
                        {{ money($listTotals['due_amount']) }}</td>
                </tr>
            </tfoot>
        @endif

        <x-slot:pagination>
            <x-core::table.pagination :paginator="$sales" itemLabel="sales" />
        </x-slot:pagination>
    </x-core::table>

    <div class="modal fade" id="quickViewModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable bp-quick-view-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa-solid fa-eye me-2"></i>{{ __('Sale Quick View') }} <span
                            class="text-muted fs-13 ms-2" id="quickViewInvoice"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="quickViewBody">
                    <div class="text-center text-muted py-4">
                        <i class="fa-solid fa-spinner fa-spin fa-2x mb-2 d-block"></i>
                        <div>{{ __('Loading...') }}</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" target="_blank" class="bp-btn bp-btn-outline" id="quickViewPrintBtn"><i
                            class="fa-solid fa-print me-1"></i>{{ __('Print') }}</a>
                    @bpCan('sales.edit')
                    <a href="#" class="bp-btn bp-btn-outline" id="quickViewEditBtn"><i
                            class="fa-solid fa-pen me-1"></i>{{ __('Edit') }}</a>
                    @endbpCan
                    <button type="button" class="bp-btn bp-btn-danger" data-bs-dismiss="modal"><i
                            class="fa-solid fa-xmark me-1"></i>{{ __('Close') }}</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="fraudCheckModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa-solid fa-shield-halved me-2"></i>{{ __('Fraud Check') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="fraudCheckBody">
                    <div class="text-muted">{{ __('Loading...') }}</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="bp-btn bp-btn-warning" id="fraudRefreshBtn"><i
                            class="fa-solid fa-rotate me-1"></i>{{ __('Re-check from API') }}</button>
                    <button type="button" class="bp-btn bp-btn-danger" data-bs-dismiss="modal"><i
                            class="fa-solid fa-xmark me-1"></i>{{ __('Close') }}</button>
                </div>
            </div>
        </div>
    </div>

    @if ($couriers->count() > 1)
        <div class="modal fade" id="sendToCourierModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form id="sendToCourierForm" method="POST">
                        @csrf
                        <div id="sendToCourierBulkIds"></div>
                        <div class="modal-header">
                            <h5 class="modal-title"><i
                                    class="fa-solid fa-truck-fast me-2"></i>{{ __('Send to Courier') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted fs-13 mb-3">{{ __('Choose a courier for') }} <strong
                                    id="sendToCourierInvoice"></strong>.</p>
                            <label class="bp-form-label">{{ __('Courier Provider') }} *</label>
                            <select class="bp-form-select w-100" name="courier_provider_id" required>
                                <option value="">{{ __('Select courier') }}</option>
                                @foreach ($couriers as $courier)
                                    <option value="{{ $courier->id }}">{{ $courier->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="bp-btn bp-btn-danger" data-bs-dismiss="modal"><i
                                    class="fa-solid fa-xmark me-1"></i>{{ __('Cancel') }}</button>
                            <button type="submit" class="bp-btn bp-btn-primary"><i
                                    class="fa-solid fa-paper-plane me-1"></i>{{ __('Send') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Assign to Staff modal — shared by the bulk action and per-row action.
             JS sets the form action (single) or the selected ids (bulk). --}}
    <div class="modal fade" id="assignStaffModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="assignStaffForm" method="POST">
                    @csrf
                    <div id="assignStaffBulkIds"></div>
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fa-solid fa-user-tag me-2"></i>{{ __('Assign to Staff') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body pt-3">
                        <p class="text-muted fs-13 mb-2" id="assignStaffContext"></p>
                        <label class="bp-form-label ">{{ __('Staff Member') }}</label>
                        <select class="bp-form-select w-100" name="staff_id" id="assignStaffSelect">
                            <option value="">{{ __('Unassigned') }}</option>
                            @foreach ($assignableStaff as $staff)
                                <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                            @endforeach
                        </select>
                        <div class="fs-11 text-muted mt-2">
                            {{ __('Pick a staff member, or choose “Unassigned” to clear.') }}</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="bp-btn bp-btn-danger" data-bs-dismiss="modal"><i
                                class="fa-solid fa-xmark me-1"></i>{{ __('Cancel') }}</button>
                        <button type="submit" class="bp-btn bp-btn-success"><i
                                class="fa-solid fa-check me-1"></i>{{ __('Save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        'use strict';

        $(function() {
            // Select all checkbox
            $('.bp-check-all').on('change', function() {
                var isChecked = $(this).prop('checked');
                $('.row-checkbox').prop('checked', isChecked);
                updateBulkBar();
            });

            $(document).on('change', '.row-checkbox', updateBulkBar);

            function getSelectedIds() {
                return $('.row-checkbox:checked').map(function() {
                    return $(this).val();
                }).get();
            }

            function updateBulkBar() {
                var count = $('.row-checkbox:checked').length;
                $('#bulkCount').text(count);
                // Enable the bulk controls only when at least one row is selected.
                $('#bulkActionsBar').find('button, select').prop('disabled', count === 0);
            }

            // Bulk status change
            $('#bulkStatusChange').on('change', function() {
                var status = $(this).val();
                if (!status) return;
                var ids = getSelectedIds();
                if (ids.length === 0) return;
                if (!confirm('Update ' + ids.length + ' sale(s) to "' + status + '"?')) {
                    $(this).val('');
                    return;
                }
                $.post('{{ route('sales.bulk-status') }}', {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    ids: ids,
                    status: status
                }).done(function() {
                    location.reload();
                }).fail(function() {
                    alert('Failed to update status.');
                });
            });

            // Bulk print
            $('#bulkPrint').on('click', function() {
                var ids = getSelectedIds();
                if (ids.length === 0) return;
                window.open('{{ route('sales.bulk-print') }}?ids=' + ids.join(','), '_blank');
            });

            // Bulk print labels (4x6in shipping labels)
            $('#bulkLabel').on('click', function() {
                var ids = getSelectedIds();
                if (ids.length === 0) return;
                window.open('{{ route('sales.bulk-label') }}?ids=' + ids.join(','), '_blank');
            });

            // ── Assign to Staff (shared modal: bulk + single) ──
            function showAssignModal() {
                var $modal = $('#assignStaffModal');
                if ($modal.length) bootstrap.Modal.getOrCreateInstance($modal[0]).show();
            }

            // Bulk: assign all selected sales
            $('#bulkAssignStaff').on('click', function() {
                var ids = getSelectedIds();
                if (ids.length === 0) return;
                $('#assignStaffForm').attr('action', '{{ route('sales.bulk-assign') }}').data('bulk',
                    true);
                var $ids = $('#assignStaffBulkIds').empty();
                ids.forEach(function(id) {
                    $ids.append($('<input type="hidden" name="ids[]">').val(id));
                });
                $('#assignStaffContext').text('{{ __('Assigning') }} ' + ids.length +
                    ' {{ __('sale(s)') }}.');
                $('#assignStaffSelect').val('').trigger('change');
                showAssignModal();
            });

            // Single: assign one sale (per-row action)
            $(document).on('click', '.bp-assign-staff-btn', function() {
                $('#assignStaffForm').attr('action', $(this).data('action')).data('bulk', false);
                $('#assignStaffBulkIds').empty();
                $('#assignStaffContext').text('');
                $('#assignStaffSelect').val($(this).data('current') || '').trigger('change');
                showAssignModal();
            });

            // Submit assignment (single or bulk) via AJAX, then refresh.
            $('#assignStaffForm').on('submit', function(e) {
                e.preventDefault();
                var $form = $(this);
                var data = {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    staff_id: $('#assignStaffSelect').val() || ''
                };
                if ($form.data('bulk')) {
                    data.ids = $('#assignStaffBulkIds input').map(function() {
                        return $(this).val();
                    }).get();
                }
                $.post($form.attr('action'), data).done(function() {
                    location.reload();
                }).fail(function(xhr) {
                    alert((xhr.responseJSON && xhr.responseJSON.message) ||
                        'Failed to assign staff.');
                });
            });

            // Searchable Select2 for the staff picker. Initialised on show with
            // dropdownParent set to the modal so the dropdown renders inside it
            // (correct positioning + z-index above the backdrop).
            $('#assignStaffModal').on('shown.bs.modal', function() {
                var $sel = $('#assignStaffSelect');
                if (typeof $.fn.select2 !== 'undefined' && !$sel.hasClass('select2-hidden-accessible')) {
                    $sel.select2({
                        width: '100%',
                        placeholder: '{{ __('Select staff member') }}',
                        allowClear: true,
                        dropdownParent: $('#assignStaffModal')
                    });
                }
                $sel.trigger('change');
            });

            // Send to courier — multi-courier picker (per-row)
            $(document).on('click', '.bp-send-to-courier-btn', function() {
                var $modal = $('#sendToCourierModal');
                if (!$modal.length) return;
                $('#sendToCourierForm').attr('action', $(this).data('action')).data('bulk', false);
                $('#sendToCourierBulkIds').empty();
                $('#sendToCourierInvoice').text($(this).data('invoice') || '');
                $('#sendToCourierForm select[name="courier_provider_id"]').val('');
                var modal = bootstrap.Modal.getOrCreateInstance($modal[0]);
                modal.show();
            });

            // Bulk send to courier
            $('#bulkSendCourier').on('click', function() {
                var ids = getSelectedIds();
                if (ids.length === 0) return;
                var courierCount = {{ $couriers->count() }};

                if (courierCount === 1) {
                    if (!confirm('Send ' + ids.length +
                            ' sale(s) to {{ optional($couriers->first())->name ?? 'the courier' }}?'))
                        return;
                    $.ajax({
                        url: '{{ route('sales.bulk-send-to-courier') }}',
                        method: 'POST',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content'),
                            ids: ids
                        }
                    }).done(function() {
                        location.reload();
                    }).fail(function(xhr) {
                        alert((xhr.responseJSON && xhr.responseJSON.message) ||
                            'Failed to send to courier.');
                    });
                    return;
                }

                // Multiple couriers — open picker modal in bulk mode
                var $modal = $('#sendToCourierModal');
                if (!$modal.length) return;
                $('#sendToCourierForm').attr('action', '{{ route('sales.bulk-send-to-courier') }}').data(
                    'bulk', true);
                $('#sendToCourierInvoice').text(ids.length + ' selected');
                var $bulk = $('#sendToCourierBulkIds').empty();
                ids.forEach(function(id) {
                    $bulk.append($('<input>').attr({
                        type: 'hidden',
                        name: 'ids[]',
                        value: id
                    }));
                });
                $('#sendToCourierForm select[name="courier_provider_id"]').val('');
                bootstrap.Modal.getOrCreateInstance($modal[0]).show();
            });

            // Intercept bulk-mode submit so it uses AJAX (single-mode posts as normal form)
            $('#sendToCourierForm').on('submit', function(e) {
                if (!$(this).data('bulk')) return;
                e.preventDefault();
                var $form = $(this);
                $.ajax({
                    url: $form.attr('action'),
                    method: 'POST',
                    data: $form.serialize()
                }).done(function() {
                    location.reload();
                }).fail(function(xhr) {
                    alert((xhr.responseJSON && xhr.responseJSON.message) ||
                        'Failed to send to courier.');
                });
            });

            // Inline status change (per-row)
            $(document).on('change', '.bp-inline-status', function() {
                var $sel = $(this);
                var newStatus = $sel.val();
                var prev = $sel.data('current');
                if (newStatus === prev) return;

                $.ajax({
                    url: $sel.data('action'),
                    method: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        status: newStatus
                    }
                }).done(function() {
                    location.reload();
                }).fail(function() {
                    alert('Failed to update status.');
                    $sel.val(prev);
                });
            });

            // ── Product Receive (Partial Cancel → Cancelled) ──
            // The courier-returned goods are physically back: cancel the sale so
            // stock is restored and it moves to the Cancelled list. Reuses the
            // same status endpoint as the inline status picker.
            $(document).on('click', '.bp-product-receive-btn', function() {
                var $btn = $(this);
                var invoice = $btn.data('invoice') || '';
                if (!confirm('{{ __('Confirm the returned product for :inv has been received? This will cancel the sale and restore stock.', ['inv' => '___INV___']) }}'.replace('___INV___', invoice))) {
                    return;
                }
                $btn.prop('disabled', true);
                $.ajax({
                    url: $btn.data('action'),
                    method: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        status: 'cancelled'
                    }
                }).done(function() {
                    location.reload();
                }).fail(function() {
                    alert('{{ __('Failed to receive product.') }}');
                    $btn.prop('disabled', false);
                });
            });

            // Confirm before dispatching to the default courier from the Courier column.
            $(document).on('submit', '.bp-send-default-courier-form', function(e) {
                var title = ($(this).find('button').attr('title') || '{{ __('Send to courier') }}');
                if (!confirm(title + '?')) {
                    e.preventDefault();
                }
            });

            // ── Quick View modal ──
            var quickViewUrls = {
                edit: '{{ route('sales.edit', ['sale' => '__ID__']) }}',
                print: '{{ route('sales.print', ['sale' => '__ID__']) }}'
            };

            $(document).on('click', '.bp-quick-view-btn', function() {
                var $btn = $(this);
                var url = $btn.data('action');
                var saleId = $btn.data('sale-id');
                var invoice = $btn.data('invoice') || '';

                $('#quickViewInvoice').text(invoice);
                $('#quickViewBody').html(
                    '<div class="text-center text-muted py-4">' +
                    '<i class="fa-solid fa-spinner fa-spin fa-2x mb-2 d-block"></i>' +
                    '<div>{{ __('Loading...') }}</div>' +
                    '</div>'
                );

                // Wire the footer action buttons to point at this sale, via named routes.
                $('#quickViewEditBtn').attr('href', quickViewUrls.edit.replace('__ID__', saleId));
                $('#quickViewPrintBtn').attr('href', quickViewUrls.print.replace('__ID__', saleId));

                bootstrap.Modal.getOrCreateInstance(document.getElementById('quickViewModal')).show();

                $.get(url)
                    .done(function(html) {
                        $('#quickViewBody').html(html);
                    })
                    .fail(function(xhr) {
                        $('#quickViewBody').html(
                            '<div class="alert alert-danger mb-0">' +
                            (xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON
                                .message : '{{ __('Failed to load sale details.') }}') +
                            '</div>'
                        );
                    });
            });

            // Fraud check
            var fraudCheckCtx = {
                action: null,
                phone: null
            };

            $(document).on('click', '.bp-fraud-check-btn', function() {
                var $btn = $(this);
                fraudCheckCtx.action = $btn.data('action');
                fraudCheckCtx.phone = $btn.data('phone');

                var $icon = $btn.find('i');
                var oldClass = $icon.attr('class');
                $icon.attr('class', 'fa-solid fa-spinner fa-spin');
                $btn.prop('disabled', true);

                runFraudCheck(false).always(function() {
                    $icon.attr('class', oldClass);
                    $btn.prop('disabled', false);
                });
            });

            $(document).on('click', '#fraudRefreshBtn', function() {
                var $rb = $(this);
                $rb.prop('disabled', true).html(
                    '<i class="fa-solid fa-spinner fa-spin me-1"></i>Re-checking...');
                runFraudCheck(true).always(function() {
                    $rb.prop('disabled', false).html(
                        '<i class="fa-solid fa-rotate me-1"></i>Re-check from API');
                });
            });

            function runFraudCheck(forceRefresh) {
                if (!fraudCheckCtx.action) return $.Deferred().reject().promise();
                var url = fraudCheckCtx.action + (forceRefresh ? '?refresh=1' : '');
                return $.get(url)
                    .done(function(res) {
                        renderFraudResult(fraudCheckCtx.phone, res);
                    })
                    .fail(function(xhr) {
                        var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Fraud check failed.';
                        $('#fraudCheckBody').html('<div class="alert alert-danger mb-0">' + msg + '</div>');
                        bootstrap.Modal.getOrCreateInstance(document.getElementById('fraudCheckModal')).show();
                    });
            }

            function renderFraudResult(phone, res) {
                var risk = (res.risk_level || 'unknown').toLowerCase();
                var riskClass = {
                    low: 'bp-badge-success',
                    medium: 'bp-badge-warning',
                    high: 'bp-badge-danger',
                    critical: 'bp-badge-danger',
                    new: 'bp-badge-info',
                    unknown: 'bp-badge-secondary'
                } [risk] || 'bp-badge-secondary';

                var report = res.report || {};
                var agg = report.aggregate || {};
                var fromCache = !!report.from_cache;
                var lastChecked = report.last_checked_at ?
                    new Date(report.last_checked_at).toLocaleString() :
                    null;

                var html = '<div class="mb-2 d-flex justify-content-between align-items-center flex-wrap gap-2">' +
                    '  <div><strong>Phone:</strong> ' + $('<div/>').text(phone).html() + '</div>' +
                    '  <span class="bp-badge ' + (fromCache ? 'bp-badge-secondary' : 'bp-badge-primary') +
                    ' fs-11">' +
                    (fromCache ? 'From cache' : 'Live from BD Courier') +
                    (lastChecked ? ' · ' + lastChecked : '') +
                    '  </span>' +
                    '</div>' +
                    '<div class="mb-3"><strong>Risk:</strong> <span class="bp-badge ' + riskClass + '">' + risk
                    .toUpperCase() + '</span></div>' +
                    '<table class="bp-table mb-3"><tbody>' +
                    '<tr><td>Total Deliveries</td><td class="fw-700">' + (agg.total_deliveries || 0) +
                    '</td></tr>' +
                    '<tr><td>Successful</td><td class="text-success fw-700">' + (agg.success_count || 0) +
                    '</td></tr>' +
                    '<tr><td>Cancelled</td><td class="text-danger fw-700">' + (agg.cancelled_count || 0) +
                    '</td></tr>' +
                    '<tr><td>Success Ratio</td><td class="fw-700">' + (agg.success_ratio || 0) + '%</td></tr>' +
                    '</tbody></table>';

                // Per-courier breakdown
                var courierData = report.courier_data || {};
                var rows = '';
                Object.keys(courierData).forEach(function(slug) {
                    var c = courierData[slug] || {};
                    rows += '<tr>' +
                        '<td>' + $('<div/>').text(c.name || slug).html() + '</td>' +
                        '<td class="text-center">' + (c.total_parcel || 0) + '</td>' +
                        '<td class="text-center text-success">' + (c.success_parcel || 0) + '</td>' +
                        '<td class="text-center text-danger">' + (c.cancelled_parcel || 0) + '</td>' +
                        '<td class="text-center fw-700">' + (c.success_ratio || 0) + '%</td>' +
                        '</tr>';
                });
                if (rows) {
                    html += '<h6 class="fw-700 fs-13 mb-2">Per-courier breakdown</h6>' +
                        '<table class="bp-table mb-3"><thead><tr>' +
                        '<th>Courier</th><th class="text-center">Total</th><th class="text-center">Success</th><th class="text-center">Cancel</th><th class="text-center">Ratio</th>' +
                        '</tr></thead><tbody>' + rows + '</tbody></table>';
                }

                // Merchant fraud reports (BD Courier extra)
                var reports = report.reports || [];
                if (reports.length > 0) {
                    var alertItems = reports.map(function(r) {
                        var when = r.created_at ? new Date(r.created_at).toLocaleDateString() : '';
                        var courier = r.courierName ? ' · ' + r.courierName : '';
                        var details = r.details ? ' — ' + $('<div/>').text(r.details).html() : '';
                        return '<li><strong>' + $('<div/>').text(r.name || '').html() + '</strong>' +
                            details +
                            '<div class="fs-11 text-muted">' + when + courier + '</div></li>';
                    }).join('');
                    html += '<div class="alert alert-danger">' +
                        '<div class="fw-700 mb-1"><i class="fa-solid fa-flag me-1"></i>Reported as fraud by other merchants (' +
                        reports.length + ')</div>' +
                        '<ul class="mb-0 ps-3 fs-12">' + alertItems + '</ul></div>';
                }

                $('#fraudCheckBody').html(html);
                bootstrap.Modal.getOrCreateInstance(document.getElementById('fraudCheckModal')).show();
            }
        });
    </script>

    {{-- Lightbox for product thumbnails in the Image column --}}
    <script src="{{ asset('vendor/venobox/venobox.min.js') }}"></script>
    <script>
        'use strict';
        $(function () {
            if ($.fn.venobox) {
                $('.venobox').venobox();
            }
        });
    </script>
@endpush
