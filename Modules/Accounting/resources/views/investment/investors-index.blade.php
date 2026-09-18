@extends('core::layouts.master')

@section('title', 'Investors')
@section('page-title', 'Investors')

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('investment.dashboard') }}">Investment</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Investors</span>
@endsection

@section('page-actions')
    <a href="{{ route('investment.dashboard') }}" class="bp-btn bp-btn-danger"><i
            class="fa-solid fa-arrow-left me-1"></i>Back</a>
    @bpCan('accounting.create')
    <a href="{{ route('investment.investors.create') }}" class="bp-btn bp-btn-primary"><i
            class="fa-solid fa-user-plus me-1"></i>Add Investor</a>
    @endbpCan
@endsection

@section('content')

    <div class="bp-card mb-3">
        <div class="bp-card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="bp-form-label">Type</label>
                    <select name="type" class="bp-form-select w-100">
                        <option value="">All</option>
                        <option value="shareholder" @selected($type === 'shareholder')>Shareholders</option>
                        <option value="investor" @selected($type === 'investor')>Investors (profit share)</option>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="bp-form-label">Search</label>
                    <input type="text" class="bp-form-control" name="search" value="{{ request('search') }}"
                        placeholder="Name, phone, or email">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="bp-btn bp-btn-primary" title="Apply Filters"><i class="fa-solid fa-filter"></i></button>
                    <a href="{{ route('investment.investors.index') }}" class="bp-btn bp-btn-danger" title="Reset Filters"><i class="fa-solid fa-rotate"></i></a>
                </div>
            </form>
        </div>
    </div>

    <div class="bp-card">
        <div class="bp-card-body p-0">
            <div class="bp-table-wrapper">
                <table class="bp-table investores_table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Contact</th>
                            <th>Shares / %</th>
                            <th>Net Capital</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($investors as $inv)
                            <tr>
                                <td>
                                    <a href="{{ route('investment.investors.show', $inv) }}"
                                        class="fw-700">{{ $inv->name }}</a>
                                    <div class="fs-12 text-muted">Joined {{ $inv->join_date?->format('d M Y') }}</div>
                                </td>
                                <td><span
                                        class="bp-badge {{ $inv->type === 'shareholder' ? 'bp-badge-primary' : 'bp-badge-info' }}">{{ ucfirst($inv->type) }}</span>
                                </td>
                                <td>
                                    @if ($inv->phone)
                                        <div class="fs-12">{{ $inv->phone }}</div>
                                    @endif
                                    @if ($inv->email)
                                        <div class="fs-12 text-muted">{{ $inv->email }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if ($inv->type === 'shareholder')
                                        <div class="fw-600">{{ number_format($inv->shares_owned ?? 0, 0) }} sh</div>
                                        <div class="fs-11 text-muted">
                                            {{ $totalShares > 0 ? num(($inv->shares_owned / $totalShares) * 100) : '0.00' }}%
                                        </div>
                                    @else
                                        <div class="fw-600">{{ num($inv->profit_share_pct ?? 0) }}%</div>
                                    @endif
                                </td>
                                <td class="fw-600">{{ currency_symbol() }}
                                    {{ number_format($inv->netCapital(), 0) }}</td>
                                <td>
                                    <x-core::status-toggle :url="route('investment.investors.toggle-status', $inv->id)" :active="$inv->is_active" />
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item" href="{{ route('investment.investors.show', $inv) }}"><i class="fa-solid fa-eye me-2"></i> View</a></li>
                                            @bpCan('accounting.edit')
                                            <li><a class="dropdown-item" href="{{ route('investment.investors.edit', $inv) }}"><i class="fa-solid fa-pen me-2"></i> Edit</a></li>
                                            @endbpCan
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <x-core::table.empty colspan="7" icon="fa-solid fa-users" title="No investors found" />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($investors->hasPages())
            <div class="bp-card-footer">{{ $investors->links('core::components.table.pagination-links') }}</div>
        @endif
    </div>

@endsection
