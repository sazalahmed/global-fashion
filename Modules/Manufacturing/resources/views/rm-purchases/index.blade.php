@extends('core::layouts.master')

@section('title', __("RM Purchase Orders"))
@section('page-title', __("RM Purchase Orders"))

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Manufacturing</span>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>RM Purchase Orders</span>
@endsection

@section('page-actions')
  @bpCan('manufacturing.create')
  <a href="{{ route('manufacturing.rm-purchases.create') }}" class="bp-btn bp-btn-primary">
    <i class="fa-solid fa-plus"></i> Create Purchase Order
  </a>
  @endbpCan
@endsection

@section('content')

  <!-- Stats -->
  <div class="row g-3 mb-4">
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-file-invoice"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Total POs</div>
          <div class="bp-stat-value">{{ $stats['total_orders'] ?? 0 }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-warning"><i class="fa-solid fa-clock"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Pending Approval</div>
          <div class="bp-stat-value">{{ ($stats['draft_orders'] ?? 0) + ($stats['pending_orders'] ?? 0) }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-bangladeshi-taka-sign"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Total Due Amount</div>
          <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($stats['total_due'] ?? 0) }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-success"><i class="fa-solid fa-cart-plus"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Total Value</div>
          <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($stats['total_value'] ?? 0) }}</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Purchase Orders Table -->
  <x-core::table>
    <x-slot:filters>
      <x-core::table.filter-bar searchPlaceholder="Search PO#, supplier...">
        <input type="date" class="bp-form-control bp-filter-date" name="from" value="{{ request('from') }}">
        <span class="text-muted">to</span>
        <input type="date" class="bp-form-control bp-filter-date" name="to" value="{{ request('to') }}">
        <select class="bp-form-select" name="supplier_id">
          <option value="">All Suppliers</option>
          @foreach($suppliers as $supplier)
            <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>{{ $supplier->company_name }}</option>
          @endforeach
        </select>
        <select class="bp-form-select" name="status">
          <option value="">All Status</option>
          @foreach(\Modules\Manufacturing\Models\RmPurchaseOrder::getStatuses() as $key => $label)
            <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
          @endforeach
        </select>
        <select class="bp-form-select" name="payment_status">
          <option value="">All Payment Status</option>
          @foreach(\Modules\Manufacturing\Models\RmPurchaseOrder::getPaymentStatuses() as $key => $label)
            <option value="{{ $key }}" {{ request('payment_status') === $key ? 'selected' : '' }}>{{ $label }}</option>
          @endforeach
        </select>
      </x-core::table.filter-bar>
    </x-slot:filters>

    <x-core::table.header>
      <x-core::table.column :sortable="true" field="po_number">PO #</x-core::table.column>
      <x-core::table.column :sortable="true" field="po_date">Date</x-core::table.column>
      <x-core::table.column>Supplier</x-core::table.column>
      <x-core::table.column>Items</x-core::table.column>
      <x-core::table.column :sortable="true" field="grand_total">Grand Total</x-core::table.column>
      <x-core::table.column>Paid</x-core::table.column>
      <x-core::table.column>Due</x-core::table.column>
      <x-core::table.column>Status</x-core::table.column>
      <x-core::table.column>Payment</x-core::table.column>
      <x-core::table.column>Actions</x-core::table.column>
    </x-core::table.header>

    <tbody>
      @forelse($purchases as $po)
      <tr>
        <td><a href="{{ route('manufacturing.rm-purchases.show', $po) }}" class="fw-700">{{ $po->po_number }}</a></td>
        <td>{{ $po->po_date?->format('d M Y') }}</td>
        <td class="fw-600">{{ $po->supplier->company_name ?? '' }}</td>
        <td>{{ $po->items_count ?? 0 }}</td>
        <td class="fw-800">{{ currency_symbol() }} {{ number_format($po->grand_total) }}</td>
        <td class="text-success">{{ currency_symbol() }} {{ number_format($po->paid_amount) }}</td>
        <td class="{{ $po->due_amount > 0 ? 'text-danger fw-700' : '' }}">{{ currency_symbol() }} {{ number_format($po->due_amount) }}</td>
        <td><span class="bp-badge {{ $po->status_badge_class }}">{{ str_replace('_', ' ', ucfirst($po->status)) }}</span></td>
        <td><span class="bp-badge {{ $po->payment_status_badge_class }}">{{ ucfirst($po->payment_status) }}</span></td>
        <td>
          @bpCanAny('manufacturing.view', 'manufacturing.edit', 'manufacturing.delete')
          <div class="dropdown">
            <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
            <ul class="dropdown-menu dropdown-menu-end">
              @bpCan('manufacturing.view')
              <li><a class="dropdown-item" href="{{ route('manufacturing.rm-purchases.show', $po) }}"><i class="fa-solid fa-eye me-2"></i> View</a></li>
              @endbpCan
              @if(in_array($po->status, ['draft', 'pending']))
                @bpCan('manufacturing.edit')
                <li><a class="dropdown-item" href="{{ route('manufacturing.rm-purchases.edit', $po) }}"><i class="fa-solid fa-pen me-2"></i> Edit</a></li>
                @endbpCan
              @endif
              @if(in_array($po->status, ['approved', 'partial_received']))
                @bpCan('manufacturing.edit')
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="{{ route('manufacturing.rm-purchases.receive.create', $po) }}"><i class="fa-solid fa-truck-ramp-box me-2"></i> Receive Goods</a></li>
                @endbpCan
              @endif
              @if($po->status !== 'cancelled')
                @bpCan('manufacturing.delete')
                <li><hr class="dropdown-divider"></li>
                <li>
                  <form action="{{ route('manufacturing.rm-purchases.destroy', $po) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Are you sure you want to delete {{ $po->po_number }}?')">
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
      <x-core::table.empty colspan="10" icon="fa-solid fa-file-invoice" title="No RM purchase orders found." />
      @endforelse
    </tbody>

    <x-slot:pagination>
      <x-core::table.pagination :paginator="$purchases" itemLabel="purchase orders" />
    </x-slot:pagination>
  </x-core::table>

@endsection
