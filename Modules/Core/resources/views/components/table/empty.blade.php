{{-- Empty state row. Renders its own <tr><td colspan> — use it directly inside
     <tbody>, NOT wrapped in another <tr><td>. Supports a single `message` or a
     `title` + optional `description`. `icon` accepts a full class
     ("fa-solid fa-users") or a bare name ("fa-users"). Any slot content (e.g. a
     call-to-action button) renders centered below the text. --}}
@props([
    'colspan' => 1,
    'icon' => 'fa-inbox',
    'message' => null,
    'title' => null,
    'description' => null,
])
@php
    $iconClass = \Illuminate\Support\Str::contains($icon, ['fa-solid', 'fa-regular', 'fa-light', 'fa-brands', 'fas', 'far', 'fab'])
        ? $icon
        : 'fa-solid ' . $icon;
    $heading = $title ?? $message ?? 'No records found';
@endphp

<tr>
    <td colspan="{{ $colspan }}" class="text-center bp-table-empty">
        <div class="bp-empty-state">
            <i class="{{ $iconClass }}"></i>
            <p class="mb-0">{{ $heading }}</p>
            @if ($description)
                <p class="fs-12 text-muted mb-0 mt-1">{{ $description }}</p>
            @endif
            @if (trim($slot) !== '')
                <div class="mt-3">{{ $slot }}</div>
            @endif
        </div>
    </td>
</tr>
