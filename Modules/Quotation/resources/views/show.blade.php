@extends('core::layouts.master')

@section('title', 'Quotation Detail — ' . $quotation->quotation_number)
@section('page-title', $quotation->quotation_number)

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('quotations.index') }}">Quotations</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>{{ $quotation->quotation_number }}</span>
@endsection

@section('page-actions')
    @bpCan('quotations.edit')
        @if (in_array($quotation->status, ['draft', 'pending']))
            <a href="{{ route('quotations.edit', $quotation) }}" class="bp-btn bp-btn-danger"><i class="fa-solid fa-pen me-1"></i>
                Edit</a>
        @endif
        @if ($quotation->isConvertible())
            <form action="{{ route('quotations.convert-to-sale', $quotation) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-cart-shopping me-1"></i> Convert to
                    Sale</button>
            </form>
        @endif
    @endbpCan
    <a href="{{ route('quotations.print', $quotation) }}" class="bp-btn bp-btn-info" target="_blank"><i
            class="fa-solid fa-print me-1"></i> Print</a>
    <a href="{{ route('quotations.index') }}" class="bp-btn bp-btn-primary"><i class="fa-solid fa-arrow-left me-1"></i>
        Back</a>
@endsection

@php
    $statusMap = [
        'draft' => ['class' => 'bp-badge-dark', 'icon' => 'fa-file'],
        'pending' => ['class' => 'bp-badge-warning', 'icon' => 'fa-clock'],
        'accepted' => ['class' => 'bp-badge-success', 'icon' => 'fa-circle-check'],
        'rejected' => ['class' => 'bp-badge-danger', 'icon' => 'fa-circle-xmark'],
        'expired' => ['class' => 'bp-badge-danger', 'icon' => 'fa-calendar-xmark'],
        'converted' => ['class' => 'bp-badge-info', 'icon' => 'fa-cart-shopping'],
    ];
    $badge = $statusMap[$quotation->status] ?? ['class' => 'bp-badge-dark', 'icon' => 'fa-file'];
    $daysRemaining = (int) now()
        ->startOfDay()
        ->diffInDays($quotation->valid_until->copy()->startOfDay(), false);
@endphp

@section('content')

    <!-- Quotation Hero Banner -->
    <div class="bp-invoice-hero mb-0">
        <div class="row align-items-center">
            <div class="col-md-7">
                <div class="inv-number">{{ $quotation->quotation_number }}</div>
                <div class="inv-meta">
                    <span class="me-3"><i class="fa-solid fa-calendar me-1"></i>
                        {{ $quotation->quotation_date->format('d M Y') }}</span>
                    <span class="me-3"><i class="fa-solid fa-clock me-1"></i> Valid Until:
                        {{ $quotation->valid_until->format('d M Y') }}</span>
                    @if ($quotation->branch)
                        <span><i class="fa-solid fa-code-branch me-1"></i> {{ $quotation->branch->name }}</span>
                    @endif
                </div>
                <div class="d-flex gap-2 mt-3">
                    <span class="bp-badge {{ $badge['class'] }} bp-badge-hero"><i
                            class="fa-solid {{ $badge['icon'] }} me-1"></i> {{ ucfirst($quotation->status) }}</span>
                </div>
            </div>
            <div class="col-md-5 mt-3 mt-md-0">
                <div class="bp-invoice-hero-stat">
                    <div class="stat-label">Grand Total</div>
                    <div class="stat-value">{{ currency_symbol() }} {{ number_format($quotation->grand_total, 0) }}</div>
                    @if ($quotation->status === 'converted' && $quotation->convertedSale)
                        <div class="stat-sub"><i class="fa-solid fa-circle-check me-1"></i> Converted to Sale
                            #{{ $quotation->convertedSale->sale_number ?? $quotation->convertedSale->id }}</div>
                    @elseif($quotation->status === 'expired')
                        <div class="stat-sub"><i class="fa-solid fa-calendar-xmark me-1"></i> Quotation expired</div>
                    @elseif($daysRemaining > 0)
                        <div class="stat-sub"><i class="fa-solid fa-clock me-1"></i> {{ $daysRemaining }} days remaining
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Row -->
    <div class="row g-4 mt-0">

        <!-- Left Column -->
        <div class="col-xl-8">

            <!-- Customer Info -->
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="bp-card h-100">
                        <div class="bp-card-header">
                            <h5 class="bp-card-title"><i class="fa-solid fa-user me-2 text-primary"></i>Customer</h5>
                            @if ($quotation->customer)
                                <a href="{{ route('customers.show', $quotation->customer->id) }}"
                                    class="bp-btn bp-btn-sm bp-btn-outline">View Customer</a>
                            @endif
                        </div>
                        <div class="bp-card-body">
                            @if ($quotation->customer)
                                <div class="fw-800 fs-14 mb-1">{{ $quotation->customer->name }}</div>
                                @if ($quotation->customer->phone)
                                    <div class="fs-13 text-muted mb-1"><i class="fa-solid fa-phone fa-sm me-1"></i>
                                        {{ $quotation->customer->phone }}</div>
                                @endif
                                @if ($quotation->customer->email)
                                    <div class="fs-13 text-muted mb-1"><i class="fa-solid fa-envelope fa-sm me-1"></i>
                                        {{ $quotation->customer->email }}</div>
                                @endif
                                @if ($quotation->customer->address)
                                    <div class="fs-13 text-muted"><i class="fa-solid fa-location-dot fa-sm me-1"></i>
                                        {{ $quotation->customer->address }}</div>
                                @endif
                            @else
                                <div class="text-muted">Walk-in Customer</div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="bp-card h-100">
                        <div class="bp-card-header">
                            <h5 class="bp-card-title"><i class="fa-solid fa-file-lines me-2 text-warning"></i>Quotation Info
                            </h5>
                        </div>
                        <div class="bp-card-body">
                            <div class="bp-info-row bp-info-row-compact">
                                <div class="bp-info-label bp-info-label-md">Reference</div>
                                <div class="bp-info-value fw-600">{{ $quotation->reference ?? '-' }}</div>
                            </div>
                            <div class="bp-info-row bp-info-row-compact">
                                <div class="bp-info-label bp-info-label-md">Created By</div>
                                <div class="bp-info-value fw-600">{{ $quotation->creator->name ?? '-' }}</div>
                            </div>
                            <div class="bp-info-row bp-info-row-compact">
                                <div class="bp-info-label bp-info-label-md">Created On</div>
                                <div class="bp-info-value fw-600">{{ $quotation->created_at->format('d M Y, h:i A') }}
                                </div>
                            </div>
                            <div class="bp-info-row bp-info-row-compact bp-info-row-last">
                                <div class="bp-info-label bp-info-label-md">Days Remaining</div>
                                <div class="bp-info-value">
                                    @if ($daysRemaining > 0)
                                        <span class="bp-badge bp-badge-warning">{{ $daysRemaining }} days</span>
                                    @elseif($quotation->status === 'converted')
                                        <span class="bp-badge bp-badge-info">Converted</span>
                                    @else
                                        <span class="bp-badge bp-badge-danger">Expired</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Line Items Table -->
            <x-core::table class="mb-4">
                <x-slot:filters>
                    <h5 class="bp-card-title"><i class="fa-solid fa-list me-2 text-primary"></i>Quoted Items</h5>
                    <span class="bp-badge bp-badge-info">{{ $quotation->items->count() }}
                        {{ Str::plural('Item', $quotation->items->count()) }}</span>
                </x-slot:filters>

                <x-core::table.header>
                    <x-core::table.column>#</x-core::table.column>
                    <x-core::table.column>Product</x-core::table.column>
                    <x-core::table.column align="center">Qty</x-core::table.column>
                    <x-core::table.column align="center">Discount</x-core::table.column>
                    <x-core::table.column align="center">Total</x-core::table.column>
                </x-core::table.header>

                <tbody>
                    @foreach ($quotation->items as $index => $item)
                        <tr>
                            <td class="text-muted">{{ $index + 1 }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @php $itemImg = $item->product?->display_image; @endphp
                                    <div class="bp-pos-item-img bp-pos-item-img-sm">
                                        @if ($itemImg)
                                            <img src="{{ upload_url($itemImg) }}" alt="">
                                        @else
                                            <i class="fa-solid fa-box"></i>
                                        @endif
                                    </div>
                                    <div class="w-100">
                                        <div class="fw-700">{{ $item->product_name }}</div>
                                        @php $breakdown = is_array($item->variant_breakdown) ? $item->variant_breakdown : []; @endphp
                                        @if (count($breakdown))
                                            <div class="bp-qline-variants mt-1">
                                                @foreach ($breakdown as $b)
                                                    <div class="bp-qline-v-row">
                                                        <span class="bp-qline-v-name">{{ $b['name'] ?? '' }}</span>
                                                        <span class="bp-qline-v-qty">{{ $b['qty'] ?? 0 }} pcs</span>
                                                        <span class="bp-qline-v-price">{{ currency_symbol() }}
                                                            {{ number_format($b['price'] ?? 0, 0) }}<span
                                                                class="bp-qline-v-unit">/pcs</span></span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @elseif($item->variant)
                                            <div class="fs-11 text-muted">{{ $item->variant->variant_name }}</div>
                                        @elseif($item->custom_note)
                                            <div class="fs-11 text-muted">{{ $item->custom_note }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="text-center fw-700">{{ $item->quantity }}</td>
                            <td class="text-center {{ $item->discount_amount > 0 ? 'text-danger' : 'text-muted' }}">
                                {{ currency_symbol() }} {{ number_format($item->discount_amount, 0) }}</td>
                            <td class="text-center">{{ currency_symbol() }}
                                {{ number_format($item->subtotal, 0) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>

                <!-- Totals Summary -->
                <x-slot:pagination>
                    <div class="bp-card-footer">
                        <div class="row justify-content-end">
                            <div class="col-md-5">
                                <div class="bp-cart-summary-row">
                                    <span>Subtotal</span>
                                    <span>{{ currency_symbol() }}
                                        {{ number_format($quotation->subtotal, 0) }}</span>
                                </div>
                                @if ($quotation->discount_amount > 0)
                                    <div class="bp-cart-summary-row">
                                        <span>Discount{{ $quotation->discount_type === 'percentage' ? ' (' . number_format($quotation->discount_value, 0) . '%)' : '' }}</span>
                                        <span class="text-danger">- {{ currency_symbol() }}
                                            {{ number_format($quotation->discount_amount, 0) }}</span>
                                    </div>
                                @endif
                                @if ($quotation->shipping_charge > 0)
                                    <div class="bp-cart-summary-row">
                                        <span>Shipping</span>
                                        <span>{{ currency_symbol() }}
                                            {{ number_format($quotation->shipping_charge, 0) }}</span>
                                    </div>
                                @endif
                                <div class="bp-cart-summary-row total">
                                    <span>Grand Total</span>
                                    <span>{{ currency_symbol() }} {{ number_format($quotation->grand_total, 0) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </x-slot:pagination>
            </x-core::table>

            <!-- Terms & Conditions -->
            @if ($quotation->notes || $quotation->terms)
                <div class="bp-card mb-4 mt-4">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-note-sticky me-2 text-muted"></i>Notes & Terms
                        </h5>
                    </div>
                    <div class="bp-card-body">
                        <div class="row g-3">
                            @if ($quotation->notes)
                                <div class="{{ $quotation->terms ? 'col-md-6' : 'col-md-12' }}">
                                    <label class="bp-form-label">Customer Notes</label>
                                    <div class="bp-form-control bp-form-control-static">{{ $quotation->notes }}</div>
                                </div>
                            @endif
                            @if ($quotation->terms)
                                <div class="{{ $quotation->notes ? 'col-md-6' : 'col-md-12' }}">
                                    <label class="bp-form-label">Terms & Conditions</label>
                                    <textarea class="bp-form-control" name="terms" rows="3" placeholder="Payment terms, warranty info...">{{ $quotation->terms }}</textarea>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

        </div>

        <!-- Right Column -->
        <div class="col-xl-4">

            <!-- Quotation Status -->
            <div class="bp-card mb-4">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-file-lines me-2 text-primary"></i>Quotation Details
                    </h5>
                </div>
                <div class="bp-card-body">
                    <div class="bp-info-row">
                        <div class="bp-info-label bp-info-label-lg">Quotation #</div>
                        <div class="bp-info-value fw-700">{{ $quotation->quotation_number }}</div>
                    </div>
                    <div class="bp-info-row">
                        <div class="bp-info-label bp-info-label-lg">Date</div>
                        <div class="bp-info-value">{{ $quotation->quotation_date->format('d M Y') }}</div>
                    </div>
                    <div class="bp-info-row">
                        <div class="bp-info-label bp-info-label-lg">Valid Until</div>
                        <div class="bp-info-value">{{ $quotation->valid_until->format('d M Y') }}</div>
                    </div>
                    <div class="bp-info-row">
                        <div class="bp-info-label bp-info-label-lg">Status</div>
                        <div class="bp-info-value"><span
                                class="bp-badge {{ $badge['class'] }}">{{ ucfirst($quotation->status) }}</span></div>
                    </div>
                    @if ($quotation->branch)
                        <div class="bp-info-row">
                            <div class="bp-info-label bp-info-label-lg">Branch</div>
                            <div class="bp-info-value">{{ $quotation->branch->name }}</div>
                        </div>
                    @endif
                    <div class="bp-info-row">
                        <div class="bp-info-label bp-info-label-lg">Created By</div>
                        <div class="bp-info-value fw-600">{{ $quotation->creator->name ?? '-' }}</div>
                    </div>
                    <div class="bp-info-row bp-info-row-last">
                        <div class="bp-info-label bp-info-label-lg">Reference</div>
                        <div class="bp-info-value">{{ $quotation->reference ?? '-' }}</div>
                    </div>
                </div>
            </div>

            <!-- Danger Zone -->
            <div class="d-flex flex-column gap-2 mb-4">
                @bpCan('quotations.create')
                    <form action="{{ route('quotations.duplicate', $quotation) }}" method="POST">
                        @csrf
                        <button type="submit" class="bp-btn bp-btn-warning w-100 justify-content-center"><i
                                class="fa-solid fa-copy me-2"></i> Duplicate Quotation</button>
                    </form>
                @endbpCan
                @bpCan('quotations.delete')
                    <form action="{{ route('quotations.destroy', $quotation) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="bp-btn bp-btn-danger w-100 justify-content-center delete-confirm"
                            data-name="{{ $quotation->quotation_number }}"><i class="fa-solid fa-trash me-2"></i> Delete
                            Quotation</button>
                    </form>
                @endbpCan
            </div>

        </div>
    </div>

@endsection

@push('scripts')
    <script>
        'use strict';

        $(function() {
            $('[data-action="print"]').on('click', function() {
                window.print();
            });
        });
    </script>
@endpush
