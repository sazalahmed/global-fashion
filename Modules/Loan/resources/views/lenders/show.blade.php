@extends('core::layouts.master')

@section('title', $lender->name)
@section('page-title', $lender->name)

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('loans.index') }}">Loans</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('lenders.index') }}">Lenders</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>{{ $lender->name }}</span>
@endsection

@section('page-actions')
    @bpCan('finance.edit')
        <a href="{{ route('lenders.edit', $lender) }}" class="bp-btn bp-btn-primary"><i class="fa-solid fa-pen me-1"></i> Edit</a>
    @endbpCan
    @bpCan('finance.delete')
        <form action="{{ route('lenders.destroy', $lender) }}" method="POST" class="d-inline">
            @csrf
            @method('DELETE')
            <button type="submit" class="bp-btn bp-btn-danger delete-confirm" data-name="{{ $lender->name }}"><i
                    class="fa-solid fa-trash me-1"></i> Delete</button>
        </form>
    @endbpCan
@endsection

@section('content')

    <!-- Lender Info Card -->
    <div class="bp-card mb-4">
        <div class="bp-card-body">
            <div class="d-flex align-items-center gap-4 mb-4 pb-4 border-bottom">
                <div class="bp-user-avatar bp-avatar-lg lender_info_img">
                    @if ($lender->photo)
                        <img src="{{ upload_url($lender->photo) }}" alt="{{ $lender->name }}">
                    @else
                        {{ $lender->initials }}
                    @endif
                </div>
                <div class="flex-1">
                    <h4 class="fw-800 mb-1 text-capitalize">{{ $lender->name }}</h4>
                    @if ($lender->company_name)
                        <div class="fs-13 fw-600 text-muted mb-1">{{ $lender->company_name }}</div>
                    @endif
                    <div class="d-flex gap-3 flex-wrap fs-13 text-muted">
                        @if ($lender->phone)
                            <span><i class="fa-solid fa-phone me-1"></i>{{ $lender->phone }}</span>
                        @endif
                        @if ($lender->email)
                            <span><i class="fa-solid fa-envelope me-1"></i>{{ $lender->email }}</span>
                        @endif
                        @if ($lender->address)
                            <span><i class="fa-solid fa-location-dot me-1"></i>{{ $lender->address }}</span>
                        @endif
                    </div>
                    <div class="d-flex gap-2 mt-2">
                        @if ($lender->status === 'active')
                            <span class="bp-badge bp-badge-success">Active</span>
                        @else
                            <span class="bp-badge bp-badge-danger">Inactive</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Details Grid -->
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="fs-12 text-muted fw-600">Bank</div>
                    <div class="fw-700 fs-13">{{ $lender->bank_name ?? '--' }}</div>
                </div>
                <div class="col-md-3">
                    <div class="fs-12 text-muted fw-600">Account Number</div>
                    <div class="fw-700 fs-13">{{ $lender->account_number ?? '--' }}</div>
                </div>
                <div class="col-md-3">
                    <div class="fs-12 text-muted fw-600">Branch</div>
                    <div class="fw-700 fs-13">{{ $lender->bank_branch ?? '--' }}</div>
                </div>
                <div class="col-md-3">
                    <div class="fs-12 text-muted fw-600">Opening Balance</div>
                    <div class="fw-700 fs-13">
                        {{ $lender->opening_balance ? currency_symbol() . ' ' . number_format($lender->opening_balance, 0) : '--' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="row g-3 mb-4">
        <div class="col-xl-4 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-hand-holding-dollar"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Borrowed</div>
                    <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($totalDebit, 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-success"><i class="fa-solid fa-check-circle"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Repaid</div>
                    <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($totalCredit, 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-bangladeshi-taka-sign"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Outstanding Balance</div>
                    <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($currentBalance, 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ledger Table -->
    <div class="bp-card ledger_show_table">
        <div class="bp-card-header">
            <h5 class="bp-card-title"><i class="fa-solid fa-book me-2"></i>Ledger</h5>
        </div>
        <div class="bp-card-body p-0">
            <div class="bp-table-wrapper">
                <table class="bp-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Reference</th>
                            <th>Type</th>
                            <th>Debit ({{ currency_symbol() }})</th>
                            <th>Credit ({{ currency_symbol() }})</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ledgerEntries as $entry)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($entry['date'])->format('d M Y') }}</td>
                                <td>{{ $entry['reference'] ?? '--' }}</td>
                                <td>{{ $entry['type'] }}</td>
                                <td>
                                    @if ($entry['debit'] > 0)
                                        <span class="text-danger">{{ currency_symbol() }}
                                            {{ number_format($entry['debit'], 0) }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if ($entry['credit'] > 0)
                                        <span class="text-success">{{ currency_symbol() }}
                                            {{ number_format($entry['credit'], 0) }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <x-core::table.empty colspan="5" icon="fa-solid fa-book" title="No ledger entries found" />
                        @endforelse
                    </tbody>
                    @if ($ledgerEntries->count() > 0)
                        <tfoot>
                            <tr class="fw-800">
                                <td colspan="3" class="text-end pe-3">Totals</td>
                                <td class="ps-3 text-danger">{{ currency_symbol() }}
                                    {{ number_format($totalDebit, 0) }}</td>
                                <td class="ps-2 text-success">{{ currency_symbol() }}
                                    {{ number_format($totalCredit, 0) }}</td>
                            </tr>
                            <tr class="fw-800">
                                <td colspan="3" class="text-end pe-3">Outstanding Balance</td>
                                <td colspan="2" class="ps-3 fw-800">{{ currency_symbol() }}
                                    {{ number_format($currentBalance, 0) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

@endsection
