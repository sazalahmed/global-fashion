@extends('core::layouts.master')

@section('title', __("Campaigns — Website"))
@section('page-title', __("Campaigns"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('ecommerce.index') }}">Website</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Campaigns</span>
@endsection

@section('page-actions')
@bpCan('ecommerce.create')
<a href="{{ route('ecommerce.campaigns.create') }}" class="bp-btn bp-btn-primary">
  <i class="fa-solid fa-plus"></i> {{ __('New Campaign') }}
</a>
@endbpCan
@endsection

@section('content')

<div class="bp-filter-bar mb-3">
  <form method="GET" action="{{ route('ecommerce.campaigns.index') }}" class="d-flex gap-2 flex-wrap">
    @foreach(['' => 'All', 'active' => 'Active', 'scheduled' => 'Scheduled', 'expired' => 'Expired', 'inactive' => 'Disabled'] as $k => $label)
      <button type="submit" name="status" value="{{ $k }}"
              class="bp-btn bp-btn-sm {{ ($status ?? '') === $k ? 'bp-btn-primary' : 'bp-btn-outline' }}">
        {{ $label }}
      </button>
    @endforeach
  </form>
</div>

<x-core::table>
  <x-core::table.header>
    <x-core::table.column>{{ __('Campaign') }}</x-core::table.column>
    <x-core::table.column>{{ __('Scope') }}</x-core::table.column>
    <x-core::table.column>{{ __('Discount') }}</x-core::table.column>
    <x-core::table.column>{{ __('Starts') }}</x-core::table.column>
    <x-core::table.column>{{ __('Ends') }}</x-core::table.column>
    <x-core::table.column>{{ __('Status') }}</x-core::table.column>
    <x-core::table.column align="right">{{ __('Actions') }}</x-core::table.column>
  </x-core::table.header>

  <tbody>
    @forelse($campaigns as $c)
      @php
        $now = now();
        $isLive = $c->is_active && $c->starts_at <= $now && $c->ends_at >= $now;
        $isScheduled = $c->is_active && $c->starts_at > $now;
        $isExpired = $c->ends_at < $now;
      @endphp
      <tr>
        <td>
          <div class="fw-700">{{ $c->name }}</div>
          <div class="fs-11 text-muted">/{{ $c->slug }}</div>
        </td>
        <td>
          @switch($c->scope)
            @case('all')        <span class="bp-badge bp-badge-primary">All Products</span> @break
            @case('categories') <span class="bp-badge bp-badge-info">{{ $c->categories->count() }} {{ Str::plural('Category', $c->categories->count()) }}</span> @break
            @case('products')   <span class="bp-badge bp-badge-secondary">{{ $c->products->count() }} {{ Str::plural('Product', $c->products->count()) }}</span> @break
          @endswitch
        </td>
        <td class="fw-700">
          @if($c->discount_type === 'percentage')
            {{ rtrim(rtrim(num($c->discount_value), '0'), '.') }}%
          @else
            {{ money($c->discount_value) }}
          @endif
        </td>
        <td>{{ $c->starts_at->format('d M Y') }}</td>
        <td>{{ $c->ends_at->format('d M Y') }}</td>
        <td>
          @if(!$c->is_active)
            <span class="bp-badge bp-badge-dark">Disabled</span>
          @elseif($isLive)
            <span class="bp-badge bp-badge-success">Active</span>
          @elseif($isScheduled)
            <span class="bp-badge bp-badge-warning">Scheduled</span>
          @elseif($isExpired)
            <span class="bp-badge bp-badge-danger">Expired</span>
          @endif
        </td>
        <td class="text-end">
          @bpCanAny('ecommerce.edit','ecommerce.delete')
          <div class="dropdown">
            <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
            <ul class="dropdown-menu dropdown-menu-end">
              @bpCan('ecommerce.edit')
              <li><a class="dropdown-item" href="{{ route('ecommerce.campaigns.edit', $c) }}"><i class="fa-solid fa-pen me-2"></i> {{ __('Edit') }}</a></li>
              @endbpCan
              @bpCan('ecommerce.delete')
              <li>
                <form action="{{ route('ecommerce.campaigns.destroy', $c) }}" method="POST" onsubmit="return confirm('Delete this campaign?');">
                  @csrf @method('DELETE')
                  <button type="submit" class="dropdown-item text-danger"><i class="fa-solid fa-trash me-2"></i> {{ __('Delete') }}</button>
                </form>
              </li>
              @endbpCan
            </ul>
          </div>
          @endbpCanAny
        </td>
      </tr>
    @empty
      <x-core::table.empty colspan="7" icon="fa-solid fa-bullhorn" :title="__('No campaigns yet')" />
    @endforelse
  </tbody>

  <x-slot:pagination>
    <div class="bp-card-footer">
      <div class="bp-pagination">
        <span class="page-info">{{ __('Showing') }} {{ $campaigns->firstItem() ?? 0 }}-{{ $campaigns->lastItem() ?? 0 }} {{ __('of') }} {{ number_format($campaigns->total()) }}</span>
        <nav>{{ $campaigns->appends(request()->query())->onEachSide(1)->links('core::components.table.pagination-links') }}</nav>
      </div>
    </div>
  </x-slot:pagination>
</x-core::table>

@endsection
