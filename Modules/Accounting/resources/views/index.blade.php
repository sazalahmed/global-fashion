@extends('core::layouts.master')

@section('title', __("Accounting"))
@section('page-title', __("Accounting Overview"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Accounting</span>
@endsection

@section('content')

<!-- Quick Stats -->
<div class="row g-3 mb-4">
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-success"><i class="fa-solid fa-chart-line"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Revenue (This Month)</div>
        <div class="bp-stat-value">{{ currency_symbol() }} 4,85,000</div>
        <div class="bp-stat-change up"><i class="fa-solid fa-arrow-up"></i> 12.3%</div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-receipt"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Expenses (This Month)</div>
        <div class="bp-stat-value">{{ currency_symbol() }} 3,42,000</div>
        <div class="bp-stat-change down"><i class="fa-solid fa-arrow-down"></i> 5.1%</div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-bangladeshi-taka-sign"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Net Profit (This Month)</div>
        <div class="bp-stat-value">{{ currency_symbol() }} 1,43,000</div>
        <div class="bp-stat-change up"><i class="fa-solid fa-arrow-up"></i> 29.5% margin</div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-warning"><i class="fa-solid fa-wallet"></i></div>
      <div class="bp-stat-content">
        <div class="bp-stat-label">Cash Balance</div>
        <div class="bp-stat-value">{{ currency_symbol() }} 7,96,500</div>
      </div>
    </div>
  </div>
</div>

<!-- Quick Links -->
<div class="row g-3">
  <div class="col-md-6 col-xl-4">
    <div class="bp-card">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-sitemap me-2"></i>Accounts</h5>
      </div>
      <div class="bp-card-body">
        <div class="d-flex flex-column gap-2">
          <a href="{{ route('accounting.chart-of-accounts') }}" class="bp-btn bp-btn-outline w-100 justify-content-start">
            <i class="fa-solid fa-sitemap me-2"></i> Chart of Accounts
          </a>
          <a href="{{ route('accounting.chart-of-accounts.create') }}" class="bp-btn bp-btn-outline w-100 justify-content-start">
            <i class="fa-solid fa-plus me-2"></i> Add New Account
          </a>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-6 col-xl-4">
    <div class="bp-card">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-book me-2"></i>Transactions</h5>
      </div>
      <div class="bp-card-body">
        <div class="d-flex flex-column gap-2">
          <a href="{{ route('accounting.journal-entries') }}" class="bp-btn bp-btn-outline w-100 justify-content-start">
            <i class="fa-solid fa-book me-2"></i> Journal Entries
          </a>
          <a href="{{ route('accounting.journal-entries.create') }}" class="bp-btn bp-btn-outline w-100 justify-content-start">
            <i class="fa-solid fa-plus me-2"></i> New Journal Entry
          </a>
          <a href="{{ route('accounting.general-ledger') }}" class="bp-btn bp-btn-outline w-100 justify-content-start">
            <i class="fa-solid fa-book-open me-2"></i> General Ledger
          </a>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-6 col-xl-4">
    <div class="bp-card">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-chart-pie me-2"></i>Reports</h5>
      </div>
      <div class="bp-card-body">
        <div class="d-flex flex-column gap-2">
          <a href="{{ route('accounting.trial-balance') }}" class="bp-btn bp-btn-outline w-100 justify-content-start">
            <i class="fa-solid fa-scale-balanced me-2"></i> Trial Balance
          </a>
          <a href="{{ route('accounting.profit-loss') }}" class="bp-btn bp-btn-outline w-100 justify-content-start">
            <i class="fa-solid fa-chart-pie me-2"></i> Profit &amp; Loss
          </a>
          <a href="{{ route('accounting.balance-sheet') }}" class="bp-btn bp-btn-outline w-100 justify-content-start">
            <i class="fa-solid fa-building-columns me-2"></i> Balance Sheet
          </a>
          <a href="{{ route('accounting.cash-flow') }}" class="bp-btn bp-btn-outline w-100 justify-content-start">
            <i class="fa-solid fa-money-bill-wave me-2"></i> Cash Flow
          </a>
          <a href="{{ route('accounting.bank-reconciliation') }}" class="bp-btn bp-btn-outline w-100 justify-content-start">
            <i class="fa-solid fa-building-columns me-2"></i> Bank Reconciliation
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script>
'use strict';
</script>
@endpush
