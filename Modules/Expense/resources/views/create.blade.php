@extends('core::layouts.master')

@section('title', __('Add Expense'))
@section('page-title', __('Add Expense'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('expenses.index') }}">Expenses</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Add Expense</span>
@endsection

@section('page-actions')
    <a href="{{ route('expenses.index') }}" class="bp-btn bp-btn-primary">
        <i class="fa-solid fa-arrow-left"></i> Back to Expenses
    </a>
@endsection

@section('content')

    <form action="{{ route('expenses.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="bp-card">
            <div class="bp-card-header">
                <h5 class="bp-card-title"><i class="fa-solid fa-money-bill-wave me-2"></i>Expense Information</h5>
            </div>
            <div class="bp-card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="bp-form-label">Date *</label>
                        <input type="date" class="bp-form-control @error('expense_date') is-invalid @enderror"
                            name="expense_date" value="{{ old('expense_date', date('Y-m-d')) }}" required>
                        @error('expense_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="bp-form-label">Category *</label>
                        <div class="input-group">
                            <select class="bp-form-select select2-search @error('expense_category_id') is-invalid @enderror"
                                name="expense_category_id" id="expenseCategorySelect" required>
                                <option value="">Select Category</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}" data-parent="{{ $cat->parent_id ?? '' }}"
                                        {{ old('expense_category_id') == $cat->id ? 'selected' : '' }}>{{ ($cat->depth ?? 0) > 0 ? '— ' : '' }}{{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="button" class="bp-btn bp-btn-outline" id="addExpenseCategoryBtn"
                                title="Quick-add category" data-bs-toggle="modal" data-bs-target="#quickAddCategoryModal">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </div>
                        @error('expense_category_id')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="bp-form-label">Amount ({{ currency_symbol() }}) *</label>
                        <input type="number" class="bp-form-control @error('amount') is-invalid @enderror" name="amount"
                            value="{{ old('amount') }}" required placeholder="0.00">
                        @error('amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="bp-form-label">Payment Account *</label>
                        <select class="bp-form-select w-100 @error('payment_account_id') is-invalid @enderror"
                            name="payment_account_id" required>
                            <option value="">Select Payment Account</option>
                            @foreach ($paymentAccounts as $acct)
                                <option value="{{ $acct->id }}"
                                    {{ old('payment_account_id') == $acct->id ? 'selected' : '' }}>{{ $acct->name }}
                                    ({{ ucfirst(str_replace('_', ' ', $acct->account_type)) }})</option>
                            @endforeach
                        </select>
                        @error('payment_account_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="bp-form-label">Reference #</label>
                        <input type="text" class="bp-form-control" name="reference" value="{{ old('reference') }}"
                            placeholder="Bill / voucher number">
                    </div>
                    <div class="col-md-6 d-none">
                        <label class="bp-form-label">Branch</label>
                        <select class="bp-form-select w-100 @error('branch_id') is-invalid @enderror" name="branch_id">
                            <option value="">All Branches</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}"
                                    {{ old('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        @error('branch_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        @include('expense::partials.receipt-field')
                    </div>
                    <div class="col-12">
                        <label class="bp-form-label">Description</label>
                        <textarea class="bp-form-control @error('description') is-invalid @enderror" name="description" rows="2"
                            placeholder="What was this expense for?">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            <div class="bp-card-footer text-end">
                <a href="{{ route('expenses.index') }}" class="bp-btn bp-btn-danger me-2"><i
                        class="fa-solid fa-xmark me-1"></i>Cancel</a>
                <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-check me-1"></i> Save
                    Expense</button>
            </div>
        </div>
    </form>

    {{-- Quick-add Category Modal --}}
    <div class="modal fade" id="quickAddCategoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa-solid fa-tag me-2"></i>New Expense Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="bp-alert d-none" id="quickAddCategoryError"></div>
                    <div class="mb-3">
                        <label class="bp-form-label">Name *</label>
                        <input type="text" class="bp-form-control" id="quickCategoryName"
                            placeholder="e.g. Utilities, Rent" autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="bp-form-label">Parent Category (optional)</label>
                        <select class="bp-form-select w-100" id="quickCategoryParent">
                            <option value="">None (top-level)</option>
                            @foreach ($categories->where('parent_id', null) as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="bp-btn bp-btn-danger" data-bs-dismiss="modal"><i
                            class="fa-solid fa-xmark me-1"></i>Cancel</button>
                    <button type="button" class="bp-btn bp-btn-success" id="quickAddCategorySubmit">
                        <i class="fa-solid fa-plus me-1"></i> Create
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        'use strict';
        $(document).ready(function() {
            // Quick-add expense category
            $('#quickAddCategorySubmit').on('click', function() {
                var $btn = $(this);
                var name = $('#quickCategoryName').val().trim();
                var parentId = $('#quickCategoryParent').val();
                var $err = $('#quickAddCategoryError');

                $err.addClass('d-none').text('');
                if (!name) {
                    $err.removeClass('d-none').text('Category name is required.');
                    return;
                }

                $btn.prop('disabled', true).html(
                    '<i class="fa-solid fa-spinner fa-spin me-1"></i> Saving...');

                $.ajax({
                    url: '{{ route('expense-categories.quick-add') }}',
                    method: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        name: name,
                        parent_id: parentId || null,
                    },
                }).done(function(res) {
                    if (res && res.success && res.category) {
                        var $sel = $('#expenseCategorySelect');
                        var label = (parentId ? '— ' : '') + res.category.name;
                        var opt = new Option(label, res.category.id, true, true);
                        opt.setAttribute('data-parent', parentId || '');
                        if (parentId) {
                            // Insert beneath the parent, after its existing children.
                            var $anchor = $sel.find('option[value="' + parentId + '"]');
                            while ($anchor.next('option').attr('data-parent') == parentId) {
                                $anchor = $anchor.next('option');
                            }
                            $anchor.after(opt);
                        } else {
                            $sel.append(opt);
                        }
                        $sel.trigger('change');
                        $('#quickCategoryName').val('');
                        $('#quickCategoryParent').val('');
                        bootstrap.Modal.getInstance(document.getElementById(
                            'quickAddCategoryModal')).hide();
                    }
                }).fail(function(xhr) {
                    var msg = 'Could not create category.';
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        msg = Object.values(xhr.responseJSON.errors).flat().join(' ');
                    }
                    $err.removeClass('d-none').text(msg);
                }).always(function() {
                    $btn.prop('disabled', false).html(
                        '<i class="fa-solid fa-plus me-1"></i> Create');
                });
            });
        });
    </script>
@endpush
