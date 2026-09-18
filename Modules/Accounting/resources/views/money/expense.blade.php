@extends('core::layouts.master')

@section('title', __("Expense"))
@section('page-title', __("money.expense_title"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>{{ __('money.menu') }}</span>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>{{ __('money.expense_title') }}</span>
@endsection

@section('content')

<div class="bp-card mb-4">
  <div class="bp-card-body">
    <form method="GET">
      <div class="row g-3 align-items-end bp-report-filters">
        <div class="col-md-4">
          <label class="bp-form-label">{{ __('money.from') }}</label>
          <input type="date" class="bp-form-control" name="date_from" value="{{ $from }}">
        </div>
        <div class="col-md-4">
          <label class="bp-form-label">{{ __('money.to') }}</label>
          <input type="date" class="bp-form-control" name="date_to" value="{{ $to }}">
        </div>
        <div class="col-md-4 d-flex gap-2">
          <button type="submit" class="bp-btn bp-btn-primary" title="Apply Filters"><i class="fa-solid fa-filter"></i></button>
          <a href="{{ route('money.expense') }}" class="bp-btn bp-btn-danger" title="Reset Filters"><i class="fa-solid fa-rotate"></i></a>
        </div>
      </div>
    </form>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-money-bill-wave"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">{{ __('money.total_expense') }}</div>
        <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($data['total'], 0) }}</div>
      </div>
    </div>
  </div>
  @foreach($data['by_account']->take(2) as $name => $amount)
    <div class="col-md-4">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-warning"><i class="fa-solid fa-tag"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">{{ $name }}</div>
          <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($amount, 0) }}</div>
        </div>
      </div>
    </div>
  @endforeach
</div>

<div class="bp-card">
  <div class="bp-card-body p-0">
    <div class="bp-table-wrapper">
      <table class="bp-table">
        <thead>
          <tr>
            <th>{{ __('money.date') }}</th>
            <th>{{ __('money.reference') }}</th>
            <th>{{ __('money.description') }}</th>
            <th>{{ __('money.expense_type') }}</th>
            <th class="text-end">{{ __('money.amount') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse($data['rows'] as $r)
            <tr>
              <td class="fs-12">{{ \Carbon\Carbon::parse($r->entry_date)->format('d M Y') }}</td>
              <td class="fs-12 fw-600">{{ $r->reference ?: $r->entry_number }}</td>
              <td class="fs-13">{{ $r->entry_description }}</td>
              <td class="fs-12 text-muted">{{ $r->account_name }}</td>
              <td class="text-end text-danger fw-700">{{ num((float) $r->amount) }}</td>
            </tr>
          @empty
            <x-core::table.empty colspan="5" icon="fa-solid fa-money-bill-wave" :title="__('money.no_expense')" />
          @endforelse
        </tbody>
        @if($data['rows']->count())
        <tfoot>
          <tr class="fw-700">
            <td colspan="4" class="text-end">{{ __('money.total') }}</td>
            <td class="text-end text-danger">{{ money($data['total']) }}</td>
          </tr>
        </tfoot>
        @endif
      </table>
    </div>
  </div>
</div>

@endsection
