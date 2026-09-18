@extends('core::layouts.master')

@section('title', __('Personal Loans'))
@section('page-title', __('Personal Loans'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('loans.index') }}">Loans</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Personal Loans</span>
@endsection

@section('page-actions')
    @bpCan('finance.create')
        <a href="{{ route('personal-loans.create') }}" class="bp-btn bp-btn-primary">
            <i class="fa-solid fa-plus me-1"></i> {{ __('Add Borrower') }}
        </a>
    @endbpCan
@endsection

@section('content')

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-users"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">{{ __('Total Borrowers') }}</div>
                    <div class="bp-stat-value">{{ number_format($stats['total_borrowers']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-hand-holding-dollar"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">{{ __('They Owe Us') }}</div>
                    <div class="bp-stat-value">{{ currency_symbol() }}
                        {{ number_format($stats['total_receivable'], 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-warning"><i
                        class="fa-solid fa-hand-holding-dollar fa-flip-horizontal"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">{{ __('We Owe Them') }}</div>
                    <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($stats['total_payable'], 0) }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-success"><i class="fa-solid fa-circle-check"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">{{ __('Recovered / Paid Back') }}</div>
                    <div class="bp-stat-value">{{ currency_symbol() }}
                        {{ number_format($stats['total_recovered'] + $stats['total_returned'], 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <x-core::table>
        <x-slot:filters>
            <x-core::table.filter-bar searchPlaceholder="Search borrower name, phone...">
                <select class="bp-form-select" name="status">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
                <select class="bp-form-select" name="has_outstanding">
                    <option value="">All</option>
                    <option value="1" {{ request('has_outstanding') === '1' ? 'selected' : '' }}>With Outstanding
                    </option>
                </select>
            </x-core::table.filter-bar>
        </x-slot:filters>

        <x-core::table.header>
            <x-core::table.column>Name</x-core::table.column>
            <x-core::table.column>Phone</x-core::table.column>
            <x-core::table.column>{{ __('Total Given') }}</x-core::table.column>
            <x-core::table.column>{{ __('Total Taken') }}</x-core::table.column>
            <x-core::table.column>{{ __('Balance') }}</x-core::table.column>
            <x-core::table.column>Status</x-core::table.column>
            <x-core::table.column>Actions</x-core::table.column>
        </x-core::table.header>

        <tbody>
            @forelse($borrowers as $borrower)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="bp-user-avatar bp-avatar-sm">
                                @if ($borrower->photo)
                                    <img src="{{ upload_url_sm($borrower->photo) }}" alt="{{ $borrower->name }}">
                                @else
                                    {{ $borrower->initials }}
                                @endif
                            </div>
                            <div>
                                <div class="fw-700 fs-13">{{ $borrower->name }}</div>
                                @if ($borrower->email)
                                    <div class="fs-11 text-muted">{{ $borrower->email }}</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td>{{ $borrower->phone ?? '' }}</td>
                    <td class="fw-600">{{ currency_symbol() }} {{ number_format($borrower->total_lent, 0) }}</td>
                    <td class="fw-600">{{ currency_symbol() }} {{ number_format($borrower->total_taken, 0) }}</td>
                    <td>
                        @if ($borrower->outstanding_balance > 0)
                            <span class="fw-800 text-danger">{{ currency_symbol() }}
                                {{ number_format($borrower->outstanding_balance, 0) }}</span>
                            <div class="fs-11 text-muted">{{ __('they owe') }}</div>
                        @elseif ($borrower->outstanding_balance < 0)
                            <span class="fw-800 text-warning">{{ currency_symbol() }}
                                {{ number_format(abs($borrower->outstanding_balance), 0) }}</span>
                            <div class="fs-11 text-muted">{{ __('we owe') }}</div>
                        @else
                            <span class="text-success">{{ __('Settled') }}</span>
                        @endif
                    </td>
                    <td>
                        @if ($borrower->status === 'active')
                            <span class="bp-badge bp-badge-success">Active</span>
                        @else
                            <span class="bp-badge bp-badge-secondary">Inactive</span>
                        @endif
                    </td>
                    <td>
                        @bpCanAny('finance.view', 'finance.create', 'finance.edit', 'finance.delete')
                            <div class="dropdown">
                                <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i
                                        class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    @bpCan('finance.view')
                                        <li><a class="dropdown-item" href="{{ route('personal-loans.show', $borrower) }}"><i
                                                    class="fa-solid fa-hand-holding-dollar me-2"></i>Manage Loan</a></li>
                                    @endbpCan
                                    @bpCan('finance.edit')
                                        <li><a class="dropdown-item" href="{{ route('personal-loans.edit', $borrower) }}"><i
                                                    class="fa-solid fa-pen me-2"></i>Edit</a></li>
                                    @endbpCan
                                    @bpCan('finance.delete')
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        <li>
                                            @if (abs($borrower->outstanding_balance) > 0 || ($borrower->transactions_count ?? 0) > 0)
                                                {{-- Deleting is blocked server-side while a balance or any
                                                     loan history exists — say so up front instead of
                                                     letting the action fail. --}}
                                                @php($blockReason = abs($borrower->outstanding_balance) > 0
                                                    ? __('Cannot delete — unsettled balance of :amount exists. Settle the loan first.', ['amount' => currency_symbol() . ' ' . number_format(abs($borrower->outstanding_balance), 0)])
                                                    : __('Cannot delete — :count loan transaction(s) exist. Mark the borrower inactive instead.', ['count' => $borrower->transactions_count]))
                                                <span class="dropdown-item text-muted" title="{{ $blockReason }}">
                                                    <i class="fa-solid fa-trash me-2"></i>Delete
                                                </span>
                                            @else
                                                <form action="{{ route('personal-loans.destroy', $borrower) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger delete-confirm"
                                                        data-name="{{ $borrower->name }}"><i
                                                            class="fa-solid fa-trash me-2"></i>Delete</button>
                                                </form>
                                            @endif
                                        </li>
                                    @endbpCan
                                </ul>
                            </div>
                        @endbpCanAny
                    </td>
                </tr>
            @empty
                <x-core::table.empty colspan="7" icon="fa-solid fa-hand-holding-dollar" title="No borrowers yet" />
            @endforelse
        </tbody>

        <x-slot:pagination>
            <x-core::table.pagination :paginator="$borrowers" itemLabel="borrowers" />
        </x-slot:pagination>
    </x-core::table>

@endsection
