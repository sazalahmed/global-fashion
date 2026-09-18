@extends('core::layouts.master')

@section('title', __('Supplier Payable'))
@section('page-title', __('Supplier Payable'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('supplier.index') }}">Suppliers</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Payable List</span>
@endsection

@section('page-actions')
    @bpCan('suppliers.export')
        <x-core::export-dropdown module="payables" />
    @endbpCan
@endsection

@section('content')

    <!-- Summary -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-bangladeshi-taka-sign"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Payable</div>
                    <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($totalPayable, 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-truck-field"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Suppliers with Due</div>
                    <div class="bp-stat-value">{{ $suppliers->total() }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payable Table -->
    <x-core::table>
        <x-slot:filters>
            <x-core::table.filter-bar searchPlaceholder="Search company, contact, phone...">
                <select class="bp-form-select select2-search" name="supplier_group_id">
                    <option value="">All Groups</option>
                    @foreach (\Modules\Supplier\Models\SupplierGroup::orderBy('name')->get() as $group)
                        <option value="{{ $group->id }}"
                            {{ request('supplier_group_id') == $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                    @endforeach
                </select>
            </x-core::table.filter-bar>
        </x-slot:filters>

        <x-core::table.header>
            <x-core::table.column>Supplier</x-core::table.column>
            <x-core::table.column>Phone</x-core::table.column>
            <x-core::table.column>Group</x-core::table.column>
            <x-core::table.column>Total Purchase</x-core::table.column>
            <x-core::table.column>Total Paid</x-core::table.column>
            <x-core::table.column>Payable</x-core::table.column>
            <x-core::table.column>Actions</x-core::table.column>
        </x-core::table.header>

        <tbody>
            @forelse($suppliers as $supplier)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="bp-user-avatar bp-avatar-sm">{{ $supplier->initials }}</div>
                            <div>
                                <div class="fw-700 fs-13">{{ $supplier->company_name }}</div>
                                @if ($supplier->contact_person)
                                    <div class="fs-11 text-muted">{{ $supplier->contact_person }}</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td>{{ $supplier->phone }}</td>
                    <td>
                        @if ($supplier->supplierGroup)
                            <span class="bp-badge bp-badge-primary">{{ $supplier->supplierGroup->name }}</span>
                        @endif
                    </td>
                    <td class="text-end fw-600">{{ currency_symbol() }} {{ number_format($supplier->total_purchase, 0) }}
                    </td>
                    <td class="text-end fw-600">{{ currency_symbol() }} {{ number_format($supplier->total_paid, 0) }}</td>
                    <td class="text-end text-danger fw-800">{{ currency_symbol() }}
                        {{ number_format($supplier->due_balance, 0) }}</td>
                    <td>
                        <div class="dropdown">
                            <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i
                                    class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                @bpCan('payments.create')
                                    <li>
                                        <a class="dropdown-item"
                                            href="{{ route('payments.create', ['direction' => 'pay', 'party_type' => 'supplier', 'party_id' => $supplier->id]) }}"><i
                                                class="fa-solid fa-bangladeshi-taka-sign me-2"></i> Pay Due</a>
                                    </li>
                                @endbpCan
                                <li>
                                    <a class="dropdown-item" href="{{ route('supplier.ledger', $supplier->id) }}"><i
                                            class="fa-solid fa-book me-2"></i> View Ledger</a>
                                </li>
                            </ul>
                        </div>
                    </td>
                </tr>
            @empty
                <x-core::table.empty colspan="7" icon="fa-solid fa-check-circle" title="No outstanding payables"
                    description="All supplier balances are settled." />
            @endforelse
        </tbody>

        <x-slot:pagination>
            <x-core::table.pagination :paginator="$suppliers" itemLabel="suppliers" />
        </x-slot:pagination>
    </x-core::table>

@endsection
