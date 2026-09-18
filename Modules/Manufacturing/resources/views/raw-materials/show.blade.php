@extends('core::layouts.master')

@section('title', $rawMaterial->name)
@section('page-title', $rawMaterial->name)

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Manufacturing</span>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <a href="{{ route('manufacturing.raw-materials.index') }}">Raw Materials</a>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>{{ $rawMaterial->name }}</span>
@endsection

@section('page-actions')
  @bpCan('manufacturing.edit')
  <a href="{{ route('manufacturing.raw-materials.edit', $rawMaterial) }}" class="bp-btn bp-btn-primary"><i class="fa-solid fa-pen"></i> Edit</a>
  @endbpCan
  @bpCan('manufacturing.delete')
  <form action="{{ route('manufacturing.raw-materials.destroy', $rawMaterial) }}" method="POST" class="d-inline">
    @csrf
    @method('DELETE')
    <button type="submit" class="bp-btn bp-btn-danger" onclick="return confirm('Are you sure you want to delete {{ $rawMaterial->name }}?')"><i class="fa-solid fa-trash"></i> Delete</button>
  </form>
  @endbpCan
@endsection

@section('content')

  <!-- Material Details Card -->
  <div class="bp-card mb-4">
    <div class="bp-card-header">
      <h5 class="bp-card-title"><i class="fa-solid fa-cubes me-2"></i>Material Details</h5>
    </div>
    <div class="bp-card-body">
      <div class="row g-3">
        <div class="col-md-3">
          <div class="fs-12 text-muted fw-600">Code</div>
          <div class="fw-700 fs-13">{{ $rawMaterial->code }}</div>
        </div>
        <div class="col-md-3">
          <div class="fs-12 text-muted fw-600">Name</div>
          <div class="fw-700 fs-13">{{ $rawMaterial->name }}</div>
        </div>
        <div class="col-md-3">
          <div class="fs-12 text-muted fw-600">Category</div>
          <div>
            @switch($rawMaterial->category)
              @case('fabric')
                <span class="bp-badge bp-badge-primary">Fabric</span>
                @break
              @case('thread')
                <span class="bp-badge bp-badge-info">Thread</span>
                @break
              @case('button')
                <span class="bp-badge bp-badge-secondary">Button</span>
                @break
              @case('zipper')
                <span class="bp-badge bp-badge-warning">Zipper</span>
                @break
              @default
                <span class="bp-badge bp-badge-dark">Other</span>
            @endswitch
          </div>
        </div>
        <div class="col-md-3">
          <div class="fs-12 text-muted fw-600">Unit</div>
          <div class="fw-700 fs-13">{{ ucfirst($rawMaterial->unit) }}</div>
        </div>
        <div class="col-md-3">
          <div class="fs-12 text-muted fw-600">Cost Price</div>
          <div class="fw-700 fs-13">{{ money($rawMaterial->cost_price) }}</div>
        </div>
        <div class="col-md-3">
          <div class="fs-12 text-muted fw-600">Last Purchase Price</div>
          <div class="fw-700 fs-13">{{ $rawMaterial->last_purchase_price ? currency_symbol() . ' ' . num($rawMaterial->last_purchase_price) : '--' }}</div>
        </div>
        <div class="col-md-3">
          <div class="fs-12 text-muted fw-600">Reorder Level</div>
          <div class="fw-700 fs-13">{{ $rawMaterial->reorder_level ?? '--' }}</div>
        </div>
        <div class="col-md-3">
          <div class="fs-12 text-muted fw-600">Status</div>
          <div>
            @if($rawMaterial->is_active)
              <span class="bp-badge bp-badge-success">Active</span>
            @else
              <span class="bp-badge bp-badge-danger">Inactive</span>
            @endif
          </div>
        </div>
        <div class="col-md-3">
          <div class="fs-12 text-muted fw-600">Created</div>
          <div class="fw-700 fs-13">{{ $rawMaterial->created_at?->format('d M Y') ?? '--' }}</div>
        </div>
        @if($rawMaterial->notes)
        <div class="col-12">
          <div class="fs-12 text-muted fw-600">Notes</div>
          <div class="fs-13">{{ $rawMaterial->notes }}</div>
        </div>
        @endif
      </div>
    </div>
  </div>

  <!-- Stock Info Placeholder -->
  <div class="bp-card mb-4">
    <div class="bp-card-header">
      <h5 class="bp-card-title"><i class="fa-solid fa-boxes-stacked me-2"></i>Stock Information</h5>
    </div>
    <div class="bp-card-body">
      <div class="text-center py-4 text-muted">
        <i class="fa-solid fa-boxes-stacked fa-2x mb-2 d-block"></i>
        Stock tracking will be available once inventory management is implemented.
      </div>
    </div>
  </div>

  <!-- Purchase History Placeholder -->
  <div class="bp-card">
    <div class="bp-card-header">
      <h5 class="bp-card-title"><i class="fa-solid fa-cart-shopping me-2"></i>Purchase History</h5>
    </div>
    <div class="bp-card-body">
      <div class="text-center py-4 text-muted">
        <i class="fa-solid fa-cart-shopping fa-2x mb-2 d-block"></i>
        Purchase history will appear here once procurement is implemented.
      </div>
    </div>
  </div>

@endsection
