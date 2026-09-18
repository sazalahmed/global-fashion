<div class="modal fade" id="qvVariantModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-layer-group me-2"></i>{{ __('Select Variants') }} — <span
                        id="qvModalProduct" class="fw-700"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="qvError" class="alert alert-danger d-none fs-13"></div>

                <div class="bp-table-wrapper">
                    <table class="bp-table">
                        <thead>
                            <tr>
                                <th>{{ __('Variant') }}</th>
                                <th>{{ __('SKU') }}</th>
                                <th class="bp-col-qty">{{ __('Qty') }}</th>
                                <th class="bp-col-price">{{ __('Unit Price') }} ({{ currency_symbol() }})</th>
                            </tr>
                        </thead>
                        <tbody id="qvVariantRows"></tbody>
                    </table>
                </div>

                <div class="mt-3">
                    <label class="bp-form-label">{{ __('Custom Note') }}</label>
                    <textarea class="bp-form-control" id="qvCustomNote" rows="2" maxlength="500"
                        placeholder="{{ __('e.g. lemon + sky + Biscuit') }}"></textarea>
                    <small
                        class="text-muted fs-11">{{ __('Auto-filled from the variants you choose. Edit freely; shown on the quotation.') }}</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="bp-btn bp-btn-danger" data-bs-dismiss="modal"><i
                        class="fa-solid fa-xmark me-1"></i>{{ __('Cancel') }}</button>
                <button type="button" class="bp-btn bp-btn-success" id="qvConfirm"><i
                        class="fa-solid fa-plus me-1"></i> {{ __('Add to Quotation') }}</button>
            </div>
        </div>
    </div>
</div>
