@extends('core::layouts.master')

@section('title', __('Customers'))
@section('page-title', __('Customers'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Customers</span>
@endsection

@section('page-actions')
    @bpCan('customers.create')
    <button class="bp-btn bp-btn-success" data-bs-toggle="modal" data-bs-target="#importModal"><i
            class="fa-solid fa-file-import me-1"></i> Import</button>
    @endbpCan
    @bpCan('customers.export')
    <x-core::export-dropdown module="customers" />
    @endbpCan
    <button class="bp-btn bp-btn-warning"><i class="fa-solid fa-message me-1"></i> Bulk SMS</button>
    @bpCan('customers.create')
    <a href="{{ route('customers.create') }}" class="bp-btn bp-btn-primary">
        <i class="fa-solid fa-user-plus me-1"></i> Add Customer
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
                    <div class="bp-stat-label">Total Customers</div>
                    <div class="bp-stat-value">{{ number_format($stats['total']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-success"><i class="fa-solid fa-user-plus"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">New This Month</div>
                    <div class="bp-stat-value">{{ number_format($stats['new_this_month']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-bangladeshi-taka-sign"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Receivable</div>
                    <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($stats['total_receivable'], 0) }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-accent"><i class="fa-solid fa-user-check"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Active Customers</div>
                    <div class="bp-stat-value">{{ number_format($stats['active']) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Customers Table -->
    <x-core::table :selectable="true">
        <x-slot:filters>
            <x-core::table.filter-bar searchPlaceholder="Search name, phone, email...">
                <select class="bp-form-select select2-search" name="customer_group_id">
                    <option value="">All Groups</option>
                    @foreach (\Modules\Customer\Models\CustomerGroup::active()->orderBy('name')->get() as $group)
                        <option value="{{ $group->id }}"
                            {{ request('customer_group_id') == $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                    @endforeach
                </select>
                <select class="bp-form-select" name="status">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </x-core::table.filter-bar>
        </x-slot:filters>

        <x-slot:bulkActions>
            <x-core::table.bulk-actions>
                <button class="bp-btn bp-btn-sm bp-btn-outline" data-bulk-action="status" data-bulk-status="active"><i
                        class="fa-solid fa-check me-1"></i> Set Active</button>
                <button class="bp-btn bp-btn-sm bp-btn-outline" data-bulk-action="status" data-bulk-status="inactive"><i
                        class="fa-solid fa-ban me-1"></i> Set Inactive</button>
                @bpCan('customers.delete')
                <button class="bp-btn bp-btn-sm bp-btn-danger" data-bulk-action="delete"><i
                        class="fa-solid fa-trash me-1"></i> Delete</button>
                @endbpCan
            </x-core::table.bulk-actions>
        </x-slot:bulkActions>

        <x-core::table.header :selectable="true">
            <x-core::table.column>Customer</x-core::table.column>
            <x-core::table.column :sortable="true" field="phone">Phone</x-core::table.column>
            <x-core::table.column>Group</x-core::table.column>
            <x-core::table.column :sortable="true" field="total_purchased">Total Purchases</x-core::table.column>
            <x-core::table.column :sortable="true" field="due_amount">Due Balance</x-core::table.column>
            <x-core::table.column>Status</x-core::table.column>
            <x-core::table.column>Actions</x-core::table.column>
        </x-core::table.header>

        <tbody>
            @forelse($customers as $customer)
                <tr>
                    <td><input type="checkbox" class="form-check-input row-checkbox" value="{{ $customer->id }}"></td>
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
                    <td class="fw-700">{{ currency_symbol() }} {{ number_format($customer->visible_total ?? 0, 0) }}</td>
                    <td class="{{ ($customer->visible_due ?? 0) > 0 ? 'text-danger fw-700' : 'text-success' }}">
                        {{ currency_symbol() }} {{ number_format($customer->visible_due ?? 0, 0) }}
                    </td>
                    <td>
                        <x-core::status-toggle :url="route('customers.toggle-status', $customer->id)" :active="$customer->is_active" />
                    </td>
                    <td>
                        @bpCanAny('customers.view','customers.edit','customers.delete','payments.create')
                        <div class="dropdown">
                            <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                @bpCan('customers.view')
                                <li>
                                    <a class="dropdown-item" href="{{ route('customers.show', $customer->id) }}">
                                        <i class="fa-solid fa-eye me-2"></i> View Profile
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item"
                                        href="{{ route('customers.ledger', ['customer_id' => $customer->id]) }}">
                                        <i class="fa-solid fa-book me-2"></i> Ledger
                                    </a>
                                </li>
                                @endbpCan
                                @bpCan('payments.create')
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                @if (($customer->visible_due ?? 0) > 0)
                                    <li>
                                        <a class="dropdown-item"
                                            href="{{ route('payments.create', ['direction' => 'receive', 'party_type' => 'customer', 'party_id' => $customer->id]) }}">
                                            <i class="fa-solid fa-bangladeshi-taka-sign me-2"></i> Receive Due
                                        </a>
                                    </li>
                                @endif
                                <li>
                                    <a class="dropdown-item"
                                        href="{{ route('payments.create', ['direction' => 'receive', 'party_type' => 'customer', 'party_id' => $customer->id, 'payment_type' => 'advance_payment']) }}">
                                        <i class="fa-solid fa-wallet me-2"></i> Receive Advance
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item"
                                        href="{{ route('payments.create', ['direction' => 'pay', 'party_type' => 'customer', 'party_id' => $customer->id, 'payment_type' => 'advance_return']) }}">
                                        <i class="fa-solid fa-rotate-left me-2"></i> Advance Return
                                    </a>
                                </li>
                                @endbpCan
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                @bpCan('customers.edit')
                                <li>
                                    <a class="dropdown-item" href="{{ route('customers.edit', $customer->id) }}">
                                        <i class="fa-solid fa-pen me-2"></i> Edit
                                    </a>
                                </li>
                                @endbpCan
                                @bpCan('customers.delete')
                                <li>
                                    <form action="{{ route('customers.destroy', $customer->id) }}" method="POST"
                                        class="delete-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="dropdown-item text-danger delete-confirm"
                                            data-name="{{ $customer->name }}">
                                            <i class="fa-solid fa-trash me-2"></i> Delete
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
                <x-core::table.empty colspan="8" icon="fa-solid fa-users" title="No customers found"
                    description="Try adjusting your filters or add a new customer." />
            @endforelse
        </tbody>

        @if ($customers->count())
            <tfoot>
                <tr class="bp-table-total-row">
                    <td colspan="4" class="fw-700">Total ({{ number_format($customers->total()) }} customers)</td>
                    <td class="fw-800">{{ currency_symbol() }} {{ number_format($listTotals['total_purchased'], 0) }}
                    </td>
                    <td class="fw-800 {{ $listTotals['due_amount'] > 0 ? 'text-danger' : 'text-success' }}">
                        {{ currency_symbol() }} {{ number_format($listTotals['due_amount'], 0) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        @endif

        <x-slot:pagination>
            <x-core::table.pagination :paginator="$customers" itemLabel="customers" />
        </x-slot:pagination>
    </x-core::table>

    <!-- Import Modal -->
    <div class="modal fade" id="importModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('import', 'customers') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title fw-700"><i class="fa-solid fa-file-import me-2"></i>Import Customers</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="bp-form-label">Upload File (Excel/CSV) *</label>
                            <input type="file" class="bp-form-control" name="file" accept=".xlsx,.xls,.csv"
                                required>
                            <small class="text-muted fs-11">Max 5MB. Accepted: .xlsx, .xls, .csv</small>
                        </div>
                        <div class="bp-card bg-light p-3">
                            <div class="fw-700 fs-12 mb-2">Required Columns:</div>
                            <code class="fs-11">name</code> (required), <code class="fs-11">phone</code>, <code
                                class="fs-11">email</code>, <code class="fs-11">address</code>, <code
                                class="fs-11">status</code>
                            <div class="text-muted fs-11 mt-2">Customers with duplicate phone numbers will be skipped.
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="bp-btn bp-btn-danger" data-bs-dismiss="modal"><i
                                class="fa-solid fa-xmark me-1"></i>Cancel</button>
                        <button type="submit" class="bp-btn bp-btn-primary"><i class="fa-solid fa-upload me-1"></i>
                            Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection
