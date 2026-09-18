{{-- Bulk actions bar — hidden until items are selected --}}
@props(['id' => 'bulkActionsBar'])

<div class="bp-bulk-actions" id="{{ $id }}" hidden>
    <div class="d-flex align-items-center gap-2 px-3 py-2">
        <span class="bp-bulk-count fw-600 fs-13">
            <span class="count">0</span> selected
        </span>
        <div class="d-flex gap-2 ms-auto">
            {{ $slot }}
        </div>
    </div>
</div>
