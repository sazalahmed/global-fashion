{{-- New Customer modal — mirrors the Sales "New Customer" quick-add.
     Requires $customerGroups and $districts from the controller, and a customer
     <select id="customer_id"> on the page (the x-core::select2 default id). --}}
<div class="modal fade" id="quickCustomerModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-user-plus me-2"></i>{{ __('New Customer') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="qcForm" enctype="multipart/form-data">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="bp-form-label">{{ __('Full Name') }} *</label>
                            <input type="text" class="bp-form-control" name="name" id="qcName" required
                                placeholder="{{ __('Customer full name') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="bp-form-label">{{ __('Phone Number') }} *</label>
                            <input type="text" class="bp-form-control" name="phone" id="qcPhone" required
                                placeholder="01XXXXXXXXX">
                        </div>
                        <div class="col-md-6">
                            <label class="bp-form-label">{{ __('Email') }}</label>
                            <input type="email" class="bp-form-control" name="email" id="qcEmail"
                                placeholder="customer@email.com">
                        </div>
                        <div class="col-md-6">
                            <label class="bp-form-label">{{ __('Customer Group') }}</label>
                            <select class="bp-form-select w-100" name="customer_group_id" id="qcGroup" data-no-select2>
                                <option value="">{{ __('Select Group') }}</option>
                                @foreach ($customerGroups as $group)
                                    <option value="{{ $group->id }}">
                                        {{ $group->name }}{{ $group->discount_percentage > 0 ? ' (' . $group->discount_percentage . '% off)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="bp-form-label">{{ __('District') }}</label>
                            <select class="bp-form-select w-100" name="district" id="qcDistrict" data-no-select2
                                data-thana-url="{{ url('admin/customers/thanas') }}">
                                <option value="">{{ __('Select District') }}</option>
                                @foreach ($districts as $d)
                                    <option value="{{ $d->district_name }}" data-id="{{ $d->id }}">
                                        {{ $d->district_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="bp-form-label">{{ __('Thana') }}</label>
                            <select class="bp-form-select w-100" name="upazila" id="qcThana" data-no-select2>
                                <option value="">{{ __('Select District first') }}</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="bp-form-label">{{ __('Street Address') }}</label>
                            <input type="text" class="bp-form-control" name="address" id="qcAddress"
                                placeholder="{{ __('House, Road, Area') }}">
                        </div>
                        <div class="col-md-12">
                            <label class="bp-form-label">{{ __('Photo') }}</label>
                            <input type="file" class="bp-form-control" name="photo" id="qcPhoto"
                                accept="image/jpg,image/jpeg,image/png,image/webp">
                        </div>
                        <div class="col-12">
                            <label class="bp-form-label">{{ __('Notes') }}</label>
                            <textarea class="bp-form-control" name="notes" id="qcNotes" rows="2"
                                placeholder="{{ __('Internal notes about this customer...') }}"></textarea>
                        </div>
                    </div>
                    <div class="text-danger fs-12 d-none mt-2" id="qcError"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="bp-btn bp-btn-danger" data-bs-dismiss="modal"><i
                        class="fa-solid fa-xmark me-1"></i>{{ __('Cancel') }}</button>
                <button type="button" class="bp-btn bp-btn-success" id="qcSave"><i
                        class="fa-solid fa-check me-1"></i> {{ __('Save & Select') }}</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
'use strict';
$(function () {
    var qcModal = new bootstrap.Modal(document.getElementById('quickCustomerModal'));

    $('#btnNewCustomer').on('click', function (e) {
        e.preventDefault();
        document.getElementById('qcForm').reset();
        $('#qcThana').html('<option value="">{{ __('Select District first') }}</option>');
        $('#qcError').addClass('d-none').text('');
        qcModal.show();
        setTimeout(function () { $('#qcName').trigger('focus'); }, 250);
    });

    // Cascading thana inside the modal.
    $('#qcDistrict').on('change', function () {
        var $thana = $('#qcThana');
        var districtId = $(this).find(':selected').data('id');
        if (!districtId) {
            $thana.html('<option value="">{{ __('Select District first') }}</option>');
            return;
        }
        var url = $('#qcDistrict').data('thana-url') + '/' + districtId;
        $thana.html('<option value="">{{ __('Loading...') }}</option>');
        $.getJSON(url, function (rows) {
            var html = '<option value="">{{ __('Select Thana') }}</option>';
            rows.forEach(function (t) {
                html += '<option value="' + t.thana_name + '">' + t.thana_name + '</option>';
            });
            $thana.html(html);
        }).fail(function () {
            $thana.html('<option value="">{{ __('Could not load thanas') }}</option>');
        });
    });

    $('#qcSave').on('click', function () {
        var $btn = $(this);
        $('#qcError').addClass('d-none').text('');

        if (!$('#qcName').val().trim() || !$('#qcPhone').val().trim()) {
            $('#qcError').removeClass('d-none').text('{{ __('Name and phone are required.') }}');
            return;
        }

        var formData = new FormData(document.getElementById('qcForm'));
        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> {{ __('Save & Select') }}');
        $.ajax({
            url: '{{ route('customers.quick-store') }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        })
        .done(function (res) {
            var c = res.customer || {};
            $('#customer_id').append(
                $('<option/>').val(c.id).text(c.name + (c.phone ? ' (' + c.phone + ')' : ''))
            );
            $('#customer_id').val(c.id).trigger('change');
            qcModal.hide();
        })
        .fail(function (xhr) {
            var msg = '{{ __('Failed to create customer.') }}';
            if (xhr.responseJSON && xhr.responseJSON.errors) {
                msg = Object.values(xhr.responseJSON.errors).flat().join(' ');
            }
            $('#qcError').removeClass('d-none').text(msg);
        })
        .always(function () {
            $btn.prop('disabled', false).html('<i class="fa-solid fa-check me-1"></i> {{ __('Save & Select') }}');
        });
    });
});
</script>
@endpush
