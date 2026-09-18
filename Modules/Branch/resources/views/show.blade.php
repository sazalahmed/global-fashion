@extends('core::layouts.master')

@section('title', $branch->name ?? 'Branch Details')
@section('page-title', $branch->name ?? 'Branch Details')

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <a href="{{ route('branches.index') }}">Branches</a>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>{{ $branch->name ?? 'Branch' }}</span>
@endsection

@section('page-actions')
  <a href="{{ route('branches.index') }}" class="bp-btn bp-btn-primary"><i class="fa-solid fa-arrow-left me-1"></i> Back</a>
  <a href="{{ route('branches.edit', $branch->id ?? 0) }}" class="bp-btn bp-btn-info"><i class="fa-solid fa-pen me-1"></i> Edit Branch</a>
@endsection

@section('content')

  <div class="row g-4">

    <!-- Left Column -->
    <div class="col-xl-4">

      <!-- Branch Info Card -->
      <div class="bp-card mb-4">
        <div class="bp-card-body text-center bp-user-profile-card">
          <div class="bp-user-avatar-lg mb-3">
            @if($branch->logo ?? null)
              <img src="{{ upload_url($branch->logo) }}" alt="{{ $branch->name }}">
            @else
              <span><i class="fa-solid fa-store"></i></span>
            @endif
          </div>
          <h5 class="fw-800 mb-1">{{ $branch->name ?? '' }}</h5>
          <div class="fs-13 text-muted mb-2">{{ $branch->code ?? '' }}</div>
          @if($branch->is_main ?? false)
            <span class="bp-badge bp-badge-primary">Main Branch</span>
          @endif
          @if($branch->is_active ?? true)
            <span class="bp-badge bp-badge-success">Active</span>
          @else
            <span class="bp-badge bp-badge-danger">Inactive</span>
          @endif
        </div>
      </div>

      <!-- Details -->
      <div class="bp-card">
        <div class="bp-card-header">
          <h5 class="bp-card-title"><i class="fa-solid fa-circle-info me-2 text-primary"></i>Details</h5>
        </div>
        <div class="bp-card-body">
          <div class="bp-info-row">
            <div class="bp-info-label">Phone</div>
            <div class="bp-info-value">{{ $branch->phone ?? '--' }}</div>
          </div>
          <div class="bp-info-row">
            <div class="bp-info-label">Email</div>
            <div class="bp-info-value">{{ $branch->email ?? '--' }}</div>
          </div>
          <div class="bp-info-row">
            <div class="bp-info-label">Address</div>
            <div class="bp-info-value">{{ $branch->address ?? '--' }}</div>
          </div>
          <div class="bp-info-row">
            <div class="bp-info-label">City</div>
            <div class="bp-info-value">{{ $branch->city ?? '--' }}</div>
          </div>
          <div class="bp-info-row">
            <div class="bp-info-label">District</div>
            <div class="bp-info-value">{{ $branch->district ?? '--' }}</div>
          </div>
          <div class="bp-info-row">
            <div class="bp-info-label">Manager</div>
            <div class="bp-info-value fw-600">{{ $branch->manager_name ?? '--' }}</div>
          </div>
          <div class="bp-info-row">
            <div class="bp-info-label">Manager Phone</div>
            <div class="bp-info-value">{{ $branch->manager_phone ?? '--' }}</div>
          </div>
          <div class="bp-info-row">
            <div class="bp-info-label">Opening</div>
            <div class="bp-info-value">{{ $branch->opening_time ?? '--' }} - {{ $branch->closing_time ?? '--' }}</div>
          </div>
          <div class="bp-info-row">
            <div class="bp-info-label">POS</div>
            <div class="bp-info-value">
              @if($branch->is_pos_enabled ?? false)
                <span class="bp-badge bp-badge-success">Enabled</span>
              @else
                <span class="bp-badge bp-badge-danger">Disabled</span>
              @endif
            </div>
          </div>
          <div class="bp-info-row">
            <div class="bp-info-label">eCommerce</div>
            <div class="bp-info-value">
              @if($branch->is_ecom_enabled ?? false)
                <span class="bp-badge bp-badge-success">Enabled</span>
              @else
                <span class="bp-badge bp-badge-danger">Disabled</span>
              @endif
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- Right Column -->
    <div class="col-xl-8">

      <!-- Assigned Users -->
      <div class="bp-card mb-4">
        <div class="bp-card-header">
          <h5 class="bp-card-title"><i class="fa-solid fa-users me-2 text-info"></i>Assigned Users</h5>
        </div>
        <div class="bp-card-body p-0">
          <div class="bp-table-wrapper">
            <table class="bp-table">
              <x-core::table.header>
                <x-core::table.column>Name</x-core::table.column>
                <x-core::table.column>Email</x-core::table.column>
                <x-core::table.column>Role</x-core::table.column>
                <x-core::table.column>Status</x-core::table.column>
              </x-core::table.header>
              <tbody>
                @forelse($users ?? [] as $user)
                <tr>
                  <td class="fw-600">{{ $user->name }}</td>
                  <td>{{ $user->email }}</td>
                  <td><span class="bp-badge bp-badge-primary">{{ $user->roles->first()->name ?? '--' }}</span></td>
                  <td>
                    @if($user->status === 'active')
                      <span class="bp-badge bp-badge-success">Active</span>
                    @else
                      <span class="bp-badge bp-badge-danger">Inactive</span>
                    @endif
                  </td>
                </tr>
                @empty
                <x-core::table.empty :colspan="4" icon="fa-users" title="No users assigned to this branch." />
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Recent Sales -->
      <div class="bp-card">
        <div class="bp-card-header">
          <h5 class="bp-card-title"><i class="fa-solid fa-chart-line me-2 text-success"></i>Recent Sales</h5>
        </div>
        <div class="bp-card-body p-0">
          <div class="bp-table-wrapper">
            <table class="bp-table">
              <x-core::table.header>
                <x-core::table.column>Invoice</x-core::table.column>
                <x-core::table.column>Date</x-core::table.column>
                <x-core::table.column>Customer</x-core::table.column>
                <x-core::table.column>Total</x-core::table.column>
                <x-core::table.column>Status</x-core::table.column>
              </x-core::table.header>
              <tbody>
                @forelse($recentSales ?? [] as $sale)
                <tr>
                  <td class="fw-700">{{ $sale->invoice_number }}</td>
                  <td>{{ $sale->created_at->format('d M Y') }}</td>
                  <td>{{ $sale->customer_display_name }}</td>
                  <td class="fw-700">{{ currency_symbol() }} {{ $sale->total ?? '0' }}</td>
                  <td><span class="bp-badge bp-badge-success">{{ ucfirst($sale->status ?? '') }}</span></td>
                </tr>
                @empty
                <x-core::table.empty :colspan="5" icon="fa-receipt" title="No recent sales for this branch." />
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>

  </div>

@endsection
