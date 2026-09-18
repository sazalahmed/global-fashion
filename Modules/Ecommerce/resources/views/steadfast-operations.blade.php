@extends('core::layouts.master')

@section('title', __('Steadfast Operations'))
@section('page-title', __('Steadfast Live Operations'))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('ecommerce.courier-providers') }}">Courier Providers</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Steadfast</span>
@endsection

@section('page-actions')
<a href="{{ route('ecommerce.courier-providers') }}" class="bp-btn bp-btn-primary"><i class="fa-solid fa-arrow-left me-1"></i>Back to Providers</a>
<a href="{{ route('ecommerce.steadfast.index') }}" class="bp-btn bp-btn-outline" title="Refresh"><i class="fa-solid fa-rotate"></i></a>
@endsection

@section('content')

@if(!$configured)
  <div class="bp-card">
    <div class="bp-card-body text-center py-5">
      <i class="fa-solid fa-key fs-1 d-block mb-3 text-warning"></i>
      <h5 class="fw-700">Steadfast not configured</h5>
      <p class="text-muted">Set <strong>API Key</strong> and <strong>API Secret</strong> on the Steadfast row in Courier Providers, then mark it active.</p>
      <a href="{{ route('ecommerce.courier-providers') }}" class="bp-btn bp-btn-primary"><i class="fa-solid fa-gears me-1"></i> Configure Steadfast</a>
    </div>
  </div>
@elseif($error)
  <div class="alert alert-danger">
    <i class="fa-solid fa-circle-exclamation me-1"></i>
    <strong>Steadfast API error:</strong> {{ $error }}
  </div>
@else
  @if(!empty($syncWarning))
    <div class="alert alert-warning">
      <i class="fa-solid fa-triangle-exclamation me-1"></i>
      <strong>Settlements not recorded:</strong> {{ $syncWarning }}
    </div>
  @endif

  {{-- Balance --}}
  <div class="row g-3 mb-4">
    <div class="col-md-6 col-lg-4">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-success"><i class="fa-solid fa-wallet"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Current Balance</div>
          <div class="bp-stat-value">
            {{ money((float) ($balance['current_balance'] ?? $balance['balance'] ?? 0)) }}
          </div>
          @if(!empty($balance['updated_at']))
            <div class="bp-stat-change text-muted fs-12">As of {{ $balance['updated_at'] }}</div>
          @endif
        </div>
      </div>
    </div>
  </div>

  {{-- Payments --}}
  <div class="bp-card">
    <div class="bp-card-header">
      <h5 class="bp-card-title"><i class="fa-solid fa-receipt me-2"></i>Payment Settlements</h5>
    </div>
    <div class="bp-card-body p-0">
      @php $rows = is_array($payments) ? $payments : []; @endphp
      @if(empty($rows))
        <div class="text-center text-muted py-4 fs-13">No payments returned by Steadfast.</div>
      @else
        <div class="bp-table-wrapper">
          <table class="bp-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Payment Date</th>
                <th class="text-end">Gross</th>
                <th class="text-end">Charges</th>
                <th class="text-end">Net Paid</th>
                <th>Method</th>
                <th>Status</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              @foreach($rows as $p)
                @php
                  $paymentId = $p['payment_id'] ?? $p['id'] ?? null;
                  $statusLabel = $p['status_label'] ?? $p['status'] ?? '';
                  $status = strtolower((string) $statusLabel);
                  // Steadfast reports gross COD, what it deducted, and the net
                  // it actually paid — due_bills/paid_bills are money, not counts.
                  $charges = (float) ($p['charges'] ?? 0) + (float) ($p['due_bills'] ?? 0);
                @endphp
                <tr>
                  <td class="fw-700 fs-12">{{ $paymentId ?? '—' }}</td>
                  <td class="fs-12">{{ $p['paid_at'] ?? $p['created_at'] ?? '' }}</td>
                  <td class="text-end fw-700">{{ money((float) ($p['amount'] ?? 0)) }}</td>
                  <td class="text-end text-danger">{{ $charges > 0 ? money($charges) : '—' }}</td>
                  <td class="text-end fw-800 text-success">{{ money((float) ($p['total'] ?? $p['amount'] ?? 0)) }}</td>
                  <td class="fs-12">{{ $p['method'] ?? $p['payment_method'] ?? '' }}</td>
                  <td>
                    <span class="bp-badge {{ str_contains($status, 'paid') ? 'bp-badge-success' : (str_contains($status, 'pending') || str_contains($status, 'ready') ? 'bp-badge-warning' : 'bp-badge-secondary') }}">
                      {{ $statusLabel !== '' ? $statusLabel : '—' }}
                    </span>
                  </td>
                  <td class="text-end">
                    @if(!empty($paymentId))
                      <button type="button" class="bp-btn bp-btn-sm bp-btn-outline bp-sf-view-payment"
                              data-payment-id="{{ $paymentId }}">
                        <i class="fa-solid fa-eye me-1"></i>View
                      </button>
                    @endif
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
    </div>
  </div>
@endif


{{-- Payment detail modal --}}
<div class="modal fade" id="sfPaymentModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fa-solid fa-receipt me-2"></i>Payment <span id="sfPaymentId"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="sfPaymentBody">
        <div class="text-muted">{{ __('Loading...') }}</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="bp-btn bp-btn-danger" data-bs-dismiss="modal"><i class="fa-solid fa-xmark me-1"></i>{{ __('Close') }}</button>
      </div>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script>
'use strict';

$(function () {
    $(document).on('click', '.bp-sf-view-payment', function () {
        var id = $(this).data('payment-id');
        $('#sfPaymentId').text(id);
        $('#sfPaymentBody').html('<div class="text-muted"><i class="fa-solid fa-spinner fa-spin me-2"></i>Loading...</div>');
        var modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('sfPaymentModal'));
        modal.show();

        $.get('{{ url("admin/ecommerce/steadfast/payments") }}/' + id)
            .done(function (res) {
                var pay = res.payment || {};
                var esc = function (v) { return $('<div/>').text(v == null ? '' : v).html(); };
                var lines = (pay.consignments || pay.data || []).map(function (c) {
                    return '<tr>'
                         + '<td class="fs-12">' + esc(c.consignment_id || c.id || '—') + '</td>'
                         + '<td class="fs-12">' + esc(c.invoice || '—') + '</td>'
                         + '<td class="fs-12">' + esc(c.recipient_name || '—') + '</td>'
                         + '<td class="text-end fw-700">{{ currency_symbol() }} ' + window.fmtAmount(c.cod_amount || 0) + '</td>'
                         + '<td class="text-end text-danger">'
                         + (c.delivery_charge === null || c.delivery_charge === undefined
                             ? '—'
                             : '{{ currency_symbol() }} ' + window.fmtAmount(c.delivery_charge)) + '</td>'
                         + '<td class="fs-12">' + esc(c.status || '—') + '</td>'
                         + '</tr>';
                }).join('');
                var deliveryTotal = (pay.consignments || []).reduce(function (sum, c) {
                    return sum + (parseFloat(c.delivery_charge) || 0);
                }, 0);
                var charges = (parseFloat(pay.charges) || 0) + (parseFloat(pay.due_bills) || 0);
                var html = '<div class="row g-3 mb-3">'
                    + '<div class="col-md-3"><strong>Gross:</strong> {{ currency_symbol() }} ' + window.fmtAmount(pay.amount || 0) + '</div>'
                    + '<div class="col-md-3"><strong>Charges:</strong> {{ currency_symbol() }} ' + window.fmtAmount(charges) + '</div>'
                    + '<div class="col-md-3"><strong>Net Paid:</strong> {{ currency_symbol() }} ' + window.fmtAmount(pay.total || pay.amount || 0) + '</div>'
                    + '<div class="col-md-3"><strong>Status:</strong> ' + esc(pay.status_label || pay.status || '—') + '</div>'
                    + '</div>';
                if (lines) {
                    html += '<table class="bp-table"><thead><tr><th>CID</th><th>Invoice</th><th>Recipient</th>'
                          + '<th class="text-end">COD</th><th class="text-end">Delivery</th><th>Status</th></tr></thead>'
                          + '<tbody>' + lines + '</tbody>'
                          + '<tfoot><tr class="bp-table-highlight"><td colspan="4" class="text-end fw-700">Delivery charges</td>'
                          + '<td class="text-end fw-800 text-danger">{{ currency_symbol() }} ' + window.fmtAmount(deliveryTotal) + '</td>'
                          + '<td></td></tr></tfoot></table>';
                } else {
                    html += '<div class="text-muted">No consignments returned for this payment.</div>';
                }
                $('#sfPaymentBody').html(html);
            })
            .fail(function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Failed to load payment.';
                $('#sfPaymentBody').html('<div class="alert alert-danger mb-0">' + msg + '</div>');
            });
    });
});
</script>
@endpush
