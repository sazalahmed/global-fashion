{{-- Purchase variant picker — lists ALL variants of the selected product so the
     buyer can enter a quantity per variant. Each chosen variant becomes its own
     order line. Includes an "Add New Variant" picker (mirrors product create).

     Params (optional):
       $showCost     — render the Unit Cost column header (default true).
                       Pages that don't price items (e.g. requisitions) pass false
                       and simply don't render cost cells in their row JS.
       $confirmLabel — confirm button text (default "Add to Order"). --}}
@php
    $showCost     = $showCost ?? true;
    $confirmLabel = $confirmLabel ?? __('Add to Order');
@endphp
<div class="modal fade" id="poVariantModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fa-solid fa-layer-group me-2"></i>Select Variants — <span id="poVariantModalProduct" class="fw-700"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="poVariantError" class="alert alert-danger d-none fs-13"></div>

        <div class="bp-table-wrapper">
          <table class="bp-table">
            <thead>
              <tr>
                <th>Variant</th>
                <th>SKU</th>
                <th style="width:110px;">Qty</th>
                @if($showCost)
                  <th style="width:140px;">Unit Cost ({{ currency_symbol() }})</th>
                @endif
              </tr>
            </thead>
            <tbody id="poVariantRows"></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="bp-btn bp-btn-danger" data-bs-dismiss="modal"><i class="fa-solid fa-xmark me-1"></i>Cancel</button>
        <button type="button" class="bp-btn bp-btn-primary" id="poVariantConfirm"><i class="fa-solid fa-cart-plus me-1"></i> {{ $confirmLabel }}</button>
      </div>
    </div>
  </div>
</div>
