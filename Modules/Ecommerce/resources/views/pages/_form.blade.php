<div class="bp-card">
    <div class="bp-card-body">
        <div class="row g-3">
            <div class="col-md-8">
                <label class="bp-form-label">{{ __('Title') }} *</label>
                <input type="text" name="title" id="pageTitleInput" class="bp-form-control @error('title') is-invalid @enderror"
                    value="{{ old('title', $page->title ?? '') }}" required>
                @error('title')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-4">
                <label class="bp-form-label">{{ __('Slug') }}</label>
                <input type="text" name="slug" id="pageSlugInput" class="bp-form-control @error('slug') is-invalid @enderror"
                    value="{{ old('slug', $page->slug ?? '') }}" placeholder="{{ __('auto from title') }}">
                @error('slug')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-12">
                <label class="bp-form-label">{{ __('Content') }}</label>
                <textarea name="content" class="bp-form-control bp-richtext" rows="12">{{ old('content', $page->content ?? '') }}</textarea>
            </div>
            <div class="col-12">
                <div class="form-check form-switch">
                    <input type="hidden" name="is_published" value="0">
                    <input type="checkbox" name="is_published" value="1" class="form-check-input"
                        {{ old('is_published', $page->is_published ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label">{{ __('Published') }}</label>
                </div>
            </div>
        </div>
    </div>
    <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-magnifying-glass me-2"></i>{{ __('SEO') }}</h5>
    </div>
    <div class="bp-card-body">
        <div class="row g-3">
            <div class="col-12">
                <label class="bp-form-label">{{ __('SEO Title') }}</label>
                <input type="text" name="seo_title" class="bp-form-control"
                    value="{{ old('seo_title', $page->seo_title ?? '') }}">
            </div>
            <div class="col-12">
                <label class="bp-form-label">{{ __('SEO Description') }}</label>
                <textarea name="seo_description" rows="2" class="bp-form-control" maxlength="300">{{ old('seo_description', $page->seo_description ?? '') }}</textarea>
            </div>
        </div>
    </div>
    <div class="bp-card-footer text-end">
        <a href="{{ route('ecommerce.pages.index') }}" class="bp-btn bp-btn-danger">{{ __('Cancel') }}</a>
        <button type="submit" class="bp-btn bp-btn-success"><i
                class="fa-solid fa-save me-1"></i>{{ __('Save') }}</button>
    </div>
</div>

@push('scripts')
<script>
'use strict';
$(function () {
    var $title = $('#pageTitleInput');
    var $slug = $('#pageSlugInput');
    if (!$title.length || !$slug.length) { return; }

    // Stop auto-filling once the slug has a value the user set (or an existing
    // slug on the edit form) — typing in the slug marks it as manually owned.
    var slugTouched = $slug.val().trim() !== '';
    $slug.on('input', function () { slugTouched = true; });

    function slugify(value) {
        return value.toString().toLowerCase().trim()
            .replace(/[^a-z0-9]+/g, '-')   // non-alphanumerics → dash
            .replace(/^-+|-+$/g, '');      // trim leading/trailing dashes
    }

    // Live-generate the slug from the title as the user types.
    $title.on('input', function () {
        if (slugTouched) { return; }
        $slug.val(slugify($title.val()));
    });
});
</script>
@endpush
