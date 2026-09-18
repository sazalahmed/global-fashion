{{-- Shared borrower form fields. $borrower may be null (create). --}}
<div class="row g-3">
    <div class="col-12">
        <h5 class="fw-800 text-capitalize fs-12 mb-0">Borrower Information</h5>
    </div>
    <div class="col-12 category_img">
        <x-core::image-upload name="photo" label="Photo" accept="image/jpg,image/jpeg,image/png,image/webp"
            :current="!empty($borrower?->photo) ? upload_url($borrower->photo) : null" />
        @error('photo')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6">
        <label class="bp-form-label">Name *</label>
        <input type="text" class="bp-form-control @error('name') is-invalid @enderror" name="name"
            value="{{ old('name', $borrower->name ?? '') }}" required placeholder="Borrower name">
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6">
        <label class="bp-form-label">Phone</label>
        <input type="text" class="bp-form-control @error('phone') is-invalid @enderror" name="phone"
            value="{{ old('phone', $borrower->phone ?? '') }}" data-phone placeholder="Phone number">
        @error('phone')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6">
        <label class="bp-form-label">Email</label>
        <input type="email" class="bp-form-control @error('email') is-invalid @enderror" name="email"
            value="{{ old('email', $borrower->email ?? '') }}" placeholder="name@email.com">
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6">
        <label class="bp-form-label">Address</label>
        <input type="text" class="bp-form-control @error('address') is-invalid @enderror" name="address"
            value="{{ old('address', $borrower->address ?? '') }}" placeholder="Address">
        @error('address')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <h5 class="fw-800 text-capitalize fs-12 mb-0">Financial</h5>
    </div>
    @php($openingSigned = (float) old('opening_balance', $borrower->opening_balance ?? 0))
    @php($openingType = old('opening_type', $openingSigned < 0 ? 'we_owe' : 'they_owe'))
    <div class="col-md-4">
        <label class="bp-form-label">Opening Balance ({{ currency_symbol() }})</label>
        <input type="number" step="0.01" min="0"
            class="bp-form-control @error('opening_balance') is-invalid @enderror" name="opening_balance"
            value="{{ num_input(abs($openingSigned)) }}" placeholder="0.00">
        <small class="text-muted fs-11">Balance carried forward from before this record</small>
        @error('opening_balance')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-4">
        <label class="bp-form-label">Opening Balance Side</label>
        <select class="bp-form-select w-100 @error('opening_type') is-invalid @enderror" name="opening_type">
            <option value="they_owe" {{ $openingType === 'they_owe' ? 'selected' : '' }}>{{ __('They owe us') }}
            </option>
            <option value="we_owe" {{ $openingType === 'we_owe' ? 'selected' : '' }}>{{ __('We owe them') }}</option>
        </select>
        @error('opening_type')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-4">
        <label class="bp-form-label">Status</label>
        <select class="bp-form-select w-100 @error('status') is-invalid @enderror" name="status">
            <option value="active" {{ old('status', $borrower->status ?? 'active') === 'active' ? 'selected' : '' }}>
                Active</option>
            <option value="inactive" {{ old('status', $borrower->status ?? '') === 'inactive' ? 'selected' : '' }}>
                Inactive</option>
        </select>
        @error('status')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label class="bp-form-label">Notes</label>
        <textarea class="bp-form-control @error('notes') is-invalid @enderror" name="notes" rows="2"
            placeholder="Internal notes...">{{ old('notes', $borrower->notes ?? '') }}</textarea>
        @error('notes')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>
