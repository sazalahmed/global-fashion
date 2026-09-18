@extends('core::layouts.master')

@section('title', __('Payments'))
@section('page-title', __('Payments'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Payments</span>
@endsection

@section('page-actions')
    @bpCan('payments.export')
        <x-core::export-dropdown module="payments" />
    @endbpCan
@endsection

@section('content')

    <!-- Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-success"><i class="fa-solid fa-arrow-down"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Received (This Month)</div>
                    <div class="bp-stat-value">{{ money($stats['total_received']) }}</div>
                    <div class="bp-stat-change"><span class="text-muted">Today: {{ money($stats['today_received']) }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-arrow-up"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Paid (This Month)</div>
                    <div class="bp-stat-value">{{ money($stats['total_paid']) }}</div>
                    <div class="bp-stat-change"><span class="text-muted">Today: {{ money($stats['today_paid']) }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-info"><i class="fa-solid fa-wallet"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Transactions (This Month)</div>
                    <div class="bp-stat-value">{{ number_format($stats['total_transactions']) }}</div>
                    <div class="bp-stat-change"><span class="text-muted">Payments recorded</span></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-warning"><i class="fa-solid fa-bangladeshi-taka-sign"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Net Flow (This Month)</div>
                    <div class="bp-stat-value">{{ money($stats['total_received'] - $stats['total_paid']) }}</div>
                    <div class="bp-stat-change"><span class="text-muted">Received minus Paid</span></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payments Table -->
    <x-core::table :selectable="true">
        <x-slot:filters>
            <x-core::table.filter-bar searchPlaceholder="Search reference, name..." :searchValue="request('search')">
                <select class="bp-form-select" name="direction">
                    <option value="">All Directions</option>
                    <option value="receive" @selected(request('direction') === 'receive')>Received</option>
                    <option value="pay" @selected(request('direction') === 'pay')>Paid</option>
                </select>
                <select class="bp-form-select" name="party_type">
                    <option value="">All Parties</option>
                    <option value="customer" @selected(request('party_type') === 'customer')>Customer</option>
                    <option value="supplier" @selected(request('party_type') === 'supplier')>Supplier</option>
                    <option value="employee" @selected(request('party_type') === 'employee')>Employee</option>
                </select>
                <select class="bp-form-select" name="payment_account_id">
                    <option value="">All Methods</option>
                    <x-payment::account-options :selected="request('payment_account_id')" />
                </select>
                <input type="date" class="bp-form-control bp-filter-date" name="date_from"
                    value="{{ request('date_from') }}">
                <span class="text-muted">to</span>
                <input type="date" class="bp-form-control bp-filter-date" name="date_to"
                    value="{{ request('date_to') }}">
            </x-core::table.filter-bar>
        </x-slot:filters>

        <x-slot:bulkActions>
            <x-core::table.bulk-actions>
                <button class="bp-btn bp-btn-sm bp-btn-danger" data-bulk-action="delete"><i
                        class="fa-solid fa-trash me-1"></i> Delete</button>
            </x-core::table.bulk-actions>
        </x-slot:bulkActions>

        <x-core::table.header :selectable="true">
            <x-core::table.column :sortable="true" field="payment_date">Date</x-core::table.column>
            <x-core::table.column :sortable="true" field="payment_number">Reference#</x-core::table.column>
            <x-core::table.column>Direction</x-core::table.column>
            <x-core::table.column>Party Type</x-core::table.column>
            <x-core::table.column :sortable="true" field="amount">Amount ({{ currency_symbol() }})</x-core::table.column>
            <x-core::table.column>Method</x-core::table.column>
            <x-core::table.column>Account</x-core::table.column>
            <x-core::table.column>Created By</x-core::table.column>
            <x-core::table.column>Actions</x-core::table.column>
        </x-core::table.header>

        <tbody>
            @forelse($payments as $payment)
                <tr>
                    <td><input type="checkbox" class="form-check-input row-checkbox" value="{{ $payment->id }}"></td>
                    <td>{{ $payment->payment_date->format('d M Y') }}</td>
                    <td class="fw-700">
                        <a href="{{ route('payments.show', $payment) }}">{{ $payment->payment_number }}</a>
                    </td>
                    <td>
                        @if ($payment->direction === 'receive')
                            <span class="bp-badge bp-badge-success"><i
                                    class="fa-solid fa-arrow-down me-1"></i>Received</span>
                        @else
                            <span class="bp-badge bp-badge-danger"><i class="fa-solid fa-arrow-up me-1"></i>Paid</span>
                        @endif
                    </td>
                    <td>{{ ucfirst($payment->party_type) }}</td>
                    <td class="fw-800">{{ money($payment->amount) }}</td>
                    <td>{{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}</td>
                    <td class="fs-12 text-muted">{{ $payment->paymentAccount->display_name ?? '' }}</td>
                    <td>{{ $payment->creator->name ?? '' }}</td>
                    <td>
                        @bpCanAny('payments.view', 'payments.delete')
                            <div class="dropdown">
                                <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i
                                        class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    @bpCan('payments.view')
                                        <li><a class="dropdown-item" href="{{ route('payments.show', $payment) }}"><i
                                                    class="fa-solid fa-eye"></i> View</a></li>
                                        <li><a class="dropdown-item" href="{{ route('payments.show', $payment) }}"
                                                onclick="setTimeout(function(){window.print()},500)" target="_blank"><i
                                                    class="fa-solid fa-print"></i> Print Receipt</a></li>
                                    @endbpCan
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    @bpCan('payments.delete')
                                        <li>
                                            <form action="{{ route('payments.destroy', $payment) }}" method="POST"
                                                class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger delete-confirm"
                                                    data-name="{{ $payment->payment_number }}">
                                                    <i class="fa-solid fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        </li>
                                    @endbpCan
                                </ul>
                            </div>
                        @endbpCanAny
                    </td>
                </tr>
            @empty
                <x-core::table.empty colspan="10" icon="fa-solid fa-bangladeshi-taka-sign" title="No payments found" />
            @endforelse
        </tbody>

        <x-slot:pagination>
            <x-core::table.pagination :paginator="$payments" itemLabel="payments" />
        </x-slot:pagination>
    </x-core::table>

@endsection
