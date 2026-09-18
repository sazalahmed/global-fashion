{{-- Sortable table column header --}}
@props(['sortable' => false, 'field' => null, 'align' => 'left', 'width' => null])

@php
    $currentSort = request('sort', '');
    $currentDirection = request('direction', 'asc');
    $isActive = $sortable && $field && $currentSort === $field;
    $nextDirection = ($isActive && $currentDirection === 'asc') ? 'desc' : 'asc';
    $sortUrl = $sortable && $field
        ? request()->fullUrlWithQuery(['sort' => $field, 'direction' => $nextDirection])
        : null;

    // Bootstrap 5 dropped .text-right / .text-left in favour of RTL-aware
    // .text-end / .text-start. Translate the prop so old call-sites keep working.
    $alignClassMap = ['left' => 'text-start', 'right' => 'text-end', 'center' => 'text-center'];
    $classes = trim(
        ($width ? 'bp-th-w-' . $width : '') . ' ' .
        ($align !== 'left' ? ($alignClassMap[$align] ?? '') : '')
    );
@endphp

<th
    @if($classes) class="{{ $classes }}" @endif
    {{ $attributes }}
>
    @if($sortable && $field)
        <a href="{{ $sortUrl }}" class="bp-sort-link {{ $isActive ? 'active' : '' }}">
            {{ $slot }}
            <i class="fa-solid {{ $isActive ? ($currentDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort' }} ms-1"></i>
        </a>
    @else
        {{ $slot }}
    @endif
</th>
