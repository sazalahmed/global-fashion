@extends('core::layouts.master')

@section('title', 'Search Results')
@section('page-title', 'Search Results')
@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Search</span>
@endsection

@section('content')
<div class="bp-card mb-3">
  <div class="bp-card-body">
    <form method="GET" action="{{ route('search.results') }}" class="bp-search-results-form">
      <div class="bp-table-search w-100">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text"
               name="q"
               value="{{ $query }}"
               class="bp-form-control"
               placeholder="Search anything..."
               autocomplete="off"
               autofocus>
        @if($activeType)
          <input type="hidden" name="type" value="{{ $activeType }}">
        @endif
      </div>
    </form>

    @if($query !== '')
      <div class="bp-search-results-filters">
        <a href="{{ route('search.results', ['q' => $query]) }}"
           class="bp-search-chip {{ $activeType === null ? 'active' : '' }}">
          <i class="fa-solid fa-border-all"></i> All
        </a>
        @foreach($types as $type => $icon)
          <a href="{{ route('search.results', ['q' => $query, 'type' => $type]) }}"
             class="bp-search-chip {{ $activeType === $type ? 'active' : '' }}">
            <i class="fa-solid {{ $icon }}"></i> {{ $type }}
          </a>
        @endforeach
      </div>
    @endif
  </div>
</div>

@if($query === '')
  <div class="bp-card">
    <div class="bp-card-body">
      <div class="bp-search-results-empty">
        <i class="fa-solid fa-magnifying-glass"></i>
        <div>Type a search term above to find products, sales, customers and more.</div>
      </div>
    </div>
  </div>
@elseif($totalCount === 0)
  <div class="bp-card">
    <div class="bp-card-body">
      <div class="bp-search-results-empty">
        <i class="fa-solid fa-circle-info"></i>
        <div>No results found for "<strong>{{ $query }}</strong>".</div>
      </div>
    </div>
  </div>
@else
  <div class="bp-search-results-meta fs-13 text-muted mb-2">
    Found <strong>{{ $totalCount }}</strong> result{{ $totalCount === 1 ? '' : 's' }} for "<strong>{{ $query }}</strong>"
  </div>

  @if(count($navigation) > 0)
    <div class="bp-card mb-3">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-compass me-2"></i>Pages <span class="bp-badge bp-badge-secondary ms-1">{{ count($navigation) }}</span></h5>
      </div>
      <div class="bp-card-body p-0">
        @foreach($navigation as $item)
          <a href="{{ $item['url'] }}" class="bp-search-result-row">
            <div class="bp-search-result-icon"><i class="fa-solid {{ $item['icon'] }}"></i></div>
            <div class="bp-search-result-content">
              <div class="bp-search-result-title">{!! $item['title'] !!}</div>
              <div class="bp-search-result-subtitle">{{ $item['subtitle'] }}</div>
            </div>
            <span class="bp-badge bp-badge-secondary">Page</span>
          </a>
        @endforeach
      </div>
    </div>
  @endif

  @foreach($groups as $group)
    <div class="bp-card mb-3">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid {{ $group['icon'] }} me-2"></i>{{ $group['type'] }} <span class="bp-badge bp-badge-primary ms-1">{{ $group['count'] }}</span></h5>
      </div>
      <div class="bp-card-body p-0">
        @foreach($group['items'] as $item)
          <a href="{{ $item['url'] }}" class="bp-search-result-row">
            <div class="bp-search-result-icon"><i class="fa-solid {{ $item['icon'] }}"></i></div>
            <div class="bp-search-result-content">
              <div class="bp-search-result-title">{!! $item['title'] !!}</div>
              <div class="bp-search-result-subtitle">{!! $item['subtitle'] !!}</div>
            </div>
            <span class="bp-badge bp-badge-primary">{{ $item['type'] }}</span>
          </a>
        @endforeach
      </div>
    </div>
  @endforeach
@endif
@endsection
