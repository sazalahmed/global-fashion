@extends('core::layouts.master')

@section('title', 'Advance Ledger — ' . $employee->name)
@section('page-title', 'Advance Ledger')

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('employee.index') }}">Employees</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('employee.show', $employee) }}">{{ $employee->name }}</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Advance Ledger</span>
@endsection

@section('page-actions')
<a href="{{ route('employee.show', $employee) }}" class="bp-btn bp-btn-primary"><i class="fa-solid fa-arrow-left me-1"></i> Back</a>
@endsection

@section('content')

<div class="row g-3 mb-4">
  <div class="col-md-4 col-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-accent"><i class="fa-solid fa-hand-holding-dollar"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Total Given</div>
        <div class="bp-stat-value">{{ number_format($totalGiven, 0) }}</div>
      </div>
    </div>
  </div>
  <div class="col-md-4 col-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-success"><i class="fa-solid fa-rotate-left"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Total Recovered</div>
        <div class="bp-stat-value">{{ number_format($totalRecovered, 0) }}</div>
      </div>
    </div>
  </div>
  <div class="col-md-4 col-12">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-bangladeshi-taka-sign"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Outstanding</div>
        <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($currentBalance, 0) }}</div>
      </div>
    </div>
  </div>
</div>

<div class="bp-card">
  <div class="bp-card-header">
    <h5 class="bp-card-title"><i class="fa-solid fa-book me-2 text-primary"></i>{{ $employee->name }} ({{ $employee->employee_id }})</h5>
  </div>
  <div class="bp-card-body p-0">
    <div class="bp-table-wrapper">
      <table class="bp-table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Reference</th>
            <th>Type</th>
            <th class="text-end">Advance Given</th>
            <th class="text-end">Recovered</th>
            <th class="text-end">Balance</th>
          </tr>
        </thead>
        <tbody>
          @forelse($ledgerEntries as $entry)
          <tr>
            <td>{{ $entry['date'] ? \Carbon\Carbon::parse($entry['date'])->format('d M Y') : '—' }}</td>
            <td>{{ $entry['reference'] }}</td>
            <td>
              @if($entry['type'] === 'Advance Given')
                <span class="bp-badge bp-badge-warning">{{ $entry['type'] }}</span>
              @else
                <span class="bp-badge bp-badge-success">{{ $entry['type'] }}</span>
              @endif
            </td>
            <td class="text-end">{{ $entry['given'] > 0 ? money($entry['given']) : '—' }}</td>
            <td class="text-end text-success">{{ $entry['recovered'] > 0 ? money($entry['recovered']) : '—' }}</td>
            <td class="text-end fw-700">{{ money($entry['balance']) }}</td>
          </tr>
          @empty
          <x-core::table.empty colspan="6" icon="fa-solid fa-book" title="No advance transactions yet" />
          @endforelse
        </tbody>
        <tfoot>
          <tr class="bp-table-total-row">
            <td colspan="3" class="text-end fw-700">Totals</td>
            <td class="text-end fw-800">{{ money($totalGiven) }}</td>
            <td class="text-end fw-800 text-success">{{ money($totalRecovered) }}</td>
            <td class="text-end fw-800 text-danger">{{ money($currentBalance) }}</td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</div>

@endsection
