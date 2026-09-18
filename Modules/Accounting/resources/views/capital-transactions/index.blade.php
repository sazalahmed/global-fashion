@extends('core::layouts.master')

@section('title', __('Deposit & Withdraw'))
@section('page-title', __('Deposit & Withdraw'))

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Deposit & Withdraw</span>
@endsection

@section('content')

  {{-- Summary cards --}}
  <div class="row g-3 mb-3">
    <div class="col-md-4">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-success"><i class="fa-solid fa-arrow-down"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Total Deposits</div>
          <div class="bp-stat-value">{{ money($totals['deposit']) }}</div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-arrow-up"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Total Withdrawals</div>
          <div class="bp-stat-value">{{ money($totals['withdraw']) }}</div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="bp-stat-card">
        <div class="bp-stat-icon {{ $totals['net'] >= 0 ? 'icon-primary' : 'icon-warning' }}">
          <i class="fa-solid fa-scale-balanced"></i>
        </div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Net Position</div>
          <div class="bp-stat-value">{{ money($totals['net']) }}</div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3">
    {{-- Form --}}
    @bpCan('accounting.create')
    <div class="col-lg-4">
      <div class="bp-card">
        <div class="bp-card-header">
          <h5 class="bp-card-title">
            <i class="fa-solid {{ $type === 'deposit' ? 'fa-arrow-down text-success' : 'fa-arrow-up text-danger' }} me-2"></i>
            New {{ ucfirst($type) }}
          </h5>
        </div>
        <div class="bp-card-body">
          <form action="{{ route('capital-transactions.store') }}" method="POST">
            @csrf
            <input type="hidden" name="type" value="{{ $type }}">

            <div class="mb-3">
              <label class="bp-form-label">Date *</label>
              <input type="date" class="bp-form-control @error('transaction_date') is-invalid @enderror" name="transaction_date" value="{{ old('transaction_date', date('Y-m-d')) }}" required>
              @error('transaction_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
              <label class="bp-form-label">Payment Account *</label>
              <select class="bp-form-select w-100 @error('payment_account_id') is-invalid @enderror" name="payment_account_id" required>
                <option value="">Select account</option>
                @foreach($accounts as $acc)
                  <option value="{{ $acc->id }}" {{ old('payment_account_id') == $acc->id ? 'selected' : '' }}>{{ $acc->name }}</option>
                @endforeach
              </select>
              @error('payment_account_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
              <label class="bp-form-label">Amount ({{ currency_symbol() }}) *</label>
              <input type="number" class="bp-form-control @error('amount') is-invalid @enderror" name="amount" value="{{ old('amount') }}" step="0.01" min="0.01" required>
              @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
              <label class="bp-form-label">Note</label>
              <textarea class="bp-form-control" name="note" rows="2" placeholder="e.g. Owner investment, monthly withdrawal">{{ old('note') }}</textarea>
            </div>

            <button type="submit" class="bp-btn {{ $type === 'deposit' ? 'bp-btn-success' : 'bp-btn-danger' }} w-100">
              <i class="fa-solid fa-save me-1"></i> Record {{ ucfirst($type) }}
            </button>
          </form>
        </div>
      </div>
    </div>
    @endbpCan

    {{-- List with tabs --}}
    <div class="col-lg-8">
      <div class="bp-card">
        <div class="bp-card-header">
          <ul class="nav nav-tabs card-header-tabs">
            <li class="nav-item">
              <a class="nav-link {{ $type === 'deposit' ? 'active' : '' }}" href="{{ route('capital-transactions.index', ['type' => 'deposit']) }}">
                <i class="fa-solid fa-arrow-down text-success me-1"></i> Deposits
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ $type === 'withdraw' ? 'active' : '' }}" href="{{ route('capital-transactions.index', ['type' => 'withdraw']) }}">
                <i class="fa-solid fa-arrow-up text-danger me-1"></i> Withdrawals
              </a>
            </li>
          </ul>
        </div>
        <div class="bp-card-body p-0">
          <div class="bp-table-wrapper">
            <table class="bp-table">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Account</th>
                  <th>Note</th>
                  <th class="text-end">Amount</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                @forelse($transactions as $tx)
                  <tr>
                    <td>{{ $tx->transaction_date->format('d M Y') }}</td>
                    <td class="fw-600">{{ $tx->paymentAccount->name ?? '' }}</td>
                    <td class="fs-13 text-muted">{{ $tx->note ?: '' }}</td>
                    <td class="text-end fw-700 {{ $tx->type === 'deposit' ? 'text-success' : 'text-danger' }}">
                      {{ money($tx->amount) }}
                    </td>
                    <td class="text-end">
                      @bpCan('accounting.delete')
                      <div class="dropdown">
                        <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                        <ul class="dropdown-menu dropdown-menu-end">
                          <li>
                            <form action="{{ route('capital-transactions.destroy', $tx) }}" method="POST" onsubmit="return confirm('Delete this transaction?');">
                              @csrf @method('DELETE')
                              <button type="submit" class="dropdown-item text-danger"><i class="fa-solid fa-trash me-2"></i> Delete</button>
                            </form>
                          </li>
                        </ul>
                      </div>
                      @endbpCan
                    </td>
                  </tr>
                @empty
                  <x-core::table.empty colspan="5" :icon="'fa-solid ' . ($type === 'deposit' ? 'fa-arrow-down' : 'fa-arrow-up')" title="No {{ $type }} transactions yet." />
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
        @if($transactions->hasPages())
          <div class="bp-card-footer">
            <div class="bp-pagination">
              <span class="page-info">Showing {{ $transactions->firstItem() }}-{{ $transactions->lastItem() }} of {{ $transactions->total() }}</span>
              {{ $transactions->links('core::components.table.pagination-links') }}
            </div>
          </div>
        @endif
      </div>
    </div>
  </div>

@endsection
