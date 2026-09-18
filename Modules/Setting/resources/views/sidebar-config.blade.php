@extends('core::layouts.master')

@section('title', __("Sidebar Configuration"))
@section('page-title', __("Sidebar Menu Configuration"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('settings.index') }}">Settings</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Sidebar</span>
@endsection

@section('content')

<form action="{{ route('settings.sidebar.save') }}" method="POST">
  @csrf

  <div class="bp-card">
    <div class="bp-card-header">
      <h5 class="bp-card-title"><i class="fa-solid fa-bars me-2"></i>Toggle Sidebar Menu Items</h5>
    </div>
    <div class="bp-card-body">
      <p class="text-muted fs-13 mb-4">Enable or disable sidebar sections. Dashboard and Settings are always visible. Disabled items are hidden for all users.</p>

      <div class="row g-4">
        @foreach($menuItems as $section => $items)
          <div class="col-md-4 col-sm-6">
            <h6 class="fw-800 text-uppercase fs-11 text-muted mb-2 border-bottom pb-2">{{ $section }}</h6>
            @foreach($items as $key => $label)
              @php
                $isEnabled = ($sidebarSettings[$key] ?? '1') === '1';
              @endphp
              <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" name="sidebar[]" value="{{ $key }}"
                       id="sb_{{ $key }}" {{ $isEnabled ? 'checked' : '' }}>
                <label class="form-check-label fw-600 fs-13" for="sb_{{ $key }}">{{ $label }}</label>
              </div>
            @endforeach
          </div>
        @endforeach
      </div>
    </div>
    <div class="bp-card-footer text-end">
      <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-save me-1"></i> Save Configuration</button>
    </div>
  </div>

</form>

@endsection
