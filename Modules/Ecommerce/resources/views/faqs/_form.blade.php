<div class="bp-card">
    <div class="bp-card-body">
        <div class="row g-3">
            <div class="col-12">
                <label class="bp-form-label">{{ __('Question') }} *</label>
                <input type="text" name="question" class="bp-form-control @error('question') is-invalid @enderror"
                    value="{{ old('question', $faq->question ?? '') }}" required>
                @error('question')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-12">
                <label class="bp-form-label">{{ __('Answer') }} *</label>
                <textarea name="answer" rows="6" class="bp-form-control bp-richtext @error('answer') is-invalid @enderror" required>{{ old('answer', $faq->answer ?? '') }}</textarea>
                @error('answer')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-12">
                <div class="form-check form-switch">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" class="form-check-input"
                        {{ old('is_active', $faq->is_active ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label">{{ __('Active') }}</label>
                </div>
            </div>
        </div>
    </div>
    <div class="bp-card-footer text-end">
        <a href="{{ route('ecommerce.faqs.index') }}" class="bp-btn bp-btn-danger">{{ __('Cancel') }}</a>
        <button type="submit" class="bp-btn bp-btn-success"><i
                class="fa-solid fa-save me-1"></i>{{ __('Save') }}</button>
    </div>
</div>
