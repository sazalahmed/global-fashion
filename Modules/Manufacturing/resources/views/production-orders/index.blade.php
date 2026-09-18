@extends('core::layouts.master')

@section('title', __("Production Orders"))
@section('page-title', __("Production Orders"))

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Manufacturing</span>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Production Orders</span>
@endsection

@section('page-actions')
  @bpCan('manufacturing.create')
  <a href="{{ route('manufacturing.production-orders.create') }}" class="bp-btn bp-btn-primary">
    <i class="fa-solid fa-plus"></i> Create Production Order
  </a>
  @endbpCan
@endsection

@section('content')

  <!-- Stats -->
  <div class="row g-3 mb-4">
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-clipboard-list"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Total Orders</div>
          <div class="bp-stat-value">{{ $stats['total_orders'] ?? 0 }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-info"><i class="fa-solid fa-spinner"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Active / In Progress</div>
          <div class="bp-stat-value">{{ $stats['active_orders'] ?? 0 }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-success"><i class="fa-solid fa-circle-check"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Completed</div>
          <div class="bp-stat-value">{{ $stats['completed_orders'] ?? 0 }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-bangladeshi-taka-sign"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Total Due</div>
          <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($stats['total_due'] ?? 0) }}</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Production Orders Table -->
  <x-core::table>
    <x-slot:filters>
      <x-core::table.filter-bar searchPlaceholder="Search PO#, factory...">
        <input type="date" class="bp-form-control bp-filter-date" name="from" value="{{ request('from') }}">
        <span class="text-muted">to</span>
        <input type="date" class="bp-form-control bp-filter-date" name="to" value="{{ request('to') }}">
        <select class="bp-form-select" name="factory_id">
          <option value="">All Factories</option>
          @foreach($factories as $factory)
            <option value="{{ $factory->id }}" {{ request('factory_id') == $factory->id ? 'selected' : '' }}>{{ $factory->name }}</option>
          @endforeach
        </select>
        <select class="bp-form-select" name="status">
          <option value="">All Status</option>
          @foreach(\Modules\Manufacturing\Models\ProductionOrder::getStatuses() as $key => $label)
            <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
          @endforeach
        </select>
        <select class="bp-form-select" name="payment_status">
          <option value="">All Payment Status</option>
          @foreach(\Modules\Manufacturing\Models\ProductionOrder::getPaymentStatuses() as $key => $label)
            <option value="{{ $key }}" {{ request('payment_status') === $key ? 'selected' : '' }}>{{ $label }}</option>
          @endforeach
        </select>
      </x-core::table.filter-bar>
    </x-slot:filters>

    <x-core::table.header>
      <x-core::table.column :sortable="true" field="po_number">PO #</x-core::table.column>
      <x-core::table.column :sortable="true" field="order_date">Date</x-core::table.column>
      <x-core::table.column>Factory</x-core::table.column>
      <x-core::table.column>Total Qty</x-core::table.column>
      <x-core::table.column>Received</x-core::table.column>
      <x-core::table.column>Good</x-core::table.column>
      <x-core::table.column>Status</x-core::table.column>
      <x-core::table.column>Payment</x-core::table.column>
      <x-core::table.column>Actions</x-core::table.column>
    </x-core::table.header>

    <tbody>
      @forelse($orders as $order)
      <tr>
        <td><a href="{{ route('manufacturing.production-orders.show', $order) }}" class="fw-700">{{ $order->po_number }}</a></td>
        <td>{{ $order->order_date?->format('d M Y') }}</td>
        <td class="fw-600">{{ $order->factory->name ?? '' }}</td>
        <td class="fw-700">{{ number_format($order->total_quantity) }}</td>
        <td>{{ number_format($order->received_quantity) }}</td>
        <td class="text-success fw-600">{{ number_format($order->good_quantity) }}</td>
        <td>
          @php
            $statusBadge = match($order->status) {
              'draft' => 'bp-badge-dark',
              'approved' => 'bp-badge-primary',
              'in_progress' => 'bp-badge-info',
              'partial_delivered' => 'bp-badge-warning',
              'completed' => 'bp-badge-success',
              'cancelled' => 'bp-badge-danger',
              default => 'bp-badge-secondary',
            };
          @endphp
          <span class="bp-badge {{ $statusBadge }}">{{ str_replace('_', ' ', ucfirst($order->status)) }}</span>
        </td>
        <td>
          @php
            $payBadge = match($order->payment_status) {
              'unpaid' => 'bp-badge-danger',
              'partial' => 'bp-badge-warning',
              'paid' => 'bp-badge-success',
              default => 'bp-badge-secondary',
            };
          @endphp
          <span class="bp-badge {{ $payBadge }}">{{ ucfirst($order->payment_status) }}</span>
        </td>
        <td>
          @bpCanAny('manufacturing.view', 'manufacturing.edit', 'manufacturing.delete')
          <div class="dropdown">
            <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
            <ul class="dropdown-menu dropdown-menu-end">
              @bpCan('manufacturing.view')
              <li><a class="dropdown-item" href="{{ route('manufacturing.production-orders.show', $order) }}"><i class="fa-solid fa-eye me-2"></i> View</a></li>
              @endbpCan
              @if($order->status === 'draft')
                @bpCan('manufacturing.edit')
                <li><a class="dropdown-item" href="{{ route('manufacturing.production-orders.edit', $order) }}"><i class="fa-solid fa-pen me-2"></i> Edit</a></li>
                @endbpCan
              @endif
              @if($order->canBeApproved())
                @bpCan('manufacturing.edit')
                <li>
                  <form action="{{ route('manufacturing.production-orders.approve', $order) }}" method="POST">
                    @csrf
                    <button type="submit" class="dropdown-item"><i class="fa-solid fa-check me-2"></i> Approve</button>
                  </form>
                </li>
                @endbpCan
              @endif
              @if($order->canReceiveLot())
                @bpCan('manufacturing.edit')
                <li><a class="dropdown-item" href="{{ route('manufacturing.production-orders.lots.create', $order) }}"><i class="fa-solid fa-truck-ramp-box me-2"></i> Receive Lot</a></li>
                @endbpCan
              @endif
              @if($order->status === 'draft')
                @bpCan('manufacturing.delete')
                <li><hr class="dropdown-divider"></li>
                <li>
                  <form action="{{ route('manufacturing.production-orders.destroy', $order) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Are you sure you want to delete {{ $order->po_number }}?')">
                      <i class="fa-solid fa-trash me-2"></i> Delete
                    </button>
                  </form>
                </li>
                @endbpCan
              @endif
            </ul>
          </div>
          @endbpCanAny
        </td>
      </tr>
      @empty
      <x-core::table.empty colspan="9" icon="fa-solid fa-clipboard-list" title="No production orders found." />
      @endforelse
    </tbody>

    <x-slot:pagination>
      <x-core::table.pagination :paginator="$orders" itemLabel="production orders" />
    </x-slot:pagination>
  </x-core::table>

@endsection
