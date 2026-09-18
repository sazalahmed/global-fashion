{{-- Table header: <thead> with optional select-all checkbox --}}
@props(['selectable' => false])

<thead>
    <tr>
        @if($selectable)
            <th class="bp-th-checkbox">
                <input type="checkbox" class="form-check-input bp-check-all" title="Select All">
            </th>
        @endif
        {{ $slot }}
    </tr>
</thead>
