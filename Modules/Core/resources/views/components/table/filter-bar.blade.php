{{-- Filter bar with search + filters + reset --}}
@props(['action' => null, 'method' => 'GET', 'searchPlaceholder' => 'Search...', 'searchName' => 'search'])

<form
    @if($action) action="{{ $action }}" @endif
    method="{{ $method }}"
    class="bp-filter-bar"
    id="filterBarForm"
    {{ $attributes }}
>
    <div class="bp-table-search">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" name="{{ $searchName }}" value="{{ request($searchName) }}" placeholder="{{ $searchPlaceholder }}">
    </div>

    {{ $slot }}

    <button type="submit" class="bp-btn bp-btn-sm bp-btn-primary bp-filter-submit" title="Apply Filters">
        <i class="fa-solid fa-filter"></i>
    </button>
    <button type="button" class="bp-btn bp-btn-sm bp-btn-danger bp-filter-reset" title="Reset Filters">
        <i class="fa-solid fa-rotate"></i>
    </button>
</form>
