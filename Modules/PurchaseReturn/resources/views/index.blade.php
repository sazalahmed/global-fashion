@extends('core::layouts.master')

@section('title', __("Purchase Returns"))
@section('page-title', __("Purchase Returns"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Purchase Returns</span>
@endsection

@section('page-actions')
@bpCan('purchases.export')
<x-core::export-dropdown module="purchase-returns" />
@endbpCan
@bpCan('purchases.create')
<a href="{{ route('purchase-returns.create') }}" class="bp-btn bp-btn-primary">
  <i class="fa-solid fa-plus me-1"></i> New Purchase Return
</a>
@endbpCan
@endsection

@section('content')

<!-- Stats -->
<div class="row g-3 mb-4">
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-undo"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Total Returns</div>
        <div class="bp-stat-value">{{ number_format($stats['total']) }}</div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-warning"><i class="fa-solid fa-clock"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Draft</div>
        <div class="bp-stat-value">{{ number_format($stats['draft']) }}</div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-success"><i class="fa-solid fa-check-double"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Completed</div>
        <div class="bp-stat-value">{{ number_format($stats['completed']) }}</div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-bangladeshi-taka-sign"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Total Value</div>
        <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($stats['totalValue']) }}</div>
      </div>
    </div>
  </div>
</div>

<!-- Purchase Returns Table -->
<x-core::table :selectable="true">
  <x-slot:filters>
    <x-core::table.filter-bar searchPlaceholder="Search return #, PO, supplier...">
      <input type="date" class="bp-form-control bp-filter-date" name="date_from" value="{{ request('date_from') }}">
      <span class="text-muted">to</span>
      <input type="date" class="bp-form-control bp-filter-date" name="date_to" value="{{ request('date_to') }}">
      <select class="bp-form-select" name="status">
        <option value="">All Status</option>
        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
        <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
      </select>
      <select class="bp-form-select" name="supplier_id">
        <option value="">All Suppliers</option>
        @foreach($suppliers as $supplier)
          <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>{{ $supplier->company_name }}</option>
        @endforeach
      </select>
    </x-core::table.filter-bar>
  </x-slot:filters>

  <x-slot:bulkActions>
    <x-core::table.bulk-actions>
      <button class="bp-btn bp-btn-sm bp-btn-danger" data-bulk-action="status" data-bulk-status="cancelled"><i class="fa-solid fa-ban me-1"></i> Cancel</button>
      <button class="bp-btn bp-btn-sm bp-btn-danger" data-bulk-action="delete"><i class="fa-solid fa-trash me-1"></i> Delete</button>
    </x-core::table.bulk-actions>
  </x-slot:bulkActions>

  <x-core::table.header :selectable="true">
    <x-core::table.column :sortable="true" field="return_number">Return #</x-core::table.column>
    <x-core::table.column :sortable="true" field="return_date">Date</x-core::table.column>
    <x-core::table.column>Original PO</x-core::table.column>
    <x-core::table.column>Supplier</x-core::table.column>
    <x-core::table.column align="end">Return Amount</x-core::table.column>
    <x-core::table.column>Reason</x-core::table.column>
    <x-core::table.column>Status</x-core::table.column>
    <x-core::table.column>Actions</x-core::table.column>
  </x-core::table.header>

  <tbody>
    @forelse($returns as $return)
      @php
        $statusMap = [
          'draft'     => 'bp-badge-warning',
          'confirmed' => 'bp-badge-info',
          'completed' => 'bp-badge-success',
          'cancelled' => 'bp-badge-danger',
        ];
        $badgeClass = $statusMap[$return->status] ?? 'bp-badge-dark';
      @endphp
      <tr>
        <td><input type="checkbox" class="form-check-input row-checkbox" value="{{ $return->id }}"></td>
        <td><a href="{{ route('purchase-returns.show', $return) }}" class="fw-700">{{ $return->return_number }}</a></td>
        <td>{{ $return->return_date?->format('d M Y') ?? '' }}</td>
        <td>
          @if($return->purchase)
            <a href="{{ route('purchases.show', $return->purchase) }}" class="fw-600">{{ $return->purchase->po_number }}</a>
          @endif
        </td>
        <td class="fw-600">{{ $return->supplier->company_name ?? '' }}</td>
        <td class="text-end fw-800">{{ currency_symbol() }} {{ number_format($return->total, 0) }}</td>
        <td>
          @if($return->reason)
            <span class="bp-badge bp-badge-secondary">{{ ucfirst($return->reason) }}</span>
          @endif
        </td>
        <td><span class="bp-badge {{ $badgeClass }}">{{ ucfirst($return->status) }}</span></td>
        <td>
          @bpCanAny('purchases.view','purchases.edit','purchases.delete')
          <div class="dropdown">
            <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
            <ul class="dropdown-menu dropdown-menu-end">
              @bpCan('purchases.view')
              <li><a class="dropdown-item" href="{{ route('purchase-returns.show', $return) }}"><i class="fa-solid fa-eye me-2"></i>View</a></li>
              @endbpCan
              @if($return->status === 'draft')
                @bpCan('purchases.edit')
                <li><a class="dropdown-item" href="{{ route('purchase-returns.edit', $return) }}"><i class="fa-solid fa-pen me-2"></i>Edit</a></li>
                @endbpCan
              @endif
              @bpCan('purchases.view')
              <li><a class="dropdown-item" href="{{ route('purchase-returns.print', $return) }}" target="_blank"><i class="fa-solid fa-print me-2"></i>Print</a></li>
              @endbpCan
              @if($return->status === 'draft')
                <li><hr class="dropdown-divider"></li>
                @bpCan('purchases.delete')
                <li>
                  <form action="{{ route('purchase-returns.destroy', $return) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="dropdown-item text-danger delete-confirm" data-name="{{ $return->return_number }}"><i class="fa-solid fa-trash me-2"></i>Delete</button>
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
      <x-core::table.empty colspan="9" icon="fa-solid fa-rotate-left" title="No purchase returns found." />
    @endforelse
  </tbody>

  <x-slot:pagination>
    <x-core::table.pagination :paginator="$returns" />
  </x-slot:pagination>
</x-core::table>

@endsection
