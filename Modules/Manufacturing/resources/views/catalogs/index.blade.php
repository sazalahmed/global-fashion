@extends('core::layouts.master')

@section('title', __("Catalogs"))
@section('page-title', __("Catalogs"))

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Manufacturing</span>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Catalogs</span>
@endsection

@section('page-actions')
  @bpCan('manufacturing.create')
  <a href="{{ route('manufacturing.catalogs.create') }}" class="bp-btn bp-btn-primary">
    <i class="fa-solid fa-plus"></i> Add Catalog
  </a>
  @endbpCan
@endsection

@section('content')

  <x-core::table>
    <x-slot:filters>
      <x-core::table.filter-bar searchPlaceholder="Search catalog name...">
        <select class="bp-form-select" name="is_active">
          <option value="">All Status</option>
          <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Active</option>
          <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactive</option>
        </select>
      </x-core::table.filter-bar>
    </x-slot:filters>

    <x-core::table.header>
      <x-core::table.column>Code</x-core::table.column>
      <x-core::table.column>Name</x-core::table.column>
      <x-core::table.column>Description</x-core::table.column>
      <x-core::table.column>Sort Order</x-core::table.column>
      <x-core::table.column>Status</x-core::table.column>
      <x-core::table.column>Actions</x-core::table.column>
    </x-core::table.header>

    <tbody>
      @forelse($catalogs as $catalog)
      <tr>
        <td class="fw-600 fs-12">{{ $catalog->code }}</td>
        <td class="fw-700 fs-13">{{ $catalog->name }}</td>
        <td class="fs-12 text-muted">{{ $catalog->description ?? '' }}</td>
        <td>{{ $catalog->sort_order ?? 0 }}</td>
        <td>
          <x-core::status-toggle :url="route('manufacturing.catalogs.toggle-status', $catalog->id)" :active="$catalog->is_active" />
        </td>
        <td>
          @bpCanAny('manufacturing.edit', 'manufacturing.delete')
          <div class="dropdown">
            <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
            <ul class="dropdown-menu dropdown-menu-end">
              @bpCan('manufacturing.edit')
              <li><a class="dropdown-item" href="{{ route('manufacturing.catalogs.edit', $catalog) }}"><i class="fa-solid fa-pen"></i> Edit</a></li>
              @endbpCan
              @bpCan('manufacturing.delete')
              <li><hr class="dropdown-divider"></li>
              <li>
                <form action="{{ route('manufacturing.catalogs.destroy', $catalog) }}" method="POST" class="d-inline">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Are you sure you want to delete {{ $catalog->name }}?')"><i class="fa-solid fa-trash"></i> Delete</button>
                </form>
              </li>
              @endbpCan
            </ul>
          </div>
          @endbpCanAny
        </td>
      </tr>
      @empty
      <x-core::table.empty colspan="6" icon="fa-solid fa-book" title="No catalogs found." />
      @endforelse
    </tbody>

    <x-slot:pagination>
      <x-core::table.pagination :paginator="$catalogs" itemLabel="catalogs" />
    </x-slot:pagination>
  </x-core::table>

@endsection
