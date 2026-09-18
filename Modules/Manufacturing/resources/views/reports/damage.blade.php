@extends('core::layouts.master')

@section('title', __("Damage Report"))
@section('page-title', __("Damage Report"))

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Manufacturing</span>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Reports</span>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Damage</span>
@endsection

@section('page-actions')
  <button class="bp-btn bp-btn-outline" onclick="window.print()">
    <i class="fa-solid fa-print me-1"></i> Print
  </button>
@endsection

@section('content')

  <!-- Filters -->
  <div class="bp-card mb-4">
    <div class="bp-card-body">
      <form method="GET" action="{{ route('manufacturing.reports.damage') }}">
        <div class="row g-3 align-items-end bp-report-filters">
          <div class="col-md-2">
            <label class="bp-form-label">Factory</label>
            <select class="bp-form-select w-100" name="factory_id">
              <option value="">All Factories</option>
              @foreach($factories as $factory)
                <option value="{{ $factory->id }}" {{ ($filters['factory_id'] ?? '') == $factory->id ? 'selected' : '' }}>{{ $factory->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <label class="bp-form-label">Damage Type</label>
            <select class="bp-form-select w-100" name="damage_type">
              <option value="">All Types</option>
              @foreach($damageTypes as $key => $label)
                <option value="{{ $key }}" {{ ($filters['damage_type'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <label class="bp-form-label">Responsibility</label>
            <select class="bp-form-select w-100" name="responsibility">
              <option value="">All</option>
              @foreach($responsibilities as $key => $label)
                <option value="{{ $key }}" {{ ($filters['responsibility'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <label class="bp-form-label">Compensation</label>
            <select class="bp-form-select w-100" name="compensation_status">
              <option value="">All Status</option>
              @foreach($compensationStatuses as $key => $label)
                <option value="{{ $key }}" {{ ($filters['compensation_status'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <label class="bp-form-label">Date Range</label>
            <input type="date" class="bp-form-control" name="from_date" value="{{ $filters['from_date'] ?? '' }}">
          </div>
          <div class="col-md-2">
            <label class="bp-form-label">To</label>
            <div class="d-flex gap-2">
              <input type="date" class="bp-form-control" name="to_date" value="{{ $filters['to_date'] ?? '' }}">
            </div>
          </div>
        </div>
        <div class="row mt-3">
          <div class="col-12">
            <button type="submit" class="bp-btn bp-btn-primary me-2" title="Apply Filters"><i class="fa-solid fa-filter"></i></button>
            <a href="{{ route('manufacturing.reports.damage') }}" class="bp-btn bp-btn-danger" title="Reset Filters"><i class="fa-solid fa-rotate"></i></a>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Summary Cards -->
  <div class="row g-3 mb-4">
    <div class="col-xl-4 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Total Damage Cost</div>
          <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($summary['total_damage_cost'], 0) }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-4 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-warning"><i class="fa-solid fa-clock"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Pending Compensation</div>
          <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($summary['pending_compensation'], 0) }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-4 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-success"><i class="fa-solid fa-hand-holding-dollar"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Received Compensation</div>
          <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($summary['received_compensation'], 0) }}</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Damage Table -->
  <div class="bp-card">
    <div class="bp-card-body p-0">
      <div class="bp-table-wrapper">
        <table class="bp-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>PO#</th>
              <th>Lot#</th>
              <th>Item</th>
              <th class="text-end">Qty</th>
              <th class="text-end">Cost</th>
              <th>Type</th>
              <th>Responsibility</th>
              <th>Comp. Status</th>
              <th class="text-end">Comp. Amount</th>
              <th class="text-end">Received</th>
            </tr>
          </thead>
          <tbody>
            @forelse($damages as $damage)
            <tr>
              <td class="fs-12">{{ $damage->damage_date ? $damage->damage_date->format('d M Y') : '' }}</td>
              <td class="fw-600 fs-12">{{ $damage->productionOrder->po_number ?? '' }}</td>
              <td class="fs-12">{{ $damage->lot->lot_number ?? '' }}</td>
              <td class="fs-12">
                {{ $damage->catalog->name ?? ($damage->product->name ?? '') }}
                @if($damage->color)
                  <span class="text-muted">/ {{ $damage->color->name }}</span>
                @endif
                @if($damage->size)
                  <span class="text-muted">/ {{ $damage->size->name }}</span>
                @endif
              </td>
              <td class="text-end">{{ $damage->quantity }}</td>
              <td class="text-end text-danger fw-600">{{ currency_symbol() }} {{ number_format($damage->total_damage_cost, 0) }}</td>
              <td><span class="bp-badge bp-badge-info">{{ ucwords(str_replace('_', ' ', $damage->damage_type)) }}</span></td>
              <td><span class="bp-badge bp-badge-secondary">{{ ucfirst($damage->responsibility) }}</span></td>
              <td>
                @php
                  $compClass = match($damage->compensation_status) {
                    'pending' => 'bp-badge-warning',
                    'partial' => 'bp-badge-info',
                    'received' => 'bp-badge-success',
                    'written_off' => 'bp-badge-dark',
                    'deducted' => 'bp-badge-primary',
                    default => 'bp-badge-secondary',
                  };
                @endphp
                <span class="bp-badge {{ $compClass }}">{{ ucwords(str_replace('_', ' ', $damage->compensation_status)) }}</span>
              </td>
              <td class="text-end">{{ currency_symbol() }} {{ number_format($damage->compensation_amount, 0) }}</td>
              <td class="text-end text-success fw-600">{{ currency_symbol() }} {{ number_format($damage->compensation_received, 0) }}</td>
            </tr>
            @empty
            <x-core::table.empty colspan="11" icon="fa-solid fa-triangle-exclamation" title="No damage records found." />
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

@endsection
