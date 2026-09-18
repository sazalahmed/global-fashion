{{-- Shared form body for shipping zone create + edit --}}
<div class="bp-card mb-3">
    <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-map-location-dot me-2"></i>Zone Details</h5>
    </div>
    <div class="bp-card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="bp-form-label">Zone Name *</label>
                <input type="text" class="bp-form-control @error('name') is-invalid @enderror" name="name"
                    value="{{ old('name', $zone->name ?? '') }}" placeholder="e.g. Inside Dhaka" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-4">
                <label class="bp-form-label">Bangla Name</label>
                <input type="text" class="bp-form-control @error('bn_name') is-invalid @enderror" name="bn_name"
                    value="{{ old('bn_name', $zone->bn_name ?? '') }}" placeholder="e.g. ঢাকার মধ্যে" lang="bn">
                <div class="fs-11 text-muted mt-1">Shown alongside the English name in the storefront checkout dropdown.
                </div>
                @error('bn_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-2">
                <label class="bp-form-label">Charge ({{ currency_symbol() }}) *</label>
                <input type="number" step="0.01" min="0"
                    class="bp-form-control @error('flat_rate') is-invalid @enderror" name="flat_rate"
                    value="{{ old('flat_rate', num_input($zone->flat_rate ?? 0)) }}" required>
                @error('flat_rate')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-2">
                <label class="bp-form-label">Estimated Days</label>
                <input type="number" min="1" max="30" class="bp-form-control" name="estimated_days"
                    value="{{ old('estimated_days', $zone->estimated_days ?? 3) }}">
            </div>
            <div class="col-md-6">
                <label class="bp-form-label">Free Shipping Above ({{ currency_symbol() }})</label>
                <input type="number" step="0.01" min="0" class="bp-form-control"
                    name="free_shipping_threshold"
                    value="{{ old('free_shipping_threshold', num_input($zone->free_shipping_threshold ?? '')) }}"
                    placeholder="Leave empty for no free-shipping rule">
                <div class="fs-11 text-muted mt-1">Order subtotals at or above this amount ship free.</div>
            </div>
            <div class="col-md-6">
                <label class="bp-form-label d-block">Status</label>
                <div class="form-check form-switch d-inline-flex align-items-center gap-2">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" class="form-check-input" id="zoneActive" name="is_active" value="1"
                        {{ old('is_active', isset($zone) ? $zone->is_active : true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="zoneActive">Active</label>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="bp-card mb-3">
    <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-location-dot me-2"></i>Districts Covered</h5>
        <div class="d-flex gap-2 align-items-center">
            <input type="text" class="bp-form-control bp-form-control-sm" id="districtFilter"
                placeholder="Filter districts…" style="max-width: 200px;">
            <button type="button" class="bp-btn bp-btn-sm bp-btn-danger" id="clearAll">Clear</button>
            <button type="button" class="bp-btn bp-btn-sm bp-btn-success" id="selectAllVisible">Select all
                visible</button>
        </div>
    </div>
    <div class="bp-card-body">
        <div class="fs-12 text-muted mb-2">
            Each district can belong to only one zone. Districts already in another zone are <span
                class="text-danger">disabled</span> below — edit that zone to move them.
        </div>
        @error('districts')
            <div class="alert alert-danger fs-13">{{ $message }}</div>
        @enderror
        <div class="row g-2" id="districtsList">
            @php
                $oldSelected = old('districts', $selectedIds ?? []);
            @endphp
            @foreach ($districts as $d)
                @php
                    $isAssignedElsewhere = in_array($d->id, $assignedDistrictIds);
                    $isChecked = in_array($d->id, $oldSelected);
                @endphp
                <div class="col-md-3 col-sm-4 col-6 district-item" data-name="{{ strtolower($d->district_name) }}">
                    <label
                        class="form-check-label d-flex align-items-center gap-2 {{ $isAssignedElsewhere ? 'text-muted' : '' }}">
                        <input type="checkbox" class="form-check-input district-checkbox" name="districts[]"
                            value="{{ $d->id }}" {{ $isChecked ? 'checked' : '' }}
                            {{ $isAssignedElsewhere ? 'disabled' : '' }}>
                        <span>{{ $d->district_name }}</span>
                        @if ($isAssignedElsewhere)
                            <i class="fa-solid fa-lock fs-11"></i>
                        @endif
                    </label>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="mt-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        @if(isset($zone) && $zone->exists)
            {{-- Submits the separate delete form (see shipping-edit) via the form
                 attribute, so all three actions line up on one row. --}}
            <button type="submit" form="zoneDeleteForm" class="bp-btn bp-btn-danger"><i
                    class="fa-solid fa-trash me-1"></i>Delete Zone</button>
        @endif
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('ecommerce.shipping') }}" class="bp-btn bp-btn-danger"><i
                class="fa-solid fa-xmark me-1"></i>Cancel</a>
        <button type="submit" class="bp-btn bp-btn-success"><i
                class="fa-solid fa-check me-1"></i>{{ isset($zone) && $zone->exists ? 'Update Zone' : 'Save Zone' }}</button>
    </div>
</div>
