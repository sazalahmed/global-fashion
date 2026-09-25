@extends('core::layouts.master')

@section('title', 'Full Ledger — ' . $employee->name)
@section('page-title', 'Employee Full Ledger')

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('employee.index') }}">Employees</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('employee.show', $employee) }}">{{ $employee->name }}</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Full Ledger</span>
@endsection

@section('page-actions')
<a href="{{ route('employee.show', $employee) }}" class="bp-btn bp-btn-primary"><i class="fa-solid fa-arrow-left me-1"></i> Back</a>
@endsection

@section('content')

<div class="bp-card mb-4">
    <div class="bp-card-body">
        <form action="{{ route('employee.full-ledger', $employee) }}" method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="bp-form-label">Month</label>
                <input type="month" name="month" class="bp-form-control" value="{{ request('month') }}">
            </div>
            <div class="col-md-4">
                <label class="bp-form-label">Search (Note/Trx ID)</label>
                <input type="text" name="search" class="bp-form-control" value="{{ request('search') }}" placeholder="Search note or transaction id...">
            </div>
            <div class="col-md-3">
                <button type="submit" class="bp-btn bp-btn-primary w-100"><i class="fa-solid fa-search me-1"></i> Filter</button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('employee.full-ledger', $employee) }}" class="bp-btn bp-btn-outline text-danger w-100"><i class="fa-solid fa-xmark me-1"></i> Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="bp-card">
  <div class="bp-card-header">
    <h5 class="bp-card-title"><i class="fa-solid fa-book me-2 text-primary"></i>Transaction History - {{ $employee->name }}</h5>
  </div>
  <div class="bp-card-body p-0">
    <div class="bp-table-wrapper">
      <table class="bp-table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Type</th>
            <th>Trx ID (Reference)</th>
            <th>Note</th>
            <th>Payment Method</th>
            <th class="text-end">Amount</th>
          </tr>
        </thead>
        <tbody>
          @forelse($transactions as $txn)
          <tr>
            <td>
                @if(strlen($txn->date) === 7) {{-- YYYY-MM format from payroll --}}
                    {{ \Carbon\Carbon::parse($txn->date . '-01')->format('M Y') }}
                @else
                    {{ \Carbon\Carbon::parse($txn->date)->format('d M Y') }}
                @endif
            </td>
            <td>
              @if($txn->type === 'salary')
                <span class="bp-badge bp-badge-success">Salary Payout</span>
              @elseif($txn->type === 'advance')
                <span class="bp-badge bp-badge-warning">Advance Given</span>
              @elseif($txn->type === 'recovery')
                <span class="bp-badge bp-badge-info">Advance Recovery</span>
              @elseif($txn->type === 'payment')
                <span class="bp-badge bp-badge-primary">General Payment</span>
              @else
                <span class="bp-badge bp-badge-secondary">{{ ucfirst($txn->type) }}</span>
              @endif
            </td>
            <td><span class="fw-600">{{ $txn->reference ?? '--' }}</span></td>
            <td>{{ $txn->note ?? '--' }}</td>
            <td>{{ ucfirst(str_replace('_', ' ', $txn->method)) ?? '--' }}</td>
            <td class="text-end fw-700">{{ money($txn->amount) }}</td>
          </tr>
          @empty
          <x-core::table.empty colspan="6" icon="fa-solid fa-book" title="No transactions found" />
          @endforelse
        </tbody>
      </table>
    </div>
    @if($transactions->hasPages())
        <div class="p-3 border-top">
            {{ $transactions->links() }}
        </div>
    @endif
  </div>
</div>

@endsection
