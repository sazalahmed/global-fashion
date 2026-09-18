@extends('core::layouts.master')

@section('title', __("Edit Payment"))
@section('page-title', __("Edit Payment"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('payments.index') }}">Payments</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Edit Payment</span>
@endsection

@section('page-actions')
<a href="{{ route('payments.index') }}" class="bp-btn bp-btn-primary">
  <i class="fa-solid fa-arrow-left me-1"></i> Back to Payments
</a>
@endsection

@section('content')

{{-- Status Badge & Warning --}}
<div class="bp-card mb-4">
  <div class="bp-card-body d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-3">
      <span class="fw-700 fs-13">Payment Status:</span>
      @if($payment->status === 'completed')
        <span class="bp-badge bp-badge-success">Completed</span>
      @elseif($payment->status === 'pending')
        <span class="bp-badge bp-badge-warning">Pending</span>
      @elseif($payment->status === 'voided')
        <span class="bp-badge bp-badge-danger">Voided</span>
      @else
        <span class="bp-badge bp-badge-dark">{{ ucfirst($payment->status ?? 'draft') }}</span>
      @endif
    </div>
    <div class="fs-12 text-muted fw-600">
      <i class="fa-solid fa-circle-info me-1"></i> Payment Reference: {{ $payment->payment_number }}
    </div>
  </div>
</div>

{{-- Completed Payment Warning --}}
<div class="bp-card mb-4" id="completedWarning">
  <div class="bp-card-body" style="background-color: var(--bp-warning-bg, #fef3e2); border-left: 4px solid var(--bp-warning);">
    <div class="d-flex align-items-center gap-2">
      <i class="fa-solid fa-triangle-exclamation" style="color: var(--bp-warning);"></i>
      <span class="fw-700 fs-13" style="color: var(--bp-warning);">This payment is marked as Completed.</span>
    </div>
    <p class="mb-0 mt-1 fs-12 text-muted">Editing a completed payment may affect accounting records and invoice balances. Proceed with caution.</p>
  </div>
</div>

<form action="{{ route('payments.update', $payment) }}" method="POST" id="editPaymentForm">
  @csrf
  @method('PUT')

  <!-- Payment Direction & Party -->
  <div class="bp-card mb-4">
    <div class="bp-card-header">
      <h5 class="bp-card-title"><i class="fa-solid fa-bangladeshi-taka-sign me-2"></i>Payment Information</h5>
    </div>
    <div class="bp-card-body">
      <div class="row g-3">

        {{-- Payment Direction Toggle --}}
        <div class="col-12">
          <label class="bp-form-label">Payment Direction *</label>
          <div class="d-flex gap-2 mt-1">
            <input type="radio" class="btn-check" name="direction" id="dirReceive" value="receive" {{ old('direction', $payment->direction) === 'receive' ? 'checked' : '' }}>
            <label class="bp-btn bp-btn-outline" for="dirReceive">
              <i class="fa-solid fa-arrow-down me-1"></i> Receive Payment
            </label>
            <input type="radio" class="btn-check" name="direction" id="dirPay" value="pay" {{ old('direction', $payment->direction) === 'pay' ? 'checked' : '' }}>
            <label class="bp-btn bp-btn-outline" for="dirPay">
              <i class="fa-solid fa-arrow-up me-1"></i> Make Payment
            </label>
          </div>
          @error('direction')
            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
          @enderror
        </div>

        {{-- Party Type --}}
        <div class="col-md-6">
          <label class="bp-form-label">Party Type *</label>
          <select class="bp-form-select w-100" name="party_type" id="paymentPartyType" required>
            <option value="">Select Party Type</option>
            <option value="customer" {{ old('party_type', $payment->party_type) === 'customer' ? 'selected' : '' }}>Customer</option>
            <option value="supplier" {{ old('party_type', $payment->party_type) === 'supplier' ? 'selected' : '' }}>Supplier</option>
            <option value="employee" {{ old('party_type', $payment->party_type) === 'employee' ? 'selected' : '' }}>Employee</option>
          </select>
          @error('party_type')
            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
          @enderror
        </div>

        {{-- Party Name --}}
        <div class="col-md-6">
          <label class="bp-form-label">Party Name *</label>
          <select class="bp-form-select w-100" name="party_id" id="paymentPartyName" required>
            <option value="">Select Party</option>
            @if($payment->party_id)
              @php $currentParty = $payment->party(); @endphp
              <option value="{{ $payment->party_id }}" selected>{{ $currentParty ? ($currentParty->company_name ?? $currentParty->name) : 'Party #' . $payment->party_id }}</option>
            @endif
          </select>
          @error('party_id')
            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
          @enderror
        </div>

        {{-- Payment Type --}}
        <div class="col-12">
          <label class="bp-form-label">Payment Type *</label>
          <div class="d-flex gap-3 mt-1">
            <div class="form-check">
              <input class="form-check-input" type="radio" name="payment_type" id="typeInvoice" value="against_invoice" {{ old('payment_type', $payment->payment_type) === 'against_invoice' ? 'checked' : '' }}>
              <label class="form-check-label fw-600 fs-13" for="typeInvoice">Against Invoice / PO</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="payment_type" id="typeAdvance" value="advance_payment" {{ old('payment_type', $payment->payment_type) === 'advance_payment' ? 'checked' : '' }}>
              <label class="form-check-label fw-600 fs-13" for="typeAdvance">Advance Payment</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="payment_type" id="typeReturn" value="advance_return" {{ old('payment_type', $payment->payment_type) === 'advance_return' ? 'checked' : '' }}>
              <label class="form-check-label fw-600 fs-13" for="typeReturn">Advance Return</label>
            </div>
          </div>
          @error('payment_type')
            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
          @enderror
        </div>

      </div>
    </div>
  </div>

  {{-- Outstanding Invoices / POs (shown when "Against Invoice/PO" selected) --}}
  <div class="bp-card mb-4" id="outstandingSection">
    <div class="bp-card-header">
      <h5 class="bp-card-title"><i class="fa-solid fa-file-invoice me-2"></i>Outstanding Invoices / POs</h5>
    </div>
    <div class="bp-card-body p-0">
      <div class="bp-table-wrapper" style="max-height: 300px; overflow-y: auto;">
        <table class="bp-table">
          <thead>
            <tr>
              <th class="bp-th-checkbox"><input type="checkbox" class="bp-check-all" title="Select All"></th>
              <th>Reference</th>
              <th>Date</th>
              <th>Total</th>
              <th>Due</th>
              <th>Paying</th>
            </tr>
          </thead>
          <tbody id="outstandingList">
            <!-- Loaded via AJAX -->
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Payment Details -->
  <div class="bp-card mb-4">
    <div class="bp-card-header">
      <h5 class="bp-card-title"><i class="fa-solid fa-credit-card me-2"></i>Payment Details</h5>
    </div>
    <div class="bp-card-body">
      <div class="row g-3">

        {{-- Amount --}}
        <div class="col-md-6">
          <label class="bp-form-label">Amount ({{ currency_symbol() }}) *</label>
          <input type="number" class="bp-form-control" name="amount" required placeholder="0.00" step="0.01" value="{{ old('amount', num_input($payment->amount)) }}">
          @error('amount')
            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
          @enderror
        </div>

        {{-- Payment Method --}}
        <div class="col-md-6">
          <label class="bp-form-label">Payment Method *</label>
          <select class="bp-form-select w-100" name="payment_account_id" required>
            <option value="">Select Payment Method</option>
            <x-payment::account-options :selected="old('payment_account_id', $payment->payment_account_id)" />
          </select>
          @error('payment_method')
            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
          @enderror
        </div>

        {{-- Reference / Transaction ID --}}
        <div class="col-md-6">
          <label class="bp-form-label">Reference / Transaction ID</label>
          <input type="text" class="bp-form-control" name="reference" placeholder="e.g. TXN-12345 or bKash TrxID" value="{{ old('reference', $payment->reference) }}">
          @error('reference')
            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
          @enderror
        </div>

        {{-- Date --}}
        <div class="col-md-6">
          <label class="bp-form-label">Date *</label>
          <input type="date" class="bp-form-control" name="payment_date" value="{{ old('payment_date', $payment->payment_date?->format('Y-m-d')) }}" required>
          @error('payment_date')
            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
          @enderror
        </div>

        {{-- Note --}}
        <div class="col-12">
          <label class="bp-form-label">Note</label>
          <textarea class="bp-form-control" name="note" rows="3" placeholder="Optional note about this payment...">{{ old('note', $payment->note) }}</textarea>
          @error('note')
            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
          @enderror
        </div>

      </div>
    </div>
  </div>

  <!-- Form Actions -->
  <div class="d-flex justify-content-end gap-2 mt-3">
    <a href="{{ route('payments.index') }}" class="bp-btn bp-btn-danger"><i class="fa-solid fa-xmark me-1"></i>Cancel</a>
    <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-save me-1"></i> Update Payment</button>
  </div>

</form>

@endsection

@push('scripts')
<script>
'use strict';

$(document).ready(function() {

    // Toggle outstanding section based on payment type
    $('input[name="payment_type"]').on('change', function() {
        if ($(this).val() === 'against_invoice') {
            $('#outstandingSection').removeClass('d-none');
        } else {
            $('#outstandingSection').addClass('d-none');
        }
    });

    // Initialize visibility on page load
    if ($('input[name="payment_type"]:checked').val() !== 'against_invoice') {
        $('#outstandingSection').addClass('d-none');
    }

    // Select all checkbox
    $('.bp-check-all').on('change', function () {
        var isChecked = $(this).prop('checked');
        $('#outstandingList .row-checkbox').prop('checked', isChecked);
    });

    // Swap party name options based on party type
    $('#paymentPartyType').on('change', function() {
        var type = $(this).val();
        var $select = $('#paymentPartyName');
        $select.html('<option value="">Select Party</option>');

        if (!type) return;

        $.get('{{ route("payments.party-search") }}', { type: type, q: '' }, function(data) {
            $.each(data, function(i, item) {
                var $opt = $('<option>').val(item.id);
                var label = item.name;
                if (item.phone) {
                    label += ' (' + item.phone + ')';
                }
                $opt.text(label);
                $select.append($opt);
            });
        });
    });

    // Load outstanding invoices when party changes
    $('#paymentPartyName').on('change', function() {
        loadOutstandingInvoices();
    });

    function loadOutstandingInvoices() {
        var partyType = $('#paymentPartyType').val();
        var partyId = $('#paymentPartyName').val();
        var $tbody = $('#outstandingList');
        $tbody.empty();

        if (!partyType || !partyId || $('input[name="payment_type"]:checked').val() !== 'against_invoice') return;

        $.get('{{ route("payments.outstanding-invoices") }}', { party_type: partyType, party_id: partyId }, function(data) {
            if (data.length === 0) {
                $tbody.html('<tr><td colspan="6" class="text-center py-3 text-muted">No outstanding invoices found.</td></tr>');
                return;
            }
            $.each(data, function(i, inv) {
                var $ref = $('<span>').text(inv.invoice_number || inv.po_number || 'INV-' + inv.id);
                var date = inv.created_at ? new Date(inv.created_at).toLocaleDateString('en-GB', {day: '2-digit', month: 'short', year: 'numeric'}) : '--';
                var allocType = (partyType === 'customer' ? 'Modules\\\\Sale\\\\Models\\\\Sale' : 'Modules\\\\Purchase\\\\Models\\\\Purchase');
                $tbody.append(
                    '<tr>' +
                    '<td><input type="checkbox" class="form-check-input row-checkbox" name="allocations[' + i + '][allocatable_id]" value="' + inv.id + '"></td>' +
                    '<td class="fw-600">' + $ref.html() + '</td>' +
                    '<td>' + date + '</td>' +
                    '<td>{{ currency_symbol() }} ' + Number(inv.grand_total).toLocaleString() + '</td>' +
                    '<td class="text-danger fw-700">{{ currency_symbol() }} ' + Number(inv.due_amount).toLocaleString() + '</td>' +
                    '<td><input type="number" class="bp-form-control paying-amount" name="allocations[' + i + '][amount]" placeholder="0" step="0.01" max="' + inv.due_amount + '">' +
                    '<input type="hidden" name="allocations[' + i + '][allocatable_type]" value="' + allocType + '"></td>' +
                    '</tr>'
                );
            });
        });
    }

    // Load outstanding invoices on page load if editing an against_invoice payment
    if ($('input[name="payment_type"]:checked').val() === 'against_invoice' && $('#paymentPartyName').val()) {
        loadOutstandingInvoices();
    }

    // Direction toggle visual state
    $('input[name="direction"]').on('change', function() {
        $('label[for="dirReceive"], label[for="dirPay"]').removeClass('bp-btn-primary').addClass('bp-btn-outline');
        $('label[for="' + $(this).attr('id') + '"]').removeClass('bp-btn-outline').addClass('bp-btn-primary');
    });

    // Initialize direction visual state on page load
    var checkedDir = $('input[name="direction"]:checked').attr('id');
    if (checkedDir) {
        $('label[for="' + checkedDir + '"]').removeClass('bp-btn-outline').addClass('bp-btn-primary');
    }

});
</script>
@endpush
