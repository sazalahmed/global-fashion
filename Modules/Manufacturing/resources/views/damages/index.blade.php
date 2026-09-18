@extends('core::layouts.master')

@section('title', __("Production Damages"))
@section('page-title', __("Production Damages"))

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Manufacturing</span>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Damages</span>
@endsection

@section('content')

  <!-- Damages Table -->
  <x-core::table>
    <x-slot:filters>
      <x-core::table.filter-bar searchPlaceholder="Search order#...">
        <select class="bp-form-select" name="factory_id">
          <option value="">All Factories</option>
          @foreach($factories as $factory)
            <option value="{{ $factory->id }}" {{ request('factory_id') == $factory->id ? 'selected' : '' }}>{{ $factory->name }}</option>
          @endforeach
        </select>
        <select class="bp-form-select" name="damage_type">
          <option value="">All Types</option>
          @foreach(\Modules\Manufacturing\Models\ProductionDamage::getDamageTypes() as $key => $label)
            <option value="{{ $key }}" {{ request('damage_type') === $key ? 'selected' : '' }}>{{ $label }}</option>
          @endforeach
        </select>
        <select class="bp-form-select" name="responsibility">
          <option value="">All Responsibility</option>
          @foreach(\Modules\Manufacturing\Models\ProductionDamage::getResponsibilities() as $key => $label)
            <option value="{{ $key }}" {{ request('responsibility') === $key ? 'selected' : '' }}>{{ $label }}</option>
          @endforeach
        </select>
        <select class="bp-form-select" name="compensation_status">
          <option value="">All Comp. Status</option>
          @foreach(\Modules\Manufacturing\Models\ProductionDamage::getCompensationStatuses() as $key => $label)
            <option value="{{ $key }}" {{ request('compensation_status') === $key ? 'selected' : '' }}>{{ $label }}</option>
          @endforeach
        </select>
      </x-core::table.filter-bar>
    </x-slot:filters>

    <x-core::table.header>
      <x-core::table.column>Date</x-core::table.column>
      <x-core::table.column>Order #</x-core::table.column>
      <x-core::table.column>Lot #</x-core::table.column>
      <x-core::table.column>Catalog / Color / Size</x-core::table.column>
      <x-core::table.column>Qty</x-core::table.column>
      <x-core::table.column>Damage Cost</x-core::table.column>
      <x-core::table.column>Type</x-core::table.column>
      <x-core::table.column>Responsibility</x-core::table.column>
      <x-core::table.column>Compensation</x-core::table.column>
      <x-core::table.column>Actions</x-core::table.column>
    </x-core::table.header>

    <tbody>
      @forelse($damages as $damage)
      <tr>
        <td>{{ $damage->damage_date?->format('d M Y') }}</td>
        <td>
          <a href="{{ route('manufacturing.production-orders.show', $damage->production_order_id) }}" class="fw-700">
            {{ $damage->productionOrder->po_number ?? '' }}
          </a>
        </td>
        <td>{{ $damage->lot->lot_number ?? '' }}</td>
        <td class="fw-600">
          {{ $damage->catalog->name ?? '' }}
          @if($damage->color) / {{ $damage->color->name }} @endif
          @if($damage->size) / {{ $damage->size->name }} @endif
        </td>
        <td class="fw-700">{{ $damage->quantity }}</td>
        <td class="text-danger fw-700">{{ currency_symbol() }} {{ number_format($damage->total_damage_cost) }}</td>
        <td>{{ str_replace('_', ' ', ucfirst($damage->damage_type)) }}</td>
        <td>{{ ucfirst($damage->responsibility) }}</td>
        <td>
          @php
            $compBadge = match($damage->compensation_status) {
              'pending' => 'bp-badge-warning',
              'partial' => 'bp-badge-info',
              'received' => 'bp-badge-success',
              'written_off' => 'bp-badge-dark',
              'deducted' => 'bp-badge-primary',
              default => 'bp-badge-secondary',
            };
          @endphp
          <span class="bp-badge {{ $compBadge }}">{{ ucfirst($damage->compensation_status) }}</span>
          @if($damage->compensation_received > 0)
            <div class="fs-11 text-muted">{{ currency_symbol() }} {{ number_format($damage->compensation_received) }} received</div>
          @endif
        </td>
        <td>
          @if($damage->compensation_status !== 'received')
            @bpCan('manufacturing.edit')
            <div class="dropdown">
              <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
              <ul class="dropdown-menu dropdown-menu-end">
                <li>
                  <button type="button" class="dropdown-item compensate-btn"
                    data-damage-id="{{ $damage->id }}"
                    data-remaining="{{ (float)$damage->total_damage_cost - (float)$damage->compensation_received }}"
                    data-bs-toggle="modal" data-bs-target="#compensationModal">
                    <i class="fa-solid fa-bangladeshi-taka-sign me-2"></i> Compensate
                  </button>
                </li>
              </ul>
            </div>
            @endbpCan
          @endif
        </td>
      </tr>
      @empty
      <x-core::table.empty colspan="10" icon="fa-solid fa-triangle-exclamation" title="No damage records found." />
      @endforelse
    </tbody>

    <x-slot:pagination>
      <x-core::table.pagination :paginator="$damages" itemLabel="damages" />
    </x-slot:pagination>
  </x-core::table>

  <!-- Compensation Modal -->
  <div class="modal fade" id="compensationModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="POST" id="compensationForm">
          @csrf
          <div class="modal-header">
            <h5 class="modal-title fw-700">Record Compensation</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="bp-form-label">Date *</label>
                <input type="date" class="bp-form-control" name="compensation_date" value="{{ date('Y-m-d') }}" required>
              </div>
              <div class="col-md-6">
                <label class="bp-form-label">Amount *</label>
                <input type="number" class="bp-form-control" name="amount" id="compAmount" step="0.01" min="0.01" required>
              </div>
              <div class="col-md-6">
                <label class="bp-form-label">Method *</label>
                <select class="bp-form-select w-100" name="method" required>
                  @foreach(\Modules\Manufacturing\Models\ProductionDamage::getCompensationMethods() as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-6">
                <label class="bp-form-label">Reference</label>
                <input type="text" class="bp-form-control" name="reference" placeholder="Ref #">
              </div>
              <div class="col-12">
                <label class="bp-form-label">Notes</label>
                <textarea class="bp-form-control" name="notes" rows="2"></textarea>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="bp-btn bp-btn-danger" data-bs-dismiss="modal"><i class="fa-solid fa-xmark me-1"></i>Cancel</button>
            <button type="submit" class="bp-btn bp-btn-primary">Record Compensation</button>
          </div>
        </form>
      </div>
    </div>
  </div>

@endsection

@push('scripts')
<script>
'use strict';

(function() {
  $(document).on('click', '.compensate-btn', function() {
    var damageId = $(this).data('damage-id');
    var remaining = $(this).data('remaining');
    var baseUrl = '{{ url("manufacturing/damages") }}/' + damageId + '/compensations';
    $('#compensationForm').attr('action', baseUrl);
    $('#compAmount').val(remaining).attr('max', remaining);
  });
})();
</script>
@endpush
