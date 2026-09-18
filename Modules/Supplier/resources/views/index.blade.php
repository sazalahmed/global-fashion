@extends('core::layouts.master')

@section('title', __('Suppliers'))
@section('page-title', __('Suppliers'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Suppliers</span>
@endsection

@section('page-actions')
    @bpCan('suppliers.create')
    <button class="bp-btn bp-btn-warning" data-bs-toggle="modal" data-bs-target="#importModal"><i
            class="fa-solid fa-file-import"></i> Import</button>
    @endbpCan
    @bpCan('suppliers.export')
    <x-core::export-dropdown module="suppliers" />
    @endbpCan
    @bpCan('suppliers.create')
    <a href="{{ route('supplier.create') }}" class="bp-btn bp-btn-primary">
        <i class="fa-solid fa-plus"></i> Add Supplier
    </a>
    @endbpCan
@endsection

@section('content')

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-truck-field"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Suppliers</div>
                    <div class="bp-stat-value">{{ $stats['total'] ?? 0 }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-success"><i class="fa-solid fa-circle-check"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Active Suppliers</div>
                    <div class="bp-stat-value">{{ $stats['active'] ?? 0 }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-bangladeshi-taka-sign"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Payable</div>
                    <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($stats['totalPayable'] ?? 0) }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-cart-shopping"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">This Month Purchases</div>
                    <div class="bp-stat-value">{{ currency_symbol() }}
                        {{ number_format($stats['thisMonthPurchases'] ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Suppliers Table -->
    <x-core::table :selectable="true">
        <x-slot:filters>
            <x-core::table.filter-bar searchPlaceholder="Search name, phone, email, company...">
                <select class="bp-form-select" name="status">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
                <select class="bp-form-select" name="payment_status">
                    <option value="">All Payment Status</option>
                    <option value="has_due">Has Due</option>
                    <option value="no_due">No Due</option>
                    <option value="overdue_30">Overdue >30 days</option>
                </select>
            </x-core::table.filter-bar>
        </x-slot:filters>

        <x-slot:bulkActions>
            <x-core::table.bulk-actions>
                <button class="bp-btn bp-btn-sm bp-btn-outline"><i class="fa-solid fa-message me-1"></i> SMS</button>
                <button class="bp-btn bp-btn-sm bp-btn-danger"><i class="fa-solid fa-trash me-1"></i> Delete</button>
            </x-core::table.bulk-actions>
        </x-slot:bulkActions>

        <x-core::table.header :selectable="true">
            <x-core::table.column>Supplier</x-core::table.column>
            <x-core::table.column :sortable="true" field="phone">Phone</x-core::table.column>
            <x-core::table.column>Email</x-core::table.column>
            <x-core::table.column :sortable="true" field="total_purchases">Total Purchases</x-core::table.column>
            <x-core::table.column :sortable="true" field="due_balance">Due Balance</x-core::table.column>
            <x-core::table.column>Last Purchase</x-core::table.column>
            <x-core::table.column>Status</x-core::table.column>
            <x-core::table.column>Actions</x-core::table.column>
        </x-core::table.header>

        <tbody>
            @forelse($suppliers as $supplier)
                <tr>
                    <td><input type="checkbox" class="form-check-input row-checkbox" value="{{ $supplier->id }}"></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="bp-user-avatar bp-avatar-sm">{{ $supplier->initials }}</div>
                            <div>
                                <div class="fw-700 fs-13">{{ $supplier->company_name }}</div>
                                <div class="fs-11 text-muted">{{ $supplier->contact_person ?? '' }}</div>
                            </div>
                        </div>
                    </td>
                    <td>{{ $supplier->phone ?? '' }}</td>
                    <td>{{ $supplier->email ?? '' }}</td>
                    <td class="fw-700">{{ currency_symbol() }} {{ number_format($supplier->total_purchase) }}</td>
                    <td class="{{ $supplier->due_balance > 0 ? 'text-danger fw-700' : 'text-success' }}">
                        {{ currency_symbol() }} {{ number_format($supplier->due_balance) }}</td>
                    <td>{{ $supplier->updated_at?->format('d M Y') ?? '' }}</td>
                    <td><x-core::status-toggle :url="route('supplier.toggle-status', $supplier->id)" :active="$supplier->status === 'active'" /></td>
                    <td>
                        @bpCanAny('suppliers.view','suppliers.edit','payments.create','suppliers.delete')
                        <div class="dropdown">
                            <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                @bpCan('suppliers.view')
                                <li><a class="dropdown-item" href="{{ route('supplier.show', $supplier) }}"><i
                                            class="fa-solid fa-eye"></i> View Profile</a></li>
                                @endbpCan
                                @bpCan('suppliers.edit')
                                <li><a class="dropdown-item" href="{{ route('supplier.edit', $supplier) }}"><i
                                            class="fa-solid fa-pen"></i> Edit</a></li>
                                @endbpCan
                                @bpCan('suppliers.view')
                                <li><a class="dropdown-item" href="{{ route('supplier.ledger', $supplier) }}"><i
                                            class="fa-solid fa-file-lines"></i> Ledger</a></li>
                                @endbpCan
                                @bpCan('payments.create')
                                <li><a class="dropdown-item"
                                        href="{{ route('payments.create', ['direction' => 'pay', 'party_type' => 'supplier', 'party_id' => $supplier->id]) }}"><i
                                            class="fa-solid fa-bangladeshi-taka-sign"></i> Make Payment</a></li>
                                @endbpCan
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                @bpCan('suppliers.delete')
                                <li>
                                    <form action="{{ route('supplier.destroy', $supplier) }}" method="POST"
                                        class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="dropdown-item text-danger"
                                            onclick="return confirm('Are you sure you want to delete {{ $supplier->company_name }}?')"><i
                                                class="fa-solid fa-trash"></i> Delete</button>
                                    </form>
                                </li>
                                @endbpCan
                            </ul>
                        </div>
                        @endbpCanAny
                    </td>
                </tr>
            @empty
                <x-core::table.empty colspan="9" icon="fa-solid fa-truck-field" title="No suppliers found." />
            @endforelse
        </tbody>

        <x-slot:pagination>
            <x-core::table.pagination :paginator="$suppliers" itemLabel="suppliers" />
        </x-slot:pagination>
    </x-core::table>

    <!-- Import Modal -->
    <div class="modal fade" id="importModal" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('import', 'suppliers') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-700">Import Suppliers</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="bp-form-label">Upload CSV/Excel File *</label>
                            <input type="file" class="bp-form-control" name="file" accept=".csv,.xlsx,.xls"
                                required>
                            <div class="fs-11 text-muted mt-1">Accepted formats: CSV, XLSX, XLS (max 5MB)</div>
                        </div>
                        <div class="bp-card bg-light">
                            <div class="bp-card-body py-2 px-3">
                                <div class="fw-600 fs-12 mb-1">Required Columns:</div>
                                <code class="fs-11">company_name, contact_person, phone</code>
                                <div class="fw-600 fs-12 mt-2 mb-1">Optional Columns:</div>
                                <code class="fs-11">email, division, district, area, address, opening_balance,
                                    credit_limit, payment_terms, supplier_group</code>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="bp-btn bp-btn-danger" data-bs-dismiss="modal"><i
                                class="fa-solid fa-xmark me-1"></i>Cancel</button>
                        <button type="submit" class="bp-btn bp-btn-primary"><i class="fa-solid fa-file-import me-1"></i>
                            Import</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        'use strict';

        $(function() {
            // Reset filters
            $('.bp-filter-reset').on('click', function() {
                // Clear all filters — go to the clean path (no query string).
                window.location.href = window.location.pathname;
            });

            // Select all checkbox
            $('.bp-check-all').on('change', function() {
                var checked = $(this).prop('checked');
                $('.row-checkbox').prop('checked', checked);
                toggleBulkActions();
            });

            // Individual row checkbox
            $(document).on('change', '.row-checkbox', function() {
                var total = $('.row-checkbox').length;
                var checked = $('.row-checkbox:checked').length;
                $('.bp-check-all').prop('checked', total === checked);
                toggleBulkActions();
            });

            function toggleBulkActions() {
                var count = $('.row-checkbox:checked').length;
                var $bar = $('#bulkActionsBar');
                if (count > 0) {
                    $bar.removeAttr('hidden');
                    $bar.find('.count').text(count);
                } else {
                    $bar.attr('hidden', '');
                }
            }

            // Delete confirmation is handled globally by .delete-confirm in app.js
        });
    </script>
@endpush
