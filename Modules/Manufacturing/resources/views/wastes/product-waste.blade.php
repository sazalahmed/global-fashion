@extends('core::layouts.master')

@section('title', __("Product Wastes"))
@section('page-title', __("Product Wastes"))

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Manufacturing</span>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Product Wastes</span>
@endsection

@section('page-actions')
  @bpCan('manufacturing.create')
  <button type="button" class="bp-btn bp-btn-primary" data-bs-toggle="modal" data-bs-target="#recordWasteModal">
    <i class="fa-solid fa-plus me-1"></i> Record Waste
  </button>
  @endbpCan
@endsection

@section('content')

  <!-- Wastes Table -->
  <x-core::table>
    <x-slot:filters>
      <x-core::table.filter-bar searchPlaceholder="Search...">
        <select class="bp-form-select" name="waste_type">
          <option value="">All Types</option>
          @foreach(\Modules\Manufacturing\Models\ProductWaste::getWasteTypes() as $key => $label)
            <option value="{{ $key }}" {{ request('waste_type') === $key ? 'selected' : '' }}>{{ $label }}</option>
          @endforeach
        </select>
        <select class="bp-form-select" name="is_normal">
          <option value="">Normal/Abnormal</option>
          <option value="1" {{ request('is_normal') === '1' ? 'selected' : '' }}>Normal</option>
          <option value="0" {{ request('is_normal') === '0' ? 'selected' : '' }}>Abnormal</option>
        </select>
      </x-core::table.filter-bar>
    </x-slot:filters>

    <x-core::table.header>
      <x-core::table.column>Date</x-core::table.column>
      <x-core::table.column>Order #</x-core::table.column>
      <x-core::table.column>Lot #</x-core::table.column>
      <x-core::table.column>Catalog / Color / Size</x-core::table.column>
      <x-core::table.column>Qty Wasted</x-core::table.column>
      <x-core::table.column>Cost</x-core::table.column>
      <x-core::table.column>Type</x-core::table.column>
      <x-core::table.column>Normal?</x-core::table.column>
    </x-core::table.header>

    <tbody>
      @forelse($wastes as $waste)
      <tr>
        <td>{{ $waste->waste_date?->format('d M Y') }}</td>
        <td>
          <a href="{{ route('manufacturing.production-orders.show', $waste->production_order_id) }}" class="fw-700">
            {{ $waste->productionOrder->po_number ?? '' }}
          </a>
        </td>
        <td>{{ $waste->lot->lot_number ?? '' }}</td>
        <td class="fw-600">
          {{ $waste->catalog->name ?? '' }}
          @if($waste->color) / {{ $waste->color->name }} @endif
          @if($waste->size) / {{ $waste->size->name }} @endif
        </td>
        <td class="fw-700">{{ $waste->quantity_wasted }}</td>
        <td class="fw-700">{{ currency_symbol() }} {{ number_format($waste->total_cost) }}</td>
        <td>{{ str_replace('_', ' ', ucfirst($waste->waste_type)) }}</td>
        <td>
          <span class="bp-badge {{ $waste->is_normal ? 'bp-badge-success' : 'bp-badge-danger' }}">{{ $waste->is_normal ? 'Normal' : 'Abnormal' }}</span>
        </td>
      </tr>
      @empty
      <x-core::table.empty colspan="8" icon="fa-solid fa-recycle" title="No product waste records found." />
      @endforelse
    </tbody>

    <x-slot:pagination>
      <x-core::table.pagination :paginator="$wastes" itemLabel="waste records" />
    </x-slot:pagination>
  </x-core::table>

  <!-- Record Product Waste Modal -->
  <div class="modal fade" id="recordWasteModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form action="{{ route('manufacturing.wastes.products.store') }}" method="POST">
          @csrf
          <div class="modal-header">
            <h5 class="modal-title fw-700">Record Product Waste</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="bp-form-label">Production Order ID *</label>
                <input type="number" class="bp-form-control" name="production_order_id" required placeholder="Order ID">
              </div>
              <div class="col-md-6">
                <label class="bp-form-label">Lot ID</label>
                <input type="number" class="bp-form-control" name="lot_id" placeholder="Lot ID (optional)">
              </div>
              <div class="col-md-4">
                <label class="bp-form-label">Catalog ID</label>
                <input type="number" class="bp-form-control" name="catalog_id" placeholder="Catalog ID">
              </div>
              <div class="col-md-4">
                <label class="bp-form-label">Color ID</label>
                <input type="number" class="bp-form-control" name="color_id" placeholder="Color ID">
              </div>
              <div class="col-md-4">
                <label class="bp-form-label">Size ID</label>
                <input type="number" class="bp-form-control" name="size_id" placeholder="Size ID">
              </div>
              <div class="col-md-6">
                <label class="bp-form-label">Waste Date *</label>
                <input type="date" class="bp-form-control" name="waste_date" value="{{ date('Y-m-d') }}" required>
              </div>
              <div class="col-md-3">
                <label class="bp-form-label">Qty Wasted *</label>
                <input type="number" class="bp-form-control" name="quantity_wasted" min="1" required>
              </div>
              <div class="col-md-3">
                <label class="bp-form-label">Unit Cost *</label>
                <input type="number" class="bp-form-control" name="unit_cost" step="0.01" min="0" required>
              </div>
              <div class="col-md-4">
                <label class="bp-form-label">Waste Type *</label>
                <select class="bp-form-select w-100" name="waste_type" required>
                  @foreach(\Modules\Manufacturing\Models\ProductWaste::getWasteTypes() as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-4">
                <label class="bp-form-label">Normal Waste? *</label>
                <select class="bp-form-select w-100" name="is_normal" required>
                  <option value="1">Normal</option>
                  <option value="0">Abnormal</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="bp-form-label">Waste %</label>
                <input type="number" class="bp-form-control" name="waste_percentage" step="0.01" min="0" max="100">
              </div>
              <div class="col-12">
                <label class="bp-form-label">Notes</label>
                <textarea class="bp-form-control" name="notes" rows="2"></textarea>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="bp-btn bp-btn-danger" data-bs-dismiss="modal"><i class="fa-solid fa-xmark me-1"></i>Cancel</button>
            <button type="submit" class="bp-btn bp-btn-primary">Record Waste</button>
          </div>
        </form>
      </div>
    </div>
  </div>

@endsection
