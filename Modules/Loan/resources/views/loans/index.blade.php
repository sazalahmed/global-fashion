@extends('core::layouts.master')

@section('title', __("Loans"))
@section('page-title', __("Loans"))

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Loans</span>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>All Loans</span>
@endsection

@section('page-actions')
  @bpCan('finance.create')
  <a href="{{ route('loans.create') }}" class="bp-btn bp-btn-primary">
    <i class="fa-solid fa-plus me-1"></i> New Loan
  </a>
  @endbpCan
@endsection

@section('content')

  <!-- Stats -->
  <div class="row g-3 mb-4">
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-hand-holding-dollar"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Total Borrowed</div>
          <div class="bp-stat-value">{{ money($stats['total_borrowed']) }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-success"><i class="fa-solid fa-bangladeshi-taka-sign"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Total Repaid</div>
          <div class="bp-stat-value">{{ money($stats['total_repaid']) }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-info"><i class="fa-solid fa-scale-balanced"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Outstanding</div>
          <div class="bp-stat-value">{{ money($stats['total_outstanding']) }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Overdue Loans</div>
          <div class="bp-stat-value">{{ number_format($stats['overdue_loans']) }}</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Upcoming Installments -->
  @if($upcoming->count() > 0)
  <div class="bp-card mb-4">
    <div class="bp-card-header">
      <h5 class="bp-card-title"><i class="fa-solid fa-bell me-2 text-warning"></i>Upcoming Installments (Next 7 Days)</h5>
    </div>
    <div class="bp-card-body p-0">
      <div class="bp-table-wrapper">
        <table class="bp-table">
          <thead>
            <tr>
              <th>Loan #</th>
              <th>Lender</th>
              <th>Due Date</th>
              <th>Amount</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            @foreach($upcoming as $schedule)
            <tr>
              <td><a href="{{ route('loans.show', $schedule->loan->id) }}" class="fw-700">{{ $schedule->loan->loan_number }}</a></td>
              <td class="fw-600">{{ $schedule->loan->lender->name ?? '' }}</td>
              <td class="fw-600">{{ $schedule->due_date->format('d M Y') }}</td>
              <td class="fw-700">{{ money($schedule->amount) }}</td>
              <td>
                @if($schedule->status === 'partial')
                  <span class="bp-badge bp-badge-warning">Partial</span>
                @elseif($schedule->status === 'overdue')
                  <span class="bp-badge bp-badge-danger">Overdue</span>
                @else
                  <span class="bp-badge bp-badge-info">Upcoming</span>
                @endif
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
  @endif

  <!-- Loans Table -->
  <x-core::table>
    <x-slot:filters>
      <x-core::table.filter-bar searchPlaceholder="Search loan #, lender...">
        <select class="bp-form-select" name="status">
          <option value="">All Status</option>
          <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
          <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
          <option value="overdue" {{ request('status') === 'overdue' ? 'selected' : '' }}>Overdue</option>
          <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
        </select>
        <select class="bp-form-select" name="lender_id">
          <option value="">All Lenders</option>
          @foreach($lenders as $lender)
            <option value="{{ $lender->id }}" {{ request('lender_id') == $lender->id ? 'selected' : '' }}>{{ $lender->name }}</option>
          @endforeach
        </select>
        <input type="date" class="bp-form-control bp-filter-date" name="date_from" value="{{ request('date_from') }}">
        <span class="text-muted">to</span>
        <input type="date" class="bp-form-control bp-filter-date" name="date_to" value="{{ request('date_to') }}">
      </x-core::table.filter-bar>
    </x-slot:filters>

    <x-core::table.header>
      <x-core::table.column :sortable="true" field="loan_number">Loan #</x-core::table.column>
      <x-core::table.column>Lender</x-core::table.column>
      <x-core::table.column :sortable="true" field="principal_amount">Principal</x-core::table.column>
      <x-core::table.column>Repaid</x-core::table.column>
      <x-core::table.column>Remaining</x-core::table.column>
      <x-core::table.column>Progress</x-core::table.column>
      <x-core::table.column>Status</x-core::table.column>
      <x-core::table.column :sortable="true" field="next_due_date">Next Due</x-core::table.column>
      <x-core::table.column>Actions</x-core::table.column>
    </x-core::table.header>

    <tbody>
      @forelse($loans as $loan)
      <tr>
        <td><a href="{{ route('loans.show', $loan->id) }}" class="fw-700">{{ $loan->loan_number }}</a></td>
        <td>
          <div class="fw-600">{{ $loan->lender->name ?? '' }}</div>
          @if($loan->lender && $loan->lender->company_name)
            <div class="fs-11 text-muted">{{ $loan->lender->company_name }}</div>
          @endif
        </td>
        <td class="fw-800">{{ money($loan->principal_amount) }}</td>
        <td class="text-success fw-700">{{ money($loan->total_repaid) }}</td>
        <td class="text-danger fw-700">{{ money($loan->total_remaining) }}</td>
        <td>
          @php
            $paidCount = $loan->schedules->where('status', 'paid')->count();
          @endphp
          <div class="fw-700">{{ $paidCount }}<span class="text-muted fw-400">/{{ $loan->total_installments }}</span></div>
          <div class="bp-progress-mini">
            <div class="bp-progress-bar {{ $loan->status === 'completed' ? 'bp-progress-bar-success' : ($loan->status === 'overdue' ? 'bp-progress-bar-danger' : '') }}" style="width: {{ $loan->progress_percentage }}%"></div>
          </div>
        </td>
        <td>
          @if($loan->status === 'active')
            <span class="bp-badge bp-badge-primary">Active</span>
          @elseif($loan->status === 'completed')
            <span class="bp-badge bp-badge-success">Completed</span>
          @elseif($loan->status === 'overdue')
            <span class="bp-badge bp-badge-danger">Overdue</span>
          @elseif($loan->status === 'cancelled')
            <span class="bp-badge bp-badge-dark">Cancelled</span>
          @endif
        </td>
        <td>
          @if($loan->next_due_date)
            @php
              $daysUntilDue = now()->startOfDay()->diffInDays($loan->next_due_date, false);
            @endphp
            <div class="fw-600 {{ $daysUntilDue < 0 ? 'text-danger' : '' }}">{{ $loan->next_due_date->format('d M Y') }}</div>
            <div class="fs-11 {{ $daysUntilDue < 0 ? 'text-danger fw-600' : 'text-muted' }}">
              @if($daysUntilDue < 0)
                {{ abs($daysUntilDue) }} days overdue
              @elseif($daysUntilDue === 0)
                Due today
              @else
                {{ $daysUntilDue }} days remaining
              @endif
            </div>
          @else
            <div class="text-muted">Completed</div>
          @endif
        </td>
        <td>
          <div class="dropdown">
            <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="{{ route('loans.show', $loan->id) }}"><i class="fa-solid fa-eye me-2"></i> View</a></li>
            </ul>
          </div>
        </td>
      </tr>
      @empty
      <x-core::table.empty colspan="9" icon="fa-solid fa-hand-holding-dollar" title="No loans found" description="There are no loans matching your filters.">
        @bpCan('finance.create')
        <a href="{{ route('loans.create') }}" class="bp-btn bp-btn-primary"><i class="fa-solid fa-plus me-1"></i>New Loan</a>
        @endbpCan
      </x-core::table.empty>
      @endforelse
    </tbody>

    <x-slot:pagination>
      <x-core::table.pagination :paginator="$loans" itemLabel="loans" />
    </x-slot:pagination>
  </x-core::table>

@endsection
