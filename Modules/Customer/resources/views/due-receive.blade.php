@extends('core::layouts.master')

@section('title', __('Customer Due Receive'))
@section('page-title', __('Customer Due Receive'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('customers.index') }}">Customers</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Due Receive</span>
@endsection

@section('page-actions')
    @bpCan('customers.export')
        <x-core::export-dropdown module="receivables" />
    @endbpCan
    <button class="bp-btn bp-btn-outline" onclick="window.print()"><i class="fa-solid fa-print me-1"></i> Print</button>
@endsection

@section('content')

    <!-- Summary -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-bangladeshi-taka-sign"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Receivable</div>
                    <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($totalDue, 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-users"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Customers with Due</div>
                    <div class="bp-stat-value">{{ $customers->total() }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Due Receive Table -->
    <x-core::table>
        <x-slot:filters>
            <x-core::table.filter-bar searchPlaceholder="Search customer name, phone...">
                <select class="bp-form-select select2-search" name="customer_group_id">
                    <option value="">All Groups</option>
                    @foreach (\Modules\Customer\Models\CustomerGroup::active()->orderBy('name')->get() as $group)
                        <option value="{{ $group->id }}"
                            {{ request('customer_group_id') == $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                    @endforeach
                </select>
            </x-core::table.filter-bar>
        </x-slot:filters>

        <x-core::table.header>
            <x-core::table.column>Customer</x-core::table.column>
            <x-core::table.column>Phone</x-core::table.column>
            <x-core::table.column>Group</x-core::table.column>
            <x-core::table.column>Total Purchased</x-core::table.column>
            <x-core::table.column>Total Paid</x-core::table.column>
            <x-core::table.column>Due Amount</x-core::table.column>
            <x-core::table.column>Actions</x-core::table.column>
        </x-core::table.header>

        <tbody>
            @forelse($customers as $customer)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="bp-user-avatar bp-avatar-sm">
                                @if ($customer->photo)
                                    <img src="{{ upload_url($customer->photo) }}" alt="{{ $customer->name }}">
                                @else
                                    {{ $customer->initials }}
                                @endif
                            </div>
                            <div>
                                <div class="fw-700 fs-13">{{ $customer->name }}</div>
                                @if ($customer->email)
                                    <div class="fs-11 text-muted">{{ $customer->email }}</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td>{{ $customer->phone }}</td>
                    <td>
                        @if ($customer->customerGroup)
                            <span class="bp-badge bp-badge-primary">{{ $customer->customerGroup->name }}</span>
                        @endif
                    </td>
                    <td class="fw-600">{{ currency_symbol() }} {{ number_format($customer->total_purchased, 0) }}</td>
                    <td class="fw-600">{{ currency_symbol() }} {{ number_format($customer->total_paid, 0) }}</td>
                    <td class="text-danger fw-800">{{ currency_symbol() }} {{ number_format($customer->due_amount, 0) }}
                    </td>
                    <td>
                        <div class="dropdown">
                            <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i
                                    class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item"
                                        href="{{ route('payments.create', ['direction' => 'receive', 'party_type' => 'customer', 'party_id' => $customer->id]) }}"><i
                                            class="fa-solid fa-bangladeshi-taka-sign me-2"></i> Receive Due</a>
                                </li>
                                <li>
                                    <a class="dropdown-item"
                                        href="{{ route('customers.ledger', ['customer_id' => $customer->id]) }}"><i
                                            class="fa-solid fa-book me-2"></i> View Ledger</a>
                                </li>
                            </ul>
                        </div>
                    </td>
                </tr>
            @empty
                <x-core::table.empty colspan="7" icon="fa-solid fa-check-circle" title="No outstanding dues"
                    description="All customers have cleared their balances." />
            @endforelse
        </tbody>

        @if ($customers->total())
            <tfoot>
                <tr class="bp-table-total-row">
                    <td colspan="3" class="fw-800">{{ __('Total') }}</td>
                    <td class="fw-800">{{ currency_symbol() }} {{ number_format($dueTotals['purchased'], 0) }}</td>
                    <td class="fw-800">{{ currency_symbol() }} {{ number_format($dueTotals['paid'], 0) }}</td>
                    <td class="text-danger fw-800">{{ currency_symbol() }} {{ number_format($dueTotals['due'], 0) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        @endif

        <x-slot:pagination>
            <x-core::table.pagination :paginator="$customers" itemLabel="customers" />
        </x-slot:pagination>
    </x-core::table>

@endsection
