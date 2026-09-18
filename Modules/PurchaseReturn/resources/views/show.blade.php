@extends('core::layouts.master')

@section('title', 'Purchase Return — ' . $purchaseReturn->return_number)
@section('page-title', $purchaseReturn->return_number)

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('purchase-returns.index') }}">Purchase Returns</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>{{ $purchaseReturn->return_number }}</span>
@endsection

@php
  $statusMap = [
    'draft' => ['class' => 'bp-badge-warning', 'icon' => 'fa-clock', 'label' => 'Draft'],
    'confirmed' => ['class' => 'bp-badge-info', 'icon' => 'fa-check', 'label' => 'Confirmed'],
    'completed' => ['class' => 'bp-badge-success', 'icon' => 'fa-check-double', 'label' => 'Completed'],
  ];
  $badge = $statusMap[$purchaseReturn->status] ?? ['class' => 'bp-badge-dark', 'icon' => 'fa-file', 'label' => ucfirst($purchaseReturn->status)];
@endphp

@section('page-actions')
@bpCan('purchases.edit')
@if($purchaseReturn->status === 'draft')
  <a href="{{ route('purchase-returns.edit', $purchaseReturn) }}" class="bp-btn bp-btn-outline"><i class="fa-solid fa-pen me-1"></i> Edit</a>
@endif
@endbpCan
<a href="{{ route('purchase-returns.print', $purchaseReturn) }}" class="bp-btn bp-btn-outline" target="_blank"><i class="fa-solid fa-print me-1"></i> Print</a>
<a href="{{ route('purchase-returns.pdf', $purchaseReturn) }}" class="bp-btn bp-btn-outline"><i class="fa-solid fa-download me-1"></i> PDF</a>
<a href="{{ route('purchase-returns.index') }}" class="bp-btn bp-btn-primary"><i class="fa-solid fa-arrow-left me-1"></i> Back</a>
@endsection

@section('content')

<!-- Return Hero Banner -->
<div class="bp-invoice-hero mb-0">
  <div class="row align-items-center">
    <div class="col-md-7">
      <div class="inv-number">{{ $purchaseReturn->return_number }}</div>
      <div class="inv-meta">
        <span class="me-3"><i class="fa-solid fa-calendar me-1"></i> {{ $purchaseReturn->return_date?->format('d M Y') ?? '—' }}</span>
        @if($purchaseReturn->purchase)
          <span class="me-3"><i class="fa-solid fa-file-invoice me-1"></i> Original: <a href="{{ route('purchases.show', $purchaseReturn->purchase) }}" class="text-white text-decoration-underline">{{ $purchaseReturn->purchase->po_number }}</a></span>
        @endif
        @if($purchaseReturn->branch)
          <span><i class="fa-solid fa-code-branch me-1"></i> {{ $purchaseReturn->branch->name ?? '—' }}</span>
        @endif
      </div>
      <div class="d-flex gap-2 mt-3">
        <span class="bp-badge {{ $badge['class'] }} bp-badge-hero"><i class="fa-solid {{ $badge['icon'] }} me-1"></i> {{ $badge['label'] }}</span>
        @if($purchaseReturn->reason)
          <span class="bp-badge bp-badge-danger bp-badge-hero"><i class="fa-solid fa-circle-exclamation me-1"></i> {{ ucfirst($purchaseReturn->reason) }}</span>
        @endif
      </div>
    </div>
    <div class="col-md-5 mt-3 mt-md-0">
      <div class="bp-invoice-hero-stat">
        <div class="stat-label">Return Value</div>
        <div class="stat-value">{{ currency_symbol() }} {{ number_format($purchaseReturn->total, 0) }}</div>
        @if($purchaseReturn->status === 'draft')
          <div class="stat-sub"><i class="fa-solid fa-clock me-1"></i> Pending completion</div>
        @elseif($purchaseReturn->status === 'completed')
          <div class="stat-sub"><i class="fa-solid fa-check-double me-1"></i> Stock deducted & supplier credited</div>
        @endif
      </div>
    </div>
  </div>
</div>

<!-- Main Content Row -->
<div class="row g-4 mt-0">

  <!-- Left Column -->
  <div class="col-xl-8">

    <!-- Supplier & Original PO -->
    <div class="row g-3 mb-4">
      <div class="col-md-6">
        <div class="bp-card h-100">
          <div class="bp-card-header">
            <h5 class="bp-card-title"><i class="fa-solid fa-building me-2 text-primary"></i>Supplier</h5>
            @if($purchaseReturn->supplier)
              <a href="{{ route('supplier.show', $purchaseReturn->supplier) }}" class="bp-btn bp-btn-sm bp-btn-outline">View Supplier</a>
            @endif
          </div>
          <div class="bp-card-body">
            @if($purchaseReturn->supplier)
              <div class="fw-800 fs-14 mb-1">{{ $purchaseReturn->supplier->company_name }}</div>
              @if($purchaseReturn->supplier->phone)
                <div class="fs-13 text-muted mb-1"><i class="fa-solid fa-phone fa-sm me-1"></i> {{ \App\Helpers\PhoneHelper::format($purchaseReturn->supplier->phone) }}</div>
              @endif
              @if($purchaseReturn->supplier->email)
                <div class="fs-13 text-muted mb-1"><i class="fa-solid fa-envelope fa-sm me-1"></i> {{ $purchaseReturn->supplier->email }}</div>
              @endif
              @if($purchaseReturn->supplier->address)
                <div class="fs-13 text-muted"><i class="fa-solid fa-location-dot fa-sm me-1"></i> {{ $purchaseReturn->supplier->address }}</div>
              @endif
            @else
              <div class="text-muted">No supplier assigned</div>
            @endif
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="bp-card h-100">
          <div class="bp-card-header">
            <h5 class="bp-card-title"><i class="fa-solid fa-file-invoice me-2 text-info"></i>Original Purchase</h5>
            @if($purchaseReturn->purchase)
              <a href="{{ route('purchases.show', $purchaseReturn->purchase) }}" class="bp-btn bp-btn-sm bp-btn-outline">View PO</a>
            @endif
          </div>
          <div class="bp-card-body">
            @if($purchaseReturn->purchase)
              <div class="bp-info-row bp-info-row-compact">
                <div class="bp-info-label bp-info-label-md">PO #</div>
                <div class="bp-info-value fw-700">{{ $purchaseReturn->purchase->po_number }}</div>
              </div>
              <div class="bp-info-row bp-info-row-compact">
                <div class="bp-info-label bp-info-label-md">PO Date</div>
                <div class="bp-info-value">{{ $purchaseReturn->purchase->po_date?->format('d M Y') ?? '—' }}</div>
              </div>
              <div class="bp-info-row bp-info-row-compact bp-info-row-last">
                <div class="bp-info-label bp-info-label-md">PO Total</div>
                <div class="bp-info-value fw-700">{{ currency_symbol() }} {{ number_format($purchaseReturn->purchase->grand_total, 0) }}</div>
              </div>
            @else
              <div class="text-muted">No linked purchase order</div>
            @endif
          </div>
        </div>
      </div>
    </div>

    <!-- Returned Items Table -->
    <x-core::table class="mb-4">
      <x-slot:filters>
        <h5 class="bp-card-title"><i class="fa-solid fa-list me-2 text-primary"></i>Returned Items</h5>
        <span class="bp-badge bp-badge-info">{{ $purchaseReturn->items->count() }} {{ Str::plural('Item', $purchaseReturn->items->count()) }}</span>
      </x-slot:filters>

      <x-core::table.header>
        <x-core::table.column>#</x-core::table.column>
        <x-core::table.column>Product</x-core::table.column>
        <x-core::table.column align="center">Return Qty</x-core::table.column>
        <x-core::table.column align="end">Unit Cost</x-core::table.column>
        <x-core::table.column align="end">Tax</x-core::table.column>
        <x-core::table.column>Reason</x-core::table.column>
        <x-core::table.column align="end">Amount</x-core::table.column>
      </x-core::table.header>

      <tbody>
        @foreach($purchaseReturn->items as $index => $item)
          <tr>
            <td class="text-muted">{{ $index + 1 }}</td>
            <td>
              <div class="d-flex align-items-center gap-2">
                <div class="bp-pos-item-img bp-pos-item-img-sm"><i class="fa-solid fa-box"></i></div>
                <div>
                  <div class="fw-700">{{ $item->product->name ?? 'Unknown' }}</div>
                  @if($item->variant)
                    <div class="fs-11 text-muted">{{ $item->variant->variant_name ?? '' }}</div>
                  @endif
                </div>
              </div>
            </td>
            <td class="text-center fw-700">{{ $item->quantity }}</td>
            <td class="text-end">{{ currency_symbol() }} {{ number_format($item->unit_price, 0) }}</td>
            <td class="text-end">{{ $item->tax_amount > 0 ? currency_symbol() . ' ' . number_format($item->tax_amount, 0) : '—' }}</td>
            <td class="fs-12 text-muted">{{ $item->reason ?? '—' }}</td>
            <td class="text-end fw-800">{{ currency_symbol() }} {{ number_format($item->line_total, 0) }}</td>
          </tr>
        @endforeach
      </tbody>

      <x-slot:pagination>
        <div class="bp-card-footer">
          <div class="row justify-content-end">
            <div class="col-md-5">
              <div class="bp-cart-summary-row">
                <span>Subtotal</span>
                <span class="fw-700">{{ currency_symbol() }} {{ number_format($purchaseReturn->subtotal, 0) }}</span>
              </div>
              @if($purchaseReturn->tax_amount > 0)
                <div class="bp-cart-summary-row">
                  <span>Tax</span>
                  <span>{{ currency_symbol() }} {{ number_format($purchaseReturn->tax_amount, 0) }}</span>
                </div>
              @endif
              <div class="bp-cart-summary-row total">
                <span>Total Return Value</span>
                <span>{{ currency_symbol() }} {{ number_format($purchaseReturn->total, 0) }}</span>
              </div>
            </div>
          </div>
        </div>
      </x-slot:pagination>
    </x-core::table>

    <!-- Notes -->
    @if($purchaseReturn->reason || $purchaseReturn->notes)
    <div class="bp-card mb-4">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-note-sticky me-2 text-muted"></i>Return Notes</h5>
      </div>
      <div class="bp-card-body">
        <div class="row g-3">
          @if($purchaseReturn->reason)
            <div class="col-md-6">
              <label class="bp-form-label">Reason</label>
              <div class="bp-form-control bp-form-control-static">{{ $purchaseReturn->reason }}</div>
            </div>
          @endif
          @if($purchaseReturn->notes)
            <div class="col-md-6">
              <label class="bp-form-label">Notes</label>
              <div class="bp-form-control bp-form-control-static">{{ $purchaseReturn->notes }}</div>
            </div>
          @endif
        </div>
      </div>
    </div>
    @endif

  </div>

  <!-- Right Column -->
  <div class="col-xl-4">

    <!-- Return Info -->
    <div class="bp-card mb-4">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-undo me-2 text-primary"></i>Return Details</h5>
      </div>
      <div class="bp-card-body">
        <div class="bp-info-row">
          <div class="bp-info-label bp-info-label-lg">Return #</div>
          <div class="bp-info-value fw-700">{{ $purchaseReturn->return_number }}</div>
        </div>
        <div class="bp-info-row">
          <div class="bp-info-label bp-info-label-lg">Return Date</div>
          <div class="bp-info-value">{{ $purchaseReturn->return_date?->format('d M Y') ?? '—' }}</div>
        </div>
        <div class="bp-info-row">
          <div class="bp-info-label bp-info-label-lg">Status</div>
          <div class="bp-info-value"><span class="bp-badge {{ $badge['class'] }}">{{ $badge['label'] }}</span></div>
        </div>
        @if($purchaseReturn->reason)
          <div class="bp-info-row">
            <div class="bp-info-label bp-info-label-lg">Reason</div>
            <div class="bp-info-value fw-600">{{ ucfirst($purchaseReturn->reason) }}</div>
          </div>
        @endif
        <div class="bp-info-row">
          <div class="bp-info-label bp-info-label-lg">Created By</div>
          <div class="bp-info-value fw-600">{{ $purchaseReturn->createdBy->name ?? '—' }}</div>
        </div>
        @if($purchaseReturn->branch)
          <div class="bp-info-row bp-info-row-last">
            <div class="bp-info-label bp-info-label-lg">Branch</div>
            <div class="bp-info-value">{{ $purchaseReturn->branch->name ?? '—' }}</div>
          </div>
        @endif
      </div>
    </div>

    <!-- Actions -->
    @if($purchaseReturn->status === 'draft')
    <div class="d-flex flex-column gap-2 mb-4">
        @bpCan('purchases.edit')
        <form action="{{ route('purchase-returns.complete', $purchaseReturn) }}" method="POST">
          @csrf
          <button type="submit" class="bp-btn bp-btn-success w-100 justify-content-center" onclick="return confirm('Complete this return? Stock will be deducted and supplier credited.')"><i class="fa-solid fa-check-double me-2"></i> Complete Return</button>
        </form>
        @endbpCan
        @bpCan('purchases.edit')
        <a href="{{ route('purchase-returns.edit', $purchaseReturn) }}" class="bp-btn bp-btn-outline w-100 justify-content-center"><i class="fa-solid fa-pen me-2"></i> Edit Return</a>
        @endbpCan
    </div>
    @endif

    @if($purchaseReturn->status === 'draft')
    <div class="d-flex flex-column gap-2 mb-4">
      @bpCan('purchases.delete')
      <form action="{{ route('purchase-returns.destroy', $purchaseReturn) }}" method="POST">
        @csrf
        @method('DELETE')
        <button type="submit" class="bp-btn bp-btn-danger w-100 justify-content-center delete-confirm" data-name="{{ $purchaseReturn->return_number }}"><i class="fa-solid fa-trash me-2"></i> Delete Return</button>
      </form>
      @endbpCan
    </div>
    @endif

  </div>
</div>

@endsection
