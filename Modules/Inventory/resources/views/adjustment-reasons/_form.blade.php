{{-- Shared adjustment-reason form fields. $reason may be null (create). --}}
<div class="row g-3">
    <div class="col-md-6">
        <label class="bp-form-label">Name *</label>
        <input type="text" class="bp-form-control @error('name') is-invalid @enderror" name="name"
            value="{{ old('name', $reason->name ?? '') }}" required placeholder="e.g. Damage, Expired, Stock Count">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="fs-12 text-muted mt-1">Whether stock goes up or down is chosen on the adjustment itself, so the same
            reason can be used either way.</div>
    </div>

    <div class="col-md-3">
        <label class="bp-form-label">Sort Order</label>
        <input type="number" class="bp-form-control" name="sort_order"
            value="{{ old('sort_order', $reason->sort_order ?? 0) }}" min="0">
    </div>

    <div class="col-md-3">
        <label class="bp-form-label">Status</label>
        <select class="bp-form-select w-100" name="is_active">
            <option value="1" {{ old('is_active', ($reason->is_active ?? true) ? '1' : '0') == '1' ? 'selected' : '' }}>Active</option>
            <option value="0" {{ old('is_active', ($reason->is_active ?? true) ? '1' : '0') === '0' ? 'selected' : '' }}>Inactive</option>
        </select>
    </div>
</div>
