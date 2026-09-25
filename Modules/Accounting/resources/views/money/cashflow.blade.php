@extends('core::layouts.master')

@section('title', 'Cash Flow')
@section('page-title', __('money.cashflow_title'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>{{ __('money.menu') }}</span>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>{{ __('money.cashflow_title') }}</span>
@endsection

@section('content')

    <div class="bp-card">
        <div class="bp-card-header">
            <h5 class="bp-card-title mb-0">
                <i class="fa-solid fa-money-bill-transfer me-2"></i>{{ __('money.cashflow_title') }}
                @if ($hasDateFilter)
                    <span class="bp-badge bp-badge-secondary ms-2">{{ __('Filtered') }}</span>
                @else
                    <span class="bp-badge bp-badge-info ms-2">{{ __('All Time') }}</span>
                @endif
            </h5>
            <form method="GET" class="cashflow_filter bp-filter-bar">
                <label class="bp-form-label mb-0 fs-12">{{ __('money.from') }}</label>
                <input type="date" class="bp-form-control bp-form-control-sm" name="date_from"
                    value="{{ $from }}">
                <label class="bp-form-label mb-0 fs-12">{{ __('money.to') }}</label>
                <input type="date" class="bp-form-control bp-form-control-sm" name="date_to" value="{{ $to }}">
                <button type="submit" class="bp-btn bp-btn-sm bp-btn-primary filtar_btn" title="Apply Filters"><i
                        class="fa-solid fa-filter"></i></button>
                <a href="{{ route('money.cashflow') }}" class="bp-btn bp-btn-sm bp-btn-danger filtar_btn"
                    title="Reset Filters"><i class="fa-solid fa-rotate"></i></a>
            </form>
        </div>
        <div class="bp-card-body p-0">
            <div class="bp-table-wrapper">
                <div class="table-responsive">
                    <table class="bp-table bp-cashflow-table">
                        <thead>
                            <tr>
                                <th colspan="2" class="text-center bp-cf-header-in">
                                    <i class="fa-solid fa-arrow-down me-1"></i>{{ __('Cash In') }}
                                </th>
                                <th colspan="2" class="text-center bp-cf-header-out">
                                    <i class="fa-solid fa-arrow-up me-1"></i>{{ __('Cash Out') }}
                                </th>
                            </tr>
                            <tr>
                                <th>{{ __('money.description') }}</th>
                                <th>{{ __('money.amount') }}</th>
                                <th>{{ __('money.description') }}</th>
                                <th>{{ __('money.amount') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><i class="fa-solid fa-cart-shopping text-success me-2"></i>Product Sale</td>
                                <td class="fw-600">{{ money($data['product_sale']) }}</td>
                                <td><i class="fa-solid fa-rotate-left text-danger me-2"></i>Sale Return</td>
                                <td class="fw-600">{{ money($data['sale_return']) }}</td>
                            </tr>
                            <tr>
                                <td><i class="fa-solid fa-money-bill-trend-up text-success me-2"></i>Customer Due Receive
                                </td>
                                <td class="fw-600">{{ money($data['customer_due_receive']) }}</td>
                                <td><i class="fa-solid fa-money-bill-transfer text-danger me-2"></i>Customer Advance Refund
                                </td>
                                <td class="fw-600">{{ money($data['customer_adv_refund']) }}</td>
                            </tr>
                            <tr>
                                <td><i class="fa-solid fa-hand-holding-dollar text-success me-2"></i>Customer Advance</td>
                                <td class="fw-600">{{ money($data['customer_advance']) }}</td>
                                <td><i class="fa-solid fa-truck-field text-danger me-2"></i>Supplier Payment</td>
                                <td class="fw-600">{{ money($data['supplier_due_pay']) }}</td>
                            </tr>
                            <tr>
                                <td><i class="fa-solid fa-arrow-rotate-left text-success me-2"></i>Supplier Advance Refund
                                </td>
                                <td class="fw-600">{{ money($data['supplier_adv_refund']) }}</td>
                                <td><i class="fa-solid fa-hand-holding-dollar text-danger me-2"></i>Supplier Advance</td>
                                <td class="fw-600">{{ money($data['supplier_advance']) }}</td>
                            </tr>
                            <tr>
                                <td><i class="fa-solid fa-box-open text-success me-2"></i>Purchase Return</td>
                                <td class="fw-600">{{ money($data['purchase_return']) }}</td>
                                <td><i class="fa-solid fa-money-bill-wave text-danger me-2"></i>Expense</td>
                                <td class="fw-600">{{ money($data['expense']) }}
                                </td>
                            </tr>
                            <tr>
                                <td><i class="fa-solid fa-hand-holding-dollar text-success me-2"></i>Investor Capital In
                                </td>
                                <td class="fw-600">{{ money($data['investor_capital']) }}</td>
                                <td><i class="fa-solid fa-user-tie text-danger me-2"></i>Salary</td>
                                <td class="fw-600">{{ money($data['salary']) }}
                                </td>
                            </tr>
                            <tr>
                                <td><i class="fa-solid fa-building-columns text-success me-2"></i>Loan Received (Lender)
                                </td>
                                <td class="fw-600">{{ money($data['loan_received']) }}</td>
                                <td><i class="fa-solid fa-money-bill-transfer text-danger me-2"></i>Investor Capital Out
                                </td>
                                <td class="fw-600">{{ money($data['investor_withdraw']) }}</td>
                            </tr>
                            <tr>
                                <td><i class="fa-solid fa-hand-holding-dollar text-success me-2"></i>Loan Recovered
                                    (Borrower)
                                </td>
                                <td class="fw-600">{{ money($data['loan_recovered']) }}</td>
                                <td><i class="fa-solid fa-percent text-danger me-2"></i>Profit Distribution</td>
                                <td class="fw-600">{{ money($data['investor_dist']) }}</td>
                            </tr>
                            <tr>
                                <td><i class="fa-solid fa-rotate-left text-success me-2"></i>Advance Recovered (Employee)
                                </td>
                                <td class="fw-600">{{ money($data['emp_adv_recovered']) }}</td>
                                <td><i class="fa-solid fa-hand-holding-dollar text-danger me-2"></i>Loan Given (Borrower)
                                </td>
                                <td class="fw-600">{{ money($data['loan_given']) }}</td>
                            </tr>
                            <tr>
                                <td><i class="fa-solid fa-hand-holding-dollar text-success me-2"></i>Loan Taken (Person)
                                </td>
                                <td class="fw-600">{{ money($data['loan_taken']) }}</td>
                                <td><i class="fa-solid fa-building-columns text-danger me-2"></i>Loan Repayment (Lender)
                                </td>
                                <td class="fw-600">{{ money($data['loan_repay']) }}</td>
                            </tr>
                            <tr>
                                <td><i class="fa-solid fa-truck-fast text-success me-2"></i>Courier COD Payout (Gross)
                                </td>
                                <td class="fw-600">{{ money($data['courier_withdrawal']) }}</td>
                                <td><i class="fa-solid fa-truck-fast text-danger me-2"></i>Courier Charges (Delivery)
                                </td>
                                <td class="fw-600">{{ money($data['courier_delivery_charge']) }}</td>
                            </tr>
                            <tr>
                                @if (!is_null($courierReceivable))
                                    {{-- Warning orange, not the green of Cash In or the red
                                         of Cash Out: this money is neither received nor
                                         spent, it is in transit — so it should not read as
                                         either column. --}}
                                    <td class="bp-memo-row"><i
                                            class="fa-solid fa-hourglass-half text-warning me-2"></i>Courier Receivable
                                        <span class="fs-11 text-muted">(held by courier — not yet received)</span>
                                    </td>
                                    <td class="fw-600 bp-memo-row text-warning">{{ money($courierReceivable) }}</td>
                                @else
                                    <td></td>
                                    <td></td>
                                @endif
                                <td><i class="fa-solid fa-hand-holding-dollar text-danger me-2"></i>Courier Charges (COD)
                                </td>
                                <td class="fw-600">{{ money($data['courier_cod_charge']) }}</td>
                            </tr>
                            <tr>
                                <td><i class="fa-solid fa-rotate-left text-success me-2"></i>Salary Refund (Payment
                                    Undone)
                                </td>
                                <td class="fw-600">{{ money($data['salary_refund']) }}</td>
                                <td><i class="fa-solid fa-hand-holding-dollar text-danger me-2"></i>Loan Paid Back
                                    (Person)
                                </td>
                                <td class="fw-600">{{ money($data['loan_returned']) }}</td>
                            </tr>
                            <tr>
                                <td>
                                    @if ($data['other_in'] > 0)
                                        <i class="fa-solid fa-circle-dollar-to-slot text-success me-2"></i>Other Receipts
                                    @endif
                                </td>
                                <td class="fw-600">{{ $data['other_in'] > 0 ? money($data['other_in']) : '' }}</td>
                                <td><i class="fa-solid fa-money-bill-transfer text-danger me-2"></i>Bank Charges</td>
                                <td class="fw-600">{{ money($data['bank_charge']) }}</td>
                            </tr>
                            <tr>
                                <td><i class="fa-solid fa-building text-success me-2"></i>Asset Sale (Disposal)</td>
                                <td class="fw-600">{{ money($data['asset_disposal']) }}</td>
                                <td><i class="fa-solid fa-hand-holding-dollar text-danger me-2"></i>Salary Advance
                                    (Employee)
                                </td>
                                <td class="fw-600">{{ money($data['emp_advance']) }}</td>
                            </tr>
                            <tr>
                                <td></td>
                                <td></td>
                                <td><i class="fa-solid fa-building text-danger me-2"></i>Asset Purchase</td>
                                <td class="fw-600">{{ money($data['asset_purchase']) }}</td>
                            </tr>
                            @if ($data['other_out'] > 0)
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td><i class="fa-solid fa-circle-dollar-to-slot text-danger me-2"></i>Other Payments
                                    </td>
                                    <td class="fw-600">{{ money($data['other_out']) }}</td>
                                </tr>
                            @endif
                            @if (isset($dueSalary) && $dueSalary > 0)
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td class="bp-memo-row"><i
                                            class="fa-solid fa-user-clock text-warning me-2"></i>Due Salary (Payable)
                                        <span class="fs-11 text-muted">(owed to employees — not yet paid)</span>
                                    </td>
                                    <td class="fw-600 bp-memo-row text-warning">{{ money($dueSalary) }}</td>
                                </tr>
                            @endif
                        </tbody>
                        <tfoot>
                            <tr class="bp-cf-total-row">
                                <td class="text-end fw-800 p-3">Total :</td>
                                <td class="fw-800 p-3">{{ money($totalReceive) }}</td>
                                <td class="text-end fw-800 p-3">Total :</td>
                                <td class="fw-800 text-danger p-3">{{ money($totalPay) }}</td>
                            </tr>
                            @if ($hasDateFilter)
                                <tr class="bp-cf-summary-row">
                                    <td colspan="3" class="fw-800 p-3 text-end">Opening Balance :</td>
                                    <td class="fw-800 p-3">{{ money($openingBalance) }}</td>
                                </tr>
                                <tr class="bp-cf-summary-row">
                                    <td colspan="3" class="fw-800 p-3 text-end">
                                        Period Net = (Cash In − Cash Out)
                                    </td>
                                    <td class="fw-800 p-3">{{ money($totalReceive - $totalPay) }}</td>
                                </tr>
                            @endif
                            <tr class="bp-cf-closing-row">
                                <td colspan="3" class="text-end fw-800 p-3">
                                    {{ $hasDateFilter ? 'Current Balance' : 'Net Balance' }} :
                                </td>
                                <td class="fw-800 p-3">{{ money($currentBalance) }}</td>
                            </tr>
                            <tr>
                                <td colspan="4" class="text-center fs-12 text-mutedp-3 py-2 px-3">
                                    @if ($hasDateFilter)
                                        (Opening Balance + Cash In − Cash Out)
                                    @else
                                        (All-time Cash In − Cash Out)
                                    @endif
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

@endsection
