@extends('core::layouts.master')

@section('title', __('Payment Accounts'))
@section('page-title', __('Payment Accounts'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Finance</span>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Payment Accounts</span>
@endsection

@section('page-actions')
    <a href="{{ route('payment-accounts.transfers') }}" class="bp-btn bp-btn-info"><i
            class="fa-solid fa-arrows-left-right me-1"></i> Transfers</a>
    <a href="{{ route('payment-accounts.banks') }}" class="bp-btn bp-btn-warning"><i
            class="fa-solid fa-building-columns me-1"></i> Banks</a>
    @bpCan('payments.create')
        <a href="{{ route('payment-accounts.create') }}" class="bp-btn bp-btn-primary"><i class="fa-solid fa-plus me-1"></i> Add
            Account</a>
    @endbpCan
@endsection

@section('content')

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-xl col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-wallet"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Accounts</div>
                    <div class="bp-stat-value">{{ $stats['total'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-success"><i class="fa-solid fa-money-bill-wave"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Cash</div>
                    <div class="bp-stat-value">{{ $stats['cash'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-info"><i class="fa-solid fa-mobile-screen"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Mobile Banking</div>
                    <div class="bp-stat-value">{{ $stats['mobile_banking'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-warning"><i class="fa-solid fa-building-columns"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Bank</div>
                    <div class="bp-stat-value">{{ $stats['bank'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl col-sm-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-accent"><i class="fa-solid fa-credit-card"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Card</div>
                    <div class="bp-stat-value">{{ $stats['card'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <x-core::table>
        <x-slot:filters>
            <x-core::table.filter-bar searchPlaceholder="Search account name, number...">
                <select class="bp-form-select" name="account_type">
                    <option value="">All Types</option>
                    <option value="cash" {{ request('account_type') === 'cash' ? 'selected' : '' }}>Cash</option>
                    <option value="mobile_banking" {{ request('account_type') === 'mobile_banking' ? 'selected' : '' }}>
                        Mobile Banking</option>
                    <option value="bank" {{ request('account_type') === 'bank' ? 'selected' : '' }}>Bank</option>
                    <option value="card" {{ request('account_type') === 'card' ? 'selected' : '' }}>Card</option>
                </select>
                <select class="bp-form-select" name="status">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </x-core::table.filter-bar>
        </x-slot:filters>

        <x-core::table.header>
            <x-core::table.column>Account Name</x-core::table.column>
            <x-core::table.column>Type</x-core::table.column>
            <x-core::table.column>Details</x-core::table.column>
            <x-core::table.column>Balance</x-core::table.column>
            <x-core::table.column>Service Charge</x-core::table.column>
            <x-core::table.column>Status</x-core::table.column>
            <x-core::table.column>Actions</x-core::table.column>
        </x-core::table.header>

        <tbody>
            @forelse($accounts as $account)
                <tr>
                    <td>
                        <div class="fw-700 fs-13">{{ $account->name }}</div>
                        @if ($account->is_default)
                            <span class="bp-badge bp-badge-primary fs-10">Default</span>
                        @endif
                    </td>
                    <td><span
                            class="bp-badge bp-badge-{{ match ($account->account_type) {'cash' => 'success','mobile_banking' => 'info','bank' => 'warning','card' => 'primary',default => 'secondary'} }}">{{ $account->account_type_label }}</span>
                    </td>
                    <td class="fs-12">
                        @if ($account->account_type === 'mobile_banking')
                            {{ $account->mobile_bank_name }} · {{ $account->mobile_number }}
                        @elseif($account->account_type === 'bank')
                            {{ $account->bank?->name }} · {{ $account->bank_account_number }}
                        @elseif($account->account_type === 'card')
                            {{ $account->card_type }} · {{ $account->card_holder_name }}
                        @endif
                    </td>
                    <td class="fw-700">{{ money($account->currentBalance()) }}</td>
                    <td>{{ $account->service_charge > 0 ? $account->service_charge . '%' : '' }}</td>
                    <td>
                        <x-core::status-toggle :url="route('payment-accounts.toggle-status', $account->id)" :active="$account->is_active" />
                    </td>
                    <td>
                        @bpCanAny('payments.view', 'payments.create', 'payments.delete')
                            <div class="dropdown">
                                <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i
                                        class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    @bpCan('payments.view')
                                        <li><a class="dropdown-item" href="{{ route('payment-accounts.ledger', $account) }}"><i
                                                    class="fa-solid fa-list me-2"></i>View Ledger</a></li>
                                    @endbpCan
                                    @bpCan('payments.create')
                                        <li><button type="button" class="dropdown-item bank-charge-btn"
                                                data-account-id="{{ $account->id }}" data-account-name="{{ $account->name }}"
                                                data-charge-url="{{ route('payment-accounts.charge', $account) }}"><i
                                                    class="fa-solid fa-money-bill-transfer me-2"></i>Bank Charge</button></li>
                                        <li><a class="dropdown-item" href="{{ route('payment-accounts.edit', $account) }}"><i
                                                    class="fa-solid fa-pen me-2"></i>Edit</a></li>
                                    @endbpCan
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    @bpCan('payments.delete')
                                        <li>
                                            <form action="{{ route('payment-accounts.destroy', $account) }}" method="POST">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger delete-confirm"
                                                    data-name="{{ $account->name }}"><i
                                                        class="fa-solid fa-trash me-2"></i>Delete</button>
                                            </form>
                                        </li>
                                    @endbpCan
                                </ul>
                            </div>
                        @endbpCanAny
                    </td>
                </tr>
            @empty
                <x-core::table.empty :colspan="7" icon="fa-solid fa-wallet" title="No payment accounts found" />
            @endforelse
        </tbody>

        @if ($accounts->total() > 0)
            {{-- Calculation row: sums the balance of EVERY account matching the
                 active filters (all pages), not just the rows on screen. --}}
            <tfoot>
                <tr class="bp-table-total-row">
                    <td colspan="3" class="fw-800">
                        {{ __('Total Balance') }}
                        <span class="fs-11 text-muted">({{ number_format($accounts->total()) }}
                            {{ trans_choice('account|accounts', $accounts->total()) }})</span>
                    </td>
                    <td class="fw-800">{{ money($totalBalance) }}</td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
        @endif

        <x-slot:pagination>
            <x-core::table.pagination :paginator="$accounts" itemLabel="accounts" />
        </x-slot:pagination>
    </x-core::table>

    @bpCan('payments.create')
        <!-- Bank Charge Modal (shared — the row button fills account name + action URL) -->
        <div class="modal fade" id="bankChargeModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" id="bankChargeForm" action="">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Bank Charge — <span id="bankChargeAccountName"></span></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="bp-form-label">Amount ({{ currency_symbol() }}) *</label>
                                    <input type="number" class="bp-form-control" name="amount" min="0.01"
                                        step="0.01" placeholder="0.00" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="bp-form-label">Date *</label>
                                    <input type="date" class="bp-form-control" name="date"
                                        value="{{ date('Y-m-d') }}" required>
                                </div>
                                <div class="col-12">
                                    <label class="bp-form-label">Note</label>
                                    <input type="text" class="bp-form-control" name="note" maxlength="500"
                                        placeholder="e.g. Monthly maintenance fee / SMS charge">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="bp-btn bp-btn-danger" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-save me-1"></i> Record
                                Charge</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endbpCan

@endsection

@push('scripts')
    <script>
        'use strict';
        $(function() {
            var modalEl = document.getElementById('bankChargeModal');
            if (!modalEl) return; // user lacks payments.create — no modal rendered
            var chargeModal = new bootstrap.Modal(modalEl);
            $(document).on('click', '.bank-charge-btn', function() {
                $('#bankChargeForm').attr('action', $(this).data('charge-url'));
                $('#bankChargeAccountName').text($(this).data('account-name'));
                chargeModal.show();
            });
        });
    </script>
@endpush
