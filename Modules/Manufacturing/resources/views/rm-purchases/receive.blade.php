@extends('core::layouts.master')

@section('title', 'Receive Goods — ' . $rmPurchase->po_number)
@section('page-title', __("Receive Goods"))

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Manufacturing</span>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <a href="{{ route('manufacturing.rm-purchases.index') }}">RM Purchase Orders</a>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <a href="{{ route('manufacturing.rm-purchases.show', $rmPurchase) }}">{{ $rmPurchase->po_number }}</a>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Receive Goods</span>
@endsection

@section('page-actions')
  <a href="{{ route('manufacturing.rm-purchases.show', $rmPurchase) }}" class="bp-btn bp-btn-primary"><i class="fa-solid fa-arrow-left me-1"></i> Back to PO</a>
@endsection

@section('content')

  <form action="{{ route('manufacturing.rm-purchases.receive.store', $rmPurchase) }}" method="POST" id="rmGrnForm">
    @csrf

    <div class="row g-4">
      <div class="col-xl-8">

        <!-- PO Summary -->
        <div class="bp-card mb-4">
          <div class="bp-card-header">
            <h5 class="bp-card-title"><i class="fa-solid fa-file-invoice me-2 text-primary"></i>{{ $rmPurchase->po_number }}</h5>
            <span class="bp-badge {{ $rmPurchase->status_badge_class }}">{{ str_replace('_', ' ', ucfirst($rmPurchase->status)) }}</span>
          </div>
          <div class="bp-card-body">
            <div class="row g-3">
              <div class="col-md-4">
                <div class="bp-info-label">Supplier</div>
                <div class="fw-700">{{ $rmPurchase->supplier->company_name ?? 'N/A' }}</div>
                @if($rmPurchase->supplier?->phone)
                  <div class="fs-12 text-muted">{{ $rmPurchase->supplier->phone }}</div>
                @endif
              </div>
              <div class="col-md-4">
                <div class="bp-info-label">PO Date</div>
                <div>{{ $rmPurchase->po_date->format('d M Y') }}</div>
              </div>
              <div class="col-md-4">
                <div class="bp-info-label">Grand Total</div>
                <div class="fw-800 text-primary">{{ currency_symbol() }} {{ number_format($rmPurchase->grand_total, 0) }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Items to Receive -->
        @php
          $pendingItems = $rmPurchase->items->filter(fn($item) => (float)$item->quantity - (float)$item->received_quantity > 0);
          $fullyReceived = $rmPurchase->items->filter(fn($item) => (float)$item->received_quantity >= (float)$item->quantity);
          $itemIdx = 0;
        @endphp

        <div class="bp-card mb-4">
          <div class="bp-card-header">
            <h5 class="bp-card-title"><i class="fa-solid fa-boxes-stacked me-2 text-info"></i>Items to Receive</h5>
            <span class="bp-badge bp-badge-info">{{ $pendingItems->count() }} pending</span>
          </div>
          <div class="bp-card-body p-0">
            <div class="bp-table-wrapper">
              <table class="bp-table" id="rmReceiveTable">
                <thead>
                  <tr>
                    <th>Raw Material</th>
                    <th class="text-center">Ordered</th>
                    <th class="text-center">Received</th>
                    <th class="text-center">Remaining</th>
                    <th>Qty to Receive</th>
                    <th>Qty Damaged</th>
                    <th>Damage Notes</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($pendingItems as $item)
                    @php
                      $remaining = (float) $item->quantity - (float) $item->received_quantity;
                    @endphp
                    <tr>
                      <td>
                        <div class="fw-700">{{ $item->rawMaterial->name ?? 'Unknown' }}</div>
                        <div class="fs-11 text-muted"><code>{{ $item->rawMaterial->code ?? '' }}</code> &middot; {{ ucfirst($item->rawMaterial->unit ?? '') }}</div>
                      </td>
                      <td class="text-center fw-600">{{ num($item->quantity) }}</td>
                      <td class="text-center">
                        @if((float)$item->received_quantity > 0)
                          <span class="fw-600 text-warning">{{ num($item->received_quantity) }}</span>
                        @else
                          <span class="text-muted">0</span>
                        @endif
                      </td>
                      <td class="text-center fw-700 text-primary">{{ num($remaining) }}</td>
                      <td>
                        <input type="hidden" name="items[{{ $itemIdx }}][purchase_item_id]" value="{{ $item->id }}">
                        <input type="number" class="bp-form-control rm-grn-receive-qty" name="items[{{ $itemIdx }}][quantity_received]" value="{{ $remaining }}" min="0" max="{{ $remaining }}" step="any">
                      </td>
                      <td>
                        <input type="number" class="bp-form-control rm-grn-damage-qty" name="items[{{ $itemIdx }}][quantity_damaged]" value="0" min="0" max="{{ $remaining }}" step="any">
                      </td>
                      <td>
                        <input type="text" class="bp-form-control" name="items[{{ $itemIdx }}][damage_notes]" placeholder="If damaged...">
                      </td>
                    </tr>
                    @php $itemIdx++; @endphp
                  @empty
                    <x-core::table.empty colspan="7" icon="fa-solid fa-circle-check" title="All items have been fully received." />
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
          @if($pendingItems->isNotEmpty())
            <div class="bp-card-footer d-flex justify-content-between align-items-center">
              <div class="fs-13 text-muted">
                <i class="fa-solid fa-info-circle me-1"></i>
                Set receive qty to 0 for items not in this delivery.
              </div>
              <div>
                <button type="button" class="bp-btn bp-btn-sm bp-btn-outline me-2" id="rmReceiveAllBtn"><i class="fa-solid fa-check-double me-1"></i> Receive All</button>
                <button type="button" class="bp-btn bp-btn-sm bp-btn-outline" id="rmReceiveNoneBtn"><i class="fa-solid fa-xmark me-1"></i> Clear All</button>
              </div>
            </div>
          @endif
        </div>

        <!-- Already Received Items -->
        @if($fullyReceived->isNotEmpty())
          <div class="bp-card mb-4">
            <div class="bp-card-header">
              <h5 class="bp-card-title"><i class="fa-solid fa-circle-check me-2 text-success"></i>Fully Received</h5>
              <span class="bp-badge bp-badge-success">{{ $fullyReceived->count() }} item(s)</span>
            </div>
            <div class="bp-card-body p-0">
              <div class="bp-table-wrapper">
                <table class="bp-table">
                  <thead>
                    <tr>
                      <th>Raw Material</th>
                      <th class="text-center">Ordered</th>
                      <th class="text-center">Received</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($fullyReceived as $item)
                      <tr class="opacity-75">
                        <td>
                          <div class="fw-600">{{ $item->rawMaterial->name ?? 'Unknown' }}</div>
                          <div class="fs-11 text-muted"><code>{{ $item->rawMaterial->code ?? '' }}</code></div>
                        </td>
                        <td class="text-center">{{ num($item->quantity) }}</td>
                        <td class="text-center text-success fw-700">
                          {{ num($item->received_quantity) }} <i class="fa-solid fa-check-circle fs-11 ms-1"></i>
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        @endif

      </div>

      <div class="col-xl-4">

        <!-- GRN Details -->
        <div class="bp-card mb-4">
          <div class="bp-card-header">
            <h5 class="bp-card-title"><i class="fa-solid fa-clipboard-check me-2 text-success"></i>GRN Details</h5>
          </div>
          <div class="bp-card-body">
            <div class="row g-3">
              <div class="col-12">
                <label class="bp-form-label">Received Date *</label>
                <input type="date" class="bp-form-control" name="receive_date" value="{{ old('receive_date', now()->format('Y-m-d')) }}" required>
                @error('receive_date')
                  <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-12">
                <label class="bp-form-label">Notes</label>
                <textarea class="bp-form-control" name="notes" rows="3" placeholder="Any notes about this delivery...">{{ old('notes') }}</textarea>
                @error('notes')
                  <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                @enderror
              </div>
            </div>
          </div>
        </div>

        <!-- Receive Summary -->
        <div class="bp-card mb-4">
          <div class="bp-card-header">
            <h5 class="bp-card-title"><i class="fa-solid fa-chart-pie me-2 text-info"></i>Summary</h5>
          </div>
          <div class="bp-card-body">
            <div class="bp-info-row bp-info-row-compact">
              <div class="bp-info-label bp-info-label-md">Total Items in PO</div>
              <div class="bp-info-value fw-700">{{ $rmPurchase->items->count() }}</div>
            </div>
            <div class="bp-info-row bp-info-row-compact">
              <div class="bp-info-label bp-info-label-md">Fully Received</div>
              <div class="bp-info-value fw-700 text-success">{{ $fullyReceived->count() }}</div>
            </div>
            <div class="bp-info-row bp-info-row-compact">
              <div class="bp-info-label bp-info-label-md">Pending</div>
              <div class="bp-info-value fw-700 text-warning">{{ $pendingItems->count() }}</div>
            </div>
            <div class="bp-info-row bp-info-row-compact bp-info-row-last">
              <div class="bp-info-label bp-info-label-md">Receiving Now</div>
              <div class="bp-info-value fw-800 text-primary" id="rmReceivingNowCount">{{ $pendingItems->count() }}</div>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="bp-card">
          <div class="bp-card-body d-flex flex-column gap-2">
            @if($pendingItems->isNotEmpty())
              @bpCan('manufacturing.edit')
              <button type="submit" class="bp-btn bp-btn-success w-100 justify-content-center">
                <i class="fa-solid fa-check me-1"></i> Confirm & Receive Goods
              </button>
              @endbpCan
            @endif
            <a href="{{ route('manufacturing.rm-purchases.show', $rmPurchase) }}" class="bp-btn bp-btn-danger w-100 justify-content-center"><i class="fa-solid fa-xmark me-1"></i>Cancel</a>
          </div>
        </div>

      </div>
    </div>

  </form>

@endsection

@push('scripts')
<script>
'use strict';

$(function () {
    // Validate damaged qty <= received qty
    $(document).on('input', '.rm-grn-receive-qty', function () {
        var $row = $(this).closest('tr');
        var received = parseFloat($(this).val()) || 0;
        var $damaged = $row.find('.rm-grn-damage-qty');
        var damaged = parseFloat($damaged.val()) || 0;

        if (damaged > received) {
            $damaged.val(0);
        }
        $damaged.attr('max', received);
        updateRmReceivingSummary();
    });

    $(document).on('input', '.rm-grn-damage-qty', function () {
        var $row = $(this).closest('tr');
        var received = parseFloat($row.find('.rm-grn-receive-qty').val()) || 0;
        var damaged = parseFloat($(this).val()) || 0;

        if (damaged > received) {
            $(this).val(received);
        }
    });

    // Receive All / Clear All buttons
    $('#rmReceiveAllBtn').on('click', function () {
        $('.rm-grn-receive-qty').each(function () {
            $(this).val($(this).attr('max'));
        });
        updateRmReceivingSummary();
    });

    $('#rmReceiveNoneBtn').on('click', function () {
        $('.rm-grn-receive-qty').val(0);
        $('.rm-grn-damage-qty').val(0);
        updateRmReceivingSummary();
    });

    function updateRmReceivingSummary() {
        var count = 0;
        $('.rm-grn-receive-qty').each(function () {
            if (parseFloat($(this).val()) > 0) {
                count++;
            }
        });
        $('#rmReceivingNowCount').text(count);
    }
});
</script>
@endpush
