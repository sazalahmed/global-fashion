@extends('core::layouts.master')

@section('title', __('Requisitions'))
@section('page-title', __('Requisitions'))

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Requisitions</span>
@endsection

@section('page-actions')
  @bpCan('purchases.create')
  <a href="{{ route('requisitions.create') }}" class="bp-btn bp-btn-primary"><i class="fa-solid fa-plus me-1"></i> New Requisition</a>
  @endbpCan
@endsection

@section('content')

<div class="row g-3 mb-4">
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-clipboard-list"></i></div>
      <div class="bp-stat-content"><div class="bp-stat-label">Total</div><div class="bp-stat-value">{{ number_format($stats['total']) }}</div></div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-warning"><i class="fa-solid fa-hourglass-half"></i></div>
      <div class="bp-stat-content"><div class="bp-stat-label">Pending</div><div class="bp-stat-value">{{ number_format($stats['pending']) }}</div></div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-info"><i class="fa-solid fa-circle-check"></i></div>
      <div class="bp-stat-content"><div class="bp-stat-label">Approved</div><div class="bp-stat-value">{{ number_format($stats['approved']) }}</div></div>
    </div>
  </div>
  <div class="col-xl-3 col-sm-6">
    <div class="bp-stat-card">
      <div class="bp-stat-icon icon-success"><i class="fa-solid fa-truck-fast"></i></div>
      <div class="bp-stat-content"><div class="bp-stat-label">Ordered</div><div class="bp-stat-value">{{ number_format($stats['ordered']) }}</div></div>
    </div>
  </div>
</div>

<x-core::table>
  <x-slot:filters>
    <x-core::table.filter-bar searchPlaceholder="Search requisition #...">
      <select class="bp-form-select" name="status">
        <option value="">All Status</option>
        @foreach(['pending','approved','rejected','ordered','fulfilled','cancelled'] as $st)
          <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ ucfirst($st) }} ({{ $statusCounts[$st] ?? 0 }})</option>
        @endforeach
      </select>
    </x-core::table.filter-bar>
  </x-slot:filters>

  <x-core::table.header>
    <x-core::table.column>Requisition #</x-core::table.column>
    <x-core::table.column>Requested By</x-core::table.column>
    <x-core::table.column>Items</x-core::table.column>
    <x-core::table.column>Required Date</x-core::table.column>
    <x-core::table.column>Priority</x-core::table.column>
    <x-core::table.column>Status</x-core::table.column>
    <x-core::table.column>Actions</x-core::table.column>
  </x-core::table.header>

  <tbody>
    @forelse($requisitions as $req)
      @php
        $statusClass = match($req->status) {
          'approved' => 'info', 'ordered' => 'primary', 'fulfilled' => 'success',
          'rejected' => 'danger', 'cancelled' => 'secondary', default => 'warning',
        };
      @endphp
      <tr>
        <td class="fw-700"><a href="{{ route('requisitions.show', $req) }}">{{ $req->requisition_number }}</a></td>
        <td>{{ $req->requester->name ?? '—' }}@if($req->department)<div class="fs-11 text-muted">{{ $req->department }}</div>@endif</td>
        <td>{{ $req->items->count() }}</td>
        <td>{{ $req->required_date ? $req->required_date->format('d M Y') : '—' }}</td>
        <td>
          @if($req->priority === 'high')<span class="bp-badge bp-badge-danger">High</span>
          @elseif($req->priority === 'low')<span class="bp-badge bp-badge-secondary">Low</span>
          @else<span class="bp-badge bp-badge-info">Normal</span>@endif
        </td>
        <td><span class="bp-badge bp-badge-{{ $statusClass }}">{{ ucfirst($req->status) }}</span></td>
        <td>
          <div class="dropdown">
            <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="{{ route('requisitions.show', $req) }}"><i class="fa-solid fa-eye me-2"></i>View</a></li>
              @if($req->isEditable())
                @bpCan('purchases.edit')
                <li><a class="dropdown-item" href="{{ route('requisitions.edit', $req) }}"><i class="fa-solid fa-pen me-2"></i>Edit</a></li>
                @endbpCan
              @endif
              @if(!in_array($req->status, ['ordered','fulfilled']))
                @bpCan('purchases.delete')
                <li><hr class="dropdown-divider"></li>
                <li>
                  <form action="{{ route('requisitions.destroy', $req) }}" method="POST">
                    @csrf @method('DELETE')
                    <button type="submit" class="dropdown-item text-danger delete-confirm" data-name="{{ $req->requisition_number }}"><i class="fa-solid fa-trash me-2"></i>Delete</button>
                  </form>
                </li>
                @endbpCan
              @endif
            </ul>
          </div>
        </td>
      </tr>
    @empty
      <x-core::table.empty colspan="7" icon="fa-solid fa-clipboard-list" title="No requisitions yet" />
    @endforelse
  </tbody>

  <x-slot:pagination>
    <x-core::table.pagination :paginator="$requisitions" itemLabel="requisitions" />
  </x-slot:pagination>
</x-core::table>

@endsection
