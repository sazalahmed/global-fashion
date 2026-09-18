@php
    /** @var \Illuminate\Support\Collection $categories root categories with nested children */
    $flattenCategoryTree = function ($items, $depth = 0) use (&$flattenCategoryTree) {
        $out = [];
        foreach ($items as $item) {
            $out[] = ['id' => $item->id, 'name' => $item->name, 'depth' => $depth];
            $children = $item->children ?? collect();
            if (count($children)) {
                $out = array_merge($out, $flattenCategoryTree($children, $depth + 1));
            }
        }
        return $out;
    };
    $flatCategories = $flattenCategoryTree($categories ?? collect());
    $selectedCategoryIds = collect($selectedCategoryIds ?? [])->map(fn ($v) => (int) $v)->all();
@endphp

<div class="bp-card mb-4" id="comboCategoriesCard">
    <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-tags me-2 text-primary"></i>Categories</h5>
        <button type="button" class="bp-btn bp-btn-sm bp-btn-icon bp-btn-outline" id="comboAddCategoryBtn"
                title="Add new category"><i class="fa-solid fa-plus"></i></button>
    </div>
    <div class="bp-card-body">
        <div class="bp-cat-search mb-2">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" class="bp-form-control" id="comboCategorySearch" placeholder="Search categories...">
        </div>
        <div class="bp-cat-list" id="comboCategoryList">
            @forelse ($flatCategories as $cat)
                <label class="bp-cat-item bp-cat-depth-{{ $cat['depth'] }}"
                       data-name="{{ \Illuminate\Support\Str::lower($cat['name']) }}"
                       data-depth="{{ $cat['depth'] }}"
                       style="--depth: {{ $cat['depth'] }};">
                    <input type="checkbox" name="categories[]" value="{{ $cat['id'] }}" class="cat-checkbox"
                           {{ in_array($cat['id'], $selectedCategoryIds, true) ? 'checked' : '' }}>
                    <span class="bp-check-box"></span>
                    <span class="bp-cat-label">{{ $cat['name'] }}</span>
                </label>
            @empty
                <div class="text-muted fs-12 py-2">No categories yet. Create categories first.</div>
            @endforelse
            <div class="bp-cat-empty text-center text-muted fs-12 py-3 d-none" id="comboCategoryEmpty">No matching categories.</div>
        </div>
        @error('categories')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        <div class="fs-11 text-muted mt-2"><i class="fa-solid fa-circle-info me-1"></i> A combo can belong to multiple categories.</div>
    </div>
</div>

<div class="modal fade" id="comboQuickCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-800"><i class="fa-solid fa-folder-plus me-2"></i>New Category</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="bp-form-label">Category Name *</label>
                <input type="text" class="bp-form-control" id="comboQuickCategoryName" placeholder="e.g., Eid Bundles" maxlength="255">
                <div class="invalid-feedback" id="comboQuickCategoryError"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="bp-btn bp-btn-danger bp-btn-sm" data-bs-dismiss="modal"><i class="fa-solid fa-xmark me-1"></i>Cancel</button>
                <button type="button" class="bp-btn bp-btn-success bp-btn-sm" id="comboQuickCategorySave"><i class="fa-solid fa-check me-1"></i>Save</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
'use strict';
$(function () {
    if (window.__comboCatSearchBound) { return; }
    window.__comboCatSearchBound = true;
    $(document).on('input', '#comboCategorySearch', function () {
        var q = $(this).val().toString().toLowerCase().trim();
        var anyVisible = false;
        $('#comboCategoryList .bp-cat-item').each(function () {
            var match = $(this).attr('data-name').indexOf(q) !== -1;
            $(this).toggleClass('d-none', q !== '' && !match);
            if (match) { anyVisible = true; }
        });
        $('#comboCategoryEmpty').toggleClass('d-none', anyVisible);
    });

    // Selecting a sub/child category auto-selects its parent chain. In the flat
    // pre-order list a node's parent is the nearest preceding row at depth-1.
    function selectComboCategoryAncestors(checkbox) {
        var item = checkbox.closest('.bp-cat-item');
        if (!item) { return; }
        var depth = parseInt(item.getAttribute('data-depth'), 10) || 0;
        var prev = item.previousElementSibling;
        while (prev && depth > 0) {
            if (prev.classList.contains('bp-cat-item')) {
                var prevDepth = parseInt(prev.getAttribute('data-depth'), 10) || 0;
                if (prevDepth === depth - 1) {
                    var cb = prev.querySelector('.cat-checkbox');
                    if (cb && !cb.checked) { cb.checked = true; }
                    depth = prevDepth;
                }
            }
            prev = prev.previousElementSibling;
        }
    }

    $(document).on('change', '#comboCategoryList .cat-checkbox', function () {
        if (this.checked) { selectComboCategoryAncestors(this); }
    });

    // On load, reflect saved selections up the tree: a combo saved with only a
    // child category should still show (and re-save) its parent chain checked.
    $('#comboCategoryList .cat-checkbox:checked').each(function () {
        selectComboCategoryAncestors(this);
    });

    $(document).on('click', '#comboAddCategoryBtn', function () {
        $('#comboQuickCategoryName').val('').removeClass('is-invalid');
        $('#comboQuickCategoryError').text('');
        var modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('comboQuickCategoryModal'));
        modal.show();
        setTimeout(function () { $('#comboQuickCategoryName').trigger('focus'); }, 300);
    });

    $(document).on('click', '#comboQuickCategorySave', function () {
        var name = $('#comboQuickCategoryName').val().toString().trim();
        if (!name) {
            $('#comboQuickCategoryName').addClass('is-invalid');
            $('#comboQuickCategoryError').text('Category name is required.');
            return;
        }
        var $btn = $(this).prop('disabled', true);
        $.post('{{ route('categories.quick-store') }}', { name: name }, function (data) {
            var $label = $('<label class="bp-cat-item bp-cat-depth-0" data-name="' +
                (data.name || '').toLowerCase() + '" data-depth="0" style="--depth: 0;"></label>');
            $('<input type="checkbox" name="categories[]" class="cat-checkbox" checked>').val(data.id).appendTo($label);
            $('<span class="bp-check-box"></span>').appendTo($label);
            $('<span class="bp-cat-label"></span>').text(data.name).appendTo($label);
            $('#comboCategoryEmpty').before($label);
            bootstrap.Modal.getInstance(document.getElementById('comboQuickCategoryModal')).hide();
        }).fail(function (xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to create category.';
            $('#comboQuickCategoryName').addClass('is-invalid');
            $('#comboQuickCategoryError').text(msg);
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });
});
</script>
@endpush
