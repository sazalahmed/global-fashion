@extends('core::layouts.master')

@section('title', __("Edit Journal Entry"))
@section('page-title', __("Edit Journal Entry"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('accounting.journal-entries') }}">Journal Entries</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Edit Entry</span>
@endsection

@section('page-actions')
<a href="{{ route('accounting.journal-entries') }}" class="bp-btn bp-btn-primary">
  <i class="fa-solid fa-arrow-left me-1"></i> Back to Journal Entries
</a>
@endsection

@section('content')

<!-- Posted Warning -->
<div class="alert alert-warning d-flex align-items-center mb-4 d-none" id="postedWarning">
  <i class="fa-solid fa-triangle-exclamation me-2 fs-5"></i>
  <div>
    <strong>This journal entry has been posted.</strong> Posted entries cannot be edited. To make changes, you must first reverse this entry and create a new one.
  </div>
</div>

<form action="{{ route('accounting.journal-entries.update', $entry) }}" method="POST" id="journalEntryForm">
  @csrf
  @method('PUT')

  <!-- Entry Header -->
  <div class="bp-card mb-4">
    <div class="bp-card-header d-flex justify-content-between align-items-center">
      <h5 class="bp-card-title"><i class="fa-solid fa-circle-info me-2"></i>Entry Details</h5>
      <span class="bp-badge bp-badge-warning" id="statusBadge">Draft</span>
    </div>
    <div class="bp-card-body">
      <div class="row g-3">
        <div class="col-md-2">
          <label class="bp-form-label">Entry #</label>
          <input type="text" class="bp-form-control" name="entry_no" value="JE-2026-0234" readonly>
        </div>
        <div class="col-md-2">
          <label class="bp-form-label">Date *</label>
          <input type="date" class="bp-form-control entry-field" name="date" value="{{ old('date', '2026-03-07') }}" required>
          @error('date')
            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-md-3">
          <label class="bp-form-label">Reference</label>
          <input type="text" class="bp-form-control entry-field" name="reference" value="{{ old('reference', 'INV-2026-0887') }}" placeholder="e.g. INV-2026-0893">
          @error('reference')
            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-md-5">
          <label class="bp-form-label">Description / Narration *</label>
          <input type="text" class="bp-form-control entry-field" name="description" value="{{ old('description', 'Cash sales revenue from POS Invoice #887') }}" placeholder="e.g. Sales revenue from POS Invoice #893" required>
          @error('description')
            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
          @enderror
        </div>
      </div>
    </div>
  </div>

  <!-- Line Items -->
  <div class="bp-card mb-4">
    <div class="bp-card-header d-flex justify-content-between align-items-center">
      <h5 class="bp-card-title"><i class="fa-solid fa-list me-2"></i>Debit &amp; Credit Lines</h5>
      <button type="button" class="bp-btn bp-btn-sm bp-btn-primary entry-field" id="addRowBtn">
        <i class="fa-solid fa-plus me-1"></i> Add Row
      </button>
    </div>
    <div class="bp-card-body p-0">
      <div class="bp-table-wrapper">
        <table class="bp-table" id="lineItemsTable">
          <thead>
            <tr>
              <th class="text-center" style="width: 40px;">#</th>
              <th style="width: 30%;">Account *</th>
              <th>Line Description</th>
              <th class="text-end" style="width: 160px;">Debit ({{ currency_symbol() }})</th>
              <th class="text-end" style="width: 160px;">Credit ({{ currency_symbol() }})</th>
              <th class="text-center" style="width: 50px;"></th>
            </tr>
          </thead>
          <tbody id="lineItemsBody">
            <tr class="line-item-row" data-row="1">
              <td class="text-center fw-600 row-number">1</td>
              <td>
                <select class="bp-form-select w-100 entry-field" name="lines[0][account]" required>
                  <option value="">Select Account</option>
                  <optgroup label="Assets">
                    <option value="1001" selected>1001 — Cash in Hand</option>
                    <option value="1002">1002 — bKash Account</option>
                    <option value="1003">1003 — Nagad Account</option>
                    <option value="1004">1004 — Bank — DBBL</option>
                    <option value="1005">1005 — Bank — BRAC Bank</option>
                    <option value="1010">1010 — Accounts Receivable</option>
                    <option value="1020">1020 — Inventory</option>
                    <option value="1025">1025 — Advance Tax (AIT)</option>
                    <option value="1500">1500 — Shop Equipment</option>
                    <option value="1510">1510 — Furniture &amp; Fixtures</option>
                  </optgroup>
                  <optgroup label="Liabilities">
                    <option value="2001">2001 — Accounts Payable</option>
                    <option value="2010">2010 — VAT Payable (15%)</option>
                    <option value="2015">2015 — Salary Payable</option>
                    <option value="2500">2500 — Bank Loan — City Bank</option>
                  </optgroup>
                  <optgroup label="Equity">
                    <option value="3001">3001 — Owner's Capital</option>
                    <option value="3010">3010 — Retained Earnings</option>
                    <option value="3020">3020 — Owner's Drawing</option>
                  </optgroup>
                  <optgroup label="Revenue">
                    <option value="4001">4001 — Sales Revenue</option>
                    <option value="4010">4010 — Sales Returns</option>
                    <option value="4020">4020 — Service Income</option>
                    <option value="4030">4030 — Discount Received</option>
                  </optgroup>
                  <optgroup label="Expenses">
                    <option value="5001">5001 — Cost of Goods Sold</option>
                    <option value="5100">5100 — Rent Expense</option>
                    <option value="5110">5110 — Salary Expense</option>
                    <option value="5120">5120 — Utilities Expense</option>
                    <option value="5130">5130 — Marketing Expense</option>
                    <option value="5140">5140 — Courier &amp; Delivery</option>
                    <option value="5150">5150 — Depreciation Expense</option>
                    <option value="5160">5160 — Office Supplies</option>
                    <option value="5170">5170 — Bank Charges</option>
                    <option value="5180">5180 — Discount Allowed</option>
                    <option value="5190">5190 — Interest Expense</option>
                  </optgroup>
                </select>
              </td>
              <td><input type="text" class="bp-form-control entry-field" name="lines[0][description]" value="Cash received from POS sales" placeholder="Line description..."></td>
              <td><input type="number" class="bp-form-control text-end debit-input entry-field" name="lines[0][debit]" value="45500" placeholder="0.00" min="0" step="0.01"></td>
              <td><input type="number" class="bp-form-control text-end credit-input entry-field" name="lines[0][credit]" placeholder="0.00" min="0" step="0.01"></td>
              <td class="text-center"><button type="button" class="bp-btn bp-btn-sm bp-btn-icon bp-btn-danger remove-row entry-field" title="Remove"><i class="fa-solid fa-trash"></i></button></td>
            </tr>
            <tr class="line-item-row" data-row="2">
              <td class="text-center fw-600 row-number">2</td>
              <td>
                <select class="bp-form-select w-100 entry-field" name="lines[1][account]" required>
                  <option value="">Select Account</option>
                  <optgroup label="Assets">
                    <option value="1001">1001 — Cash in Hand</option>
                    <option value="1002">1002 — bKash Account</option>
                    <option value="1003">1003 — Nagad Account</option>
                    <option value="1004">1004 — Bank — DBBL</option>
                    <option value="1005">1005 — Bank — BRAC Bank</option>
                    <option value="1010">1010 — Accounts Receivable</option>
                    <option value="1020">1020 — Inventory</option>
                    <option value="1025">1025 — Advance Tax (AIT)</option>
                    <option value="1500">1500 — Shop Equipment</option>
                    <option value="1510">1510 — Furniture &amp; Fixtures</option>
                  </optgroup>
                  <optgroup label="Liabilities">
                    <option value="2001">2001 — Accounts Payable</option>
                    <option value="2010">2010 — VAT Payable (15%)</option>
                    <option value="2015">2015 — Salary Payable</option>
                    <option value="2500">2500 — Bank Loan — City Bank</option>
                  </optgroup>
                  <optgroup label="Equity">
                    <option value="3001">3001 — Owner's Capital</option>
                    <option value="3010">3010 — Retained Earnings</option>
                    <option value="3020">3020 — Owner's Drawing</option>
                  </optgroup>
                  <optgroup label="Revenue">
                    <option value="4001" selected>4001 — Sales Revenue</option>
                    <option value="4010">4010 — Sales Returns</option>
                    <option value="4020">4020 — Service Income</option>
                    <option value="4030">4030 — Discount Received</option>
                  </optgroup>
                  <optgroup label="Expenses">
                    <option value="5001">5001 — Cost of Goods Sold</option>
                    <option value="5100">5100 — Rent Expense</option>
                    <option value="5110">5110 — Salary Expense</option>
                    <option value="5120">5120 — Utilities Expense</option>
                    <option value="5130">5130 — Marketing Expense</option>
                    <option value="5140">5140 — Courier &amp; Delivery</option>
                    <option value="5150">5150 — Depreciation Expense</option>
                    <option value="5160">5160 — Office Supplies</option>
                    <option value="5170">5170 — Bank Charges</option>
                    <option value="5180">5180 — Discount Allowed</option>
                    <option value="5190">5190 — Interest Expense</option>
                  </optgroup>
                </select>
              </td>
              <td><input type="text" class="bp-form-control entry-field" name="lines[1][description]" value="Sales revenue — POS Invoice #887" placeholder="Line description..."></td>
              <td><input type="number" class="bp-form-control text-end debit-input entry-field" name="lines[1][debit]" placeholder="0.00" min="0" step="0.01"></td>
              <td><input type="number" class="bp-form-control text-end credit-input entry-field" name="lines[1][credit]" value="45500" placeholder="0.00" min="0" step="0.01"></td>
              <td class="text-center"><button type="button" class="bp-btn bp-btn-sm bp-btn-icon bp-btn-danger remove-row entry-field" title="Remove"><i class="fa-solid fa-trash"></i></button></td>
            </tr>
          </tbody>
          <tfoot>
            <tr class="bp-table-totals-row">
              <td colspan="3" class="text-end fw-800">Totals:</td>
              <td class="text-end fw-800" id="totalDebit">{{ currency_symbol() }} {{ number_format($entry->lines->sum('debit_amount'), 0) }}</td>
              <td class="text-end fw-800" id="totalCredit">{{ currency_symbol() }} {{ number_format($entry->lines->sum('credit_amount'), 0) }}</td>
              <td></td>
            </tr>
            <tr>
              <td colspan="3" class="text-end fw-700">Difference:</td>
              <td colspan="2" class="text-center fw-800" id="totalDifference">
                <span class="bp-badge bp-badge-success">{{ currency_symbol() }} 0 (Balanced)</span>
              </td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>

  <!-- Attachments -->
  <div class="bp-card mb-4">
    <div class="bp-card-header">
      <h5 class="bp-card-title"><i class="fa-solid fa-paperclip me-2"></i>Attachments</h5>
    </div>
    <div class="bp-card-body">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="bp-form-label">Attach Document</label>
          <input type="file" class="bp-form-control entry-field" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.webp">
          <div class="fs-11 text-muted mt-1">PDF, JPG, PNG (Max 5MB)</div>
        </div>
        <div class="col-md-6">
          <label class="bp-form-label">Notes</label>
          <textarea class="bp-form-control entry-field" name="notes" rows="2" placeholder="Additional notes for this entry...">{{ old('notes') }}</textarea>
        </div>
      </div>
    </div>
  </div>

  <!-- Form Actions -->
  <div class="bp-card">
    <div class="bp-card-footer d-flex justify-content-between">
      <button type="button" class="bp-btn bp-btn-danger" id="deleteEntryBtn">
        <i class="fa-solid fa-trash me-1"></i> Delete Entry
      </button>
      <div class="d-flex gap-2">
        <a href="{{ route('accounting.journal-entries') }}" class="bp-btn bp-btn-danger"><i class="fa-solid fa-xmark me-1"></i>Cancel</a>
        <button type="submit" name="status" value="draft" class="bp-btn bp-btn-outline entry-field"><i class="fa-solid fa-file-pen me-1"></i> Save as Draft</button>
        <button type="submit" name="status" value="posted" class="bp-btn bp-btn-primary entry-field" id="postBtn"><i class="fa-solid fa-check me-1"></i> Post Entry</button>
      </div>
    </div>
  </div>

</form>

<!-- Delete Form (hidden) -->
<form action="{{ route('accounting.journal-entries.destroy', $entry) }}" method="POST" id="deleteEntryForm">
  @csrf
  @method('DELETE')
</form>

@endsection

@push('scripts')
<script>
'use strict';

$(function () {
    var rowIndex = 2;
    var entryStatus = 'draft'; // Change to 'posted' to simulate posted state

    function formatBDT(num) {
        num = Math.round(num * 100) / 100;
        var parts = num.toFixed(2).split('.');
        var intPart = parts[0];
        var decPart = parts[1];
        var lastThree = intPart.substring(intPart.length - 3);
        var otherNumbers = intPart.substring(0, intPart.length - 3);
        if (otherNumbers !== '') {
            lastThree = ',' + lastThree;
        }
        var formatted = otherNumbers.replace(/\B(?=(\d{2})+(?!\d))/g, ',') + lastThree;
        return formatted;
    }

    function getAccountOptionsHtml() {
        return $('#lineItemsBody tr:first-child select').first().html();
    }

    function recalculateTotals() {
        var totalDebit = 0;
        var totalCredit = 0;

        $('.debit-input').each(function () {
            totalDebit += parseFloat($(this).val()) || 0;
        });
        $('.credit-input').each(function () {
            totalCredit += parseFloat($(this).val()) || 0;
        });

        $('#totalDebit').text('{{ currency_symbol() }} ' + formatBDT(totalDebit));
        $('#totalCredit').text('{{ currency_symbol() }} ' + formatBDT(totalCredit));

        var diff = Math.abs(totalDebit - totalCredit);
        if (diff === 0 && (totalDebit > 0 || totalCredit > 0)) {
            $('#totalDifference').html('<span class="bp-badge bp-badge-success">{{ currency_symbol() }} 0 (Balanced)</span>');
            $('#postBtn').prop('disabled', false);
        } else if (diff === 0) {
            $('#totalDifference').html('<span class="bp-badge bp-badge-warning">{{ currency_symbol() }} 0 (No amounts entered)</span>');
            $('#postBtn').prop('disabled', true);
        } else {
            $('#totalDifference').html('<span class="bp-badge bp-badge-danger">{{ currency_symbol() }} ' + formatBDT(diff) + ' (Unbalanced)</span>');
            $('#postBtn').prop('disabled', true);
        }
    }

    function renumberRows() {
        $('#lineItemsBody .line-item-row').each(function (i) {
            $(this).find('.row-number').text(i + 1);
        });
    }

    function lockFieldsIfPosted() {
        if (entryStatus === 'posted') {
            $('#postedWarning').removeClass('d-none');
            $('#statusBadge').removeClass('bp-badge-warning').addClass('bp-badge-success').text('Posted');
            $('.entry-field').prop('disabled', true);
            $('#deleteEntryBtn').prop('disabled', true);
        }
    }

    // Lock fields if entry is posted
    lockFieldsIfPosted();

    // Add row
    $('#addRowBtn').on('click', function () {
        var optionsHtml = getAccountOptionsHtml();
        var newRow = '<tr class="line-item-row" data-row="' + (rowIndex + 1) + '">' +
            '<td class="text-center fw-600 row-number">' + (rowIndex + 1) + '</td>' +
            '<td><select class="bp-form-select w-100 entry-field" name="lines[' + rowIndex + '][account]" required>' + optionsHtml + '</select></td>' +
            '<td><input type="text" class="bp-form-control entry-field" name="lines[' + rowIndex + '][description]" placeholder="Line description..."></td>' +
            '<td><input type="number" class="bp-form-control text-end debit-input entry-field" name="lines[' + rowIndex + '][debit]" placeholder="0.00" min="0" step="0.01"></td>' +
            '<td><input type="number" class="bp-form-control text-end credit-input entry-field" name="lines[' + rowIndex + '][credit]" placeholder="0.00" min="0" step="0.01"></td>' +
            '<td class="text-center"><button type="button" class="bp-btn bp-btn-sm bp-btn-icon bp-btn-danger remove-row entry-field" title="Remove"><i class="fa-solid fa-trash"></i></button></td>' +
            '</tr>';
        $('#lineItemsBody').append(newRow);
        rowIndex++;
    });

    // Remove row
    $(document).on('click', '.remove-row', function () {
        var rowCount = $('#lineItemsBody .line-item-row').length;
        if (rowCount <= 2) {
            alert('A journal entry must have at least 2 lines.');
            return;
        }
        $(this).closest('tr').remove();
        renumberRows();
        recalculateTotals();
    });

    // Recalculate on input change
    $(document).on('input', '.debit-input, .credit-input', function () {
        var $row = $(this).closest('tr');
        if ($(this).hasClass('debit-input') && parseFloat($(this).val()) > 0) {
            $row.find('.credit-input').val('');
        } else if ($(this).hasClass('credit-input') && parseFloat($(this).val()) > 0) {
            $row.find('.debit-input').val('');
        }
        recalculateTotals();
    });

    // Form validation before submit
    $('#journalEntryForm').on('submit', function (e) {
        var totalDebit = 0;
        var totalCredit = 0;

        $('.debit-input').each(function () {
            totalDebit += parseFloat($(this).val()) || 0;
        });
        $('.credit-input').each(function () {
            totalCredit += parseFloat($(this).val()) || 0;
        });

        var diff = Math.abs(totalDebit - totalCredit);

        if (totalDebit === 0 && totalCredit === 0) {
            e.preventDefault();
            alert('Please enter at least one debit and one credit amount.');
            return false;
        }

        if (diff > 0.01) {
            e.preventDefault();
            alert('Debits and Credits must be equal. Current difference: {{ currency_symbol() }} ' + diff.toFixed(2));
            return false;
        }
    });

    // Delete entry confirmation
    $('#deleteEntryBtn').on('click', function () {
        if (confirm('Are you sure you want to delete this journal entry?\n\nThis action cannot be undone.')) {
            $('#deleteEntryForm').submit();
        }
    });

    // Initial calculation
    recalculateTotals();
});
</script>
@endpush
