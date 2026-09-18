@extends('core::layouts.master')

@section('title', __('Expenses'))
@section('page-title', __('Expenses'))

@push('styles')
    <link href="{{ asset('vendor/venobox/venobox.min.css') }}" rel="stylesheet">
@endpush

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Expenses</span>
@endsection

@section('page-actions')
    @bpCan('finance.export')
        <x-core::export-dropdown module="expenses" />
    @endbpCan
    @bpCan('finance.create')
        <a href="{{ route('expenses.create') }}" class="bp-btn bp-btn-primary">
            <i class="fa-solid fa-plus"></i> Add Expense
        </a>
    @endbpCan
@endsection

@section('content')

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-calendar-day"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Today</div>
                    <div class="bp-stat-value">{{ money($stats['today']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-warning"><i class="fa-solid fa-calendar-week"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">This Week</div>
                    <div class="bp-stat-value">{{ money($stats['this_week']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-calendar"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">This Month</div>
                    <div class="bp-stat-value">{{ money($stats['this_month']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-info"><i class="fa-solid fa-hourglass-half"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Pending Approval</div>
                    <div class="bp-stat-value">{{ $stats['pending_count'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Expenses Table -->
    <x-core::table :selectable="true">
        <x-slot:filters>
            <x-core::table.filter-bar searchPlaceholder="Search expense...">
                <input type="date" class="bp-form-control bp-filter-date" name="date_from"
                    value="{{ request('date_from') }}">
                <span class="text-muted">to</span>
                <input type="date" class="bp-form-control bp-filter-date" name="date_to"
                    value="{{ request('date_to') }}">
                <select class="bp-form-select" name="category">
                    <option value="">All Categories</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}</option>
                    @endforeach
                </select>
                <select class="bp-form-select" name="status">
                    <option value="">All Status</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
                <select class="bp-form-select" name="payment_account_id">
                    <option value="">All Methods</option>
                    <x-payment::account-options :selected="request('payment_account_id')" />
                </select>
            </x-core::table.filter-bar>
        </x-slot:filters>

        <x-core::table.header>
            <x-core::table.column :sortable="true" field="expense_number">ID</x-core::table.column>
            <x-core::table.column :sortable="true" field="expense_date">Date</x-core::table.column>
            <x-core::table.column>Category</x-core::table.column>
            <x-core::table.column>Description</x-core::table.column>
            <x-core::table.column :sortable="true" field="total_amount">Amount</x-core::table.column>
            <x-core::table.column>Method</x-core::table.column>
            <x-core::table.column>Added By</x-core::table.column>
            <x-core::table.column>Status</x-core::table.column>
            <x-core::table.column>Attachment</x-core::table.column>
            <x-core::table.column>Actions</x-core::table.column>
        </x-core::table.header>

        <tbody>
            @forelse($expenses as $expense)
                @php
                    $statusClass = match ($expense->status) {
                        'approved' => 'info',
                        'paid' => 'success',
                        'rejected' => 'danger',
                        default => 'warning',
                    };
                    $statusLabel = ucfirst($expense->status);
                @endphp
                <tr>
                    <td class="fw-700">{{ $expense->expense_number }}</td>
                    <td>{{ $expense->expense_date->format('d M Y') }}</td>
                    <td><span class="bp-badge bp-badge-primary">{{ $expense->category->name }}</span></td>
                    <td>{{ $expense->description }}</td>
                    <td class="fw-800 text-danger">{{ money($expense->total_amount) }}</td>
                    <td>{{ $expense->payment_method_label }}</td>
                    <td>{{ $expense->creator->name }}</td>
                    <td><span class="bp-badge bp-badge-{{ $statusClass }}">{{ $statusLabel }}</span></td>
                    <td>
                        @if ($expense->receipt_path)
                            @php
                                $isPdf = \Illuminate\Support\Str::endsWith(strtolower($expense->receipt_path), '.pdf');
                                $receiptName = basename($expense->receipt_path);
                            @endphp
                            @if ($isPdf)
                                {{-- A PDF cannot render in the lightbox, so it opens in its own tab. --}}
                                <a href="{{ upload_url($expense->receipt_path) }}" target="_blank" rel="noopener"
                                    class="bp-expense-attach" title="{{ $receiptName }}">
                                    <i class="fa-solid fa-file-pdf"></i>
                                </a>
                            @else
                                <a href="{{ upload_url($expense->receipt_path) }}" class="bp-expense-attach venobox"
                                    data-gall="expense-receipts" title="{{ $receiptName }}">
                                    <img src="{{ upload_url_sm($expense->receipt_path) }}" alt="Receipt"
                                        class="bp-expense-attach-thumb">
                                </a>
                            @endif
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        @bpCanAny('finance.view', 'finance.edit')
                            <div class="dropdown">
                                <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i
                                        class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    @bpCan('finance.view')
                                        <li><a class="dropdown-item" href="{{ route('expenses.show', $expense) }}"><i
                                                    class="fa-solid fa-eye"></i> View</a></li>
                                    @endbpCan
                                    @if ($expense->status === 'pending')
                                        @bpCan('finance.edit')
                                            <li><a class="dropdown-item" href="{{ route('expenses.edit', $expense) }}"><i
                                                        class="fa-solid fa-pen"></i> Edit</a></li>
                                        @endbpCan
                                    @endif
                                    @bpCan('finance.delete')
                                        <li>
                                            <form action="{{ route('expenses.destroy', $expense) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger delete-confirm"
                                                    data-name="{{ $expense->expense_number }}"><i
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
                <x-core::table.empty colspan="10" icon="fa-solid fa-receipt" title="No expenses found." />
            @endforelse
        </tbody>

        @if ($expenses->count())
            <tfoot>
                <tr class="bp-table-total-row">
                    <td colspan="4" class="text-end fw-800">{{ __('Total (filtered)') }}</td>
                    <td class="fw-800 text-danger">{{ money($filteredTotal) }}</td>
                    <td colspan="5"></td>
                </tr>
            </tfoot>
        @endif

        <x-slot:pagination>
            <x-core::table.pagination :paginator="$expenses" itemLabel="expenses" />
        </x-slot:pagination>
    </x-core::table>

@endsection

@push('scripts')
    <script src="{{ asset('vendor/venobox/venobox.min.js') }}"></script>
    <script>
        'use strict';

        $(function() {
            // Receipt images open in the lightbox; PDFs are plain links that
            // carry no .venobox class and so keep their target="_blank".
            if ($.fn.venobox) {
                $('.venobox').venobox();
            }
        });
    </script>
@endpush
