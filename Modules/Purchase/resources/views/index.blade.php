@extends('core::layouts.master')

@section('title', __("Purchase Orders"))
@section('page-title', __("Purchase Orders"))

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Purchases</span>
@endsection

@section('page-actions')
  @bpCan('purchases.export')
  <x-core::export-dropdown module="purchases" />
  @endbpCan
  @bpCan('purchases.create')
  <a href="{{ route('purchases.create') }}" class="bp-btn bp-btn-primary">
    <i class="fa-solid fa-plus"></i> New Purchase Order
  </a>
  @endbpCan
@endsection

@section('content')

  <!-- Stats -->
  <div class="row g-3 mb-4">
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-cart-plus"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">This Month Purchases</div>
          <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($stats['thisMonthPurchases'] ?? 0) }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-warning"><i class="fa-solid fa-clock"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Pending Orders</div>
          <div class="bp-stat-value">{{ $stats['pendingOrders'] ?? 0 }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-bangladeshi-taka-sign"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Total Payable</div>
          <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($stats['totalPayable'] ?? 0) }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-success"><i class="fa-solid fa-truck"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Received This Month</div>
          <div class="bp-stat-value">{{ $stats['receivedThisMonth'] ?? 0 }}</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Purchases Table -->
  <x-core::table :selectable="true">
    <x-slot:filters>
      <x-core::table.filter-bar searchPlaceholder="Search PO#, supplier...">
        <input type="date" class="bp-form-control bp-filter-date" name="date_from" value="{{ request('date_from', now()->startOfMonth()->toDateString()) }}">
        <span class="text-muted">to</span>
        <input type="date" class="bp-form-control bp-filter-date" name="date_to" value="{{ request('date_to', now()->toDateString()) }}">
        <select class="bp-form-select" name="status">
          <option value="">All Status</option>
          @foreach ([
              'draft' => 'Draft',
              'pending' => 'Pending Approval',
              'approved' => 'Approved',
              'partial_received' => 'Partial Received',
              'received' => 'Received',
              'cancelled' => 'Cancelled',
          ] as $val => $label)
            <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
          @endforeach
        </select>
        <select class="bp-form-select" name="supplier_id">
          <option value="">All Suppliers</option>
          @foreach($suppliers as $supplier)
            <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>{{ $supplier->company_name }}</option>
          @endforeach
        </select>
        <select class="bp-form-select" name="payment_status">
          <option value="">All Payment Status</option>
          @foreach (['paid' => 'Paid', 'partial' => 'Partial', 'unpaid' => 'Unpaid'] as $val => $label)
            <option value="{{ $val }}" {{ request('payment_status') === $val ? 'selected' : '' }}>{{ $label }}</option>
          @endforeach
        </select>
      </x-core::table.filter-bar>
    </x-slot:filters>

    <x-slot:bulkActions>
      <x-core::table.bulk-actions>
        <button class="bp-btn bp-btn-sm bp-btn-outline"><i class="fa-solid fa-print me-1"></i> Print</button>
        <button class="bp-btn bp-btn-sm bp-btn-danger"><i class="fa-solid fa-ban me-1"></i> Cancel</button>
      </x-core::table.bulk-actions>
    </x-slot:bulkActions>

    <x-core::table.header :selectable="true">
      <x-core::table.column :sortable="true" field="po_number">PO #</x-core::table.column>
      <x-core::table.column :sortable="true" field="date">Date</x-core::table.column>
      <x-core::table.column>Supplier</x-core::table.column>
      <x-core::table.column>Branch</x-core::table.column>
      <x-core::table.column>Items</x-core::table.column>
      <x-core::table.column :sortable="true" field="total">Total</x-core::table.column>
      <x-core::table.column>Paid</x-core::table.column>
      <x-core::table.column :sortable="true" field="due">Due</x-core::table.column>
      <x-core::table.column>Delivery</x-core::table.column>
      <x-core::table.column>Payment</x-core::table.column>
      <x-core::table.column>Actions</x-core::table.column>
    </x-core::table.header>

    <tbody>
      @forelse($purchases as $po)
      <tr>
        <td><input type="checkbox" class="form-check-input row-checkbox" value="{{ $po->id }}"></td>
        <td><a href="{{ route('purchases.show', $po) }}" class="fw-700">{{ $po->po_number }}</a></td>
        <td>{{ $po->po_date?->format('d M Y') }}</td>
        <td class="fw-600">{{ $po->supplier->company_name ?? '' }}</td>
        <td>{{ $po->branch->name ?? '' }}</td>
        <td>{{ $po->items_count ?? $po->items->count() }}</td>
        <td class="fw-800">{{ currency_symbol() }} {{ number_format($po->grand_total) }}</td>
        <td class="text-success">{{ currency_symbol() }} {{ number_format($po->paid_amount) }}</td>
        <td class="{{ $po->due_amount > 0 ? 'text-danger fw-700' : '' }}">{{ currency_symbol() }} {{ $po->due_amount > 0 ? number_format($po->due_amount) : '0' }}</td>
        <td>
          @if($po->status === 'received')
            <span class="bp-badge bp-badge-success">Received</span>
          @elseif($po->status === 'partial_received')
            <span class="bp-badge bp-badge-warning">Partial Received</span>
          @elseif($po->status === 'approved')
            <span class="bp-badge bp-badge-info">Approved</span>
          @elseif($po->status === 'draft')
            <span class="bp-badge bp-badge-dark">Draft</span>
          @elseif($po->status === 'cancelled')
            <span class="bp-badge bp-badge-danger">Cancelled</span>
          @else
            <span class="bp-badge bp-badge-warning">{{ ucfirst($po->status) }}</span>
          @endif
        </td>
        <td>
          @if($po->payment_status === 'paid')
            <span class="bp-badge bp-badge-success">Paid</span>
          @elseif($po->payment_status === 'partial')
            <span class="bp-badge bp-badge-warning">Partial</span>
          @else
            <span class="bp-badge bp-badge-danger">Unpaid</span>
          @endif
        </td>
        <td>
          @bpCanAny('purchases.view','purchases.edit','purchases.approve','payments.create','purchases.delete')
          <div class="dropdown">
            <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
            <ul class="dropdown-menu dropdown-menu-end">
              @bpCan('purchases.view')
              <li><a class="dropdown-item" href="{{ route('purchases.show', $po) }}"><i class="fa-solid fa-eye me-2"></i> View</a></li>
              @endbpCan
              @if($po->status !== 'cancelled')
                @bpCan('purchases.edit')
                <li><a class="dropdown-item" href="{{ route('purchases.edit', $po) }}"><i class="fa-solid fa-pen me-2"></i> Edit</a></li>
                @endbpCan
              @endif
              @bpCan('purchases.view')
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="{{ route('purchases.print', $po->id) }}" target="_blank"><i class="fa-solid fa-print me-2"></i> Print PO</a></li>
              @endbpCan
              @if($po->status !== 'cancelled')
                @if(in_array($po->status, ['approved', 'partial_received']))
                  @bpCan('purchases.approve')
                  <li><hr class="dropdown-divider"></li>
                  <li><a class="dropdown-item" href="{{ route('purchases.receive.create', $po) }}"><i class="fa-solid fa-truck-ramp-box me-2"></i> Receive Stock</a></li>
                  @endbpCan
                @endif
                @if($po->due_amount > 0)
                  @bpCan('payments.create')
                  <li>
                    <a class="dropdown-item" href="{{ route('payments.create', ['direction' => 'pay', 'party_type' => 'supplier', 'party_id' => $po->supplier_id]) }}">
                      <i class="fa-solid fa-bangladeshi-taka-sign me-2"></i> Make Payment
                    </a>
                  </li>
                  @endbpCan
                @endif
                @bpCan('purchases.create')
                <li>
                  <a class="dropdown-item" href="{{ route('purchase-returns.create', ['purchase_id' => $po->id]) }}">
                    <i class="fa-solid fa-rotate-left me-2"></i> Purchase Return
                  </a>
                </li>
                @endbpCan
              @endif
              <li><hr class="dropdown-divider"></li>
              @bpCan('purchases.delete')
              <li>
                <form action="{{ route('purchases.destroy', $po) }}" method="POST">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="dropdown-item text-danger delete-confirm" data-name="{{ $po->po_number }}">
                    <i class="fa-solid fa-trash me-2"></i> Delete
                  </button>
                </form>
              </li>
              @endbpCan
            </ul>
          </div>
          @endbpCanAny
        </td>
      </tr>
      @empty
      <x-core::table.empty colspan="12" icon="fa-solid fa-cart-plus" title="No purchase orders found." />
      @endforelse
    </tbody>

    <x-slot:pagination>
      <x-core::table.pagination :paginator="$purchases" itemLabel="purchase orders" />
    </x-slot:pagination>
  </x-core::table>

@endsection
