@extends('core::layouts.master')

@section('title', $supplier->company_name)
@section('page-title', $supplier->company_name)

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Manufacturing</span>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <a href="{{ route('manufacturing.suppliers.index') }}">Suppliers</a>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>{{ $supplier->company_name }}</span>
@endsection

@section('page-actions')
  @bpCan('manufacturing.edit')
  <a href="{{ route('manufacturing.suppliers.edit', $supplier) }}" class="bp-btn bp-btn-primary"><i class="fa-solid fa-pen"></i> Edit Supplier</a>
  @endbpCan
  @bpCan('manufacturing.delete')
  <form action="{{ route('manufacturing.suppliers.destroy', $supplier) }}" method="POST" class="d-inline">
    @csrf
    @method('DELETE')
    <button type="submit" class="bp-btn bp-btn-danger" onclick="return confirm('Are you sure you want to delete {{ $supplier->company_name }}?')"><i class="fa-solid fa-trash"></i> Delete</button>
  </form>
  @endbpCan
@endsection

@section('content')

  <!-- Supplier Details -->
  <div class="bp-card mb-4">
    <div class="bp-card-body">
      <div class="row g-4">
        <!-- Contact Info -->
        <div class="col-md-6">
          <h6 class="fw-700 text-uppercase fs-12 text-muted mb-3">Contact Information</h6>
          <div class="row g-3">
            <div class="col-sm-6">
              <div class="fs-12 text-muted fw-600">Company Name</div>
              <div class="fw-700 fs-13">{{ $supplier->company_name }}</div>
            </div>
            <div class="col-sm-6">
              <div class="fs-12 text-muted fw-600">Contact Person</div>
              <div class="fw-700 fs-13">{{ $supplier->contact_person ?? '--' }}</div>
            </div>
            <div class="col-sm-6">
              <div class="fs-12 text-muted fw-600">Phone</div>
              <div class="fw-700 fs-13">{{ $supplier->phone ?? '--' }}</div>
            </div>
            <div class="col-sm-6">
              <div class="fs-12 text-muted fw-600">Email</div>
              <div class="fw-700 fs-13">{{ $supplier->email ?? '--' }}</div>
            </div>
            <div class="col-12">
              <div class="fs-12 text-muted fw-600">Address</div>
              <div class="fw-700 fs-13">{{ $supplier->address ?? '--' }}</div>
            </div>
            <div class="col-sm-6">
              <div class="fs-12 text-muted fw-600">Status</div>
              <div>
                @if($supplier->is_active)
                  <span class="bp-badge bp-badge-success">Active</span>
                @else
                  <span class="bp-badge bp-badge-danger">Inactive</span>
                @endif
              </div>
            </div>
            <div class="col-sm-6">
              <div class="fs-12 text-muted fw-600">Payment Terms</div>
              <div class="fw-700 fs-13">{{ $supplier->payment_terms ?? '--' }}</div>
            </div>
          </div>
        </div>

        <!-- Bank Info -->
        <div class="col-md-6">
          <h6 class="fw-700 text-uppercase fs-12 text-muted mb-3">Bank Information</h6>
          <div class="row g-3">
            <div class="col-sm-6">
              <div class="fs-12 text-muted fw-600">Bank Name</div>
              <div class="fw-700 fs-13">{{ $supplier->bank_name ?? '--' }}</div>
            </div>
            <div class="col-sm-6">
              <div class="fs-12 text-muted fw-600">Account Number</div>
              <div class="fw-700 fs-13">{{ $supplier->account_number ?? '--' }}</div>
            </div>
            <div class="col-sm-6">
              <div class="fs-12 text-muted fw-600">Bank Branch</div>
              <div class="fw-700 fs-13">{{ $supplier->bank_branch ?? '--' }}</div>
            </div>
            <div class="col-sm-6">
              <div class="fs-12 text-muted fw-600">Opening Balance</div>
              <div class="fw-700 fs-13">{{ currency_symbol() }} {{ number_format($supplier->opening_balance, 0) }}</div>
            </div>
          </div>

          @if($supplier->notes)
            <h6 class="fw-700 text-uppercase fs-12 text-muted mb-2 mt-4">Notes</h6>
            <div class="fs-13">{{ $supplier->notes }}</div>
          @endif
        </div>
      </div>
    </div>
  </div>

  <!-- Financial Summary -->
  <div class="row g-3 mb-4">
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-cart-shopping"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Total Purchases</div>
          <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($supplier->total_purchase, 0) }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-success"><i class="fa-solid fa-check-circle"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Total Paid</div>
          <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($supplier->total_paid, 0) }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-bangladeshi-taka-sign"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Due Balance</div>
          <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($supplier->due_balance, 0) }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-accent"><i class="fa-solid fa-hand-holding-dollar"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Advance Balance</div>
          <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($supplier->advance_balance, 0) }}</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Purchase Orders Placeholder -->
  <div class="bp-card mb-4">
    <div class="bp-card-header">
      <h5 class="bp-card-title"><i class="fa-solid fa-cart-shopping me-2"></i>Purchase Orders</h5>
    </div>
    <div class="bp-card-body">
      <div class="text-center py-4 text-muted">
        <i class="fa-solid fa-cart-shopping fa-2x mb-2 d-block"></i>
        Purchase orders will appear here once raw material procurement is implemented.
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
