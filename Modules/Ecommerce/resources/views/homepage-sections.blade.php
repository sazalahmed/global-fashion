@extends('core::layouts.master')

@section('title', __("Manage Sections — Website"))
@section('page-title', __("Manage Sections"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('ecommerce.index') }}">Website</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Manage Sections</span>
@endsection

@section('content')

<div class="row g-4">
  <div class="col-xl-8">

    <div class="bp-card">
      <div class="bp-card-header d-flex justify-content-between align-items-center">
        <h5 class="bp-card-title"><i class="fa-solid fa-layer-group me-2"></i>Homepage Sections</h5>
        <span class="fs-12 text-muted"><i class="fa-solid fa-arrows-up-down me-1"></i>Drag to reorder</span>
      </div>
      <div class="bp-card-body p-0">
        <div class="bp-table-wrapper">
          <table class="bp-table">
            <thead>
              <tr>
                <th class="bp-reorder-col" title="Drag to reorder">&nbsp;</th>
                <th>Section</th>
                <th>Title</th>
                <th class="text-center">Active</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody class="bp-reorderable" data-reorder-url="{{ route('ecommerce.homepage-sections.reorder') }}">
              @foreach($sections as $section)
              <tr data-id="{{ $section->id }}">
                <td class="bp-drag-handle" title="Drag to reorder"><i class="fa-solid fa-grip-vertical"></i></td>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid {{ $section->section_type === 'hero_slider' ? 'fa-desktop' : ($section->section_type === 'flash_deals' ? 'fa-bolt' : ($section->section_type === 'categories' ? 'fa-th-large' : ($section->section_type === 'new_arrivals' ? 'fa-sparkles' : ($section->section_type === 'best_selling' ? 'fa-fire' : ($section->section_type === 'brands' ? 'fa-tags' : ($section->section_type === 'blog' ? 'fa-newspaper' : ($section->section_type === 'newsletter' ? 'fa-envelope' : ($section->section_type === 'promo_banners' ? 'fa-rectangle-ad' : 'fa-puzzle-piece')))))))) }} text-muted"></i>
                    <code class="fs-12">{{ $section->section_type }}</code>
                  </div>
                </td>
                <td>{{ $section->title }}</td>
                <td class="text-center">
                  @bpCan('ecommerce.edit')
                  <div class="form-check form-switch d-flex justify-content-center">
                    <input class="form-check-input hs-toggle" type="checkbox"
                           data-toggle-url="{{ route('ecommerce.homepage-sections.toggle', $section) }}"
                           {{ $section->is_active ? 'checked' : '' }}>
                  </div>
                  @endbpCan
                </td>
                <td>
                  @bpCan('ecommerce.edit')
                  <div class="dropdown">
                    <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                    <ul class="dropdown-menu dropdown-menu-end">
                      <li><a class="dropdown-item" href="{{ route('ecommerce.homepage-sections.settings', $section) }}"><i class="fa-solid fa-gear me-2"></i> Settings</a></li>
                      @if($section->isProductSection())
                      <li><a class="dropdown-item" href="{{ route('ecommerce.homepage-sections.products', $section) }}"><i class="fa-solid fa-boxes-stacked me-2"></i> Manage Products &amp; Combos</a></li>
                      @endif
                      @if($section->isComboSection())
                      <li><a class="dropdown-item" href="{{ route('ecommerce.homepage-sections.combos', $section) }}"><i class="fa-solid fa-box-open me-2"></i> Manage Combos</a></li>
                      @endif
                    </ul>
                  </div>
                  @endbpCan
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>

  <div class="col-xl-4">
    <div class="bp-card">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-circle-info me-2"></i>How It Works</h5>
      </div>
      <div class="bp-card-body">
        <ul class="list-unstyled mb-0 fs-13">
          <li class="mb-2"><i class="fa-solid fa-up-down-left-right text-muted me-2"></i><strong>Drag</strong> a row by its handle to reorder — the new order saves automatically</li>
          <li class="mb-2"><i class="fa-solid fa-toggle-on text-muted me-2"></i>Toggle <strong>Active</strong> to show/hide a section instantly</li>
          <li class="mb-2"><i class="fa-solid fa-gear text-muted me-2"></i>Click <strong>Settings</strong> to customize each section</li>
          <li class="mb-2"><i class="fa-solid fa-boxes-stacked text-muted me-2"></i>Click <strong>Manage Products &amp; Combos</strong> (where available) to curate a section's products and combo packages</li>
        </ul>
      </div>
    </div>

    <div class="bp-card mt-4">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-link me-2"></i>Quick Links</h5>
      </div>
      <div class="bp-card-body">
        <div class="d-flex flex-column gap-2">
          <a href="{{ route('ecommerce.banners') }}" class="bp-btn bp-btn-sm bp-btn-outline w-100">
            <i class="fa-solid fa-images me-2"></i> Manage Banners
          </a>
          <a href="{{ route('ecommerce.collections') }}" class="bp-btn bp-btn-sm bp-btn-outline w-100">
            <i class="fa-solid fa-layer-group me-2"></i> Manage Collections
          </a>
          <a href="{{ route('ecommerce.flash-deals') }}" class="bp-btn bp-btn-sm bp-btn-outline w-100">
            <i class="fa-solid fa-bolt me-2"></i> Manage Flash Deals
          </a>
          <a href="{{ route('ecommerce.blog') }}" class="bp-btn bp-btn-sm bp-btn-outline w-100">
            <i class="fa-solid fa-newspaper me-2"></i> Manage Blog
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script>
'use strict';
// Active toggle — persists instantly via AJAX (CSRF header is set globally).
$(document).on('change', '.hs-toggle', function () {
    var $cb = $(this);
    var desired = $cb.is(':checked');
    $cb.prop('disabled', true);
    $.post($cb.data('toggle-url'))
        .fail(function () { $cb.prop('checked', !desired); })
        .always(function () { $cb.prop('disabled', false); });
});
</script>
@endpush
