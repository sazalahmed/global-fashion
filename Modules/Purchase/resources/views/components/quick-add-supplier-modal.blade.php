{{-- Quick "Add New Supplier" modal — shared by the purchase create + edit forms. --}}
<div class="modal fade" id="quickAddSupplierModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-800"><i class="fa-solid fa-truck-field me-2"></i>Add New Supplier</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-12">
            <label class="bp-form-label">Company Name *</label>
            <input type="text" class="bp-form-control" id="newSupplierCompany" placeholder="e.g. TechVision BD">
          </div>
          <div class="col-12">
            <label class="bp-form-label">Contact Person</label>
            <input type="text" class="bp-form-control" id="newSupplierContact" placeholder="e.g. Md. Tarek Rahman">
          </div>
          <div class="col-12">
            <label class="bp-form-label">Phone *</label>
            <input type="text" class="bp-form-control" id="newSupplierPhone" placeholder="e.g. 01811-456789">
          </div>
          <div class="col-12">
            <label class="bp-form-label">Email</label>
            <input type="email" class="bp-form-control" id="newSupplierEmail" placeholder="e.g. info@supplier.com">
          </div>
          <div class="col-12">
            <label class="bp-form-label">Address</label>
            <input type="text" class="bp-form-control" id="newSupplierAddress" placeholder="e.g. 45/B, Elephant Road, Dhaka">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="bp-btn bp-btn-danger" data-bs-dismiss="modal"><i class="fa-solid fa-xmark me-1"></i>Cancel</button>
        <button type="button" class="bp-btn bp-btn-success" id="saveNewSupplier">
          <i class="fa-solid fa-check me-1"></i> Save & Select
        </button>
      </div>
    </div>
  </div>
</div>
