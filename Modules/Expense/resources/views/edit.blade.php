@extends('core::layouts.master')

@section('title', 'Edit Expense — ' . $expense->expense_number)
@section('page-title', __('Edit Expense'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('expenses.index') }}">Expenses</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('expenses.show', $expense) }}">{{ $expense->expense_number }}</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Edit</span>
@endsection

@section('page-actions')
    <a href="{{ route('expenses.show', $expense) }}" class="bp-btn bp-btn-primary">
        <i class="fa-solid fa-arrow-left me-1"></i> Back
    </a>
@endsection

@section('content')

    <form action="{{ route('expenses.update', $expense) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row g-4">
            <div class="col-xl-8">
                <div class="bp-card">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-pen me-2"></i>Expense Details</h5>
                    </div>
                    <div class="bp-card-body">
                        <div class="row g-3">

                            <div class="col-md-6">
                                <label class="bp-form-label">Date *</label>
                                <input type="date" class="bp-form-control @error('expense_date') is-invalid @enderror"
                                    name="expense_date"
                                    value="{{ old('expense_date', $expense->expense_date->format('Y-m-d')) }}" required>
                                @error('expense_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="bp-form-label">Category *</label>
                                <select class="bp-form-select w-100 @error('expense_category_id') is-invalid @enderror"
                                    name="expense_category_id" required>
                                    <option value="">Select Category</option>
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat->id }}"
                                            {{ old('expense_category_id', $expense->expense_category_id) == $cat->id ? 'selected' : '' }}>
                                            {{ ($cat->depth ?? 0) > 0 ? '— ' : '' }}{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                                @error('expense_category_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="bp-form-label">Amount ({{ currency_symbol() }}) *</label>
                                <input type="number" class="bp-form-control @error('amount') is-invalid @enderror"
                                    name="amount" id="expAmount" value="{{ old('amount', num_input($expense->amount)) }}"
                                    min="0.01" step="0.01" required>
                                @error('amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="bp-form-label">Tax Amount</label>
                                <input type="number" class="bp-form-control" name="tax_amount" id="expTax"
                                    value="{{ old('tax_amount', num_input($expense->tax_amount)) }}" min="0" step="0.01">
                            </div>

                            <div class="col-md-6 d-none">
                                <label class="bp-form-label">Branch</label>
                                <select class="bp-form-select w-100" name="branch_id">
                                    @foreach ($branches as $br)
                                        <option value="{{ $br->id }}"
                                            {{ old('branch_id', $expense->branch_id) == $br->id ? 'selected' : '' }}>
                                            {{ $br->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="bp-form-label">Reference</label>
                                <input type="text" class="bp-form-control" name="reference"
                                    value="{{ old('reference', $expense->reference) }}"
                                    placeholder="Bill / voucher number">
                            </div>

                            <div class="col-md-6">
                                @include('expense::partials.receipt-field', [
                                    'existingPath' => $expense->receipt_path,
                                    'showPreview' => true,
                                ])
                            </div>

                            <div class="col-12">
                                <label class="bp-form-label">Description</label>
                                <textarea class="bp-form-control @error('description') is-invalid @enderror" name="description" rows="2">{{ old('description', $expense->description) }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Payment Method -->
                <div class="bp-card mt-4">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-bangladeshi-taka-sign me-2"></i>Payment Method</h5>
                    </div>
                    <div class="bp-card-body">
                        <input type="hidden" name="payment_account_id" id="expPayAccountId"
                            value="{{ old('payment_account_id', $expense->payment_account_id) }}">
                        <label class="bp-form-label">Payment Account *</label>
                        <select class="bp-form-select w-100 @error('payment_account_id') is-invalid @enderror"
                            onchange="document.getElementById('expPayAccountId').value=this.value" required>
                            <option value="">Select Payment Account</option>
                            @foreach ($paymentAccounts as $pa)
                                <option value="{{ $pa->id }}"
                                    {{ old('payment_account_id', $expense->payment_account_id) == $pa->id ? 'selected' : '' }}>
                                    {{ $pa->name }} ({{ ucfirst(str_replace('_', ' ', $pa->account_type)) }})</option>
                            @endforeach
                        </select>
                        @error('payment_account_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="fs-11 text-muted mt-1">This sets the default payment method. Split payments are
                            available after approval.</div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="mt-4 d-flex justify-content-end gap-2">
                    <a href="{{ route('expenses.show', $expense) }}" class="bp-btn bp-btn-danger"><i
                            class="fa-solid fa-xmark me-1"></i>Cancel</a>
                    <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-save me-1"></i> Update
                        Expense</button>
                </div>
            </div>

            <!-- Right Column: Summary -->
            <div class="col-xl-4">
                <div class="bp-card">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-calculator me-2"></i>Summary</h5>
                    </div>
                    <div class="bp-card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Expense Number</span>
                            <span class="fw-700">{{ $expense->expense_number }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Status</span>
                            <span class="bp-badge bp-badge-warning">{{ ucfirst($expense->status) }}</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Amount</span>
                            <span class="text-muted" id="summaryAmount">{{ currency_symbol() }}
                                {{ number_format($expense->amount, 0) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Tax</span>
                            <span id="summaryTax">{{ currency_symbol() }}
                                {{ number_format($expense->tax_amount, 0) }}</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <span class="fw-800">Total</span>
                            <span class="fw-800 fs-14" id="summaryTotal">{{ currency_symbol() }}
                                {{ number_format($expense->total_amount, 0) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

@endsection

@push('scripts')
    <script>
        'use strict';
        $(function() {
            function recalcSummary() {
                var amt = parseFloat($('#expAmount').val()) || 0;
                var tax = parseFloat($('#expTax').val()) || 0;
                var total = amt + tax;
                $('#summaryAmount').text('{{ currency_symbol() }} ' + Math.round(amt).toLocaleString('en-IN'));
                $('#summaryTax').text('{{ currency_symbol() }} ' + Math.round(tax).toLocaleString('en-IN'));
                $('#summaryTotal').text('{{ currency_symbol() }} ' + Math.round(total).toLocaleString('en-IN'));
            }
            $('#expAmount, #expTax').on('input', recalcSummary);
        });
    </script>
@endpush
