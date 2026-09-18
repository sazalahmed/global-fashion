@extends('core::layouts.master')

@section('title', __("Create Journal Entry"))
@section('page-title', __("Create Journal Entry"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('accounting.journal-entries') }}">Journal Entries</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Create</span>
@endsection

@section('page-actions')
<a href="{{ route('accounting.journal-entries') }}" class="bp-btn bp-btn-primary">
  <i class="fa-solid fa-arrow-left me-1"></i> Back to Journal Entries
</a>
@endsection

@section('content')

<form action="{{ route('accounting.journal-entries.store') }}" method="POST" id="journalEntryForm">
  @csrf

  <!-- Entry Header -->
  <div class="bp-card mb-4">
    <div class="bp-card-header">
      <h5 class="bp-card-title"><i class="fa-solid fa-circle-info me-2"></i>Entry Details</h5>
    </div>
    <div class="bp-card-body">
      <div class="row g-3">
        <div class="col-md-2">
          <label class="bp-form-label">Entry #</label>
          <input type="text" class="bp-form-control" name="entry_no" value="{{ $nextEntryNumber }}" readonly>
        </div>
        <div class="col-md-2">
          <label class="bp-form-label">Date *</label>
          <input type="date" class="bp-form-control" name="date" value="{{ old('date', date('Y-m-d')) }}" required>
          @error('date')
            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-md-3">
          <label class="bp-form-label">Reference</label>
          <input type="text" class="bp-form-control" name="reference" value="{{ old('reference') }}" placeholder="e.g. INV-2026-0893">
          @error('reference')
            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-md-5">
          <label class="bp-form-label">Description / Narration *</label>
          <input type="text" class="bp-form-control" name="description" value="{{ old('description') }}" placeholder="e.g. Sales revenue from POS Invoice #893" required>
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
      <button type="button" class="bp-btn bp-btn-sm bp-btn-primary" id="addRowBtn">
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
            @php $lineIndex = 0; @endphp
            @for($lineIndex = 0; $lineIndex < 2; $lineIndex++)
            <tr class="line-item-row" data-row="{{ $lineIndex + 1 }}">
              <td class="text-center fw-600 row-number">{{ $lineIndex + 1 }}</td>
              <td>
                <select class="bp-form-select w-100" name="lines[{{ $lineIndex }}][account]" required>
                  <option value="">Select Account</option>
                  @foreach($accounts as $type => $typeAccounts)
                    <optgroup label="{{ $type }}">
                      @foreach($typeAccounts as $acct)
                        <option value="{{ $acct->id }}">{{ $acct->code ? $acct->code . ' — ' : '' }}{{ $acct->name }}</option>
                      @endforeach
                    </optgroup>
                  @endforeach
                </select>
              </td>
              <td><input type="text" class="bp-form-control" name="lines[{{ $lineIndex }}][description]" placeholder="Line description..."></td>
              <td><input type="number" class="bp-form-control text-end debit-input" name="lines[{{ $lineIndex }}][debit]" placeholder="0.00" min="0" step="0.01"></td>
              <td><input type="number" class="bp-form-control text-end credit-input" name="lines[{{ $lineIndex }}][credit]" placeholder="0.00" min="0" step="0.01"></td>
              <td class="text-center"><button type="button" class="bp-btn bp-btn-sm bp-btn-icon bp-btn-danger remove-row" title="Remove"><i class="fa-solid fa-trash"></i></button></td>
            </tr>
            @endfor
          </tbody>
          <tfoot>
            <tr class="bp-table-totals-row">
              <td colspan="3" class="text-end fw-800">Totals:</td>
              <td class="text-end fw-800" id="totalDebit">{{ currency_symbol() }} 0</td>
              <td class="text-end fw-800" id="totalCredit">{{ currency_symbol() }} 0</td>
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
          <input type="file" class="bp-form-control" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.webp">
          <div class="fs-11 text-muted mt-1">PDF, JPG, PNG (Max 5MB)</div>
        </div>
        <div class="col-md-6">
          <label class="bp-form-label">Notes</label>
          <textarea class="bp-form-control" name="notes" rows="2" placeholder="Additional notes for this entry...">{{ old('notes') }}</textarea>
        </div>
      </div>
    </div>
  </div>

  <!-- Form Actions -->
  <div class="d-flex justify-content-end gap-2 mt-3">
    <a href="{{ route('accounting.journal-entries') }}" class="bp-btn bp-btn-danger"><i class="fa-solid fa-xmark me-1"></i>Cancel</a>
    <button type="submit" name="status" value="draft" class="bp-btn bp-btn-outline"><i class="fa-solid fa-file-pen me-1"></i> Save as Draft</button>
    <button type="submit" name="status" value="posted" class="bp-btn bp-btn-primary" id="postBtn"><i class="fa-solid fa-check me-1"></i> Post Entry</button>
  </div>

</form>

@endsection

@push('scripts')
<script>
'use strict';

var accountsJson = @json($accounts->flatten());

$(function () {
    var rowIndex = 2;

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
        var html = '<option value="">Select Account</option>';
        var grouped = {};
        $.each(accountsJson, function (i, acct) {
            var type = acct.account_type || 'Other';
            if (!grouped[type]) { grouped[type] = []; }
            grouped[type].push(acct);
        });
        $.each(grouped, function (type, accts) {
            html += '<optgroup label="' + $('<div>').text(type).html() + '">';
            $.each(accts, function (i, acct) {
                var label = acct.code ? acct.code + ' \u2014 ' + acct.name : acct.name;
                html += '<option value="' + acct.id + '">' + $('<div>').text(label).html() + '</option>';
            });
            html += '</optgroup>';
        });
        return html;
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

    // Add row
    $('#addRowBtn').on('click', function () {
        var optionsHtml = getAccountOptionsHtml();
        var newRow = '<tr class="line-item-row" data-row="' + (rowIndex + 1) + '">' +
            '<td class="text-center fw-600 row-number">' + (rowIndex + 1) + '</td>' +
            '<td><select class="bp-form-select w-100" name="lines[' + rowIndex + '][account]" required>' + optionsHtml + '</select></td>' +
            '<td><input type="text" class="bp-form-control" name="lines[' + rowIndex + '][description]" placeholder="Line description..."></td>' +
            '<td><input type="number" class="bp-form-control text-end debit-input" name="lines[' + rowIndex + '][debit]" placeholder="0.00" min="0" step="0.01"></td>' +
            '<td><input type="number" class="bp-form-control text-end credit-input" name="lines[' + rowIndex + '][credit]" placeholder="0.00" min="0" step="0.01"></td>' +
            '<td class="text-center"><button type="button" class="bp-btn bp-btn-sm bp-btn-icon bp-btn-danger remove-row" title="Remove"><i class="fa-solid fa-trash"></i></button></td>' +
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
        // If debit is entered, clear credit on same row (and vice versa)
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
});
</script>
@endpush
