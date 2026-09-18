@extends('core::layouts.master')

@section('title', $factory->name)
@section('page-title', $factory->name)

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Manufacturing</span>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <a href="{{ route('manufacturing.factories.index') }}">Factories</a>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>{{ $factory->name }}</span>
@endsection

@section('page-actions')
  @bpCan('manufacturing.edit')
  <a href="{{ route('manufacturing.factories.edit', $factory) }}" class="bp-btn bp-btn-primary"><i class="fa-solid fa-pen"></i> Edit Factory</a>
  @endbpCan
  @bpCan('manufacturing.delete')
  <form action="{{ route('manufacturing.factories.destroy', $factory) }}" method="POST" class="d-inline">
    @csrf
    @method('DELETE')
    <button type="submit" class="bp-btn bp-btn-danger" onclick="return confirm('Are you sure you want to delete {{ $factory->name }}?')"><i class="fa-solid fa-trash"></i> Delete</button>
  </form>
  @endbpCan
@endsection

@section('content')

  <!-- Factory Info Card -->
  <div class="bp-card mb-4">
    <div class="bp-card-header">
      <h5 class="bp-card-title"><i class="fa-solid fa-industry me-2"></i>Factory Details</h5>
    </div>
    <div class="bp-card-body">
      <div class="row g-3">
        <div class="col-md-3">
          <div class="fs-12 text-muted fw-600">Code</div>
          <div class="fw-700 fs-13">{{ $factory->code }}</div>
        </div>
        <div class="col-md-3">
          <div class="fs-12 text-muted fw-600">Name</div>
          <div class="fw-700 fs-13">{{ $factory->name }}</div>
        </div>
        <div class="col-md-3">
          <div class="fs-12 text-muted fw-600">Contact Person</div>
          <div class="fw-700 fs-13">{{ $factory->contact_person ?? '--' }}</div>
        </div>
        <div class="col-md-3">
          <div class="fs-12 text-muted fw-600">Phone</div>
          <div class="fw-700 fs-13">{{ $factory->phone ?? '--' }}</div>
        </div>
        <div class="col-md-3">
          <div class="fs-12 text-muted fw-600">Email</div>
          <div class="fw-700 fs-13">{{ $factory->email ?? '--' }}</div>
        </div>
        <div class="col-md-3">
          <div class="fs-12 text-muted fw-600">Payment Terms</div>
          <div class="fw-700 fs-13">{{ $factory->payment_terms ?? '--' }}</div>
        </div>
        <div class="col-md-3">
          <div class="fs-12 text-muted fw-600">Status</div>
          <div>
            @if($factory->is_active)
              <span class="bp-badge bp-badge-success">Active</span>
            @else
              <span class="bp-badge bp-badge-danger">Inactive</span>
            @endif
          </div>
        </div>
        <div class="col-md-3">
          <div class="fs-12 text-muted fw-600">Created</div>
          <div class="fw-700 fs-13">{{ $factory->created_at?->format('d M Y') ?? '--' }}</div>
        </div>
        <div class="col-12">
          <div class="fs-12 text-muted fw-600">Address</div>
          <div class="fw-700 fs-13">{{ $factory->address ?? '--' }}</div>
        </div>
        @if($factory->notes)
        <div class="col-12">
          <div class="fs-12 text-muted fw-600">Notes</div>
          <div class="fs-13">{{ $factory->notes }}</div>
        </div>
        @endif
      </div>
    </div>
  </div>

  <!-- Financial Summary -->
  <div class="row g-3 mb-4">
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-clipboard-list"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Total Orders</div>
          <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($factory->total_orders, 0) }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-success"><i class="fa-solid fa-check-circle"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Total Paid</div>
          <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($factory->total_paid, 0) }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-bangladeshi-taka-sign"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Due Balance</div>
          <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($factory->due_balance, 0) }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-accent"><i class="fa-solid fa-hand-holding-dollar"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Advance Balance</div>
          <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($factory->advance_balance, 0) }}</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Production Orders Placeholder -->
  <div class="bp-card mb-4">
    <div class="bp-card-header">
      <h5 class="bp-card-title"><i class="fa-solid fa-clipboard-list me-2"></i>Production Orders</h5>
    </div>
    <div class="bp-card-body">
      <div class="text-center py-4 text-muted">
        <i class="fa-solid fa-clipboard-list fa-2x mb-2 d-block"></i>
        Production orders will appear here once production management is implemented.
      </div>
    </div>
  </div>

  <!-- Payments Placeholder -->
  <div class="bp-card">
    <div class="bp-card-header">
      <h5 class="bp-card-title"><i class="fa-solid fa-bangladeshi-taka-sign me-2"></i>Payments</h5>
    </div>
    <div class="bp-card-body">
      <div class="text-center py-4 text-muted">
        <i class="fa-solid fa-bangladeshi-taka-sign fa-2x mb-2 d-block"></i>
        Payment history will appear here once payment integration is implemented.
      </div>
    </div>
  </div>

@endsection
