@extends('core::layouts.master')

@section('title', __('Payroll'))
@section('page-title', __('Payroll Dashboard'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>HR</span>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Payroll</span>
@endsection

@section('page-actions')
    <x-core::export-dropdown module="payroll" />
    @bpCan('hr.create')
    <a href="{{ route('payroll.generate') }}" class="bp-btn bp-btn-primary">
        <i class="fa-solid fa-plus"></i> Generate Payroll
    </a>
    @endbpCan
    <a href="{{ route('payroll.salary-structures') }}" class="bp-btn bp-btn-success">
        <i class="fa-solid fa-sitemap"></i> Salary Structures
    </a>
@endsection

@section('content')

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-bangladeshi-taka-sign"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">This Month Payroll</div>
                    <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($stats['this_month'], 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-success"><i class="fa-solid fa-file-invoice"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Payrolls</div>
                    <div class="bp-stat-value">{{ $stats['total_payrolls'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-warning"><i class="fa-solid fa-clock"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Pending (Draft)</div>
                    <div class="bp-stat-value">{{ $stats['pending'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-bangladeshi-taka-sign"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Paid (All Time)</div>
                    <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($stats['total_paid'], 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payroll Runs Table -->
    <x-core::table :selectable="true">
        <x-slot:filters>
            <x-core::table.filter-bar searchPlaceholder="Search payroll...">
                <input type="month" class="bp-form-control" name="month" value="{{ request('month', now()->format('Y-m')) }}"
                    placeholder="Filter by month">
                <select class="bp-form-select" name="status">
                    <option value="">All Status</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
                <select class="bp-form-select d-none" name="branch_id">
                    <option value="">All Branches</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}"
                            {{ (int) request('branch_id') === $branch->id ? 'selected' : '' }}>{{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            </x-core::table.filter-bar>
        </x-slot:filters>

        <x-slot:bulkActions>
            <x-core::table.bulk-actions>
                @bpCan('hr.edit')
                <button class="bp-btn bp-btn-sm bp-btn-outline" data-bulk-action="status" data-bulk-status="approved"><i
                        class="fa-solid fa-check me-1"></i> Approve</button>
                <button class="bp-btn bp-btn-sm bp-btn-outline" data-bulk-action="status" data-bulk-status="cancelled"><i
                        class="fa-solid fa-ban me-1"></i> Cancel</button>
                @endbpCan
                @bpCan('hr.delete')
                <button class="bp-btn bp-btn-sm bp-btn-danger" data-bulk-action="delete"><i
                        class="fa-solid fa-trash me-1"></i> Delete</button>
                @endbpCan
            </x-core::table.bulk-actions>
        </x-slot:bulkActions>

        <x-core::table.header :selectable="true">
            <x-core::table.column :sortable="true" field="payroll_number">Payroll #</x-core::table.column>
            <x-core::table.column :sortable="true" field="month">Month</x-core::table.column>
            <th class="d-none">Branch</th>
            <x-core::table.column :sortable="true" field="total_employees">Employees</x-core::table.column>
            <x-core::table.column :sortable="true" field="total_gross" align="right">Gross Pay</x-core::table.column>
            <x-core::table.column :sortable="true" field="total_deductions"
                align="right">Deductions</x-core::table.column>
            <x-core::table.column :sortable="true" field="total_net" align="right">Net Pay</x-core::table.column>
            <x-core::table.column>Status</x-core::table.column>
            <x-core::table.column>Actions</x-core::table.column>
        </x-core::table.header>

        <tbody>
            @forelse($payrolls as $payroll)
                <tr>
                    <td><input type="checkbox" class="form-check-input row-checkbox" value="{{ $payroll->id }}"></td>
                    <td class="fw-600 fs-13">{{ $payroll->payroll_number }}</td>
                    <td class="fw-700 fs-13">
                        <i class="fa-solid fa-calendar me-1 text-muted"></i>
                        {{ \Carbon\Carbon::parse($payroll->month . '-01')->format('F Y') }}
                    </td>
                    <td class="fs-13 d-none">{{ $payroll->branch?->name ?? 'All Branches' }}</td>
                    <td class="fw-600">{{ $payroll->total_employees }}</td>
                    <td class="fw-600 text-end">{{ currency_symbol() }} {{ number_format($payroll->total_gross, 0) }}</td>
                    <td class="bp-text-danger fw-600 text-end">{{ currency_symbol() }}
                        {{ number_format($payroll->total_deductions, 0) }}</td>
                    <td class="fw-700 text-end">{{ currency_symbol() }} {{ number_format($payroll->total_net, 0) }}</td>
                    <td>
                        @if ($payroll->status === 'draft')
                            <span class="bp-badge bp-badge-warning">Draft</span>
                        @elseif($payroll->status === 'approved')
                            <span class="bp-badge bp-badge-primary">Approved</span>
                        @elseif($payroll->status === 'paid')
                            <span class="bp-badge bp-badge-success">Paid</span>
                        @elseif($payroll->status === 'cancelled')
                            <span class="bp-badge bp-badge-danger">Cancelled</span>
                        @endif
                    </td>
                    <td>
                        <div class="dropdown">
                            <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="{{ route('payroll.show', $payroll) }}"><i class="fa-solid fa-eye me-2"></i> View</a></li>
                                @bpCan('hr.delete')
                                    {{-- Only draft/cancelled payrolls are deletable (service rule);
                                         approved/paid must be cancelled from the detail page first. --}}
                                    @if(in_array($payroll->status, ['draft', 'cancelled']))
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form action="{{ route('payroll.destroy', $payroll) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger delete-confirm"
                                                    data-name="{{ $payroll->payroll_number }}">
                                                    <i class="fa-solid fa-trash me-2"></i> Delete
                                                </button>
                                            </form>
                                        </li>
                                    @endif
                                @endbpCan
                            </ul>
                        </div>
                    </td>
                </tr>
            @empty
                <x-core::table.empty colspan="10" icon="fa-solid fa-file-invoice" title="No payroll records found" />
            @endforelse
        </tbody>

        <x-slot:pagination>
            <x-core::table.pagination :paginator="$payrolls" itemLabel="payrolls" />
        </x-slot:pagination>
    </x-core::table>

@endsection
