{{-- Combo manager: shown as a single combo row in the order table; this modal
     lets the admin set the combo package price and add / edit / remove the
     products inside it. Component unit prices are auto-allocated from the combo
     price on submit, so the table only ever shows the combo price. Shared by the
     sale create + edit forms. --}}
<div class="modal fade" id="comboManagerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-layer-group me-2"></i><span
                        id="comboMgrTitle">{{ __('Combo') }}</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-5">
                        <label class="bp-form-label">{{ __('Combo Price') }} *</label>
                        <input type="number" class="bp-form-control" id="comboMgrPrice" min="0" step="0.01">
                        <div class="fs-12 text-muted mt-1">
                            {{ __('Package price. Split across products automatically.') }}</div>
                    </div>
                </div>

                <label class="bp-form-label">{{ __('Products in this combo') }}</label>
                <div class="bp-table-wrapper">
                    <table class="bp-table mb-0" id="comboMgrItems">
                        <thead>
                            <tr>
                                <th>{{ __('Product') }}</th>
                                <th>{{ __('Variant') }}</th>
                                <th>{{ __('Quantity') }}</th>
                                <th class="text-end"></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

                <div class="bp-combo-add-row mt-3">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-5">
                            <label class="bp-form-label">{{ __('Add Product') }}</label>
                            <select class="bp-form-select select2-search w-100" id="comboMgrAddProduct"></select>
                        </div>
                        <div class="col-md-4">
                            <label class="bp-form-label">{{ __('Variant') }}</label>
                            <select class="bp-form-select w-100" id="comboMgrAddVariant">
                                <option value="">{{ __('No variant') }}</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="bp-form-label">{{ __('Qty') }}</label>
                            <input type="number" class="bp-form-control" id="comboMgrAddQty" min="1" value="1">
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="bp-btn bp-btn-primary bp-btn-icon w-100" id="comboMgrAddBtn"
                                title="{{ __('Add') }}"><i class="fa-solid fa-plus"></i></button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="bp-btn bp-btn-danger" data-bs-dismiss="modal"><i
                        class="fa-solid fa-xmark me-1"></i>{{ __('Cancel') }}</button>
                <button type="button" class="bp-btn bp-btn-success" id="comboMgrSave"><i
                        class="fa-solid fa-check me-1"></i>{{ __('Save Combo') }}</button>
            </div>
        </div>
    </div>
</div>
