@extends('core::layouts.master')

@section('title', __("Ad Platforms"))
@section('page-title', __("Ad Platforms"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('adspend.index') }}">Ad Spend</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Platforms</span>
@endsection

@section('page-actions')
@bpCan('marketing.create')
    <button class="bp-btn bp-btn-primary" data-bs-toggle="modal" data-bs-target="#addPlatformModal"><i class="fa-solid fa-plus me-1"></i> Add Platform</button>
@endbpCan
@endsection

@section('content')

<div class="bp-card">
  <div class="bp-card-body p-0">
    <div class="bp-table-wrapper">
      <table class="bp-table">
        <thead>
          <tr>
            <th>Icon</th>
            <th>Name</th>
            <th class="text-end">Total Spent</th>
            <th class="text-end">Campaigns</th>
            <th>Status</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach($platforms as $p)
          <tr>
            <td><i class="{{ $p->icon }}" style="color: {{ $p->color }}; font-size: 20px"></i></td>
            <td class="fw-700">{{ $p->name }}</td>
            <td class="text-end fw-700">{{ currency_symbol() }} {{ number_format($p->total_spent, 0) }}</td>
            <td class="text-end">{{ $p->campaigns()->count() }}</td>
            <td>
              <x-core::status-toggle :url="route('adspend.platforms.toggle-status', $p->id)" :active="$p->is_active" />
            </td>
            <td class="text-end">
              @bpCan('marketing.delete')
                <div class="dropdown">
                  <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                      <form action="{{ route('adspend.platforms.destroy', $p) }}" method="POST">
                        @csrf @method('DELETE')
                        <button type="submit" class="dropdown-item text-danger delete-confirm" data-name="{{ $p->name }}"><i class="fa-solid fa-trash me-2"></i> Delete</button>
                      </form>
                    </li>
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

<!-- Add Platform Modal -->
<div class="modal fade" id="addPlatformModal" tabindex="-1">
  <div class="modal-dialog">
    <form action="{{ route('adspend.platforms.store') }}" method="POST">
      @csrf
      <div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Add Platform</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="bp-form-label">Name *</label>
            <input type="text" class="bp-form-control" name="name" required placeholder="e.g. Reddit">
          </div>
          <div class="mb-3">
            <label class="bp-form-label">Icon Class</label>
            <input type="text" class="bp-form-control" name="icon" value="fa-solid fa-globe" placeholder="fa-brands fa-reddit">
            <div class="fs-11 text-muted mt-1">FontAwesome class. Preview: <i id="iconPreview" class="fa-solid fa-globe ms-1"></i></div>
          </div>
          <div class="mb-3">
            <label class="bp-form-label">Color</label>
            <input type="color" class="form-control form-control-color" name="color" value="#6C757D">
          </div>
          <div class="mb-3">
            <label class="bp-form-label">Sort Order</label>
            <input type="number" class="bp-form-control" name="sort_order" value="50" min="0">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="bp-btn bp-btn-danger" data-bs-dismiss="modal"><i class="fa-solid fa-xmark me-1"></i>Cancel</button>
          <button type="submit" class="bp-btn bp-btn-primary">Add Platform</button>
        </div>
      </div>
    </form>
  </div>
</div>

@endsection

@push('scripts')
<script>
'use strict';
$(function () {
    $('input[name="icon"]').on('input', function () {
        $('#iconPreview').attr('class', $(this).val());
    });
});
</script>
@endpush
